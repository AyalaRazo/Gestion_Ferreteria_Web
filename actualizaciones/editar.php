<?php
include '../includes/db.php';
$id = $_GET['id'];
$tabla = $_GET['tabla'];

$stmt = $pdo->prepare("SELECT * FROM $tabla WHERE ".$tabla."_id = ?");
$stmt->execute([$id]);
$registro = $stmt->fetch();

// Obtener los nombres de las columnas
$stmt = $pdo->prepare("DESCRIBE $tabla");
$stmt->execute();
$columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar <?php echo ucfirst($tabla); ?> | Ferretería</title>
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
            text-transform: capitalize;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.15s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #3b82f6;
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-group textarea {
            height: 120px;
            resize: vertical;
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
                <h2><i class="fas fa-edit"></i> Editar <?php echo ucfirst($tabla); ?></h2>
                <a href="../consultas/<?php echo strtolower($tabla); ?>.php" class="btn-secondary">
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
                <form action="procesar_edicion.php" method="post" class="form">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="tabla" value="<?php echo $tabla; ?>">
                    
                    <div class="form-grid">
                        <?php foreach ($columnas as $columna): ?>
                            <?php 
                            $campo = $columna['Field'];
                            if ($campo != $tabla.'_id'): 
                                $tipo = $columna['Type'];
                                $requerido = $columna['Null'] === 'NO';
                                $valor = isset($registro[$campo]) ? $registro[$campo] : '';
                            ?>
                                <div class="form-group <?php echo (strpos($tipo, 'text') !== false) ? 'full-width' : ''; ?>">
                                    <label for="<?php echo $campo; ?>" <?php echo $requerido ? 'class="required-field"' : ''; ?>>
                                        <?php echo str_replace('_', ' ', $campo); ?>
                                    </label>
                                    <?php if (strpos($tipo, 'text') !== false): ?>
                                        <textarea id="<?php echo $campo; ?>" name="<?php echo $campo; ?>" 
                                                <?php echo $requerido ? 'required' : ''; ?>><?php echo htmlspecialchars($valor); ?></textarea>
                                    <?php elseif (strpos($tipo, 'enum') !== false): ?>
                                        <?php
                                        preg_match("/^enum\((.*)\)$/", $tipo, $matches);
                                        $opciones = str_getcsv($matches[1], ',', "'");
                                        ?>
                                        <select id="<?php echo $campo; ?>" name="<?php echo $campo; ?>" 
                                                <?php echo $requerido ? 'required' : ''; ?>>
                                            <?php foreach ($opciones as $opcion): ?>
                                                <option value="<?php echo htmlspecialchars($opcion); ?>" 
                                                        <?php echo $valor === $opcion ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($opcion); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?php echo strpos($tipo, 'int') !== false ? 'number' : 
                                                         (strpos($tipo, 'decimal') !== false ? 'number' : 
                                                         (strpos($tipo, 'date') !== false ? 'date' : 'text')); ?>" 
                                               id="<?php echo $campo; ?>" 
                                               name="<?php echo $campo; ?>" 
                                               value="<?php echo htmlspecialchars($valor); ?>"
                                               <?php echo $requerido ? 'required' : ''; ?>
                                               <?php echo strpos($tipo, 'decimal') !== false ? 'step="0.01"' : ''; ?>>
                                    <?php endif; ?>
                                </div>
        <?php endif; ?>
    <?php endforeach; ?>
                    </div>

                    <div class="form-actions">
                        <a href="../consultas/<?php echo strtolower($tabla); ?>.php" class="btn-secondary">
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