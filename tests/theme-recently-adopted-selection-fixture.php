<?php
/**
 * Focused fixture for the Recently Adopted strip (PFOA 0.1.131,
 * shortcode [pfoa_recently_adopted] for page 20323).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: newest-first selection (date DESC + event ID DESC
 * tiebreak), cross-year coverage (no year filter), limit handling (default
 * 4, fewer when fewer qualify), invalid-date/future/missing-uuid/empty-
 * member exclusion, stored member order + photo/caption/name preservation,
 * conditional badges (red "Bonded Pair" for true bonded pairs, green
 * "Adopted Together" for non-bonded multi-cat cards, singletons
 * badge-free), section wrapper + aria grouping, shortcode limit attribute,
 * empty-set '',
 * scoped CSS contract (page-id-20323 block, red bonded-badge + green
 * together-badge, no hardcoded IDs), and no DB writes.
 *
 * Run with:
 *   php tests/theme-recently-adopted-selection-fixture.php
 *
 * Exits nonzero on any failure.
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
$GLOBALS['fixture_shortcodes']      = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_shortcode( $tag, $callback ) {
	$GLOBALS['fixture_shortcodes'][ (string) $tag ] = $callback;
}
function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$out = array();
	foreach ( (array) $pairs as $key => $default ) {
		$out[ $key ] = ( is_array( $atts ) && array_key_exists( $key, $atts ) ) ? $atts[ $key ] : $default;
	}
	return $out;
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
function wp_update_post( $postarr, $wp_error = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
}
function wp_delete_post( $post_id, $force = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
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
function current_time( $type ) {
	// Fixed "today" so future-date exclusion is deterministic.
	return '2026-10-14';
}
function get_post( $post = null ) {
	if ( null === $post ) {
		$page            = new WP_Post();
		$page->ID        = 20323;
		$page->post_type = 'page';
		$page->post_name = 'adoptablecats2';
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
function esc_attr__( $text, $domain = null ) {
	return (string) $text;
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

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_require( $condition, $invariant, $detail ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL [{$invariant}]: {$detail}\n" );
		exit( 1 );
	}
}

function fixture_set_event( $event_id, $uuid, $date, $members ) {
	$GLOBALS['fixture_meta'][ (int) $event_id ] = array(
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

function fixture_ids( $events ) {
	$ids = array();
	foreach ( $events as $event ) {
		$ids[] = (int) $event['id'];
	}
	return $ids;
}

fixture_assert( function_exists( 'pfoa_get_recently_adopted_events' ), 'selection function exists' );
fixture_assert( function_exists( 'pfoa_render_recently_adopted_event_card' ), 'card renderer exists' );
fixture_assert( function_exists( 'pfoa_recently_adopted_events' ), 'shortcode callback exists' );
fixture_assert( isset( $GLOBALS['fixture_shortcodes']['pfoa_recently_adopted'] ), 'shortcode pfoa_recently_adopted registered' );

// Selection set mirrors the live DEV shape: newest pair, two singles, an
// older pair that must drop out of the default top-4, plus a cross-year
// (2025) event proving there is no year filter.
$GLOBALS['fixture_event_ids'] = array( 11, 22, 33, 44, 55, 66 );
fixture_set_event( 11, 'uuid-new-pair', '2026-10-10', array( fixture_member( 101, 'Cloud' ), fixture_member( 102, 'Storm' ) ) );
fixture_set_event( 22, 'uuid-mid-single', '2026-10-06', array( fixture_member( 103, 'Martone' ) ) );
fixture_set_event( 33, 'uuid-old-single', '2026-09-29', array( fixture_member( 104, 'Sonny' ) ) );
fixture_set_event( 44, 'uuid-older-pair', '2026-09-26', array( fixture_member( 105, 'Sugar' ), fixture_member( 106, 'Spice' ) ) );
fixture_set_event( 55, 'uuid-oldest-single', '2026-09-01', array( fixture_member( 107, 'Veteran' ) ) );
fixture_set_event( 66, 'uuid-cross-year', '2025-06-15', array( fixture_member( 108, 'Elder' ) ) );

// (RA-01) Default top-4: newest first, oldest drops out.
$top4 = pfoa_get_recently_adopted_events();
fixture_assert( array( 11, 22, 33, 44 ) === fixture_ids( $top4 ), 'selection: default top-4 newest-first (older pair + oldest + 2025 drop)' );

// (RA-02) Cross-year: the 2025 event is selectable when the limit allows.
$top6 = pfoa_get_recently_adopted_events( 6 );
fixture_assert( array( 11, 22, 33, 44, 55, 66 ) === fixture_ids( $top6 ), 'selection: cross-year event included, no year filter' );

// (RA-03) Fewer qualify than the limit: only two valid events.
$GLOBALS['fixture_event_ids'] = array( 22, 44 );
$top_few = pfoa_get_recently_adopted_events();
fixture_assert( array( 22, 44 ) === fixture_ids( $top_few ), 'selection: fewer than limit returns what qualifies' );

// (RA-04) Same-date tiebreak is deterministic ID DESC.
$GLOBALS['fixture_event_ids'] = array( 71, 72, 73 );
fixture_set_event( 71, 'uuid-same-low', '2026-10-10', array( fixture_member( 111, 'Low' ) ) );
fixture_set_event( 72, 'uuid-same-mid', '2026-10-10', array( fixture_member( 112, 'Mid' ) ) );
fixture_set_event( 73, 'uuid-same-high', '2026-10-10', array( fixture_member( 113, 'High' ) ) );
$top_same = pfoa_get_recently_adopted_events( 3 );
fixture_assert( array( 73, 72, 71 ) === fixture_ids( $top_same ), 'selection: same-date order is stable ID DESC' );

// (RA-05) Exclusions: malformed date, impossible date, future date,
// missing UUID, and memberless events never qualify.
$GLOBALS['fixture_event_ids'] = array( 81, 82, 83, 84, 85, 22 );
fixture_set_event( 81, 'uuid-bad-format', '10/10/2026', array( fixture_member( 121, 'Bad' ) ) );
fixture_set_event( 82, 'uuid-impossible', '2026-02-30', array( fixture_member( 122, 'Impossible' ) ) );
fixture_set_event( 83, 'uuid-future', '2026-10-15', array( fixture_member( 123, 'Future' ) ) );
fixture_set_event( 84, '', '2026-10-12', array( fixture_member( 124, 'NoUuid' ) ) );
fixture_set_event( 85, 'uuid-no-members', '2026-10-12', array() );
$top_clean = pfoa_get_recently_adopted_events();
fixture_assert( array( 22 ) === fixture_ids( $top_clean ), 'selection: invalid/future/uuid-less/memberless events excluded' );

// (RA-06) Presentation: full shortcode render of the RA-01 set. Both pairs
// are non-bonded here (no bonded helpers defined yet, cat_post_id 0), so
// both multi-cat cards carry the green badge; singletons stay badge-free.
$GLOBALS['fixture_event_ids'] = array( 11, 22, 33, 44 );
$out = pfoa_recently_adopted_events( array() );
fixture_assert( 0 === strpos( $out, '<section class="pfoa-adoption-events pfoa-recently-adopted"' ), 'render: section wrapper with scope class' );
fixture_assert( 4 === substr_count( $out, 'role="group"' ), 'render: four grouped event cards' );
fixture_assert( 6 === substr_count( $out, 'data-pfoa-caption' ), 'render: six photos with lightbox captions (2+1+1+2)' );
fixture_assert( 6 === substr_count( $out, '<img' ), 'render: six visible photos, no more, no fewer' );
fixture_assert( false !== strpos( $out, 'Cloud &amp; Storm' ), 'render: pair combined title kept' );
fixture_assert( false !== strpos( $out, 'Sugar &amp; Spice' ), 'render: older pair combined title kept' );
fixture_require( 2 === substr_count( $out, 'pfoa-adoption-event-together-badge' ), 'Adopted Together card', 'expected exactly 2 green badges on both non-bonded multi-cat cards, found ' . substr_count( $out, 'pfoa-adoption-event-together-badge' ) );
fixture_require( 2 === substr_count( $out, 'Adopted Together' ), 'Adopted Together card', 'expected green badge text on both multi-cat cards' );
fixture_require( 0 === substr_count( $out, 'bonded-badge' ), 'Adopted Together card', 'expected no bonded badge markup in all-non-bonded render' );
fixture_require( 0 === substr_count( $out, 'Bonded Pair' ), 'Adopted Together card', 'expected no bonded-pair text in all-non-bonded render' );
foreach ( array( 'October 10, 2026', 'October 6, 2026', 'September 29, 2026', 'September 26, 2026' ) as $date_text ) {
	fixture_assert( false !== strpos( $out, '<p class="pfoa-adoption-event-date">' . $date_text . '</p>' ), 'render: visible date ' . $date_text );
}
$pos_new = strpos( $out, 'data-pfoa-adoption-event="uuid-new-pair"' );
$pos_mid = strpos( $out, 'data-pfoa-adoption-event="uuid-mid-single"' );
$pos_old = strpos( $out, 'data-pfoa-adoption-event="uuid-old-single"' );
$pos_op  = strpos( $out, 'data-pfoa-adoption-event="uuid-older-pair"' );
fixture_assert( false !== $pos_new && $pos_new < $pos_mid && $pos_mid < $pos_old && $pos_old < $pos_op, 'render: cards in newest-first DOM order' );
// Singleton card carries no badge: slice between its wrapper and the next.
$solo_next = strpos( $out, 'data-pfoa-adoption-event="uuid-old-single"', (int) $pos_mid );
$solo_card = substr( $out, (int) $pos_mid, (int) $solo_next - (int) $pos_mid );
fixture_require( false === strpos( $solo_card, 'badge' ), 'Single card', 'expected singleton card carries no badge markup' );

// (RA-07) Shortcode limit attribute + empty-set contract.
$out_two = pfoa_recently_adopted_events( array( 'limit' => 2 ) );
fixture_assert( 2 === substr_count( $out_two, 'role="group"' ), 'shortcode: limit attribute respected' );
$GLOBALS['fixture_event_ids'] = array( 81 );
$out_empty = pfoa_recently_adopted_events( array() );
fixture_assert( '' === $out_empty, 'shortcode: empty string when nothing qualifies' );

// (RA-08) Bonded classification preserved in data, public badge is
// conditional red: a strict reciprocal pair keeps --bonded and renders the
// red Bonded Pair badge — never the green one.
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, array( 901, 902 ), true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	if ( in_array( (int) $cat_id, array( 901, 902 ), true ) ) {
		return array( 901, 902 );
	}
	return array();
}
$GLOBALS['fixture_event_ids'] = array( 91 );
fixture_set_event(
	91,
	'uuid-bonded-pair',
	'2026-10-11',
	array(
		array(
			'image_id'          => 131,
			'cat_name_at_event' => 'Alpha',
			'caption'           => 'Alpha',
			'cat_post_id'       => 901,
		),
		array(
			'image_id'          => 132,
			'cat_name_at_event' => 'Beta',
			'caption'           => 'Beta',
			'cat_post_id'       => 902,
		),
	)
);
$top_bonded = pfoa_get_recently_adopted_events( 1 );
fixture_assert( true === ( $top_bonded[0]['bonded_pair'] ?? null ), 'data: true reciprocal pair classified bonded' );
$out_bonded = pfoa_recently_adopted_events( array( 'limit' => 1 ) );
fixture_require( false !== strpos( $out_bonded, 'pfoa-adoption-event--bonded' ), 'Bonded Pair card', 'expected bonded pair keeps --bonded class' );
fixture_require( 1 === substr_count( $out_bonded, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair card', 'expected exactly 1 red bonded badge, found ' . substr_count( $out_bonded, 'pfoa-adoption-event-bonded-badge' ) );
fixture_require( 1 === substr_count( $out_bonded, 'Bonded Pair' ), 'Bonded Pair card', 'expected exactly 1 red "Bonded Pair" text node' );
fixture_require( false !== strpos( $out_bonded, 'adopted together as a bonded pair' ), 'Bonded Pair card', 'expected bonded screen-reader suffix' );
fixture_require( false === strpos( $out_bonded, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair card', 'expected 0 green together badges on bonded pair (universal-green regression)' );
fixture_require( false === strpos( $out_bonded, 'Adopted Together' ), 'Bonded Pair card', 'expected no adopted-together text on a bonded pair' );

// (RA-09) Theme source contract: version bump, shortcode registration,
// deterministic ordering, no hardcoded IDs, and both badges styled in the
// new CSS block (page-id-20323 scoped): red bonded-badge (#934244 via
// --pfoa-color-brand) + green together-badge (#057205 via --pfoa-accent).
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, "add_shortcode( 'pfoa_recently_adopted'" ), 'source: shortcode registered' );
fixture_assert( false !== strpos( $functions_source, 'function pfoa_get_recently_adopted_events' ), 'source: selector present' );
fixture_assert( false !== strpos( $functions_source, 'function pfoa_render_recently_adopted_event_card' ), 'source: card renderer present' );
$selector_pos = strpos( $functions_source, 'function pfoa_get_recently_adopted_events' );
$renderer_pos = strpos( $functions_source, 'function pfoa_render_recently_adopted_event_card' );
fixture_assert( false !== $selector_pos && false !== $renderer_pos && $selector_pos < $renderer_pos, 'source: selector precedes renderer' );
$selector_body = substr( $functions_source, (int) $selector_pos, (int) $renderer_pos - (int) $selector_pos );
fixture_assert( false !== strpos( $selector_body, 'usort' ), 'source: deterministic sort in selector' );
foreach ( array( '24653', '24654', '24655', '24559' ) as $live_id ) {
	fixture_assert( false === strpos( $functions_source, "'_pfoa_event_date', " . $live_id ), 'source: no hardcoded event ID ' . $live_id );
}
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
	fixture_assert( 1 === preg_match( '/Version:\s*0\.1\.223/', $css ), 'css: theme version 0.1.223' );
$block_pos = strpos( $css, 'body.page-id-20323 .pfoa-recently-adopted' );
fixture_assert( false !== $block_pos, 'css: page-id-20323 scoped recently-adopted block' );
$css_block = substr( $css, (int) $block_pos, 15000 );
fixture_require( false !== strpos( $css_block, 'pfoa-adoption-event-together-badge' ), 'CSS Recently Adopted green scope', 'expected green together badge styled in scope' );
fixture_require( false !== strpos( $css_block, 'pfoa-adoption-event-bonded-badge' ), 'CSS Recently Adopted red scope', 'expected red bonded badge styled in scope' );
fixture_require( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-bonded-badge\s*\{\s*background:\s*var\(--pfoa-color-brand\)/', $css_block ), 'CSS Recently Adopted red scope', 'expected bonded badge keeps brand red (#934244)' );
fixture_require( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-together-badge\s*\{\s*background:\s*var\(--pfoa-cta-primary\)/', $css_block ), 'CSS Recently Adopted green scope', 'expected together badge keeps CTA green (#057205)' );
fixture_assert( 1 === preg_match( '/pfoa-adoption-event--bonded,\s*body\.page-id-20323 \.pfoa-recently-adopted > \.pfoa-adoption-event--together\s*\{\s*position:\s*relative\s*;/', $css_block ), 'css: bonded+together cards establish badge containing block' );
fixture_assert( false !== strpos( $css_block, 'position: absolute' ), 'css: badge stays absolute (zero flow)' );
fixture_assert( false !== strpos( $css_block, 'translate(-50%, 50%)' ), 'css: badge stays centered-straddle' );
fixture_assert( false !== strpos( $css_block, '@media' ), 'css: responsive rules in scope' );

// (RA-10) Plugin-down fail-open: real cat_post_ids with no helper doubles
// and no stored flags are retained (null, never false) and reported
// snapshot-unavailable; nothing is dropped.
$GLOBALS['fixture_event_ids'] = array( 101, 102 );
fixture_set_event( 101, 'uuid-plugin-down-single', '2026-10-13', array( fixture_member( 141, 'Solo', 7701 ) ) );
fixture_set_event(
	102,
	'uuid-plugin-down-pair',
	'2026-10-12',
	array(
		fixture_member( 142, 'Duo1', 7702 ),
		fixture_member( 143, 'Duo2', 7703 ),
	)
);
$top_down = pfoa_get_recently_adopted_events( 2 );
fixture_assert( array( 101, 102 ) === fixture_ids( $top_down ), 'plugin-down: unresolvable members retained fail-open' );
$down_report = isset( $GLOBALS['pfoa_recently_adopted_unresolvable_report'] ) ? $GLOBALS['pfoa_recently_adopted_unresolvable_report'] : null;
fixture_assert( is_array( $down_report ), 'plugin-down: report exposed via $GLOBALS fallback (no apply_filters double)' );
$down_codes = array();
foreach ( (array) $down_report as $down_row ) {
	$down_codes[] = (string) $down_row['code'];
}
fixture_assert( in_array( 'snapshot-unavailable', $down_codes, true ), 'plugin-down: snapshot-unavailable reported' );
fixture_assert( ! in_array( 'returned', $down_codes, true ), 'plugin-down: absence of flags never proves return' );
foreach ( (array) $down_report as $down_row ) {
	fixture_assert( isset( $down_row['code'], $down_row['event_id'], $down_row['cat_post_id'], $down_row['detail'] ), 'plugin-down: report rows carry code/event_id/cat_post_id/detail' );
}

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during selection/presentation' );

fwrite( STDOUT, "PASS: theme recently adopted selection fixture (DESC+tiebreak, cross-year, limits, exclusions, badges/captions/grouping, CSS scope, no writes)\n" );
