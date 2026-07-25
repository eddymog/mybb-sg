# Tienda de Tobis (Pasivas) — Interpretación y diseño propuesto

Basado en `docs/tienda_tobis.txt` y en el catálogo real de `docs/tobis.txt`. Esto es **solo diseño** — no se implementó código todavía.

> **Estado:** diseño prácticamente cerrado. Queda una sola asunción marcada en la sección 4 para confirmar antes de implementar.

## 1. Mi interpretación de lo que pediste

- Página pública nueva: `sg/tienda_tobis.php` — una tienda que se paga con **Tobis** (no Ryos).
- Visualmente, cada ítem es una tarjeta con imagen propia, en el mismo lenguaje visual que ya usamos para objetos (`sg_objeto_card()` / `objetos.php` / `tienda.php`).
- El gasto de Tobis debe quedar registrado en el **Historial** de la ficha.
- Nueva pestaña **pública** "Pasivas" en la ficha (hay que crearla), mostrando las que el personaje ya compró.
- Herramienta de Staff para editar los campos de texto de una pasiva (nombre, descripción, imagen, costo) — **no** el efecto programado en sí.
- **Fase actual**: se pueden comprar y se ven reflejadas, pero **no tienen ningún efecto real todavía**.
- **Los requisitos entre pasivas no los valida el sistema**: son texto informativo (ya vienen escritos en la descripción de `tobis.txt`, ej. "Requiere la Pasiva: Afinidad Yang"), y es responsabilidad del jugador cumplirlos narrativamente — si no los cumple, simplemente no puede usar la pasiva en juego. Esto simplifica bastante el diseño: no hace falta una tabla de requisitos ni lógica de validación en la compra.

Revisando `docs/tobis.txt` (el catálogo real de 16 entradas), confirmo algo que ya habías anticipado: **no todo es "una pasiva"** en el sentido de "unlock permanente". Hay dos familias distintas mezcladas en la misma lista:

### Familia A — Pasivas permanentes (comprás una vez, quedan para siempre, no se venden)
Presencia Abrumadora, Piel de Piedra, Huesos de Hierro, Fuerza Descomunal, Genio, Afinidad, Experto en Sellos de Mano, Nacionalista, Escalar Árboles, Caminar sobre el Agua, Ninja Médico, Ninja Ilusionista, Maestro Elemental.

### Familia B — "Pergaminos" consumibles (se compran, se usan, se gastan, se pueden re-comprar)
**Pergamino del Reset** (reinicia un árbol de jutsus), **Pergamino del Cuerpo** (reinicia estadísticas), **Pergamino del Olvido** (olvida un árbol de habilidades permanentemente).

Como son consumibles, **no necesitan tabla nueva**: encajan directo en el sistema de objetos/inventario que ya existe (`mybb_sg_sg_objetos` + `mybb_sg_sg_inventario` + el tag `[consumir=OBJETO_ID]` que ya construimos). Ver sección 3.

⚠️ **Ojo con el nombre**: estos "Pergaminos" de la tienda de Tobis son un concepto totalmente distinto de los pergaminos de gacha `PERG001`-`PERG007` (`docs/pergaminos_diseno.md`). Uso el prefijo `TRST00X` (confirmado) para que no se confundan.

## 2. Clasificación del catálogo real (`docs/tobis.txt`)

La columna "Valor" de `tobis.txt` **no se guarda** (confirmado que no existe como campo del sistema). `requiere_programacion` marca si, cuando se programe el efecto real más adelante, hace falta lógica custom o si alcanza con mostrar el texto (el jugador se autogestiona, como con los requisitos):

| # | Nombre | Familia | ¿Requiere programación? |
|---|---|---|---|
| 1 | Presencia Abrumadora | Pasiva | Sí — +5 Tenketsu / −5 Sigilo |
| 2 | Piel de Piedra | Pasiva | Sí — reducción de sangrado, −5 Salud |
| 3 | Huesos de Hierro | Pasiva | Sí — mod. de velocidad |
| 4 | Fuerza Descomunal | Pasiva | Sí — +20 Fuerza, −1 esquiva |
| 5 | Genio | Pasiva | Sí — elige stat, +1 mod (selector diferido, ver §4) |
| 6 | Afinidad | Pasiva | **No** — confirmado, es textual |
| 7 | Experto en Sellos de Mano | Pasiva | **No** — textual (tu respuesta sobre la exclusión) |
| 8 | Pergamino del Reset | Consumible | — (no aplica; se resuelve narrativamente vía petición administrativa) |
| 9 | Pergamino del Cuerpo | Consumible | — (ídem) |
| 10 | Nacionalista | Pasiva | *Asumo* **No** (se resuelve vía petición administrativa, como los Pergaminos) — avisame si no |
| 11 | Pergamino del Olvido | Consumible | — (ídem) |
| 12 | Escalar Árboles | Pasiva | *Asumo* **Sí** — +5 Velocidad, delta simple. Avisame si también es textual |
| 13 | Caminar sobre el Agua | Pasiva | *Asumo* **Sí** — +5 Tenketsu, delta simple. Ídem |
| 14 | Ninja Médico | Pasiva | **No** — confirmado, textual |
| 15 | Ninja Ilusionista | Pasiva | **No** — confirmado, textual |
| 16 | Maestro Elemental | Pasiva | **No** — confirmado, textual |

Para esta fase (sin efectos) el valor de `requiere_programacion` no cambia nada del comportamiento — es solo metadata para cuando llegue la fase de programar. Si las filas marcadas "asumo" están mal, es un simple `UPDATE` más adelante, no bloquea nada ahora.

## 3. Modelo de datos propuesto

### Catálogo de pasivas permanentes: `mybb_sg_sg_pasivas_tobis`

```sql
CREATE TABLE `mybb_sg_sg_pasivas_tobis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pasiva_id` varchar(20) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `descripcion` text NOT NULL,           -- incluye el texto de requisitos si aplica (ej. "Requiere la Pasiva: Afinidad Yang")
  `efecto_texto` varchar(255) DEFAULT NULL,  -- la columna "Efecto" de tobis.txt, en texto plano
  `imagen` varchar(255) DEFAULT NULL,    -- vacío -> objeto_default.png (confirmado)
  `coste` int(11) NOT NULL DEFAULT '0',  -- en Tobis
  `requiere_programacion` tinyint(1) NOT NULL DEFAULT '0',
  `codigo_efecto` varchar(60) DEFAULT NULL,  -- único (confirmado), etiqueta para el futuro programador
  `en_tienda` tinyint(1) NOT NULL DEFAULT '1',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pasiva_id` (`pasiva_id`),
  UNIQUE KEY `codigo_efecto` (`codigo_efecto`)  -- UNIQUE permite múltiples NULL, no molesta a las narrativas sin código
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

Sin tabla de requisitos: quedan como texto dentro de `descripcion`, igual que en la fuente original.

### Pasivas de un personaje: `mybb_sg_sg_ficha_pasivas`

Única por personaje, sin cantidad (no se vende ni se devuelve):

```sql
CREATE TABLE `mybb_sg_sg_ficha_pasivas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL,
  `pasiva_id` varchar(20) NOT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_pasiva` (`uid`, `pasiva_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

(Sin columna `variante`: como el selector de Genio queda diferido — ver §4 — no hay todavía ningún dato de elección que guardar. Se agrega cuando se implemente esa fase.)

### Historial: `mybb_sg_sg_historial_pasivas`

Cuarto dominio del feed de Historial, mismo patrón que `historial_tecnicas`:

```sql
CREATE TABLE `mybb_sg_sg_historial_pasivas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED DEFAULT NULL,
  `grupo` varchar(40) DEFAULT NULL,
  `pasiva_id` varchar(20) NOT NULL,
  `accion` enum('comprar','quitar') NOT NULL DEFAULT 'comprar',
  `tipo` varchar(20) NOT NULL,
  `origen` varchar(40) NOT NULL,
  `detalle` varchar(255) DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

### Los 3 "Pergaminos" consumibles → NO necesitan tabla nueva

Van directo a `mybb_sg_sg_objetos`, `tipo='Consumible'`, `objeto_id` = `TRST001`/`TRST002`/`TRST003`. Para que `tienda_tobis.php` los pueda vender en Tobis en vez de Ryos, hacen falta **dos columnas nuevas en `mybb_sg_sg_objetos`**, paralelas a las que ya existen:

```sql
ALTER TABLE `mybb_sg_sg_objetos`
  ADD COLUMN `coste_tobi` int(11) DEFAULT NULL AFTER `coste`,
  ADD COLUMN `en_tienda_tobis` tinyint(1) NOT NULL DEFAULT '0' AFTER `en_tienda`;
```

Así, `tienda_tobis.php` termina siendo una tienda que muestra **dos catálogos combinados**: pasivas permanentes (`mybb_sg_sg_pasivas_tobis`) + objetos consumibles marcados `en_tienda_tobis=1`. Comprar un "Pergamino" ahí reutiliza `sg_inventario_dar_objeto()` tal cual, y "usarlo" es el mismo `[consumir=TRST001]` que ya armamos — sin código nuevo para esa parte.

## 4. Flujo de compra

### Pasivas permanentes
Mismo patrón que `tienda.php`/`abrir_pergamino.php` (candado `GET_LOCK` por usuario, transacción, `$grupo` compartido):
1. Valida que no la tenga ya y que tenga Tobis suficientes. **No valida requisitos** (eso es responsabilidad del jugador, ver §1).
2. `sg_ficha_set_campo($uid, 'tobi', ...)` + INSERT en `mybb_sg_sg_ficha_pasivas` + INSERT en `mybb_sg_sg_historial_pasivas`, mismo `$grupo`.

### "Pergaminos" consumibles
Mismo flujo que ya tiene `tienda.php`, pero cobrando `coste_tobi` en vez de `coste`. Ya quedan visibles en el inventario de la ficha con el resto de los objetos, sin trabajo extra.

Nueva constante `SG_ORIGEN_TIENDA_TOBIS` para ambos casos.

### Sobre "Genio" (selector de variante diferido)

Confirmaste: sí hace falta un selector ("elegí una estadística: fuerza / destreza / inteligencia / control de chakra"), pero **no lo construyo todavía** — queda para cuando se empiece a implementar el efecto mecánico real. Mientras tanto, "Genio" se compra igual que cualquier otra pasiva (sin elegir nada) y en la tarjeta de la tienda simplemente se muestra el texto de la descripción tal cual está en `tobis.txt` (que ya explica que hay que elegir una stat, como dato narrativo).

## 5. Pestaña "Pasivas" en la ficha (pública, confirmado)

Mismo mecanismo CSS-only de tabs que ya tiene la ficha (radio + label, `#fx-dossier`/`#fx-tecnicas`/etc.) — se suma `#fx-pasivas` a esa misma cadena. Contenido: grid de tarjetas de las pasivas permanentes compradas (estilo `sg_objeto_card()`, mostrando nombre + descripción + `efecto_texto`). Los "Pergaminos" consumibles NO van acá: al vivir en el inventario normal, ya se ven en la pestaña Inventario existente.

## 6. Herramienta de Staff

`sg/admin/gestionar_pasivas_tobis.php` (pasivas permanentes) — mismo patrón crear/modificar/eliminar que `gestionar_objetos.php`: nombre, descripción (incluye ahí el texto de requisitos si aplica), efecto en texto, imagen, costo, `codigo_efecto`, en_tienda, activo. Los "Pergaminos" consumibles no necesitan herramienta nueva: se administran desde el ya existente `gestionar_objetos.php`, con los 2 campos nuevos (`coste_tobi`, `en_tienda_tobis`).

## 7. Sobre las pasivas "mecánicas" (para la fase futura, NO ahora)

Sigue igual: hoy `pas_fuerza`/`pas_destreza`/etc. en `mybb_sg_sg_fichas` los edita Staff a mano vía `modificar_ficha.php`, sin sumar automáticamente varias fuentes. Cuando se programe el efecto real, recomiendo sumar las pasivas de Tobis como fuente independiente dentro de `sg_stats_efectivas()` en vez de tocar `pas_*` directamente, para no pisar las ediciones manuales de Staff. No hace falta resolverlo ahora.

## 8. Plan de implementación sugerido (fase actual: sin efectos)

1. Tablas `mybb_sg_sg_pasivas_tobis`, `mybb_sg_sg_ficha_pasivas`, `mybb_sg_sg_historial_pasivas` + `ALTER TABLE mybb_sg_sg_objetos` (coste_tobi, en_tienda_tobis) (SQL).
2. Funciones en `sg_functions.php`: `sg_historial_pasiva_log()`, `sg_pasivas_tobis_catalogo()`, `sg_ficha_pasivas_compradas($uid)`, constante `SG_ORIGEN_TIENDA_TOBIS`.
3. `sg/tienda_tobis.php` + template (catálogo combinado pasivas+pergaminos, saldo de Tobis, compra con candado atómico).
4. Pestaña pública "Pasivas" en `sg_ficha.html` + bloque PHP en `sg/ficha.php`.
5. `sg/admin/gestionar_pasivas_tobis.php` + template (alta/edición/baja) + 2 campos nuevos en `gestionar_objetos.php`/`staff_modificar_objetos.html` + enlace en `staff_consola_mod.html`.
6. Extender `sg_historial_ficha_feed_html()` para incluir el dominio `pasiva`.
7. Cargar las 16 filas de `docs/tobis.txt` como datos semilla del catálogo (INSERT: 13 en `pasivas_tobis`, 3 en `mybb_sg_sg_objetos`).
8. Pruebas: compra exitosa, saldo insuficiente, doble-compra (bloqueada), historial agrupado, Pergamino consumible aparece en inventario y se puede `[consumir=...]`.

---

Solo queda confirmar las 3 filas marcadas "asumo" en la tabla del punto 2 (Nacionalista, Escalar Árboles, Caminar sobre el Agua) — aunque, como expliqué ahí, no bloquean nada para esta fase. ¿Empezamos a implementar?
