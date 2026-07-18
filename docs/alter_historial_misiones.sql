-- Historial de misiones por usuario (jugadas / narradas / autonarradas).
-- Se llena automáticamente desde sg/admin/recompensas_mision.php cada vez
-- que se CONFIRMA una recompensa (una fila por recipiente). Sirve como
-- metadata de referencia — se muestra en la nueva pestaña "Estadísticas"
-- de la ficha (sg/ficha.php + templates/html/sg_ficha.html).
--
-- No sustituye a mybb_sg_sg_audit_consola_mod (esa es la auditoría de staff,
-- texto libre); esta tabla es estructurada para poder agregar/contar por
-- rango de forma eficiente.

CREATE TABLE `mybb_sg_sg_historial_misiones` (
  `id` int(11) NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `rol` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `tipo` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `rango` varchar(5) COLLATE utf8_unicode_ci NOT NULL,
  `oficial` tinyint(1) NOT NULL DEFAULT '0',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `mybb_sg_sg_historial_misiones`
--
ALTER TABLE `mybb_sg_sg_historial_misiones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid_rol_rango` (`uid`,`rol`,`rango`),
  ADD KEY `tid` (`tid`);

--
-- AUTO_INCREMENT for table `mybb_sg_sg_historial_misiones`
--
ALTER TABLE `mybb_sg_sg_historial_misiones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
