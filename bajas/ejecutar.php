<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $tabla = $_POST['tabla'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM $tabla WHERE ".$tabla."_id = ?");
        $stmt->execute([$id]);
        echo "Registro eliminado exitosamente!";
    } catch (PDOException $e) {
        die("Error al eliminar: " . $e->getMessage());
    }
}
?>