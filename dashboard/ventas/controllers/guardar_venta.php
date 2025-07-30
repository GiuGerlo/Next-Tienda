<?php
/**
 * Controlador para guardar ventas (crear/editar)
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

    // Obtener datos del formulario
    $id = $_POST['id'] ?? null;
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $estado_pago = $_POST['estado_pago'] ?? '';
    $total = floatval($_POST['total'] ?? 0);
    $monto_pagado = floatval($_POST['monto_pagado'] ?? 0);
    $monto_adeudado = floatval($_POST['monto_adeudado'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Obtener productos del POST
    $productos = [];
    if (isset($_POST['productos']) && is_array($_POST['productos'])) {
        foreach ($_POST['productos'] as $producto) {
            if (!empty($producto['nombre']) && !empty($producto['precio'])) {
                $productos[] = [
                    'nombre' => trim($producto['nombre']),
                    'talle' => trim($producto['talle'] ?? ''),
                    'cantidad' => intval($producto['cantidad'] ?? 1),
                    'precio_unitario' => floatval($producto['precio']),
                    'subtotal' => floatval($producto['precio']) * intval($producto['cantidad'] ?? 1)
                ];
            }
        }
    }

    // Validaciones
    if (empty($cliente_nombre)) {
        throw new Exception('El nombre del cliente es requerido');
    }

    if (empty($metodo_pago)) {
        throw new Exception('El método de pago es requerido');
    }

    if (empty($estado_pago)) {
        throw new Exception('El estado de pago es requerido');
    }

    if (empty($productos)) {
        throw new Exception('Debe agregar al menos un producto');
    }

    if ($total <= 0) {
        throw new Exception('El total debe ser mayor a 0');
    }

    // Validar estado de pago
    if ($estado_pago === 'parcial' && $monto_pagado <= 0) {
        throw new Exception('Para pago parcial debe especificar el monto pagado');
    }

    if ($estado_pago === 'parcial' && $monto_pagado >= $total) {
        throw new Exception('El monto pagado no puede ser mayor o igual al total para pago parcial');
    }

    // Ajustar montos según estado de pago
    if ($estado_pago === 'completo') {
        $monto_pagado = $total;
        $monto_adeudado = 0;
    } elseif ($estado_pago === 'pendiente') {
        $monto_pagado = 0;
        $monto_adeudado = $total;
    } else { // parcial
        $monto_adeudado = $total - $monto_pagado;
    }

    // Obtener usuario actual
    $user = getCurrentUser();
    $usuario_id = $user['id'];

    // Iniciar transacción
    // Configurar zona horaria para Argentina
    date_default_timezone_set('America/Argentina/Buenos_Aires');
    
    $pdo->beginTransaction();

    if (empty($id)) {
        // CREAR NUEVA VENTA
        
        // Generar fecha y hora actual en formato para MySQL
        $fecha_venta = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO ventas (
            cliente_nombre, fecha_venta, total, metodo_pago, estado_pago, 
            monto_pagado, monto_adeudado, observaciones, usuario_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $cliente_nombre, $fecha_venta, $total, $metodo_pago, $estado_pago,
            $monto_pagado, $monto_adeudado, $observaciones, $usuario_id
        ]);
        
        $venta_id = $pdo->lastInsertId();
        
        // Insertar productos
        $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_nombre, talle, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        
        foreach ($productos as $producto) {
            $stmt_detalle->execute([
                $venta_id,
                $producto['nombre'],
                $producto['talle'],
                $producto['cantidad'],
                $producto['precio_unitario'],
                $producto['subtotal']
            ]);
        }
        
        // Registrar pago inicial si hay monto pagado
        if ($monto_pagado > 0) {
            $fecha_pago = date('Y-m-d H:i:s'); // Usar misma fecha y hora
            $sql_pago = "INSERT INTO pagos_ventas (venta_id, monto, metodo_pago, fecha_pago, observaciones, usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_pago = $pdo->prepare($sql_pago);
            $stmt_pago->execute([
                $venta_id, 
                $monto_pagado, 
                $metodo_pago,
                $fecha_pago,
                'Pago inicial de la venta',
                $usuario_id
            ]);
        }
        
        $message = 'Venta creada exitosamente';
        
    } else {
        // EDITAR VENTA EXISTENTE
        
        // Verificar que la venta existe
        $sql_check = "SELECT id FROM ventas WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$id]);
        
        if (!$stmt_check->fetch()) {
            throw new Exception('La venta no existe');
        }
        
        // Actualizar venta
        $sql = "UPDATE ventas SET 
            cliente_nombre = ?, total = ?, metodo_pago = ?, estado_pago = ?, 
            monto_pagado = ?, monto_adeudado = ?, observaciones = ?
            WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $cliente_nombre, $total, $metodo_pago, $estado_pago,
            $monto_pagado, $monto_adeudado, $observaciones, $id
        ]);
        
        // Eliminar productos existentes
        $sql_delete = "DELETE FROM detalle_ventas WHERE venta_id = ?";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([$id]);
        
        // Insertar productos actualizados
        $sql_detalle = "INSERT INTO detalle_ventas (venta_id, producto_nombre, talle, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_detalle = $pdo->prepare($sql_detalle);
        
        foreach ($productos as $producto) {
            $stmt_detalle->execute([
                $id,
                $producto['nombre'],
                $producto['talle'],
                $producto['cantidad'],
                $producto['precio_unitario'],
                $producto['subtotal']
            ]);
        }
        
        $venta_id = $id;
        $message = 'Venta actualizada exitosamente';
    }

    // Confirmar transacción
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'venta_id' => $venta_id
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar la venta: ' . $e->getMessage()
    ]);
}
?>
