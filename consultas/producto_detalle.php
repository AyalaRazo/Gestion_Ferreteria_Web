<?php
session_start();
require_once '../includes/db.php';

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$producto_id) {
    header('Location: generales.php');
    exit;
}

// Obtener información del producto
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        pr.nombre as proveedor_nombre,
        pr.telefono as proveedor_telefono,
        pr.email as proveedor_email
    FROM Producto p
    LEFT JOIN Proveedor pr ON p.proveedor_id = pr.proveedor_id
    WHERE p.producto_id = :id
");
$stmt->execute([':id' => $producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: generales.php');
    exit;
}

// Obtener inventario por sucursal
$stmt = $pdo->prepare("
    SELECT 
        i.*,
        s.nombre as sucursal_nombre,
        s.direccion as sucursal_direccion
    FROM Inventario i
    JOIN Sucursal s ON i.sucursal_id = s.sucursal_id
    WHERE i.producto_id = :id
    ORDER BY s.nombre
");
$stmt->execute([':id' => $producto_id]);
$inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de movimientos
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        s.nombre as sucursal_nombre,
        e.nombre as empleado_nombre
    FROM Movimiento m
    JOIN Sucursal s ON m.sucursal_id = s.sucursal_id
    JOIN Empleado e ON m.empleado_id = e.empleado_id
    WHERE m.producto_id = :id
    ORDER BY m.fecha DESC
    LIMIT 10
");
$stmt->execute([':id' => $producto_id]);
$movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Producto | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .product-title {
            font-size: 1.5rem;
            color: #1f2937;
        }
        .product-code {
            color: #6b7280;
            font-size: 1rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .info-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-card h3 {
            margin: 0 0 1rem 0;
            color: #374151;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #6b7280;
        }
        .info-value {
            color: #1f2937;
            font-weight: 500;
        }
        .stock-warning {
            color: #ef4444;
        }
        .stock-ok {
            color: #10b981;
        }
        .movement-type {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.875rem;
        }
        .movement-entrada {
            background: #dcfce7;
            color: #166534;
        }
        .movement-salida {
            background: #fee2e2;
            color: #991b1b;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #374151;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="product-header">
                <div>
                    <h2 class="product-title"><?= htmlspecialchars($producto['nombre']) ?></h2>
                    <div class="product-code">Código: <?= htmlspecialchars($producto['codigo']) ?></div>
                </div>
                <div>
                    <a href="../actualizaciones/producto.php?id=<?= $producto_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Producto
                    </a>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Información General</h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Categoría</span>
                            <span class="info-value"><?= htmlspecialchars($producto['categoria']) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Unidad de Medida</span>
                            <span class="info-value"><?= htmlspecialchars($producto['unidad_medida']) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Precio de Compra</span>
                            <span class="info-value">$<?= number_format($producto['precio_compra'], 2) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Precio de Venta</span>
                            <span class="info-value">$<?= number_format($producto['precio_venta'], 2) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Margen de Ganancia</span>
                            <span class="info-value">
                                <?= number_format(
                                    (($producto['precio_venta'] - $producto['precio_compra']) / $producto['precio_compra']) * 100, 
                                    1
                                ) ?>%
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-truck"></i> Información del Proveedor</h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Nombre</span>
                            <span class="info-value"><?= htmlspecialchars($producto['proveedor_nombre']) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"><?= htmlspecialchars($producto['proveedor_telefono']) ?></span>
                        </li>
                        <li>
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($producto['proveedor_email']) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-boxes"></i> Inventario por Sucursal</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th>Dirección</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Stock Máximo</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventario as $inv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inv['sucursal_nombre']) ?></td>
                                    <td><?= htmlspecialchars($inv['sucursal_direccion']) ?></td>
                                    <td class="<?= $inv['stock_actual'] < $inv['stock_minimo'] ? 'stock-warning' : 'stock-ok' ?>">
                                        <?= number_format($inv['stock_actual']) ?>
                                    </td>
                                    <td><?= number_format($inv['stock_minimo']) ?></td>
                                    <td><?= number_format($inv['stock_maximo']) ?></td>
                                    <td>
                                        <?php if ($inv['stock_actual'] < $inv['stock_minimo']): ?>
                                            <span class="stock-warning">
                                                <i class="fas fa-exclamation-triangle"></i> Bajo stock
                                            </span>
                                        <?php elseif ($inv['stock_actual'] > $inv['stock_maximo']): ?>
                                            <span class="stock-warning">
                                                <i class="fas fa-exclamation-circle"></i> Sobre stock
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-ok">
                                                <i class="fas fa-check-circle"></i> Normal
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($inventario)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay registros de inventario</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="info-card">
                <h3><i class="fas fa-history"></i> Últimos Movimientos</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Cantidad</th>
                                <th>Sucursal</th>
                                <th>Empleado</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?></td>
                                    <td>
                                        <span class="movement-type movement-<?= strtolower($mov['tipo']) ?>">
                                            <?= $mov['tipo'] ?>
                                        </span>
                                    </td>
                                    <td><?= number_format($mov['cantidad']) ?></td>
                                    <td><?= htmlspecialchars($mov['sucursal_nombre']) ?></td>
                                    <td><?= htmlspecialchars($mov['empleado_nombre']) ?></td>
                                    <td><?= htmlspecialchars($mov['observaciones']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($movimientos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No hay movimientos registrados</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 