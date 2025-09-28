<?php
session_start();
require_once '../includes/db.php';

// Obtener sucursales
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener productos
$stmt = $pdo->query("
    SELECT 
        p.producto_id,
        p.codigo_producto,
        p.nombre_producto,
        p.precio,
        p.categoria
    FROM Producto p
    WHERE p.activo = 1
    ORDER BY p.nombre_producto
");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Venta | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            max-width: 1200px;
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
        .table-container {
            margin: 2rem 0;
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
        .btn-add {
            padding: 0.5rem;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-remove {
            padding: 0.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .totals {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .total-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.25rem;
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
                <h2><i class="fas fa-cash-register"></i> Nueva Venta</h2>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?>">
                        <?= $_SESSION['mensaje'] ?>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                <?php endif; ?>

                <form action="procesar_alta_venta.php" method="POST" id="formVenta">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="sucursal_id">Sucursal *</label>
                            <select class="form-control" id="sucursal_id" name="sucursal_id" required>
                                <option value="">Seleccione una sucursal</option>
                                <?php foreach ($sucursales as $sucursal): ?>
                                    <option value="<?= $sucursal['sucursal_id'] ?>">
                                        <?= htmlspecialchars($sucursal['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="metodo_pago">Método de Pago *</label>
                            <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="DEBITO">Débito</option>
                                <option value="CREDITO">Crédito</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Agregar Producto</label>
                        <div class="form-row">
                            <div class="form-group">
                                <select class="form-control" id="producto_select" disabled>
                                    <option value="">Seleccione primero una sucursal</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="number" class="form-control" id="cantidad_input" min="1" value="1" placeholder="Cantidad">
                            </div>
                            <div class="form-group">
                                <button type="button" class="btn-add" id="agregar_producto" disabled>
                                    <i class="fas fa-plus"></i> Agregar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="productos_tabla">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Precio Unitario</th>
                                    <th>Subtotal</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                    <div class="totals">
                        <div class="total-row">
                            <span>Total:</span>
                            <span id="total_venta">$0.00</span>
                        </div>
                    </div>

                    <input type="hidden" name="productos" id="productos_json">
                    <input type="hidden" name="total_venta" id="total_venta_input">

                    <div class="form-group">
                        <button type="submit" class="btn-primary" id="submit_venta">
                            <i class="fas fa-save"></i> Registrar Venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        let productos = [];
        const formatter = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        });

        // Función para cargar productos según la sucursal seleccionada
        function cargarProductos(sucursal_id) {
            const select = document.getElementById('producto_select');
            const btnAgregar = document.getElementById('agregar_producto');
            
            if (!sucursal_id) {
                select.disabled = true;
                btnAgregar.disabled = true;
                select.innerHTML = '<option value="">Seleccione primero una sucursal</option>';
                return;
            }

            select.disabled = true;
            btnAgregar.disabled = true;
            select.innerHTML = '<option value="">Cargando productos...</option>';

            fetch(`obtener_productos.php?sucursal_id=${sucursal_id}`)
                .then(response => response.json())
                .then(data => {
                    select.innerHTML = '<option value="">Seleccione un producto</option>';
                    data.forEach(producto => {
                        const option = document.createElement('option');
                        option.value = producto.producto_id;
                        option.textContent = `${producto.codigo_producto} - ${producto.nombre_producto} (Stock: ${producto.stock_actual})`;
                        option.dataset.codigo = producto.codigo_producto;
                        option.dataset.nombre = producto.nombre_producto;
                        option.dataset.precio = producto.precio;
                        option.dataset.stock = producto.stock_actual;
                        select.appendChild(option);
                    });
                    select.disabled = false;
                    btnAgregar.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    select.innerHTML = '<option value="">Error al cargar productos</option>';
                });
        }

        // Evento para cuando se cambia la sucursal
        document.getElementById('sucursal_id').addEventListener('change', function() {
            cargarProductos(this.value);
            // Limpiar productos agregados cuando se cambia de sucursal
            productos = [];
            actualizarTabla();
            actualizarTotal();
        });

        document.getElementById('agregar_producto').addEventListener('click', function() {
            const select = document.getElementById('producto_select');
            const option = select.options[select.selectedIndex];
            
            if (!option.value) return;

            const cantidad = parseInt(document.getElementById('cantidad_input').value);
            if (cantidad < 1) return;

            // Verificar stock disponible
            const stockDisponible = parseInt(option.dataset.stock);
            if (cantidad > stockDisponible) {
                alert(`Stock insuficiente. Solo hay ${stockDisponible} unidades disponibles.`);
                return;
            }

            // Verificar si el producto ya está en la lista
            const productoExistente = productos.find(p => p.id === option.value);
            if (productoExistente) {
                if (productoExistente.cantidad + cantidad > stockDisponible) {
                    alert(`Stock insuficiente. Solo puede agregar ${stockDisponible - productoExistente.cantidad} unidades más.`);
                    return;
                }
                productoExistente.cantidad += cantidad;
                productoExistente.subtotal = productoExistente.cantidad * productoExistente.precio;
            } else {
                const producto = {
                    id: option.value,
                    codigo: option.dataset.codigo,
                    nombre: option.dataset.nombre,
                    cantidad: cantidad,
                    precio: parseFloat(option.dataset.precio),
                    subtotal: cantidad * parseFloat(option.dataset.precio)
                };
                productos.push(producto);
            }

            actualizarTabla();
            actualizarTotal();

            // Limpiar selección
            select.value = '';
            document.getElementById('cantidad_input').value = '1';
        });

        function eliminarProducto(index) {
            productos.splice(index, 1);
            actualizarTabla();
            actualizarTotal();
        }

        function actualizarTabla() {
            const tbody = document.querySelector('#productos_tabla tbody');
            tbody.innerHTML = '';

            productos.forEach((producto, index) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre}</td>
                    <td>${producto.cantidad}</td>
                    <td>${formatter.format(producto.precio)}</td>
                    <td>${formatter.format(producto.subtotal)}</td>
                    <td>
                        <button type="button" class="btn-remove" onclick="eliminarProducto(${index})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('productos_json').value = JSON.stringify(productos);
        }

        function actualizarTotal() {
            const total = productos.reduce((sum, p) => sum + p.subtotal, 0);
            document.getElementById('total_venta').textContent = formatter.format(total);
            document.getElementById('total_venta_input').value = total;
        }

        document.getElementById('formVenta').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (productos.length === 0) {
                alert('Debe agregar al menos un producto a la venta.');
                return;
            }

            if (!document.getElementById('sucursal_id').value) {
                alert('Debe seleccionar una sucursal.');
                return;
            }

            this.submit();
        });
    </script>
</body>
</html> 