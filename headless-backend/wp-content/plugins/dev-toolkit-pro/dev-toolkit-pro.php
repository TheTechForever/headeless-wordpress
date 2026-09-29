<?php
/**
 * Plugin Name:       Dev Toolkit Pro
 * Description:        Pro add-on for Dev Toolkit. Adds form validation, a CSS boilerplate injector (container + media queries + fixed header), a child-theme generator, bulk install of the standard plugin list, per-page H1 scanning and a PageSpeed lookup. Requires Dev Toolkit (Basic).
 * Version:           1.0.0
 * Requires PHP:      7.4
 * Author:            Your Agency
 * License:           GPL-2.0-or-later
 * Text Domain:       dev-toolkit-pro
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DTP_VERSION', '1.0.0' );
define( 'DTP_FILE', __FILE__ );
define( 'DTP_PATH', plugin_dir_path( __FILE__ ) );
define( 'DTP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Boot Pro only after Basic has loaded. If Basic is missing, show a notice
 * and stand down — Pro extends Basic, it does not duplicate it.
 */
function dtp_bootstrap() {
	if ( ! class_exists( 'Dev_Toolkit' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p><strong>Dev Toolkit Pro</strong> requires the free <strong>Dev Toolkit (Basic)</strong> plugin to be installed and active.</p></div>';
		} );
		return;
	}

	require_once DTP_PATH . 'includes/class-dtp-license.php';
	require_once DTP_PATH . 'includes/class-dtp-form-validation.php';
	require_once DTP_PATH . 'includes/class-dtp-css-injector.php';
	require_once DTP_PATH . 'includes/class-dtp-child-theme.php';
	require_once DTP_PATH . 'includes/class-dtp-plugin-installer.php';
	require_once DTP_PATH . 'includes/class-dtp-page-audit.php';
	require_once DTP_PATH . 'includes/class-dtp-ai.php';
	require_once DTP_PATH . 'includes/class-dtp-design-qa.php';
	require_once DTP_PATH . 'includes/class-dtp-help.php';

	DTP_License::instance();

	// Feature classes gate themselves behind an active licence.
	DTP_Form_Validation::instance();
	DTP_CSS_Injector::instance();
	DTP_Child_Theme::instance();
	DTP_Plugin_Installer::instance();
	DTP_Page_Audit::instance();
	DTP_AI::instance();
	DTP_Design_QA::instance();
	DTP_Help::instance();
}
// 'dev_toolkit_loaded' fires from Basic after it finishes booting.
add_action( 'dev_toolkit_loaded', 'dtp_bootstrap' );

// Fallback if Basic loads on the same priority: also try on plugins_loaded.
add_action( 'plugins_loaded', function () {
	if ( class_exists( 'Dev_Toolkit' ) && ! class_exists( 'DTP_License' ) ) {
		dtp_bootstrap();
	}
}, 20 );
