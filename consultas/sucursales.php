<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtrado
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$ciudad = isset($_GET['ciudad']) ? trim($_GET['ciudad']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($busqueda)) {
    $where_clauses[] = "(codigo_sucursal LIKE :busqueda OR nombre LIKE :busqueda OR direccion LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($ciudad)) {
    $where_clauses[] = "ciudad = :ciudad";
    $params[':ciudad'] = $ciudad;
}

if (!empty($estado)) {
    $where_clauses[] = "estado = :estado";
    $params[':estado'] = $estado;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener sucursales
$sql = "
    SELECT * FROM Sucursal
    $where_sql
    ORDER BY nombre
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener ciudades y estados únicos para los filtros
$stmt = $pdo->query("SELECT DISTINCT ciudad FROM Sucursal ORDER BY ciudad");
$ciudades = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->query("SELECT DISTINCT estado FROM Sucursal ORDER BY estado");
$estados = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sucursales | Ferretería</title>
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
        .branch-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
        }
        .branch-actions a {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: transform 0.15s ease;
            color: white;
        }
        .branch-actions a:hover {
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
                <h2><i class="fas fa-store"></i> Sucursales</h2>
                <a href="../altas/sucursal.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nueva Sucursal
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
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Código, nombre o dirección...">
                    </div>

                    <div class="filter-group">
                        <label for="ciudad">Ciudad:</label>
                        <select id="ciudad" name="ciudad">
                            <option value="">Todas</option>
                            <?php foreach ($ciudades as $c): ?>
                                <option value="<?php echo htmlspecialchars($c); ?>" 
                                        <?php echo $ciudad === $c ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado">
                            <option value="">Todos</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?php echo htmlspecialchars($e); ?>" 
                                        <?php echo $estado === $e ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($e); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="sucursales.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Ciudad</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sucursal['codigo_sucursal']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['ciudad']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['estado']); ?></td>
                                <td>
                                    <div class="branch-actions">
                                        <a href="sucursal_detalle.php?id=<?php echo $sucursal['sucursal_id']; ?>" 
                                           class="btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../modificaciones/modificar_sucursal.php?id=<?php echo $sucursal['sucursal_id']; ?>" 
                                           class="btn-edit" title="Editar sucursal">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" onclick="confirmarEliminacion(<?php echo $sucursal['sucursal_id']; ?>)" 
                                           class="btn-delete" title="Eliminar sucursal">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($sucursales)): ?>
                            <tr>
                                <td colspan="7" class="text-center" style="padding: 2rem;">
                                    No se encontraron sucursales
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(sucursalId) {
        if (confirm('¿Estás seguro de que deseas eliminar esta sucursal? Esta acción no se puede deshacer.')) {
            window.location.href = '../bajas/eliminar_sucursal.php?id=' + sucursalId;
        }
    }
    </script>
</body>
</html> 