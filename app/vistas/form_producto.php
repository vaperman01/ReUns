<?php
/** @var array $o */
/** @var ?array $producto */

$v = $o['valores'];
$er = $o['errores'];
$producto ??= null;

// ID numérico de la categoría elegida (POST fallido, edición, o 0 = ninguna)
$catSel = (int)($v['categoria_id'] ?? $producto['categoria_id'] ?? 0);

$todas = categorias_todas();
// Categorías principales (padre_id es NULL)
$padres = array_filter($todas, fn($c) => $c['padre_id'] === null);
?>

<form method="post" enctype="multipart/form-data" data-validar="producto" novalidate>
  <?= csrf_campo(); ?>
  <?php aviso_error($er); ?>

  <?php
  campo('titulo', 'Título', 'text', $o + [
      'attr' => ' maxlength="100"'
  ]);
  ?>

  <div class="campo">
    <label for="categoria_id">Categoría</label>

    <select
      id="categoria_id"
      name="categoria_id"
      required
      <?= isset($er['categoria_id']) ? 'aria-invalid="true"' : '' ?>
    >
      <option value="">Elige…</option>

      <?php foreach ($padres as $padre): ?>
        <optgroup label="<?= e($padre['icono'] . ' ' . $padre['nombre']) ?>">

          <!-- La categoría principal también se puede elegir -->
          <option
            value="<?= (int)$padre['id'] ?>"
            <?= $catSel === (int)$padre['id'] ? 'selected' : '' ?>
          >
            <?= e($padre['nombre']) ?> (General)
          </option>

          <?php foreach ($todas as $hijo): ?>
            <?php if ($hijo['padre_id'] !== null && (int)$hijo['padre_id'] === (int)$padre['id']): ?>
              <option
                value="<?= (int)$hijo['id'] ?>"
                <?= $catSel === (int)$hijo['id'] ? 'selected' : '' ?>
              >
                &nbsp;&nbsp;↳ <?= e($hijo['nombre']) ?>
              </option>
            <?php endif; ?>
          <?php endforeach; ?>

        </optgroup>
      <?php endforeach; ?>
    </select>

    <p class="error"><?= e($er['categoria_id'] ?? '') ?></p>
  </div>

  <?php
  selector(
      'modalidad',
      'Modalidad',
      MODALIDADES,
      $v['modalidad'] ?? $producto['modalidad'] ?? '',
      $er['modalidad'] ?? ''
  );

  echo '<div class="dos campo-precio">';

  campo('precio', 'Precio en soles (S/)', 'text', $o + [
      'req' => false,
      'attr' => ' inputmode="decimal"'
  ]);

  campo('precio_anterior', 'Precio anterior (opcional, muestra oferta)', 'text', $o + [
      'req' => false,
      'attr' => ' inputmode="decimal"'
  ]);

  echo '</div>';

  selector(
      'estado_conservacion',
      'Estado del producto',
      ESTADOS_CONS,
      $v['estado_conservacion'] ?? $producto['estado_producto'] ?? '',
      $er['estado_conservacion'] ?? ''
  );

  campo('ubicacion', 'Ubicación aproximada (distrito o ciudad)', 'text', $o + [
      'req' => false,
      'attr' => ' maxlength="80"'
  ]);
  ?>

  <div class="campo">
    <label for="descripcion">Descripción</label>

    <textarea
      id="descripcion"
      name="descripcion"
      maxlength="2000"
      style="min-height:140px"
    ><?= e($v['descripcion'] ?? $producto['descripcion'] ?? '') ?></textarea>

    <p class="error"><?= e($er['descripcion'] ?? '') ?></p>
  </div>

  <?php if ($producto && !empty($producto['imagenes'])): ?>
    <p style="margin:0 0 6px;font-weight:600;font-size:.92rem">
      Fotos actuales
    </p>

    <div class="fotos-actuales">
      <?php foreach ($producto['imagenes'] as $im): ?>
        <label>
          <img
            src="<?= BASE ?>/uploads/productos/<?= e($im['archivo']) ?>"
            alt="Foto del producto"
          >
          <input
            type="checkbox"
            name="quitar[]"
            value="<?= (int)$im['id'] ?>"
            style="width:auto"
          >
          Quitar
        </label>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="campo">
    <label for="fotos">
      <?= $producto ? 'Agregar fotos' : 'Fotos (1 a 5)' ?>
    </label>

    <input
      id="fotos"
      name="fotos[]"
      type="file"
      multiple
      accept="image/jpeg,image/png,image/webp"
      data-max-mb="3"
      data-max-files="5"
    >

    <p class="error"><?= e($er['fotos'] ?? '') ?></p>

    <p class="meta">
      JPG, PNG o WebP, hasta 3 MB cada una. La primera es la foto principal.
    </p>
  </div>

  <button class="btn btn--primario" type="submit">
    <?= $producto ? 'Guardar cambios' : 'Publicar producto' ?>
  </button>

  <a class="btn btn--linea" href="<?= BASE ?>/paginas/mis_productos.php">
    Cancelar
  </a>
</form>