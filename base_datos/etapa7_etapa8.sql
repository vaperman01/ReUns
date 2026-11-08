-- ReUns - Etapa 7 y 8: Cierre C2C, Calificaciones, Reputación y Reseñas
USE reuns;

CREATE TABLE IF NOT EXISTS calificaciones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_operacion ENUM('oferta','intercambio') NOT NULL,
  operacion_id INT UNSIGNED NOT NULL,
  evaluador_id INT UNSIGNED NOT NULL,
  calificado_id INT UNSIGNED NOT NULL,
  puntuacion TINYINT UNSIGNED NOT NULL,
  comentario VARCHAR(500) NULL,
  rol_evaluador ENUM('comprador','vendedor','solicitante','receptor') NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_op_evaluador (tipo_operacion, operacion_id, evaluador_id),
  KEY idx_calif_calificado (calificado_id, puntuacion),
  KEY idx_calif_evaluador (evaluador_id),
  CONSTRAINT fk_calif_eval FOREIGN KEY (evaluador_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_calif_calif FOREIGN KEY (calificado_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
