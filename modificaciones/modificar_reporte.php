<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de reporte no especificado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/reportes.php');
    exit();
}

$reporte_id = intval($_GET['id']);

// Si el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre = trim($_POST['nombre']);
        $tipo = trim($_POST['tipo']);

        if (empty($nombre)) {
            throw new Exception('El nombre del reporte es requerido.');
        }

        if (empty($tipo)) {
            throw new Exception('El tipo de reporte es requerido.');
        }

        // Actualizar el reporte
        $stmt = $pdo->prepare("
            UPDATE Reporte 
            SET nombre = :nombre,
                tipo = :tipo
            WHERE reporte_id = :id
        ");

        $stmt->execute([
            ':nombre' => $nombre,
            ':tipo' => $tipo,
            ':id' => $reporte_id
        ]);

        $_SESSION['mensaje'] = 'Reporte actualizado exitosamente.';
        $_SESSION['mensaje_tipo'] = 'success';
        header('Location: ../consultas/reportes.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
        $_SESSION['mensaje_tipo'] = 'danger';
    }
}

// Obtener datos del reporte
$stmt = $pdo->prepare("SELECT * FROM Reporte WHERE reporte_id = ?");
$stmt->execute([$reporte_id]);
$reporte = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporte) {
    $_SESSION['mensaje'] = 'Reporte no encontrado.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/reportes.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Reporte | Ferretería</title>
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
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-edit"></i> Modificar Reporte</h2>
                <a href="../consultas/reportes.php" class="btn-secondary">
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
                            <label for="nombre" class="required-field">Nombre del Reporte</label>
                            <input type="text" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($reporte['nombre']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="tipo" class="required-field">Tipo de Reporte</label>
                            <select name="tipo" id="tipo" required>
                                <option value="VENTAS" <?php echo $reporte['tipo'] === 'VENTAS' ? 'selected' : ''; ?>>
                                    Ventas
                                </option>
                                <option value="INVENTARIO" <?php echo $reporte['tipo'] === 'INVENTARIO' ? 'selected' : ''; ?>>
                                    Inventario
                                </option>
                                <option value="COMPRAS" <?php echo $reporte['tipo'] === 'COMPRAS' ? 'selected' : ''; ?>>
                                    Compras
                                </option>
                                <option value="EMPLEADOS" <?php echo $reporte['tipo'] === 'EMPLEADOS' ? 'selected' : ''; ?>>
                                    Empleados
                                </option>
                                <option value="SUCURSALES" <?php echo $reporte['tipo'] === 'SUCURSALES' ? 'selected' : ''; ?>>
                                    Sucursales
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/reportes.php" class="btn-secondary">
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