<?php
/**
 * ReUns - Etapas 7 y 8: Calificaciones, Reseñas y Reputación C2C
 * Permite a compradores, vendedores y partes de intercambios evaluar
 * operaciones completadas con puntuación (1-5 estrellas) y comentarios.
 */

const CALIF_MSG_MAX = 500;

class CalificacionError extends RuntimeException {}

/**
 * Registra una calificación de forma transaccional y segura.
 * Devuelve [id|null, error|null].
 */
function calificar_operacion(string $tipo, int $opId, int $evaluadorId, int $puntuacion, string $comentario): array {
    if (!in_array($tipo, ['oferta', 'intercambio'], true)) {
        return [null, 'Tipo de operación no válido.'];
    }

    if ($puntuacion < 1 || $puntuacion > 5) {
        return [null, 'La puntuación debe ser entre 1 y 5 estrellas.'];
    }

    $comentario = trim(preg_replace('/[^\P{C}\n]/u', '', $comentario) ?? '');
    if (mb_strlen($comentario) > CALIF_MSG_MAX) {
        return [null, 'El comentario no puede superar los ' . CALIF_MSG_MAX . ' caracteres.'];
    }

    $u = usuario_por_id($evaluadorId);
    if (!$u || $u['estado'] !== 'activo') {
        return [null, 'Tu cuenta no está activa.'];
    }

    try {
        $pdo = db();
        $propia = !$pdo->inTransaction();
        if ($propia) $pdo->beginTransaction();

        $calificadoId = 0;
        $rolEvaluador = '';
        $tituloProd = '';

        if ($tipo === 'oferta') {
            $stmt = $pdo->prepare(
                "SELECT o.*, p.titulo AS p_titulo
                 FROM ofertas o
                 JOIN productos p ON p.id = o.producto_id
                 WHERE o.id = ? FOR UPDATE"
            );
            $stmt->execute([$opId]);
            $op = $stmt->fetch();

            if (!$op) throw new CalificacionError('No encontramos la operación.');
            if ($op['estado'] !== 'completada') {
                throw new CalificacionError('Solo puedes calificar operaciones marcadas como completadas.');
            }

            if ((int)$op['comprador_id'] === $evaluadorId) {
                $calificadoId = (int)$op['vendedor_id'];
                $rolEvaluador = 'comprador';
            } elseif ((int)$op['vendedor_id'] === $evaluadorId) {
                $calificadoId = (int)$op['comprador_id'];
                $rolEvaluador = 'vendedor';
            } else {
                throw new CalificacionError('No participaste en esta operación.');
            }
            $tituloProd = $op['p_titulo'];
        } else {
            $stmt = $pdo->prepare(
                "SELECT x.*, p.titulo AS p_titulo
                 FROM intercambios x
                 JOIN productos p ON p.id = x.producto_id
                 WHERE x.id = ? FOR UPDATE"
            );
            $stmt->execute([$opId]);
            $op = $stmt->fetch();

            if (!$op) throw new CalificacionError('No encontramos el intercambio.');
            if ($op['estado'] !== 'completado') {
                throw new CalificacionError('Solo puedes calificar intercambios marcados como completados.');
            }

            if ((int)$op['solicitante_id'] === $evaluadorId) {
                $calificadoId = (int)$op['receptor_id'];
                $rolEvaluador = 'solicitante';
            } elseif ((int)$op['receptor_id'] === $evaluadorId) {
                $calificadoId = (int)$op['solicitante_id'];
                $rolEvaluador = 'receptor';
            } else {
                throw new CalificacionError('No participaste en este intercambio.');
            }
            $tituloProd = $op['p_titulo'];
        }

        if ($evaluadorId === $calificadoId) {
            throw new CalificacionError('No puedes calificarte a ti mismo.');
        }

        // Verificar si ya existe calificación previa
        $chk = $pdo->prepare(
            "SELECT id FROM calificaciones WHERE tipo_operacion = ? AND operacion_id = ? AND evaluador_id = ?"
        );
        $chk->execute([$tipo, $opId, $evaluadorId]);
        if ($chk->fetchColumn()) {
            throw new CalificacionError('Ya calificaste esta operación.');
        }

        // Insertar calificación
        $ins = $pdo->prepare(
            "INSERT INTO calificaciones (tipo_operacion, operacion_id, evaluador_id, calificado_id, puntuacion, comentario, rol_evaluador)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $ins->execute([
            $tipo,
            $opId,
            $evaluadorId,
            $calificadoId,
            $puntuacion,
            $comentario !== '' ? $comentario : null,
            $rolEvaluador
        ]);
        $idCalificacion = (int)$pdo->lastInsertId();

        // Registrar en historial de la operación
        if (function_exists('op_historial')) {
            op_historial(
                $tipo,
                $opId,
                $evaluadorId,
                'calificacion_creada',
                null,
                'calificada',
                "Calificó con $puntuacion estrella" . ($puntuacion > 1 ? 's' : '') . ($comentario ? ": «" . mb_substr($comentario, 0, 40) . "…»" : "")
            );
        }

        // Notificar al contraparte calificado
        if (function_exists('op_avisar')) {
            $estrellasStr = str_repeat('⭐', $puntuacion);
            op_avisar(
                $calificadoId,
                "Recibiste $estrellasStr ($puntuacion/5) de calificación por «" . mb_substr($tituloProd, 0, 30) . "»",
                '/paginas/operaciones.php?ver=historial'
            );
        }

        if (function_exists('auditar')) {
            auditar($evaluadorId, 'calificacion_creada', "op=$tipo:$opId calificado=$calificadoId estrellas=$puntuacion");
        }

        if ($propia) $pdo->commit();
        return [$idCalificacion, null];
    } catch (CalificacionError $e) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        return [null, $e->getMessage()];
    } catch (PDOException $e) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23000') {
            return [null, 'Ya registraste una calificación para esta operación.'];
        }
        error_log('[ReUns] calificar_operacion SQL error: ' . $e->getMessage());
        return [null, 'Ocurrió un error al guardar tu calificación. Inténtalo de nuevo.'];
    } catch (Throwable $t) {
        if ($propia && $pdo->inTransaction()) $pdo->rollBack();
        error_log('[ReUns] calificar_operacion error: ' . $t->getMessage());
        return [null, 'Error inesperado. Inténtalo más tarde.'];
    }
}

/**
 * Obtiene la calificación emitida por un usuario específico para una operación.
 */
function calificacion_de_usuario_en_op(string $tipo, int $opId, int $evaluadorId): ?array {
    $s = db()->prepare(
        "SELECT * FROM calificaciones WHERE tipo_operacion = ? AND operacion_id = ? AND evaluador_id = ? LIMIT 1"
    );
    $s->execute([$tipo, $opId, $evaluadorId]);
    return $s->fetch() ?: null;
}

/**
 * Obtiene todas las calificaciones asociadas a una operación (máximo 2: comprador y vendedor).
 */
function calificaciones_de_operacion(string $tipo, int $opId): array {
    $s = db()->prepare(
        "SELECT c.*, u.nombre AS evaluador_nombre, u.foto AS evaluador_foto
         FROM calificaciones c
         JOIN usuarios u ON u.id = c.evaluador_id
         WHERE c.tipo_operacion = ? AND c.operacion_id = ?
         ORDER BY c.id ASC"
    );
    $s->execute([$tipo, $opId]);
    return $s->fetchAll();
}

/**
 * Mapea las calificaciones de un conjunto de operaciones para visualización en masa.
 * Devuelve [op_id => [evaluador_id => calificacion]].
 */
function calificaciones_por_operaciones(string $tipo, array $ids): array {
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) return [];
    $in = implode(',', array_fill(0, count($ids), '?'));

    $s = db()->prepare(
        "SELECT c.*, u.nombre AS evaluador_nombre, u.foto AS evaluador_foto
         FROM calificaciones c
         JOIN usuarios u ON u.id = c.evaluador_id
         WHERE c.tipo_operacion = ? AND c.operacion_id IN ($in)"
    );
    $s->execute(array_merge([$tipo], $ids));

    $out = [];
    foreach ($s->fetchAll() as $r) {
        $out[(int)$r['operacion_id']][(int)$r['evaluador_id']] = $r;
    }
    return $out;
}

/**
 * Resumen consolidado de reputación de un usuario.
 */
function resumen_reputacion_usuario(int $usuarioId): array {
    $s = db()->prepare(
        "SELECT 
            COUNT(*) AS total,
            COALESCE(AVG(puntuacion), 0) AS promedio,
            SUM(CASE WHEN puntuacion = 5 THEN 1 ELSE 0 END) AS e5,
            SUM(CASE WHEN puntuacion = 4 THEN 1 ELSE 0 END) AS e4,
            SUM(CASE WHEN puntuacion = 3 THEN 1 ELSE 0 END) AS e3,
            SUM(CASE WHEN puntuacion = 2 THEN 1 ELSE 0 END) AS e2,
            SUM(CASE WHEN puntuacion = 1 THEN 1 ELSE 0 END) AS e1
         FROM calificaciones
         WHERE calificado_id = ?"
    );
    $s->execute([$usuarioId]);
    $r = $s->fetch();

    $total = (int)($r['total'] ?? 0);
    $promedio = round((float)($r['promedio'] ?? 0), 1);
    $positivas = (int)($r['e5'] ?? 0) + (int)($r['e4'] ?? 0);
    $porcentaje = $total > 0 ? (int)round(($positivas / $total) * 100) : 100;

    return [
        'total' => $total,
        'promedio' => $promedio,
        'porcentaje_positivo' => $porcentaje,
        'distribucion' => [
            5 => (int)($r['e5'] ?? 0),
            4 => (int)($r['e4'] ?? 0),
            3 => (int)($r['e3'] ?? 0),
            2 => (int)($r['e2'] ?? 0),
            1 => (int)($r['e1'] ?? 0)
        ]
    ];
}

/**
 * Lista las reseñas y calificaciones recibidas por un usuario.
 */
function listar_calificaciones_usuario(int $usuarioId, int $limite = 20): array {
    $limite = max(1, min(100, $limite));
    $s = db()->prepare(
        "SELECT c.*, u.nombre AS autor_nombre, u.foto AS autor_foto,
                CASE 
                    WHEN c.tipo_operacion = 'oferta' THEN (SELECT p.titulo FROM ofertas o JOIN productos p ON p.id = o.producto_id WHERE o.id = c.operacion_id)
                    WHEN c.tipo_operacion = 'intercambio' THEN (SELECT p.titulo FROM intercambios x JOIN productos p ON p.id = x.producto_id WHERE x.id = c.operacion_id)
                    ELSE 'Producto'
                END AS producto_titulo
         FROM calificaciones c
         JOIN usuarios u ON u.id = c.evaluador_id
         WHERE c.calificado_id = ?
         ORDER BY c.creado_en DESC, c.id DESC
         LIMIT $limite"
    );
    $s->execute([$usuarioId]);
    return $s->fetchAll();
}
