-- Agrega el campo `visibilidad` a mybb_sg_sg_objetos.
-- 1 = Visible (por defecto), 0 = Invisible.
-- Se gestiona desde sg/admin/gestionar_objetos.php (staff_modificar_objetos.html).
ALTER TABLE `mybb_sg_sg_objetos`
  ADD COLUMN `visibilidad` tinyint(1) NOT NULL DEFAULT '1' AFTER `en_tienda`;
