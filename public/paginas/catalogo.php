<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$f = []; 
foreach (['q', 'cat', 'mod', 'est', 'min', 'max', 'ubicacion', 'oferta', 'orden'] as $k) { 
    $x = trim((string)($_GET[$k] ?? '')); 
    if ($x !== '') {
        $f[$k] = $x; 
    }
}

if (isset($f['orden']) && !in_array($f['orden'], ['recientes', 'precio_asc', 'precio_desc', 'relevancia'], true)) {
    unset($f['orden']);
}

$r = buscar_productos($f, max(1, (int)($_GET['pag'] ?? 1)), 12); 
$lista = $r['items'];

$cat = isset($f['cat']) ? categoria_por_slug($f['cat']) : null;
$titulo = isset($f['q']) ? 'Resultados' : ($cat['nombre'] ?? 'Catálogo'); 
$orden = $f['orden'] ?? (isset($f['q']) ? 'relevancia' : 'recientes');

// 3. Inclusión de la cabecera
require APP . '/vistas/cabecera.php'; 
?>

<div class="contenedor catalogo">
    <!-- Formulario de Filtros -->
    <form class="filtros" method="get" aria-label="Filtros">
        <h2>Filtros</h2>
        <input type="hidden" name="q" value="<?= e($f['q'] ?? '') ?>">
        
        <div class="campo">
            <label for="cat">Categoría</label>
            <select id="cat" name="cat">
                <option value="">Todas</option>
                <?php foreach (categorias_todas() as $c): ?>
                    <option value="<?= e($c['slug']) ?>" <?= ($f['cat'] ?? '') === $c['slug'] ? 'selected' : '' ?>>
                        <?= $c['padre_id'] ? '— ' : '' ?><?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php 
        selector('mod', 'Modalidad', ['venta' => 'Venta', 'intercambio' => 'Intercambio'], $f['mod'] ?? '', '', 'Todas'); 
        selector('est', 'Estado', ESTADOS_CONS, $f['est'] ?? '', '', 'Cualquiera'); 
        ?>

        <div class="dos">
            <div class="campo">
                <label for="min">Mín. (S/)</label>
                <input id="min" name="min" type="number" min="0" value="<?= e($f['min'] ?? '') ?>">
            </div>
            <div class="campo">
                <label for="max">Máx. (S/)</label>
                <input id="max" name="max" type="number" min="0" value="<?= e($f['max'] ?? '') ?>">
            </div>
        </div>

        <div class="campo">
            <label for="ubicacion">Ubicación</label>
            <input id="ubicacion" name="ubicacion" value="<?= e($f['ubicacion'] ?? '') ?>" placeholder="Ej. Chimbote">
        </div>

        <div class="campo">
            <label>
                <input type="checkbox" name="oferta" value="1" <?= isset($f['oferta']) ? 'checked' : '' ?> style="width:auto"> 
                Solo ofertas
            </label>
        </div>

        <?php 
        selector(
            'orden', 
            'Ordenar por', 
            ['relevancia' => 'Relevancia', 'recientes' => 'Más recientes', 'precio_asc' => 'Precio: menor a mayor', 'precio_desc' => 'Precio: mayor a mayor'], 
            $orden, 
            '', 
            'Más recientes'
        ); 
        ?>

        <button class="btn btn--primario btn--bloque" type="submit">Aplicar filtros</button>
        <a class="btn btn--linea btn--bloque" style="margin-top:8px" href="catalogo.php">Limpiar</a>
    </form>

    <!-- Sección de Resultados / Catálogo -->
    <section>
        <h1 style="font-size:1.5rem">
            <?= isset($f['q']) ? 'Resultados para "' . e($f['q']) . '"' : e($cat['nombre'] ?? 'Todos los productos') ?> 
            <small style="color:var(--gris);font:400 1rem Inter">(<?= $r['total'] ?>)</small>
        </h1>

        <?php if (!$lista): ?>
            <div class="vacio">
                <h2>No encontramos productos</h2>
                <p>Prueba con otras palabras o quita algunos filtros.</p>
                <a class="btn btn--primario" href="catalogo.php">Ver todo</a>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($lista as $p) {
                    require APP . '/vistas/tarjeta_producto.php';
                } ?>
            </div>
            <?= paginacion($r['pag'], $r['paginas'], $f) ?>
        <?php endif; ?>
    </section>
</div>

<?php 
require APP . '/vistas/pie.php'; 
?>