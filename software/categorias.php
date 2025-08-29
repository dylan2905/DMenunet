<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "dmenunet";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    enviarRespuesta(['error' => 'Error de conexión: ' . $e->getMessage()], 500);
}

// Función común para enviar respuestas JSON
function enviarRespuesta($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $stmt = $pdo->query("SELECT * FROM categorias ORDER BY nombre");
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            enviarRespuesta($categorias);
        } catch(PDOException $e) {
            enviarRespuesta(['error' => 'Error al obtener categorías: ' . $e->getMessage()], 500);
        }
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['nombre']) || empty(trim($input['nombre']))) {
            enviarRespuesta(['error' => 'El nombre de la categoría es requerido'], 400);
        }

        try {
            // Validar duplicados
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt->execute([trim($input['nombre'])]);
            if ($stmt->fetch()) {
                enviarRespuesta(['error' => 'Ya existe una categoría con ese nombre'], 409);
            }

            $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (?)");
            $stmt->execute([trim($input['nombre'])]);

            enviarRespuesta([
                'success' => true,
                'id' => $pdo->lastInsertId(),
                'message' => 'Categoría creada exitosamente'
            ], 201);
        } catch(PDOException $e) {
            enviarRespuesta(['error' => 'Error al crear categoría: ' . $e->getMessage()], 500);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
            enviarRespuesta(['error' => 'ID de categoría inválido'], 400);
        }

        try {
            $pdo->beginTransaction();

            // Eliminar subcategorías
            $stmt = $pdo->prepare("DELETE FROM grupos WHERE categoria_id = ?");
            $stmt->execute([$_GET['id']]);

            // Eliminar platos
            $stmt = $pdo->prepare("DELETE FROM platos WHERE categoria_id = ?");
            $stmt->execute([$_GET['id']]);

            // Eliminar categoría
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$_GET['id']]);

            $pdo->commit();

            if ($stmt->rowCount() > 0) {
                enviarRespuesta(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
            } else {
                enviarRespuesta(['error' => 'Categoría no encontrada'], 404);
            }
        } catch(PDOException $e) {
            $pdo->rollBack();
            enviarRespuesta(['error' => 'Error al eliminar categoría: ' . $e->getMessage()], 500);
        }
        break;

    default:
        enviarRespuesta(['error' => 'Método no permitido'], 405);
        break;
}
