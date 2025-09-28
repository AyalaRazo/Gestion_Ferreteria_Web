<?php
include '../includes/db.php';
$id = $_GET['id'];
$tabla = $_GET['tabla'];

// Obtener datos para mostrar antes de borrar
$stmt = $pdo->prepare("SELECT * FROM $tabla WHERE ".$tabla."_id = ?");
$stmt->execute([$id]);
$registro = $stmt->fetch();
?>

<h2>Confirmar Baja</h2>
<p>¿Está seguro de eliminar este registro?</p>
<!-- Mostrar datos del registro -->
<form action="ejecutar.php" method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="tabla" value="<?= $tabla ?>">
    <button type="submit">Confirmar</button>
    <a href="../consultas/generales.php">Cancelar</a>
</form>