<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();
$lista = favoritos_de((int)$u['id']);

$titulo = 'Mis favoritos';
require APP . '/vistas/cabecera.php';
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/social.css">

<div class="contenedor" style="padding:24px 0">
    <h1 style="font-size:1.6rem">Mis favoritos</h1>

    <?php if (!$lista): ?>
        <div class="vacio">
            <h2>Aún no guardaste productos</h2>
            <p>En la página de un producto pulsa «Guardar en favoritos» para encontrarlo aquí después.</p>
            <a class="btn btn--primario" href="<?= BASE ?>/paginas/catalogo.php">Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach ($lista as $p): ?>
                <div class="fav">
                    <?php require APP . '/vistas/tarjeta_producto.php'; ?>
                    <form method="post" action="<?= BASE ?>/paginas/favorito_accion.php">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="accion" value="quitar">
                        <input type="hidden" name="producto_id" value="<?= (int)$p['id'] ?>">
                        <input type="hidden" name="volver" value="<?= BASE ?>/paginas/favoritos.php">
                        <button class="btn btn--linea btn--bloque" type="submit">♥ Quitar de favoritos</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require APP . '/vistas/pie.php'; ?>
