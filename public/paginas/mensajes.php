<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$lista = conversaciones_de((int)$u['id']);

$titulo = 'Mensajes';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">

<div class="contenedor" style="padding:24px 0">
    <h1 style="font-size:1.6rem">Mensajes</h1>

    <?php if (!$lista): ?>
        <div class="vacio">
            <h2>Todavía no tienes conversaciones</h2>
            <p>Abre un producto y pulsa «Chatear con el vendedor» para empezar.</p>
            <a class="btn btn--primario" href="<?= BASE ?>/paginas/catalogo.php">Ver catálogo</a>
        </div>
    <?php else: ?>
        <ul class="convs">
            <?php foreach ($lista as $c): ?>
                <li>
                    <a class="conv <?= (int)$c['no_leidos'] > 0 ? 'conv--nuevo' : '' ?>" href="<?= BASE ?>/paginas/conversacion.php?c=<?= (int)$c['id'] ?>">
                        <?php if (!empty($c['p_imagen'])): ?>
                            <img src="<?= BASE ?>/uploads/productos/<?= e($c['p_imagen']) ?>" alt="">
                        <?php else: ?>
                            <span class="chat__ph" aria-hidden="true">📦</span>
                        <?php endif; ?>
                        <span class="conv__cuerpo">
                            <strong><?= e($c['otro_nombre']) ?></strong>
                            <span class="meta"> · <?= e($c['p_titulo']) ?></span>
                            <span class="conv__ultimo"><?= $c['ultimo_texto'] !== null ? e(mb_strimwidth($c['ultimo_texto'], 0, 90, '…', 'UTF-8')) : 'Sin mensajes todavía' ?></span>
                        </span>
                        <span class="conv__lado">
                            <?php if ($c['ultimo_mensaje_en']): ?><time class="meta"><?= e(date('d/m H:i', strtotime($c['ultimo_mensaje_en']))) ?></time><?php endif; ?>
                            <?php if ((int)$c['no_leidos'] > 0): ?><b class="insignia"><?= (int)$c['no_leidos'] ?></b><?php endif; ?>
                        </span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require APP . '/vistas/pie.php'; ?>
