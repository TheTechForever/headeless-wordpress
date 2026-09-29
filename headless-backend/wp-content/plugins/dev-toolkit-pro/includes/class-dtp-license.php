<?php
/**
 * Simple licence gate. In production you'd validate the key against your
 * store's API; here we provide a local key store + an activation screen so
 * the Pro features have a real on/off switch. Other Pro classes call
 * DTP_License::is_active() before running.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_License {

	const OPTION = 'dtp_license';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	public static function is_active() {
		$data = get_option( self::OPTION, array() );
		return ! empty( $data['status'] ) && 'active' === $data['status'];
	}

	public function register() {
		register_setting( 'dtp_license_group', self::OPTION, array( 'sanitize_callback' => array( $this, 'sanitize' ) ) );
	}

	/**
	 * Validate the key. Replace the check below with a remote API call to
	 * your licensing server. The format check keeps the demo self-contained.
	 */
	public function sanitize( $input ) {
		$key = isset( $input['key'] ) ? sanitize_text_field( $input['key'] ) : '';
		$out = array( 'key' => $key, 'status' => 'inactive' );

		// Accept keys shaped like DTP-XXXX-XXXX-XXXX (demo rule).
		if ( preg_match( '/^DTP-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $key ) ) {
			$out['status'] = 'active';
			add_settings_error( self::OPTION, 'dtp_ok', __( 'Licence activated. Pro features are now enabled.', 'dev-toolkit-pro' ), 'updated' );
		} elseif ( $key ) {
			add_settings_error( self::OPTION, 'dtp_bad', __( 'That licence key is not valid.', 'dev-toolkit-pro' ), 'error' );
		}
		return $out;
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Pro Licence', 'dev-toolkit-pro' ),
			__( 'Pro Licence', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-license',
			array( $this, 'page' )
		);
	}

	public function page() {
		$data = get_option( self::OPTION, array() );
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Dev Toolkit Pro — Licence', 'dev-toolkit-pro' ); ?></h1>
			<p>
				<?php if ( self::is_active() ) : ?>
					<span style="color:#1a9d5a;font-weight:600;">&#10003; <?php esc_html_e( 'Active', 'dev-toolkit-pro' ); ?></span>
				<?php else : ?>
					<span style="color:#c92c2c;font-weight:600;"><?php esc_html_e( 'Inactive — enter a key to enable Pro features.', 'dev-toolkit-pro' ); ?></span>
				<?php endif; ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'dtp_license_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><?php esc_html_e( 'Licence key', 'dev-toolkit-pro' ); ?></th>
						<td>
							<input type="text" name="dtp_license[key]" value="<?php echo esc_attr( $data['key'] ?? '' ); ?>" class="regular-text" placeholder="DTP-XXXX-XXXX-XXXX">
							<p class="description"><?php esc_html_e( 'Demo key format: DTP-ABCD-1234-EFGH', 'dev-toolkit-pro' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save & activate', 'dev-toolkit-pro' ) ); ?>
			</form>
		</div>
		<?php
	}
}
