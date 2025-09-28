<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtrado
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$sucursal = isset($_GET['sucursal']) ? intval($_GET['sucursal']) : 0;
$fecha_desde = isset($_GET['fecha_desde']) ? $_GET['fecha_desde'] : '';
$fecha_hasta = isset($_GET['fecha_hasta']) ? $_GET['fecha_hasta'] : '';

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($estado)) {
    $where_clauses[] = "tp.estado = :estado";
    $params[':estado'] = $estado;
}

if ($sucursal > 0) {
    $where_clauses[] = "(tp.sucursal_origen_id = :sucursal OR tp.sucursal_destino_id = :sucursal)";
    $params[':sucursal'] = $sucursal;
}

if (!empty($fecha_desde)) {
    $where_clauses[] = "DATE(tp.fecha_solicitud) >= :fecha_desde";
    $params[':fecha_desde'] = $fecha_desde;
}

if (!empty($fecha_hasta)) {
    $where_clauses[] = "DATE(tp.fecha_solicitud) <= :fecha_hasta";
    $params[':fecha_hasta'] = $fecha_hasta;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener transferencias
$sql = "
    SELECT 
        tp.*,
        so.nombre as sucursal_origen,
        sd.nombre as sucursal_destino,
        e.nombre as empleado_nombre
    FROM transferencia_producto tp
    JOIN Sucursal so ON tp.sucursal_origen_id = so.sucursal_id
    JOIN Sucursal sd ON tp.sucursal_destino_id = sd.sucursal_id
    JOIN Empleado e ON tp.empleado_id = e.empleado_id
    $where_sql
    ORDER BY tp.fecha_solicitud DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$transferencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de sucursales para el filtro
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencias | Ferretería</title>
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
        .estado-enviado { background: #dbeafe; color: #1e40af; }
        .estado-recibido { background: #d1fae5; color: #065f46; }
        .estado-cancelado { background: #fee2e2; color: #991b1b; }
        .transfer-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
        }
        .transfer-actions a {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: transform 0.15s ease;
        }
        .transfer-actions a:hover {
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
                <h2><i class="fas fa-exchange-alt"></i> Transferencias</h2>
                <a href="../altas/transferencia.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nueva Transferencia
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
                        <label for="sucursal">Sucursal:</label>
                        <select id="sucursal" name="sucursal">
                            <option value="">Todas</option>
                            <?php foreach ($sucursales as $suc): ?>
                                <option value="<?php echo $suc['sucursal_id']; ?>" 
                                        <?php echo $sucursal === $suc['sucursal_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($suc['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <option value="SOLICITADA" <?php echo $estado === 'SOLICITADA' ? 'selected' : ''; ?>>Solicitada</option>
                            <option value="PREPARACION" <?php echo $estado === 'PREPARACION' ? 'selected' : ''; ?>>En Preparación</option>
                            <option value="EN_TRANSITO" <?php echo $estado === 'EN_TRANSITO' ? 'selected' : ''; ?>>En Tránsito</option>
                            <option value="RECIBIDA" <?php echo $estado === 'RECIBIDA' ? 'selected' : ''; ?>>Recibida</option>
                            <option value="CANCELADA" <?php echo $estado === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                            <option value="RECHAZADA" <?php echo $estado === 'RECHAZADA' ? 'selected' : ''; ?>>Rechazada</option>
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
                        <a href="transferencias.php" class="btn btn-secondary">
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
                            <th>Fecha Solicitud</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Empleado</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transferencias as $transferencia): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transferencia['transferencia_producto_id']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($transferencia['fecha_solicitud'])); ?></td>
                                <td><?php echo htmlspecialchars($transferencia['sucursal_origen']); ?></td>
                                <td><?php echo htmlspecialchars($transferencia['sucursal_destino']); ?></td>
                                <td><?php echo htmlspecialchars($transferencia['empleado_nombre']); ?></td>
                                <td>
                                    <span class="estado-badge estado-<?php echo strtolower($transferencia['estado']); ?>">
                                        <?php echo $transferencia['estado']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="transfer-actions">
                                        <a href="transferencia_detalle.php?id=<?php echo $transferencia['transferencia_producto_id']; ?>" 
                                           class="btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($transferencia['estado'] === 'SOLICITADA'): ?>
                                            <a href="../modificaciones/modificar_transferencia.php?id=<?php echo $transferencia['transferencia_producto_id']; ?>" 
                                               class="btn-edit" title="Editar transferencia">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" onclick="confirmarEliminacion(<?php echo $transferencia['transferencia_producto_id']; ?>)" 
                                               class="btn-delete" title="Eliminar transferencia">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($transferencias)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 2rem;">
                                    No se encontraron transferencias
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(transferId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta transferencia? Esta acción no se puede deshacer.')) {
            window.location.href = '../bajas/eliminar_transferencia.php?id=' + transferId;
        }
    }
    </script>
</body>
</html> 