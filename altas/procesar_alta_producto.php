<?php
session_start();
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: producto.php');
    exit;
}

try {
    // Obtener y validar datos
    $codigo_producto = trim($_POST['codigo_producto']);
    $nombre_producto = trim($_POST['nombre_producto']);
    $precio = floatval($_POST['precio']);
    $categoria = $_POST['categoria'] === 'nueva' ? trim($_POST['nuevaCategoria']) : trim($_POST['categoria']);
    $sucursal_id = intval($_POST['sucursal_id']);
    $stock_inicial = intval($_POST['stock_inicial']);
    $stock_minimo = intval($_POST['stock_minimo']);
    $stock_maximo = intval($_POST['stock_maximo']);

    // Validaciones
    if (empty($codigo_producto) || !preg_match('/^[A-Za-z0-9-]{3,20}$/', $codigo_producto)) {
        throw new Exception('El código del producto debe tener entre 3 y 20 caracteres alfanuméricos.');
    }

    if (empty($nombre_producto)) {
        throw new Exception('El nombre del producto es requerido.');
    }

    if ($precio <= 0) {
        throw new Exception('El precio debe ser mayor a 0.');
    }

    if (empty($categoria)) {
        throw new Exception('La categoría es requerida.');
    }

    if ($stock_maximo <= $stock_minimo) {
        throw new Exception('El stock máximo debe ser mayor al stock mínimo.');
    }

    if ($stock_inicial < 0) {
        throw new Exception('El stock inicial no puede ser negativo.');
    }

    if ($stock_inicial > $stock_maximo) {
        throw new Exception('El stock inicial no puede ser mayor al stock máximo.');
    }

    // Verificar que el código no esté duplicado
    $stmt = $pdo->prepare("SELECT producto_id FROM Producto WHERE codigo_producto = ?");
    $stmt->execute([$codigo_producto]);
    if ($stmt->fetch()) {
        throw new Exception('El código de producto ya existe.');
    }

    // Verificar que la sucursal exista
    $stmt = $pdo->prepare("SELECT sucursal_id FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if (!$stmt->fetch()) {
        throw new Exception('La sucursal seleccionada no existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Insertar el producto
    $stmt = $pdo->prepare("
        INSERT INTO Producto (
            codigo_producto, 
            nombre_producto, 
            precio, 
            categoria,
            activo
        ) VALUES (?, ?, ?, ?, TRUE)
    ");

    $stmt->execute([
        $codigo_producto,
        $nombre_producto,
        $precio,
        $categoria
    ]);

    $producto_id = $pdo->lastInsertId();

    // Registrar en inventario
    $stmt = $pdo->prepare("
        INSERT INTO Inventario (
            producto_id,
            sucursal_id,
            stock_actual,
            stock_minimo,
            stock_maximo,
            ultima_actualizacion
        ) VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $producto_id,
        $sucursal_id,
        $stock_inicial,
        $stock_minimo,
        $stock_maximo
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto registrado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // Redirigir al formulario de reportes
    $datos_reporte = urlencode(json_encode([
        'nombre' => $nombre_producto,
        'stock' => $stock_inicial
    ]));
    
    header("Location: producto.php");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    
    // Redireccionar de vuelta al formulario
    header('Location: producto.php');
    exit;
} 