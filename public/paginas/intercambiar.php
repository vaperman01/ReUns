<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$id = (int)($_GET['id'] ?? 0);

$p = producto_por_id($id);

if (!$p || ($p['estado'] ?? '') !== 'activo' || ($p['vendedor_estado'] ?? '') !== 'activo') {
    pagina_error(404, 'Este producto no está disponible', 'Puede haber sido vendido, pausado o eliminado.');
}

if ((int)$p['usuario_id'] === (int)$u['id']) {
    pagina_error(403, 'Es tu producto', 'No puedes proponer un intercambio por tu propia publicación.');
}

if ($p['modalidad'] === 'venta') {
    pagina_error(403, 'Solo venta', 'Este producto no admite intercambio.');
}

$mios = productos_para_intercambio((int)$u['id']);
$errores = [];
$mensaje = '';
$elegido = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $elegido = (int)($_POST['ofrecido'] ?? 0);
    $mensaje = (string)($_POST['mensaje'] ?? '');

    [$iid, $err] = proponer_intercambio($id, (int)$u['id'], $elegido, $mensaje);

    if ($iid) {
        flash('ok', 'Propuesta enviada. El dueño te responderá en Operaciones.');
        redirigir(BASE . '/paginas/operaciones.php?ver=enviadas');
    }

    $errores['general'] = $err;
}

$titulo = 'Proponer intercambio';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor" style="padding:24px 0">
    <div class="op-form panel">
        <h1 style="font-size:1.5rem">Proponer intercambio</h1>
        <p>Quieres: <strong><?= e($p['titulo']) ?></strong></p>

        <?php aviso_error($errores); ?>

        <?php if (!$mios): ?>
            <div class="vacio">
                <h2>No tienes productos para ofrecer</h2>
                <p>Publica un producto con modalidad «Intercambio» o «Venta o intercambio» y vuelve aquí.</p>
                <a class="btn btn--primario" href="<?= BASE ?>/paginas/publicar.php">Publicar producto</a>
            </div>
        <?php else: ?>
            <form method="post" novalidate>
                <?= csrf_campo() ?>
                <fieldset style="border:0;padding:0;margin:0">
                    <legend><strong>¿Qué producto ofreces a cambio?</strong></legend>
                    <div class="op-lista">
                        <?php foreach ($mios as $m): ?>
                            <label class="op-opcion">
                                <input type="radio" name="ofrecido" value="<?= (int)$m['id'] ?>" required <?= $elegido === (int)$m['id'] ? 'checked' : '' ?>>
                                <?php if (!empty($m['imagen'])): ?>
                                    <img src="<?= BASE ?>/uploads/productos/<?= e($m['imagen']) ?>" alt="">
                                <?php else: ?>
                                    <span class="op__ph" aria-hidden="true">📦</span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($m['titulo']) ?></strong><br>
                                    <span class="meta">
                                        <?= e(ESTADOS_CONS[$m['estado_producto']] ?? '') ?>
                                        <?= (float)$m['precio'] > 0 ? ' · ' . e(soles((float)$m['precio'])) : '' ?>
                                    </span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <div class="campo">
                    <label for="mensaje">Mensaje (opcional)</label>
                    <textarea id="mensaje" name="mensaje" maxlength="300"><?= e($mensaje) ?></textarea>
                </div>

                <button class="btn btn--ambar btn--bloque" type="submit">🔄 Enviar propuesta</button>
            </form>
        <?php endif; ?>

        <a class="btn btn--linea btn--bloque" href="<?= BASE ?>/paginas/producto.php?id=<?= $id ?>">Volver al producto</a>
    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>
