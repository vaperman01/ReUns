-- ==============================================================================
-- ReUns - Script de Sincronización Completa para Chat, Notificaciones y Favoritos
-- Resuelve: SQLSTATE[42S02]: Table 'reuns.mensajes' doesn't exist en chat.php:148
-- ==============================================================================

USE reuns;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABLA: conversaciones (Necesaria antes de crear mensajes por la Foreign Key)
CREATE TABLE IF NOT EXISTS conversaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  comprador_id INT UNSIGNED NOT NULL,
  vendedor_id INT UNSIGNED NOT NULL,
  leido_comprador BIGINT UNSIGNED NOT NULL DEFAULT 0,
  leido_vendedor BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_mensaje_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv (producto_id, comprador_id),
  KEY idx_conv_comprador (comprador_id, ultimo_mensaje_en),
  KEY idx_conv_vendedor (vendedor_id, ultimo_mensaje_en),
  CONSTRAINT fk_conv_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_comp FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_conv_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA: mensajes (Estructura relacional completa para chat.php)
CREATE TABLE IF NOT EXISTS mensajes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversacion_id INT UNSIGNED NOT NULL,
  remitente_id INT UNSIGNED NOT NULL,
  destinatario_id INT UNSIGNED NULL,
  texto VARCHAR(1000) NOT NULL,
  leido TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_conv (conversacion_id, id),
  KEY idx_msg_remitente (remitente_id, creado_en),
  KEY idx_msg_dest (destinatario_id, leido),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_rem FOREIGN KEY (remitente_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_dest FOREIGN KEY (destinatario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA: favoritos (Faltante en reuns.sql para app/modelos/favorito.php)
CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, producto_id),
  KEY idx_fav_producto (producto_id),
  CONSTRAINT fk_fav_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA: notificaciones (Unificada con los campos 'texto' y 'url' que espera notificacion.php)
DROP TABLE IF EXISTS notificaciones;
CREATE TABLE notificaciones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(20) NOT NULL,
  texto VARCHAR(160) NOT NULL,
  url VARCHAR(160) NULL,
  leida_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_usuario (usuario_id, leida_en, id),
  CONSTRAINT fk_notif_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
