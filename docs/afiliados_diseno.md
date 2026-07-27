# Sección de Afiliados — Diseño

Basado en el pedido del usuario. Esto es **solo diseño** — todavía no se
implementó código. Incluyo sugerencias marcadas como tales; el resto es la
interpretación directa de lo pedido.

## 1. Lo que se pidió

Una sección nueva en el índice del foro, **debajo de todo**, con tres niveles:

1. **Afiliado hermano** (1 solo, "One Piece Gaiden", mismo creador) — trato
   especial/destacado.
2. **Afiliados grandes** (2 a 4, cantidad no cerrada) — logos medianos.
3. **Afiliados pequeños** — cuadritos de **45×45px**.

Además:
- Herramienta de Staff para agregar/quitar afiliados de las tres categorías.
- Los cuadros **vacíos** (sin afiliado todavía) muestran un placeholder y un
  link a `sg/peticion_afiliados.php`, una página **pública, sin necesidad de
  registrarse**, con un formulario que dispara una petición administrativa.

## 2. Punto de enganche en el índice (clave del diseño)

Repasé `templates/html/index.html` y hay algo que ya está preparado para
exactamente este caso: al final del template, justo antes de `{$footer}`, existe

```html
<div class="sg-index-hook">{$index_section_mundo}{$index_section_offrol}{$index_section_final}</div>
```

Ninguna de esas tres variables (`index_section_mundo`, `index_section_offrol`,
`index_section_final`) la define hoy ningún archivo PHP — son placeholders
dejados a propósito para secciones futuras del índice, y quedan vacíos.

**Decisión final: llamada directa en `index.php`, sin plugin.** La primera
versión de este diseño evitaba tocar el core usando un plugin
(`inc/plugins/sg_afiliados.php`) enganchado a `index_end`. En la práctica esto
resultó difícil de diagnosticar en producción (depende de que el plugin quede
realmente activo en el ACP, de que no haya opcode cache sirviendo una copia
vieja de `sg_functions.php`, etc. — varios eslabones sin visibilidad remota).
El usuario prefirió simplificar: se **descartó el plugin** y en su lugar se
edita `index.php` (core de MyBB) directamente, justo antes del `eval` que arma
la página:

```php
$plugins->run_hooks('index_end');

// Sección de Afiliados (índice). Ver docs/afiliados_diseno.md.
$index_section_final = function_exists('sg_afiliados_index_html') ? sg_afiliados_index_html() : '';

eval('$index = "'.$templates->get('index').'";');
output_page($index);
```

**Riesgo documentado (regla de `AGENTS.md` sobre archivos core):** esto es un
cambio directo a `index.php`, que un update de MyBB podría sobrescribir. Es un
riesgo aceptado a cambio de simplicidad de despliegue (un solo archivo PHP más
para verificar, sin depender del estado de activación de un plugin ni de
caché de opcode). Si en el futuro se actualiza MyBB, hay que reaplicar estas
3 líneas.

## 3. Modelo de datos

Una sola tabla para los tres niveles (misma forma, distinto `tipo`), en vez de
tres tablas — evita duplicar esquema para algo que es conceptualmente lo mismo
("un afiliado, con nivel"):

```sql
CREATE TABLE `mybb_sg_sg_afiliados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('hermano','grande','pequeno') NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `url` varchar(255) NOT NULL,
  `imagen` varchar(255) NOT NULL,       -- logo/banner; ya subido vía subir_imagenes.php
  `descripcion` varchar(255) DEFAULT NULL, -- opcional; con más sentido en 'hermano'/'grande'
  `orden` int(11) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `agregado_por` varchar(255) NOT NULL DEFAULT '',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tipo_orden` (`tipo`, `orden`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

`tipo='hermano'` en la práctica va a tener **una sola fila activa** (One Piece
Gaiden), pero no lo restrinjo a nivel de esquema — así Staff puede, el día de
mañana, cambiar de "hermano" sin tocar código, simplemente desactivando el
viejo y creando el nuevo.

### Solicitudes públicas (sin login) — decisión final: reutilizar `mybb_sg_sg_peticiones`

Confirmado por el usuario: es una **petición normal, más, para que Staff la
revise** — no debe disparar ningún cambio en `mybb_sg_sg_afiliados`. Eso
simplifica el diseño: en vez de una tabla y un panel de revisión nuevos,
**reutilizo la tabla y la consola de peticiones que ya existen**
(`mybb_sg_sg_peticiones` / `sg/admin/peticiones_admin.php`), agregando una
categoría nueva `'afiliados'`.

El único ajuste necesario es que `sg/peticion_afiliados.php` (a diferencia de
`sg/peticiones.php`) **no exige login** — inserta igual en
`mybb_sg_sg_peticiones` pero con `uid='0'` y el nombre/contacto que la persona
haya escrito en el formulario (en vez de `$mybb->user['username']`, que no
existe para un visitante anónimo):

```php
$db->query("
    INSERT INTO `mybb_sg_sg_peticiones` (`uid`, `nombre`, `categoria`, `resumen`, `descripcion`, `url`)
    VALUES ('0', '$contacto_esc', 'afiliados', '$resumen_esc', '$descripcion_esc', '$url_foro_esc')
");
```

- `resumen` = algo tipo `"$nombre_foro solicita afiliación"`. El formulario
  público no pregunta nivel (grande/pequeño): esa nomenclatura es solo interna
  de `gestionar_afiliados.php`; si alguien quiere un nivel puntual, lo aclara
  en el mensaje libre.
- `descripcion` = el mensaje + el contacto, todo junto (la tabla no tiene
  columna `contacto` propia; se guarda ahí ya que es texto libre).
- `url` = la URL del foro que solicita.
- **Sin campo de imagen** — el formulario es puro texto (ver §6).

Cambios mínimos en lo ya existente:
- `peticiones_categoria_label()`: agregar `'afiliados' => 'Solicitud de Afiliación'`.
- `peticiones_admin.php`: agregar una línea más,
  `$peticiones_li .= print_peticion('Solicitudes de Afiliación', 'afiliados', $uid);`
  — es literalmente el mismo patrón que las otras 5 categorías, cero lógica
  nueva.

`gestionar_afiliados.php` (§5) queda **desacoplado por completo** de esto: es
pura administración de `mybb_sg_sg_afiliados`, sin ninguna bandeja de
solicitudes ni acción de "aprobar" que cree filas.

## 4. Render público (índice)

Nueva función en `sg_functions.php`, algo como:

```php
function sg_afiliados_index_html() {
    global $db;
    $filas = array('hermano' => array(), 'grande' => array(), 'pequeno' => array());
    $q = $db->query("SELECT * FROM mybb_sg_sg_afiliados WHERE activo=1 ORDER BY tipo, orden, id");
    while ($r = $db->fetch_array($q)) { $filas[$r['tipo']][] = $r; }
    // arma 3 bloques <section> con las tarjetas + cuadros vacíos (ver abajo)
    // y devuelve el HTML final para $index_section_final.
}
```

Estructura visual (mismo lenguaje visual "washi oscuro" del resto del sitio):

Un solo row, **tres columnas** (mismo ancho cada una). Los títulos públicos
son distintos del `tipo` interno (que sigue siendo `hermano`/`grande`/`pequeno`
en la tabla y en `gestionar_afiliados.php` — esa nomenclatura queda como algo
interno de la herramienta de Staff):

| `tipo` (interno) | Título público  |
|---|---|
| `hermano` | Afiliado Kage |
| `grande`  | Afiliados ANBU |
| `pequeno` | Afiliados Shinobis |

- **Columna Kage** (`hermano`): **solo imagen**, ancho de su columna, link al
  foro. Si no hay ninguno activo, un placeholder único ("Todavía no hay
  afiliado hermano") sin CTA de petición (no es solicitable, ver arriba).
- **Columna ANBU** (`grande`): **4 espacios** fijos (`SG_AFILIADOS_SLOTS_GRANDE = 4`
  como constante fácil de subir el día que se necesiten más). Si hay menos de
  4 activos, se completan los cuadros vacíos restantes con el placeholder +
  link a `peticion_afiliados.php`. Si en el futuro hay más de 4 activos,
  simplemente se muestran todos (la constante es un mínimo de espacios a
  completar con placeholder, no un tope duro).
- **Columna Shinobis** (`pequeno`): grid de cuadritos **45×45** (logo
  únicamente, `title` con el nombre, sin texto visible — por el tamaño).
  Mismo mecanismo que ANBU pero con su propia constante de espacios mínimos a
  mostrar (`SG_AFILIADOS_SLOTS_PEQUENO`); cada hueco vacío también linkea a
  `peticion_afiliados.php`.
- El formulario público es único — no distingue "grande" de "pequeño"; esa
  nomenclatura queda como algo interno de la herramienta de Staff.

Los cuadros vacíos son simplemente `<a>` con clase `.afil-slot--vacio`,
mostrando un ícono/símbolo (ej. "+") y `title="Solicitar afiliación"`.

## 5. Herramienta de Staff: `sg/admin/gestionar_afiliados.php`

Mismo patrón que `gestionar_banners.php` (el precedente más cercano: tarjetas
+ endpoint AJAX agregar/activar-desactivar/eliminar, con `verify_post_check`):

- Vista con 3 columnas/secciones (una por `tipo`), cada afiliado como tarjeta
  con thumbnail, nombre, estado, y botones Activar/Desactivar/Eliminar.
- Formulario "Agregar" por sección: nombre, URL, imagen (pegar URL — la imagen
  se sube antes con el uploader ya existente, `sg/admin/subir_imagenes.php`,
  igual que banners), descripción opcional, orden.
- **Sin ninguna bandeja de solicitudes acá** (decisión confirmada): esta
  página es pura administración de `mybb_sg_sg_afiliados`, nada más. Las
  solicitudes públicas se revisan aparte, en la consola de peticiones ya
  existente (ver §3) — completamente desacoplado, para que dar de alta un
  afiliado nunca dependa de "aprobar" nada.
- Enlace nuevo en `staff_consola_mod.html`.

## 6. `sg/peticion_afiliados.php` (pública, sin login)

- Sin ningún gate de sesión (a diferencia de `peticiones.php`).
- Formulario **100% texto, sin imagen** (decisión confirmada): nombre del
  foro, URL, contacto, mensaje. Sin selector de nivel — si alguien quiere
  "grande" o "pequeño" lo escribe en el mensaje; esa nomenclatura es solo
  interna de `gestionar_afiliados.php`.
- Al enviar: `INSERT` en `mybb_sg_sg_peticiones` con `categoria='afiliados'`
  (ver §3) + honeypot y rate-limit por IP (§8) + mensaje de confirmación
  (mismo patrón `sg_redireccion` que ya usa `peticiones.php`), y ese mensaje
  **debe decirle explícitamente que contacte a un administrador para
  coordinar imágenes y más detalles**: *"¡Tu solicitud fue enviada! Para
  coordinar el logo y los detalles de la afiliación, pueden contactar a
  cualquiera de nuestros administradores (daimyo); por defecto, a
  @kurosame."* Todo el texto orientado al público (subtítulo del formulario,
  placeholders, mensajes de error/éxito) usa español neutro, sin voseo.
- Los cuadros vacíos del índice linkean todos al mismo
  `peticion_afiliados.php`, sin parámetro de nivel.

## 7. Decisiones confirmadas

- **Afiliados Grandes (ANBU) arrancan con 4 espacios.** Constante fácil de
  subir después si hace falta.
- **"Hermano" es editable desde el panel** (queda como una fila más de la
  tabla, no hardcodeado) — en la práctica no va a cambiar nunca, pero permite
  actualizarle la imagen sin tocar código si hiciera falta.

## 8. Confirmaciones sobre las sugerencias originales

- **Anti-spam básico: aceptado.** Honeypot oculto + límite de "1 solicitud
  cada X minutos por IP" en `sg/peticion_afiliados.php`. Como ahora se
  reutiliza `mybb_sg_sg_peticiones` (sin columna `ip` propia), el rate-limit
  se resuelve más simple: mirar si ya existe una fila reciente con
  `categoria='afiliados'` y el mismo `$_SERVER['REMOTE_ADDR']` guardado en,
  por ejemplo, `descripcion` o vía una tabla auxiliar mínima
  `mybb_sg_sg_afiliados_rate_limit (ip, tiempo)` — más limpio que forzar una
  columna nueva en la tabla de peticiones genérica.
- **Sin subida de imágenes en el formulario público: confirmado como
  requisito** (no ya una sugerencia) — el formulario es puro texto (§6), y
  se le pide a la persona que se contacte con un administrador para lo demás.
- **Tamaño de imagen consistente en "Pequeños": confirmado.** Caja fija
  45×45 con `object-fit: cover`, sin importar el tamaño real de la imagen
  subida.

## 9. Plan de implementación sugerido

1. `docs/alter_afiliados.sql`: tabla `mybb_sg_sg_afiliados` + tabla auxiliar
   mínima para el rate-limit del formulario público (ver §8).
2. `sg_functions.php`: `sg_afiliados_index_html()` (arma las 3 secciones +
   cuadros vacíos con sus constantes de espacios mínimos).
3. `index.php`: llamada directa a `sg_afiliados_index_html()` para llenar
   `$index_section_final`, justo antes del `eval` de la plantilla `index`
   (ver decisión en §2 — sin plugin).
4. `sg/peticion_afiliados.php` + template público (formulario de puro texto +
   honeypot + rate-limit; inserta en `mybb_sg_sg_peticiones` con
   `categoria='afiliados'`).
5. Extender lo existente: `peticiones_categoria_label()` (+1 entrada) y
   `sg/admin/peticiones_admin.php` (+1 línea `print_peticion(...)`). Cero
   lógica nueva, es el mismo patrón que las otras 5 categorías.
6. `sg/admin/gestionar_afiliados.php` + template (CRUD puro, tipo
   `gestionar_banners`, sin bandeja de solicitudes). Enlace en
   `staff_consola_mod.html`.
7. CSS para las 3 secciones + cuadros vacíos, agregado a `index.html`.
8. Pruebas: alta/baja en cada nivel desde el panel, cuadro vacío linkea con el
   nivel correcto, la solicitud pública sin login aparece en la consola de
   peticiones bajo "Solicitudes de Afiliación", y respeta honeypot/rate-limit.
