<?php
/**
 * Child-theme generator (Theme Step 2 of the process doc). Creates a child
 * theme for the chosen parent with a style.css, a functions.php that
 * enqueues the parent stylesheet, and a starter screenshot placeholder.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Child_Theme {

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
		add_action( 'admin_menu', array( $this, 'menu' ), 22 );
		add_action( 'admin_post_dtp_make_child', array( $this, 'handle' ) );
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Child Theme', 'dev-toolkit-pro' ),
			__( 'Child Theme', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-child',
			array( $this, 'page' )
		);
	}

	public function page() {
		$parents = wp_get_themes();
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Generate a child theme', 'dev-toolkit-pro' ); ?></h1>
			<?php if ( isset( $_GET['dtp_child'] ) ) : ?>
				<div class="notice notice-<?php echo 'ok' === $_GET['dtp_child'] ? 'success' : 'error'; ?>"><p><?php echo esc_html( get_transient( 'dtp_child_msg' ) ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'dtp_make_child' ); ?>
				<input type="hidden" name="action" value="dtp_make_child">
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Parent theme', 'dev-toolkit-pro' ); ?></th>
						<td><select name="parent">
							<?php foreach ( $parents as $slug => $theme ) :
								if ( $theme->parent() ) { continue; } // don't parent to a child ?>
								<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $theme->get( 'Name' ) ); ?></option>
							<?php endforeach; ?>
						</select></td></tr>
				</table>
				<?php submit_button( __( 'Create child theme', 'dev-toolkit-pro' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handle() {
		if ( ! current_user_can( 'install_themes' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'dev-toolkit-pro' ) );
		}
		check_admin_referer( 'dtp_make_child' );

		$parent_slug = isset( $_POST['parent'] ) ? sanitize_key( $_POST['parent'] ) : '';
		$parent      = wp_get_theme( $parent_slug );

		if ( ! $parent->exists() ) {
			$this->redirect( 'err', __( 'Parent theme not found.', 'dev-toolkit-pro' ) );
		}

		$child_slug = $parent_slug . '-child';
		$child_dir  = get_theme_root() . '/' . $child_slug;

		if ( file_exists( $child_dir ) ) {
			$this->redirect( 'err', __( 'A child theme already exists for that parent.', 'dev-toolkit-pro' ) );
		}

		// Use the WP filesystem API rather than raw fopen.
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		if ( ! $wp_filesystem->mkdir( $child_dir ) ) {
			$this->redirect( 'err', __( 'Could not create the child theme folder (permissions?).', 'dev-toolkit-pro' ) );
		}

		$name  = $parent->get( 'Name' ) . ' Child';
		$style = "/*\nTheme Name: {$name}\nTemplate: {$parent_slug}\nVersion: 1.0.0\nText Domain: {$child_slug}\n*/\n";

		$functions = "<?php\n"
			. "// Enqueue parent + child stylesheets.\n"
			. "add_action( 'wp_enqueue_scripts', function () {\n"
			. "\t\$parent = 'parent-style';\n"
			. "\twp_enqueue_style( \$parent, get_template_directory_uri() . '/style.css' );\n"
			. "\twp_enqueue_style( 'child-style', get_stylesheet_uri(), array( \$parent ), wp_get_theme()->get( 'Version' ) );\n"
			. "} );\n";

		$wp_filesystem->put_contents( $child_dir . '/style.css', $style, FS_CHMOD_FILE );
		$wp_filesystem->put_contents( $child_dir . '/functions.php', $functions, FS_CHMOD_FILE );

		$this->redirect( 'ok', sprintf( __( 'Child theme "%s" created. Activate it under Appearance → Themes.', 'dev-toolkit-pro' ), $name ) );
	}

	private function redirect( $status, $msg ) {
		set_transient( 'dtp_child_msg', $msg, 60 );
		wp_safe_redirect( add_query_arg(
			array( 'page' => 'dev-toolkit-child', 'dtp_child' => $status ),
			admin_url( 'admin.php' )
		) );
		exit;
	}
}
