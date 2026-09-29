<?php
/** @var array $components @var \ACB\Template_Resolver $templates @var \ACB\Template_Store $store */
if (!defined('ABSPATH')) { exit; }
$store = $store ?? null;
?>
<div class="wrap acb-wrap">
    <h1 class="acb-title"><?php esc_html_e('Components', 'acf-component-builder'); ?></h1>
    <p class="description">
        <?php esc_html_e('Components are auto-discovered from your ACF Flexible Content fields. Building a template controls how a component looks — to make it appear on a page, display it using one of the options in “How to display”.', 'acf-component-builder'); ?>
    </p>

    <?php if (empty($components)) : ?>
        <div class="acb-empty"><?php esc_html_e('Nothing here yet. Import an ACF JSON export first.', 'acf-component-builder'); ?></div>
    <?php else : ?>
    <table class="widefat striped acb-table">
        <thead><tr>
            <th><?php esc_html_e('Name', 'acf-component-builder'); ?></th>
            <th><?php esc_html_e('Slug', 'acf-component-builder'); ?></th>
            <th><?php esc_html_e('Type', 'acf-component-builder'); ?>
                <?php echo \ACB\Admin::help(__('flexible = a whole ACF Flexible Content field (renders every row). layout = one flexible layout. group = a field group.', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
            <th><?php esc_html_e('Maps To', 'acf-component-builder'); ?></th>
            <th><?php esc_html_e('Template', 'acf-component-builder'); ?>
                <?php echo \ACB\Admin::help(__('Optional. A visual template overrides the auto-generated markup. Without one, the component still renders automatically.', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
            <th><?php esc_html_e('How to display', 'acf-component-builder'); ?>
                <?php echo \ACB\Admin::help(__('A component only appears on a page when you display it. Click to see the block, shortcode, and PHP options.', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($components as $c) : ?>
            <?php
            $existing = $store ? $store->find_id_by_slug($c->slug) : null;
            $builder  = add_query_arg(
                $existing ? ['page' => 'acb-builder', 'template' => $existing] : ['page' => 'acb-builder', 'component' => $c->slug],
                admin_url('admin.php')
            );
            ?>
            <tr>
                <td><strong><?php echo esc_html($c->name); ?></strong></td>
                <td><code><?php echo esc_html($c->slug); ?></code></td>
                <td><span class="acb-badge acb-badge--<?php echo esc_attr($c->source_type); ?>"><?php echo esc_html($c->source_type); ?></span></td>
                <td><code><?php echo esc_html($c->field_name); ?></code></td>
                <td>
                    <a class="button button-small" href="<?php echo esc_url($builder); ?>">
                        <?php echo $existing
                            ? esc_html__('Edit template', 'acf-component-builder')
                            : esc_html__('Build template', 'acf-component-builder'); ?>
                    </a>
                </td>
                <td>
                    <button type="button" class="button button-small acb-usage-toggle" aria-expanded="false" data-target="acb-usage-<?php echo esc_attr($c->slug); ?>">
                        <?php esc_html_e('How to display', 'acf-component-builder'); ?>
                    </button>
                </td>
            </tr>
            <tr class="acb-usage-drawer" id="acb-usage-<?php echo esc_attr($c->slug); ?>" hidden>
                <td colspan="6"><?php echo \ACB\Admin::usage_card($c); // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_* internally ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    (function () {
        document.querySelectorAll('.acb-usage-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var row = document.getElementById(btn.dataset.target);
                if (!row) { return; }
                var open = !row.hidden;
                row.hidden = open;
                btn.setAttribute('aria-expanded', String(!open));
            });
        });
    })();
    </script>
    <?php endif; ?>
</div>
