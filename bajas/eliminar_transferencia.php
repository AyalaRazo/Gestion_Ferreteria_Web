<?php
session_start();
require_once '../includes/db.php';

$transferencia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transferencia_id <= 0) {
    $_SESSION['mensaje'] = "ID de transferencia no válido.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ../consultas/transferencias.php");
    exit;
}

try {
    // Verificar si la transferencia existe y está en estado PENDIENTE
    $stmt = $pdo->prepare("
        SELECT estado 
        FROM transferencia_producto 
        WHERE transferencia_producto_id = :id
    ");
    $stmt->execute([':id' => $transferencia_id]);
    $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transferencia) {
        throw new Exception("Transferencia no encontrada.");
    }

    if ($transferencia['estado'] !== 'SOLICITADA') {
        throw new Exception("Solo se pueden eliminar transferencias en estado SOLICITADA.");
    }

    $pdo->beginTransaction();

    // Eliminar los detalles de la transferencia
    $stmt = $pdo->prepare("DELETE FROM detalles_transferencia WHERE transferencia_producto_id = :id");
    $stmt->execute([':id' => $transferencia_id]);

    // Eliminar la transferencia
    $stmt = $pdo->prepare("DELETE FROM transferencia_producto WHERE transferencia_producto_id = :id");
    $resultado = $stmt->execute([':id' => $transferencia_id]);

    if ($resultado) {
        $pdo->commit();
        $_SESSION['mensaje'] = "Transferencia eliminada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } else {
        throw new Exception("Error al eliminar la transferencia.");
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
}

header("Location: ../consultas/transferencias.php");
exit; 