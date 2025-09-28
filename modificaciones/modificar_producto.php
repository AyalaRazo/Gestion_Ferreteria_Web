<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de producto no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/productos.php');
    exit();
}

$producto_id = intval($_GET['id']);

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo = trim($_POST['codigo']);
        $nombre = trim($_POST['nombre']);
        $precio = floatval($_POST['precio']);
        $categoria = trim($_POST['categoria']);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $sucursal_id = intval($_POST['sucursal_id']);
        $stock_actual = intval($_POST['stock_actual']);
        $stock_minimo = intval($_POST['stock_minimo']);
        $stock_maximo = intval($_POST['stock_maximo']);

        if (empty($codigo) || empty($nombre) || empty($categoria)) {
            throw new Exception('Todos los campos obligatorios deben ser completados.');
        }

        // Verificar si el código ya existe para otro producto
        $stmt = $pdo->prepare("
            SELECT producto_id 
            FROM Producto 
            WHERE codigo_producto = ? AND producto_id != ?
        ");
        $stmt->execute([$codigo, $producto_id]);
        if ($stmt->fetch()) {
            throw new Exception('El código de producto ya existe.');
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Actualizar el producto
        $stmt = $pdo->prepare("
            UPDATE Producto 
            SET codigo_producto = ?,
                nombre_producto = ?,
                precio = ?,
                categoria = ?,
                activo = ?
            WHERE producto_id = ?
        ");

        $stmt->execute([
            $codigo,
            $nombre,
            $precio,
            $categoria,
            $activo,
            $producto_id
        ]);

        // Actualizar el inventario
        $stmt = $pdo->prepare("
            UPDATE Inventario 
            SET stock_actual = ?,
                stock_minimo = ?,
                stock_maximo = ?,
                ultima_actualizacion = CURRENT_TIMESTAMP
            WHERE producto_id = ? AND sucursal_id = ?
        ");

        $stmt->execute([
            $stock_actual,
            $stock_minimo,
            $stock_maximo,
            $producto_id,
            $sucursal_id
        ]);

        $pdo->commit();

        $_SESSION['mensaje'] = 'Producto actualizado exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: ../consultas/productos.php');
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

// Obtener datos del producto
$stmt = $pdo->prepare("SELECT * FROM Producto WHERE producto_id = ?");
$stmt->execute([$producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    $_SESSION['mensaje'] = 'Producto no encontrado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/productos.php');
    exit();
}

// Obtener categorías únicas para el select
$stmt = $pdo->query("SELECT DISTINCT categoria FROM Producto ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener sucursales
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal WHERE estado = 'ACTIVO' ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos del inventario
$stmt = $pdo->prepare("
    SELECT i.*, s.nombre as sucursal_nombre
    FROM Inventario i
    JOIN Sucursal s ON i.sucursal_id = s.sucursal_id
    WHERE i.producto_id = ?
");
$stmt->execute([$producto_id]);
$inventarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Producto | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 2rem auto;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.15s ease;
        }
        .form-group input:focus,
        .form-group select:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .btn-primary,
        .btn-secondary {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.15s ease;
            cursor: pointer;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
            text-decoration: none;
        }
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        .required-field::after {
            content: ' *';
            color: #ef4444;
        }
        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .header-actions h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .inventory-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }
        .inventory-section h3 {
            margin-bottom: 1rem;
            color: #1f2937;
            font-size: 1.25rem;
        }
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .inventory-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .inventory-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        .inventory-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        .inventory-stat {
            text-align: center;
        }
        .inventory-stat-label {
            color: #6b7280;
            margin-bottom: 0.25rem;
        }
        .inventory-stat-value {
            font-weight: 500;
            color: #1f2937;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-edit"></i> Modificar Producto</h2>
                <a href="../consultas/productos.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
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

            <div class="form-container">
                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="codigo" class="required-field">Código del Producto</label>
                            <input type="text" id="codigo" name="codigo" 
                                   value="<?php echo htmlspecialchars($producto['codigo_producto']); ?>" 
                                   pattern="[A-Za-z0-9-]{3,20}"
                                   title="El código debe tener entre 3 y 20 caracteres alfanuméricos"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="nombre" class="required-field">Nombre del Producto</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="precio" class="required-field">Precio</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" 
                                   value="<?php echo htmlspecialchars($producto['precio']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="categoria" class="required-field">Categoría</label>
                            <select name="categoria" id="categoria" required>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                            <?php echo $producto['categoria'] === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" name="activo" value="1" 
                                       <?php echo $producto['activo'] ? 'checked' : ''; ?>>
                                Producto Activo
                            </label>
                        </div>
                    </div>

                    <div class="inventory-section">
                        <h3>Inventario por Sucursal</h3>
                        <div class="inventory-grid">
                            <?php foreach ($inventarios as $inv): ?>
                                <div class="inventory-card">
                                    <div class="inventory-title">
                                        <?php echo htmlspecialchars($inv['sucursal_nombre']); ?>
                                    </div>
                                    <div class="inventory-stats">
                                        <div class="inventory-stat">
                                            <div class="inventory-stat-label">Stock Actual</div>
                                            <input type="number" name="stock_actual" 
                                                   value="<?php echo $inv['stock_actual']; ?>" 
                                                   min="0" required>
                                        </div>
                                        <div class="inventory-stat">
                                            <div class="inventory-stat-label">Stock Mínimo</div>
                                            <input type="number" name="stock_minimo" 
                                                   value="<?php echo $inv['stock_minimo']; ?>" 
                                                   min="0" required>
                                        </div>
                                        <div class="inventory-stat">
                                            <div class="inventory-stat-label">Stock Máximo</div>
                                            <input type="number" name="stock_maximo" 
                                                   value="<?php echo $inv['stock_maximo']; ?>" 
                                                   min="0" required>
                                        </div>
                                    </div>
                                    <input type="hidden" name="sucursal_id" value="<?php echo $inv['sucursal_id']; ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/productos.php" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 