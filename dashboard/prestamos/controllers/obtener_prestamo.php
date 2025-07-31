<?php
/**
 * Controlador para obtener datos de un préstamo específico
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

    $prestamo_id = $_POST['prestamo_id'] ?? null;

    if (empty($prestamo_id)) {
        throw new Exception('ID de préstamo no proporcionado');
    }

    // Obtener datos del préstamo
    $sql = "SELECT 
                p.*,
                u.nombre_completo as usuario_nombre,
                DATE_FORMAT(p.fecha_prestamo, '%Y-%m-%d') as fecha_prestamo_input,
                DATE_FORMAT(p.fecha_limite, '%Y-%m-%d') as fecha_limite_input,
                DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato
            FROM prestamos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = :id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $prestamo_id]);
    $prestamo = $stmt->fetch();

    if (!$prestamo) {
        throw new Exception('Préstamo no encontrado');
    }

    // Obtener productos del préstamo
    $sql = "SELECT 
                id,
                producto_nombre,
                talle,
                cantidad,
                precio_unitario,
                estado_producto,
                DATE_FORMAT(fecha_devolucion, '%d/%m/%Y %H:%i') as fecha_devolucion_formato,
                DATE_FORMAT(fecha_compra, '%d/%m/%Y %H:%i') as fecha_compra_formato,
                venta_id,
                observaciones
            FROM detalle_prestamos
            WHERE prestamo_id = :prestamo_id
            ORDER BY id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prestamo_id' => $prestamo_id]);
    $productos = $stmt->fetchAll();

    // Calcular totales
    $total_valor = 0;
    foreach ($productos as $producto) {
        $total_valor += $producto['cantidad'] * $producto['precio_unitario'];
    }

    echo json_encode([
        'success' => true,
        'prestamo' => $prestamo,
        'productos' => $productos,
        'total_valor' => $total_valor
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_prestamo.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
