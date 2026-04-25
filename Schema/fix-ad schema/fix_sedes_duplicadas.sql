-- fix_sedes_duplicadas.sql
-- Ejecutar en phpMyAdmin si tienes sedes duplicadas en la tabla
-- Solo borra los duplicados, conserva el registro con id más bajo de cada nombre

-- Ver cuáles están duplicadas (ejecuta esto primero para verificar)
-- SELECT nombre, COUNT(*) as total FROM sedes GROUP BY nombre HAVING total > 1;

-- Eliminar duplicados conservando el id más bajo de cada nombre
DELETE s1 FROM sedes s1
INNER JOIN sedes s2
WHERE s1.id > s2.id AND s1.nombre = s2.nombre;

-- Verificar resultado
SELECT id, nombre, ciudad, activa FROM sedes ORDER BY id;
