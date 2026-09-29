<?php
/**
 * Design QA. Store a design reference per page (uploaded design image +
 * optional screenshot of the current build), overlay the design on the live
 * page to spot differences, run the automated audit, and request an
 * AI-assisted review that lists what to fix.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_Design_QA {

	const CPT = 'dtp_design_ref';

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
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'admin_menu', array( $this, 'menu' ), 19 );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_post_dtp_save_ref', array( $this, 'save_ref' ) );
		add_action( 'admin_post_dtp_delete_ref', array( $this, 'delete_ref' ) );
		add_action( 'admin_post_dtp_ai_review', array( $this, 'run_ai_review' ) );
	}

	public function register_cpt() {
		register_post_type( self::CPT, array(
			'label'    => __( 'Design References', 'dev-toolkit-pro' ),
			'public'   => false,
			'show_ui'  => false,
			'supports' => array( 'title' ),
		) );
	}

	public function assets( $hook ) {
		if ( false === strpos( (string) $hook, 'dev-toolkit-design' ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_script( 'dtp-overlay', DTP_URL . 'assets/overlay.js', array( 'jquery' ), DTP_VERSION, true );
		wp_enqueue_style( 'dtp-design', DTP_URL . 'assets/design-qa.css', array(), DTP_VERSION );
	}

	public function menu() {
		add_submenu_page(
			'dev-toolkit',
			__( 'Design QA', 'dev-toolkit-pro' ),
			__( 'Design QA', 'dev-toolkit-pro' ),
			'manage_options',
			'dev-toolkit-design',
			array( $this, 'router' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Router: list / edit / compare                                       */
	/* ------------------------------------------------------------------ */
	public function router() {
		$view = isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : 'list';
		$id   = isset( $_GET['ref'] ) ? absint( $_GET['ref'] ) : 0;

		echo '<div class="wrap dt-wrap">';
		if ( 'edit' === $view ) {
			$this->screen_edit( $id );
		} elseif ( 'compare' === $view && $id ) {
			$this->screen_compare( $id );
		} else {
			$this->screen_list();
		}
		echo '</div>';
	}

	private function screen_list() {
		$refs = get_posts( array( 'post_type' => self::CPT, 'numberposts' => -1, 'post_status' => 'any' ) );
		$new  = add_query_arg( array( 'page' => 'dev-toolkit-design', 'view' => 'edit' ), admin_url( 'admin.php' ) );
		?>
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Design QA', 'dev-toolkit-pro' ); ?></h1>
		<a href="<?php echo esc_url( $new ); ?>" class="page-title-action"><?php esc_html_e( 'Add reference', 'dev-toolkit-pro' ); ?></a>

		<div class="dtp-help-strip">
			<strong><?php esc_html_e( 'How it works:', 'dev-toolkit-pro' ); ?></strong>
			<?php esc_html_e( '1) Add a reference per page (design image + the live URL). 2) Open Compare to overlay the design on the live page and spot differences. 3) Run the audit + AI review to get a fix-list.', 'dev-toolkit-pro' ); ?>
		</div>

		<?php if ( ! $refs ) : ?>
			<p><?php esc_html_e( 'No references yet. Add your first page design above.', 'dev-toolkit-pro' ); ?></p>
		<?php else : ?>
			<table class="widefat striped" style="margin-top:12px">
				<thead><tr>
					<th><?php esc_html_e( 'Page', 'dev-toolkit-pro' ); ?></th>
					<th><?php esc_html_e( 'Live URL', 'dev-toolkit-pro' ); ?></th>
					<th><?php esc_html_e( 'Design', 'dev-toolkit-pro' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'dev-toolkit-pro' ); ?></th>
				</tr></thead>
				<tbody>
				<?php foreach ( $refs as $r ) :
					$url     = get_post_meta( $r->ID, 'live_url', true );
					$design  = (int) get_post_meta( $r->ID, 'design_id', true );
					$thumb   = $design ? wp_get_attachment_image( $design, array( 60, 60 ) ) : '—';
					$compare = add_query_arg( array( 'page' => 'dev-toolkit-design', 'view' => 'compare', 'ref' => $r->ID ), admin_url( 'admin.php' ) );
					$edit    = add_query_arg( array( 'page' => 'dev-toolkit-design', 'view' => 'edit', 'ref' => $r->ID ), admin_url( 'admin.php' ) );
					$del     = wp_nonce_url( add_query_arg( array( 'action' => 'dtp_delete_ref', 'ref' => $r->ID ), admin_url( 'admin-post.php' ) ), 'dtp_delete_ref_' . $r->ID );
					?>
					<tr>
						<td><strong><?php echo esc_html( get_the_title( $r ) ); ?></strong></td>
						<td><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $url ); ?></a></td>
						<td><?php echo wp_kses_post( $thumb ); ?></td>
						<td>
							<a class="button button-primary" href="<?php echo esc_url( $compare ); ?>"><?php esc_html_e( 'Compare', 'dev-toolkit-pro' ); ?></a>
							<a class="button" href="<?php echo esc_url( $edit ); ?>"><?php esc_html_e( 'Edit', 'dev-toolkit-pro' ); ?></a>
							<a class="button-link-delete" href="<?php echo esc_url( $del ); ?>" onclick="return confirm('Delete this reference?')"><?php esc_html_e( 'Delete', 'dev-toolkit-pro' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif;
	}

	private function screen_edit( $id ) {
		$title  = $id ? get_the_title( $id ) : '';
		$url    = $id ? get_post_meta( $id, 'live_url', true ) : '';
		$design = $id ? (int) get_post_meta( $id, 'design_id', true ) : 0;
		$shot   = $id ? (int) get_post_meta( $id, 'screenshot_id', true ) : 0;
		$notes  = $id ? get_post_meta( $id, 'notes', true ) : '';
		?>
		<h1><?php echo $id ? esc_html__( 'Edit reference', 'dev-toolkit-pro' ) : esc_html__( 'Add reference', 'dev-toolkit-pro' ); ?></h1>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'dtp_save_ref' ); ?>
			<input type="hidden" name="action" value="dtp_save_ref">
			<input type="hidden" name="ref" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table" role="presentation">
				<tr><th><?php esc_html_e( 'Page name', 'dev-toolkit-pro' ); ?></th>
					<td><input type="text" name="title" value="<?php echo esc_attr( $title ); ?>" class="regular-text" required placeholder="e.g. Home, About, Contact"></td></tr>
				<tr><th><?php esc_html_e( 'Live URL', 'dev-toolkit-pro' ); ?></th>
					<td><input type="url" name="live_url" value="<?php echo esc_attr( $url ); ?>" class="regular-text" required placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>"></td></tr>

				<tr><th><?php esc_html_e( 'Design image', 'dev-toolkit-pro' ); ?></th>
					<td>
						<?php $this->media_field( 'design_id', $design ); ?>
						<p class="description"><?php esc_html_e( 'Upload the exported design (PNG/JPG). Export each Figma frame or PDF page as an image and add one reference per page.', 'dev-toolkit-pro' ); ?></p>
					</td></tr>

				<tr><th><?php esc_html_e( 'Current build screenshot', 'dev-toolkit-pro' ); ?></th>
					<td>
						<?php $this->media_field( 'screenshot_id', $shot ); ?>
						<p class="description"><?php esc_html_e( 'Optional. A screenshot of the live page lets the AI review compare design vs build directly.', 'dev-toolkit-pro' ); ?></p>
					</td></tr>

				<tr><th><?php esc_html_e( 'Notes', 'dev-toolkit-pro' ); ?></th>
					<td><textarea name="notes" rows="3" class="large-text"><?php echo esc_textarea( $notes ); ?></textarea></td></tr>
			</table>
			<?php submit_button( __( 'Save reference', 'dev-toolkit-pro' ) ); ?>
		</form>
		<?php
	}

	/**
	 * A media-library picker field (uses wp.media via overlay.js).
	 */
	private function media_field( $name, $attachment_id ) {
		$img = $attachment_id ? wp_get_attachment_image( $attachment_id, array( 120, 120 ) ) : '';
		printf(
			'<div class="dtp-media" data-target="%1$s">
				<div class="dtp-media__preview">%2$s</div>
				<input type="hidden" name="%1$s" value="%3$s">
				<button type="button" class="button dtp-media__pick">%4$s</button>
				<button type="button" class="button dtp-media__clear">%5$s</button>
			</div>',
			esc_attr( $name ),
			$img,
			esc_attr( $attachment_id ),
			esc_html__( 'Select / upload', 'dev-toolkit-pro' ),
			esc_html__( 'Clear', 'dev-toolkit-pro' )
		);
	}

	private function screen_compare( $id ) {
		$url    = get_post_meta( $id, 'live_url', true );
		$design = (int) get_post_meta( $id, 'design_id', true );
		$design_src = $design ? wp_get_attachment_image_url( $design, 'full' ) : '';
		$back   = add_query_arg( array( 'page' => 'dev-toolkit-design' ), admin_url( 'admin.php' ) );

		// Reuse the automated audit for this URL.
		$audit = method_exists( 'DTP_Page_Audit', 'instance' ) ? $this->quick_audit( $url ) : array();
		?>
		<h1><?php printf( esc_html__( 'Compare — %s', 'dev-toolkit-pro' ), esc_html( get_the_title( $id ) ) ); ?>
			<a href="<?php echo esc_url( $back ); ?>" class="page-title-action"><?php esc_html_e( 'Back', 'dev-toolkit-pro' ); ?></a>
		</h1>

		<div class="dt-card">
			<h2><?php esc_html_e( 'Overlay comparison', 'dev-toolkit-pro' ); ?></h2>
			<p class="description"><?php esc_html_e( 'The design is overlaid on the live page. Drag the opacity slider to fade between them and spot spacing, size and colour differences.', 'dev-toolkit-pro' ); ?></p>
			<div class="dtp-overlay-controls">
				<label><?php esc_html_e( 'Design opacity', 'dev-toolkit-pro' ); ?>
					<input type="range" id="dtp-op" min="0" max="100" value="50">
				</label>
				<label><?php esc_html_e( 'Offset Y', 'dev-toolkit-pro' ); ?>
					<input type="number" id="dtp-offy" value="0" step="1"> px
				</label>
			</div>
			<div class="dtp-overlay" data-url="<?php echo esc_attr( $url ); ?>" data-design="<?php echo esc_attr( $design_src ); ?>">
				<iframe class="dtp-overlay__live" src="<?php echo esc_url( $url ); ?>"></iframe>
				<?php if ( $design_src ) : ?>
					<img class="dtp-overlay__design" src="<?php echo esc_url( $design_src ); ?>" alt="design overlay">
				<?php else : ?>
					<p><?php esc_html_e( 'No design image on this reference yet.', 'dev-toolkit-pro' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="dt-card">
			<h2><?php esc_html_e( 'AI review', 'dev-toolkit-pro' ); ?></h2>
			<?php if ( ! DTP_AI::is_configured() ) : ?>
				<p class="description"><?php esc_html_e( 'Add your Anthropic API key on the AI Settings screen to enable the review.', 'dev-toolkit-pro' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Sends the design image, the current-build screenshot (if set) and the audit findings to Claude, and returns a prioritised fix-list with suggested CSS. Output is a suggestion — review before applying.', 'dev-toolkit-pro' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'dtp_ai_review' ); ?>
					<input type="hidden" name="action" value="dtp_ai_review">
					<input type="hidden" name="ref" value="<?php echo esc_attr( $id ); ?>">
					<?php submit_button( __( 'Run AI review', 'dev-toolkit-pro' ), 'primary', 'submit', false ); ?>
				</form>
				<?php
				$review = get_transient( 'dtp_ai_review_' . $id );
				if ( $review ) : ?>
					<div class="dtp-ai-output"><?php echo wp_kses_post( wpautop( esc_html( $review ) ) ); ?></div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function quick_audit( $url ) {
		// Lightweight subset so Compare shows context without a full re-render.
		$out = array();
		$res = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => false ) );
		if ( is_wp_error( $res ) ) {
			return array( array( 'status' => 'fail', 'label' => 'Fetch page', 'note' => $res->get_error_message() ) );
		}
		$html = wp_remote_retrieve_body( $res );
		$out[] = array( 'status' => ( substr_count( $html, '<h1' ) === 1 ) ? 'pass' : 'warn', 'label' => 'H1 count', 'note' => substr_count( $html, '<h1' ) . ' found' );
		$out[] = array( 'status' => ( false !== stripos( $html, 'name="viewport"' ) ) ? 'pass' : 'fail', 'label' => 'Viewport meta', 'note' => '' );
		return $out;
	}

	/* ------------------------------------------------------------------ */
	/* Handlers                                                            */
	/* ------------------------------------------------------------------ */
	public function save_ref() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'no' ); }
		check_admin_referer( 'dtp_save_ref' );

		$id    = isset( $_POST['ref'] ) ? absint( $_POST['ref'] ) : 0;
		$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$data  = array( 'post_type' => self::CPT, 'post_status' => 'publish', 'post_title' => $title );

		if ( $id ) {
			$data['ID'] = $id;
			wp_update_post( $data );
		} else {
			$id = wp_insert_post( $data );
		}

		update_post_meta( $id, 'live_url', isset( $_POST['live_url'] ) ? esc_url_raw( wp_unslash( $_POST['live_url'] ) ) : '' );
		update_post_meta( $id, 'design_id', isset( $_POST['design_id'] ) ? absint( $_POST['design_id'] ) : 0 );
		update_post_meta( $id, 'screenshot_id', isset( $_POST['screenshot_id'] ) ? absint( $_POST['screenshot_id'] ) : 0 );
		update_post_meta( $id, 'notes', isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '' );

		wp_safe_redirect( add_query_arg( array( 'page' => 'dev-toolkit-design' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function delete_ref() {
		$id = isset( $_GET['ref'] ) ? absint( $_GET['ref'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! $id ) { wp_die( 'no' ); }
		check_admin_referer( 'dtp_delete_ref_' . $id );
		wp_delete_post( $id, true );
		wp_safe_redirect( add_query_arg( array( 'page' => 'dev-toolkit-design' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function run_ai_review() {
		$id = isset( $_POST['ref'] ) ? absint( $_POST['ref'] ) : 0;
		if ( ! current_user_can( 'manage_options' ) || ! $id ) { wp_die( 'no' ); }
		check_admin_referer( 'dtp_ai_review' );

		$design = (int) get_post_meta( $id, 'design_id', true );
		$shot   = (int) get_post_meta( $id, 'screenshot_id', true );
		$notes  = get_post_meta( $id, 'notes', true );
		$url    = get_post_meta( $id, 'live_url', true );

		$audit  = $this->quick_audit( $url );
		$result = DTP_AI::instance()->review( $design, $shot, $audit, $notes );

		$text = is_wp_error( $result ) ? '⚠ ' . $result->get_error_message() : $result;
		set_transient( 'dtp_ai_review_' . $id, $text, HOUR_IN_SECONDS );

		wp_safe_redirect( add_query_arg( array( 'page' => 'dev-toolkit-design', 'view' => 'compare', 'ref' => $id ), admin_url( 'admin.php' ) ) );
		exit;
	}
}
