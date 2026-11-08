<?php
/** @var array $p */

$modalidad = $p['modalidad'] ?? 'venta';
$trueque = $modalidad !== 'venta';

$precio = $p['precio'] ?? null;
$precioAnterior = $p['precio_anterior'] ?? null;

$oferta = $precio !== null
    && $precioAnterior !== null
    && (float)$precio > 0
    && (float)$precioAnterior > (float)$precio;

$imagen = $p['imagen'] ?? null;
$titulo = $p['titulo'] ?? 'Producto sin título';
$estado = $p['estado'] ?? 'activo';
$estadoConservacion = $p['estado_producto'] ?? null;
$ubicacion = $p['ubicacion'] ?? '';
$icono = $p['icono'] ?? '📦';

$estadosConservacion = defined('ESTADOS_CONS')
    ? ESTADOS_CONS
    : [];

$estadoTexto = $estadoConservacion !== null
    ? ($estadosConservacion[$estadoConservacion] ?? '')
    : '';

$disponibilidades = defined('DISPONIBILIDAD')
    ? DISPONIBILIDAD
    : [];
?>

<article class="tarjeta">
  <a class="tarjeta__enlace"
     href="<?= BASE ?>/paginas/producto.php?id=<?= (int)($p['id'] ?? 0) ?>">

    <div class="tarjeta__img" style="background:var(--verde-cl)">
      <?php if ($imagen): ?>
        <img
          src="<?= BASE ?>/uploads/productos/<?= e($imagen) ?>"
          alt="<?= e($titulo) ?>"
          loading="lazy">
      <?php else: ?>
        <span aria-hidden="true"><?= e($icono) ?></span>
      <?php endif; ?>

      <?php if ($oferta): ?>
        <b class="etiqueta etiqueta--oferta">
          -<?= round((1 - (float)$precio / (float)$precioAnterior) * 100) ?>%
        </b>
      <?php endif; ?>

      <?php if ($trueque): ?>
        <b class="etiqueta etiqueta--trueque">🔄 Intercambio</b>
      <?php endif; ?>

      <?php if ($estado !== 'activo'): ?>
        <b class="etiqueta etiqueta--estado">
          <?= e($disponibilidades[$estado] ?? $estado) ?>
        </b>
      <?php endif; ?>
    </div>

    <div class="tarjeta__cuerpo">
      <h3><?= e($titulo) ?></h3>

      <p class="precio">
        <?= $precio !== null ? soles((float)$precio) : 'Solo intercambio' ?>

        <?php if ($oferta): ?>
          <s><?= soles((float)$precioAnterior) ?></s>
        <?php endif; ?>
      </p>

      <p class="meta">
        <?= e($estadoTexto) ?><?= $ubicacion !== '' ? ' · '.e($ubicacion) : '' ?>
      </p>
    </div>
  </a>
</article>