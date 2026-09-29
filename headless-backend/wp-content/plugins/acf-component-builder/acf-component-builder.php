<?php
/**
 * Plugin Name:       ACF Component Builder
 * Plugin URI:        https://example.com/acf-component-builder
 * Description:       A reusable component / template / layout / rendering layer built on top of ACF Pro. Turns ACF Flexible Content layouts into reusable, theme-overridable, Bootstrap / Flex / Grid rendered sections.
 * Version:           1.3.0
 * Requires at least: 6.0
 * Requires PHP:      8.1
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       acf-component-builder
 * Domain Path:       /languages
 *
 * @package ACFComponentBuilder
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit; // No direct access.
}

define('ACB_VERSION', '1.3.0');
define('ACB_PLUGIN_FILE', __FILE__);
define('ACB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * PSR-4-ish autoloader for the ACB\ namespace living in /includes.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'ACB\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', '/', $relative);
    $file     = ACB_PLUGIN_DIR . 'includes/' . $relative . '.php';
    if (is_readable($file)) {
        require_once $file;
    }
});

// Procedural helper API (acf_component(), ACB facade, etc.).
require_once ACB_PLUGIN_DIR . 'includes/helpers.php';

register_activation_hook(__FILE__, ['ACB\\Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ACB\\Deactivator', 'deactivate']);

/**
 * Boot the plugin once all plugins are loaded (so ACF detection is reliable).
 */
add_action('plugins_loaded', static function (): void {
    \ACB\Plugin::instance()->boot();
}, 20);
