-- ============================================================================
-- Estadisticas PASIVAS (invisibles) para fichas y snapshots de personaje.
--
-- 4 pasivas para las estadisticas principales (fuerza, destreza, cchakra,
-- inteligencia) y 4 para las secundarias (salud, velocidad, tenketsu, sigilo).
-- Se SUMAN a las estadisticas base para el calculo real de modificadores,
-- vida, chakra y regeneracion de chakra. Solo el staff / procesos automaticos
-- las modifican; los usuarios nunca las editan.
--
-- Ejecutar una sola vez sobre la base de datos.
-- ============================================================================

-- Ficha del personaje
ALTER TABLE `mybb_sg_sg_fichas`
  ADD COLUMN `pas_fuerza`       int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_destreza`     int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_cchakra`      int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_inteligencia` int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_salud`        int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_velocidad`    int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_tenketsu`     int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_sigilo`       int(11) NOT NULL DEFAULT '0';

-- Snapshot de estadisticas por tema/post ([personaje])
ALTER TABLE `mybb_sg_sg_thread_personaje`
  ADD COLUMN `pas_fuerza`       int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_destreza`     int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_cchakra`      int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_inteligencia` int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_salud`        int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_velocidad`    int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_tenketsu`     int(11) NOT NULL DEFAULT '0',
  ADD COLUMN `pas_sigilo`       int(11) NOT NULL DEFAULT '0';
