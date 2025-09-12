<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT monto_inicial, fecha_apertura FROM cajas WHERE estado = 'abierta' ORDER BY fecha_apertura DESC LIMIT 1");
    $sentencia->execute();
    $caja = $sentencia->fetch(PDO::FETCH_OBJ);

    if ($caja) {
        $ventas_simuladas = [
            'efectivo' => 150.75,
            'tarjeta' => 90.50,
            'bonos' => 25.00,
            'transferencia' => 120.00
        ];

        $response = [
            'success' => true,
            'abierta' => true,
            'montoInicial' => (float)$caja->monto_inicial,
            'horaApertura' => $caja->fecha_apertura,
            'ventas' => $ventas_simuladas,
        ];
        echo json_encode($response);
    } else {
        echo json_encode([
            'success' => true,
            'abierta' => false,
            'message' => 'No hay una caja abierta en este momento.'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>