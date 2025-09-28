<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtros
$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$metodo_pago = isset($_GET['metodo_pago']) ? trim($_GET['metodo_pago']) : '';
$sucursal_id = isset($_GET['sucursal_id']) ? intval($_GET['sucursal_id']) : 0;

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($fecha_inicio)) {
    $where_clauses[] = "v.fecha_venta >= :fecha_inicio";
    $params[':fecha_inicio'] = $fecha_inicio . ' 00:00:00';
}

if (!empty($fecha_fin)) {
    $where_clauses[] = "v.fecha_venta <= :fecha_fin";
    $params[':fecha_fin'] = $fecha_fin . ' 23:59:59';
}

if (!empty($estado)) {
    $where_clauses[] = "v.estado = :estado";
    $params[':estado'] = $estado;
}

if (!empty($metodo_pago)) {
    $where_clauses[] = "p.metodo_pago = :metodo_pago";
    $params[':metodo_pago'] = $metodo_pago;
}

if (!empty($sucursal_id)) {
    $where_clauses[] = "v.sucursal_id = :sucursal_id";
    $params[':sucursal_id'] = $sucursal_id;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener sucursales para el filtro
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener ventas con filtros
$sql = "
    SELECT 
        v.venta_id,
        v.fecha_venta,
        v.total_venta,
        v.estado,
        s.nombre as sucursal,
        e.nombre as empleado,
        p.metodo_pago,
        COUNT(d.venta_id) as num_productos
    FROM Venta v
    LEFT JOIN Sucursal s ON v.sucursal_id = s.sucursal_id
    LEFT JOIN Empleado e ON v.empleado_id = e.empleado_id
    LEFT JOIN Pago p ON v.venta_id = p.venta_id
    LEFT JOIN Detalles_venta d ON v.venta_id = d.venta_id
    $where_sql
    GROUP BY v.venta_id
    ORDER BY v.fecha_venta DESC
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .sale-actions {
            display: flex;
            gap: 0.5rem;
        }
        .sale-actions a {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            text-decoration: none;
            color: white;
        }
        .btn-view { background-color: #3b82f6; }
        .btn-edit { background-color: #f59e0b; }
        .btn-delete { background-color: #ef4444; }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .status-completada { background-color: #dcfce7; color: #166534; }
        .status-pendiente { background-color: #fef3c7; color: #92400e; }
        .status-cancelada { background-color: #fee2e2; color: #991b1b; }
        .payment-method {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            background-color: #f3f4f6;
            color: #374151;
        }
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
        .date-filter input {
            width: 150px;
        }
        /* Estilos mejorados para la tabla */
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
        /* Estilos para las columnas específicas */
        .column-id {
            width: 100px;
        }
        .column-date {
            width: 150px;
        }
        .column-client {
            min-width: 200px;
        }
        .column-total {
            width: 120px;
            text-align: right;
        }
        .column-status {
            width: 120px;
            text-align: center;
        }
        .column-payment {
            width: 150px;
        }
        .column-actions {
            width: 120px;
        }
        /* Estilos para los badges */
        .status-badge {
            min-width: 100px;
            text-align: center;
            display: inline-block;
        }
        .payment-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            background-color: #f3f4f6;
            color: #374151;
        }
        /* Estilos para las acciones */
        .sale-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
        }
        .sale-actions a {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: transform 0.15s ease;
        }
        .sale-actions a:hover {
            transform: translateY(-1px);
        }
        /* Estilo para números y moneda */
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .amount-value {
            font-family: 'Roboto Mono', monospace;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-shopping-cart"></i> Gestión de Ventas</h2>
                <a href="../altas/venta.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nueva Venta
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
                        <label for="fecha_inicio">Fecha Inicio:</label>
                        <input type="date" id="fecha_inicio" name="fecha_inicio" 
                               value="<?php echo htmlspecialchars($fecha_inicio); ?>" 
                               class="form-control">
                    </div>

                    <div class="filter-group">
                        <label for="fecha_fin">Fecha Fin:</label>
                        <input type="date" id="fecha_fin" name="fecha_fin" 
                               value="<?php echo htmlspecialchars($fecha_fin); ?>" 
                               class="form-control">
                    </div>

                    <div class="filter-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" class="form-control">
                            <option value="">Todos</option>
                            <option value="COMPLETADA" <?php echo $estado === 'COMPLETADA' ? 'selected' : ''; ?>>
                                Completada
                            </option>
                            <option value="PENDIENTE" <?php echo $estado === 'PENDIENTE' ? 'selected' : ''; ?>>
                                Pendiente
                            </option>
                            <option value="CANCELADA" <?php echo $estado === 'CANCELADA' ? 'selected' : ''; ?>>
                                Cancelada
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="metodo_pago">Método de Pago:</label>
                        <select id="metodo_pago" name="metodo_pago" class="form-control">
                            <option value="">Todos</option>
                            <option value="EFECTIVO" <?php echo $metodo_pago === 'EFECTIVO' ? 'selected' : ''; ?>>
                                Efectivo
                            </option>
                            <option value="TARJETA" <?php echo $metodo_pago === 'TARJETA' ? 'selected' : ''; ?>>
                                Tarjeta
                            </option>
                            <option value="TRANSFERENCIA" <?php echo $metodo_pago === 'TRANSFERENCIA' ? 'selected' : ''; ?>>
                                Transferencia
                            </option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="sucursal_id">Sucursal:</label>
                        <select id="sucursal_id" name="sucursal_id" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>" 
                                        <?php echo $sucursal_id === intval($sucursal['sucursal_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="ventas.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="column-id">ID Venta</th>
                            <th class="column-date">Fecha</th>
                            <th class="column-client">Cliente</th>
                            <th class="column-total">Total</th>
                            <th class="column-status">Estado</th>
                            <th class="column-payment">Método de Pago</th>
                            <th class="column-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td class="column-id">
                                    <?php echo htmlspecialchars($venta['venta_id']); ?>
                                </td>
                                <td class="column-date">
                                    <?php echo date('d/m/Y', strtotime($venta['fecha_venta'])); ?>
                                </td>
                                <td class="column-client">
                                    <?php echo htmlspecialchars($venta['sucursal']); ?>
                                </td>
                                <td class="column-total">
                                    <span class="amount-value">
                                        $<?php echo number_format($venta['total_venta'], 2); ?>
                                    </span>
                                </td>
                                <td class="column-status">
                                    <span class="status-badge status-<?php echo strtolower($venta['estado']); ?>">
                                        <?php echo htmlspecialchars($venta['estado']); ?>
                                    </span>
                                </td>
                                <td class="column-payment">
                                    <span class="payment-badge">
                                        <?php echo htmlspecialchars($venta['metodo_pago']); ?>
                                    </span>
                                </td>
                                <td class="column-actions">
                                    <div class="sale-actions">
                                        <a href="venta_detalle.php?id=<?php echo $venta['venta_id']; ?>" 
                                           class="btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../modificaciones/modificar_venta.php?id=<?php echo $venta['venta_id']; ?>" 
                                           class="btn-edit" title="Editar venta">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" onclick="confirmarEliminacion(<?php echo $venta['venta_id']; ?>)" 
                                           class="btn-delete" title="Eliminar venta">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ventas)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 2rem;">
                                    No se encontraron ventas
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(ventaId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta venta? Esta acción no se puede deshacer.')) {
            window.location.href = '../bajas/eliminar_venta.php?id=' + ventaId;
        }
    }
    </script>
</body>
</html> 