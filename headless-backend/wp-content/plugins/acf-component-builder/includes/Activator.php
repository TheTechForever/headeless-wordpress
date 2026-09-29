<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Runs on plugin activation.
 *
 * @package ACFComponentBuilder
 */
final class Activator
{
    public static function activate(): void
    {
        // Register CPTs then flush so their (private) rewrite state is clean.
        Post_Types::register();
        flush_rewrite_rules();

        // Seed default options.
        if (get_option('acb_settings') === false) {
            add_option('acb_settings', [
                'bootstrap_version' => '5',
                'default_engine'    => 'bootstrap',
                'load_bootstrap'    => false,
            ]);
        }

        add_option('acb_version', ACB_VERSION);
    }
}
