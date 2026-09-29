<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Loads frontend CSS only when a component actually rendered on the page, and
 * admin assets only on ACB admin screens.
 *
 * @package ACFComponentBuilder
 */
final class Asset_Manager
{
    /** @var array<string,bool> */
    private static array $used = [];

    private static bool $printed = false;

    public static function init(): void
    {
        // Register (not enqueue) the frontend stylesheet.
        add_action('wp_enqueue_scripts', static function (): void {
            wp_register_style(
                'acb-frontend',
                ACB_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                ACB_VERSION
            );

            // Optionally load Bootstrap from CDN (off by default).
            $settings = (array) get_option('acb_settings', []);
            if (!empty($settings['load_bootstrap'])) {
                $ver = ((string) ($settings['bootstrap_version'] ?? '5')) === '4'
                    ? '4.6.2'
                    : '5.3.3';
                wp_register_style(
                    'acb-bootstrap',
                    'https://cdn.jsdelivr.net/npm/bootstrap@' . $ver . '/dist/css/bootstrap.min.css',
                    [],
                    $ver
                );
            }
        });

        // Enqueue in the footer only if a component was used during rendering.
        add_action('wp_footer', static function (): void {
            if (!empty(self::$used) && !self::$printed) {
                self::$printed = true;
                if (wp_style_is('acb-frontend', 'registered')) {
                    wp_enqueue_style('acb-frontend');
                    // Late enqueue in footer: print it explicitly.
                    wp_print_styles(['acb-frontend']);
                }
            }
        }, 1);
    }

    public static function mark_used(string $slug): void
    {
        self::$used[$slug] = true;

        // If styles haven't been printed yet and we're before wp_head close,
        // ensure enqueue happens.
        if (!wp_style_is('acb-frontend', 'enqueued') && !did_action('wp_footer')) {
            if (wp_style_is('acb-bootstrap', 'registered')) {
                wp_enqueue_style('acb-bootstrap');
            }
            if (wp_style_is('acb-frontend', 'registered')) {
                wp_enqueue_style('acb-frontend');
            }
        }
    }

    /**
     * Enqueue admin assets. Called from Admin only on ACB pages.
     */
    public static function admin(): void
    {
        wp_enqueue_style(
            'acb-admin',
            ACB_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ACB_VERSION
        );
        wp_enqueue_script(
            'acb-admin',
            ACB_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            ACB_VERSION,
            true
        );
        wp_localize_script('acb-admin', 'ACB_ADMIN', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('acb_admin'),
            'i18n'    => [
                'confirmDelete' => __('Are you sure? This cannot be undone.', 'acf-component-builder'),
                'copied'        => __('Copied!', 'acf-component-builder'),
            ],
        ]);
    }

    /**
     * Enqueue the visual builder bundle. Called from Admin only on the builder
     * screen. Depends on jquery + wp-util (wp.template not required, kept lean).
     */
    public static function builder(): void
    {
        wp_enqueue_style(
            'acb-builder',
            ACB_PLUGIN_URL . 'assets/css/builder.css',
            ['acb-admin'],
            ACB_VERSION
        );
        wp_enqueue_script(
            'acb-builder',
            ACB_PLUGIN_URL . 'assets/js/builder.js',
            ['jquery'],
            ACB_VERSION,
            true
        );
    }
}