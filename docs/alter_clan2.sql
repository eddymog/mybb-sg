-- ============================================================================
-- Clan secundario (clan híbrido) en la ficha.
--
-- `clan2` guarda el cid del segundo clan (clan híbrido). Es opcional y SOLO para
-- mostrar ambos clanes en la ficha; el Dojo no lo usa. El código trata '', '0'
-- y '1001' como «sin clan secundario», por eso el DEFAULT '' deja válidas todas
-- las fichas existentes.
--
-- Mismo tipo/charset que la columna `clan` primaria. Ejecutar una sola vez.
-- ============================================================================

ALTER TABLE `mybb_sg_sg_fichas`
  ADD COLUMN `clan2` varchar(20) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL DEFAULT '' AFTER `clan`;
