<?php
/** @var array $settings */
if (!defined('ABSPATH')) { exit; }
$bs = $settings['bootstrap_version'] ?? '5';
$engine = $settings['default_engine'] ?? 'bootstrap';
$load = !empty($settings['load_bootstrap']);
?>
<div class="wrap acb-wrap">
    <h1 class="acb-title"><?php esc_html_e('Settings', 'acf-component-builder'); ?></h1>
    <?php if (!empty($_GET['saved'])) : ?><div class="notice notice-success"><p><?php esc_html_e('Settings saved.', 'acf-component-builder'); ?></p></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="acb-panel">
        <?php wp_nonce_field('acb_save_settings'); ?>
        <input type="hidden" name="action" value="acb_save_settings" />
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Default layout engine', 'acf-component-builder'); ?></th>
                <td>
                    <select name="default_engine">
                        <option value="bootstrap" <?php selected($engine, 'bootstrap'); ?>>Bootstrap</option>
                        <option value="flex" <?php selected($engine, 'flex'); ?>>Flexbox</option>
                        <option value="grid" <?php selected($engine, 'grid'); ?>>CSS Grid</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Bootstrap version', 'acf-component-builder'); ?></th>
                <td>
                    <select name="bootstrap_version">
                        <option value="5" <?php selected($bs, '5'); ?>>Bootstrap 5</option>
                        <option value="4" <?php selected($bs, '4'); ?>>Bootstrap 4</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Load Bootstrap CSS', 'acf-component-builder'); ?></th>
                <td><label><input type="checkbox" name="load_bootstrap" value="1" <?php checked($load); ?> /> <?php esc_html_e('Enqueue Bootstrap from CDN on the front end (leave off if your theme already includes it).', 'acf-component-builder'); ?></label></td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php esc_html_e('Save Settings', 'acf-component-builder'); ?></button></p>
    </form>

    <div class="acb-panel">
        <h2><?php esc_html_e('Export', 'acf-component-builder'); ?></h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('acb_export'); ?>
            <input type="hidden" name="action" value="acb_export" />
            <button type="submit" class="button"><?php esc_html_e('Export all ACF field groups (JSON)', 'acf-component-builder'); ?></button>
        </form>
    </div>
</div>
