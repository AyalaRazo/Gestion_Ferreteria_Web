<?php
session_start();

// Verificar autenticación
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Verificar permisos
function verificarPermiso($rolRequerido) {
    if ($_SESSION['usuario_rol'] != $rolRequerido && $_SESSION['usuario_rol'] != 'ADMIN') {
        header('Location: /acceso-denegado.php');
        exit;
    }
}
?>