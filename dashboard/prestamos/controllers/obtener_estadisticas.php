<?php
/**
 * Controlador para obtener estadísticas de préstamos
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
    // Estadísticas generales
    $estadisticas = [];

    // Total de préstamos por estado
    $sql = "SELECT 
                estado, 
                COUNT(*) as cantidad,
                SUM(total_productos) as total_productos,
                SUM(productos_pendientes) as productos_pendientes,
                SUM(productos_devueltos) as productos_devueltos,
                SUM(productos_comprados) as productos_comprados
            FROM prestamos 
            GROUP BY estado";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['por_estado'] = $stmt->fetchAll();

    // Préstamos del mes actual
    $sql = "SELECT 
                COUNT(*) as total_mes,
                SUM(total_productos) as productos_mes
            FROM prestamos 
            WHERE MONTH(fecha_prestamo) = MONTH(CURDATE()) 
            AND YEAR(fecha_prestamo) = YEAR(CURDATE())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['mes_actual'] = $stmt->fetch();

    // Préstamos próximos a vencer (en los próximos 7 días)
    $sql = "SELECT 
                COUNT(*) as total_prox_vencer
            FROM prestamos 
            WHERE fecha_limite IS NOT NULL 
            AND fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND estado IN ('pendiente', 'parcial')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['proximos_vencer'] = $stmt->fetchColumn();

    // Préstamos vencidos
    $sql = "SELECT 
                COUNT(*) as total_vencidos
            FROM prestamos 
            WHERE fecha_limite IS NOT NULL 
            AND fecha_limite < CURDATE()
            AND estado IN ('pendiente', 'parcial')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['vencidos'] = $stmt->fetchColumn();

    // Top 5 clientes con más préstamos activos
    $sql = "SELECT 
                cliente_nombre, 
                COUNT(*) as total_prestamos,
                SUM(productos_pendientes) as productos_pendientes
            FROM prestamos 
            WHERE estado IN ('pendiente', 'parcial')
            GROUP BY cliente_nombre 
            ORDER BY total_prestamos DESC, productos_pendientes DESC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['top_clientes'] = $stmt->fetchAll();

    // Productos más prestados (últimos 30 días)
    $sql = "SELECT 
                dp.producto_nombre, 
                SUM(dp.cantidad) as total_prestado,
                COUNT(DISTINCT dp.prestamo_id) as veces_prestado
            FROM detalle_prestamos dp
            JOIN prestamos p ON dp.prestamo_id = p.id
            WHERE p.fecha_prestamo >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY dp.producto_nombre 
            ORDER BY total_prestado DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['productos_populares'] = $stmt->fetchAll();

    // Conversiones a venta (últimos 30 días)
    $sql = "SELECT 
                COUNT(*) as total_conversiones,
                SUM(dp.cantidad * dp.precio_unitario) as valor_convertido
            FROM detalle_prestamos dp
            JOIN prestamos p ON dp.prestamo_id = p.id
            WHERE dp.estado_producto = 'comprado'
            AND dp.fecha_compra >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $estadisticas['conversiones'] = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'estadisticas' => $estadisticas
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_estadisticas_prestamos.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas de préstamos'
    ]);
}
?>
