<?php
include_once "encabezado.php";
include_once "funciones.php";

// Verificar que llegan los datos
if (!isset($_POST['nombre'], $_POST['telefono'], $_POST['direccion'], $_POST['numeroMesas'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Faltan datos obligatorios"]);
    exit;
}

// Procesar la imagen si existe
$rutaLogo = null;
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $carpeta = __DIR__ . "/fotos/"; // carpeta api/fotos/
    
    if (!file_exists($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

    $nombreArchivo = uniqid("logo_") . "." . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $rutaDestino = $carpeta . $nombreArchivo;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $rutaDestino)) {
        $rutaLogo = "api/fotos/" . $nombreArchivo; // Ruta relativa para guardar en BD
    } else {
        echo json_encode(["success" => false, "message" => "Error al guardar la imagen"]);
        exit;
    }
}

// Construir objeto con la info
$informacion = (object)[
    "nombre" => $_POST['nombre'],
    "telefono" => $_POST['telefono'],
    "direccion" => $_POST['direccion'],
    "numeroMesas" => $_POST['numeroMesas'],
    "logo" => $rutaLogo
];

// Verificar si ya hay registro en BD
$hayAjustes = obtenerInformacionLocal();

// Decidir si actualiza o inserta
$resultado = (count($hayAjustes) > 0)
    ? actualizarInformacionLocal($informacion)
    : registrarInformacionLocal($informacion);

echo json_encode(["success" => $resultado]);
