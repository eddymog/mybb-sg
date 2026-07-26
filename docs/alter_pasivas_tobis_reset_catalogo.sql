-- ============================================================================
-- Reinicia mybb_sg_sg_pasivas_tobis desde cero (en vez de migrar en sitio) en
-- una instalación que ya corrió la versión vieja de alter_pasivas_tobis.sql
-- (con las columnas efecto_texto/codigo_efecto).
--
-- Solo toca esta tabla — mybb_sg_sg_ficha_pasivas, mybb_sg_sg_historial_pasivas
-- y las columnas nuevas de mybb_sg_sg_objetos NO se tocan (ya existen de la
-- corrida anterior).
--
-- Nota: si algún personaje ya compró una pasiva, su fila en
-- mybb_sg_sg_ficha_pasivas no se borra (no hay FK), pero el JOIN contra el
-- catálogo recién recreado devolverá los mismos datos siempre que el
-- pasiva_id no haya cambiado — mismo comportamiento de fallback que ya
-- describe sg_ficha_pasivas_compradas() en sg_functions.php.
-- ============================================================================

DROP TABLE IF EXISTS `mybb_sg_sg_pasivas_tobis`;

-- ----------------------------------------------------------------------------
-- 1) Catálogo de pasivas permanentes (schema nuevo, sin efecto_texto/codigo_efecto)
-- ----------------------------------------------------------------------------
CREATE TABLE `mybb_sg_sg_pasivas_tobis` (
  `id` int(11) NOT NULL,
  `pasiva_id` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `nombre` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8_unicode_ci NOT NULL,
  `imagen` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `coste` int(11) NOT NULL DEFAULT '0',
  `requiere_programacion` tinyint(1) NOT NULL DEFAULT '0',
  `en_tienda` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `mybb_sg_sg_pasivas_tobis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pasiva_id` (`pasiva_id`);

ALTER TABLE `mybb_sg_sg_pasivas_tobis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ----------------------------------------------------------------------------
-- 2) Datos semilla — 13 pasivas permanentes (Familia A), efecto mecánico ya
--    incorporado como texto dentro de `descripcion`.
-- ----------------------------------------------------------------------------
INSERT INTO `mybb_sg_sg_pasivas_tobis`
  (`pasiva_id`, `nombre`, `descripcion`, `coste`, `requiere_programacion`) VALUES
('presencia_abrumadora', 'Presencia Abrumadora',
 'El chakra del usuario es de una pureza inusual, fluyendo con tanta intensidad que fortalece y abre más sus tenketsu. Esto le otorga un control y poder superior al manipular su energía. Sin embargo, esta misma fuerza interior genera una presencia palpable, difícil de ocultar: incluso sin proponérselo, el usuario resulta evidente para quienes lo rodean, perdiendo parte de su capacidad de moverse en silencio o pasar desapercibido.\n\nEfecto: Obtiene +5 Tenketsu y −5 Sigilo.', 40, 1),
('piel_de_piedra', 'Piel de Piedra',
 'La piel del usuario es más gruesa y compacta de lo normal, como si estuviera recubierta por una coraza natural. Esto le permite resistir mejor cortes y heridas sangrantes, ya que la sangre tarda más en fluir fuera del cuerpo. Sin embargo, esta densidad anormal también dificulta la correcta oxigenación de los tejidos y sobrecarga los órganos, lo que reduce la salud general del individuo.\n\nEfecto: [SAL/2] Reducción al nivel de Sangrado, −5 Salud.', 30, 1),
('huesos_de_hierro', 'Huesos de Hierro',
 'El cuerpo del usuario es más sólido y resistente de lo normal, como si sus huesos fueran forjados en acero. Puede soportar golpes, caídas y presiones físicas que dejarían a otros fuera de combate. Sin embargo, esa densidad extra también le resta agilidad y rapidez en sus movimientos.\n\nEfecto: −3 m/s Velocidad de Movimiento, +5 Velocidad para resistir Lesiones.', 20, 1),
('fuerza_descomunal', 'Fuerza Descomunal',
 'El usuario desarrolla una potencia física extraordinaria, capaz de ejecutar ataques devastadores y levantar cargas imposibles para la mayoría. Su cuerpo prioriza la fuerza bruta sobre la agilidad fina, por lo que, aunque puede golpear con gran poder, sus movimientos precisos para esquivar ataques se ven afectados.\n\nEfecto: Obtiene +20 de Fuerza, −1 al Potencial de Esquiva.', 20, 1),
('genio', 'Genio',
 'El usuario posee un talento excepcional que le permite sobresalir en un área de su elección: fuerza, destreza, inteligencia o control de chakra. Su capacidad innata para adaptarse y mejorar le da ventaja sobre sus compañeros, aunque debe concentrarse en un solo aspecto a la vez para no dispersar su potencial.\n\nEfecto: Elige una estadística: fuerza, destreza, inteligencia o control de chakra. Obtiene +1 de Modificador en la estadística elegida.', 40, 1),
('afinidad', 'Afinidad',
 'El usuario debe elegir un elemento, incluido Yin-Yang. Una vez seleccionado, podrá activar su Afinidad Elemental un número de veces según su Control de Chakra para elegir uno de los siguientes efectos: aumentar en +1 el Nivel del Jutsu, dificultando su esquiva, o aplicar por segunda vez el efecto del Jutsu Base del elemento elegido durante su ejecución. No afecta a árboles de Clan ni a la ventaja elemental.\n\nEfecto: [MCCKx1] de Veces por Tema.', 50, 0),
('experto_en_sellos_de_mano', 'Experto en Sellos de Mano',
 'El ninja puede ejecutar sellos manuales con una sola mano de forma opcional al pagar un costo adicional de 10 puntos de Chakra por Jutsu. Si ya posee la capacidad de realizar sellos con una sola mano gracias a una Especialidad, Pasiva o cualquier otra habilidad, esta pasiva no surte efecto.', 60, 0),
('nacionalista', 'Nacionalista',
 'Tu próxima Multicuenta podrá ignorar la regla de nacionalidad. Al adquirir esta virtud, esa cuenta podrá pertenecer a la misma nación que este personaje. Esta virtud solo puede adquirirse una vez por personaje.', 30, 0),
('escalar_arboles', 'Escalar Árboles',
 'El ninja aprende a escalar superficies sin necesidad de utilizar las manos, adhiriéndose a ellas únicamente mediante la aplicación de Chakra en sus pies. Este entrenamiento, aunque sencillo en apariencia, fortalece el cuerpo y mejora la velocidad de quien lo practica.\n\nEfecto: Obtiene +5 de Velocidad.', 50, 1),
('caminar_sobre_el_agua', 'Caminar sobre el Agua',
 'Requiere la Pasiva: Escalar Árboles.\n\nTras dominar el desplazamiento sobre superficies sólidas, el ninja aprende a caminar sobre el agua manteniendo un flujo continuo de Chakra en sus pies. La precisión requerida para este entrenamiento favorece un mejor flujo de Chakra a través de sus Tenketsu, mejorando las capacidades del usuario.\n\nEfecto: Obtiene +5 de Tenketsu.', 70, 1),
('ninja_medico', 'Ninja Médico',
 'Requiere la Pasiva: Afinidad Yang\n\nEl ninja aprende los principios fundamentales de la medicina y la curación, permitiéndole utilizar instrumental médico y Ninjutsus de naturaleza Yang destinados a sanar o restaurar el cuerpo de otras personas con mayor eficacia. Al emplear Ninjutsu Médico, su Chakra se manifestará con una tonalidad verde. Además, todos los procedimientos médicos, ya sea Jutsus o Herramientas, que normalmente requieren un turno completo para su ejecución pasarán a realizarse en tan solo 2 segundos.', 30, 0),
('ninja_ilusionista', 'Ninja Ilusionista',
 'Requiere la Pasiva: Afinidad Yin\n\nLos ilusionistas dominan el arte de engañar a la realidad misma, moldeando los Jutsus de naturaleza Yin a su voluntad. Gracias a ello, podrán transformar cualquiera de sus Jutsus Yin en una ilusión, manteniendo sus requisitos y efectos originales, pero dejando de ser considerados técnicas de Control Físico o Control Mental. Además, todos los Genjutsus cuya frecuencia esté limitada a un único uso por combate perderán esa restricción.', 30, 0),
('maestro_elemental', 'Maestro Elemental',
 'Requiere la Pasiva: Afinidad (Katon, Suiton, Raiton, Doton o Futon)\n\nUna vez por combate, el usuario podrá ignorar la ventaja elemental de un Ninjutsu enemigo durante un choque de Jutsus. Además, los Ninjutsus del elemento correspondiente a su Afinidad Elemental obtendrán un aumento de daño durante cualquier Choque contra otro Ninjutsu elemental, independientemente del elemento al que se enfrenten.', 30, 0);
