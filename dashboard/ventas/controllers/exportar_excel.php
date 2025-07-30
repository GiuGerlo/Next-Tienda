<?php
/**
 * Controlador para exportar ventas a Excel
 * Sistema Next - Gestión de Ventas
 */

// Iniciar sesión y verificar autenticación
session_start();
require_once '../../../controllers/auth.php';

// Verificar autenticación sin redirigir
if (!isAuthenticated()) {
    http_response_code(401);
    exit('No autorizado');
}

// Incluir conexión a la base de datos
require_once '../../../config/connect.php';

// Configurar zona horaria de Argentina
date_default_timezone_set('America/Argentina/Buenos_Aires');

try {
    // Obtener filtros de la URL
    $fechaDesde = $_GET['fecha_desde'] ?? '';
    $fechaHasta = $_GET['fecha_hasta'] ?? '';
    $estadoPago = $_GET['estado_pago'] ?? '';
    $metodoPago = $_GET['metodo_pago'] ?? '';
    $searchValue = $_GET['search'] ?? '';
    
    // Log para debug
    error_log("Exportando Excel con filtros: " . json_encode($_GET));
    
    // Query base para obtener ventas con filtros
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
                v.metodo_pago
            ) as metodo_pago,
            v.monto_pagado,
            v.monto_adeudado,
            (SELECT COUNT(*) FROM detalle_ventas dv WHERE dv.venta_id = v.id) as cantidad_productos
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
    
    // Filtro de método de pago
    if (!empty($metodoPago)) {
        if ($metodoPago === 'pendiente') {
            $whereClause .= " AND (v.estado_pago = 'pendiente' OR NOT EXISTS (SELECT 1 FROM pagos_ventas p WHERE p.venta_id = v.id))";
        } else {
            $whereClause .= " AND EXISTS (SELECT 1 FROM pagos_ventas p WHERE p.venta_id = v.id AND p.metodo_pago = ?)";
            $params[] = $metodoPago;
        }
    }
    
    // Ejecutar query
    $query = $selectQuery . $whereClause . " ORDER BY v.fecha_venta DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar si hay datos para exportar
    if (empty($ventas)) {
        // Si no hay datos, mostrar una página informativa
        echo '<html><body style="font-family: Arial; text-align: center; padding: 50px;">';
        echo '<h2>No hay datos para exportar</h2>';
        echo '<p>No se encontraron ventas que coincidan con los filtros aplicados.</p>';
        echo '<p>Intenta modificar los filtros o el rango de fechas.</p>';
        echo '</body></html>';
        exit();
    }
    
    // Crear el contenido HTML para el Excel
    $html = generarExcelHTML($ventas, $fechaDesde, $fechaHasta, $estadoPago, $metodoPago, $searchValue);
    
    // Configurar headers para descarga de Excel
    $filename = 'Reporte_Ventas_' . date('Y-m-d_H-i-s') . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Agregar BOM para UTF-8
    echo "\xEF\xBB\xBF";
    echo $html;
    
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error al generar el reporte: ' . $e->getMessage();
}

function generarExcelHTML($ventas, $fechaDesde, $fechaHasta, $estadoPago, $metodoPago, $searchValue) {
    // Calcular estadísticas
    $totalVentas = count($ventas);
    $montoTotal = array_sum(array_column($ventas, 'total'));
    $montoPagado = array_sum(array_column($ventas, 'monto_pagado'));
    $montoAdeudado = array_sum(array_column($ventas, 'monto_adeudado'));
    
    // Obtener fecha actual
    $fechaReporte = date('d/m/Y H:i');
    
    // Determinar el título del reporte según filtros
    $tituloFiltros = '';
    if (!empty($fechaDesde) || !empty($fechaHasta)) {
        $desde = !empty($fechaDesde) ? date('d/m/Y', strtotime($fechaDesde)) : 'Inicio';
        $hasta = !empty($fechaHasta) ? date('d/m/Y', strtotime($fechaHasta)) : 'Hoy';
        $tituloFiltros .= "Período: $desde - $hasta";
    }
    if (!empty($estadoPago)) {
        $estados = ['completo' => 'Completo', 'parcial' => 'Parcial', 'pendiente' => 'Pendiente'];
        $tituloFiltros .= (!empty($tituloFiltros) ? ' | ' : '') . 'Estado: ' . ($estados[$estadoPago] ?? $estadoPago);
    }
    if (!empty($metodoPago)) {
        $metodos = [
            'efectivo' => 'Efectivo',
            'tarjeta_debito' => 'Tarjeta Débito',
            'tarjeta_credito' => 'Tarjeta Crédito', 
            'transferencia' => 'Transferencia',
            'cuenta_corriente' => 'Cuenta Corriente',
            'otro' => 'Otro'
        ];
        $tituloFiltros .= (!empty($tituloFiltros) ? ' | ' : '') . 'Método: ' . ($metodos[$metodoPago] ?? $metodoPago);
    }
    if (!empty($searchValue)) {
        $tituloFiltros .= (!empty($tituloFiltros) ? ' | ' : '') . 'Búsqueda: ' . $searchValue;
    }
    
    $html = '
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Reporte de Ventas - Sistema Next</title>
        <style>
            @page {
                margin: 1in;
                size: A4 landscape;
            }
            
            body { 
                font-family: "Segoe UI", Arial, sans-serif; 
                margin: 0;
                padding: 20px;
                background-color: #ffffff;
                color: #333;
                line-height: 1.4;
            }
            
            /* HEADER CORPORATIVO */
            .header-corporativo {
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                color: white;
                padding: 25px;
                margin: -20px -20px 30px -20px;
                text-align: center;
                border-bottom: 4px solid #3498db;
            }
            
            .logo-empresa {
                font-size: 32px;
                font-weight: bold;
                margin: 0 0 8px 0;
                letter-spacing: 2px;
            }
            
            .subtitulo-empresa {
                font-size: 16px;
                margin: 0 0 15px 0;
                opacity: 0.9;
                font-weight: 300;
            }
            
            .info-reporte {
                font-size: 14px;
                opacity: 0.8;
                border-top: 1px solid rgba(255,255,255,0.2);
                padding-top: 15px;
                margin-top: 15px;
            }
            
            /* SECCIÓN DE FILTROS */
            .seccion-filtros {
                background: #f8f9fa;
                border: 2px solid #e9ecef;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 25px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .titulo-filtros {
                color: #495057;
                font-size: 18px;
                font-weight: 600;
                margin: 0 0 12px 0;
                display: flex;
                align-items: center;
            }
            
            .titulo-filtros::before {
                content: "🔍";
                margin-right: 8px;
                font-size: 20px;
            }
            
            .filtros-aplicados {
                background: #e7f3ff;
                border: 2px solid #b8daff;
                border-radius: 8px;
                padding: 12px 16px;
                color: #004085;
                font-size: 14px;
                font-weight: 500;
            }
            
            /* SECCIÓN DE ESTADÍSTICAS */
            .seccion-estadisticas {
                margin-bottom: 30px;
            }
            
            .titulo-estadisticas {
                color: #2c3e50;
                font-size: 20px;
                font-weight: 600;
                margin: 0 0 20px 0;
                text-align: center;
                border-bottom: 3px solid #3498db;
                padding-bottom: 10px;
            }
            
            .grid-estadisticas {
                display: table;
                width: 100%;
                border-collapse: separate;
                border-spacing: 15px;
            }
            
            .fila-estadisticas {
                display: table-row;
            }
            
            .tarjeta-estadistica {
                display: table-cell;
                background: white;
                border: 2px solid #dee2e6;
                border-radius: 12px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                vertical-align: top;
                width: 25%;
            }
            
            .icono-estadistica {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                margin: 0 auto 15px auto;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                font-weight: bold;
                color: white;
            }
            
            .icono-ventas { background: linear-gradient(135deg, #28a745, #20c997); }
            .icono-facturado { background: linear-gradient(135deg, #007bff, #0056b3); }
            .icono-cobrado { background: linear-gradient(135deg, #28a745, #1e7e34); }
            .icono-adeudado { background: linear-gradient(135deg, #dc3545, #c82333); }
            
            .valor-estadistica {
                font-size: 24px;
                font-weight: bold;
                margin: 0 0 5px 0;
                color: #2c3e50;
            }
            
            .label-estadistica {
                font-size: 12px;
                color: #6c757d;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            /* TABLA PRINCIPAL */
            .seccion-tabla {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                border: 2px solid #dee2e6;
            }
            
            .titulo-tabla {
                background: linear-gradient(135deg, #495057, #343a40);
                color: white;
                padding: 20px;
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                text-align: center;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 11px;
                margin: 0;
            }
            
            th {
                background: linear-gradient(135deg, #6c757d, #495057);
                color: white;
                padding: 15px 8px;
                text-align: center;
                font-weight: bold;
                font-size: 11px;
                border: 1px solid #495057;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            td {
                padding: 12px 8px;
                border: 1px solid #dee2e6;
                text-align: center;
                vertical-align: middle;
                font-size: 11px;
            }
            
            tr:nth-child(even) {
                background-color: #f8f9fa;
            }
            
            tr:hover {
                background-color: #e2e6ea;
            }
            
            /* BADGES DE ESTADO */
            .badge {
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                display: inline-block;
                min-width: 70px;
            }
            
            .badge-completo {
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                box-shadow: 0 2px 4px rgba(40, 167, 69, 0.3);
            }
            
            .badge-parcial {
                background: linear-gradient(135deg, #ffc107, #e0a800);
                color: #000;
                box-shadow: 0 2px 4px rgba(255, 193, 7, 0.3);
            }
            
            .badge-pendiente {
                background: linear-gradient(135deg, #dc3545, #c82333);
                color: white;
                box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
            }
            
            /* FORMATEO DE MONTOS */
            .monto {
                font-weight: bold;
                font-family: "Courier New", monospace;
            }
            
            .monto-total { color: #007bff; }
            .monto-pagado { color: #28a745; }
            .monto-adeudado { color: #dc3545; }
            
            /* COLUMNAS ESPECÍFICAS */
            .col-id { width: 6%; font-weight: bold; color: #6c757d; }
            .col-cliente { width: 18%; text-align: left; font-weight: 500; }
            .col-fecha { width: 12%; font-size: 10px; }
            .col-total { width: 10%; }
            .col-estado { width: 12%; }
            .col-metodo { width: 12%; }
            .col-pagado { width: 10%; }
            .col-adeudado { width: 10%; }
            .col-productos { width: 8%; }
            
            /* FOOTER */
            .footer-reporte {
                margin-top: 40px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                border: 1px solid #dee2e6;
                text-align: center;
                font-size: 11px;
                color: #6c757d;
            }
            
            .firma-empresa {
                font-weight: bold;
                color: #2c3e50;
                margin-bottom: 5px;
            }
            
            /* RESALTADOS */
            .destacar-fila {
                background: linear-gradient(135deg, #fff3cd, #ffeaa7) !important;
                border-left: 4px solid #ffc107 !important;
            }
            
            .sin-datos {
                text-align: center;
                padding: 40px;
                color: #6c757d;
                font-style: italic;
                background: #f8f9fa;
            }
        </style>
    </head>
    <body>
        <!-- HEADER CORPORATIVO -->
        <div class="header-corporativo">
            <div class="logo-empresa">NEXT</div>
            <div class="subtitulo-empresa">Sistema de Gestión de Tienda de Ropa</div>
            <div class="info-reporte">
                <strong>Reporte de Ventas</strong><br>
                Chañar Ladeado, Santa Fe, Argentina<br>
                Generado el: ' . $fechaReporte . '
            </div>
        </div>
        
        <!-- SECCIÓN DE FILTROS -->
        <div class="seccion-filtros">
            <div class="titulo-filtros">Filtros Aplicados</div>
            <div class="filtros-aplicados">
                ' . (!empty($tituloFiltros) ? $tituloFiltros : 'Sin filtros aplicados - Mostrando todas las ventas del sistema') . '
            </div>
        </div>
        
        <!-- SECCIÓN DE ESTADÍSTICAS -->
        <div class="seccion-estadisticas">
            <div class="titulo-estadisticas">📊 Resumen Ejecutivo</div>
            <div class="grid-estadisticas">
                <div class="fila-estadisticas">
                    <div class="tarjeta-estadistica">
                        <div class="icono-estadistica icono-ventas">📈</div>
                        <div class="valor-estadistica">' . number_format($totalVentas) . '</div>
                        <div class="label-estadistica">Total Ventas</div>
                    </div>
                    <div class="tarjeta-estadistica">
                        <div class="icono-estadistica icono-facturado">💰</div>
                        <div class="valor-estadistica">$' . number_format($montoTotal, 0, ',', '.') . '</div>
                        <div class="label-estadistica">Total Facturado</div>
                    </div>
                    <div class="tarjeta-estadistica">
                        <div class="icono-estadistica icono-cobrado">✅</div>
                        <div class="valor-estadistica">$' . number_format($montoPagado, 0, ',', '.') . '</div>
                        <div class="label-estadistica">Total Cobrado</div>
                    </div>
                    <div class="tarjeta-estadistica">
                        <div class="icono-estadistica icono-adeudado">⏰</div>
                        <div class="valor-estadistica">$' . number_format($montoAdeudado, 0, ',', '.') . '</div>
                        <div class="label-estadistica">Por Cobrar</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TABLA PRINCIPAL -->
        <div class="seccion-tabla">
            <div class="titulo-tabla">🛍️ Detalle de Ventas</div>
            <table>
                <thead>
                    <tr>
                        <th class="col-id">ID</th>
                        <th class="col-cliente">Cliente</th>
                        <th class="col-fecha">Fecha y Hora</th>
                        <th class="col-total">Total</th>
                        <th class="col-estado">Estado Pago</th>
                        <th class="col-metodo">Método Pago</th>
                        <th class="col-pagado">Pagado</th>
                        <th class="col-adeudado">Adeudado</th>
                        <th class="col-productos">Productos</th>
                    </tr>
                </thead>
                <tbody>';
    
    // Generar filas de datos
    if (empty($ventas)) {
        $html .= '
                    <tr>
                        <td colspan="9" class="sin-datos">
                            📭 No se encontraron ventas que coincidan con los filtros aplicados
                        </td>
                    </tr>';
    } else {
        foreach ($ventas as $index => $venta) {
            // Formatear fecha
            $fecha = new DateTime($venta['fecha_venta']);
            $fechaFormateada = $fecha->format('d/m/Y H:i');
            
            // Formatear estado de pago
            $estadoBadge = '';
            switch ($venta['estado_pago']) {
                case 'completo':
                    $estadoBadge = '<span class="badge badge-completo">Completo</span>';
                    break;
                case 'parcial':
                    $estadoBadge = '<span class="badge badge-parcial">Parcial</span>';
                    break;
                case 'pendiente':
                    $estadoBadge = '<span class="badge badge-pendiente">Pendiente</span>';
                    break;
            }
            
            // Formatear método de pago
            $metodos = [
                'efectivo' => '💵 Efectivo',
                'tarjeta_debito' => '💳 T. Débito',
                'tarjeta_credito' => '💳 T. Crédito',
                'transferencia' => '🏦 Transferencia',
                'cuenta_corriente' => '📋 Cta. Corriente',
                'otro' => '📄 Otro'
            ];
            $metodoPagoFormateado = $metodos[$venta['metodo_pago']] ?? $venta['metodo_pago'];
            
            // Formatear montos
            $totalFormateado = '$' . number_format($venta['total'], 0, ',', '.');
            $pagadoFormateado = '$' . number_format($venta['monto_pagado'], 0, ',', '.');
            $adeudadoFormateado = '$' . number_format($venta['monto_adeudado'], 0, ',', '.');
            
            // Resaltar filas con adeudos grandes
            $claseFilaEspecial = ($venta['monto_adeudado'] > 50000) ? 'destacar-fila' : '';
            
            $html .= '
                        <tr class="' . $claseFilaEspecial . '">
                            <td class="col-id">#' . $venta['id'] . '</td>
                            <td class="col-cliente">' . htmlspecialchars($venta['cliente_nombre']) . '</td>
                            <td class="col-fecha">' . $fechaFormateada . '</td>
                            <td class="col-total"><span class="monto monto-total">' . $totalFormateado . '</span></td>
                            <td class="col-estado">' . $estadoBadge . '</td>
                            <td class="col-metodo">' . $metodoPagoFormateado . '</td>
                            <td class="col-pagado"><span class="monto monto-pagado">' . $pagadoFormateado . '</span></td>
                            <td class="col-adeudado"><span class="monto monto-adeudado">' . $adeudadoFormateado . '</span></td>
                            <td class="col-productos">' . $venta['cantidad_productos'] . ' items</td>
                        </tr>';
        }
    }
    
    $html .= '
                </tbody>
            </table>
        </div>
        
        <!-- FOOTER -->
        <div class="footer-reporte">
            <div class="firma-empresa">NEXT - Sistema de Gestión de Tienda de Ropa</div>
            <div>Chañar Ladeado, Santa Fe, Argentina</div>
            <div>Reporte generado automáticamente el ' . $fechaReporte . '</div>
            <div style="margin-top: 10px; font-size: 10px; color: #adb5bd;">
                📊 Este reporte contiene información confidencial de la empresa
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}
?>
