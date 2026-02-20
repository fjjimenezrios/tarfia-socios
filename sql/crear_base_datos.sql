-- ============================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS
-- Sistema de Gestión de Socios
-- Compatible con MariaDB 10.x / MySQL 8.x
-- ============================================================

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS `TarfiaSocios` 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE `TarfiaSocios`;

-- ============================================================
-- TABLA: Niveles-Cursos
-- Catálogo de niveles educativos/deportivos
-- ============================================================
CREATE TABLE IF NOT EXISTS `Niveles-Cursos` (
    `Nivel` INT NOT NULL,
    `Curso` VARCHAR(50) NOT NULL,
    PRIMARY KEY (`Nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar niveles por defecto (adaptar según el club)
INSERT INTO `Niveles-Cursos` (`Nivel`, `Curso`) VALUES
    (0, '4º EPO'),
    (1, '5º EPO'),
    (2, '6º EPO'),
    (3, '1º ESO'),
    (4, '2º ESO'),
    (5, '3º ESO'),
    (6, '4º ESO'),
    (7, '1º BACH'),
    (8, '2º BACH'),
    (9, 'UNIV')
ON DUPLICATE KEY UPDATE `Curso` = VALUES(`Curso`);

-- ============================================================
-- TABLA: Familias Socios
-- Datos de las familias (padres/tutores)
-- ============================================================
CREATE TABLE IF NOT EXISTS `Familias Socios` (
    `Id` INT NOT NULL AUTO_INCREMENT,
    `Apellidos` VARCHAR(100) NOT NULL COMMENT 'Apellidos de la familia',
    `Nombre padre` VARCHAR(50) DEFAULT NULL,
    `Apellidos padre` VARCHAR(100) DEFAULT NULL,
    `Nombre madre` VARCHAR(50) DEFAULT NULL,
    `Apellidos madre` VARCHAR(100) DEFAULT NULL,
    `Dirección` VARCHAR(200) DEFAULT NULL,
    `Localidad` VARCHAR(100) DEFAULT NULL,
    `Teléfono` VARCHAR(20) DEFAULT NULL COMMENT 'Teléfono fijo',
    `Movil Padre` VARCHAR(20) DEFAULT NULL,
    `Movil Madre` VARCHAR(20) DEFAULT NULL,
    `e-mail` VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (`Id`),
    INDEX `idx_familias_apellidos` (`Apellidos`(50)),
    INDEX `idx_familias_localidad` (`Localidad`(50))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: Socios
-- Datos individuales de cada socio
-- ============================================================
CREATE TABLE IF NOT EXISTS `Socios` (
    `Id` INT NOT NULL AUTO_INCREMENT,
    `Nombre` VARCHAR(100) NOT NULL COMMENT 'Nombre del socio',
    `IdFamilia` INT DEFAULT NULL COMMENT 'FK a Familias Socios',
    `Nivel` INT DEFAULT NULL COMMENT 'FK a Niveles-Cursos',
    `Cuota` DECIMAL(10,2) DEFAULT 25.00 COMMENT 'Cuota mensual en euros',
    `Socio/Ex Socio` VARCHAR(20) DEFAULT 'Socio' COMMENT 'Estado: Socio o Ex Socio',
    `Móvil del socio` VARCHAR(20) DEFAULT NULL,
    `Fecha de admisión` DATE DEFAULT NULL,
    `Observaciones` TEXT DEFAULT NULL,
    PRIMARY KEY (`Id`),
    INDEX `idx_socios_familia` (`IdFamilia`),
    INDEX `idx_socios_nivel` (`Nivel`),
    INDEX `idx_socios_estado` (`Socio/Ex Socio`(20)),
    INDEX `idx_socios_nombre` (`Nombre`(50)),
    INDEX `idx_socios_fecha` (`Fecha de admisión`),
    INDEX `idx_socios_nivel_estado` (`Nivel`, `Socio/Ex Socio`(20)),
    CONSTRAINT `fk_socios_familia` 
        FOREIGN KEY (`IdFamilia`) REFERENCES `Familias Socios`(`Id`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_socios_nivel` 
        FOREIGN KEY (`Nivel`) REFERENCES `Niveles-Cursos`(`Nivel`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: usuarios (opcional)
-- Para autenticación de administradores
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
    `nombre` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_usuarios_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario admin por defecto (contraseña: admin123)
-- IMPORTANTE: Cambiar la contraseña después de la instalación
INSERT INTO `usuarios` (`username`, `password`, `nombre`) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador')
ON DUPLICATE KEY UPDATE `id` = `id`;

-- ============================================================
-- DATOS DE EJEMPLO (opcional, descomentar para probar)
-- ============================================================

/*
-- Familia de ejemplo
INSERT INTO `Familias Socios` 
    (`Apellidos`, `Nombre padre`, `Apellidos padre`, `Nombre madre`, `Apellidos madre`, 
     `Dirección`, `Localidad`, `Teléfono`, `Movil Padre`, `Movil Madre`, `e-mail`)
VALUES 
    ('García López', 'Juan', 'García Martín', 'María', 'López Ruiz',
     'Calle Mayor 123', 'Madrid', '912345678', '612345678', '623456789', 'familia.garcia@email.com');

-- Socios de ejemplo (hijos)
INSERT INTO `Socios` 
    (`Nombre`, `IdFamilia`, `Nivel`, `Cuota`, `Socio/Ex Socio`, `Móvil del socio`, `Fecha de admisión`)
VALUES 
    ('Pablo García López', 1, 3, 25.00, 'Socio', '634567890', '2024-09-01'),
    ('Ana García López', 1, 1, 25.00, 'Socio', NULL, '2024-09-01');
*/

-- ============================================================
-- VERIFICACIÓN
-- ============================================================
SELECT 'Base de datos creada correctamente' AS mensaje;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'TarfiaSocios';
