<?php
// obtener_grupos.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Se lee el parámetro de la URL (GET) o del cuerpo (POST)
$categoria = $_GET['categoria'] ?? '';

if (empty($categoria)) {
    http_response_code(400);
    echo json_encode(['error' => 'La categoría es obligatoria.']);
    exit;
}

try {
    // Consulta para obtener los grupos únicos para la categoría dada
    $sentencia = $bd->prepare("SELECT DISTINCT grupo FROM insumos WHERE categoria = ? ORDER BY grupo");
    $sentencia->execute([$categoria]);
    $grupos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    $respuesta = [];
    foreach ($grupos as $fila) {
        // Se omite si el grupo es nulo o vacío
        if (!empty($fila['grupo'])) {
            $respuesta[] = [
                'id' => $fila['grupo'],
                'nombre' => $fila['grupo']
            ];
        }
    }
    
    echo json_encode($respuesta);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
