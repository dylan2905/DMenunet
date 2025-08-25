<?php
include_once "encabezado.php";
include_once "funciones.php";

$host = "localhost";
$usuario = "root";
$password = "";
$resultados = [];
$nombre_bd = "DMenunet";

try {
    // Conectar al servidor para crear la base de datos si no existe
    $conexion = new PDO("mysql:host=$host", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $creada_bd = $conexion->exec("CREATE DATABASE IF NOT EXISTS $nombre_bd");
    if ($creada_bd !== false) {
        array_push($resultados, "Base de datos '$nombre_bd' creada correctamente o ya existe.");
    }
    
    // Conectar a la base de datos recién creada
    $conexion = new PDO("mysql:host=$host;dbname=$nombre_bd", $usuario, $password);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Definición de las sentencias para crear las tablas
    $sentencias = [ 
        ["tabla" => "usuarios",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS usuarios(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            correo VARCHAR(100) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            telefono VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            rol VARCHAR(20) NOT NULL
        );'],
        
        ["tabla" => "informacion_negocio",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS informacion_negocio(
            nombre VARCHAR(100),
            telefono VARCHAR(15),
            numeroMesas TINYINT,
            logo VARCHAR(255)
        );'],

        ["tabla" => "categorias",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS categorias(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(50) NOT NULL,
            descripcion VARCHAR(255)
        );'],

        ["tabla" => "grupos",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS grupos(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            id_categoria BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255)
        );'],

        ["tabla" => "subgrupos",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS subgrupos(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            id_grupo BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255)
        );'],

        ["tabla" => "sub_subgrupos",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS sub_subgrupos(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            id_subgrupo BIGINT UNSIGNED NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255)
        );'],

        ["tabla" => "insumos",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS insumos(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            codigo VARCHAR(100) NOT NULL,
            nombre VARCHAR(100) NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            precio DECIMAL(6,2) NOT NULL,
            stock INT UNSIGNED NOT NULL,
            id_sub_subgrupo BIGINT UNSIGNED NULL,
            id_subgrupo BIGINT UNSIGNED NOT NULL
        );'],

        ["tabla" => "meseros",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS meseros(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL
        );'],
        
        ["tabla" => "ventas",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS ventas(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            idMesa TINYINT NOT NULL,
            cliente VARCHAR(100),
            fecha DATETIME NOT NULL,
            total DECIMAL(6,2) NOT NULL,
            pagado DECIMAL(6,2) NOT NULL,
            idUsuario BIGINT UNSIGNED NOT NULL,
            idMesero BIGINT UNSIGNED NULL
        );'],

        ["tabla" => "productos_venta",
         "sentencia" => 'CREATE TABLE IF NOT EXISTS productos_venta(
            id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
            idInsumo BIGINT UNSIGNED NOT NULL,
            precio DECIMAL(6,2) NOT NULL,
            cantidad INT NOT NULL,
            idVenta BIGINT UNSIGNED
        );']
    ];

    // Ejecutar las sentencias de creación de tablas
    foreach ($sentencias as $sentencia) {
        $conexion->exec($sentencia["sentencia"]);
        array_push($resultados, "Tabla '{$sentencia["tabla"]}' creada o ya existe.");
    }
    
    // Sentencias para crear las relaciones (claves foráneas)
    $sentencias_fk = [
        ["relacion" => "grupos_categorias",
         "sentencia" => 'ALTER TABLE grupos ADD CONSTRAINT fk_grupos_categorias FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE CASCADE;'],
         
        ["relacion" => "subgrupos_grupos",
         "sentencia" => 'ALTER TABLE subgrupos ADD CONSTRAINT fk_subgrupos_grupos FOREIGN KEY (id_grupo) REFERENCES grupos(id) ON DELETE CASCADE;'],
         
        ["relacion" => "sub_subgrupos_subgrupos",
         "sentencia" => 'ALTER TABLE sub_subgrupos ADD CONSTRAINT fk_sub_subgrupos_subgrupos FOREIGN KEY (id_subgrupo) REFERENCES subgrupos(id) ON DELETE CASCADE;'],

        ["relacion" => "insumos_subgrupos",
         "sentencia" => 'ALTER TABLE insumos ADD CONSTRAINT fk_insumos_subgrupos FOREIGN KEY (id_subgrupo) REFERENCES subgrupos(id) ON DELETE RESTRICT;'],

        ["relacion" => "insumos_sub_subgrupos",
         "sentencia" => 'ALTER TABLE insumos ADD CONSTRAINT fk_insumos_sub_subgrupos FOREIGN KEY (id_sub_subgrupo) REFERENCES sub_subgrupos(id) ON DELETE RESTRICT;'],
         
        ["relacion" => "ventas_usuarios",
         "sentencia" => 'ALTER TABLE ventas ADD CONSTRAINT fk_ventas_usuarios FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON DELETE RESTRICT;'],

        ["relacion" => "ventas_meseros",
         "sentencia" => 'ALTER TABLE ventas ADD CONSTRAINT fk_ventas_meseros FOREIGN KEY (idMesero) REFERENCES meseros(id) ON DELETE SET NULL;'],
         
        ["relacion" => "productos_venta_insumos",
         "sentencia" => 'ALTER TABLE productos_venta ADD CONSTRAINT fk_pv_insumos FOREIGN KEY (idInsumo) REFERENCES insumos(id) ON DELETE RESTRICT;'],
         
        ["relacion" => "productos_venta_ventas",
         "sentencia" => 'ALTER TABLE productos_venta ADD CONSTRAINT fk_pv_ventas FOREIGN KEY (idVenta) REFERENCES ventas(id) ON DELETE CASCADE;']
    ];
    
    // Intenta añadir las claves foráneas, ignorando errores si ya existen
    foreach ($sentencias_fk as $sentencia_fk) {
        try {
            $conexion->exec($sentencia_fk["sentencia"]);
            array_push($resultados, "Relación '{$sentencia_fk["relacion"]}' creada correctamente.");
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate foreign key constraint name') !== false || strpos($e->getMessage(), 'a foreign key constraint exists') !== false) {
                array_push($resultados, "La relación '{$sentencia_fk["relacion"]}' ya existe. Ignorando.");
            } else {
                throw $e; // Re-lanza otros errores
            }
        }
    }
    
} catch (PDOException $e) {
    array_push($resultados, "Error: " . $e->getMessage());
} finally {
    $conexion = null;
}

echo json_encode($resultados);
?>
