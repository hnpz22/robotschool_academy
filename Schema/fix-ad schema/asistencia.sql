-- ============================================================
-- Modulo de Asistencia - ROBOTSchool Academy Learning
-- Ejecutar en: robotschool_academy
-- ============================================================

-- Tabla de sesiones (clases programadas por grupo)
CREATE TABLE IF NOT EXISTS `sesiones` (
  `id`          int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `grupo_id`    int(10) UNSIGNED NOT NULL,
  `fecha`       date NOT NULL COMMENT 'Fecha real de la clase',
  `tema`        varchar(200) DEFAULT NULL COMMENT 'Tema o actividad de la sesion',
  `observaciones` text DEFAULT NULL,
  `creado_por`  int(10) UNSIGNED NOT NULL COMMENT 'usuario_id quien crea la sesion',
  `created_at`  datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sesion_grupo_fecha` (`grupo_id`, `fecha`),
  KEY `fk_sesion_grupo` (`grupo_id`),
  KEY `fk_sesion_creador` (`creado_por`),
  CONSTRAINT `fk_sesion_grupo`   FOREIGN KEY (`grupo_id`)  REFERENCES `grupos`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sesion_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de registros de asistencia por estudiante y sesion
CREATE TABLE IF NOT EXISTS `asistencia` (
  `id`           int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_id`    int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED NOT NULL,
  `estado`       enum('presente','tarde','ausente','excusa') NOT NULL DEFAULT 'ausente',
  `observacion`  varchar(255) DEFAULT NULL,
  `registrado_por` int(10) UNSIGNED NOT NULL COMMENT 'usuario_id quien registra',
  `created_at`   datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at`   datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_asistencia` (`sesion_id`, `matricula_id`),
  KEY `fk_asist_sesion`    (`sesion_id`),
  KEY `fk_asist_matricula` (`matricula_id`),
  KEY `fk_asist_registrador` (`registrado_por`),
  CONSTRAINT `fk_asist_sesion`      FOREIGN KEY (`sesion_id`)    REFERENCES `sesiones`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asist_matricula`   FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_asist_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Tabla de observaciones de clase / estudiante
-- ============================================================
CREATE TABLE IF NOT EXISTS `observaciones` (
  `id`           int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `grupo_id`     int(10) UNSIGNED NOT NULL,
  `matricula_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL = observacion general del grupo',
  `tipo`         enum('general','estudiante') NOT NULL DEFAULT 'estudiante',
  `fecha`        date NOT NULL,
  `texto`        text NOT NULL,
  `visible_padre` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = padre puede verla en su portal',
  `registrado_por` int(10) UNSIGNED NOT NULL,
  `created_at`   datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_obs_grupo`      (`grupo_id`),
  KEY `fk_obs_matricula`  (`matricula_id`),
  KEY `fk_obs_registrador`(`registrado_por`),
  CONSTRAINT `fk_obs_grupo`       FOREIGN KEY (`grupo_id`)     REFERENCES `grupos`     (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obs_matricula`   FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_obs_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
