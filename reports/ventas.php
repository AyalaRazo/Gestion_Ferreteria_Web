<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

try {
    require_once '../includes/db.php';
} catch (Exception $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Obtener parámetros de filtro
$fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
$fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
$sucursal_id = isset($_GET['sucursal_id']) ? intval($_GET['sucursal_id']) : 0;

try {
    // Obtener sucursales para el filtro
    $stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
    $sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener sucursales: " . $e->getMessage());
}

// Construir la consulta base
$where_clauses = ["v.fecha_venta BETWEEN :fecha_inicio AND :fecha_fin"];
$params = [
    ':fecha_inicio' => $fecha_inicio,
    ':fecha_fin' => $fecha_fin
];

if ($sucursal_id > 0) {
    $where_clauses[] = "v.sucursal_id = :sucursal_id";
    $params[':sucursal_id'] = $sucursal_id;
}

$where_sql = implode(" AND ", $where_clauses);

// Obtener resumen de ventas
$sql = "
    SELECT 
        DATE(v.fecha_venta) as fecha,
        s.nombre as sucursal,
        COUNT(v.venta_id) as total_ventas,
        SUM(v.total_venta) as total_ingresos,
        p.metodo_pago,
        COUNT(DISTINCT v.sucursal_id) as num_sucursales,
        v.estado
    FROM Venta v
    JOIN Sucursal s ON v.sucursal_id = s.sucursal_id
    JOIN Pago p ON v.venta_id = p.venta_id
    WHERE $where_sql AND v.estado = 'COMPLETADA'
    GROUP BY DATE(v.fecha_venta), s.nombre, p.metodo_pago, v.estado
    ORDER BY v.fecha_venta DESC, s.nombre
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcular totales
$total_ventas = 0;
$total_ingresos = 0;
$ventas_por_tipo = [];
$ventas_por_sucursal = [];

foreach ($ventas as $venta) {
    $total_ventas += $venta['total_ventas'];
    $total_ingresos += $venta['total_ingresos'];
    
    // Acumular por tipo de pago
    if (!isset($ventas_por_tipo[$venta['metodo_pago']])) {
        $ventas_por_tipo[$venta['metodo_pago']] = [
            'total_ventas' => 0,
            'total_ingresos' => 0
        ];
    }
    $ventas_por_tipo[$venta['metodo_pago']]['total_ventas'] += $venta['total_ventas'];
    $ventas_por_tipo[$venta['metodo_pago']]['total_ingresos'] += $venta['total_ingresos'];
    
    // Acumular por sucursal
    if (!isset($ventas_por_sucursal[$venta['sucursal']])) {
        $ventas_por_sucursal[$venta['sucursal']] = [
            'total_ventas' => 0,
            'total_ingresos' => 0
        ];
    }
    $ventas_por_sucursal[$venta['sucursal']]['total_ventas'] += $venta['total_ventas'];
    $ventas_por_sucursal[$venta['sucursal']]['total_ingresos'] += $venta['total_ingresos'];
}

// Obtener productos más vendidos
$sql = "
    SELECT 
        p.codigo_producto,
        p.nombre_producto as producto,
        p.categoria,
        SUM(d.cantidad) as total_vendido,
        SUM(d.total_particular) as total_ingresos
    FROM Venta v
    JOIN Detalles_venta d ON v.venta_id = d.venta_id
    JOIN Producto p ON d.producto_id = p.producto_id
    WHERE $where_sql AND v.estado = 'COMPLETADA'
    GROUP BY p.producto_id, p.codigo_producto, p.nombre_producto, p.categoria
    ORDER BY total_vendido DESC
    LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos_top = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Ventas | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .report-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-title {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
        }
        .chart-container {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-container {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
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
        .filter-group {
            display: inline-block;
            margin-right: 1rem;
            margin-bottom: 1rem;
        }
        .form-control {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="report-container">
            <h2><i class="fas fa-chart-bar"></i> Reporte de Ventas</h2>

            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label>Fecha Inicio:</label>
                        <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label>Fecha Fin:</label>
                        <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label>Sucursal:</label>
                        <select name="sucursal_id" class="form-control">
                            <option value="0">Todas las sucursales</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?= $sucursal['sucursal_id'] ?>" 
                                        <?= $sucursal_id == $sucursal['sucursal_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sucursal['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total Ventas</div>
                    <div class="stat-value"><?= number_format($total_ventas) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Total Ingresos</div>
                    <div class="stat-value">$<?= number_format($total_ingresos, 2) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Promedio por Venta</div>
                    <div class="stat-value">$<?= $total_ventas > 0 ? number_format($total_ingresos / $total_ventas, 2) : '0.00' ?></div>
                </div>
            </div>

            <div class="chart-container">
                <h3>Ventas por Tipo de Pago</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo de Pago</th>
                            <th>Total Ventas</th>
                            <th>Total Ingresos</th>
                            <th>Promedio por Venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas_por_tipo as $tipo => $datos): ?>
                            <tr>
                                <td><?= htmlspecialchars($tipo) ?></td>
                                <td><?= number_format($datos['total_ventas']) ?></td>
                                <td>$<?= number_format($datos['total_ingresos'], 2) ?></td>
                                <td>$<?= number_format($datos['total_ingresos'] / $datos['total_ventas'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="chart-container">
                <h3>Ventas por Sucursal</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Sucursal</th>
                            <th>Total Ventas</th>
                            <th>Total Ingresos</th>
                            <th>Promedio por Venta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas_por_sucursal as $sucursal => $datos): ?>
                            <tr>
                                <td><?= htmlspecialchars($sucursal) ?></td>
                                <td><?= number_format($datos['total_ventas']) ?></td>
                                <td>$<?= number_format($datos['total_ingresos'], 2) ?></td>
                                <td>$<?= number_format($datos['total_ingresos'] / $datos['total_ventas'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <h3>Productos Más Vendidos</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Cantidad Vendida</th>
                            <th>Total Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos_top as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['codigo_producto']) ?></td>
                                <td><?= htmlspecialchars($producto['producto']) ?></td>
                                <td><?= htmlspecialchars($producto['categoria']) ?></td>
                                <td><?= number_format($producto['total_vendido']) ?></td>
                                <td>$<?= number_format($producto['total_ingresos'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 