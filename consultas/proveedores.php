<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir la consulta con filtros
$where_clauses = [];
$params = [];

if (!empty($busqueda)) {
    $where_clauses[] = "(nombre LIKE :busqueda OR email LIKE :busqueda OR telefono LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener proveedores
$sql = "
    SELECT 
        proveedor_id,
        nombre,
        telefono,
        email,
        direccion
    FROM Proveedor
    $where_sql
    ORDER BY nombre
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Proveedores | Ferretería</title>
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
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        /* Estilos para la tabla */
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
        /* Estilos para las columnas */
        .column-id {
            width: 80px;
        }
        .column-name {
            min-width: 200px;
        }
        .column-contact {
            width: 150px;
        }
        .column-email {
            width: 200px;
        }
        .column-address {
            min-width: 250px;
        }
        .column-actions {
            width: 120px;
        }
        /* Estilos para las acciones */
        .provider-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-start;
        }
        .provider-actions a {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: transform 0.15s ease;
        }
        .provider-actions a:hover {
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
                <h2><i class="fas fa-truck"></i> Gestión de Proveedores</h2>
                <a href="../altas/proveedor.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Proveedor
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
                               placeholder="Nombre, email o teléfono..." class="form-control">
                    </div>

                    <div class="filter-group">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-filter"></i> Filtrar
                        </button>
                        <a href="proveedores.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="column-id">ID</th>
                            <th class="column-name">Nombre</th>
                            <th class="column-contact">Teléfono</th>
                            <th class="column-email">Email</th>
                            <th class="column-address">Dirección</th>
                            <th class="column-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <tr>
                                <td class="column-id">
                                    <?php echo htmlspecialchars($proveedor['proveedor_id']); ?>
                                </td>
                                <td class="column-name">
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </td>
                                <td class="column-contact">
                                    <?php echo htmlspecialchars($proveedor['telefono']); ?>
                                </td>
                                <td class="column-email">
                                    <?php echo htmlspecialchars($proveedor['email']); ?>
                                </td>
                                <td class="column-address">
                                    <?php echo htmlspecialchars($proveedor['direccion']); ?>
                                </td>
                                <td class="column-actions">
                                    <div class="provider-actions">
                                        <a href="proveedor_detalle.php?id=<?php echo $proveedor['proveedor_id']; ?>" 
                                           class="btn-view" title="Ver detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../modificaciones/modificar_proveedor.php?id=<?php echo $proveedor['proveedor_id']; ?>" 
                                           class="btn-edit" title="Editar proveedor">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" onclick="confirmarEliminacion(<?php echo $proveedor['proveedor_id']; ?>)" 
                                           class="btn-delete" title="Eliminar proveedor">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($proveedores)): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 2rem;">
                                    No se encontraron proveedores
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function confirmarEliminacion(proveedorId) {
        if (confirm('¿Estás seguro de que deseas eliminar este proveedor? Esta acción no se puede deshacer.')) {
            window.location.href = '../bajas/eliminar_proveedor.php?id=' + proveedorId;
        }
    }
    </script>
</body>
</html> 