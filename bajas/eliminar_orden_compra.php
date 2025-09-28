<?php
session_start();
require_once '../includes/db.php';

$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orden_id <= 0) {
    $_SESSION['mensaje'] = "ID de orden no válido.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ../consultas/ordenes_compra.php");
    exit;
}

try {
    // Verificar si la orden existe y está en estado PENDIENTE
    $stmt = $pdo->prepare("
        SELECT estado 
        FROM orden_compra 
        WHERE orden_compra_id = :id
    ");
    $stmt->execute([':id' => $orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        throw new Exception("Orden no encontrada.");
    }

    if ($orden['estado'] !== 'PENDIENTE') {
        throw new Exception("Solo se pueden eliminar órdenes en estado PENDIENTE.");
    }

    $pdo->beginTransaction();

    // Eliminar los detalles de la orden
    $stmt = $pdo->prepare("DELETE FROM detalles_orden_compra WHERE orden_compra_id = :id");
    $stmt->execute([':id' => $orden_id]);

    // Eliminar la orden
    $stmt = $pdo->prepare("DELETE FROM orden_compra WHERE orden_compra_id = :id");
    $resultado = $stmt->execute([':id' => $orden_id]);

    if ($resultado) {
        $pdo->commit();
        $_SESSION['mensaje'] = "Orden eliminada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } else {
        throw new Exception("Error al eliminar la orden.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
}

header("Location: ../consultas/ordenes_compra.php");
exit; 