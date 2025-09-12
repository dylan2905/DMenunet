<?php
header('Content-Type: application/json');
session_start();

include_once "../funciones.php"; // La ruta se ajusta para salir de la carpeta 'api'

try {
    $bd = conectarBaseDatos();
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['correo']) || empty($data['contrasena'])) {
        echo json_encode(['success' => false, 'message' => 'El correo y la contraseña son obligatorios.']);
        exit;
    }

    $correo = $data['correo'];
    $contrasena = $data['contrasena'];

    // Buscar al usuario por correo electrónico
    $sentencia = $bd->prepare("SELECT id, nombre, contrasena_hash, rol FROM usuarios WHERE correo = ?");
    $sentencia->execute([$correo]);
    $usuario = $sentencia->fetch(PDO::FETCH_ASSOC);

    // Verificar si el usuario existe y si la contraseña es correcta
    if ($usuario && password_verify($contrasena, $usuario['contrasena_hash'])) {
        // Inicio de sesión exitoso
        $_SESSION['loggedin'] = true;
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['user_name'] = $usuario['nombre'];
        $_SESSION['user_rol'] = $usuario['rol'];

        echo json_encode([
            'success' => true,
            'message' => 'Inicio de sesión exitoso.',
            'user' => [
                'id' => $usuario['id'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol']
            ]
        ]);
    } else {
        // Credenciales inválidas
        echo json_encode(['success' => false, 'message' => 'Correo o contraseña incorrectos.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos: ' . $e->getMessage()]);
}
?>