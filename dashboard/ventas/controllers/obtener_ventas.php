<?php
/**
 * Controlador para obtener ventas (DataTable) con filtros avanzados
 * Sistema Next - Gestión de Ventas
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
    $estadoPago = $_GET['estado_pago'] ?? '';
    $metodoPago = $_GET['metodo_pago'] ?? '';
    
    // Log para debug
    error_log("Filtros aplicados - Desde: $fechaDesde, Hasta: $fechaHasta, Estado: $estadoPago, Método: $metodoPago");
    
    // Ordenamiento
    $orderColumn = intval($_GET['order'][0]['column'] ?? 0);
    $orderDir = $_GET['order'][0]['dir'] ?? 'desc';
    
    $columns = ['id', 'cliente_nombre', 'fecha_venta', 'total', 'estado_pago', 'metodo_pago', 'monto_adeudado'];
    $orderBy = $columns[$orderColumn] ?? 'id';
    
    // Query base con subconsultas para datos calculados
    $selectQuery = "
        SELECT 
            v.id,
            v.cliente_nombre,
            v.fecha_venta,
            v.total,
            v.estado_pago,
            v.observaciones,
            COALESCE(
                (SELECT p.metodo_pago 
                 FROM pagos_ventas p 
                 WHERE p.venta_id = v.id 
                 ORDER BY p.monto DESC 
                 LIMIT 1),
                'pendiente'
            ) as metodo_pago,
            COALESCE(v.total - (
                SELECT COALESCE(SUM(p.monto), 0) 
                FROM pagos_ventas p 
                WHERE p.venta_id = v.id
            ), v.total) as monto_adeudado
        FROM ventas v 
    ";
    
    // Construir WHERE clause
    $whereClause = "WHERE 1=1";
    $params = [];
    
    // Filtro de búsqueda general
    if (!empty($searchValue)) {
        $whereClause .= " AND (v.cliente_nombre LIKE ? OR v.id LIKE ? OR v.observaciones LIKE ?)";
        $searchParam = "%$searchValue%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // Filtro de fecha desde
    if (!empty($fechaDesde)) {
        $whereClause .= " AND DATE(v.fecha_venta) >= ?";
        $params[] = $fechaDesde;
    }
    
    // Filtro de fecha hasta
    if (!empty($fechaHasta)) {
        $whereClause .= " AND DATE(v.fecha_venta) <= ?";
        $params[] = $fechaHasta;
    }
    
    // Filtro de estado de pago
    if (!empty($estadoPago)) {
        $whereClause .= " AND v.estado_pago = ?";
        $params[] = $estadoPago;
    }
    
    // Filtro de método de pago (requiere subconsulta)
    if (!empty($metodoPago)) {
        $whereClause .= " AND EXISTS (
            SELECT 1 FROM pagos_ventas pv 
            WHERE pv.venta_id = v.id AND pv.metodo_pago = ?
        )";
        $params[] = $metodoPago;
    }
    
    // Contar total de registros (sin filtros)
    $totalQuery = "SELECT COUNT(*) FROM ventas";
    $totalStmt = $pdo->prepare($totalQuery);
    $totalStmt->execute();
    $totalRecords = $totalStmt->fetchColumn();
    
    // Contar registros filtrados
    $filteredQuery = "SELECT COUNT(*) FROM ventas v $whereClause";
    $filteredStmt = $pdo->prepare($filteredQuery);
    $filteredStmt->execute($params);
    $filteredRecords = $filteredStmt->fetchColumn();
    
    // Query principal con paginación y ordenamiento
    $mainQuery = "$selectQuery $whereClause ORDER BY v.$orderBy $orderDir LIMIT $start, $length";
    
    error_log("Query ejecutada: $mainQuery");
    error_log("Parámetros: " . json_encode($params));
    
    $stmt = $pdo->prepare($mainQuery);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos para DataTables
    $data = [];
    foreach ($ventas as $venta) {
        // Formatear fecha
        $fecha = new DateTime($venta['fecha_venta']);
        $fechaFormateada = $fecha->format('d/m/Y H:i');
        
        // Formatear total
        $totalFormateado = '$' . number_format($venta['total'], 0, ',', '.');
        
        // Formatear estado con badges
        $estadoBadges = [
            'completo' => '<span class="badge bg-success">Completo</span>',
            'parcial' => '<span class="badge bg-warning text-dark">Parcial</span>',
            'pendiente' => '<span class="badge bg-danger">Pendiente</span>'
        ];
        $estadoFormateado = $estadoBadges[$venta['estado_pago']] ?? '<span class="badge bg-secondary">' . ucfirst($venta['estado_pago']) . '</span>';
        
        // Formatear método de pago
        $metodos = [
            'efectivo' => 'Efectivo',
            'tarjeta_debito' => 'T. Débito',
            'tarjeta_credito' => 'T. Crédito',
            'transferencia' => 'Transferencia',
            'cuenta_corriente' => 'Cta. Corriente',
            'otro' => 'Otro',
            'pendiente' => '<span class="text-muted">Pendiente</span>'
        ];
        $metodoPagoFormateado = $metodos[$venta['metodo_pago']] ?? $venta['metodo_pago'];
        
        // Formatear monto adeudado
        $adeudadoFormateado = '$' . number_format($venta['monto_adeudado'], 0, ',', '.');
        if ($venta['monto_adeudado'] > 0) {
            $adeudadoFormateado = '<span class="text-danger fw-bold">' . $adeudadoFormateado . '</span>';
        } else {
            $adeudadoFormateado = '<span class="text-success">$0</span>';
        }
        
        // Botones de acción
        $acciones = '
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-accion btn-ver btn-sm" 
                        onclick="verDetalle(' . $venta['id'] . ')" 
                        title="Ver detalle">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-accion btn-editar btn-sm" 
                        onclick="editarVenta(' . $venta['id'] . ')" 
                        title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-accion btn-pagos btn-sm" 
                        onclick="gestionarPagos(' . $venta['id'] . ')" 
                        title="Gestionar Pagos">
                    <i class="fas fa-money-bill-wave"></i>
                </button>
                <button type="button" class="btn btn-accion btn-eliminar btn-sm" 
                        onclick="eliminarVenta(' . $venta['id'] . ')" 
                        title="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ';
        
        $data[] = [
            $venta['id'],
            $venta['cliente_nombre'],
            $fechaFormateada,
            $totalFormateado,
            $estadoFormateado,
            $metodoPagoFormateado,
            $adeudadoFormateado,
            $acciones
        ];
    }
    
    // Respuesta exitosa
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => intval($totalRecords),
        'recordsFiltered' => intval($filteredRecords),
        'data' => $data,
        'debug' => [
            'filtros' => [
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'estado_pago' => $estadoPago,
                'metodo_pago' => $metodoPago
            ],
            'total_sin_filtros' => $totalRecords,
            'total_con_filtros' => $filteredRecords
        ]
    ]);

} catch (Exception $e) {
    // Log del error
    error_log("Error en obtener_ventas.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Respuesta de error
    echo json_encode([
        'draw' => $draw ?? 1,
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>
