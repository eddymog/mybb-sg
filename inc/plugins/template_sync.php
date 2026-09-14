<?php

/**
 * Template File Sync
 * - Writes templates to disk on admin save, new-template creation, and
 *   removes the file on template deletion.
 * - Detects FTP-uploaded changes and pushes them to the DB every ~5 seconds,
 *   checked only on page loads by TEMPLATE_SYNC_TRIGGER_UID.
 * - Adds a "Template Sync" page under AdminCP > Configuration with a button
 *   to bulk-export every DB template to disk on demand.
 * - Logs every write/import/delete/export to the template_sync_log table.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

require_once MYBB_ROOT . 'inc/plugins/template_sync_validate.php';

define('TEMPLATE_SYNC_INTERVAL',    5);
define('TEMPLATE_SYNC_BASE_DIR',    'templates');
define('TEMPLATE_SYNC_CACHE',       'cache/template_sync_last.php');
define('TEMPLATE_SYNC_TRIGGER_UID', 196);

function template_sync_info()
{
    return [
        'name'          => 'Template File Sync',
        'description'   => 'Syncs templates between the DB and /templates/: saves to disk on admin edits/creates, removes the file on delete, and imports FTP-uploaded changes (checked on UID ' . TEMPLATE_SYNC_TRIGGER_UID . '\'s page loads, every ~5 seconds). Adds a bulk-export button under Configuration > Template Sync. Logs every write/import/delete/export to template_sync_log.',
        'author'        => 'SG',
        'version'       => '1.6',
        'compatibility' => '18*',
    ];
}

function template_sync_is_installed()
{
    return file_exists(MYBB_ROOT . TEMPLATE_SYNC_CACHE);
}

function template_sync_install()
{
    global $db;

    // Mark all existing files as already synced — only files uploaded after this moment will be imported.
    file_put_contents(MYBB_ROOT . TEMPLATE_SYNC_CACHE, TIME_NOW);

    if (!$db->table_exists('template_sync_log')) {
        $collation = $db->build_create_table_collation();

        switch ($db->type) {
            case 'pgsql':
                $db->write_query("CREATE TABLE " . TABLE_PREFIX . "template_sync_log (
                    id serial,
                    sid int NOT NULL default 0,
                    title varchar(100) NOT NULL default '',
                    action varchar(10) NOT NULL default '',
                    uid int NOT NULL default 0,
                    dateline int NOT NULL default 0,
                    PRIMARY KEY (id)
                );");
                break;
            case 'sqlite':
                $db->write_query("CREATE TABLE " . TABLE_PREFIX . "template_sync_log (
                    id INTEGER PRIMARY KEY,
                    sid int NOT NULL default 0,
                    title varchar(100) NOT NULL default '',
                    action varchar(10) NOT NULL default '',
                    uid int NOT NULL default 0,
                    dateline int NOT NULL default 0
                );");
                break;
            default:
                $db->write_query("CREATE TABLE " . TABLE_PREFIX . "template_sync_log (
                    id int unsigned NOT NULL auto_increment,
                    sid int NOT NULL default 0,
                    title varchar(100) NOT NULL default '',
                    action varchar(10) NOT NULL default '',
                    uid int NOT NULL default 0,
                    dateline int NOT NULL default 0,
                    PRIMARY KEY (id),
                    KEY dateline (dateline)
                ) ENGINE=MyISAM{$collation};");
                break;
        }
    }
}

function template_sync_uninstall()
{
    global $db;

    @unlink(MYBB_ROOT . TEMPLATE_SYNC_CACHE);

    if ($db->table_exists('template_sync_log')) {
        $db->drop_table('template_sync_log');
    }
}

// MyBB only ever requires an AdminCP config page from a physical file under
// admin/modules/config/ — so activation drops a tiny loader there that just
// hands off to the real logic living with the rest of this plugin.
define('TEMPLATE_SYNC_ADMIN_LOADER', 'admin/modules/config/template_sync.php');

function template_sync_activate()
{
    $loader = MYBB_ROOT . TEMPLATE_SYNC_ADMIN_LOADER;

    if (!file_exists($loader)) {
        file_put_contents($loader, <<<'PHP'
<?php
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

require_once MYBB_ROOT . 'inc/plugins/template_sync_admin.php';

template_sync_admin_page();

PHP
        );
    }
}

function template_sync_deactivate()
{
    @unlink(MYBB_ROOT . TEMPLATE_SYNC_ADMIN_LOADER);
}

if (defined('IN_ADMINCP')) {
    $plugins->add_hook('admin_config_menu', 'template_sync_config_menu');
    $plugins->add_hook('admin_config_action_handler', 'template_sync_config_action_handler');
    $plugins->add_hook('admin_config_permissions', 'template_sync_config_permissions');
}

function template_sync_config_menu($sub_menu)
{
    $sub_menu['template_sync'] = [
        'id'    => 'template_sync',
        'title' => 'Template Sync',
        'link'  => 'index.php?module=config-template_sync',
    ];

    return $sub_menu;
}

function template_sync_config_action_handler($actions)
{
    $actions['template_sync'] = ['active' => 'template_sync', 'file' => 'template_sync.php'];

    return $actions;
}

function template_sync_config_permissions($admin_permissions)
{
    $admin_permissions['template_sync'] = 'Can manage Template Sync';

    return $admin_permissions;
}

function template_sync_sanitize($name)
{
    return preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
}

function template_sync_log($sid, $title, $action, $uid)
{
    global $db;

    $db->insert_query('template_sync_log', [
        'sid'      => (int) $sid,
        'title'    => $db->escape_string($title),
        'action'   => $db->escape_string($action),
        'uid'      => (int) $uid,
        'dateline' => TIME_NOW,
    ]);
}

// ── Hook: file → DB on every forum request (throttled) ───────────────────────

$plugins->add_hook('global_start', 'template_sync_check_files');

function template_sync_check_files()
{
    global $db, $mybb;

    if (empty($mybb->user['uid']) || (int) $mybb->user['uid'] !== TEMPLATE_SYNC_TRIGGER_UID) {
        return;
    }

    $cache_file = MYBB_ROOT . TEMPLATE_SYNC_CACHE;
    $last_sync  = file_exists($cache_file) ? (int) file_get_contents($cache_file) : 0;

    if (TIME_NOW - $last_sync < TEMPLATE_SYNC_INTERVAL) {
        return;
    }

    $now = TIME_NOW;

    // Sync each custom template set from its own folder — master isn't in
    // this table, so it's never watched here (matches the write side, which
    // never expects master edits to be re-imported).
    $query = $db->simple_select('templatesets', 'sid, title');
    while ($set = $db->fetch_array($query)) {
        $dir = MYBB_ROOT . TEMPLATE_SYNC_BASE_DIR . '/' . template_sync_sanitize($set['title']);

        if (!is_dir($dir)) {
            continue;
        }

        foreach (glob($dir . '/*.html') as $file) {
            if (filemtime($file) <= $last_sync) {
                continue;
            }

            $title   = basename($file, '.html');
            $content = rtrim(file_get_contents($file));

            if (template_sync_check_template($content)) {
                template_sync_log($set['sid'], $title, 'rejected', $mybb->user['uid']);
                continue;
            }

            $db->update_query(
                'templates',
                [
                    'template' => $db->escape_string($content),
                    'dateline' => $now,
                ],
                "title='" . $db->escape_string($title) . "' AND sid='" . (int) $set['sid'] . "'"
            );

            template_sync_log($set['sid'], $title, 'import', $mybb->user['uid']);
        }
    }

    file_put_contents($cache_file, $now);
}

// ── Hooks: DB → file on every admin save, delete, or new template ────────────

if (defined('IN_ADMINCP')) {
    $plugins->add_hook('admin_style_templates_edit_template_commit', 'template_sync_write_file', 5);
    $plugins->add_hook('admin_style_templates_add_template_commit', 'template_sync_write_file', 5);
    $plugins->add_hook('admin_style_templates_delete_template_commit', 'template_sync_delete_file', 5);
}

// Shared by the save/create hook and the bulk exporter. Returns true only if
// the file actually landed on disk.
function template_sync_write_template_file($sid, $title, $content)
{
    global $db;

    $sid = (int) $sid;

    if ($sid === -2) {
        $set_name = 'master';
    } elseif ($sid <= 0) {
        $set_name = 'custom';
    } else {
        $query    = $db->simple_select('templatesets', 'title', "sid='{$sid}'");
        $row      = $db->fetch_array($query);
        $set_name = $row ? $row['title'] : 'set_' . $sid;
    }

    $set_name  = template_sync_sanitize($set_name);
    $file_name = template_sync_sanitize($title) . '.html';

    $dir = MYBB_ROOT . TEMPLATE_SYNC_BASE_DIR . '/' . $set_name;

    // mkdir can fail if another request just created the same dir — only a
    // real failure if the dir still doesn't exist afterwards.
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        return false;
    }

    return file_put_contents($dir . '/' . $file_name, rtrim($content)) !== false;
}

function template_sync_write_file()
{
    global $mybb;

    $title = $mybb->input['title'];
    $sid   = (int) $mybb->input['sid'];

    if (template_sync_write_template_file($sid, $title, $mybb->input['template'])) {
        template_sync_log($sid, $title, 'write', $mybb->user['uid']);
    }
}

// Writes every current custom-set template to disk, overwriting whatever's
// already there. Used by the "Export now" button on the config page — never
// runs automatically, since it only makes sense as an explicit, on-demand
// refresh before an FTP editing session.
function template_sync_export_all()
{
    global $db, $mybb;

    $uid     = isset($mybb->user['uid']) ? $mybb->user['uid'] : 0;
    $exported = 0;

    $query = $db->simple_select('templates', 'sid, title, template', "sid != '-2'");
    while ($row = $db->fetch_array($query)) {
        if (template_sync_write_template_file($row['sid'], $row['title'], $row['template'])) {
            template_sync_log($row['sid'], $row['title'], 'export', $uid);
            $exported++;
        }
    }

    return $exported;
}

// $template here is the row (title, sid, set_title, ...) the delete_template
// action already fetched — set in admin/modules/style/templates.php's global
// scope, not passed as a hook argument.
function template_sync_delete_file()
{
    global $template, $mybb;

    $sid   = (int) $template['sid'];
    $title = $template['title'];

    $set_name = $sid <= 0 ? 'custom' : ($template['set_title'] ?: ('set_' . $sid));

    $set_name  = template_sync_sanitize($set_name);
    $file_name = template_sync_sanitize($title) . '.html';

    $file = MYBB_ROOT . TEMPLATE_SYNC_BASE_DIR . '/' . $set_name . '/' . $file_name;

    if (file_exists($file) && @unlink($file)) {
        template_sync_log($sid, $title, 'delete', $mybb->user['uid']);
    }
}
