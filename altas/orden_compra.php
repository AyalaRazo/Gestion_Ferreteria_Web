<?php
session_start();
require_once '../includes/db.php';

// Verificar si el empleado está autorizado (ID: 1)
$empleado_id = 1; // Por ahora hardcodeado, después se puede implementar un sistema de login

// Obtener lista de proveedores
$stmt = $pdo->query("SELECT proveedor_id, nombre FROM Proveedor ORDER BY nombre");
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de productos
$stmt = $pdo->query("SELECT producto_id, codigo_producto, nombre_producto, precio FROM Producto ORDER BY nombre_producto");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insertar la orden de compra
        $stmt = $pdo->prepare("
            INSERT INTO orden_compra (proveedor_id, empleado_id, fecha_orden, total, estado)
            VALUES (:proveedor_id, :empleado_id, NOW(), :total, 'PENDIENTE')
        ");

        $total_orden = 0;
        $productos_orden = json_decode($_POST['productos_orden'], true);
        
        foreach ($productos_orden as $producto) {
            $total_orden += $producto['cantidad'] * $producto['precio_unitario'];
        }

        $stmt->execute([
            ':proveedor_id' => $_POST['proveedor_id'],
            ':empleado_id' => $empleado_id,
            ':total' => $total_orden
        ]);

        $orden_compra_id = $pdo->lastInsertId();

        // Insertar los detalles de la orden
        $stmt = $pdo->prepare("
            INSERT INTO detalles_orden_compra 
            (orden_compra_id, producto_id, cantidad, precio_unitario, fecha_entrega, estado)
            VALUES (:orden_compra_id, :producto_id, :cantidad, :precio_unitario, :fecha_entrega, 'PENDIENTE')
        ");

        foreach ($productos_orden as $producto) {
            $stmt->execute([
                ':orden_compra_id' => $orden_compra_id,
                ':producto_id' => $producto['producto_id'],
                ':cantidad' => $producto['cantidad'],
                ':precio_unitario' => $producto['precio_unitario'],
                ':fecha_entrega' => $producto['fecha_entrega']
            ]);
        }

        $pdo->commit();
        $_SESSION['mensaje'] = "Orden de compra creada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
        header("Location: ../consultas/ordenes_compra.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['mensaje'] = "Error: " . $e->getMessage();
        $_SESSION['mensaje_tipo'] = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Orden de Compra | Ferretería</title>
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
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
        }
        .productos-lista {
            margin-top: 2rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1rem;
        }
        .producto-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .producto-item:last-child {
            border-bottom: none;
        }
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        .btn-add-product {
            background-color: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn-remove-product {
            background-color: #ef4444;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: none;
        }
        .total-orden {
            margin-top: 2rem;
            text-align: right;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-shopping-basket"></i> Nueva Orden de Compra</h2>
                <a href="../consultas/ordenes_compra.php" class="btn-secondary">
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
                <form method="POST" action="" id="ordenCompraForm">
                    <div class="form-group">
                        <label for="proveedor_id">Proveedor *</label>
                        <select id="proveedor_id" name="proveedor_id" required>
                            <option value="">Seleccione un proveedor</option>
                            <?php foreach ($proveedores as $proveedor): ?>
                                <option value="<?php echo $proveedor['proveedor_id']; ?>">
                                    <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="productos-lista" id="productosLista">
                        <h3>Productos</h3>
                        <div id="productosContainer"></div>
                        <button type="button" class="btn-add-product" onclick="agregarProducto()">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </div>

                    <div class="total-orden">
                        Total: $<span id="totalOrden">0.00</span>
                    </div>

                    <input type="hidden" name="productos_orden" id="productosOrden">

                    <div class="btn-container">
                        <button type="button" class="btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" onclick="prepararEnvio(event)">
                            <i class="fas fa-save"></i> Crear Orden
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let productos = [];
        const productosData = <?php echo json_encode($productos); ?>;

        function agregarProducto() {
            const productoHtml = `
                <div class="producto-item">
                    <div class="form-group">
                        <label>Producto *</label>
                        <select class="producto-select" required onchange="actualizarPrecio(this)">
                            <option value="">Seleccione un producto</option>
                            ${productosData.map(p => `
                                <option value="${p.producto_id}" 
                                        data-precio="${p.precio}">
                                    ${p.codigo_producto} - ${p.nombre_producto}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cantidad *</label>
                        <input type="number" class="cantidad-input" min="1" required 
                               onchange="actualizarTotal(this)">
                    </div>
                    <div class="form-group">
                        <label>Precio Unitario *</label>
                        <input type="number" class="precio-input" step="0.01" min="0" required 
                               onchange="actualizarTotal(this)">
                    </div>
                    <div class="form-group">
                        <label>Fecha de Entrega *</label>
                        <input type="date" class="fecha-entrega" required>
                    </div>
                    <button type="button" class="btn-remove" onclick="eliminarProducto(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            document.getElementById('productosContainer').insertAdjacentHTML('beforeend', productoHtml);
        }

        function actualizarPrecio(select) {
            const precio = select.options[select.selectedIndex].dataset.precio;
            const productoItem = select.closest('.producto-item');
            productoItem.querySelector('.precio-input').value = precio;
            actualizarTotal(productoItem.querySelector('.cantidad-input'));
        }

        function actualizarTotal(input) {
            const productoItem = input.closest('.producto-item');
            const cantidad = parseFloat(productoItem.querySelector('.cantidad-input').value) || 0;
            const precio = parseFloat(productoItem.querySelector('.precio-input').value) || 0;
            
            let total = 0;
            document.querySelectorAll('.producto-item').forEach(item => {
                const cant = parseFloat(item.querySelector('.cantidad-input').value) || 0;
                const prec = parseFloat(item.querySelector('.precio-input').value) || 0;
                total += cant * prec;
            });
            
            document.getElementById('totalOrden').textContent = total.toFixed(2);
        }

        function eliminarProducto(btn) {
            const productoItem = btn.closest('.producto-item');
            productoItem.remove();
            actualizarTotal(document.querySelector('.cantidad-input'));
        }

        function prepararEnvio(event) {
            event.preventDefault();
            const form = document.getElementById('ordenCompraForm');
            
            // Validar proveedor
            const proveedor_id = form.querySelector('#proveedor_id').value;
            if (!proveedor_id) {
                alert('Por favor seleccione un proveedor');
                return;
            }

            // Validar que haya al menos un producto
            const productos = document.querySelectorAll('.producto-item');
            if (productos.length === 0) {
                alert('Debe agregar al menos un producto a la orden');
                return;
            }

            // Recopilar datos de productos
            const productosData = [];
            let isValid = true;

            productos.forEach(item => {
                const producto_id = item.querySelector('.producto-select').value;
                const cantidad = item.querySelector('.cantidad-input').value;
                const precio_unitario = item.querySelector('.precio-input').value;
                const fecha_entrega = item.querySelector('.fecha-entrega').value;

                if (!producto_id || !cantidad || !precio_unitario || !fecha_entrega) {
                    alert('Por favor complete todos los campos requeridos para cada producto');
                    isValid = false;
                    return;
                }

                if (cantidad <= 0) {
                    alert('La cantidad debe ser mayor a 0');
                    isValid = false;
                    return;
                }

                if (precio_unitario <= 0) {
                    alert('El precio unitario debe ser mayor a 0');
                    isValid = false;
                    return;
                }

                productosData.push({
                    producto_id: parseInt(producto_id),
                    cantidad: parseInt(cantidad),
                    precio_unitario: parseFloat(precio_unitario),
                    fecha_entrega: fecha_entrega
                });
            });

            if (!isValid) return;

            // Guardar datos en el campo oculto
            document.getElementById('productosOrden').value = JSON.stringify(productosData);

            // Enviar formulario
            form.submit();
        }

        // Agregar primer producto al cargar la página
        agregarProducto();
    </script>
</body>
</html> 