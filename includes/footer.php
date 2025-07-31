<?php
// Obtener estadísticas para el footer si no están definidas
if (!isset($footer_stats)) {
    try {
        // Solo obtener estadísticas si tenemos conexión a BD disponible
        if (isset($pdo)) {
            // Ventas del mes
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total_ventas 
                FROM ventas 
                WHERE MONTH(fecha_venta) = MONTH(CURRENT_DATE()) 
                AND YEAR(fecha_venta) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $ventas_mes = $stmt->fetchColumn();

            // Préstamos activos
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prestamos WHERE estado IN ('pendiente', 'parcial')");
            $stmt->execute();
            $prestamos_activos = $stmt->fetchColumn();

            $footer_stats = [
                'ventas_mes' => $ventas_mes,
                'prestamos_activos' => $prestamos_activos
            ];
        } else {
            // Valores por defecto si no hay conexión
            $footer_stats = [
                'ventas_mes' => 0,
                'prestamos_activos' => 0
            ];
        }
    } catch (Exception $e) {
        // En caso de error, usar valores por defecto
        $footer_stats = [
            'ventas_mes' => 0,
            'prestamos_activos' => 0
        ];
    }
}
?>

    <!-- Footer -->
    <footer class="bg-white border-top mt-5">
        <div class="container-fluid">
            <div class="row py-4">
                <!-- Información de la empresa -->
                <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= isset($base_path) ? $base_path : '../' ?>assets/img/logo.jpg" alt="Next Logo" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;" class="me-2">
                        <h5 class="mb-0 fw-bold">
                            <span class="text-dark">Ne</span><span style="color: var(--next-yellow);">xt</span>
                        </h5>
                    </div>
                    <p class="text-muted mb-3">
                        Sistema de gestión integral para tienda de ropa. 
                        Administra ventas, préstamos y usuarios de manera eficiente.
                    </p>
                    <div class="text-muted">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-map-marker-alt me-2" style="color: var(--next-yellow);"></i>
                            <small>Chañar Ladeado, Santa Fe, Argentina</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-calendar me-2" style="color: var(--next-yellow);"></i>
                            <small>Desarrollado por <a href="https://artisansthinking.com" target="_blank"><img src="<?= isset($base_path) ? $base_path : '../' ?>assets/img/logoArtisans.png" width="100px" alt="Logo Artisans"></a></small>
                        </div>
                    </div>
                </div>
                
                <!-- Enlaces rápidos -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h6 class="fw-bold text-dark mb-3">Navegación</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <a href="<?= $base_path ?>dashboard/" class="text-muted text-decoration-none hover-link">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= $base_path ?>dashboard/ventas/" class="text-muted text-decoration-none hover-link">
                                <i class="fas fa-shopping-cart me-2"></i>Ventas
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= $base_path ?>dashboard/prestamos/" class="text-muted text-decoration-none hover-link">
                                <i class="fas fa-handshake me-2"></i>Préstamos
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="<?= $base_path ?>dashboard/usuarios/" class="text-muted text-decoration-none hover-link">
                                <i class="fas fa-users me-2"></i>Usuarios
                            </a>
                        </li>
                    </ul>
                </div>
                
                <!-- Estadísticas rápidas -->
                <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                    <h6 class="fw-bold text-dark mb-3">Estado del Sistema</h6>
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="text-center p-2 rounded" style="background: rgba(248, 251, 96, 0.1);">
                                <div class="fw-bold text-dark"><?= number_format($footer_stats['ventas_mes']) ?></div>
                                <small class="text-muted">Ventas del Mes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center p-2 rounded" style="background: rgba(114, 116, 118, 0.1);">
                                <div class="fw-bold text-dark"><?= number_format($footer_stats['prestamos_activos']) ?></div>
                                <small class="text-muted">Préstamos Activos</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle me-2" style="width: 8px; height: 8px; background: #28a745;"></div>
                            <small class="text-muted">Sistema Operativo</small>
                        </div>
                        <div class="d-flex align-items-center mt-1">
                            <i class="fas fa-server me-2 text-muted" style="font-size: 10px;"></i>
                            <small class="text-muted">Servidor: OK</small>
                        </div>
                    </div>
                </div>
                
                <!-- Información de contacto -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="fw-bold text-dark mb-3">Soporte</h6>
                    <div class="text-muted">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-envelope me-2" style="color: var(--next-yellow);"></i>
                            <small>ggiuliano526@gmail.com</small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-phone me-2" style="color: var(--next-yellow);"></i>
                            <small>+54 9 3468546422</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="row border-top pt-3 pb-2">
                <div class="col-md-6">
                    <small class="text-muted">
                        © 2025 Sistema Next. Todos los derechos reservados.
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        <span class="text-success">
                            <i class="fas fa-circle me-1" style="font-size: 6px;"></i>
                            v1.0.0
                        </span>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <!-- CSS adicional para hover effects -->
    <style>
        .hover-link:hover {
            color: var(--next-yellow) !important;
            transition: color 0.3s ease;
        }
        
        footer {
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            footer .col-lg-4,
            footer .col-lg-2,
            footer .col-lg-3 {
                text-align: center;
                margin-bottom: 2rem;
            }
            
            footer .d-flex {
                justify-content: center;
            }
        }
    </style>
    
    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js para gráficos (si es necesario) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="<?= isset($base_path) ? $base_path : '../' ?>assets/js/main.js"></script>
    
    <!-- Scripts adicionales específicos de página -->
    <?php if(isset($additional_js)): ?>
        <?= $additional_js ?>
    <?php endif; ?>
    
    <!-- Script para actualizar la hora en tiempo real -->
    <script>
        // Actualizar la hora cada minuto
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleString('es-AR', {
                day: '2-digit',
                month: '2-digit', 
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const timeElement = document.getElementById('last-update-time');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // Actualizar inmediatamente y luego cada minuto
        updateTime();
        setInterval(updateTime, 60000);
        
        // Cerrar alerts automáticamente después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
        
        // Confirmar acciones de eliminación
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('[data-action="delete"]');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de que deseas eliminar este elemento?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
