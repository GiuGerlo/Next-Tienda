/**
 * Login Page JavaScript - Sistema Next
 * Funcionalidades específicas para la página de login
 * Fecha: 25/07/2025
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Referencias a elementos del DOM
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const btnText = loginBtn.querySelector('.btn-text');
    const btnSpinner = loginBtn.querySelector('.btn-spinner');
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        // Cambiar icono
        if (type === 'password') {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        } else {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        }
    });
    
    // Manejo del envío del formulario
    loginForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Limpiar alertas y estados de error previos
        clearLoginErrors();

        // Validar campos
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
            showAlert('Por favor, completa todos los campos.', 'danger');
            return;
        }

        if (!isValidEmail(email)) {
            showAlert('Por favor, ingresa un email válido.', 'danger');
            return;
        }

        // Enviar formulario
        submitLogin();
    });
    
    /**
     * Limpia alertas y estados de error en los campos del login
     */
    function clearLoginErrors() {
        // Eliminar alertas
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        // Eliminar clases de error en los campos
        const inputs = loginForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.classList.remove('is-invalid');
            input.classList.remove('animate-error');
            // Limpiar mensajes de error
            const feedback = input.parentNode.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = '';
                feedback.style.display = 'none';
            }
        });
    }
    
    /**
     * Envía el formulario de login
     */
    function submitLogin() {
        // Deshabilitar botón y mostrar spinner
        loginBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.classList.remove('d-none');
        
        // Crear FormData
        const formData = new FormData(loginForm);
        
        // Enviar datos
        fetch('controllers/login_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1500);
            } else {
                showAlert(data.message, 'danger');
                
                // Si hay errores específicos de campos
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = document.querySelector(`[name="${field}"]`);
                        if (input) {
                            input.classList.add('is-invalid');
                            showFieldError(input, data.errors[field]);
                        }
                    });
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Ocurrió un error inesperado. Por favor, intenta nuevamente.', 'danger');
        })
        .finally(() => {
            // Rehabilitar botón
            loginBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.classList.add('d-none');
        });
    }
    
    /**
     * Muestra error en un campo
     */
    function showFieldError(field, message) {
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.textContent = message;
            feedback.style.display = 'block';
        }
    }
    
    /**
     * Muestra alerta
     */
    function showAlert(message, type) {
        // Remover alertas existentes
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
        
        // Crear nueva alerta
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Insertar antes del formulario
        const loginCard = document.querySelector('.login-card');
        const form = document.getElementById('loginForm');
        loginCard.insertBefore(alertDiv, form);
        
        // Hacer scroll a la alerta
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    /**
     * Valida formato de email
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    // Validación en tiempo real
    const emailField = document.getElementById('email');
    emailField.addEventListener('blur', function() {
        if (this.value && !isValidEmail(this.value)) {
            this.setCustomValidity('Por favor, ingresa un email válido');
        } else {
            this.setCustomValidity('');
        }
    });
    
    // Limpiar validación al escribir
    emailField.addEventListener('input', function() {
        this.setCustomValidity('');
    });
    
    passwordField.addEventListener('input', function() {
        this.setCustomValidity('');
    });
    
    // Auto-focus en campo de email si está vacío
    if (!emailField.value.trim()) {
        emailField.focus();
    } else {
        passwordField.focus();
    }
    
    // Efecto de escritura en el subtítulo
    animateText();
});

/**
 * Función para validar email
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Mostrar loading overlay
 */
function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

/**
 * Ocultar loading overlay
 */
function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

/**
 * Animación de texto en el subtítulo
 */
function animateText() {
    const subtitle = document.querySelector('.brand-subtitle');
    const text = subtitle.textContent;
    subtitle.textContent = '';
    
    let i = 0;
    const timer = setInterval(() => {
        subtitle.textContent += text[i];
        i++;
        if (i >= text.length) {
            clearInterval(timer);
        }
    }, 50);
}

/**
 * Easter egg: Konami code
 */
let konamiCode = [];
const konami = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65];

document.addEventListener('keydown', function(e) {
    konamiCode.push(e.keyCode);
    if (konamiCode.length > konami.length) {
        konamiCode.shift();
    }
    
    if (konamiCode.toString() === konami.toString()) {
        // Efecto especial
        document.body.style.animation = 'rainbow 2s infinite';
        setTimeout(() => {
            document.body.style.animation = '';
        }, 4000);
    }
});

/**
 * Agregar animación rainbow para easter egg
 */
const style = document.createElement('style');
style.textContent = `
    @keyframes rainbow {
        0% { filter: hue-rotate(0deg); }
        100% { filter: hue-rotate(360deg); }
    }
`;
document.head.appendChild(style);

/**
 * Credential hints para desarrollo
 */
console.log('🔐 Credenciales de prueba:');
console.log('📧 Email: admin@next.com');
console.log('🔑 Password: admin123');
