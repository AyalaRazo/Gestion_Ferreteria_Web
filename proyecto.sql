-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 28-09-2025 a las 08:40:23
-- Versión del servidor: 8.0.17
-- Versión de PHP: 7.3.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `proyecto`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `config_interfaz`
--

CREATE TABLE `config_interfaz` (
  `empleado_id` int(11) NOT NULL,
  `nombre_paleta` varchar(50) NOT NULL DEFAULT 'CLARO',
  `codigo_colores` json NOT NULL,
  `fecha_modificacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_orden_compra`
--

CREATE TABLE `detalles_orden_compra` (
  `orden_compra_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `total_particular` decimal(12,2) GENERATED ALWAYS AS ((`cantidad` * `precio_unitario`)) STORED,
  `fecha_entrega` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_transferencia`
--

CREATE TABLE `detalles_transferencia` (
  `transferencia_producto_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_venta`
--

CREATE TABLE `detalles_venta` (
  `venta_id` int(11) NOT NULL,
  `producto_id` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `descuento_total` decimal(10,2) DEFAULT '0.00',
  `total_particular` decimal(12,2) GENERATED ALWAYS AS (((`cantidad` * `precio_unitario`) - `descuento_total`)) STORED
) ;

--
-- Volcado de datos para la tabla `detalles_venta`
--

INSERT INTO `detalles_venta` (`venta_id`, `producto_id`, `cantidad`, `precio_unitario`, `descuento_total`) VALUES
(5, 18, 1, '89.00', '0.00'),
(5, 19, 2, '35.00', '0.00'),
(7, 19, 5, '35.00', '0.00'),
(9, 20, 3, '6.50', '0.00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleado`
--

CREATE TABLE `empleado` (
  `empleado_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido_paterno` varchar(50) NOT NULL,
  `apellido_materno` varchar(50) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `sucursal_id` int(11) NOT NULL,
  `fecha_contratacion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `empleado`
--

INSERT INTO `empleado` (`empleado_id`, `nombre`, `apellido_paterno`, `apellido_materno`, `telefono`, `correo`, `sucursal_id`, `fecha_contratacion`) VALUES
(1, 'Julio Cesar', 'Ayala', 'Razo', '6861234567', 'julio.razo@uabc.edu.mx', 1, '2025-05-19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario`
--

CREATE TABLE `inventario` (
  `producto_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `stock_actual` int(11) NOT NULL DEFAULT '0',
  `stock_minimo` int(11) NOT NULL DEFAULT '1',
  `stock_maximo` int(11) NOT NULL DEFAULT '100',
  `ultima_actualizacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `inventario`
--

INSERT INTO `inventario` (`producto_id`, `sucursal_id`, `stock_actual`, `stock_minimo`, `stock_maximo`) VALUES
(18, 1, 15, 10, 50),
(19, 1, 20, 15, 50),
(20, 1, 27, 25, 60),
(23, 1, 0, 1, 10);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `orden_compra`
--

CREATE TABLE `orden_compra` (
  `orden_compra_id` int(11) NOT NULL,
  `proveedor_id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha_orden` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total` decimal(12,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PENDIENTE'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pago`
--

CREATE TABLE `pago` (
  `pago_id` int(11) NOT NULL,
  `venta_id` int(11) NOT NULL,
  `referencia` varchar(50) DEFAULT NULL,
  `metodo_pago` varchar(30) NOT NULL,
  `monto_pago` decimal(12,2) NOT NULL,
  `fecha_pago` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Volcado de datos para la tabla `pago`
--

INSERT INTO `pago` (`pago_id`, `venta_id`, `referencia`, `metodo_pago`, `monto_pago`, `fecha_pago`) VALUES
(4, 5, 'VENTA-5', 'EFECTIVO', '159.00', '2025-06-06 09:32:09'),
(5, 7, 'VENTA-7', 'EFECTIVO', '175.00', '2025-06-06 09:33:30'),
(7, 9, 'VENTA-9', 'EFECTIVO', '19.50', '2025-06-06 09:48:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `producto_id` int(11) NOT NULL,
  `codigo_producto` varchar(20) NOT NULL,
  `nombre_producto` varchar(100) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`producto_id`, `codigo_producto`, `nombre_producto`, `precio`, `categoria`, `activo`) VALUES
(18, 'HRR-001', 'Martillo de uña 16 oz', '89.00', 'Herramientas manuales', 1),
(19, 'HRR-002', 'Desarmador plano 6\"', '35.00', 'Herramientas manuales', 1),
(20, 'FST-001', 'Teflón para rosca 1/2\" x 10 m', '6.50', 'Fontanería', 1),
(23, '2198589', 'Taladro Eléctrico Inalámbrico', '1200.00', 'Herramientas eléctricas', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_promocion`
--

CREATE TABLE `producto_promocion` (
  `producto_id` int(11) NOT NULL,
  `promocion_id` int(11) NOT NULL,
  `porcentaje_descuento` decimal(5,2) DEFAULT '0.00',
  `precio_promocional` decimal(10,2) DEFAULT NULL,
  `stock_promocional` int(11) DEFAULT NULL,
  `limite_por_cliente` int(11) DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `promocion`
--

CREATE TABLE `promocion` (
  `promocion_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(30) NOT NULL,
  `descripcion` text,
  `fecha_inicio` datetime NOT NULL,
  `fecha_fin` datetime NOT NULL,
  `condiciones` text,
  `activo` tinyint(1) NOT NULL DEFAULT '1'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `proveedor`
--

CREATE TABLE `proveedor` (
  `proveedor_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(200) DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `proveedor`
--

INSERT INTO `proveedor` (`proveedor_id`, `nombre`, `telefono`, `email`, `direccion`) VALUES
(6, 'Proveedor 1', '68600101002', 'proveedor1@gmail.com', 'direccion 1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reporte`
--

CREATE TABLE `reporte` (
  `reporte_id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `fecha_generacion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `seguridad`
--

CREATE TABLE `seguridad` (
  `empleado_id` int(11) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `contraseña` varchar(255) NOT NULL,
  `rol` varchar(30) NOT NULL,
  `ultimo_login` datetime DEFAULT NULL,
  `intentos_fallidos` int(11) DEFAULT '0',
  `cuenta_bloqueada` tinyint(1) DEFAULT '0',
  `fecha_creacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_actualizacion` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sucursal`
--

CREATE TABLE `sucursal` (
  `sucursal_id` int(11) NOT NULL,
  `codigo_sucursal` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `direccion` varchar(200) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `ciudad` varchar(50) NOT NULL,
  `estado` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `sucursal`
--

INSERT INTO `sucursal` (`sucursal_id`, `codigo_sucursal`, `nombre`, `direccion`, `telefono`, `ciudad`, `estado`) VALUES
(1, '001', 'Ferreteria Nuñez 1', '13 de Septiembre, Los Naranjos, 21387', '6868423963', 'Mexicali', 'Baja California'),
(12, '002', 'Ferreteria Nuñez 2', 'Gral. Santiago Vidaurri 781, Rivera Campestre, 21387 Mexicali, B.C.', '6865806226', 'Mexicali', 'Baja California');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `transferencia_producto`
--

CREATE TABLE `transferencia_producto` (
  `transferencia_producto_id` int(11) NOT NULL,
  `sucursal_origen_id` int(11) NOT NULL,
  `sucursal_destino_id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha_solicitud` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_envio` datetime DEFAULT NULL,
  `fecha_recibido` datetime DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'SOLICITADA'
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `venta_id` int(11) NOT NULL,
  `fecha_venta` datetime NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `sucursal_id` int(11) NOT NULL,
  `total_venta` decimal(12,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'COMPLETADA'
) ;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`venta_id`, `fecha_venta`, `empleado_id`, `sucursal_id`, `total_venta`, `estado`) VALUES
(5, '2025-06-06 09:32:09', 1, 1, '159.00', 'COMPLETADA'),
(7, '2025-06-06 09:33:30', 1, 1, '175.00', 'COMPLETADA'),
(9, '2025-06-06 09:48:52', 1, 1, '19.50', 'COMPLETADA');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `config_interfaz`
--
ALTER TABLE `config_interfaz`
  ADD PRIMARY KEY (`empleado_id`);

--
-- Indices de la tabla `detalles_orden_compra`
--
ALTER TABLE `detalles_orden_compra`
  ADD PRIMARY KEY (`orden_compra_id`,`producto_id`),
  ADD KEY `detalles_orden_compra_ibfk_2` (`producto_id`);

--
-- Indices de la tabla `detalles_transferencia`
--
ALTER TABLE `detalles_transferencia`
  ADD PRIMARY KEY (`transferencia_producto_id`,`producto_id`),
  ADD KEY `detalles_transferencia_ibfk_2` (`producto_id`);

--
-- Indices de la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD PRIMARY KEY (`venta_id`,`producto_id`),
  ADD KEY `detalles_venta_ibfk_2` (`producto_id`);

--
-- Indices de la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD PRIMARY KEY (`empleado_id`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `sucursal_id` (`sucursal_id`);

--
-- Indices de la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD PRIMARY KEY (`producto_id`,`sucursal_id`),
  ADD KEY `inventario_ibfk_2` (`sucursal_id`);

--
-- Indices de la tabla `orden_compra`
--
ALTER TABLE `orden_compra`
  ADD PRIMARY KEY (`orden_compra_id`),
  ADD KEY `orden_compra_ibfk_1` (`proveedor_id`),
  ADD KEY `orden_compra_ibfk_2` (`empleado_id`);

--
-- Indices de la tabla `pago`
--
ALTER TABLE `pago`
  ADD PRIMARY KEY (`pago_id`),
  ADD UNIQUE KEY `referencia` (`referencia`),
  ADD KEY `pago_ibfk_1` (`venta_id`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`producto_id`),
  ADD UNIQUE KEY `codigo_producto` (`codigo_producto`);

--
-- Indices de la tabla `producto_promocion`
--
ALTER TABLE `producto_promocion`
  ADD PRIMARY KEY (`producto_id`,`promocion_id`),
  ADD KEY `producto_promocion_ibfk_2` (`promocion_id`);

--
-- Indices de la tabla `promocion`
--
ALTER TABLE `promocion`
  ADD PRIMARY KEY (`promocion_id`);

--
-- Indices de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  ADD PRIMARY KEY (`proveedor_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `reporte`
--
ALTER TABLE `reporte`
  ADD PRIMARY KEY (`reporte_id`),
  ADD KEY `empleado_id` (`empleado_id`);

--
-- Indices de la tabla `seguridad`
--
ALTER TABLE `seguridad`
  ADD PRIMARY KEY (`empleado_id`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  ADD PRIMARY KEY (`sucursal_id`),
  ADD UNIQUE KEY `codigo_sucursal` (`codigo_sucursal`);

--
-- Indices de la tabla `transferencia_producto`
--
ALTER TABLE `transferencia_producto`
  ADD PRIMARY KEY (`transferencia_producto_id`),
  ADD KEY `sucursal_origen_id` (`sucursal_origen_id`),
  ADD KEY `sucursal_destino_id` (`sucursal_destino_id`),
  ADD KEY `transferencia_producto_ibfk_3` (`empleado_id`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`venta_id`),
  ADD KEY `id_empleado` (`empleado_id`),
  ADD KEY `venta_ibfk_2` (`sucursal_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `empleado`
--
ALTER TABLE `empleado`
  MODIFY `empleado_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `orden_compra`
--
ALTER TABLE `orden_compra`
  MODIFY `orden_compra_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pago`
--
ALTER TABLE `pago`
  MODIFY `pago_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `producto_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de la tabla `promocion`
--
ALTER TABLE `promocion`
  MODIFY `promocion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `proveedor`
--
ALTER TABLE `proveedor`
  MODIFY `proveedor_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reporte`
--
ALTER TABLE `reporte`
  MODIFY `reporte_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sucursal`
--
ALTER TABLE `sucursal`
  MODIFY `sucursal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `transferencia_producto`
--
ALTER TABLE `transferencia_producto`
  MODIFY `transferencia_producto_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `venta_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `config_interfaz`
--
ALTER TABLE `config_interfaz`
  ADD CONSTRAINT `config_interfaz_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`);

--
-- Filtros para la tabla `detalles_orden_compra`
--
ALTER TABLE `detalles_orden_compra`
  ADD CONSTRAINT `detalles_orden_compra_ibfk_1` FOREIGN KEY (`orden_compra_id`) REFERENCES `orden_compra` (`orden_compra_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_orden_compra_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_transferencia`
--
ALTER TABLE `detalles_transferencia`
  ADD CONSTRAINT `detalles_transferencia_ibfk_1` FOREIGN KEY (`transferencia_producto_id`) REFERENCES `transferencia_producto` (`transferencia_producto_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_transferencia_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `detalles_venta`
--
ALTER TABLE `detalles_venta`
  ADD CONSTRAINT `detalles_venta_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`venta_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `detalles_venta_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `empleado`
--
ALTER TABLE `empleado`
  ADD CONSTRAINT `empleado_ibfk_1` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`sucursal_id`);

--
-- Filtros para la tabla `inventario`
--
ALTER TABLE `inventario`
  ADD CONSTRAINT `inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventario_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`sucursal_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `orden_compra`
--
ALTER TABLE `orden_compra`
  ADD CONSTRAINT `orden_compra_ibfk_1` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedor` (`proveedor_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `orden_compra_ibfk_2` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `pago`
--
ALTER TABLE `pago`
  ADD CONSTRAINT `pago_ibfk_1` FOREIGN KEY (`venta_id`) REFERENCES `venta` (`venta_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `producto_promocion`
--
ALTER TABLE `producto_promocion`
  ADD CONSTRAINT `producto_promocion_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `producto` (`producto_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `producto_promocion_ibfk_2` FOREIGN KEY (`promocion_id`) REFERENCES `promocion` (`promocion_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `reporte`
--
ALTER TABLE `reporte`
  ADD CONSTRAINT `reporte_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`);

--
-- Filtros para la tabla `seguridad`
--
ALTER TABLE `seguridad`
  ADD CONSTRAINT `seguridad_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`);

--
-- Filtros para la tabla `transferencia_producto`
--
ALTER TABLE `transferencia_producto`
  ADD CONSTRAINT `transferencia_producto_ibfk_3` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `venta_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`empleado_id`),
  ADD CONSTRAINT `venta_ibfk_2` FOREIGN KEY (`sucursal_id`) REFERENCES `sucursal` (`sucursal_id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
