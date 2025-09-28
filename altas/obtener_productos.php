<?php
header('Content-Type: application/json');
require_once '../includes/db.php';

$sucursal_id = isset($_GET['sucursal_id']) ? intval($_GET['sucursal_id']) : 0;

if (!$sucursal_id) {
    echo json_encode(['error' => 'Sucursal no válida']);
    exit;
}

try {
    // Obtener productos que tienen stock en la sucursal
    $stmt = $pdo->prepare("
        SELECT 
            p.producto_id,
            p.codigo_producto,
            p.nombre_producto,
            p.precio,
            i.stock_actual
        FROM Producto p
        JOIN Inventario i ON p.producto_id = i.producto_id
        WHERE i.sucursal_id = :sucursal_id
        AND i.stock_actual > 0
        AND p.activo = 1
        ORDER BY p.nombre_producto
    ");

    $stmt->execute([':sucursal_id' => $sucursal_id]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($productos);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
} 