-- ReUns - Etapa 4 (catálogo). Ejecútalo DESPUÉS de esquema.sql, en phpMyAdmin → SQL.
USE reuns;
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS foto VARCHAR(40) NULL AFTER ubicacion;
CREATE TABLE IF NOT EXISTS categorias (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(40) NOT NULL UNIQUE,
  nombre VARCHAR(60) NOT NULL,
  icono VARCHAR(8) NOT NULL DEFAULT '📦',
  padre_id INT UNSIGNED NULL,
  FOREIGN KEY (padre_id) REFERENCES categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  categoria_id INT UNSIGNED NOT NULL,
  titulo VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  precio DECIMAL(10,2) NULL,
  precio_anterior DECIMAL(10,2) NULL,
  modalidad ENUM('venta','intercambio','ambas') NOT NULL,
  estado_conservacion ENUM('nuevo','como_nuevo','bueno','aceptable') NOT NULL,
  ubicacion VARCHAR(80) NULL,
  disponibilidad ENUM('activo','pausado','vendido','intercambiado','eliminado') NOT NULL DEFAULT 'activo',
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (disponibilidad, creado_en), INDEX (categoria_id), INDEX (usuario_id, disponibilidad),
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
  FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS producto_imagenes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  producto_id INT UNSIGNED NOT NULL,
  archivo VARCHAR(40) NOT NULL,
  orden TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT IGNORE INTO categorias (slug,nombre,icono) VALUES
 ('tecnologia','Tecnología','💻'),('hogar','Hogar','🛋️'),('moda','Moda','👟'),('deportes','Deportes','🚲'),
 ('libros','Libros','📚'),('ninos','Niños','🧸'),('vehiculos','Vehículos','🛵'),('otros','Otros','📦');
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'celulares','Celulares','📱',id FROM categorias WHERE slug='tecnologia';
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'computadoras','Computadoras','💻',id FROM categorias WHERE slug='tecnologia';
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'muebles','Muebles','🪑',id FROM categorias WHERE slug='hogar';
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'calzado','Calzado','👟',id FROM categorias WHERE slug='moda';
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'bicicletas','Bicicletas','🚲',id FROM categorias WHERE slug='deportes';
INSERT IGNORE INTO categorias (slug,nombre,icono,padre_id) SELECT 'motos','Motos','🛵',id FROM categorias WHERE slug='vehiculos';
