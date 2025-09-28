<?php
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../consultas/productos.php');
    exit;
}

session_start();
require_once '../includes/auth.php';

// Verificar permisos
if (!tienePermiso('eliminar_productos')) {
    $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/generales.php');
    exit;
}

try {
    $producto_id = intval($_POST['producto_id']);

    // Verificar que el producto existe y obtener su información
    $stmt = $pdo->prepare("SELECT nombre FROM Producto WHERE producto_id = :id");
    $stmt->execute([':id' => $producto_id]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        throw new Exception('El producto no existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Verificar si hay ventas asociadas
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM Detalles_venta 
        WHERE producto_id = :id
    ");
    $stmt->execute([':id' => $producto_id]);
    $tiene_ventas = $stmt->fetchColumn() > 0;

    if ($tiene_ventas) {
        // Si hay ventas, solo marcar como inactivo
        $stmt = $pdo->prepare("
            UPDATE Producto 
            SET activo = 0 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);
        
        $mensaje_adicional = 'El producto tiene ventas asociadas, se ha marcado como inactivo.';
    } else {
        // Si no hay ventas, eliminar registros relacionados
        
        // Eliminar de producto_promocion
        $stmt = $pdo->prepare("
            DELETE FROM producto_promocion 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);

        // Eliminar de detalles_transferencia
        $stmt = $pdo->prepare("
            DELETE FROM detalles_transferencia 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);

        // Eliminar de detalles_orden_compra
        $stmt = $pdo->prepare("
            DELETE FROM detalles_orden_compra 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);

        // Eliminar de inventario
        $stmt = $pdo->prepare("
            DELETE FROM Inventario 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);

        // Finalmente, eliminar el producto
        $stmt = $pdo->prepare("
            DELETE FROM Producto 
            WHERE producto_id = :id
        ");
        $stmt->execute([':id' => $producto_id]);
        
        $mensaje_adicional = 'El producto ha sido eliminado permanentemente.';
    }

    // Registrar en el log de actividad
    $empleado_id = $_SESSION['empleado_id'];
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ELIMINACION_PRODUCTO', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':nombre' => "Eliminación de producto: " . $producto['nombre']
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto procesado exitosamente. ' . $mensaje_adicional;
    $_SESSION['mensaje_tipo'] = 'success';
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
}

// Redireccionar a la lista de productos
header('Location: ../consultas/generales.php');
exit; 