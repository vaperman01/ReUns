-- ReUns - Etapa 3. Ejecuta en phpMyAdmin (pestaña SQL) o: mysql -u root < esquema.sql
CREATE DATABASE IF NOT EXISTS reuns CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE reuns;
CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(60) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  clave_hash VARCHAR(255) NOT NULL,
  rol ENUM('usuario','moderador','admin') NOT NULL DEFAULT 'usuario',
  estado ENUM('activo','suspendido') NOT NULL DEFAULT 'activo',
  email_verificado_en DATETIME NULL,
  bio VARCHAR(300) NULL,
  ubicacion VARCHAR(80) NULL,
  version_sesion INT UNSIGNED NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ultimo_login DATETIME NULL
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS tokens_usuario (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  tipo ENUM('verificar','recuperar') NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  expira_en DATETIME NOT NULL,
  usado_en DATETIME NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS intentos_login (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('login','registro','recuperar') NOT NULL,
  identificador VARCHAR(150) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (tipo, identificador, creado_en),
  INDEX (tipo, ip, creado_en)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS auditoria (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  accion VARCHAR(60) NOT NULL,
  detalle VARCHAR(255) NULL,
  ip VARCHAR(45) NOT NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (usuario_id), INDEX (accion)
) ENGINE=InnoDB;
