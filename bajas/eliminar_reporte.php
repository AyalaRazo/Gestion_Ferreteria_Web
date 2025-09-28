<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de reporte no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/reportes.php');
    exit();
}

$reporte_id = intval($_GET['id']);

try {
    // Verificar que el reporte exista
    $stmt = $pdo->prepare("SELECT reporte_id FROM Reporte WHERE reporte_id = ?");
    $stmt->execute([$reporte_id]);
    if (!$stmt->fetch()) {
        throw new Exception('El reporte no existe.');
    }

    // Eliminar el reporte
    $stmt = $pdo->prepare("DELETE FROM Reporte WHERE reporte_id = ?");
    $stmt->execute([$reporte_id]);

    $_SESSION['mensaje'] = 'Reporte eliminado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';

} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

header('Location: ../consultas/reportes.php');
exit(); 