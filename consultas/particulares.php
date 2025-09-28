<?php include '../includes/header.php'; ?>
<h2>Consultas Particulares</h2>

<div class="consulta">
    <h3>1. Productos con precio mayor a $100</h3>
    <?php
    include '../includes/db.php';
    $stmt = $pdo->query("SELECT nombre, precio FROM Producto WHERE precio > 100 ORDER BY precio DESC");
    $resultados = $stmt->fetchAll();
    // Mostrar resultados...
    ?>
</div>

<div class="consulta">
    <h3>2. Ventas por sucursal</h3>
    <?php
    $stmt = $pdo->query("SELECT s.nombre, COUNT(v.venta_id) as total_ventas 
                         FROM Venta v JOIN Sucursal s ON v.id_sucursal = s.sucursal_id 
                         GROUP BY s.nombre");
    // Mostrar resultados...
    ?>
</div>

<!-- Más consultas según álgebra relacional -->
<?php include '../includes/footer.php'; ?>