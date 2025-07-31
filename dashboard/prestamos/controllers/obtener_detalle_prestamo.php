<?php
/**
 * Controlador para obtener detalles de un préstamo específico
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

    // Obtener ID del préstamo
    $prestamo_id = $_POST['prestamo_id'] ?? null;

    if (empty($prestamo_id)) {
        throw new Exception('ID del préstamo es obligatorio');
    }

    // Obtener datos del préstamo
    $sql = "SELECT 
                p.id,
                p.cliente_nombre,
                DATE_FORMAT(p.fecha_prestamo, '%d/%m/%Y %H:%i') as fecha_prestamo,
                DATE_FORMAT(p.fecha_limite, '%d/%m/%Y') as fecha_limite,
                p.estado,
                p.total_productos,
                p.productos_devueltos,
                p.productos_comprados,
                p.productos_pendientes,
                p.observaciones,
                u.nombre_completo as usuario_nombre
            FROM prestamos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.id = :prestamo_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prestamo_id' => $prestamo_id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        throw new Exception('Préstamo no encontrado');
    }

    // Obtener productos del préstamo
    $sql = "SELECT 
                dp.id,
                dp.producto_nombre,
                dp.talle,
                dp.cantidad,
                dp.precio_unitario,
                dp.estado_producto,
                DATE_FORMAT(dp.fecha_devolucion, '%d/%m/%Y %H:%i') as fecha_devolucion_formato,
                dp.venta_id
            FROM detalle_prestamos dp
            WHERE dp.prestamo_id = :prestamo_id
            ORDER BY dp.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prestamo_id' => $prestamo_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular valor total referencial
    $total_valor = 0;
    foreach ($productos as $producto) {
        $total_valor += $producto['precio_unitario'] * $producto['cantidad'];
    }

    echo json_encode([
        'success' => true,
        'prestamo' => $prestamo,
        'productos' => $productos,
        'total_valor' => $total_valor
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_detalle_prestamo.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
