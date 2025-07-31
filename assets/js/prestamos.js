/**
 * JavaScript para Módulo de Préstamos - Sistema Next
 */

// Variables globales
let tabla;
let contadorProductos = 0;
let prestamoEnEdicion = null;

// Verificar que todas las librerías estén cargadas y entonces inicializar
function esperarLibrerias(callback) {
    if (typeof $ !== 'undefined' && typeof $.fn.dataTable !== 'undefined' && typeof Swal !== 'undefined') {
        callback();
    } else {
        setTimeout(() => esperarLibrerias(callback), 100);
    }
}

// Inicializar cuando todas las librerías estén listas
esperarLibrerias(() => {
    $(document).ready(function() {
        // Inicializar componentes
        inicializarTabla();
        inicializarEventos();
        configurarFechasPorDefecto();
    });
});

/**
 * Inicializar DataTable de préstamos
 */
function inicializarTabla() {
    tabla = $('#tabla-prestamos').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'controllers/obtener_prestamos.php',
            type: 'GET',
            data: function(d) {
                d.fecha_desde = $('#filtro_fecha_desde').val();
                d.fecha_hasta = $('#filtro_fecha_hasta').val();
                d.estado = $('#filtro_estado').val();
            }
        },
        columns: [
            { data: 0, title: 'ID', width: '60px' },
            { data: 1, title: 'Cliente' },
            { data: 2, title: 'Fecha', width: '100px' },
            { data: 3, title: 'Estado', width: '100px', orderable: false },
            { data: 4, title: 'Productos', width: '80px' },
            { data: 5, title: 'Progreso', orderable: false },
            { data: 6, title: 'Acciones', width: '120px', orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        responsive: true,
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            // Reinicializar tooltips después de cada redibujado
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
}

/**
 * Inicializar eventos
 */
function inicializarEventos() {
    // Filtros
    $('#aplicar_filtros').on('click', function() {
        tabla.ajax.reload();
    });

    $('#limpiar_filtros').on('click', function() {
        $('#filtro_fecha_desde').val('');
        $('#filtro_fecha_hasta').val('');
        $('#filtro_estado').val('');
        tabla.ajax.reload();
    });

    // Nuevo préstamo
    $('#modalPrestamo').on('show.bs.modal', function() {
        limpiarFormulario();
        agregarFilaProducto();
    });

    // Agregar producto
    $('#agregarProducto').on('click', function() {
        agregarFilaProducto();
    });

    // Guardar préstamo
    $('#guardarPrestamo').on('click', function() {
        guardarPrestamo();
    });

    // Eventos delegados para productos
    $(document).on('click', '.eliminar-producto', function() {
        $(this).closest('tr').remove();
        calcularTotal();
    });

    $(document).on('input', '.cantidad-producto, .precio-producto', function() {
        calcularSubtotal($(this).closest('tr'));
        calcularTotal();
    });
}

/**
 * Configurar fechas por defecto
 */
function configurarFechasPorDefecto() {
    const hoy = new Date().toISOString().split('T')[0];
    $('#fecha_prestamo').val(hoy);
}

/**
 * Limpiar formulario
 */
function limpiarFormulario() {
    $('#formPrestamo')[0].reset();
    $('#prestamo_id').val('');
    $('#productosContainer').empty();
    $('#totalPrestamo').text('0.00');
    $('#modalPrestamoLabel').html('<i class="fas fa-handshake me-2"></i>Nuevo Préstamo');
    contadorProductos = 0;
    prestamoEnEdicion = null;
    configurarFechasPorDefecto();
}

/**
 * Agregar fila de producto
 */
function agregarFilaProducto() {
    contadorProductos++;
    const html = `
        <tr>
            <td>
                <input type="text" class="form-control nombre-producto" name="productos[${contadorProductos}][nombre]" required>
            </td>
            <td>
                <select class="form-select talle-producto" name="productos[${contadorProductos}][talle]">
                    <option value="">Talle</option>
                    <option value="XS">XS</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="XXL">XXL</option>
                    <option value="34">34</option>
                    <option value="36">36</option>
                    <option value="38">38</option>
                    <option value="40">40</option>
                    <option value="42">42</option>
                    <option value="44">44</option>
                    <option value="46">46</option>
                    <option value="48">48</option>
                    <option value="50">50</option>
                </select>
            </td>
            <td>
                <input type="number" class="form-control cantidad-producto" name="productos[${contadorProductos}][cantidad]" min="1" value="1" required>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">$</span>
                    <input type="number" class="form-control precio-producto" name="productos[${contadorProductos}][precio]" min="0" step="0.01" required>
                </div>
            </td>
            <td class="text-end">
                <span class="subtotal-producto">$0.00</span>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger eliminar-producto" title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    $('#productosContainer').append(html);
}

/**
 * Calcular subtotal de un producto
 */
function calcularSubtotal(fila) {
    const cantidad = parseFloat(fila.find('.cantidad-producto').val()) || 0;
    const precio = parseFloat(fila.find('.precio-producto').val()) || 0;
    const subtotal = cantidad * precio;
    fila.find('.subtotal-producto').text('$' + subtotal.toFixed(2));
}

/**
 * Calcular total del préstamo
 */
function calcularTotal() {
    let total = 0;
    $('.subtotal-producto').each(function() {
        const subtotal = parseFloat($(this).text().replace('$', '')) || 0;
        total += subtotal;
    });
    $('#totalPrestamo').text(total.toFixed(2));
}

/**
 * Guardar préstamo
 */
function guardarPrestamo() {
    if (!validarFormulario()) {
        return;
    }

    const formData = new FormData($('#formPrestamo')[0]);
    
    mostrarLoading();

    $.ajax({
        url: 'controllers/guardar_prestamo.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2000
                });
                
                $('#modalPrestamo').modal('hide');
                tabla.ajax.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.message
                });
            }
        },
        error: function() {
            ocultarLoading();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error de conexión. Por favor, inténtalo de nuevo.'
            });
        }
    });
}

/**
 * Validar formulario
 */
function validarFormulario() {
    const cliente = $('#cliente_nombre').val().trim();
    const fecha = $('#fecha_prestamo').val();
    const productos = $('#productosContainer tr').length;

    if (!cliente) {
        mostrarError('El nombre del cliente es obligatorio');
        return false;
    }

    if (!fecha) {
        mostrarError('La fecha del préstamo es obligatoria');
        return false;
    }

    if (productos === 0) {
        mostrarError('Debe agregar al menos un producto');
        return false;
    }

    // Validar productos
    let productosValidos = true;
    $('#productosContainer tr').each(function() {
        const nombre = $(this).find('.nombre-producto').val().trim();
        const cantidad = $(this).find('.cantidad-producto').val();
        const precio = $(this).find('.precio-producto').val();

        if (!nombre || !cantidad || cantidad <= 0 || !precio || precio <= 0) {
            productosValidos = false;
            return false;
        }
    });

    if (!productosValidos) {
        mostrarError('Todos los productos deben tener nombre, cantidad y precio válidos');
        return false;
    }

    return true;
}

/**
 * Ver detalles del préstamo
 */
function verPrestamo(id) {
    mostrarLoading();

    $.ajax({
        url: 'controllers/obtener_prestamo.php',
        type: 'POST',
        data: { prestamo_id: id },
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                mostrarDetallesPrestamo(response.prestamo, response.productos, response.total_valor);
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al cargar los detalles del préstamo');
        }
    });
}

/**
 * Mostrar detalles del préstamo en modal
 */
function mostrarDetallesPrestamo(prestamo, productos, totalValor) {
    let html = `
        <div class="row">
            <div class="col-md-6">
                <div class="detalle-item">
                    <div class="detalle-label">Cliente:</div>
                    <div class="detalle-valor">${prestamo.cliente_nombre}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Fecha de Préstamo:</div>
                    <div class="detalle-valor">${prestamo.fecha_prestamo}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Fecha Límite:</div>
                    <div class="detalle-valor">${prestamo.fecha_limite || 'No especificada'}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="detalle-item">
                    <div class="detalle-label">Estado:</div>
                    <div class="detalle-valor">
                        <span class="badge bg-${obtenerColorEstado(prestamo.estado)}">${prestamo.estado}</span>
                    </div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Total de Productos:</div>
                    <div class="detalle-valor">${prestamo.total_productos}</div>
                </div>
                <div class="detalle-item">
                    <div class="detalle-label">Valor Referencial:</div>
                    <div class="detalle-valor">$${totalValor.toFixed(2)}</div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h6>Progreso de Productos:</h6>
                <div class="row">
                    <div class="col-md-4">
                        <small class="text-warning">● Pendientes: ${prestamo.productos_pendientes}</small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-success">● Devueltos: ${prestamo.productos_devueltos}</small>
                    </div>
                    <div class="col-md-4">
                        <small class="text-primary">● Comprados: ${prestamo.productos_comprados}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <h6>Productos del Préstamo:</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Talle</th>
                                <th>Cantidad</th>
                                <th>Precio Ref.</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    productos.forEach(function(producto) {
        html += `
            <tr>
                <td>${producto.producto_nombre}</td>
                <td>${producto.talle || '-'}</td>
                <td>${producto.cantidad}</td>
                <td>$${parseFloat(producto.precio_unitario).toFixed(2)}</td>
                <td>
                    <span class="badge bg-${obtenerColorEstado(producto.estado_producto)}">${producto.estado_producto}</span>
                </td>
            </tr>
        `;
    });

    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    if (prestamo.observaciones) {
        html += `
            <div class="row mt-3">
                <div class="col-12">
                    <div class="detalle-item">
                        <div class="detalle-label">Observaciones:</div>
                        <div class="detalle-valor">${prestamo.observaciones}</div>
                    </div>
                </div>
            </div>
        `;
    }

    $('#detallesPrestamoContent').html(html);
    $('#modalDetallesPrestamo').modal('show');
}

/**
 * Gestionar productos del préstamo
 */
function gestionarProductos(id) {
    mostrarLoading();

    $.ajax({
        url: 'controllers/obtener_prestamo.php',
        type: 'POST',
        data: { prestamo_id: id },
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                mostrarGestionProductos(response.prestamo, response.productos);
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al cargar los productos del préstamo');
        }
    });
}

/**
 * Mostrar gestión de productos en modal
 */
function mostrarGestionProductos(prestamo, productos) {
    let html = `
        <div class="mb-3">
            <h6>Préstamo #${prestamo.id} - ${prestamo.cliente_nombre}</h6>
            <small class="text-muted">Fecha: ${prestamo.fecha_prestamo}</small>
        </div>
    `;

    productos.forEach(function(producto) {
        const estadoClass = producto.estado_producto === 'pendiente' ? 'producto-pendiente' : 
                           producto.estado_producto === 'devuelto' ? 'producto-devuelto' : 'producto-comprado';

        html += `
            <div class="producto-item ${estadoClass}">
                <div class="producto-header">
                    <div class="producto-nombre">${producto.producto_nombre}</div>
                    <div class="estado-producto ${producto.estado_producto}">${producto.estado_producto}</div>
                </div>
                <div class="producto-detalles">
                    <strong>Talle:</strong> ${producto.talle || 'No especificado'} | 
                    <strong>Cantidad:</strong> ${producto.cantidad} | 
                    <strong>Precio Ref:</strong> $${parseFloat(producto.precio_unitario).toFixed(2)}
                </div>
        `;

        if (producto.estado_producto === 'pendiente') {
            html += `
                <div class="producto-acciones">
                    <button class="btn btn-sm btn-success me-2" onclick="devolverProducto(${producto.id}, ${prestamo.id})">
                        <i class="fas fa-undo me-1"></i>Devolver
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="comprarProducto(${producto.id}, ${prestamo.id}, ${producto.precio_unitario * producto.cantidad})">
                        <i class="fas fa-shopping-cart me-1"></i>Comprar
                    </button>
                </div>
            `;
        } else if (producto.estado_producto === 'devuelto' && producto.fecha_devolucion_formato) {
            html += `<div class="text-muted"><small>Devuelto el: ${producto.fecha_devolucion_formato}</small></div>`;
        } else if (producto.estado_producto === 'comprado' && producto.venta_id) {
            html += `<div class="text-muted"><small>Comprado - Venta #${producto.venta_id}</small></div>`;
        }

        html += `</div>`;
    });

    $('#gestionarProductosContent').html(html);
    $('#modalGestionarProductos').modal('show');
}

/**
 * Devolver producto
 */
function devolverProducto(detalleId, prestamoId) {
    Swal.fire({
        title: '¿Confirmar devolución?',
        text: 'Marcarás este producto como devuelto.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, devolver',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            procesarAccionProducto('devolver', detalleId, prestamoId);
        }
    });
}

/**
 * Comprar producto
 */
function comprarProducto(detalleId, prestamoId, total) {
    Swal.fire({
        title: 'Convertir a Venta',
        html: `
            <div class="text-start">
                <div class="mb-3">
                    <label class="form-label">Método de Pago:</label>
                    <select id="metodo_pago_compra" class="form-select">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta_debito">Tarjeta de Débito</option>
                        <option value="tarjeta_credito">Tarjeta de Crédito</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cuenta_corriente">Cuenta Corriente</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Estado del Pago:</label>
                    <select id="estado_pago_compra" class="form-select">
                        <option value="completo">Pago Completo</option>
                        <option value="parcial">Pago Parcial</option>
                    </select>
                </div>
                <div class="mb-3" id="div_monto_pagado" style="display: none;">
                    <label class="form-label">Monto Pagado:</label>
                    <input type="number" id="monto_pagado_compra" class="form-control" min="0" max="${total}" step="0.01">
                    <small class="text-muted">Total: $${total.toFixed(2)}</small>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Confirmar Compra',
        cancelButtonText: 'Cancelar',
        didOpen: () => {
            $('#estado_pago_compra').on('change', function() {
                if ($(this).val() === 'parcial') {
                    $('#div_monto_pagado').show();
                    $('#monto_pagado_compra').attr('required', true);
                } else {
                    $('#div_monto_pagado').hide();
                    $('#monto_pagado_compra').attr('required', false);
                }
            });
        },
        preConfirm: () => {
            const estadoPago = $('#estado_pago_compra').val();
            const montoPagado = $('#monto_pagado_compra').val();

            if (estadoPago === 'parcial' && (!montoPagado || montoPagado <= 0)) {
                Swal.showValidationMessage('Debe especificar el monto pagado');
                return false;
            }

            return {
                metodo_pago: $('#metodo_pago_compra').val(),
                estado_pago: estadoPago,
                monto_pagado: montoPagado || total
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            procesarAccionProducto('comprar', detalleId, prestamoId, result.value);
        }
    });
}

/**
 * Procesar acción de producto
 */
function procesarAccionProducto(accion, detalleId, prestamoId, datosAdicionales = {}) {
    mostrarLoading();

    const data = {
        accion: accion,
        detalle_id: detalleId,
        prestamo_id: prestamoId,
        ...datosAdicionales
    };

    $.ajax({
        url: 'controllers/gestionar_productos.php',
        type: 'POST',
        data: data,
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: response.message,
                    showConfirmButton: false,
                    timer: 2000
                });
                
                $('#modalGestionarProductos').modal('hide');
                tabla.ajax.reload();
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al procesar la acción');
        }
    });
}

/**
 * Editar préstamo
 */
function editarPrestamo(id) {
    mostrarLoading();

    $.ajax({
        url: 'controllers/obtener_prestamo.php',
        type: 'POST',
        data: { prestamo_id: id },
        success: function(response) {
            ocultarLoading();
            
            if (response.success) {
                cargarPrestamoEnFormulario(response.prestamo, response.productos);
            } else {
                mostrarError(response.message);
            }
        },
        error: function() {
            ocultarLoading();
            mostrarError('Error al cargar el préstamo');
        }
    });
}

/**
 * Cargar préstamo en formulario para edición
 */
function cargarPrestamoEnFormulario(prestamo, productos) {
    limpiarFormulario();
    prestamoEnEdicion = prestamo.id;

    // Llenar datos básicos
    $('#prestamo_id').val(prestamo.id);
    $('#cliente_nombre').val(prestamo.cliente_nombre);
    $('#fecha_prestamo').val(prestamo.fecha_prestamo_input);
    $('#fecha_limite').val(prestamo.fecha_limite_input || '');
    $('#observaciones').val(prestamo.observaciones || '');

    // Cargar productos (solo los pendientes, los otros ya están procesados)
    productos.forEach(function(producto) {
        if (producto.estado_producto === 'pendiente') {
            agregarFilaProducto();
            const ultimaFila = $('#productosContainer tr:last');
            ultimaFila.find('.nombre-producto').val(producto.producto_nombre);
            ultimaFila.find('.talle-producto').val(producto.talle || '');
            ultimaFila.find('.cantidad-producto').val(producto.cantidad);
            ultimaFila.find('.precio-producto').val(producto.precio_unitario);
            calcularSubtotal(ultimaFila);
        }
    });

    calcularTotal();

    $('#modalPrestamoLabel').html('<i class="fas fa-edit me-2"></i>Editar Préstamo #' + prestamo.id);
    $('#modalPrestamo').modal('show');
}

/**
 * Eliminar préstamo
 */
function eliminarPrestamo(id) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer. Solo se pueden eliminar préstamos sin productos procesados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            mostrarLoading();

            $.ajax({
                url: 'controllers/eliminar_prestamo.php',
                type: 'POST',
                data: { prestamo_id: id },
                success: function(response) {
                    ocultarLoading();
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Eliminado!',
                            text: response.message,
                            showConfirmButton: false,
                            timer: 2000
                        });
                        
                        tabla.ajax.reload();
                    } else {
                        mostrarError(response.message);
                    }
                },
                error: function() {
                    ocultarLoading();
                    mostrarError('Error al eliminar el préstamo');
                }
            });
        }
    });
}

/**
 * Funciones de utilidad
 */
function obtenerColorEstado(estado) {
    switch (estado) {
        case 'pendiente': return 'warning';
        case 'parcial': return 'info';
        case 'finalizado': return 'success';
        case 'vencido': return 'danger';
        case 'devuelto': return 'success';
        case 'comprado': return 'primary';
        default: return 'secondary';
    }
}

function mostrarError(mensaje) {
    Swal.fire({
        icon: 'error',
        title: 'Error',
        text: mensaje
    });
}

function mostrarLoading() {
    Swal.fire({
        title: 'Procesando...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function ocultarLoading() {
    Swal.close();
}

// Exponer funciones al scope global para que puedan ser llamadas desde el HTML
window.verPrestamo = verPrestamo;
window.gestionarProductos = gestionarProductos;
window.devolverProducto = devolverProducto;
window.comprarProducto = comprarProducto;
window.editarPrestamo = editarPrestamo;
window.eliminarPrestamo = eliminarPrestamo;
