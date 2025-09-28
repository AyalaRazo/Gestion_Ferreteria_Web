<?php
require_once '../includes/db.php';

// Obtener estados para el select
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
    <title>Registro de Sucursal | Ferretería</title>
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
                <h2><i class="fas fa-store"></i> Registro de Nueva Sucursal</h2>
                
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?>">
                        <?= $_SESSION['mensaje'] ?>
                    </div>
                    <?php 
                    unset($_SESSION['mensaje']);
                    unset($_SESSION['mensaje_tipo']);
                    ?>
                <?php endif; ?>

                <form action="procesar_alta_sucursal.php" method="POST" id="formSucursal">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="codigo_sucursal">Código de Sucursal *</label>
                            <input type="text" class="form-control" id="codigo_sucursal" name="codigo_sucursal" 
                                   required pattern="[A-Za-z0-9]{3,10}" 
                                   title="El código debe tener entre 3 y 10 caracteres alfanuméricos">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="nombre">Nombre de la Sucursal *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="direccion">Dirección *</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="telefono">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" 
                                   required pattern="[0-9]{10}" title="Ingrese un número de 10 dígitos">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="ciudad">Ciudad *</label>
                            <input type="text" class="form-control" id="ciudad" name="ciudad" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="estado">Estado *</label>
                            <select class="form-control" id="estado" name="estado" required>
                                <option value="">Seleccione un estado</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?= htmlspecialchars($estado) ?>">
                                        <?= htmlspecialchars($estado) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Guardar Sucursal
                        </button>
                        <a href="../index.php" class="btn btn-secondary" style="margin-left: 1rem;">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
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
    </script>
</body>
</html> 