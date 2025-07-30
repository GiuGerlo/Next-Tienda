<?php
/**
 * Controlador para obtener una venta específica para edición
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
        'message' => 'No autorizado'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once '../../../config/connect.php';

try {
    $venta_id = intval($_GET['id'] ?? 0);
    
    if ($venta_id <= 0) {
        throw new Exception('ID de venta inválido');
    }

    // Obtener datos de la venta
    $sql_venta = "
        SELECT 
            id,
            cliente_nombre,
            DATE_FORMAT(fecha_venta, '%Y-%m-%d') as fecha_venta,
            total,
            metodo_pago,
            estado_pago,
            monto_pagado,
            monto_adeudado,
            observaciones
        FROM ventas 
        WHERE id = ?
    ";
    
    $stmt_venta = $pdo->prepare($sql_venta);
    $stmt_venta->execute([$venta_id]);
    $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);
    
    if (!$venta) {
        throw new Exception('Venta no encontrada');
    }

    // Obtener productos de la venta
    $sql_productos = "
        SELECT 
            producto_nombre,
            talle,
            cantidad,
            precio_unitario
        FROM detalle_ventas 
        WHERE venta_id = ?
        ORDER BY id
    ";
    
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute([$venta_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'venta' => $venta,
        'productos' => $productos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener venta: ' . $e->getMessage()
    ]);
}
?>
