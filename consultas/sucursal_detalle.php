<?php
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: sucursales.php');
    exit;
}

$sucursal_id = intval($_GET['id']);

try {
    // Obtener información de la sucursal
    $stmt = $pdo->prepare("
        SELECT s.*,
               COUNT(DISTINCT e.empleado_id) as num_empleados,
               COUNT(DISTINCT i.producto_id) as num_productos,
               COALESCE(SUM(i.stock_actual), 0) as stock_total,
               (
                   SELECT COUNT(v.venta_id)
                   FROM Venta v
                   WHERE v.id_sucursal = s.sucursal_id
                   AND v.fecha_venta >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
               ) as ventas_ultimo_mes,
               (
                   SELECT COALESCE(SUM(v.total_venta), 0)
                   FROM Venta v
                   WHERE v.id_sucursal = s.sucursal_id
                   AND v.fecha_venta >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
               ) as ingresos_ultimo_mes
        FROM Sucursal s
        LEFT JOIN Empleado e ON s.sucursal_id = e.sucursal_id
        LEFT JOIN Inventario i ON s.sucursal_id = i.sucursal_id
        WHERE s.sucursal_id = :id
        GROUP BY s.sucursal_id
    ");
    $stmt->execute([':id' => $sucursal_id]);
    $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sucursal) {
        throw new Exception('Sucursal no encontrada');
    }

    // Obtener empleados de la sucursal
    $stmt = $pdo->prepare("
        SELECT e.*, s.rol
        FROM Empleado e
        LEFT JOIN Seguridad s ON e.empleado_id = s.empleado_id
        WHERE e.sucursal_id = :id
        ORDER BY e.apellido_paterno, e.apellido_materno, e.nombre
    ");
    $stmt->execute([':id' => $sucursal_id]);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener productos con bajo stock
    $stmt = $pdo->prepare("
        SELECT p.nombre, p.categoria, i.*
        FROM Inventario i
        JOIN Producto p ON i.producto_id = p.producto_id
        WHERE i.sucursal_id = :id
        AND i.stock_actual <= i.stock_minimo
        ORDER BY (i.stock_actual / i.stock_minimo)
    ");
    $stmt->execute([':id' => $sucursal_id]);
    $productos_bajo_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener ventas por mes (últimos 6 meses)
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(fecha_venta, '%Y-%m') as mes,
            COUNT(*) as total_ventas,
            SUM(total_venta) as total_ingresos
        FROM Venta
        WHERE id_sucursal = :id
        AND fecha_venta >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(fecha_venta, '%Y-%m')
        ORDER BY mes DESC
    ");
    $stmt->execute([':id' => $sucursal_id]);
    $ventas_mensuales = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: sucursales.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Sucursal | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .sucursal-details {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .detail-section {
            margin-bottom: 2rem;
        }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .detail-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
        }
        .detail-label {
            font-weight: 500;
            color: #666;
            margin-bottom: 0.5rem;
        }
        .detail-value {
            font-size: 1.1rem;
            color: #333;
        }
        .employee-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .stock-warning {
            color: #ef4444;
            font-weight: 500;
        }
        .chart-container {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="sucursal-details">
                <h2>
                    <i class="fas fa-store"></i> 
                    <?= htmlspecialchars($sucursal['nombre']) ?>
                    <span class="text-muted">(<?= htmlspecialchars($sucursal['codigo_sucursal']) ?>)</span>
                </h2>

                <div class="detail-section">
                    <h3>Información General</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Dirección</div>
                            <div class="detail-value"><?= htmlspecialchars($sucursal['direccion']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Ciudad</div>
                            <div class="detail-value"><?= htmlspecialchars($sucursal['ciudad']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Estado</div>
                            <div class="detail-value"><?= htmlspecialchars($sucursal['estado']) ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Teléfono</div>
                            <div class="detail-value"><?= htmlspecialchars($sucursal['telefono']) ?></div>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h3>Estadísticas</h3>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Total Empleados</div>
                            <div class="detail-value"><?= $sucursal['num_empleados'] ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Productos en Inventario</div>
                            <div class="detail-value"><?= $sucursal['num_productos'] ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Stock Total</div>
                            <div class="detail-value"><?= number_format($sucursal['stock_total']) ?> unidades</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Ventas Último Mes</div>
                            <div class="detail-value"><?= $sucursal['ventas_ultimo_mes'] ?> ventas</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Ingresos Último Mes</div>
                            <div class="detail-value">$<?= number_format($sucursal['ingresos_ultimo_mes'], 2) ?></div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($productos_bajo_stock)): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-exclamation-triangle"></i> Productos con Bajo Stock</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Producto</th>
                                        <th>Categoría</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                        <th>Stock Máximo</th>
                                        <th>Última Actualización</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($productos_bajo_stock as $producto): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                            <td><?= htmlspecialchars($producto['categoria']) ?></td>
                                            <td class="stock-warning"><?= $producto['stock_actual'] ?></td>
                                            <td><?= $producto['stock_minimo'] ?></td>
                                            <td><?= $producto['stock_maximo'] ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($producto['ultima_actualizacion'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="detail-section">
                    <h3>Empleados</h3>
                    <div class="detail-grid">
                        <?php foreach ($empleados as $empleado): ?>
                            <div class="employee-card">
                                <h4>
                                    <?= htmlspecialchars($empleado['nombre']) ?> 
                                    <?= htmlspecialchars($empleado['apellido_paterno']) ?> 
                                    <?= htmlspecialchars($empleado['apellido_materno']) ?>
                                </h4>
                                <div class="detail-label">Rol</div>
                                <div class="detail-value"><?= htmlspecialchars($empleado['rol'] ?? 'No asignado') ?></div>
                                <div class="detail-label">Contacto</div>
                                <div class="detail-value">
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($empleado['telefono']) ?><br>
                                    <i class="fas fa-envelope"></i> <?= htmlspecialchars($empleado['correo']) ?>
                                </div>
                                <div class="detail-label">Fecha Contratación</div>
                                <div class="detail-value">
                                    <?= date('d/m/Y', strtotime($empleado['fecha_contratacion'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($ventas_mensuales)): ?>
                    <div class="detail-section">
                        <h3>Historial de Ventas</h3>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mes</th>
                                        <th>Total Ventas</th>
                                        <th>Ingresos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ventas_mensuales as $venta): ?>
                                        <tr>
                                            <td><?= date('F Y', strtotime($venta['mes'] . '-01')) ?></td>
                                            <td><?= number_format($venta['total_ventas']) ?></td>
                                            <td>$<?= number_format($venta['total_ingresos'], 2) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="../actualizaciones/sucursal.php?id=<?= $sucursal['sucursal_id'] ?>" class="btn-primary">
                        <i class="fas fa-edit"></i> Editar Sucursal
                    </a>
                    <a href="sucursales.php" class="btn btn-secondary" style="margin-left: 1rem;">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 