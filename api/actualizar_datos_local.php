<?php
// actualizar_datos_local.php

include_once "encabezado.php";
include_once "funciones.php";

// Verificar si llegaron datos
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
    exit;
}

// Obtener los datos del formulario
$nombre = $_POST['nombre'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$numeroMesas = $_POST['numeroMesas'] ?? 0;
$direccion = $_POST['direccion'] ?? '';
$logo = null;

// Si subieron un nuevo archivo de logo
if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $directorio = "fotos/";
    if (!is_dir($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombreArchivo = uniqid("logo_") . "." . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
    $ruta = $directorio . $nombreArchivo;

    if (move_uploaded_file($_FILES['logo']['tmp_name'], $ruta)) {
        $logo = $ruta;
    }
} else {
    // Si no enviaron archivo, mantener el logo que ya está en DB
    $hayAjustes = obtenerInformacionLocal();
    if (count($hayAjustes) > 0) {
        $logo = $hayAjustes[0]['logo'];
    }
}

// Crear objeto para pasarlo a la función
$informacion = (object)[
    "nombre" => $nombre,
    "telefono" => $telefono,
    "numeroMesas" => $numeroMesas,
    "direccion" => $direccion,
    "logo" => $logo
];

// Actualizar en base de datos
$resultado = actualizarInformacionLocal($informacion);

// Responder
echo json_encode($resultado);
