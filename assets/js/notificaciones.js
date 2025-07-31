/**
 * Sistema de Notificaciones para Préstamos - Sistema Next
 */

class NotificacionesPrestamos {
    constructor() {
        this.notificaciones = [];
        this.intervaloActualizacion = 5 * 60 * 1000; // 5 minutos
        this.intervaloId = null;
        this.dropdown = null;
        this.badge = null;
        this.inicializar();
    }

    inicializar() {
        this.crearElementosUI();
        this.cargarNotificaciones();
        this.iniciarActualizacionAutomatica();
        this.configurarEventos();
    }

    crearElementosUI() {
        // Crear el botón de notificaciones en el header
        const headerNav = document.querySelector('.navbar-nav');
        if (headerNav) {
            const notificationContainer = document.createElement('li');
            notificationContainer.className = 'nav-item notifications-panel';
            notificationContainer.innerHTML = `
                <a class="nav-link position-relative" href="#" id="notificationsToggle">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge d-none" id="notificationBadge">0</span>
                </a>
                <div class="notifications-dropdown" id="notificationsDropdown">
                    <div class="notifications-header">
                        <h6><i class="fas fa-bell me-2"></i>Notificaciones de Préstamos</h6>
                    </div>
                    <div id="notificationsContent">
                        <!-- Contenido cargado dinámicamente -->
                    </div>
                </div>
            `;
            headerNav.appendChild(notificationContainer);
            
            this.dropdown = document.getElementById('notificationsDropdown');
            this.badge = document.getElementById('notificationBadge');
        }
    }

    configurarEventos() {
        // Toggle del dropdown
        document.getElementById('notificationsToggle')?.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleDropdown();
        });

        // Cerrar dropdown al hacer click fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notifications-panel')) {
                this.cerrarDropdown();
            }
        });
    }

    async cargarNotificaciones() {
        try {
            // Determinar la ruta correcta según la página actual
            let url = 'prestamos/controllers/obtener_notificaciones.php';
            
            // Si estamos en la página de préstamos, ajustar la ruta
            if (window.location.pathname.includes('prestamos')) {
                url = 'controllers/obtener_notificaciones.php';
            }
            
            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.notificaciones = data.notificaciones;
                this.actualizarUI(data.total);
            }
        } catch (error) {
            console.error('Error al cargar notificaciones:', error);
        }
    }

    actualizarUI(total) {
        // Actualizar badge
        if (this.badge) {
            if (total > 0) {
                this.badge.textContent = total > 99 ? '99+' : total;
                this.badge.classList.remove('d-none');
            } else {
                this.badge.classList.add('d-none');
            }
        }

        // Actualizar contenido del dropdown
        const content = document.getElementById('notificationsContent');
        if (content) {
            content.innerHTML = this.generarHTMLNotificaciones();
        }
    }

    generarHTMLNotificaciones() {
        const { vencidos, proximos_vencer, antiguos } = this.notificaciones;
        let html = '';

        // Préstamos vencidos
        if (vencidos.length > 0) {
            vencidos.forEach(prestamo => {
                html += `
                    <div class="notification-item notification-vencido d-flex">
                        <div class="notification-icon vencido">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Préstamo Vencido</div>
                            <div class="notification-text">
                                Cliente: ${prestamo.cliente_nombre}<br>
                                Vencido hace ${prestamo.dias_vencidos} día(s)
                            </div>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-outline-primary" onclick="irAPrestamo(${prestamo.id})">
                                    Ver Préstamo
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Préstamos próximos a vencer
        if (proximos_vencer.length > 0) {
            proximos_vencer.forEach(prestamo => {
                html += `
                    <div class="notification-item notification-proximo d-flex">
                        <div class="notification-icon proximo">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Préstamo Próximo a Vencer</div>
                            <div class="notification-text">
                                Cliente: ${prestamo.cliente_nombre}<br>
                                Vence en ${prestamo.dias_restantes} día(s)
                            </div>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-outline-warning" onclick="irAPrestamo(${prestamo.id})">
                                    Ver Préstamo
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Préstamos antiguos sin fecha límite
        if (antiguos.length > 0) {
            antiguos.forEach(prestamo => {
                html += `
                    <div class="notification-item notification-antiguo d-flex">
                        <div class="notification-icon antiguo">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="notification-content">
                            <div class="notification-title">Préstamo Antiguo</div>
                            <div class="notification-text">
                                Cliente: ${prestamo.cliente_nombre}<br>
                                Creado: ${prestamo.fecha_prestamo_formato || prestamo.fecha_prestamo}<br>
                                ${prestamo.dias_transcurridos} días sin fecha límite
                            </div>
                            <div class="notification-actions">
                                <button class="btn btn-sm btn-outline-secondary" onclick="irAPrestamo(${prestamo.id})">
                                    Ver Préstamo
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Si no hay notificaciones
        if (html === '') {
            html = `
                <div class="notifications-empty">
                    <i class="fas fa-check-circle"></i>
                    <div>No hay notificaciones pendientes</div>
                    <small>Todos los préstamos están al día</small>
                </div>
            `;
        }

        return html;
    }

    toggleDropdown() {
        if (this.dropdown) {
            const isVisible = this.dropdown.style.display === 'block';
            this.dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                this.cargarNotificaciones(); // Recargar al abrir
            }
        }
    }

    cerrarDropdown() {
        if (this.dropdown) {
            this.dropdown.style.display = 'none';
        }
    }

    iniciarActualizacionAutomatica() {
        this.intervaloId = setInterval(() => {
            this.cargarNotificaciones();
        }, this.intervaloActualizacion);
    }

    detenerActualizacionAutomatica() {
        if (this.intervaloId) {
            clearInterval(this.intervaloId);
            this.intervaloId = null;
        }
    }

    destruir() {
        this.detenerActualizacionAutomatica();
        
        // Remover elementos del DOM
        const container = document.querySelector('.notifications-panel');
        if (container) {
            container.remove();
        }
    }
}

// Función global para navegar a un préstamo específico
function irAPrestamo(prestamoId) {
    // Si estamos en la página de préstamos
    if (window.location.pathname.includes('prestamos')) {
        // Cerrar el dropdown
        document.querySelector('.notifications-dropdown').style.display = 'none';
        
        // Buscar y resaltar el préstamo
        if (typeof tabla !== 'undefined') {
            tabla.search(`#${prestamoId}`).draw();
            
            // Scroll al préstamo después de un momento
            setTimeout(() => {
                const row = tabla.row(`#prestamo-${prestamoId}`);
                if (row.length) {
                    row.node().scrollIntoView({ behavior: 'smooth', block: 'center' });
                    $(row.node()).addClass('table-warning');
                    setTimeout(() => {
                        $(row.node()).removeClass('table-warning');
                    }, 3000);
                }
            }, 500);
        }
    } else {
        // Navegar a la página de préstamos
        window.location.href = `prestamos/index.php?highlight=${prestamoId}`;
    }
}

// Inicializar el sistema de notificaciones cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    // Solo inicializar en páginas del dashboard
    if (document.querySelector('.dashboard-main')) {
        window.notificacionesPrestamos = new NotificacionesPrestamos();
    }
});

// Limpiar al descargar la página
window.addEventListener('beforeunload', function() {
    if (window.notificacionesPrestamos) {
        window.notificacionesPrestamos.destruir();
    }
});
