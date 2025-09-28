<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de producto no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/productos.php');
    exit();
}

$producto_id = intval($_GET['id']);

try {
    // Verificar que el producto exista
    $stmt = $pdo->prepare("SELECT producto_id FROM Producto WHERE producto_id = ?");
    $stmt->execute([$producto_id]);
    if (!$stmt->fetch()) {
        throw new Exception('El producto no existe.');
    }

    // Verificar si el producto tiene ventas asociadas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM Detalles_venta 
        WHERE producto_id = ?
    ");
    $stmt->execute([$producto_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar el producto porque tiene ventas asociadas. Considere marcarlo como inactivo en su lugar.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar registros de inventario
    $stmt = $pdo->prepare("DELETE FROM Inventario WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // Eliminar el producto
    $stmt = $pdo->prepare("DELETE FROM Producto WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto eliminado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: ../consultas/productos.php');
exit(); 