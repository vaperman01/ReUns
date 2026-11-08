<?php 
require_once __DIR__ . '/../../app/ayudas/bootstrap.php';
require_once APP . '/modelos/negociacion.php';
require_once APP . '/modelos/producto.php';

$p = producto_por_id((int)($_GET['id'] ?? 0)); 
$usr = usuario_actual();

// Verificaciones seguras
$es_admin_o_mod = $usr && isset($usr['rol']) && in_array($usr['rol'], ['admin', 'moderador'], true);
$gestiona = $usr && $p && function_exists('puede_editar_producto') ? puede_editar_producto($usr, $p) : $es_admin_o_mod; 
$moder = $es_admin_o_mod;
$es_dueno_ver = $usr && $p && (int)$usr['id'] === (int)$p['usuario_id'] && $p['estado'] !== 'eliminado'; 

$es_publico = function_exists('producto_publico') ? producto_publico($p) : ($p && $p['estado'] === 'activo');

if (!$p || !($es_publico || $gestiona || $moder || $es_dueno_ver)) {
    pagina_error(404, 'Este producto no existe', 'Puede haber sido eliminado o pausado por su vendedor.');
}

$titulo = $p['titulo']; 
require APP . '/vistas/cabecera.php';

$cats = categorias(); 
[$cn, $ci] = $cats[$p['categoria']] ?? ['Otros', '📦']; 
$imgs = function_exists('imagenes_de_producto') ? imagenes_de_producto((int)$p['id']) : ($p['imagenes'] ?? []);
$es_dueno = $usr && (int)$usr['id'] === (int)$p['usuario_id']; 
$activo = $p['estado'] === 'activo';
$login = BASE . '/paginas/login.php?volver=' . urlencode($_SERVER['REQUEST_URI'] ?? ''); 
$desc = isset($p['precio_anterior']) && $p['precio_anterior'] !== null && (float)$p['precio_anterior'] > (float)$p['precio'];
$volver = BASE . '/paginas/producto.php?id=' . (int)$p['id']; 

// Cálculo seguro de la fecha/días
$tiempo_publicado = 'recientemente';
if (isset($p['dias']) && function_exists('hace_dias')) {
    $tiempo_publicado = hace_dias((int)$p['dias']);
} elseif (isset($p['creado_en'])) {
    $tiempo_publicado = $p['creado_en'];
}
?>
<div class="contenedor">
  <p class="migas"><a href="<?= BASE ?>/index.php">Inicio</a> › <a href="catalogo.php?cat=<?= e($p['categoria']) ?>"><?= e($cn) ?></a> › <?= e($p['titulo']) ?></p>
  
  <div class="detalle">
    <div class="galeria">
      <div class="detalle__img" style="background:<?= function_exists('color_categoria') ? e(color_categoria($p['categoria'])) : '#f8f9fa' ?>">
        <?php if (!empty($imgs)): ?>
            <img id="foto-principal" src="<?= BASE ?>/uploads/productos/<?= e($imgs[0]['archivo']) ?>" alt="Foto de <?= e($p['titulo']) ?>">
        <?php else: ?>
            <span role="img" aria-label="Sin fotos"><?= $ci ?></span>
        <?php endif; ?>
      </div>
      
      <?php if (count($imgs) > 1): ?>
        <div class="miniaturas" aria-label="Fotos del producto">
            <?php foreach ($imgs as $i => $img): ?>
                <a href="<?= BASE ?>/uploads/productos/<?= e($img['archivo']) ?>" data-galeria<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                    <img src="<?= BASE ?>/uploads/productos/<?= e($img['archivo']) ?>" alt="Ver foto <?= $i + 1 ?>" style="width: 70px; height: 70px; object-fit: cover;">
                </a>
            <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    
    <aside class="panel">
      <?php if (!$activo): ?>
        <p class="aviso <?= $p['estado'] === 'vendido' ? 'aviso--info' : 'aviso--error' ?>">
            <?= ['vendido' => 'Este producto ya fue vendido.', 'pausado' => 'Pausado: solo tú puedes verlo.', 'oculto' => 'Moderación ocultó este producto. Si crees que es un error, escribe a soporte.', 'eliminado' => 'Eliminado.'][$p['estado']] ?? '' ?>
        </p>
      <?php endif; ?>
      
      <h1 style="font-size:1.5rem"><?= e($p['titulo']) ?></h1>
      <p class="meta">
          <?= e($p['ubicacion'] ?? 'Campus Universitario') ?> · Publicado <?= e($tiempo_publicado) ?>
      </p>
      <p class="precio">
          <?= (float)$p['precio'] > 0 ? (function_exists('soles') ? e(soles((float)$p['precio'])) : 'S/ ' . number_format((float)$p['precio'], 2)) : 'Solo intercambio' ?> 
          <?php if ($desc): ?><s><?= function_exists('soles') ? e(soles((float)$p['precio_anterior'])) : 'S/ ' . number_format((float)$p['precio_anterior'], 2) ?></s><?php endif; ?>
      </p>
      
      <?php if ($activo && !$es_dueno): 
        $venta = isset($p['modalidad']) ? $p['modalidad'] !== 'intercambio' : true; 
        $trueque = isset($p['modalidad']) ? $p['modalidad'] !== 'venta' : false;
        if (!$usr): 
      ?>
          <?php if ($venta): ?>
              <a class="btn btn--primario btn--bloque" href="<?= e($login) ?>">Comprar</a>
              <a class="btn btn--linea btn--bloque" href="<?= e($login) ?>">Hacer una oferta</a>
          <?php endif; ?>
          <?php if ($trueque): ?>
              <a class="btn btn--ambar btn--bloque" href="<?= e($login) ?>">🔄 Proponer intercambio</a>
          <?php endif; ?>
          <a class="btn btn--linea btn--bloque" href="<?= e($login) ?>">Chatear con el vendedor</a>
      <?php else: ?>
          <?php if ($venta): ?>
              <button class="btn btn--primario btn--bloque" type="button" disabled>Comprar</button>
              <button class="btn btn--linea btn--bloque" type="button" disabled>Hacer una oferta</button>
          <?php endif; ?>
          <?php if ($trueque): ?>
              <button class="btn btn--ambar btn--bloque" type="button" disabled>🔄 Proponer intercambio</button>
          <?php endif; ?>
          <button class="btn btn--linea btn--bloque" type="button" disabled>Chatear con el vendedor</button>
          <p class="aviso aviso--info" style="margin-top:16px">Comprar, ofertar e intercambiar se activan en la Etapa 5; el chat, en la Etapa 6.</p>
      <?php endif; endif; ?>
      
      <?php if ($gestiona): ?>
        <h2 style="font-size:1.05rem;margin:18px 0 0"><?= $es_dueno ? 'Tu publicación' : 'Moderación' ?></h2>
        <?php 
        $ruta_acciones = __DIR__ . '/../../app/vistas/acciones_producto.php';
        if (file_exists($ruta_acciones)) {
            require $ruta_acciones;
        }
        ?>
      <?php endif; ?>
      
      <?php 
      $repVendedor = function_exists('resumen_reputacion_usuario') ? resumen_reputacion_usuario((int)$p['usuario_id']) : ['total' => 0, 'promedio' => 0.0];
      ?>
      <div class="vendedor">
        <?= avatar($p['vendedor'] ?? 'Usuario', $p['vendedor_foto'] ?? null, 48) ?>
        <div>
          <a href="<?= BASE ?>/paginas/vendedor.php?id=<?= (int)$p['usuario_id'] ?>" style="font-weight:600;text-decoration:none;color:var(--tinta)"><?= e($p['vendedor'] ?? 'Usuario ReUns') ?></a>
          <br>
          <span class="meta">
            <?php if ($repVendedor['total'] > 0): ?>
              ⭐ <?= number_format($repVendedor['promedio'], 1) ?> (<?= $repVendedor['total'] ?>) · 
            <?php endif; ?>
            Miembro de la comunidad
          </span>
        </div>
      </div>
    </aside>
  </div>
  
  <section class="panel">
    <h2>Descripción</h2>
    <p style="white-space:pre-line;margin:0"><?= e($p['descripcion']) ?></p>
  </section>
</div>

<?php require APP . '/vistas/pie.php'; ?>