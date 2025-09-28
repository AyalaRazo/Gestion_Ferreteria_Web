<?php
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../consultas/sucursales.php');
    exit;
}

session_start();
require_once '../includes/auth.php';

// Verificar permisos
if (!tienePermiso('eliminar_sucursales')) {
    $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit;
}

try {
    // Obtener y validar ID
    $sucursal_id = isset($_POST['sucursal_id']) ? intval($_POST['sucursal_id']) : 0;
    
    if (empty($sucursal_id)) {
        throw new Exception('ID de sucursal no válido.');
    }

    // Verificar que la sucursal exista
    $stmt = $pdo->prepare("SELECT nombre FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sucursal) {
        throw new Exception('La sucursal no existe.');
    }

    // Verificar dependencias
    // 1. Empleados
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Empleado WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar la sucursal porque tiene empleados asignados.');
    }

    // 2. Inventario
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Inventario WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar la sucursal porque tiene productos en inventario.');
    }

    // 3. Ventas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Venta WHERE id_sucursal = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if ($stmt->fetchColumn() > 0) {
        throw new Exception('No se puede eliminar la sucursal porque tiene ventas registradas.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Eliminar la sucursal
    $stmt = $pdo->prepare("DELETE FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);

    // Registrar en el log de actividad
    $empleado_id = $_SESSION['empleado_id'];
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ELIMINACION_SUCURSAL', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':nombre' => "Eliminación de sucursal: " . $sucursal['nombre']
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Sucursal eliminada exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: ../consultas/sucursales.php');
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    header("Location: sucursal.php?id=$sucursal_id");
}
exit; 