<?php
session_start();
require_once '../includes/db.php';

// Verificar si el empleado está autorizado (ID: 1)
$empleado_id = 1; // Por ahora hardcodeado, después se puede implementar un sistema de login

// Obtener lista de sucursales
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de productos
$stmt = $pdo->query("SELECT producto_id, codigo_producto, nombre_producto FROM Producto ORDER BY nombre_producto");
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Insertar la transferencia
        $stmt = $pdo->prepare("
            INSERT INTO transferencia_producto 
            (sucursal_origen_id, sucursal_destino_id, empleado_id, fecha_solicitud, estado)
            VALUES (:origen_id, :destino_id, :empleado_id, NOW(), 'SOLICITADA')
        ");

        $stmt->execute([
            ':origen_id' => $_POST['sucursal_origen_id'],
            ':destino_id' => $_POST['sucursal_destino_id'],
            ':empleado_id' => $empleado_id
        ]);

        $transferencia_id = $pdo->lastInsertId();

        // Insertar los detalles de la transferencia
        $stmt = $pdo->prepare("
            INSERT INTO detalles_transferencia 
            (transferencia_producto_id, producto_id, cantidad)
            VALUES (:transferencia_id, :producto_id, :cantidad)
        ");

        $productos_transferencia = json_decode($_POST['productos_transferencia'], true);
        
        foreach ($productos_transferencia as $producto) {
            $stmt->execute([
                ':transferencia_id' => $transferencia_id,
                ':producto_id' => $producto['producto_id'],
                ':cantidad' => $producto['cantidad']
            ]);
        }

        $pdo->commit();
        $_SESSION['mensaje'] = "Transferencia registrada exitosamente.";
        $_SESSION['mensaje_tipo'] = "success";
        header("Location: ../consultas/transferencias.php");
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
    <title>Nueva Transferencia | Ferretería</title>
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
            grid-template-columns: 2fr 1fr auto;
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
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-exchange-alt"></i> Nueva Transferencia</h2>
                <a href="../consultas/transferencias.php" class="btn-secondary">
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
                <form method="POST" action="" id="transferenciaForm">
                    <div class="form-group">
                        <label for="sucursal_origen_id">Sucursal de Origen *</label>
                        <select id="sucursal_origen_id" name="sucursal_origen_id" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>">
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="sucursal_destino_id">Sucursal de Destino *</label>
                        <select id="sucursal_destino_id" name="sucursal_destino_id" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>">
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="productos-lista" id="productosLista">
                        <h3>Productos a Transferir</h3>
                        <div id="productosContainer"></div>
                        <button type="button" class="btn-add-product" onclick="agregarProducto()">
                            <i class="fas fa-plus"></i> Agregar Producto
                        </button>
                    </div>

                    <input type="hidden" name="productos_transferencia" id="productosTransferencia">

                    <div class="btn-container">
                        <button type="button" class="btn-secondary" onclick="window.history.back()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary" onclick="prepararEnvio(event)">
                            <i class="fas fa-save"></i> Crear Transferencia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    const productos = <?php echo json_encode($productos); ?>;
    let productosAgregados = [];

    function agregarProducto() {
        const container = document.getElementById('productosContainer');
        const productoItem = document.createElement('div');
        productoItem.className = 'producto-item';
        
        const productoIndex = productosAgregados.length;
        
        productoItem.innerHTML = `
            <select required>
                <option value="">Seleccione un producto</option>
                ${productos.map(p => `
                    <option value="${p.producto_id}">
                        ${p.codigo_producto} - ${p.nombre_producto}
                    </option>
                `).join('')}
            </select>
            <input type="number" min="1" value="1" required placeholder="Cantidad">
            <button type="button" class="btn-remove-product" onclick="eliminarProducto(${productoIndex})">
                <i class="fas fa-trash"></i>
            </button>
        `;
        
        container.appendChild(productoItem);
        productosAgregados.push({
            producto_id: '',
            cantidad: 1,
            elemento: productoItem
        });
    }

    function eliminarProducto(index) {
        productosAgregados[index].elemento.remove();
        productosAgregados[index] = null;
    }

    function prepararEnvio(event) {
        event.preventDefault();
        
        // Validar que no se seleccione la misma sucursal
        const origen = document.getElementById('sucursal_origen_id').value;
        const destino = document.getElementById('sucursal_destino_id').value;
        
        if (origen === destino) {
            alert('La sucursal de origen y destino no pueden ser la misma');
            return;
        }

        const productos = productosAgregados
            .filter(p => p !== null)
            .map(p => {
                const elemento = p.elemento;
                return {
                    producto_id: elemento.querySelector('select').value,
                    cantidad: elemento.querySelector('input[type="number"]').value
                };
            });

        if (productos.length === 0) {
            alert('Debe agregar al menos un producto a la transferencia');
            return;
        }

        document.getElementById('productosTransferencia').value = JSON.stringify(productos);
        document.getElementById('transferenciaForm').submit();
    }
    </script>
</body>
</html> 