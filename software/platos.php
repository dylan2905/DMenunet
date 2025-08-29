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
function obtenerPlatos($pdo) {
    try {
        $sql = "
            SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre
            FROM platos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
            ORDER BY c.nombre, s.nombre, p.nombre
        ";
        
        $stmt = $pdo->query($sql);
        $platos = $stmt->fetchAll();
        enviarRespuesta($platos);
    } catch (PDOException $e) {
        enviarRespuesta(['error' => 'Error al obtener platos: ' . $e->getMessage()], 500);
    }
}

function crearPlato($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos requeridos
    if (!isset($datos['nombre']) || !isset($datos['precio']) || !isset($datos['categoria_id']) ||
        empty(trim($datos['nombre'])) || empty($datos['precio']) || empty($datos['categoria_id'])) {
        enviarRespuesta(['error' => 'Nombre, precio y categoría son requeridos'], 400);
    }
    
    // Validar precio
    if (!is_numeric($datos['precio']) || $datos['precio'] < 0) {
        enviarRespuesta(['error' => 'El precio debe ser un número válido'], 400);
    }
    
    try {
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['categoria_id']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Verificar subcategoría si se proporciona
        if (!empty($datos['subcategoria_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM subcategorias WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$datos['subcategoria_id'], $datos['categoria_id']]);
            if (!$stmt->fetch()) {
                enviarRespuesta(['error' => 'La subcategoría especificada no existe o no pertenece a esta categoría'], 400);
            }
        }
        
        // Crear plato
        $sql = "INSERT INTO platos (nombre, descripcion, precio, categoria_id, subcategoria_id, estado, creado_en) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim($datos['nombre']),
            isset($datos['descripcion']) ? trim($datos['descripcion']) : null,
            $datos['precio'],
            $datos['categoria_id'],
            !empty($datos['subcategoria_id']) ? $datos['subcategoria_id'] : null,
            isset($datos['estado']) ? $datos['estado'] : 'activo'
        ]);
        
        $id = $pdo->lastInsertId();
        
        // Obtener el plato creado con información completa
        $stmt = $pdo->prepare("
            SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre
            FROM platos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $plato = $stmt->fetch();
        
        enviarRespuesta($plato, 201);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe un plato con ese nombre'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al crear plato: ' . $e->getMessage()], 500);
        }
    }
}

function actualizarPlato($pdo) {
    $datos = json_decode(file_get_contents('php://input'), true);
    
    // Validar campos requeridos
    if (!isset($datos['id']) || !isset($datos['nombre']) || !isset($datos['precio']) || !isset($datos['categoria_id']) ||
        empty($datos['id']) || empty(trim($datos['nombre'])) || empty($datos['precio']) || empty($datos['categoria_id'])) {
        enviarRespuesta(['error' => 'ID, nombre, precio y categoría son requeridos'], 400);
    }
    
    // Validar precio
    if (!is_numeric($datos['precio']) || $datos['precio'] < 0) {
        enviarRespuesta(['error' => 'El precio debe ser un número válido'], 400);
    }
    
    try {
        // Verificar que el plato existe
        $stmt = $pdo->prepare("SELECT id FROM platos WHERE id = ?");
        $stmt->execute([$datos['id']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'Plato no encontrado'], 404);
        }
        
        // Verificar que la categoría existe
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE id = ?");
        $stmt->execute([$datos['categoria_id']]);
        if (!$stmt->fetch()) {
            enviarRespuesta(['error' => 'La categoría especificada no existe'], 400);
        }
        
        // Verificar subcategoría si se proporciona
        if (!empty($datos['subcategoria_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM subcategorias WHERE id = ? AND categoria_id = ?");
            $stmt->execute([$datos['subcategoria_id'], $datos['categoria_id']]);
            if (!$stmt->fetch()) {
                enviarRespuesta(['error' => 'La subcategoría especificada no existe o no pertenece a esta categoría'], 400);
            }
        }
        
        // Actualizar plato
        $sql = "UPDATE platos SET nombre = ?, descripcion = ?, precio = ?, categoria_id = ?, subcategoria_id = ?, estado = ? WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            trim($datos['nombre']),
            isset($datos['descripcion']) ? trim($datos['descripcion']) : null,
            $datos['precio'],
            $datos['categoria_id'],
            !empty($datos['subcategoria_id']) ? $datos['subcategoria_id'] : null,
            isset($datos['estado']) ? $datos['estado'] : 'activo',
            $datos['id']
        ]);
        
        // Obtener el plato actualizado con información completa
        $stmt = $pdo->prepare("
            SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre
            FROM platos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN subcategorias s ON p.subcategoria_id = s.id
            WHERE p.id = ?
        ");
        $stmt->execute([$datos['id']]);
        $plato = $stmt->fetch();
        
        enviarRespuesta($plato);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            enviarRespuesta(['error' => 'Ya existe un plato con ese nombre'], 409);
        } else {
            enviarRespuesta(['error' => 'Error al actualizar plato: ' . $e->getMessage()], 500);
        }
    }
}

function eliminarPlato($pdo) {
    if (!isset($_GET['id'])) {
        enviarRespuesta(['error' => 'ID de plato requerido'], 400);
    }
    
    $id = $_GET['id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM platos WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            enviarRespuesta(['error' => 'Plato no encontrado'], 404);
        }
        
        enviarRespuesta(['mensaje' => 'Plato eliminado correctamente']);
        
    } catch (PDOException $e) {
        enviarRespuesta(['error' => 'Error al eliminar plato: ' . $e->getMessage()], 500);
    }
}

// Función para enviar respuestas JSON
function enviarRespuesta($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Enrutador básico según método HTTP
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        obtenerPlatos($pdo);
        break;
    case 'POST':
        crearPlato($pdo);
        break;
    case 'PUT':
        actualizarPlato($pdo);
        break;
    case 'DELETE':
        eliminarPlato($pdo);
        break;
    default:
        enviarRespuesta(['error' => 'Método no permitido'], 405);
        break;
}

?>