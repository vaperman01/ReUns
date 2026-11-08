
<?php
require __DIR__ . '/../../app/ayudas/bootstrap.php';

$u = requerir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verificar_csrf();

    $mapa = [
        'pausar' => 'pausado',
        'reactivar' => 'activo',
        'vendido' => 'vendido',
        'intercambiado' => 'intercambiado',
        'eliminar' => 'eliminado'
    ];

    $acc = (string)($_POST['accion'] ?? '');

    $err = isset($mapa[$acc])
        ? cambiar_disponibilidad(
            (int)($_POST['id'] ?? 0),
            (int)$u['id'],
            $mapa[$acc]
        )
        : 'Acción no válida.';

    if (!$err) {
        auditar((int)$u['id'], 'producto_' . $acc, 'id=' . (int)($_POST['id'] ?? 0));
    }

    flash($err ? 'error' : 'ok', $err ?? 'Listo, publicación actualizada.');

    redirigir(
        BASE . '/paginas/mis_productos.php?ver=' .
        urlencode((string)($_GET['ver'] ?? 'activos'))
    );
}

$ver = ($_GET['ver'] ?? '') === 'historial' ? 'historial' : 'activos';
$lista = productos_de_usuario((int)$u['id'], $ver);

$titulo = 'Mis productos';
require APP . '/vistas/cabecera.php';

function boton(
    string $acc,
    int $id,
    string $txt,
    string $cls = 'btn--linea',
    string $conf = ''
): string {
    return '<form method="post"' .
        ($conf ? ' data-confirmar="' . e($conf) . '"' : '') .
        '>' .
        csrf_campo() .
        '<input type="hidden" name="id" value="' . $id . '">' .
        '<input type="hidden" name="accion" value="' . e($acc) . '">' .
        '<button class="btn ' . e($cls) . '" type="submit">' .
        e($txt) .
        '</button></form>';
}

$etiquetasEstado = [
    'activo' => 'Activo',
    'pausado' => 'Pausado',
    'vendido' => 'Vendido',
    'intercambiado' => 'Intercambiado',
    'oculto' => 'Oculto',
    'eliminado' => 'Eliminado'
];
?>

<div class="contenedor" style="padding-top:20px">
    <div class="seccion__cab">
        <h1>Mis productos</h1>
        <a class="btn btn--primario" href="publicar.php">Publicar producto</a>
    </div>

    <nav class="pestanas" aria-label="Filtro">
        <a href="?ver=activos" <?= $ver === 'activos' ? 'aria-current="page"' : '' ?>>
            Activas y pausadas
        </a>
        <a href="?ver=historial" <?= $ver === 'historial' ? 'aria-current="page"' : '' ?>>
            Historial
        </a>
    </nav>

    <?php if (!$lista): ?>
        <div class="vacio">
            <h2>
                <?= $ver === 'activos'
                    ? 'Aún no tienes publicaciones'
                    : 'Todavía no hay ventas ni intercambios' ?>
            </h2>

            <p>
                <?= $ver === 'activos'
                    ? 'Publica tu primer producto en un minuto.'
                    : 'Aquí verás lo que marques como vendido o intercambiado.' ?>
            </p>

            <?php if ($ver === 'activos'): ?>
                <a class="btn btn--primario" href="publicar.php">Publicar producto</a>
            <?php endif; ?>
        </div>

    <?php else: ?>

        <?php foreach ($lista as $p): ?>
            <?php
            $id = (int)$p['id'];

            // La base de datos utiliza "estado", no "disponibilidad".
            $estado = $p['estado'] ?? 'activo';
            $etiquetaEstado = $etiquetasEstado[$estado] ?? 'Estado desconocido';

            $modalidad = $p['modalidad'] ?? 'venta';
            $imagen = $p['imagen'] ?? '';
            $icono = $p['icono'] ?? '📦';
            ?>

            <div class="fila-prod">
                <?php if ($imagen !== ''): ?>
                    <img
                        src="<?= BASE ?>/uploads/productos/<?= e($imagen) ?>"
                        alt="<?= e($p['titulo'] ?? 'Producto') ?>"
                    >
                <?php else: ?>
                    <div class="ph"><?= e($icono) ?></div>
                <?php endif; ?>

                <div>
                    <a href="producto.php?id=<?= $id ?>">
                        <strong><?= e($p['titulo'] ?? 'Producto sin título') ?></strong>
                    </a>
                    <br>

                    <span class="chip chip--<?= e($estado) ?>">
                        <?= e($etiquetaEstado) ?>
                    </span>

                    <span class="meta">
                        <?= $p['precio'] !== null
                            ? soles((float)$p['precio'])
                            : 'Solo intercambio' ?>
                        · <?= e(MODALIDADES[$modalidad] ?? 'Venta') ?>
                    </span>
                </div>

                <div class="fila-prod__acciones">
                    <?php if (in_array($estado, ['activo', 'pausado'], true)): ?>
                        <a class="btn btn--linea" href="editar.php?id=<?= $id ?>">Editar</a>

                        <?= $estado === 'activo'
                            ? boton('pausar', $id, 'Pausar')
                            : boton('reactivar', $id, 'Reactivar') ?>

                        <?= $modalidad !== 'intercambio'
                            ? boton(
                                'vendido',
                                $id,
                                'Marcar vendido',
                                'btn--linea',
                                '¿Marcar como vendido? Dejará de aparecer en el catálogo.'
                            )
                            : '' ?>

                        <?= $modalidad !== 'venta'
                            ? boton(
                                'intercambiado',
                                $id,
                                'Marcar intercambiado',
                                'btn--linea',
                                '¿Marcar como intercambiado? Dejará de aparecer en el catálogo.'
                            )
                            : '' ?>

                    <?php else: ?>
                        <?= boton('reactivar', $id, 'Volver a publicar') ?>
                    <?php endif; ?>

                    <?= boton(
                        'eliminar',
                        $id,
                        'Eliminar',
                        'btn--peligro',
                        '¿Eliminar esta publicación? No se puede deshacer.'
                    ) ?>
                </div>
            </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?php require APP . '/vistas/pie.php'; ?>
