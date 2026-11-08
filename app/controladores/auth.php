
<?php

function validar_clave_nueva(string $c): ?string
{
    if (strlen($c) < 8) {
        return 'Usa al menos 8 caracteres.';
    }

    if (strlen($c) > 72) {
        return 'Usa como máximo 72 caracteres.';
    }

    if (
        !preg_match('/[A-Za-z]/', $c) ||
        !preg_match('/\d/', $c)
    ) {
        return 'Incluye al menos una letra y un número.';
    }

    return null;
}


function intentos_recientes(
    string $tipo,
    string $col,
    string $val,
    int $min
): int {
    if (!in_array($col, ['identificador', 'ip'], true)) {
        throw new InvalidArgumentException('columna');
    }

    $s = db()->prepare(
        "SELECT COUNT(*)
         FROM intentos_login
         WHERE tipo = ?
           AND $col = ?
           AND creado_en > (NOW() - INTERVAL " . (int)$min . " MINUTE)"
    );

    $s->execute([$tipo, $val]);

    return (int)$s->fetchColumn();
}


function registrar_intento(string $tipo, string $ident): void
{
    db()->prepare(
        'INSERT INTO intentos_login (tipo, identificador, ip)
         VALUES (?, ?, ?)'
    )->execute([
        $tipo,
        mb_strimwidth_seguro($ident),
        ip_cliente()
    ]);

    if (random_int(1, 100) === 1) {
        db()->exec(
            'DELETE FROM intentos_login
             WHERE creado_en < (NOW() - INTERVAL 1 DAY)'
        );
    }
}


function mb_strimwidth_seguro(string $s): string
{
    return substr($s, 0, 150);
}


function crear_token(int $uid, string $tipo, int $minutos): string
{
    db()->prepare(
        'UPDATE tokens_usuario
         SET usado_en = NOW()
         WHERE usuario_id = ?
           AND tipo = ?
           AND usado_en IS NULL'
    )->execute([$uid, $tipo]);

    $raw = bin2hex(random_bytes(32));

    db()->prepare(
        'INSERT INTO tokens_usuario
            (usuario_id, tipo, token_hash, expira_en)
         VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    )->execute([
        $uid,
        $tipo,
        hash('sha256', $raw),
        $minutos
    ]);

    return $raw;
}


function token_valido(string $raw, string $tipo): bool
{
    $s = db()->prepare(
        'SELECT 1
         FROM tokens_usuario
         WHERE token_hash = ?
           AND tipo = ?
           AND usado_en IS NULL
           AND expira_en > NOW()'
    );

    $s->execute([
        hash('sha256', $raw),
        $tipo
    ]);

    return (bool)$s->fetchColumn();
}


function consumir_token(string $raw, string $tipo): ?int
{
    $h = hash('sha256', $raw);

    $s = db()->prepare(
        'SELECT id, usuario_id
         FROM tokens_usuario
         WHERE token_hash = ?
           AND tipo = ?
           AND usado_en IS NULL
           AND expira_en > NOW()'
    );

    $s->execute([$h, $tipo]);

    $t = $s->fetch();

    if (!$t) {
        return null;
    }

    $u = db()->prepare(
        'UPDATE tokens_usuario
         SET usado_en = NOW()
         WHERE id = ?
           AND usado_en IS NULL'
    );

    $u->execute([$t['id']]);

    return $u->rowCount() === 1
        ? (int)$t['usuario_id']
        : null;
}


function enviar_verificacion(array $u): void
{
    $tok = crear_token((int)$u['id'], 'verificar', 60 * 24);

    enviar_correo(
        $u['email'],
        'Verifica tu correo en ReUns',
        "Hola {$u['nombre']},\n\n" .
        "Confirma tu correo aquí (vale 24 horas):\n" .
        url_app('/paginas/verificar.php?token=' . $tok)
    );
}


function registrar_usuario(array $in): array
{
    $n = trim((string)($in['nombre'] ?? ''));
    $em = strtolower(trim((string)($in['email'] ?? '')));
    $c = (string)($in['clave'] ?? '');
    $c2 = (string)($in['clave2'] ?? '');
    $err = [];

    if (!preg_match('/^.{2,60}$/u', $n)) {
        $err['nombre'] = 'Escribe tu nombre (2 a 60 caracteres).';
    }

    if (
        strlen($em) > 150 ||
        !filter_var($em, FILTER_VALIDATE_EMAIL)
    ) {
        $err['email'] = 'Escribe un correo válido, por ejemplo nombre@correo.com.';
    } elseif (!str_ends_with($em, '@uns.edu.pe')) {
        $err['email'] = 'Solo se permite el registro con el correo institucional (@uns.edu.pe).';
    }

    if ($m = validar_clave_nueva($c)) {
        $err['clave'] = $m;
    }

    if ($c !== $c2) {
        $err['clave2'] = 'Las contraseñas no coinciden.';
    }

    if (empty($in['acepto'])) {
        $err['acepto'] = 'Debes aceptar los términos y la privacidad.';
    }

    if ($err) {
        return [$err, null];
    }

    if (intentos_recientes('registro', 'ip', ip_cliente(), 60) >= 5) {
        return [
            ['general' => 'Demasiados registros desde esta conexión. Inténtalo en una hora.'],
            null
        ];
    }

    registrar_intento('registro', $em);

    if (usuario_por_email($em)) {
        return [
            ['email' => 'Ese correo ya está registrado. Prueba iniciar sesión.'],
            null
        ];
    }

    try {
        $id = crear_usuario(
            $n,
            $em,
            password_hash($c, PASSWORD_DEFAULT)
        );
    } catch (PDOException $x) {
        if ($x->getCode() === '23000') {
            return [
                ['email' => 'Ese correo ya está registrado. Prueba iniciar sesión.'],
                null
            ];
        }

        throw $x;
    }

    auditar($id, 'registro');
    enviar_verificacion(usuario_por_id($id));

    return [[], $id];
}


function verificar_correo(string $tok): bool
{
    $uid = consumir_token($tok, 'verificar');

    if (!$uid) {
        return false;
    }

    db()->prepare(
        'UPDATE usuarios
         SET email_verificado_en = COALESCE(email_verificado_en, NOW())
         WHERE id = ?'
    )->execute([$uid]);

    auditar($uid, 'correo_verificado');

    return true;
}


function abrir_sesion(array $u): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['uid'] = (int)$u['id'];
    $_SESSION['ver'] = (int)$u['version_sesion'];
    $_SESSION['ultimo'] = time();
}


function iniciar_login(string $email, string $clave): ?string
{
    $em = strtolower(trim($email));

    if (
        intentos_recientes('login', 'identificador', $em, 15) >= 5 ||
        intentos_recientes('login', 'ip', ip_cliente(), 15) >= 30
    ) {
        return 'Demasiados intentos. Espera 15 minutos e inténtalo de nuevo.';
    }

    $u = usuario_por_email($em);

    if ($u) {
        $ok = password_verify($clave, $u['clave_hash']);
    } else {
        password_hash($clave, PASSWORD_DEFAULT);
        $ok = false;
    }

    if (!$ok) {
        registrar_intento('login', $em);
        auditar($u['id'] ?? null, 'login_fallido');

        return 'Correo o contraseña incorrectos.';
    }

    if ($u['estado'] !== 'activo') {
        auditar((int)$u['id'], 'login_bloqueado', 'cuenta suspendida');

        return 'Tu cuenta está suspendida. Escribe a soporte para más información.';
    }

    if (password_needs_rehash($u['clave_hash'], PASSWORD_DEFAULT)) {
        db()->prepare(
            'UPDATE usuarios SET clave_hash = ? WHERE id = ?'
        )->execute([
            password_hash($clave, PASSWORD_DEFAULT),
            $u['id']
        ]);
    }

    db()->prepare(
        'UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?'
    )->execute([$u['id']]);

    db()->prepare(
        "DELETE FROM intentos_login
         WHERE tipo = 'login'
           AND identificador = ?"
    )->execute([$em]);

    abrir_sesion($u);
    auditar((int)$u['id'], 'login');

    return null;
}


function cerrar_sesion(): void
{
    if (!empty($_SESSION['uid'])) {
        auditar((int)$_SESSION['uid'], 'logout');
    }

    $_SESSION = [];

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}


function solicitar_recuperacion(string $email): void
{
    $em = strtolower(trim($email));

    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    if (
        intentos_recientes('recuperar', 'ip', ip_cliente(), 60) >= 5 ||
        intentos_recientes('recuperar', 'identificador', $em, 60) >= 3
    ) {
        return;
    }

    registrar_intento('recuperar', $em);

    $u = usuario_por_email($em);

    if (!$u || $u['estado'] !== 'activo') {
        return;
    }

    $tok = crear_token((int)$u['id'], 'recuperar', 60);

    enviar_correo(
        $em,
        'Restablece tu contraseña de ReUns',
        "Hola {$u['nombre']},\n\n" .
        "Crea una contraseña nueva aquí (vale 1 hora):\n" .
        url_app('/paginas/restablecer.php?token=' . $tok) .
        "\n\nSi no lo pediste, ignora este mensaje."
    );

    auditar((int)$u['id'], 'recuperacion_solicitada');
}


function restablecer_clave(string $tok, string $c, string $c2): array
{
    $err = [];

    if ($m = validar_clave_nueva($c)) {
        $err['clave'] = $m;
    }

    if ($c !== $c2) {
        $err['clave2'] = 'Las contraseñas no coinciden.';
    }

    if ($err) {
        return $err;
    }

    $uid = consumir_token($tok, 'recuperar');

    if (!$uid) {
        return ['general' => 'El enlace no es válido o ya venció. Pide uno nuevo.'];
    }

    cambiar_clave_usuario($uid, password_hash($c, PASSWORD_DEFAULT));
    auditar($uid, 'clave_restablecida');

    return [];
}


function cambiar_clave(
    array $u,
    string $actual,
    string $c,
    string $c2
): array {
    $err = [];

    if (!password_verify($actual, $u['clave_hash'])) {
        $err['actual'] = 'La contraseña actual no es correcta.';
    }

    if ($m = validar_clave_nueva($c)) {
        $err['clave'] = $m;
    }

    if ($c !== $c2) {
        $err['clave2'] = 'Las contraseñas no coinciden.';
    }

    if ($err) {
        return $err;
    }

    $_SESSION['ver'] = cambiar_clave_usuario(
        (int)$u['id'],
        password_hash($c, PASSWORD_DEFAULT)
    );

    auditar((int)$u['id'], 'clave_cambiada');

    return [];
}


function cerrar_otras_sesiones(array $u): void
{
    $_SESSION['ver'] = subir_version_sesion((int)$u['id']);

    auditar((int)$u['id'], 'sesiones_cerradas');
}


function actualizar_perfil(array $u, array $in): array
{
    $n = trim((string)($in['nombre'] ?? ''));
    $ub = trim((string)($in['ubicacion'] ?? ''));
    $bio = trim((string)($in['bio'] ?? ''));
    $err = [];

    if (!preg_match('/^.{2,60}$/u', $n)) {
        $err['nombre'] = 'Escribe tu nombre (2 a 60 caracteres).';
    }

    if (!preg_match('/^.{0,80}$/u', $ub)) {
        $err['ubicacion'] = 'Máximo 80 caracteres.';
    }

    if (!preg_match('/^.{0,300}$/us', $bio)) {
        $err['bio'] = 'Máximo 300 caracteres.';
    }

    if ($err) {
        return $err;
    }

    db()->prepare(
        'UPDATE usuarios
         SET nombre = ?, ubicacion = ?, bio = ?
         WHERE id = ?'
    )->execute([
        $n,
        $ub ?: null,
        $bio ?: null,
        $u['id']
    ]);

    auditar((int)$u['id'], 'perfil_actualizado');

    return [];
}