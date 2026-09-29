<?php

/**
 * Template Manager screen.
 *
 * @var array<int,array{id:int,title:string,slug:string,engine:string,modified:string}> $rows
 * @var array<string,\ACB\Component>                                                     $components
 *
 * @package ACFComponentBuilder
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/** @var array<int,array<string,mixed>> $rows */
$rows = $rows ?? [];
/** @var array<string,\ACB\Component> $components */
$components = $components ?? [];

$builder_url = static fn(array $args): string =>
    esc_url(add_query_arg(array_merge(['page' => 'acb-builder'], $args), admin_url('admin.php')));
?>
<div class="wrap acb-wrap">
    <h1 class="acb-title">
        <span class="dashicons dashicons-layout"></span>
        <?php esc_html_e('Templates', 'acf-component-builder'); ?>
    </h1>
    <p class="description">
        <?php esc_html_e('Visual templates override the auto-generated markup for a component. Build one, save it, and it takes priority automatically.', 'acf-component-builder'); ?>
    </p>

    <div class="acb-panel">
        <h2><?php esc_html_e('Create a new template', 'acf-component-builder'); ?></h2>
        <p><?php esc_html_e('Pick a component to start from its auto-generated structure, then customise it visually.', 'acf-component-builder'); ?></p>
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" class="acb-new-template">
            <input type="hidden" name="page" value="acb-builder" />
            <select name="component" required>
                <option value=""><?php esc_html_e('— Choose a component —', 'acf-component-builder'); ?></option>
                <?php foreach ($components as $slug => $component) : ?>
                    <option value="<?php echo esc_attr($slug); ?>">
                        <?php echo esc_html($component->name . ' (' . $slug . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Open Builder', 'acf-component-builder'); ?>
            </button>
        </form>
    </div>

    <?php if (empty($rows)) : ?>
        <div class="acb-empty">
            <p><?php esc_html_e('No saved templates yet.', 'acf-component-builder'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped acb-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Title', 'acf-component-builder'); ?></th>
                    <th><?php esc_html_e('Component', 'acf-component-builder'); ?></th>
                    <th><?php esc_html_e('Engine', 'acf-component-builder'); ?></th>
                    <th><?php esc_html_e('Modified', 'acf-component-builder'); ?></th>
                    <th><?php esc_html_e('Actions', 'acf-component-builder'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($row['title']); ?></strong></td>
                        <td><code><?php echo esc_html($row['slug']); ?></code></td>
                        <td><span class="acb-badge acb-badge--layout"><?php echo esc_html($row['engine']); ?></span></td>
                        <td><?php echo esc_html($row['modified']); ?></td>
                        <td>
                            <a class="button button-small"
                               href="<?php echo $builder_url(['template' => $row['id']]); ?>">
                                <?php esc_html_e('Edit', 'acf-component-builder'); ?>
                            </a>
                            <button type="button"
                                    class="button button-small acb-dup-template"
                                    data-id="<?php echo esc_attr((string) $row['id']); ?>">
                                <?php esc_html_e('Duplicate', 'acf-component-builder'); ?>
                            </button>
                            <button type="button"
                                    class="button button-small acb-del-template"
                                    data-id="<?php echo esc_attr((string) $row['id']); ?>"
                                    data-acb-confirm>
                                <?php esc_html_e('Delete', 'acf-component-builder'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
/* Lightweight manager actions (delete / duplicate) via AJAX. */
(function () {
    'use strict';
    var cfg = {
        ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
        nonce: <?php echo wp_json_encode(wp_create_nonce('acb_builder')); ?>
    };
    function post(action, id, cb) {
        var body = new URLSearchParams();
        body.set('action', action);
        body.set('nonce', cfg.nonce);
        body.set('id', String(id));
        fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(cb)
            .catch(function () { window.location.reload(); });
    }
    document.querySelectorAll('.acb-del-template').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm(<?php echo wp_json_encode(__('Delete this template? This cannot be undone.', 'acf-component-builder')); ?>)) { return; }
            post('acb_delete_template', btn.dataset.id, function () { window.location.reload(); });
        });
    });
    document.querySelectorAll('.acb-dup-template').forEach(function (btn) {
        btn.addEventListener('click', function () {
            post('acb_duplicate_template', btn.dataset.id, function () { window.location.reload(); });
        });
    });
})();
</script>
