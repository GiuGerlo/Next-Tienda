<?php
/**
 * Controlador para validar email único
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

    $email = trim($_POST['email'] ?? '');
    $id = $_POST['id'] ?? null; // ID del usuario que se está editando (opcional)

    if (empty($email)) {
        throw new Exception('Email requerido');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Formato de email inválido');
    }

    // Verificar si el email ya existe
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $params = [$email];
    
    // Si se está editando un usuario, excluirlo de la búsqueda
    if ($id) {
        $sql .= " AND id != ?";
        $params[] = $id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $existe = $stmt->fetch();

    if ($existe) {
        echo json_encode([
            'success' => false,
            'disponible' => false,
            'message' => 'Este email ya está en uso por otro usuario'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'disponible' => true,
            'message' => 'Email disponible'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>