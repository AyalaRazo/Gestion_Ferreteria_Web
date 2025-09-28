<?php
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../consultas/generales.php');
    exit;
}

session_start();
require_once '../includes/auth.php';

// Verificar permisos
if (!tienePermiso('editar_productos')) {
    $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/generales.php');
    exit;
}

try {
    // Obtener y validar datos
    $producto_id = intval($_POST['producto_id']);
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

    // Verificar que el producto existe
    $stmt = $pdo->prepare("SELECT producto_id FROM Producto WHERE producto_id = :id");
    $stmt->execute([':id' => $producto_id]);
    if (!$stmt->fetch()) {
        throw new Exception('El producto no existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Actualizar el producto
    $stmt = $pdo->prepare("
        UPDATE Producto 
        SET nombre = :nombre,
            precio = :precio,
            categoria = :categoria,
            activo = :activo
        WHERE producto_id = :id
    ");

    $stmt->execute([
        ':nombre' => $nombre,
        ':precio' => $precio,
        ':categoria' => $categoria,
        ':activo' => $activo,
        ':id' => $producto_id
    ]);

    // Registrar en el log de actividad
    $empleado_id = $_SESSION['empleado_id'];
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ACTUALIZACION_PRODUCTO', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':nombre' => "Actualización de producto: $nombre"
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto actualizado exitosamente.';
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
header('Location: producto.php?id=' . $producto_id);
exit; 