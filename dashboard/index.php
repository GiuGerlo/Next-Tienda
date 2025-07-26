<?php
// Iniciar sesión
session_start();

// Incluir middleware de autenticación
require_once '../controllers/auth.php';

// Verificar autenticación
requireAuth('../ind    </nav>

    <main class="flex-grow-1 dashboard-main">
        <div class="container-fluid">hp');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Incluir configuración de base de datos para estadísticas
require_once '../config/connect.php';

// Obtener estadísticas del dashboard
$stats = getDashboardStats($pdo);

/**
 * Obtiene las estadísticas para el dashboard
 */
function getDashboardStats($pdo) {
    $stats = [];
    
    try {
        // Total de ventas del mes actual
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos 
            FROM ventas 
            WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
            AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())
        ");
        $stmt->execute();
        $ventasData = $stmt->fetch();
        
        $stats['ventas_mes'] = $ventasData['total_ventas'];
        $stats['ingresos_mes'] = $ventasData['total_ingresos'];
        
        // Total de préstamos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE estado = 'activo'");
        $stmt->execute();
        $stats['prestamos_activos'] = $stmt->fetchColumn();
        
        // Total de usuarios
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = TRUE");
        $stmt->execute();
        $stats['total_usuarios'] = $stmt->fetchColumn();
        
        // Ventas de los últimos 7 días
        $stmt = $pdo->prepare("
            SELECT DATE(fecha_venta) as fecha, COUNT(*) as cantidad, SUM(total) as monto
            FROM ventas 
            WHERE fecha_venta >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
            GROUP BY DATE(fecha_venta)
            ORDER BY fecha_venta DESC
        ");
        $stmt->execute();
        $stats['ventas_recientes'] = $stmt->fetchAll();
        
        // Préstamos pendientes
        $stmt = $pdo->prepare("
            SELECT cliente_nombre, total, fecha_prestamo, estado
            FROM prestamos 
            WHERE estado IN ('activo', 'vencido')
            ORDER BY fecha_prestamo DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['prestamos_pendientes'] = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        // Valores por defecto en caso de error
        $stats = [
            'ventas_mes' => 0,
            'ingresos_mes' => 0,
            'prestamos_activos' => 0,
            'total_usuarios' => 0,
            'ventas_recientes' => [],
            'prestamos_pendientes' => []
        ];
    }
    
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Dashboard - Sistema Next</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="../assets/img/logo.jpg" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container-fluid px-4">
            <!-- Logo/Brand -->
            <a class="navbar-brand d-flex align-items-center" href="../dashboard/">
                <img src="../assets/img/logo.jpg" alt="Next Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;" class="me-2">
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
                        <a class="nav-link active" href="../dashboard/">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../ventas/">
                            <i class="fas fa-shopping-cart me-1"></i>Ventas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../prestamos/">
                            <i class="fas fa-handshake me-1"></i>Préstamos
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../usuarios/">
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
                            <li><h6 class="dropdown-header">Mi Cuenta</h6></li>
                            <li><a class="dropdown-item" href="../perfil/"><i class="fas fa-user-circle me-2"></i>Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="../configuracion/"><i class="fas fa-cog me-2"></i>Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../controllers/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="container-fluid px-4">
            <!-- Header del Dashboard -->
            <div class="dashboard-header">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h1 class="dashboard-title">
                            <i class="fas fa-tachometer-alt me-3"></i>Dashboard
                        </h1>
                        <p class="dashboard-subtitle">
                            Bienvenido, <strong><?= htmlspecialchars($user['name']) ?></strong>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="dashboard-date">
                            <i class="fas fa-calendar-alt me-2"></i>
                            <?= strftime('%A, %d de %B de %Y', time()) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de Estadísticas -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stats-card sales-card">
                        <div class="stats-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stats-content">
                            <h3 class="stats-number"><?= number_format($stats['ventas_mes']) ?></h3>
                            <p class="stats-label">Ventas este mes</p>
                            <span class="stats-trend positive">
                                <i class="fas fa-arrow-up"></i> +12%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card revenue-card">
                        <div class="stats-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stats-content">
                            <h3 class="stats-number">$<?= number_format($stats['ingresos_mes'], 0, ',', '.') ?></h3>
                            <p class="stats-label">Ingresos del mes</p>
                            <span class="stats-trend positive">
                                <i class="fas fa-arrow-up"></i> +8%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card loans-card">
                        <div class="stats-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stats-content">
                            <h3 class="stats-number"><?= number_format($stats['prestamos_activos']) ?></h3>
                            <p class="stats-label">Préstamos activos</p>
                            <span class="stats-trend neutral">
                                <i class="fas fa-minus"></i> Sin cambios
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <div class="stats-card users-card">
                        <div class="stats-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stats-content">
                            <h3 class="stats-number"><?= number_format($stats['total_usuarios']) ?></h3>
                            <p class="stats-label">Usuarios totales</p>
                            <span class="stats-trend positive">
                                <i class="fas fa-arrow-up"></i> +1
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y Tablas -->
            <div class="row g-4">
                <!-- Ventas Recientes -->
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-chart-line me-2"></i>
                                Ventas de los últimos 7 días
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats['ventas_recientes'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-chart-line"></i>
                                    <p>No hay ventas recientes para mostrar</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Cantidad</th>
                                                <th>Monto</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['ventas_recientes'] as $venta): ?>
                                                <tr>
                                                    <td><?= date('d/m/Y', strtotime($venta['fecha'])) ?></td>
                                                    <td>
                                                        <span class="badge bg-primary"><?= $venta['cantidad'] ?></span>
                                                    </td>
                                                    <td>
                                                        <strong>$<?= number_format($venta['monto'], 0, ',', '.') ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Préstamos Pendientes -->
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-clock me-2"></i>
                                Préstamos Pendientes
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($stats['prestamos_pendientes'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-handshake"></i>
                                    <p>No hay préstamos pendientes</p>
                                </div>
                            <?php else: ?>
                                <div class="loans-list">
                                    <?php foreach ($stats['prestamos_pendientes'] as $prestamo): ?>
                                        <div class="loan-item">
                                            <div class="loan-info">
                                                <h6 class="loan-client"><?= htmlspecialchars($prestamo['cliente_nombre']) ?></h6>
                                                <p class="loan-amount">$<?= number_format($prestamo['total'], 0, ',', '.') ?></p>
                                            </div>
                                            <div class="loan-status">
                                                <span class="badge bg-<?= $prestamo['estado'] === 'vencido' ? 'danger' : 'warning' ?>">
                                                    <?= ucfirst($prestamo['estado']) ?>
                                                </span>
                                                <small class="text-muted d-block">
                                                    <?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
