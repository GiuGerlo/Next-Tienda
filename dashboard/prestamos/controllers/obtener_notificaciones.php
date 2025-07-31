<?php
/**
 * Controlador para obtener notificaciones de préstamos
 * Sistema Next - Gestión de Préstamos
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../../../config/connect.php';

try {
    // Préstamos vencidos
    $sql_vencidos = "
        SELECT 
            p.id,
            p.cliente_nombre,
            p.fecha_limite,
            DATEDIFF(CURDATE(), p.fecha_limite) as dias_vencidos
        FROM prestamos p 
        WHERE p.fecha_limite IS NOT NULL 
        AND p.fecha_limite < CURDATE() 
        AND p.estado IN ('pendiente', 'parcial')
        ORDER BY p.fecha_limite ASC
    ";
    
    // Préstamos próximos a vencer (7 días)
    $sql_proximos = "
        SELECT 
            p.id,
            p.cliente_nombre,
            p.fecha_limite,
            DATEDIFF(p.fecha_limite, CURDATE()) as dias_restantes
        FROM prestamos p 
        WHERE p.fecha_limite IS NOT NULL 
        AND p.fecha_limite BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND p.estado IN ('pendiente', 'parcial')
        ORDER BY p.fecha_limite ASC
    ";
    
    // Préstamos sin fecha límite antiguos (más de 30 días)
    $sql_antiguos = "
        SELECT 
            p.id,
            p.cliente_nombre,
            DATE_FORMAT(p.fecha_prestamo, '%d/%m/%Y %H:%i') as fecha_prestamo_formato,
            p.fecha_prestamo,
            DATEDIFF(CURDATE(), DATE(p.fecha_prestamo)) as dias_transcurridos
        FROM prestamos p 
        WHERE p.fecha_limite IS NULL 
        AND DATE(p.fecha_prestamo) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        AND p.estado IN ('pendiente', 'parcial')
        ORDER BY p.fecha_prestamo ASC
    ";
    
    $vencidos = $pdo->query($sql_vencidos)->fetchAll(PDO::FETCH_ASSOC);
    $proximos = $pdo->query($sql_proximos)->fetchAll(PDO::FETCH_ASSOC);
    $antiguos = $pdo->query($sql_antiguos)->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'notificaciones' => [
            'vencidos' => $vencidos,
            'proximos_vencer' => $proximos,
            'antiguos' => $antiguos
        ],
        'total' => count($vencidos) + count($proximos) + count($antiguos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener notificaciones: ' . $e->getMessage()
    ]);
}
?>
