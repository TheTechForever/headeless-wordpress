<?php
/**
 * Thin client for AI-assisted review. Calls the Anthropic Messages API with
 * the site owner's own API key. Given design + current-build images and the
 * automated audit findings, it asks the model for a prioritised list of
 * discrepancies and suggested CSS fixes.
 *
 * IMPORTANT: this returns SUGGESTIONS. It never writes to theme files by
 * itself — the developer reviews output and applies it deliberately.
 *
 * @package DevToolkitPro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DTP_AI {

	const KEY_OPTION   = 'dtp_ai_key';
	const MODEL_OPTION = 'dtp_ai_model';
	const ENDPOINT     = 'https://api.anthropic.com/v1/messages';

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

	public function register() {
		register_setting( 'dtp_ai_group', self::KEY_OPTION, array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'dtp_ai_group', self::MODEL_OPTION, array( 'sanitize_callback' => 'sanitize_text_field' ) );
	}

	public static function is_configured() {
		return (bool) get_option( self::KEY_OPTION );
	}

	public static function model() {
		$m = get_option( self::MODEL_OPTION );
		return $m ? $m : 'claude-sonnet-4-5';
	}

	/**
	 * Build an image content block from an attachment ID (base64).
	 */
	private function image_block( $attachment_id, $label ) {
		$file = get_attached_file( $attachment_id );
		if ( ! $file || ! file_exists( $file ) ) {
			return null;
		}
		$type = get_post_mime_type( $attachment_id );
		if ( ! in_array( $type, array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ), true ) ) {
			return null;
		}
		// Skip very large files to stay within request limits.
		if ( filesize( $file ) > 4 * MB_IN_BYTES ) {
			return array(
				array( 'type' => 'text', 'text' => $label . ' (image too large to send; describe manually)' ),
			);
		}
		$data = base64_encode( file_get_contents( $file ) ); // phpcs:ignore
		return array(
			array( 'type' => 'text', 'text' => $label ),
			array(
				'type'   => 'image',
				'source' => array(
					'type'       => 'base64',
					'media_type' => $type,
					'data'       => $data,
				),
			),
		);
	}

	/**
	 * Run a review. $design_id and $current_id are attachment IDs (either may
	 * be 0). $audit is an array of check rows. Returns the model's text or a
	 * WP_Error.
	 */
	public function review( $design_id, $current_id, $audit, $notes = '' ) {
		$key = get_option( self::KEY_OPTION );
		if ( ! $key ) {
			return new WP_Error( 'no_key', __( 'No AI API key configured.', 'dev-toolkit-pro' ) );
		}

		$content = array();
		$content[] = array(
			'type' => 'text',
			'text' => "You are a senior front-end QA reviewer. Compare the intended DESIGN with the CURRENT BUILD and the automated audit findings below. Return a concise, prioritised list of what needs to change (High/Medium/Low). For layout/style issues, include a ready-to-paste CSS snippet where you can. Do not invent elements you cannot see. If something can't be judged from the images, say so.",
		);

		if ( $design_id ) {
			$blk = $this->image_block( $design_id, 'DESIGN (intended layout):' );
			if ( $blk ) { $content = array_merge( $content, $blk ); }
		}
		if ( $current_id ) {
			$blk = $this->image_block( $current_id, 'CURRENT BUILD (screenshot of live page):' );
			if ( $blk ) { $content = array_merge( $content, $blk ); }
		}

		$audit_text = "AUTOMATED AUDIT FINDINGS:\n";
		foreach ( (array) $audit as $c ) {
			$audit_text .= sprintf( "- [%s] %s — %s\n", strtoupper( $c['status'] ), $c['label'], $c['note'] );
		}
		if ( $notes ) {
			$audit_text .= "\nDEVELOPER NOTES:\n" . $notes;
		}
		$content[] = array( 'type' => 'text', 'text' => $audit_text );

		$res = wp_remote_post( self::ENDPOINT, array(
			'timeout' => 90,
			'headers' => array(
				'content-type'      => 'application/json',
				'x-api-key'         => $key,
				'anthropic-version' => '2023-06-01',
			),
			'body'    => wp_json_encode( array(
				'model'      => self::model(),
				'max_tokens' => 2000,
				'messages'   => array(
					array( 'role' => 'user', 'content' => $content ),
				),
			) ),
		) );

		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( isset( $body['error'] ) ) {
			return new WP_Error( 'api_error', $body['error']['message'] ?? __( 'API error.', 'dev-toolkit-pro' ) );
		}
		if ( empty( $body['content'] ) ) {
			return new WP_Error( 'empty', __( 'No response from the model.', 'dev-toolkit-pro' ) );
		}

		$text = '';
		foreach ( $body['content'] as $blk ) {
			if ( isset( $blk['type'] ) && 'text' === $blk['type'] ) {
				$text .= $blk['text'];
			}
		}
		return $text;
	}
}
