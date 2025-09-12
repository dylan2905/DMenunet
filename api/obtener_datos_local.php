<?php
header('Content-Type: application/json');
include_once "funciones.php";

try {
    $bd = conectarBaseDatos();
    $sentencia = $bd->prepare("SELECT nombre, telefono, email, ruc_nit, direccion FROM informacion_negocio LIMIT 1");
    $sentencia->execute();
    $data = $sentencia->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        echo json_encode($data);
    } else {
        echo json_encode([
            'nombre' => '',
            'telefono' => '',
            'email' => '',
            'ruc_nit' => '',
            'direccion' => ''
        ]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>