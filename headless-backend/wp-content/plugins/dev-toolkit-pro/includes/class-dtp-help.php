<?php
/**
 * Getting Started / documentation screen and the AI settings screen. Keeps
 * the toolkit approachable: a single place that explains every feature and
 * where to configure keys.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Help {

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'menu' ), 18 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
	}

	public function assets( $hook ) {
		if ( false !== strpos( (string) $hook, 'dev-toolkit' ) ) {
			wp_enqueue_style( 'dtp-design', DTP_URL . 'assets/design-qa.css', array(), DTP_VERSION );
		}
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Getting Started', 'dev-toolkit-pro' ),
			__( 'Getting Started', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-help',
			array( $this, 'help_page' )
		);
		add_submenu_page(
			'dev-toolkit',
			__( 'AI Settings', 'dev-toolkit-pro' ),
			__( 'AI Settings', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-ai',
			array( $this, 'ai_page' )
		);
	}

	public function ai_page() {
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'AI Settings', 'dev-toolkit-pro' ); ?></h1>
			<div class="dtp-help-strip">
				<?php esc_html_e( 'The AI review uses your own Anthropic API key. Nothing is sent anywhere until you press “Run AI review”. Output is a suggestion — it never edits your files automatically.', 'dev-toolkit-pro' ); ?>
			</div>
			<form method="post" action="options.php">
				<?php settings_fields( 'dtp_ai_group' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><?php esc_html_e( 'Anthropic API key', 'dev-toolkit-pro' ); ?></th>
						<td>
							<input type="password" name="dtp_ai_key" value="<?php echo esc_attr( get_option( 'dtp_ai_key', '' ) ); ?>" class="regular-text" autocomplete="off">
							<p class="description"><?php esc_html_e( 'Get a key from the Anthropic Console. Stored in your database; keep it private.', 'dev-toolkit-pro' ); ?></p>
						</td></tr>
					<tr><th><?php esc_html_e( 'Model', 'dev-toolkit-pro' ); ?></th>
						<td>
							<input type="text" name="dtp_ai_model" value="<?php echo esc_attr( get_option( 'dtp_ai_model', 'claude-sonnet-4-5' ) ); ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Use any model string your key supports (from the Anthropic Console).', 'dev-toolkit-pro' ); ?></p>
						</td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function help_page() {
		$steps = array(
			array(
				'title' => __( '1. Run one-click setup', 'dev-toolkit-pro' ),
				'body'  => __( 'Setup & Audit → toggle the site defaults, then “Apply one-click setup”. This disables comments, zeroes media sizes and sets the timezone.', 'dev-toolkit-pro' ),
			),
			array(
				'title' => __( '2. Fill common fields', 'dev-toolkit-pro' ),
				'body'  => __( 'Common Fields → add email, phone, address and socials once, then use the shortcodes anywhere.', 'dev-toolkit-pro' ),
			),
			array(
				'title' => __( '3. Build the theme', 'dev-toolkit-pro' ),
				'body'  => __( 'Child Theme → generate a child theme. CSS Boilerplate → inject the container rules and copy the media-query stubs. Plugin Stack → install the baseline plugins.', 'dev-toolkit-pro' ),
			),
			array(
				'title' => __( '4. Add design references', 'dev-toolkit-pro' ),
				'body'  => __( 'Design QA → add one reference per page (export each Figma frame / PDF page as an image), set the live URL, optionally a screenshot of your build.', 'dev-toolkit-pro' ),
			),
			array(
				'title' => __( '5. Compare & audit', 'dev-toolkit-pro' ),
				'body'  => __( 'Open Compare to overlay the design on the live page. Page Audit → run on-page checks, PageSpeed and the responsive overflow check across every device width.', 'dev-toolkit-pro' ),
			),
			array(
				'title' => __( '6. AI review', 'dev-toolkit-pro' ),
				'body'  => __( 'From Compare, “Run AI review” returns a prioritised fix-list with suggested CSS. Review it, then apply changes yourself (e.g. via the CSS Boilerplate box or your theme).', 'dev-toolkit-pro' ),
			),
		);
		?>
		<div class="wrap dt-wrap">
			<h1><?php esc_html_e( 'Dev Toolkit — Getting Started', 'dev-toolkit-pro' ); ?></h1>

			<div class="dtp-steps">
				<?php foreach ( $steps as $s ) : ?>
					<div class="dtp-step">
						<h3><?php echo esc_html( $s['title'] ); ?></h3>
						<p><?php echo esc_html( $s['body'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dt-card">
				<h2><?php esc_html_e( 'Shortcodes', 'dev-toolkit-pro' ); ?></h2>
				<ul class="dtp-code-list">
					<li><code>[dt_email]</code> — <?php esc_html_e( 'mailto link (add link="false" for plain text)', 'dev-toolkit-pro' ); ?></li>
					<li><code>[dt_phone]</code> — <?php esc_html_e( 'tel: link', 'dev-toolkit-pro' ); ?></li>
					<li><code>[dt_address]</code> — <?php esc_html_e( 'address with line breaks', 'dev-toolkit-pro' ); ?></li>
					<li><code>[dt_social]</code> — <?php esc_html_e( 'list of social links (open in new tab)', 'dev-toolkit-pro' ); ?></li>
					<li><code>[dt_year start="2019"]</code> — <?php esc_html_e( 'dynamic copyright year', 'dev-toolkit-pro' ); ?></li>
				</ul>
			</div>

			<div class="dt-card">
				<h2><?php esc_html_e( 'What is automated vs. manual', 'dev-toolkit-pro' ); ?></h2>
				<p><strong><?php esc_html_e( 'Automated:', 'dev-toolkit-pro' ); ?></strong> <?php esc_html_e( 'setup defaults, shortcodes, mailto/tel, external links, aria-labels, exports, H1/viewport/alt/link-name checks, PageSpeed, horizontal-overflow detection.', 'dev-toolkit-pro' ); ?></p>
				<p><strong><?php esc_html_e( 'Human review:', 'dev-toolkit-pro' ); ?></strong> <?php esc_html_e( 'whether a layout looks right at each breakpoint, animation smoothness, hover effects, link-colour distinctness, and matching the design pixel-for-pixel (use the overlay + AI review to make this fast).', 'dev-toolkit-pro' ); ?></p>
			</div>
		</div>
		<?php
	}
}
