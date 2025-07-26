/**
 * Login Page JavaScript - Sistema Next
 * Funcionalidades específicas para la página de login
 * Fecha: 25/07/2025
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordField = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
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
    
    // Validación del formulario
    const form = document.getElementById('loginForm');
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Enfocar primer campo inválido
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        } else {
            // Mostrar loading
            showLoading();
        }
        
        form.classList.add('was-validated');
    });
    
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
