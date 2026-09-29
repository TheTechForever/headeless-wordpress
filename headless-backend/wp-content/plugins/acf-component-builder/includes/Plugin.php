<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin orchestrator. Wires up all subsystems.
 *
 * @package ACFComponentBuilder
 */
final class Plugin
{
    private static ?Plugin $instance = null;

    private bool $booted = false;

    private ACF_Manager $acf;
    private Component_Registry $registry;
    private Template_Resolver $templates;
    private Renderer $renderer;

    private function __construct()
    {
    }

    public static function instance(): Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Boot the plugin. Safe to call once.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain(
            'acf-component-builder',
            false,
            dirname(ACB_PLUGIN_BASENAME) . '/languages'
        );

        $this->acf = new ACF_Manager();

        // Register the internal CPTs regardless of ACF so admin does not fatal.
        add_action('init', [Post_Types::class, 'register']);

        if (!$this->acf->is_available()) {
            // Show a friendly notice, register nothing that depends on ACF, bail.
            add_action('admin_notices', [$this->acf, 'render_missing_notice']);
            // Still load admin menu so the user sees guidance.
            $this->boot_admin_lite();
            return;
        }

        $this->registry  = new Component_Registry($this->acf);
        $this->templates = new Template_Resolver();
        $this->renderer  = new Renderer($this->registry, $this->templates, $this->acf);
        $store           = new Template_Store();
        $blocks          = new Block_Store();

        // Expose singletons to the helper API / ACB facade.
        Container::set('acf', $this->acf);
        Container::set('registry', $this->registry);
        Container::set('templates', $this->templates);
        Container::set('renderer', $this->renderer);
        Container::set('store', $store);
        Container::set('blocks', $blocks);

        // Discover components from ACF field groups (cached).
        add_action('init', [$this->registry, 'discover'], 15);

        // Frontend assets only load on demand (see Renderer / Asset_Manager).
        Asset_Manager::init();

        // Shortcode.
        add_shortcode('acf_component', [Shortcode::class, 'render']);

        // Gutenberg block — registers on front end, REST, and editor.
        (new Block_Editor($this->registry, $this->renderer))->init();

        // Admin + AJAX (Phase 2 builder).
        if (is_admin()) {
            (new Admin($this->registry, $this->templates, $this->acf, $store))->init();
            (new Ajax($this->registry, $this->renderer, $store, $blocks))->init();
        }

        /**
         * Fires after ACB has fully booted. Good place to register custom
         * components, renderers, or layout engines.
         *
         * @param Plugin $plugin The plugin instance.
         */
        do_action('acb_booted', $this);
    }

    /**
     * Minimal admin boot when ACF is missing (menu + notice only).
     */
    private function boot_admin_lite(): void
    {
        if (!is_admin()) {
            return;
        }
        add_action('admin_menu', static function (): void {
            add_menu_page(
                __('ACF Component Builder', 'acf-component-builder'),
                __('ACF Components', 'acf-component-builder'),
                'manage_options',
                'acb-dashboard',
                static function (): void {
                    echo '<div class="wrap"><h1>' . esc_html__('ACF Component Builder', 'acf-component-builder') . '</h1>';
                    echo '<div class="notice notice-error"><p>' .
                        esc_html__('ACF Component Builder requires Advanced Custom Fields PRO. Please install and activate ACF Pro.', 'acf-component-builder') .
                        '</p></div></div>';
                },
                'dashicons-layout',
                58
            );
        });
    }

    public function registry(): Component_Registry
    {
        return $this->registry;
    }

    public function renderer(): Renderer
    {
        return $this->renderer;
    }

    public function templates(): Template_Resolver
    {
        return $this->templates;
    }

    public function acf(): ACF_Manager
    {
        return $this->acf;
    }
}
