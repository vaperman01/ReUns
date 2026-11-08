<?php
/**
 * ReUns - Suite de pruebas unitarias y de integración para Pasos 7 y 8.
 * Cierre transaccional de operaciones, resolución, calificaciones, reputación y reseñas.
 * Uso desde la terminal o shell de XAMPP:
 *   set DB_NAME=reuns_prueba
 *   php pruebas/prueba_paso7_paso8.php
 */
require __DIR__ . '/../app/config/config.php';
require APP . '/config/db.php';
require APP . '/ayudas/bootstrap.php';

if (!str_ends_with((string)env('DB_NAME', 'reuns'), '_prueba')) {
    exit("Detenido por seguridad: Ejecuta contra una base que termine en _prueba (ej: set DB_NAME=reuns_prueba).\n");
}

$ok = 0; 
$fallos = 0;

function t(string $n, bool $c): void { 
    global $ok, $fallos; 
    echo ($c ? 'OK     ' : 'FALLA  ') . $n . PHP_EOL; 
    $c ? $ok++ : $fallos++; 
}

function q(string $sql, array $p = []) { 
    $s = db()->prepare($sql); 
    $s->execute($p); 
    return $s; 
}

function nuevo_usuario(string $n): int {
    q("INSERT INTO usuarios (nombre, email, clave_hash, email_verificado_en, rol, estado) VALUES (?, ?, 'x', NOW(), 'usuario', 'activo')",
      [$n, 't_' . strtolower($n) . '_' . bin2hex(random_bytes(4)) . '@uns.edu.pe']);
    return (int)db()->lastInsertId();
}

function nuevo_producto(int $uid, string $titulo, string $mod, float $precio): int {
    q("INSERT INTO productos (usuario_id, categoria_id, titulo, descripcion, estado_conservacion, modalidad, precio, ubicacion, estado) 
       VALUES (?, 1, ?, 'Descripción de prueba', 'bueno', ?, ?, 'Chimbote', 'activo')",
      [$uid, $titulo, $mod, $precio]);
    return (int)db()->lastInsertId();
}

echo "=== INICIANDO PRUEBAS DE PASOS 7 Y 8 (CIERRE, CALIFICACIONES Y REPUTACIÓN) ===\n";

// 1. Limpieza de base de pruebas
db()->exec('SET FOREIGN_KEY_CHECKS=0');
foreach (['calificaciones', 'historial_operaciones', 'intercambios', 'ofertas', 'producto_imagenes', 'productos'] as $tb) {
    db()->exec("DELETE FROM $tb");
}
db()->exec("DELETE FROM usuarios WHERE email LIKE 't\\_%@uns.edu.pe'");
db()->exec('SET FOREIGN_KEY_CHECKS=1');

// Crear usuarios de prueba
$ana = nuevo_usuario('Ana');
$beto = nuevo_usuario('Beto');
$carla = nuevo_usuario('Carla');

$laptop = nuevo_producto($ana, 'Laptop ThinkPad', 'venta', 1500.0);
$polera = nuevo_producto($ana, 'Polera UNS', 'ambas', 50.0);
$libro  = nuevo_producto($beto, 'Libro Algoritmos', 'intercambio', 0.0);
$celular = nuevo_producto($carla, 'Celular Moto G', 'venta', 400.0);

echo "\n--- 1. Cierre de Operaciones C2C (Oferta / Compra) ---\n";
// Beto solicita compra de la laptop de Ana
[$ofId, $err] = crear_solicitud($laptop, $beto, 'compra', '', '');
t('Crear solicitud de compra', $ofId > 0 && $err === null);

// Ana acepta la solicitud
$errAceptar = gestionar_oferta($ofId, $ana, 'aceptar');
t('Vendedor acepta oferta', $errAceptar === null);
t('Producto queda reservado tras aceptación', producto_reservado($laptop) === true);

// Ana confirma la entrega
$errConfV = gestionar_oferta($ofId, $ana, 'confirmar');
t('Vendedor confirma entrega', $errConfV === null);
$estOferta = q('SELECT estado FROM ofertas WHERE id = ?', [$ofId])->fetchColumn();
t('Estado sigue siendo aceptada mientras falta la confirmación de la otra parte', $estOferta === 'aceptada');

// Beto confirma la entrega -> Debe cerrarse y actualizar stock
$errConfC = gestionar_oferta($ofId, $beto, 'confirmar');
t('Comprador confirma recepción', $errConfC === null);

$estOfertaFinal = q('SELECT estado FROM ofertas WHERE id = ?', [$ofId])->fetchColumn();
t('Operación pasa a completada tras confirmación mutua', $estOfertaFinal === 'completada');

$estProdFinal = q('SELECT estado FROM productos WHERE id = ?', [$laptop])->fetchColumn();
t('Producto pasa transaccionalmente a estado "vendido"', $estProdFinal === 'vendido');

echo "\n--- 2. Cancelación con Motivo y Liberación de Stock ---\n";
// Carla oferta por celular de Ana (crear nueva oferta de prueba)
$mouse = nuevo_producto($ana, 'Mouse Logitech', 'venta', 80.0);
[$ofMouse, ] = crear_solicitud($mouse, $carla, 'oferta', '70', 'Ofrezco 70');
gestionar_oferta($ofMouse, $ana, 'aceptar');
t('Mouse queda reservado por aceptación', producto_reservado($mouse) === true);

// Carla cancela la oferta con motivo
$errCanc = gestionar_oferta($ofMouse, $carla, 'cancelar', 'Encontré otro producto más cercano');
t('Comprador cancela operación con motivo', $errCanc === null);

$estMouseFinal = q('SELECT estado FROM ofertas WHERE id = ?', [$ofMouse])->fetchColumn();
t('Oferta pasa a estado "cancelada"', $estMouseFinal === 'cancelada');
t('Mouse queda liberado para nuevos compradores', producto_reservado($mouse) === false);

$detCanc = q("SELECT detalle FROM historial_operaciones WHERE operacion_id = ? AND accion = 'cancelada'", [$ofMouse])->fetchColumn();
t('El motivo de cancelación quedó registrado en el historial', str_contains((string)$detCanc, 'Encontré otro producto'));

echo "\n--- 3. Cierre de Intercambio C2C ---\n";
// Beto propone intercambiar su Libro por la Polera de Ana
[$inId, $errIn] = proponer_intercambio($polera, $beto, $libro, 'Intercambio directo');
t('Proponer intercambio', $inId > 0 && $errIn === null);

// Ana acepta el intercambio
gestionar_intercambio($inId, $ana, 'aceptar');
t('Ambos productos quedan reservados', producto_reservado($polera) && producto_reservado($libro));

// Ambos confirman
gestionar_intercambio($inId, $ana, 'confirmar');
gestionar_intercambio($inId, $beto, 'confirmar');

$estInFinal = q('SELECT estado FROM intercambios WHERE id = ?', [$inId])->fetchColumn();
t('Intercambio pasa a estado "completado"', $estInFinal === 'completado');

$estPolera = q('SELECT estado FROM productos WHERE id = ?', [$polera])->fetchColumn();
$estLibro = q('SELECT estado FROM productos WHERE id = ?', [$libro])->fetchColumn();
t('Ambos productos pasan a estado "intercambiado"', $estPolera === 'intercambiado' && $estLibro === 'intercambiado');

echo "\n--- 4. Calificaciones y Reseñas (Paso 8) ---\n";
// Beto califica a Ana por la laptop (operación completada)
[$c1, $errC1] = calificar_operacion('oferta', $ofId, $beto, 5, 'Excelente vendedora, puntual y el equipo impecable.');
t('Comprador califica al vendedor con 5 estrellas', $c1 > 0 && $errC1 === null);

// Ana califica a Beto en la misma operación
[$c2, $errC2] = calificar_operacion('oferta', $ofId, $ana, 4, 'Buen comprador, pago exacto en efectivo.');
t('Vendedor califica al comprador con 4 estrellas', $c2 > 0 && $errC2 === null);

// Intento de doble calificación (debe fallar)
[$cDup, $errDup] = calificar_operacion('oferta', $ofId, $beto, 5, 'Otra calificación');
t('La BD y el modelo impiden calificar dos veces la misma operación', $cDup === null && str_contains((string)$errDup, 'Ya calificaste'));

// Intento de calificar operación no completada (la cancelada de Carla)
[$cInv, $errInv] = calificar_operacion('oferta', $ofMouse, $carla, 5, 'No se concretó');
t('No se permite calificar operaciones canceladas o pendientes', $cInv === null && str_contains((string)$errInv, 'completadas'));

// Intento de calificar con puntuación inválida
[$cRango, $errRango] = calificar_operacion('intercambio', $inId, $beto, 6, 'Puntuación fuera de rango');
t('No se permite puntuación mayor a 5 o menor a 1', $cRango === null && str_contains((string)$errRango, 'entre 1 y 5'));

// Beto califica a Ana por el intercambio
[$c3, $errC3] = calificar_operacion('intercambio', $inId, $beto, 5, 'Gran trueque, la polera en muy buen estado.');
t('Calificación de intercambio completado', $c3 > 0 && $errC3 === null);

echo "\n--- 5. Consolidación de Reputación y Reseñas ---\n";
// Ana recibió: 5 estrellas de Beto (por laptop) y 5 estrellas de Beto (por polera) = 2 calificaciones de 5 estrellas -> promedio 5.0
$repAna = resumen_reputacion_usuario($ana);
t('Reputación de Ana: total de calificaciones es 2', $repAna['total'] === 2);
t('Reputación de Ana: promedio es 5.0', $repAna['promedio'] === 5.0);
t('Reputación de Ana: 100% de calificaciones positivas', $repAna['porcentaje_positivo'] === 100);
t('Distribución de Ana: 2 de cinco estrellas', ($repAna['distribucion'][5] ?? 0) === 2);

// Beto recibió: 4 estrellas de Ana (por laptop) -> promedio 4.0
$repBeto = resumen_reputacion_usuario($beto);
t('Reputación de Beto: total es 1', $repBeto['total'] === 1);
t('Reputación de Beto: promedio es 4.0', $repBeto['promedio'] === 4.0);

// Listar calificaciones recibidas
$resenasAna = listar_calificaciones_usuario($ana);
t('Listado de reseñas de Ana contiene comentarios y producto', count($resenasAna) === 2 && !empty($resenasAna[0]['comentario']) && isset($resenasAna[0]['autor_nombre']));

// Consultar calificaciones emitidas en una operación específica
$califsOfId = calificaciones_de_operacion('oferta', $ofId);
t('Operación registra calificaciones de ambas partes', count($califsOfId) === 2);

echo "\n============================================\n";
echo "RESULTADO: $ok pruebas correctas, $fallos fallidas\n";
echo "============================================\n";
exit($fallos ? 1 : 0);
