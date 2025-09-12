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

    $nombre = $data['nombre_completo'] ?? '';
    $telefono = $data['telefono'] ?? '';
    $correo = $data['correo'] ?? '';
    $contrasena = $data['contrasena'] ?? '';
    $rol = $data['rol'] ?? '';

    // Validación simple de datos
    if (empty($nombre) || empty($correo) || empty($contrasena) || empty($rol)) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
        exit;
    }

    // Cifrar la contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    $sentencia = $bd->prepare("INSERT INTO usuarios (nombre, telefono, correo, contrasena_hash, rol) VALUES (?, ?, ?, ?, ?)");
    
    if ($sentencia->execute([$nombre, $telefono, $correo, $contrasena_hash, $rol])) {
        echo json_encode(['success' => true, 'message' => 'Usuario creado con éxito.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear el usuario.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>