/**
 * JavaScript para el módulo             ajax: {
                url: 'controllers/obtener_ventas.php',
                type: 'GET',ventas
 * Sistema Next - Gestión de Ventas
 * Incluye DataTable, CRUD completo y SweetAlert
 */

$(document).ready(function() {
    // Variables globales
    let tabla;
    let editandoVenta = false;
    
    // Verificar si la tabla ya fue inicializada (evitar doble inicialización)
    if ($.fn.DataTable.isDataTable('#tablaVentas')) {
        $('#tablaVentas').DataTable().destroy();
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
        tabla = $('#tablaVentas').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'controllers/obtener_ventas.php',
                type: 'GET',
                data: function(d) {
                    // Agregar filtros personalizados
                    d.fecha_desde = $('#filtroFechaDesde').val();
                    d.fecha_hasta = $('#filtroFechaHasta').val();
                    d.estado_pago = $('#filtroEstado').val();
                    d.metodo_pago = $('#filtroMetodo').val();
                    
                    // Debug: mostrar los filtros aplicados
                    console.log('Filtros aplicados:', {
                        fecha_desde: d.fecha_desde,
                        fecha_hasta: d.fecha_hasta,
                        estado_pago: d.estado_pago,
                        metodo_pago: d.metodo_pago
                    });
                },
                error: function(xhr, error, thrown) {
                    console.error('Error al cargar ventas:', error);
                    console.error('Respuesta del servidor:', xhr.responseText);
                    Toast.fire({
                        icon: 'error',
                        title: 'Error al cargar las ventas'
                    });
                }
            },
            columns: [
                { data: 0, name: 'id', title: 'ID' },
                { data: 1, name: 'cliente_nombre', title: 'Cliente' },
                { data: 2, name: 'fecha_venta', title: 'Fecha' },
                { data: 3, name: 'total', title: 'Total' },
                { 
                    data: 4, 
                    name: 'estado_pago', 
                    title: 'Estado Pago',
                    className: 'd-none d-md-table-cell'
                },
                { 
                    data: 5, 
                    name: 'metodo_pago', 
                    title: 'Método Pago',
                    className: 'd-none d-lg-table-cell'
                },
                { 
                    data: 6, 
                    name: 'monto_adeudado', 
                    title: 'Adeudado',
                    className: 'd-none d-xl-table-cell'
                },
                { 
                    data: 7, 
                    name: 'acciones', 
                    title: 'Acciones', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [[0, 'desc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            responsive: {
                details: {
                    type: 'inline',
                    display: $.fn.dataTable.Responsive.display.childRowImmediate,
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                 '<"row"<"col-sm-12"tr>>' +
                 '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
            drawCallback: function() {
                // Re-enlazar eventos después de cada redibujado
                enlazarEventosTabla();
            }
        });
    }

    // Cargar estadísticas
    function cargarEstadisticas() {
        $.get('controllers/obtener_estadisticas.php')
            .done(function(response) {
                if (response.success) {
                    $('#totalVentas').text(response.data.total_ventas);
                    $('#ventasHoy').text(response.data.ventas_hoy);
                    $('#pagosPendientes').text(response.data.pagos_pendientes);
                    $('#totalTransacciones').text(response.data.total_transacciones);
                }
            })
            .fail(function() {
                console.error('Error al cargar estadísticas');
            });
    }

    // Funciones globales para los botones de acción (con debug)
    window.verDetalle = function(id) {
        console.log('Ver detalle clicked:', id);
        verDetalleVenta(id);
    };
    
    window.editarVenta = function(id) {
        console.log('Editar venta clicked:', id);
        editarVentaFuncion(id);
    };
    
    window.gestionarPagos = function(id) {
        console.log('Gestionar pagos clicked:', id);
        gestionarPagosFuncion(id);
    };
    
    window.eliminarVenta = function(id) {
        console.log('Eliminar venta clicked:', id);
        eliminarVentaFuncion(id);
    };

    // Enlazar eventos de la tabla (usando delegación de eventos)
    function enlazarEventosTabla() {
        // Usar delegación de eventos para que funcione en responsive
        $(document).off('click', '.ver-detalle').on('click', '.ver-detalle', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            verDetalleVenta(id);
        });

        $(document).off('click', '.gestionar-pagos').on('click', '.gestionar-pagos', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            gestionarPagosFuncion(id);
        });

        $(document).off('click', '.editar-venta').on('click', '.editar-venta', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            editarVentaFuncion(id);
        });

        $(document).off('click', '.eliminar-venta').on('click', '.eliminar-venta', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const id = $(this).data('id');
            eliminarVentaFuncion(id);
        });
    }

    // Ver detalle de venta
    function verDetalleVenta(id) {
        $.get('controllers/obtener_detalle.php', { id: id })
            .done(function(response) {
                if (response.success) {
                    mostrarDetalleVenta(response.venta, response.productos, response.pagos);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: response.message || 'Error al obtener el detalle'
                    });
                }
            })
            .fail(function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Error de conexión'
                });
            });
    }

    // Mostrar modal con detalle de venta
    function mostrarDetalleVenta(venta, productos, pagos) {
        let html = `
            <div class="row">
                <div class="col-md-6 text-center">
                    <h6><i class="fas fa-user me-2"></i>Información del Cliente</h6>
                    <p><strong>Cliente:</strong> ${venta.cliente_nombre}</p>
                    <p><strong>Fecha:</strong> ${venta.fecha_formatted}</p>
                    <p><strong>Total:</strong> $${parseInt(venta.total).toLocaleString('es-AR')}</p>
                </div>
                <div class="col-md-6 text-center">
                    <h6><i class="fas fa-credit-card me-2"></i>Información de Pago</h6>
                    <p><strong>Método:</strong> ${venta.metodo_pago_formatted}</p>
                    <p><strong>Estado:</strong> <span class="badge bg-${venta.estado_pago === 'completo' ? 'success' : venta.estado_pago === 'parcial' ? 'warning' : 'danger'}">${venta.estado_pago_formatted}</span></p>
                    ${venta.monto_adeudado > 0 ? `<p><strong>Adeudado:</strong> <span class="text-danger">$${parseInt(venta.monto_adeudado).toLocaleString('es-AR')}</span></p>` : ''}
                </div>
            </div>
            
            <hr>

            <h6 class="text-center"><i class="fas fa-box me-2"></i>Productos</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Talle</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
        
        productos.forEach(producto => {
            html += `
                <tr>
                    <td>${producto.producto_nombre}</td>
                    <td>${producto.talle || '-'}</td>
                    <td>${producto.cantidad}</td>
                    <td>$${parseInt(producto.precio_unitario).toLocaleString('es-AR')}</td>
                    <td>$${parseInt(producto.subtotal).toLocaleString('es-AR')}</td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;

        if (pagos.length > 0) {
            html += `
                <hr>
                <h6 class="text-center"><i class="fas fa-money-bill me-2"></i>Historial de Pagos</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Monto</th>
                                <th>Método</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            pagos.forEach(pago => {
                html += `
                    <tr>
                        <td>${pago.fecha_pago_formatted}</td>
                        <td>$${parseInt(pago.monto).toLocaleString('es-AR')}</td>
                        <td>${pago.metodo_pago}</td>
                        <td>${pago.usuario_nombre}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }

        if (venta.observaciones) {
            html += `
                <hr>
                <h6><i class="fas fa-comment me-2"></i>Observaciones</h6>
                <p>${venta.observaciones}</p>
            `;
        }

        $('#contenidoDetalleVenta').html(html);
        $('#modalDetalleVenta').modal('show');
    }

    // Eliminar venta con doble confirmación
    function eliminarVentaFuncion(id) {
        // Primera confirmación
        Swal.fire({
            title: '¿Eliminar venta?',
            text: 'Esta acción eliminará permanentemente la venta y todos sus datos relacionados',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fd7e14',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Continuar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Segunda confirmación más específica
                Swal.fire({
                    title: '¡CONFIRMACIÓN FINAL!',
                    html: `
                        <div class="text-danger mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x"></i>
                        </div>
                        <p><strong>¿Estás absolutamente seguro?</strong></p>
                        <p>Esta acción:</p>
                        <ul class="text-start">
                            <li>Eliminará la venta de forma permanente</li>
                            <li>Eliminará todos los productos asociados</li>
                            <li>Eliminará todo el historial de pagos</li>
                            <li><strong>NO se puede deshacer</strong></li>
                        </ul>
                    `,
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'SÍ, ELIMINAR DEFINITIVAMENTE',
                    cancelButtonText: 'No, cancelar',
                    focusCancel: true
                }).then((finalResult) => {
                    if (finalResult.isConfirmed) {
                        // Proceder con la eliminación
                        const btnEliminar = $(`button[data-id="${id}"].eliminar-venta`);
                        const iconoOriginal = btnEliminar.find('i').attr('class');
                        
                        btnEliminar.prop('disabled', true)
                                  .find('i').attr('class', 'fas fa-spinner fa-spin');

                        $.post('controllers/eliminar_venta.php', { id: id })
                            .done(function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: '¡Eliminado!',
                                        text: response.message,
                                        icon: 'success',
                                        timer: 2000,
                                        showConfirmButton: false
                                    });
                                    tabla.ajax.reload();
                                    cargarEstadisticas();
                                } else {
                                    Toast.fire({
                                        icon: 'error',
                                        title: response.message || 'Error al eliminar la venta'
                                    });
                                }
                            })
                            .fail(function() {
                                Toast.fire({
                                    icon: 'error',
                                    title: 'Error de conexión al eliminar la venta'
                                });
                            })
                            .always(function() {
                                btnEliminar.prop('disabled', false)
                                          .find('i').attr('class', iconoOriginal);
                            });
                    }
                });
            }
        });
    }

    // Manejar formulario de venta
    $('#formVenta').on('submit', function(e) {
        e.preventDefault();
        
        // Validar productos
        const productos = obtenerProductos();
        if (productos.length === 0) {
            Toast.fire({
                icon: 'error',
                title: 'Debe agregar al menos un producto'
            });
            return;
        }

        // Validar que todos los productos tengan datos completos
        let productosValidos = true;
        productos.forEach(producto => {
            if (!producto.nombre || !producto.precio || producto.cantidad <= 0) {
                productosValidos = false;
            }
        });

        if (!productosValidos) {
            Toast.fire({
                icon: 'error',
                title: 'Todos los productos deben tener nombre, precio y cantidad válidos'
            });
            return;
        }

        const formData = new FormData(this);
        
        // Agregar productos al FormData
        productos.forEach((producto, index) => {
            formData.append(`productos[${index}][nombre]`, producto.nombre);
            formData.append(`productos[${index}][talle]`, producto.talle);
            formData.append(`productos[${index}][cantidad]`, producto.cantidad);
            formData.append(`productos[${index}][precio]`, producto.precio);
        });

        const btnGuardar = $('#btnGuardar');
        const textoOriginal = btnGuardar.html();
        
        btnGuardar.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Guardando...');

        $.ajax({
            url: 'controllers/guardar_venta.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                Toast.fire({
                    icon: 'success',
                    title: response.message
                });
                $('#modalVenta').modal('hide');
                tabla.ajax.reload();
                cargarEstadisticas();
                limpiarFormulario();
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
                title: 'Error de conexión'
            });
        })
        .always(function() {
            btnGuardar.prop('disabled', false).html(textoOriginal);
        });
    });

    // Obtener productos del formulario
    function obtenerProductos() {
        const productos = [];
        $('.producto-item').each(function() {
            const nombre = $(this).find('.producto-nombre').val().trim();
            const talle = $(this).find('.producto-talle').val().trim();
            const cantidad = parseInt($(this).find('.producto-cantidad').val()) || 0;
            const precio = parseFloat($(this).find('.producto-precio').val()) || 0;

            if (nombre && precio > 0 && cantidad > 0) {
                productos.push({
                    nombre: nombre,
                    talle: talle,
                    cantidad: cantidad,
                    precio: precio
                });
            }
        });
        return productos;
    }

    // Calcular total automáticamente
    function calcularTotal() {
        let total = 0;
        $('.producto-item').each(function() {
            const cantidad = parseFloat($(this).find('.producto-cantidad').val()) || 0;
            const precio = parseFloat($(this).find('.producto-precio').val()) || 0;
            total += cantidad * precio;
        });
        
        $('#totalVenta').val(total.toFixed(2));
        actualizarMontos();
    }

    // Actualizar montos según estado de pago
    function actualizarMontos() {
        const estadoPago = $('#estadoPago').val();
        const total = parseFloat($('#totalVenta').val()) || 0;
        const montoPagadoContainer = $('#montoPagadoContainer');
        const montoPagado = $('#montoPagado');
        const montoAdeudado = $('#montoAdeudado');

        if (estadoPago === 'completo') {
            montoPagadoContainer.hide();
            montoPagado.val(total.toFixed(2));
            montoAdeudado.val('0.00');
        } else if (estadoPago === 'pendiente') {
            montoPagadoContainer.hide();
            montoPagado.val('0.00');
            montoAdeudado.val(total.toFixed(2));
        } else { // parcial
            montoPagadoContainer.show();
            montoPagado.attr('required', true);
            const pagado = parseFloat(montoPagado.val()) || 0;
            montoAdeudado.val((total - pagado).toFixed(2));
        }
    }

    // Agregar producto
    $('#agregarProducto').on('click', function() {
        const nuevoProducto = `
            <div class="producto-item border rounded p-3 mb-2">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <input type="text" class="form-control producto-nombre" 
                               placeholder="Nombre del producto" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-select producto-talle">
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
                    </div>
                    <div class="col-md-2 mb-2">
                        <input type="number" class="form-control producto-cantidad" 
                               placeholder="Cant." min="1" value="1" required>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control producto-precio" 
                                   placeholder="Precio" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="col-md-1 mb-2">
                        <button type="button" class="btn btn-danger btn-sm w-100 eliminar-producto">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#productosContainer').append(nuevoProducto);
        enlazarEventosProductos();
    });

    // Enlazar eventos de productos
    function enlazarEventosProductos() {
        // Eliminar producto
        $('.eliminar-producto').off('click').on('click', function() {
            if ($('.producto-item').length > 1) {
                $(this).closest('.producto-item').remove();
                calcularTotal();
            } else {
                Toast.fire({
                    icon: 'warning',
                    title: 'Debe mantener al menos un producto'
                });
            }
        });

        // Calcular total cuando cambian cantidad o precio
        $('.producto-cantidad, .producto-precio').off('input').on('input', calcularTotal);
    }

    // Estado de pago change
    $('#estadoPago').on('change', actualizarMontos);

    // Monto pagado change
    $('#montoPagado').on('input', function() {
        const total = parseFloat($('#totalVenta').val()) || 0;
        const pagado = parseFloat($(this).val()) || 0;
        $('#montoAdeudado').val((total - pagado).toFixed(2));
    });

    // Limpiar formulario
    function limpiarFormulario() {
        $('#formVenta')[0].reset();
        $('#ventaId').val('');
        $('#modalTitulo').text('Nueva Venta');
        $('#btnGuardarTexto').text('Crear Venta');
        
        // Limpiar productos excepto el primero
        $('.producto-item').not(':first').remove();
        $('.producto-item:first .form-control').val('');
        $('.producto-item:first .form-select').val('');
        $('.producto-item:first .producto-cantidad').val('1');
        
        $('#totalVenta').val('0.00');
        $('#montoPagado').val('0.00');
        $('#montoAdeudado').val('0.00');
        $('#montoPagadoContainer').hide();
        
        editandoVenta = false;
    }

    // Gestionar pagos
    function gestionarPagosFuncion(id) {
        $.get('controllers/gestionar_pagos.php', { id: id })
            .done(function(response) {
                if (response.success) {
                    mostrarModalPagos(response.venta, response.pagos);
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: response.message || 'Error al obtener información de pagos'
                    });
                }
            })
            .fail(function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Error de conexión'
                });
            });
    }

    // Mostrar modal de gestión de pagos
    function mostrarModalPagos(venta, pagos) {
        let html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6><i class="fas fa-info-circle me-2"></i>Información de la Venta</h6>
                    <p><strong>Cliente:</strong> ${venta.cliente_nombre}</p>
                    <p><strong>Fecha:</strong> ${venta.fecha_venta_formatted}</p>
                    <p><strong>Total:</strong> $${parseInt(venta.total).toLocaleString('es-AR')}</p>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-money-bill me-2"></i>Estado de Pagos</h6>
                    <p><strong>Pagado:</strong> <span class="text-success">$${parseInt(venta.monto_pagado).toLocaleString('es-AR')}</span></p>
                    <p><strong>Adeudado:</strong> <span class="text-danger">$${parseInt(venta.monto_adeudado).toLocaleString('es-AR')}</span></p>
                    <p><strong>Estado:</strong> <span class="badge bg-${venta.estado_pago === 'completo' ? 'success' : 'warning'}">${venta.estado_pago === 'completo' ? 'Completo' : 'Pendiente'}</span></p>
                </div>
            </div>
        `;

        if (venta.monto_adeudado > 0) {
            html += `
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-plus me-2"></i>Registrar Nuevo Pago</h6>
                    </div>
                    <div class="card-body">
                        <form id="formNuevoPago">
                            <input type="hidden" id="pagoVentaId" value="${venta.id}">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="pagoMonto" class="form-label">Monto <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="pagoMonto" 
                                               step="0.01" min="0.01" max="${venta.monto_adeudado}" required>
                                    </div>
                                    <small class="text-muted">Máximo: $${parseInt(venta.monto_adeudado).toLocaleString('es-AR')}</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="pagoMetodo" class="form-label">Método de Pago <span class="text-danger">*</span></label>
                                    <select class="form-select" id="pagoMetodo" required>
                                        <option value="">Seleccionar método</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta_debito">Tarjeta de Débito</option>
                                        <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                        <option value="transferencia">Transferencia</option>
                                        <option value="cuenta_corriente">Cuenta Corriente</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="pagoComprobante" class="form-label">Comprobante</label>
                                    <input type="text" class="form-control" id="pagoComprobante" 
                                           placeholder="Número de comprobante">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="pagoObservaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="pagoObservaciones" rows="2" 
                                              placeholder="Observaciones del pago..."></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>Registrar Pago
                            </button>
                        </form>
                    </div>
                </div>
            `;
        }

        if (pagos.length > 0) {
            html += `
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-history me-2"></i>Historial de Pagos</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Método</th>
                                        <th>Comprobante</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
            `;
            
            pagos.forEach(pago => {
                html += `
                    <tr>
                        <td>${pago.fecha_pago_formatted}</td>
                        <td>$${parseInt(pago.monto).toLocaleString('es-AR')}</td>
                        <td>${pago.metodo_pago}</td>
                        <td>${pago.comprobante || '-'}</td>
                        <td>${pago.usuario_nombre}</td>
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
        }

        $('#contenidoPagos').html(html);
        $('#modalPagos').modal('show');

        // Enlazar evento del formulario de nuevo pago
        $('#formNuevoPago').off('submit').on('submit', function(e) {
            e.preventDefault();
            registrarNuevoPago();
        });
    }

    // Registrar nuevo pago
    function registrarNuevoPago() {
        const formData = new FormData();
        formData.append('venta_id', $('#pagoVentaId').val());
        formData.append('monto', $('#pagoMonto').val());
        formData.append('metodo_pago', $('#pagoMetodo').val());
        formData.append('comprobante', $('#pagoComprobante').val());
        formData.append('observaciones', $('#pagoObservaciones').val());

        $.ajax({
            url: 'controllers/gestionar_pagos.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                Toast.fire({
                    icon: 'success',
                    title: response.message
                });
                $('#modalPagos').modal('hide');
                tabla.ajax.reload();
                cargarEstadisticas();
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
                title: 'Error de conexión'
            });
        });
    }

    // Editar venta
    function editarVentaFuncion(id) {
        $.get('controllers/obtener_venta.php', { id: id })
            .done(function(response) {
                if (response.success) {
                    cargarDatosVenta(response.venta, response.productos);
                    $('#modalVenta').modal('show');
                } else {
                    Toast.fire({
                        icon: 'error',
                        title: response.message || 'Error al obtener la venta'
                    });
                }
            })
            .fail(function() {
                Toast.fire({
                    icon: 'error',
                    title: 'Error de conexión'
                });
            });
    }

    // Cargar datos de venta en el formulario
    function cargarDatosVenta(venta, productos) {
        editandoVenta = true;
        
        // Cambiar título del modal
        $('#modalTitulo').text('Editar Venta');
        $('#btnGuardarTexto').text('Actualizar Venta');
        
        // Cargar datos básicos
        $('#ventaId').val(venta.id);
        $('#clienteNombre').val(venta.cliente_nombre);
        $('#metodoPago').val(venta.metodo_pago);
        $('#estadoPago').val(venta.estado_pago);
        $('#totalVenta').val(parseFloat(venta.total).toFixed(2));
        $('#montoPagado').val(parseFloat(venta.monto_pagado).toFixed(2));
        $('#montoAdeudado').val(parseFloat(venta.monto_adeudado).toFixed(2));
        $('#observaciones').val(venta.observaciones);
        
        // Limpiar productos existentes
        $('#productosContainer').empty();
        
        // Cargar productos
        productos.forEach(function(producto, index) {
            const productoHtml = `
                <div class="producto-item border rounded p-3 mb-2">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" class="form-control producto-nombre" 
                                   placeholder="Nombre del producto" value="${producto.producto_nombre}" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <select class="form-select producto-talle">
                                <option value="">Talle</option>
                                <option value="XS" ${producto.talle === 'XS' ? 'selected' : ''}>XS</option>
                                <option value="S" ${producto.talle === 'S' ? 'selected' : ''}>S</option>
                                <option value="M" ${producto.talle === 'M' ? 'selected' : ''}>M</option>
                                <option value="L" ${producto.talle === 'L' ? 'selected' : ''}>L</option>
                                <option value="XL" ${producto.talle === 'XL' ? 'selected' : ''}>XL</option>
                                <option value="XXL" ${producto.talle === 'XXL' ? 'selected' : ''}>XXL</option>
                                <option value="34" ${producto.talle === '34' ? 'selected' : ''}>34</option>
                                <option value="36" ${producto.talle === '36' ? 'selected' : ''}>36</option>
                                <option value="38" ${producto.talle === '38' ? 'selected' : ''}>38</option>
                                <option value="40" ${producto.talle === '40' ? 'selected' : ''}>40</option>
                                <option value="42" ${producto.talle === '42' ? 'selected' : ''}>42</option>
                                <option value="44" ${producto.talle === '44' ? 'selected' : ''}>44</option>
                                <option value="46" ${producto.talle === '46' ? 'selected' : ''}>46</option>
                                <option value="48" ${producto.talle === '48' ? 'selected' : ''}>48</option>
                                <option value="50" ${producto.talle === '50' ? 'selected' : ''}>50</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="number" class="form-control producto-cantidad" 
                                   placeholder="Cant." min="1" value="${producto.cantidad}" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control producto-precio" 
                                       placeholder="Precio" step="0.01" min="0" value="${producto.precio_unitario}" required>
                            </div>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button type="button" class="btn btn-danger btn-sm w-100 eliminar-producto">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            $('#productosContainer').append(productoHtml);
        });
        
        // Si no hay productos, agregar uno vacío
        if (productos.length === 0) {
            $('#agregarProducto').click();
        }
        
        // Re-enlazar eventos y actualizar montos
        enlazarEventosProductos();
        actualizarMontos();
    }

    // Event listeners del modal
    $('#modalVenta').on('hidden.bs.modal', function() {
        if (!editandoVenta) {
            limpiarFormulario();
        }
        // Remover indicador de hora
        $('#horaActual').remove();
    });

    $('#modalVenta').on('show.bs.modal', function() {
        if (!editandoVenta) {
            limpiarFormulario();
        }
        // Mostrar hora actual argentina
        mostrarHoraActual();
        
        // Actualizar hora cada segundo
        const intervalHora = setInterval(function() {
            if ($('#modalVenta').hasClass('show')) {
                mostrarHoraActual();
            } else {
                clearInterval(intervalHora);
            }
        }, 1000);
    });

    // Función para calcular fechas rápidas
    function calcularFechasRapidas(opcion) {
        const hoy = new Date();
        const ayer = new Date(hoy);
        ayer.setDate(hoy.getDate() - 1);
        
        let fechaDesde, fechaHasta;
        
        switch(opcion) {
            case 'hoy':
                fechaDesde = fechaHasta = formatearFecha(hoy);
                break;
            case 'ayer':
                fechaDesde = fechaHasta = formatearFecha(ayer);
                break;
            case 'ultimos_7':
                fechaHasta = formatearFecha(hoy);
                const hace7dias = new Date(hoy);
                hace7dias.setDate(hoy.getDate() - 7);
                fechaDesde = formatearFecha(hace7dias);
                break;
            case 'ultimos_30':
                fechaHasta = formatearFecha(hoy);
                const hace30dias = new Date(hoy);
                hace30dias.setDate(hoy.getDate() - 30);
                fechaDesde = formatearFecha(hace30dias);
                break;
            case 'este_mes':
                fechaDesde = formatearFecha(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
                fechaHasta = formatearFecha(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
                break;
            case 'mes_anterior':
                fechaDesde = formatearFecha(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1));
                fechaHasta = formatearFecha(new Date(hoy.getFullYear(), hoy.getMonth(), 0));
                break;
            default:
                return null;
        }
        
        return { desde: fechaDesde, hasta: fechaHasta };
    }
    
    // Función para formatear fecha a YYYY-MM-DD
    function formatearFecha(fecha) {
        const año = fecha.getFullYear();
        const mes = String(fecha.getMonth() + 1).padStart(2, '0');
        const dia = String(fecha.getDate()).padStart(2, '0');
        return `${año}-${mes}-${dia}`;
    }

    // Función para mostrar hora actual argentina en el formulario
    function mostrarHoraActual() {
        // Mostrar hora actual en el modal al abrirlo
        const ahora = new Date();
        const opciones = {
            timeZone: 'America/Argentina/Buenos_Aires',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        const fechaHoraStr = ahora.toLocaleString('es-AR', opciones);
        
        // Agregar indicador de hora si no existe
        if (!$('#horaActual').length) {
            $('#modalVenta .modal-body').prepend(`
                <div class="alert alert-info" id="horaActual">
                    <i class="fas fa-clock me-2"></i>
                    <strong>Fecha y hora de registro:</strong> ${fechaHoraStr} (Argentina)
                </div>
            `);
        } else {
            $('#horaActual').html(`
                <i class="fas fa-clock me-2"></i>
                <strong>Fecha y hora de registro:</strong> ${fechaHoraStr} (Argentina)
            `);
        }
    }

    // Event listener para filtro de fecha rápida
    $('#filtroFechaRapida').on('change', function() {
        const opcion = $(this).val();
        
        if (opcion === '') {
            // Personalizado - no cambiar las fechas
            return;
        }
        
        const fechas = calcularFechasRapidas(opcion);
        if (fechas) {
            $('#filtroFechaDesde').val(fechas.desde);
            $('#filtroFechaHasta').val(fechas.hasta);
            
            // Aplicar filtros automáticamente
            tabla.ajax.reload();
            
            Toast.fire({
                icon: 'info',
                title: `Filtro aplicado: ${$(this).find('option:selected').text()}`
            });
        }
    });

    // Event listeners para filtros dinámicos (sin botón)
    $('#filtroEstado, #filtroMetodo').on('change', function() {
        tabla.ajax.reload();
        Toast.fire({
            icon: 'info',
            title: 'Filtros actualizados'
        });
    });

    // Aplicar filtros al cambiar fechas manualmente (con debounce)
    let timeoutFechas;
    $('#filtroFechaDesde, #filtroFechaHasta').on('change', function() {
        // Cambiar el select a "Personalizado" cuando se modifiquen las fechas manualmente
        $('#filtroFechaRapida').val('');
        
        // Usar debounce para evitar múltiples peticiones
        clearTimeout(timeoutFechas);
        timeoutFechas = setTimeout(function() {
            tabla.ajax.reload();
            Toast.fire({
                icon: 'info',
                title: 'Filtros de fecha actualizados'
            });
        }, 500);
    });

    // Mantener botones para casos específicos
    $('#aplicarFiltros').on('click', function() {
        tabla.ajax.reload();
        Toast.fire({
            icon: 'info',
            title: 'Filtros aplicados manualmente'
        });
    });

    $('#limpiarFiltros').on('click', function() {
        $('#filtroFechaRapida').val('');
        $('#filtroFechaDesde').val('');
        $('#filtroFechaHasta').val('');
        $('#filtroEstado').val('');
        $('#filtroMetodo').val('');
        tabla.ajax.reload();
        Toast.fire({
            icon: 'success',
            title: 'Todos los filtros limpiados'
        });
    });

    // Inicialización
    inicializarTabla();
    cargarEstadisticas();
    enlazarEventosProductos();
    enlazarEventosTabla(); // Llamar aquí también para eventos globales
    actualizarMontos();

    // Recargar estadísticas cada 5 minutos
    setInterval(cargarEstadisticas, 300000);
});
