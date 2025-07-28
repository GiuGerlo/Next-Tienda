<?php
/**
 * Controlador para eliminar usuarios
 * Sistema Next - Gestión de Usuarios
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

    $id = $_POST['id'] ?? 0;

    if (!$id) {
        throw new Exception('ID de usuario requerido');
    }

    // Verificar que el usuario existe
    $sql = "SELECT id, nombre_completo FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }

    // Verificar que no se esté intentando eliminar al usuario actual
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['id'] == $id) {
        throw new Exception('No puedes eliminar tu propio usuario');
    }

    // Verificar si el usuario tiene ventas asociadas
    $sql = "SELECT COUNT(*) as total FROM ventas WHERE usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $ventasCount = $stmt->fetchColumn();

    if ($ventasCount > 0) {
        throw new Exception('No se puede eliminar el usuario porque tiene ventas asociadas. Considera desactivarlo en su lugar.');
    }

    // Eliminar el usuario
    $sql = "DELETE FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    echo json_encode([
        'success' => true,
        'message' => 'Usuario eliminado correctamente'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>