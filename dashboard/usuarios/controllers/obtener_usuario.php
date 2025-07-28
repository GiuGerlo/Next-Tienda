<?php
/**
 * Controlador para obtener datos de usuarios
 * Sistema Next - Gestión de Usuarios
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión y verificar autenticación
session_start();
require_once '../../../controllers/auth.php';

// Verificar autenticación sin redirigir (para AJAX)
if (!isAuthenticated()) {
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Por favor inicia sesión.',
        'redirect' => '../../../index.php'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once '../../../config/connect.php';

try {
    // Verificar método de petición
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'listar':
            // Obtener todos los usuarios para DataTable
            $sql = "SELECT 
                        id,
                        nombre_completo,
                        email,
                        activo,
                        DATE_FORMAT(fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
                        DATE_FORMAT(ultimo_acceso, '%d/%m/%Y %H:%i') as ultimo_acceso_formato,
                        fecha_creacion,
                        ultimo_acceso
                    FROM usuarios 
                    ORDER BY fecha_creacion DESC";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $usuarios = $stmt->fetchAll();

            // Preparar datos para DataTable
            $data = [];
            foreach ($usuarios as $usuario) {
                $data[] = [
                    'id' => $usuario['id'],
                    'nombre_completo' => htmlspecialchars($usuario['nombre_completo']),
                    'email' => htmlspecialchars($usuario['email']),
                    'estado' => $usuario['activo'] ? 'Activo' : 'Inactivo',
                    'activo' => $usuario['activo'],
                    'fecha_creacion' => $usuario['fecha_creacion_formato'],
                    'ultimo_acceso' => $usuario['ultimo_acceso_formato'] ?? 'Nunca',
                    'acciones' => '' // Se llenarán desde JavaScript
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $data
            ]);
            break;

        case 'obtener':
            // Obtener usuario específico por ID
            $id = $_POST['id'] ?? 0;
            
            if (!$id) {
                throw new Exception('ID de usuario requerido');
            }

            $sql = "SELECT id, nombre_completo, email, activo FROM usuarios WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $usuario = $stmt->fetch();

            if (!$usuario) {
                throw new Exception('Usuario no encontrado');
            }

            echo json_encode([
                'success' => true,
                'data' => [
                    'id' => $usuario['id'],
                    'nombre_completo' => $usuario['nombre_completo'],
                    'email' => $usuario['email'],
                    'activo' => $usuario['activo']
                ]
            ]);
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>