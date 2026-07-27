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

**No hace falta tocar el `index.php` core de MyBB.** El hook `index_end` ya
corre justo antes del `eval` que arma la página (`index.php:467`, justo antes
de `$templates->get('index')`). La forma correcta y de bajo riesgo:

- Un plugin nuevo y chico: `inc/plugins/sg_afiliados.php`.
- Se engancha a `index_end` con `$plugins->add_hook('index_end', 'sg_afiliados_index_end');`.
- Esa función hace `global $index_section_final;` y le asigna el HTML de las 3
  secciones (armado por una función en `sg_functions.php`). Como `index.php`
  corre todo en scope global (no dentro de una función), un plugin que declara
  `global $index_section_final` y lo setea SÍ es visible para el `eval()` de
  más abajo — es la misma técnica que ya usa el plugin de `newpoints` en este
  repo para pasar contexto (`sg_np_origen`, etc.).

Esto respeta la regla del proyecto de no tocar archivos core sin necesidad
(`AGENTS.md`): cero cambios en `index.php`, todo vía hook.

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

### Solicitudes públicas (sin login)

`mybb_sg_sg_peticiones` (la tabla de peticiones ya existente) **exige estar
logueado** (`sg/peticiones.php` corta con "Tienes que estar logueado..." si
`uid==0`) y su forma (categoria/resumen/descripcion/url) no encaja con lo que
hace falta pedir acá (nombre del foro, link, contacto, nivel deseado). Por eso
una tabla nueva y separada:

```sql
CREATE TABLE `mybb_sg_sg_peticiones_afiliados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_foro` varchar(120) NOT NULL,
  `url_foro` varchar(255) NOT NULL,
  `contacto` varchar(150) NOT NULL,       -- email o usuario/discord de contacto
  `nivel_solicitado` enum('grande','pequeno') NOT NULL DEFAULT 'pequeno',
  `mensaje` text,
  `imagen_propuesta` varchar(255) DEFAULT NULL, -- opcional: URL a un logo ya alojado
  `ip` varchar(45) NOT NULL DEFAULT '',   -- ver §6, moderación anti-spam
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `tiempo` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
```

Nota: **"hermano" no es solicitable** desde este formulario — ese nivel lo
crea Staff a mano (es un caso especial de "mismo creador"), así que el select
solo ofrece `grande`/`pequeno`.

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

- **Afiliado hermano**: una tarjeta destacada, más grande, con logo + nombre +
  `descripcion` (blurb corto) + link. Si no hay ninguno activo, un placeholder
  único ("Todavía no hay afiliado hermano") sin CTA de petición (no es
  solicitable, ver arriba).
- **Afiliados grandes**: grid de 2 a 4 tarjetas medianas (logo + nombre).
  Si hay menos de 4 activas, se completan los **cuadros vacíos restantes**
  hasta un máximo configurable (sugiero 4, ver §7) con el placeholder + link a
  `peticion_afiliados.php?nivel=grande`.
- **Afiliados pequeños**: grid de cuadritos **45×45** (logo únicamente, `title`
  con el nombre, sin texto visible — por el tamaño). Acá la cantidad de
  "huecos vacíos" a mostrar es más ambigua (ver pregunta en §7); cada hueco
  vacío también linkea a `peticion_afiliados.php?nivel=pequeno`.

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
- **Bandeja de solicitudes pendientes** (`mybb_sg_sg_peticiones_afiliados`
  `WHERE estado='pendiente'`) integrada en la misma página, no en la consola
  general de peticiones — la forma del dato no encaja ahí y mezclarla sería
  ruido. Cada solicitud pendiente muestra sus datos + dos acciones:
  - **Aprobar**: abre/pre-llena el formulario de "Agregar" correspondiente
    (mismo nivel solicitado) para que Staff solo tenga que pegar la URL de
    imagen ya subida y confirmar — no un alta 100% automática, porque Staff
    debe poder revisar/ajustar antes de publicar.
  - **Rechazar**: marca `estado='rechazada'` (no se borra, queda de registro).
- Enlace nuevo en `staff_consola_mod.html`.

## 6. `sg/peticion_afiliados.php` (pública, sin login)

- Sin ningún gate de sesión (a diferencia de `peticiones.php`).
- Formulario: nombre del foro, URL, contacto, nivel deseado (grande/pequeño),
  mensaje, y opcionalmente una URL de imagen ya alojada (no subida directo acá
  — no le doy upload de archivos a un formulario anónimo, ver sugerencia en §8).
- Al enviar: `INSERT` en `mybb_sg_sg_peticiones_afiliados` + mensaje de
  confirmación (mismo patrón `sg_redireccion` que ya usa `peticiones.php`).
- Los cuadros vacíos del índice linkean acá con `?nivel=grande` o
  `?nivel=pequeno` para preseleccionar el nivel en el formulario.

## 7. Preguntas abiertas (no bloquean el diseño, pero definen detalles)

- **¿Cuántos "huecos vacíos" mostrar en Afiliados Grandes/Pequeños?** Para
  Grandes propongo un máximo fijo de 4 (si hay 2 activos, se muestran 2
  huecos). Para Pequeños, al ser cuadritos chicos pensados para crecer, ¿un
  número fijo (ej. siempre completar una fila de 8) o "mostrar siempre
  exactamente 1 hueco vacío al final, sin importar cuántos activos haya"? Esto
  cambia bastante el layout.
- **¿"Hermano" es editable por Staff o es un caso hardcodeado narrativamente
  único?** Lo diseñé como una fila más de la tabla (editable desde el panel)
  por consistencia y porque no cuesta nada extra, pero si preferís que sea
  literalmente fijo en el template (sin pasar por BD), es un cambio menor.

## 8. Sugerencias (no pedidas, las agrego para que decidas)

- **Anti-spam básico en el formulario público.** Al no exigir login, va a
  recibir bots tarde o temprano. Sugiero lo mínimo sin fricción para
  humanos: un campo *honeypot* oculto (si viene lleno, se descarta en
  silencio) + un límite simple de "1 solicitud cada X minutos por IP" (por
  eso la columna `ip` en la tabla). Nada de captcha visible salvo que prefieras
  más fricción a cambio de más seguridad.
- **No subida de archivos en el formulario anónimo.** Le pido una URL de
  imagen ya alojada en vez de un `<input type="file">` público — subir
  archivos sin autenticar es superficie de ataque innecesaria. Si el
  solicitante no tiene dónde alojarla, deja el campo vacío y Staff gestiona la
  imagen al aprobar (ya sea pidiéndosela por otro medio o subiéndola ella
  misma vía `subir_imagenes.php`).
- **Tamaño de imagen consistente en "Pequeños".** No se puede forzar que el
  archivo subido sea exactamente 45×45, así que la caja siempre es 45×45 con
  `object-fit: cover` — cualquier imagen (aunque no sea cuadrada) se recorta
  prolijo en vez de deformar el layout.
- **Aprobar ≠ alta automática.** Ya lo mencioné en §5, pero lo remarco: dejar
  que Staff revise antes de publicar (en vez de que "Aprobar" cree la fila
  directo) evita que alguien apruebe sin mirar la imagen/URL primero.

## 9. Plan de implementación sugerido

1. `docs/alter_afiliados.sql`: tablas `mybb_sg_sg_afiliados` y
   `mybb_sg_sg_peticiones_afiliados`.
2. `sg_functions.php`: `sg_afiliados_index_html()` (+ helper para los cuadros
   vacíos) y la lógica de anti-spam básica que use `peticion_afiliados.php`.
3. `inc/plugins/sg_afiliados.php`: plugin mínimo, hook `index_end`, setea
   `$index_section_final`. Sin tocar `index.php`.
4. `sg/peticion_afiliados.php` + template público (formulario + honeypot).
5. `sg/admin/gestionar_afiliados.php` + template (CRUD tipo `gestionar_banners`
   + bandeja de solicitudes pendientes). Enlace en `staff_consola_mod.html`.
6. CSS para las 3 secciones + cuadros vacíos, agregado a `index.html`.
7. Pruebas: alta/baja en cada nivel, cuadro vacío linkea con el nivel correcto,
   solicitud pública sin login funciona y respeta el honeypot/rate-limit,
   aprobar una solicitud pre-llena el alta correctamente.
