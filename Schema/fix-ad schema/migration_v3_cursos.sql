-- ============================================================
-- Migración v3: Curso único sin sede_id
-- Los grupos ya tienen sede_id — el curso es independiente de sede
-- SEGURA: no borra datos, solo restructura
-- Ejecutar en: robotschool_academy
-- ============================================================

-- PASO 1: Si hay cursos duplicados por nombre (mismo curso en varias sedes),
-- dejar solo el de menor id y reasignar grupos, módulos, materiales, galería
-- -----------------------------------------------------------------------

-- Identificar los ids a conservar (menor id por nombre)
-- y los ids duplicados que se eliminarán
CREATE TEMPORARY TABLE IF NOT EXISTS _curso_keeper AS
    SELECT MIN(id) AS keeper_id, nombre
    FROM cursos GROUP BY nombre;

CREATE TEMPORARY TABLE IF NOT EXISTS _curso_duplicados AS
    SELECT c.id AS dup_id, k.keeper_id
    FROM cursos c
    JOIN _curso_keeper k ON k.nombre = c.nombre AND k.keeper_id != c.id;

-- Reasignar grupos a los ids que se conservan
UPDATE grupos g
JOIN _curso_duplicados d ON d.dup_id = g.curso_id
SET g.curso_id = d.keeper_id;

-- Reasignar módulos
UPDATE curso_modulos m
JOIN _curso_duplicados d ON d.dup_id = m.curso_id
SET m.curso_id = d.keeper_id;

-- Reasignar materiales
UPDATE curso_materiales m
JOIN _curso_duplicados d ON d.dup_id = m.curso_id
SET m.curso_id = d.keeper_id;

-- Reasignar galería
UPDATE curso_galeria g
JOIN _curso_duplicados d ON d.dup_id = g.curso_id
SET g.curso_id = d.keeper_id;

-- Reasignar rúbricas
UPDATE rubricas r
JOIN _curso_duplicados d ON d.dup_id = r.curso_id
SET r.curso_id = d.keeper_id;

-- Eliminar cursos duplicados (los que no son keeper)
DELETE c FROM cursos c
JOIN _curso_duplicados d ON d.dup_id = c.id;

DROP TEMPORARY TABLE IF EXISTS _curso_keeper;
DROP TEMPORARY TABLE IF EXISTS _curso_duplicados;

-- PASO 2: Quitar la columna sede_id de cursos
-- (ya no necesaria: la sede está en grupos)
-- -----------------------------------------------------------------------
ALTER TABLE cursos DROP FOREIGN KEY fk_cursos_sede;
ALTER TABLE cursos DROP COLUMN sede_id;

-- PASO 3: Quitar filtro sede_id del índice si existe
-- -----------------------------------------------------------------------
-- (el índice fk_cursos_sede ya no existe tras DROP FOREIGN KEY)

-- Listo — verificar resultado:
SELECT id, nombre, publicado FROM cursos ORDER BY orden, nombre;
