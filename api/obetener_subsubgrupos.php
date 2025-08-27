<?php
// obtener_subsubgrupos.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Se leen los parámetros de la URL (GET)
$categoria = $_GET['categoria'] ?? '';
$grupo = $_GET['grupo'] ?? '';
$subgrupo = $_GET['subgrupo'] ?? '';

if (empty($categoria) || empty($grupo) || empty($subgrupo)) {
    http_response_code(400);
    echo json_encode(['error' => 'La categoría, el grupo y el subgrupo son obligatorios.']);
    exit;
}

try {
    // Consulta para obtener los sub-subgrupos únicos para la categoría, grupo y subgrupo dados
    $sentencia = $bd->prepare("SELECT DISTINCT sub_subgrupo FROM insumos WHERE categoria = ? AND grupo = ? AND subgrupo = ? ORDER BY sub_subgrupo");
    $sentencia->execute([$categoria, $grupo, $subgrupo]);
    $sub_subgrupos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    $respuesta = [];
    foreach ($sub_subgrupos as $fila) {
        // Se omite si el sub-subgrupo es nulo o vacío
        if (!empty($fila['sub_subgrupo'])) {
            $respuesta[] = [
                'id' => $fila['sub_subgrupo'],
                'nombre' => $fila['sub_subgrupo']
            ];
        }
    }
    
    echo json_encode($respuesta);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
