<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de venta no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/ventas.php');
    exit();
}

$venta_id = intval($_GET['id']);

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $estado = trim($_POST['estado']);
        $fecha_entrega = !empty($_POST['fecha_entrega']) ? trim($_POST['fecha_entrega']) : null;

        if (empty($estado)) {
            throw new Exception('El estado es obligatorio.');
        }

        // Actualizar la venta
        $stmt = $pdo->prepare("
            UPDATE Venta 
            SET estado = ?,
                fecha_entrega = ?
            WHERE venta_id = ?
        ");

        $stmt->execute([
            $estado,
            $fecha_entrega,
            $venta_id
        ]);

        $_SESSION['mensaje'] = 'Venta actualizada exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: ../consultas/ventas.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

// Obtener datos de la venta
$stmt = $pdo->prepare("
    SELECT v.*, 
           s.nombre as sucursal_nombre,
           e.nombre as empleado_nombre,
           c.nombre as cliente_nombre
    FROM Venta v
    LEFT JOIN Sucursal s ON v.sucursal_id = s.sucursal_id
    LEFT JOIN Empleado e ON v.empleado_id = e.empleado_id
    LEFT JOIN Cliente c ON v.cliente_id = c.cliente_id
    WHERE v.venta_id = ?
");
$stmt->execute([$venta_id]);
$venta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
    $_SESSION['mensaje'] = 'Venta no encontrada.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/ventas.php');
    exit();
}

// Obtener detalles de la venta
$stmt = $pdo->prepare("
    SELECT dv.*, p.nombre_producto, p.codigo_producto
    FROM Detalle_venta dv
    JOIN Producto p ON dv.producto_id = p.producto_id
    WHERE dv.venta_id = ?
");
$stmt->execute([$venta_id]);
$detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estados válidos para ventas
$estados_venta = ['PENDIENTE', 'EN_PROCESO', 'COMPLETADA', 'CANCELADA'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Venta | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 2rem auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.15s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .btn-primary,
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .required-field::after {
            content: ' *';
            color: #ef4444;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header-actions h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-section {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .info-item {
            display: flex;
            flex-direction: column;
        }
        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        .info-value {
            font-weight: 500;
            color: #1f2937;
        }
        .products-section {
            margin-top: 2rem;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .products-table th,
        .products-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .products-table th {
            background: #f8fafc;
            font-weight: 500;
            color: #4b5563;
        }
        .products-table tr:hover {
            background: #f8fafc;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        .status-en-proceso {
            background: #e0f2fe;
            color: #075985;
        }
        .status-completada {
            background: #dcfce7;
            color: #166534;
        }
        .status-cancelada {
            background: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-edit"></i> Modificar Venta</h2>
                <a href="../consultas/ventas.php" class="btn-secondary">
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

            <div class="form-container">
                <div class="info-section">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Sucursal</span>
                            <span class="info-value"><?php echo htmlspecialchars($venta['sucursal_nombre']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Empleado</span>
                            <span class="info-value"><?php echo htmlspecialchars($venta['empleado_nombre']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Cliente</span>
                            <span class="info-value"><?php echo htmlspecialchars($venta['cliente_nombre']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Fecha de Venta</span>
                            <span class="info-value"><?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Total</span>
                            <span class="info-value">$<?php echo number_format($venta['total_venta'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="products-section">
                    <h3>Productos</h3>
                    <div class="table-responsive">
                        <table class="products-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unit.</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $detalle): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($detalle['codigo_producto']); ?></td>
                                        <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                                        <td><?php echo number_format($detalle['cantidad']); ?></td>
                                        <td>$<?php echo number_format($detalle['precio_unitario'], 2); ?></td>
                                        <td>$<?php echo number_format($detalle['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="estado" class="required-field">Estado</label>
                            <select name="estado" id="estado" required>
                                <?php foreach ($estados_venta as $estado): ?>
                                    <option value="<?php echo $estado; ?>" 
                                            <?php echo $venta['estado'] === $estado ? 'selected' : ''; ?>>
                                        <?php echo str_replace('_', ' ', $estado); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="fecha_entrega">Fecha de Entrega</label>
                            <input type="date" id="fecha_entrega" name="fecha_entrega" 
                                   value="<?php echo $venta['fecha_entrega'] ? date('Y-m-d', strtotime($venta['fecha_entrega'])) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/ventas.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 