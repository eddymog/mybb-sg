<?php

/**
 * Template File Sync — AdminCP config page.
 * Loaded via a tiny loader file at admin/modules/config/template_sync.php
 * (written by template_sync_activate()), so the real logic can stay here
 * with the rest of the plugin instead of living inside a core admin folder.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function template_sync_admin_page()
{
    global $mybb, $page;

    $page->add_breadcrumb_item('Template Sync', 'index.php?module=config-template_sync');

    if ($mybb->request_method == 'post') {
        $count = template_sync_export_all();

        flash_message("Exported {$count} template(s) to disk.", 'success');
        admin_redirect('index.php?module=config-template_sync');
    }

    $page->output_header('Template Sync');

    $sub_tabs['template_sync'] = [
        'title'       => 'Template Sync',
        'link'        => 'index.php?module=config-template_sync',
        'description' => 'Export every database template to its on-disk mirror under ' . TEMPLATE_SYNC_BASE_DIR . '/.',
    ];
    $page->output_nav_tabs($sub_tabs, 'template_sync');

    $form = new Form('index.php?module=config-template_sync', 'post');

    $form_container = new FormContainer('Export templates to disk');
    $form_container->output_row(
        'Bulk export',
        'Writes every current custom-set template to its ' . TEMPLATE_SYNC_BASE_DIR . '/&lt;set&gt;/ folder, overwriting whatever is currently on disk. Use this before an FTP editing session to make sure the files match the database.'
    );
    $form_container->end();

    $buttons[] = $form->generate_submit_button('Export now');
    $form->output_submit_wrapper($buttons);
    $form->end();

    $page->output_footer();
}
