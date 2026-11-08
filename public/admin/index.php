<?php 
require __DIR__ . '/../../app/ayudas/bootstrap.php';

// Control estricto de acceso basado en roles y sesión activa
requerir_rol('admin', 'moderador');

$totalUsuarios   = (int) db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalProductos  = (int) db()->query("SELECT COUNT(*) FROM productos WHERE estado = 'activo'")->fetchColumn();

// Estadísticas de operaciones y reputación C2C
$totalCompletadas = 0;
$totalCalifs = 0;
try {
    $sOp = db()->query("SELECT (SELECT COUNT(*) FROM ofertas WHERE estado = 'completada') + (SELECT COUNT(*) FROM intercambios WHERE estado = 'completado')");
    $totalCompletadas = (int)$sOp->fetchColumn();

    $sCal = db()->query("SELECT COUNT(*) FROM calificaciones");
    $totalCalifs = (int)$sCal->fetchColumn();
} catch (Throwable $e) {
    // Si las tablas aún no se han migrado en el entorno
}

$log = db()->query(
    'SELECT a.creado_en, a.accion, a.detalle, a.ip, u.email 
     FROM auditoria a 
     LEFT JOIN usuarios u ON u.id = a.usuario_id 
     ORDER BY a.id DESC 
     LIMIT 25'
)->fetchAll();

$titulo = 'Administración'; 
require APP . '/vistas/cabecera.php'; 
?>
<link rel="stylesheet" href="<?= BASE ?>/assets/css/operaciones.css">

<div class="contenedor" style="padding:24px 0">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:16px">
        <h1 style="font-size:1.8rem;margin:0">Panel de Administración</h1>
        <span class="badge-reputacion" style="background:var(--verde-cl);color:var(--verde-osc);font-size:0.9rem;padding:4px 12px">
            🛡️ Acceso Autorizado (Rol: <?= e(usuario_actual()['rol']) ?>)
        </span>
    </div>

    <p class="meta" style="margin-bottom:20px">
        Monitor de integridad del sistema, estado transaccional C2C y trazabilidad de auditoría.
    </p>

    <div class="cat-grid" style="grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));margin-bottom:24px">
        <div class="panel" style="text-align:center;padding:16px">
            <span style="font-size:2rem;font-weight:700;color:var(--verde-osc);display:block"><?= $totalUsuarios ?></span>
            <span class="meta">Usuarios Registrados</span>
        </div>
        <div class="panel" style="text-align:center;padding:16px">
            <span style="font-size:2rem;font-weight:700;color:var(--verde);display:block"><?= $totalProductos ?></span>
            <span class="meta">Productos Activos</span>
        </div>
        <div class="panel" style="text-align:center;padding:16px">
            <span style="font-size:2rem;font-weight:700;color:var(--ambar-osc);display:block"><?= $totalCompletadas ?></span>
            <span class="meta">Operaciones C2C Completadas</span>
        </div>
        <div class="panel" style="text-align:center;padding:16px">
            <span style="font-size:2rem;font-weight:700;color:var(--tinta);display:block"><?= $totalCalifs ?></span>
            <span class="meta">Calificaciones / Reseñas</span>
        </div>
    </div>
    
    <div class="panel" style="overflow-x:auto">
        <h2 style="font-size:1.2rem;margin-bottom:12px">Últimos registros de auditoría y operaciones</h2>
        <table class="tabla" style="width:100%;font-size:0.9rem">
            <thead>
                <tr style="text-align:left;border-bottom:2px solid var(--linea)">
                    <th style="padding:8px">Fecha</th>
                    <th style="padding:8px">Acción</th>
                    <th style="padding:8px">Detalle</th>
                    <th style="padding:8px">Usuario</th>
                    <th style="padding:8px">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$log): ?>
                    <tr><td colspan="5" style="padding:16px;text-align:center" class="meta">No hay registros de auditoría.</td></tr>
                <?php else: ?>
                    <?php foreach ($log as $r): ?>
                        <tr style="border-bottom:1px solid var(--linea)">
                            <td style="padding:8px;white-space:nowrap"><?= e($r['creado_en']) ?></td>
                            <td style="padding:8px"><code><?= e($r['accion']) ?></code></td>
                            <td style="padding:8px"><?= e($r['detalle'] ?? '—') ?></td>
                            <td style="padding:8px"><?= e($r['email'] ?? 'Sistema') ?></td>
                            <td style="padding:8px;font-family:monospace"><?= e($r['ip']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require APP . '/vistas/pie.php'; ?>
