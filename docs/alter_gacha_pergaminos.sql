-- Sistema de gacha de pergaminos (PERG001..PERG007). Ver docs/pergaminos_diseno.md.
--
-- mybb_sg_sg_gacha_premios: los "casilleros" del sorteo de un pergamino, cada
-- uno con su probabilidad. es_jackpot=1 dispara jackpot_tiradas sorteos extra
-- gratis sobre el mismo pool (excluyendo otros jackpots) en vez de entregar
-- recompensas propias.
--
-- mybb_sg_sg_gacha_recompensas: el paquete de recompensas de un premio
-- (uno-a-muchos): un mismo premio puede dar cualquier combinación de
-- ryos/rin/madara/tobi y/o varios objetos (distintos o repetidos).

CREATE TABLE `mybb_sg_sg_gacha_premios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pergamino_id` varchar(20) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `probabilidad` decimal(5,2) NOT NULL,
  `es_jackpot` tinyint(1) NOT NULL DEFAULT '0',
  `jackpot_tiradas` int(11) NOT NULL DEFAULT '5',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int(11) NOT NULL DEFAULT '0',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pergamino_id` (`pergamino_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `mybb_sg_sg_gacha_recompensas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `premio_id` int(11) NOT NULL,
  `tipo` enum('ryos','rin','madara','tobi','objeto') NOT NULL,
  `valor` int(11) DEFAULT NULL,
  `objeto_id` varchar(20) DEFAULT NULL,
  `cantidad` int(11) NOT NULL DEFAULT '1',
  `orden` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `premio_id` (`premio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
