<?php
// Estadísticas rápidas para el dashboard
try {
    // Total productos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Producto");
    $totalProductos = $stmt->fetch()['total'];
    
    // Ventas hoy
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Venta WHERE DATE(fecha_venta) = CURDATE()");
    $ventasHoy = $stmt->fetch()['total'];
    
    // Productos bajo stock mínimo
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Inventario WHERE stock_actual < stock_minimo");
    $bajoStock = $stmt->fetch()['total'];
} catch (PDOException $e) {
    // Silenciar errores para no romper la página principal
    $totalProductos = $ventasHoy = $bajoStock = 'N/A';
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Productos</h3>
            <p><?= $totalProductos ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Ventas Hoy</h3>
            <p><?= $ventasHoy ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <h3>Bajo Stock</h3>
            <p><?= $bajoStock ?></p>
        </div>
    </div>
</div>