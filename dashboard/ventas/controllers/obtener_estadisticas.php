<?php
/**
 * Controlador para obtener estadísticas de ventas
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
    // Total de ventas acumuladas
    $sql_total = "SELECT COALESCE(SUM(total), 0) as total_ventas FROM ventas";
    $stmt_total = $pdo->prepare($sql_total);
    $stmt_total->execute();
    $total_ventas = $stmt_total->fetch()['total_ventas'];

    // Ventas del día actual
    $sql_hoy = "SELECT COALESCE(SUM(total), 0) as ventas_hoy FROM ventas WHERE DATE(fecha_venta) = CURDATE()";
    $stmt_hoy = $pdo->prepare($sql_hoy);
    $stmt_hoy->execute();
    $ventas_hoy = $stmt_hoy->fetch()['ventas_hoy'];

    // Pagos pendientes (monto adeudado)
    $sql_pendientes = "SELECT COALESCE(SUM(monto_adeudado), 0) as pagos_pendientes FROM ventas WHERE estado_pago IN ('parcial', 'pendiente')";
    $stmt_pendientes = $pdo->prepare($sql_pendientes);
    $stmt_pendientes->execute();
    $pagos_pendientes = $stmt_pendientes->fetch()['pagos_pendientes'];

    // Total de transacciones
    $sql_transacciones = "SELECT COUNT(*) as total_transacciones FROM ventas";
    $stmt_transacciones = $pdo->prepare($sql_transacciones);
    $stmt_transacciones->execute();
    $total_transacciones = $stmt_transacciones->fetch()['total_transacciones'];

    // Formatear números sin decimales
    $response = [
        'success' => true,
        'data' => [
            'total_ventas' => '$' . number_format($total_ventas, 0, ',', '.'),
            'ventas_hoy' => '$' . number_format($ventas_hoy, 0, ',', '.'),
            'pagos_pendientes' => '$' . number_format($pagos_pendientes, 0, ',', '.'),
            'total_transacciones' => number_format($total_transacciones, 0, ',', '.')
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
?>
