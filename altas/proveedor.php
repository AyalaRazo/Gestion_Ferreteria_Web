<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Proveedor (nombre, telefono, email, direccion)
            VALUES (:nombre, :telefono, :email, :direccion)
        ");

        $resultado = $stmt->execute([
            ':nombre' => $_POST['nombre'],
            ':telefono' => $_POST['telefono'],
            ':email' => $_POST['email'],
            ':direccion' => $_POST['direccion']
        ]);

        if ($resultado) {
            $_SESSION['mensaje'] = "Proveedor agregado exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
        } else {
            throw new Exception("Error al agregar el proveedor.");
        }

        header("Location: ../consultas/proveedores.php");
        exit;
    } catch (Exception $e) {
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
    <title>Nuevo Proveedor | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
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
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        .btn-container {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-truck"></i> Nuevo Proveedor</h2>
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
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre">Nombre del Proveedor *</label>
                        <input type="text" id="nombre" name="nombre" required 
                               placeholder="Ingrese el nombre del proveedor">
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono *</label>
                        <input type="tel" id="telefono" name="telefono" required 
                               placeholder="Ingrese el teléfono">
                    </div>

                    <div class="form-group">
                        <label for="email">Correo Electrónico *</label>
                        <input type="email" id="email" name="email" required 
                               placeholder="Ingrese el correo electrónico">
                    </div>

                    <div class="form-group">
                        <label for="direccion">Dirección</label>
                        <textarea id="direccion" name="direccion" 
                                  placeholder="Ingrese la dirección completa"></textarea>
                    </div>

                    <div class="btn-container">
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 