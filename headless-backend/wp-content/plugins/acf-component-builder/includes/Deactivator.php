<?php

declare(strict_types=1);

namespace ACB;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Runs on plugin deactivation. Intentionally conservative: it does NOT delete
 * any component/template data (see uninstall.php for opt-in cleanup).
 *
 * @package ACFComponentBuilder
 */
final class Deactivator
{
    public static function deactivate(): void
    {
        delete_transient('acb_discovered_components');
        flush_rewrite_rules();
    }
}
