<?php
/**
 * Form validation (Step 9 of the process doc): required-field enforcement,
 * password strength, and confirm-password matching. Ships a small JS
 * helper you attach to any form by adding data-dt-validate, and a reusable
 * server-side validator for custom handlers.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Form_Validation {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets() {
		wp_register_script( 'dtp-validate', DTP_URL . 'assets/validate.js', array(), DTP_VERSION, true );
		wp_enqueue_script( 'dtp-validate' );
	}

	/**
	 * Reusable server-side validator. Returns an array of error messages
	 * keyed by field name (empty array = valid). Use from your own AJAX or
	 * form handlers.
	 *
	 * @param array $data  Submitted values.
	 * @param array $rules field => rule string, e.g. 'required|email',
	 *                     'required|password', 'match:password'.
	 */
	public static function validate( array $data, array $rules ) {
		$errors = array();

		foreach ( $rules as $field => $ruleset ) {
			$value = isset( $data[ $field ] ) ? (string) $data[ $field ] : '';
			foreach ( explode( '|', $ruleset ) as $rule ) {
				if ( 'required' === $rule && '' === trim( $value ) ) {
					$errors[ $field ][] = __( 'This field is required.', 'dev-toolkit-pro' );
				} elseif ( 'email' === $rule && $value && ! is_email( $value ) ) {
					$errors[ $field ][] = __( 'Enter a valid email address.', 'dev-toolkit-pro' );
				} elseif ( 'password' === $rule && $value ) {
					if ( ! self::strong_password( $value ) ) {
						$errors[ $field ][] = __( 'Password must be 8+ chars with upper, lower and a number.', 'dev-toolkit-pro' );
					}
				} elseif ( 0 === strpos( $rule, 'match:' ) ) {
					$other = substr( $rule, 6 );
					if ( $value !== ( $data[ $other ] ?? null ) ) {
						$errors[ $field ][] = __( 'Values do not match.', 'dev-toolkit-pro' );
					}
				}
			}
		}
		return $errors;
	}

	public static function strong_password( $pw ) {
		return strlen( $pw ) >= 8
			&& preg_match( '/[A-Z]/', $pw )
			&& preg_match( '/[a-z]/', $pw )
			&& preg_match( '/[0-9]/', $pw );
	}
}
