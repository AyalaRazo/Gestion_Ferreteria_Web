<?php
session_start();
require_once '../includes/db.php';

// Parámetros de búsqueda y filtrado
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$categoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Obtener categorías únicas para el filtro
$stmt = $pdo->query("SELECT DISTINCT categoria FROM Producto ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Construir la consulta base
$sql = "
    SELECT p.*, 
           COALESCE(i.stock_total, 0) as stock_total
    FROM Producto p
    LEFT JOIN (
        SELECT producto_id, SUM(stock_actual) as stock_total
        FROM Inventario
        GROUP BY producto_id
    ) i ON p.producto_id = i.producto_id
    WHERE 1=1
";

// Aplicar filtros
if (!empty($busqueda)) {
    $sql .= " AND (p.codigo_producto LIKE :busqueda OR p.nombre_producto LIKE :busqueda)";
}
if (!empty($categoria)) {
    $sql .= " AND p.categoria = :categoria";
}

$sql .= " ORDER BY p.codigo_producto";

// Preparar y ejecutar la consulta
$stmt = $pdo->prepare($sql);

// Bind de parámetros
if (!empty($busqueda)) {
    $stmt->bindValue(':busqueda', "%$busqueda%", PDO::PARAM_STR);
}
if (!empty($categoria)) {
    $stmt->bindValue(':categoria', $categoria, PDO::PARAM_STR);
}

$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de sucursales para el filtro
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .filters-container {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }
        .filters-form {
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
            font-size: 0.875rem;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.15s ease;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .filter-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-filter {
            padding: 0.625rem 1rem;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-filter.primary {
            background-color: #3b82f6;
            color: white;
        }
        .btn-filter.primary:hover {
            background-color: #2563eb;
        }
        .btn-filter.secondary {
            background-color: #f3f4f6;
            color: #4b5563;
        }
        .btn-filter.secondary:hover {
            background-color: #e5e7eb;
        }
        .table-responsive {
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .table th {
            background-color: #f8fafc;
            font-weight: 600;
            color: #374151;
        }
        .table tr:hover {
            background-color: #f9fafb;
        }
        .stock-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .stock-bajo {
            background: #fee2e2;
            color: #991b1b;
        }
        .stock-medio {
            background: #fef3c7;
            color: #92400e;
        }
        .stock-alto {
            background: #dcfce7;
            color: #166534;
        }
        .table-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn-icon {
            padding: 0.5rem;
            border-radius: 6px;
            border: none;
            background: none;
            cursor: pointer;
            transition: all 0.15s ease;
            color: #4b5563;
        }
        .btn-icon:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }
        .btn-icon.text-danger {
            color: #ef4444;
        }
        .btn-icon.text-danger:hover {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .header-actions h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            margin: 0;
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: #3b82f6;
            color: white;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s ease;
        }
        .btn-primary:hover {
            background-color: #2563eb;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-content">
            <div class="header-actions">
                <h1>Productos</h1>
                <a href="../formularios/agregar_producto.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Nuevo Producto
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

            <div class="filters-container">
                <form method="GET" class="filters-form">
                    <div class="filter-group">
                        <label for="busqueda">Buscar</label>
                        <input type="text" id="busqueda" name="busqueda" 
                               value="<?php echo htmlspecialchars($busqueda); ?>" 
                               placeholder="Código o nombre del producto">
                    </div>
                    <div class="filter-group">
                        <label for="categoria">Categoría</label>
                        <select id="categoria" name="categoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $categoria === $cat ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <div class="filter-buttons">
                            <button type="submit" class="btn-filter primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                            <a href="productos.php" class="btn-filter secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Stock Total</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($producto['codigo_producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                                <td><?php echo htmlspecialchars($producto['categoria']); ?></td>
                                <td>$<?php echo number_format($producto['precio'], 2); ?></td>
                                <td>
                                    <?php
                                    $stock = $producto['stock_total'];
                                    $stock_class = 'bajo';
                                    if ($stock >= 50) {
                                        $stock_class = 'alto';
                                    } elseif ($stock >= 20) {
                                        $stock_class = 'medio';
                                    }
                                    ?>
                                    <span class="stock-badge stock-<?php echo $stock_class; ?>">
                                        <?php echo number_format($stock); ?> unidades
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="../modificaciones/modificar_producto.php?id=<?php echo $producto['producto_id']; ?>" 
                                           class="btn-icon" title="Modificar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="../eliminaciones/eliminar_producto.php?id=<?php echo $producto['producto_id']; ?>" 
                                           class="btn-icon text-danger" 
                                           onclick="return confirm('¿Está seguro de que desea eliminar este producto?')"
                                           title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">
                                    No se encontraron productos
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