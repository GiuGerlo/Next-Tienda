/**
 * JavaScript para Módulo de Préstamos - Sistema Next
 */

// Variables globales
let tabla;
let contadorProductos = 0;

// Configuración de SweetAlert Toast (igual que ventas)
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
                d.cliente = $('#filtro_cliente').val();
                d.vencidos = $('#filtro_vencidos').is(':checked') ? 'true' : '';
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
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
             '<"row"<"col-sm-12"tr>>' +
             '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        drawCallback: function() {
            // Reinicializar tooltips después de cada redibujado
            $('[data-bs-toggle="tooltip"]').tooltip();
            
            // Agregar clases para préstamos vencidos
            $('#tabla-prestamos tbody tr').each(function() {
                const row = $(this);
                const estadoCell = row.find('td:eq(3)');
                
                if (estadoCell.find('.badge.bg-danger').length > 0) {
                    row.addClass('table-danger');
                } else if (estadoCell.find('.badge.bg-warning').length > 0) {
                    row.addClass('table-warning');
                }
            });
        }
    });
}

/**
 * Inicializar eventos
 */
function inicializarEventos() {
    // Filtros dinámicos - se aplican automáticamente
    $('#filtro_fecha_desde, #filtro_fecha_hasta, #filtro_estado').on('change', function() {
        tabla.ajax.reload();
    });

    $('#filtro_vencidos').on('change', function() {
        tabla.ajax.reload();
    });

    // Limpiar filtros
    $('#limpiar_filtros').on('click', function() {
        $('#filtro_fecha_desde').val('');
        $('#filtro_fecha_hasta').val('');
        $('#filtro_cliente').val('');
        $('#filtro_estado').val('');
        $('#filtro_vencidos').prop('checked', false);
        tabla.ajax.reload();
    });

    // Filtro de cliente dinámico con delay para evitar muchas consultas
    let timeoutFiltroCliente;
    $('#filtro_cliente').on('input', function() {
        clearTimeout(timeoutFiltroCliente);
        timeoutFiltroCliente = setTimeout(() => {
            tabla.ajax.reload();
        }, 500); // Espera 500ms después de que el usuario deje de escribir
    });

    // Autocompletado de cliente
    inicializarAutocompletado();

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

    // Eventos para modal de conversión a venta
    $('#estado_pago_venta').on('change', function() {
        const divMonto = $('#div_monto_pagado_venta');
        const inputMonto = $('#monto_pagado_venta');
        const total = parseFloat($('#total_producto_venta').val());
        
        if ($(this).val() === 'parcial') {
            divMonto.show();
            inputMonto.attr('required', true);
            inputMonto.attr('max', total);
            setTimeout(() => {
                inputMonto.focus();
            }, 100);
        } else {
            divMonto.hide();
            inputMonto.removeAttr('required');
            inputMonto.val('');
        }
    });

    // Confirmar conversión a venta
    $('#confirmarConversionVenta').on('click', function() {
        const form = $('#formConvertirVenta')[0];
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const estadoPago = $('#estado_pago_venta').val();
        const montoPagado = $('#monto_pagado_venta').val();
        const total = parseFloat($('#total_producto_venta').val());

        // Validaciones adicionales
        if (estadoPago === 'parcial') {
            if (!montoPagado || parseFloat(montoPagado) <= 0) {
                Toast.fire({
                    icon: 'error',
                    title: 'Debe especificar un monto válido mayor a 0'
                });
                return;
            }
            
            if (parseFloat(montoPagado) > total) {
                Toast.fire({
                    icon: 'error',
                    title: `El monto no puede ser mayor al total ($${total.toFixed(2)})`
                });
                return;
            }
        }

        // Obtener datos
        const datosVenta = {
            metodo_pago: $('#metodo_pago_venta').val(),
            estado_pago: estadoPago,
            monto_pagado: estadoPago === 'parcial' ? parseFloat(montoPagado) : total
        };

        const detalleId = $('#detalle_id_venta').val();
        const prestamoId = $('#prestamo_id_venta').val();

        // Cerrar modal y procesar
        $('#modalConvertirVenta').modal('hide');
        procesarAccionProducto('comprar', detalleId, prestamoId, datosVenta);
    });
}

/**
 * Inicializar autocompletado de clientes
 */
function inicializarAutocompletado() {
    let timeoutId;
    const inputCliente = $('#filtro_cliente');
    const sugerenciasContainer = $('#sugerencias_cliente');
    
    inputCliente.on('input', function() {
        const termino = $(this).val().trim();
        
        clearTimeout(timeoutId);
        
        if (termino.length < 2) {
            sugerenciasContainer.hide().empty();
            return;
        }
        
        timeoutId = setTimeout(() => {
            buscarClientes(termino);
        }, 300);
    });
    
    // Cerrar sugerencias al hacer click fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#filtro_cliente, #sugerencias_cliente').length) {
            sugerenciasContainer.hide();
        }
    });
    
    // Navegación con teclado
    inputCliente.on('keydown', function(e) {
        const items = sugerenciasContainer.find('.suggestion-item');
        const activeItem = items.filter('.active');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                if (activeItem.length === 0) {
                    items.first().addClass('active');
                } else {
                    const nextItem = activeItem.removeClass('active').next('.suggestion-item');
                    if (nextItem.length) {
                        nextItem.addClass('active');
                    } else {
                        items.first().addClass('active');
                    }
                }
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                if (activeItem.length === 0) {
                    items.last().addClass('active');
                } else {
                    const prevItem = activeItem.removeClass('active').prev('.suggestion-item');
                    if (prevItem.length) {
                        prevItem.addClass('active');
                    } else {
                        items.last().addClass('active');
                    }
                }
                break;
                
            case 'Enter':
                e.preventDefault();
                if (activeItem.length) {
                    seleccionarCliente(activeItem.data('nombre'));
                }
                break;
                
            case 'Escape':
                sugerenciasContainer.hide();
                break;
        }
    });
}

/**
 * Buscar clientes para autocompletado
 */
function buscarClientes(termino) {
    $.get('controllers/buscar_clientes.php', { q: termino })
        .done(function(response) {
            if (response.success) {
                mostrarSugerenciasClientes(response.clientes);
            }
        })
        .fail(function() {
            console.error('Error al buscar clientes');
        });
}

/**
 * Mostrar sugerencias de clientes
 */
function mostrarSugerenciasClientes(clientes) {
    const sugerenciasContainer = $('#sugerencias_cliente');
    
    if (clientes.length === 0) {
        sugerenciasContainer.hide();
        return;
    }
    
    let html = '';
    clientes.forEach(cliente => {
        const iconClass = cliente.prestamos_activos > 0 ? 'fas fa-exclamation-circle text-warning' : 
                         cliente.tiene_prestamos ? 'fas fa-history text-info' : 'fas fa-user text-muted';
        
        html += `
            <div class="suggestion-item" data-nombre="${cliente.nombre}">
                <div class="suggestion-cliente">
                    <i class="${iconClass} me-2"></i>
                    ${cliente.nombre}
                </div>
                <div class="suggestion-detalle">${cliente.detalle}</div>
            </div>
        `;
    });
    
    sugerenciasContainer.html(html).show();
    
    // Agregar evento click a las sugerencias
    sugerenciasContainer.find('.suggestion-item').on('click', function() {
        const nombre = $(this).data('nombre');
        seleccionarCliente(nombre);
    });
}

/**
 * Seleccionar cliente del autocompletado
 */
function seleccionarCliente(nombre) {
    $('#filtro_cliente').val(nombre);
    $('#sugerencias_cliente').hide();
    
    // El filtro se aplicará automáticamente por el evento 'input'
}

/**
 * Configurar fechas por defecto
 */
function configurarFechasPorDefecto() {
    // Configurar fecha actual en zona horaria argentina
    const ahora = new Date();
    // Ajustar a zona horaria argentina (UTC-3)
    const offsetArgentina = -3 * 60; // minutos
    const offsetLocal = ahora.getTimezoneOffset();
    const offsetDiferencia = offsetArgentina - offsetLocal;
    const fechaArgentina = new Date(ahora.getTime() + (offsetDiferencia * 60 * 1000));
    
    // Formatear para input date (YYYY-MM-DD)
    const fechaFormatted = fechaArgentina.toISOString().split('T')[0];
    $('#fecha_prestamo').val(fechaFormatted);
}

/**
 * Limpiar formulario
 */
function limpiarFormulario() {
    $('#formPrestamo')[0].reset();
    $('#productosContainer').empty();
    $('#totalPrestamo').text('0.00');
    $('#modalPrestamoLabel').html('<i class="fas fa-handshake me-2"></i>Nuevo Préstamo');
    contadorProductos = 0;
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
                Toast.fire({
                    icon: 'success',
                    title: response.message
                });
                
                $('#modalPrestamo').modal('hide');
                tabla.ajax.reload();
            } else {
                Toast.fire({
                    icon: 'error',
                    title: response.message
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
        url: 'controllers/obtener_detalle_prestamo.php',
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
        url: 'controllers/obtener_detalle_prestamo.php',
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
    // Contar productos pendientes
    const productosPendientes = productos.filter(p => p.estado_producto === 'pendiente');
    const hayProductosPendientes = productosPendientes.length > 0;
    
    let html = `
        <div class="mb-3">
            <h6>Préstamo #${prestamo.id} - ${prestamo.cliente_nombre}</h6>
            <small class="text-muted">Fecha: ${prestamo.fecha_prestamo}</small>
        </div>
    `;

    // Botones de acción múltiple (solo si hay productos pendientes)
    if (hayProductosPendientes) {
        html += `
            <div class="mb-3 p-3 bg-light rounded">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                <strong>Seleccionar todos los productos pendientes</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            <span id="contadorSeleccionados">0</span> de ${productosPendientes.length} productos seleccionados
                        </small>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-success btn-sm me-2" id="btnDevolverSeleccionados" disabled>
                            <i class="fas fa-undo me-1"></i>Devolver Seleccionados
                        </button>
                        <button class="btn btn-primary btn-sm" id="btnComprarSeleccionados" disabled>
                            <i class="fas fa-shopping-cart me-1"></i>Comprar Seleccionados
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    productos.forEach(function(producto) {
        const estadoClass = producto.estado_producto === 'pendiente' ? 'producto-pendiente' : 
                           producto.estado_producto === 'devuelto' ? 'producto-devuelto' : 'producto-comprado';
        
        const subtotal = producto.precio_unitario * producto.cantidad;

        html += `
            <div class="producto-item product-item ${estadoClass} mb-3 p-3 border rounded">
                <div class="row align-items-center">
        `;

        // Checkbox para productos pendientes
        if (producto.estado_producto === 'pendiente') {
            html += `
                    <div class="col-md-1">
                        <div class="form-check">
                            <input class="form-check-input product-checkbox" type="checkbox" 
                                   value="${producto.id}"
                                   data-detalle-id="${producto.id}" 
                                   data-prestamo-id="${prestamo.id}" 
                                   data-subtotal="${subtotal}">
                        </div>
                    </div>
            `;
        } else {
            html += `<div class="col-md-1"></div>`;
        }

        html += `
                    <div class="col-md-8">
                        <div class="producto-header">
                            <h6 class="mb-1 product-name">${producto.producto_nombre} 
                                <span class="badge bg-${obtenerColorEstado(producto.estado_producto)} ms-2">${producto.estado_producto}</span>
                            </h6>
                        </div>
                        <div class="producto-detalles text-muted">
                            <strong>Talle:</strong> ${producto.talle || 'No especificado'} | 
                            <strong>Cantidad:</strong> ${producto.cantidad} | 
                            <strong>Precio Unit:</strong> $${parseFloat(producto.precio_unitario).toFixed(2)} |
                            <strong>Subtotal:</strong> $<span class="product-total">${subtotal.toFixed(2)}</span>
                        </div>
        `;

        // Información adicional según el estado
        if (producto.estado_producto === 'devuelto' && producto.fecha_devolucion_formato) {
            html += `<small class="text-success"><i class="fas fa-check-circle me-1"></i>Devuelto el: ${producto.fecha_devolucion_formato}</small>`;
        } else if (producto.estado_producto === 'comprado' && producto.venta_id) {
            html += `<small class="text-primary"><i class="fas fa-shopping-cart me-1"></i>Comprado - Venta #${producto.venta_id}</small>`;
        }

        html += `
                    </div>
                    <div class="col-md-3 text-end">
        `;

        // Acciones individuales para productos pendientes
        if (producto.estado_producto === 'pendiente') {
            html += `
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-success btn-sm" onclick="devolverProducto(${producto.id}, ${prestamo.id})" title="Devolver">
                                <i class="fas fa-undo"></i>
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="comprarProducto(${producto.id}, ${prestamo.id}, ${subtotal})" title="Comprar">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
            `;
        }

        html += `
                    </div>
                </div>
            </div>
        `;
    });

    $('#gestionarProductosContent').html(html);
    
    // Configurar eventos para selección múltiple si hay productos pendientes
    if (hayProductosPendientes) {
        configurarSeleccionMultiple(prestamo.id);
    }
    
    $('#modalGestionarProductos').modal('show');
}

/**
 * Configurar eventos para selección múltiple de productos
 */
function configurarSeleccionMultiple(prestamoId) {
    // Evento para "Seleccionar todos"
    $('#selectAll').change(function() {
        const isChecked = $(this).is(':checked');
        $('.product-checkbox').prop('checked', isChecked);
        actualizarBotonesAccion();
    });
    
    // Evento para checkboxes individuales
    $(document).on('change', '.product-checkbox', function() {
        const totalCheckboxes = $('.product-checkbox').length;
        const checkedCheckboxes = $('.product-checkbox:checked').length;
        
        // Actualizar estado del "Seleccionar todos"
        $('#selectAll').prop('checked', checkedCheckboxes === totalCheckboxes);
        $('#selectAll').prop('indeterminate', checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes);
        
        actualizarBotonesAccion();
    });
    
    // Eventos para botones de acción múltiple
    $('#btnDevolverSeleccionados').click(function() {
        const productosSeleccionados = obtenerProductosSeleccionados();
        if (productosSeleccionados.length === 0) {
            Toast.fire({
                icon: 'warning',
                title: 'Selecciona al menos un producto'
            });
            return;
        }
        
        Swal.fire({
            title: '¿Confirmar devolución múltiple?',
            text: `Devolverás ${productosSeleccionados.length} producto(s) seleccionado(s).`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, devolver',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                procesarAccionMultiple('devolver', productosSeleccionados, prestamoId);
            }
        });
    });
    
    $('#btnComprarSeleccionados').click(function() {
        const productosSeleccionados = obtenerProductosSeleccionados();
        if (productosSeleccionados.length === 0) {
            Toast.fire({
                icon: 'warning',
                title: 'Selecciona al menos un producto'
            });
            return;
        }
        
        // Calcular total de productos seleccionados
        let totalGeneral = 0;
        productosSeleccionados.forEach(producto => {
            totalGeneral += parseFloat(producto.total);
        });
        
        // Abrir modal de compra múltiple
        comprarProductosMultiples(productosSeleccionados, prestamoId, totalGeneral);
    });
}

/**
 * Obtener productos seleccionados
 */
function obtenerProductosSeleccionados() {
    const productos = [];
    $('.product-checkbox:checked').each(function() {
        const row = $(this).closest('.product-item');
        productos.push({
            detalle_id: $(this).val(),
            nombre: row.find('.product-name').text(),
            total: parseFloat(row.find('.product-total').text())
        });
    });
    return productos;
}

/**
 * Actualizar estado de botones de acción
 */
function actualizarBotonesAccion() {
    const cantidadSeleccionados = $('.product-checkbox:checked').length;
    $('#btnDevolverSeleccionados, #btnComprarSeleccionados').prop('disabled', cantidadSeleccionados === 0);
    
    // Actualizar contador si existe
    if ($('#contadorSeleccionados').length > 0) {
        $('#contadorSeleccionados').text(cantidadSeleccionados);
    }
}

/**
 * Procesar acción múltiple (devolver varios productos)
 */
function procesarAccionMultiple(accion, productos, prestamoId) {
    mostrarLoading();
    
    const promesas = productos.map(producto => {
        return $.ajax({
            url: 'controllers/gestionar_productos.php',
            type: 'POST',
            data: {
                accion: accion,
                detalle_id: producto.detalle_id,
                prestamo_id: prestamoId
            }
        });
    });
    
    Promise.all(promesas)
        .then(responses => {
            ocultarLoading();
            
            const exitosos = responses.filter(r => r.success).length;
            const errores = responses.filter(r => !r.success).length;
            
            if (exitosos > 0) {
                Toast.fire({
                    icon: 'success',
                    title: `${exitosos} producto(s) procesado(s) correctamente`
                });
                
                // Actualizar tabla y cerrar modal
                tablaPrestamos.ajax.reload();
                $('#modalGestionarProductos').modal('hide');
            }
            
            if (errores > 0) {
                Toast.fire({
                    icon: 'error',
                    title: `${errores} producto(s) no pudieron procesarse`
                });
            }
        })
        .catch(error => {
            ocultarLoading();
            console.error('Error en operación múltiple:', error);
            Toast.fire({
                icon: 'error',
                title: 'Error al procesar productos'
            });
        });
}

/**
 * Comprar productos múltiples
 */
function comprarProductosMultiples(productos, prestamoId, totalGeneral) {
    // Preparar lista de productos para mostrar
    let listaProductos = productos.map(p => `• ${p.nombre}: $${p.total.toFixed(2)}`).join('\n');
    
    // Llenar datos en el modal
    $('#detalle_id_venta').val(productos.map(p => p.detalle_id).join(','));
    $('#prestamo_id_venta').val(prestamoId);
    $('#total_producto_venta').val(totalGeneral);
    $('#total_mostrar_venta').text(totalGeneral.toFixed(2));
    
    // Agregar información de productos múltiples
    $('#productos_info_venta').html(`
        <div class="alert alert-info">
            <strong>Productos seleccionados (${productos.length}):</strong><br>
            ${listaProductos.replace(/\n/g, '<br>')}
            <hr>
            <strong>Total: $${totalGeneral.toFixed(2)}</strong>
        </div>
    `);
    
    // Resetear formulario
    $('#formConvertirVenta')[0].reset();
    $('#detalle_id_venta').val(productos.map(p => p.detalle_id).join(','));
    $('#prestamo_id_venta').val(prestamoId);
    $('#total_producto_venta').val(totalGeneral);
    $('#div_monto_pagado_venta').hide();
    $('#monto_pagado_venta').removeAttr('required');
    
    // Cerrar modal de gestión y mostrar modal de venta
    $('#modalGestionarProductos').modal('hide');
    $('#modalConvertirVenta').modal('show');
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
    // Llenar los datos en el modal
    $('#detalle_id_venta').val(detalleId);
    $('#prestamo_id_venta').val(prestamoId);
    $('#total_producto_venta').val(total);
    $('#total_mostrar_venta').text(total.toFixed(2));
    
    // Resetear formulario
    $('#formConvertirVenta')[0].reset();
    $('#detalle_id_venta').val(detalleId);
    $('#prestamo_id_venta').val(prestamoId);
    $('#total_producto_venta').val(total);
    $('#div_monto_pagado_venta').hide();
    $('#monto_pagado_venta').removeAttr('required');
    
    // Limpiar información de productos múltiples
    $('#productos_info_venta').html('');
    
    // Mostrar modal
    $('#modalConvertirVenta').modal('show');
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
                Toast.fire({
                    icon: 'success',
                    title: response.message
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
                        Toast.fire({
                            icon: 'success',
                            title: response.message
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
    Toast.fire({
        icon: 'error',
        title: mensaje
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
window.eliminarPrestamo = eliminarPrestamo;
