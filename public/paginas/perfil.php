<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login(); 
$errores = []; 
$valores = ['nombre' => $u['nombre'], 'ubicacion' => $u['ubicacion'], 'bio' => $u['bio']]; 
$seccion = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verificar_csrf(); 
  $seccion = (string)($_POST['accion'] ?? '');
  switch ($seccion) {
    case 'perfil': 
      $valores = $_POST; 
      $errores = actualizar_perfil($u, $_POST);
      if (!$errores) { flash('ok', 'Perfil actualizado.'); redirigir(BASE . '/paginas/perfil.php'); } 
      break;
    case 'clave': 
      $errores = cambiar_clave($u, (string)($_POST['actual'] ?? ''), (string)($_POST['clave'] ?? ''), (string)($_POST['clave2'] ?? ''));
      if (!$errores) { flash('ok', 'Contraseña cambiada. Las demás sesiones se cerraron.'); redirigir(BASE . '/paginas/perfil.php'); } 
      break;
    case 'foto': 
      $fs = normalizar_archivos($_FILES['foto'] ?? null);
      if (!$fs) { $errores['foto'] = 'Elige una imagen.'; break; }
      [$a, $e2] = guardar_imagen($fs[0], 'perfiles', 400, true); 
      if ($e2) { $errores['foto'] = $e2; break; }
      borrar_imagen('perfiles', $u['foto']); 
      actualizar_foto_usuario((int)$u['id'], $a); 
      auditar((int)$u['id'], 'foto_actualizada');
      flash('ok', 'Foto actualizada.'); 
      redirigir(BASE . '/paginas/perfil.php');
    case 'quitar_foto': 
      borrar_imagen('perfiles', $u['foto']); 
      actualizar_foto_usuario((int)$u['id'], null); 
      flash('ok', 'Foto eliminada.'); 
      redirigir(BASE . '/paginas/perfil.php');
    case 'otras': 
      cerrar_otras_sesiones($u); 
      flash('ok', 'Cerraste la sesión en los demás dispositivos.'); 
      redirigir(BASE . '/paginas/perfil.php');
    case 'reenviar': 
      if (!$u['email_verificado_en']) { 
        enviar_verificacion($u); 
        flash('ok', 'Te enviamos un nuevo correo de verificación.'); 
      } 
      redirigir(BASE . '/paginas/perfil.php');
  }
}

// Consultar datos de reputación del usuario (Paso 8)
$rep = function_exists('resumen_reputacion_usuario') ? resumen_reputacion_usuario((int)$u['id']) : ['total' => 0, 'promedio' => 0.0, 'distribucion' => [5=>0,4=>0,3=>0,2=>0,1=>0], 'porcentaje_positivo' => 100];
$resenas = function_exists('listar_calificaciones_usuario') ? listar_calificaciones_usuario((int)$u['id'], 10) : [];

$titulo = 'Mi perfil'; 
require APP . '/vistas/cabecera.php';
$op = ['errores' => $seccion === 'perfil' ? $errores : [], 'valores' => $valores]; 
$oc = ['errores' => $seccion === 'clave' ? $errores : []]; 
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor">
  <h1 style="padding-top:24px">Mi perfil</h1>
  
  <?php /* --- PANEL DE REPUTACIÓN Y RESEÑAS CONSOLIDADAS (PASO 8) --- */ ?>
  <section class="reputacion-panel">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:16px">
      <h2 style="margin:0;font-size:1.3rem">⭐ Mi Reputación y Reseñas C2C</h2>
      <a href="<?= BASE ?>/paginas/vendedor.php?id=<?= (int)$u['id'] ?>" class="btn btn--linea" style="font-size:0.85rem">Ver mi perfil público</a>
    </div>

    <div class="reputacion-resumen">
      <div class="reputacion-score">
        <span class="reputacion-score__num"><?= number_format($rep['promedio'], 1) ?></span>
        <span class="reputacion-score__estrellas">
          <?php 
          $llenas = (int)round($rep['promedio']);
          echo str_repeat('★', $llenas) . str_repeat('☆', 5 - $llenas);
          ?>
        </span>
        <span class="reputacion-score__total"><?= $rep['total'] ?> <?= $rep['total'] === 1 ? 'calificación' : 'calificaciones' ?></span>
      </div>

      <div class="distribucion-barras">
        <?php foreach ([5, 4, 3, 2, 1] as $est): 
          $cant = $rep['distribucion'][$est] ?? 0;
          $pct = $rep['total'] > 0 ? round(($cant / $rep['total']) * 100) : 0;
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
      <h3 style="font-size:1rem;margin-bottom:12px">Últimas opiniones recibidas</h3>
      <?php if (!$resenas): ?>
        <p class="meta" style="margin:0">
          Aún no has recibido calificaciones. Al concretar ventas, compras o intercambios en ReUns, las reseñas de tus contrapartes se listarán aquí.
        </p>
      <?php else: ?>
        <?php foreach ($resenas as $r): ?>
          <div class="resena-item">
            <div class="resena-item__avatar">
              <?= avatar($r['autor_nombre'], $r['autor_foto'], 40) ?>
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
                Operación: <strong><?= e($r['producto_titulo']) ?></strong> · <?= e(date('d/m/Y', strtotime($r['creado_en']))) ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <div class="cuenta">
    <section class="panel">
      <h2>Foto de perfil</h2>
      <div class="vendedor" style="border:0;margin:0 0 12px;padding:0">
        <?= avatar($u['nombre'], $u['foto'], 72) ?>
        <p class="meta">JPG, PNG o WebP, hasta 3 MB. Se recorta en cuadrado.</p>
      </div>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="foto">
        <div class="campo">
          <label for="foto">Elige una imagen</label>
          <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" data-max-mb="3" data-max-files="1">
          <p class="error"><?= e($seccion === 'foto' ? ($errores['foto'] ?? '') : '') ?></p>
        </div>
        <button class="btn btn--primario" type="submit">Subir foto</button>
      </form>
      <?php if ($u['foto']): ?>
        <form method="post" data-confirmar="¿Quitar tu foto de perfil?" style="margin-top:8px">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="quitar_foto">
          <button class="btn btn--peligro" type="submit">Quitar foto</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Información pública</h2>
      <form method="post" data-validar="perfil" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="perfil">
        <?php 
        campo('nombre', 'Nombre', 'text', $op + ['auto' => 'name']); 
        campo('ubicacion', 'Ubicación (opcional)', 'text', $op + ['req' => false]); 
        ?>
        <div class="campo">
          <label for="bio">Sobre ti (opcional)</label>
          <textarea id="bio" name="bio" maxlength="300"><?= e($valores['bio'] ?? '') ?></textarea>
          <p class="error"><?= e($op['errores']['bio'] ?? '') ?></p>
        </div>
        <button class="btn btn--primario" type="submit">Guardar cambios</button>
      </form>
      <p class="meta">Correo: <?= e($u['email']) ?> · <?= $u['email_verificado_en'] ? '✔ verificado' : 'sin verificar' ?></p>
      <?php if (!$u['email_verificado_en']): ?>
        <form method="post">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="reenviar">
          <button class="btn btn--linea" type="submit">Reenviar correo de verificación</button>
        </form>
      <?php endif; ?>
    </section>

    <section class="panel">
      <h2>Cambiar contraseña</h2>
      <form method="post" data-validar="nueva" novalidate>
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="clave">
        <?php
        campo('actual', 'Contraseña actual', 'password', $oc + ['auto' => 'current-password']); 
        campo('clave', 'Contraseña nueva', 'password', $oc + ['auto' => 'new-password']); 
        campo('clave2', 'Repite la nueva', 'password', $oc + ['auto' => 'new-password']); 
        ?>
        <button class="btn btn--primario" type="submit">Cambiar contraseña</button>
      </form>
      <h2 style="margin-top:24px">Sesiones</h2>
      <p class="meta">Si usaste otro equipo, puedes cerrar la sesión en todos los demás.</p>
      <form method="post" data-confirmar="¿Cerrar la sesión en todos los demás dispositivos?">
        <?= csrf_campo() ?>
        <input type="hidden" name="accion" value="otras">
        <button class="btn btn--peligro" type="submit">Cerrar otras sesiones</button>
      </form>
    </section>
  </div>
</div>
<?php require APP . '/vistas/pie.php'; ?>
