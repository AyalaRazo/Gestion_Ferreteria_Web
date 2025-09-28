<?php
session_start();
require_once '../includes/db.php';

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: venta.php');
    exit;
}

try {
    // Obtener y validar datos
    $sucursal_id = intval($_POST['sucursal_id']);
    $metodo_pago = trim($_POST['metodo_pago']);
    $total_venta = floatval($_POST['total_venta']);
    $productos = json_decode($_POST['productos'], true);

    // Validaciones básicas
    if (empty($sucursal_id)) {
        throw new Exception('La sucursal es requerida.');
    }

    if (empty($metodo_pago)) {
        throw new Exception('El método de pago es requerido.');
    }

    if (empty($productos)) {
        throw new Exception('Debe agregar al menos un producto.');
    }

    if ($total_venta <= 0) {
        throw new Exception('El total de la venta debe ser mayor a 0.');
    }

    // Verificar que la sucursal exista
    $stmt = $pdo->prepare("SELECT sucursal_id FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    if (!$stmt->fetch()) {
        throw new Exception('La sucursal seleccionada no existe.');
    }

    // VALIDAR STOCK DISPONIBLE ANTES DE PROCESAR LA VENTA
    foreach ($productos as $producto) {
        $stmt = $pdo->prepare("
            SELECT i.stock_actual, p.nombre 
            FROM Inventario i
            INNER JOIN Producto p ON i.producto_id = p.producto_id
            WHERE i.producto_id = :producto_id AND i.sucursal_id = :sucursal_id
        ");
        $stmt->execute([
            ':producto_id' => $producto['id'],
            ':sucursal_id' => $sucursal_id
        ]);
        
        $inventario = $stmt->fetch();
        if (!$inventario) {
            throw new Exception("No hay inventario registrado para el producto ID: " . $producto['id'] . " en esta sucursal.");
        }
        
        // VALIDAR QUE EL PRODUCTO TENGA STOCK DISPONIBLE (> 0)
        if ($inventario['stock_actual'] <= 0) {
            throw new Exception("El producto '" . $inventario['nombre'] . "' no tiene stock disponible (Stock: " . $inventario['stock_actual'] . ").");
        }
        
        // VALIDAR QUE HAY SUFICIENTE STOCK PARA LA CANTIDAD SOLICITADA
        if ($inventario['stock_actual'] < $producto['cantidad']) {
            throw new Exception("Stock insuficiente para el producto '" . $inventario['nombre'] . "'. Stock disponible: " . $inventario['stock_actual'] . ", solicitado: " . $producto['cantidad']);
        }
        
        // VALIDAR QUE LA CANTIDAD A VENDER SEA POSITIVA
        if ($producto['cantidad'] <= 0) {
            throw new Exception("La cantidad a vender debe ser mayor a 0 para el producto '" . $inventario['nombre'] . "'.");
        }
    }

    $pdo->beginTransaction();
    
    // Insertar venta
    $sql = "INSERT INTO Venta (fecha_venta, empleado_id, sucursal_id, total_venta, estado) 
            VALUES (NOW(), 1, :sucursal_id, :total_venta, 'COMPLETADA')";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':sucursal_id' => $sucursal_id,
        ':total_venta' => $total_venta
    ]);

    $venta_id = $pdo->lastInsertId();

    // Registrar el pago
    $stmt = $pdo->prepare("INSERT INTO Pago (venta_id, referencia, metodo_pago, monto_pago, fecha_pago) 
                          VALUES (:venta_id, :referencia, :metodo_pago, :monto_pago, NOW())");
    $stmt->execute([
        ':venta_id' => $venta_id,
        ':referencia' => 'VENTA-' . $venta_id,
        ':metodo_pago' => $metodo_pago,
        ':monto_pago' => $total_venta
    ]);

    // Procesar cada producto
    foreach ($productos as $producto) {
        // Insertar detalle de venta
        $stmt = $pdo->prepare("
            INSERT INTO Detalles_venta (
                venta_id, producto_id, cantidad, 
                precio_unitario, descuento_total
            ) VALUES (
                :venta_id, :producto_id, :cantidad,
                :precio_unitario, :descuento_total
            )
        ");

        $stmt->execute([
            ':venta_id' => $venta_id,
            ':producto_id' => $producto['id'],
            ':cantidad' => $producto['cantidad'],
            ':precio_unitario' => $producto['precio'],
            ':descuento_total' => 0
        ]);
    }

    // ACTUALIZAR INVENTARIO - DESCONTAR STOCK
    $stmt = $pdo->prepare("
        UPDATE Inventario 
        SET stock_actual = stock_actual - :cantidad,
            ultima_actualizacion = NOW()
        WHERE producto_id = :producto_id AND sucursal_id = :sucursal_id
    ");
    $stmt->execute([
        ':cantidad' => $producto['cantidad'],
        ':producto_id' => $producto['id'],
        ':sucursal_id' => $sucursal_id
    ]);
    
    // Verificar que la actualización fue exitosa
    if ($stmt->rowCount() == 0) {
        throw new Exception("Error al actualizar el inventario para el producto ID: " . $producto['id']);
    }

    // Confirmar transacción
    $pdo->commit();

    $_SESSION['mensaje'] = 'Venta registrada exitosamente. Venta #' . $venta_id;
    $_SESSION['mensaje_tipo'] = 'success';
    
    // Redireccionar de vuelta a la página de registro de venta
    header('Location: venta.php');
    exit;
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    
    // Redireccionar de vuelta al formulario
    header('Location: venta.php');
    exit;
} 