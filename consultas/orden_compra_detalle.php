<?php
session_start();
require_once '../includes/db.php';

$orden_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orden_id <= 0) {
    $_SESSION['mensaje'] = "ID de orden no válido.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ordenes_compra.php");
    exit;
}

try {
    // Obtener datos de la orden
    $stmt = $pdo->prepare("
        SELECT 
            oc.*,
            p.nombre as proveedor_nombre,
            p.telefono as proveedor_telefono,
            p.email as proveedor_email,
            e.nombre as empleado_nombre
        FROM orden_compra oc
        JOIN Proveedor p ON oc.proveedor_id = p.proveedor_id
        JOIN Empleado e ON oc.empleado_id = e.empleado_id
        WHERE oc.orden_compra_id = :id
    ");
    $stmt->execute([':id' => $orden_id]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        throw new Exception("Orden no encontrada.");
    }

    // Obtener detalles de la orden
    $stmt = $pdo->prepare("
        SELECT 
            doc.*,
            p.codigo_producto,
            p.nombre_producto
        FROM detalles_orden_compra doc
        JOIN Producto p ON doc.producto_id = p.producto_id
        WHERE doc.orden_compra_id = :id
        ORDER BY doc.orden_compra_id
    ");
    $stmt->execute([':id' => $orden_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: ordenes_compra.php");
    exit;
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE orden_compra 
            SET estado = :estado
            WHERE orden_compra_id = :id
        ");

        $resultado = $stmt->execute([
            ':estado' => $_POST['nuevo_estado'],
            ':id' => $orden_id
        ]);

        if ($resultado) {
            // Actualizar el estado de todos los detalles
            $stmt = $pdo->prepare("
                UPDATE detalles_orden_compra 
                SET estado = :estado
                WHERE orden_compra_id = :id
            ");
            $stmt->execute([
                ':estado' => $_POST['nuevo_estado'],
                ':id' => $orden_id
            ]);

            $_SESSION['mensaje'] = "Estado de la orden actualizado exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
            header("Location: orden_compra_detalle.php?id=" . $orden_id);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['mensaje_tipo'] = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Orden de Compra | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .order-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }
        .order-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-info h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        .order-info p {
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .estado-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-aprobada { background: #d1fae5; color: #065f46; }
        .estado-rechazada { background: #fee2e2; color: #991b1b; }
        .estado-completada { background: #e0e7ff; color: #3730a3; }
        .table-responsive {
            margin-top: 1rem;
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table th {
            background: #f8fafc;
            padding: 1rem;
            font-weight: 600;
            text-align: left;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
        }
        .table td {
            padding: 1rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .total-section {
            margin-top: 2rem;
            text-align: right;
            padding-top: 1rem;
            border-top: 2px solid #e5e7eb;
        }
        .total-amount {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
        }
        .estado-form {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .estado-form select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-right: 1rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-shopping-basket"></i> Detalles de Orden de Compra</h2>
                <a href="ordenes_compra.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?php echo $_SESSION['mensaje_tipo']; ?>">
                    <?php 
                    echo $_SESSION['mensaje'];
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="order-details">
                <div class="order-header">
                    <div class="order-info">
                        <h3>Información de la Orden</h3>
                        <p><strong>Orden #:</strong> <?php echo $orden['orden_compra_id']; ?></p>
                        <p><strong>Fecha:</strong> <?php echo date('d/m/Y', strtotime($orden['fecha_orden'])); ?></p>
                        <p><strong>Estado:</strong> 
                            <span class="estado-badge estado-<?php echo strtolower($orden['estado']); ?>">
                                <?php echo $orden['estado']; ?>
                            </span>
                        </p>
                    </div>
                    <div class="order-info">
                        <h3>Proveedor</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['proveedor_nombre']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($orden['proveedor_telefono']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($orden['proveedor_email']); ?></p>
                    </div>
                    <div class="order-info">
                        <h3>Empleado</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($orden['empleado_nombre']); ?></p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Precio Unitario</th>
                                <th>Total</th>
                                <th>Fecha Entrega</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detalle['codigo_producto']); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                                    <td><?php echo $detalle['cantidad']; ?></td>
                                    <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                    <td>$<?php echo number_format($detalle['total_particular'], 2); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($detalle['fecha_entrega'])); ?></td>
                                    <td>
                                        <span class="estado-badge estado-<?php echo strtolower($detalle['estado']); ?>">
                                            <?php echo $detalle['estado']; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="total-section">
                    <p class="total-amount">Total: $<?php echo number_format($orden['total'], 2); ?></p>
                </div>

                <?php if ($orden['estado'] === 'PENDIENTE'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="APROBADA">Aprobar Orden</option>
                                <option value="RECHAZADA">Rechazar Orden</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php elseif ($orden['estado'] === 'APROBADA'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="ENVIADA">Marcar como Enviada</option>
                                <option value="CANCELADA">Cancelar Orden</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php elseif ($orden['estado'] === 'ENVIADA'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="RECIBIDA">Marcar como Recibida</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 