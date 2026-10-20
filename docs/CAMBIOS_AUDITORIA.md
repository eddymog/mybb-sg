# Auditoría de Código y Registro de Cambios Pendientes - Shinobi Gaiden

**Fecha:** 2026-10-19  
**Estado:** En proceso de auditoría  
**Entorno:** MyBB 1.8 + Módulos personalizados `sg/`

---

## 1. Resumen de la Auditoría

Este documento recopila las inconsistencias, desajustes entre frontend/backend, errores potenciales y tareas identificadas durante la auditoría del código de Shinobi Gaiden.

---

## 2. Hallazgos y Correcciones Requeridas

### [AUDIT-001] Desfase en etiqueta BBCode de Armas (`[arma=]` vs `[objeto=]`)
* **Severidad:** Media / Alta (Afecta funcionalidad de usuarios en posts)
* **Estado:** Pendiente de aplicar

#### Descripción del problema:
Al utilizar el botón de la barra de herramientas del editor (SCEditor) para insertar armas:
1. El script cliente [`jscripts/bbcodes_sceditor.js`](file:///c:/Users/usuario/Downloads/Thekingdom/jscripts/bbcodes_sceditor.js#L710) inserta la etiqueta:
   ```text
   [arma=ID]
   ```
2. Sin embargo, en el backend, el plugin [`inc/plugins/tecnicatag.php`](file:///c:/Users/usuario/Downloads/Thekingdom/inc/plugins/tecnicatag.php#L415) únicamente busca y reemplaza:
   ```php
   while(preg_match('#\[objeto=(.*?)\]#si', $message, $matches))
   ```
   El plugin consulta `mybb_sg_sg_objetos` y dibuja `sg_objeto_card()`, pero **no tiene ninguna regla para `[arma=]`**.

#### Impacto:
Cualquier post donde un usuario pulse el botón "Code Arma" e inserte `[arma=...]` queda sin procesar (texto crudo visible en el tema) a menos que exista un reemplazo manual por MyCode en base de datos.

#### Cambios a realizar:
* **Backend (`inc/plugins/tecnicatag.php`):**
  - Ampliar la expresión regular en la línea ~415 para aceptar tanto `objeto` como `arma`:
    ```php
    // Soportar tanto [objeto=ID] como [arma=ID] para retrocompatibilidad total
    while(preg_match('#\[(objeto|arma)=(.*?)\]#si', $message, $matches))
    ```
* **Frontend (`jscripts/bbcodes_sceditor.js`):**
  - Unificar la inserción en la línea ~710 para insertar `[objeto=` o mantener `[arma=` una vez soportado en backend.
* **Caché / Plantillas MyBB:**
  - Si el JS se sirve minificado o desde caché de temas, purgar/actualizar la versión del asset.

---

### [AUDIT-002] Mapeo de Etiquetas SCEditor vs. Backend (`tecnicatag.php`)
* **Severidad:** Baja (Revisión de consistencia)
* **Estado:** En revisión

Alineación actual de etiquetas entre el editor visual y el parser PHP:

| Etiqueta SCEditor | Inserción en JS | Parser PHP (`tecnicatag.php`) | Estado |
| :--- | :--- | :--- | :--- |
| `[tecnica=ID]` | `bbcodes_sceditor.js:669` | `tecnicatag.php:1017` |  Correcto |
| `[arma=ID]` | `bbcodes_sceditor.js:710` | **No existe** (solo `[objeto=]`) |  **Desalineado** (Ver AUDIT-001) |
| `[vida=X,Y]` | `bbcodes_sceditor.js:756` | `tecnicatag.php:712` |  Correcto |
| `[chakra=X,Y]` | `bbcodes_sceditor.js:801` | `tecnicatag.php:808` |  Correcto |
| `[dado=XdY]` | `bbcodes_sceditor.js:846` | `tecnicatag.php:1219` |  Correcto |
| `[personaje]` | `bbcodes_sceditor.js:630` | `tecnicatag.php:524` |  Correcto |
| `[consumir=ID]` | *Sin botón en editor* | `tecnicatag.php:1293` |  Backend listo, evaluar si requiere botón |
| `[npc=CODIGO]` | *Sin botón en editor* | `tecnicatag.php:391` |  Backend listo, evaluar si requiere botón |

---

### [AUDIT-003] Widget "Calendario Shinobi" congelado en la página de inicio
* **Severidad:** Media (Experiencia de usuario / Información de rol desactualizada)
* **Estado:**  Implementado / Resuelto

#### Causa del problema:
El widget era 100% estático en la plantilla HTML y CSS. Tenía instrucciones en un comentario para que el Staff actualizara el día y el porcentaje `--fill` a mano.

#### Solución implementada (Ciclo dinámico de 90 días):
1. **Lógica en [`sg/functions/sg_functions.php`](file:///c:/Users/usuario/Downloads/Thekingdom/sg/functions/sg_functions.php):**
   - Se crearon las funciones `sg_calendario_shinobi_data()` y `sg_calendario_index_html()`.
   - Se definió un ciclo fijo de **90 días por temporada** (360 días por año shinobi: Otoño -> Invierno -> Primavera -> Verano) con fecha base configurada en `2026-10-19` para iniciar en **Año 150, Día 1 de Otoño**.
   - Se calcula automáticamente:
     - Año shinobi actual (`Año 150 + N`).
     - Estación actual y días de rol transcurridos (`Día X de 90`).
     - Porcentaje dinámico de la barra de progreso (`--fill: X%`).
     - Imagen de fondo dinámica mediante variable CSS `--sg-season-bg`.
2. **Inyección en [`index.php`](file:///c:/Users/usuario/Downloads/Thekingdom/index.php):**
   - Se inyectó `$index_calendario_shinobi = sg_calendario_index_html();` justo antes de compilar la plantilla `index`.
3. **Plantillas de referencia ([`templates/html/index.html`](file:///c:/Users/usuario/Downloads/Thekingdom/templates/html/index.html) y [`templates/SG2Templates/index.html`](file:///c:/Users/usuario/Downloads/Thekingdom/templates/SG2Templates/index.html)):**
   - En CSS: `.sg-index-season::after` ahora usa `var(--sg-season-bg, url('https://i.ibb.co/kxBKxcp/verano.png'))`.
   - En HTML: Se reemplazó el `<article>` quemado por la variable dinámica `{$index_calendario_shinobi}`.

>  **Paso pendiente para producción en MyBB:**  
> Según las directrices de `AGENTS.md`, MyBB almacena las plantillas en base de datos. Para que el cambio sea visible en producción, debe actualizarse en el Panel de Administración (ACP -> Plantillas -> Tu Tema -> index):
> 1. En el CSS `.sg-index-season::after`, sustituir la línea de `background` por:
>    `var(--sg-season-bg, url('https://i.ibb.co/kxBKxcp/verano.png'))`
> 2. Reemplazar el bloque `<article class="sg-index-panel sg-index-season">...</article>` por `{$index_calendario_shinobi}`.

---

### [AUDIT-004] Error de cálculo de nivel en la calculadora de estadísticas (`sg_calculadora`)
* **Severidad:** Baja / Media (Confusión para los jugadores al planificar builds)
* **Estado:**  Implementado / Resuelto

#### Descripción del problema:
En la plantilla [`templates/html/sg_calculadora.html`](file:///c:/Users/usuario/Downloads/Thekingdom/templates/html/sg_calculadora.html#L549), la función JavaScript calculaba el nivel sumando 1 artificialmente:
```javascript
function nivelvalue(fuerza, destreza, cchakra, inteligencia) {
  var prom = ((fuerza + destreza + cchakra + inteligencia) / 15) + 1; // <--- ERROR (+1)
  nivelida.innerHTML = Math.floor(prom);
}
```
Al crear una ficha de personaje nueva (Nivel 1), el jugador recibe **15 puntos iniciales** para repartir en fuerza, destreza, control de chakra e inteligencia. Con la fórmula anterior:
- Con 15 puntos: `(15 / 15) + 1 = 2` $\rightarrow$ arrojaba **Nivel 2** (un nivel más de lo que corresponde).
- Con 30 puntos: `(30 / 15) + 1 = 3` $\rightarrow$ arrojaba **Nivel 3**, desplazando todos los niveles un escalón por delante.

#### Corrección aplicada:
Se ajustó la fórmula para que el umbral de 15 puntos por nivel coincida exactamente con las reglas de creación y progresión del foro:
```javascript
function nivelvalue(fuerza, destreza, cchakra, inteligencia) {
  var puntos = fuerza + destreza + cchakra + inteligencia;
  var nivel = Math.max(1, Math.floor(puntos / 15));
  nivelida.innerHTML = nivel;
}
```
- **0 a 29 puntos:** Nivel 1 (cubre la creación y puntos antes de la primera subida).
- **30 a 44 puntos:** Nivel 2.
- **45 a 59 puntos:** Nivel 3.
- **60 a 74 puntos:** Nivel 4.

>  **Paso pendiente para producción en MyBB:**  
> Copiar la función corregida `nivelvalue()` en la plantilla `sg_calculadora` en **ACP $\rightarrow$ Plantillas y Estilo $\rightarrow$ Plantillas $\rightarrow$ [Tu Tema Activo] $\rightarrow$ `sg_calculadora`**.

---

### [AUDIT-005] Auditoría del Sistema de Dojo (`sg/dojo.php`) y Corrección de Acordeones
* **Severidad:** Media (Interfaz bloqueada si se usa plantilla base desincronizada)
* **Estado:**  Auditado / Corregido en plantilla

#### 1. Hallazgo en plantilla (`sg_dojo.html`):
En [`sg/dojo.php`](file:///c:/Users/usuario/Downloads/Thekingdom/sg/dojo.php#L249-L255), cada árbol de habilidades se renderiza inicialmente cerrado:
```php
$dojo_html .= "<section class=\"sg-tree is-collapsed\">";
$dojo_html .= "<div class=\"sg-tree-head\" onclick=\"sgToggleTree(this)\">";
```
Sin embargo, la plantilla [`templates/html/sg_dojo.html`](file:///c:/Users/usuario/Downloads/Thekingdom/templates/html/sg_dojo.html) carecía de la función `sgToggleTree(head)`, de la barra para expandir/colapsar todo y de las reglas CSS de `.is-collapsed`. Al hacer clic en un árbol, la consola arrojaba `Uncaught ReferenceError: sgToggleTree is not defined` y los árboles no se podían desplegar.

**Corrección aplicada:**
- Se añadieron a [`templates/html/sg_dojo.html`](file:///c:/Users/usuario/Downloads/Thekingdom/templates/html/sg_dojo.html) las funciones JS `sgToggleTree()` y `sgToggleAllTrees()`.
- Se incorporaron las reglas CSS para `.is-collapsed`, rotación del indicador (`.sg-tree-caret`) y el botón `Colapsar todo / Expandir todo`.

#### 2. Auditoría de lógica del backend (`sg_functions.php`):
- **Adquisición de Árboles:** Validada fórmula de coste ($15 + 10 \times \text{desbloqueos}$) y requisitos de nivel ($1, 5, 10, 15, 20$).
- **Ramas:** Validado coste ($10 + 5 \times \text{desbloqueos}$), rama inicial de clan gratuita y primera rama elemental gratuita.
- **Mejoras (Nivel de rama):** Verificada selección forzada de exactamente 2 mejoras de la misma rama y consumo preferente de créditos gratuitos (`nivel_rama_disponibles`).
- **Especializaciones:** Verificado desbloqueo a niveles 3, 6 y 9 del árbol sin coste de Tobis.
- **Seguridad y Concurrencia:** Protegido contra duplicación mediante `GET_LOCK("sg_dojo_$uid", 5)` y bloqueo total de acciones en vista de staff impersonada (`?uid=X`).

---

### [AUDIT-006] Atajo y Publicación Dinámica de Árboles de Habilidades en Técnicas
- **Estado:** ✅ Corregido / Implementado
- **Severidad:** Moderada (Mejora funcional de administración y CMS)
- **Archivos Afectados:**
  - `sg/tecnicas.php`
  - `sg/functions/sg_functions.php`
  - `templates/html/sg_tecnicas.html`
  - `templates/SG2Templates/sg_tecnicas.html`
- **Problema Detectado:**
  1. El listado de técnicas y árboles en `sg/tecnicas.php` (`templates/SG2Templates/sg_tecnicas.html`) estaba cableado de forma estática en el HTML. Si el Staff creaba un nuevo árbol en la base de datos, este no aparecía a los usuarios a menos que un programador editara las plantillas a mano.
  2. No existía un atajo rápido para que un administrador creara un árbol nuevo desde el propio apartado de técnicas del foro sin tener que usar PHPMyAdmin o rellenar múltiples formularios manuales.
- **Solución Implementada:**
  1. **Generación Dinámica del Grid de Técnicas (`sg_tecnicas_render_grid` & `sg_get_arboles_agrupados`):**
     - Consulta en tiempo real todos los árboles existentes en `mybb_sg_sg_tecnicas` agrupándolos automáticamente en sus secciones: Elementales, Clanes Konoha, Clanes Kiri, Jutsus Generales o Árboles Nuevos/Especiales.
     - Cualquier árbol creado aparece instantáneamente en el grid público.
  2. **Toolbar y Modal de Creación Rápida para Staff (`sg_tecnicas_render_staff_toolbar`):**
     - Solo visible para usuarios con rango de Staff/Mod (`is_mod($uid) || is_staff($uid)`).
     - Permite crear un árbol completo con 1 solo clic: define slug del árbol, sección, técnica base (con su TID y descripción) y las 3 ramas iniciales.
     - Reconstruye automáticamente la caché de catálogo del Dojo (`sg_rebuild_catalogo_arboles`) y registra la acción en `mybb_sg_sg_audit_consola`.
     - Incluye botón directo para forzar reconstrucción de caché en caliente.

---

### [AUDIT-007] Panel de Gestión Staff del Dojo y Atajo Directo desde Ficha
- **Estado:** ✅ Corregido / Implementado
- **Severidad:** Moderada (Herramienta operativa de administración)
- **Archivos Afectados:**
  - `sg/dojo.php`
  - `sg/functions/sg_functions.php`
  - `templates/html/sg_dojo.html`
  - `templates/SG2Templates/sg_dojo.html`
  - `templates/html/sg_ficha.html`
  - `templates/SG2Templates/sg_ficha.html`
- **Problema Detectado:**
  1. No existía ninguna interfaz directa para que los administradores otorgaran árboles de habilidades aprendidos a un personaje específico desde el juego. En el diseño de Shinobi Gaiden, poseer un árbol equivale a poseer su técnica base (`rama='Base'`), por lo que el Staff debía recurrir a inserciones SQL manuales.
  2. No había un atajo directo desde la ficha del personaje (`sg/ficha.php`) para abrir el Dojo del usuario con herramientas de gestión.
  3. Los moderadores no tenían controles visuales rápidos para habilitar/bloquear el acceso al Dojo (`puede_usar_dojo`) o conceder créditos de mejora gratuitos.
- **Solución Implementada:**
  1. **Atajo en Ficha (`.fx-staffbar`):**
     - Añadido enlace directo `🥋 Administrar Dojo` apuntando a `{$mybb->settings['bburl']}/sg/dojo.php?uid={$ficha['fid']}`.
  2. **Panel de Gestión Staff en Dojo (`sg_dojo_render_staff_panel`):**
     - Se renderiza al inicio del Dojo únicamente para usuarios Staff/Mod.
     - **Otorgar Árbol Directo:** Selector desplegable con todos los árboles del catálogo. Marca claramente cuáles ya posee el personaje. Al seleccionar uno y enviar, inserta la técnica base en `mybb_sg_sg_tec_aprendidas` mediante `sg_dojo_aprender` con origen Staff y auditoría.
     - **Habilitar / Bloquear Dojo:** Botón toggle instantáneo para alternar la bandera `puede_usar_dojo` del usuario.
     - **+1 Crédito de Mejora Gratis:** Concede directamente al usuario créditos de mejora para subir niveles de rama sin consumir Tobis.
     - **Refrescar Caché:** Actualiza la caché del catálogo de árboles en memoria/BD.
  3. **Seguridad reforzada:**
     - El modo impersonado de solo lectura sigue protegiendo las acciones de jugador, mientras que las acciones administrativas de Staff (`action_staff`) se validan con verificación estricta de permisos de Staff/Mod y se registran en los logs.

---

## 3. Registro de Próximas Secciones a Auditar

- [ ] **Módulo `sg/ficha.php` y `sg/editar_ficha.php`**: Validación de entrada y sanitización SQL en cálculo de atributos ninja.
- [ ] **Módulo `sg/tienda.php` y `sg/tienda_rins.php`**: Transacciones y control de saldo de Ryos/Rins para evitar duplicidad de compras.
- [ ] **Módulo `sg/entrenamientos.php`**: Auditoría de inserción en historial y permisos de validación de Staff.
- [ ] **Módulo `sg/dojo.php`**: Integridad de estructuras JSON en `arboles_progreso`.

---

> **Nota para desarrolladores:** Conforme avancemos en la auditoría, iremos registrando aquí cada anomalía con su respectivo identificador `[AUDIT-XXX]`, el análisis de impacto y los archivos a modificar.
