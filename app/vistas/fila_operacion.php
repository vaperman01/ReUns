<?php
/** @var array $op operación normalizada (ver op_normalizar_oferta / op_normalizar_intercambio) */
/** @var array $cambios filas de historial_operaciones de esta operación (opcional) */
/** @var array $calificacionesOp calificaciones emitidas para esta operación (opcional) */
/** @var string $ver pestaña actual, para volver a ella después de una acción */
/** @var int $uid ID del usuario autenticado */

$esOferta = $op['tipo'] === 'oferta';
$rotulos = [
    'comprador' => 'Tú compras', 'vendedor' => 'Te compran',
    'solicitante' => 'Tú propones', 'receptor' => 'Te proponen',
];

if ($esOferta) {
    $titulo = $op['subtipo'] === 'compra' ? 'Solicitud de compra' : 'Oferta de precio';
} else {
    $titulo = 'Intercambio';
}

$fecha = fn(string $f): string => date('d/m/Y H:i', strtotime($f));

$producto = function (array $x, string $rotulo): void {
    $cond = ESTADOS_CONS[$x['condicion'] ?? ''] ?? '';
    ?>
    <div class="op__prod">
        <?php if (!empty($x['imagen'])): ?>
            <img src="<?= BASE ?>/uploads/productos/<?= e($x['imagen']) ?>" alt="<?= e($x['titulo']) ?>" loading="lazy">
        <?php else: ?>
            <div class="op__ph" aria-hidden="true">📦</div>
        <?php endif; ?>
        <div>
            <p class="op__rotulo"><?= e($rotulo) ?></p>
            <h3><a href="<?= BASE ?>/paginas/producto.php?id=<?= (int)$x['id'] ?>"><?= e($x['titulo']) ?></a></h3>
            <p class="meta">
                <?= $x['precio'] > 0 ? e(soles($x['precio'])) : 'Solo intercambio' ?>
                <?= $cond !== '' ? ' · ' . e($cond) : '' ?>
                <?= !empty($x['ubicacion']) ? ' · ' . e($x['ubicacion']) : '' ?>
            </p>
            <?php if (!empty($x['descripcion'])): ?>
                <p class="meta"><?= e(mb_strimwidth($x['descripcion'], 0, 110, '…', 'UTF-8')) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

$esCompletada = in_array($op['estado'], ['completada', 'completado'], true);
$esCancelada = in_array($op['estado'], ['cancelada', 'cancelado'], true);

$califs = $calificacionesOp ?? [];
$miCalif = $califs[$uid] ?? null;
$otraCalif = null;
foreach ($califs as $autorId => $cRow) {
    if ($autorId !== $uid) {
        $otraCalif = $cRow;
        break;
    }
}
?>
<article class="op" id="op-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>">
    <div class="op__cab">
        <span class="op__tipo"><?= e($titulo) ?> · <?= e($rotulos[$op['soy']] ?? '') ?></span>
        <span class="op-chip op-chip--<?= e($op['estado']) ?>"><?= e($op['estado_texto']) ?></span>
    </div>

    <p class="meta">
        <?= in_array($op['soy'], ['vendedor', 'receptor'], true) ? 'De' : 'Con' ?>
        <strong><?= e($op['otro']) ?></strong>
        · Creada el <?= e($fecha($op['creado_en'])) ?>
        <?php if ($op['actualizado_en'] !== $op['creado_en']): ?>
            · Actualizada el <?= e($fecha($op['actualizado_en'])) ?>
        <?php endif; ?>
    </p>

    <?php if ($esOferta): ?>
        <div class="op__prods op__prods--uno"><?php $producto($op['p'], 'Producto'); ?></div>
        <p>
            <?= $op['subtipo'] === 'compra' ? 'Precio acordado:' : 'Monto ofrecido:' ?>
            <strong><?= e(soles($op['monto'])) ?></strong>
            <?php if ($op['subtipo'] === 'oferta' && $op['p']['precio'] > 0): ?>
                <span class="meta">(precio publicado <?= e(soles($op['p']['precio'])) ?>)</span>
            <?php endif; ?>
        </p>
    <?php else: ?>
        <?php $mio = in_array($op['soy'], ['receptor'], true); ?>
        <div class="op__prods">
            <?php $producto($op['q'], $op['soy'] === 'receptor' ? 'Te ofrecen' : 'Tú ofreces'); ?>
            <span class="op__flecha" aria-hidden="true">🔄</span>
            <?php $producto($op['p'], $op['soy'] === 'receptor' ? 'A cambio de tu producto' : 'A cambio de'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($op['mensaje'])): ?>
        <p class="op__msg"><?= e($op['mensaje']) ?></p>
    <?php endif; ?>

    <?php if (in_array($op['estado'], ['aceptada', 'aceptado'], true)): ?>
        <p class="aviso aviso--info" style="margin:12px 0 0">
            Coordinen la entrega entre ustedes (en ReUns no se procesan pagos).
            Cuando se concrete, <strong>ambos</strong> deben confirmar para darla por terminada.
            <?= $op['yo_confirme'] ? '✔ <strong>Tú ya confirmaste.</strong>' : '' ?>
            <?= $op['otro_confirmo'] ? '✔ <strong>La otra parte ya confirmó.</strong>' : '' ?>
        </p>
    <?php endif; ?>

    <?php if ($esCancelada): ?>
        <div class="op__resolucion op__resolucion--cancelada">
            <p style="margin:0;font-size:0.9rem;color:var(--error);font-weight:600">
                ❌ Operación cancelada. El producto queda liberado en el catálogo.
            </p>
            <?php
            $motivoCanc = '';
            foreach ($cambios ?? [] as $c) {
                if (in_array($c['accion'], ['cancelada', 'cancelado'], true) && !empty($c['detalle'])) {
                    $motivoCanc = $c['detalle'];
                    break;
                }
            }
            if ($motivoCanc): ?>
                <p style="margin:4px 0 0;font-size:0.85rem;color:var(--tinta)">
                    <strong>Detalle:</strong> <?= e($motivoCanc) ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php $acciones = acciones_disponibles($op); ?>
    <?php if ($acciones): ?>
        <div class="op__acciones">
            <?php foreach ($acciones as [$acc, $txt, $cls, $conf]): ?>
                <form method="post" action="<?= BASE ?>/paginas/operaciones.php?ver=<?= e($ver) ?>"<?= $conf !== '' ? ' data-op-confirmar="' . e($conf) . '"' : '' ?>>
                    <?= csrf_campo() ?>
                    <input type="hidden" name="tipo" value="<?= e($op['tipo']) ?>">
                    <input type="hidden" name="id" value="<?= (int)$op['id'] ?>">
                    <input type="hidden" name="accion" value="<?= e($acc) ?>">
                    <button class="btn <?= e($cls) ?>" type="submit"><?= e($txt) ?></button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php /* --- PASO 8: CALIFICACIONES Y RESEÑAS DE OPERACIONES COMPLETADAS --- */ ?>
    <?php if ($esCompletada): ?>
        <div class="op__resolucion op__resolucion--exito">
            <p style="margin:0;font-size:0.9rem;color:var(--verde-osc);font-weight:600">
                ✔ Operación completada con éxito. El producto ha sido marcado como vendido/intercambiado.
            </p>
        </div>

        <?php if ($miCalif): ?>
            <div class="resena-item" style="background:var(--verde-cl);border-color:#A7F3D0;margin-top:10px">
                <div class="resena-item__cuerpo">
                    <div class="resena-item__cab">
                        <span class="resena-item__autor">Tu calificación para <?= e($op['otro']) ?></span>
                        <span class="resena-item__estrellas"><?= str_repeat('⭐', (int)$miCalif['puntuacion']) ?> (<?= (int)$miCalif['puntuacion'] ?>/5)</span>
                    </div>
                    <?php if (!empty($miCalif['comentario'])): ?>
                        <p class="resena-item__texto">«<?= e($miCalif['comentario']) ?>»</p>
                    <?php endif; ?>
                    <span class="resena-item__meta">Registrada el <?= e($fecha($miCalif['creado_en'])) ?></span>
                </div>
            </div>
        <?php else: ?>
            <div class="op__calificar">
                <div class="op__calificar-cab">
                    <span>⭐</span>
                    <h4>Calificar la transacción con <?= e($op['otro']) ?></h4>
                </div>
                <p class="meta" style="margin-bottom:8px">
                    Tu reseña ayuda a la comunidad universitaria a identificar usuarios seguros y de confianza.
                </p>
                <form method="post" action="<?= BASE ?>/paginas/operaciones.php?ver=<?= e($ver) ?>" class="form-calificar">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="tipo" value="<?= e($op['tipo']) ?>">
                    <input type="hidden" name="id" value="<?= (int)$op['id'] ?>">
                    <input type="hidden" name="accion" value="calificar">

                    <div class="rating-estrellas" title="Selecciona de 1 a 5 estrellas">
                        <input type="radio" id="s5-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" name="puntuacion" value="5" required>
                        <label for="s5-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" title="5 estrellas - Excelente">★</label>

                        <input type="radio" id="s4-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" name="puntuacion" value="4">
                        <label for="s4-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" title="4 estrellas - Muy bueno">★</label>

                        <input type="radio" id="s3-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" name="puntuacion" value="3">
                        <label for="s3-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" title="3 estrellas - Bueno">★</label>

                        <input type="radio" id="s2-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" name="puntuacion" value="2">
                        <label for="s2-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" title="2 estrellas - Regular">★</label>

                        <input type="radio" id="s1-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" name="puntuacion" value="1">
                        <label for="s1-<?= e($op['tipo']) ?>-<?= (int)$op['id'] ?>" title="1 estrella - Malo">★</label>
                    </div>

                    <textarea name="comentario" class="calif-textarea" placeholder="Escribe tu opinión sobre el trato, puntualidad y cumplimiento (opcional)..." maxlength="500"></textarea>
                    <button class="btn btn--ambar" type="submit">Publicar calificación</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($otraCalif): ?>
            <div class="resena-item" style="margin-top:10px">
                <div class="resena-item__avatar">
                    <?= avatar($otraCalif['evaluador_nombre'], $otraCalif['evaluador_foto'] ?? null, 36) ?>
                </div>
                <div class="resena-item__cuerpo">
                    <div class="resena-item__cab">
                        <span class="resena-item__autor">Calificación recibida de <?= e($otraCalif['evaluador_nombre']) ?></span>
                        <span class="resena-item__estrellas"><?= str_repeat('⭐', (int)$otraCalif['puntuacion']) ?> (<?= (int)$otraCalif['puntuacion'] ?>/5)</span>
                    </div>
                    <?php if (!empty($otraCalif['comentario'])): ?>
                        <p class="resena-item__texto">«<?= e($otraCalif['comentario']) ?>»</p>
                    <?php endif; ?>
                    <span class="resena-item__meta"><?= e($fecha($otraCalif['creado_en'])) ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($cambios)): ?>
        <ul class="op__hist" aria-label="Historial de cambios">
            <?php foreach ($cambios as $c): ?>
                <li>
                    <?= e($fecha($c['creado_en'])) ?> —
                    <?= e(str_replace('_', ' ', $c['accion'])) ?>
                    <?= $c['quien'] ? 'por ' . e($c['quien']) : '(automático)' ?>
                    <?= !empty($c['detalle']) ? ' · ' . e($c['detalle']) : '' ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</article>
