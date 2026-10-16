<?php
/**
 * Companion fixture: Recently Adopted return / re-adoption filtering.
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Plugin helpers pfoa_cat_is_adopted(),
 * pfoa_cat_get_current_adoption_event_id(), pfoa_cat_is_bonded() and
 * pfoa_cat_get_bonded_pair() are stubbed via $GLOBALS maps so currency
 * (link-first, forward-scan fallback) and liveness (adopted flag) paths are
 * deterministic.
 *
 * Covers: single return drop, bonded-pair return drop-whole (never
 * downgrade), partial Adopted-Together member filtering (order kept, badge
 * follows), full Together drop, re-adoption supersede via current-event
 * link, supersede via forward-scan fallback (link 0), fill past exclusions
 * to the limit (validate→sort→filter→fill, never slice-then-filter),
 * unresolvable report codes via apply_filters capture, and no DB writes.
 *
 * Run with:
 *   php tests/theme-recently-adopted-currency-fixture.php
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

$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_event_ids']       = array();
$GLOBALS['fixture_meta']            = array();
$GLOBALS['fixture_shortcodes']      = array();
$GLOBALS['fixture_adopt_map']       = array();
$GLOBALS['fixture_link_map']        = array();
$GLOBALS['fixture_bonded_ids']      = array();
$GLOBALS['fixture_bonded_pairs']    = array();
$GLOBALS['fixture_captured_report'] = null;

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function apply_filters( $hook, $value = null ) {
	if ( 'pfoa_recently_adopted_unresolvable' === (string) $hook ) {
		$GLOBALS['fixture_captured_report'] = $value;
	}

	return $value;
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

// Plugin-helper doubles: liveness + currency + bonded, all map-driven.
function pfoa_cat_is_adopted( $cat_id ) {
	$cat_id = (int) $cat_id;

	if ( array_key_exists( $cat_id, $GLOBALS['fixture_adopt_map'] ) ) {
		return (bool) $GLOBALS['fixture_adopt_map'][ $cat_id ];
	}

	return true;
}
function pfoa_cat_get_current_adoption_event_id( $cat_id ) {
	$cat_id = (int) $cat_id;

	if ( array_key_exists( $cat_id, $GLOBALS['fixture_link_map'] ) ) {
		return (int) $GLOBALS['fixture_link_map'][ $cat_id ];
	}

	return 0;
}
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, $GLOBALS['fixture_bonded_ids'], true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	$cat_id = (int) $cat_id;

	foreach ( $GLOBALS['fixture_bonded_pairs'] as $pair ) {
		if ( in_array( $cat_id, $pair, true ) ) {
			return $pair;
		}
	}

	return array();
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
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

function fixture_cats( $event ) {
	$cats = array();
	foreach ( $event['members'] as $member ) {
		$cats[] = (int) $member['cat_post_id'];
	}
	return $cats;
}

function fixture_report_codes( $report ) {
	$codes = array();
	foreach ( (array) $report as $row ) {
		$codes[] = (string) $row['code'];
	}
	return $codes;
}

fixture_assert( function_exists( 'pfoa_get_recently_adopted_events' ), 'selection function exists' );
fixture_assert( function_exists( 'pfoa_recently_adopted_is_currently_adopted' ), 'currency/liveness resolver exists' );

// Liveness map: false = returned. Unlisted cats default adopted (true).
$GLOBALS['fixture_adopt_map'] = array(
	1002 => false, // single return
	1004 => false, // bonded-pair return (partner 1003 stays adopted)
	1006 => false, // together partial return
	1008 => false, // together full return
	1009 => false, // together full return
);
// Currency map: cat 1010 re-adopted (old 96 superseded by 95). Cat 1011
// has NO link entry (returns 0) so the forward-scan fallback must
// supersede its older event 98 in favour of 97.
$GLOBALS['fixture_link_map'] = array(
	1010 => 95,
);
$GLOBALS['fixture_bonded_ids']   = array( 1003, 1004 );
$GLOBALS['fixture_bonded_pairs'] = array( array( 1003, 1004 ) );

$GLOBALS['fixture_event_ids'] = array( 90, 91, 92, 93, 94, 95, 96, 97, 98, 99 );
fixture_set_event( 90, 'uuid-single-keep', '2026-10-12', array( fixture_member( 201, 'Keep', 1001 ) ) );
fixture_set_event( 91, 'uuid-single-returned', '2026-10-11', array( fixture_member( 202, 'Gone', 1002 ) ) );
fixture_set_event(
	92,
	'uuid-bonded-returned',
	'2026-10-10',
	array(
		fixture_member( 203, 'BondA', 1003 ),
		fixture_member( 204, 'BondB', 1004 ),
	)
);
fixture_set_event(
	93,
	'uuid-together-partial',
	'2026-10-09',
	array(
		fixture_member( 205, 'Trio1', 1005 ),
		fixture_member( 206, 'TrioGone', 1006 ),
		fixture_member( 207, 'Trio2', 1007 ),
	)
);
fixture_set_event(
	94,
	'uuid-together-all-returned',
	'2026-10-08',
	array(
		fixture_member( 208, 'DuoGone1', 1008 ),
		fixture_member( 209, 'DuoGone2', 1009 ),
	)
);
fixture_set_event( 95, 'uuid-readopt-new', '2026-10-07', array( fixture_member( 210, 'ReNew', 1010 ) ) );
fixture_set_event( 96, 'uuid-readopt-old', '2026-10-05', array( fixture_member( 211, 'ReOld', 1010 ) ) );
fixture_set_event( 97, 'uuid-fallback-new', '2026-10-04', array( fixture_member( 212, 'FbNew', 1011 ) ) );
fixture_set_event( 98, 'uuid-fallback-old', '2026-10-03', array( fixture_member( 213, 'FbOld', 1011 ) ) );
fixture_set_event( 99, 'uuid-fill-depth', '2026-10-01', array( fixture_member( 214, 'Depth', 1012 ) ) );

// (RC-01) Default top-4 with fill: 90 keep, 91 returned-drop, 92 bonded
// drop-whole, 93 partial-keep, 94 all-returned drop, 95 re-adopted keep
// (96 superseded), 97 keep (98 superseded via forward scan).
$top4 = pfoa_get_recently_adopted_events();
fixture_assert( array( 90, 93, 95, 97 ) === fixture_ids( $top4 ), 'filter+fill: survivors are 90,93,95,97 in newest-first order' );

// (RC-02) Bonded return never downgrades: no singleton of 1003 anywhere.
foreach ( $top4 as $event ) {
	fixture_assert( array( 1003 ) !== fixture_cats( $event ), 'bonded: no downgraded singleton of returned pair' );
}

// (RC-03) Partial Together: returned member dropped, order + badge kept.
fixture_assert( array( 1005, 1007 ) === fixture_cats( $top4[1] ), 'together: false member dropped, true members keep stored order' );
fixture_assert( false === ( $top4[1]['bonded_pair'] ?? null ) || empty( $top4[1]['bonded_pair'] ), 'together: bonded_pair flag untouched (still falsy)' );

// (RC-04) Fill depth: limit 6 backfills past exclusions to 99.
$top6 = pfoa_get_recently_adopted_events( 6 );
fixture_assert( array( 90, 93, 95, 97, 99 ) === fixture_ids( $top6 ), 'fill: limit 6 yields five survivors (99 backfilled)' );

// (RC-05) Report codes exposed via filter: returned + superseded present,
// no fail-open codes (every fixture cat resolved via doubles).
$report = $GLOBALS['fixture_captured_report'];
fixture_assert( is_array( $report ), 'report: filter received an array' );
$codes = fixture_report_codes( $report );
foreach ( array( 'returned', 'superseded' ) as $expected_code ) {
	fixture_assert( in_array( $expected_code, $codes, true ), 'report: code ' . $expected_code . ' present' );
}
fixture_assert( ! in_array( 'snapshot-unavailable', $codes, true ), 'report: no snapshot-unavailable when doubles resolve' );
fixture_assert( ! in_array( 'unresolvable-anonymous', $codes, true ), 'report: no anonymous members in this set' );
// Superseded pair is exactly the re-adopted + fallback old events.
$superseded_events = array();
foreach ( (array) $report as $row ) {
	if ( 'superseded' === (string) $row['code'] ) {
		$superseded_events[] = (int) $row['event_id'];
	}
}
sort( $superseded_events );
fixture_assert( array( 96, 98 ) === $superseded_events, 'report: superseded events are exactly 96 (link) and 98 (forward-scan)' );

// (RC-06) Render: filtered Together still earns the green badge (2 members
// remain), singletons stay badge-free, excluded events never render.
$out = pfoa_recently_adopted_events( array( 'limit' => 4 ) );
fixture_assert( 4 === substr_count( $out, 'role="group"' ), 'render: four surviving cards' );
fixture_assert( false === strpos( $out, 'uuid-single-returned' ), 'render: returned single absent' );
fixture_assert( false === strpos( $out, 'uuid-bonded-returned' ), 'render: returned bonded pair absent' );
fixture_assert( false === strpos( $out, 'uuid-together-all-returned' ), 'render: fully-returned together absent' );
fixture_assert( false === strpos( $out, 'uuid-readopt-old' ), 'render: superseded old adoption absent' );
fixture_assert( false === strpos( $out, 'TrioGone' ), 'render: returned together member absent' );
fixture_assert( false !== strpos( $out, 'Trio1 &amp; Trio2' ), 'render: filtered together keeps combined title in order' );
fixture_assert( 1 === substr_count( $out, 'pfoa-adoption-event-together-badge' ), 'render: filtered together keeps one green badge' );

// No writes, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during currency/liveness filtering' );

fwrite( STDOUT, "PASS: recently adopted currency/liveness fixture (single/bonded/together returns, re-adoption + forward-scan supersede, fill, report, no writes)\n" );
