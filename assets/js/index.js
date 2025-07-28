/**
 * Login Page JavaScript - Sistema Next
 * Funcionalidades específicas para la página de login
 * Fecha: 25/07/2025
 */

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const loginBtn = document.getElementById('loginBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');

    // Toggle password visibility
    if (togglePassword && passwordInput && toggleIcon) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            if (type === 'text') {
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    }

    // Handle form submission
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!loginForm.checkValidity()) {
                e.stopPropagation();
                loginForm.classList.add('was-validated');
                return;
            }

            // Show loading state
            showLoading();
            
            // Prepare form data
            const formData = new FormData(loginForm);
            
            // Submit form
            fetch('controllers/login_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text first to check content
                return response.text();
            })
            .then(text => {
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (error) {
                    console.error('Response is not valid JSON:', text);
                    throw new Error('La respuesta del servidor no es válida');
                }
                
                hideLoading();
                
                if (data.success) {
                    // Success - redirect
                    showSuccessMessage(data.message);
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1000);
                } else {
                    // Error - show message
                    showErrorMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                hideLoading();
                showErrorMessage('Error de conexión. Verifica tu conexión a internet.');
            });
        });
    }

    // Show loading state
    function showLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
        
        if (loginBtn) {
            loginBtn.disabled = true;
            const btnText = loginBtn.querySelector('.btn-text');
            const btnSpinner = loginBtn.querySelector('.btn-spinner');
            
            if (btnText) btnText.style.display = 'none';
            if (btnSpinner) {
                btnSpinner.classList.remove('d-none');
                btnSpinner.classList.add('d-inline-block');
            }
        }
    }

    // Hide loading state
    function hideLoading() {
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }
        
        if (loginBtn) {
            loginBtn.disabled = false;
            const btnText = loginBtn.querySelector('.btn-text');
            const btnSpinner = loginBtn.querySelector('.btn-spinner');
            
            if (btnText) btnText.style.display = 'inline';
            if (btnSpinner) {
                btnSpinner.classList.add('d-none');
                btnSpinner.classList.remove('d-inline-block');
            }
        }
    }

    // Show error message
    function showErrorMessage(message) {
        removeExistingAlerts();
        
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const form = document.getElementById('loginForm');
        if (form) {
            form.insertAdjacentHTML('beforebegin', alertHtml);
        }
    }

    // Show success message
    function showSuccessMessage(message) {
        removeExistingAlerts();
        
        const alertHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const form = document.getElementById('loginForm');
        if (form) {
            form.insertAdjacentHTML('beforebegin', alertHtml);
        }
    }

    // Remove existing alerts
    function removeExistingAlerts() {
        const existingAlerts = document.querySelectorAll('.alert');
        existingAlerts.forEach(alert => alert.remove());
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
