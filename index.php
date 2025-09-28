<?php
session_start();
require_once 'includes/db.php';

// Obtener estadísticas generales
try {
    // Productos con stock bajo
    $stmt = $pdo->query("
        SELECT p.nombre_producto, p.codigo_producto, i.stock_actual, i.stock_minimo, s.nombre as sucursal
        FROM Producto p
        JOIN Inventario i ON p.producto_id = i.producto_id
        JOIN Sucursal s ON i.sucursal_id = s.sucursal_id
        WHERE i.stock_actual <= i.stock_minimo
        ORDER BY i.stock_actual ASC
        LIMIT 5
    ");
    $productos_bajos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Últimas ventas
    $stmt = $pdo->query("
        SELECT v.venta_id, v.fecha_venta, v.total_venta, s.nombre as sucursal,
               COUNT(dv.producto_id) as num_productos
        FROM Venta v
        JOIN Sucursal s ON v.sucursal_id = s.sucursal_id
        JOIN Detalles_venta dv ON v.venta_id = dv.venta_id
        GROUP BY v.venta_id, v.fecha_venta, v.total_venta, s.nombre
        ORDER BY v.fecha_venta DESC
        LIMIT 5
    ");
    $ultimas_ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Órdenes de compra pendientes
    $stmt = $pdo->query("
        SELECT oc.orden_compra_id, oc.fecha_orden, p.nombre as proveedor, 
               oc.total, oc.estado
        FROM Orden_compra oc
        JOIN Proveedor p ON oc.proveedor_id = p.proveedor_id
        WHERE oc.estado = 'PENDIENTE'
        ORDER BY oc.fecha_orden DESC
        LIMIT 5
    ");
    $ordenes_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estadísticas generales
    $stats = $pdo->query("
        SELECT 
        (SELECT COUNT(*) FROM Producto WHERE activo = 1) as total_productos,
        (SELECT COUNT(*) FROM Sucursal) as total_sucursales,
        (SELECT COUNT(*) FROM Proveedor) as total_proveedores,
        (SELECT COUNT(*) FROM Venta WHERE DATE(fecha_venta) = CURDATE()) as ventas_hoy
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['mensaje'] = "Error al cargar el dashboard: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "danger";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Ferretería</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .dashboard-body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: #f5f7fb;
            min-height: 100vh;
        }
        .card {
            transition: transform 0.2s;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            border-left: 4px solid;
        }
        .stat-card.productos { border-left-color: #0d6efd; }
        .stat-card.sucursales { border-left-color: #198754; }
        .stat-card.proveedores { border-left-color: #dc3545; }
        .stat-card.ventas { border-left-color: #ffc107; }
        .alert-stock {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
        }
        .order-pending {
            background-color: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        .sale-success {
            background-color: #d1e7dd;
            border-left: 4px solid #198754;
        }
        .quick-action {
            text-decoration: none;
            color: inherit;
        }
        .quick-action:hover {
            color: inherit;
        }
        .quick-action .card {
            border-left: 4px solid #0d6efd;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid">
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['mensaje'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php 
                unset($_SESSION['mensaje']);
                unset($_SESSION['mensaje_tipo']);
                ?>
            <?php endif; ?>

            <h1 class="mb-4">Panel de Control</h1>

            <!-- Estadísticas Generales -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card productos">
                        <div class="card-body">
                            <h5 class="card-title">Productos Activos</h5>
                            <p class="card-text h2"><?= number_format($stats['total_productos']) ?></p>
                            <a href="consultas/productos.php" class="text-decoration-none">Ver productos →</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card sucursales">
                        <div class="card-body">
                            <h5 class="card-title">Sucursales</h5>
                            <p class="card-text h2"><?= number_format($stats['total_sucursales']) ?></p>
                            <a href="consultas/sucursales.php" class="text-decoration-none">Ver sucursales →</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card proveedores">
                        <div class="card-body">
                            <h5 class="card-title">Proveedores</h5>
                            <p class="card-text h2"><?= number_format($stats['total_proveedores']) ?></p>
                            <a href="consultas/proveedores.php" class="text-decoration-none">Ver proveedores →</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card ventas">
                        <div class="card-body">
                            <h5 class="card-title">Ventas Hoy</h5>
                            <p class="card-text h2"><?= number_format($stats['ventas_hoy']) ?></p>
                            <a href="consultas/ventas.php" class="text-decoration-none">Ver ventas →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">Acciones Rápidas</h4>
                </div>
                <div class="col-md-3">
                    <a href="altas/venta.php" class="quick-action">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-shopping-cart me-2"></i>Nueva Venta</h5>
                                <p class="card-text">Registrar una nueva venta</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="altas/producto.php" class="quick-action">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-box me-2"></i>Nuevo Producto</h5>
                                <p class="card-text">Agregar un nuevo producto</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="altas/orden_compra.php" class="quick-action">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-file-invoice me-2"></i>Nueva Orden</h5>
                                <p class="card-text">Crear orden de compra</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="altas/transferencia.php" class="quick-action">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-exchange-alt me-2"></i>Transferencia</h5>
                                <p class="card-text">Nueva transferencia</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Productos con Stock Bajo -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="card-title mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stock Bajo</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($productos_bajos)): ?>
                                <p class="text-center py-3 mb-0">No hay productos con stock bajo</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                <?php foreach ($productos_bajos as $producto): ?>
                                    <div class="list-group-item alert-stock">
                                        <h6 class="mb-1"><?= htmlspecialchars($producto['nombre_producto']) ?></h6>
                                        <p class="mb-1">Código: <?= htmlspecialchars($producto['codigo_producto']) ?></p>
                                        <small>
                                            Stock: <?= $producto['stock_actual'] ?> / <?= $producto['stock_minimo'] ?>
                                            <br>
                                            Sucursal: <?= htmlspecialchars($producto['sucursal']) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Órdenes Pendientes -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Órdenes Pendientes</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ordenes_pendientes)): ?>
                                <p class="text-center py-3 mb-0">No hay órdenes pendientes</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                <?php foreach ($ordenes_pendientes as $orden): ?>
                                    <div class="list-group-item order-pending">
                                        <h6 class="mb-1">Orden #<?= $orden['orden_compra_id'] ?></h6>
                                        <p class="mb-1">Proveedor: <?= htmlspecialchars($orden['proveedor']) ?></p>
                                        <small>
                                            Fecha: <?= date('d/m/Y', strtotime($orden['fecha_orden'])) ?>
                                            <br>
                                            Total: $<?= number_format($orden['total'], 2) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Últimas Ventas -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0"><i class="fas fa-check-circle me-2"></i>Últimas Ventas</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($ultimas_ventas)): ?>
                                <p class="text-center py-3 mb-0">No hay ventas registradas</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                <?php foreach ($ultimas_ventas as $venta): ?>
                                    <div class="list-group-item sale-success">
                                        <h6 class="mb-1">Venta #<?= $venta['venta_id'] ?></h6>
                                        <p class="mb-1">
                                            Total: $<?= number_format($venta['total_venta'], 2) ?>
                                            (<?= $venta['num_productos'] ?> productos)
                                        </p>
                                        <small>
                                            Fecha: <?= date('d/m/Y H:i', strtotime($venta['fecha_venta'])) ?>
                                            <br>
                                            Sucursal: <?= htmlspecialchars($venta['sucursal']) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>