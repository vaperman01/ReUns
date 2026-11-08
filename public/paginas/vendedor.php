<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$s = db()->prepare(
    "SELECT id, nombre, foto, ubicacion, bio, creado_en, (email_verificado_en IS NOT NULL) AS verificado 
     FROM usuarios 
     WHERE id = ? AND estado = 'activo'"
); 
$s->execute([(int)($_GET['id'] ?? 0)]); 
$v = $s->fetch();

if (!$v) {
    pagina_error(404, 'Vendedor no encontrado', 'Este perfil no existe o la cuenta no está activa.');
}

$r = buscar_productos([], 1, 100); 
$lista = array_values(array_filter($r['items'], fn($x) => (int)$x['usuario_id'] === (int)$v['id']));

// Reputación del vendedor (Paso 8)
$rep = function_exists('resumen_reputacion_usuario') ? resumen_reputacion_usuario((int)$v['id']) : ['total' => 0, 'promedio' => 0.0, 'distribucion' => [5=>0,4=>0,3=>0,2=>0,1=>0], 'porcentaje_positivo' => 100];
$resenas = function_exists('listar_calificaciones_usuario') ? listar_calificaciones_usuario((int)$v['id'], 10) : [];

$titulo = $v['nombre']; 
require APP . '/vistas/cabecera.php'; 
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor" style="padding-top:24px">
  <div class="panel">
    <div class="vendedor" style="border:0;margin:0;padding:0">
      <?= avatar($v['nombre'], $v['foto'], 80) ?>
      <div>
        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
          <h1 style="font-size:1.6rem;margin:0"><?= e($v['nombre']) ?></h1>
          <?php if ($rep['total'] > 0): ?>
            <span class="badge-reputacion" title="Calificación promedio basada en transacciones completadas">
              ⭐ <?= number_format($rep['promedio'], 1) ?> (<?= $rep['total'] ?> <?= $rep['total'] === 1 ? 'opinión' : 'opiniones' ?>)
            </span>
          <?php else: ?>
            <span class="badge-reputacion" style="background:var(--fondo);color:var(--gris)">
              Nuevo usuario (sin calificaciones)
            </span>
          <?php endif; ?>
        </div>

        <span class="meta" style="display:block;margin-top:4px">
          <?= $v['verificado'] ? '✔ Correo universitario verificado · ' : '' ?>
          <?= $v['ubicacion'] ? e($v['ubicacion']) . ' · ' : '' ?>
          Miembro desde <?= e(date('m/Y', strtotime($v['creado_en']))) ?>
        </span>

        <?php if ($v['bio']): ?>
          <p style="margin:10px 0 0;font-size:0.95rem;color:var(--tinta)"><?= e($v['bio']) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($rep['total'] > 0): ?>
    <section class="reputacion-panel" style="margin-top:24px">
      <h2 style="font-size:1.25rem;margin-bottom:16px">⭐ Calificaciones y Reputación C2C</h2>
      
      <div class="reputacion-resumen">
        <div class="reputacion-score">
          <span class="reputacion-score__num"><?= number_format($rep['promedio'], 1) ?></span>
          <span class="reputacion-score__estrellas">
            <?php 
            $llenas = (int)round($rep['promedio']);
            echo str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
            ?>
          </span>
          <span class="reputacion-score__total"><?= $rep['porcentaje_positivo'] ?>% recomendaciones positivas</span>
        </div>

        <div class="distribucion-barras">
          <?php foreach ([5, 4, 3, 2, 1] as $est): 
            $cant = $rep['distribucion'][$est] ?? 0;
            $pct = round(($cant / $rep['total']) * 100);
          ?>
            <div class="barra-fila">
              <span class="barra-fila__label"><?= $est ?>★</span>
              <div class="barra-fila__track">
                <div class="barra-fila__fill" style="width: <?= $pct ?>%"></div>
              </div>
              <span class="barra-fila__cant"><?= $cant ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div style="margin-top:20px">
        <h3 style="font-size:1rem;margin-bottom:12px">Opiniones de compradores y usuarios</h3>
        <?php foreach ($resenas as $r): ?>
          <div class="resena-item">
            <div class="resena-item__avatar">
              <?= avatar($r['autor_nombre'], $r['autor_foto'], 36) ?>
            </div>
            <div class="resena-item__cuerpo">
              <div class="resena-item__cab">
                <span class="resena-item__autor"><?= e($r['autor_nombre']) ?></span>
                <span class="resena-item__estrellas"><?= str_repeat('⭐', (int)$r['puntuacion']) ?> (<?= (int)$r['puntuacion'] ?>/5)</span>
              </div>
              <?php if (!empty($r['comentario'])): ?>
                <p class="resena-item__texto">«<?= e($r['comentario']) ?>»</p>
              <?php endif; ?>
              <span class="resena-item__meta">
                Transacción: <strong><?= e($r['producto_titulo']) ?></strong> · <?= e(date('d/m/Y', strtotime($r['creado_en']))) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <h2 style="margin-top:32px">Productos disponibles</h2>
  <?php if (!$lista): ?>
    <div class="vacio"><p>Este vendedor no tiene publicaciones activas en este momento.</p></div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($lista as $p) require APP . '/vistas/tarjeta_producto.php'; ?>
    </div>
  <?php endif; ?>
</div>

<?php require APP . '/vistas/pie.php'; ?>
