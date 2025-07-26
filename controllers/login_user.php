<?php
/**
 * Controlador de Login de Usuarios - Sistema Next
 * Procesa el inicio de sesión de usuarios
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
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);

    // Array para almacenar errores específicos
    $errors = [];

    // Validaciones del lado del servidor
    
    // Validar email
    if (empty($email)) {
        $errors['email'] = 'El correo electrónico es obligatorio.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Por favor, ingresa un email válido.';
    }

    // Validar contraseña
    if (empty($password)) {
        $errors['password'] = 'La contraseña es obligatoria.';
    }

    // Si hay errores de validación, devolverlos
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, completa todos los campos correctamente.',
            'errors' => $errors
        ]);
        exit;
    }

    // Buscar usuario en la base de datos
    $stmt = $pdo->prepare("SELECT id, nombre_completo, email, password, activo FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Verificar si existe el usuario y está activo
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Las credenciales proporcionadas no son correctas.',
            'errors' => ['email' => 'Usuario no encontrado.']
        ]);
        exit;
    }

    if (!$user['activo']) {
        echo json_encode([
            'success' => false,
            'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
            'errors' => ['email' => 'Cuenta desactivada.']
        ]);
        exit;
    }

    // Verificar contraseña
    if (!password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Las credenciales proporcionadas no son correctas.',
            'errors' => ['password' => 'Contraseña incorrecta.']
        ]);
        exit;
    }

    // Login exitoso - establecer sesión
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nombre_completo'];
    $_SESSION['user_email'] = $user['email'];

    // Recordar usuario si se marcó la opción
    if ($remember_me) {
        setcookie('remember_email', $email, time() + (86400 * 30), "/"); // 30 días
    } else {
        // Limpiar cookie si existe
        if (isset($_COOKIE['remember_email'])) {
            setcookie('remember_email', '', time() - 3600, "/");
        }
    }

    // Actualizar último acceso del usuario
    $update_stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
    $update_stmt->execute([$user['id']]);

    // Log de actividad
    $log_sql = "INSERT INTO logs_sistema (usuario_id, accion, descripcion, ip_address, user_agent, fecha) 
                VALUES (?, 'LOGIN', ?, ?, ?, NOW())";
    $log_stmt = $pdo->prepare($log_sql);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $log_stmt->execute([
        $user['id'], 
        "Usuario logueado: {$email}", 
        $ip_address, 
        $user_agent
    ]);

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => '¡Bienvenido al sistema!',
        'user' => [
            'id' => $user['id'],
            'name' => $user['nombre_completo'],
            'email' => $user['email']
        ],
        'redirect' => 'dashboard/'
    ]);

} catch (PDOException $e) {
    // Error de base de datos
    error_log("Error de BD en login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión con la base de datos. Intenta más tarde.'
    ]);
    
} catch (Exception $e) {
    // Otros errores
    error_log("Error en login: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
