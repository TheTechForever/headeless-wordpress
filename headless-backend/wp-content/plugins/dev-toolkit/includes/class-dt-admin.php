<?php
/**
 * Admin UI: menu, dashboard (setup + audit), common fields screen, and
 * export screen. Everything is rendered here to keep the feature classes
 * focused on logic.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Admin {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Dev Toolkit', 'dev-toolkit' ),
			__( 'Dev Toolkit', 'dev-toolkit' ),
			'manage_options',
			'dev-toolkit',
			array( $this, 'page_dashboard' ),
			'dashicons-hammer',
			58
		);
		add_submenu_page( 'dev-toolkit', __( 'Setup & Audit', 'dev-toolkit' ), __( 'Setup & Audit', 'dev-toolkit' ), 'manage_options', 'dev-toolkit', array( $this, 'page_dashboard' ) );
		add_submenu_page( 'dev-toolkit', __( 'Common Fields', 'dev-toolkit' ), __( 'Common Fields', 'dev-toolkit' ), 'manage_options', 'dev-toolkit-fields', array( $this, 'page_fields' ) );
		add_submenu_page( 'dev-toolkit', __( 'Export', 'dev-toolkit' ), __( 'Export', 'dev-toolkit' ), 'manage_options', 'dev-toolkit-export', array( $this, 'page_export' ) );
	}

	public function assets( $hook ) {
		if ( false === strpos( $hook, 'dev-toolkit' ) ) {
			return;
		}
		wp_enqueue_style( 'dt-admin', DT_URL . 'assets/admin.css', array(), DT_VERSION );
	}

	/* ------------------------------------------------------------------ */
	/* Dashboard: settings + one-click setup + audit                      */
	/* ------------------------------------------------------------------ */
	public function page_dashboard() {
		$s = DT_Settings::all();
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Dev Toolkit — Setup & Audit', 'dev-toolkit' ); ?></h1>

			<?php if ( isset( $_GET['dt_setup'] ) && 'done' === $_GET['dt_setup'] ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Setup applied.', 'dev-toolkit' ); ?></p></div>
			<?php endif; ?>

			<div class="dt-grid">
				<div class="dt-card">
					<h2><?php esc_html_e( 'Site defaults', 'dev-toolkit' ); ?></h2>
					<form method="post" action="options.php">
						<?php settings_fields( 'dt_settings_group' ); ?>
						<table class="form-table" role="presentation">
							<?php
							$this->cb_row( 'disable_comments', $s, 'Disable comments site-wide' );
							$this->cb_row( 'zero_media_sizes', $s, 'Set all media image sizes to 0' );
							$this->cb_row( 'set_timezone', $s, 'Set site timezone' );
							?>
							<tr>
								<th><?php esc_html_e( 'Timezone', 'dev-toolkit' ); ?></th>
								<td>
									<select name="dt_settings[timezone]">
										<?php
										$zones = timezone_identifiers_list();
										foreach ( $zones as $z ) {
											printf(
												'<option value="%1$s"%2$s>%1$s</option>',
												esc_attr( $z ),
												selected( $s['timezone'], $z, false )
											);
										}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Australia/Sydney is UTC+11 during daylight saving.', 'dev-toolkit' ); ?></p>
								</td>
							</tr>
							<?php
							$this->cb_row( 'disable_media_upload', $s, 'Block new media uploads (lock down media library)' );
							$this->cb_row( 'external_new_tab', $s, 'Open external links in a new tab' );
							$this->cb_row( 'auto_mailto', $s, 'Auto-link bare emails as mailto:' );
							$this->cb_row( 'slider_aria', $s, 'Add aria-labels to slider prev/next arrows' );
							$this->cb_row( 'fixed_mobile_phone', $s, 'Show fixed phone bar on mobile' );
							?>
							<tr>
								<th><?php esc_html_e( 'Mobile phone number', 'dev-toolkit' ); ?></th>
								<td><input type="text" name="dt_settings[mobile_phone_number]" value="<?php echo esc_attr( $s['mobile_phone_number'] ); ?>" class="regular-text" placeholder="+61 ..."></td>
							</tr>
						</table>
						<?php submit_button( __( 'Save settings', 'dev-toolkit' ) ); ?>
					</form>

					<hr>
					<h3><?php esc_html_e( 'Apply core WordPress options', 'dev-toolkit' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Writes the timezone, media sizes and comment status into WordPress based on the toggles above.', 'dev-toolkit' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'dt_apply_setup' ); ?>
						<input type="hidden" name="action" value="dt_apply_setup">
						<?php submit_button( __( 'Apply one-click setup', 'dev-toolkit' ), 'primary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dt-card">
					<h2><?php esc_html_e( 'Build audit', 'dev-toolkit' ); ?></h2>
					<ul class="dt-audit">
						<?php foreach ( DT_Audit::instance()->run() as $c ) : ?>
							<li class="dt-audit__item dt-<?php echo esc_attr( $c['status'] ); ?>">
								<span class="dt-badge"><?php echo esc_html( strtoupper( $c['status'] ) ); ?></span>
								<strong><?php echo esc_html( $c['label'] ); ?></strong>
								<span class="dt-note"><?php echo esc_html( $c['note'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>

					<h3><?php esc_html_e( 'Manual checklist (developer craft)', 'dev-toolkit' ); ?></h3>
					<p class="description"><?php esc_html_e( 'These can’t be automated — they are reminders from the process doc.', 'dev-toolkit' ); ?></p>
					<ul class="dt-manual">
						<li><?php esc_html_e( 'Exactly one H1 per page (not in the slider banner)', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'Links use a distinct colour; smooth animations; fixed header', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'Every button has a hover effect', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'Full-width sections use 1920px images', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'PageSpeed — Mobile 65+, Desktop 70+', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'Responsive across the device list in the doc', 'dev-toolkit' ); ?></li>
						<li><?php esc_html_e( 'No raw HTML in ACF fields (client-editable)', 'dev-toolkit' ); ?></li>
					</ul>
					<?php if ( ! defined( 'DTP_VERSION' ) ) : ?>
						<div class="dt-upsell">
							<p><strong><?php esc_html_e( 'Pro', 'dev-toolkit' ); ?></strong> <?php esc_html_e( 'adds per-page H1 scanning, PageSpeed lookup, CSS boilerplate injector, form validation and a child-theme generator.', 'dev-toolkit' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<?php
			/** Pro renders extra panels on the dashboard. */
			do_action( 'dt_dashboard_after' );
			?>
		</div>
		<?php
	}

	private function cb_row( $key, $s, $label ) {
		printf(
			'<tr><th>%3$s</th><td><label><input type="checkbox" name="dt_settings[%1$s]" value="1" %2$s> %4$s</label></td></tr>',
			esc_attr( $key ),
			checked( ! empty( $s[ $key ] ), true, false ),
			esc_html( $label ),
			esc_html__( 'Enabled', 'dev-toolkit' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Common fields screen                                                */
	/* ------------------------------------------------------------------ */
	public function page_fields() {
		$f = DT_Common_Fields::all();
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Common Fields', 'dev-toolkit' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Edit once, reflect everywhere via shortcodes: [dt_email] [dt_phone] [dt_address] [dt_social] [dt_year start="2019"]', 'dev-toolkit' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'dt_common_fields_group' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Email', 'dev-toolkit' ); ?></th><td><input type="email" name="dt_common_fields[email]" value="<?php echo esc_attr( $f['email'] ?? '' ); ?>" class="regular-text"></td></tr>
					<tr><th><?php esc_html_e( 'Phone', 'dev-toolkit' ); ?></th><td><input type="text" name="dt_common_fields[phone]" value="<?php echo esc_attr( $f['phone'] ?? '' ); ?>" class="regular-text"></td></tr>
					<tr><th><?php esc_html_e( 'Address', 'dev-toolkit' ); ?></th><td><textarea name="dt_common_fields[address]" rows="3" class="large-text"><?php echo esc_textarea( $f['address'] ?? '' ); ?></textarea></td></tr>
					<?php foreach ( DT_Common_Fields::social_networks() as $key => $label ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td><input type="url" name="dt_common_fields[social_<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $f[ 'social_' . $key ] ?? '' ); ?>" class="regular-text" placeholder="https://"></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/* Export screen                                                       */
	/* ------------------------------------------------------------------ */
	public function page_export() {
		$base = admin_url( 'admin-post.php' );
		$types = array(
			'pages'   => __( 'Pages', 'dev-toolkit' ),
			'posts'   => __( 'Posts', 'dev-toolkit' ),
			'cpts'    => __( 'Custom Post Types', 'dev-toolkit' ),
			'plugins' => __( 'Plugins', 'dev-toolkit' ),
			'themes'  => __( 'Themes', 'dev-toolkit' ),
		);
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Export site inventory', 'dev-toolkit' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Download CSV files (open in Excel) for the Step 2 backup checklist.', 'dev-toolkit' ); ?></p>
			<p>
				<?php foreach ( $types as $type => $label ) :
					$url = wp_nonce_url(
						add_query_arg( array( 'action' => 'dt_export', 'type' => $type ), $base ),
						'dt_export'
					);
					?>
					<a class="button button-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</p>
		</div>
		<?php
	}
}
