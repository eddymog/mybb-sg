-- ============================================================================
-- Banners rotativos del header (gestionados por staff).
--
-- `activo`  -> 0/1. Solo los banners activos entran en la rotación.
-- `orden`   -> orden de rotación (ascendente); empate se rompe por id.
-- Sin columna de "enlace": el click en el banner siempre lleva a la portada,
-- igual que el comportamiento actual (decisión de diseño).
--
-- Ejecutar una sola vez.
-- ============================================================================

CREATE TABLE `mybb_sg_sg_banners` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `imagen` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `titulo` varchar(150) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int(10) NOT NULL DEFAULT '0',
  `agregado_por` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
