<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos para el cierre.']);
        exit;
    }

    $monto_inicial = $data['montoInicial'] ?? 0;
    $efectivo_contado = $data['efectivoContado'] ?? 0;
    $efectivo_esperado = $data['efectivoEsperado'] ?? 0;
    $diferencia = $data['diferencia'] ?? 0;
    $ventas_efectivo = $data['ventas']['efectivo'] ?? 0;
    $ventas_tarjeta = $data['ventas']['tarjeta'] ?? 0;
    $ventas_bonos = $data['ventas']['bonos'] ?? 0;
    $ventas_transferencia = $data['ventas']['transferencia'] ?? 0;
    $fecha_cierre = date('Y-m-d H:i:s');

    $ventas_totales = $ventas_efectivo + $ventas_tarjeta + $ventas_bonos + $ventas_transferencia;

    $sentencia = $bd->prepare("SELECT id FROM cajas WHERE estado = 'abierta' ORDER BY fecha_apertura DESC LIMIT 1");
    $sentencia->execute();
    $caja = $sentencia->fetch(PDO::FETCH_OBJ);
    $caja_id = $caja ? $caja->id : null;

    if (!$caja_id) {
        echo json_encode(['success' => false, 'message' => 'No hay una caja abierta para cerrar.']);
        exit;
    }

    $sentencia = $bd->prepare("UPDATE cajas SET 
        fecha_cierre = ?, 
        estado = 'cerrada', 
        ventas_totales = ?, 
        ventas_efectivo = ?, 
        ventas_tarjeta = ?, 
        ventas_bonos = ?, 
        ventas_transferencia = ?, 
        efectivo_contado = ?, 
        diferencia = ? 
        WHERE id = ?");

    if ($sentencia->execute([
        $fecha_cierre,
        $ventas_totales,
        $ventas_efectivo,
        $ventas_tarjeta,
        $ventas_bonos,
        $ventas_transferencia,
        $efectivo_contado,
        $diferencia,
        $caja_id
    ])) {
        echo json_encode(['success' => true, 'message' => 'Caja cerrada con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cerrar la caja.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>