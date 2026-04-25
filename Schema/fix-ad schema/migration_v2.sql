-- ============================================================
--  RSAL Migration v2
--  - Nuevo rol: coordinador_pedagogico
--  - Nueva tabla: matricula_historial (avances de modulo)
-- ============================================================

-- 1. Agregar coordinador_pedagogico al ENUM de roles
ALTER TABLE `usuarios`
  MODIFY COLUMN `rol` ENUM('admin_general','admin_sede','coordinador_pedagogico','docente','padre')
  NOT NULL DEFAULT 'padre';

-- 2. Tabla historial de avances de matricula
CREATE TABLE IF NOT EXISTS `matricula_historial` (
  `id`                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `matricula_id`       INT UNSIGNED NOT NULL COMMENT 'Matricula original (queda finalizada)',
  `grupo_id_anterior`  INT UNSIGNED NOT NULL,
  `grupo_id_nuevo`     INT UNSIGNED NOT NULL,
  `motivo`             VARCHAR(255) DEFAULT 'Avance de modulo',
  `usuario_id`         INT UNSIGNED DEFAULT NULL COMMENT 'Quien registro el avance',
  `fecha`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_mh_matricula`  (`matricula_id`),
  KEY `fk_mh_g_anterior` (`grupo_id_anterior`),
  KEY `fk_mh_g_nuevo`    (`grupo_id_nuevo`),
  CONSTRAINT `fk_mh_matricula`
    FOREIGN KEY (`matricula_id`) REFERENCES `matriculas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mh_g_anterior`
    FOREIGN KEY (`grupo_id_anterior`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mh_g_nuevo`
    FOREIGN KEY (`grupo_id_nuevo`) REFERENCES `grupos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
--  Fin migration_v2.sql
-- ============================================================
