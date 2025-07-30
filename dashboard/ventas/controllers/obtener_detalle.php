<?php
/**
 * Controlador para obtener detalle de una venta específica
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
            v.*,
            u.nombre_completo as usuario_nombre,
            DATE_FORMAT(v.fecha_venta, '%d/%m/%Y %H:%i') as fecha_formatted,
            DATE_FORMAT(v.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formatted
        FROM ventas v 
        LEFT JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.id = ?
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
            precio_unitario,
            subtotal
        FROM detalle_ventas 
        WHERE venta_id = ?
        ORDER BY id
    ";
    
    $stmt_productos = $pdo->prepare($sql_productos);
    $stmt_productos->execute([$venta_id]);
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener historial de pagos
    $sql_pagos = "
        SELECT 
            monto,
            metodo_pago,
            DATE_FORMAT(fecha_pago, '%d/%m/%Y %H:%i') as fecha_pago_formatted,
            comprobante,
            observaciones,
            u.nombre_completo as usuario_nombre
        FROM pagos_ventas pv
        LEFT JOIN usuarios u ON pv.usuario_id = u.id
        WHERE pv.venta_id = ?
        ORDER BY pv.fecha_pago
    ";
    
    $stmt_pagos = $pdo->prepare($sql_pagos);
    $stmt_pagos->execute([$venta_id]);
    $pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

    // Formatear método de pago
    $metodos_pago = [
        'efectivo' => 'Efectivo',
        'tarjeta_debito' => 'Tarjeta de Débito',
        'tarjeta_credito' => 'Tarjeta de Crédito',
        'transferencia' => 'Transferencia',
        'cuenta_corriente' => 'Cuenta Corriente',
        'otro' => 'Otro'
    ];

    $venta['metodo_pago_formatted'] = $metodos_pago[$venta['metodo_pago']] ?? $venta['metodo_pago'];

    // Formatear estado de pago
    $estados_pago = [
        'completo' => 'Pago Completo',
        'parcial' => 'Pago Parcial',
        'pendiente' => 'Pago Pendiente'
    ];

    $venta['estado_pago_formatted'] = $estados_pago[$venta['estado_pago']] ?? $venta['estado_pago'];

    echo json_encode([
        'success' => true,
        'venta' => $venta,
        'productos' => $productos,
        'pagos' => $pagos
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener detalle de venta: ' . $e->getMessage()
    ]);
}
?>
