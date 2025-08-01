<?php
/**
 * Middleware de Autenticación - Sistema Next
 * Verifica que el usuario esté autenticado
 * Fecha: 26/07/2025
 */

/**
 * Verifica si el usuario está autenticado
 * @return bool True si está autenticado, False si no
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Inicia la sesión con duración prolongada si corresponde
 */
function startLongSessionIfRemembered() {
    // Si la cookie de sesión ya existe, no modificar
    if (session_status() === PHP_SESSION_NONE) {
        // Si el usuario eligió "recordar sesión", extender duración
        if (isset($_COOKIE['remember_email']) && !empty($_COOKIE['remember_email'])) {
            $lifetime = 30 * 24 * 60 * 60;
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        session_start();
    }
}

/**
 * Requiere autenticación, redirige al login si no está autenticado
 * @param string $redirect_url URL a la que redirigir si no está autenticado
 */
function requireAuth($redirect_url = '../index.php') {
    startLongSessionIfRemembered();
    // Iniciar sesión si no está iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticación
    if (!isAuthenticated()) {
        // Guardar la URL actual para redirigir después del login
        if (isset($_SERVER['REQUEST_URI'])) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        // Redirigir al login
        header("Location: $redirect_url");
        exit();
    }
}

/**
 * Obtiene los datos del usuario actual
 * @return array|null Datos del usuario o null si no está autenticado
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'Usuario',
        'email' => $_SESSION['user_email'] ?? ''
    ];
}

/**
 * Verifica si el usuario tiene un rol específico
 * @param string $role Rol a verificar
 * @return bool True si tiene el rol, False si no
 */
function hasRole($role) {
    if (!isAuthenticated()) {
        return false;
    }
    
    // Por ahora todos los usuarios son admin
    // En el futuro se puede expandir con roles desde la base de datos
    return true;
}

/**
 * Cierra la sesión del usuario
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Borrar la cookie de sesión si existe
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destruir la sesión
    session_destroy();
    
    // Borrar cookie de email recordado
    if (isset($_COOKIE['remember_email'])) {
        setcookie('remember_email', '', time() - 3600, "/");
    }
}

/**
 * Regenera el ID de sesión por seguridad
 */
function regenerateSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}
?>
