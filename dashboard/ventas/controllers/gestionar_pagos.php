<?php
/**
 * Controlador para gestionar pagos de ventas
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
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // OBTENER INFORMACIÓN DE PAGOS DE UNA VENTA
        $venta_id = intval($_GET['id'] ?? 0);
        
        if ($venta_id <= 0) {
            throw new Exception('ID de venta inválido');
        }

        // Obtener datos de la venta
        $sql_venta = "
            SELECT 
                id,
                cliente_nombre,
                total,
                estado_pago,
                monto_pagado,
                monto_adeudado,
                DATE_FORMAT(fecha_venta, '%d/%m/%Y') as fecha_venta_formatted
            FROM ventas 
            WHERE id = ?
        ";
        
        $stmt_venta = $pdo->prepare($sql_venta);
        $stmt_venta->execute([$venta_id]);
        $venta = $stmt_venta->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            throw new Exception('Venta no encontrada');
        }

        // Obtener historial de pagos
        $sql_pagos = "
            SELECT 
                id,
                monto,
                metodo_pago,
                DATE_FORMAT(fecha_pago, '%d/%m/%Y %H:%i') as fecha_pago_formatted,
                comprobante,
                observaciones,
                u.nombre_completo as usuario_nombre
            FROM pagos_ventas pv
            LEFT JOIN usuarios u ON pv.usuario_id = u.id
            WHERE pv.venta_id = ?
            ORDER BY pv.fecha_pago DESC
        ";
        
        $stmt_pagos = $pdo->prepare($sql_pagos);
        $stmt_pagos->execute([$venta_id]);
        $pagos = $stmt_pagos->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'venta' => $venta,
            'pagos' => $pagos
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // AGREGAR NUEVO PAGO
        $venta_id = intval($_POST['venta_id'] ?? 0);
        $monto = floatval($_POST['monto'] ?? 0);
        $metodo_pago = trim($_POST['metodo_pago'] ?? '');
        $comprobante = trim($_POST['comprobante'] ?? '');
        $observaciones = trim($_POST['observaciones'] ?? '');
        
        // Validaciones
        if ($venta_id <= 0) {
            throw new Exception('ID de venta inválido');
        }
        
        if ($monto <= 0) {
            throw new Exception('El monto debe ser mayor a 0');
        }
        
        if (empty($metodo_pago)) {
            throw new Exception('El método de pago es requerido');
        }
        
        // Verificar que la venta existe y tiene saldo pendiente
        $sql_check = "SELECT id, total, monto_pagado, monto_adeudado FROM ventas WHERE id = ?";
        $stmt_check = $pdo->prepare($sql_check);
        $stmt_check->execute([$venta_id]);
        $venta = $stmt_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$venta) {
            throw new Exception('La venta no existe');
        }
        
        if ($venta['monto_adeudado'] <= 0) {
            throw new Exception('Esta venta no tiene saldo pendiente');
        }
        
        if ($monto > $venta['monto_adeudado']) {
            throw new Exception('El monto no puede ser mayor al saldo adeudado');
        }
        
        // Obtener usuario actual
        $user = getCurrentUser();
        $usuario_id = $user['id'];
        
        // Configurar zona horaria para Argentina
        date_default_timezone_set('America/Argentina/Buenos_Aires');
        $fecha_pago = date('Y-m-d H:i:s');
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Insertar el pago
        $sql_pago = "INSERT INTO pagos_ventas (venta_id, monto, metodo_pago, fecha_pago, comprobante, observaciones, usuario_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt_pago = $pdo->prepare($sql_pago);
        $stmt_pago->execute([$venta_id, $monto, $metodo_pago, $fecha_pago, $comprobante, $observaciones, $usuario_id]);
        
        // Actualizar montos en la venta
        $nuevo_monto_pagado = $venta['monto_pagado'] + $monto;
        $nuevo_monto_adeudado = $venta['total'] - $nuevo_monto_pagado;
        
        // Determinar nuevo estado
        $nuevo_estado = 'parcial';
        if ($nuevo_monto_adeudado <= 0) {
            $nuevo_estado = 'completo';
            $nuevo_monto_adeudado = 0;
            $nuevo_monto_pagado = $venta['total'];
        }
        
        // Actualizar venta
        $sql_update = "UPDATE ventas SET monto_pagado = ?, monto_adeudado = ?, estado_pago = ? WHERE id = ?";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([$nuevo_monto_pagado, $nuevo_monto_adeudado, $nuevo_estado, $venta_id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Pago registrado exitosamente'
        ]);
    }

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
