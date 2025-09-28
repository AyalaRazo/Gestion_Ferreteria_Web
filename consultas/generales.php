<?php
require_once '../includes/db.php';

// Parámetros de paginación y filtros
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filtros
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$proveedor_id = isset($_GET['proveedor_id']) ? intval($_GET['proveedor_id']) : 0;

// Construir la consulta base
$where_clauses = [];
$params = [];

if (!empty($categoria)) {
    $where_clauses[] = "p.categoria = :categoria";
    $params[':categoria'] = $categoria;
}

if (!empty($busqueda)) {
    $where_clauses[] = "(p.nombre LIKE :busqueda OR p.codigo LIKE :busqueda OR p.descripcion LIKE :busqueda)";
    $params[':busqueda'] = "%$busqueda%";
}

if (!empty($proveedor_id)) {
    $where_clauses[] = "p.proveedor_id = :proveedor_id";
    $params[':proveedor_id'] = $proveedor_id;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Obtener total de registros para paginación
$count_sql = "SELECT COUNT(*) FROM Producto p $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Consulta principal
$sql = "
    SELECT 
        p.*,
        pr.nombre as proveedor_nombre,
        (
            SELECT COALESCE(SUM(i.stock_actual), 0)
            FROM Inventario i
            WHERE i.producto_id = p.producto_id
        ) as stock_total
    FROM Producto p
    LEFT JOIN Proveedor pr ON p.proveedor_id = pr.proveedor_id
    $where_sql
    ORDER BY p.nombre
    LIMIT :offset, :per_page
";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':per_page', $per_page, PDO::PARAM_INT);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías para el filtro
$stmt = $pdo->query("SELECT DISTINCT categoria FROM Producto ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener proveedores para el filtro
$stmt = $pdo->query("SELECT proveedor_id, nombre FROM Proveedor ORDER BY nombre");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Productos | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filter-group {
            display: inline-block;
            margin-right: 1rem;
            margin-bottom: 1rem;
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
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
        .pagination {
            margin-top: 2rem;
            text-align: center;
        }
        .pagination a {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 5px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
        }
        .pagination a.active {
            background: #2563eb;
            color: white;
        }
        .stock-warning {
            color: #ef4444;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <h2><i class="fas fa-box-open"></i> Consulta de Productos</h2>

            <!-- Estadísticas Generales -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-title">Total de Productos</div>
                    <div class="stat-value"><?= $total_records ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Stock Total</div>
                    <div class="stat-value">
                        <?= number_format(array_sum(array_column($productos, 'stock_total'))) ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Categorías</div>
                    <div class="stat-value"><?= count($categorias) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-title">Proveedores</div>
                    <div class="stat-value"><?= count($proveedores) ?></div>
                </div>
            </div>
            
            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label for="busqueda">Buscar:</label>
                        <input type="text" id="busqueda" name="busqueda" value="<?= htmlspecialchars($busqueda) ?>" 
                               placeholder="Nombre, código o descripción..." class="form-control">
                    </div>
                    
                    <div class="filter-group">
                        <label for="categoria">Categoría:</label>
                        <select id="categoria" name="categoria" class="form-control">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" 
                                        <?= $categoria === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="proveedor_id">Proveedor:</label>
                        <select id="proveedor_id" name="proveedor_id" class="form-control">
                            <option value="">Todos</option>
                            <?php foreach ($proveedores as $prov): ?>
                                <option value="<?= $prov['proveedor_id'] ?>" 
                                        <?= $proveedor_id === $prov['proveedor_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prov['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </form>
            </div>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Proveedor</th>
                            <th>Precio Compra</th>
                            <th>Precio Venta</th>
                            <th>Stock Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?= htmlspecialchars($producto['codigo']) ?></td>
                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                <td><?= htmlspecialchars($producto['categoria']) ?></td>
                                <td><?= htmlspecialchars($producto['proveedor_nombre']) ?></td>
                                <td>$<?= number_format($producto['precio_compra'], 2) ?></td>
                                <td>$<?= number_format($producto['precio_venta'], 2) ?></td>
                                <td class="<?= $producto['stock_total'] < 10 ? 'stock-warning' : '' ?>">
                                    <?= number_format($producto['stock_total']) ?>
                                </td>
                                <td>
                                    <a href="../actualizaciones/producto.php?id=<?= $producto['producto_id'] ?>" 
                                       class="btn btn-sm btn-primary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="producto_detalle.php?id=<?= $producto['producto_id'] ?>" 
                                       class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="8" class="text-center">No se encontraron productos</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page-1 ?>&categoria=<?= urlencode($categoria) ?>&busqueda=<?= urlencode($busqueda) ?>&proveedor_id=<?= $proveedor_id ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?= $i ?>&categoria=<?= urlencode($categoria) ?>&busqueda=<?= urlencode($busqueda) ?>&proveedor_id=<?= $proveedor_id ?>" 
                               class="<?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page+1 ?>&categoria=<?= urlencode($categoria) ?>&busqueda=<?= urlencode($busqueda) ?>&proveedor_id=<?= $proveedor_id ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>