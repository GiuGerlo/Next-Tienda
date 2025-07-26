/**
 * Register Page JavaScript - Sistema Next
 * Funcionalidades específicas para la página de registro
 * Fecha: 26/07/2025
 */

document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const registerForm = document.getElementById('registerForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const registerBtn = document.getElementById('registerBtn');
    const btnText = registerBtn.querySelector('.btn-text');
    const btnSpinner = registerBtn.querySelector('.btn-spinner');

    // Inicializar validaciones
    initializeValidation();
    initializeFormSubmission();

    /**
     * Inicializa la validación del formulario
     */
    function initializeValidation() {
        const inputs = registerForm.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            // Validación en tiempo real
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    validateField(input);
                }
            });
        });

        // Validación especial para confirmación de contraseña
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);
        passwordInput.addEventListener('input', validatePasswordMatch);
    }

    /**
     * Valida un campo específico
     */
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Limpiar estados previos
        field.classList.remove('is-valid', 'is-invalid', 'animate-error', 'animate-success');

        switch (field.type) {
            case 'text':
                if (field.name === 'nombre_completo') {
                    if (value.length < 2) {
                        isValid = false;
                        errorMessage = 'El nombre debe tener al menos 2 caracteres.';
                    } else if (!/^[a-zA-ZÀ-ÿ\s]+$/.test(value)) {
                        isValid = false;
                        errorMessage = 'El nombre solo puede contener letras y espacios.';
                    }
                }
                break;

            case 'email':
                if (!isValidEmail(value)) {
                    isValid = false;
                    errorMessage = 'Por favor, ingresa un email válido.';
                }
                break;

            case 'password':
                if (field.name === 'confirm_password') {
                    if (value !== passwordInput.value) {
                        isValid = false;
                        errorMessage = 'Las contraseñas no coinciden.';
                    }
                }
                break;

            case 'checkbox':
                if (field.name === 'terms' && !field.checked) {
                    isValid = false;
                    errorMessage = 'Debes aceptar los términos y condiciones.';
                }
                break;
        }

        // Aplicar resultado de validación
        if (isValid) {
            field.classList.add('is-valid', 'animate-success');
            hideFieldError(field);
        } else {
            field.classList.add('is-invalid', 'animate-error');
            showFieldError(field, errorMessage);
        }

        return isValid;
    }

    /**
     * Valida que las contraseñas coincidan
     */
    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;

        if (confirmPassword && password !== confirmPassword) {
            confirmPasswordInput.classList.add('is-invalid');
            showFieldError(confirmPasswordInput, 'Las contraseñas no coinciden.');
        } else if (confirmPassword) {
            confirmPasswordInput.classList.remove('is-invalid');
            confirmPasswordInput.classList.add('is-valid');
            hideFieldError(confirmPasswordInput);
        }
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
     * Oculta error en un campo
     */
    function hideFieldError(field) {
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.style.display = 'none';
        }
    }


    /**
     * Inicializa el manejo del envío del formulario
     */
    function initializeFormSubmission() {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar todos los campos
            const inputs = this.querySelectorAll('input[required]');
            let isFormValid = true;

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isFormValid = false;
                }
            });

            if (isFormValid) {
                submitForm();
            } else {
                // Hacer scroll al primer error
                const firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                    firstError.focus();
                }
            }
        });
    }

    /**
     * Envía el formulario
     */
    function submitForm() {
        // Deshabilitar botón y mostrar spinner
        registerBtn.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.classList.remove('d-none');

        // Crear FormData
        const formData = new FormData(registerForm);

        // Enviar datos
        fetch('controllers/register_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                setTimeout(() => {
                    window.location.href = 'index.php?registered=1';
                }, 2000);
            } else {
                showErrorMessage(data.message);
                
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
            showErrorMessage('Ocurrió un error inesperado. Por favor, intenta nuevamente.');
        })
        .finally(() => {
            // Rehabilitar botón
            registerBtn.disabled = false;
            btnText.style.display = 'inline';
            btnSpinner.classList.add('d-none');
        });
    }

    /**
     * Muestra mensaje de éxito
     */
    function showSuccessMessage(message) {
        showAlert(message, 'success');
    }

    /**
     * Muestra mensaje de error
     */
    function showErrorMessage(message) {
        showAlert(message, 'danger');
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
        const registerCard = document.querySelector('.register-card');
        const form = document.getElementById('registerForm');
        registerCard.insertBefore(alertDiv, form);

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
});

/**
 * Toggle password visibility
 */
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * Animación de entrada para el logo
 */
window.addEventListener('load', function() {
    const logo = document.querySelector('.register-logo');
    if (logo) {
        logo.style.animation = 'logoFloat 3s ease-in-out infinite';
    }
});
