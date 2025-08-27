<?php
// obtener_subgrupos.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Se leen los parámetros de la URL (GET)
$categoria = $_GET['categoria'] ?? '';
$grupo = $_GET['grupo'] ?? '';

if (empty($categoria)) {
    http_response_code(400);
    echo json_encode(['error' => 'La categoría es obligatoria.']);
    exit;
}

try {
    // Consulta para obtener los subgrupos únicos para la categoría y grupo dados
    $sentencia = $bd->prepare("SELECT DISTINCT subgrupo FROM insumos WHERE categoria = ? AND grupo = ? ORDER BY subgrupo");
    $sentencia->execute([$categoria, $grupo]);
    $subgrupos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    $respuesta = [];
    foreach ($subgrupos as $fila) {
        // Se omite si el subgrupo es nulo o vacío
        if (!empty($fila['subgrupo'])) {
            $respuesta[] = [
                'id' => $fila['subgrupo'],
                'nombre' => $fila['subgrupo']
            ];
        }
    }
    
    echo json_encode($respuesta);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
