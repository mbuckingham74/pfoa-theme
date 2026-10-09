<?php
/**
 * Theme-owned Cat Profile listing card compat renderer (INACTIVE).
 *
 * INACTIVE - not required by functions.php, no hooks, no writes, no assets.
 * Consumes schema 1.0 neutral data only and reproduces the legacy per-profile
 * card markup so the theme owns markup while the provider owns behaviour.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * Expected neutral input shape (schema 1.0):
 *   array(
 *     'schema_version' => '1.0',
 *     'post_id'        => int,
 *     'post_type'      => 'pfoa_cat',
 *     'record_status'  => string, // Raw status text, '' when none; never null.
 *     'publication'    => array(
 *       'complete'               => bool,
 *       'has_required_card_image' => bool, // False = nothing renderable.
 *     ),
 *     'card_image_id'  => int, // Single-cat Card Image; rendered via the
 *                              // post thumbnail, never read directly here.
 *     'legacy'         => array(
 *       'members'                   => string[], // Stored order, verbatim.
 *       'card_labels'               => string[], // Explicit labels, verbatim.
 *       'resolved_member_card_ids'  => int[],    // Stored order, verbatim.
 *     )|null, // null = single-cat listing: exactly one card from the Card Image.
 *   )
 *
 * Single-cat status comes from top-level record_status only ('' omits the
 * status line); multi-cat cards never render a status line. Listing
 * membership, ordering across profiles, and bonded composition stay with the
 * caller; this renderer outputs cards for one profile snapshot only and never
 * infers bonded state from a two-member grouping.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_CARD_COMPAT_RENDERER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_CARD_COMPAT_RENDERER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_render_cat_card_compat' ) ) {
	/**
	 * Render per-profile listing cards from schema 1.0 neutral profile data.
	 *
	 * Defensive and side-effect free: validates schema, id, and type, then
	 * mirrors the legacy branches exactly. Single-cat listings emit exactly
	 * one card from the chosen Card Image ('' when the thumbnail is missing);
	 * multi-cat listings emit one card per stored member in stored order,
	 * skipping members without a valid card attachment ('' when none
	 * remain). Complete cards link media and title to the canonical
	 * permalink with dialog attributes; incomplete cards render plain
	 * markup with a coming-soon note and no links or dialog attributes.
	 * Exactly two stored members add pair-left/pair-right classes.
	 *
	 * @param array $profile_data Neutral profile snapshot (schema 1.0).
	 * @return string Zero or more <article class="pfoa-cat-card..."> elements.
	 */
	function pfoa_theme_render_cat_card_compat( array $profile_data ): string {
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

		$publication = isset( $profile_data['publication'] ) && is_array( $profile_data['publication'] )
			? $profile_data['publication']
			: array();
		$complete    = isset( $publication['complete'] ) ? (bool) $publication['complete'] : false;

		if ( array_key_exists( 'has_required_card_image', $publication ) && false === (bool) $publication['has_required_card_image'] ) {
			return '';
		}

		$legacy = isset( $profile_data['legacy'] ) && is_array( $profile_data['legacy'] )
			? $profile_data['legacy']
			: null;

		$stored_members = array();
		if ( null !== $legacy && isset( $legacy['members'] ) && is_array( $legacy['members'] ) ) {
			foreach ( $legacy['members'] as $member ) {
				$stored_members[] = (string) $member;
			}
		}

		$card_labels = array();
		if ( null !== $legacy && isset( $legacy['card_labels'] ) && is_array( $legacy['card_labels'] ) ) {
			$card_labels = array_values( $legacy['card_labels'] );
		}

		$record_status = isset( $profile_data['record_status'] ) ? (string) $profile_data['record_status'] : '';
		$status        = '' !== $record_status
			? '<p class="pfoa-cat-card-status">' . esc_html( $record_status ) . '</p>'
			: '';

		$slug_field = get_post_field( 'post_name', $post_id );
		$slug       = is_string( $slug_field ) ? $slug_field : '';

		$title = get_the_title( $post_id );

		if ( array() === $stored_members ) {
			$thumbnail = get_the_post_thumbnail(
				$post_id,
				'medium',
				array( 'class' => 'pfoa-cat-card-thumb' )
			);
			$thumbnail = is_string( $thumbnail ) ? $thumbnail : '';
			if ( '' === $thumbnail ) {
				return '';
			}

			$display = pfoa_theme_card_compat_label_for_index( $card_labels, 0, (string) $title );

			if ( $complete ) {
				$url  = (string) get_permalink( $post_id );
				$html = '<article class="pfoa-cat-card" data-pfoa-profile-id="' . esc_attr( (string) $post_id ) . '">';
				$html .= sprintf(
					'<a class="pfoa-cat-card-media" href="%s" data-pfoa-cat-dialog="%s">%s</a>',
					esc_url( $url ),
					esc_attr( $slug ),
					$thumbnail
				);
				$html .= sprintf(
					'<h3 class="pfoa-cat-card-title"><a href="%s" data-pfoa-cat-dialog="%s">%s</a></h3>',
					esc_url( $url ),
					esc_attr( $slug ),
					esc_html( $display )
				);
				$html .= $status;
				$html .= '</article>';

				return $html;
			}

			$html = '<article class="pfoa-cat-card pfoa-cat-card-incomplete" data-pfoa-profile-id="' . esc_attr( (string) $post_id ) . '">';
			$html .= '<div class="pfoa-cat-card-media">' . $thumbnail . '</div>';
			$html .= '<h3 class="pfoa-cat-card-title">' . esc_html( $display ) . '</h3>';
			$html .= $status;
			$html .= '<p class="pfoa-cat-card-note">' . esc_html__( 'Full profile coming soon.', 'pfoa-theme' ) . '</p>';
			$html .= '</article>';

			return $html;
		}

		$stored_card_ids = array();
		if ( null !== $legacy && isset( $legacy['resolved_member_card_ids'] ) && is_array( $legacy['resolved_member_card_ids'] ) ) {
			$stored_card_ids = array_values( $legacy['resolved_member_card_ids'] );
		}

		$url     = $complete ? (string) get_permalink( $post_id ) : '';
		$is_pair = 2 === count( $stored_members );
		$html    = '';

		foreach ( $stored_members as $index => $member_name ) {
			$attachment_id = isset( $stored_card_ids[ $index ] ) ? (int) $stored_card_ids[ $index ] : 0;
			if ( $attachment_id <= 0 || get_post_type( $attachment_id ) !== 'attachment' ) {
				continue;
			}

			$thumbnail = wp_get_attachment_image(
				$attachment_id,
				'medium',
				false,
				array( 'class' => 'pfoa-cat-card-thumb' )
			);
			if ( ! is_string( $thumbnail ) || '' === $thumbnail ) {
				continue;
			}

			$pair_class = $is_pair ? ( 0 === (int) $index ? ' pfoa-cat-pair-left' : ' pfoa-cat-pair-right' ) : '';
			$display    = pfoa_theme_card_compat_label_for_index( $card_labels, (int) $index, (string) $member_name );

			if ( $complete ) {
				$html .= '<article class="pfoa-cat-card' . $pair_class . '" data-pfoa-profile-id="' . esc_attr( (string) $post_id ) . '">';
				$html .= sprintf(
					'<a class="pfoa-cat-card-media" href="%s" data-pfoa-cat-dialog="%s">%s</a>',
					esc_url( $url ),
					esc_attr( $slug ),
					$thumbnail
				);
				$html .= sprintf(
					'<h3 class="pfoa-cat-card-title"><a href="%s" data-pfoa-cat-dialog="%s">%s</a></h3>',
					esc_url( $url ),
					esc_attr( $slug ),
					esc_html( $display )
				);
				$html .= '</article>';
			} else {
				$html .= '<article class="pfoa-cat-card pfoa-cat-card-incomplete' . $pair_class . '" data-pfoa-profile-id="' . esc_attr( (string) $post_id ) . '">';
				$html .= '<div class="pfoa-cat-card-media">' . $thumbnail . '</div>';
				$html .= '<h3 class="pfoa-cat-card-title">' . esc_html( $display ) . '</h3>';
				$html .= '<p class="pfoa-cat-card-note">' . esc_html__( 'Full profile coming soon.', 'pfoa-theme' ) . '</p>';
				$html .= '</article>';
			}
		}

		return $html;
	}
}

if ( ! function_exists( 'pfoa_theme_card_compat_label_for_index' ) ) {
	/**
	 * Resolve a card display label: explicit stored label wins, else fallback.
	 *
	 * Stored labels are used exactly as stored (no normalization); a missing
	 * or blank entry falls back to the caller-supplied title or member name.
	 *
	 * @param array  $card_labels Explicit stored labels in member order.
	 * @param int    $index       Member index.
	 * @param string $fallback    Title or member name fallback.
	 * @return string Display label.
	 */
	function pfoa_theme_card_compat_label_for_index( array $card_labels, int $index, string $fallback ): string {
		$candidate = trim( (string) ( $card_labels[ $index ] ?? '' ) );

		return '' !== $candidate ? $candidate : $fallback;
	}
}
