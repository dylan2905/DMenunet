<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos de impuestos.']);
        exit;
    }

    $bd->beginTransaction();

    $bd->exec("DELETE FROM impuestos");

    $sentencia = $bd->prepare("INSERT INTO impuestos (nombre, valor) VALUES (?, ?)");

    foreach ($data as $impuesto) {
        $nombre = $impuesto['nombre'] ?? '';
        $valor = $impuesto['valor'] ?? 0;
        $sentencia->execute([$nombre, $valor]);
    }

    $bd->commit();
    echo json_encode(['success' => true, 'message' => 'Impuestos actualizados con éxito.']);

} catch (PDOException $e) {
    $bd->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error al actualizar impuestos: ' . $e->getMessage()]);
}
?>