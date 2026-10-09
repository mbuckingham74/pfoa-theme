<?php
/**
 * Theme-owned Cat Profile gallery compat renderer (INACTIVE).
 *
 * INACTIVE - not required by functions.php, no hooks/shortcodes/filters/actions,
 * no writes. Consumes schema 1.0 neutral data only (as produced by
 * pfoa_cat_get_profile_data()) and reproduces the legacy compat gallery markup
 * so the theme owns markup while the provider owns lightbox init.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * register any hook/shortcode until the cutover spec explicitly authorises it.
 * Lightbox behaviour stays with the provider: this renderer only emits
 * `data-pfoa-cat-gallery` plus standard anchor markup and never initialises
 * Photobox or enqueues assets.
 *
 * Expected neutral input shape (schema 1.0):
 *   array(
 *     'schema_version' => '1.0',
 *     'post_id'        => int,
 *     'gallery'        => array(
 *       'ids'      => int[],
 *       'captions' => array<int, string|null>, // null = absent caption (blank,
 *                                              // no attachment-metadata fallback),
 *                                              // '' = explicit blank (suppress dd).
 *     ),
 *   )
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_GALLERY_COMPAT_RENDERER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_GALLERY_COMPAT_RENDERER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_render_cat_gallery_compat' ) ) {
	/**
	 * Render the compat gallery from schema 1.0 neutral profile data.
	 *
	 * Defensive and side-effect free: validates schema_version, extracts the
	 * gallery ids/captions without storage reads, preserves stored id order
	 * verbatim, preserves the null-vs-'' caption distinction via
	 * array_key_exists, skips invalid attachments, and returns '' for
	 * empty/all-invalid input.
	 *
	 * @param array $profile_data Neutral profile snapshot (schema 1.0).
	 * @return string Compat gallery HTML, or '' when nothing renderable.
	 */
	function pfoa_theme_render_cat_gallery_compat( array $profile_data ): string {
		if ( ! isset( $profile_data['schema_version'] ) || '1.0' !== $profile_data['schema_version'] ) {
			return '';
		}

		$post_id = isset( $profile_data['post_id'] ) ? (int) $profile_data['post_id'] : 0;
		if ( $post_id <= 0 ) {
			return '';
		}

		$gallery = isset( $profile_data['gallery'] ) && is_array( $profile_data['gallery'] )
			? $profile_data['gallery']
			: array();

		$ids = isset( $gallery['ids'] ) && is_array( $gallery['ids'] )
			? $gallery['ids']
			: array();

		$captions = isset( $gallery['captions'] ) && is_array( $gallery['captions'] )
			? $gallery['captions']
			: array();

		if ( array() === $ids ) {
			return '';
		}

		$items = array();
		foreach ( $ids as $raw_id ) {
			$attachment_id = (int) $raw_id;
			if ( $attachment_id <= 0 ) {
				continue;
			}

			$grid = wp_get_attachment_image_src( $attachment_id, 'grid_fifth_1' );
			if ( ! is_array( $grid ) ) {
				$grid = wp_get_attachment_image_src( $attachment_id, 'medium' );
			}
			$full = wp_get_attachment_url( $attachment_id );
			if ( ! is_array( $grid ) || ! is_string( $full ) || '' === $full ) {
				continue;
			}

			if ( array_key_exists( $attachment_id, $captions ) ) {
				$raw_caption = $captions[ $attachment_id ];
				$local       = null === $raw_caption ? null : (string) $raw_caption;
			} else {
				$local = null;
			}
			$caption = null !== $local ? $local : '';

			$items[] = array(
				'id'      => $attachment_id,
				'full'    => $full,
				'grid'    => $grid,
				'alt'     => $caption,
				'caption' => $caption,
			);
		}

		if ( array() === $items ) {
			return '';
		}

		$html = sprintf(
			'<div id="pfoa-cat-gallery-%d" class="pfoa-cat-gallery galleryid-%d gallery-columns-5 gallery-size-grid_fifth_1" data-pfoa-cat-gallery>',
			$post_id,
			$post_id
		);

		foreach ( $items as $item ) {
			$width       = isset( $item['grid'][1] ) ? (int) $item['grid'][1] : 0;
			$height      = isset( $item['grid'][2] ) ? (int) $item['grid'][2] : 0;
			$orientation = $width >= $height ? 'landscape' : 'portrait';

			$image = wp_get_attachment_image(
				(int) $item['id'],
				'grid_fifth_1',
				false,
				array(
					'alt'     => $item['alt'],
					'title'   => $item['caption'],
					'loading' => 'lazy',
				)
			);

			if ( '' === $image ) {
				$image = sprintf(
					'<img src="%s" width="%d" height="%d" alt="%s"%s loading="lazy" />',
					esc_url( $item['grid'][0] ),
					$width,
					$height,
					esc_attr( $item['alt'] ),
					'' !== $item['caption'] ? ' title="' . esc_attr( $item['caption'] ) . '"' : ''
				);
			}

			$html .= sprintf(
				'<dl class="gallery-item"><dt class="gallery-icon %s"><a href="%s">%s</a></dt>',
				esc_attr( $orientation ),
				esc_url( $item['full'] ),
				$image
			);

			if ( '' !== $item['caption'] ) {
				$html .= '<dd class="wp-caption-text gallery-caption">' . esc_html( $item['caption'] ) . '</dd>';
			}

			$html .= '</dl>';
		}

		$html .= '<br style="clear: both" /></div>';

		return $html;
	}
}
