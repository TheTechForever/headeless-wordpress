<?php
/**
 * One-click site setup. Applies the standard build defaults from the
 * process doc: disable comments site-wide, zero the media image sizes,
 * set the timezone, optionally lock down media uploads.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Site_Setup {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Run the "hard" WP-option changes when the developer clicks Apply.
		add_action( 'admin_post_dt_apply_setup', array( $this, 'apply_setup' ) );

		// Runtime behaviour that must stay enforced on every load.
		if ( DT_Settings::get( 'disable_comments' ) ) {
			$this->enforce_disable_comments();
		}
		if ( DT_Settings::get( 'disable_media_upload' ) ) {
			add_filter( 'upload_mimes', '__return_empty_array' );
		}
	}

	/**
	 * Handler for the "Apply setup" button. Writes core WP options in one go.
	 */
	public function apply_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'dev-toolkit' ) );
		}
		check_admin_referer( 'dt_apply_setup' );

		$applied = array();

		if ( DT_Settings::get( 'zero_media_sizes' ) ) {
			foreach ( array( 'thumbnail_size_w', 'thumbnail_size_h', 'medium_size_w', 'medium_size_h', 'large_size_w', 'large_size_h' ) as $opt ) {
				update_option( $opt, 0 );
			}
			$applied[] = 'media_sizes';
		}

		if ( DT_Settings::get( 'set_timezone' ) ) {
			// Storing a named timezone_string is the modern, DST-aware way.
			update_option( 'timezone_string', DT_Settings::get( 'timezone' ) );
			update_option( 'gmt_offset', '' );
			$applied[] = 'timezone';
		}

		if ( DT_Settings::get( 'disable_comments' ) ) {
			update_option( 'default_comment_status', 'closed' );
			update_option( 'default_ping_status', 'closed' );
			// Close comments on every existing post/page as well.
			$this->close_comments_on_existing();
			$applied[] = 'comments';
		}

		set_transient( 'dt_setup_applied', $applied, 60 );

		wp_safe_redirect( add_query_arg(
			array( 'page' => 'dev-toolkit', 'dt_setup' => 'done' ),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Enforce comment-disabling at runtime (menus, admin bar, support flags).
	 */
	private function enforce_disable_comments() {
		// Turn off support across all post types.
		add_action( 'init', function () {
			foreach ( get_post_types() as $type ) {
				if ( post_type_supports( $type, 'comments' ) ) {
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
		}, 100 );

		// Force comments closed everywhere.
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', '__return_empty_array', 20 );

		// Remove admin menu + admin bar node.
		add_action( 'admin_menu', function () {
			remove_menu_page( 'edit-comments.php' );
		} );
		add_action( 'wp_before_admin_bar_render', function () {
			global $wp_admin_bar;
			$wp_admin_bar->remove_node( 'comments' );
		} );
		add_action( 'admin_init', function () {
			// Redirect anyone trying to hit the comments screen.
			global $pagenow;
			if ( 'edit-comments.php' === $pagenow ) {
				wp_safe_redirect( admin_url() );
				exit;
			}
		} );
	}

	private function close_comments_on_existing() {
		global $wpdb;
		// Direct UPDATE is intentional here: this is a one-shot bulk admin
		// action, and looping wp_update_post() over thousands of rows would
		// be needlessly slow.
		$wpdb->query( "UPDATE {$wpdb->posts} SET comment_status = 'closed', ping_status = 'closed' WHERE post_status = 'publish'" );
		clean_post_cache_all();
	}
}

/**
 * Flush post cache after the bulk update above. Wrapped so it is only
 * defined once even if the file is required twice.
 */
if ( ! function_exists( 'clean_post_cache_all' ) ) {
	function clean_post_cache_all() {
		wp_cache_flush();
	}
}
