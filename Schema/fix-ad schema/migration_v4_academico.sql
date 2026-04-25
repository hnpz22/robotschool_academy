-- Schema/migration_v4_academico.sql
-- Migraci?n para el m?dulo Acad?mico: temas y actividades

-- ============================================================
-- TEMAS: unidades pedag?gicas dentro de un curso
-- Due?o: coordinador pedag?gico
-- ============================================================
CREATE TABLE IF NOT EXISTS `temas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT NULL,
  `objetivos` TEXT NULL COMMENT 'Objetivos pedagogicos del tema',
  `orden` INT UNSIGNED NOT NULL DEFAULT 0,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tema_curso` (`curso_id`, `orden`),
  CONSTRAINT `fk_tema_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- ACTIVIDADES: tareas concretas dentro de un tema
-- Due?o: coordinador pedag?gico
-- Pueden asociarse opcionalmente a una r?brica para evaluaci?n
-- ============================================================
CREATE TABLE IF NOT EXISTS `actividades` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tema_id` INT UNSIGNED NOT NULL,
  `rubrica_id` INT UNSIGNED NULL COMMENT 'Rubrica para evaluar esta actividad (opcional)',
  `nombre` VARCHAR(200) NOT NULL,
  `descripcion` TEXT NULL,
  `tipo` ENUM('armado','programacion','investigacion','reto','proyecto','exposicion','taller','otro') NOT NULL DEFAULT 'taller',
  `duracion_min` INT UNSIGNED NULL COMMENT 'Duracion estimada en minutos',
  `materiales` TEXT NULL COMMENT 'Materiales o kit requerido',
  `orden` INT UNSIGNED NOT NULL DEFAULT 0,
  `activa` TINYINT(1) NOT NULL DEFAULT 1,
  `creado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_actividad_tema` (`tema_id`, `orden`),
  KEY `idx_actividad_rubrica` (`rubrica_id`),
  CONSTRAINT `fk_actividad_tema` FOREIGN KEY (`tema_id`) REFERENCES `temas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_actividad_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
