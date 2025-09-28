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
if (!tienePermiso('editar_sucursales')) {
    $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit;
}

try {
    // Obtener y validar datos
    $sucursal_id = isset($_POST['sucursal_id']) ? intval($_POST['sucursal_id']) : 0;
    $codigo_sucursal = trim($_POST['codigo_sucursal']);
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $ciudad = trim($_POST['ciudad']);
    $estado = trim($_POST['estado']);

    // Validaciones
    if (empty($sucursal_id)) {
        throw new Exception('ID de sucursal no válido.');
    }

    if (empty($codigo_sucursal) || !preg_match('/^[A-Za-z0-9]{3,10}$/', $codigo_sucursal)) {
        throw new Exception('El código de sucursal debe tener entre 3 y 10 caracteres alfanuméricos.');
    }

    if (empty($nombre)) {
        throw new Exception('El nombre de la sucursal es requerido.');
    }

    if (empty($direccion)) {
        throw new Exception('La dirección es requerida.');
    }

    if (empty($telefono) || !preg_match('/^[0-9]{10}$/', $telefono)) {
        throw new Exception('El teléfono debe contener exactamente 10 dígitos.');
    }

    if (empty($ciudad)) {
        throw new Exception('La ciudad es requerida.');
    }

    if (empty($estado)) {
        throw new Exception('El estado es requerido.');
    }

    // Verificar que el código no esté duplicado (excluyendo la sucursal actual)
    $stmt = $pdo->prepare("
        SELECT sucursal_id 
        FROM Sucursal 
        WHERE codigo_sucursal = :codigo 
        AND sucursal_id != :id
    ");
    $stmt->execute([
        ':codigo' => $codigo_sucursal,
        ':id' => $sucursal_id
    ]);
    if ($stmt->fetch()) {
        throw new Exception('El código de sucursal ya existe.');
    }

    // Verificar que la sucursal exista
    $stmt = $pdo->prepare("SELECT sucursal_id FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if (!$stmt->fetch()) {
        throw new Exception('La sucursal no existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Actualizar la sucursal
    $stmt = $pdo->prepare("
        UPDATE Sucursal 
        SET codigo_sucursal = :codigo,
            nombre = :nombre,
            direccion = :direccion,
            telefono = :telefono,
            ciudad = :ciudad,
            estado = :estado
        WHERE sucursal_id = :id
    ");

    $stmt->execute([
        ':codigo' => $codigo_sucursal,
        ':nombre' => $nombre,
        ':direccion' => $direccion,
        ':telefono' => $telefono,
        ':ciudad' => $ciudad,
        ':estado' => $estado,
        ':id' => $sucursal_id
    ]);

    // Registrar en el log de actividad
    $empleado_id = $_SESSION['empleado_id'];
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ACTUALIZACION_SUCURSAL', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $empleado_id,
        ':nombre' => "Actualización de sucursal: $nombre"
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Sucursal actualizada exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    header("Location: ../consultas/sucursal_detalle.php?id=$sucursal_id");
    
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