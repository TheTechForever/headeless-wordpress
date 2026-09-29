<?php
/**
 * Central settings store. All feature toggles live in a single option
 * ('dt_settings') so features can read a shared, sanitized array.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Settings {

	const OPTION = 'dt_settings';

	private static $instance = null;
	private $data = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_init', array( $this, 'register' ) );
	}

	/**
	 * Default feature flags. Everything that could break a site is OFF by
	 * default; the developer opts in from the setup screen.
	 */
	public static function defaults() {
		return array(
			'disable_comments'     => 1,
			'zero_media_sizes'     => 1,
			'set_timezone'         => 1,
			'timezone'             => 'Australia/Sydney', // UTC+11 with DST.
			'disable_media_upload' => 0,
			'external_new_tab'     => 1,
			'auto_mailto'          => 1,
			'slider_aria'          => 1,
			'fixed_mobile_phone'   => 0,
			'mobile_phone_number'  => '',
		);
	}

	/**
	 * Read a single setting with a fallback to its default.
	 */
	public static function get( $key, $fallback = null ) {
		$all = get_option( self::OPTION, self::defaults() );
		if ( isset( $all[ $key ] ) ) {
			return $all[ $key ];
		}
		$defaults = self::defaults();
		if ( isset( $defaults[ $key ] ) ) {
			return $defaults[ $key ];
		}
		return $fallback;
	}

	public static function all() {
		return wp_parse_args( get_option( self::OPTION, array() ), self::defaults() );
	}

	public function register() {
		register_setting(
			'dt_settings_group',
			self::OPTION,
			array( 'sanitize_callback' => array( $this, 'sanitize' ) )
		);
	}

	/**
	 * Sanitize the whole settings array on save.
	 */
	public function sanitize( $input ) {
		$out       = array();
		$checkboxes = array(
			'disable_comments',
			'zero_media_sizes',
			'set_timezone',
			'disable_media_upload',
			'external_new_tab',
			'auto_mailto',
			'slider_aria',
			'fixed_mobile_phone',
		);
		foreach ( $checkboxes as $cb ) {
			$out[ $cb ] = empty( $input[ $cb ] ) ? 0 : 1;
		}

		// Validate timezone against the real list of PHP identifiers.
		$tz = isset( $input['timezone'] ) ? sanitize_text_field( $input['timezone'] ) : 'Australia/Sydney';
		$out['timezone'] = in_array( $tz, timezone_identifiers_list(), true ) ? $tz : 'Australia/Sydney';

		$out['mobile_phone_number'] = isset( $input['mobile_phone_number'] )
			? sanitize_text_field( $input['mobile_phone_number'] )
			: '';

		return $out;
	}
}
