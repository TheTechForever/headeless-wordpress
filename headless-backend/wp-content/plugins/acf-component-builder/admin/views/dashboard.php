<?php
/** @var int $component_count @var int $flexible_count @var int $group_count @var array $components @var bool $acf_ok */
if (!defined('ABSPATH')) { exit; }
?>
<div class="wrap acb-wrap">
    <h1 class="acb-title"><span class="dashicons dashicons-layout"></span> <?php esc_html_e('ACF Component Builder', 'acf-component-builder'); ?></h1>

    <div class="acb-panel acb-howitworks">
        <h2><?php esc_html_e('How it works', 'acf-component-builder'); ?></h2>
        <p><?php esc_html_e('ACF stores your content but does not display it on its own. This plugin is the display step. Three stages:', 'acf-component-builder'); ?></p>
        <ol class="acb-steps">
            <li><strong><?php esc_html_e('1. Import / detect fields', 'acf-component-builder'); ?></strong><br><?php esc_html_e('Your ACF Flexible Content fields become components automatically.', 'acf-component-builder'); ?></li>
            <li><strong><?php esc_html_e('2. (Optional) Build a template', 'acf-component-builder'); ?></strong><br><?php esc_html_e('Customise how a component looks. Skipping this still renders sensible default markup.', 'acf-component-builder'); ?></li>
            <li><strong><?php esc_html_e('3. Display it on a page', 'acf-component-builder'); ?></strong><br><?php esc_html_e('Add the “ACF Component” block to any page, or use the shortcode / PHP snippet. Nothing appears until you do this.', 'acf-component-builder'); ?></li>
        </ol>
        <p class="acb-hint"><?php esc_html_e('Tip: on the Components screen, click “How to display” next to any component for its exact block, shortcode, and PHP.', 'acf-component-builder'); ?></p>
    </div>

    <div class="acb-cards">
        <div class="acb-card"><div class="acb-card__num"><?php echo (int) $component_count; ?></div><div class="acb-card__label"><?php esc_html_e('Components', 'acf-component-builder'); ?></div></div>
        <div class="acb-card"><div class="acb-card__num"><?php echo (int) $flexible_count; ?></div><div class="acb-card__label"><?php esc_html_e('Flexible Fields', 'acf-component-builder'); ?></div></div>
        <div class="acb-card"><div class="acb-card__num"><?php echo (int) $group_count; ?></div><div class="acb-card__label"><?php esc_html_e('Field Groups', 'acf-component-builder'); ?></div></div>
    </div>

    <div class="acb-panel">
        <h2><?php esc_html_e('Quick Actions', 'acf-component-builder'); ?></h2>
        <p>
            <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=acb-import')); ?>"><?php esc_html_e('Import ACF JSON', 'acf-component-builder'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=acb-components')); ?>"><?php esc_html_e('View Components', 'acf-component-builder'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=acb-templates')); ?>"><?php esc_html_e('Templates', 'acf-component-builder'); ?></a>
            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=acb-settings')); ?>"><?php esc_html_e('Settings', 'acf-component-builder'); ?></a>
        </p>
    </div>

    <div class="acb-panel">
        <h2><?php esc_html_e('System Status', 'acf-component-builder'); ?></h2>
        <table class="acb-status">
            <tr><td><?php esc_html_e('ACF Pro', 'acf-component-builder'); ?></td><td><?php echo $acf_ok ? '<span class="acb-ok">✓ '.esc_html__('Active','acf-component-builder').'</span>' : '<span class="acb-bad">✕ '.esc_html__('Missing','acf-component-builder').'</span>'; ?></td></tr>
            <tr><td><?php esc_html_e('WordPress', 'acf-component-builder'); ?></td><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr>
            <tr><td><?php esc_html_e('PHP', 'acf-component-builder'); ?></td><td><?php echo esc_html(PHP_VERSION); ?></td></tr>
        </table>
    </div>

    <div class="acb-panel">
        <h2><?php esc_html_e('Recent Components', 'acf-component-builder'); ?></h2>
        <?php if (empty($components)) : ?>
            <div class="acb-empty"><?php esc_html_e('No components discovered yet. Import an ACF JSON export to get started.', 'acf-component-builder'); ?></div>
        <?php else : ?>
            <ul class="acb-list">
                <?php foreach (array_slice($components, 0, 8) as $c) : ?>
                    <li><span class="acb-badge acb-badge--<?php echo esc_attr($c->source_type); ?>"><?php echo esc_html($c->source_type); ?></span> <strong><?php echo esc_html($c->name); ?></strong> <code><?php echo esc_html($c->slug); ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
