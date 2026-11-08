-- ReUns - Etapa 5: Ofertas, compras e intercambios C2C.
-- Esquema consolidado y seguro para producción y pruebas.
USE reuns;

CREATE TABLE IF NOT EXISTS ofertas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  comprador_id INT UNSIGNED NOT NULL,
  vendedor_id INT UNSIGNED NOT NULL,
  tipo ENUM('compra','oferta') NOT NULL,
  monto DECIMAL(10,2) NOT NULL,
  mensaje VARCHAR(300) NULL,
  estado ENUM('pendiente','aceptada','rechazada','cancelada','completada') NOT NULL DEFAULT 'pendiente',
  activa TINYINT(1) NULL DEFAULT 1,
  aceptada_producto INT UNSIGNED NULL,
  confirma_comprador TINYINT(1) NOT NULL DEFAULT 0,
  confirma_vendedor TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_oferta_activa (producto_id, comprador_id, activa),
  UNIQUE KEY uq_oferta_aceptada (aceptada_producto),
  KEY idx_of_comprador (comprador_id, creado_en),
  KEY idx_of_vendedor (vendedor_id, creado_en),
  CONSTRAINT fk_of_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_of_comp FOREIGN KEY (comprador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_of_vend FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS intercambios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  producto_ofrecido_id INT UNSIGNED NOT NULL,
  solicitante_id INT UNSIGNED NOT NULL,
  receptor_id INT UNSIGNED NOT NULL,
  mensaje VARCHAR(300) NULL,
  estado ENUM('pendiente','aceptado','rechazado','cancelado','completado') NOT NULL DEFAULT 'pendiente',
  activa TINYINT(1) NULL DEFAULT 1,
  aceptado_producto INT UNSIGNED NULL,
  aceptado_ofrecido INT UNSIGNED NULL,
  confirma_solicitante TINYINT(1) NOT NULL DEFAULT 0,
  confirma_receptor TINYINT(1) NOT NULL DEFAULT 0,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_inter_activa (producto_id, solicitante_id, activa),
  UNIQUE KEY uq_inter_aceptado_p (aceptado_producto),
  UNIQUE KEY uq_inter_aceptado_o (aceptado_ofrecido),
  KEY idx_in_solic (solicitante_id, creado_en),
  KEY idx_in_recep (receptor_id, creado_en),
  CONSTRAINT fk_in_prod FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_in_ofrecido FOREIGN KEY (producto_ofrecido_id) REFERENCES productos(id) ON DELETE CASCADE,
  CONSTRAINT fk_in_solic FOREIGN KEY (solicitante_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_in_recep FOREIGN KEY (receptor_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historial_operaciones (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('oferta','intercambio') NOT NULL,
  operacion_id INT UNSIGNED NOT NULL,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(60) NOT NULL,
  estado_anterior VARCHAR(30) NULL,
  estado_nuevo VARCHAR(30) NOT NULL,
  detalle VARCHAR(255) NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_hist_op (tipo, operacion_id),
  KEY idx_hist_usr (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
