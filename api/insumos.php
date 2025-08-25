<?php
// insumos.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        // Obtener un solo insumo por ID
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $sentencia = $bd->prepare("SELECT * FROM insumos WHERE id = ?");
            $sentencia->execute([$id]);
            $insumo = $sentencia->fetch(PDO::FETCH_ASSOC);
            echo json_encode($insumo);
        } else {
            // Obtener todos los insumos
            $sentencia = $bd->query("SELECT * FROM insumos ORDER BY nombre ASC");
            $insumos = $sentencia->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($insumos);
        }
        break;

    case 'PUT':
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        $id = $data['id'] ?? null;
        $stock = $data['stock'] ?? null;
        $precio = $data['precio'] ?? null;

        if (!$id || (!isset($stock) && !isset($precio))) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Faltan datos para la actualización.']);
            exit;
        }

        $sentencia = $bd->prepare("UPDATE insumos SET stock = ?, precio = ? WHERE id = ?");
        $resultado = $sentencia->execute([$stock, $precio, $id]);

        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Insumo actualizado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el insumo.']);
        }
        break;

    default:
        http_response_code(405); // Método no permitido
        echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
        break;
}
?>
