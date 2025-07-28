<?php
/**
 * Controlador para cambiar estado de usuarios (activo/inactivo)
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
    $sql = "SELECT id, nombre_completo, activo FROM usuarios WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }

    // Verificar que no se esté intentando desactivar al usuario actual
    $currentUser = getCurrentUser();
    if ($currentUser && $currentUser['id'] == $id && $usuario['activo'] == 1) {
        throw new Exception('No puedes desactivar tu propio usuario');
    }

    // Cambiar el estado
    $nuevo_estado = $usuario['activo'] ? 0 : 1;
    
    $sql = "UPDATE usuarios SET activo = ?, fecha_actualizacion = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nuevo_estado, $id]);

    $mensaje = $nuevo_estado ? 'Usuario activado correctamente' : 'Usuario desactivado correctamente';

    echo json_encode([
        'success' => true,
        'message' => $mensaje,
        'nuevo_estado' => $nuevo_estado
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>