<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtrado
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($busqueda)) {
    $where_clauses[] = "(p.nombre LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($estado)) {
    $where_clauses[] = "oc.estado = :estado";
    $params[':estado'] = $estado;
}

if (!empty($fecha_desde)) {
    $where_clauses[] = "DATE(oc.fecha_orden) >= :fecha_desde";
    $params[':fecha_desde'] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $where_clauses[] = "DATE(oc.fecha_orden) <= :fecha_hasta";
    $params[':fecha_hasta'] = $fecha_hasta;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener órdenes de compra
$sql = "
    SELECT 
        oc.orden_compra_id,
        oc.fecha_orden,
        oc.total,
        oc.estado,
        p.nombre as proveedor_nombre,
        e.nombre as empleado_nombre
    FROM orden_compra oc
    JOIN Proveedor p ON oc.proveedor_id = p.proveedor_id
    JOIN Empleado e ON oc.empleado_id = e.empleado_id
    $where_sql
    ORDER BY oc.fecha_orden DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Órdenes de Compra | Ferretería</title>
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
            overflow: hidden;
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
        .estado-badge {
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
        .order-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
        }
        .order-actions a {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: transform 0.15s ease;
        }
        .order-actions a:hover {
            transform: translateY(-1px);
        }
        .btn-view { background-color: #3b82f6; }
        .btn-edit { background-color: #f59e0b; }
        .btn-delete { background-color: #ef4444; }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-shopping-basket"></i> Órdenes de Compra</h2>
                <a href="../altas/orden_compra.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nueva Orden
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

            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label for="busqueda">Buscar por proveedor:</label>
                        <input type="text" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Nombre del proveedor...">
                    </div>

                    <div class="filter-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="PENDIENTE" <?php echo $estado === 'PENDIENTE' ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="APROBADA" <?php echo $estado === 'APROBADA' ? 'selected' : ''; ?>>Aprobada</option>
                            <option value="RECHAZADA" <?php echo $estado === 'RECHAZADA' ? 'selected' : ''; ?>>Rechazada</option>
                            <option value="ENVIADA" <?php echo $estado === 'ENVIADA' ? 'selected' : ''; ?>>Enviada</option>
                            <option value="RECIBIDA" <?php echo $estado === 'RECIBIDA' ? 'selected' : ''; ?>>Recibida</option>
                            <option value="CANCELADA" <?php echo $estado === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
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
                        <a href="ordenes_compra.php" class="btn btn-secondary">
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
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Empleado</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $orden): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($orden['orden_compra_id']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($orden['fecha_orden'])); ?></td>
                                <td><?php echo htmlspecialchars($orden['proveedor_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($orden['empleado_nombre']); ?></td>
                                <td>$<?php echo number_format($orden['total'], 2); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo strtolower($orden['estado']); ?>">
                                        <?php echo $orden['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="order-actions">
                                        <a href="orden_compra_detalle.php?id=<?php echo $orden['orden_compra_id']; ?>" 
                                           class="btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($orden['estado'] === 'PENDIENTE'): ?>
                                            <a href="../modificaciones/modificar_orden_compra.php?id=<?php echo $orden['orden_compra_id']; ?>" 
                                               class="btn-edit" title="Editar orden">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmarEliminacion(<?php echo $orden['orden_compra_id']; ?>)" 
                                               class="btn-delete" title="Eliminar orden">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 2rem;">
                                    No se encontraron órdenes de compra
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(ordenId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta orden de compra? Esta acción no se puede deshacer.')) {
            window.location.href = '../bajas/eliminar_orden_compra.php?id=' + ordenId;
        }
    }
    </script>
</body>
</html> 