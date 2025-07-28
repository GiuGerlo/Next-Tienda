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
$page_title = 'Usuarios';
$current_page = 'usuarios';

// CSS adicional para DataTables y SweetAlert
$additional_css = '
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <!-- SweetAlert CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- CSS personalizado para usuarios -->
    <link rel="stylesheet" href="../../assets/css/usuarios.css">
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
                        <i class="fas fa-users me-3"></i>Usuarios
                    </h1>
                    <p class="dashboard-subtitle">
                        Gestión de usuarios del sistema
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-primary btn-nuevo-usuario" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                        <i class="fas fa-plus me-2"></i>
                        <span class="d-none d-sm-inline">Nuevo Usuario</span>
                        <span class="d-sm-none">Nuevo</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="row">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Lista de Usuarios
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tablaUsuarios" class="table table-striped table-hover w-100">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th class="d-none d-md-table-cell">Fecha Creación</th>
                                        <th class="d-none d-lg-table-cell">Último Acceso</th>
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

<!-- Modal para crear/editar usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-labelledby="modalUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formUsuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioLabel">
                        <i class="fas fa-user-plus me-2"></i>
                        <span id="modalTitulo">Nuevo Usuario</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="usuarioId" name="id">

                    <div class="mb-3">
                        <label for="nombreCompleto" class="form-label">
                            <i class="fas fa-user me-1"></i>
                            Nombre Completo <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nombreCompleto" name="nombre_completo" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope me-1"></i>
                            Email <span class="text-danger">*</span>
                        </label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i>
                            Contraseña <span id="passwordRequired" class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">
                            <span id="passwordHelp">Mínimo 6 caracteres</span>
                        </small>
                        <div class="invalid-feedback"></div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                            <label class="form-check-label" for="activo">
                                <i class="fas fa-toggle-on me-1"></i>
                                Usuario Activo
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i>
                        <span id="btnGuardarTexto">Crear Usuario</span>
                    </button>
                </div>
            </form>
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
<!-- JS personalizado para usuarios -->
<script src="../../assets/js/usuarios.js"></script>

<?php include '../../includes/footer.php'; ?>