<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    header('Location: ../altas/reporte.php');
    exit();
}

$reporte_id = intval($_GET['id']);

// Obtener información del reporte
$stmt = $pdo->prepare("SELECT * FROM Reporte WHERE reporte_id = ?");
$stmt->execute([$reporte_id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) {
    $_SESSION['mensaje'] = 'Reporte no encontrado.';
    $_SESSION['mensaje_tipo'] = 'error';
    header('Location: ../altas/reporte.php');
    exit();
}

// Obtener datos según el tipo de reporte
$datos = [];
switch($reporte['tipo']) {
    case 'listado_sucursales':
        $stmt = $pdo->query("
            SELECT 
                s.sucursal_id,
                s.nombre,
                s.direccion,
                s.telefono,
                s.estado,
                COUNT(DISTINCT v.venta_id) as total_ventas,
                COALESCE(SUM(v.total_venta), 0) as monto_total_ventas
            FROM Sucursal s
            LEFT JOIN Venta v ON s.sucursal_id = v.sucursal_id
            GROUP BY s.sucursal_id
            ORDER BY s.nombre
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'sucursales_ventas':
        $stmt = $pdo->query("
            SELECT 
                s.nombre as sucursal,
                COUNT(v.venta_id) as cantidad_ventas,
                COALESCE(SUM(v.total_venta), 0) as total_ventas,
                AVG(v.total_venta) as promedio_venta
            FROM Sucursal s
            LEFT JOIN Venta v ON s.sucursal_id = v.sucursal_id
            GROUP BY s.sucursal_id
            ORDER BY total_ventas DESC
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'inventario_general':
        $stmt = $pdo->query("
            SELECT 
                p.producto_id,
                p.codigo,
                p.nombre,
                p.descripcion,
                c.nombre as categoria,
                p.precio_venta,
                SUM(i.stock_actual) as stock_total,
                COUNT(DISTINCT i.sucursal_id) as num_sucursales
            FROM Producto p
            LEFT JOIN Inventario i ON p.producto_id = i.producto_id
            LEFT JOIN Categoria c ON p.categoria_id = c.categoria_id
            GROUP BY p.producto_id
            ORDER BY c.nombre, p.nombre
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'productos_stock_bajo':
        $stmt = $pdo->query("
            SELECT 
                p.producto_id,
                p.codigo,
                p.nombre,
                s.nombre as sucursal,
                i.stock_actual,
                i.stock_minimo
            FROM Producto p
            JOIN Inventario i ON p.producto_id = i.producto_id
            JOIN Sucursal s ON i.sucursal_id = s.sucursal_id
            WHERE i.stock_actual <= i.stock_minimo
            ORDER BY (i.stock_actual - i.stock_minimo)
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'productos_categoria':
        $stmt = $pdo->query("
            SELECT 
                c.nombre as categoria,
                COUNT(p.producto_id) as total_productos,
                COALESCE(SUM(i.stock_actual), 0) as stock_total,
                AVG(p.precio_venta) as precio_promedio
            FROM Categoria c
            LEFT JOIN Producto p ON c.categoria_id = p.categoria_id
            LEFT JOIN Inventario i ON p.producto_id = i.producto_id
            GROUP BY c.categoria_id
            ORDER BY c.nombre
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 'movimientos_inventario':
        $stmt = $pdo->query("
            SELECT 
                p.nombre as producto,
                s.nombre as sucursal,
                m.tipo_movimiento,
                m.cantidad,
                m.fecha_movimiento,
                m.motivo
            FROM Movimiento_inventario m
            JOIN Producto p ON m.producto_id = p.producto_id
            JOIN Sucursal s ON m.sucursal_id = s.sucursal_id
            ORDER BY m.fecha_movimiento DESC
            LIMIT 100
        ");
        $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        break;

    // ... otros casos para otros tipos de reportes ...
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Reporte | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="report-container">
                <h2><i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($reporte['nombre']); ?></h2>
                <p class="report-meta">
                    Generado el: <?php echo date('d/m/Y H:i', strtotime($reporte['fecha_generacion'])); ?>
                </p>

                <?php if ($reporte['tipo'] === 'listado_sucursales'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Dirección</th>
                                    <th>Teléfono</th>
                                    <th>Estado</th>
                                    <th>Total Ventas</th>
                                    <th>Monto Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $sucursal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sucursal['sucursal_id']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['telefono']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['estado']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['total_ventas']); ?></td>
                                        <td>$<?php echo number_format($sucursal['monto_total_ventas'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reporte['tipo'] === 'sucursales_ventas'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Sucursal</th>
                                    <th>Cantidad de Ventas</th>
                                    <th>Total Ventas</th>
                                    <th>Promedio por Venta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $sucursal): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($sucursal['sucursal']); ?></td>
                                        <td><?php echo htmlspecialchars($sucursal['cantidad_ventas']); ?></td>
                                        <td>$<?php echo number_format($sucursal['total_ventas'], 2); ?></td>
                                        <td>$<?php echo number_format($sucursal['promedio_venta'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reporte['tipo'] === 'inventario_general'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Categoría</th>
                                    <th>Precio Venta</th>
                                    <th>Stock Total</th>
                                    <th>Sucursales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $producto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['descripcion']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                        <td>$<?php echo number_format($producto['precio_venta'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($producto['stock_total']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['num_sucursales']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reporte['tipo'] === 'productos_stock_bajo'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Sucursal</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $producto): ?>
                                    <tr class="<?php echo $producto['stock_actual'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                        <td><?php echo htmlspecialchars($producto['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['sucursal']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['stock_actual']); ?></td>
                                        <td><?php echo htmlspecialchars($producto['stock_minimo']); ?></td>
                                        <td>
                                            <?php if ($producto['stock_actual'] == 0): ?>
                                                <span class="badge bg-danger">Sin Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Stock Bajo</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reporte['tipo'] === 'productos_categoria'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th>Total Productos</th>
                                    <th>Stock Total</th>
                                    <th>Precio Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $categoria): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($categoria['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($categoria['total_productos']); ?></td>
                                        <td><?php echo htmlspecialchars($categoria['stock_total']); ?></td>
                                        <td>$<?php echo number_format($categoria['precio_promedio'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($reporte['tipo'] === 'movimientos_inventario'): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Producto</th>
                                    <th>Sucursal</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($datos as $movimiento): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])); ?></td>
                                        <td><?php echo htmlspecialchars($movimiento['producto']); ?></td>
                                        <td><?php echo htmlspecialchars($movimiento['sucursal']); ?></td>
                                        <td>
                                            <?php if ($movimiento['tipo_movimiento'] === 'ENTRADA'): ?>
                                                <span class="badge bg-success">Entrada</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Salida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($movimiento['cantidad']); ?></td>
                                        <td><?php echo htmlspecialchars($movimiento['motivo']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <a href="../altas/reporte.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a Reportes
                    </a>
                    <button onclick="window.print()" class="btn-primary">
                        <i class="fas fa-print"></i> Imprimir Reporte
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 