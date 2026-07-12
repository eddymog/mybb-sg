-- ============================================================================
-- Acceso al Dojo por ficha.
--
-- `puede_usar_dojo` = 1  -> la ficha puede operar el Dojo (dojo.php).
-- `puede_usar_dojo` = 0  -> puede VER la página del Dojo pero con una alerta;
--                          las acciones están bloqueadas hasta que un moderador
--                          revise su ficha y lo ponga en 1.
--
-- El DEFAULT 0 hace que TODAS las fichas existentes queden bloqueadas tras el
-- ALTER (necesitan revisión). Las fichas NUEVAS se crean con 1 (ver nueva_ficha.php).
--
-- Ejecutar una sola vez.
-- ============================================================================

ALTER TABLE `mybb_sg_sg_fichas`
  ADD COLUMN `puede_usar_dojo` tinyint(1) NOT NULL DEFAULT 0;
