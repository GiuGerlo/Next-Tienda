<?php
// Iniciar sesión
session_start();

// Incluir middleware de autenticación
require_once '../../controllers/auth.php';

// Verificar autenticación via AJAX
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Incluir configuración de base de datos
require_once '../../config/connect.php';

header('Content-Type: application/json');

try {
    // Obtener estadísticas actualizadas
    $stats = getDashboardStatsAjax($pdo);
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log("Error en refresh_stats.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error al obtener estadísticas'
    ]);
}

/**
 * Obtiene las estadísticas para AJAX
 */
function getDashboardStatsAjax($pdo)
{
    $stats = [];

    // Total de ventas del mes actual
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos 
        FROM ventas 
        WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
        AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $ventasData = $stmt->fetch();

    $stats['ventas_mes'] = $ventasData['total_ventas'];
    $stats['ingresos_mes'] = $ventasData['total_ingresos'];

    // Ventas del mes anterior para comparación
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos 
        FROM ventas 
        WHERE MONTH(fecha_venta) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND YEAR(fecha_venta) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
    ");
    $stmt->execute();
    $ventasAnterior = $stmt->fetch();

    $stats['ventas_mes_anterior'] = $ventasAnterior['total_ventas'];
    $stats['ingresos_mes_anterior'] = $ventasAnterior['total_ingresos'];

    // Calcular porcentajes de cambio
    $stats['ventas_cambio'] = $stats['ventas_mes_anterior'] > 0 ? 
        round((($stats['ventas_mes'] - $stats['ventas_mes_anterior']) / $stats['ventas_mes_anterior']) * 100, 1) : 0;
    $stats['ingresos_cambio'] = $stats['ingresos_mes_anterior'] > 0 ? 
        round((($stats['ingresos_mes'] - $stats['ingresos_mes_anterior']) / $stats['ingresos_mes_anterior']) * 100, 1) : 0;

    // Total de préstamos activos
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('pendiente', 'parcial')");
    $stmt->execute();
    $stats['prestamos_activos'] = $stmt->fetchColumn();

    // Monto por cobrar
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto_adeudado), 0) as total FROM ventas WHERE estado_pago IN ('parcial', 'pendiente')");
    $stmt->execute();
    $stats['monto_por_cobrar'] = $stmt->fetchColumn();

    return $stats;
}
?>
