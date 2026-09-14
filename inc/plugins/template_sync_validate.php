<?php

/**
 * Template File Sync — content validation.
 * Standalone copy of admin/inc/functions.php's check_template() logic, kept
 * separate so template_sync.php never has to require the full admin
 * functions.php (and its 17 other functions) into front-end page loads.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function template_sync_check_template($template)
{
    // DB credentials leaking into a rendered template
    if (preg_match('#\$config\[(([\'|"]database[\'|"])|([^\'"].*?))\]\[(([\'|"](database|hostname|password|table_prefix|username)[\'|"])|([^\'"].*?))\]#i', $template)) {
        return true;
    }

    // System calls via backtick-style syntax
    if (preg_match('#\$\s*\{#', $template)) {
        return true;
    }

    // Any other unescaped {$...} eval-style expression
    if (preg_match("~\\{\\$.+?\\}~s", preg_replace('~\\{\\$+[a-zA-Z_][a-zA-Z_0-9]*((?:-\\>|\\:\\:)\\$*[a-zA-Z_][a-zA-Z_0-9]*|\\[\s*\\$*([\'"]?)[a-zA-Z_ 0-9 ]+\\2\\]\s*)*\\}~', '', $template))) {
        return true;
    }

    return false;
}
