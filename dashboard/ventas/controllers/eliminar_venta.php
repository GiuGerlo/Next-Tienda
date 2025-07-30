<?php
/**
 * Controlador para eliminar ventas
 * Sistema Next - Gestión de Ventas
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión y verificar autenticación
session_start();
require_once '../../../controllers/auth.php';

// Verificar autenticación sin redirigir (para AJAX)
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor inicia sesión.',
        'redirect' => '../../../index.php'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once '../../../config/connect.php';

try {
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $venta_id = intval($_POST['id'] ?? 0);
    
    if ($venta_id <= 0) {
        throw new Exception('ID de venta inválido');
    }

    // Verificar que la venta existe
    $sql_check = "SELECT id, cliente_nombre FROM ventas WHERE id = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$venta_id]);
    $venta = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        throw new Exception('La venta no existe');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar pagos relacionados
    $sql_delete_pagos = "DELETE FROM pagos_ventas WHERE venta_id = ?";
    $stmt_delete_pagos = $pdo->prepare($sql_delete_pagos);
    $stmt_delete_pagos->execute([$venta_id]);

    // Eliminar detalles de la venta
    $sql_delete_detalles = "DELETE FROM detalle_ventas WHERE venta_id = ?";
    $stmt_delete_detalles = $pdo->prepare($sql_delete_detalles);
    $stmt_delete_detalles->execute([$venta_id]);

    // Eliminar la venta
    $sql_delete = "DELETE FROM ventas WHERE id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$venta_id]);

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Venta de {$venta['cliente_nombre']} eliminada exitosamente"
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar la venta: ' . $e->getMessage()
    ]);
}
?>
