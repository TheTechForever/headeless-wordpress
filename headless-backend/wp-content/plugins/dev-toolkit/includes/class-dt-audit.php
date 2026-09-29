<?php
/**
 * Build audit. Runs the checks from the process doc that CAN be verified
 * server-side and reports pass/warn/fail. Things a plugin cannot "do" for
 * you (like writing responsive CSS) are surfaced here as reminders so
 * nothing is silently skipped.
 *
 * @package DevToolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DT_Audit {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * @return array List of checks: label, status (pass|warn|fail), note.
	 */
	public function run() {
		$checks = array();

		// Comments disabled?
		$comments_closed = 'closed' === get_option( 'default_comment_status' );
		$checks[] = $this->check(
			'Comments disabled site-wide',
			$comments_closed ? 'pass' : 'warn',
			$comments_closed ? 'Default comment status is closed.' : 'Run one-click setup to close comments.'
		);

		// Media sizes zeroed?
		$sizes_zero = 0 === (int) get_option( 'thumbnail_size_w' )
			&& 0 === (int) get_option( 'medium_size_w' )
			&& 0 === (int) get_option( 'large_size_w' );
		$checks[] = $this->check(
			'Media image sizes set to 0',
			$sizes_zero ? 'pass' : 'warn',
			$sizes_zero ? 'Thumbnail/medium/large widths are 0.' : 'Media sizes are not all 0.'
		);

		// Timezone set to a named zone (doc wants Australia +11).
		$tz = get_option( 'timezone_string' );
		$checks[] = $this->check(
			'Timezone configured',
			$tz ? 'pass' : 'warn',
			$tz ? 'Timezone: ' . $tz : 'No named timezone set.'
		);

		// Permalinks not "plain".
		$permalink = get_option( 'permalink_structure' );
		$checks[] = $this->check(
			'Pretty permalinks enabled',
			$permalink ? 'pass' : 'warn',
			$permalink ? 'Permalink structure is set.' : 'Permalinks are set to plain.'
		);

		// A child theme is active (doc requires child theme).
		$is_child = is_child_theme();
		$checks[] = $this->check(
			'Child theme active',
			$is_child ? 'pass' : 'warn',
			$is_child ? 'A child theme is active.' : 'Active theme is not a child theme.'
		);

		// 404 template exists in the active theme.
		$has_404 = (bool) get_404_template();
		$checks[] = $this->check(
			'404 template present',
			$has_404 ? 'pass' : 'warn',
			$has_404 ? 'Theme provides a 404.php.' : 'No 404 template found in the active theme.'
		);

		// Common fields filled.
		$has_contact = DT_Common_Fields::get( 'email' ) && DT_Common_Fields::get( 'phone' );
		$checks[] = $this->check(
			'Common contact fields set',
			$has_contact ? 'pass' : 'warn',
			$has_contact ? 'Email and phone are set.' : 'Fill in the Common Fields screen.'
		);

		// Discourage search-engine blocking left on before launch.
		$blocked = '0' === get_option( 'blog_public' );
		$checks[] = $this->check(
			'Search engine visibility',
			$blocked ? 'fail' : 'pass',
			$blocked ? 'Site is set to discourage search engines!' : 'Site is visible to search engines.'
		);

		return $checks;
	}

	private function check( $label, $status, $note ) {
		return compact( 'label', 'status', 'note' );
	}
}
