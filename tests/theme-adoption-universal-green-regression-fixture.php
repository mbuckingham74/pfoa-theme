<?php
/**
 * Regression guard against reinstating universal-green multi-cat badges
 * (PFOA 0.1.129 commit 294f68c behavior: unconditional `if (2<=count)
 * together-badge`, no red).
 *
 * Correct 0.1.157 behavior (this file MUST PASS):
 *   single            -> no badge,
 *   true bonded pair  -> exactly 1 RED "Bonded Pair" + --bonded, 0 green,
 *   non-bonded multi  -> exactly 1 GREEN "Adopted Together" + --together.
 *
 * Universal-green logic (this file MUST FAIL with a clear invariant):
 *   bonded pairs render green instead of red, so every "Bonded Pair"
 *   assertion below exits nonzero naming the violated invariant.
 *
 * Sections:
 *   A. Live gallery path (pfoa_present_adoption_events) on bonded 701/702.
 *   B. Live shared card path (pfoa_render_recently_adopted_event_card) on a
 *      bonded_pair=true card.
 *   C. Synthetic universal-green sample: the exact markup the 0.1.129 logic
 *      would emit for a bonded pair, run through the same invariant checker
 *      to prove the detector flags it (failure diagnostic exercised while
 *      this file still passes on correct code).
 *   D. CSS guard: bonded must stay brand-red, together must stay CTA-green.
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Exits nonzero on any failure.
 *
 * Run with:
 *   php tests/theme-adoption-universal-green-regression-fixture.php
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	class Walker_Nav_Menu {}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		/** @var int */
		public $ID = 0;
		/** @var string */
		public $post_type = '';
		/** @var string */
		public $post_name = '';
	}
}

$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_event_ids']       = array();
$GLOBALS['fixture_meta']            = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_shortcode( $tag, $callback ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function delete_post_meta( $post_id, $key ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function wp_insert_post( $postarr, $wp_error = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
}

function is_admin() {
	return false;
}
function is_feed() {
	return false;
}
function in_the_loop() {
	return true;
}
function is_main_query() {
	return true;
}
function is_page( $page = '' ) {
	return true;
}
function get_post( $post = null ) {
	if ( null === $post ) {
		$page            = new WP_Post();
		$page->ID        = 22514;
		$page->post_type = 'page';
		$page->post_name = 'adopted2026';
		return $page;
	}
	$id = (int) $post;
	if ( isset( $GLOBALS['fixture_meta'][ $id ] ) ) {
		$event            = new WP_Post();
		$event->ID        = $id;
		$event->post_type = 'pfoa_adoption_event';
		return $event;
	}
	return null;
}
function get_posts( $args = array() ) {
	return $GLOBALS['fixture_event_ids'];
}
function get_post_meta( $post_id, $key = '', $single = false ) {
	$id = (int) $post_id;
	if ( ! isset( $GLOBALS['fixture_meta'][ $id ] ) || ! array_key_exists( (string) $key, $GLOBALS['fixture_meta'][ $id ] ) ) {
		return $single ? '' : array();
	}
	$value = $GLOBALS['fixture_meta'][ $id ][ (string) $key ];
	return $single ? $value : array( $value );
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text, $domain = null ) {
	return (string) $text;
}
function date_i18n( $format, $timestamp = null ) {
	return date( (string) $format, (int) $timestamp );
}
function wp_attachment_is_image( $attachment_id ) {
	return true;
}
function wp_get_attachment_link( $attachment_id, $size = 'thumbnail', $permalink = false, $icon = false ) {
	$id = (int) $attachment_id;
	return '<a href="https://example.com/full' . $id . '.jpg"><img src="https://example.com/med' . $id . '.jpg" alt=""></a>';
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_require( $condition, $invariant, $detail ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL [{$invariant}]: {$detail}\n" );
		exit( 1 );
	}
}

function fixture_set_event( $event_id, $uuid, $date, $members ) {
	$GLOBALS['fixture_meta'][ (int) $event_id ] = array(
		'_pfoa_event_year'    => '2026',
		'_pfoa_event_date'    => (string) $date,
		'_pfoa_event_uuid'    => (string) $uuid,
		'_pfoa_event_members' => $members,
	);
}

function fixture_member( $image_id, $name, $cat_post_id = 0 ) {
	return array(
		'image_id'          => (int) $image_id,
		'cat_name_at_event' => (string) $name,
		'caption'           => (string) $name,
		'cat_post_id'       => (int) $cat_post_id,
	);
}

function fixture_gallery_item( $attachment_id ) {
	$id = (int) $attachment_id;
	return '<figure class="gallery-item"><img aria-describedby="gallery-1-' . $id . '" src="https://example.com/med' . $id . '.jpg"><figcaption id="gallery-1-' . $id . '">Cap ' . $id . '</figcaption></figure>';
}

function fixture_gallery( $attachment_ids ) {
	$items = array();
	foreach ( $attachment_ids as $id ) {
		$items[] = fixture_gallery_item( $id );
	}
	return '<div class="gallery galleryid-1 gallery-columns-3">' . implode( '', $items ) . '</div>';
}

/**
 * Invariant checker shared by live output (must be clean) and the synthetic
 * universal-green sample (must be flagged). Returns '' when the markup
 * satisfies the bonded-pair invariant, else a diagnostic naming it.
 */
function bonded_pair_diagnostic( $html ) {
	if ( 1 !== substr_count( (string) $html, 'pfoa-adoption-event-bonded-badge' ) ) {
		return 'expected exactly 1 red bonded badge, found ' . substr_count( (string) $html, 'pfoa-adoption-event-bonded-badge' );
	}
	if ( false !== strpos( (string) $html, 'pfoa-adoption-event-together-badge' ) ) {
		return 'expected 0 green together badges on bonded pair, found green markup (universal-green regression)';
	}
	if ( false === strpos( (string) $html, 'pfoa-adoption-event--bonded' ) ) {
		return 'expected --bonded modifier class on true bonded pair';
	}
	return '';
}

fixture_require( function_exists( 'pfoa_present_adoption_events' ), 'Universal-green guard wiring', 'gallery path pfoa_present_adoption_events() missing' );
fixture_require( function_exists( 'pfoa_render_recently_adopted_event_card' ), 'Universal-green guard wiring', 'card path pfoa_render_recently_adopted_event_card() missing' );

// Strict reciprocal bonded double 701/702 (Sugar/Spice analogue).
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, array( 701, 702 ), true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	if ( in_array( (int) $cat_id, array( 701, 702 ), true ) ) {
		return array( 701, 702 );
	}
	return array();
}

// ---- A. Live gallery path on a bonded pair. ----
// Under universal-green logic this renders green (no red) and the first
// assertion fails: FAIL [Bonded Pair gallery]: expected exactly 1 red ...
$GLOBALS['fixture_event_ids'] = array( 12 );
fixture_set_event(
	12,
	'uuid-regression-bonded',
	'2026-10-05',
	array( fixture_member( 701, 'Sugar', 701 ), fixture_member( 702, 'Spice', 702 ) )
);
$gallery_out = pfoa_present_adoption_events( fixture_gallery( array( 701, 702 ) ) );
$gallery_diag = bonded_pair_diagnostic( $gallery_out );
fixture_require( '' === $gallery_diag, 'Bonded Pair gallery', $gallery_diag . ' (live gallery output violates bonded-pair invariant; universal-green 0.1.129 logic reinstated?)' );
fixture_require( 1 === substr_count( $gallery_out, 'Bonded Pair' ), 'Bonded Pair gallery', 'expected exactly 1 red "Bonded Pair" text node in live gallery' );

// ---- B. Live shared card path on a bonded pair. ----
// Under universal-green logic this renders green (no red) and the first
// assertion fails: FAIL [Bonded Pair card]: expected exactly 1 red ...
$card_out = pfoa_render_recently_adopted_event_card(
	array(
		'id'          => 100,
		'date'        => '2026-10-10',
		'uuid'        => 'uuid-regression-card-bonded',
		'members'     => array(
			array( 'image_id' => 212, 'name' => 'Sugar', 'caption' => 'Sugar' ),
			array( 'image_id' => 213, 'name' => 'Spice', 'caption' => 'Spice' ),
		),
		'bonded_pair' => true,
	)
);
$card_diag = bonded_pair_diagnostic( $card_out );
fixture_require( '' === $card_diag, 'Bonded Pair card', $card_diag . ' (live card output violates bonded-pair invariant; universal-green 0.1.129 logic reinstated?)' );
fixture_require( 1 === substr_count( $card_out, 'Bonded Pair' ), 'Bonded Pair card', 'expected exactly 1 red "Bonded Pair" text node in live card' );

// ---- C. Synthetic universal-green sample: prove the detector fires. ----
// This is byte-for-byte what the 0.1.129 `if (2<=count) together-badge`
// branch emits for the same bonded pair: --together + green, no red.
$synthetic_universal_green = '<div class="pfoa-adoption-event pfoa-adoption-event-multi pfoa-adoption-event-count-2 pfoa-adoption-event--together" data-pfoa-adoption-event="uuid-regression-bonded"><h2 class="pfoa-adoption-event-title">Sugar &amp; Spice <span class="pfoa-adoption-event-together-badge">Adopted Together<span class="screen-reader-text"> in the same adoption event</span></span></h2></div>';
$synthetic_diag = bonded_pair_diagnostic( $synthetic_universal_green );
fixture_require( '' !== $synthetic_diag, 'Universal-green detector', 'expected the invariant checker to flag the synthetic universal-green sample, but it passed clean (detector broken)' );
fwrite( STDOUT, 'INFO [Universal-green detector]: synthetic 0.1.129 sample correctly flagged: ' . $synthetic_diag . "\n" );

// ---- D. CSS guard: red stays red, green stays green. ----
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_require( 1 === preg_match( '/--pfoa-color-brand:\s*#934244/', $css ), 'CSS brand red', 'expected --pfoa-color-brand: #934244 intact' );
fixture_require( 1 === preg_match( '/--pfoa-accent:\s*#057205/', $css ), 'CSS accent green', 'expected --pfoa-accent: #057205 intact' );
fixture_require( 1 === preg_match( '/\.pfoa-adoption-event-bonded-badge\s*\{\s*background:\s*var\(--pfoa-color-brand\)/', $css ), 'CSS bonded red', 'expected bonded badge background var(--pfoa-color-brand) (red #934244), not green' );
fixture_require( 1 === preg_match( '/\.pfoa-adoption-event-together-badge\s*\{\s*background:\s*var\(--pfoa-cta-primary\)/', $css ), 'CSS together green', 'expected together badge background var(--pfoa-cta-primary) (green #057205)' );

// No writes, no throws, read-only meta access.
fixture_require( false === $GLOBALS['fixture_db_touched'], 'Universal-green guard hygiene', 'expected no DB writes during badge rendering' );

fwrite( STDOUT, "PASS: universal-green regression guard (bonded 701/702 red on live gallery + card paths; synthetic 0.1.129 green sample flagged; CSS red/green intact; no writes)\n" );
