-- ============================================================================
-- Backfill de mybb_sg_sg_fichas.arboles_progreso al valor por defecto:
--   {"desbloqueo_arboles":0,"desbloqueo_ramas":0,"desbloqueo_nivel_ramas":0,
--    "nivel_rama_disponibles":0,"clan_rama_usada":0}
--
-- sg_progreso_parse() rellena claves faltantes al leer, así que estos contadores
-- son la ÚNICA parte no derivable del progreso del Dojo (ver arboles_instruciones.txt).
-- ============================================================================

-- Comprobaciones previas -----------------------------------------------------
-- ¿Existe la columna? (0 filas => hace falta un ALTER antes)
--   SHOW COLUMNS FROM `mybb_sg_sg_fichas` LIKE 'arboles_progreso';
-- Si NO existe:
--   ALTER TABLE `mybb_sg_sg_fichas`
--     ADD COLUMN `arboles_progreso` text COLLATE utf8_unicode_ci NOT NULL DEFAULT '';

-- ¿Cuántas fichas afectaría la opción A?
--   SELECT COUNT(*) FROM `mybb_sg_sg_fichas`
--   WHERE `arboles_progreso` IS NULL OR TRIM(`arboles_progreso`) = ''
--      OR `arboles_progreso` = '0' OR `arboles_progreso` NOT LIKE '%desbloqueo_arboles%';

-- ============================================================================
-- OPCIÓN A (RECOMENDADA) — Backfill seguro.
-- Solo toca fichas SIN progreso válido; conserva el progreso ya existente.
-- ============================================================================
UPDATE `mybb_sg_sg_fichas`
SET `arboles_progreso` = '{"desbloqueo_arboles":0,"desbloqueo_ramas":0,"desbloqueo_nivel_ramas":0,"nivel_rama_disponibles":0,"clan_rama_usada":0}'
WHERE `arboles_progreso` IS NULL
   OR TRIM(`arboles_progreso`) = ''
   OR `arboles_progreso` = '0'
   OR `arboles_progreso` NOT LIKE '%desbloqueo_arboles%';

-- ============================================================================
-- OPCIÓN B (DESTRUCTIVA) — Reset total.
-- ⚠️ Pone a CERO el progreso del Dojo de TODAS las fichas (se pierde lo comprado).
-- Descomenta solo si de verdad quieres reiniciar a todos.
-- ============================================================================
-- UPDATE `mybb_sg_sg_fichas`
-- SET `arboles_progreso` = '{"desbloqueo_arboles":0,"desbloqueo_ramas":0,"desbloqueo_nivel_ramas":0,"nivel_rama_disponibles":0,"clan_rama_usada":0}';
