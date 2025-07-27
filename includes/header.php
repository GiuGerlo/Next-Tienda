<?php
// Configurar zona horaria de Argentina para todo el sistema
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Verificar que el usuario esté definido
if (!isset($user)) {
    // Si no está definido, intentar obtenerlo desde la sesión o redirigir
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
    $user = getCurrentUser();
}

// Detectar el nivel de directorio para rutas relativas
$base_path = '';
$current_dir = dirname($_SERVER['SCRIPT_NAME']);

// Si estamos en un subdirectorio del dashboard (como ventas/, prestamos/, usuarios/)
if (strpos($current_dir, '/dashboard/') !== false && substr_count($current_dir, '/') > 2) {
    $base_path = '../../';
} else {
    $base_path = '../';
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= isset($page_title) ? $page_title . ' - Sistema Next' : 'Dashboard - Sistema Next' ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="<?= $base_path ?>assets/img/logo.jpg" type="image/x-icon">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/dashboard.css">

    <?php if (isset($additional_css)): ?>
        <?= $additional_css ?>
    <?php endif; ?>
</head>

<body class="d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid px-4">
            <!-- Logo/Brand -->
            <a class="navbar-brand d-flex align-items-center" href="<?= $base_path ?>dashboard/">
                <img src="<?= $base_path ?>assets/img/logo.jpg" alt="Next Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" class="me-2">
                <span class="fw-bold">
                    <span class="text-dark">Ne</span><span style="color: var(--next-yellow);">xt</span>
                </span>
            </a>

            <!-- Botón toggle para móvil -->
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú de navegación -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Menú principal -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($current_page) && $current_page === 'dashboard') ? 'active' : '' ?>" href="<?= $base_path ?>dashboard/">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($current_page) && $current_page === 'ventas') ? 'active' : '' ?>" href="<?= $base_path ?>dashboard/ventas/">
                            <i class="fas fa-shopping-cart me-1"></i>Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($current_page) && $current_page === 'prestamos') ? 'active' : '' ?>" href="<?= $base_path ?>dashboard/prestamos/">
                            <i class="fas fa-handshake me-1"></i>Préstamos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (isset($current_page) && $current_page === 'usuarios') ? 'active' : '' ?>" href="<?= $base_path ?>dashboard/usuarios/">
                            <i class="fas fa-users me-1"></i>Usuarios
                        </a>
                    </li>
                </ul>

                <!-- Menú de usuario -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px; background: var(--next-yellow);">
                                <i class="fas fa-user text-dark"></i>
                            </div>
                            <span class="d-none d-md-inline">
                                <?= htmlspecialchars($user['name']) ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <h6 class="dropdown-header">Mi Cuenta</h6>
                            </li>
                            <li><a class="dropdown-item" href="<?= $base_path ?>dashboard/configuracion/"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="<?= $base_path ?>controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>