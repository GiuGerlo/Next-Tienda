<?php
/**
 * Controlador para autocompletado de clientes
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
    $termino = $_GET['q'] ?? '';
    
    if (strlen($termino) < 2) {
        echo json_encode(['success' => true, 'clientes' => []]);
        exit();
    }
    
    // Buscar clientes en préstamos y ventas
    $sql = "
        SELECT DISTINCT 
            cliente_nombre,
            COUNT(*) as total_prestamos,
            MAX(fecha_prestamo) as ultimo_prestamo,
            SUM(CASE WHEN estado IN ('pendiente', 'parcial') THEN 1 ELSE 0 END) as prestamos_activos
        FROM prestamos 
        WHERE cliente_nombre LIKE :termino
        GROUP BY cliente_nombre
        
        UNION
        
        SELECT DISTINCT 
            cliente_nombre,
            0 as total_prestamos,
            NULL as ultimo_prestamo,
            0 as prestamos_activos
        FROM ventas 
        WHERE cliente_nombre LIKE :termino
        AND cliente_nombre NOT IN (
            SELECT DISTINCT cliente_nombre 
            FROM prestamos 
            WHERE cliente_nombre LIKE :termino
        )
        
        ORDER BY cliente_nombre ASC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':termino' => "%$termino%"]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear los resultados
    $resultado = [];
    foreach ($clientes as $cliente) {
        $detalle = '';
        if ($cliente['total_prestamos'] > 0) {
            $detalle = $cliente['total_prestamos'] . ' préstamo(s)';
            if ($cliente['prestamos_activos'] > 0) {
                $detalle .= ' (' . $cliente['prestamos_activos'] . ' activo(s))';
            }
        } else {
            $detalle = 'Cliente de ventas';
        }
        
        $resultado[] = [
            'nombre' => $cliente['cliente_nombre'],
            'detalle' => $detalle,
            'tiene_prestamos' => $cliente['total_prestamos'] > 0,
            'prestamos_activos' => $cliente['prestamos_activos']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'clientes' => $resultado
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}
?>
