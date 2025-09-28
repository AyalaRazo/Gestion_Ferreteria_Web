<?php
session_start();
require_once '../includes/db.php';

// Obtener lista de sucursales
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías únicas existentes
$stmt = $pdo->query("SELECT DISTINCT categoria FROM Producto WHERE categoria IS NOT NULL ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Producto | Ferretería</title>
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
        .form-group {
            margin-bottom: 1.5rem;
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
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group input[type="number"] {
            width: 100%;
        }
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        .categoria-container {
            margin-top: 1rem;
            display: none;
        }
        .stock-info {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 2rem;
        }
        .stock-info h3 {
            margin-bottom: 1rem;
            color: #1f2937;
        }
        .stock-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-box"></i> Nuevo Producto</h2>
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
                <form method="POST" action="procesar_alta_producto.php">
                    <div class="form-group">
                        <label for="codigo_producto">Código del Producto *</label>
                        <input type="text" id="codigo_producto" name="codigo_producto" required 
                               pattern="[A-Za-z0-9-]{3,20}" 
                               title="El código debe tener entre 3 y 20 caracteres alfanuméricos">
                    </div>

                    <div class="form-group">
                        <label for="nombre_producto">Nombre del Producto *</label>
                        <input type="text" id="nombre_producto" name="nombre_producto" required>
                    </div>

                    <div class="form-group">
                        <label for="precio">Precio *</label>
                        <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="categoria">Categoría *</label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Seleccione una categoría</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="nueva">+ Nueva Categoría</option>
                        </select>
                    </div>

                    <div id="nuevaCategoriaContainer" class="form-group categoria-container">
                        <label for="nuevaCategoria">Nueva Categoría *</label>
                        <input type="text" id="nuevaCategoria" name="nuevaCategoria">
                    </div>

                    <div class="stock-info">
                        <h3>Información de Inventario Inicial</h3>
                        <div class="form-group">
                            <label for="sucursal_id">Sucursal *</label>
                            <select id="sucursal_id" name="sucursal_id" required>
                                <option value="">Seleccione una sucursal</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?php echo $sucursal['sucursal_id']; ?>">
                                        <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="stock-grid">
                            <div class="form-group">
                                <label for="stock_inicial">Stock Inicial *</label>
                                <input type="number" id="stock_inicial" name="stock_inicial" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="stock_minimo">Stock Mínimo *</label>
                                <input type="number" id="stock_minimo" name="stock_minimo" min="0" required>
                            </div>

                            <div class="form-group">
                                <label for="stock_maximo">Stock Máximo *</label>
                                <input type="number" id="stock_maximo" name="stock_maximo" min="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="btn-container">
                        <button type="button" class="btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Producto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('categoria').addEventListener('change', function() {
        const nuevaCategoriaContainer = document.getElementById('nuevaCategoriaContainer');
        const nuevaCategoriaInput = document.getElementById('nuevaCategoria');
        
        if (this.value === 'nueva') {
            nuevaCategoriaContainer.style.display = 'block';
            nuevaCategoriaInput.required = true;
        } else {
            nuevaCategoriaContainer.style.display = 'none';
            nuevaCategoriaInput.required = false;
        }
    });
    </script>
</body>
</html>