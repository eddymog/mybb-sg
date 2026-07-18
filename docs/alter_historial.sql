-- ============================================================================
-- Historial estructurado de cambios de ficha (Staff + Usuario).
-- Ver docs/instrucciones_cambios.md para el diseño completo.
--
-- Tres tablas, una por dominio (mismo criterio que historial_misiones /
-- historial_combates: una tabla por tipo de evento, no una tabla gigante):
--   * historial_ficha    -> campos escalares de mybb_sg_sg_fichas y de
--                           mybb_sg_users (ej. newpoints).
--   * historial_tecnicas -> técnicas aprendidas / quitadas.
--   * historial_objetos  -> entregas y ventas de inventario (delta).
--
-- NO reemplazan a mybb_sg_sg_audit_consola_mod (auditoría de texto libre de
-- Staff); son la capa estructurada que alimenta el tab "Historial" de la
-- ficha y permite filtrar/sumar por campo con SQL trivial en MySQL 5.7.
--
-- Columnas comunes:
--   uid       -> ficha afectada (dueña del cambio).
--   actor_uid -> QUIÉN hizo el cambio (== uid si fue el propio usuario).
--   grupo     -> token uniqid() que une varias filas de la misma acción
--                (ej. una recompensa que toca ryos+tobi+newpoints). NULL si
--                la acción tocó una sola cosa.
--   tipo      -> 'staff' | 'usuario'.
--   origen    -> constante SG_ORIGEN_* (ver §1.2 del doc).
--   detalle   -> razón (Staff) u observación libre.
--   tiempo    -> se llena solo (CURRENT_TIMESTAMP).
--
-- Estas tablas son APPEND-ONLY: nunca se UPDATE ni DELETE. Para "revertir"
-- un cambio se inserta el cambio inverso.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1) Campos escalares de ficha / usuario
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_historial_ficha` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tabla` enum('fichas','users') COLLATE utf8_unicode_ci NOT NULL,
  `campo` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `valor_anterior` text COLLATE utf8_unicode_ci,
  `valor_nuevo` text COLLATE utf8_unicode_ci,
  `tipo` enum('staff','usuario') COLLATE utf8_unicode_ci NOT NULL,
  `origen` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `pid` int(10) UNSIGNED DEFAULT NULL,
  `tid` int(10) UNSIGNED DEFAULT NULL,
  `detalle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_historial_ficha`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid_tiempo` (`uid`,`tiempo`),
  ADD KEY `grupo` (`grupo`),
  ADD KEY `campo` (`campo`);

ALTER TABLE `mybb_sg_sg_historial_ficha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------------
-- 2) Técnicas aprendidas / quitadas
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_historial_tecnicas` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tecnica_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `accion` enum('aprender','quitar') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'aprender',
  `tipo` enum('staff','usuario') COLLATE utf8_unicode_ci NOT NULL,
  `origen` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `detalle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_historial_tecnicas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid_tiempo` (`uid`,`tiempo`),
  ADD KEY `grupo` (`grupo`);

ALTER TABLE `mybb_sg_sg_historial_tecnicas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------------
-- 3) Objetos de inventario (delta: + ganado, - vendido/gastado)
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_historial_objetos` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(24) COLLATE utf8_unicode_ci DEFAULT NULL,
  `objeto_id` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `cantidad` int(11) NOT NULL,
  `tipo` enum('staff','usuario') COLLATE utf8_unicode_ci NOT NULL,
  `origen` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `detalle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_historial_objetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid_tiempo` (`uid`,`tiempo`),
  ADD KEY `grupo` (`grupo`);

ALTER TABLE `mybb_sg_sg_historial_objetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
