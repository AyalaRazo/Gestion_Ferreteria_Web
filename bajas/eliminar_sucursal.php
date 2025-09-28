<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de sucursal no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit();
}

$sucursal_id = intval($_GET['id']);

try {
    // Verificar que la sucursal exista
    $stmt = $pdo->prepare("SELECT sucursal_id FROM Sucursal WHERE sucursal_id = ?");
    $stmt->execute([$sucursal_id]);
    if (!$stmt->fetch()) {
        throw new Exception('La sucursal no existe.');
    }

    // Verificar si la sucursal tiene empleados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Empleado WHERE sucursal_id = ?");
    $stmt->execute([$sucursal_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar la sucursal porque tiene empleados asignados. Reasigne los empleados primero.');
    }

    // Verificar si la sucursal tiene ventas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Venta WHERE sucursal_id = ?");
    $stmt->execute([$sucursal_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar la sucursal porque tiene ventas registradas. Considere marcarla como inactiva en su lugar.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar registros de inventario
    $stmt = $pdo->prepare("DELETE FROM Inventario WHERE sucursal_id = ?");
    $stmt->execute([$sucursal_id]);

    // Eliminar la sucursal
    $stmt = $pdo->prepare("DELETE FROM Sucursal WHERE sucursal_id = ?");
    $stmt->execute([$sucursal_id]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Sucursal eliminada exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';

} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: ../consultas/sucursales.php');
exit(); 