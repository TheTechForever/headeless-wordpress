<?php
/**
 * Common site info fields (email, phone, address, social links) stored in
 * one option so they can be edited in a single place and echoed anywhere
 * via shortcodes. Works with or without ACF/SCF installed.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Common_Fields {

	const OPTION = 'dt_common_fields';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	public static function get( $key, $fallback = '' ) {
		$all = get_option( self::OPTION, array() );
		return isset( $all[ $key ] ) ? $all[ $key ] : $fallback;
	}

	public static function all() {
		return get_option( self::OPTION, array() );
	}

	/**
	 * The social networks we expose. Add to this array to add more fields.
	 */
	public static function social_networks() {
		return array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'linkedin'  => 'LinkedIn',
			'youtube'   => 'YouTube',
			'x'         => 'X / Twitter',
			'tiktok'    => 'TikTok',
		);
	}

	public function register() {
		register_setting(
			'dt_common_fields_group',
			self::OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize' ) )
		);
	}

	public function sanitize( $input ) {
		$out = array();

		$out['email']   = isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '';
		$out['phone']   = isset( $input['phone'] ) ? sanitize_text_field( $input['phone'] ) : '';
		$out['address'] = isset( $input['address'] ) ? sanitize_textarea_field( $input['address'] ) : '';

		foreach ( array_keys( self::social_networks() ) as $net ) {
			$key         = 'social_' . $net;
			$out[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : '';
		}

		return $out;
	}
}
