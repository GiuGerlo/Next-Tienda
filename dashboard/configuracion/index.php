<?php
// Iniciar sesión
session_start();

// Incluir middleware de autenticación
require_once '../../controllers/auth.php';

// Verificar autenticación
requireAuth('../../index.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Incluir configuración de base de datos
require_once '../../config/connect.php';

// Configuración de la página
$page_title = 'Configuración';
$current_page = 'configuracion';

// Procesar formulario si se envía
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Actualizar configuraciones
        $configuraciones = [
            'nombre_empresa' => $_POST['nombre_empresa'] ?? '',
            'direccion_empresa' => $_POST['direccion_empresa'] ?? '',
            'telefono_empresa' => $_POST['telefono_empresa'] ?? '',
            'email_empresa' => $_POST['email_empresa'] ?? '',
            'tema_sistema' => $_POST['tema_sistema'] ?? 'light'
        ];

        foreach ($configuraciones as $clave => $valor) {
            $stmt = $pdo->prepare("
                INSERT INTO configuracion (clave, valor) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE 
                valor = VALUES(valor), fecha_actualizacion = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$clave, $valor]);
        }

        $mensaje = 'Configuración actualizada correctamente';
        $tipo_mensaje = 'success';
        
        // Log de la actividad
        $stmt = $pdo->prepare("
            INSERT INTO log_actividades (usuario_id, accion, tabla_afectada, ip_address, user_agent) 
            VALUES (?, 'actualizar_configuracion', 'configuracion', ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

    } catch (Exception $e) {
        $mensaje = 'Error al actualizar la configuración: ' . $e->getMessage();
        $tipo_mensaje = 'danger';
    }
}

// Obtener configuraciones actuales
$configuraciones = [];
try {
    $stmt = $pdo->query("SELECT clave, valor FROM configuracion");
    while ($row = $stmt->fetch()) {
        $configuraciones[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    error_log("Error al obtener configuraciones: " . $e->getMessage());
}

// Valores por defecto si no existen
$config = [
    'nombre_empresa' => $configuraciones['nombre_empresa'] ?? 'Next - Tienda de Ropa',
    'direccion_empresa' => $configuraciones['direccion_empresa'] ?? 'Chañar Ladeado, Santa Fe, Argentina',
    'telefono_empresa' => $configuraciones['telefono_empresa'] ?? '',
    'email_empresa' => $configuraciones['email_empresa'] ?? 'info@next.com',
    'tema_sistema' => $configuraciones['tema_sistema'] ?? 'light',
    'version_sistema' => $configuraciones['version_sistema'] ?? '1.0.0'
];

// Incluir header
include '../../includes/header.php';
?>

<main class="dashboard-main">
    <div class="container-fluid px-4">
        <!-- Header del módulo -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="dashboard-title">
                        <i class="fas fa-cog me-3"></i>Configuración
                    </h1>
                    <p class="dashboard-subtitle">
                        Configuración general del sistema
                    </p>
                </div>
            </div>
        </div>

        <!-- Mostrar mensaje -->
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $tipo_mensaje === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Configuración de la Empresa -->
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-building me-2"></i>
                            Información de la Empresa
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_empresa" class="form-label">Nombre de la Empresa</label>
                                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" 
                                           value="<?= htmlspecialchars($config['nombre_empresa']) ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor ingresa el nombre de la empresa.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email_empresa" class="form-label">Email de Contacto</label>
                                    <input type="email" class="form-control" id="email_empresa" name="email_empresa" 
                                           value="<?= htmlspecialchars($config['email_empresa']) ?>" required>
                                    <div class="invalid-feedback">
                                        Por favor ingresa un email válido.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="direccion_empresa" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion_empresa" name="direccion_empresa" 
                                       value="<?= htmlspecialchars($config['direccion_empresa']) ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono_empresa" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono_empresa" name="telefono_empresa" 
                                       value="<?= htmlspecialchars($config['telefono_empresa']) ?>"
                                       placeholder="Ej: +54 9 11 1234-5678">
                            </div>

                            <div class="mb-3">
                                <label for="tema_sistema" class="form-label">Tema del Sistema</label>
                                <select class="form-select" id="tema_sistema" name="tema_sistema">
                                    <option value="light" <?= $config['tema_sistema'] === 'light' ? 'selected' : '' ?>>
                                        🌞 Claro
                                    </option>
                                    <option value="dark" <?= $config['tema_sistema'] === 'dark' ? 'selected' : '' ?>>
                                        🌙 Oscuro
                                    </option>
                                </select>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        El cambio de tema se aplicará automáticamente al guardar la configuración.
                                        <br>
                                        <strong>Tema actual:</strong> 
                                        <span class="badge bg-<?= $config['tema_sistema'] === 'dark' ? 'dark' : 'light text-dark' ?> ms-1">
                                            <?= $config['tema_sistema'] === 'dark' ? '🌙 Oscuro' : '🌞 Claro' ?>
                                        </span>
                                    </small>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Información del Sistema -->
            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-info-circle me-2"></i>
                            Información del Sistema
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <strong>Versión:</strong>
                            <span class="badge bg-primary ms-2"><?= htmlspecialchars($config['version_sistema']) ?></span>
                        </div>
                        
                        <div class="info-item mb-3">
                            <strong>Zona Horaria:</strong>
                            <br><small class="text-muted">America/Argentina/Buenos_Aires</small>
                        </div>
                        
                        <div class="info-item mb-3">
                            <strong>Fecha y Hora Actual:</strong>
                            <br><small class="text-muted"><?= date('d/m/Y H:i:s') ?></small>
                        </div>

                        <div class="info-item mb-3">
                            <strong>Última Actualización:</strong>
                            <br><small class="text-muted" id="last-update-time">
                                <?= date('d/m/Y H:i') ?>
                            </small>
                        </div>

                        <hr>

                        <div class="d-grid">
                            <a href="../" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas Rápidas -->
                <div class="dashboard-card mt-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estadísticas Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Obtener estadísticas básicas
                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM ventas");
                            $total_ventas = $stmt->fetchColumn();

                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('pendiente', 'parcial')");
                            $prestamos_activos = $stmt->fetchColumn();

                            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = TRUE");
                            $total_usuarios = $stmt->fetchColumn();
                        } catch (Exception $e) {
                            $total_ventas = 0;
                            $prestamos_activos = 0;
                            $total_usuarios = 0;
                        }
                        ?>
                        
                        <div class="stat-item d-flex justify-content-between mb-2">
                            <span>Total Ventas:</span>
                            <strong><?= number_format($total_ventas) ?></strong>
                        </div>
                        
                        <div class="stat-item d-flex justify-content-between mb-2">
                            <span>Préstamos Activos:</span>
                            <strong><?= number_format($prestamos_activos) ?></strong>
                        </div>
                        
                        <div class="stat-item d-flex justify-content-between">
                            <span>Usuarios:</span>
                            <strong><?= number_format($total_usuarios) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
// Preview del tema en tiempo real
document.getElementById('tema_sistema').addEventListener('change', function() {
    const tema = this.value;
    const darkThemeLink = document.querySelector('link[href*="dark-theme.css"]');
    
    if (tema === 'dark') {
        if (!darkThemeLink) {
            // Crear el link para el tema oscuro
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = '../../assets/css/dark-theme.css';
            document.head.appendChild(link);
        }
    } else {
        // Remover el tema oscuro si existe
        if (darkThemeLink) {
            darkThemeLink.remove();
        }
    }
    
    // Mostrar mensaje de preview
    const previewAlert = document.createElement('div');
    previewAlert.className = 'alert alert-info alert-dismissible fade show mt-2';
    previewAlert.innerHTML = `
        <i class="fas fa-eye me-2"></i>
        Vista previa del tema <strong>${tema === 'dark' ? 'Oscuro' : 'Claro'}</strong>. 
        Guarda los cambios para aplicar permanentemente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insertar después del select
    this.parentNode.appendChild(previewAlert);
    
    // Auto-remover después de 3 segundos
    setTimeout(() => {
        if (previewAlert.parentNode) {
            previewAlert.remove();
        }
    }, 3000);
});
</script>

<?php include '../../includes/footer.php'; ?>
