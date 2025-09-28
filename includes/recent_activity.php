<?php
// Simulación de actividad reciente (en un sistema real esto vendría de la BD)
$actividades = [
    [
        'icon' => 'fas fa-cash-register',
        'titulo' => 'Nueva venta registrada',
        'descripcion' => 'Venta #456 por $1,250.00',
        'tiempo' => 'Hace 15 min'
    ],
    [
        'icon' => 'fas fa-box',
        'titulo' => 'Producto agregado',
        'descripcion' => 'Nuevo producto "Monitor LED 24""',
        'tiempo' => 'Hace 2 horas'
    ],
    [
        'icon' => 'fas fa-user',
        'titulo' => 'Nuevo empleado',
        'descripcion' => 'Carlos Martínez registrado',
        'tiempo' => 'Ayer'
    ],
    [
        'icon' => 'fas fa-chart-line',
        'titulo' => 'Reporte generado',
        'descripcion' => 'Reporte de ventas mensual',
        'tiempo' => 'Ayer'
    ]
];

foreach ($actividades as $actividad): ?>
    <div class="activity-item">
        <div class="activity-icon">
            <i class="<?= $actividad['icon'] ?>"></i>
        </div>
        <div class="activity-content">
            <h4><?= $actividad['titulo'] ?></h4>
            <p><?= $actividad['descripcion'] ?></p>
        </div>
        <div class="activity-time">
            <?= $actividad['tiempo'] ?>
        </div>
    </div>
<?php endforeach; ?>