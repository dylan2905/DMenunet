<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['montoInicial'])) {
        echo json_encode(['success' => false, 'message' => 'El monto inicial es obligatorio.']);
        exit;
    }

    $monto_inicial = $data['montoInicial'];
    $fecha_apertura = date('Y-m-d H:i:s');

    $sentencia = $bd->prepare("INSERT INTO cajas (monto_inicial, fecha_apertura, estado) VALUES (?, ?, 'abierta')");

    if ($sentencia->execute([$monto_inicial, $fecha_apertura])) {
        echo json_encode(['success' => true, 'message' => 'Caja abierta con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al abrir la caja.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>