<?php
// Solo funciones de sesión para cuando se incluya en otras páginas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'Usuario',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }
    return null;
}
?>
                
                <!-- Botón toggle para móvil -->
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Menú de navegación -->
                <div class="collapse navbar-collapse" id="navbarNav">
                    
                    <!-- Menú principal (izquierda en desktop, centro en móvil) -->
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'dashboard') ? 'active' : ''; ?>" 
                               href="../dashboard/">
                                <i class="fas fa-tachometer-alt me-1"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'ventas') ? 'active' : ''; ?>" 
                               href="../ventas/">
                                <i class="fas fa-shopping-cart me-1"></i>
                                Ventas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'prestamos') ? 'active' : ''; ?>" 
                               href="../prestamos/">
                                <i class="fas fa-handshake me-1"></i>
                                Préstamos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (isset($current_page) && $current_page == 'usuarios') ? 'active' : ''; ?>" 
                               href="../usuarios/">
                                <i class="fas fa-users me-1"></i>
                                Usuarios
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Menú de usuario (derecha) -->
                    <ul class="navbar-nav">
                        <!-- Notificaciones -->
                        <li class="nav-item dropdown d-none d-lg-block">
                            <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                    3
                                    <span class="visually-hidden">notificaciones no leídas</span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Notificaciones</h6></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-info-circle text-info me-2"></i>Pago pendiente - Cliente Juan</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-clock text-warning me-2"></i>Préstamo vencido - María</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-check-circle text-success me-2"></i>Venta completada</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">Ver todas</a></li>
                            </ul>
                        </li>
                        
                        <!-- Menú de usuario -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="fas fa-user text-dark"></i>
                                </div>
                                <span class="d-none d-md-inline">
                                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrador'; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" aria-labelledby="userDropdown">
                                <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                                <li>
                                    <a class="dropdown-item" href="../perfil/">
                                        <i class="fas fa-user-circle me-2"></i>
                                        Mi Perfil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="../configuracion/">
                                        <i class="fas fa-cog me-2"></i>
                                        Configuración
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="../controllers/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>
                                        Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Breadcrumb (opcional) -->
        <?php if(isset($breadcrumb) && !empty($breadcrumb)): ?>
        <nav aria-label="breadcrumb" class="bg-light py-2">
            <div class="container-fluid">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="../dashboard/" class="text-decoration-none">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <?php foreach($breadcrumb as $item): ?>
                        <?php if(isset($item['url'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?php echo $item['url']; ?>" class="text-decoration-none">
                                    <?php echo $item['name']; ?>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?php echo $item['name']; ?>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </div>
        </nav>
        <?php endif; ?>
        
        <!-- Contenido principal -->
        <main class="main-content flex-grow-1">
            
            <!-- Mensajes de alerta/notificación -->
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?php echo $_SESSION['message_type'] == 'success' ? 'check-circle' : ($_SESSION['message_type'] == 'error' ? 'exclamation-triangle' : 'info-circle'); ?> me-2"></i>
                    <?php 
                        echo htmlspecialchars($_SESSION['message']); 
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
