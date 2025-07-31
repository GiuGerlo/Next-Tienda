<?php
/**
 * Controlador para eliminar préstamos
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

    // Verificar que el préstamo existe
    $sql = "SELECT p.*, 
                   (SELECT COUNT(*) FROM detalle_prestamos WHERE prestamo_id = p.id AND estado_producto != 'pendiente') as productos_procesados
            FROM prestamos p 
            WHERE p.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $prestamo_id]);
    $prestamo = $stmt->fetch();

    if (!$prestamo) {
        throw new Exception('Préstamo no encontrado');
    }

    // Verificar si se puede eliminar
    if ($prestamo['productos_procesados'] > 0) {
        throw new Exception('No se puede eliminar un préstamo que tiene productos devueltos o comprados. Considere marcarlo como finalizado.');
    }

    // Comenzar transacción
    $pdo->beginTransaction();

    // Eliminar detalles del préstamo (CASCADE se encarga automáticamente, pero lo hacemos explícito)
    $sql = "DELETE FROM detalle_prestamos WHERE prestamo_id = :prestamo_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':prestamo_id' => $prestamo_id]);

    // Eliminar el préstamo
    $sql = "DELETE FROM prestamos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $prestamo_id]);

    // Confirmar transacción
    $pdo->commit();

    // Log de actividad
    error_log("Préstamo eliminado: ID $prestamo_id por usuario " . $_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'message' => 'Préstamo eliminado exitosamente'
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en eliminar_prestamo.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
