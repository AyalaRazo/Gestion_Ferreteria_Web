<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de venta no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/ventas.php');
    exit();
}

$venta_id = intval($_GET['id']);

try {
    // Verificar que la venta exista
    $stmt = $pdo->prepare("SELECT venta_id, estado FROM Venta WHERE venta_id = ?");
    $stmt->execute([$venta_id]);
    $venta = $stmt->fetch();
    
    if (!$venta) {
        throw new Exception('La venta no existe.');
    }

    // No permitir eliminar ventas completadas
    if ($venta['estado'] === 'COMPLETADA') {
        throw new Exception('No se pueden eliminar ventas completadas. Considere marcarla como cancelada en su lugar.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Obtener detalles de la venta para restaurar el inventario si es necesario
    if ($venta['estado'] === 'PENDIENTE') {
        $stmt = $pdo->prepare("
            SELECT 
                d.producto_id,
                d.cantidad,
                d.sucursal_id
            FROM Detalles_venta d
            WHERE d.venta_id = ?
        ");
        $stmt->execute([$venta_id]);
        $detalles = $stmt->fetchAll();

        // Restaurar el inventario
        foreach ($detalles as $detalle) {
            $stmt = $pdo->prepare("
                UPDATE Inventario 
                SET stock_actual = stock_actual + ?
                WHERE producto_id = ? AND sucursal_id = ?
            ");
            $stmt->execute([
                $detalle['cantidad'],
                $detalle['producto_id'],
                $detalle['sucursal_id']
            ]);
        }
    }

    // Eliminar registros relacionados
    $stmt = $pdo->prepare("DELETE FROM Pago WHERE venta_id = ?");
    $stmt->execute([$venta_id]);

    $stmt = $pdo->prepare("DELETE FROM Detalles_venta WHERE venta_id = ?");
    $stmt->execute([$venta_id]);

    // Eliminar la venta
    $stmt = $pdo->prepare("DELETE FROM Venta WHERE venta_id = ?");
    $stmt->execute([$venta_id]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Venta eliminada exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: ../consultas/ventas.php');
exit(); 