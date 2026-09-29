<?php
/**
 * Bulk installer for the "basic plugin" list from the process doc. Presents
 * the standard set with one-click install links to the WordPress.org
 * repository so a new build starts with the agency's baseline stack.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Plugin_Installer {

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
		add_action( 'admin_menu', array( $this, 'menu' ), 23 );
	}

	/**
	 * The standard stack. Slug = WordPress.org plugin slug where one exists;
	 * commercial/host plugins are listed with a note instead of a slug.
	 */
	public static function stack() {
		return array(
			array( 'name' => 'Contact Form 7', 'slug' => 'contact-form-7' ),
			array( 'name' => 'Contact Form 7 Database Addon (CFDB7)', 'slug' => 'contact-form-cfdb7' ),
			array( 'name' => 'Secure Custom Fields (SCF)', 'slug' => 'secure-custom-fields' ),
			array( 'name' => 'ACF Photo Gallery Field', 'slug' => 'acf-photo-gallery' ),
			array( 'name' => 'Smash Balloon Instagram Feed', 'slug' => 'instagram-feed' ),
			array( 'name' => 'Widget for Google Reviews', 'slug' => 'widget-google-reviews' ),
			array( 'name' => 'Wordfence Security', 'slug' => 'wordfence' ),
			array( 'name' => 'Activity Log', 'slug' => 'aryo-activity-log' ),
			array( 'name' => 'Rank Math SEO', 'slug' => 'seo-by-rank-math' ),
			array( 'name' => 'WooCommerce (if needed)', 'slug' => 'woocommerce' ),
			array( 'name' => 'ManageWP Worker', 'note' => 'Installed via ManageWP dashboard' ),
			array( 'name' => 'SiteGround Security / Speed (host-specific)', 'note' => 'Provided by the host' ),
			array( 'name' => 'LiteSpeed Cache / Speed optimizer', 'slug' => 'litespeed-cache' ),
		);
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Plugin Stack', 'dev-toolkit-pro' ),
			__( 'Plugin Stack', 'dev-toolkit-pro' ),
			'install_plugins',
			'dev-toolkit-stack',
			array( $this, 'page' )
		);
	}

	public function page() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$installed = array_keys( get_plugins() );
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Standard plugin stack', 'dev-toolkit-pro' ); ?></h1>
			<p class="description"><?php esc_html_e( 'One-click install links for the baseline plugins. Review whether each is needed for the project.', 'dev-toolkit-pro' ); ?></p>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Plugin', 'dev-toolkit-pro' ); ?></th><th><?php esc_html_e( 'Status', 'dev-toolkit-pro' ); ?></th><th><?php esc_html_e( 'Action', 'dev-toolkit-pro' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( self::stack() as $p ) :
					$is_installed = false;
					if ( ! empty( $p['slug'] ) ) {
						foreach ( $installed as $file ) {
							if ( 0 === strpos( $file, $p['slug'] . '/' ) ) { $is_installed = true; break; }
						}
					}
					?>
					<tr>
						<td><strong><?php echo esc_html( $p['name'] ); ?></strong></td>
						<td>
							<?php if ( ! empty( $p['note'] ) ) : ?>
								<em><?php echo esc_html( $p['note'] ); ?></em>
							<?php elseif ( $is_installed ) : ?>
								<span style="color:#1a9d5a;">&#10003; <?php esc_html_e( 'Installed', 'dev-toolkit-pro' ); ?></span>
							<?php else : ?>
								<?php esc_html_e( 'Not installed', 'dev-toolkit-pro' ); ?>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( empty( $p['note'] ) && ! $is_installed ) :
								$url = wp_nonce_url(
									self_admin_url( 'update.php?action=install-plugin&plugin=' . $p['slug'] ),
									'install-plugin_' . $p['slug']
								); ?>
								<a class="button" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Install', 'dev-toolkit-pro' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}
}
