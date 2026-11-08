<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$uid = (int)$u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    if (($_POST['accion'] ?? '') === 'todas') {
        marcar_todas_leidas($uid);
        flash('ok', 'Todas las notificaciones quedaron como leídas.');
    } elseif (($_POST['accion'] ?? '') === 'leer') {
        marcar_notificacion_leida((int)($_POST['id'] ?? 0), $uid);   // solo afecta a las del propio usuario
    }
    redirigir(BASE . '/paginas/notificaciones.php');
}

$lista = notificaciones_de($uid, 50);
$sinLeer = contar_notificaciones_no_leidas($uid);

$titulo = 'Notificaciones';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">

<div class="contenedor" style="padding:24px 0">
    <div class="seccion__cab">
        <h1 style="font-size:1.6rem">Notificaciones <?= $sinLeer ? '<b class="insignia">' . $sinLeer . '</b>' : '' ?></h1>
        <?php if ($sinLeer): ?>
            <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="todas">
                <button class="btn btn--linea" type="submit">Marcar todas como leídas</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$lista): ?>
        <div class="vacio">
            <h2>No tienes notificaciones</h2>
            <p>Te avisaremos de ofertas, propuestas de intercambio, mensajes y cambios en tus operaciones.</p>
        </div>
    <?php else: ?>
        <ul class="notifs">
            <?php foreach ($lista as $n): ?>
                <li class="notif <?= $n['leida_en'] ? '' : 'notif--nueva' ?>">
                    <span class="notif__icono" aria-hidden="true"><?= $n['tipo'] === 'mensaje' ? '💬' : ($n['tipo'] === 'operacion' ? '🤝' : '🔔') ?></span>
                    <div class="notif__cuerpo">
                        <p><?= e($n['texto']) ?></p>
                        <time class="meta"><?= e(date('d/m/Y H:i', strtotime($n['creado_en']))) ?></time>
                    </div>
                    <div class="notif__acciones">
                        <?php if ($n['url']): ?><a class="btn btn--linea" href="<?= BASE . e($n['url']) ?>">Ver</a><?php endif; ?>
                        <?php if (!$n['leida_en']): ?>
                            <form method="post">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="accion" value="leer">
                                <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                                <button class="btn btn--linea" type="submit">Marcar leída</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require APP . '/vistas/pie.php'; ?>
