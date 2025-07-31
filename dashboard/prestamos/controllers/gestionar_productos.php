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

    // Verificar si son múltiples productos (IDs separados por comas)
    $detalle_ids = explode(',', $detalle_id);
    $es_multiple = count($detalle_ids) > 1;

    // Comenzar transacción
    $pdo->beginTransaction();

    if ($es_multiple && $accion === 'comprar') {
        // Procesar compra múltiple
        $resultado = procesarCompraMultiple($pdo, $detalle_ids, $_POST);
        $mensaje = $resultado['mensaje'];
    } else {
        // Procesar producto individual
        $resultado = procesarProductoIndividual($pdo, $detalle_ids[0], $accion, $_POST);
        $mensaje = $resultado['mensaje'];
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

/**
 * Procesar producto individual
 */
function procesarProductoIndividual($pdo, $detalle_id, $accion, $post_data) {
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
            // Configurar timezone de Argentina para la fecha de devolución
            $timezone_argentina = new DateTimeZone('America/Argentina/Buenos_Aires');
            $fecha_actual = new DateTime('now', $timezone_argentina);
            $fecha_devolucion = $fecha_actual->format('Y-m-d H:i:s');
            
            // Marcar producto como devuelto
            $sql = "UPDATE detalle_prestamos 
                    SET estado_producto = 'devuelto', 
                        fecha_devolucion = :fecha_devolucion
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fecha_devolucion' => $fecha_devolucion,
                ':id' => $detalle_id
            ]);
            
            $mensaje = 'Producto marcado como devuelto';
            break;

        case 'comprar':
            // Obtener datos adicionales para la compra
            $metodo_pago = $post_data['metodo_pago'] ?? 'efectivo';
            $estado_pago = $post_data['estado_pago'] ?? 'completo';
            $monto_pagado = $post_data['monto_pagado'] ?? null;
            
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

            // Configurar timezone de Argentina para la fecha de venta
            $timezone_argentina = new DateTimeZone('America/Argentina/Buenos_Aires');
            $fecha_actual = new DateTime('now', $timezone_argentina);
            $fecha_venta = $fecha_actual->format('Y-m-d H:i:s');

            // Crear venta desde el préstamo
            $sql = "INSERT INTO ventas (cliente_nombre, fecha_venta, subtotal, total, metodo_pago, estado_pago, monto_pagado, monto_adeudado, observaciones, usuario_id) 
                    VALUES (:cliente_nombre, :fecha_venta, :subtotal, :total, :metodo_pago, :estado_pago, :monto_pagado, :monto_adeudado, :observaciones, :usuario_id)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':cliente_nombre' => $producto['cliente_nombre'],
                ':fecha_venta' => $fecha_venta,
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
                        VALUES (:venta_id, :monto, :metodo_pago, :fecha_pago, :observaciones, :usuario_id)";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':venta_id' => $venta_id,
                    ':monto' => $monto_pagado,
                    ':metodo_pago' => $metodo_pago,
                    ':fecha_pago' => $fecha_venta,
                    ':observaciones' => 'Pago inicial de compra desde préstamo',
                    ':usuario_id' => $_SESSION['user_id']
                ]);
            }

            // Actualizar producto del préstamo
            $sql = "UPDATE detalle_prestamos 
                    SET estado_producto = 'comprado', 
                        fecha_compra = :fecha_compra, 
                        venta_id = :venta_id
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fecha_compra' => $fecha_venta,
                ':venta_id' => $venta_id,
                ':id' => $detalle_id
            ]);
            
            $mensaje = 'Producto convertido a venta exitosamente (Venta #' . $venta_id . ')';
            break;

        default:
            throw new Exception('Acción no válida');
    }

    return ['mensaje' => $mensaje];
}

/**
 * Procesar compra múltiple
 */
function procesarCompraMultiple($pdo, $detalle_ids, $post_data) {
    // Obtener datos adicionales para la compra
    $metodo_pago = $post_data['metodo_pago'] ?? 'efectivo';
    $estado_pago = $post_data['estado_pago'] ?? 'completo';
    $monto_pagado = $post_data['monto_pagado'] ?? null;
    
    // Obtener todos los productos
    $placeholders = str_repeat('?,', count($detalle_ids) - 1) . '?';
    $sql = "SELECT dp.*, p.cliente_nombre 
            FROM detalle_prestamos dp
            JOIN prestamos p ON dp.prestamo_id = p.id
            WHERE dp.id IN ($placeholders) AND dp.estado_producto = 'pendiente'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($detalle_ids);
    $productos = $stmt->fetchAll();

    if (count($productos) !== count($detalle_ids)) {
        throw new Exception('Algunos productos no fueron encontrados o ya fueron procesados');
    }

    // Verificar que todos pertenezcan al mismo cliente
    $cliente_nombre = $productos[0]['cliente_nombre'];
    foreach ($productos as $producto) {
        if ($producto['cliente_nombre'] !== $cliente_nombre) {
            throw new Exception('No se pueden comprar productos de diferentes clientes en una sola venta');
        }
    }

    // Calcular total general
    $total_general = 0;
    foreach ($productos as $producto) {
        $total_general += $producto['cantidad'] * $producto['precio_unitario'];
    }

    // Validar monto pagado si es pago parcial
    if ($estado_pago === 'parcial') {
        if (empty($monto_pagado) || $monto_pagado <= 0) {
            throw new Exception('Debe especificar el monto pagado para pagos parciales');
        }
        if ($monto_pagado > $total_general) {
            throw new Exception('El monto pagado no puede ser mayor al total');
        }
    } else {
        $monto_pagado = $total_general;
    }

    $monto_adeudado = $total_general - $monto_pagado;

    // Configurar timezone de Argentina para la fecha de venta
    $timezone_argentina = new DateTimeZone('America/Argentina/Buenos_Aires');
    $fecha_actual = new DateTime('now', $timezone_argentina);
    $fecha_venta = $fecha_actual->format('Y-m-d H:i:s');

    // Crear venta desde el préstamo (múltiples productos)
    $observaciones = 'Compra múltiple generada desde préstamos (IDs: ' . implode(', ', array_unique(array_column($productos, 'prestamo_id'))) . ')';
    
    $sql = "INSERT INTO ventas (cliente_nombre, fecha_venta, subtotal, total, metodo_pago, estado_pago, monto_pagado, monto_adeudado, observaciones, usuario_id) 
            VALUES (:cliente_nombre, :fecha_venta, :subtotal, :total, :metodo_pago, :estado_pago, :monto_pagado, :monto_adeudado, :observaciones, :usuario_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cliente_nombre' => $cliente_nombre,
        ':fecha_venta' => $fecha_venta,
        ':subtotal' => $total_general,
        ':total' => $total_general,
        ':metodo_pago' => $metodo_pago,
        ':estado_pago' => $estado_pago,
        ':monto_pagado' => $monto_pagado,
        ':monto_adeudado' => $monto_adeudado,
        ':observaciones' => $observaciones,
        ':usuario_id' => $_SESSION['user_id']
    ]);
    
    $venta_id = $pdo->lastInsertId();

    // Crear detalles de la venta para cada producto
    $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_nombre, talle, cantidad, precio_unitario, subtotal) 
                    VALUES (:venta_id, :producto_nombre, :talle, :cantidad, :precio_unitario, :subtotal)";
    $stmt_detalle = $pdo->prepare($sql_detalle);

    // Actualizar cada producto del préstamo
    $sql_update = "UPDATE detalle_prestamos 
                   SET estado_producto = 'comprado', 
                       fecha_compra = :fecha_compra, 
                       venta_id = :venta_id
                   WHERE id = :id";
    $stmt_update = $pdo->prepare($sql_update);

    foreach ($productos as $producto) {
        $subtotal_producto = $producto['cantidad'] * $producto['precio_unitario'];
        
        // Insertar detalle de venta
        $stmt_detalle->execute([
            ':venta_id' => $venta_id,
            ':producto_nombre' => $producto['producto_nombre'],
            ':talle' => $producto['talle'],
            ':cantidad' => $producto['cantidad'],
            ':precio_unitario' => $producto['precio_unitario'],
            ':subtotal' => $subtotal_producto
        ]);

        // Actualizar producto del préstamo
        $stmt_update->execute([
            ':fecha_compra' => $fecha_venta,
            ':venta_id' => $venta_id,
            ':id' => $producto['id']
        ]);
    }

    // Si hay un pago, registrarlo
    if ($monto_pagado > 0) {
        $sql = "INSERT INTO pagos_ventas (venta_id, monto, metodo_pago, fecha_pago, observaciones, usuario_id) 
                VALUES (:venta_id, :monto, :metodo_pago, :fecha_pago, :observaciones, :usuario_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':venta_id' => $venta_id,
            ':monto' => $monto_pagado,
            ':metodo_pago' => $metodo_pago,
            ':fecha_pago' => $fecha_venta,
            ':observaciones' => 'Pago inicial de compra múltiple desde préstamo',
            ':usuario_id' => $_SESSION['user_id']
        ]);
    }

    $mensaje = count($productos) . ' productos convertidos a venta exitosamente (Venta #' . $venta_id . ')';
    
    return ['mensaje' => $mensaje];
}
?>
