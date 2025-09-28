<?php
// Mostrar todos los errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php';

// Verificar conexión a la base de datos
try {
    $pdo->query("SELECT 1");
    echo "Conexión exitosa a la base de datos<br>";
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Método no permitido.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: sucursal.php');
    exit;
}

try {
    // Obtener y validar datos
    $codigo_sucursal = trim($_POST['codigo_sucursal']);
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $ciudad = trim($_POST['ciudad']);
    $estado = trim($_POST['estado']);

    // Validaciones
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

    // Verificar que el código de sucursal no esté duplicado
    $stmt = $pdo->prepare("SELECT sucursal_id FROM sucursal WHERE codigo_sucursal = :codigo_sucursal");
    $stmt->execute([':codigo_sucursal' => $codigo_sucursal]);
    if ($stmt->fetch()) {
        throw new Exception('El código de sucursal ya existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Insertar la sucursal
    $sql = "
        INSERT INTO sucursal (
            codigo_sucursal, 
            nombre, 
            direccion, 
            telefono, 
            ciudad, 
            estado
        ) VALUES (
            :codigo_sucursal, 
            :nombre, 
            :direccion, 
            :telefono, 
            :ciudad, 
            :estado
        )
    ";
    
    $stmt = $pdo->prepare($sql);
    
    $params = [
        ':codigo_sucursal' => $codigo_sucursal,
        ':nombre' => $nombre,
        ':direccion' => $direccion,
        ':telefono' => $telefono,
        ':ciudad' => $ciudad,
        ':estado' => $estado
    ];
    
    $stmt->execute($params);
    
    $sucursal_id = $pdo->lastInsertId();

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Sucursal registrada exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    header('Location: sucursal.php');
    exit;
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: sucursal.php');
    exit;
} 