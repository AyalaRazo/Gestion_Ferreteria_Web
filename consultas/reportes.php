<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtrado
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($busqueda)) {
    $where_clauses[] = "(r.nombre LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($tipo)) {
    $where_clauses[] = "r.tipo = :tipo";
    $params[':tipo'] = $tipo;
}

if (!empty($fecha_desde)) {
    $where_clauses[] = "DATE(r.fecha_generacion) >= :fecha_desde";
    $params[':fecha_desde'] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $where_clauses[] = "DATE(r.fecha_generacion) <= :fecha_hasta";
    $params[':fecha_hasta'] = $fecha_hasta;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener reportes
$sql = "
    SELECT 
        r.*,
        e.nombre as empleado_nombre
    FROM Reporte r
    LEFT JOIN Empleado e ON r.empleado_id = e.empleado_id
    $where_sql
    ORDER BY r.fecha_generacion DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener tipos únicos de reportes para el filtro
$stmt = $pdo->query("SELECT DISTINCT tipo FROM Reporte ORDER BY tipo");
$tipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .filters {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .filters form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filter-group label {
            font-weight: 500;
            color: #374151;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-top: 1rem;
            overflow: auto;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table thead {
            background: #f8fafc;
        }
        .table th {
            padding: 1rem;
            font-weight: 600;
            text-align: left;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            white-space: nowrap;
        }
        .table td {
            padding: 1rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .tipo-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .tipo-ventas { background: #dbeafe; color: #1e40af; }
        .tipo-inventario { background: #d1fae5; color: #065f46; }
        .tipo-productos { background: #fef3c7; color: #92400e; }
        .tipo-otros { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-file-alt"></i> Reportes</h2>
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

            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Nombre del reporte...">
                    </div>

                    <div class="filter-group">
                        <label for="tipo">Tipo:</label>
                        <select id="tipo" name="tipo">
                            <option value="">Todos</option>
                            <?php foreach ($tipos as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>" 
                                        <?php echo $tipo === $t ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($t); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="fecha_desde">Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" 
                               value="<?php echo htmlspecialchars($fecha_desde); ?>">
                    </div>

                    <div class="filter-group">
                        <label for="fecha_hasta">Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" 
                               value="<?php echo htmlspecialchars($fecha_hasta); ?>">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="reportes.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Tipo</th>
                            <th>Fecha Generación</th>
                            <th>Generado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportes as $reporte): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($reporte['reporte_id']); ?></td>
                                <td><?php echo htmlspecialchars($reporte['nombre']); ?></td>
                                <td>
                                    <?php 
                                    $tipo_class = 'tipo-otros';
                                    if (strpos(strtolower($reporte['tipo']), 'venta') !== false) {
                                        $tipo_class = 'tipo-ventas';
                                    } elseif (strpos(strtolower($reporte['tipo']), 'inventario') !== false) {
                                        $tipo_class = 'tipo-inventario';
                                    } elseif (strpos(strtolower($reporte['tipo']), 'producto') !== false) {
                                        $tipo_class = 'tipo-productos';
                                    }
                                    ?>
                                    <span class="tipo-badge <?php echo $tipo_class; ?>">
                                        <?php echo htmlspecialchars($reporte['tipo']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($reporte['fecha_generacion'])); ?></td>
                                <td><?php echo htmlspecialchars($reporte['empleado_nombre'] ?? 'Sistema'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($reportes)): ?>
                            <tr>
                                <td colspan="5" class="text-center" style="padding: 2rem;">
                                    No se encontraron reportes
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html> 