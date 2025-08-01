<?php
// Iniciar sesión
session_start();

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/');
    exit();
}

// Variables para manejar mensajes
$error_message = $_SESSION['error_message'] ?? '';
$success_message = '';

// Limpiar mensaje de error después de mostrarlo
if (!empty($error_message)) {
    unset($_SESSION['error_message']);
}

// Verificar si viene desde registro exitoso
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success_message = "¡Cuenta creada exitosamente! Ya puedes iniciar sesión.";
}

// Recuperar email recordado
$remembered_email = $_COOKIE['remember_email'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Login - Sistema Next</title>
    <meta name="description" content="Acceso al sistema de gestión Next - Tienda de ropa en Chañar Ladeado">
    <meta name="robots" content="noindex, nofollow">

    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/logo-redondo.png" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Login specific CSS -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>

<body>
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Login wrapper -->
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                
                <!-- Logo y branding -->
                <div class="logo-container">
                    <div class="logo-placeholder">
                        <img src="assets/img/logo.jpg" alt="Logo de Next" style="width:100%;height:100%;object-fit:cover;border-radius:50%;box-shadow:0 2px 8px rgba(0,0,0,0.08);border:2px solid #F8FB60;">
                    </div>
                    <h1 class="brand-text">
                        <span class="next">Next</span>
                    </h1>
                    <p class="brand-subtitle">
                        Sistema de Gestión de Tienda
                    </p>
                </div>
                
                <!-- Mensajes de estado -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de login -->
                <form method="POST" action="controllers/login_user.php" id="loginForm" novalidate>
                    
                    <!-- Campo de email -->
                    <div class="form-floating">
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="nombre@ejemplo.com"
                            value="<?php echo htmlspecialchars($remembered_email); ?>"
                            required
                            autocomplete="email"
                        >
                        <label for="email">
                            <i class="fas fa-envelope me-2"></i>
                            Correo Electrónico
                        </label>
                        <div class="invalid-feedback">
                            Por favor, ingresa un email válido.
                        </div>
                    </div>
                    
                    <!-- Campo de contraseña -->
                    <div class="form-floating position-relative">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Contraseña"
                            required
                            autocomplete="current-password"
                        >
                        <label for="password">
                            <i class="fas fa-lock me-2"></i>
                            Contraseña
                        </label>
                        <button 
                            type="button" 
                            class="password-toggle" 
                            id="togglePassword"
                            title="Mostrar/Ocultar contraseña"
                        >
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                        <div class="invalid-feedback">
                            Por favor, ingresa tu contraseña.
                        </div>
                    </div>
                    
                    <!-- Recordar sesión -->
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            value="1" 
                            id="remember_me" 
                            name="remember_me"
                            <?php echo !empty($remembered_email) ? 'checked' : ''; ?>
                        >
                        <label class="form-check-label" for="remember_me">
                            Recordar mi email
                        </label>
                    </div>
                    
                    <!-- Botón de login -->
                    <button type="submit" class="btn btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        <span class="btn-text">Iniciar Sesión</span>
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </span>
                    </button>
                </form>
                <!-- Información adicional -->
                <div class="text-center mt-4">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Acceso seguro al sistema
                    </small>
                </div>
            </div>
            
            <!-- Footer info -->
            <div class="footer-info">
                <p class="mb-0">
                    <i class="fas fa-copyright" style="color:#F8FB60;"></i>
                    <?php echo date('Y'); ?> Sistema Next
                    &nbsp;|&nbsp;
                    <a href="https://artisansthinking.com" target="_blank" style="text-decoration:none;vertical-align:middle;">
                        <img src="assets/img/logoArtisans.png" alt="Artisans Thinking" style="height:22px;vertical-align:middle;margin-right:4px;">
                    </a>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Login specific JavaScript -->
    <script src="assets/js/index.js"></script>
</body>
</html>
