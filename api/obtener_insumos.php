<?php
// Incluir el archivo de encabezado para la conexión a la base de datos
include_once "encabezado.php";
// Incluir el archivo de funciones donde está `obtenerInsumos()`
include_once "funciones.php";

// Establecer el encabezado para que el navegador sepa que la respuesta es un JSON
header('Content-Type: application/json');

// Obtener el ID de la categoría de los parámetros de la URL
// La clave es 'categoria_id', tal como la envía el JavaScript en pedidos.html
$categoriaId = $_GET['categoria_id'] ?? null;

// Validar que se recibió un ID de categoría
if (empty($categoriaId)) {
    // Devolver un error si no se proporciona el ID de categoría
    echo json_encode(['error' => 'Se requiere el ID de la categoría']);
    http_response_code(400); // 400 Bad Request
    exit();
}

try {
    // Llamar a la función `obtenerInsumosPorCategoria` con el ID recibido
    $insumos = obtenerInsumosPorCategoria($categoriaId);
    echo json_encode($insumos);

} catch (Exception $e) {
    // Manejar errores de la base de datos
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['error' => 'Error al obtener los insumos: ' . $e->getMessage()]);
}

?>