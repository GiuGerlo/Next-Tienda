<?php
/**
 * Controlador para gestionar productos de préstamos (devolver/comprar)
 * Sistema Next - Gestión de Préstamos
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

    $accion = $_POST['accion'] ?? '';
    $detalle_id = $_POST['detalle_id'] ?? null;
    $prestamo_id = $_POST['prestamo_id'] ?? null;

    if (empty($accion) || empty($detalle_id)) {
        throw new Exception('Datos incompletos');
    }

    // Comenzar transacción
    $pdo->beginTransaction();

    // Obtener datos del producto
    $sql = "SELECT dp.*, p.cliente_nombre 
            FROM detalle_prestamos dp
            JOIN prestamos p ON dp.prestamo_id = p.id
            WHERE dp.id = :id AND dp.estado_producto = 'pendiente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $detalle_id]);
    $producto = $stmt->fetch();

    if (!$producto) {
        throw new Exception('Producto no encontrado o ya fue procesado');
    }

    switch ($accion) {
        case 'devolver':
            // Marcar producto como devuelto
            $sql = "UPDATE detalle_prestamos 
                    SET estado_producto = 'devuelto', 
                        fecha_devolucion = NOW()
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $detalle_id]);
            
            $mensaje = 'Producto marcado como devuelto';
            break;

        case 'comprar':
            // Obtener datos adicionales para la compra
            $metodo_pago = $_POST['metodo_pago'] ?? 'efectivo';
            $estado_pago = $_POST['estado_pago'] ?? 'completo';
            $monto_pagado = $_POST['monto_pagado'] ?? null;
            
            $total_producto = $producto['cantidad'] * $producto['precio_unitario'];
            
            // Validar monto pagado si es pago parcial
            if ($estado_pago === 'parcial') {
                if (empty($monto_pagado) || $monto_pagado <= 0) {
                    throw new Exception('Debe especificar el monto pagado para pagos parciales');
                }
                if ($monto_pagado > $total_producto) {
                    throw new Exception('El monto pagado no puede ser mayor al total');
                }
            } else {
                $monto_pagado = $total_producto;
            }

            $monto_adeudado = $total_producto - $monto_pagado;

            // Crear venta desde el préstamo
            $sql = "INSERT INTO ventas (cliente_nombre, fecha_venta, subtotal, total, metodo_pago, estado_pago, monto_pagado, monto_adeudado, observaciones, usuario_id) 
                    VALUES (:cliente_nombre, CURDATE(), :subtotal, :total, :metodo_pago, :estado_pago, :monto_pagado, :monto_adeudado, :observaciones, :usuario_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente_nombre' => $producto['cliente_nombre'],
                ':subtotal' => $total_producto,
                ':total' => $total_producto,
                ':metodo_pago' => $metodo_pago,
                ':estado_pago' => $estado_pago,
                ':monto_pagado' => $monto_pagado,
                ':monto_adeudado' => $monto_adeudado,
                ':observaciones' => 'Compra generada desde préstamo ID: ' . $producto['prestamo_id'],
                ':usuario_id' => $_SESSION['user_id']
            ]);
            
            $venta_id = $pdo->lastInsertId();

            // Crear detalle de la venta
            $sql = "INSERT INTO detalle_ventas (venta_id, producto_nombre, talle, cantidad, precio_unitario, subtotal) 
                    VALUES (:venta_id, :producto_nombre, :talle, :cantidad, :precio_unitario, :subtotal)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':venta_id' => $venta_id,
                ':producto_nombre' => $producto['producto_nombre'],
                ':talle' => $producto['talle'],
                ':cantidad' => $producto['cantidad'],
                ':precio_unitario' => $producto['precio_unitario'],
                ':subtotal' => $total_producto
            ]);

            // Si hay un pago, registrarlo
            if ($monto_pagado > 0) {
                $sql = "INSERT INTO pagos_ventas (venta_id, monto, metodo_pago, fecha_pago, observaciones, usuario_id) 
                        VALUES (:venta_id, :monto, :metodo_pago, NOW(), :observaciones, :usuario_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':venta_id' => $venta_id,
                    ':monto' => $monto_pagado,
                    ':metodo_pago' => $metodo_pago,
                    ':observaciones' => 'Pago inicial de compra desde préstamo',
                    ':usuario_id' => $_SESSION['user_id']
                ]);
            }

            // Actualizar producto del préstamo
            $sql = "UPDATE detalle_prestamos 
                    SET estado_producto = 'comprado', 
                        fecha_compra = NOW(), 
                        venta_id = :venta_id
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':venta_id' => $venta_id,
                ':id' => $detalle_id
            ]);
            
            $mensaje = 'Producto convertido a venta exitosamente (Venta #' . $venta_id . ')';
            break;

        default:
            throw new Exception('Acción no válida');
    }

    // Los triggers se encargarán de actualizar los contadores del préstamo

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $mensaje
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en gestionar_productos.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
