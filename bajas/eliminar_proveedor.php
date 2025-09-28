<?php
session_start();
require_once '../includes/db.php';

$proveedor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($proveedor_id <= 0) {
    $_SESSION['mensaje'] = "ID de proveedor no válido.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ../consultas/proveedores.php");
    exit;
}

try {
    // Verificar si el proveedor existe
    $stmt = $pdo->prepare("SELECT nombre FROM Proveedor WHERE proveedor_id = :id");
    $stmt->execute([':id' => $proveedor_id]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proveedor) {
        throw new Exception("Proveedor no encontrado.");
    }

    // Eliminar el proveedor
    $stmt = $pdo->prepare("DELETE FROM Proveedor WHERE proveedor_id = :id");
    $resultado = $stmt->execute([':id' => $proveedor_id]);

    if ($resultado) {
        $_SESSION['mensaje'] = "Proveedor eliminado exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } else {
        throw new Exception("Error al eliminar el proveedor.");
    }
} catch (PDOException $e) {
    // Manejar errores de base de datos
    if ($e->getCode() == '23000') {
        $_SESSION['mensaje'] = "No se puede eliminar el proveedor porque tiene registros asociados.";
    } else {
        $_SESSION['mensaje'] = "Error de base de datos: " . $e->getMessage();
    }
    $_SESSION['mensaje_tipo'] = "error";
} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
}

header("Location: ../consultas/proveedores.php");
exit; 