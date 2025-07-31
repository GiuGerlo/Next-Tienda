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
    if (!empty($fecha_limite) && $fecha_limite < date('Y-m-d', strtotime($fecha_prestamo))) {
        throw new Exception('La fecha límite no puede ser anterior a la fecha del préstamo');
    }

    // Convertir fecha_prestamo a DATETIME con zona horaria argentina
    // Configurar timezone de Argentina
    $timezone_argentina = new DateTimeZone('America/Argentina/Buenos_Aires');
    
    // Si la fecha es de hoy y no se especificó hora, usar hora actual argentina
    if (date('Y-m-d', strtotime($fecha_prestamo)) === date('Y-m-d')) {
        $fecha_actual = new DateTime('now', $timezone_argentina);
        $fecha_prestamo_dt = $fecha_actual->format('Y-m-d H:i:s');
    } else {
        // Para fechas diferentes a hoy, usar las 00:00:00 en horario argentino
        $fecha_dt = new DateTime($fecha_prestamo . ' 00:00:00', $timezone_argentina);
        $fecha_prestamo_dt = $fecha_dt->format('Y-m-d H:i:s');
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

    // CREAR NUEVO PRÉSTAMO
    $sql = "INSERT INTO prestamos (cliente_nombre, fecha_prestamo, fecha_limite, observaciones, usuario_id) 
            VALUES (:cliente_nombre, :fecha_prestamo, :fecha_limite, :observaciones, :usuario_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':cliente_nombre' => $cliente_nombre,
        ':fecha_prestamo' => $fecha_prestamo_dt,
        ':fecha_limite' => $fecha_limite ?: null,
        ':observaciones' => $observaciones,
        ':usuario_id' => $usuario_id
    ]);
    
    $prestamo_id = $pdo->lastInsertId();

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
    error_log("Préstamo creado: ID $prestamo_id por usuario " . $_SESSION['user_id']);

    echo json_encode([
        'success' => true,
        'message' => "Préstamo creado exitosamente",
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
