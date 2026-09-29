<?php

/**
 * Visual Template Builder shell. All interaction is driven by builder.js,
 * which reads the bootstrap data attributes below and talks to the AJAX API.
 *
 * @var int                    $template_id
 * @var string                 $slug
 * @var \ACB\Component|null     $component
 * @var array<string,\ACB\Component> $components
 *
 * @package ACFComponentBuilder
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$template_id = (int) ($template_id ?? 0);
$slug        = (string) ($slug ?? '');
$component   = $component ?? null;
$manager_url = esc_url(admin_url('admin.php?page=acb-templates'));
?>
<div class="wrap acb-wrap acb-builder-wrap"
     data-template-id="<?php echo esc_attr((string) $template_id); ?>"
     data-slug="<?php echo esc_attr($slug); ?>">

    <h1 class="acb-title">
        <span class="dashicons dashicons-layout"></span>
        <?php esc_html_e('Template Builder', 'acf-component-builder'); ?>
        <?php if ($component) : ?>
            <span class="acb-builder-sub">— <?php echo esc_html($component->name); ?></span>
        <?php endif; ?>
    </h1>

    <?php if (!$component) : ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('No component selected. Choose one from the Templates screen to begin.', 'acf-component-builder'); ?></p>
            <p><a class="button" href="<?php echo $manager_url; ?>"><?php esc_html_e('Back to Templates', 'acf-component-builder'); ?></a></p>
        </div>
    <?php else : ?>

    <div class="acb-builder-toolbar">
        <label>
            <?php esc_html_e('Title', 'acf-component-builder'); ?>
            <input type="text" id="acb-tpl-title" value="<?php echo esc_attr($component->name); ?>" />
        </label>
        <label>
            <?php esc_html_e('Layout engine', 'acf-component-builder'); ?>
            <select id="acb-tpl-engine">
                <option value="bootstrap"><?php esc_html_e('Bootstrap', 'acf-component-builder'); ?></option>
                <option value="flex"><?php esc_html_e('Flexbox', 'acf-component-builder'); ?></option>
                <option value="grid"><?php esc_html_e('CSS Grid', 'acf-component-builder'); ?></option>
            </select>
        </label>
        <label>
            <?php esc_html_e('Container', 'acf-component-builder'); ?>
            <select id="acb-tpl-container">
                <option value=""><?php esc_html_e('None', 'acf-component-builder'); ?></option>
                <option value="container"><?php esc_html_e('container', 'acf-component-builder'); ?></option>
                <option value="container-fluid"><?php esc_html_e('container-fluid', 'acf-component-builder'); ?></option>
            </select>
        </label>
        <label>
            <?php esc_html_e('Section class', 'acf-component-builder'); ?>
            <input type="text" id="acb-tpl-class" placeholder="acb-section my-mod" />
        </label>
        <span class="acb-toolbar-spacer"></span>
        <button type="button" class="button" id="acb-tpl-reset">
            <?php esc_html_e('Reset to auto', 'acf-component-builder'); ?>
        </button>
        <button type="button" class="button button-primary" id="acb-tpl-save">
            <?php esc_html_e('Save template', 'acf-component-builder'); ?>
        </button>
        <a class="button" href="<?php echo $manager_url; ?>"><?php esc_html_e('Done', 'acf-component-builder'); ?></a>
        <span id="acb-save-status" class="acb-save-status" role="status" aria-live="polite"></span>
    </div>

    <div class="acb-builder-subbar">
        <button type="button" class="button button-primary" id="acb-how-to">
            <span class="dashicons dashicons-visibility"></span>
            <?php esc_html_e('How to display this', 'acf-component-builder'); ?>
        </button>
        <button type="button" class="button" id="acb-open-library">
            <span class="dashicons dashicons-screenoptions"></span>
            <?php esc_html_e('Block library', 'acf-component-builder'); ?>
        </button>
        <button type="button" class="button" id="acb-open-versions">
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e('Version history', 'acf-component-builder'); ?>
        </button>
        <span class="acb-toolbar-spacer"></span>
        <button type="button" class="button" id="acb-import-tpl">
            <span class="dashicons dashicons-upload"></span>
            <?php esc_html_e('Import', 'acf-component-builder'); ?>
        </button>
        <button type="button" class="button" id="acb-export-tpl">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Export', 'acf-component-builder'); ?>
        </button>
    </div>

    <div class="acb-builder-grid">
        <!-- Tree -->
        <section class="acb-builder-pane acb-pane-tree" aria-label="<?php esc_attr_e('Template structure', 'acf-component-builder'); ?>">
            <header class="acb-pane-head">
                <h2><?php esc_html_e('Structure', 'acf-component-builder'); ?></h2>
                <div class="acb-add-root">
                    <select id="acb-add-root-type" aria-label="<?php esc_attr_e('Node type to add', 'acf-component-builder'); ?>"></select>
                    <button type="button" class="button button-small" id="acb-add-root">
                        <?php esc_html_e('Add', 'acf-component-builder'); ?>
                    </button>
                </div>
            </header>
            <ul id="acb-tree" class="acb-tree"></ul>
        </section>

        <!-- Inspector -->
        <section class="acb-builder-pane acb-pane-inspector" aria-label="<?php esc_attr_e('Node settings', 'acf-component-builder'); ?>">
            <header class="acb-pane-head"><h2><?php esc_html_e('Settings', 'acf-component-builder'); ?></h2></header>
            <div id="acb-inspector" class="acb-inspector">
                <p class="acb-inspector-empty"><?php esc_html_e('Select a node to edit its settings.', 'acf-component-builder'); ?></p>
            </div>
        </section>

        <!-- Preview -->
        <section class="acb-builder-pane acb-pane-preview" aria-label="<?php esc_attr_e('Live preview', 'acf-component-builder'); ?>">
            <header class="acb-pane-head">
                <h2><?php esc_html_e('Live preview', 'acf-component-builder'); ?></h2>
                <div class="acb-preview-controls">
                    <select id="acb-preview-post" aria-label="<?php esc_attr_e('Preview content source', 'acf-component-builder'); ?>">
                        <option value=""><?php esc_html_e('Loading posts…', 'acf-component-builder'); ?></option>
                    </select>
                    <div class="acb-device-toggle" role="group" aria-label="<?php esc_attr_e('Preview width', 'acf-component-builder'); ?>">
                        <button type="button" class="button button-small is-active" data-w="100%" title="<?php esc_attr_e('Desktop', 'acf-component-builder'); ?>"><span class="dashicons dashicons-desktop"></span></button>
                        <button type="button" class="button button-small" data-w="768px" title="<?php esc_attr_e('Tablet', 'acf-component-builder'); ?>"><span class="dashicons dashicons-tablet"></span></button>
                        <button type="button" class="button button-small" data-w="375px" title="<?php esc_attr_e('Mobile', 'acf-component-builder'); ?>"><span class="dashicons dashicons-smartphone"></span></button>
                    </div>
                    <button type="button" class="button button-small" id="acb-preview-refresh">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </header>
            <div class="acb-preview-stage">
                <iframe id="acb-preview-frame" title="<?php esc_attr_e('Template preview', 'acf-component-builder'); ?>"></iframe>
            </div>
            <p id="acb-preview-note" class="acb-preview-note"></p>
        </section>
    </div>

    <!-- Modal host (library / versions / import-export) -->
    <div id="acb-modal" class="acb-modal" hidden>
        <div class="acb-modal-backdrop" data-acb-close></div>
        <div class="acb-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="acb-modal-title">
            <header class="acb-modal-head">
                <h2 id="acb-modal-title"></h2>
                <button type="button" class="acb-icon-btn" data-acb-close aria-label="<?php esc_attr_e('Close', 'acf-component-builder'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </header>
            <div class="acb-modal-body" id="acb-modal-body"></div>
        </div>
    </div>

    <?php endif; ?>
</div>
