<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$id = (int)($_GET['id'] ?? 0);
$tipo = ($_GET['tipo'] ?? '') === 'oferta' ? 'oferta' : 'compra';

$p = producto_por_id($id);

if (!$p || ($p['estado'] ?? '') !== 'activo' || ($p['vendedor_estado'] ?? '') !== 'activo') {
    pagina_error(404, 'Este producto no está disponible', 'Puede haber sido vendido, pausado o eliminado.');
}

if ((int)$p['usuario_id'] === (int)$u['id']) {
    pagina_error(403, 'Es tu producto', 'No puedes comprar ni ofertar por tu propia publicación.');
}

if ($p['modalidad'] === 'intercambio' || solo_intercambio($p)) {
    pagina_error(403, 'Solo intercambio', 'Este producto no está a la venta. Puedes proponer un intercambio.');
}

$errores = [];
$valores = ['monto' => '', 'mensaje' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();
    $tipo = ($_POST['tipo'] ?? '') === 'oferta' ? 'oferta' : 'compra';
    $valores = ['monto' => (string)($_POST['monto'] ?? ''), 'mensaje' => (string)($_POST['mensaje'] ?? '')];

    [$oid, $err] = crear_solicitud($id, (int)$u['id'], $tipo, $valores['monto'], $valores['mensaje']);

    if ($oid) {
        flash('ok', $tipo === 'compra'
            ? 'Solicitud enviada. El vendedor te responderá en Operaciones.'
            : 'Oferta enviada. El vendedor te responderá en Operaciones.');
        redirigir(BASE . '/paginas/operaciones.php?ver=enviadas');
    }

    $errores['general'] = $err;
}

$titulo = $tipo === 'compra' ? 'Solicitar compra' : 'Hacer una oferta';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor" style="padding:24px 0">
    <div class="op-form panel">
        <h1 style="font-size:1.5rem"><?= e($titulo) ?></h1>
        <p><strong><?= e($p['titulo']) ?></strong> · <?= e(soles((float)$p['precio'])) ?></p>

        <p class="aviso aviso--info">
            En ReUns no se procesan pagos. El vendedor aceptará tu solicitud y ustedes coordinan
            la entrega; cuando se concrete, ambos lo confirman en la página de Operaciones.
        </p>

        <?php aviso_error($errores); ?>

        <form method="post" novalidate>
            <?= csrf_campo() ?>
            <input type="hidden" name="tipo" value="<?= e($tipo) ?>">

            <?php if ($tipo === 'oferta'): ?>
                <div class="campo">
                    <label for="monto">Tu oferta (S/)</label>
                    <input id="monto" name="monto" type="text" inputmode="decimal" required maxlength="10"
                           placeholder="Máximo <?= e(number_format((float)$p['precio'], 2, '.', '')) ?>"
                           value="<?= e($valores['monto']) ?>">
                </div>
            <?php endif; ?>

            <div class="campo">
                <label for="mensaje">Mensaje para el vendedor (opcional)</label>
                <textarea id="mensaje" name="mensaje" maxlength="300"><?= e($valores['mensaje']) ?></textarea>
            </div>

            <button class="btn btn--primario btn--bloque" type="submit">
                <?= $tipo === 'compra' ? 'Enviar solicitud de compra' : 'Enviar oferta' ?>
            </button>
            <a class="btn btn--linea btn--bloque" href="<?= BASE ?>/paginas/producto.php?id=<?= $id ?>">Volver al producto</a>
        </form>
    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>
