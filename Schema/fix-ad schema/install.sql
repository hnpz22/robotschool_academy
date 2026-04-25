-- ============================================================
--  ROBOTSchool Academy Learning (RSAL)
--  install.sql — Instalación completa de la base de datos
--  academy.robotschool.com.co
--  Versión 1.0 — 2026
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-05:00";

CREATE DATABASE IF NOT EXISTS `robotschool_academy`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `robotschool_academy`;

-- ============================================================
--  1. SEDES
-- ============================================================
CREATE TABLE `sedes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`      VARCHAR(100) NOT NULL,
  `ciudad`      VARCHAR(80)  NOT NULL,
  `direccion`   VARCHAR(150) NOT NULL,
  `telefono`    VARCHAR(20)  DEFAULT NULL,
  `email`       VARCHAR(100) DEFAULT NULL,
  `activa`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  2. USUARIOS  (admin_general | admin_sede | padre)
-- ============================================================
CREATE TABLE `usuarios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sede_id`       INT UNSIGNED DEFAULT NULL COMMENT 'NULL = admin general',
  `nombre`        VARCHAR(120) NOT NULL,
  `email`         VARCHAR(120) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol`           ENUM('admin_general','admin_sede','docente','padre') NOT NULL DEFAULT 'padre',
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `ultimo_login`  DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`),
  KEY `fk_usuarios_sede` (`sede_id`),
  CONSTRAINT `fk_usuarios_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  3. CURSOS
-- ============================================================
CREATE TABLE `cursos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sede_id`      INT UNSIGNED NOT NULL,
  `nombre`       VARCHAR(150) NOT NULL,
  `imagen`       VARCHAR(255) DEFAULT NULL COMMENT 'Ruta relativa en /uploads/cursos/',
  `introduccion` TEXT         DEFAULT NULL,
  `objetivos`    TEXT         DEFAULT NULL,
  `edad_min`     TINYINT UNSIGNED DEFAULT NULL,
  `edad_max`     TINYINT UNSIGNED DEFAULT NULL,
  `valor`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tipo_valor`   ENUM('mensual','semestral') NOT NULL DEFAULT 'mensual' COMMENT 'mensual=4 sesiones, semestral',
  `cupo_maximo`  INT UNSIGNED  NOT NULL DEFAULT 20,
  `publicado`    TINYINT(1)    NOT NULL DEFAULT 0,
  `orden`        INT UNSIGNED  NOT NULL DEFAULT 0,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_cursos_sede` (`sede_id`),
  CONSTRAINT `fk_cursos_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  4. MÓDULOS DEL CURSO
-- ============================================================
CREATE TABLE `curso_modulos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id`    INT UNSIGNED NOT NULL,
  `nombre`      VARCHAR(150) NOT NULL,
  `descripcion` TEXT         DEFAULT NULL,
  `orden`       INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_modulos_curso` (`curso_id`),
  CONSTRAINT `fk_modulos_curso`
    FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  5. MATERIALES DEL CURSO
-- ============================================================
CREATE TABLE `curso_materiales` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id`       INT UNSIGNED NOT NULL,
  `nombre`         VARCHAR(150) NOT NULL,
  `cantidad`       INT UNSIGNED NOT NULL DEFAULT 1,
  `kit_referencia` VARCHAR(100) DEFAULT NULL COMMENT 'Ej: Ecua-InnTech-03, Kuntur-02',
  `observaciones`  VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_materiales_curso` (`curso_id`),
  CONSTRAINT `fk_materiales_curso`
    FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  6. GALERÍA DE IMÁGENES DEL CURSO
-- ============================================================
CREATE TABLE `curso_galeria` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id`    INT UNSIGNED NOT NULL,
  `imagen`      VARCHAR(255) NOT NULL COMMENT 'Ruta en /uploads/cursos/galeria/',
  `caption`     VARCHAR(200) DEFAULT NULL,
  `orden`       INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_galeria_curso` (`curso_id`),
  CONSTRAINT `fk_galeria_curso`
    FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  7. EQUIPOS (inventario de equipos por sede)
-- ============================================================
CREATE TABLE `equipos` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sede_id`             INT UNSIGNED NOT NULL,
  `nombre`              VARCHAR(150) NOT NULL COMMENT 'Ej: LEGO Spike Prime',
  `descripcion`         VARCHAR(255) DEFAULT NULL,
  `cantidad_total`      INT UNSIGNED NOT NULL DEFAULT 1,
  `activo`              TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_equipos_sede` (`sede_id`),
  CONSTRAINT `fk_equipos_sede`
    FOREIGN KEY (`sede_id`) REFERENCES `sedes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  7. GRUPOS (sesiones de un curso — reemplaza horarios)
--  Un curso puede tener varios grupos (Ej: S1 8-10, S2 10:30-12:30)
--  El cupo real = MIN(cupo_equipos, cupo_aula, cupo_admin)
-- ============================================================
CREATE TABLE `grupos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id`      INT UNSIGNED NOT NULL,
  `sede_id`       INT UNSIGNED NOT NULL,
  `nombre`        VARCHAR(100) NOT NULL COMMENT 'Ej: Grupo Sábado S1',
  `dia_semana`    ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio`   TIME         NOT NULL,
  `hora_fin`      TIME         NOT NULL,
  `modalidad`     ENUM('presencial','virtual','hibrida') NOT NULL DEFAULT 'presencial',
  `cupo_equipos`  INT UNSIGNED DEFAULT NULL COMMENT 'Calculado por equipos asignados',
  `cupo_aula`     INT UNSIGNED DEFAULT NULL COMMENT 'Capacidad física del aula',
  `cupo_admin`    INT UNSIGNED DEFAULT NULL COMMENT 'Límite manual del admin',
  `cupo_real`     INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'MIN de los tres anteriores — calculado',
  `periodo`       VARCHAR(20)  NOT NULL COMMENT 'Ej: 2026-1',
  `fecha_inicio`  DATE         DEFAULT NULL,
  `fecha_fin`     DATE         DEFAULT NULL,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_grupos_curso` (`curso_id`),
  KEY `fk_grupos_sede`  (`sede_id`),
  CONSTRAINT `fk_grupos_curso`
    FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_grupos_sede`
    FOREIGN KEY (`sede_id`)  REFERENCES `sedes`  (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  8. GRUPO_EQUIPOS (qué equipos usa cada grupo)
--  Valida que el mismo equipo no se asigne a dos grupos
--  simultáneos (mismo día y hora solapada)
-- ============================================================
CREATE TABLE `grupo_equipos` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `grupo_id`           INT UNSIGNED NOT NULL,
  `equipo_id`          INT UNSIGNED NOT NULL,
  `cantidad_requerida` INT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grupo_equipo` (`grupo_id`,`equipo_id`),
  KEY `fk_ge_grupo`  (`grupo_id`),
  KEY `fk_ge_equipo` (`equipo_id`),
  CONSTRAINT `fk_ge_grupo`
    FOREIGN KEY (`grupo_id`)  REFERENCES `grupos`  (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ge_equipo`
    FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  NOTA: La tabla horarios original fue reemplazada por grupos.
--  Las matrículas ahora referencian grupo_id en lugar de horario_id.
-- ============================================================

-- ============================================================
--  7. PADRES / ACUDIENTES
-- ============================================================
CREATE TABLE `padres` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`        INT UNSIGNED NOT NULL,
  `nombre_completo`   VARCHAR(150) NOT NULL,
  `tipo_doc`          ENUM('CC','CE','PP','NIT','TI') NOT NULL DEFAULT 'CC',
  `numero_doc`        VARCHAR(30)  NOT NULL,
  `telefono`          VARCHAR(20)  NOT NULL,
  `telefono_alt`      VARCHAR(20)  DEFAULT NULL,
  `email`             VARCHAR(120) NOT NULL,
  `direccion`         VARCHAR(200) DEFAULT NULL,
  `ocupacion`         VARCHAR(100) DEFAULT NULL,
  `acepta_datos`      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Política de tratamiento de datos',
  `acepta_imagenes`   TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Autorización uso de imágenes',
  `fecha_aceptacion`  DATETIME     DEFAULT NULL,
  `ip_aceptacion`     VARCHAR(45)  DEFAULT NULL,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_padre_doc` (`numero_doc`),
  KEY `fk_padres_usuario` (`usuario_id`),
  CONSTRAINT `fk_padres_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  8. ESTUDIANTES
-- ============================================================
CREATE TABLE `estudiantes` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `padre_id`       INT UNSIGNED NOT NULL,
  `sede_id`        INT UNSIGNED NOT NULL,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `tipo_doc`       ENUM('TI','RC','PP','CE') NOT NULL DEFAULT 'TI',
  `numero_doc`     VARCHAR(30)  DEFAULT NULL,
  `fecha_nacimiento` DATE       NOT NULL,
  `genero`         ENUM('masculino','femenino','otro','prefiero_no_decir') NOT NULL DEFAULT 'prefiero_no_decir',
  `colegio`        VARCHAR(150) DEFAULT NULL,
  `grado`          VARCHAR(30)  DEFAULT NULL,
  `eps`            VARCHAR(100) DEFAULT NULL,
  `grupo_sanguineo` VARCHAR(10) DEFAULT NULL,
  `alergias`       TEXT         DEFAULT NULL,
  `seguro_estudiantil` VARCHAR(150) DEFAULT NULL COMMENT 'Nombre o número de póliza del seguro estudiantil',
  `observaciones`  TEXT         DEFAULT NULL,
  `avatar`         VARCHAR(255) DEFAULT NULL COMMENT 'Ruta relativa en /uploads/estudiantes/',
  `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_estudiantes_padre` (`padre_id`),
  KEY `fk_estudiantes_sede`  (`sede_id`),
  CONSTRAINT `fk_estudiantes_padre`
    FOREIGN KEY (`padre_id`) REFERENCES `padres` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_estudiantes_sede`
    FOREIGN KEY (`sede_id`)  REFERENCES `sedes`  (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  9. MATRÍCULAS
-- ============================================================
CREATE TABLE `matriculas` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estudiante_id`  INT UNSIGNED NOT NULL,
  `grupo_id`       INT UNSIGNED NOT NULL,
  `sede_id`        INT UNSIGNED NOT NULL,
  `estado`         ENUM('pre_inscrito','activa','retirada','finalizada','suspendida') NOT NULL DEFAULT 'pre_inscrito',
  `periodo`        VARCHAR(20)  NOT NULL COMMENT 'Ej: 2026-1, 2026-2',
  `fecha_matricula` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `observaciones`  TEXT         DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_matriculas_estudiante` (`estudiante_id`),
  KEY `fk_matriculas_grupo`      (`grupo_id`),
  KEY `fk_matriculas_sede`       (`sede_id`),
  CONSTRAINT `fk_matriculas_estudiante`
    FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matriculas_grupo`
    FOREIGN KEY (`grupo_id`)      REFERENCES `grupos`      (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_matriculas_sede`
    FOREIGN KEY (`sede_id`)       REFERENCES `sedes`       (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  10. PAGOS (cabecera — un registro por matrícula)
-- ============================================================
CREATE TABLE `pagos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula_id`  INT UNSIGNED NOT NULL,
  `padre_id`      INT UNSIGNED NOT NULL,
  `valor_total`   DECIMAL(10,2) NOT NULL,
  `valor_pagado`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado`        ENUM('pendiente','parcial','pagado','vencido','exonerado') NOT NULL DEFAULT 'pendiente',
  `fecha_limite`  DATE          DEFAULT NULL,
  `observaciones` TEXT          DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pagos_matricula` (`matricula_id`),
  KEY `fk_pagos_padre`     (`padre_id`),
  CONSTRAINT `fk_pagos_matricula`
    FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pagos_padre`
    FOREIGN KEY (`padre_id`)     REFERENCES `padres`     (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  11. PAGOS — ABONOS (detalle de cada pago parcial)
-- ============================================================
CREATE TABLE `pagos_abonos` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pago_id`         INT UNSIGNED NOT NULL,
  `valor`           DECIMAL(10,2) NOT NULL,
  `medio_pago`      ENUM('efectivo','transferencia','nequi','daviplata','pse','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
  `comprobante`     VARCHAR(255) DEFAULT NULL COMMENT 'Ruta /uploads/comprobantes/ o número de ref.',
  `fecha`           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por`  INT UNSIGNED NOT NULL COMMENT 'usuario_id del admin que registra',
  `observaciones`   VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_abonos_pago`    (`pago_id`),
  KEY `fk_abonos_usuario` (`registrado_por`),
  CONSTRAINT `fk_abonos_pago`
    FOREIGN KEY (`pago_id`)        REFERENCES `pagos`    (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_abonos_usuario`
    FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  12. RÚBRICAS
-- ============================================================
CREATE TABLE `rubricas` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `curso_id`    INT UNSIGNED NOT NULL,
  `nombre`      VARCHAR(150) NOT NULL,
  `descripcion` TEXT         DEFAULT NULL,
  `periodo`     VARCHAR(20)  DEFAULT NULL,
  `activa`      TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_rubricas_curso` (`curso_id`),
  CONSTRAINT `fk_rubricas_curso`
    FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  13. CRITERIOS DE LA RÚBRICA
-- ============================================================
CREATE TABLE `rubrica_criterios` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `rubrica_id`   INT UNSIGNED NOT NULL,
  `criterio`     VARCHAR(200) NOT NULL,
  `descripcion`  TEXT         DEFAULT NULL,
  `puntaje_max`  INT UNSIGNED NOT NULL DEFAULT 5,
  `orden`        INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_criterios_rubrica` (`rubrica_id`),
  CONSTRAINT `fk_criterios_rubrica`
    FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  14. EVALUACIONES
-- ============================================================
CREATE TABLE `evaluaciones` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula_id`  INT UNSIGNED NOT NULL,
  `rubrica_id`    INT UNSIGNED NOT NULL,
  `docente_id`    INT UNSIGNED NOT NULL COMMENT 'usuario_id del docente o admin',
  `fecha`         DATE         NOT NULL,
  `observaciones` TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_eval_matricula` (`matricula_id`),
  KEY `fk_eval_rubrica`   (`rubrica_id`),
  KEY `fk_eval_docente`   (`docente_id`),
  CONSTRAINT `fk_eval_matricula`
    FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_eval_rubrica`
    FOREIGN KEY (`rubrica_id`)   REFERENCES `rubricas`   (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_eval_docente`
    FOREIGN KEY (`docente_id`)   REFERENCES `usuarios`   (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  15. DETALLE DE EVALUACIÓN (puntaje por criterio)
-- ============================================================
CREATE TABLE `evaluacion_detalle` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evaluacion_id`  INT UNSIGNED NOT NULL,
  `criterio_id`    INT UNSIGNED NOT NULL,
  `puntaje`        INT UNSIGNED NOT NULL DEFAULT 0,
  `observacion`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_eval_criterio` (`evaluacion_id`,`criterio_id`),
  KEY `fk_detalle_evaluacion` (`evaluacion_id`),
  KEY `fk_detalle_criterio`   (`criterio_id`),
  CONSTRAINT `fk_detalle_evaluacion`
    FOREIGN KEY (`evaluacion_id`) REFERENCES `evaluaciones`      (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_detalle_criterio`
    FOREIGN KEY (`criterio_id`)   REFERENCES `rubrica_criterios` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
--  16. DOCENTE — GRUPOS ASIGNADOS
-- ============================================================
CREATE TABLE IF NOT EXISTS `docente_grupos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `docente_id`  INT UNSIGNED NOT NULL,
  `grupo_id`    INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_docente_grupo` (`docente_id`,`grupo_id`),
  CONSTRAINT `fk_dg_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dg_grupo`   FOREIGN KEY (`grupo_id`)   REFERENCES `grupos`   (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- Las 3 sedes de ROBOTSchool
INSERT IGNORE INTO `sedes` (`id`, `nombre`, `ciudad`, `direccion`, `telefono`, `email`) VALUES
(1, 'Sede 75 San Felipe',  'Bogotá',  'Calle 75 # San Felipe, Bogotá',   NULL, NULL),
(2, 'Sede Norte 136',      'Bogotá',  'Calle 136 Norte, Bogotá',         NULL, NULL),
(3, 'Sede Cali',           'Cali',    'Cali, Valle del Cauca',            NULL, NULL);

-- ============================================================
--  USUARIOS INICIALES
--  Las contraseñas se generan con setup_admin.php
--  Por ahora se inserta un placeholder que será reemplazado.
-- ============================================================
INSERT INTO `usuarios` (`sede_id`, `nombre`, `email`, `password_hash`, `rol`) VALUES
(NULL, 'Administrador General', 'admin@robotschool.com.co',   'PENDIENTE', 'admin_general'),
(1,    'Admin Sede 75',         'sede75@robotschool.com.co',   'PENDIENTE', 'admin_sede'),
(2,    'Admin Sede Norte',      'sedenorte@robotschool.com.co','PENDIENTE', 'admin_sede'),
(3,    'Admin Sede Cali',       'sedecali@robotschool.com.co', 'PENDIENTE', 'admin_sede');

-- ============================================================
--  PASO FINAL: ejecuta setup_admin.php en el navegador
--  para generar los hashes reales y activar los accesos.
-- ============================================================
--  FIN install.sql — ROBOTSchool Academy Learning v1.0
-- ============================================================
