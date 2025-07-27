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

// Incluir header
include '../../includes/header.php';
?>

    <main class="dashboard-main">
        <div class="container-fluid px-4">
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
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="fas fa-users me-2"></i>
                                Gestión de Usuarios
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-secondary">
                                <i class="fas fa-users me-2"></i>
                                <strong>Módulo en desarrollo:</strong> Este módulo estará disponible próximamente.
                            </div>
                            <a href="../" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Volver al Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php include '../../includes/footer.php'; ?>
