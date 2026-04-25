-- Schema/migration_v6_ec_asistencia.sql
-- Agrega fecha_ingreso a ec_estudiantes para saber desde cuando
-- se integro el nino al programa (se pueden incorporar durante el curso)

ALTER TABLE `ec_estudiantes`
  ADD COLUMN IF NOT EXISTS `fecha_ingreso` DATE DEFAULT NULL
    COMMENT 'Desde cuando este nino participa en el programa'
    AFTER `edad`;

-- Para los registros existentes si los hay se asigna la fecha de creacion
UPDATE `ec_estudiantes`
   SET `fecha_ingreso` = DATE(`created_at`)
 WHERE `fecha_ingreso` IS NULL;
