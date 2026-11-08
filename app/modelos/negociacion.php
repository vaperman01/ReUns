<?php
/**
 * ReUns - Módulo de Negociaciones (Compatibilidad)
 * Refactorizado a PDO bajo la arquitectura central de ReUns.
 * Para operaciones completas C2C (ofertas, compras e intercambios),
 * la plataforma utiliza preferentemente app/modelos/operacion.php.
 */
require_once __DIR__ . '/../ayudas/bootstrap.php';

function crear_propuesta(array $datos, int $comprador_id): array {
    $producto_id = (int)($datos['producto_id'] ?? 0);
    $producto = producto_por_id($producto_id);

    if (!$producto || ($producto['estado'] ?? '') !== 'activo') {
        return ['error' => 'El producto no está disponible para ofertas.'];
    }

    if ((int)$producto['usuario_id'] === $comprador_id) {
        return ['error' => 'No puedes realizar ofertas en tu propio producto.'];
    }

    $tipo = ($datos['tipo'] ?? '') === 'intercambio' ? 'intercambio' : 'oferta_precio';
    $monto = isset($datos['monto']) && $datos['monto'] !== '' ? (float)$datos['monto'] : null;
    $producto_ofrecido_id = !empty($datos['producto_ofrecido_id']) ? (int)$datos['producto_ofrecido_id'] : null;
    $mensaje = trim((string)($datos['mensaje'] ?? ''));

    try {
        $stmt = db()->prepare(
            "INSERT INTO propuestas (producto_id, comprador_id, vendedor_id, tipo, monto_ofrecido, producto_ofrecido_id, mensaje)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $producto_id,
            $comprador_id,
            (int)$producto['usuario_id'],
            $tipo,
            $monto,
            $producto_ofrecido_id,
            $mensaje !== '' ? $mensaje : null
        ]);

        return ['ok' => true, 'id' => (int)db()->lastInsertId()];
    } catch (PDOException $e) {
        error_log('[ReUns] crear_propuesta error: ' . $e->getMessage());
        return ['error' => 'No se pudo registrar la propuesta.'];
    }
}

function aceptar_propuesta(int $propuesta_id, int $usuario_id): bool {
    $pdo = db();
    $enTx = $pdo->inTransaction();
    if (!$enTx) $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("SELECT * FROM propuestas WHERE id = ? AND vendedor_id = ? AND estado = 'pendiente' FOR UPDATE");
        $stmt->execute([$propuesta_id, $usuario_id]);
        $propuesta = $stmt->fetch();

        if (!$propuesta) {
            if (!$enTx) $pdo->rollBack();
            return false;
        }

        $pdo->prepare("UPDATE propuestas SET estado = 'aceptada' WHERE id = ?")->execute([$propuesta_id]);
        $pdo->prepare("UPDATE productos SET estado = 'reservado' WHERE id = ?")->execute([$propuesta['producto_id']]);

        if ($propuesta['tipo'] === 'intercambio' && !empty($propuesta['producto_ofrecido_id'])) {
            $pdo->prepare("UPDATE productos SET estado = 'reservado' WHERE id = ?")->execute([$propuesta['producto_ofrecido_id']]);
        }

        $pdo->prepare("UPDATE propuestas SET estado = 'cancelada' WHERE producto_id = ? AND id != ? AND estado = 'pendiente'")
            ->execute([$propuesta['producto_id'], $propuesta_id]);

        if (!$enTx) $pdo->commit();
        return true;
    } catch (Throwable $e) {
        if (!$enTx && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[ReUns] aceptar_propuesta error: ' . $e->getMessage());
        return false;
    }
}
