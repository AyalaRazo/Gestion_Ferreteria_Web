<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../consultas/generales.php');
    exit;
}

$id = $_POST['id'];
$tabla = $_POST['tabla'];

try {
    // Obtener la estructura de la tabla
    $stmt = $pdo->prepare("DESCRIBE $tabla");
    $stmt->execute();
    $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir la consulta de actualización
    $campos = [];
    $valores = [];
    $tipos = [];

    foreach ($columnas as $columna) {
        $campo = $columna['Field'];
        if ($campo != $tabla.'_id' && isset($_POST[$campo])) {
            $campos[] = "$campo = ?";
            $valores[] = $_POST[$campo];
            
            // Determinar el tipo de dato
            $tipo = $columna['Type'];
            if (strpos($tipo, 'int') !== false) {
                $valores[count($valores)-1] = intval($valores[count($valores)-1]);
            } elseif (strpos($tipo, 'decimal') !== false || strpos($tipo, 'float') !== false || strpos($tipo, 'double') !== false) {
                $valores[count($valores)-1] = floatval($valores[count($valores)-1]);
            }
        }
    }

    // Agregar el ID al final del array de valores
    $valores[] = $id;

    // Ejecutar la actualización
    $sql = "UPDATE $tabla SET " . implode(', ', $campos) . " WHERE ".$tabla."_id = ?";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute($valores);

    if ($resultado) {
        $_SESSION['mensaje'] = ucfirst($tabla) . " actualizado exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
    } else {
        throw new Exception("Error al actualizar el registro.");
    }

} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "danger";
}

header("Location: ../consultas/".strtolower($tabla).".php");
exit;
?>