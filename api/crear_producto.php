<?php
// crear_producto.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Se lee el cuerpo de la solicitud JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Se extraen los datos del producto
$nombre = $data['nombre'] ?? '';
$precio = $data['precio'] ?? 0;
$stock = $data['stock'] ?? 0;
$categoria = $data['categoria'] ?? '';
$grupo = $data['grupo'] ?? '';
$subgrupo = $data['subgrupo'] ?? '';
$sub_subgrupo = $data['sub_subgrupo'] ?? '';
$codigo = $data['codigo'] ?? '';
$detalle = $data['detalle'] ?? '';

// Validar datos
if (empty($nombre) || empty($precio) || empty($stock) || empty($codigo)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'El nombre, precio, stock y código son obligatorios.']);
    exit;
}

try {
    // Preparar la consulta SQL para insertar un nuevo producto
    $sentencia = $bd->prepare("INSERT INTO insumos (nombre, precio, stock, categoria, grupo, subgrupo, sub_subgrupo, codigo, detalle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Ejecutar la consulta con los datos del formulario
    $resultado = $sentencia->execute([
        $nombre, 
        $precio, 
        $stock, 
        $categoria,
        $grupo,
        $subgrupo,
        $sub_subgrupo,
        $codigo,
        $detalle
    ]);

    if ($resultado) {
        echo json_encode(['success' => true, 'message' => 'Producto creado correctamente.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error al guardar el producto.']);
    }

} catch (PDOException $e) {
    // Capturar error si el código ya existe
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'El código de producto ya existe.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
    }
}
?>
