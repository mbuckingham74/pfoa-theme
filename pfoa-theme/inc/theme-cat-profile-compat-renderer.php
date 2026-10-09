<?php
/**
 * Theme-owned Cat Profile detail compat renderer (INACTIVE).
 *
 * INACTIVE - not required by functions.php, no hooks, no writes, no assets.
 * Consumes schema 1.0 neutral data only and reproduces the legacy detail
 * markup so the theme owns markup while the provider owns behaviour.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * Expected neutral input shape (schema 1.0):
 *   array(
 *     'schema_version'  => '1.0',
 *     'post_id'         => int,
 *     'post_type'       => 'pfoa_cat',
 *     'description_raw' => string,
 *     'record_status'   => string, // Raw status text, '' when none; never null.
 *     'publication'     => array( 'complete' => bool, 'templated' => bool ),
 *     'lifecycle'       => array( 'effectively_pending' => bool ),
 *     'youtube_id'      => string,
 *     'legacy'          => array(
 *       'status'  => string, // Duplicates top-level record_status.
 *       'members' => string[],
 *     )|null, // null = no grouped data: members omitted, status still from record_status.
 *   )
 *
 * Incomplete status is read from top-level record_status only ('' omits the
 * status line); members still come only from legacy when present.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_PROFILE_COMPAT_RENDERER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_PROFILE_COMPAT_RENDERER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_render_cat_profile_compat' ) ) {
	/**
	 * Render the compat detail from schema 1.0 neutral profile data.
	 *
	 * Defensive and side-effect free: validates schema, id, and type;
	 * emits only the detail wrapper (single/content shells stay with the
	 * calling template); incomplete records render title plus record_status
	 * when present plus grouped members when present plus a coming-soon note; complete records render title,
	 * pending badge, primary image, filtered description, delegated gallery,
	 * and validated video. Templated flag does not fork markup.
	 *
	 * @param array  $profile_data Neutral profile snapshot (schema 1.0).
	 * @param string $heading_tag  Heading tag, only h1/h2 honoured.
	 * @return string Compat detail HTML, or '' when nothing renderable.
	 */
	function pfoa_theme_render_cat_profile_compat( array $profile_data, string $heading_tag = 'h1' ): string {
		if ( ! isset( $profile_data['schema_version'] ) || '1.0' !== $profile_data['schema_version'] ) {
			return '';
		}

		$post_id = isset( $profile_data['post_id'] ) ? (int) $profile_data['post_id'] : 0;
		if ( $post_id <= 0 ) {
			return '';
		}

		if ( ! isset( $profile_data['post_type'] ) || 'pfoa_cat' !== $profile_data['post_type'] ) {
			return '';
		}

		$heading_tag = in_array( $heading_tag, array( 'h1', 'h2' ), true ) ? $heading_tag : 'h1';

		$slug_field = get_post_field( 'post_name', $post_id );
		$slug       = is_string( $slug_field ) ? $slug_field : '';

		$html = sprintf(
			'<div class="pfoa-cat-profile" data-pfoa-cat-profile="%s">',
			esc_attr( $slug )
		);

		$title = get_the_title( $post_id );

		$publication = isset( $profile_data['publication'] ) && is_array( $profile_data['publication'] )
			? $profile_data['publication']
			: array();
		$complete    = isset( $publication['complete'] ) ? (bool) $publication['complete'] : false;

		if ( ! $complete ) {
			$html .= sprintf(
				'<%1$s class="pfoa-cat-profile-title">%2$s</%1$s>',
				$heading_tag,
				esc_html( $title )
			);

		$record_status = isset( $profile_data['record_status'] ) ? (string) $profile_data['record_status'] : '';
		if ( '' !== $record_status ) {
			$html .= sprintf( '<p class="pfoa-cat-profile-status">%s</p>', esc_html( $record_status ) );
		}

		$legacy = isset( $profile_data['legacy'] ) && is_array( $profile_data['legacy'] )
			? $profile_data['legacy']
			: null;

		if ( null !== $legacy ) {
			$members = isset( $legacy['members'] ) && is_array( $legacy['members'] )
				? $legacy['members']
				: array();

				if ( array() !== $members ) {
					$html .= '<ul class="pfoa-cat-profile-members" aria-label="' . esc_attr__( 'Members', 'pfoa-theme' ) . '">';
					foreach ( $members as $member ) {
						$html .= sprintf( '<li>%s</li>', esc_html( (string) $member ) );
					}
					$html .= '</ul>';
				}
			}

			$html .= '<p class="pfoa-cat-profile-note">' . esc_html__( 'Full profile coming soon.', 'pfoa-theme' ) . '</p>';
			$html .= '</div>';

			return $html;
		}

		$html .= sprintf(
			'<%1$s class="pfoa-cat-profile-title">%2$s</%1$s>',
			$heading_tag,
			esc_html( $title )
		);

		$lifecycle = isset( $profile_data['lifecycle'] ) && is_array( $profile_data['lifecycle'] )
			? $profile_data['lifecycle']
			: array();

		if ( isset( $lifecycle['effectively_pending'] ) && true === $lifecycle['effectively_pending'] ) {
			$html .= '<p class="pfoa-cat-profile-status pfoa-cat-profile-status-pending">' . esc_html__( 'Adoption pending', 'pfoa-theme' ) . '</p>';
		}

		$image = (string) get_the_post_thumbnail(
			$post_id,
			'large',
			array( 'alt' => get_the_title( $post_id ) )
		);
		if ( '' !== $image ) {
			$html .= '<div class="pfoa-cat-profile-image">' . $image . '</div>';
		}

		$description_raw = isset( $profile_data['description_raw'] ) ? (string) $profile_data['description_raw'] : '';
		$filtered        = apply_filters( 'the_content', $description_raw );
		$filtered_string = is_string( $filtered ) ? $filtered : '';

		$out = preg_replace_callback(
			'/<p(?=[^>]*\bclass\s*=\s*["\'][^"\']*\bpfoa-cat-special-needs\b[^"\']*["\'])[^>]*>\s*<strong[^>]*>\s*Special needs:\s*<\/strong>\s*(.*?)<\/p>/is',
			function ( $m ) {
				$inner = isset( $m[1] ) ? trim( (string) $m[1] ) : '';
				$plain = trim( html_entity_decode( strip_tags( $inner ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
				if ( '' === $plain ) {
					return '';
				}
				return '<p class="pfoa-cat-special-needs"><strong>Special needs:</strong><br>' . $inner . '</p>';
			},
			$filtered_string
		);
		$description     = is_string( $out ) ? $out : $filtered_string;

		if ( '' !== trim( $description ) ) {
			$html .= '<div class="pfoa-cat-profile-description">' . $description . '</div>';
		}

		if ( function_exists( 'pfoa_theme_render_cat_gallery_compat' ) ) {
			$html .= pfoa_theme_render_cat_gallery_compat( $profile_data );
		}

		$youtube_id = isset( $profile_data['youtube_id'] ) ? (string) $profile_data['youtube_id'] : '';
		if ( 1 === preg_match( '/^[A-Za-z0-9_-]{11}$/', $youtube_id ) ) {
			$embed = function_exists( 'wp_oembed_get' )
				? wp_oembed_get( 'https://www.youtube.com/watch?v=' . $youtube_id )
				: false;
			if ( is_string( $embed ) && '' !== trim( $embed ) ) {
				$html .= '<div class="pfoa-cat-profile-video">' . $embed . '</div>';
			}
		}

		$html .= '</div>';

		return $html;
	}
}
