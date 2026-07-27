-- ============================================================================
-- Sección de Afiliados (índice) + solicitud pública de afiliación.
-- Ver docs/afiliados_diseno.md para el diseño completo.
--
-- mybb_sg_sg_afiliados: catálogo de afiliados en sus 3 niveles (hermano /
-- grande / pequeno), administrado desde sg/admin/gestionar_afiliados.php y
-- mostrado en el índice vía inc/plugins/sg_afiliados.php (hook index_end).
--
-- Las solicitudes públicas de afiliación NO tienen tabla propia: reutilizan
-- mybb_sg_sg_peticiones (categoria='afiliados'), la consola de Staff ya
-- existente en sg/admin/peticiones_admin.php.
--
-- mybb_sg_sg_afiliados_rate_limit: tabla auxiliar mínima para el rate-limit
-- por IP del formulario público sin login (sg/peticion_afiliados.php).
-- ============================================================================

CREATE TABLE `mybb_sg_sg_afiliados` (
  `id` int(11) NOT NULL,
  `tipo` enum('hermano','grande','pequeno') COLLATE utf8_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `agregado_por` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_afiliados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tipo_orden` (`tipo`, `orden`);

ALTER TABLE `mybb_sg_sg_afiliados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------------
-- Rate-limit del formulario público (1 solicitud cada X minutos por IP).
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_afiliados_rate_limit` (
  `id` int(11) NOT NULL,
  `ip` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_afiliados_rate_limit`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_tiempo` (`ip`, `tiempo`);

ALTER TABLE `mybb_sg_sg_afiliados_rate_limit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
