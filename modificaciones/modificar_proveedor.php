<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de proveedor no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/proveedores.php');
    exit();
}

$proveedor_id = intval($_GET['id']);

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre']);
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $direccion = trim($_POST['direccion']);

        if (empty($nombre) || empty($telefono) || empty($email)) {
            throw new Exception('Los campos nombre, teléfono y email son obligatorios.');
        }

        // Verificar si el email ya existe para otro proveedor
        $stmt = $pdo->prepare("
            SELECT proveedor_id 
            FROM Proveedor 
            WHERE email = ? AND proveedor_id != ?
        ");
        $stmt->execute([$email, $proveedor_id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe un proveedor con este email.');
        }

        // Actualizar el proveedor
        $stmt = $pdo->prepare("
            UPDATE Proveedor 
            SET nombre = ?,
                telefono = ?,
                email = ?,
                direccion = ?
            WHERE proveedor_id = ?
        ");

        $stmt->execute([
            $nombre,
            $telefono,
            $email,
            $direccion,
            $proveedor_id
        ]);

        $_SESSION['mensaje'] = 'Proveedor actualizado exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: ../consultas/proveedores.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

// Obtener datos del proveedor
$stmt = $pdo->prepare("SELECT * FROM Proveedor WHERE proveedor_id = ?");
$stmt->execute([$proveedor_id]);
$proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proveedor) {
    $_SESSION['mensaje'] = 'Proveedor no encontrado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/proveedores.php');
    exit();
}

// Obtener estadísticas del proveedor
$stmt = $pdo->prepare("
    SELECT 
        COUNT(oc.orden_compra_id) as total_ordenes,
        COALESCE(SUM(oc.total), 0) as total_compras,
        COUNT(CASE WHEN oc.estado = 'PENDIENTE' THEN 1 END) as ordenes_pendientes
    FROM orden_compra oc
    WHERE oc.proveedor_id = ?
    AND oc.fecha_orden >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
");
$stmt->execute([$proveedor_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Proveedor | Ferretería</title>
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
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.15s ease;
        }
        .form-group input:focus {
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
                <h2><i class="fas fa-edit"></i> Modificar Proveedor</h2>
                <a href="../consultas/proveedores.php" class="btn-secondary">
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
                        <div class="stat-title">Órdenes (30d)</div>
                        <div class="stat-value">
                            <i class="fas fa-shopping-cart stat-icon"></i>
                            <?php echo number_format($stats['total_ordenes']); ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Total Compras (30d)</div>
                        <div class="stat-value">
                            <i class="fas fa-dollar-sign stat-icon"></i>
                            $<?php echo number_format($stats['total_compras'], 2); ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-title">Órdenes Pendientes</div>
                        <div class="stat-value">
                            <i class="fas fa-clock stat-icon"></i>
                            <?php echo number_format($stats['ordenes_pendientes']); ?>
                        </div>
                    </div>
                </div>

                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nombre" class="required-field">Nombre</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($proveedor['nombre']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="telefono" class="required-field">Teléfono</label>
                            <input type="tel" id="telefono" name="telefono" 
                                   value="<?php echo htmlspecialchars($proveedor['telefono']); ?>" 
                                   pattern="[0-9]{10}"
                                   title="El teléfono debe contener 10 dígitos"
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="required-field">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($proveedor['email']); ?>" 
                                   required>
                        </div>

                        <div class="form-group full-width">
                            <label for="direccion">Dirección</label>
                            <input type="text" id="direccion" name="direccion" 
                                   value="<?php echo htmlspecialchars($proveedor['direccion']); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/proveedores.php" class="btn-secondary">
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