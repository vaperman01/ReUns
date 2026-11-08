-- ReUns - Etapa 6: favoritos, chat y notificaciones. NO destructivo y re-ejecutable.
-- Ejecuta ANTES etapa4b.sql y etapa5.sql. phpMyAdmin -> base reuns -> SQL. Copia de seguridad primero.
USE reuns;

CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id INT UNSIGNED NOT NULL,
  producto_id INT UNSIGNED NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, producto_id),            -- evita favoritos duplicados
  KEY idx_fav_producto (producto_id),
  CONSTRAINT fk_fav_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Una conversación por (producto, comprador). Solo participan esos dos usuarios.
CREATE TABLE IF NOT EXISTS conversaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  comprador_id INT UNSIGNED NOT NULL,               -- quien inicia el chat
  vendedor_id INT UNSIGNED NOT NULL,                -- dueño del producto
  leido_comprador BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- id del último mensaje que vio cada uno
  leido_vendedor BIGINT UNSIGNED NOT NULL DEFAULT 0,
  ultimo_mensaje_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_conv (producto_id, comprador_id),
  KEY idx_conv_comprador (comprador_id, ultimo_mensaje_en),
  KEY idx_conv_vendedor (vendedor_id, ultimo_mensaje_en),
  CONSTRAINT fk_conv_prod FOREIGN KEY (producto_id) REFERENCES productos(id),
  CONSTRAINT fk_conv_comp FOREIGN KEY (comprador_id) REFERENCES usuarios(id),
  CONSTRAINT fk_conv_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  conversacion_id INT UNSIGNED NOT NULL,
  remitente_id INT UNSIGNED NOT NULL,
  texto VARCHAR(1000) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_conv (conversacion_id, id),
  KEY idx_msg_remitente (remitente_id, creado_en),
  CONSTRAINT fk_msg_conv FOREIGN KEY (conversacion_id) REFERENCES conversaciones(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_rem FOREIGN KEY (remitente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notificaciones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(20) NOT NULL,                        -- operacion | mensaje | sistema
  texto VARCHAR(160) NOT NULL,
  url VARCHAR(160) NULL,                            -- ruta relativa a BASE, p. ej. /paginas/operaciones.php
  leida_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notif_usuario (usuario_id, leida_en, id),
  CONSTRAINT fk_notif_usr FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
