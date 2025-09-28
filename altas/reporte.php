<?php
session_start();
require_once '../includes/db.php';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_reporte'];
    $nombre = '';
    $origen = isset($_POST['origen']) ? $_POST['origen'] : null;
    $datos_origen = isset($_POST['datos_origen']) ? json_decode($_POST['datos_origen'], true) : null;
    
    // Generar nombre descriptivo según el tipo
    switch($tipo) {
        case 'ventas_diarias':
            $nombre = 'Reporte de Ventas Diarias';
            break;
        case 'productos_populares':
            $nombre = 'Reporte de Productos Populares';
            break;
        case 'rendimiento_sucursal':
            $nombre = 'Reporte de Rendimiento por Sucursal';
            break;
        case 'listado_sucursales':
            $nombre = 'Listado General de Sucursales';
            break;
        case 'sucursales_ventas':
            $nombre = 'Reporte de Sucursales por Ventas';
            break;
        case 'inventario_general':
            $nombre = 'Inventario General';
            break;
        case 'productos_stock_bajo':
            $nombre = 'Productos con Stock Bajo';
            break;
        case 'productos_categoria':
            $nombre = 'Productos por Categoría';
            break;
        case 'movimientos_inventario':
            $nombre = 'Movimientos de Inventario';
            break;
    }

    // Si viene de otra parte del sistema, personalizar el nombre
    if ($origen && $datos_origen) {
        switch($origen) {
            case 'alta_producto':
                $nombre = "Alta de producto: {$datos_origen['nombre']} con stock inicial de {$datos_origen['stock']} unidades";
                break;
            case 'alta_sucursal':
                $nombre = "Alta de sucursal: {$datos_origen['nombre']}";
                break;
            // Puedes agregar más casos según necesites
        }
    }
    
    // Generar reporte y guardar en la base de datos
    $sql = "INSERT INTO Reporte (empleado_id, nombre, tipo, fecha_generacion) 
            VALUES (1, :nombre, :tipo, NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nombre,
        ':tipo' => $tipo
    ]);
    
    $reporte_id = $pdo->lastInsertId();
    
    // Si viene de otra parte del sistema y no queremos mostrar el detalle
    if ($origen && isset($_POST['redirect_back']) && $_POST['redirect_back']) {
        $_SESSION['mensaje'] = 'Reporte generado exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header("Location: " . $_POST['redirect_url']);
        exit();
    }
    
    // Si no, mostrar el detalle del reporte
    header("Location: ../consultas/reporte_detalle.php?id=" . $reporte_id);
    exit();
}

// Obtener lista de sucursales
$stmt = $pdo->query("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$sucursales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Verificar si viene de otra parte del sistema
$origen = isset($_GET['origen']) ? $_GET['origen'] : null;
$tipo_sugerido = isset($_GET['tipo']) ? $_GET['tipo'] : null;
$datos_origen = isset($_GET['datos']) ? json_decode(urldecode($_GET['datos']), true) : null;
$redirect_back = isset($_GET['redirect_back']) ? $_GET['redirect_back'] : false;
$redirect_url = isset($_GET['redirect_url']) ? $_GET['redirect_url'] : '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Reporte | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .report-types {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .report-type {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .report-type:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .report-type.selected {
            border: 2px solid #2563eb;
        }
        
        .report-type h3 {
            margin: 0 0 1rem 0;
            color: #1f2937;
        }
        
        .report-type p {
            color: #6b7280;
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="form-container">
                <h2><i class="fas fa-file-alt"></i> Generar Nuevo Reporte</h2>

                <form action="" method="POST" class="form">
                    <?php if ($origen && $datos_origen): ?>
                        <input type="hidden" name="origen" value="<?php echo htmlspecialchars($origen); ?>">
                        <input type="hidden" name="datos_origen" value="<?php echo htmlspecialchars(json_encode($datos_origen)); ?>">
                        <input type="hidden" name="redirect_back" value="<?php echo htmlspecialchars($redirect_back); ?>">
                        <input type="hidden" name="redirect_url" value="<?php echo htmlspecialchars($redirect_url); ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="tipo_reporte">Tipo de Reporte:</label>
                        <select name="tipo_reporte" id="tipo_reporte" class="form-control" required>
                            <option value="">Seleccione un tipo de reporte</option>
                            <optgroup label="Ventas">
                                <option value="ventas_diarias" <?php echo $tipo_sugerido === 'ventas_diarias' ? 'selected' : ''; ?>>Ventas Diarias</option>
                                <option value="productos_populares" <?php echo $tipo_sugerido === 'productos_populares' ? 'selected' : ''; ?>>Productos Más Vendidos</option>
                                <option value="rendimiento_sucursal" <?php echo $tipo_sugerido === 'rendimiento_sucursal' ? 'selected' : ''; ?>>Rendimiento por Sucursal</option>
                            </optgroup>
                            <optgroup label="Sucursales">
                                <option value="listado_sucursales" <?php echo $tipo_sugerido === 'listado_sucursales' ? 'selected' : ''; ?>>Listado General de Sucursales</option>
                                <option value="sucursales_ventas" <?php echo $tipo_sugerido === 'sucursales_ventas' ? 'selected' : ''; ?>>Sucursales por Volumen de Ventas</option>
                            </optgroup>
                            <optgroup label="Inventario">
                                <option value="inventario_general" <?php echo $tipo_sugerido === 'inventario_general' ? 'selected' : ''; ?>>Inventario General</option>
                                <option value="productos_stock_bajo" <?php echo $tipo_sugerido === 'productos_stock_bajo' ? 'selected' : ''; ?>>Productos con Stock Bajo</option>
                                <option value="productos_categoria" <?php echo $tipo_sugerido === 'productos_categoria' ? 'selected' : ''; ?>>Productos por Categoría</option>
                                <option value="movimientos_inventario" <?php echo $tipo_sugerido === 'movimientos_inventario' ? 'selected' : ''; ?>>Movimientos de Inventario</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i> Generar Reporte
                        </button>
                        <?php if ($redirect_back && $redirect_url): ?>
                            <a href="<?php echo htmlspecialchars($redirect_url); ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        <?php else: ?>
                            <a href="../index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    function selectReportType(type) {
        // Remover selección previa
        document.querySelectorAll('.report-type').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Seleccionar el nuevo tipo
        const selectedType = document.querySelector(`.report-type input[value="${type}"]`);
        if (selectedType) {
            selectedType.checked = true;
            selectedType.closest('.report-type').classList.add('selected');
        }
    }
    
    // Establecer fecha actual y hace un mes por defecto
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const lastMonth = new Date();
        lastMonth.setMonth(lastMonth.getMonth() - 1);
        
        document.getElementById('fecha_fin').value = today.toISOString().split('T')[0];
        document.getElementById('fecha_inicio').value = lastMonth.toISOString().split('T')[0];
    });
    </script>
</body>
</html> 