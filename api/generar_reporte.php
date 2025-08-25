<?php
// generar_reporte.php
require('../fpdf/fpdf.php');
include_once "../funciones.php";

header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Parámetros de filtro
$fechaInicio = $_GET['fecha_inicio'] ?? null;
$fechaFin = $_GET['fecha_fin'] ?? null;
$meseroId = $_GET['mesero_id'] ?? null;

// Construir la consulta con los filtros
$params = [];
$whereClause = "";

if ($fechaInicio && $fechaFin) {
    $whereClause .= " AND v.fecha BETWEEN ? AND ?";
    $params[] = $fechaInicio . " 00:00:00";
    $params[] = $fechaFin . " 23:59:59";
}

if ($meseroId) {
    $whereClause .= " AND v.idMesero = ?";
    $params[] = $meseroId;
}

try {
    // Consulta para Ventas en el Tiempo
    $sqlVentas = "SELECT DATE(fecha) AS fecha_dia, SUM(total) AS total_ventas
                  FROM ventas
                  WHERE estado = 'pagada' " . $whereClause . "
                  GROUP BY fecha_dia ORDER BY fecha_dia ASC";
    $sentenciaVentas = $bd->prepare($sqlVentas);
    $sentenciaVentas->execute($params);
    $ventasAgregadas = $sentenciaVentas->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para Top Productos
    $sqlProductos = "SELECT i.nombre AS producto_nombre, SUM(pv.cantidad) AS cantidad_vendida
                     FROM ventas v
                     JOIN productos_venta pv ON v.id = pv.idVenta
                     JOIN insumos i ON pv.idInsumo = i.id
                     WHERE v.estado = 'pagada' " . $whereClause . "
                     GROUP BY i.id ORDER BY cantidad_vendida DESC LIMIT 5";
    $sentenciaProductos = $bd->prepare($sqlProductos);
    $sentenciaProductos->execute($params);
    $productosVendidos = $sentenciaProductos->fetchAll(PDO::FETCH_ASSOC);

    // Consulta para KPIs
    $sqlKpis = "SELECT SUM(total) AS total_ingresos, AVG(total) AS ticket_promedio, SUM(CASE WHEN estado = 'anulada' THEN 1 ELSE 0 END) AS ventas_anuladas
                FROM ventas WHERE 1=1 " . $whereClause;
    $sentenciaKpis = $bd->prepare($sqlKpis);
    $sentenciaKpis->execute($params);
    $kpis = $sentenciaKpis->fetch(PDO::FETCH_ASSOC);
    
    // Crear el documento PDF
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Reporte de Ventas DMENUNET', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, 'Generado el: ' . date('Y-m-d H:i:s'), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Resumen de KPIs
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Resumen del Periodo', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, 'Ventas Totales: $' . number_format($kpis['total_ingresos'] ?? 0, 2), 0, 1);
    $pdf->Cell(0, 8, 'Ticket Promedio: $' . number_format($kpis['ticket_promedio'] ?? 0, 2), 0, 1);
    $pdf->Cell(0, 8, 'Ventas Anuladas: ' . ($kpis['ventas_anuladas'] ?? 0), 0, 1);
    $pdf->Ln(10);

    // Ventas por día
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Ventas por Dia', 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 7, 'Fecha', 1);
    $pdf->Cell(50, 7, 'Total de Ventas', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 12);
    foreach ($ventasAgregadas as $venta) {
        $pdf->Cell(50, 7, $venta['fecha_dia'], 1);
        $pdf->Cell(50, 7, '$' . number_format($venta['total_ventas'], 2), 1);
        $pdf->Ln();
    }
    $pdf->Ln(10);

    // Top 5 Productos
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, 'Top 5 Productos Vendidos', 0, 1);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 7, 'Producto', 1);
    $pdf->Cell(50, 7, 'Cantidad Vendida', 1);
    $pdf->Ln();
    $pdf->SetFont('Arial', '', 12);
    foreach ($productosVendidos as $producto) {
        $pdf->Cell(80, 7, utf8_decode($producto['producto_nombre']), 1);
        $pdf->Cell(50, 7, $producto['cantidad_vendida'], 1);
        $pdf->Ln();
    }
    
    $pdf->Output('I', 'Reporte_Ventas.pdf');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    echo "Error al generar el reporte: " . $e->getMessage();
}
?>
