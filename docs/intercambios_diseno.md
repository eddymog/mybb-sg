# Intercambios entre jugadores — Diseño

Basado en `docs/intercambios.md`. Esto es **solo diseño** — todavía no se
implementó código.

> **Estado:** diseño cerrado tras confirmar las 3 decisiones clave (ver §1).
> Listo para implementar.

## 1. Interpretación y decisiones tomadas

Página nueva `sg/intercambios.php` para mover **ryos y/o objetos** de un
personaje a otro. Requisitos de elegibilidad (ambos obligatorios):

1. **Tema de contacto (TID) obligatorio.** El emisor indica **explícitamente**
   un TID, que debe cumplir dos cosas:
   - **Estar dentro del foro 37** (o cualquier subforo de 37, o subforo de
     subforo — recursivo). Es la zona de rol; se valida por
     `mybb_sg_forums.parentlist`.
   - **Que ambos jugadores hayan posteado en ese tema** (posts visibles de los
     dos en ese TID) — así "comparten" concretamente ese tema, no uno cualquiera.
2. **Misma aldea**: comparación por `mybb_sg_sg_fichas.villa` (el `vid`).

Decisiones confirmadas con el autor:

- **Flujo: regalo unilateral (una vía).** A le entrega ryos/objetos a B; B **no
  entrega nada a cambio**. No hay oferta/aceptación, ni escrow, ni máquina de
  estados. El "trueque" recíproco, si lo hay, se resuelve narrativamente fuera
  del sistema (por eso se sigue llamando "Intercambios", pero la acción real es
  **enviar/regalar**).
- **Aldea = `fichas.villa` (vid).** No se usa el GID de usergroup del foro; se
  compara el campo canónico de aldea del personaje.
- **Contenido: mezcla libre.** Un mismo envío puede incluir ryos **y** varios
  objetos del inventario con sus cantidades.

## 2. Lo que YA existe y se reutiliza (no se reinventa)

El movimiento de ryos/objetos ya está resuelto por los *choke points* del
historial estructurado (ver `docs/instrucciones_cambios.md`), que **loguean
solos**:

- **Ryos** → `sg_ficha_set_campo($uid, 'ryos', $valor_nuevo, ...)` — escribe en
  `mybb_sg_sg_historial_ficha`. (Los ryos viven en `mybb_sg_sg_fichas`, InnoDB.)
- **Objetos** → `sg_inventario_quitar_objeto()` / `sg_inventario_dar_objeto_cantidad()`
  — escriben en `mybb_sg_sg_historial_objetos`.

Firmas relevantes (todas ya existen en `sg_functions.php`):

```php
sg_ficha_set_campo($fid, $campo, $valor_nuevo, $tipo, $origen, $detalle='', $grupo=null, $actor_uid=null)
sg_inventario_quitar_objeto($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle='', $grupo=null, $actor_uid=null, $pid=null, $tid=null)
sg_inventario_dar_objeto_cantidad($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle='', $grupo=null, $actor_uid=null, $pid=null, $tid=null)
```

**Consecuencia clave:** si el envío se enruta por esas funciones con un
`$grupo = uniqid()` **compartido** y un `origen` nuevo, cada intercambio aparece
**automáticamente** en la pestaña Historial de **ambas** fichas, agrupado como
un único evento. No hay que tocar `sg_historial_ficha_feed_html()`.

**`actor_uid` en el lado del receptor.** Al acreditarle a B, se pasa
`$actor_uid = uid_de_A`. Así el historial de la ficha de B registra
`uid = B` (ficha afectada) y `actor_uid = A` (quién lo hizo) — exactamente para
lo que se diseñó `actor_uid`. En el lado de A, `actor_uid` queda por defecto (el
propio A logueado).

Patrones de página ya hechos que se copian:

- Candado `GET_LOCK` por usuario + transacción + validación server-side →
  `sg/tienda.php` / `sg/tienda_tobis.php`.
- Registro "global + personal" con dos listas → `sg/pergaminos.php`
  (`sg_gacha_historial_global()` / `sg_gacha_historial_usuario()`).

## 3. Modelo de datos

Solo hace falta **una tabla nueva (con detalle)** para el *registro visual* de
intercambios (global y personal). El movimiento de valores ya queda en el
historial; esta tabla guarda cada envío como **una fila legible** ("A envió X a
B"), que es más claro que reconstruirlo desde las filas por-campo del historial.

Patrón cabecera + detalle (igual que `gacha_premios` / `gacha_recompensas`):

```sql
-- Cabecera: un envío = una fila.
CREATE TABLE `mybb_sg_sg_intercambios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `from_uid` int(10) UNSIGNED NOT NULL,   -- quién envía
  `to_uid` int(10) UNSIGNED NOT NULL,     -- quién recibe
  `tid` int(10) UNSIGNED NOT NULL,        -- tema de contacto (obligatorio, validado bajo foro 37)
  `ryos` int(11) NOT NULL DEFAULT '0',    -- ryos enviados (0 si no hubo)
  `grupo` varchar(24) DEFAULT NULL,       -- mismo token uniqid() que las filas del historial
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `from_uid` (`from_uid`),
  KEY `to_uid` (`to_uid`),
  KEY `tiempo` (`tiempo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Detalle: objetos enviados (0..N por envío).
CREATE TABLE `mybb_sg_sg_intercambios_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intercambio_id` int(11) NOT NULL,
  `objeto_id` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `intercambio_id` (`intercambio_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

El nombre del objeto **no** se guarda: se resuelve por join contra
`mybb_sg_sg_objetos` al renderizar, con fallback al `objeto_id` crudo si el
objeto fue borrado del catálogo (mismo criterio que el resto del historial y la
tabla de premios de gacha — ojo con el casing: normalizar la clave en
mayúsculas al armar el mapa `objeto_id → nombre`, como se hizo en
`sg_gacha_abrir_bajo_lock`).

> **Alternativa más simple (descartada):** guardar los objetos como JSON en una
> columna de la cabecera en vez de la tabla `_items`. Se descarta para mantener
> el estilo relacional del repo y poder consultar/sumar por objeto con SQL
> trivial.

Se guardará el archivo `docs/alter_intercambios.sql` con estas dos tablas,
siguiendo la convención de `docs/alter_gacha_log.sql` (CREATE con
`AUTO_INCREMENT` inline + `PRIMARY KEY`/`KEY` dentro del CREATE).

## 4. Constantes y funciones nuevas en `sg_functions.php`

### Constante de origen
```php
define('SG_ORIGEN_INTERCAMBIO', 'intercambio');
```
+ entrada en `sg_origen_label()`: `'intercambio' => 'Intercambio'`.

### Helpers
```php
// ¿El TID es un tema válido para intercambio? Existe, es visible, y su foro
// está dentro de la zona de rol (foro 37 o descendiente). Convención del repo
// (censo.php, recompensa_diaria.php): f.parentlist LIKE '37,%'. Para cubrir
// también un tema que esté DIRECTO en el foro 37, se usa el chequeo robusto por
// elemento: (f.fid = 37 OR CONCAT(',', f.parentlist, ',') LIKE '%,37,%').
function sg_tid_zona_rol($tid) { ... }
//   SELECT 1 FROM mybb_sg_threads t
//   JOIN mybb_sg_forums f ON f.fid = t.fid
//   WHERE t.tid = $tid AND t.visible = 1
//     AND (f.fid = 37 OR CONCAT(',', f.parentlist, ',') LIKE '%,37,%') LIMIT 1

// ¿Ambos jugadores postearon (posts visibles) en ESE tid?
function sg_ambos_en_tema($uid_a, $uid_b, $tid) { ... }
//   SELECT
//     SUM(uid = A) AS a, SUM(uid = B) AS b
//   FROM mybb_sg_posts WHERE tid = $tid AND visible = 1
//   -> válido si a > 0 AND b > 0   (una sola pasada)

// ¿Misma aldea? Compara mybb_sg_sg_fichas.villa de ambos.
function sg_fichas_misma_aldea($uid_a, $uid_b) { ... }

// Registro global de intercambios (últimos N), con nombres resueltos.
// Mismo patrón que sg_gacha_historial_global().
function sg_intercambios_global($limite = 100) { ... }

// Registro personal (los que envié o recibí).
function sg_intercambios_usuario($uid, $limite = 100) { ... }
```

## 5. Flujo de envío en `sg/intercambios.php`

Mismo esqueleto que `tienda_tobis.php` (candado + transacción + saldos frescos):

1. **Entrada**: `destinatario` (por username), `tid` (obligatorio), `ryos` (≥0),
   y una lista de objetos `objeto[]` + `cantidad[]` del inventario del emisor.
2. **Resolver destinatario**: username → uid. Debe existir, tener ficha, y no
   ser el propio emisor.
3. **Candado** `GET_LOCK("sg_intercambio_<from_uid>", 5)` (serializa envíos del
   emisor; el receptor solo suma, que es aditivo y seguro).
4. **Validaciones (con datos frescos dentro del lock):**
   - `tid` obligatorio y dentro de la zona de rol (`sg_tid_zona_rol`).
   - Ambos postearon en ese `tid` (`sg_ambos_en_tema`).
   - Misma aldea (`sg_fichas_misma_aldea`).
   - Se envía **al menos algo** (ryos>0 o ≥1 objeto con cantidad>0).
   - El emisor tiene ryos suficientes.
   - El emisor tiene cada objeto en la cantidad indicada.
5. **Transacción** (`START TRANSACTION` … `COMMIT`), con un `$grupo = uniqid()`
   compartido por TODO:
   - **Emisor (A):** `sg_ficha_set_campo(A,'ryos', A_ryos−total, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle, $grupo)`
     + por cada objeto `sg_inventario_quitar_objeto(A, oid, n, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle, $grupo)`.
   - **Receptor (B):** `sg_ficha_set_campo(B,'ryos', B_ryos+ryos, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle, $grupo, $actor_uid=A)`
     + por cada objeto `sg_inventario_dar_objeto_cantidad(B, oid, n, 'usuario', SG_ORIGEN_INTERCAMBIO, $detalle, $grupo, $actor_uid=A)`.
   - **Registro:** INSERT en `mybb_sg_sg_intercambios` (con `grupo`, `tid` de
     contacto) + un INSERT por objeto en `mybb_sg_sg_intercambios_items`.
   - `COMMIT`.
6. `RELEASE_LOCK`. Mensaje de éxito/error para el render.

**Atomicidad real:** `fichas`, `inventario`, las tablas `historial_*` y las de
`intercambios` son todas InnoDB → si algo falla a mitad, el `ROLLBACK` revierte
el envío completo. Un regalo nunca queda a medias (a diferencia de acciones que
tocan `mybb_sg_users`/newpoints, que es MyISAM — acá no aplica).

## 6. Template `sg_intercambios.html`

Mismo lenguaje visual "washi oscuro" que `sg_tienda_tobis.html` /
`sg_pergaminos.html` (fuentes Cinzel + Noto Serif JP, mismas CSS vars).

- **Formulario de envío**: input de destinatario (username), input de **TID**
  (obligatorio), input de ryos, y una grilla de los objetos del inventario del
  emisor con selector de cantidad. El botón se llama **"Enviar"**. Confirmación
  (`confirm()`), porque mueve valor real y no se deshace.
- **Registro "Intercambios globales"** y **"Mis intercambios"** (los que envié o
  recibí), dos listas al estilo de los historiales de Pergaminos (con nombre de
  emisor/receptor, ryos, objetos, fecha, y **enlace al tema de contacto** vía el
  `tid`).
- **Enlace** en `header.html`, dropdown "Rol" (junto a Tienda / Tienda de Tobis
  / Pergaminos).

Recordatorio de siempre: los templates en `templates/html/` son de referencia;
hay que reimportarlos en el ACP para que la versión en la BD cambie.

## 7. Entrada del destinatario y del TID

- **Destinatario:** input de username + validación al enviar. Si no es elegible,
  error explícito diciendo por qué ("no son de la misma aldea", "no postearon
  ambos en ese tema", etc.).
- **TID:** input obligatorio (número del tema). Se acepta que el jugador pegue el
  número o, si se prefiere, la URL del tema y se extrae el `tid` con regex. Se
  valida server-side que sea de la zona de rol (foro 37) y que ambos hayan
  posteado ahí.

Mejora futura opcional: autocompletar destinatarios/temas a partir de los temas
donde el emisor ya posteó, pero no bloquea la primera versión.

## 8. Riesgos y sugerencias (fuera de alcance salvo que se pidan)

- **Abuso / multicuentas:** al ser un regalo unilateral, se puede usar para
  dumpear ryos/objetos a alts. Los gates (TID obligatorio en zona de rol +
  ambos posteados + misma aldea) lo acotan bastante, y **todo queda en el
  historial** (auditable por Staff). Si se quiere más control, opciones: **tope
  diario** de envíos, o límite de ryos por envío. No se incluye por ahora.
- **Naming (confirmado):** el sistema/página se llama **"Intercambios"**, y el
  botón de acción es **"Enviar"** (el flujo elegido es unilateral).
- **`tid` de contacto:** obligatorio, se guarda para enlazar el tema en el
  registro ("intercambiaron en el tema X") y como prueba del contacto exigido.

## 9. Plan de implementación sugerido

1. `docs/alter_intercambios.sql`: tablas `mybb_sg_sg_intercambios` +
   `mybb_sg_sg_intercambios_items` (SQL a correr a mano en la BD).
2. `sg_functions.php`: constante `SG_ORIGEN_INTERCAMBIO` + entrada en
   `sg_origen_label()`; helpers `sg_tid_zona_rol`, `sg_ambos_en_tema`,
   `sg_fichas_misma_aldea`, `sg_intercambios_global`, `sg_intercambios_usuario`.
3. `sg/intercambios.php` + template `sg_intercambios.html` (formulario de envío
   con candado atómico + registros global/personal). Botón "Enviar".
4. Enlace en `header.html` (dropdown "Rol").
5. Pruebas: envío exitoso (ryos+objetos); TID fuera del foro 37 (rechazado);
   TID donde no postearon ambos (rechazado); distinta aldea (rechazado);
   saldo/objetos insuficientes; aparición en el Historial de ambas fichas
   agrupado; y en los registros global/personal.
