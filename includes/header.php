<?php
//Verificar sesión
/**if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}**/
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/">Sistema Gestión</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/altas/producto.php">Productos</a></li>
                    <li class="nav-item"><a class="nav-link" href="/altas/venta.php">Ventas</a></li>
                    <li class="nav-item"><a class="nav-link" href="/consultas/generales.php">Consultas</a></li>
                    <li class="nav-item"><a class="nav-link" href="/reports/ventas.php">Reportes</a></li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><span class="nav-text">Bienvenido, <?= $_SESSION['usuario_nombre'] ?></span></li>
                    <li class="nav-item"><a class="nav-link" href="/logout.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">