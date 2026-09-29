<?php
/**
 * Backup/export helper for Step 2 of the process doc. Produces CSV files
 * (openable in Excel) listing Pages, Posts, Plugins, Themes and Custom
 * Post Types so the developer can hand the list to the team lead.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Exporter {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_post_dt_export', array( $this, 'handle_export' ) );
	}

	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'dev-toolkit' ) );
		}
		check_admin_referer( 'dt_export' );

		$type = isset( $_GET['type'] ) ? sanitize_key( $_GET['type'] ) : '';
		$rows = array();
		$name = 'export';

		switch ( $type ) {
			case 'pages':
				$name = 'pages';
				$rows = $this->posts_rows( 'page' );
				break;
			case 'posts':
				$name = 'posts';
				$rows = $this->posts_rows( 'post' );
				break;
			case 'cpts':
				$name = 'custom-post-types';
				$rows = $this->cpt_rows();
				break;
			case 'plugins':
				$name = 'plugins';
				$rows = $this->plugin_rows();
				break;
			case 'themes':
				$name = 'themes';
				$rows = $this->theme_rows();
				break;
			default:
				wp_die( esc_html__( 'Unknown export type.', 'dev-toolkit' ) );
		}

		$this->stream_csv( $name, $rows );
	}

	private function posts_rows( $post_type ) {
		$rows  = array( array( 'ID', 'Title', 'Status', 'Slug', 'URL', 'Modified' ) );
		$items = get_posts( array(
			'post_type'   => $post_type,
			'post_status' => 'any',
			'numberposts' => -1,
		) );
		foreach ( $items as $p ) {
			$rows[] = array(
				$p->ID,
				get_the_title( $p ),
				$p->post_status,
				$p->post_name,
				get_permalink( $p ),
				$p->post_modified,
			);
		}
		return $rows;
	}

	private function cpt_rows() {
		$rows  = array( array( 'Slug', 'Label', 'Public', 'Item Count' ) );
		$types = get_post_types( array( '_builtin' => false ), 'objects' );
		foreach ( $types as $slug => $obj ) {
			$count  = wp_count_posts( $slug );
			$total  = isset( $count->publish ) ? $count->publish : 0;
			$rows[] = array(
				$slug,
				$obj->labels->name,
				$obj->public ? 'yes' : 'no',
				$total,
			);
		}
		return $rows;
	}

	private function plugin_rows() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active = (array) get_option( 'active_plugins', array() );
		$rows   = array( array( 'Name', 'Version', 'Active', 'Author', 'File' ) );
		foreach ( get_plugins() as $file => $data ) {
			$rows[] = array(
				$data['Name'],
				$data['Version'],
				in_array( $file, $active, true ) ? 'active' : 'inactive',
				wp_strip_all_tags( $data['Author'] ),
				$file,
			);
		}
		return $rows;
	}

	private function theme_rows() {
		$current = wp_get_theme();
		$rows    = array( array( 'Name', 'Version', 'Active', 'Parent', 'Stylesheet' ) );
		foreach ( wp_get_themes() as $slug => $theme ) {
			$rows[] = array(
				$theme->get( 'Name' ),
				$theme->get( 'Version' ),
				( $theme->get_stylesheet() === $current->get_stylesheet() ) ? 'active' : 'inactive',
				$theme->parent() ? $theme->parent()->get( 'Name' ) : '',
				$slug,
			);
		}
		return $rows;
	}

	/**
	 * Stream an array of rows as a downloadable CSV.
	 */
	private function stream_csv( $name, $rows ) {
		$filename = sprintf(
			'%s-%s-%s.csv',
			sanitize_title( get_bloginfo( 'name' ) ),
			$name,
			gmdate( 'Y-m-d' )
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		$fh = fopen( 'php://output', 'w' );
		// BOM so Excel reads UTF-8 correctly.
		fprintf( $fh, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );
		foreach ( $rows as $row ) {
			fputcsv( $fh, $row );
		}
		fclose( $fh );
		exit;
	}
}
