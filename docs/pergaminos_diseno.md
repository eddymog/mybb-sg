# Pergaminos (Gacha) — Interpretación y diseño propuesto

Basado en `docs/pergaminos.txt`. Esto es **solo diseño** — no se implementó código todavía.

> **Estado:** diseño confirmado por el usuario (ver sección 6). Listo para pasar a implementación cuando se pida.

## 1. Mi interpretación de lo que pediste

- Página pública nueva: `sg/pergaminos.php`.
- Chequea si el usuario tiene en su inventario alguno de `PERG001`...`PERG007`. Solo muestra los que **sí** tiene (no una lista completa con "no tenés esto").
- Por cada pergamino que tiene: botón **"Abrir Pergamino"** → confirmación → animación tipo **tragamonedas** → revela un premio.
- Cada pergamino (PERG001, PERG002, etc.) tiene su **propia tabla de premios** con porcentajes que suman 100%.
- Cada premio de esa tabla puede entregar una **combinación libre de recompensas**: por ejemplo, un mismo premio puede dar ryos + rin + madara a la vez, y/o **más de un objeto** (objetos distintos, o varias unidades del mismo objeto). No es "un premio = un tipo", es "un premio = un paquete de recompensas".
- **JACKPOT**: un tipo especial de premio que dispara **5 tiradas extra gratis** sobre la misma tabla de premios de ese pergamino.
- Herramienta de Staff en `/sg/admin` para configurar, por cada pergamino, sus premios y el paquete de recompensas de cada uno.

Esto es un sistema de **loot table ponderada con premios compuestos** — el pergamino es la "llave" (se consume del inventario al abrirlo) y dispara un sorteo configurable.

## 2. Modelo de datos propuesto

Como un premio ya no es "un tipo con un valor" sino "un paquete de recompensas", se necesitan **dos tablas**: una para los premios (los "casilleros" del sorteo, cada uno con su probabilidad) y otra para las recompensas que da cada premio (uno-a-muchos).

```sql
CREATE TABLE `mybb_sg_sg_gacha_premios` (
  `id` int(11) NOT NULL,
  `pergamino_id` varchar(20) NOT NULL,      -- 'PERG001'...'PERG007'
  `nombre` varchar(60) NOT NULL,            -- etiqueta para mostrar, ej. "Premio Mayor", "Suerte básica"
  `probabilidad` decimal(5,2) NOT NULL,     -- 0.01 a 100.00
  `es_jackpot` tinyint(1) NOT NULL DEFAULT '0',
  `jackpot_tiradas` int(11) NOT NULL DEFAULT '5',  -- solo aplica si es_jackpot=1
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int(11) NOT NULL DEFAULT '0',
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `mybb_sg_sg_gacha_recompensas` (
  `id` int(11) NOT NULL,
  `premio_id` int(11) NOT NULL,             -- FK a mybb_sg_sg_gacha_premios.id
  `tipo` enum('ryos','rin','madara','tobi','objeto') NOT NULL,
  `valor` int(11) DEFAULT NULL,             -- monto, si tipo es moneda
  `objeto_id` varchar(20) DEFAULT NULL,     -- objeto a entregar, si tipo='objeto'
  `cantidad` int(11) NOT NULL DEFAULT '1',  -- cuántas unidades (útil para objetos repetidos)
  `orden` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

Con esto, un premio "150 Ryos + 1 Rin + 2x Kunai" es un solo registro en `gacha_premios` con 3 filas hijas en `gacha_recompensas`. Un premio JACKPOT normalmente no necesita filas hijas propias (su "recompensa" son las 5 tiradas extra), aunque el modelo permite que también tenga recompensas directas además del jackpot si algún día se quisiera.

## 3. Flujo de apertura (servidor)

**Importante, no negociable:** el sorteo se decide **100% en el servidor**. Nunca se manda al navegador "elegí un número random" — eso se puede manipular fácilmente desde la consola del navegador. El cliente solo pide "abrir" y el servidor responde con el resultado ya decidido.

1. Usuario hace clic en "Abrir Pergamino" → modal de confirmación (mismo patrón de dos pasos que ya usamos para vender objetos en el inventario de la ficha).
2. Al confirmar, un fetch AJAX a algo como `sg/abrir_pergamino.php`.
3. En el servidor, con el mismo patrón de **candado atómico** que ya usan `tienda.php`/`tienda_rins.php` (`GET_LOCK`, ej. `sg_pergamino_$uid`):
   - Verifica que el usuario tenga ≥1 del pergamino en inventario (sin límite de veces — puede abrir tantas copias como tenga, una tirada independiente por cada una).
   - Consume 1 del inventario vía `sg_inventario_quitar_objeto()` (la misma función que ya usa `vender.php` y `[consumir=...]`).
   - Trae los premios activos de `mybb_sg_sg_gacha_premios` para ese `pergamino_id`, tira un número aleatorio 0-100 y recorre las probabilidades acumuladas para elegir un premio.
   - Si el premio elegido tiene `es_jackpot=1`: repite el sorteo `jackpot_tiradas` veces más (default 5) **sobre el mismo pool, excluyendo los premios con `es_jackpot=1`** (para que no se pueda encadenar jackpots infinitos), y junta todas las recompensas de esas tiradas — **gratis**, sin consumir pergaminos adicionales.
   - Recolecta todas las filas de `gacha_recompensas` del/los premio(s) resultante(s) y las entrega con las funciones que ya existen: `sg_ficha_set_campo()` para ryos/rin/madara/tobi, `sg_inventario_dar_objeto()` para cada objeto (respetando `cantidad`) — todas con el mismo `$grupo` (token `uniqid()`), así en el **tab Historial de la ficha** el evento completo aparece agrupado como uno solo: "Abrió PERG001 → -1 Pergamino, +150 Ryos, +1 Rin, +2 Kunai".
   - Nueva constante `SG_ORIGEN_PERGAMINO` para todo esto.
4. Si el pergamino elegido no tiene ningún premio activo configurado, el botón de abrir queda deshabilitado de antemano (ver punto 4) — el endpoint igual debería rechazar defensivamente por si acaso.
5. Responde JSON con el/los premio(s) para que el frontend los muestre.

## 4. Diseño visual

Siguiendo la paleta "Nocturnal Plum" que ya usa todo el sitio (`docs/STYLE.md`: Cinzel para títulos, Source Serif 4 para cuerpo, fondo `#0f0c18`/`#130f1e`, acento óxido `#c0582a`/dorado `#c09840` para momentos "especiales"):

- Cada pergamino que el usuario tiene se muestra como una tarjeta (mismo lenguaje visual que ya usamos en `sg_objeto_card()`/`objetos.php`), con su imagen, nombre, y el botón "Abrir Pergamino" abajo.
- **Animación confirmada: tragamonedas.** Una tira de íconos de premios que se desliza rápido y va frenando hasta detenerse en el premio real que ya decidió el servidor (animación CSS de scroll con desaceleración, sin geometría de ángulos que calcular).
- Revelado del premio: tarjeta grande centrada, listando cada recompensa del paquete ganado (ej. "+150 Ryos", "+1 Rin", tarjetas de los objetos ganados), en dorado con un pequeño efecto de "brillo". Si fue JACKPOT, se muestra primero el aviso de JACKPOT y luego las recompensas combinadas de las 5 tiradas, agrupadas visualmente (para que se note que fueron 5 premios, no uno solo).
- Si un pergamino no tiene premios configurados (ver punto 6), el botón "Abrir" aparece deshabilitado con un aviso tipo "Próximamente".

## 5. Herramienta de Staff

`sg/admin/gestionar_pergaminos.php` (+ enlace nuevo en `staff_consola_mod.html`, sección de Objetos, tag "Editar" — mismo patrón que "Gestionar objetos"):

- Selector de pergamino (PERG001...PERG007).
- Lista de premios del pergamino elegido (cada uno = una fila en `gacha_premios`): nombre/etiqueta, probabilidad (%), casillero "Es Jackpot" (y de marcarlo, cuántas tiradas extra da, default 5), activo (sí/no).
- Al entrar/expandir un premio, una sub-tabla de sus recompensas (`gacha_recompensas`): agregar/quitar filas libremente, cada una con tipo (select: ryos/rin/madara/tobi/objeto), valor u objeto_id según el tipo, y cantidad. Así se arma la combinación libre (ej. un premio con 3 filas: ryos, rin, objeto).
- Suma de probabilidades del pergamino en vivo, con aviso visual si no da 100%.
- Igual que el resto de herramientas de Staff, quedaría auditado en `mybb_sg_sg_audit_consola_mod`.

## 6. Decisiones confirmadas

1. **Jackpot**: las 5 tiradas extra son gratis (no consumen pergaminos adicionales). ✅
2. **Cantidad de aperturas**: sin límite — el usuario puede abrir tantas veces como copias tenga en inventario, cada una es una tirada independiente. ✅
3. **Madara visible en la ficha**: ya implementado — se agregó a la sección "Recursos" de `sg_ficha.html`. ✅
4. **Objeto `CHAKRAM`**: confirmado que existe en el catálogo. ✅
5. **Animación**: tragamonedas (no ruleta circular). ✅
6. **Pergamino sin premios configurados**: el botón "Abrir" queda deshabilitado con aviso "Próximamente". ✅

## 7. Sugerencias — resultado

- **Historial integrado gratis**: confirmado, se implementa (cada apertura queda registrada en el tab Historial de la ficha usando el mismo `grupo`, sin trabajo extra).
- **Auto-limitante por diseño** (sin límite artificial de "una vez por día", el propio inventario ya limita el ritmo de juego): confirmado, así queda.
- **Pity system** (premio garantizado tras N intentos fallidos): descartado, no se implementa.
- **Narrar apertura como post/BBCode** (reusar las precauciones de `[npc]`/`[objeto]` para mostrar el resultado dentro de un post): no hace falta, no se implementa.

## 8. Plan de implementación sugerido

1. Tablas `mybb_sg_sg_gacha_premios` y `mybb_sg_sg_gacha_recompensas` (SQL).
2. Función(es) de sorteo + apertura en `sg_functions.php` (`sg_gacha_sortear($pergamino_id)`, `sg_gacha_abrir($uid, $pergamino_id)`), con la constante `SG_ORIGEN_PERGAMINO`.
3. `sg/abrir_pergamino.php` (endpoint AJAX, con `GET_LOCK`).
4. `sg/pergaminos.php` + template (listado de pergaminos que tenés + UI de apertura/animación tragamonedas).
5. `sg/admin/gestionar_pergaminos.php` + template (configuración de premios y sus recompensas por Staff) + enlace en `staff_consola_mod.html`.
6. Pruebas: simulación de la distribución de probabilidades (¿da ~30% de las veces el premio de 30%, en una muestra grande?), casos límite (pergamino sin premios, jackpot, premios con recompensas combinadas, probabilidades que no suman 100%).
