<?php
// Iniciar sesión
session_start();

// Incluir middleware de autenticación
require_once '../controllers/auth.php';

// Verificar autenticación
requireAuth('../index.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Incluir configuración de base de datos para estadísticas
require_once '../config/connect.php';

// Configuración de la página
$page_title = 'Dashboard';
$current_page = 'dashboard';
$additional_js = '<script src="../assets/js/dashboard.js"></script>';

// Obtener estadísticas del dashboard
$stats = getDashboardStats($pdo);

/**
 * Obtiene las estadísticas para el dashboard
 */
function getDashboardStats($pdo)
{
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

        // Ventas del mes anterior para comparación
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos 
            FROM ventas 
            WHERE MONTH(fecha_venta) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
            AND YEAR(fecha_venta) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        ");
        $stmt->execute();
        $ventasAnterior = $stmt->fetch();

        $stats['ventas_mes_anterior'] = $ventasAnterior['total_ventas'];
        $stats['ingresos_mes_anterior'] = $ventasAnterior['total_ingresos'];

        // Calcular porcentajes de cambio
        $stats['ventas_cambio'] = $stats['ventas_mes_anterior'] > 0 ? 
            round((($stats['ventas_mes'] - $stats['ventas_mes_anterior']) / $stats['ventas_mes_anterior']) * 100, 1) : 0;
        $stats['ingresos_cambio'] = $stats['ingresos_mes_anterior'] > 0 ? 
            round((($stats['ingresos_mes'] - $stats['ingresos_mes_anterior']) / $stats['ingresos_mes_anterior']) * 100, 1) : 0;

        // Ventas de hoy
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as total_ingresos 
            FROM ventas 
            WHERE DATE(fecha_venta) = CURRENT_DATE()
        ");
        $stmt->execute();
        $ventasHoy = $stmt->fetch();
        $stats['ventas_hoy'] = $ventasHoy['total_ventas'];
        $stats['ingresos_hoy'] = $ventasHoy['total_ingresos'];

        // Total de préstamos activos
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('pendiente', 'parcial')");
        $stmt->execute();
        $stats['prestamos_activos'] = $stmt->fetchColumn();

        // Préstamos vencidos
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total FROM prestamos 
            WHERE (estado IN ('pendiente', 'parcial') AND fecha_limite IS NOT NULL AND fecha_limite < CURRENT_DATE())
            OR estado = 'vencido'
        ");
        $stmt->execute();
        $stats['prestamos_vencidos'] = $stmt->fetchColumn();

        // Total de usuarios
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE activo = TRUE");
        $stmt->execute();
        $stats['total_usuarios'] = $stmt->fetchColumn();

        // Productos en préstamo
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(productos_pendientes), 0) as total FROM prestamos WHERE estado IN ('pendiente', 'parcial')");
        $stmt->execute();
        $stats['productos_en_prestamo'] = $stmt->fetchColumn();

        // Monto por cobrar (cuentas corrientes)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(monto_adeudado), 0) as total FROM ventas WHERE estado_pago IN ('parcial', 'pendiente')");
        $stmt->execute();
        $stats['monto_por_cobrar'] = $stmt->fetchColumn();

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

        // Productos más vendidos este mes
        $stmt = $pdo->prepare("
            SELECT dv.producto_nombre, dv.talle, SUM(dv.cantidad) as total_vendido, 
                   COUNT(DISTINCT dv.venta_id) as veces_vendido, AVG(dv.precio_unitario) as precio_promedio
            FROM detalle_ventas dv
            JOIN ventas v ON dv.venta_id = v.id
            WHERE MONTH(v.fecha_venta) = MONTH(CURRENT_DATE()) 
            AND YEAR(v.fecha_venta) = YEAR(CURRENT_DATE())
            GROUP BY dv.producto_nombre, dv.talle
            ORDER BY total_vendido DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['productos_mas_vendidos'] = $stmt->fetchAll();

        // Préstamos pendientes
        $stmt = $pdo->prepare("
            SELECT cliente_nombre, total_productos, fecha_prestamo, estado, productos_pendientes, fecha_limite
            FROM prestamos 
            WHERE estado IN ('pendiente', 'parcial', 'vencido')
            ORDER BY 
                CASE WHEN estado = 'vencido' THEN 1 
                     WHEN fecha_limite IS NOT NULL AND fecha_limite < CURRENT_DATE() THEN 2 
                     ELSE 3 END,
                fecha_prestamo DESC
            LIMIT 8
        ");
        $stmt->execute();
        $stats['prestamos_pendientes'] = $stmt->fetchAll();

        // Clientes con más compras (histórico total)
        $stmt = $pdo->prepare("
            SELECT cliente_nombre, COUNT(*) as total_compras, SUM(total) as total_gastado,
                   MAX(fecha_venta) as ultima_compra
            FROM ventas 
            GROUP BY cliente_nombre
            ORDER BY total_compras DESC, total_gastado DESC
            LIMIT 5
        ");
        $stmt->execute();
        $stats['mejores_clientes'] = $stmt->fetchAll();

    } catch (PDOException $e) {
        error_log("Error al obtener estadísticas: " . $e->getMessage());
        // Valores por defecto en caso de error
        $stats = [
            'ventas_mes' => 0,
            'ingresos_mes' => 0,
            'ventas_mes_anterior' => 0,
            'ingresos_mes_anterior' => 0,
            'ventas_cambio' => 0,
            'ingresos_cambio' => 0,
            'ventas_hoy' => 0,
            'ingresos_hoy' => 0,
            'prestamos_activos' => 0,
            'prestamos_vencidos' => 0,
            'productos_en_prestamo' => 0,
            'monto_por_cobrar' => 0,
            'total_usuarios' => 0,
            'ventas_recientes' => [],
            'productos_mas_vendidos' => [],
            'prestamos_pendientes' => [],
            'mejores_clientes' => []
        ];
    }

    return $stats;
}

/**
 * Convierte el día de la semana de inglés a español
 */
function diaSemanaEspanol($fecha)
{
    $dias = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $diaIngles = date('l', strtotime($fecha));
    return isset($dias[$diaIngles]) ? $dias[$diaIngles] : $diaIngles;
}

/**
 * Formatea una fecha completa en español
 */
function fechaCompletaEspanol($timestamp = null)
{
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $dias = [
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes', 
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado',
        'Sunday' => 'Domingo'
    ];
    
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    
    $diaIngles = date('l', $timestamp);
    $mesIngles = date('F', $timestamp);
    $dia = date('d', $timestamp);
    $año = date('Y', $timestamp);
    
    $diaEspanol = isset($dias[$diaIngles]) ? $dias[$diaIngles] : $diaIngles;
    $mesEspanol = isset($meses[$mesIngles]) ? $meses[$mesIngles] : $mesIngles;
    
    return $diaEspanol . ', ' . $dia . ' de ' . $mesEspanol . ' de ' . $año;
}

// Incluir header
include '../includes/header.php';
?>

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
                        <?= fechaCompletaEspanol() ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas -->
        <div class="row g-4 mb-4">
            <!-- Ventas del mes -->
            <div class="col-lg-3 col-md-6">
                <div class="stats-card sales-card">
                    <div class="stats-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= number_format($stats['ventas_mes']) ?></h3>
                        <p class="stats-label">Ventas este mes</p>
                        <span class="stats-trend <?= $stats['ventas_cambio'] >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fas fa-arrow-<?= $stats['ventas_cambio'] >= 0 ? 'up' : 'down' ?>"></i> 
                            <?= abs($stats['ventas_cambio']) ?>%
                        </span>
                        <div class="stats-extra">
                            <small class="text-muted">Hoy: <?= $stats['ventas_hoy'] ?> ventas</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ingresos del mes -->
            <div class="col-lg-3 col-md-6">
                <div class="stats-card revenue-card">
                    <div class="stats-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number">$<?= number_format($stats['ingresos_mes']) ?></h3>
                        <p class="stats-label">Ingresos del mes</p>
                        <span class="stats-trend <?= $stats['ingresos_cambio'] >= 0 ? 'positive' : 'negative' ?>">
                            <i class="fas fa-arrow-<?= $stats['ingresos_cambio'] >= 0 ? 'up' : 'down' ?>"></i> 
                            <?= abs($stats['ingresos_cambio']) ?>%
                        </span>
                        <div class="stats-extra">
                            <small class="text-muted">Hoy: $<?= number_format($stats['ingresos_hoy']) ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Préstamos activos -->
            <div class="col-lg-3 col-md-6">
                <div class="stats-card loans-card">
                    <div class="stats-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number"><?= number_format($stats['prestamos_activos']) ?></h3>
                        <p class="stats-label">Préstamos activos</p>
                        <?php if ($stats['prestamos_vencidos'] > 0): ?>
                            <span class="stats-trend negative">
                                <i class="fas fa-exclamation-triangle"></i> <?= $stats['prestamos_vencidos'] ?> vencidos
                            </span>
                        <?php else: ?>
                            <span class="stats-trend positive">
                                <i class="fas fa-check"></i> Sin vencidos
                            </span>
                        <?php endif; ?>
                        <div class="stats-extra">
                            <small class="text-muted"><?= $stats['productos_en_prestamo'] ?> productos</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cuentas por cobrar -->
            <div class="col-lg-3 col-md-6">
                <div class="stats-card debt-card">
                    <div class="stats-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="stats-content">
                        <h3 class="stats-number">$<?= number_format($stats['monto_por_cobrar']) ?></h3>
                        <p class="stats-label">Por cobrar</p>
                        <?php if ($stats['monto_por_cobrar'] > 0): ?>
                            <span class="stats-trend neutral">
                                <i class="fas fa-clock"></i> Pendiente
                            </span>
                        <?php else: ?>
                            <span class="stats-trend positive">
                                <i class="fas fa-check"></i> Al día
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Accesos Rápidos -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bolt me-2"></i>Accesos Rápidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <a href="ventas/" class="quick-action-card">
                                    <div class="quick-action-icon sales">
                                        <i class="fas fa-plus"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Nueva Venta</h6>
                                        <p>Registrar nueva venta</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="prestamos/" class="quick-action-card">
                                    <div class="quick-action-icon loans">
                                        <i class="fas fa-handshake"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Nuevo Préstamo</h6>
                                        <p>Registrar préstamo</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="ventas/" class="quick-action-card">
                                    <div class="quick-action-icon reports">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Ver Ventas</h6>
                                        <p>Historial y reportes</p>
                                    </div>
                                </a>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <a href="usuarios/" class="quick-action-card">
                                    <div class="quick-action-icon settings">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="quick-action-content">
                                        <h6>Usuarios</h6>
                                        <p>Gestionar usuarios</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos y Tablas -->
        <div class="row g-4 mb-4">
            <!-- Ventas Recientes -->
            <div class="col-lg-6">
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
                                                <td>
                                                    <strong><?= date('d/m/Y', strtotime($venta['fecha'])) ?></strong>
                                                    <br><small class="text-muted"><?= diaSemanaEspanol($venta['fecha']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success"><?= $venta['cantidad'] ?></span>
                                                </td>
                                                <td>
                                                    <strong>$<?= number_format($venta['monto']) ?></strong>
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

            <!-- Productos Más Vendidos -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-trophy me-2"></i>
                            Productos más vendidos este mes
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['productos_mas_vendidos'])): ?>
                            <div class="empty-state">
                                <i class="fas fa-trophy"></i>
                                <p>No hay productos vendidos este mes</p>
                            </div>
                        <?php else: ?>
                            <div class="products-ranking">
                                <?php foreach ($stats['productos_mas_vendidos'] as $index => $producto): ?>
                                    <div class="product-rank-item">
                                        <div class="rank-number">
                                            <span class="badge bg-<?= $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'light text-dark') ?>">
                                                #<?= $index + 1 ?>
                                            </span>
                                        </div>
                                        <div class="product-info">
                                            <h6 class="product-name"><?= htmlspecialchars($producto['producto_nombre']) ?></h6>
                                            <small class="text-muted">
                                                <?= $producto['talle'] ? 'Talle: ' . $producto['talle'] . ' | ' : '' ?>
                                                Vendido: <?= $producto['total_vendido'] ?> unidades
                                            </small>
                                        </div>
                                        <div class="product-stats">
                                            <span class="badge bg-success">$<?= number_format($producto['precio_promedio']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Préstamos Pendientes -->
            <div class="col-lg-6">
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
                                    <?php 
                                    $vencido = false;
                                    if ($prestamo['fecha_limite'] && strtotime($prestamo['fecha_limite']) < time() && $prestamo['productos_pendientes'] > 0) {
                                        $vencido = true;
                                    }
                                    ?>
                                    <div class="loan-item <?= $vencido ? 'loan-overdue' : '' ?>">
                                        <div class="loan-info">
                                            <h6 class="loan-client">
                                                <?= htmlspecialchars($prestamo['cliente_nombre']) ?>
                                                <?php if ($vencido): ?>
                                                    <i class="fas fa-exclamation-triangle text-danger ms-1" title="Vencido"></i>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="loan-amount">
                                                <?= $prestamo['total_productos'] ?> productos 
                                                (<?= $prestamo['productos_pendientes'] ?> pendientes)
                                            </p>
                                            <?php if ($prestamo['fecha_limite']): ?>
                                                <small class="text-<?= $vencido ? 'danger' : 'muted' ?>">
                                                    Límite: <?= date('d/m/Y', strtotime($prestamo['fecha_limite'])) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="loan-status">
                                            <span class="badge bg-<?= $vencido ? 'danger' : ($prestamo['estado'] === 'parcial' ? 'info' : 'warning') ?>">
                                                <?= $vencido ? 'Vencido' : ucfirst($prestamo['estado']) ?>
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

            <!-- Mejores Clientes -->
            <div class="col-lg-6">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-star me-2"></i>
                            Mejores clientes (histórico)
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['mejores_clientes'])): ?>
                            <div class="empty-state">
                                <i class="fas fa-star"></i>
                                <p>No hay clientes registrados</p>
                            </div>
                        <?php else: ?>
                            <div class="clients-ranking">
                                <?php foreach ($stats['mejores_clientes'] as $index => $cliente): ?>
                                    <div class="client-rank-item">
                                        <div class="rank-number">
                                            <span class="badge bg-<?= $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'light text-dark') ?>">
                                                #<?= $index + 1 ?>
                                            </span>
                                        </div>
                                        <div class="client-info">
                                            <h6 class="client-name"><?= htmlspecialchars($cliente['cliente_nombre']) ?></h6>
                                            <small class="text-muted">
                                                <?= $cliente['total_compras'] ?> compras | $<?= number_format($cliente['total_gastado']) ?>
                                                <br>Última compra: <?= date('d/m/Y', strtotime($cliente['ultima_compra'])) ?>
                                            </small>
                                        </div>
                                        <div class="client-stats">
                                            <span class="badge bg-success">
                                                $<?= number_format($cliente['total_gastado'] / $cliente['total_compras']) ?> promedio
                                            </span>
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