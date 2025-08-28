<?php
include_once "encabezado.php";
$categoria = json_decode(file_get_contents("php://input"));

if (!$categoria) {
    http_response_code(400);
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

include_once "funciones.php";

// llamamos a la función que ya tienes en funciones.php
$resultado = editarCategoria($categoria);

if ($resultado) {
    echo json_encode(["ok" => true, "mensaje" => "Categoría actualizada correctamente"]);
} else {
    http_response_code(500);
    echo json_encode(["ok" => false, "mensaje" => "Error al actualizar categoría"]);
}
