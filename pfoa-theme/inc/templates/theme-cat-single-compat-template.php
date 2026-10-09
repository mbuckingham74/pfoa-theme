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
 * Fail-closed: every byte is buffered until one fully validated record has
 * rendered. Missing dependencies, wrong post type, or missing/invalid neutral
 * data discards the buffer and emits nothing — never a partial profile.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Hold all output until validation succeeds so failures emit nothing.
ob_start();

$pfoa_compat_rendered = false;

if ( function_exists( 'pfoa_cat_get_profile_data' ) && function_exists( 'pfoa_theme_render_cat_profile_compat' ) ) {
	get_header();

	while ( have_posts() ) :
		the_post();

		$compat_post          = get_post();
		$compat_expected_type = defined( 'PFOA_CAT_POST_TYPE' ) ? PFOA_CAT_POST_TYPE : 'pfoa_cat';
		if ( ! ( $compat_post instanceof WP_Post ) || $compat_post->post_type !== $compat_expected_type ) {
			continue;
		}

		$compat_data = pfoa_cat_get_profile_data( get_the_ID() );
		if ( ! is_array( $compat_data ) ) {
			continue;
		}
		if ( ! isset( $compat_data['post_type'] ) || 'pfoa_cat' !== $compat_data['post_type'] ) {
			continue;
		}
		if ( ! isset( $compat_data['schema_version'] ) || '1.0' !== $compat_data['schema_version'] ) {
			continue;
		}

		echo '<main class="pfoa-cat-single">';
		echo pfoa_theme_render_cat_profile_compat( $compat_data, 'h1' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- theme renderer escapes its output.
		echo '</main>';

		$pfoa_compat_rendered = true;

	endwhile;

	get_footer();
}

if ( ! $pfoa_compat_rendered ) {
	ob_end_clean();
	return;
}

ob_end_flush();
