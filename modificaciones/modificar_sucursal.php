<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de sucursal no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit();
}

$sucursal_id = intval($_GET['id']);

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $codigo = trim($_POST['codigo_sucursal']);
        $nombre = trim($_POST['nombre']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);
        $ciudad = trim($_POST['ciudad']);
        $estado = trim($_POST['estado']);

        if (empty($codigo) || empty($nombre) || empty($direccion) || empty($telefono) || empty($ciudad) || empty($estado)) {
            throw new Exception('Todos los campos obligatorios deben ser completados.');
        }

        // Verificar si el código ya existe para otra sucursal
        $stmt = $pdo->prepare("
            SELECT sucursal_id 
            FROM Sucursal 
            WHERE codigo_sucursal = ? AND sucursal_id != ?
        ");
        $stmt->execute([$codigo, $sucursal_id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe una sucursal con este código.');
        }

        // Actualizar la sucursal
        $stmt = $pdo->prepare("
            UPDATE Sucursal 
            SET codigo_sucursal = ?,
                nombre = ?,
                direccion = ?,
                telefono = ?,
                ciudad = ?,
                estado = ?
            WHERE sucursal_id = ?
        ");

        $stmt->execute([
            $codigo,
            $nombre,
            $direccion,
            $telefono,
            $ciudad,
            $estado,
            $sucursal_id
        ]);

        $_SESSION['mensaje'] = 'Sucursal actualizada exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: ../consultas/sucursales.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

// Obtener datos de la sucursal
$stmt = $pdo->prepare("SELECT * FROM Sucursal WHERE sucursal_id = ?");
$stmt->execute([$sucursal_id]);
$sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sucursal) {
    $_SESSION['mensaje'] = 'Sucursal no encontrada.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit();
}

// Obtener estadísticas de la sucursal
$stmt = $pdo->prepare("
    SELECT 
        COUNT(v.venta_id) as num_ventas,
        COALESCE(SUM(v.total_venta), 0) as total_ventas
    FROM Venta v
    WHERE v.sucursal_id = ?
    AND v.fecha_venta >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt->execute([$sucursal_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Lista de estados de México
$estados = [
    'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
    'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
    'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
    'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
    'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Sucursal | Ferretería</title>
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
            text-decoration: none;
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
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .stat-title {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .stat-icon {
            color: #3b82f6;
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-edit"></i> Modificar Sucursal</h2>
                <a href="../consultas/sucursales.php" class="btn-secondary">
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
                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-title">Ventas (30d)</div>
                        <div class="stat-value">
                            <i class="fas fa-chart-line stat-icon"></i>
                            <?php echo number_format($stats['num_ventas']); ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Ingresos (30d)</div>
                        <div class="stat-value">
                            <i class="fas fa-dollar-sign stat-icon"></i>
                            $<?php echo number_format($stats['total_ventas'], 2); ?>
                        </div>
                    </div>
                </div>

                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="codigo_sucursal" class="required-field">Código de Sucursal</label>
                            <input type="text" id="codigo_sucursal" name="codigo_sucursal" 
                                   value="<?php echo htmlspecialchars($sucursal['codigo_sucursal']); ?>" 
                                   pattern="[A-Za-z0-9-]{3,10}"
                                   title="El código debe tener entre 3 y 10 caracteres alfanuméricos"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="nombre" class="required-field">Nombre de la Sucursal</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($sucursal['nombre']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="telefono" class="required-field">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($sucursal['telefono']); ?>" 
                                   pattern="[0-9]{10}"
                                   title="El teléfono debe contener 10 dígitos"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="estado" class="required-field">Estado</label>
                            <select name="estado" id="estado" required>
                                <?php foreach ($estados as $est): ?>
                                    <option value="<?php echo htmlspecialchars($est); ?>" 
                                            <?php echo $sucursal['estado'] === $est ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($est); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ciudad" class="required-field">Ciudad</label>
                            <input type="text" id="ciudad" name="ciudad" 
                                   value="<?php echo htmlspecialchars($sucursal['ciudad']); ?>" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="direccion" class="required-field">Dirección</label>
                            <input type="text" id="direccion" name="direccion" 
                                   value="<?php echo htmlspecialchars($sucursal['direccion']); ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/sucursales.php" class="btn-secondary">
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