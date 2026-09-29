<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin UI controller: registers the menu, enqueues admin assets on our pages
 * only, and processes import/export form submissions (nonce + capability
 * protected).
 *
 * @package ACFComponentBuilder
 */
final class Admin
{
    private const CAP  = 'manage_options';
    private const SLUG = 'acb-dashboard';

    public function __construct(
        private Component_Registry $registry,
        private Template_Resolver $templates,
        private ACF_Manager $acf,
        private Template_Store $store
    ) {
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('admin_post_acb_import_json', [$this, 'handle_import']);
        add_action('admin_post_acb_export', [$this, 'handle_export']);
        add_action('admin_post_acb_save_settings', [$this, 'handle_settings']);
    }

    public function menu(): void
    {
        add_menu_page(
            __('ACF Component Builder', 'acf-component-builder'),
            __('ACF Components', 'acf-component-builder'),
            self::CAP,
            self::SLUG,
            [$this, 'page_dashboard'],
            'dashicons-layout',
            58
        );
        $sub = [
            ['acb-dashboard', __('Dashboard', 'acf-component-builder'), 'page_dashboard'],
            ['acb-components', __('Components', 'acf-component-builder'), 'page_components'],
            ['acb-templates', __('Templates', 'acf-component-builder'), 'page_templates'],
            ['acb-import', __('Import ACF JSON', 'acf-component-builder'), 'page_import'],
            ['acb-settings', __('Settings', 'acf-component-builder'), 'page_settings'],
        ];
        foreach ($sub as [$slug, $label, $cb]) {
            add_submenu_page(self::SLUG, $label, $label, self::CAP, $slug, [$this, $cb]);
        }

        // Builder screen — reachable from the manager/components, hidden from menu.
        add_submenu_page(
            '',
            __('Template Builder', 'acf-component-builder'),
            __('Template Builder', 'acf-component-builder'),
            self::CAP,
            'acb-builder',
            [$this, 'page_builder']
        );
    }

    public function assets(string $hook): void
    {
        if (!str_contains($hook, 'acb-')) {
            return;
        }
        Asset_Manager::admin();

        // The visual builder needs its own bundle + AJAX context.
        if (str_contains($hook, 'acb-builder')) {
            Asset_Manager::builder();
            $component = $this->current_builder_component();
            wp_localize_script('acb-builder', 'ACB_BUILDER', [
                'ajaxUrl'   => admin_url('admin-ajax.php'),
                'nonce'     => wp_create_nonce('acb_builder'),
                'managerUrl'=> admin_url('admin.php?page=acb-templates'),
                'engines'   => Node_Sanitizer::ENGINES,
                'nodeTypes' => Node_Sanitizer::NODE_TYPES,
                'renderers' => Node_Sanitizer::RENDERERS,
                'headTags'  => Node_Sanitizer::HEAD_TAGS,
                'htmlTags'  => Node_Sanitizer::HTML_TAGS,
                'frontendCss' => ACB_PLUGIN_URL . 'assets/css/frontend.css?ver=' . ACB_VERSION,
                'bootstrapCss' => self::bootstrap_cdn_url(),
                'usage'      => $component ? self::usage_snippets($component) : null,
                'i18n'      => [
                    'saved'      => __('Template saved.', 'acf-component-builder'),
                    'saveError'  => __('Could not save.', 'acf-component-builder'),
                    'confirmDel' => __('Delete this node and its children?', 'acf-component-builder'),
                    'previewing' => __('Rendering preview…', 'acf-component-builder'),
                    'noData'     => __('No matching ACF data on the selected post — showing structure only.', 'acf-component-builder'),
                    'blockName'  => __('Block name:', 'acf-component-builder'),
                    'blockSaved' => __('Saved to library.', 'acf-component-builder'),
                    'noBlocks'   => __('No saved blocks yet. Select a node and choose “Save as block”.', 'acf-component-builder'),
                    'noVersions' => __('No previous versions yet. They appear after you save changes.', 'acf-component-builder'),
                    'restored'   => __('Version restored.', 'acf-component-builder'),
                    'imported'   => __('Template imported. Reloading…', 'acf-component-builder'),
                    'importErr'  => __('Import failed — check the JSON.', 'acf-component-builder'),
                    'saveFirst'  => __('Save the template before exporting.', 'acf-component-builder'),
                ],
            ]);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Pages                                                              */
    /* ------------------------------------------------------------------ */

    public function page_dashboard(): void
    {
        $this->guard();
        $components = $this->registry->all();
        $flexible   = $this->registry->flexible_components();
        $groups     = $this->acf->get_field_groups();
        $this->view('dashboard', [
            'component_count' => count($components),
            'flexible_count'  => count($flexible),
            'group_count'     => count($groups),
            'components'      => $components,
            'acf_ok'          => $this->acf->is_pro(),
        ]);
    }

    public function page_components(): void
    {
        $this->guard();
        $this->view('components', [
            'components' => $this->registry->all(),
            'templates'  => $this->templates,
            'store'      => $this->store,
        ]);
    }

    public function page_templates(): void
    {
        $this->guard();
        $this->view('templates-manager', [
            'rows'       => $this->store->all(),
            'components' => $this->registry->all(),
        ]);
    }

    public function page_builder(): void
    {
        $this->guard();

        $id   = isset($_GET['template']) ? (int) $_GET['template'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $slug = isset($_GET['component']) ? Component_Registry::slugify(sanitize_text_field(wp_unslash($_GET['component']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($id > 0 && $slug === '') {
            $record = $this->store->get($id);
            $slug   = $record['slug'] ?? '';
        }

        $this->view('builder', [
            'template_id' => $id,
            'slug'        => $slug,
            'component'   => $this->current_builder_component(),
            'components'  => $this->registry->all(),
        ]);
    }

    public function page_import(): void
    {
        $this->guard();
        $report = get_transient('acb_import_report');
        delete_transient('acb_import_report');
        $this->view('import', ['report' => $report]);
    }

    public function page_settings(): void
    {
        $this->guard();
        $this->view('settings', ['settings' => (array) get_option('acb_settings', [])]);
    }

    /* ------------------------------------------------------------------ */
    /* Handlers                                                           */
    /* ------------------------------------------------------------------ */

    public function handle_import(): void
    {
        $this->guard();
        check_admin_referer('acb_import_json');

        if (empty($_FILES['acb_json']['tmp_name']) || !is_uploaded_file($_FILES['acb_json']['tmp_name'])) {
            $this->redirect_import(new \WP_Error('acb_no_file', __('No file uploaded.', 'acf-component-builder')));
        }

        // Read the file safely.
        $tmp  = $_FILES['acb_json']['tmp_name'];
        $size = (int) ($_FILES['acb_json']['size'] ?? 0);
        if ($size <= 0 || $size > 5 * MB_IN_BYTES) {
            $this->redirect_import(new \WP_Error('acb_bad_size', __('File is empty or too large (max 5MB).', 'acf-component-builder')));
        }
        $json = file_get_contents($tmp);
        if ($json === false) {
            $this->redirect_import(new \WP_Error('acb_read_fail', __('Could not read the uploaded file.', 'acf-component-builder')));
        }

        $data = Importer::parse($json);
        if (is_wp_error($data)) {
            $this->redirect_import($data);
        }

        $overwrite = !empty($_POST['acb_overwrite']);
        $result    = Importer::import($data, $overwrite);
        $analysis  = Importer::analyze($data);

        set_transient('acb_import_report', [
            'result'   => $result,
            'analysis' => $analysis,
        ], 60);

        wp_safe_redirect(add_query_arg(['page' => 'acb-import', 'imported' => 1], admin_url('admin.php')));
        exit;
    }

    public function handle_export(): void
    {
        $this->guard();
        check_admin_referer('acb_export');

        $groups = $this->acf->get_field_groups();
        $export = [];
        foreach ($groups as $g) {
            if (function_exists('acf_prepare_field_group_for_export')) {
                $export[] = acf_prepare_field_group_for_export($g);
            } else {
                $export[] = $g;
            }
        }
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=acb-export-' . gmdate('Y-m-d') . '.json');
        echo wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public function handle_settings(): void
    {
        $this->guard();
        check_admin_referer('acb_save_settings');

        $settings = [
            'bootstrap_version' => in_array(($_POST['bootstrap_version'] ?? '5'), ['4', '5'], true)
                ? sanitize_text_field(wp_unslash($_POST['bootstrap_version']))
                : '5',
            'default_engine'    => in_array(($_POST['default_engine'] ?? 'bootstrap'), ['bootstrap', 'flex', 'grid'], true)
                ? sanitize_text_field(wp_unslash($_POST['default_engine']))
                : 'bootstrap',
            'load_bootstrap'    => !empty($_POST['load_bootstrap']),
        ];
        update_option('acb_settings', $settings);
        do_action('acb_flush_cache');

        wp_safe_redirect(add_query_arg(['page' => 'acb-settings', 'saved' => 1], admin_url('admin.php')));
        exit;
    }

    /* ------------------------------------------------------------------ */
    /* Utilities                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Resolve the component the builder is currently editing, from the request.
     * Used both by {@see page_builder()} and asset localisation.
     */
    private function current_builder_component(): ?Component
    {
        $id   = isset($_GET['template']) ? (int) $_GET['template'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $slug = isset($_GET['component']) ? Component_Registry::slugify(sanitize_text_field(wp_unslash($_GET['component']))) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($id > 0 && $slug === '') {
            $record = $this->store->get($id);
            $slug   = $record['slug'] ?? '';
        }
        return $slug !== '' ? $this->registry->get($slug) : null;
    }

    private function guard(): void
    {
        if (!current_user_can(self::CAP)) {
            wp_die(esc_html__('You do not have permission to do this.', 'acf-component-builder'));
        }
    }

    /**
     * Render an accessible help icon with a tooltip. The JS in admin.js shows
     * the tip on hover / focus, and pins it on click (touch-friendly).
     */
    public static function help(string $text): string
    {
        return '<button type="button" class="acb-help" tabindex="0" aria-label="'
            . esc_attr__('Help', 'acf-component-builder') . '" data-acb-tip="'
            . esc_attr($text) . '"><span class="dashicons dashicons-editor-help"></span></button>';
    }

    /**
     * Build the "how to display" usage snippets for a component: the block
     * name, the shortcode, and the PHP one-liner — each copy-able.
     */
    public static function usage_snippets(Component $component): array
    {
        if ($component->is_flexible()) {
            $shortcode = '[acf_component flexible="' . $component->field_name . '"]';
            $php       = "echo ACB::render_flexible('" . $component->field_name . "');";
        } else {
            $shortcode = '[acf_component name="' . $component->slug . '"]';
            $php       = "echo ACB::render('" . $component->slug . "');";
        }
        return [
            'block'     => __('“ACF Component” block', 'acf-component-builder'),
            'shortcode' => $shortcode,
            'php'       => $php,
        ];
    }

    /**
     * Render the reusable "How to display this" card for a component.
     */
    public static function usage_card(Component $component): string
    {
        $u = self::usage_snippets($component);

        ob_start();
        ?>
        <div class="acb-usage">
            <div class="acb-usage-row">
                <h4><?php esc_html_e('Option 1 — Block (no code)', 'acf-component-builder'); ?>
                    <?php echo self::help(__('In the page/post editor, add the “ACF Component” block and pick this component from the sidebar. Easiest option.', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></h4>
                <p><?php esc_html_e('Edit any page → add the block below → choose this component in the sidebar.', 'acf-component-builder'); ?></p>
                <div class="acb-usage-code"><code><?php echo esc_html($u['block']); ?></code></div>
            </div>
            <div class="acb-usage-row">
                <h4><?php esc_html_e('Option 2 — Shortcode', 'acf-component-builder'); ?>
                    <?php echo self::help(__('Paste this into a Shortcode block or anywhere shortcodes run. Uses the current page’s ACF data unless you add post_id.', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></h4>
                <p><?php esc_html_e('Add a Shortcode block to your page and paste:', 'acf-component-builder'); ?></p>
                <div class="acb-usage-code">
                    <code><?php echo esc_html($u['shortcode']); ?></code>
                    <button type="button" class="acb-copy" data-copy="<?php echo esc_attr($u['shortcode']); ?>"><?php esc_html_e('Copy', 'acf-component-builder'); ?></button>
                </div>
            </div>
            <div class="acb-usage-row">
                <h4><?php esc_html_e('Option 3 — PHP (theme templates)', 'acf-component-builder'); ?>
                    <?php echo self::help(__('For developers: call this inside The Loop in your theme (page.php, single.php, a template part, etc.).', 'acf-component-builder')); // phpcs:ignore WordPress.Security.EscapeOutput ?></h4>
                <p><?php esc_html_e('Place inside The Loop in your theme:', 'acf-component-builder'); ?></p>
                <div class="acb-usage-code">
                    <code><?php echo esc_html($u['php']); ?></code>
                    <button type="button" class="acb-copy" data-copy="<?php echo esc_attr($u['php']); ?>"><?php esc_html_e('Copy', 'acf-component-builder'); ?></button>
                </div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Bootstrap CDN URL honouring the configured version, or '' when the
     * "Load Bootstrap" setting is off (preview still works, just unstyled by BS).
     */
    private static function bootstrap_cdn_url(): string
    {
        $settings = (array) get_option('acb_settings', []);
        $ver      = ((string) ($settings['bootstrap_version'] ?? '5')) === '4' ? '4.6.2' : '5.3.3';
        return 'https://cdn.jsdelivr.net/npm/bootstrap@' . $ver . '/dist/css/bootstrap.min.css';
    }

    private function redirect_import(\WP_Error $error): void
    {
        set_transient('acb_import_report', ['error' => $error->get_error_message()], 60);
        wp_safe_redirect(add_query_arg(['page' => 'acb-import'], admin_url('admin.php')));
        exit;
    }

    /**
     * @param array<string,mixed> $vars
     */
    private function view(string $name, array $vars = []): void
    {
        $file = ACB_PLUGIN_DIR . 'admin/views/' . sanitize_file_name($name) . '.php';
        if (!is_readable($file)) {
            echo '<div class="wrap"><p>' . esc_html__('View not found.', 'acf-component-builder') . '</p></div>';
            return;
        }
        extract($vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract
        include $file;
    }
}
