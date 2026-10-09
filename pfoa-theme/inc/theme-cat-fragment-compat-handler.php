<?php
/**
 * Theme-owned Cat Profile fragment compatibility handler (INACTIVE).
 *
 * INACTIVE - not wired: never required by functions.php, no hooks, no output.
 * No query_vars, no template_redirect, no header()/echo/exit/wp_die in this
 * file beyond the ABSPATH guard. Pure function only: validates method/slug,
 * resolves the pfoa_cat post, reads neutral schema 1.0 profile data,
 * enforces publication.complete, and delegates markup to
 * pfoa_theme_render_cat_profile_compat().
 * A future single dispatcher owns HTTP emission, no-cache headers, and
 * termination; this handler only returns array('status'=>int,'body'=>string).
 *
 * Mirrors plugin pfoa_cat_serve_fragment() legacy behavior (GET-only, strict
 * slug, publish check, complete check, h2 detail) via neutral data only.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_FRAGMENT_COMPAT_HANDLER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_FRAGMENT_COMPAT_HANDLER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_handle_cat_fragment_compat' ) ) {
	/**
	 * Resolve a cat fragment compat response without side effects.
	 *
	 * Fail-closed ordering: method, slug shape, dependency presence, post
	 * resolution and visibility, neutral data shape, publication
	 * completeness, then delegated h2 rendering. Never touches hooks,
	 * storage, or the response; the future dispatcher decides emission.
	 *
	 * @param string $method Request method, only 'GET' accepted.
	 * @param string $slug   Cat slug, strict lowercase slug shape.
	 * @return array{status:int,body:string} Status with fragment HTML or ''.
	 */
	function pfoa_theme_handle_cat_fragment_compat( string $method, string $slug ): array {
		if ( 'GET' !== $method ) {
			return array( 'status' => 405, 'body' => '' );
		}

		if ( '' === $slug || ! preg_match( '/^[a-z0-9-]+$/D', $slug ) ) {
			return array( 'status' => 404, 'body' => '' );
		}

		if ( ! function_exists( 'pfoa_cat_get_profile_data' ) || ! function_exists( 'pfoa_theme_render_cat_profile_compat' ) || ! function_exists( 'get_page_by_path' ) ) {
			return array( 'status' => 503, 'body' => '' );
		}

		$post_type = defined( 'PFOA_CAT_POST_TYPE' ) ? PFOA_CAT_POST_TYPE : 'pfoa_cat';
		$post      = get_page_by_path( $slug, OBJECT, $post_type );

		if ( ! ( $post instanceof WP_Post ) ) {
			return array( 'status' => 404, 'body' => '' );
		}

		if ( $post->post_type !== $post_type ) {
			return array( 'status' => 404, 'body' => '' );
		}

		if ( 'publish' !== $post->post_status ) {
			return array( 'status' => 404, 'body' => '' );
		}

		$data = pfoa_cat_get_profile_data( (int) $post->ID );

		if ( ! is_array( $data ) ) {
			return array( 'status' => 404, 'body' => '' );
		}

		if ( ! isset( $data['schema_version'] ) || '1.0' !== $data['schema_version'] ) {
			return array( 'status' => 503, 'body' => '' );
		}

		if ( ! isset( $data['post_id'] ) || (int) $data['post_id'] !== (int) $post->ID || (int) $data['post_id'] <= 0 ) {
			return array( 'status' => 503, 'body' => '' );
		}

		if ( ! isset( $data['post_type'] ) || 'pfoa_cat' !== $data['post_type'] ) {
			return array( 'status' => 503, 'body' => '' );
		}

		if ( ! isset( $data['post_status'] ) || 'publish' !== $data['post_status'] ) {
			return array( 'status' => 503, 'body' => '' );
		}

		$publication = isset( $data['publication'] ) && is_array( $data['publication'] ) ? $data['publication'] : array();

		if ( ! isset( $publication['complete'] ) || true !== $publication['complete'] ) {
			return array( 'status' => 404, 'body' => '' );
		}

		$html = pfoa_theme_render_cat_profile_compat( $data, 'h2' );

		if ( ! is_string( $html ) || '' === trim( $html ) ) {
			return array( 'status' => 503, 'body' => '' );
		}

		return array( 'status' => 200, 'body' => $html );
	}
}
