<?php
// obtener_categorias.php
include_once "encabezado.php";
include_once "funciones.php";
header('Content-Type: application/json; charset=utf-8');

$categorias = obtenerCategorias();

if ($categorias) {
    echo json_encode($categorias);
} else {
    http_response_code(404);
    echo json_encode(["error" => "No se encontraron categorías"]);
}
