<?php
/**
 * Controlador para eliminar pagos de ventas
 * Sistema Next - Gestión de Ventas
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión y verificar autenticación
session_start();
require_once '../../../controllers/auth.php';

// Verificar autenticación
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once '../../../config/connect.php';

// Configurar zona horaria de Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $pago_id = intval($_POST['pago_id'] ?? 0);
    
    if ($pago_id <= 0) {
        throw new Exception('ID de pago inválido');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Obtener información del pago antes de eliminarlo
    $sql_pago = "
        SELECT pv.id, pv.venta_id, pv.monto, v.total, v.monto_pagado, v.monto_adeudado 
        FROM pagos_ventas pv 
        INNER JOIN ventas v ON pv.venta_id = v.id 
        WHERE pv.id = ?
    ";
    $stmt_pago = $pdo->prepare($sql_pago);
    $stmt_pago->execute([$pago_id]);
    $pago = $stmt_pago->fetch(PDO::FETCH_ASSOC);
    
    if (!$pago) {
        throw new Exception('Pago no encontrado');
    }

    // Eliminar el pago
    $sql_delete = "DELETE FROM pagos_ventas WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$pago_id]);

    // Recalcular montos de la venta
    $nuevo_monto_pagado = $pago['monto_pagado'] - $pago['monto'];
    $nuevo_monto_adeudado = $pago['monto_adeudado'] + $pago['monto'];
    
    // Determinar nuevo estado de pago
    if ($nuevo_monto_adeudado <= 0) {
        $nuevo_estado = 'completo';
    } elseif ($nuevo_monto_pagado > 0) {
        $nuevo_estado = 'parcial';
    } else {
        $nuevo_estado = 'pendiente';
    }

    // Actualizar la venta
    $sql_update = "
        UPDATE ventas 
        SET monto_pagado = ?, monto_adeudado = ?, estado_pago = ? 
        WHERE id = ?
    ";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([
        $nuevo_monto_pagado, 
        $nuevo_monto_adeudado, 
        $nuevo_estado, 
        $pago['venta_id']
    ]);

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pago eliminado exitosamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
