/**
 * JavaScript para el módulo de usuarios
 * Sistema Next - Gestión de Usuarios
 * Incluye DataTable, CRUD completo y SweetAlert
 */

$(document).ready(function() {
    // Variables globales
    let tabla;
    let editandoUsuario = false;
    
    // Verificar si la tabla ya fue inicializada (evitar doble inicialización)
    if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
        $('#tablaUsuarios').DataTable().destroy();
    }
    
    // Configuración de SweetAlert
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    // Inicializar DataTable
    function inicializarTabla() {
        tabla = $('#tablaUsuarios').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
            order: [[0, 'desc']],
            columns: [
                { 
                    data: 'id',
                    width: "60px",
                    className: "text-center"
                },
                { 
                    data: 'nombre_completo'
                },
                { 
                    data: 'email'
                },
                { 
                    data: 'estado',
                    width: "100px",
                    className: "text-center",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const badgeClass = row.activo ? 'estado-activo' : 'estado-inactivo';
                            return `<span class="badge badge-estado ${badgeClass}">${data}</span>`;
                        }
                        return data;
                    }
                },
                { 
                    data: 'fecha_creacion',
                    className: "d-none d-md-table-cell text-center"
                },
                { 
                    data: 'ultimo_acceso',
                    className: "d-none d-lg-table-cell text-center"
                },
                { 
                    data: null,
                    orderable: false,
                    searchable: false,
                    width: "140px",
                    className: "text-center",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const toggleIcon = row.activo ? 'fa-toggle-off' : 'fa-toggle-on';
                            const toggleTitle = row.activo ? 'Desactivar' : 'Activar';
                            const toggleClass = 'btn-toggle';
                            
                            return `
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-accion btn-editar btn-sm" 
                                            onclick="editarUsuario(${row.id})" 
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-accion ${toggleClass} btn-sm" 
                                            onclick="toggleEstadoUsuario(${row.id})" 
                                            title="${toggleTitle}">
                                        <i class="fas ${toggleIcon}"></i>
                                    </button>
                                    <button type="button" class="btn btn-accion btn-eliminar btn-sm" 
                                            onclick="eliminarUsuario(${row.id}, '${row.nombre_completo.replace(/'/g, '\\\'')}')" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                        return '';
                    }
                }
            ],
            ajax: {
                url: 'controllers/obtener_usuario.php',
                type: 'POST',
                data: { action: 'listar' },
                dataSrc: function(json) {
                    if (json.success) {
                        return json.data;
                    } else {
                        // Manejar error de autenticación
                        if (json.redirect) {
                            window.location.href = json.redirect;
                            return [];
                        }
                        
                        Toast.fire({
                            icon: 'error',
                            title: 'Error al cargar usuarios: ' + (json.message || 'Error desconocido')
                        });
                        return [];
                    }
                },
                error: function(xhr, error, thrown) {
                    // Intentar parsear respuesta JSON si existe
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.redirect) {
                            window.location.href = response.redirect;
                            return;
                        }
                    } catch (e) {
                        // No es JSON válido, continuar con error genérico
                    }
                    
                    Toast.fire({
                        icon: 'error',
                        title: 'Error de conexión al cargar usuarios'
                    });
                }
            }
        });
    }

    // Recargar tabla
    function recargarTabla() {
        if (tabla) {
            tabla.ajax.reload(null, false);
        }
    }

    // Limpiar formulario
    function limpiarFormulario() {
        $('#formUsuario')[0].reset();
        $('#usuarioId').val('');
        $('#formUsuario .form-control').removeClass('is-valid is-invalid');
        $('#formUsuario .invalid-feedback').text('');
        editandoUsuario = false;
        
        // Resetear textos del modal
        $('#modalTitulo').text('Nuevo Usuario');
        $('#btnGuardarTexto').text('Crear Usuario');
        $('#passwordRequired').show();
        $('#passwordHelp').text('Mínimo 6 caracteres');
        $('#password').prop('required', true);
    }

    // Validar email en tiempo real
    function validarEmail(email, id = null) {
        return new Promise((resolve) => {
            if (!email) {
                resolve({ valid: false, message: 'Email requerido' });
                return;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                resolve({ valid: false, message: 'Formato de email inválido' });
                return;
            }

            $.post('controllers/validar_email.php', {
                email: email,
                id: id
            })
            .done(function(response) {
                if (response.success && response.disponible) {
                    resolve({ valid: true, message: 'Email disponible' });
                } else {
                    resolve({ valid: false, message: response.message || 'Email no disponible' });
                }
            })
            .fail(function() {
                resolve({ valid: false, message: 'Error al validar email' });
            });
        });
    }

    // Eventos del formulario
    $('#modalUsuario').on('show.bs.modal', function() {
        if (!editandoUsuario) {
            limpiarFormulario();
        }
    });

    // Toggle para mostrar/ocultar contraseña
    $('#togglePassword').click(function() {
        const passwordField = $('#password');
        const icon = $(this).find('i');
        
        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordField.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Validación de email en tiempo real
    let emailTimeout;
    $('#email').on('input', function() {
        const email = $(this).val();
        const emailField = $(this);
        const feedback = emailField.siblings('.invalid-feedback');
        
        clearTimeout(emailTimeout);
        
        if (email) {
            emailTimeout = setTimeout(async function() {
                const id = $('#usuarioId').val() || null;
                const validacion = await validarEmail(email, id);
                
                if (validacion.valid) {
                    emailField.removeClass('is-invalid').addClass('is-valid');
                    feedback.text('');
                } else {
                    emailField.removeClass('is-valid').addClass('is-invalid');
                    feedback.text(validacion.message);
                }
            }, 500);
        } else {
            emailField.removeClass('is-valid is-invalid');
            feedback.text('');
        }
    });

    // Enviar formulario
    $('#formUsuario').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $('#btnGuardar');
        const submitText = $('#btnGuardarTexto');
        const originalText = submitText.text();
        
        // Deshabilitar botón
        submitBtn.prop('disabled', true);
        submitText.html('<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...');
        
        $.ajax({
            url: 'controllers/guardar_usuario.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                const esNuevoUsuario = !$('#usuarioId').val();
                
                Toast.fire({
                    icon: 'success',
                    title: response.message
                });
                
                $('#modalUsuario').modal('hide');
                
                // Si es un nuevo usuario, recargar la página
                if (esNuevoUsuario) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500); // Esperar 1.5 segundos para que se vea el toast
                } else {
                    // Si es edición, solo recargar la tabla
                    recargarTabla();
                }
                
                limpiarFormulario();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        })
        .fail(function(xhr) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión al guardar usuario'
            });
        })
        .always(function() {
            // Rehabilitar botón
            submitBtn.prop('disabled', false);
            submitText.text(originalText);
        });
    });

    // Función global para editar usuario
    window.editarUsuario = function(id) {
        editandoUsuario = true;
        
        $.post('controllers/obtener_usuario.php', {
            action: 'obtener',
            id: id
        })
        .done(function(response) {
            if (response.success) {
                const usuario = response.data;
                
                // Llenar formulario
                $('#usuarioId').val(usuario.id);
                $('#nombreCompleto').val(usuario.nombre_completo);
                $('#email').val(usuario.email);
                $('#activo').prop('checked', usuario.activo);
                
                // Cambiar textos del modal
                $('#modalTitulo').text('Editar Usuario');
                $('#btnGuardarTexto').text('Actualizar Usuario');
                $('#passwordRequired').hide();
                $('#passwordHelp').text('Dejar en blanco para mantener la contraseña actual');
                $('#password').prop('required', false);
                
                // Mostrar modal
                $('#modalUsuario').modal('show');
            } else {
                Toast.fire({
                    icon: 'error',
                    title: response.message
                });
            }
        })
        .fail(function() {
            Toast.fire({
                icon: 'error',
                title: 'Error al cargar datos del usuario'
            });
        });
    };

    // Función global para cambiar estado
    window.toggleEstadoUsuario = function(id) {
        Swal.fire({
            title: '¿Confirmar cambio de estado?',
            text: 'Se cambiará el estado del usuario (activo/inactivo)',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, cambiar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#0969da',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('controllers/toggle_estado_usuario.php', { id: id })
                .done(function(response) {
                    if (response.success) {
                        Toast.fire({
                            icon: 'success',
                            title: response.message
                        });
                        recargarTabla();
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: response.message
                        });
                    }
                })
                .fail(function() {
                    Toast.fire({
                        icon: 'error',
                        title: 'Error al cambiar estado del usuario'
                    });
                });
            }
        });
    };

    // Función global para eliminar usuario
    window.eliminarUsuario = function(id, nombre) {
        Swal.fire({
            title: '¿Estás seguro?',
            html: `Se eliminará permanentemente el usuario:<br><strong>${nombre}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#d1242f',
            cancelButtonColor: '#6c757d',
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Segunda confirmación
                Swal.fire({
                    title: 'Confirmación final',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar definitivamente',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#d1242f',
                    cancelButtonColor: '#6c757d'
                }).then((secondResult) => {
                    if (secondResult.isConfirmed) {
                        $.post('controllers/eliminar_usuario.php', { id: id })
                        .done(function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Eliminado',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                                recargarTabla();
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message
                                });
                            }
                        })
                        .fail(function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error de conexión al eliminar usuario'
                            });
                        });
                    }
                });
            }
        });
    };

    // Inicializar aplicación
    inicializarTabla();

    // Evento para el botón de nuevo usuario
    $('.btn-nuevo-usuario').click(function() {
        limpiarFormulario();
    });

    // Validación adicional en tiempo real
    $('#nombreCompleto').on('input', function() {
        const valor = $(this).val().trim();
        if (valor.length >= 2) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('El nombre debe tener al menos 2 caracteres');
        }
    });

    $('#password').on('input', function() {
        const valor = $(this).val();
        const esRequerido = $(this).prop('required');
        
        if (!esRequerido && valor === '') {
            $(this).removeClass('is-valid is-invalid');
            return;
        }
        
        if (valor.length >= 6) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
            $(this).siblings('.invalid-feedback').text('La contraseña debe tener al menos 6 caracteres');
        }
    });
});