<?php
// Evitar cualquier output antes del JSON
ob_start();
session_start();
require_once '../config/connect.php';

// Limpiar cualquier output previo
ob_clean();

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message, $data = null) {
    ob_clean(); // Limpiar cualquier output previo
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(false, 'Método no permitido');
}

// Obtener datos del formulario
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

// Validar campos obligatorios
if (empty($email) || empty($password)) {
    sendJsonResponse(false, 'Por favor, completa todos los campos.');
}

try {
    // Buscar usuario por email
    $stmt = $pdo->prepare("SELECT id, nombre_completo, email, password, activo FROM usuarios WHERE email = ? AND activo = 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($password, $usuario['password'])) {
        // Login exitoso
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nombre_completo'];
        $_SESSION['user_email'] = $usuario['email'];
        
        // Actualizar último acceso
        $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        
        // Recordar email si está marcado
        if ($remember_me) {
            setcookie('remember_email', $email, time() + (30 * 24 * 60 * 60), '/'); // 30 días
        } else {
            setcookie('remember_email', '', time() - 3600, '/'); // Eliminar cookie
        }
        
        // Registrar actividad
        $stmt = $pdo->prepare("INSERT INTO log_actividades (usuario_id, accion, ip_address, user_agent) VALUES (?, 'login', ?, ?)");
        $stmt->execute([
            $usuario['id'], 
            $_SERVER['REMOTE_ADDR'] ?? '', 
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        sendJsonResponse(true, 'Login exitoso', [
            'redirect' => 'dashboard/'
        ]);
    } else {
        // Login fallido
        sendJsonResponse(false, 'Email o contraseña incorrectos.');
    }
    
} catch (PDOException $e) {
    error_log("Error en login: " . $e->getMessage());
    sendJsonResponse(false, 'Error del sistema. Intenta nuevamente.');
} catch (Exception $e) {
    error_log("Error general en login: " . $e->getMessage());
    sendJsonResponse(false, 'Error inesperado. Intenta nuevamente.');
}
?>
