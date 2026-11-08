-- ReUns - Etapa 4b: unifica la BD real con el modelo. NO destructivo y re-ejecutable.
-- Ejecutar en phpMyAdmin -> base reuns -> SQL. Haz una copia de seguridad antes (Exportar).
USE reuns;

-- 1) Permite el estado "intercambiado" (conserva los valores existentes).
ALTER TABLE productos
  MODIFY estado ENUM('activo','pausado','vendido','intercambiado','oculto','eliminado')
  NOT NULL DEFAULT 'activo';

-- 2) Categoría "ropa" que usaba el PHP y no existía en la tabla categorias.
INSERT IGNORE INTO categorias (slug, nombre, icono) VALUES ('ropa', 'Ropa y accesorios', '👕');

-- 3) Índices para catálogo y "Mis productos" (se crean solo si faltan).
SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND INDEX_NAME = 'idx_estado_creado');
SET @q := IF(@i = 0, 'ALTER TABLE productos ADD INDEX idx_estado_creado (estado, creado_en)', 'SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND INDEX_NAME = 'idx_usuario_estado');
SET @q := IF(@i = 0, 'ALTER TABLE productos ADD INDEX idx_usuario_estado (usuario_id, estado)', 'SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;

SET @i := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'productos' AND INDEX_NAME = 'idx_categoria');
SET @q := IF(@i = 0, 'ALTER TABLE productos ADD INDEX idx_categoria (categoria)', 'SELECT 1');
PREPARE s FROM @q; EXECUTE s; DEALLOCATE PREPARE s;
