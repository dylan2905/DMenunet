<?php
// api/pedidos/obtener.php
header('Content-Type: application/json; charset=utf-8');
require_once '../funciones.php';

$bd = conectarBaseDatos();

try {
    // Si se recibe un ID, buscar un pedido específico
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = $_GET['id'];
        
        $sentencia = $bd->prepare("
            SELECT 
                p.*, 
                m.nombre AS mesa_nombre 
            FROM pedidos p 
            LEFT JOIN mesas m ON p.mesa_id = m.id 
            WHERE p.id = ?
        ");
        $sentencia->execute([$id]);
        $pedido = $sentencia->fetch(PDO::FETCH_ASSOC);

        if ($pedido) {
            // Obtener los productos del pedido
            $sentencia_productos = $bd->prepare("
                SELECT 
                    pp.cantidad, 
                    pv.nombre, 
                    pv.precio, 
                    pv.id AS producto_id 
                FROM pedido_productos pp 
                JOIN productos_venta pv ON pp.producto_id = pv.id 
                WHERE pp.pedido_id = ?
            ");
            $sentencia_productos->execute([$id]);
            $productos = $sentencia_productos->fetchAll(PDO::FETCH_ASSOC);
            
            $pedido['productos'] = $productos;
            $pedido['total'] = floatval($pedido['total']);
            
            echo json_encode($pedido);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Pedido no encontrado.']);
        }
    } else {
        // Si no hay ID, obtener todos los pedidos activos
        $sentencia = $bd->query("
            SELECT 
                p.id, 
                p.mesa_id, 
                p.cliente_nombre,
                p.num_personas,
                p.total,
                p.estado,
                m.nombre AS mesa_nombre
            FROM pedidos p
            LEFT JOIN mesas m ON p.mesa_id = m.id
            WHERE p.estado IN ('en_preparacion', 'servido')
            ORDER BY p.fecha_creacion DESC
        ");
        $pedidos = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        foreach ($pedidos as &$pedido) {
            $pedido['id'] = strval($pedido['id']);
            $pedido['total'] = floatval($pedido['total']);
            $pedido['estado_texto'] = str_replace('_', ' ', $pedido['estado']);
        }
        
        echo json_encode($pedidos);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>