<?php
/**
 * Page-level audit for Pro. Fetches a rendered page and checks the items
 * from the process doc that can be verified programmatically:
 *   - exactly one H1
 *   - viewport meta present (responsive prerequisite)
 *   - images missing alt
 *   - icon/empty links with no discernible name (slider-arrow point)
 *   - external links missing target/rel
 *   - title + meta description present
 *   - mixed (http://) content
 * Plus a Google PageSpeed Insights lookup (Mobile/Desktop vs 65/70) and a
 * client-side responsive overflow checker across the doc's device widths.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Page_Audit {

	const PSI_OPTION = 'dtp_psi_key';

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
		add_action( 'admin_menu', array( $this, 'menu' ), 24 );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function register() {
		register_setting( 'dtp_psi_group', self::PSI_OPTION, array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public function assets( $hook ) {
		if ( false === strpos( $hook, 'dev-toolkit-audit' ) ) {
			return;
		}
		wp_enqueue_script( 'dtp-responsive', DTP_URL . 'assets/responsive.js', array(), DTP_VERSION, true );
		wp_localize_script( 'dtp-responsive', 'DTP_DEVICES', $this->devices() );
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Page Audit', 'dev-toolkit-pro' ),
			__( 'Page Audit', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-audit',
			array( $this, 'page' )
		);
	}

	/**
	 * Device widths straight from the process doc (Step 6).
	 */
	private function devices() {
		return array(
			array( 'label' => 'Galaxy / Pixel', 'w' => 360, 'h' => 640, 'group' => 'Mobile' ),
			array( 'label' => 'iPhone SE', 'w' => 375, 'h' => 667, 'group' => 'Mobile' ),
			array( 'label' => 'iPhone 12–14', 'w' => 390, 'h' => 844, 'group' => 'Mobile' ),
			array( 'label' => 'iPhone XR/11', 'w' => 414, 'h' => 896, 'group' => 'Mobile' ),
			array( 'label' => 'iPhone Pro Max', 'w' => 428, 'h' => 926, 'group' => 'Mobile' ),
			array( 'label' => 'iPad portrait', 'w' => 768, 'h' => 1024, 'group' => 'Tablet' ),
			array( 'label' => 'iPad landscape', 'w' => 1024, 'h' => 768, 'group' => 'Tablet' ),
			array( 'label' => 'iPad Air', 'w' => 820, 'h' => 1180, 'group' => 'Tablet' ),
			array( 'label' => 'Android tablet', 'w' => 1280, 'h' => 800, 'group' => 'Tablet' ),
			array( 'label' => 'Surface Pro 8', 'w' => 912, 'h' => 1368, 'group' => 'Tablet' ),
			array( 'label' => 'HD laptop', 'w' => 1366, 'h' => 768, 'group' => 'Laptop' ),
			array( 'label' => 'MacBook Air', 'w' => 1440, 'h' => 900, 'group' => 'Laptop' ),
			array( 'label' => 'Standard laptop', 'w' => 1600, 'h' => 900, 'group' => 'Laptop' ),
			array( 'label' => 'Full HD desktop', 'w' => 1920, 'h' => 1080, 'group' => 'Desktop' ),
		);
	}

	/* ------------------------------------------------------------------ */
	/* On-page checks (fetch + DOM parse)                                  */
	/* ------------------------------------------------------------------ */
	private function scan_url( $url ) {
		$res = wp_remote_get( $url, array( 'timeout' => 20, 'sslverify' => false, 'user-agent' => 'DevToolkitAudit/1.0' ) );
		if ( is_wp_error( $res ) ) {
			return array( 'error' => $res->get_error_message() );
		}
		$code = wp_remote_retrieve_response_code( $res );
		$html = wp_remote_retrieve_body( $res );
		if ( ! $html ) {
			return array( 'error' => sprintf( __( 'Empty response (HTTP %d).', 'dev-toolkit-pro' ), $code ) );
		}

		$home = wp_parse_url( home_url(), PHP_URL_HOST );

		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
		libxml_clear_errors();
		$xp = new DOMXPath( $dom );

		$checks = array();

		// H1 count.
		$h1 = $xp->query( '//h1' )->length;
		$checks[] = $this->c( 'Exactly one H1', 1 === $h1 ? 'pass' : ( 0 === $h1 ? 'fail' : 'warn' ), sprintf( _n( '%d H1 found.', '%d H1s found.', $h1, 'dev-toolkit-pro' ), $h1 ) );

		// Viewport meta.
		$vp = $xp->query( '//meta[@name="viewport"]' )->length;
		$checks[] = $this->c( 'Responsive viewport meta', $vp ? 'pass' : 'fail', $vp ? 'Present.' : 'Missing — page will not be mobile-responsive.' );

		// Title + meta description.
		$title = $xp->query( '//title' )->length;
		$desc  = $xp->query( '//meta[@name="description"]' )->length;
		$checks[] = $this->c( 'Title tag', $title ? 'pass' : 'fail', $title ? 'Present.' : 'Missing.' );
		$checks[] = $this->c( 'Meta description', $desc ? 'pass' : 'warn', $desc ? 'Present.' : 'Missing.' );

		// Images without alt.
		$imgs    = $xp->query( '//img' );
		$no_alt  = 0;
		foreach ( $imgs as $img ) {
			if ( ! $img->hasAttribute( 'alt' ) || '' === trim( $img->getAttribute( 'alt' ) ) ) {
				$no_alt++;
			}
		}
		$checks[] = $this->c( 'Images have alt text', $no_alt ? 'warn' : 'pass', $no_alt ? sprintf( '%d image(s) missing alt.', $no_alt ) : 'All images have alt.' );

		// Links with no discernible name (empty text + no aria-label/title) —
		// this is the slider-arrow / icon-link PageSpeed point.
		$links      = $xp->query( '//a[@href]' );
		$empty_name = 0;
		$ext_no_tab = 0;
		foreach ( $links as $a ) {
			$text  = trim( $a->textContent );
			$aria  = $a->getAttribute( 'aria-label' );
			$title = $a->getAttribute( 'title' );
			$hasImgAlt = false;
			foreach ( $a->getElementsByTagName( 'img' ) as $i ) {
				if ( trim( $i->getAttribute( 'alt' ) ) ) { $hasImgAlt = true; }
			}
			if ( '' === $text && '' === trim( $aria ) && '' === trim( $title ) && ! $hasImgAlt ) {
				$empty_name++;
			}

			// External links opening in same tab.
			$href = $a->getAttribute( 'href' );
			$host = wp_parse_url( $href, PHP_URL_HOST );
			if ( $host && $host !== $home && false === stripos( $a->getAttribute( 'target' ), '_blank' ) ) {
				$ext_no_tab++;
			}
		}
		$checks[] = $this->c( 'Links have discernible names', $empty_name ? 'warn' : 'pass', $empty_name ? sprintf( '%d link(s) need aria-label (e.g. slider arrows).', $empty_name ) : 'All links are named.' );
		$checks[] = $this->c( 'External links open in new tab', $ext_no_tab ? 'warn' : 'pass', $ext_no_tab ? sprintf( '%d external link(s) not set to _blank.', $ext_no_tab ) : 'OK.' );

		// Mixed content.
		$mixed = substr_count( $html, 'src="http://' ) + substr_count( $html, 'href="http://' );
		$checks[] = $this->c( 'No mixed (http) content', $mixed ? 'warn' : 'pass', $mixed ? sprintf( '%d http:// resource(s) found.', $mixed ) : 'None.' );

		return array( 'checks' => $checks );
	}

	private function c( $label, $status, $note ) {
		return compact( 'label', 'status', 'note' );
	}

	/* ------------------------------------------------------------------ */
	/* PageSpeed Insights                                                  */
	/* ------------------------------------------------------------------ */
	private function pagespeed( $url, $strategy ) {
		$key = get_option( self::PSI_OPTION, '' );
		$api = add_query_arg(
			array(
				'url'      => rawurlencode( $url ),
				'strategy' => $strategy,
				'category' => 'performance',
				'key'      => $key,
			),
			'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
		);
		$res = wp_remote_get( $api, array( 'timeout' => 60 ) );
		if ( is_wp_error( $res ) ) {
			return null;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( isset( $body['lighthouseResult']['categories']['performance']['score'] ) ) {
			return (int) round( $body['lighthouseResult']['categories']['performance']['score'] * 100 );
		}
		return null;
	}

	/* ------------------------------------------------------------------ */
	/* Admin page                                                          */
	/* ------------------------------------------------------------------ */
	public function page() {
		$url = '';
		if ( isset( $_GET['audit_url'] ) ) {
			$url = esc_url_raw( wp_unslash( $_GET['audit_url'] ) );
		}
		$run = isset( $_GET['run'] ) && check_admin_referer( 'dtp_audit' );
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Page Audit', 'dev-toolkit-pro' ); ?></h1>

			<div class="dt-card">
				<h2><?php esc_html_e( 'Choose a page to audit', 'dev-toolkit-pro' ); ?></h2>
				<form method="get" action="">
					<input type="hidden" name="page" value="dev-toolkit-audit">
					<input type="hidden" name="run" value="1">
					<?php wp_nonce_field( 'dtp_audit' ); ?>
					<select name="audit_url" style="min-width:340px">
						<option value="<?php echo esc_attr( home_url( '/' ) ); ?>"><?php esc_html_e( '— Home —', 'dev-toolkit-pro' ); ?></option>
						<?php
						$pages = get_posts( array( 'post_type' => array( 'page', 'post' ), 'numberposts' => 100, 'post_status' => 'publish' ) );
						foreach ( $pages as $p ) {
							printf( '<option value="%s"%s>%s</option>', esc_url( get_permalink( $p ) ), selected( $url, get_permalink( $p ), false ), esc_html( get_the_title( $p ) ) );
						}
						?>
					</select>
					<?php submit_button( __( 'Run audit', 'dev-toolkit-pro' ), 'primary', 'submit', false ); ?>
				</form>
			</div>

			<?php if ( $run && $url ) :
				$scan = $this->scan_url( $url ); ?>
				<div class="dt-card">
					<h2><?php printf( esc_html__( 'On-page checks — %s', 'dev-toolkit-pro' ), esc_html( $url ) ); ?></h2>
					<?php if ( ! empty( $scan['error'] ) ) : ?>
						<p class="dt-fail"><?php echo esc_html( $scan['error'] ); ?></p>
					<?php else : ?>
						<ul class="dt-audit">
							<?php foreach ( $scan['checks'] as $c ) : ?>
								<li class="dt-audit__item dt-<?php echo esc_attr( $c['status'] ); ?>">
									<span class="dt-badge"><?php echo esc_html( strtoupper( $c['status'] ) ); ?></span>
									<strong><?php echo esc_html( $c['label'] ); ?></strong>
									<span class="dt-note"><?php echo esc_html( $c['note'] ); ?></span>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>

				<div class="dt-card">
					<h2><?php esc_html_e( 'PageSpeed Insights', 'dev-toolkit-pro' ); ?></h2>
					<?php
					$key = get_option( self::PSI_OPTION, '' );
					if ( ! $key ) : ?>
						<p class="description"><?php esc_html_e( 'Add a free Google PageSpeed Insights API key below to fetch scores.', 'dev-toolkit-pro' ); ?></p>
					<?php else :
						$m = $this->pagespeed( $url, 'mobile' );
						$d = $this->pagespeed( $url, 'desktop' );
						?>
						<p>
							<strong><?php esc_html_e( 'Mobile:', 'dev-toolkit-pro' ); ?></strong>
							<span class="dt-badge <?php echo ( null !== $m && $m >= 65 ) ? 'dt-ok' : 'dt-bad'; ?>" style="background:<?php echo ( null !== $m && $m >= 65 ) ? '#1a9d5a' : '#c92c2c'; ?>">
								<?php echo null === $m ? '—' : esc_html( $m ); ?>
							</span> <?php esc_html_e( '(target 65+)', 'dev-toolkit-pro' ); ?>
							&nbsp;&nbsp;
							<strong><?php esc_html_e( 'Desktop:', 'dev-toolkit-pro' ); ?></strong>
							<span class="dt-badge" style="background:<?php echo ( null !== $d && $d >= 70 ) ? '#1a9d5a' : '#c92c2c'; ?>">
								<?php echo null === $d ? '—' : esc_html( $d ); ?>
							</span> <?php esc_html_e( '(target 70+)', 'dev-toolkit-pro' ); ?>
						</p>
					<?php endif; ?>

					<form method="post" action="options.php">
						<?php settings_fields( 'dtp_psi_group' ); ?>
						<input type="text" name="<?php echo esc_attr( self::PSI_OPTION ); ?>" value="<?php echo esc_attr( get_option( self::PSI_OPTION, '' ) ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'PageSpeed API key', 'dev-toolkit-pro' ); ?>">
						<?php submit_button( __( 'Save key', 'dev-toolkit-pro' ), 'secondary', 'submit', false ); ?>
					</form>
				</div>

				<div class="dt-card">
					<h2><?php esc_html_e( 'Responsive check', 'dev-toolkit-pro' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Each device width from the process doc is loaded in a frame. Horizontal overflow is flagged automatically for same-site pages; use the previews to eyeball layout.', 'dev-toolkit-pro' ); ?></p>
					<div id="dtp-responsive" data-url="<?php echo esc_attr( $url ); ?>"></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
