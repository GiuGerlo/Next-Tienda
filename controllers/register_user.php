<?php
/**
 * Controlador de Registro de Usuarios - Sistema Next
 * Procesa el registro de nuevos usuarios en el sistema
 * Fecha: 26/07/2025
 */

// Configuración de headers para respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión
session_start();

try {
    // Verificar que sea una petición POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Incluir configuración de base de datos
    require_once '../config/connect.php';

    // Obtener y sanitizar datos del formulario
    $nombre_completo = trim($_POST['nombre_completo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms = isset($_POST['terms']);

    // Array para almacenar errores específicos
    $errors = [];

    // Validaciones del lado del servidor
    
    // Validar nombre completo
    if (empty($nombre_completo)) {
        $errors['nombre_completo'] = 'El nombre completo es obligatorio.';
    } elseif (strlen($nombre_completo) < 2) {
        $errors['nombre_completo'] = 'El nombre debe tener al menos 2 caracteres.';
    } elseif (strlen($nombre_completo) > 100) {
        $errors['nombre_completo'] = 'El nombre no puede exceder 100 caracteres.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $nombre_completo)) {
        $errors['nombre_completo'] = 'El nombre solo puede contener letras y espacios.';
    }

    // Validar email
    if (empty($email)) {
        $errors['email'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Por favor, ingresa un email válido.';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'El email no puede exceder 100 caracteres.';
    }

    // Validar contraseña
    if (empty($password)) {
        $errors['password'] = 'La contraseña es obligatoria.';
    } elseif (strlen($password) > 255) {
        $errors['password'] = 'La contraseña no puede exceder 255 caracteres.';
    }

    // Validar confirmación de contraseña
    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Debes confirmar tu contraseña.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Las contraseñas no coinciden.';
    }

    // Validar términos y condiciones

    // Si hay errores de validación, devolverlos
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, corrige los errores en el formulario.',
            'errors' => $errors
        ]);
        exit;
    }

    // Verificar si el email ya está registrado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND activo = TRUE");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Este correo electrónico ya está registrado en el sistema.',
            'errors' => ['email' => 'Este email ya está en uso.']
        ]);
        exit;
    }

    // Hashear la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Preparar consulta de inserción
    $sql = "INSERT INTO usuarios (nombre_completo, email, password, activo, fecha_creacion) 
            VALUES (?, ?, ?, TRUE, NOW())";
    
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar inserción
    if ($stmt->execute([$nombre_completo, $email, $password_hash])) {
        // Obtener ID del usuario recién creado
        $user_id = $pdo->lastInsertId();
        
        // Log de actividad (opcional)
        $log_sql = "INSERT INTO logs_sistema (usuario_id, accion, descripcion, fecha) 
                    VALUES (?, 'REGISTRO', ?, NOW())";
        $log_stmt = $pdo->prepare($log_sql);
        $log_stmt->execute([$user_id, "Nuevo usuario registrado: {$email}"]);
        
        // Respuesta exitosa
        echo json_encode([
            'success' => true,
            'message' => '¡Cuenta creada exitosamente! Serás redirigido al login.',
            'user_id' => $user_id
        ]);
        
    } else {
        throw new Exception('Error al crear la cuenta. Por favor, intenta nuevamente.');
    }

} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error de BD en registro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos. Intenta más tarde.'
    ]);
    
} catch (Exception $e) {
    // Otros errores
    error_log("Error en registro: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
