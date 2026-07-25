-- Log de aperturas de pergaminos, para las estadísticas globales visibles en
-- sg/pergaminos.php (total abiertos, jackpots, desglose por pergamino). Ver
-- docs/pergaminos_diseno.md. Una fila por apertura (el premio PRINCIPAL de
-- esa tirada; las tiradas extra de un jackpot no generan filas propias acá,
-- ya quedan reflejadas en `es_jackpot` de la fila principal).
CREATE TABLE `mybb_sg_sg_gacha_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL,
  `pergamino_id` varchar(20) NOT NULL,
  `premio_id` int(11) NOT NULL,
  `es_jackpot` tinyint(1) NOT NULL DEFAULT '0',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pergamino_id` (`pergamino_id`),
  KEY `uid` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
