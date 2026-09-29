<?php
/**
 * Shortcodes so the client's contact details live in one place and update
 * site-wide. Covers the doc's requirement for email/phone shortcodes plus
 * a dynamic-year shortcode for copyright text.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Shortcodes {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_shortcode( 'dt_email', array( $this, 'email' ) );
		add_shortcode( 'dt_phone', array( $this, 'phone' ) );
		add_shortcode( 'dt_address', array( $this, 'address' ) );
		add_shortcode( 'dt_year', array( $this, 'year' ) );
		add_shortcode( 'dt_social', array( $this, 'social' ) );
	}

	/**
	 * [dt_email link="true"]  -> mailto link, or plain text if link="false".
	 */
	public function email( $atts ) {
		$atts  = shortcode_atts( array( 'link' => 'true' ), $atts, 'dt_email' );
		$email = DT_Common_Fields::get( 'email' );
		if ( ! $email ) {
			return '';
		}
		if ( 'false' === $atts['link'] ) {
			return esc_html( $email );
		}
		return sprintf(
			'<a href="mailto:%1$s">%2$s</a>',
			esc_attr( antispambot( $email ) ),
			esc_html( antispambot( $email ) )
		);
	}

	/**
	 * [dt_phone link="true"] -> tel: link with digits normalised.
	 */
	public function phone( $atts ) {
		$atts  = shortcode_atts( array( 'link' => 'true' ), $atts, 'dt_phone' );
		$phone = DT_Common_Fields::get( 'phone' );
		if ( ! $phone ) {
			return '';
		}
		if ( 'false' === $atts['link'] ) {
			return esc_html( $phone );
		}
		// tel: needs a clean number: keep leading + and digits only.
		$tel = preg_replace( '/[^0-9+]/', '', $phone );
		return sprintf(
			'<a href="tel:%1$s">%2$s</a>',
			esc_attr( $tel ),
			esc_html( $phone )
		);
	}

	/**
	 * [dt_address] -> address with line breaks preserved.
	 */
	public function address() {
		$address = DT_Common_Fields::get( 'address' );
		return $address ? nl2br( esc_html( $address ) ) : '';
	}

	/**
	 * [dt_year] or [dt_year start="2019"] -> "2026" or "2019–2026".
	 * Solves the "dynamic year in copyright" requirement.
	 */
	public function year( $atts ) {
		$atts = shortcode_atts( array( 'start' => '' ), $atts, 'dt_year' );
		$now  = wp_date( 'Y' );
		if ( $atts['start'] && (int) $atts['start'] < (int) $now ) {
			return esc_html( $atts['start'] . '–' . $now );
		}
		return esc_html( $now );
	}

	/**
	 * [dt_social] -> <ul> of social links that are set. Third-party links
	 * open in a new tab per the process doc.
	 */
	public function social( $atts ) {
		$atts = shortcode_atts( array( 'class' => 'dt-social' ), $atts, 'dt_social' );
		$out  = '';
		foreach ( DT_Common_Fields::social_networks() as $key => $label ) {
			$url = DT_Common_Fields::get( 'social_' . $key );
			if ( ! $url ) {
				continue;
			}
			$out .= sprintf(
				'<li class="dt-social-%1$s"><a href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%3$s</a></li>',
				esc_attr( $key ),
				esc_url( $url ),
				esc_attr( $label )
			);
		}
		if ( ! $out ) {
			return '';
		}
		return sprintf( '<ul class="%1$s">%2$s</ul>', esc_attr( $atts['class'] ), $out );
	}
}
