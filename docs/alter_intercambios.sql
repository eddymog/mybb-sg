-- ============================================================================
-- Intercambios entre jugadores. Ver docs/intercambios_diseno.md para el diseño.
--
-- Flujo: REGALO UNILATERAL. El emisor (from_uid) le entrega ryos y/o objetos al
-- receptor (to_uid); el receptor no entrega nada a cambio. Requisitos: un TID de
-- contacto obligatorio dentro de la zona de rol (foro 37 o descendiente) donde
-- ambos hayan posteado, y que ambos sean de la misma aldea (fichas.villa).
--
-- El movimiento real de ryos/objetos lo loguean solos los choke points del
-- historial (sg_ficha_set_campo / sg_inventario_*), con origen 'intercambio' y
-- un `grupo` compartido, así aparece en la pestaña Historial de AMBAS fichas.
-- Estas dos tablas son SOLO el registro visual (global + personal): cada envío
-- es una fila legible ("A envió X a B"), con sus objetos en la tabla de detalle.
--
-- Todas InnoDB → el envío se envuelve en una transacción con atomicidad real.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1) Cabecera: un envío = una fila.
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_intercambios` (
  `id` int(11) NOT NULL,
  `from_uid` int(10) UNSIGNED NOT NULL,
  `to_uid` int(10) UNSIGNED NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL,
  `ryos` int(11) NOT NULL DEFAULT '0',
  `grupo` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_intercambios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_uid` (`from_uid`),
  ADD KEY `to_uid` (`to_uid`),
  ADD KEY `tiempo` (`tiempo`);

ALTER TABLE `mybb_sg_sg_intercambios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------------
-- 2) Detalle: objetos enviados (0..N por envío).
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_intercambios_items` (
  `id` int(11) NOT NULL,
  `intercambio_id` int(11) NOT NULL,
  `objeto_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_intercambios_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `intercambio_id` (`intercambio_id`);

ALTER TABLE `mybb_sg_sg_intercambios_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
