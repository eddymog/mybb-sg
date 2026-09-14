# DATABASE.md

## Purpose

This document is a practical database reference for agents and developers working in this repository.

It is based on the SQL dump at [docs/shinobi9_mybb.sql](/Users/eddymogollon/Documents/Code/mybb-sg/docs/shinobi9_mybb.sql), plus light cross-checking against current PHP usage in the codebase.

Use this file to understand:

- which tables exist
- which tables matter most for custom forum behavior
- how the custom data model is organized
- which files are likely to read or write each area

## Source Of Truth

- Primary schema source: [docs/shinobi9_mybb.sql](/Users/eddymogollon/Documents/Code/mybb-sg/docs/shinobi9_mybb.sql)
- Runtime code references: `sg/`, `inc/`, `showthread.php`, `newthread.php`, `global.php`, plugin files under `inc/plugins/`

Important caveat:

- The SQL dump is the best schema reference currently available, but code may have drifted over time.
- There are very few explicit foreign keys in the dump. Most relationships are enforced by convention in application code.
- Always verify field usage in PHP before changing queries.

## Naming Conventions

The schema uses two main prefixes:

- `mybb_sg_*`
  Standard MyBB tables with the project-specific table prefix.
- `mybb_sg_sg_*`
  Custom Shinobi Gaiden tables for forum/game mechanics.

There is also at least one audit table outside that pattern:

- `mybb_audit_prueba`

## How To Read This Schema

In practice, this project is built around four layers of data:

1. Standard MyBB forum/runtime data
   Users, forums, threads, posts, templates, themes, sessions, permissions.

2. Character sheet and progression data
   Fichas, stats, villages, clans, learned techniques, inventory, rewards.

3. Activity/workflow data
   Missions, training, reward claims, requests, likes, thread-character snapshots.

4. Audit/admin tracking
   Staff console logs, technique logs, stat edit logs, mission/training audit history.

## Quick Truths

- `mybb_sg_users` is the core identity table.
- `mybb_sg_sg_fichas` is the core custom character sheet table.
- Many custom relationships are convention-based: `fid` or `uid` often points to `mybb_sg_users.uid`.
- Post/thread rendering depends on both MyBB tables and SG tables.
- `inc/functions_post.php` is one of the highest-value files for understanding schema usage.
- There are custom audit tables for many state-changing flows.
- There is a trigger on `mybb_sg_sg_fichas` that logs some updates into `mybb_audit_prueba`.

## Core MyBB Tables Most Relevant To Custom Code

### `mybb_sg_users`

Purpose:

- Main MyBB user table.
- Also stores forum/game-related values used by custom systems, especially `newpoints`.

Important columns:

- `uid`
- `username`
- `avatar`
- `avatar2`
- `usergroup`
- `displaygroup`
- `postnum`
- `threadnum`
- `signature`
- `newpoints`

Common usage in code:

- Character pages read user avatar and points.
- Admin tools update PR or other account-level values.
- Post rendering joins against user and usergroup data.

Representative code paths:

- `sg/ficha.php`
- `sg/ficha2.php`
- `sg/misiones.php`
- `sg/entrenamientos.php`
- `sg/admin/ficha_atributos.php`
- `global.php`

### `mybb_sg_forums`

Purpose:

- Forum/category structure and forum-level settings.

Important columns:

- `fid`
- `name`
- `pid`
- `parentlist`
- `type`
- `open`
- `style`
- `allowhtml`
- `allowmycode`
- `allowsmilies`
- `allowimgcode`
- `allowvideocode`

Why it matters:

- Custom code often filters by forum hierarchy.
- `parentlist` is used to infer forum areas such as roleplay sections.

Representative code paths:

- `newthread.php`
- `global.php`
- `sg/ficha.php`
- `sg/censo.php`

### `mybb_sg_threads`

Purpose:

- Standard MyBB thread metadata.

Important columns:

- `tid`
- `fid`
- `subject`
- `uid`
- `firstpost`
- `lastpost`
- `lastposteruid`
- `views`
- `replies`
- `visible`

Why it matters:

- Used by both standard forum flows and custom thread-linked features.
- Custom tables such as `mybb_sg_sg_thread_personaje`, `mybb_sg_sg_likes`, `mybb_sg_sg_hide`, and `mybb_sg_sg_threads_cron` point at threads.

Representative code paths:

- `showthread.php`
- `newthread.php`
- `global.php`
- `sg/censo.php`

### `mybb_sg_posts`

Purpose:

- Standard MyBB post records.

Important columns:

- `pid`
- `tid`
- `fid`
- `uid`
- `username`
- `message`
- `dateline`
- `visible`

Why it matters:

- Many SG systems attach data to posts, including likes and hide mechanics.
- Post rendering pulls in SG-specific enrichments.

Representative code paths:

- `inc/functions_post.php`
- `sg/liked.php`
- `hide.php`

### `mybb_sg_usergroups`

Purpose:

- Standard MyBB permissions and styling by group.

Important columns:

- `gid`
- `title`
- `namestyle`
- permission flags

Why it matters:

- The project uses usergroups for village/staff display and rendering logic.

Representative code paths:

- `global.php`
- `inc/functions_post.php`

### `mybb_sg_templates` and `mybb_sg_themestylesheets`

Purpose:

- MyBB database-backed templates and theme CSS records.

Why it matters:

- Many live templates are database-managed, even though reference copies exist under `templates/`.
- File edits in `templates/` do not automatically update these DB tables.

## Core Custom Tables

### `mybb_sg_sg_fichas`

Purpose:

- Main character sheet table.
- This is the central custom entity in the project.

Primary key shape:

- Logical key: `fid`
- In practice, `fid` maps to `mybb_sg_users.uid`

Important columns:

- identity and profile:
  - `fid`
  - `nombre`
  - `apodo`
  - `edad`
  - `temporada_nacimiento`
  - `sexo`
  - `peso`
  - `altura`
  - `fisico_de_pj`
  - `banner`
- progression and economy:
  - `puntos_habilidad`
  - `ryos`
  - `pe`
  - `reputacion`
  - `puntos_estadistica`
  - `mejoras`
  - `nivel`
  - `limite_nivel`
  - `limite_clase`
- affiliation:
  - `villa`
  - `clan`
  - `rango`
- specialization and build:
  - `espe`
  - `espe_estilo`
  - `maestria`
  - `maestria_secundaria`
  - `elemento1` through `elemento5`
  - `invocacion`
  - `invocacion_secundaria`
  - `pasiva_slot`
  - `kosei1`
  - `kosei2`
- stats:
  - `str`
  - `res`
  - `spd`
  - `agi`
  - `dex`
  - `pres`
  - `inte`
  - `ctrl`
  - `vida`
  - `chakra`
  - `regchakra`
- newer/parallel stat model:
  - `fuerza`
  - `destreza`
  - `cchakra`
  - `inteligencia`
  - `salud`
  - `velocidad`
  - `tenketsu`
  - `sigilo`
- text fields:
  - `apariencia`
  - `personalidad`
  - `historia`
  - `extra`
  - `frase`
  - `notas`
  - `virtudes`
  - `defectos`
- moderation:
  - `moderated`

Representative code paths:

- `sg/ficha.php`
- `sg/ficha2.php`
- `sg/nueva_ficha.php`
- `sg/editar_ficha.php`
- `sg/ficha_editada.php`
- `sg/nueva_descripcion.php`
- `sg/censo.php`
- `inc/functions_post.php`

Notes:

- This table appears to support both an older stat model (`str`, `res`, etc.) and a newer one (`fuerza`, `destreza`, etc.).
- The `moderated` field is important in ficha visibility logic.
- There is a trigger `log_fichas_b_u` that logs some updates into `mybb_audit_prueba`.

### `mybb_sg_sg_clanes`

Purpose:

- Clan catalog and clan availability.

Important columns:

- `cid`
- `vid`
- `nombreClan`
- `elementos`
- `abierto`
- `descripcion`
- `img`
- `activo`

Representative code paths:

- `sg/ficha.php`
- `sg/ficha2.php`
- `sg/tecnicas_lista.php`
- `sg/censo.php`

Relationship notes:

- `mybb_sg_sg_fichas.clan` points here by convention.
- `vid` appears to relate a clan to a village.

### `mybb_sg_sg_villas`

Purpose:

- Village catalog and open/closed state.

Important columns:

- `vid`
- `nombreVilla`
- `abierta`
- `numUsers`
- `img`
- `activa`

Representative code paths:

- `sg/ficha.php`
- `sg/ficha2.php`
- `sg/censo.php`

Relationship notes:

- `mybb_sg_sg_fichas.villa` points here by convention.

### `mybb_sg_sg_tecnicas`

Purpose:

- Technique catalog.

Important columns:

- `tid`
- `nombre`
- `arbol`
- `rama`
- `tipo`
- `aldea`
- `categoria`
- `sellos`
- `rango`
- `exclusiva`
- `acciones`
- `coste`
- `efecto`
- `requisito`
- `descripcion`

Representative code paths:

- `sg/tecnicas.php`
- `sg/tecnicas2.php`
- `sg/tecnicas_lista.php`
- `sg/tecnicas_show.php`
- `sg/tecnicas_show2.php`
- `sg/ficha.php`
- `sg/ficha2.php`

Notes:

- `efecto` is application-significant and may encode structured values interpreted in PHP.

### `mybb_sg_sg_tecnicas_version`

Purpose:

- Versioned history or moderation/balance snapshot of techniques.

Important columns:

- `tid`
- `tid_old`
- `version`
- `nombre`
- `puntuacion`
- `balance`
- `notas_balance`
- `balance_prioridad`

Why it matters:

- Useful for understanding technique evolution, balance changes, or moderation workflows.

### `mybb_sg_sg_tec_aprendidas`

Purpose:

- Join table for learned techniques.

Important columns:

- `id`
- `tid`
- `uid`
- `tiempo`

Representative code paths:

- `sg/ficha.php`
- `sg/ficha2.php`
- `sg/entrenamientos.php`

Relationship notes:

- `uid` links to `mybb_sg_users.uid` / `mybb_sg_sg_fichas.fid`
- `tid` links to `mybb_sg_sg_tecnicas.tid`

### `mybb_sg_sg_tec_para_aprender`

Purpose:

- Queue/join table for techniques pending learning.

Important columns:

- `id`
- `tid`
- `uid`
- `tiempo`

Representative code paths:

- likely used in technique/training/admin workflows

## Missions, Training, Rewards

### `mybb_sg_sg_misiones_lista`

Purpose:

- Mission catalog.

Important columns:

- `id`
- `cod`
- `rango`
- `niv`
- `title`
- `descripcion`
- `ryos`
- `expt`
- `time`
- `coste`

Representative code paths:

- `sg/misiones.php`

### `mybb_sg_sg_misiones_usuarios`

Purpose:

- Active mission assignment per user.

Important columns:

- `id`
- `cod`
- `uid`
- `nombre`
- `tiempo_iniciado`
- `tiempo_finaliza`
- `mision_duracion`

Representative code paths:

- `sg/misiones.php`
- `sg/censo.php`

### `mybb_sg_sg_entrenamientos_usuarios`

Purpose:

- Active training assignment per user.

Important columns:

- `id`
- `tid`
- `uid`
- `nombre`
- `tiempo_iniciado`
- `tiempo_finaliza`
- `duracion`

Representative code paths:

- `sg/entrenamientos.php`
- `sg/censo.php`

Relationship notes:

- `tid` here refers to a technique id, not a forum thread id.

### `mybb_sg_sg_recompensas_usuarios`

Purpose:

- User reward claim tracking, likely daily reward or periodic claim state.

Important columns:

- `id`
- `uid`
- `nombre`
- `dia`
- `tiempo`

Representative code paths:

- `sg/censo.php`
- likely reward-related custom flows

### `mybb_sg_sg_experiencia_limite`

Purpose:

- Experience cap tracking by user/week.

Important columns:

- `id`
- `uid`
- `semana`
- `experiencia_semanal`

Why it matters:

- Important if weekly progression limits are changed.

## Inventory, Objects, Shop

### `mybb_sg_sg_objetos`

Purpose:

- Master catalog for custom items and weapons.

Important columns:

- `id`
- `objeto_id`
- `nombre`
- `rango`
- `categoria`
- `tipo`
- `descripcion`
- `coste`
- `cantidadMaxima`
- `imagen`
- `upgrade`
- `efecto`
- `exclusivo`

Representative code paths:

- `sg/objetos.php`
- `sg/armas.php`
- `sg/inventario.php`
- `sg/admin/crear_objetos.php`
- `sg/admin/modificar_objetos.php`

### `mybb_sg_sg_inventario`

Purpose:

- User inventory join table.

Important columns:

- `id`
- `objeto_id`
- `uid`
- `cantidad`
- `tiempo`

Representative code paths:

- `sg/inventario.php`
- `sg/admin/modificar_objetos.php`

Relationship notes:

- `objeto_id` links to `mybb_sg_sg_objetos.objeto_id`
- `uid` links to `mybb_sg_users.uid`

### `mybb_sg_sg_tienda`

Purpose:

- Shop-facing inventory or legacy shop item table.

Important columns:

- `eid`
- `rango`
- `nombreArma`
- `tipo`
- `categoria`
- `descripcion`
- `coste`
- `cantidadMax`
- `urlImagen`

Notes:

- There is overlap in domain with `mybb_sg_sg_objetos`.
- Verify which table is actually authoritative before changing shop logic.

## Post/Thread-Linked Custom Mechanics

### `mybb_sg_sg_likes`

Purpose:

- Likes attached to forum posts.

Important columns:

- `pid`
- `tid`
- `fid`
- `uid`
- `username`
- `subject`
- `liked_by_uid`
- `liked_by_username`
- `liked_by_timestamp`

Representative code paths:

- `inc/functions_post.php`
- `sg/liked.php`

Relationship notes:

- `pid` links to `mybb_sg_posts.pid`
- `tid` links to `mybb_sg_threads.tid`
- `uid` is the post author
- `liked_by_uid` is the liking user

### `mybb_sg_sg_thread_personaje`

Purpose:

- Snapshot of a user's active character state for a specific thread/post context.

Important columns:

- `id`
- `tid`
- `pid`
- `uid`
- `clase`
- `fue`
- `res`
- `vel`
- `agi`
- `des`
- `pre`
- `int`
- `cck`
- `vida`
- `chakra`
- `regchakra`
- `nombre`
- `espe`
- `estilo`
- `maestria`
- `maestria2`
- `inventario`
- `timestamp`

Representative code paths:

- `inc/functions_post.php`
- `inc/plugins/tecnicatag.php`
- `sg/censo.php`

Why it matters:

- This table appears central to thread-specific character stats and custom tags like `[personaje=...]`.

### `mybb_sg_sg_hide`

Purpose:

- Hidden-content mechanic linked to posts/threads.

Important columns:

- `hid`
- `tid`
- `pid`
- `uid`
- `hide_counter`
- `show_hide`
- `hide_uids`
- `hide_content`
- `tiempo_creacion`

Representative code paths:

- `hide.php`
- `inc/plugins/hidetag.php`

### `mybb_sg_sg_susurro`

Purpose:

- Whisper/hidden-content variant.

Important columns:

- `hid`
- `tid`
- `pid`
- `uid`
- `hide_counter`
- `susurro_ids`
- `hide_content`
- `tiempo_creacion`

### `mybb_sg_sg_dados`

Purpose:

- Dice/custom post mechanic storage.

Important columns:

- `did`
- `tid`
- `pid`
- `uid`
- `dado_counter`
- `dado_content`
- `tiempo_creacion`

## NPCs, Requests, Codes, Misc Gameplay Tables

### `mybb_sg_sg_npcs`

Purpose:

- NPC catalog with stats and presentation fields.

Important columns:

- `npc_id`
- `nombre`
- `apodo`
- `faccion`
- `edad`
- `temporada`
- `rango`
- stat fields
- `vida`
- `chakra`
- `reg_chakra`
- `apariencia`
- `personalidad`
- `historia1`
- `historia2`
- `historia3`
- `extra`
- `notas`
- `avatar1`
- `avatar2`

Representative code paths:

- `sg/npcs.php`
- `sg/admin/npcs_crear.php`
- `sg/admin/npcs_modificar.php`

### `mybb_sg_sg_peticiones`

Purpose:

- User requests/tickets submitted through the custom system.

Important columns:

- `id`
- `uid`
- `nombre`
- `categoria`
- `resumen`
- `descripcion`
- `url`
- `resuelto`
- `tiempo`
- `mod_uid`
- `mod_nombre`

Representative code paths:

- `sg/peticiones.php`
- `sg/admin/peticiones_admin.php`

### `mybb_sg_sg_codigos_admin`

Purpose:

- Admin-created codes with expiration and category.

Important columns:

- `id`
- `codigo`
- `expiracion_codigo`
- `duracion`
- `categoria`
- `uso_unico`
- `usado`

### `mybb_sg_sg_codigos_usuarios`

Purpose:

- Codes assigned/claimed by users.

Important columns:

- `id`
- `uid`
- `nombre`
- `codigo`
- `categoria`
- `expiracion`

Representative code paths:

- `sg/promocion.php`
- `sg/censo.php`

### `mybb_sg_sg_sabiasque`

Purpose:

- "Did you know?" content table.

Important columns:

- `id`
- `tipo`
- `texto`

Representative code paths:

- `sg/admin/sabiasque_modificar.php`

### `mybb_sg_sg_hentai`

Purpose:

- User-level toggle/preferences table for a specific custom content mode.

Representative code paths:

- `sg/admin/hentai.php`

### `mybb_sg_sg_pages`

Purpose:

- Query/page logging table.

Important columns:

- `id`
- `queries`
- `url`
- `username`
- `uid`
- `timestamp`

## Audit Tables

These tables matter whenever you change progression, admin tools, or rewards.

### Main audit tables

- `mybb_sg_sg_audit_consola`
- `mybb_sg_sg_audit_consola_mod`
- `mybb_sg_sg_audit_consola_tec`
- `mybb_sg_sg_audit_consola_tec_mod`
- `mybb_sg_sg_audit_descripcion`
- `mybb_sg_sg_audit_entrenamientos`
- `mybb_sg_sg_audit_general`
- `mybb_sg_sg_audit_misiones`
- `mybb_sg_sg_audit_recompensas`
- `mybb_sg_sg_audit_stats`

What they usually capture:

- staff changes
- moderation console activity
- technique-related staff changes
- description edits
- completed trainings
- completed missions
- reward claims
- stat edits

Representative code paths:

- `sg/nueva_descripcion.php`
- `sg/misiones.php`
- `sg/entrenamientos.php`
- `sg/admin/ficha_atributos.php`
- `sg/admin/modificar_objetos.php`
- `sg/admin/log_consola.php`

## Inferred Relationships That Matter

These are application-level relationships that agents should keep in mind:

- `mybb_sg_users.uid` <-> `mybb_sg_sg_fichas.fid`
  Main user-to-character-sheet mapping.

- `mybb_sg_sg_fichas.clan` <-> `mybb_sg_sg_clanes.cid`

- `mybb_sg_sg_fichas.villa` <-> `mybb_sg_sg_villas.vid`

- `mybb_sg_sg_tec_aprendidas.uid` <-> `mybb_sg_users.uid`
- `mybb_sg_sg_tec_aprendidas.tid` <-> `mybb_sg_sg_tecnicas.tid`

- `mybb_sg_sg_inventario.uid` <-> `mybb_sg_users.uid`
- `mybb_sg_sg_inventario.objeto_id` <-> `mybb_sg_sg_objetos.objeto_id`

- `mybb_sg_sg_likes.pid` <-> `mybb_sg_posts.pid`
- `mybb_sg_sg_likes.tid` <-> `mybb_sg_threads.tid`

- `mybb_sg_sg_thread_personaje.tid` <-> `mybb_sg_threads.tid`
- `mybb_sg_sg_thread_personaje.pid` <-> `mybb_sg_posts.pid`
- `mybb_sg_sg_thread_personaje.uid` <-> `mybb_sg_users.uid`

- `mybb_sg_sg_hide.pid` <-> `mybb_sg_posts.pid`
- `mybb_sg_sg_hide.tid` <-> `mybb_sg_threads.tid`

## Tables Agents Should Treat As High Risk

Changes touching these tables should be treated carefully:

- `mybb_sg_sg_fichas`
  Core progression and rendering table.

- `mybb_sg_users`
  Identity, groups, avatars, and PR/newpoints.

- `mybb_sg_sg_tecnicas`
  Shared technique catalog used across multiple flows.

- `mybb_sg_sg_tec_aprendidas`
  Learned-technique state.

- `mybb_sg_sg_misiones_usuarios`
  Active mission state.

- `mybb_sg_sg_entrenamientos_usuarios`
  Active training state.

- `mybb_sg_sg_inventario`
  User-owned items.

- `mybb_sg_sg_likes`
  Post rendering enrichment and interaction data.

- `mybb_sg_sg_thread_personaje`
  Thread-level character snapshot logic.

## Recommended Agent Workflow Before Editing SQL-Oriented Code

1. Identify the table(s) involved.
2. Read their definitions in [docs/shinobi9_mybb.sql](/Users/eddymogollon/Documents/Code/mybb-sg/docs/shinobi9_mybb.sql).
3. Search the codebase for the table name.
4. Check whether the table is read in templates/rendering code, admin tools, and audit flows.
5. Check whether the fields are used in more than one stat model or progression path.
6. If changing writes, look for audit side effects.
7. If changing read logic, check `inc/functions_post.php`, `sg/`, and any relevant plugin hooks.

## Gaps / Limitations

This is not a full DBA-grade schema document.

Current limitations:

- not every MyBB core table is explained in detail
- some table purposes are inferred from names and code usage
- there are no fully mapped foreign keys because the schema relies mostly on application convention
- the dump may not reflect every later production change

When in doubt:

- trust the SQL dump for column names
- trust current code for runtime usage
- trust neither blindly without checking both
