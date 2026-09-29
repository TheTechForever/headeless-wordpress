<?php
/**
 * Plugin Name:       Dev Toolkit (Basic)
 * Plugin URI:        https://example.com/dev-toolkit
 * Description:        Automates the standard WordPress site build checklist: one-click setup (comments, media, timezone), common info fields + shortcodes, mailto/tel & external-link handling, aria-labels, dynamic year, content exports, and a build audit. Pro unlocks form validation, CSS boilerplate injector, child-theme generator and bulk plugin install.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Your Agency
 * License:           GPL-2.0-or-later
 * Text Domain:       dev-toolkit
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'DT_VERSION', '1.0.0' );
define( 'DT_FILE', __FILE__ );
define( 'DT_PATH', plugin_dir_path( __FILE__ ) );
define( 'DT_URL', plugin_dir_url( __FILE__ ) );
define( 'DT_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Simple, predictable loader. Each feature lives in its own class and is
 * booted here. Keeping this explicit (instead of a magic autoloader) makes
 * it obvious to any developer what the plugin actually runs.
 */
final class Dev_Toolkit {

	/**
	 * @var Dev_Toolkit
	 */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->includes();
		$this->boot();
	}

	private function includes() {
		require_once DT_PATH . 'includes/class-dt-settings.php';
		require_once DT_PATH . 'includes/class-dt-site-setup.php';
		require_once DT_PATH . 'includes/class-dt-common-fields.php';
		require_once DT_PATH . 'includes/class-dt-shortcodes.php';
		require_once DT_PATH . 'includes/class-dt-content-filters.php';
		require_once DT_PATH . 'includes/class-dt-exporter.php';
		require_once DT_PATH . 'includes/class-dt-audit.php';
		require_once DT_PATH . 'includes/class-dt-admin.php';
	}

	private function boot() {
		DT_Settings::instance();
		DT_Site_Setup::instance();
		DT_Common_Fields::instance();
		DT_Shortcodes::instance();
		DT_Content_Filters::instance();
		DT_Exporter::instance();
		DT_Audit::instance();
		DT_Admin::instance();

		/**
		 * Fires after the Basic plugin has loaded all of its features.
		 * The Pro add-on hooks in here to extend the toolkit.
		 */
		do_action( 'dev_toolkit_loaded' );
	}
}

/**
 * On activation, stamp a default option set so the settings screens have
 * sane values and nothing runs against an empty option.
 */
function dev_toolkit_activate() {
	if ( false === get_option( 'dt_settings' ) ) {
		add_option( 'dt_settings', DT_Settings::defaults() );
	}
	if ( false === get_option( 'dt_common_fields' ) ) {
		add_option( 'dt_common_fields', array() );
	}
}
register_activation_hook( __FILE__, 'dev_toolkit_activate' );

// Settings class is needed by the activation hook, so require it early too.
require_once DT_PATH . 'includes/class-dt-settings.php';

add_action( 'plugins_loaded', array( 'Dev_Toolkit', 'instance' ) );
