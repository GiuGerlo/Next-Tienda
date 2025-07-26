        </main>
        
        <!-- Footer -->
        <footer class="bg-white border-top mt-auto">
            <div class="container-fluid">
                
                <!-- Footer principal -->
                <div class="row py-4">
                    
                    <!-- Información de la empresa -->
                    <div class="col-lg-4 col-md-6 mb-4 mb-lg-0">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-store me-2 text-primary fs-4"></i>
                            <h5 class="mb-0 fw-bold">
                                <span class="text-dark">Ne</span><span class="text-primary">xt</span>
                            </h5>
                        </div>
                        <p class="text-muted mb-3">
                            Sistema de gestión integral para tienda de ropa. 
                            Administra ventas, préstamos y usuarios de manera eficiente.
                        </p>
                        <div class="text-muted">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                <small>Chañar Ladeado, Santa Fe, Argentina</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar me-2 text-primary"></i>
                                <small>Desarrollado en Julio 2025</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enlaces rápidos -->
                    <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                        <h6 class="fw-bold text-dark mb-3">Navegación</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <a href="../dashboard/" class="text-muted text-decoration-none">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../ventas/" class="text-muted text-decoration-none">
                                    <i class="fas fa-shopping-cart me-2"></i>Ventas
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../prestamos/" class="text-muted text-decoration-none">
                                    <i class="fas fa-handshake me-2"></i>Préstamos
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="../usuarios/" class="text-muted text-decoration-none">
                                    <i class="fas fa-users me-2"></i>Usuarios
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Estadísticas rápidas -->
                    <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
                        <h6 class="fw-bold text-dark mb-3">Estadísticas del Sistema</h6>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="bg-light rounded p-2">
                                    <div class="text-primary fw-bold fs-5" id="footer-ventas-count">
                                        <?php 
                                        // Aquí podrías hacer una consulta rápida para obtener estadísticas
                                        echo isset($footer_stats['ventas']) ? $footer_stats['ventas'] : '0'; 
                                        ?>
                                    </div>
                                    <small class="text-muted">Ventas</small>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <div class="bg-light rounded p-2">
                                    <div class="text-primary fw-bold fs-5" id="footer-prestamos-count">
                                        <?php 
                                        echo isset($footer_stats['prestamos']) ? $footer_stats['prestamos'] : '0'; 
                                        ?>
                                    </div>
                                    <small class="text-muted">Préstamos</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Última actualización: 
                                <span id="last-update-time">
                                    <?php echo date('d/m/Y H:i'); ?>
                                </span>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Acciones rápidas -->
                    <div class="col-lg-3 col-md-6">
                        <h6 class="fw-bold text-dark mb-3">Acciones Rápidas</h6>
                        <div class="d-grid gap-2">
                            <a href="../ventas/nueva.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Nueva Venta
                            </a>
                            <a href="../prestamos/nuevo.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-handshake me-2"></i>Nuevo Préstamo
                            </a>
                        </div>
                        
                        <!-- Estado del sistema -->
                        <div class="mt-3 p-2 bg-success bg-opacity-10 rounded">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-success me-2" style="width: 8px; height: 8px;"></div>
                                <small class="text-success fw-bold">Sistema Operativo</small>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-server me-1"></i>
                                Servidor: OK
                            </small>
                        </div>
                    </div>
                </div>
                
                <!-- Separador -->
                <hr class="my-0">
                
                <!-- Footer bottom -->
                <div class="row py-3 align-items-center">
                    <div class="col-md-6">
                        <p class="text-muted mb-0 small">
                            <i class="fas fa-copyright me-1"></i>
                            <?php echo date('Y'); ?> Sistema Next. 
                            <span class="d-none d-md-inline">Todos los derechos reservados.</span>
                        </p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <div class="d-flex justify-content-md-end justify-content-start align-items-center">
                            <!-- Información de versión -->
                            <span class="badge bg-secondary me-3">
                                <i class="fas fa-code-branch me-1"></i>
                                v1.0.0
                            </span>
                            
                            <!-- Enlaces útiles -->
                            <div class="btn-group" role="group" aria-label="Enlaces útiles">
                                <a href="#" class="btn btn-link btn-sm text-muted p-1" title="Ayuda" data-bs-toggle="modal" data-bs-target="#helpModal">
                                    <i class="fas fa-question-circle"></i>
                                </a>
                                <a href="#" class="btn btn-link btn-sm text-muted p-1" title="Soporte" data-bs-toggle="modal" data-bs-target="#supportModal">
                                    <i class="fas fa-life-ring"></i>
                                </a>
                                <a href="#" class="btn btn-link btn-sm text-muted p-1" title="Configuración">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Modal de Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="helpModalLabel">
                        <i class="fas fa-question-circle me-2"></i>Ayuda del Sistema
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold">Funciones Principales:</h6>
                            <ul class="list-unstyled">
                                <li class="mb-2">
                                    <i class="fas fa-tachometer-alt text-primary me-2"></i>
                                    <strong>Dashboard:</strong> Vista general de estadísticas
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-shopping-cart text-primary me-2"></i>
                                    <strong>Ventas:</strong> Gestión completa de ventas y pagos
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-handshake text-primary me-2"></i>
                                    <strong>Préstamos:</strong> Control de préstamos y devoluciones
                                </li>
                                <li class="mb-2">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <strong>Usuarios:</strong> Administración de usuarios del sistema
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Soporte -->
    <div class="modal fade" id="supportModal" tabindex="-1" aria-labelledby="supportModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supportModalLabel">
                        <i class="fas fa-life-ring me-2"></i>Soporte Técnico
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Si necesitas ayuda con el sistema, puedes:</p>
                    <ul>
                        <li>Consultar la documentación integrada</li>
                        <li>Contactar al administrador del sistema</li>
                        <li>Reportar errores o sugerencias</li>
                    </ul>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Versión del Sistema:</strong> 1.0.0<br>
                        <strong>Última actualización:</strong> <?php echo date('d/m/Y'); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart.js para gráficos (si es necesario) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Scripts personalizados -->
    <script src="../assets/js/main.js"></script>
    
    <!-- Scripts adicionales específicos de página -->
    <?php if(isset($additional_js)): ?>
        <?php foreach($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
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
