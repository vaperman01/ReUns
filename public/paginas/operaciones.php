<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$uid = (int)$u['id'];

$pestanas = ['recibidas', 'enviadas', 'historial'];
$ver = in_array($_GET['ver'] ?? '', $pestanas, true) ? $_GET['ver'] : 'recibidas';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $tipoOp = (string)($_POST['tipo'] ?? '');
    $idOp = (int)($_POST['id'] ?? 0);
    $accion = (string)($_POST['accion'] ?? '');

    if ($accion === 'calificar') {
        $puntuacion = (int)($_POST['puntuacion'] ?? 0);
        $comentario = (string)($_POST['comentario'] ?? '');
        [$cid, $err] = calificar_operacion($tipoOp, $idOp, $uid, $puntuacion, $comentario);
        flash($err ? 'error' : 'ok', $err ?? '¡Gracias! Tu calificación y reseña fueron registradas.');
        redirigir(BASE . '/paginas/operaciones.php?ver=historial#op-' . $tipoOp . '-' . $idOp);
    }

    $motivo = (string)($_POST['motivo'] ?? '');
    if ($tipoOp === 'oferta') {
        $err = gestionar_oferta($idOp, $uid, $accion, $motivo !== '' ? $motivo : null);
    } elseif ($tipoOp === 'intercambio') {
        $err = gestionar_intercambio($idOp, $uid, $accion, $motivo !== '' ? $motivo : null);
    } else {
        $err = 'Operación no válida.';
    }

    flash($err ? 'error' : 'ok', $err ?? 'Listo, operación actualizada.');
    redirigir(BASE . '/paginas/operaciones.php?ver=' . $ver);
}

$pendientes = contar_pendientes_recibidas($uid);
$filtros = ['todas' => 'Todas', 'compras' => 'Mis compras', 'ventas' => 'Mis ventas', 'ofertas' => 'Ofertas', 'intercambios' => 'Intercambios'];
$filtro = array_key_exists($_GET['f'] ?? '', $filtros) ? $_GET['f'] : 'todas';

if ($ver === 'historial') {
    $lista = historial_de($uid, $filtro);
} else {
    $lista = array_merge(
        ofertas_de($uid, $ver, 'abiertas'),
        intercambios_de($uid, $ver, 'abiertas')
    );
    usort($lista, fn($a, $b) => strcmp($b['actualizado_en'], $a['actualizado_en']) ?: ($b['id'] <=> $a['id']));
}

$todosCambios = [
    'oferta' => cambios_de('oferta', array_column(array_filter($lista, fn($x) => $x['tipo'] === 'oferta'), 'id')),
    'intercambio' => cambios_de('intercambio', array_column(array_filter($lista, fn($x) => $x['tipo'] === 'intercambio'), 'id')),
];

$todasCalificaciones = [
    'oferta' => function_exists('calificaciones_por_operaciones') ? calificaciones_por_operaciones('oferta', array_column(array_filter($lista, fn($x) => $x['tipo'] === 'oferta'), 'id')) : [],
    'intercambio' => function_exists('calificaciones_por_operaciones') ? calificaciones_por_operaciones('intercambio', array_column(array_filter($lista, fn($x) => $x['tipo'] === 'intercambio'), 'id')) : [],
];

$titulo = 'Operaciones';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor" style="padding:24px 0">
    <h1 style="font-size:1.6rem">Operaciones</h1>

    <nav class="op-tabs" aria-label="Operaciones">
        <a href="?ver=recibidas" <?= $ver === 'recibidas' ? 'aria-current="page"' : '' ?>>
            Recibidas<?= $pendientes ? ' <b>' . $pendientes . '</b>' : '' ?>
        </a>
        <a href="?ver=enviadas" <?= $ver === 'enviadas' ? 'aria-current="page"' : '' ?>>Enviadas</a>
        <a href="?ver=historial" <?= $ver === 'historial' ? 'aria-current="page"' : '' ?>>Historial</a>
    </nav>

    <?php if ($ver === 'historial'): ?>
        <nav class="op-filtros" aria-label="Filtrar historial">
            <?php foreach ($filtros as $k => $n): ?>
                <a href="?ver=historial&f=<?= e($k) ?>" <?= $filtro === $k ? 'aria-current="true"' : '' ?>><?= e($n) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <?php if (!$lista): ?>
        <div class="vacio">
            <h2>
                <?= match ($ver) {
                    'recibidas' => 'No tienes solicitudes pendientes',
                    'enviadas' => 'No tienes solicitudes en curso',
                    default => 'Todavía no hay operaciones en tu historial',
                } ?>
            </h2>
            <p>
                <?= $ver === 'historial'
                    ? 'Aquí verás tus compras, ventas, ofertas e intercambios terminados.'
                    : 'Las ofertas y propuestas de intercambio aparecerán aquí.' ?>
            </p>
            <a class="btn btn--primario" href="<?= BASE ?>/paginas/catalogo.php">Ver catálogo</a>
        </div>
    <?php else: ?>
        <?php foreach ($lista as $op):
            $cambios = $todosCambios[$op['tipo']][$op['id']] ?? [];
            $calificacionesOp = $todasCalificaciones[$op['tipo']][$op['id']] ?? [];
            require APP . '/vistas/fila_operacion.php';
        endforeach; ?>
    <?php endif; ?>
</div>

<script src="<?= BASE ?>/assets/js/operaciones.js"></script>
<?php require APP . '/vistas/pie.php'; ?>
