-- Historial de combates 1v1 (metadata de referencia: victorias/empates/derrotas
-- por usuario). Se llena automáticamente desde sg/admin/recompensas_mision.php
-- al CONFIRMAR una recompensa de combate (una fila por combatiente). Se
-- muestra en la pestaña "Estadísticas" de la ficha.
--
-- `modo` identifica el tipo de combate ('combate_1v1' por ahora) para poder
-- añadir otras variantes en el futuro (p.ej. equipos, torneos) sin tocar el
-- esquema — cada modo se cuenta y muestra por separado.
--
-- No sustituye a mybb_sg_sg_audit_consola_mod (esa es la auditoría de staff,
-- texto libre); esta tabla es estructurada para poder agregar/contar
-- victorias/empates/derrotas de forma eficiente.

CREATE TABLE `mybb_sg_sg_historial_combates` (
  `id` int(11) NOT NULL,
  `tid` int(10) UNSIGNED NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `modo` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'combate_1v1',
  `resultado` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `nivel_propio` int(11) NOT NULL DEFAULT '0',
  `nivel_oponente` int(11) NOT NULL DEFAULT '0',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Indexes for table `mybb_sg_sg_historial_combates`
--
ALTER TABLE `mybb_sg_sg_historial_combates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uid_modo_resultado` (`uid`,`modo`,`resultado`),
  ADD KEY `tid` (`tid`);

--
-- AUTO_INCREMENT for table `mybb_sg_sg_historial_combates`
--
ALTER TABLE `mybb_sg_sg_historial_combates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
