<?php
/**
 * Controlador para guardar usuarios (crear/editar)
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

    // Obtener datos del formulario
    $id = $_POST['id'] ?? null;
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $activo = isset($_POST['activo']) ? 1 : 0;

    // Validaciones
    if (empty($nombre_completo)) {
        throw new Exception('El nombre completo es requerido');
    }

    if (empty($email)) {
        throw new Exception('El email es requerido');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del email no es válido');
    }

    // Verificar si el email ya existe (excepto para el usuario actual en edición)
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    $params = [$email];
    
    if ($id) {
        $sql .= " AND id != ?";
        $params[] = $id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    if ($stmt->fetch()) {
        throw new Exception('Ya existe un usuario con este email');
    }

    if ($id) {
        // EDITAR usuario existente
        if (empty($password)) {
            // No cambiar contraseña
            $sql = "UPDATE usuarios SET 
                        nombre_completo = ?, 
                        email = ?, 
                        activo = ?,
                        fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $params = [$nombre_completo, $email, $activo, $id];
        } else {
            // Cambiar contraseña también
            if (strlen($password) < 6) {
                throw new Exception('La contraseña debe tener al menos 6 caracteres');
            }
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET 
                        nombre_completo = ?, 
                        email = ?, 
                        password = ?,
                        activo = ?,
                        fecha_actualizacion = CURRENT_TIMESTAMP
                    WHERE id = ?";
            $params = [$nombre_completo, $email, $password_hash, $activo, $id];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Usuario actualizado correctamente'
        ]);

    } else {
        // CREAR nuevo usuario
        if (empty($password)) {
            throw new Exception('La contraseña es requerida para nuevos usuarios');
        }

        if (strlen($password) < 6) {
            throw new Exception('La contraseña debe tener al menos 6 caracteres');
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (nombre_completo, email, password, activo) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre_completo, $email, $password_hash, $activo]);

        echo json_encode([
            'success' => true,
            'message' => 'Usuario creado correctamente'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>