<?php
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: producto.php');
    exit;
}

try {
    // Obtener y validar datos
    $nombre = trim($_POST['nombre']);
    $precio = floatval($_POST['precio']);
    $categoria = $_POST['categoria'] === 'nueva' ? trim($_POST['nuevaCategoria']) : trim($_POST['categoria']);
    $activo = isset($_POST['activo']) ? intval($_POST['activo']) : 1;

    // Validaciones
    if (empty($nombre)) {
        throw new Exception('El nombre del producto es requerido.');
    }

    if ($precio <= 0) {
        throw new Exception('El precio debe ser mayor a 0.');
    }

    if (empty($categoria)) {
        throw new Exception('La categoría es requerida.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Insertar el producto
    $stmt = $pdo->prepare("
        INSERT INTO Producto (nombre, precio, categoria, activo) 
        VALUES (:nombre, :precio, :categoria, :activo)
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':precio' => $precio,
        ':categoria' => $categoria,
        ':activo' => $activo
    ]);

    $producto_id = $pdo->lastInsertId();

    // Registrar en el log de actividad
    $empleado_id = $_SESSION['empleado_id'];
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ALTA_PRODUCTO', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':nombre' => "Alta de producto: $nombre"
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto registrado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

// Redireccionar de vuelta al formulario
header('Location: producto.php');
exit;
?>