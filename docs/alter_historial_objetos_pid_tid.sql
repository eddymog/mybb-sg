-- Agrega pid/tid a mybb_sg_sg_historial_objetos (mismo patrón que ya tiene
-- mybb_sg_sg_historial_ficha), para el nuevo tag [consumir=OBJETO_ID]: al
-- consumirse un objeto en un post, el historial de la ficha debe poder
-- enlazar directo al post/tema donde ocurrió ("ver post").
-- NULL para todo lo que no venga de un post (compras, ventas, ajustes de Staff, etc.).
ALTER TABLE `mybb_sg_sg_historial_objetos`
  ADD COLUMN `pid` int(10) UNSIGNED DEFAULT NULL AFTER `origen`,
  ADD COLUMN `tid` int(10) UNSIGNED DEFAULT NULL AFTER `pid`;
