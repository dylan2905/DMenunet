<?php
// Incluir el archivo de funciones donde se encuentra la conexión a la base de datos
include_once "../funciones.php";

// Establecer el encabezado para que el navegador sepa que la respuesta es un JSON
header('Content-Type: application/json');

try {
    // Obtener la conexión a la base de datos
    $bd = conectarBaseDatos();
    
    // Consulta para obtener las mesas con pedidos activos.
    // Asumimos que una mesa está 'activa' o 'ocupada' si tiene un pedido en la tabla `ventas`
    // con un estado que no sea 'finalizada' o 'pagada'.
    $sentencia = $bd->query("SELECT mesa_id FROM ventas WHERE estado = 'activa' GROUP BY mesa_id");
    
    $mesasActivas = $sentencia->fetchAll(PDO::FETCH_COLUMN);
    
    // Devolver el resultado en formato JSON
    echo json_encode($mesasActivas);

} catch (PDOException $e) {
    // Manejar errores de la base de datos
    http_response_code(500); // 500 Internal Server Error
    echo json_encode(['error' => 'Error al obtener el estado de las mesas: ' . $e->getMessage()]);
}

?>