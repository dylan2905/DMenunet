<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de base de datos
$servername = "localhost";
$username = "root";  // Cambia por tus credenciales
$password = ""; // Cambia por tus credenciales
$dbname = "menunet"; // Cambia por tu base de datos

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit();
}

function obtenerSubcategorias($pdo) {
    try {
        if (isset($_GET['categoria_id'])) {
            // Obtener subcategorías de una categoría específica
            $stmt = $pdo->prepare("SELECT * FROM subcategorias WHERE categoria_id = ? ORDER BY nombre");
            $stmt->execute([$_GET['categoria_id']]);
        } else {
            // Obtener todas las subcategorías
            $stmt = $pdo->query("
                SELECT s.*, c.nombre as categoria_nombre 
                FROM subcategorias s 
                LEFT JOIN categorias c ON s.categoria_id = c.id 
                ORDER BY c.nombre, s.nombre
            ");
        }
        
        $subcategorias = $stmt->fetchAll();
        enviarRespuesta($subcategorias);
    } catch (PDOException $e) {
        enviarRespuesta(['error' => 'Error al obtener subcategorías: ' . $e->getMessage()], 500);
    }
}

function crearSubcategoria($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($datos['nombre']) || !isset($datos['categoria_id']) || 
        empty(trim($datos['nombre'])) || empty($datos['categoria_id'])) {
        enviarRespuesta(['error' => 'Nombre y categoría son requeridos'], 400);
    }
    
    try {
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['categoria_id']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Crear subcategoría
        $stmt = $pdo->prepare("INSERT INTO subcategorias (nombre, categoria_id) VALUES (?, ?)");
        $stmt->execute([trim($datos['nombre']), $datos['categoria_id']]);
        
        $id = $pdo->lastInsertId();
        $subcategoria = [
            'id' => $id,
            'nombre' => trim($datos['nombre']),
            'categoria_id' => $datos['categoria_id']
        ];
        
        enviarRespuesta($subcategoria, 201);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe una subcategoría con ese nombre en esta categoría'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al crear subcategoría: ' . $e->getMessage()], 500);
        }
    }
}

function actualizarSubcategoria($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($datos['id']) || !isset($datos['nombre']) || !isset($datos['categoria_id']) ||
        empty(trim($datos['nombre'])) || empty($datos['categoria_id'])) {
        enviarRespuesta(['error' => 'ID, nombre y categoría son requeridos'], 400);
    }
    
    try {
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['categoria_id']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Actualizar subcategoría
        $stmt = $pdo->prepare("UPDATE subcategorias SET nombre = ?, categoria_id = ? WHERE id = ?");
        $stmt->execute([trim($datos['nombre']), $datos['categoria_id'], $datos['id']]);
        
        if ($stmt->rowCount() === 0) {
            enviarRespuesta(['error' => 'Subcategoría no encontrada'], 404);
        }
        
        enviarRespuesta(['mensaje' => 'Subcategoría actualizada correctamente']);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe una subcategoría con ese nombre en esta categoría'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al actualizar subcategoría: ' . $e->getMessage()], 500);
        }
    }
}

function eliminarSubcategoria($pdo) {
    if (!isset($_GET['id'])) {
        enviarRespuesta(['error' => 'ID de subcategoría requerido'], 400);
    }
    
    $id = $_GET['id'];
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Actualizar platos que tengan esta subcategoría (poner subcategoria_id en NULL)
        $stmt = $pdo->prepare("UPDATE platos SET subcategoria_id = NULL WHERE subcategoria_id = ?");
        $stmt->execute([$id]);
        
        // Eliminar subcategoría
        $stmt = $pdo->prepare("DELETE FROM subcategorias WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            $pdo->rollBack();
            enviarRespuesta(['error' => 'Subcategoría no encontrada'], 404);
        }
        
        // Confirmar transacción
        $pdo->commit();
        enviarRespuesta(['mensaje' => 'Subcategoría eliminada correctamente']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        enviarRespuesta(['error' => 'Error al eliminar subcategoría: ' . $e->getMessage()], 500);
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