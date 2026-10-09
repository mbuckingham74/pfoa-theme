<?php
/**
 * Theme-owned Cat Profile card-list compat renderer (INACTIVE).
 *
 * INACTIVE - not required by functions.php, no hooks, no writes, no assets.
 * Consumes schema 1.0 neutral data only and assembles accepted per-profile
 * compat cards in caller order so the theme owns markup while the provider
 * owns behaviour.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * Expected neutral input: an ordered array of schema 1.0 profile snapshots
 * already selected and eligibility-checked by the caller (see the singular
 * per-profile compat renderer for the snapshot shape). Ordering across
 * profiles, listing membership, and bonded composition stay with the caller;
 * this renderer preserves caller order exactly and never sorts or queries.
 *
 * The dialog shell below only returns markup. The caller must place it at
 * wp_footer; no footer wiring lives here. A single footer owner decides
 * placement in a future cutover.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_CARDS_COMPAT_RENDERER_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_CARDS_COMPAT_RENDERER_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_render_cat_cards_compat' ) ) {
	/**
	 * Render an ordered card list from schema 1.0 neutral profile snapshots.
	 *
	 * Defensive and side-effect free: iterates the input in order with no
	 * sorting and no queries. Each entry that is an array is passed to the
	 * singular per-profile compat renderer; non-array entries are skipped.
	 * Non-empty card output is concatenated in order. Gated or invalid
	 * profiles contribute nothing but never disturb the order of the rest.
	 *
	 * @param array $profiles Ordered neutral snapshots (schema 1.0).
	 * @return string Wrapper div with cards, or the empty-list message.
	 */
	function pfoa_theme_render_cat_cards_compat( array $profiles ): string {
		if ( ! function_exists( 'pfoa_theme_render_cat_card_compat' ) ) {
			$sibling = __DIR__ . '/theme-cat-card-compat-renderer.php';
			if ( file_exists( $sibling ) ) {
				require_once $sibling;
			}
		}

		if ( 0 === count( $profiles ) ) {
			return '<p class="pfoa-cat-cards-empty">' . esc_html__( 'No cat profiles found.', 'pfoa-theme' ) . '</p>';
		}

		$cards = '';

		foreach ( $profiles as $profile ) {
			if ( ! is_array( $profile ) ) {
				continue;
			}

			if ( ! function_exists( 'pfoa_theme_render_cat_card_compat' ) ) {
				continue;
			}

			$card_html = pfoa_theme_render_cat_card_compat( $profile );

			if ( '' !== $card_html ) {
				$cards .= $card_html;
			}
		}

		return '<div class="pfoa-cat-cards">' . $cards . '</div>';
	}
}

if ( ! function_exists( 'pfoa_theme_render_cat_dialog_shell_compat' ) ) {
	/**
	 * Return the reusable theme-owned dialog-shell markup (compat copy).
	 *
	 * Matches the legacy compatibility shell exactly: same ids, classes,
	 * attributes, and structure. Labels use the pfoa-theme textdomain.
	 * First call per request returns the shell; later calls return '' so
	 * the shell is never duplicated. This function only returns markup;
	 * the caller must place it at wp_footer under a single future owner.
	 *
	 * @return string Dialog shell markup, or '' when already rendered.
	 */
	function pfoa_theme_render_cat_dialog_shell_compat(): string {
		static $rendered = false;

		if ( $rendered ) {
			return '';
		}

		$rendered = true;

		$html = '<div id="pfoa-cat-dialog" class="pfoa-cat-dialog" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'Cat profile', 'pfoa-theme' ) . '" hidden>';
		$html .= '<div class="pfoa-cat-dialog-container">';
		$html .= '<button type="button" class="pfoa-cat-dialog-close" data-pfoa-cat-dialog-close aria-label="' . esc_attr__( 'Close', 'pfoa-theme' ) . '">×</button>';
		$html .= '<div class="pfoa-cat-dialog-body" data-pfoa-cat-dialog-body aria-live="polite" tabindex="0"></div>';
		$html .= '</div></div>';

		return $html;
	}
}
