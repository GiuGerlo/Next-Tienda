<?php
// Iniciar sesión
session_start();

// Incluir middleware de autenticación
require_once '../../controllers/auth.php';

// Verificar autenticación
requireAuth('../../index.php');

// Obtener datos del usuario actual
$user = getCurrentUser();

// Configuración de la página
$page_title = 'Ventas';
$current_page = 'ventas';

// CSS adicional para DataTables y SweetAlert
$additional_css = '
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- CSS personalizado para ventas -->
    <link rel="stylesheet" href="../../assets/css/ventas.css">
';

// Incluir header
include '../../includes/header.php';
?>

<main class="dashboard-main">
    <div class="container-fluid px-2 px-md-4">
        <!-- Header del módulo -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="dashboard-title">
                        <i class="fas fa-shopping-cart me-3"></i>Ventas
                    </h1>
                    <p class="dashboard-subtitle">
                        Gestión de ventas y transacciones
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary btn-nueva-venta" data-bs-toggle="modal" data-bs-target="#modalVenta">
                        <i class="fas fa-plus me-2"></i>
                        <span class="d-none d-sm-inline">Nueva Venta</span>
                        <span class="d-sm-none">Nueva</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tarjetas de estadísticas -->
        <div class="row mb-4">
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-success">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="totalVentas">$0</h3>
                        <p>Total Ventas</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-primary">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="ventasHoy">$0</h3>
                        <p>Ventas Hoy</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="pagosPendientes">$0</h3>
                        <p>Pagos Pendientes</p>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3 mb-3">
                <div class="stats-card">
                    <div class="stats-icon bg-info">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stats-content">
                        <h3 id="totalTransacciones">0</h3>
                        <p>Total Transacciones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de ventas -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>
                                    Lista de Ventas
                                </h5>
                            </div>
                            <div class="col-md-6 text-end">
                                <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#filtrosVentas">
                                    <i class="fas fa-filter me-1"></i>Filtros</small>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Filtros avanzados -->
                        <div class="collapse mt-3" id="filtrosVentas">
                            <div class="card card-body bg-light">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label for="filtroFechaRapida" class="form-label">Período</label>
                                        <select class="form-select form-select-sm" id="filtroFechaRapida">
                                            <option value="">Personalizado</option>
                                            <option value="hoy">Hoy</option>
                                            <option value="ayer">Ayer</option>
                                            <option value="ultimos_7">Últimos 7 días</option>
                                            <option value="ultimos_30">Últimos 30 días</option>
                                            <option value="este_mes">Este mes</option>
                                            <option value="mes_anterior">Mes anterior</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="filtroFechaDesde" class="form-label">Desde</label>
                                        <input type="date" class="form-control form-control-sm" id="filtroFechaDesde">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="filtroFechaHasta" class="form-label">Hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="filtroFechaHasta">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="filtroEstado" class="form-label">Estado</label>
                                        <select class="form-select form-select-sm" id="filtroEstado">
                                            <option value="">Todos</option>
                                            <option value="completo">Completo</option>
                                            <option value="parcial">Parcial</option>
                                            <option value="pendiente">Pendiente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="filtroMetodo" class="form-label">Método</label>
                                        <select class="form-select form-select-sm" id="filtroMetodo">
                                            <option value="">Todos</option>
                                            <option value="efectivo">Efectivo</option>
                                            <option value="tarjeta_debito">T. Débito</option>
                                            <option value="tarjeta_credito">T. Crédito</option>
                                            <option value="transferencia">Transferencia</option>
                                            <option value="cuenta_corriente">Cta. Corriente</option>
                                            <option value="otro">Otro</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1 mb-2">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="d-grid gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="limpiarFiltros">
                                                <i class="fas fa-times me-1"></i>Limpiar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaVentas" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Total</th>
                                        <th class="d-none d-md-table-cell">Estado Pago</th>
                                        <th class="d-none d-lg-table-cell">Método Pago</th>
                                        <th class="d-none d-xl-table-cell">Adeudado</th>
                                        <th class="text-center">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Los datos se cargan vía AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal para crear/editar venta -->
<div class="modal fade" id="modalVenta" tabindex="-1" aria-labelledby="modalVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form id="formVenta">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVentaLabel">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <span id="modalTitulo">Nueva Venta</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="ventaId" name="id">

                    <!-- Información del cliente -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="clienteNombre" class="form-label">
                                <i class="fas fa-user me-1"></i>
                                Cliente <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="clienteNombre" name="cliente_nombre" 
                                   placeholder="Nombre completo del cliente" required>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-box me-1"></i>
                            Productos <span class="text-danger">*</span>
                        </label>
                        <div id="productosContainer">
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
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="agregarProducto">
                            <i class="fas fa-plus me-1"></i>Agregar Producto
                        </button>
                    </div>

                    <!-- Información de pago -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="metodoPago" class="form-label">
                                <i class="fas fa-credit-card me-1"></i>
                                Método de Pago <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="metodoPago" name="metodo_pago" required>
                                <option value="">Seleccionar método</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="cuenta_corriente">Cuenta Corriente</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="estadoPago" class="form-label">
                                <i class="fas fa-money-bill me-1"></i>
                                Estado de Pago <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="estadoPago" name="estado_pago" required>
                                <option value="completo">Pago Completo</option>
                                <option value="parcial">Pago Parcial</option>
                                <option value="pendiente">Pago Pendiente</option>
                            </select>
                        </div>
                    </div>

                    <!-- Montos -->
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="totalVenta" class="form-label">
                                <i class="fas fa-calculator me-1"></i>
                                Total
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="totalVenta" name="total" 
                                       step="0.01" min="0" readonly>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3" id="montoPagadoContainer">
                            <label for="montoPagado" class="form-label">
                                <i class="fas fa-dollar-sign me-1"></i>
                                Monto Pagado <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="montoPagado" name="monto_pagado" 
                                       step="0.01" min="0">
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="montoAdeudado" class="form-label">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Adeudado
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="montoAdeudado" name="monto_adeudado" 
                                       step="0.01" min="0" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Observaciones
                        </label>
                        <textarea class="form-control" id="observaciones" name="observaciones" 
                                  rows="2" placeholder="Observaciones adicionales..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i>
                        <span id="btnGuardarTexto">Crear Venta</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para ver detalles de venta -->
<div class="modal fade" id="modalDetalleVenta" tabindex="-1" aria-labelledby="modalDetalleVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalleVentaLabel">
                    <i class="fas fa-eye me-2"></i>
                    Detalle de Venta
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="contenidoDetalleVenta">
                <!-- Se carga dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para gestionar pagos -->
<div class="modal fade" id="modalPagos" tabindex="-1" aria-labelledby="modalPagosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPagosLabel">
                    <i class="fas fa-money-bill me-2"></i>
                    Gestionar Pagos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="contenidoPagos">
                <!-- Se carga dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts adicionales -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
<!-- SweetAlert JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JS personalizado para ventas -->
<script src="../../assets/js/ventas.js"></script>

<?php include '../../includes/footer.php'; ?>
