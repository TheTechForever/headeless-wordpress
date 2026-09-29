<?php
/**
 * CSS boilerplate injector. Outputs the exact container rules and the
 * media-query breakpoint stubs from Steps 7 & 8 of the process doc, plus
 * an optional fixed-header rule, so every project starts from the same
 * responsive baseline.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_CSS_Injector {

	const OPTION = 'dtp_css';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( ! DTP_License::is_active() ) {
			return;
		}
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 21 );
		add_action( 'wp_head', array( $this, 'output' ), 99 );
	}

	public function register() {
		register_setting( 'dtp_css_group', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	public function sanitize( $input ) {
		return array(
			'container'    => empty( $input['container'] ) ? 0 : 1,
			'fixed_header' => empty( $input['fixed_header'] ) ? 0 : 1,
			'header_sel'   => isset( $input['header_sel'] ) ? sanitize_text_field( $input['header_sel'] ) : 'header',
		);
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'CSS Boilerplate', 'dev-toolkit-pro' ),
			__( 'CSS Boilerplate', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-css',
			array( $this, 'page' )
		);
	}

	/**
	 * The container rules straight from the process doc (Step 8).
	 */
	private function container_css() {
		return <<<CSS
.container{max-width:1320px;margin:0 auto;}
@media all and (max-width:1366px){.container{max-width:100%;padding:0 60px;}}
@media all and (max-width:1024px){.container{max-width:100%;padding:0 15px;}}
CSS;
	}

	public function output() {
		$o = get_option( self::OPTION, array() );
		$css = '';

		if ( ! empty( $o['container'] ) ) {
			$css .= $this->container_css();
		}
		if ( ! empty( $o['fixed_header'] ) ) {
			$sel  = $o['header_sel'] ? $o['header_sel'] : 'header';
			$css .= sprintf( '%s{position:fixed;top:0;left:0;right:0;z-index:999;width:100%%;}', $sel );
		}

		if ( $css ) {
			printf( "\n<style id=\"dtp-boilerplate\">%s</style>\n", wp_strip_all_tags( $css ) );
		}
	}

	/**
	 * The full media-query stub set (Step 7) for copy/paste into the theme.
	 */
	public static function media_query_stubs() {
		return <<<CSS
/* Mobile */
@media all and (max-width:767px){}
/* Tablet */
@media (min-width:768px) and (max-width:1024px){}
/* Laptops / desktops */
@media (min-width:1025px) and (max-width:1280px){}
@media (min-width:1281px) and (max-width:1366px){}
@media (min-width:1367px) and (max-width:1440px){}
@media (min-width:1441px) and (max-width:1600px){}
CSS;
	}

	public function page() {
		$o = get_option( self::OPTION, array() );
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'CSS Boilerplate', 'dev-toolkit-pro' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dtp_css_group' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Inject container rules', 'dev-toolkit-pro' ); ?></th>
						<td><label><input type="checkbox" name="dtp_css[container]" value="1" <?php checked( ! empty( $o['container'] ) ); ?>> <?php esc_html_e( 'Output the .container breakpoints in <head>', 'dev-toolkit-pro' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Fixed header', 'dev-toolkit-pro' ); ?></th>
						<td><label><input type="checkbox" name="dtp_css[fixed_header]" value="1" <?php checked( ! empty( $o['fixed_header'] ) ); ?>> <?php esc_html_e( 'Make the header fixed', 'dev-toolkit-pro' ); ?></label></td></tr>
					<tr><th><?php esc_html_e( 'Header selector', 'dev-toolkit-pro' ); ?></th>
						<td><input type="text" name="dtp_css[header_sel]" value="<?php echo esc_attr( $o['header_sel'] ?? 'header' ); ?>" class="regular-text"></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>

			<h2><?php esc_html_e( 'Copy: media-query stubs (Step 7)', 'dev-toolkit-pro' ); ?></h2>
			<textarea class="large-text code" rows="8" readonly onclick="this.select()"><?php echo esc_textarea( self::media_query_stubs() ); ?></textarea>
		</div>
		<?php
	}
}
