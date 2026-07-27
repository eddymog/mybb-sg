-- ============================================================================
-- Pergaminos de gacha dinámicos: Staff puede crear pergaminos nuevos desde
-- gestionar_objetos.php sin tocar código. Antes, la lista de pergaminos
-- válidos para el gacha (PERG001..PERG007) estaba hardcodeada en
-- sg_gacha_pergamino_ids() (sg_functions.php).
--
-- `en_gacha=1` en un objeto tipo='Pergamino' lo agrega a esa lista (que ahora
-- se arma con un SELECT en vez de un array fijo). El resto del sistema de
-- gacha (sorteo, gestionar_pergaminos.php) no cambia: cualquier objeto_id que
-- devuelva la lista ya funciona como pergamino de gacha.
--
-- Sin cambios en el sistema de misiones: sg_mision_pergaminos() (el mapa de
-- rango E..S -> PERG001..PERG007) queda igual, hardcodeado; los pergaminos
-- nuevos que cree Staff son extras exclusivos del gacha, no premio de misión.
-- ============================================================================

ALTER TABLE `mybb_sg_sg_objetos`
  ADD COLUMN `en_gacha` tinyint(1) NOT NULL DEFAULT '0' AFTER `en_tienda_tobis`;

-- Migra los 7 pergaminos ya existentes, para que no desaparezcan del gacha
-- al aplicar este cambio.
UPDATE `mybb_sg_sg_objetos` SET `en_gacha` = '1'
  WHERE `objeto_id` IN ('PERG001','PERG002','PERG003','PERG004','PERG005','PERG006','PERG007');
