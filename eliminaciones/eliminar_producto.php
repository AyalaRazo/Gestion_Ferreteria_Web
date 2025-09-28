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
    // Iniciar transacción
    $pdo->beginTransaction();

    // 1. Eliminar registros de inventario
    $stmt = $pdo->prepare("DELETE FROM Inventario WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // 2. Eliminar detalles de transferencias
    $stmt = $pdo->prepare("DELETE FROM detalles_transferencia WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // 3. Eliminar detalles de ventas (corregido el nombre de la tabla)
    $stmt = $pdo->prepare("DELETE FROM detalles_venta WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // 4. Eliminar detalles de órdenes de compra
    $stmt = $pdo->prepare("DELETE FROM detalles_orden_compra WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // 5. Finalmente, eliminar el producto
    $stmt = $pdo->prepare("DELETE FROM Producto WHERE producto_id = ?");
    $stmt->execute([$producto_id]);

    // Si todo salió bien, confirmar los cambios
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto eliminado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';

} catch (PDOException $e) {
    // Si algo salió mal, revertir los cambios
    $pdo->rollBack();
    $_SESSION['mensaje'] = 'Error al eliminar el producto: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: ../consultas/productos.php');
exit();
?> 