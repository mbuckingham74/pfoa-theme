<?php
/**
 * Theme-owned Cat Profile single COMPAT template router (INACTIVE).
 *
 * INACTIVE — not required by functions.php, no hooks, no output, no writes.
 * Do NOT require this file from functions.php and do NOT wire any
 * template_include filter until the cutover spec explicitly authorises it.
 *
 * Pure template selection only: given the incoming template path and an
 * explicitly supplied/frozen presentation-readiness snapshot, return the
 * existing inactive COMPAT single template path only when the frozen
 * decision selects COMPAT and every required dependency is present.
 * Otherwise return the incoming template unchanged (fail-closed).
 *
 * This router never recalculates readiness: it never reads the
 * PFOA_THEME_PRESENTATION switch, never calls the passive resolver or its
 * freeze/getter helpers, and never inspects profile data, schema, or
 * publication completeness. Schema 1.0 validation and complete/incomplete
 * behaviour stay entirely with the existing COMPAT template and its
 * delegated renderer; both shapes route to the same template.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_SINGLE_COMPAT_ROUTER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_SINGLE_COMPAT_ROUTER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_select_cat_single_compat_template' ) ) {
	/**
	 * Select the inactive COMPAT single template for a Cat Profile request.
	 *
	 * Deterministic and side-effect free: validates the request first, then
	 * the explicitly supplied frozen readiness decision, then the required
	 * dependencies — returning the incoming template unchanged at the first
	 * failure. No hooks, no rendering, no storage, no output.
	 *
	 * Valid COMPAT selection requires all of:
	 *   - a non-empty incoming template string,
	 *   - a singular pfoa_cat request (is_singular('pfoa_cat')),
	 *   - a frozen readiness array with requested_mode 'compat',
	 *     readiness 'compat', compat_ready true, and no invalid
	 *     request_state,
	 *   - the neutral provider and compat renderer dependencies, plus the
	 *     existing COMPAT template file itself.
	 *
	 * @param string $template  Incoming template path.
	 * @param mixed  $readiness Explicitly supplied frozen readiness snapshot (array expected).
	 * @return string COMPAT template path when valid, else $template unchanged.
	 */
	function pfoa_theme_select_cat_single_compat_template( string $template, $readiness ): string {
		if ( '' === $template ) {
			return $template;
		}

		if ( ! function_exists( 'is_singular' ) || ! is_singular( 'pfoa_cat' ) ) {
			return $template;
		}

		if ( ! is_array( $readiness ) ) {
			return $template;
		}

		if ( isset( $readiness['request_state'] ) && 'invalid' === $readiness['request_state'] ) {
			return $template;
		}

		if ( ! isset( $readiness['requested_mode'] ) || 'compat' !== $readiness['requested_mode'] ) {
			return $template;
		}

		if ( ! isset( $readiness['readiness'] ) || 'compat' !== $readiness['readiness'] ) {
			return $template;
		}

		if ( ! array_key_exists( 'compat_ready', $readiness ) || true !== $readiness['compat_ready'] ) {
			return $template;
		}

		if ( ! function_exists( 'pfoa_cat_get_profile_data' ) || ! function_exists( 'pfoa_theme_render_cat_profile_compat' ) ) {
			return $template;
		}

		$candidate = dirname( __FILE__ ) . '/templates/theme-cat-single-compat-template.php';

		if ( ! file_exists( $candidate ) ) {
			return $template;
		}

		return $candidate;
	}
}
