<?php
/**
 * Front-end content/behaviour filters from the process doc:
 *  - Third-party (external) links open in a new tab with safe rel.
 *  - Bare email addresses in content become mailto: links.
 *  - Slider prev/next arrows get aria-labels (done in JS for reliability).
 *  - Optional fixed mobile phone bar at the bottom on small screens.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Content_Filters {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		if ( DT_Settings::get( 'external_new_tab' ) ) {
			add_filter( 'the_content', array( $this, 'external_links_new_tab' ), 15 );
		}
		if ( DT_Settings::get( 'auto_mailto' ) ) {
			add_filter( 'the_content', array( $this, 'linkify_emails' ), 16 );
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'wp_footer', array( $this, 'mobile_phone_bar' ) );
	}

	/**
	 * Add target/rel to links that point off-site. Internal links untouched.
	 */
	public function external_links_new_tab( $content ) {
		if ( empty( $content ) || false === strpos( $content, '<a ' ) ) {
			return $content;
		}

		$home = wp_parse_url( home_url(), PHP_URL_HOST );

		return preg_replace_callback(
			'/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
			function ( $m ) use ( $home ) {
				$tag  = $m[0];
				$href = $m[1];

				// Skip anchors, mailto, tel, and internal links.
				if ( preg_match( '/^(#|mailto:|tel:)/i', $href ) ) {
					return $tag;
				}
				$host = wp_parse_url( $href, PHP_URL_HOST );
				if ( ! $host || $host === $home ) {
					return $tag;
				}

				if ( false === stripos( $tag, 'target=' ) ) {
					$tag = str_replace( '<a ', '<a target="_blank" ', $tag );
				}
				if ( false === stripos( $tag, 'rel=' ) ) {
					$tag = str_replace( '<a ', '<a rel="noopener noreferrer" ', $tag );
				}
				return $tag;
			},
			$content
		);
	}

	/**
	 * Convert bare email addresses (not already inside a tag) to mailto links.
	 */
	public function linkify_emails( $content ) {
		if ( empty( $content ) || false === strpos( $content, '@' ) ) {
			return $content;
		}
		// Only match emails that are NOT immediately preceded by a > or : or "
		// which would indicate they're already inside an href/attribute.
		return preg_replace_callback(
			'/(^|[\s(])([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})(?![^<]*>)/i',
			function ( $m ) {
				$email = $m[2];
				return $m[1] . sprintf(
					'<a href="mailto:%1$s">%1$s</a>',
					esc_attr( $email )
				);
			},
			$content
		);
	}

	public function assets() {
		wp_enqueue_style( 'dt-frontend', DT_URL . 'assets/frontend.css', array(), DT_VERSION );
		wp_enqueue_script( 'dt-frontend', DT_URL . 'assets/frontend.js', array(), DT_VERSION, true );

		wp_localize_script( 'dt-frontend', 'DT_FRONT', array(
			'sliderAria' => (bool) DT_Settings::get( 'slider_aria' ),
		) );
	}

	/**
	 * Fixed mobile phone bar (icon + tel link) shown only on small screens.
	 */
	public function mobile_phone_bar() {
		if ( ! DT_Settings::get( 'fixed_mobile_phone' ) ) {
			return;
		}
		$phone = DT_Settings::get( 'mobile_phone_number' );
		if ( ! $phone ) {
			$phone = DT_Common_Fields::get( 'phone' );
		}
		if ( ! $phone ) {
			return;
		}
		$tel = preg_replace( '/[^0-9+]/', '', $phone );
		printf(
			'<a class="dt-mobile-phone" href="tel:%1$s" aria-label="Call us">
				<span class="dt-mobile-phone__icon" aria-hidden="true">&#9742;</span>
				<span class="dt-mobile-phone__text">%2$s</span>
			</a>',
			esc_attr( $tel ),
			esc_html( $phone )
		);
	}
}
