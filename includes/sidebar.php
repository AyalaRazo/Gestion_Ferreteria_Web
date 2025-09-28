<?php
// Determinar la profundidad de la ruta actual
$depth = substr_count(trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'), '/');
$base_path = str_repeat('../', max(0, $depth - 1));
if ($depth <= 1) $base_path = './';
?>
<div class="sidebar">
    <div class="sidebar-brand">
        <h2><i class="fas fa-store-alt"></i> <span>Ferretería</span></h2>
    </div>
    
    <ul class="sidebar-menu">
        <li>
            <a href="<?= $base_path ?>" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'producto') !== false ? 'active' : '' ?>">
                <i class="fas fa-boxes"></i>
                <span>Productos</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'producto') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/producto.php">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Producto</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/productos.php">
                        <i class="fas fa-list"></i>
                        <span>Lista de Productos</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'sucursal') !== false ? 'active' : '' ?>">
                <i class="fas fa-store"></i>
                <span>Sucursales</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'sucursal') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/sucursal.php">
                        <i class="fas fa-plus"></i>
                        <span>Nueva Sucursal</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/sucursales.php">
                        <i class="fas fa-list"></i>
                        <span>Lista de Sucursales</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>altas/transferencia.php">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Nueva Transferencia</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/transferencias.php">
                        <i class="fas fa-truck-loading"></i>
                        <span>Transferencias</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'venta') !== false ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>Ventas</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'venta') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/venta.php">
                        <i class="fas fa-plus"></i>
                        <span>Nueva Venta</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/ventas.php">
                        <i class="fas fa-list"></i>
                        <span>Lista de Ventas</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'proveedor') !== false ? 'active' : '' ?>">
                <i class="fas fa-truck"></i>
                <span>Proveedores</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'proveedor') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/proveedor.php">
                        <i class="fas fa-plus"></i>
                        <span>Nuevo Proveedor</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/proveedores.php">
                        <i class="fas fa-list"></i>
                        <span>Lista de Proveedores</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'orden_compra') !== false ? 'active' : '' ?>">
                <i class="fas fa-shopping-basket"></i>
                <span>Órdenes de Compra</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'orden_compra') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/orden_compra.php">
                        <i class="fas fa-plus"></i>
                        <span>Nueva Orden</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/ordenes_compra.php">
                        <i class="fas fa-list"></i>
                        <span>Lista de Órdenes</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="#" class="<?= strpos($_SERVER['REQUEST_URI'], 'reporte') !== false ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Reportes</span>
            </a>
            <ul class="submenu <?= strpos($_SERVER['REQUEST_URI'], 'reporte') !== false ? 'show' : '' ?>">
                <li>
                    <a href="<?= $base_path ?>altas/reporte.php">
                        <i class="fas fa-file-alt"></i>
                        <span>Generar Reporte</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base_path ?>consultas/reportes.php">
                        <i class="fas fa-list-alt"></i>
                        <span>Lista de Reportes</span>
                    </a>
                </li>
            </ul>
        </li>
        <li>
            <a href="<?= $base_path ?>admin/config.php" class="<?= strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>
    </ul>
</div>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    width: 250px;
    background: #2c3e50;
    color: white;
    overflow-y: auto;
    z-index: 1000;
    transition: all 0.3s ease;
}

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.1);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}

.sidebar-brand {
    padding: 1rem 1.5rem;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-brand h2 {
    color: white;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.sidebar-menu {
    list-style: none;
    padding: 1rem 0;
    margin: 0;
}

.sidebar-menu li {
    margin-bottom: 0.25rem;
}

.sidebar-menu a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
    gap: 0.75rem;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: rgba(255,255,255,0.1);
    color: white;
}

.submenu {
    display: none;
    background: rgba(0,0,0,0.1);
    padding-left: 1rem;
    list-style: none;
    margin: 0;
}

.submenu.show {
    display: block;
}

.submenu a {
    padding: 0.5rem 1.5rem;
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    
    .sidebar-overlay.active {
        display: block;
    }
}
</style>

<div class="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejar los menús desplegables
    const menuItems = document.querySelectorAll('.sidebar-menu > li > a');
    menuItems.forEach(item => {
        if (item.getAttribute('href') === '#') {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const submenu = this.nextElementSibling;
                if (submenu && submenu.classList.contains('submenu')) {
                    submenu.classList.toggle('show');
                }
            });
        }
    });
    
    // Manejar el overlay en dispositivos móviles
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (overlay) {
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            this.classList.remove('active');
        });
    }
    
    // Agregar botón de menú móvil si no existe
    if (!document.querySelector('.mobile-menu-btn')) {
        const mobileBtn = document.createElement('button');
        mobileBtn.className = 'mobile-menu-btn';
        mobileBtn.innerHTML = '<i class="fas fa-bars"></i>';
        document.body.appendChild(mobileBtn);
        
        mobileBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }
});
</script>