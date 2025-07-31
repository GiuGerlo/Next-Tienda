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
$page_title = 'Préstamos';
$current_page = 'prestamos';

// CSS adicional para DataTables y SweetAlert
$additional_css = '
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- CSS personalizado para préstamos -->
    <link rel="stylesheet" href="../../assets/css/prestamos.css">
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
                        <i class="fas fa-handshake me-3"></i>Préstamos
                    </h1>
                    <p class="dashboard-subtitle">
                        Gestión de préstamos de productos
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary btn-nuevo-prestamo" data-bs-toggle="modal" data-bs-target="#modalPrestamo">
                        <i class="fas fa-plus me-2"></i>
                        <span class="d-none d-sm-inline">Nuevo Préstamo</span>
                        <span class="d-sm-none">Nuevo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filtros de búsqueda
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="filtro_fecha_desde" class="form-label">Desde</label>
                                <input type="date" class="form-control" id="filtro_fecha_desde">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_fecha_hasta" class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="filtro_fecha_hasta">
                            </div>
                            <div class="col-md-3">
                                <label for="filtro_estado" class="form-label">Estado</label>
                                <select class="form-select" id="filtro_estado">
                                    <option value="">Todos los estados</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="parcial">Parcial</option>
                                    <option value="finalizado">Finalizado</option>
                                    <option value="vencido">Vencido</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-secondary me-2" id="limpiar_filtros">
                                    <i class="fas fa-times me-1"></i>Limpiar
                                </button>
                                <button type="button" class="btn btn-primary" id="aplicar_filtros">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de préstamos -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i>Lista de Préstamos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tabla-prestamos" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Productos</th>
                                        <th>Progreso</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Datos cargados via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Modal Nuevo/Editar Préstamo -->
<div class="modal fade" id="modalPrestamo" tabindex="-1" aria-labelledby="modalPrestamoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalPrestamoLabel">
                    <i class="fas fa-handshake me-2"></i>Nuevo Préstamo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formPrestamo">
                    <input type="hidden" id="prestamo_id" name="prestamo_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cliente_nombre" class="form-label">Cliente *</label>
                                <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="fecha_prestamo" class="form-label">Fecha de Préstamo *</label>
                                <input type="date" class="form-control" id="fecha_prestamo" name="fecha_prestamo" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label for="fecha_limite" class="form-label">Fecha Límite (Opcional)</label>
                                <input type="date" class="form-control" id="fecha_limite" name="fecha_limite">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Productos del Préstamo</h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="agregarProducto">
                                    <i class="fas fa-plus me-1"></i>Agregar Producto
                                </button>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-bordered" id="tablaProductos">
                                    <thead>
                                        <tr>
                                            <th width="30%">Producto</th>
                                            <th width="15%">Talle</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="20%">Precio Ref.</th>
                                            <th width="15%">Subtotal</th>
                                            <th width="5%">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="productosContainer">
                                        <!-- Productos se agregan dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-end">
                                <strong>Total Referencial: $<span id="totalPrestamo">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarPrestamo">
                    <i class="fas fa-save me-1"></i>Guardar Préstamo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ver Detalles del Préstamo -->
<div class="modal fade" id="modalDetallesPrestamo" tabindex="-1" aria-labelledby="modalDetallesPrestamoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetallesPrestamoLabel">
                    <i class="fas fa-eye me-2"></i>Detalles del Préstamo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detallesPrestamoContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Gestionar Productos -->
<div class="modal fade" id="modalGestionarProductos" tabindex="-1" aria-labelledby="modalGestionarProductosLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalGestionarProductosLabel">
                    <i class="fas fa-tasks me-2"></i>Gestionar Productos del Préstamo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="gestionarProductosContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php 
// JavaScript adicional para préstamos
$additional_js = '
    <!-- jQuery (necesario para DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <!-- SweetAlert JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <!-- JS personalizado para préstamos -->
    <script src="../../assets/js/prestamos.js"></script>
';

include '../../includes/footer.php'; ?>
