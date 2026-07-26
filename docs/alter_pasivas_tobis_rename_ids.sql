-- ============================================================================
-- Renombra pasiva_id de códigos genéricos (TOBI001…TOBI013) a un slug legible
-- basado en el nombre de cada pasiva (ej. 'presencia_abrumadora').
--
-- 1) Ensancha `pasiva_id` a varchar(60) en las 3 tablas que lo usan (algunos
--    slugs, ej. 'experto_en_sellos_de_mano', no entran en el varchar(20)
--    original). Mismo ancho que tenía la vieja columna codigo_efecto.
-- 2) Actualiza el catálogo (mybb_sg_sg_pasivas_tobis) y, por si ya hay
--    personajes con alguna pasiva comprada o historial registrado, también
--    mybb_sg_sg_ficha_pasivas y mybb_sg_sg_historial_pasivas — mismo patrón
--    que ya usa gestionar_pasivas_tobis.php cuando se edita el ID a mano.
--
-- Ejecutar una sola vez.
-- ============================================================================

ALTER TABLE `mybb_sg_sg_pasivas_tobis`
  MODIFY `pasiva_id` varchar(60) COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `mybb_sg_sg_ficha_pasivas`
  MODIFY `pasiva_id` varchar(60) COLLATE utf8_unicode_ci NOT NULL;
ALTER TABLE `mybb_sg_sg_historial_pasivas`
  MODIFY `pasiva_id` varchar(60) COLLATE utf8_unicode_ci NOT NULL;

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'presencia_abrumadora'      WHERE `pasiva_id` = 'TOBI001';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'presencia_abrumadora'      WHERE `pasiva_id` = 'TOBI001';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'presencia_abrumadora'     WHERE `pasiva_id` = 'TOBI001';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'piel_de_piedra'           WHERE `pasiva_id` = 'TOBI002';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'piel_de_piedra'           WHERE `pasiva_id` = 'TOBI002';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'piel_de_piedra'          WHERE `pasiva_id` = 'TOBI002';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'huesos_de_hierro'         WHERE `pasiva_id` = 'TOBI003';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'huesos_de_hierro'         WHERE `pasiva_id` = 'TOBI003';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'huesos_de_hierro'        WHERE `pasiva_id` = 'TOBI003';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'fuerza_descomunal'        WHERE `pasiva_id` = 'TOBI004';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'fuerza_descomunal'        WHERE `pasiva_id` = 'TOBI004';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'fuerza_descomunal'       WHERE `pasiva_id` = 'TOBI004';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'genio'                    WHERE `pasiva_id` = 'TOBI005';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'genio'                    WHERE `pasiva_id` = 'TOBI005';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'genio'                   WHERE `pasiva_id` = 'TOBI005';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'afinidad'                 WHERE `pasiva_id` = 'TOBI006';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'afinidad'                 WHERE `pasiva_id` = 'TOBI006';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'afinidad'                WHERE `pasiva_id` = 'TOBI006';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'experto_en_sellos_de_mano' WHERE `pasiva_id` = 'TOBI007';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'experto_en_sellos_de_mano' WHERE `pasiva_id` = 'TOBI007';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'experto_en_sellos_de_mano' WHERE `pasiva_id` = 'TOBI007';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'nacionalista'             WHERE `pasiva_id` = 'TOBI008';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'nacionalista'             WHERE `pasiva_id` = 'TOBI008';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'nacionalista'            WHERE `pasiva_id` = 'TOBI008';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'escalar_arboles'          WHERE `pasiva_id` = 'TOBI009';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'escalar_arboles'          WHERE `pasiva_id` = 'TOBI009';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'escalar_arboles'         WHERE `pasiva_id` = 'TOBI009';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'caminar_sobre_el_agua'    WHERE `pasiva_id` = 'TOBI010';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'caminar_sobre_el_agua'    WHERE `pasiva_id` = 'TOBI010';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'caminar_sobre_el_agua'   WHERE `pasiva_id` = 'TOBI010';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'ninja_medico'             WHERE `pasiva_id` = 'TOBI011';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'ninja_medico'             WHERE `pasiva_id` = 'TOBI011';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'ninja_medico'            WHERE `pasiva_id` = 'TOBI011';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'ninja_ilusionista'        WHERE `pasiva_id` = 'TOBI012';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'ninja_ilusionista'        WHERE `pasiva_id` = 'TOBI012';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'ninja_ilusionista'       WHERE `pasiva_id` = 'TOBI012';

UPDATE `mybb_sg_sg_pasivas_tobis`    SET `pasiva_id` = 'maestro_elemental'        WHERE `pasiva_id` = 'TOBI013';
UPDATE `mybb_sg_sg_ficha_pasivas`    SET `pasiva_id` = 'maestro_elemental'        WHERE `pasiva_id` = 'TOBI013';
UPDATE `mybb_sg_sg_historial_pasivas` SET `pasiva_id` = 'maestro_elemental'       WHERE `pasiva_id` = 'TOBI013';
