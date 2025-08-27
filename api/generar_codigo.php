<?php
// generar_codigo.php
include_once "../funciones.php";
header('Content-Type: application/json; charset=utf-8');

$bd = conectarBaseDatos();

// Se leen los parámetros de la URL (GET)
$categoria = $_GET['categoria'] ?? '';
$grupo = $_GET['grupo'] ?? '';
$subgrupo = $_GET['subgrupo'] ?? '';
$sub_subgrupo = $_GET['sub_subgrupo'] ?? '';

// Asegurarse de que al menos la categoría está presente para evitar errores
if (empty($categoria)) {
    http_response_code(400);
    echo json_encode(['error' => 'La categoría es obligatoria para generar el código.']);
    exit;
}

try {
    // Definir los segmentos del código
    $segmentos = [];
    $segmentos[] = $categoria;
    
    // Si el grupo está presente, lo añadimos. Si no, usamos '00'.
    $segmentos[] = !empty($grupo) ? $grupo : '00';
    
    // Si el subgrupo está presente, lo añadimos. Si no, usamos '00'.
    $segmentos[] = !empty($subgrupo) ? $subgrupo : '00';

    // Si el sub-subgrupo está presente, lo añadimos. Si no, usamos '00'.
    $segmentos[] = !empty($sub_subgrupo) ? $sub_subgrupo : '00';
    
    $base_codigo = implode('-', $segmentos);

    // Buscar el último producto con un código que empiece con la base_codigo
    $sentencia = $bd->prepare("SELECT codigo FROM insumos WHERE codigo LIKE ? ORDER BY codigo DESC LIMIT 1");
    $sentencia->execute(["$base_codigo-%"]);
    $ultimo_codigo_fila = $sentencia->fetch(PDO::FETCH_ASSOC);

    $siguiente_numero = 1;
    if ($ultimo_codigo_fila) {
        $ultimo_codigo = $ultimo_codigo_fila['codigo'];
        // Extraer el número del final del código
        $partes = explode('-', $ultimo_codigo);
        $ultimo_numero_str = end($partes);
        $ultimo_numero = intval($ultimo_numero_str);
        $siguiente_numero = $ultimo_numero + 1;
    }
    
    // Formatear el siguiente número para que tenga 3 dígitos (ej. 001, 002)
    $siguiente_numero_formato = sprintf("%03d", $siguiente_numero);
    
    // Construir el código final
    $codigo_final = $base_codigo . '-' . $siguiente_numero_formato;

    echo json_encode(['codigo' => $codigo_final]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
