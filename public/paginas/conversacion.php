<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$uid = (int)$u['id'];
$cid = (int)($_GET['c'] ?? 0);

// Si no eres uno de los dos participantes, para ti la conversación "no existe".
$c = conversacion_de($cid, $uid);
if (!$c) {
    pagina_error(404, 'Conversación no encontrada', 'No existe o no eres parte de ella.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {   // envío sin JavaScript (el JS usa chat_api.php)
    verificar_csrf();
    [, $err] = enviar_mensaje($cid, $uid, (string)($_POST['texto'] ?? ''));
    if ($err) flash('error', $err);
    redirigir(BASE . '/paginas/conversacion.php?c=' . $cid);
}

$mensajes = mensajes_de($cid, $uid, 0) ?? [];
$ultimo = $mensajes ? (int)end($mensajes)['id'] : 0;
$puedeEscribir = $c['p_estado'] !== 'eliminado' && $c['otro_estado'] === 'activo';

$titulo = 'Chat con ' . $c['otro_nombre'];
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">

<div class="contenedor" style="padding:20px 0">
    <div class="chat">
        <header class="chat__cab">
            <a href="<?= BASE ?>/paginas/mensajes.php" aria-label="Volver a mis mensajes">←</a>
            <?php if (!empty($c['p_imagen'])): ?>
                <img src="<?= BASE ?>/uploads/productos/<?= e($c['p_imagen']) ?>" alt="">
            <?php else: ?>
                <span class="chat__ph" aria-hidden="true">📦</span>
            <?php endif; ?>
            <div>
                <strong><?= e($c['otro_nombre']) ?></strong><br>
                <a class="meta" href="<?= BASE ?>/paginas/producto.php?id=<?= (int)$c['producto_id'] ?>"><?= e($c['p_titulo']) ?></a>
                <?php if ($c['p_estado'] !== 'activo'): ?>
                    <span class="meta">· <?= e(DISPONIBILIDAD[$c['p_estado']] ?? $c['p_estado']) ?></span>
                <?php endif; ?>
            </div>
        </header>

        <ol id="mensajes" class="chat__lista" aria-live="polite" aria-label="Mensajes"
            data-ultimo="<?= $ultimo ?>" data-api="<?= BASE ?>/paginas/chat_api.php?c=<?= $cid ?>">
            <?php if (!$mensajes): ?>
                <li class="chat__vacio" id="chat-vacio">Aún no hay mensajes. ¡Saluda y pregunta por el producto!</li>
            <?php endif; ?>
            <?php foreach ($mensajes as $m): ?>
                <li class="msg <?= $m['mio'] ? 'msg--mio' : 'msg--otro' ?>">
                    <p class="msg__texto"><?= e($m['texto']) ?></p>
                    <time><?= e($m['hora']) ?></time>
                </li>
            <?php endforeach; ?>
        </ol>

        <?php if ($puedeEscribir): ?>
            <p id="chat-error" class="aviso aviso--error" role="alert" hidden></p>
            <form id="form-chat" class="chat__form" method="post" action="<?= BASE ?>/paginas/conversacion.php?c=<?= $cid ?>">
                <?= csrf_campo() ?>
                <input type="hidden" name="c" value="<?= $cid ?>">
                <label class="solo-lector" for="texto">Escribe tu mensaje</label>
                <textarea id="texto" name="texto" rows="2" maxlength="<?= CHAT_MAX ?>" required placeholder="Escribe un mensaje…"></textarea>
                <button class="btn btn--primario" type="submit">Enviar</button>
            </form>
        <?php else: ?>
            <p class="aviso aviso--info" style="margin:12px">Ya no se puede escribir en esta conversación.</p>
        <?php endif; ?>
    </div>
</div>

<script src="<?= BASE ?>/assets/js/chat.js"></script>
<?php require APP . '/vistas/pie.php'; ?>
