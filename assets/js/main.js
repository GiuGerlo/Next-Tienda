/**
 * Sistema Next - JavaScript Principal
 * Funciones globales y utilidades para el sistema
 * Fecha: 25/07/2025
 */

// Variables globales
let currentTheme = 'light';
let notificationCount = 0;

// Inicialización cuando el DOM está listo
document.addEventListener('DOMContentLoaded', function() {
    initializeSystem();
    initializeTooltips();
    initializeFormValidation();
    initializeDataTables();
    initializeModals();
});

/**
 * Inicialización general del sistema
 */
function initializeSystem() {
    console.log('Sistema Next inicializado correctamente');
    
    // Marcar enlace activo en la navegación
    markActiveNavigation();
    
    // Inicializar contadores automáticos
    initializeCounters();
    
    // Configurar eventos globales
    setupGlobalEvents();
}

/**
 * Marcar navegación activa
 */
function markActiveNavigation() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.replace('../', ''))) {
            link.classList.add('active');
        }
    });
}

/**
 * Inicializar tooltips de Bootstrap
 */
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Validación de formularios
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Mostrar primer campo inválido
                const firstInvalidField = form.querySelector(':invalid');
                if (firstInvalidField) {
                    firstInvalidField.focus();
                    showNotification('Por favor, completa todos los campos requeridos', 'warning');
                }
            }
            
            form.classList.add('was-validated');
        });
    });
}

/**
 * Inicializar DataTables para tablas
 */
function initializeDataTables() {
    if (typeof $ !== 'undefined' && $.fn.DataTable) {
        $('.data-table').each(function() {
            $(this).DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                pageLength: 25,
                order: [[0, 'desc']],
                columnDefs: [
                    { orderable: false, targets: 'no-sort' }
                ]
            });
        });
    }
}

/**
 * Configurar modales
 */
function initializeModals() {
    // Limpiar formularios al cerrar modales
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = modal.querySelectorAll('form');
            forms.forEach(form => {
                form.reset();
                form.classList.remove('was-validated');
            });
        });
    });
}

/**
 * Configurar eventos globales
 */
function setupGlobalEvents() {
    // Confirmación para botones de eliminación
    document.addEventListener('click', function(e) {
        if (e.target.matches('[data-action="delete"], [data-action="delete"] *')) {
            const button = e.target.closest('[data-action="delete"]');
            const itemName = button.getAttribute('data-item') || 'este elemento';
            
            if (!confirm(`¿Estás seguro de que deseas eliminar ${itemName}?`)) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Auto-guardar formularios (opcional)
    setupAutoSave();
    
    // Atajos de teclado
    setupKeyboardShortcuts();
}

/**
 * Inicializar contadores animados
 */
function initializeCounters() {
    const counters = document.querySelectorAll('.stat-number');
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent);
        const duration = 2000; // 2 segundos
        const step = target / (duration / 16); // 60 FPS
        let current = 0;
        
        const timer = setInterval(() => {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(timer);
            }
            counter.textContent = Math.floor(current).toLocaleString('es-AR');
        }, 16);
    });
}

/**
 * Sistema de notificaciones
 */
function showNotification(message, type = 'info', duration = 5000) {
    const alertContainer = document.getElementById('alert-container') || createAlertContainer();
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.setAttribute('role', 'alert');
    
    const iconClass = getAlertIcon(type);
    
    alertDiv.innerHTML = `
        <i class="fas fa-${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-eliminar después del tiempo especificado
    if (duration > 0) {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                const bsAlert = new bootstrap.Alert(alertDiv);
                bsAlert.close();
            }
        }, duration);
    }
}

/**
 * Crear contenedor de alertas si no existe
 */
function createAlertContainer() {
    let container = document.getElementById('alert-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'alert-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    return container;
}

/**
 * Obtener icono para el tipo de alerta
 */
function getAlertIcon(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle',
        'primary': 'star'
    };
    return icons[type] || 'info-circle';
}

/**
 * Auto-guardar formularios
 */
function setupAutoSave() {
    const autoSaveForms = document.querySelectorAll('[data-autosave="true"]');
    
    autoSaveForms.forEach(form => {
        const formId = form.id || 'form_' + Math.random().toString(36).substr(2, 9);
        let timeoutId;
        
        form.addEventListener('input', function() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                saveFormData(formId, new FormData(form));
                showNotification('Datos guardados automáticamente', 'success', 2000);
            }, 2000);
        });
        
        // Cargar datos guardados al iniciar
        loadFormData(formId, form);
    });
}

/**
 * Guardar datos del formulario en localStorage
 */
function saveFormData(formId, formData) {
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    localStorage.setItem(`autosave_${formId}`, JSON.stringify(data));
}

/**
 * Cargar datos del formulario desde localStorage
 */
function loadFormData(formId, form) {
    const savedData = localStorage.getItem(`autosave_${formId}`);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = data[key];
            }
        });
    }
}

/**
 * Configurar atajos de teclado
 */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl + S para guardar
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const activeForm = document.querySelector('form:focus-within');
            if (activeForm) {
                const submitBtn = activeForm.querySelector('[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                    showNotification('Formulario guardado', 'success', 2000);
                }
            }
        }
        
        // Ctrl + N para nuevo
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            const newBtn = document.querySelector('[data-action="new"]');
            if (newBtn) {
                newBtn.click();
            }
        }
        
        // Escape para cerrar modales
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                const bsModal = bootstrap.Modal.getInstance(openModal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        }
    });
}

/**
 * Formatear números como moneda
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS'
    }).format(amount);
}

/**
 * Formatear fechas
 */
function formatDate(date, format = 'dd/mm/yyyy') {
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    
    switch (format) {
        case 'dd/mm/yyyy':
            return `${day}/${month}/${year}`;
        case 'yyyy-mm-dd':
            return `${year}-${month}-${day}`;
        default:
            return d.toLocaleDateString('es-AR');
    }
}

/**
 * Validar email
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Validar CUIT/CUIL (opcional para futuras mejoras)
 */
function validateCUIT(cuit) {
    if (!cuit || cuit.length !== 11) return false;
    
    const digits = cuit.split('').map(Number);
    const multipliers = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
    
    let sum = 0;
    for (let i = 0; i < 10; i++) {
        sum += digits[i] * multipliers[i];
    }
    
    const remainder = sum % 11;
    const checkDigit = remainder < 2 ? remainder : 11 - remainder;
    
    return checkDigit === digits[10];
}

/**
 * Generar ID único
 */
function generateUniqueId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

/**
 * Debounce function para optimizar búsquedas
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Función para hacer peticiones AJAX simplificadas
 */
async function apiRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    try {
        const response = await fetch(url, finalOptions);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        return { success: true, data };
    } catch (error) {
        console.error('API Request Error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * Mostrar loading spinner
 */
function showLoading(element = null) {
    const spinner = document.createElement('div');
    spinner.className = 'loading-spinner';
    spinner.id = 'global-loading';
    
    if (element) {
        element.appendChild(spinner);
    } else {
        document.body.appendChild(spinner);
    }
}

/**
 * Ocultar loading spinner
 */
function hideLoading(element = null) {
    const spinner = element 
        ? element.querySelector('.loading-spinner')
        : document.getElementById('global-loading');
    
    if (spinner) {
        spinner.remove();
    }
}

/**
 * Actualizar estadísticas en tiempo real
 */
function updateStats() {
    apiRequest('../api/stats.php')
        .then(result => {
            if (result.success) {
                const stats = result.data;
                
                // Actualizar contadores en el footer
                const ventasCounter = document.getElementById('footer-ventas-count');
                const prestamosCounter = document.getElementById('footer-prestamos-count');
                
                if (ventasCounter) ventasCounter.textContent = stats.ventas || '0';
                if (prestamosCounter) prestamosCounter.textContent = stats.prestamos || '0';
                
                // Actualizar notificaciones
                updateNotifications(stats.notifications || 0);
            }
        })
        .catch(error => {
            console.error('Error updating stats:', error);
        });
}

/**
 * Actualizar contador de notificaciones
 */
function updateNotifications(count) {
    const badge = document.querySelector('.navbar-nav .badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'inline' : 'none';
    }
}

// Exportar funciones principales para uso global
window.NextSystem = {
    showNotification,
    formatCurrency,
    formatDate,
    validateEmail,
    validateCUIT,
    generateUniqueId,
    debounce,
    apiRequest,
    showLoading,
    hideLoading,
    updateStats
};

// Actualizar estadísticas cada 5 minutos
setInterval(updateStats, 300000);

// Ejecutar primera actualización después de 2 segundos
setTimeout(updateStats, 2000);
