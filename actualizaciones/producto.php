<?php
session_start();
require_once '../includes/db.php';

// Obtener ID del producto
$producto_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$producto_id) {
    header('Location: ../consultas/generales.php');
    exit;
}

// Obtener información del producto
$stmt = $pdo->prepare("
    SELECT p.*
    FROM Producto p
    WHERE p.producto_id = :id
");
$stmt->execute([':id' => $producto_id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    header('Location: ../consultas/generales.php');
    exit;
}

// Obtener categorías para el select
$stmt = $pdo->query("SELECT DISTINCT categoria FROM Producto ORDER BY categoria");
$categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Obtener proveedores para el select
$stmt = $pdo->query("SELECT proveedor_id, nombre FROM Proveedor ORDER BY nombre");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #166534;
        }
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #991b1b;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="form-container">
                <h2><i class="fas fa-edit"></i> Editar Producto</h2>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?>">
                        <?= $_SESSION['mensaje'] ?>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                <?php endif; ?>

                <form action="procesar_actualizacion_producto.php" method="POST" id="formProducto">
                    <input type="hidden" name="producto_id" value="<?= $producto_id ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="codigo">Código de Producto *</label>
                            <input type="text" class="form-control" id="codigo" name="codigo" 
                                   value="<?= htmlspecialchars($producto['codigo']) ?>"
                                   required pattern="[A-Za-z0-9-]{3,20}" 
                                   title="El código debe tener entre 3 y 20 caracteres alfanuméricos">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre del Producto *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($producto['nombre']) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="categoria">Categoría *</label>
                            <select class="form-control" id="categoria" name="categoria" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" 
                                            <?= $producto['categoria'] === $cat ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="nueva">+ Nueva Categoría</option>
                            </select>
                        </div>

                        <div class="form-group" id="nuevaCategoriaGroup" style="display: none;">
                            <label class="form-label" for="nueva_categoria">Nueva Categoría *</label>
                            <input type="text" class="form-control" id="nueva_categoria" name="nueva_categoria">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="proveedor_id">Proveedor *</label>
                            <select class="form-control" id="proveedor_id" name="proveedor_id" required>
                                <option value="">Seleccione un proveedor</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= $prov['proveedor_id'] ?>" 
                                            <?= $producto['proveedor_id'] == $prov['proveedor_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($prov['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="precio_compra">Precio de Compra *</label>
                            <input type="number" class="form-control" id="precio_compra" name="precio_compra" 
                                   value="<?= $producto['precio_compra'] ?>"
                                   step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="precio_venta">Precio de Venta *</label>
                            <input type="number" class="form-control" id="precio_venta" name="precio_venta" 
                                   value="<?= $producto['precio_venta'] ?>"
                                   step="0.01" min="0" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="unidad_medida">Unidad de Medida *</label>
                            <select class="form-control" id="unidad_medida" name="unidad_medida" required>
                                <option value="">Seleccione una unidad</option>
                                <option value="PIEZA" <?= $producto['unidad_medida'] === 'PIEZA' ? 'selected' : '' ?>>Pieza</option>
                                <option value="METRO" <?= $producto['unidad_medida'] === 'METRO' ? 'selected' : '' ?>>Metro</option>
                                <option value="KILOGRAMO" <?= $producto['unidad_medida'] === 'KILOGRAMO' ? 'selected' : '' ?>>Kilogramo</option>
                                <option value="LITRO" <?= $producto['unidad_medida'] === 'LITRO' ? 'selected' : '' ?>>Litro</option>
                                <option value="CAJA" <?= $producto['unidad_medida'] === 'CAJA' ? 'selected' : '' ?>>Caja</option>
                                <option value="ROLLO" <?= $producto['unidad_medida'] === 'ROLLO' ? 'selected' : '' ?>>Rollo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion']) ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="../consultas/producto_detalle.php?id=<?= $producto_id ?>" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('categoria').addEventListener('change', function() {
            const nuevaCategoriaGroup = document.getElementById('nuevaCategoriaGroup');
            const nuevaCategoriaInput = document.getElementById('nueva_categoria');
            
            if (this.value === 'nueva') {
                nuevaCategoriaGroup.style.display = 'block';
                nuevaCategoriaInput.required = true;
            } else {
                nuevaCategoriaGroup.style.display = 'none';
                nuevaCategoriaInput.required = false;
            }
        });

        document.getElementById('formProducto').addEventListener('submit', function(e) {
            const codigo = document.getElementById('codigo').value;
            const precioCompra = parseFloat(document.getElementById('precio_compra').value);
            const precioVenta = parseFloat(document.getElementById('precio_venta').value);
            
            if (!/^[A-Za-z0-9-]{3,20}$/.test(codigo)) {
                e.preventDefault();
                alert('El código debe tener entre 3 y 20 caracteres alfanuméricos');
                return;
            }
            
            if (precioVenta <= precioCompra) {
                e.preventDefault();
                alert('El precio de venta debe ser mayor al precio de compra');
                return;
            }
        });

        // Formatear código mientras se escribe
        document.getElementById('codigo').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-z0-9-]/g, '').slice(0, 20);
        });

        // Formatear precios mientras se escriben
        const precioInputs = document.querySelectorAll('input[type="number"]');
        precioInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                if (this.value < 0) this.value = 0;
            });
        });
    </script>
</body>
</html> 