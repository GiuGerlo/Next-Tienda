<?php
/**
 * Controlador para obtener préstamos (DataTable) con filtros avanzados
 * Sistema Next - Gestión de Préstamos
 */

// Configurar headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Iniciar sesión y verificar autenticación
session_start();

// Verificar si hay sesión activa (método más simple para AJAX)
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Sesión no válida. Por favor, inicia sesión nuevamente.'
    ]);
    exit();
}

// Incluir conexión a la base de datos
require_once __DIR__ . '/../../../config/connect.php';

try {
    // Configuración de DataTables
    $draw = intval($_GET['draw'] ?? 1);
    $start = intval($_GET['start'] ?? 0);
    $length = intval($_GET['length'] ?? 10);
    $searchValue = $_GET['search']['value'] ?? '';
    
    // Filtros personalizados
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $cliente = $_GET['cliente'] ?? '';
    $soloVencidos = $_GET['vencidos'] ?? '';
    
    // Log para debug
    error_log("Filtros aplicados - Desde: $fechaDesde, Hasta: $fechaHasta, Estado: $estado, Cliente: $cliente, Vencidos: $soloVencidos");
    
    // Ordenamiento
    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
    
    $columns = ['id', 'cliente_nombre', 'fecha_prestamo', 'estado', 'total_productos', 'productos_pendientes'];
    $orderBy = $columns[$orderColumn] ?? 'id';
    
    // Construcción de la consulta base
    $baseQuery = "
        FROM prestamos p
        LEFT JOIN usuarios u ON p.usuario_id = u.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Aplicar filtros
    if (!empty($searchValue)) {
        $baseQuery .= " AND (p.cliente_nombre LIKE :search OR p.observaciones LIKE :search)";
        $params[':search'] = "%$searchValue%";
    }
    
    if (!empty($fechaDesde)) {
        $baseQuery .= " AND p.fecha_prestamo >= :fecha_desde";
        $params[':fecha_desde'] = $fechaDesde;
    }
    
    if (!empty($fechaHasta)) {
        $baseQuery .= " AND p.fecha_prestamo <= :fecha_hasta";
        $params[':fecha_hasta'] = $fechaHasta;
    }
    
    if (!empty($estado)) {
        $baseQuery .= " AND p.estado = :estado";
        $params[':estado'] = $estado;
    }
    
    if (!empty($cliente)) {
        $baseQuery .= " AND p.cliente_nombre LIKE :cliente";
        $params[':cliente'] = "%$cliente%";
    }
    
    if (!empty($soloVencidos) && $soloVencidos === 'true') {
        $baseQuery .= " AND p.fecha_limite IS NOT NULL AND p.fecha_limite < CURDATE() AND p.estado IN ('pendiente', 'parcial')";
    }
    
    // Contar total de registros
    $countQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($params);
    $totalRecords = $stmt->fetch()['total'];
    
    // Consulta principal con datos
    $dataQuery = "
        SELECT 
            p.id,
            p.cliente_nombre,
            DATE_FORMAT(p.fecha_prestamo, '%d/%m/%Y %H:%i') as fecha_prestamo_formato,
            DATE_FORMAT(p.fecha_prestamo, '%Y-%m-%d') as fecha_prestamo_input,
            p.fecha_prestamo,
            DATE_FORMAT(p.fecha_limite, '%d/%m/%Y') as fecha_limite_formato,
            DATE_FORMAT(p.fecha_limite, '%Y-%m-%d') as fecha_limite_input,
            p.fecha_limite,
            p.estado,
            p.total_productos,
            p.productos_devueltos,
            p.productos_comprados,
            p.productos_pendientes,
            p.observaciones,
            u.nombre_completo as usuario_nombre,
            DATE_FORMAT(p.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formato,
            -- Calcular si está vencido
            CASE 
                WHEN p.fecha_limite IS NOT NULL AND p.fecha_limite < CURDATE() AND p.estado IN ('pendiente', 'parcial') 
                THEN 'vencido' 
                ELSE p.estado 
            END as estado_calculado
        " . $baseQuery . "
        ORDER BY p.$orderBy $orderDir
        LIMIT :start, :length
    ";
    
    $stmt = $pdo->prepare($dataQuery);
    
    // Bind de parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':start', $start, PDO::PARAM_INT);
    $stmt->bindValue(':length', $length, PDO::PARAM_INT);
    
    $stmt->execute();
    $prestamos = $stmt->fetchAll();
    
    // Formatear datos para DataTables
    $data = [];
    foreach ($prestamos as $prestamo) {
        // Badge de estado
        $estadoBadge = '';
        switch ($prestamo['estado_calculado']) {
            case 'pendiente':
                $estadoBadge = '<span class="badge bg-warning">Pendiente</span>';
                break;
            case 'parcial':
                $estadoBadge = '<span class="badge bg-info">Parcial</span>';
                break;
            case 'finalizado':
                $estadoBadge = '<span class="badge bg-success">Finalizado</span>';
                break;
            case 'vencido':
                $estadoBadge = '<span class="badge bg-danger">Vencido</span>';
                break;
        }
        
        // Progreso de productos
        $progreso = '';
        if ($prestamo['total_productos'] > 0) {
            $porcentajePendiente = ($prestamo['productos_pendientes'] / $prestamo['total_productos']) * 100;
            $porcentajeDevuelto = ($prestamo['productos_devueltos'] / $prestamo['total_productos']) * 100;
            $porcentajeComprado = ($prestamo['productos_comprados'] / $prestamo['total_productos']) * 100;
            
            $progreso = '
                <div class="progress mb-1" style="height: 10px;">
                    <div class="progress-bar bg-warning" style="width: ' . $porcentajePendiente . '%"></div>
                    <div class="progress-bar bg-success" style="width: ' . $porcentajeDevuelto . '%"></div>
                    <div class="progress-bar bg-primary" style="width: ' . $porcentajeComprado . '%"></div>
                </div>
                <small class="text-muted">
                    ' . $prestamo['productos_pendientes'] . ' pendientes | 
                    ' . $prestamo['productos_devueltos'] . ' devueltos | 
                    ' . $prestamo['productos_comprados'] . ' comprados
                </small>
            ';
        }
        
        // Botones de acción
        $acciones = '
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-accion btn-ver btn-sm" 
                        onclick="verPrestamo(' . $prestamo['id'] . ')" 
                        title="Ver detalles">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-accion btn-gestionar btn-sm" 
                        onclick="gestionarProductos(' . $prestamo['id'] . ')" 
                        title="Gestionar productos">
                    <i class="fas fa-tasks"></i>
                </button>
                <button type="button" class="btn btn-accion btn-eliminar btn-sm" 
                        onclick="eliminarPrestamo(' . $prestamo['id'] . ')" 
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
        
        $data[] = [
            $prestamo['id'],
            $prestamo['cliente_nombre'],
            $prestamo['fecha_prestamo_formato'],
            $estadoBadge,
            $prestamo['total_productos'],
            $progreso,
            $acciones
        ];
    }
    
    // Respuesta para DataTables
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);

} catch (Exception $e) {
    error_log("Error en obtener_prestamos.php: " . $e->getMessage());
    
    echo json_encode([
        'draw' => intval($_GET['draw'] ?? 1),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error interno del servidor. Por favor, inténtalo de nuevo.'
    ]);
}
?>
