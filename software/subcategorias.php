<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de base de datos
$servername = "localhost";
$username = "root";  // Cambia por tus credenciales
$password = ""; // Cambia por tus credenciales
$dbname = "dmenunet"; // Cambia por tu base de datos

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit();
}

function obtenerSubcategorias($pdo) {
    try {
        if (isset($_GET['id_categoria'])) {
            // Obtener subcategorías de una categoría específica
            $stmt = $pdo->prepare("SELECT * FROM grupos WHERE id_categoria = ? ORDER BY nombre");
            $stmt->execute([$_GET['id_categoria']]);
        } else {
            // Obtener todas las subcategorías
            $stmt = $pdo->query("
                SELECT g.*, c.nombre as categoria_nombre 
                FROM grupos g 
                LEFT JOIN categorias c ON g.id_categoria = c.id 
                ORDER BY c.nombre, g.nombre
            ");
        }
        
        $subcategorias = $stmt->fetchAll();
        enviarRespuesta($subcategorias);
    } catch (PDOException $e) {
        enviarRespuesta(['error' => 'Error al obtener grupos: ' . $e->getMessage()], 500);
    }
}

function crearSubcategoria($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($datos['nombre']) || !isset($datos['categoria_id']) || 
        empty(trim($datos['nombre'])) || empty($datos['categoria_id'])) {
        enviarRespuesta(['error' => 'Nombre y id_categoria son requeridos'], 400);
    }
    
    try {
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['id_categoria']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Crear subcategoría
        $stmt = $pdo->prepare("INSERT INTO grupos (nombre, id_categoria) VALUES (?, ?)");
        $stmt->execute([trim($datos['nombre']), $datos['id_categoria']]);
        
        $id = $pdo->lastInsertId();
        $grupo = [
            'id' => $id,
            'nombre' => trim($datos['nombre']),
            'id_categoria' => $datos['id_categoria']
        ];
        
        enviarRespuesta($grupo, 201);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe un grupo con ese nombre en esta categoría'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al crear grupo: ' . $e->getMessage()], 500);
        }
    }
}

function actualizarSubcategoria($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($datos['id']) || !isset($datos['nombre']) || !isset($datos['id_categoria']) ||
        empty(trim($datos['nombre'])) || empty($datos['id_categoria'])) {
        enviarRespuesta(['error' => 'ID, nombre y id_categoria son requeridos'], 400);
    }
    
    try {
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['id_categoria']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Actualizar subcategoría
        $stmt = $pdo->prepare("UPDATE grupos SET nombre = ?, id_categoria = ? WHERE id = ?");
        $stmt->execute([trim($datos['nombre']), $datos['id_categoria'], $datos['id']]);
        
        if ($stmt->rowCount() === 0) {
            enviarRespuesta(['error' => 'Grupo no encontrado'], 404);
        }
        
        enviarRespuesta(['mensaje' => 'Grupo actualizado correctamente']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe un grupo con ese nombre en esta categoría'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al actualizar grupo: ' . $e->getMessage()], 500);
        }
    }
}

function eliminarSubcategoria($pdo) {
    if (!isset($_GET['id'])) {
        enviarRespuesta(['error' => 'ID de grupo requerido'], 400);
    }
    
    $id = $_GET['id'];
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Actualizar platos que tengan este grupo (poner grupo_id en NULL)
        $stmt = $pdo->prepare("UPDATE platos SET grupo_id = NULL WHERE grupo_id = ?");
        $stmt->execute([$id]);
        
        // Eliminar grupo
        $stmt = $pdo->prepare("DELETE FROM grupos WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            enviarRespuesta(['error' => 'Grupo no encontrado'], 404);
        }
        
        // Confirmar transacción
        $pdo->commit();
        enviarRespuesta(['mensaje' => 'Grupo eliminado correctamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        enviarRespuesta(['error' => 'Error al eliminar grupo: ' . $e->getMessage()], 500);
    }
}
// Función común para enviar respuestas JSON
function enviarRespuesta($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Determinar método HTTP
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        obtenerSubcategorias($pdo);
        break;
    case 'POST':
        crearSubcategoria($pdo);
        break;
    case 'PUT':
        actualizarSubcategoria($pdo);
        break;
    case 'DELETE':
        eliminarSubcategoria($pdo);
        break;
    default:
        enviarRespuesta(['error' => 'Método no permitido'], 405);
        break;
}

?>