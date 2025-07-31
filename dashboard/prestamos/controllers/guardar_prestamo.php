<?php
/**
 * Controlador para guardar préstamos (crear/editar)
 * Sistema Next - Gestión de Préstamos
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

    // Obtener datos del formulario
    $prestamo_id = $_POST['prestamo_id'] ?? null;
    $cliente_nombre = trim($_POST['cliente_nombre'] ?? '');
    $fecha_prestamo = $_POST['fecha_prestamo'] ?? '';
    $fecha_limite = $_POST['fecha_limite'] ?? null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $productos = $_POST['productos'] ?? [];

    // Validaciones básicas
    if (empty($cliente_nombre)) {
        throw new Exception('El nombre del cliente es obligatorio');
    }

    if (empty($fecha_prestamo)) {
        throw new Exception('La fecha del préstamo es obligatoria');
    }

    if (empty($productos) || !is_array($productos)) {
        throw new Exception('Debe agregar al menos un producto al préstamo');
    }

    // Validar fecha límite si se proporciona
    if (!empty($fecha_limite) && $fecha_limite < $fecha_prestamo) {
        throw new Exception('La fecha límite no puede ser anterior a la fecha del préstamo');
    }

    // Validar productos
    foreach ($productos as $index => $producto) {
        if (empty($producto['nombre'])) {
            throw new Exception("El nombre del producto en la fila " . ($index + 1) . " es obligatorio");
        }
        
        if (empty($producto['cantidad']) || $producto['cantidad'] <= 0) {
            throw new Exception("La cantidad del producto en la fila " . ($index + 1) . " debe ser mayor a 0");
        }
        
        if (empty($producto['precio']) || $producto['precio'] <= 0) {
            throw new Exception("El precio del producto en la fila " . ($index + 1) . " debe ser mayor a 0");
        }
    }

    // Comenzar transacción
    $pdo->beginTransaction();

    $usuario_id = $_SESSION['user_id'];

    if (empty($prestamo_id)) {
        // CREAR NUEVO PRÉSTAMO
        $sql = "INSERT INTO prestamos (cliente_nombre, fecha_prestamo, fecha_limite, observaciones, usuario_id) 
                VALUES (:cliente_nombre, :fecha_prestamo, :fecha_limite, :observaciones, :usuario_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_nombre' => $cliente_nombre,
            ':fecha_prestamo' => $fecha_prestamo,
            ':fecha_limite' => $fecha_limite ?: null,
            ':observaciones' => $observaciones,
            ':usuario_id' => $usuario_id
        ]);
        
        $prestamo_id = $pdo->lastInsertId();
        $accion = 'creado';
        
    } else {
        // EDITAR PRÉSTAMO EXISTENTE
        
        // Verificar que el préstamo existe y no está finalizado
        $sql = "SELECT estado FROM prestamos WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $prestamo_id]);
        $prestamo_actual = $stmt->fetch();
        
        if (!$prestamo_actual) {
            throw new Exception('El préstamo no existe');
        }
        
        if ($prestamo_actual['estado'] === 'finalizado') {
            throw new Exception('No se puede editar un préstamo finalizado');
        }
        
        // Actualizar datos del préstamo
        $sql = "UPDATE prestamos 
                SET cliente_nombre = :cliente_nombre, 
                    fecha_prestamo = :fecha_prestamo, 
                    fecha_limite = :fecha_limite, 
                    observaciones = :observaciones
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cliente_nombre' => $cliente_nombre,
            ':fecha_prestamo' => $fecha_prestamo,
            ':fecha_limite' => $fecha_limite ?: null,
            ':observaciones' => $observaciones,
            ':id' => $prestamo_id
        ]);
        
        // Eliminar productos anteriores que no estén devueltos o comprados
        $sql = "DELETE FROM detalle_prestamos 
                WHERE prestamo_id = :prestamo_id AND estado_producto = 'pendiente'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':prestamo_id' => $prestamo_id]);
        
        $accion = 'actualizado';
    }

    // Insertar productos del préstamo
    $sql = "INSERT INTO detalle_prestamos (prestamo_id, producto_nombre, talle, cantidad, precio_unitario) 
            VALUES (:prestamo_id, :producto_nombre, :talle, :cantidad, :precio_unitario)";
    $stmt = $pdo->prepare($sql);

    foreach ($productos as $producto) {
        $stmt->execute([
            ':prestamo_id' => $prestamo_id,
            ':producto_nombre' => trim($producto['nombre']),
            ':talle' => !empty($producto['talle']) ? trim($producto['talle']) : null,
            ':cantidad' => intval($producto['cantidad']),
            ':precio_unitario' => floatval($producto['precio'])
        ]);
    }

    // Los triggers se encargarán de actualizar los contadores automáticamente

    // Confirmar transacción
    $pdo->commit();

    // Log de actividad
    error_log("Préstamo $accion: ID $prestamo_id por usuario " . $_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'message' => "Préstamo $accion exitosamente",
        'prestamo_id' => $prestamo_id
    ]);

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error en guardar_prestamo.php: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
