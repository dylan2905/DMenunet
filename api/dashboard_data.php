<?php
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Parámetros de filtro de la URL
$fechaInicio = $_GET['fecha_inicio'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
$meseroId = $_GET['mesero_id'] ?? null;
$periodo = $_GET['periodo'] ?? 'dia'; // 'dia', 'semana', 'mes'

// Parámetros para la consulta de ventas por mesero
$params = [];
$fechaFiltro = "";
if ($fechaInicio && $fechaFin) {
    $fechaFiltro = "AND v.fecha BETWEEN ? AND ?";
    $params[] = $fechaInicio . " 00:00:00";
    $params[] = $fechaFin . " 23:59:59";
}

$meseroFiltro = "";
if ($meseroId) {
    $meseroFiltro = "AND v.mesero_id = ?";
    $params[] = $meseroId;
}

try {
    // Consulta para Ventas en el Tiempo
    $sqlVentas = "SELECT 
        DATE(v.fecha) AS fecha_dia,
        SUM(v.total) AS total_ventas
    FROM ventas v
    WHERE v.estado = 'pagada' $fechaFiltro $meseroFiltro";
    
    switch ($periodo) {
        case 'semana':
            $sqlVentas .= " GROUP BY YEARWEEK(v.fecha, 1)";
            break;
        case 'mes':
            $sqlVentas .= " GROUP BY MONTH(v.fecha), YEAR(v.fecha)";
            break;
        case 'dia':
        default:
            $sqlVentas .= " GROUP BY fecha_dia";
            break;
    }
    $sqlVentas .= " ORDER BY fecha_dia ASC";
    $sentenciaVentas = $bd->prepare($sqlVentas);
    $sentenciaVentas->execute($params);
    $ventasAgregadas = $sentenciaVentas->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para Top Productos
    $sqlProductos = "SELECT
        i.nombre AS producto_nombre,
        SUM(vi.cantidad) AS cantidad_vendida
    FROM ventas v
    JOIN ventas_insumos vi ON v.id = vi.venta_id
    JOIN insumos i ON vi.insumo_id = i.id
    WHERE v.estado = 'pagada' $fechaFiltro $meseroFiltro
    GROUP BY i.id ORDER BY cantidad_vendida DESC LIMIT 5";
    $sentenciaProductos = $bd->prepare($sqlProductos);
    $sentenciaProductos->execute($params);
    $productosVendidos = $sentenciaProductos->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para KPIs
    $sqlKpis = "SELECT 
        SUM(total) AS total_ingresos,
        AVG(total) AS ticket_promedio
    FROM ventas WHERE estado = 'pagada' $fechaFiltro $meseroFiltro";
    $sentenciaKpis = $bd->prepare($sqlKpis);
    $sentenciaKpis->execute($params);
    $kpis = $sentenciaKpis->fetch(PDO::FETCH_ASSOC);

    // Consulta para Ventas Anuladas
    $sqlAnuladas = "SELECT COUNT(*) AS total_anuladas FROM ventas WHERE estado = 'anulada' $fechaFiltro $meseroFiltro";
    $sentenciaAnuladas = $bd->prepare($sqlAnuladas);
    $sentenciaAnuladas->execute($params);
    $anuladas = $sentenciaAnuladas->fetch(PDO::FETCH_ASSOC);

    // Consulta para Ventas por Mesero
    $sqlVentasMesero = "SELECT
        m.nombre AS nombre_mesero,
        SUM(v.total) AS total_ventas
    FROM ventas v
    JOIN meseros m ON v.mesero_id = m.id
    WHERE v.estado = 'pagada' $fechaFiltro
    GROUP BY m.id ORDER BY total_ventas DESC";
    $sentenciaVentasMesero = $bd->prepare($sqlVentasMesero);
    $sentenciaVentasMesero->execute($params);
    $ventasPorMesero = $sentenciaVentasMesero->fetchAll(PDO::FETCH_ASSOC);

    // --- NUEVAS CONSULTAS ---
    
    // Consulta para Ventas por Categoría
    $sqlVentasCategoria = "SELECT
        c.nombre AS categoria_nombre,
        SUM(vi.cantidad) AS total_vendido
    FROM ventas v
    JOIN ventas_insumos vi ON v.id = vi.venta_id
    JOIN insumos i ON vi.insumo_id = i.id
    JOIN categorias c ON i.categoria_id = c.id
    WHERE v.estado = 'pagada' $fechaFiltro $meseroFiltro
    GROUP BY c.id ORDER BY total_vendido DESC";
    $sentenciaVentasCategoria = $bd->prepare($sqlVentasCategoria);
    $sentenciaVentasCategoria->execute($params);
    $ventasPorCategoria = $sentenciaVentasCategoria->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para Ventas por Día de la Semana
    $sqlVentasDiaSemana = "SELECT
        WEEKDAY(v.fecha) AS dia_semana_num,
        SUM(v.total) AS total_ventas
    FROM ventas v
    WHERE v.estado = 'pagada' $fechaFiltro $meseroFiltro
    GROUP BY dia_semana_num ORDER BY dia_semana_num ASC";
    $sentenciaVentasDiaSemana = $bd->prepare($sqlVentasDiaSemana);
    $sentenciaVentasDiaSemana->execute($params);
    $ventasDiaSemana = $sentenciaVentasDiaSemana->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para Ventas por Hora del Día
    $sqlVentasHora = "SELECT
        HOUR(v.fecha) AS hora_dia,
        SUM(v.total) AS total_ventas
    FROM ventas v
    WHERE v.estado = 'pagada' $fechaFiltro $meseroFiltro
    GROUP BY hora_dia ORDER BY hora_dia ASC";
    $sentenciaVentasHora = $bd->prepare($sqlVentasHora);
    $sentenciaVentasHora->execute($params);
    $ventasPorHora = $sentenciaVentasHora->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ventas_agregadas' => $ventasAgregadas,
        'productos_vendidos' => $productosVendidos,
        'kpis' => $kpis,
        'ventas_anuladas' => $anuladas['total_anuladas'],
        'ventas_por_mesero' => $ventasPorMesero,
        'ventas_por_categoria' => $ventasPorCategoria,
        'ventas_por_dia_semana' => $ventasDiaSemana,
        'ventas_por_hora' => $ventasPorHora
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener los datos del dashboard: ' . $e->getMessage()]);
}
?>