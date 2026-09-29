<?php
/**
 * Uninstall routine. Only runs on explicit plugin deletion.
 * Removes ACB options and internal CPT posts. It NEVER deletes ACF field
 * groups or content — that data belongs to ACF, not to this plugin.
 *
 * @package ACFComponentBuilder
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('acb_settings');
delete_option('acb_version');
delete_transient('acb_discovered_components');
delete_transient('acb_import_report');

// Remove internal component/template CPT entries (config only).
$types = ['acb_component', 'acb_template', 'acb_block'];
foreach ($types as $type) {
    $posts = get_posts([
        'post_type'   => $type,
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
    ]);
    foreach ($posts as $id) {
        wp_delete_post((int) $id, true);
    }
}
