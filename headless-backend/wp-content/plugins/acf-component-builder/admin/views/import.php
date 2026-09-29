<?php
/** @var mixed $report */
if (!defined('ABSPATH')) { exit; }
?>
<div class="wrap acb-wrap">
    <h1 class="acb-title"><?php esc_html_e('Import ACF JSON', 'acf-component-builder'); ?></h1>

    <?php if (is_array($report) && !empty($report['error'])) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($report['error']); ?></p></div>
    <?php endif; ?>

    <?php if (is_array($report) && !empty($report['result'])) :
        $r = $report['result']; ?>
        <div class="notice notice-success">
            <p><?php printf(
                esc_html__('Imported %1$d field group(s), skipped %2$d.', 'acf-component-builder'),
                (int) $r['imported'], (int) $r['skipped']
            ); ?></p>
        </div>
        <?php if (!empty($r['errors'])) : ?>
            <div class="notice notice-warning"><ul>
                <?php foreach ($r['errors'] as $e) : ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="acb-panel">
        <h2><?php esc_html_e('Upload an export file', 'acf-component-builder'); ?></h2>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('acb_import_json'); ?>
            <input type="hidden" name="action" value="acb_import_json" />
            <p><input type="file" name="acb_json" accept="application/json,.json" required /></p>
            <p><label><input type="checkbox" name="acb_overwrite" value="1" /> <?php esc_html_e('Overwrite existing field groups with the same key', 'acf-component-builder'); ?></label></p>
            <p><button type="submit" class="button button-primary"><?php esc_html_e('Analyze & Import', 'acf-component-builder'); ?></button></p>
        </form>
    </div>

    <?php if (is_array($report) && !empty($report['analysis'])) : ?>
        <div class="acb-panel">
            <h2><?php esc_html_e('Detected Components', 'acf-component-builder'); ?></h2>
            <?php foreach ($report['analysis'] as $group) :
                if (empty($group['flexible'])) { continue; } ?>
                <h3><?php echo esc_html($group['title']); ?> <?php echo $group['existing'] ? '<span class="acb-badge">'.esc_html__('exists','acf-component-builder').'</span>' : ''; ?></h3>
                <?php foreach ($group['flexible'] as $flex) : ?>
                    <div class="acb-flex-detect">
                        <p><strong><?php echo esc_html($flex['label']); ?></strong> <code><?php echo esc_html($flex['name']); ?></code></p>
                        <ul class="acb-list">
                            <?php foreach ($flex['layouts'] as $l) : ?>
                                <li>✓ <?php echo esc_html($l['label']); ?> <code><?php echo esc_html($l['slug']); ?></code>
                                    <?php echo $l['has_repeater'] ? '<span class="acb-badge acb-badge--repeater">'.esc_html__('repeater','acf-component-builder').'</span>' : ''; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="description"><?php printf(esc_html__('Render with: %s', 'acf-component-builder'), '<code>echo ACB::render_flexible(\''.esc_html($flex['name']).'\');</code>'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
