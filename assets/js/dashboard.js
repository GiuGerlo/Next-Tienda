/**
 * Dashboard JavaScript - Sistema Next
 * Funcionalidades específicas para el dashboard
 * Fecha: 26/07/2025
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar funcionalidades del dashboard
    initializeDashboard();
    
    /**
     * Inicializa todas las funcionalidades del dashboard
     */
    function initializeDashboard() {
        // Animaciones de entrada
        animateStatsCards();
        
        // Actualizar fecha y hora
        updateDateTime();
        setInterval(updateDateTime, 60000); // Actualizar cada minuto
        
        // Inicializar tooltips dinámicos
        addDynamicTooltips();
        
        // Inicializar contadores animados
        initializeCounters();
        
        // Auto-refresh de estadísticas cada 5 minutos
        setInterval(refreshStats, 300000);
        
        // Añadir efectos de hover mejorados
        enhanceHoverEffects();

        // Comprobar conexión a internet
        checkConnection();
        
        // Agregar indicador de carga
        addLoadingIndicator();
    }
    
    /**
     * Anima las tarjetas de estadísticas al cargar
     */
    function animateStatsCards() {
        const statsCards = document.querySelectorAll('.stats-card');
        
        statsCards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                
                // Trigger animation
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 50);
                
            }, index * 100);
        });
    }
    
    /**
     * Actualiza la fecha y hora actual
     */
    function updateDateTime() {
        const dateElement = document.querySelector('.dashboard-date');
        if (dateElement) {
            const now = new Date();
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            
            const formattedDate = now.toLocaleDateString('es-ES', options);
            dateElement.innerHTML = `<i class="fas fa-calendar-alt me-2"></i>${formattedDate}`;
        }
    }
    
    /**
     * Inicializa los tooltips de Bootstrap
     */
    function initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    /**
     * Refresca las estadísticas del dashboard
     */
    async function refreshStats() {
        try {
            const response = await fetch('ajax/refresh_stats.php');
            const data = await response.json();
            
            if (data.success) {
                updateStatsCards(data.stats);
                showNotification('Estadísticas actualizadas', 'success');
            }
        } catch (error) {
            console.log('Error al actualizar estadísticas:', error);
        }
    }
    
    /**
     * Actualiza las tarjetas de estadísticas con nuevos datos
     */
    function updateStatsCards(stats) {
        // Actualizar números en las tarjetas
        const elements = {
            ventas: document.querySelector('.sales-card .stats-number'),
            ingresos: document.querySelector('.revenue-card .stats-number'),
            prestamos: document.querySelector('.loans-card .stats-number'),
            deudas: document.querySelector('.debt-card .stats-number')
        };
        
        if (elements.ventas) {
            animateNumber(elements.ventas, stats.ventas_mes);
        }
        if (elements.ingresos) {
            animateNumber(elements.ingresos, stats.ingresos_mes, '$');
        }
        if (elements.prestamos) {
            animateNumber(elements.prestamos, stats.prestamos_activos);
        }
        if (elements.deudas) {
            animateNumber(elements.deudas, stats.monto_por_cobrar, '$');
        }

        // Actualizar tendencias
        updateTrends(stats);
    }

    /**
     * Actualiza las tendencias en las tarjetas
     */
    function updateTrends(stats) {
        const ventasTrend = document.querySelector('.sales-card .stats-trend');
        const ingresosTrend = document.querySelector('.revenue-card .stats-trend');
        
        if (ventasTrend && stats.ventas_cambio !== undefined) {
            updateTrendElement(ventasTrend, stats.ventas_cambio);
        }
        
        if (ingresosTrend && stats.ingresos_cambio !== undefined) {
            updateTrendElement(ingresosTrend, stats.ingresos_cambio);
        }
    }

    /**
     * Actualiza un elemento de tendencia
     */
    function updateTrendElement(element, cambio) {
        const isPositive = cambio >= 0;
        const isNeutral = cambio === 0;
        
        element.className = `stats-trend ${isNeutral ? 'neutral' : (isPositive ? 'positive' : 'negative')}`;
        
        const icon = isNeutral ? 'fas fa-minus' : `fas fa-arrow-${isPositive ? 'up' : 'down'}`;
        element.innerHTML = `<i class="${icon}"></i> ${Math.abs(cambio)}%`;
    }
    
    /**
     * Anima el cambio de números en las estadísticas
     */
    function animateNumber(element, targetValue, prefix = '') {
        const currentValue = parseInt(element.textContent.replace(/[^0-9]/g, '')) || 0;
        const increment = (targetValue - currentValue) / 20;
        let current = currentValue;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= targetValue) || (increment < 0 && current <= targetValue)) {
                current = targetValue;
                clearInterval(timer);
            }
            
            element.textContent = prefix + Math.round(current).toLocaleString();
        }, 50);
    }
    
    /**
     * Mejora los efectos de hover
     */
    function enhanceHoverEffects() {
        // Efecto de parallax suave en las tarjetas
        const cards = document.querySelectorAll('.stats-card, .dashboard-card, .quick-action-card');
        
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Efecto de ondas en los accesos rápidos
        const quickActions = document.querySelectorAll('.quick-action-card');
        quickActions.forEach(action => {
            action.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(248, 251, 96, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s ease-out;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Efectos en rankings
        enhanceRankingEffects();
    }

    /**
     * Mejora los efectos en los rankings
     */
    function enhanceRankingEffects() {
        const rankItems = document.querySelectorAll('.product-rank-item, .client-rank-item, .loan-item');
        
        rankItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    }

    /**
     * Agrega tooltips dinámicos
     */
    function addDynamicTooltips() {
        // Tooltips para préstamos vencidos
        const overdueLoans = document.querySelectorAll('.loan-overdue');
        overdueLoans.forEach(loan => {
            loan.setAttribute('data-bs-toggle', 'tooltip');
            loan.setAttribute('data-bs-placement', 'top');
            loan.setAttribute('title', 'Este préstamo está vencido');
        });

        // Tooltips para rankings
        const rankBadges = document.querySelectorAll('.rank-number .badge');
        rankBadges.forEach((badge, index) => {
            const position = index + 1;
            let title = '';
            if (position === 1) title = '🥇 Primer lugar';
            else if (position === 2) title = '🥈 Segundo lugar';
            else if (position === 3) title = '🥉 Tercer lugar';
            else title = `Posición #${position}`;
            
            badge.setAttribute('data-bs-toggle', 'tooltip');
            badge.setAttribute('data-bs-placement', 'top');
            badge.setAttribute('title', title);
        });

        // Reinicializar tooltips
        initializeTooltips();
    }

    /**
     * Inicializa contadores animados
     */
    function initializeCounters() {
        const statsNumbers = document.querySelectorAll('.stats-number');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const element = entry.target;
                    const text = element.textContent;
                    const number = parseInt(text.replace(/[^0-9]/g, '')) || 0;
                    const prefix = text.replace(/[0-9,]/g, '');
                    
                    animateNumber(element, number, prefix);
                    observer.unobserve(element);
                }
            });
        });
        
        statsNumbers.forEach(number => {
            observer.observe(number);
        });
    }
    
    /**
     * Muestra una notificación temporal
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
        `;
        
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Trigger animation
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 3000);
    }
    
    /**
     * Maneja los clics en los accesos rápidos
     */
    document.querySelectorAll('.quick-action-card').forEach(card => {
        card.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Agregar efecto de clic
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    /**
     * Comprueba la conexión a internet
     */
    function checkConnection() {
        if (!navigator.onLine) {
            showNotification('Sin conexión a internet. Algunas funciones pueden no estar disponibles.', 'warning');
        }
        
        window.addEventListener('online', () => {
            showNotification('Conexión restaurada', 'success');
        });
        
        window.addEventListener('offline', () => {
            showNotification('Conexión perdida', 'warning');
        });
    }

    /**
     * Agrega indicador de carga
     */
    function addLoadingIndicator() {
        const cards = document.querySelectorAll('.dashboard-card, .stats-card');
        cards.forEach(card => {
            card.classList.add('loaded');
        });
    }

    /**
     * Formatea números para mostrar
     */
    function formatNumber(number, prefix = '') {
        if (number >= 1000000) {
            return prefix + (number / 1000000).toFixed(1) + 'M';
        } else if (number >= 1000) {
            return prefix + (number / 1000).toFixed(1) + 'K';
        }
        return prefix + number.toLocaleString();
    }
});

// CSS adicional para las nuevas funcionalidades
const additionalStyle = document.createElement('style');
additionalStyle.textContent = `
    @keyframes ripple {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }

    .stats-card.loaded,
    .dashboard-card.loaded {
        animation: fadeInUp 0.8s ease-out;
    }

    .loan-overdue {
        animation: pulse 2s infinite;
    }

    .rank-number .badge.bg-warning {
        animation: pulse 1.5s infinite;
    }

    .quick-action-card:active {
        transform: scale(0.95) !important;
    }

    /* Mejoras responsivas */
    @media (max-width: 768px) {
        .product-rank-item,
        .client-rank-item {
            flex-direction: column;
            text-align: center;
            gap: 0.5rem;
        }
        
        .rank-number {
            order: -1;
        }
        
        .product-stats,
        .client-stats {
            margin-top: 0.5rem;
        }
    }
`;
document.head.appendChild(additionalStyle);
