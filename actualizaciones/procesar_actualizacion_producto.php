<?php
session_start();
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../consultas/generales.php');
    exit;
}

try {
    // Obtener y validar datos
    $producto_id = intval($_POST['producto_id']);
    $codigo = trim($_POST['codigo']);
    $nombre = trim($_POST['nombre']);
    $categoria = $_POST['categoria'] === 'nueva' ? trim($_POST['nueva_categoria']) : trim($_POST['categoria']);
    $proveedor_id = intval($_POST['proveedor_id']);
    $precio_compra = floatval($_POST['precio_compra']);
    $precio_venta = floatval($_POST['precio_venta']);
    $unidad_medida = trim($_POST['unidad_medida']);
    $descripcion = trim($_POST['descripcion']);

    // Validaciones
    if (empty($codigo) || !preg_match('/^[A-Za-z0-9-]{3,20}$/', $codigo)) {
        throw new Exception('El código debe tener entre 3 y 20 caracteres alfanuméricos.');
    }

    if (empty($nombre)) {
        throw new Exception('El nombre del producto es requerido.');
    }

    if (empty($categoria)) {
        throw new Exception('La categoría es requerida.');
    }

    if (empty($proveedor_id)) {
        throw new Exception('El proveedor es requerido.');
    }

    if ($precio_compra <= 0) {
        throw new Exception('El precio de compra debe ser mayor a 0.');
    }

    if ($precio_venta <= $precio_compra) {
        throw new Exception('El precio de venta debe ser mayor al precio de compra.');
    }

    if (empty($unidad_medida)) {
        throw new Exception('La unidad de medida es requerida.');
    }

    // Verificar que el código no esté duplicado (excluyendo el producto actual)
    $stmt = $pdo->prepare("
        SELECT producto_id 
        FROM Producto 
        WHERE codigo = :codigo AND producto_id != :producto_id
    ");
    $stmt->execute([
        ':codigo' => $codigo,
        ':producto_id' => $producto_id
    ]);
    if ($stmt->fetch()) {
        throw new Exception('El código de producto ya existe.');
    }

    // Verificar que el proveedor exista
    $stmt = $pdo->prepare("SELECT proveedor_id FROM Proveedor WHERE proveedor_id = :id");
    $stmt->execute([':id' => $proveedor_id]);
    if (!$stmt->fetch()) {
        throw new Exception('El proveedor seleccionado no existe.');
    }

    // Verificar que el producto exista
    $stmt = $pdo->prepare("SELECT producto_id FROM Producto WHERE producto_id = :id");
    $stmt->execute([':id' => $producto_id]);
    if (!$stmt->fetch()) {
        throw new Exception('El producto no existe.');
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    // Actualizar el producto
    $stmt = $pdo->prepare("
        UPDATE Producto SET
            codigo = :codigo,
            nombre = :nombre,
            categoria = :categoria,
            proveedor_id = :proveedor_id,
            precio_compra = :precio_compra,
            precio_venta = :precio_venta,
            unidad_medida = :unidad_medida,
            descripcion = :descripcion,
            fecha_actualizacion = NOW()
        WHERE producto_id = :producto_id
    ");

    $stmt->execute([
        ':codigo' => $codigo,
        ':nombre' => $nombre,
        ':categoria' => $categoria,
        ':proveedor_id' => $proveedor_id,
        ':precio_compra' => $precio_compra,
        ':precio_venta' => $precio_venta,
        ':unidad_medida' => $unidad_medida,
        ':descripcion' => $descripcion,
        ':producto_id' => $producto_id
    ]);

    // Registrar en el log de actividad
    $stmt = $pdo->prepare("
        INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion)
        VALUES (:empleado_id, :nombre, 'ACTUALIZACION_PRODUCTO', NOW())
    ");

    $stmt->execute([
        ':empleado_id' => $_SESSION['empleado_id'],
        ':nombre' => "Actualización de producto: $nombre"
    ]);

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Producto actualizado exitosamente.';
    $_SESSION['mensaje_tipo'] = 'success';
    
    // Redireccionar al detalle del producto
    header("Location: ../consultas/producto_detalle.php?id=$producto_id");
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    
    // Redireccionar de vuelta al formulario
    header("Location: producto.php?id=$producto_id");
    exit;
} 