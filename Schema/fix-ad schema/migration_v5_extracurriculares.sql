-- Schema/migration_v5_extracurriculares.sql
-- Modulo Extracurriculares: actividades en colegios empresas e instituciones
-- Prefijo ec_ para todas las tablas

-- ============================================================
-- ec_clientes: colegios empresas instituciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_clientes` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo`              ENUM('colegio','empresa','institucion','otro') NOT NULL DEFAULT 'colegio',
  `nombre`            VARCHAR(200) NOT NULL,
  `nit`               VARCHAR(30)  DEFAULT NULL COMMENT 'NIT o identificacion fiscal',
  `razon_social`      VARCHAR(200) DEFAULT NULL COMMENT 'Si difiere del nombre comercial',
  `ciudad`            VARCHAR(100) DEFAULT NULL,
  `direccion`         VARCHAR(250) DEFAULT NULL,
  `barrio`            VARCHAR(100) DEFAULT NULL,
  `latitud`           DECIMAL(10,7) DEFAULT NULL,
  `longitud`          DECIMAL(10,7) DEFAULT NULL,
  `telefono`          VARCHAR(30)  DEFAULT NULL,
  `email`             VARCHAR(150) DEFAULT NULL,
  `sitio_web`         VARCHAR(200) DEFAULT NULL,
  `logo`              VARCHAR(255) DEFAULT NULL COMMENT 'Ruta en /uploads/ec_clientes/',
  `contacto_nombre`   VARCHAR(150) DEFAULT NULL COMMENT 'Contacto principal rectoria coordinacion',
  `contacto_cargo`    VARCHAR(100) DEFAULT NULL,
  `contacto_telefono` VARCHAR(30)  DEFAULT NULL,
  `contacto_email`    VARCHAR(150) DEFAULT NULL,
  `notas`             TEXT         DEFAULT NULL,
  `activo`            TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_cliente_tipo` (`tipo`, `activo`),
  KEY `idx_ec_cliente_ciudad` (`ciudad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_contratos: un cliente puede tener N contratos a lo largo del tiempo
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_contratos` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cliente_id`      INT UNSIGNED NOT NULL,
  `codigo`          VARCHAR(30)  DEFAULT NULL COMMENT 'Codigo interno legible ej EC-2026-001',
  `nombre`          VARCHAR(200) NOT NULL COMMENT 'Ej Robotica 2026-1 Gimnasio Moderno',
  `fecha_inicio`    DATE         NOT NULL,
  `fecha_fin`       DATE         NOT NULL,
  `tipo_duracion`   ENUM('mensual','bimestral','trimestral','semestral','anual','personalizado') NOT NULL DEFAULT 'semestral',
  `valor_total`     DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total pactado del contrato (suma de programas)',
  `condiciones_pago` VARCHAR(200) DEFAULT NULL COMMENT 'Ej pago mensual contra factura',
  `estado`          ENUM('borrador','vigente','suspendido','finalizado','cancelado') NOT NULL DEFAULT 'borrador',
  `observaciones`   TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ec_contrato_codigo` (`codigo`),
  KEY `idx_ec_contrato_cliente` (`cliente_id`, `estado`),
  CONSTRAINT `fk_ec_contrato_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `ec_clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_programas: dentro de un contrato N programas un contrato puede tener
-- varios cursos ofrecidos en paralelo a diferentes grupos
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_programas` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `contrato_id`     INT UNSIGNED NOT NULL,
  `curso_id`        INT UNSIGNED DEFAULT NULL COMMENT 'FK a cursos RSAL contenido pedagogico reutilizado',
  `nombre`          VARCHAR(200) NOT NULL COMMENT 'Ej LEGO SPIKE 3ero y 4to',
  `equipos_kit`     VARCHAR(200) DEFAULT NULL COMMENT 'Ej LEGO SPIKE Prime Arduino UNO',
  `grado_desde`     VARCHAR(30)  DEFAULT NULL COMMENT 'Ej 3o 1ro bachillerato',
  `grado_hasta`     VARCHAR(30)  DEFAULT NULL,
  `edad_min`        TINYINT UNSIGNED DEFAULT NULL,
  `edad_max`        TINYINT UNSIGNED DEFAULT NULL,
  `cantidad_ninos`  INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Cantidad de ninos planeada para el programa (base del cobro)',
  `minimo_ninos`    INT UNSIGNED NOT NULL DEFAULT 10 COMMENT 'Minimo viable bajo este numero se alerta pero no se bloquea',
  `dia_semana`      ENUM('lunes','martes','miercoles','jueves','viernes','sabado','domingo') NOT NULL,
  `hora_inicio`     TIME         NOT NULL,
  `hora_fin`        TIME         NOT NULL,
  `total_sesiones`  INT UNSIGNED NOT NULL DEFAULT 4 COMMENT 'Fijo en 4 por paquete 1 sesion semanal',
  `valor_por_nino`  DECIMAL(10,2) NOT NULL DEFAULT 120000.00 COMMENT 'Tarifa por nino paquete de 4 sesiones',
  `color`           VARCHAR(7)   DEFAULT '#7c3aed' COMMENT 'Color para calendario visual',
  `estado`          ENUM('planeado','en_curso','finalizado','suspendido') NOT NULL DEFAULT 'planeado',
  `observaciones`   TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_programa_contrato` (`contrato_id`),
  KEY `idx_ec_programa_curso` (`curso_id`),
  CONSTRAINT `fk_ec_programa_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `ec_contratos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_programa_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_sesiones: sesiones individuales generadas a partir del programa
-- Cada fila es una clase concreta con fecha hora y estado
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_sesiones` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `programa_id`       INT UNSIGNED NOT NULL,
  `numero_sesion`     INT UNSIGNED NOT NULL COMMENT 'Secuencia 1 2 3 ... en el programa',
  `fecha`             DATE         NOT NULL,
  `hora_inicio`       TIME         NOT NULL,
  `hora_fin`          TIME         NOT NULL,
  `tema_planeado`     VARCHAR(200) DEFAULT NULL,
  `estado`            ENUM('programada','dictada','fallida_justificada','fallida_no_justificada','recuperada','cancelada') NOT NULL DEFAULT 'programada',
  `motivo_falla`      VARCHAR(255) DEFAULT NULL,
  `sesion_original_id` INT UNSIGNED DEFAULT NULL COMMENT 'Si es recuperacion referencia la sesion fallida',
  `observaciones`     TEXT         DEFAULT NULL,
  `registrado_por`    INT UNSIGNED DEFAULT NULL COMMENT 'Tallerista que cerro la sesion',
  `created_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ec_sesion_programa_numero` (`programa_id`, `numero_sesion`),
  KEY `idx_ec_sesion_fecha` (`fecha`, `estado`),
  KEY `idx_ec_sesion_original` (`sesion_original_id`),
  CONSTRAINT `fk_ec_sesion_programa` FOREIGN KEY (`programa_id`) REFERENCES `ec_programas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_sesion_original` FOREIGN KEY (`sesion_original_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ec_sesion_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_asignaciones: que tallerista va a que sesion
-- Una sesion puede tener principal + apoyo
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_asignaciones` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_id`     INT UNSIGNED NOT NULL,
  `tallerista_id` INT UNSIGNED NOT NULL,
  `rol`           ENUM('principal','apoyo') NOT NULL DEFAULT 'principal',
  `confirmado`    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Si el tallerista confirmo asistencia',
  `notas`         VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ec_asig_sesion_tallerista` (`sesion_id`, `tallerista_id`),
  KEY `idx_ec_asig_tallerista` (`tallerista_id`),
  CONSTRAINT `fk_ec_asig_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_asig_tallerista` FOREIGN KEY (`tallerista_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_estudiantes: listado minimalista por programa
-- No son fichas completas como en RSAL
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_estudiantes` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `programa_id`     INT UNSIGNED NOT NULL,
  `nombre_completo` VARCHAR(150) NOT NULL,
  `grado`           VARCHAR(30)  DEFAULT NULL,
  `edad`            TINYINT UNSIGNED DEFAULT NULL,
  `documento`       VARCHAR(30)  DEFAULT NULL COMMENT 'Opcional',
  `observaciones`   VARCHAR(255) DEFAULT NULL,
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_estudiante_programa` (`programa_id`, `activo`),
  CONSTRAINT `fk_ec_estudiante_programa` FOREIGN KEY (`programa_id`) REFERENCES `ec_programas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_asistencia: estado por estudiante por sesion
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_asistencia` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sesion_id`       INT UNSIGNED NOT NULL,
  `estudiante_id`   INT UNSIGNED NOT NULL,
  `estado`          ENUM('presente','tarde','ausente','excusa') NOT NULL DEFAULT 'ausente',
  `observacion`     VARCHAR(255) DEFAULT NULL,
  `registrado_por`  INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ec_asist` (`sesion_id`, `estudiante_id`),
  KEY `idx_ec_asist_estudiante` (`estudiante_id`),
  CONSTRAINT `fk_ec_asist_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `ec_sesiones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_asist_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `ec_estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_asist_registrador` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_evaluaciones: evaluacion de un estudiante con una rubrica RSAL
-- Reutiliza rubricas y rubrica_criterios del modulo academico
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_evaluaciones` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `estudiante_id`   INT UNSIGNED NOT NULL,
  `rubrica_id`      INT UNSIGNED NOT NULL COMMENT 'FK a rubricas RSAL',
  `docente_id`      INT UNSIGNED NOT NULL COMMENT 'Tallerista que evalua',
  `fecha`           DATE         NOT NULL,
  `observaciones`   TEXT         DEFAULT NULL,
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_eval_estudiante` (`estudiante_id`),
  KEY `idx_ec_eval_rubrica` (`rubrica_id`),
  CONSTRAINT `fk_ec_eval_estudiante` FOREIGN KEY (`estudiante_id`) REFERENCES `ec_estudiantes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_eval_rubrica` FOREIGN KEY (`rubrica_id`) REFERENCES `rubricas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_eval_docente` FOREIGN KEY (`docente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_evaluacion_detalle: puntaje por criterio
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_evaluacion_detalle` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `evaluacion_id`  INT UNSIGNED NOT NULL,
  `criterio_id`    INT UNSIGNED NOT NULL COMMENT 'FK a rubrica_criterios',
  `puntaje`        INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ec_eval_det` (`evaluacion_id`, `criterio_id`),
  CONSTRAINT `fk_ec_eval_det_eval` FOREIGN KEY (`evaluacion_id`) REFERENCES `ec_evaluaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ec_eval_det_criterio` FOREIGN KEY (`criterio_id`) REFERENCES `rubrica_criterios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ec_desplazamientos_cache: evita calcular distancias repetidas
-- Para el calendario con deteccion de tiempos entre sesiones
-- ============================================================
CREATE TABLE IF NOT EXISTS `ec_desplazamientos_cache` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `origen_lat`       DECIMAL(10,7) NOT NULL,
  `origen_lng`       DECIMAL(10,7) NOT NULL,
  `destino_lat`      DECIMAL(10,7) NOT NULL,
  `destino_lng`      DECIMAL(10,7) NOT NULL,
  `distancia_km`     DECIMAL(8,2) NOT NULL COMMENT 'Haversine o API real',
  `duracion_min`     INT UNSIGNED NOT NULL COMMENT 'Estimacion en minutos',
  `origen_nombre`    VARCHAR(200) DEFAULT NULL,
  `destino_nombre`   VARCHAR(200) DEFAULT NULL,
  `created_at`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ec_desp_coords` (`origen_lat`, `origen_lng`, `destino_lat`, `destino_lng`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Datos semilla las 3 sedes RSAL como clientes opcional
-- Comentado por defecto descomenta si quieres
-- ============================================================
-- INSERT IGNORE INTO ec_clientes (tipo, nombre, ciudad, activo) VALUES
--   ('colegio', 'Gimnasio Moderno', 'Bogota', 1),
--   ('colegio', 'Colegio Colombo Americano', 'Bogota', 1);
