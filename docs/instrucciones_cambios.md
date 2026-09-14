# Historial de cambios de ficha (Staff + Usuario)

## Objetivo

Llevar un registro cronológico de **todos** los cambios que sufre una ficha
— campos escalares (`mybb_sg_sg_fichas`, `mybb_sg_users.newpoints`), técnicas
aprendidas y objetos de inventario — tanto los que hace Staff manualmente
como los que hace el propio usuario (gasto de monedas, compras, XP por
posts), sin depender de que cada página se acuerde de escribir el log a
mano.

Hoy eso solo existe a medias: `mybb_sg_sg_audit_consola_mod` (texto libre,
`staff`/`username`/`razon`/`log`) registra la mayoría de los cambios de
**Staff** sobre campos escalares, pero requiere que cada script inserte el
log explícitamente — y hay scripts que ya se olvidan de hacerlo (ver tabla
abajo). Los cambios de **usuario** no se registran en ningún lado.

## Por qué no trigger

Un trigger `AFTER UPDATE` capturaría cualquier cambio sin importar qué
script lo disparó, pero no sabe **por qué** cambió algo (¿Staff corrigiendo
un error? ¿usuario comprando en la tienda?) — esa información solo existe en
el código de aplicación en el momento del cambio, y meterla vía variables de
sesión de MySQL es indirecto y fácil de dejar desincronizado.

Decisión: **sin trigger.** En su lugar, la garantía de "todo cambio queda
logueado" viene de que **la función que muta el dato es también la que
loguea** — no un paso aparte que se pueda olvidar, sino parte de la única
forma "fácil" de hacer el cambio. Si un script quiere tocar el dato sin
pasar por la función, tiene que escribir SQL crudo a propósito, lo cual se
nota en code review.

## Estado actual: dos choke points ya existen, pero están subutilizados

`sg/functions/sg_functions.php` ya tiene funciones compartidas para dos de
los tres dominios, pero varios scripts las bypasean con SQL propio:

- **`sg_dojo_aprender($db, $uid, $tid)`** (línea 1270) — inserta en
  `mybb_sg_sg_tec_aprendidas`. Solo la usa la compra de técnicas del dojo
  (usuario, vía `sg_dojo_procesar_*` en el mismo archivo).
  `sg/admin/ficha_tecnicas.php` (Staff, líneas 67, 88-94, 118-122) hace su
  propio INSERT/DELETE en paralelo.
- **`sg_inventario_dar_objeto($uid, $objeto_id)`** (línea 1735) — upsert
  +1 en `mybb_sg_sg_inventario`. Solo la usa
  `sg/admin/recompensas_mision.php:358` (pergamino de misión).
  `sg/tienda.php:100,102`, `sg/tienda_rins.php:143,145` y
  `sg/admin/ficha_objetos.php` hacen su propio UPDATE/INSERT en paralelo.
  `sg/vender.php:70` resta cantidad y no tiene función hermana.

Para los campos escalares de ficha (`ryos`, `tobi`, `rin`, `moderated`,
`puntos_habilidad`, `pe`, `reputacion`) y `mybb_sg_users.newpoints` **no
existe ningún choke point hoy** — cada script hace su propio `UPDATE`.

## Diseño propuesto

### 1. Tres tablas, no una

La forma natural de cada tipo de evento es distinta (diff de un valor
escalar vs. "aprendió la técnica X" vs. "+/-N de un objeto"), igual que el
código ya separa `historial_misiones` de `historial_combates` en vez de
fusionarlos:

```sql
-- Campos escalares de ficha o de mybb_sg_users (ej. newpoints)
CREATE TABLE `mybb_sg_sg_historial_ficha` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,       -- ficha afectada (dueño del cambio)
  `actor_uid` int(10) UNSIGNED NOT NULL, -- QUIÉN hizo el cambio (== uid si fue el propio usuario)
  `grupo` varchar(24) DEFAULT NULL,      -- token que une varias filas de la misma acción (ver §1.1)
  `tabla` enum('fichas','users') NOT NULL,
  `campo` varchar(40) NOT NULL,
  `valor_anterior` text,
  `valor_nuevo` text,
  `tipo` enum('staff','usuario') NOT NULL,
  `origen` varchar(40) NOT NULL,         -- constante SG_ORIGEN_* (ver §1.2)
  `detalle` varchar(255) DEFAULT NULL,   -- razón (Staff) u observación libre
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Técnicas aprendidas
CREATE TABLE `mybb_sg_sg_historial_tecnicas` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(24) DEFAULT NULL,
  `tecnica_id` varchar(20) NOT NULL,
  `accion` enum('aprender','quitar') NOT NULL DEFAULT 'aprender',
  `tipo` enum('staff','usuario') NOT NULL,
  `origen` varchar(40) NOT NULL,         -- constante SG_ORIGEN_* (ver §1.2)
  `detalle` varchar(255) DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Objetos de inventario (entregas y ventas)
CREATE TABLE `mybb_sg_sg_historial_objetos` (
  `id` int(11) NOT NULL,
  `uid` int(10) UNSIGNED NOT NULL,
  `actor_uid` int(10) UNSIGNED NOT NULL,
  `grupo` varchar(24) DEFAULT NULL,
  `objeto_id` varchar(20) NOT NULL,
  `cantidad` int(11) NOT NULL,           -- delta: positivo = ganado, negativo = vendido/gastado
  `tipo` enum('staff','usuario') NOT NULL,
  `origen` varchar(40) NOT NULL,         -- constante SG_ORIGEN_* (ver §1.2)
  `detalle` varchar(255) DEFAULT NULL,
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

(+ `PRIMARY KEY(id)` autoincrement e índices `uid`, `grupo`, `(uid, tiempo)`
en cada una, mismo patrón que `alter_historial_misiones.sql`. El índice
`(uid, tiempo)` sirve al feed cronológico y a las consultas por rango de
fecha de la consola.)

**`actor_uid` — quién hizo el cambio (no solo el tipo).** `uid` es la ficha
afectada; `actor_uid` es quién ejecutó la acción. Para cambios de usuario
son iguales; para cambios de Staff, `actor_uid` es el moderador y `uid` es la
ficha editada. Esto recupera la trazabilidad que hoy da el campo `staff` de
`audit_consola_mod` (que el historial no tendría si solo guardara `tipo`).

**Append-only.** Estas tablas nunca se `UPDATE` ni `DELETE` — solo `INSERT`.
Para "revertir" un cambio se inserta el cambio inverso, no se borra el
original. Así el historial es una bitácora inmutable de verdad.

### 1.1 Acciones que cambian varias cosas a la vez (columna `grupo`)

Una sola acción puede tocar varios campos y hasta varias tablas — ej. la
recompensa semanal cambia `ryos`+`tobi` (ficha) y `newpoints` (usuario); una
compra en el dojo resta `tobi` (ficha) **y** agrega una técnica
(historial_tecnicas). Se mantiene **una fila por campo/evento** (para poder
filtrar y sumar cada campo por separado con SQL trivial en 5.7), y esas filas
se vinculan con un token `grupo` generado **una vez** por acción:

```php
$grupo = uniqid();  // un token por acción, compartido entre todas sus filas
sg_ficha_set_campo($fid, 'ryos', $ryos_new, 'staff', 'recompensas_staff', $razon, $grupo);
sg_ficha_set_campo($fid, 'tobi', $tobi_new, 'staff', 'recompensas_staff', $razon, $grupo);
sg_usuario_set_campo($fid, 'newpoints', $exp_new, 'staff', 'recompensas_staff', $razon, $grupo);
```

- **Feed** (tab de ficha / consola): `GROUP BY grupo` (o agrupar client-side
  tras el JSON-embed) para renderizar una sola línea de evento con todos sus
  campos adentro: `tobi (25→47), rin (12→84), exp (564→601)`.
- **Filtrar/sumar por campo**: se ignora `grupo` y se consulta como filas
  sueltas — `WHERE campo='tobi'`, `SUM(valor_nuevo - valor_anterior)`, etc.
- El token es un `varchar` opaco (`uniqid()`); no se ordena ni se interpreta,
  solo agrupa. Si una acción toca un solo campo, `grupo` puede quedar `NULL`
  (fila suelta = evento de un campo).
- El mismo `grupo` se comparte **entre las tres tablas** cuando la acción
  cruza dominios (ej. compra en dojo: fila en `historial_ficha` por el tobi +
  fila en `historial_tecnicas` por la técnica, ambas con el mismo token), así
  el feed las muestra como un único evento.

`audit_consola_mod` **no se reemplaza** — sigue siendo la auditoría de texto
libre con la razón completa que escribe Staff. Las tablas de historial son
la capa estructurada (campo por campo / evento por evento) que permite
filtrar y alimentar el tab de la ficha.

### 1.2 `origen` como vocabulario controlado (constantes)

`origen` es un `varchar`, pero **nunca** se pasa un literal suelto: si un
script escribe `'tienda'` y otro `'Tienda'` o `'tienda_rins'`, el filtrado
por origen se rompe en silencio. Se definen constantes en `sg_functions.php`
y se pasan esas:

```php
define('SG_ORIGEN_MODIFICAR_FICHA', 'modificar_ficha');
define('SG_ORIGEN_ATRIBUTOS',       'ficha_atributos');
define('SG_ORIGEN_RECOMPENSA_STAFF','recompensa_staff');
define('SG_ORIGEN_RECOMPENSA_MISION','recompensa_mision');
define('SG_ORIGEN_APROBACION',      'aprobacion');       // fichas_en_cola
define('SG_ORIGEN_TIENDA',          'tienda');
define('SG_ORIGEN_TIENDA_RINS',     'tienda_rins');
define('SG_ORIGEN_VENDER',          'vender');
define('SG_ORIGEN_DOJO',            'dojo');
define('SG_ORIGEN_FICHA_TECNICAS',  'ficha_tecnicas');
define('SG_ORIGEN_FICHA_OBJETOS',   'ficha_objetos');
define('SG_ORIGEN_POST_XP',         'post_xp');          // ver §2.1
```

### 2. Funciones en `sg_functions.php`

**Ampliar las dos que ya existen**, agregando parámetros obligatorios de
contexto (obligatorios a propósito: si un call site se olvida de pasarlos,
PHP tira error en vez de loguear con un valor por defecto incorrecto).

Los dos últimos parámetros son opcionales:
- `$grupo` (ver §1.1): token que une las filas de una misma acción. Si se
  omite, la fila queda suelta.
- `$actor_uid`: quién ejecuta la acción. Si se omite, la función usa
  `$mybb->user['uid']` (el usuario logueado) — correcto tanto para acciones
  de usuario como para las de Staff desde el panel. Solo hay que pasarlo
  explícito cuando el actor no es el usuario logueado (ej. una recompensa que
  acredita a un tercero).

```php
function sg_dojo_aprender($db, $uid, $tid, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    // ...INSERT IGNORE existente...
    // + INSERT en mybb_sg_sg_historial_tecnicas
}

function sg_inventario_dar_objeto($uid, $objeto_id, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) {
    // ...upsert existente...
    // + INSERT en mybb_sg_sg_historial_objetos con cantidad=+1
}
```

**Funciones nuevas** para completar los huecos:

```php
// Resta 1 (o $cantidad) de un objeto del inventario; hermana de sg_inventario_dar_objeto.
function sg_inventario_quitar_objeto($uid, $objeto_id, $cantidad, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) { ... }

// Quita una técnica aprendida; hermana de sg_dojo_aprender.
function sg_tecnica_quitar($db, $uid, $tid, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) { ... }

// Choke point nuevo para campos escalares de ficha: SELECT valor actual, UPDATE,
// log — en una sola llamada. Si no hubo cambio real, no hace UPDATE ni loguea.
function sg_ficha_set_campo($fid, $campo, $valor_nuevo, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) { ... }

// Mismo patrón para mybb_sg_users (cubre newpoints).
function sg_usuario_set_campo($uid, $campo, $valor_nuevo, $tipo, $origen, $detalle = '', $grupo = null, $actor_uid = null) { ... }
```

Para una acción multi-campo, el script genera `$grupo = uniqid();` una vez y
lo pasa a cada llamada (ver ejemplo en §1.1).

### 2.1 XP por postear (`origen = 'post_xp'`)

Todo el XP por postear pasa por **`newpoints_addpoints()`**
(`inc/plugins/newpoints.php:362`) — un único choke point. Ahí es donde se
loguea, no en cada hook de post/hilo/respuesta que la llama.

Detalles clave detectados en esa función:

- **Sin doble-log.** `newpoints` cambia por dos caminos disjuntos: postear
  (siempre vía `newpoints_addpoints()`) y Staff (`UPDATE mybb_sg_users`
  directo). Como no se cruzan, se loguea post-XP dentro de `addpoints` y lo de
  Staff vía `sg_usuario_set_campo`, sin contar lo mismo dos veces.
- **Un post puede generar dos filas.** Por el tope semanal de experiencia
  (100/semana), un post puede sumar `newpoints` **y** convertir el excedente
  en `rin` en la misma llamada. Esas dos filas comparten `grupo` y en el feed
  se ven como un evento: "Posteó: +8 exp, +2 rin".
- **Volumen / ruido.** Es el evento de mayor frecuencia del foro. Se le da su
  propio `origen = 'post_xp'` para que el tab de la ficha pueda **colapsar**
  filas consecutivas de post_xp en una línea-resumen ("Ganó 84 exp en 12 posts
  esta semana") y/o filtrarlas con un toggle, y no ahoguen los eventos
  importantes. El tope semanal ya acota el volumen por usuario.

### 3. Atomicidad: transacciones para acciones multi-campo

Al pasar de un `UPDATE` combinado (ej. `ryos`+`tobi` en una sentencia) a
varias llamadas `sg_*_set_campo`, se pierde la atomicidad de una sola
sentencia: si algo falla a mitad, la ficha puede quedar con un campo
actualizado y otro no. InnoDB soporta transacciones, así que las acciones que
comparten `grupo` deberían envolverse:

```php
$db->query("START TRANSACTION");
$grupo = uniqid();
sg_ficha_set_campo(...);   // ryos
sg_ficha_set_campo(...);   // tobi
sg_usuario_set_campo(...); // newpoints
$db->query("COMMIT");
```

Ningún script del proyecto usa transacciones hoy, así que es un patrón nuevo
— pero es la forma correcta de que un fallo a mitad de una recompensa no deje
la ficha inconsistente.

### 4. UI: nuevo tab "Historial" en la ficha

En `sg/ficha.php` + `templates/html/sg_ficha.html`: un tab nuevo (no dentro
de "Estadísticas", que son contadores agregados) con el mismo patrón
JSON-embed + filtro cliente que `sg/registro_personajes.php`
(`WHERE uid = X` sobre las tres tablas, toggle Staff/Usuario, paginado).

**Resolución de nombres al leer.** El historial guarda IDs
(`tecnica_id`, `objeto_id`), no nombres. Al construir el feed hay que joinear
contra el catálogo (`mybb_sg_sg_tecnicas`, `mybb_sg_sg_objetos`) para mostrar
"Ganó: Katana de acero" en vez de "Ganó: katana_01". Si el objeto/técnica fue
borrado del catálogo después, el join no encuentra nombre → mostrar el ID
crudo como fallback en lugar de un hueco vacío.

### 5. Reorganizar la consola de audits

`sg/admin/log_consola.php` y `sg/admin/log_consola_mod.php` deberían
unificarse para leer también de las tres tablas de historial, así consola y
tab de ficha comparten la misma fuente de verdad estructurada.

## Call sites a migrar

### Campos escalares de ficha / usuario (requieren `sg_ficha_set_campo` / `sg_usuario_set_campo` nuevas)

| Archivo | Línea | Campo(s) | ¿Hoy loguea en `audit_consola_mod`? |
|---|---|---|---|
| `sg/admin/modificar_ficha.php` | 118 | `$set_clause` dinámico (cualquier campo del panel) | Sí |
| `sg/admin/ficha_atributos.php` | 57, 64, 80, 88 | `ryos`, `puntos_habilidad`, `pe`, `reputacion` | Sí |
| `sg/admin/recompensas_staff.php` | 167, 276 | `ryos`+`tobi`, `rin` | Sí |
| `sg/admin/recompensas_mision.php` | 230, 349 | `tobi`, `ryos`+`tobi` | Sí |
| `sg/admin/fichas_en_cola.php` | 69 | `moderated` | **No** |
| `sg/admin/backfill_arboles.php` | 93 | `arboles` (JSON grande — candidato a excluir del historial campo-por-campo) | **No** |
| `sg/functions/sg_functions.php` (`sg_dojo_guardar`) | 1277-1286 | `tobi`, `arboles_progreso` (gasto de usuario en el dojo) | No aplica (no es admin) |
| `sg/tienda_rins.php` | 92, 103, 148 | `rin`, `ryos`/`tobi` (gasto de usuario) | **No** |

### Técnicas (requieren migrar a `sg_dojo_aprender` ampliada / `sg_tecnica_quitar` nueva)

| Archivo | Línea | Acción | Tipo |
|---|---|---|---|
| `sg/admin/ficha_tecnicas.php` | 67 | Quitar técnica | staff |
| `sg/admin/ficha_tecnicas.php` | 88-94, 118-122 | Agregar técnica | staff |
| `sg/functions/sg_functions.php` (dojo) | 1372…1560 (8 llamadas a `sg_dojo_aprender`) | Aprender por compra en dojo | usuario |

### Objetos (requieren migrar a `sg_inventario_dar_objeto` ampliada / `sg_inventario_quitar_objeto` nueva)

| Archivo | Línea | Acción | Tipo |
|---|---|---|---|
| `sg/tienda.php` | 100, 102 | Compra de objeto | usuario |
| `sg/tienda_rins.php` | 143, 145 | Compra de objeto (con rins) | usuario |
| `sg/vender.php` | 70 | Venta de objeto | usuario |
| `sg/admin/ficha_objetos.php` | — (setea cantidad absoluta, no delta) | Ajuste manual de inventario | staff |
| `sg/admin/recompensas_mision.php` | 358 | Ya usa `sg_inventario_dar_objeto` — solo agregar los params nuevos | staff |

`ficha_objetos.php` es un caso especial: Staff **setea** una cantidad
absoluta desde una tabla de edición, no incrementa de a 1 como el resto.
Necesita su propio wrapper (ej. `sg_inventario_set_cantidad`) que calcule el
delta contra el valor actual y loguee ese delta, en vez de reusar
`sg_inventario_dar_objeto` tal cual.

## Pendiente de decidir

- Firma exacta de cada función nueva y si `$db` se pasa como parámetro o se
  usa `global $db` (las funciones existentes son inconsistentes entre sí:
  `sg_dojo_aprender` recibe `$db`, `sg_inventario_dar_objeto` usa `global`).
- Migración SQL (`docs/alter_historial_ficha.sql`,
  `docs/alter_historial_tecnicas.sql`, `docs/alter_historial_objetos.sql`).
- Diseño del tab "Historial" en la ficha.
- Orden de implementación sugerido: 1) tablas, 2) funciones nuevas/ampliadas
  en `sg_functions.php`, 3) migrar call sites uno por uno (probando cada
  uno), 4) tab en la ficha, 5) reorganizar consola de audits.
