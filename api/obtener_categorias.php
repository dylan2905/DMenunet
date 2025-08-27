<?php
// obtener_categorias.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

try {
    // Consulta para obtener las categorías únicas
    $sentencia = $bd->prepare("SELECT DISTINCT categoria FROM insumos ORDER BY categoria");
    $sentencia->execute();
    $categorias = $sentencia->fetchAll(PDO::FETCH_ASSOC);

    $respuesta = [];
    foreach ($categorias as $fila) {
        $respuesta[] = [
            'id' => $fila['categoria'],
            'nombre' => $fila['categoria']
        ];
    }
    
    echo json_encode($respuesta);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
