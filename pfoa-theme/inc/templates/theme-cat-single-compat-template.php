<?php
/**
 * INACTIVE canonical Cat Profile compatibility template (OFF/COMPAT bundle).
 *
 * INACTIVE — NOT wired. Do NOT require this file from functions.php and do NOT
 * load it via template_include. It lives under inc/templates/ so the WordPress
 * template hierarchy can never auto-select it; a future dispatcher must opt in
 * explicitly once the cutover specification authorises activation.
 *
 * When authorised, this template mirrors the canonical single structure
 * (header, ordinary loop, main wrapper, footer) while preserving the existing
 * h1 detail presentation by delegating every record to
 * pfoa_theme_render_cat_profile_compat(), which already covers gallery output
 * for complete records — nothing here reimplements markup or calls the gallery
 * renderer separately.
 *
 * Data contract: current neutral snapshot only, retrieved via
 * pfoa_cat_get_profile_data() for the current post. No storage reads happen
 * here. Complete and incomplete records pass through untouched; the renderer
 * decides presentation from publication.complete. Rendering is read-only and
 * safe under GET.
 *
 * Fail-closed: every byte stays buffered and the neutral snapshot is fully
 * validated — dependencies, loop post type, snapshot shape, schema 1.0,
 * snapshot post_id matching the current loop post, and nonempty renderer
 * output — before any page output is emitted. Any failure discards the
 * buffer and emits zero bytes — never a partial profile.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Buffer every byte as a backstop: header/footer output stays inside the
// buffer, so any failure below emits nothing.
ob_start();

$pfoa_compat_rendered = false;
$pfoa_compat_mains    = '';

if ( function_exists( 'pfoa_cat_get_profile_data' ) && function_exists( 'pfoa_theme_render_cat_profile_compat' ) ) {
	// Phase 1: validate the neutral snapshot and capture rendering first —
	// the loop itself emits nothing, so no page output exists yet.
	while ( have_posts() ) :
		the_post();

		$compat_post          = get_post();
		$compat_expected_type = defined( 'PFOA_CAT_POST_TYPE' ) ? PFOA_CAT_POST_TYPE : 'pfoa_cat';
		if ( ! ( $compat_post instanceof WP_Post ) || $compat_post->post_type !== $compat_expected_type ) {
			continue;
		}

		$compat_loop_id = get_the_ID();

		$compat_data = pfoa_cat_get_profile_data( $compat_loop_id );
		if ( ! is_array( $compat_data ) ) {
			continue;
		}
		if ( ! isset( $compat_data['post_type'] ) || 'pfoa_cat' !== $compat_data['post_type'] ) {
			continue;
		}
		if ( ! isset( $compat_data['schema_version'] ) || '1.0' !== $compat_data['schema_version'] ) {
			continue;
		}
		if ( ! isset( $compat_data['post_id'] ) || (int) $compat_data['post_id'] <= 0 ) {
			continue;
		}
		if ( (int) $compat_data['post_id'] !== (int) $compat_loop_id ) {
			continue;
		}
		if ( (int) $compat_data['post_id'] !== (int) $compat_post->ID ) {
			continue;
		}

		$compat_html = pfoa_theme_render_cat_profile_compat( $compat_data, 'h1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme renderer escapes its output.
		if ( ! is_string( $compat_html ) || '' === trim( $compat_html ) ) {
			continue;
		}

		$pfoa_compat_mains    .= '<main class="pfoa-cat-single">' . $compat_html . '</main>';
		$pfoa_compat_rendered = true;

	endwhile;

	// Phase 2: emit page output only after full validation succeeded.
	if ( $pfoa_compat_rendered ) {
		get_header();
		echo $pfoa_compat_mains; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- captured validated renderer output.
		get_footer();
	}
}

if ( ! $pfoa_compat_rendered ) {
	ob_end_clean();
	return;
}

ob_end_flush();
