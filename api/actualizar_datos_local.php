<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data)) {
        echo json_encode(['success' => false, 'message' => 'No se recibieron datos.']);
        exit;
    }

    $nombre = $data['nombre'] ?? '';
    $telefono = $data['telefono'] ?? '';
    $email = $data['email'] ?? '';
    $ruc_nit = $data['ruc_nit'] ?? '';
    $direccion = $data['direccion'] ?? '';

    $sentencia = $bd->prepare("
        INSERT INTO informacion_negocio (id, nombre, telefono, email, ruc_nit, direccion) 
        VALUES (1, ?, ?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        nombre=?, telefono=?, email=?, ruc_nit=?, direccion=?
    ");

    if ($sentencia->execute([$nombre, $telefono, $email, $ruc_nit, $direccion, $nombre, $telefono, $email, $ruc_nit, $direccion])) {
        echo json_encode(['success' => true, 'message' => 'Información del restaurante actualizada con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la información.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>