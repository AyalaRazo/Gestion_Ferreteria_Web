<?php
session_start();
require_once '../includes/db.php';

$transferencia_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transferencia_id <= 0) {
    $_SESSION['mensaje'] = "ID de transferencia no válido.";
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: transferencias.php");
    exit;
}

try {
    // Obtener datos de la transferencia
    $stmt = $pdo->prepare("
        SELECT 
            tp.*,
            so.nombre as sucursal_origen,
            so.direccion as origen_direccion,
            so.telefono as origen_telefono,
            sd.nombre as sucursal_destino,
            sd.direccion as destino_direccion,
            sd.telefono as destino_telefono,
            e.nombre as empleado_nombre
        FROM transferencia_producto tp
        JOIN Sucursal so ON tp.sucursal_origen_id = so.sucursal_id
        JOIN Sucursal sd ON tp.sucursal_destino_id = sd.sucursal_id
        JOIN Empleado e ON tp.empleado_id = e.empleado_id
        WHERE tp.transferencia_producto_id = :id
    ");
    $stmt->execute([':id' => $transferencia_id]);
    $transferencia = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transferencia) {
        throw new Exception("Transferencia no encontrada.");
    }

    // Obtener detalles de la transferencia
    $stmt = $pdo->prepare("
        SELECT 
            dt.*,
            p.codigo_producto,
            p.nombre_producto
        FROM detalles_transferencia dt
        JOIN Producto p ON dt.producto_id = p.producto_id
        WHERE dt.transferencia_producto_id = :id
    ");
    $stmt->execute([':id' => $transferencia_id]);
    $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $_SESSION['mensaje'] = "Error: " . $e->getMessage();
    $_SESSION['mensaje_tipo'] = "error";
    header("Location: transferencias.php");
    exit;
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_estado'])) {
    try {
        $pdo->beginTransaction();

        $nuevo_estado = $_POST['nuevo_estado'];
        $fecha_campo = '';

        if ($nuevo_estado === 'EN_TRANSITO') {
            $fecha_campo = 'fecha_envio = NOW()';
        } elseif ($nuevo_estado === 'RECIBIDA') {
            $fecha_campo = 'fecha_recibido = NOW()';
            
            // Obtener los detalles de la transferencia
            $stmt = $pdo->prepare("
                SELECT 
                    dt.producto_id,
                    dt.cantidad,
                    tp.sucursal_origen_id,
                    tp.sucursal_destino_id
                FROM detalles_transferencia dt
                JOIN transferencia_producto tp ON dt.transferencia_producto_id = tp.transferencia_producto_id
                WHERE dt.transferencia_producto_id = :id
            ");
            $stmt->execute([':id' => $transferencia_id]);
            $detalles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Actualizar el inventario para cada producto
            foreach ($detalles as $detalle) {
                // Restar del inventario de origen
                $stmt = $pdo->prepare("
                    UPDATE Inventario 
                    SET 
                        stock_actual = stock_actual - :cantidad,
                        ultima_actualizacion = NOW()
                    WHERE producto_id = :producto_id 
                    AND sucursal_id = :sucursal_origen_id
                ");
                $stmt->execute([
                    ':cantidad' => $detalle['cantidad'],
                    ':producto_id' => $detalle['producto_id'],
                    ':sucursal_origen_id' => $detalle['sucursal_origen_id']
                ]);

                // Verificar si el producto ya existe en el inventario de destino
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM Inventario 
                    WHERE producto_id = :producto_id 
                    AND sucursal_id = :sucursal_destino_id
                ");
                $stmt->execute([
                    ':producto_id' => $detalle['producto_id'],
                    ':sucursal_destino_id' => $detalle['sucursal_destino_id']
                ]);
                $existe = $stmt->fetchColumn();

                if ($existe) {
                    // Actualizar el inventario existente
                    $stmt = $pdo->prepare("
                        UPDATE Inventario 
                        SET 
                            stock_actual = stock_actual + :cantidad,
                            ultima_actualizacion = NOW()
                        WHERE producto_id = :producto_id 
                        AND sucursal_id = :sucursal_destino_id
                    ");
                } else {
                    // Obtener información del inventario de origen para los valores de stock mínimo y máximo
                    $stmt = $pdo->prepare("
                        SELECT stock_minimo, stock_maximo 
                        FROM Inventario 
                        WHERE producto_id = :producto_id 
                        AND sucursal_id = :sucursal_origen_id
                    ");
                    $stmt->execute([
                        ':producto_id' => $detalle['producto_id'],
                        ':sucursal_origen_id' => $detalle['sucursal_origen_id']
                    ]);
                    $inventario_origen = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Crear nuevo registro de inventario
                    $stmt = $pdo->prepare("
                        INSERT INTO Inventario (
                            producto_id, 
                            sucursal_id, 
                            stock_actual, 
                            stock_minimo, 
                            stock_maximo, 
                            ultima_actualizacion
                        ) VALUES (
                            :producto_id,
                            :sucursal_destino_id,
                            :cantidad,
                            :stock_minimo,
                            :stock_maximo,
                            NOW()
                        )
                    ");
                    $stmt->execute([
                        ':producto_id' => $detalle['producto_id'],
                        ':sucursal_destino_id' => $detalle['sucursal_destino_id'],
                        ':cantidad' => $detalle['cantidad'],
                        ':stock_minimo' => $inventario_origen['stock_minimo'],
                        ':stock_maximo' => $inventario_origen['stock_maximo']
                    ]);
                    continue;
                }

                $stmt->execute([
                    ':cantidad' => $detalle['cantidad'],
                    ':producto_id' => $detalle['producto_id'],
                    ':sucursal_destino_id' => $detalle['sucursal_destino_id']
                ]);
            }
        } elseif ($nuevo_estado === 'CANCELADA') {
            $fecha_campo = 'fecha_cancelado = NOW()';
        }

        $sql = "
            UPDATE transferencia_producto 
            SET estado = :estado" . 
            ($fecha_campo ? ", $fecha_campo" : "") . 
            " WHERE transferencia_producto_id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $resultado = $stmt->execute([
            ':estado' => $nuevo_estado,
            ':id' => $transferencia_id
        ]);

        if ($resultado) {
            $pdo->commit();
            $_SESSION['mensaje'] = "Estado de la transferencia actualizado exitosamente.";
            $_SESSION['mensaje_tipo'] = "success";
            header("Location: transferencia_detalle.php?id=" . $transferencia_id);
            exit;
        }
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
    <title>Detalles de Transferencia | Ferretería</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .transfer-details {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            padding: 1.5rem;
        }
        .transfer-header {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .transfer-info h3 {
            font-size: 0.875rem;
            color: #6b7280;
            margin-bottom: 0.5rem;
        }
        .transfer-info p {
            font-size: 1rem;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }
        .estado-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-enviado { background: #dbeafe; color: #1e40af; }
        .estado-recibido { background: #d1fae5; color: #065f46; }
        .estado-cancelado { background: #fee2e2; color: #991b1b; }
        .table-responsive {
            margin-top: 1rem;
            overflow-x: auto;
        }
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .table th {
            background: #f8fafc;
            padding: 1rem;
            font-weight: 600;
            text-align: left;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
        }
        .table td {
            padding: 1rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }
        .table tbody tr:hover {
            background-color: #f9fafb;
        }
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        .estado-form {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .estado-form select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-right: 1rem;
        }
    </style>
</head>
<body class="dashboard-body">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include '../includes/topbar.php'; ?>
        
        <div class="page-content">
            <div class="header-actions">
                <h2><i class="fas fa-exchange-alt"></i> Detalles de Transferencia</h2>
                <a href="transferencias.php" class="btn-secondary">
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

            <div class="transfer-details">
                <div class="transfer-header">
                    <div class="transfer-info">
                        <h3>Información de la Transferencia</h3>
                        <p><strong>ID:</strong> <?php echo $transferencia['transferencia_producto_id']; ?></p>
                        <p><strong>Fecha Solicitud:</strong> <?php echo date('d/m/Y', strtotime($transferencia['fecha_solicitud'])); ?></p>
                        <?php if ($transferencia['fecha_envio']): ?>
                            <p><strong>Fecha Envío:</strong> <?php echo date('d/m/Y', strtotime($transferencia['fecha_envio'])); ?></p>
                        <?php endif; ?>
                        <?php if ($transferencia['fecha_recibido']): ?>
                            <p><strong>Fecha Recibido:</strong> <?php echo date('d/m/Y', strtotime($transferencia['fecha_recibido'])); ?></p>
                        <?php endif; ?>
                        <p><strong>Estado:</strong> 
                            <span class="estado-badge estado-<?php echo strtolower($transferencia['estado']); ?>">
                                <?php echo $transferencia['estado']; ?>
                            </span>
                        </p>
                    </div>
                    <div class="transfer-info">
                        <h3>Sucursal de Origen</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($transferencia['sucursal_origen']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($transferencia['origen_direccion']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($transferencia['origen_telefono']); ?></p>
                    </div>
                    <div class="transfer-info">
                        <h3>Sucursal de Destino</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($transferencia['sucursal_destino']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($transferencia['destino_direccion']); ?></p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($transferencia['destino_telefono']); ?></p>
                    </div>
                    <div class="transfer-info">
                        <h3>Empleado</h3>
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($transferencia['empleado_nombre']); ?></p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detalles as $detalle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($detalle['codigo_producto']); ?></td>
                                    <td><?php echo htmlspecialchars($detalle['nombre_producto']); ?></td>
                                    <td><?php echo $detalle['cantidad']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($transferencia['estado'] === 'SOLICITADA'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="PREPARACION">Marcar en Preparación</option>
                                <option value="RECHAZADA">Rechazar Transferencia</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php elseif ($transferencia['estado'] === 'PREPARACION'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="EN_TRANSITO">Marcar En Tránsito</option>
                                <option value="CANCELADA">Cancelar Transferencia</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php elseif ($transferencia['estado'] === 'EN_TRANSITO'): ?>
                    <div class="estado-form">
                        <form method="POST" action="" class="d-flex align-items-center">
                            <select name="nuevo_estado" class="form-control">
                                <option value="RECIBIDA">Marcar como Recibida</option>
                            </select>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Actualizar Estado
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 