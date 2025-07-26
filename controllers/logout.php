<?php
// Controlador para cerrar sesión en el sistema Next
session_start();

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

// Redirigir al login
header('Location: ../index.php');
exit;
