<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Verificar permisos
if (!tienePermiso('editar_sucursales')) {
    $_SESSION['mensaje'] = 'No tienes permisos para realizar esta acción.';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit;
}

// Verificar ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de sucursal no válido';
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit;
}

$sucursal_id = intval($_GET['id']);

try {
    // Obtener datos de la sucursal
    $stmt = $pdo->prepare("SELECT * FROM Sucursal WHERE sucursal_id = :id");
    $stmt->execute([':id' => $sucursal_id]);
    $sucursal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sucursal) {
        throw new Exception('Sucursal no encontrada');
    }

    // Lista de estados para el select
    $estados = [
        'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
        'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
        'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
        'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
        'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
    ];

} catch (Exception $e) {
    $_SESSION['mensaje'] = 'Error: ' . $e->getMessage();
    $_SESSION['mensaje_tipo'] = 'danger';
    header('Location: ../consultas/sucursales.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Sucursal | Ferretería</title>
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
        #confirmDelete {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="form-container">
                <h2><i class="fas fa-edit"></i> Editar Sucursal</h2>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?>">
                        <?= $_SESSION['mensaje'] ?>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                <?php endif; ?>

                <form action="procesar_actualizacion_sucursal.php" method="POST" id="formSucursal">
                    <input type="hidden" name="sucursal_id" value="<?= $sucursal['sucursal_id'] ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="codigo_sucursal">Código de Sucursal *</label>
                            <input type="text" class="form-control" id="codigo_sucursal" name="codigo_sucursal" 
                                   value="<?= htmlspecialchars($sucursal['codigo_sucursal']) ?>"
                                   required pattern="[A-Za-z0-9]{3,10}" 
                                   title="El código debe tener entre 3 y 10 caracteres alfanuméricos">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre de la Sucursal *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($sucursal['nombre']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="direccion">Dirección *</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" 
                               value="<?= htmlspecialchars($sucursal['direccion']) ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   value="<?= htmlspecialchars($sucursal['telefono']) ?>"
                                   required pattern="[0-9]{10}" title="Ingrese un número de 10 dígitos">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ciudad">Ciudad *</label>
                            <input type="text" class="form-control" id="ciudad" name="ciudad" 
                                   value="<?= htmlspecialchars($sucursal['ciudad']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estado">Estado *</label>
                            <select class="form-control" id="estado" name="estado" required>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= htmlspecialchars($estado) ?>" 
                                            <?= $sucursal['estado'] === $estado ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($estado) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                        <a href="../consultas/sucursal_detalle.php?id=<?= $sucursal['sucursal_id'] ?>" 
                           class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="button" class="btn-danger" onclick="confirmarEliminacion()">
                            <i class="fas fa-trash"></i> Eliminar Sucursal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="overlay" id="overlay"></div>
    <div id="confirmDelete">
        <h3>¿Estás seguro?</h3>
        <p>Esta acción eliminará la sucursal permanentemente.</p>
        <form action="eliminar_sucursal.php" method="POST">
            <input type="hidden" name="sucursal_id" value="<?= $sucursal['sucursal_id'] ?>">
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash"></i> Sí, Eliminar
            </button>
            <button type="button" class="btn btn-secondary" style="margin-left: 1rem;"
                    onclick="ocultarConfirmacion()">
                Cancelar
            </button>
        </form>
    </div>

    <script>
        document.getElementById('formSucursal').addEventListener('submit', function(e) {
            const telefono = document.getElementById('telefono').value;
            const codigo = document.getElementById('codigo_sucursal').value;
            
            if (!/^[0-9]{10}$/.test(telefono)) {
                e.preventDefault();
                alert('El teléfono debe contener exactamente 10 dígitos');
                return;
            }
            
            if (!/^[A-Za-z0-9]{3,10}$/.test(codigo)) {
                e.preventDefault();
                alert('El código de sucursal debe tener entre 3 y 10 caracteres alfanuméricos');
                return;
            }
        });

        // Formatear teléfono mientras se escribe
        document.getElementById('telefono').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        // Formatear código de sucursal mientras se escribe
        document.getElementById('codigo_sucursal').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-z0-9]/g, '').slice(0, 10);
        });

        function mostrarConfirmacion() {
            document.getElementById('overlay').style.display = 'block';
            document.getElementById('confirmDelete').style.display = 'block';
        }

        function ocultarConfirmacion() {
            document.getElementById('overlay').style.display = 'none';
            document.getElementById('confirmDelete').style.display = 'none';
        }
    </script>
</body>
</html> 