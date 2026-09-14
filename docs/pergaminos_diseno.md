# Pergaminos (Gacha) — Interpretación y diseño propuesto

Basado en `docs/pergaminos.txt`. Esto es **solo diseño** — no se implementó código todavía.

## 1. Mi interpretación de lo que pediste

- Página pública nueva: `sg/pergaminos.php`.
- Chequea si el usuario tiene en su inventario alguno de `PERG001`...`PERG007`. Solo muestra los que **sí** tiene (no una lista completa con "no tenés esto").
- Por cada pergamino que tiene: botón **"Abrir Pergamino"** → confirmación → animación tipo ruleta/gacha → revela un premio.
- Cada pergamino (PERG001, PERG002, etc.) tiene su **propia tabla de premios** con porcentajes que suman 100%: montos de `ryos`/`rin`/`madara`/`tobi`, objetos del catálogo, o un **"JACKPOT"** que en vez de dar un premio da **5 premios adicionales al azar** de esa misma tabla.
- Herramienta de Staff en `/sg/admin` para configurar, por cada pergamino: qué premios existen, su tipo (ryos/rin/madara/tobi/objeto) y su probabilidad.

Esto es un sistema de **loot table ponderada** — el pergamino es la "llave" (se consume del inventario al abrirlo) y dispara un sorteo configurable.

## 2. Modelo de datos propuesto

Una tabla nueva, `mybb_sg_sg_gacha_premios`, con una fila por premio posible (no una fila por pergamino — cada pergamino tiene N filas):

```sql
CREATE TABLE `mybb_sg_sg_gacha_premios` (
  `id` int(11) NOT NULL,
  `pergamino_id` varchar(20) NOT NULL,      -- 'PERG001'...'PERG007'
  `tipo` enum('ryos','rin','madara','tobi','objeto','jackpot') NOT NULL,
  `valor` int(11) DEFAULT NULL,             -- monto (ryos/rin/madara/tobi); NULL si tipo='objeto'/'jackpot'
  `objeto_id` varchar(20) DEFAULT NULL,     -- objeto a entregar; NULL salvo tipo='objeto'
  `probabilidad` decimal(5,2) NOT NULL,     -- 0.01 a 100.00 (dos decimales, ej. 7.50%)
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int(11) NOT NULL DEFAULT '0',     -- orden de visualización en el admin
  `tiempo_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

Con esto, tu ejemplo de `PERG001` serían 9 filas (una por cada línea de tu lista, incluyendo el 20% de "20 Tobi" que aparece separado del 7% de "30 Tobi" — dos premios distintos, mismo tipo).

## 3. Flujo de apertura (servidor)

**Importante, no negociable:** el sorteo se decide **100% en el servidor**. Nunca se manda al navegador "elegí un número random" — eso se puede manipular fácilmente desde la consola del navegador. El cliente solo pide "abrir" y el servidor responde con el resultado ya decidido.

1. Usuario hace clic en "Abrir Pergamino" → modal de confirmación (mismo patrón de dos pasos que ya usamos para vender objetos en el inventario de la ficha).
2. Al confirmar, un fetch AJAX a algo como `sg/abrir_pergamino.php`.
3. En el servidor, con el mismo patrón de **candado atómico** que ya usan `tienda.php`/`tienda_rins.php` (`GET_LOCK`, ej. `sg_pergamino_$uid`):
   - Verifica que el usuario tenga ≥1 del pergamino en inventario.
   - Consume 1 del inventario vía `sg_inventario_quitar_objeto()` (la misma función que ya usa `vender.php` y `[consumir=...]`).
   - Trae las filas activas de `mybb_sg_sg_gacha_premios` para ese `pergamino_id`, tira un número aleatorio 0-100 y recorre las probabilidades acumuladas para elegir el premio.
   - Si el premio es `jackpot`: repite el sorteo 5 veces más **sobre el mismo pool, pero excluyendo las filas `jackpot`** (para que no pueda encadenar jackpots infinitos) y entrega los 5 premios.
   - Entrega el/los premio(s) con las funciones que ya existen: `sg_ficha_set_campo()` para ryos/rin/madara/tobi, `sg_inventario_dar_objeto()` para objetos — todas con el mismo `$grupo` (token `uniqid()`), así en el **tab Historial de la ficha** el evento completo aparece agrupado como uno solo: "Abrió PERG001 → -1 Pergamino, +100 Ryos" (o, si fue jackpot, los 6 movimientos juntos).
   - Nueva constante `SG_ORIGEN_PERGAMINO` para todo esto.
4. Responde JSON con el/los premio(s) para que el frontend los muestre.

## 4. Diseño visual

Siguiendo la paleta "Nocturnal Plum" que ya usa todo el sitio (`docs/STYLE.md`: Cinzel para títulos, Source Serif 4 para cuerpo, fondo `#0f0c18`/`#130f1e`, acento óxido `#c0582a`/dorado `#c09840` para momentos "especiales"):

- Cada pergamino que el usuario tiene se muestra como una tarjeta (mismo lenguaje visual que ya usamos en `sg_objeto_card()`/`objetos.php`), con su imagen, nombre, y el botón "Abrir Pergamino" abajo.
- **Sobre la animación de "ruleta":** una ruleta circular real (SVG/canvas con una aguja que gira y frena exactamente en el premio ganador) es visualmente vistosa pero es bastante trabajo de CSS/JS para hacerla bien (calcular el ángulo exacto de cada premio según su probabilidad, sincronizar la animación con el resultado real del servidor, que no "tiemble" al final). Te propongo una alternativa más simple de construir y igual de "gachero": un **efecto de tragamonedas** — una tira de íconos de premios que se desliza rápido y va frenando hasta detenerse en el premio real (como una slot machine). Es más fácil de implementar de forma robusta (no hay geometría de ángulos, solo una animación CSS de scroll con desaceleración) y el efecto de suspenso es equivalente.
- Si preferís sí la ruleta circular clásica, se puede hacer, pero avisame para presupuestar mejor ese pedazo (es la parte más laboriosa de todo esto).
- Revelado del premio: una tarjeta grande centrada, con el ícono/imagen del premio, en dorado si es algo bueno, con un pequeño efecto de "brillo" — y si fue JACKPOT, una tarjeta especial mostrando los 5 premios en grilla.

## 5. Herramienta de Staff

`sg/admin/gestionar_pergaminos.php` (+ enlace nuevo en `staff_consola_mod.html`, sección de Objetos, tag "Editar" — mismo patrón que "Gestionar objetos"):

- Selector de pergamino (PERG001...PERG007).
- Tabla editable de premios para el pergamino elegido: tipo (select: ryos/rin/madara/tobi/objeto/jackpot), valor u objeto_id según el tipo, probabilidad (%), activo (sí/no).
- Suma de probabilidades en vivo, con aviso visual si no da 100% (bloqueando el guardado, o al menos advirtiendo fuerte — a definir).
- Igual que el resto de herramientas de Staff, quedaría auditado en `mybb_sg_sg_audit_consola_mod`.

## 6. Preguntas que necesito que me confirmes antes de implementar

1. **Jackpot**: ¿los 5 premios extra salen "gratis" del mismo sorteo (mi asunción arriba), o el jugador necesita gastar 5 pergaminos más? Asumo lo primero por como lo describiste, pero quiero confirmarl
2. **Cantidad de aperturas**: si el usuario tiene 3x `PERG001`, ¿puede abrir las 3 (cada una es un sorteo independiente), o el pergamino es "único" por tipo? Asumo que puede abrir tantas veces como copias tenga.
3. **`madara` no se muestra hoy en ningún lado de la ficha pública** (encontré esto investigando) — solo existe como campo administrable por Staff. Si va a ser premio de gacha, un usuario que gane "50 Madara" no va a poder ver ese número en ningún lado de su perfil. ¿Lo agregamos a la vista pública de la ficha (al lado de Rin/Tobi/Ryos), o preferís que "madara" siga siendo una moneda invisible/administrativa y mejor no la uses como premio?
4. **El objeto de ejemplo "CHAKRAM"**: no pude confirmar si ya existe en el catálogo (no tengo acceso a la base en vivo desde acá). Antes de configurar premios de tipo "objeto" hay que asegurarse de que el `objeto_id` exista.
5. **¿Ruleta circular o tragamonedas?** (ver punto 4 de diseño visual).
6. **Pergamino sin premios configurados todavía**: ¿qué debería pasar si Staff no configuró nada para, digamos, `PERG005`? Propongo que el botón "Abrir" quede deshabilitado con un aviso ("Próximamente") en vez de romper o dar un premio vacío.

## 7. Sugerencias adicionales

- **Historial ya integrado gratis**: como reutilizamos las funciones existentes (`sg_ficha_set_campo`, `sg_inventario_dar_objeto`, etc.), cada apertura de pergamino queda automáticamente registrada en el tab Historial de la ficha sin trabajo extra — el jugador puede repasar qué ganó y cuándo.
- **Auto-limitante por diseño**: como el pergamino se consume del inventario al abrirlo, no hace falta un límite artificial de "una vez por día" — el propio inventario ya limita cuántas veces se puede jugar. Si más adelante querés un límite temporal igual (ej. "1 pergamino gratis por semana"), es una feature aparte.
- **Opcional — "pity system"**: en muchos gachas, si tenés mala suerte muchas veces seguidas, el juego te garantiza un premio bueno después de N intentos fallidos, para que no se sienta injusto. No lo pediste, lo menciono por si te interesa como mejora futura — no lo asumo por defecto.
- **Reusar `strip_tags` / mismo cuidado con validación XML**: si en algún momento el resultado del sorteo se muestra dentro de un post del foro (ej. "narrar" la apertura como un `[dado]`), aplicaría exactamente las mismas precauciones que ya aprendimos con `[npc]`/`[objeto]` (autocerrar tags, evitar entidades HTML no válidas en XML, etc.) — lo tengo en cuenta si en algún momento pedís que esto también se pueda narrar en un post.

## 8. Plan de implementación sugerido (si confirmás todo lo de arriba)

1. Tabla `mybb_sg_sg_gacha_premios` (SQL).
2. Función(es) de sorteo + apertura en `sg_functions.php` (`sg_gacha_sortear($pergamino_id)`, `sg_gacha_abrir($uid, $pergamino_id)`), con la constante `SG_ORIGEN_PERGAMINO`.
3. `sg/abrir_pergamino.php` (endpoint AJAX, con `GET_LOCK`).
4. `sg/pergaminos.php` + template (listado de pergaminos que tenés + UI de apertura/animación).
5. `sg/admin/gestionar_pergaminos.php` + template (configuración de premios por Staff) + enlace en `staff_consola_mod.html`.
6. Pruebas: simulación de la distribución de probabilidades (¿da ~30% de las veces el premio de 30%, en una muestra grande?), casos límite (pergamino sin premios, jackpot, probabilidades que no suman 100%).

---

¿Confirmás las preguntas del punto 6, o querés que ajuste algo del diseño antes de que empecemos a implementar?
