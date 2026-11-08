-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2026 a las 20:03:23
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `reuns`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(60) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `ip` varchar(45) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria`
--

INSERT INTO `auditoria` (`id`, `usuario_id`, `accion`, `detalle`, `ip`, `creado_en`) VALUES
(1, 1, 'admin_creado', 'por terminal', 'cli', '2026-10-22 21:18:36'),
(2, 1, 'login_fallido', NULL, '::1', '2026-10-22 22:59:06'),
(3, 3, 'registro', NULL, '::1', '2026-10-22 23:08:32'),
(4, 3, 'correo_verificado', NULL, '::1', '2026-10-22 23:08:55'),
(5, 3, 'producto_creado', 'id=4', '::1', '2026-10-23 00:30:51'),
(6, 3, 'logout', NULL, '::1', '2026-10-23 00:41:47'),
(7, NULL, 'login_fallido', NULL, '::1', '2026-10-23 00:42:09'),
(8, 1, 'login', NULL, '::1', '2026-10-23 00:42:40'),
(9, 1, 'login', NULL, '::1', '2026-10-23 08:11:00'),
(10, 3, 'login', NULL, '::1', '2026-10-29 11:25:15'),
(11, 3, 'producto_pausar', 'id=4', '::1', '2026-10-29 11:25:24'),
(12, 3, 'producto_reactivar', 'id=4', '::1', '2026-10-29 11:25:25'),
(13, 3, 'logout', NULL, '::1', '2026-10-29 11:36:25'),
(14, 3, 'login', NULL, '::1', '2026-10-30 09:51:23'),
(15, 3, 'login_fallido', NULL, '::1', '2026-10-30 12:30:59'),
(16, 3, 'login_fallido', NULL, '::1', '2026-10-30 12:31:04'),
(17, 3, 'login_fallido', NULL, '::1', '2026-10-30 12:31:08'),
(18, 3, 'login_fallido', NULL, '::1', '2026-10-30 12:31:12'),
(19, 3, 'login', NULL, '::1', '2026-10-30 12:31:17'),
(20, NULL, 'login_fallido', NULL, '::1', '2026-11-05 13:52:54'),
(21, NULL, 'login_fallido', NULL, '::1', '2026-11-05 13:53:00'),
(22, 4, 'registro', NULL, '::1', '2026-11-05 13:53:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `tipo_operacion` enum('oferta','intercambio') NOT NULL,
  `operacion_id` int(10) UNSIGNED NOT NULL,
  `evaluador_id` int(10) UNSIGNED NOT NULL,
  `calificado_id` int(10) UNSIGNED NOT NULL,
  `puntuacion` tinyint(3) UNSIGNED NOT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `rol_evaluador` enum('comprador','vendedor','solicitante','receptor') NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(40) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `icono` varchar(8) NOT NULL DEFAULT '?',
  `padre_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `slug`, `nombre`, `icono`, `padre_id`) VALUES
(1, 'tecnologia', 'Tecnología', '💻', NULL),
(2, 'hogar', 'Hogar', '🛋️', NULL),
(3, 'moda', 'Moda', '👟', NULL),
(4, 'deportes', 'Deportes', '🚲', NULL),
(5, 'libros', 'Libros', '📚', NULL),
(6, 'ninos', 'Niños', '🧸', NULL),
(7, 'vehiculos', 'Vehículos', '🛵', NULL),
(8, 'otros', 'Otros', '📦', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_operaciones`
--

CREATE TABLE `historial_operaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('oferta','intercambio') NOT NULL,
  `operacion_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED DEFAULT NULL,
  `accion` varchar(60) NOT NULL,
  `estado_anterior` varchar(30) DEFAULT NULL,
  `estado_nuevo` varchar(30) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_login`
--

CREATE TABLE `intentos_login` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` enum('login','registro','recuperar') NOT NULL,
  `identificador` varchar(150) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `intentos_login`
--

INSERT INTO `intentos_login` (`id`, `tipo`, `identificador`, `ip`, `creado_en`) VALUES
(2, 'registro', '0202614040@uns.edu.pe', '::1', '2026-10-22 23:08:32'),
(3, 'login', 'admin@gmail.com', '::1', '2026-10-23 00:42:09'),
(4, 'registro', '0202614040@uns.edu.pe', '::1', '2026-10-29 11:25:04'),
(9, 'login', 'admin@gmail.com', '::1', '2026-11-05 13:52:54'),
(10, 'login', 'admin@gmail.com', '::1', '2026-11-05 13:53:00'),
(11, 'registro', 'admin@uns.edu.pe', '::1', '2026-11-05 13:53:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intercambios`
--

CREATE TABLE `intercambios` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `producto_ofrecido_id` int(10) UNSIGNED NOT NULL,
  `solicitante_id` int(10) UNSIGNED NOT NULL,
  `receptor_id` int(10) UNSIGNED NOT NULL,
  `mensaje` varchar(300) DEFAULT NULL,
  `estado` enum('pendiente','aceptado','rechazado','cancelado','completado') NOT NULL DEFAULT 'pendiente',
  `activa` tinyint(1) DEFAULT 1,
  `aceptado_producto` int(10) UNSIGNED DEFAULT NULL,
  `aceptado_ofrecido` int(10) UNSIGNED DEFAULT NULL,
  `confirma_solicitante` tinyint(1) NOT NULL DEFAULT 0,
  `confirma_receptor` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `texto` varchar(160) NOT NULL,
  `url` varchar(160) DEFAULT NULL,
  `leida_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `conversaciones`
--

CREATE TABLE `conversaciones` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `vendedor_id` int(10) UNSIGNED NOT NULL,
  `leido_comprador` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `leido_vendedor` bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  `ultimo_mensaje_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversacion_id` int(10) UNSIGNED NOT NULL,
  `remitente_id` int(10) UNSIGNED NOT NULL,
  `destinatario_id` int(10) UNSIGNED DEFAULT NULL,
  `texto` varchar(1000) NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `favoritos`
--

CREATE TABLE `favoritos` (
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ofertas`
--

CREATE TABLE `ofertas` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `vendedor_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('compra','oferta') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `mensaje` varchar(300) DEFAULT NULL,
  `estado` enum('pendiente','aceptada','rechazada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  `activa` tinyint(1) DEFAULT 1,
  `aceptada_producto` int(10) UNSIGNED DEFAULT NULL,
  `confirma_comprador` tinyint(1) NOT NULL DEFAULT 0,
  `confirma_vendedor` tinyint(1) NOT NULL DEFAULT 0,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `vendedor_id` int(10) UNSIGNED NOT NULL,
  `propuesta_id` int(11) DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `metodo_pago` enum('prueba_efectivo','prueba_yape_plin') DEFAULT 'prueba_efectivo',
  `estado` enum('reservado','completado','cancelado') DEFAULT 'reservado',
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `precio` decimal(10,2) DEFAULT NULL,
  `precio_anterior` decimal(10,2) DEFAULT NULL,
  `modalidad` enum('venta','intercambio','ambas') NOT NULL,
  `estado_conservacion` enum('nuevo','como_nuevo','bueno','aceptable') NOT NULL,
  `ubicacion` varchar(80) DEFAULT NULL,
  `disponibilidad` enum('activo','pausado','vendido','intercambiado','eliminado') NOT NULL DEFAULT 'activo',
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estado` varchar(50) NOT NULL DEFAULT 'activo',
  `categoria` varchar(100) DEFAULT NULL,
  `estado_producto` varchar(50) DEFAULT 'disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `usuario_id`, `categoria_id`, `titulo`, `descripcion`, `precio`, `precio_anterior`, `modalidad`, `estado_conservacion`, `ubicacion`, `disponibilidad`, `creado_en`, `actualizado_en`, `estado`, `categoria`, `estado_producto`) VALUES
(4, 3, 1, 'cagbet', 'gdyhdydfghr', 120.00, 190.00, 'venta', 'nuevo', '', 'activo', '2026-10-23 00:30:51', '2026-10-29 11:25:25', 'activo', NULL, 'como_nuevo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto_imagenes`
--

CREATE TABLE `producto_imagenes` (
  `id` int(10) UNSIGNED NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `archivo` varchar(40) NOT NULL,
  `orden` tinyint(3) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `producto_imagenes`
--

INSERT INTO `producto_imagenes` (`id`, `producto_id`, `archivo`, `orden`) VALUES
(1, 4, '406aa78961c65410614363a11f81ad18.jpg', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `propuestas`
--

CREATE TABLE `propuestas` (
  `id` int(11) NOT NULL,
  `producto_id` int(10) UNSIGNED NOT NULL,
  `comprador_id` int(10) UNSIGNED NOT NULL,
  `vendedor_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('oferta_precio','intercambio') NOT NULL,
  `monto_ofrecido` decimal(10,2) DEFAULT NULL,
  `producto_ofrecido_id` int(10) UNSIGNED DEFAULT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` enum('pendiente','aceptada','rechazada','contraofertada','cancelada') DEFAULT 'pendiente',
  `creado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tokens_usuario`
--

CREATE TABLE `tokens_usuario` (
  `id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `tipo` enum('verificar','recuperar') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expira_en` datetime NOT NULL,
  `usado_en` datetime DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tokens_usuario`
--

INSERT INTO `tokens_usuario` (`id`, `usuario_id`, `tipo`, `token_hash`, `expira_en`, `usado_en`, `creado_en`) VALUES
(1, 3, 'verificar', '5b2375b9d89cfbe90eaf5579496b88df2fa1b4a05712f5b173d3ed72e8bd1b6b', '2026-10-23 23:08:32', '2026-10-22 23:08:55', '2026-10-22 23:08:32'),
(2, 4, 'verificar', '0cc2a3e08856f2dca0fc6bfd3c4f22d76ed93045f4937282e838a53a3165c3ed', '2026-11-06 13:53:55', NULL, '2026-11-05 13:53:55');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `email` varchar(150) NOT NULL,
  `clave_hash` varchar(255) NOT NULL,
  `rol` enum('usuario','moderador','admin') NOT NULL DEFAULT 'usuario',
  `estado` enum('activo','suspendido') NOT NULL DEFAULT 'activo',
  `email_verificado_en` datetime DEFAULT NULL,
  `bio` varchar(300) DEFAULT NULL,
  `ubicacion` varchar(80) DEFAULT NULL,
  `foto` varchar(40) DEFAULT NULL,
  `version_sesion` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `clave_hash`, `rol`, `estado`, `email_verificado_en`, `bio`, `ubicacion`, `foto`, `version_sesion`, `creado_en`, `ultimo_login`) VALUES
(1, 'Admin', 'tekayyt2019@gmail.com', '$2y$10$IfckUEheMNfC2Kkx0LysYuSbTee/c0cVZUXDHKG8E/0AchFeARxqW', 'admin', 'activo', '2026-10-22 21:18:36', NULL, NULL, NULL, 1, '2026-10-22 21:18:36', '2026-10-23 08:11:00'),
(2, 'Vendedor Demo', 'demo@reuns.local', '$2y$10$EqUDmbI9pYeiZ9PoHopdeOO3p9lAOkefFibbD8rKA9DSy/WnpwD/6', 'usuario', 'activo', '2026-10-22 21:19:50', NULL, 'Chimbote', NULL, 1, '2026-10-22 21:19:50', NULL),
(3, 'Juan Panta', '0202614040@uns.edu.pe', '$2y$10$qQ1SoDuuc7n49f7iK1ckUuBoKAt9cYIq30KgfGpLn7NwaO6bFcDq2', 'usuario', 'activo', '2026-10-22 23:08:55', NULL, NULL, NULL, 1, '2026-10-22 23:08:32', '2026-10-30 12:31:17'),
(4, 'Juan pablo', 'admin@uns.edu.pe', '$2y$10$39zbubPpaXPujVquE/bCWeZnGH3FgpacC0.U8PhNBGM1UqHUMnW0a', 'usuario', 'activo', NULL, NULL, NULL, NULL, 1, '2026-11-05 13:53:55', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `accion` (`accion`);

--
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_op_evaluador` (`tipo_operacion`,`operacion_id`,`evaluador_id`),
  ADD KEY `idx_calif_calificado` (`calificado_id`,`puntuacion`),
  ADD KEY `idx_calif_evaluador` (`evaluador_id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `padre_id` (`padre_id`);

--
-- Indices de la tabla `historial_operaciones`
--
ALTER TABLE `historial_operaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hist_op` (`tipo`,`operacion_id`),
  ADD KEY `idx_hist_usr` (`usuario_id`);

--
-- Indices de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo` (`tipo`,`identificador`,`creado_en`),
  ADD KEY `tipo_2` (`tipo`,`ip`,`creado_en`);

--
-- Indices de la tabla `intercambios`
--
ALTER TABLE `intercambios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inter_activa` (`producto_id`,`solicitante_id`,`activa`),
  ADD UNIQUE KEY `uq_inter_aceptado_p` (`aceptado_producto`),
  ADD UNIQUE KEY `uq_inter_aceptado_o` (`aceptado_ofrecido`),
  ADD KEY `idx_in_solic` (`solicitante_id`,`creado_en`),
  ADD KEY `idx_in_recep` (`receptor_id`,`creado_en`),
  ADD KEY `fk_in_ofrecido` (`producto_ofrecido_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_usuario` (`usuario_id`,`leida_en`,`id`);

--
-- Indices de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_conv` (`producto_id`,`comprador_id`),
  ADD KEY `idx_conv_comprador` (`comprador_id`,`ultimo_mensaje_en`),
  ADD KEY `idx_conv_vendedor` (`vendedor_id`,`ultimo_mensaje_en`);

--
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_msg_conv` (`conversacion_id`,`id`),
  ADD KEY `idx_msg_remitente` (`remitente_id`,`creado_en`),
  ADD KEY `idx_msg_dest` (`destinatario_id`,`leido`);

--
-- Indices de la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`usuario_id`,`producto_id`),
  ADD KEY `idx_fav_producto` (`producto_id`);

--
-- Indices de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_oferta_activa` (`producto_id`,`comprador_id`,`activa`),
  ADD UNIQUE KEY `uq_oferta_aceptada` (`aceptada_producto`),
  ADD KEY `idx_of_comprador` (`comprador_id`,`creado_en`),
  ADD KEY `idx_of_vendedor` (`vendedor_id`,`creado_en`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `comprador_id` (`comprador_id`),
  ADD KEY `vendedor_id` (`vendedor_id`),
  ADD KEY `propuesta_id` (`propuesta_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `disponibilidad` (`disponibilidad`,`creado_en`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `usuario_id` (`usuario_id`,`disponibilidad`);

--
-- Indices de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `producto_id` (`producto_id`),
  ADD KEY `comprador_id` (`comprador_id`),
  ADD KEY `vendedor_id` (`vendedor_id`),
  ADD KEY `producto_ofrecido_id` (`producto_ofrecido_id`);

--
-- Indices de la tabla `tokens_usuario`
--
ALTER TABLE `tokens_usuario`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `historial_operaciones`
--
ALTER TABLE `historial_operaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `intercambios`
--
ALTER TABLE `intercambios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ofertas`
--
ALTER TABLE `ofertas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `propuestas`
--
ALTER TABLE `propuestas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tokens_usuario`
--
ALTER TABLE `tokens_usuario`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `fk_calif_calif` FOREIGN KEY (`calificado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_calif_eval` FOREIGN KEY (`evaluador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`padre_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `intercambios`
--
ALTER TABLE `intercambios`
  ADD CONSTRAINT `fk_in_ofrecido` FOREIGN KEY (`producto_ofrecido_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_in_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_in_recep` FOREIGN KEY (`receptor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_in_solic` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ofertas`
--
ALTER TABLE `ofertas`
  ADD CONSTRAINT `fk_of_comp` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_of_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_of_vend` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_2` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_3` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pedidos_ibfk_4` FOREIGN KEY (`propuesta_id`) REFERENCES `propuestas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);

--
-- Filtros para la tabla `producto_imagenes`
--
ALTER TABLE `producto_imagenes`
  ADD CONSTRAINT `producto_imagenes_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `propuestas`
--
ALTER TABLE `propuestas`
  ADD CONSTRAINT `propuestas_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propuestas_ibfk_2` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propuestas_ibfk_3` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `propuestas_ibfk_4` FOREIGN KEY (`producto_ofrecido_id`) REFERENCES `productos` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `tokens_usuario`
--
ALTER TABLE `tokens_usuario`
  ADD CONSTRAINT `tokens_usuario_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `conversaciones`
--
ALTER TABLE `conversaciones`
  ADD CONSTRAINT `fk_conv_comp` FOREIGN KEY (`comprador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_conv_vend` FOREIGN KEY (`vendedor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `fk_msg_conv` FOREIGN KEY (`conversacion_id`) REFERENCES `conversaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_rem` FOREIGN KEY (`remitente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_msg_dest` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_fav_prod` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fav_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notif_usr` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
