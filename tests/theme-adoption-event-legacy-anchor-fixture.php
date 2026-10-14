<?php
/**
 * Focused fixture for the adoption-event legacy-gallery anchor (PFOA 0.1.120,
 * placement demoted to suppression-only in 0.1.126).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: anchor suppression, chronological placement that no
 * anchor can displace (AG-03/AG-04), anchor == member invalidation, shared-
 * anchor invalidation, missing-anchor fallback, malformed-meta fallback,
 * quarantine of legacy survivors after all events, and byte-identical
 * no-anchor behavior.
 *
 * Run with:
 *   php tests/theme-adoption-event-legacy-anchor-fixture.php
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
		$page        = new WP_Post();
		$page->ID    = 22514;
		$page->post_type = 'page';
		$page->post_name = 'adopted2026';
		return $page;
	}
	$id = (int) $post;
	if ( isset( $GLOBALS['fixture_meta'][ $id ] ) ) {
		$event        = new WP_Post();
		$event->ID    = $id;
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

function fixture_set_event( $event_id, $uuid, $date, $members, $anchor = '__absent__' ) {
	$GLOBALS['fixture_meta'][ (int) $event_id ] = array(
		'_pfoa_event_year'    => '2026',
		'_pfoa_event_date'    => (string) $date,
		'_pfoa_event_uuid'    => (string) $uuid,
		'_pfoa_event_members' => $members,
	);
	if ( '__absent__' !== $anchor ) {
		$GLOBALS['fixture_meta'][ (int) $event_id ]['_pfoa_event_legacy_anchor_image_id'] = $anchor;
	}
}

function fixture_member( $image_id, $name ) {
	return array(
		'image_id'          => (int) $image_id,
		'cat_name_at_event' => (string) $name,
		'caption'           => (string) $name,
		'cat_post_id'       => 0,
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

function fixture_pos( $haystack, $needle, $label ) {
	$pos = strpos( (string) $haystack, (string) $needle );
	fixture_assert( false !== $pos, $label . ': marker found (' . $needle . ')' );
	return (int) $pos;
}

fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'present function exists' );

// Shared event: 24607 / uuid-1, members 23262 + 23263.
$members_e1 = array( fixture_member( 23262, 'Alpha' ), fixture_member( 23263, 'Beta' ) );

// (1) Anchor suppression + chronological placement (0.1.126: anchors
// are suppression-only and never set position).
// Gallery: S1=100, anchor=23235, S2=101, members 23262/23263, S3=102.
// The valid anchor's standalone node is suppressed; the single event
// renders first chronologically with all survivors quarantined after it
// in relative order — never at the anchor position.
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23235 );
$content = fixture_gallery( array( 100, 23235, 101, 23262, 23263, 102 ) );
$out     = pfoa_present_adoption_events( $content );
$pos_event = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-1"', 'suppression-only: wrapper rendered' );
$pos_s1    = fixture_pos( $out, 'gallery-1-100', 'suppression-only: S1 survivor present' );
$pos_s2    = fixture_pos( $out, 'gallery-1-101', 'suppression-only: S2 survivor present' );
$pos_s3    = fixture_pos( $out, 'gallery-1-102', 'suppression-only: S3 survivor present' );
fixture_assert( $pos_event < $pos_s1 && $pos_s1 < $pos_s2 && $pos_s2 < $pos_s3, 'suppression-only: event leads chronologically, survivors quarantined after in relative order' );
fixture_assert( false === strpos( $out, 'gallery-1-23235' ), 'anchor suppression: standalone anchor item removed' );
fixture_assert( false === strpos( $out, 'gallery-1-23262' ), 'suppression-only: member item 23262 removed' );
fixture_assert( false === strpos( $out, 'gallery-1-23263' ), 'suppression-only: member item 23263 removed' );
fixture_assert( 2 === substr_count( $out, 'pfoa-adoption-event-member"' ), 'anchor suppression: grouped event still renders both member images' );
fixture_assert( 1 === substr_count( $out, 'data-pfoa-adoption-event="uuid-1"' ), 'suppression-only: event renders exactly once (no merge)' );

// (1b) Anchor cannot displace chronology (0.1.126 regression): the older
// event owns a valid anchor at the very start of the gallery while the
// newer event's snapshots sit at the end — the newer event must still
// render first (date DESC), proving legacy positions never influence
// placement. The pre-0.1.126 anchor-order insertion failed this.
$GLOBALS['fixture_event_ids'] = array( 24607, 24608 );
fixture_set_event( 24607, 'uuid-1', '2026-02-01', $members_e1, 23235 );
fixture_set_event( 24608, 'uuid-2', '2026-03-02', array( fixture_member( 23264, 'Gamma' ), fixture_member( 23265, 'Delta' ) ) );
$content_chrono = fixture_gallery( array( 23235, 100, 23262, 23263, 23264, 23265 ) );
$out_chrono     = pfoa_present_adoption_events( $content_chrono );
$pos_old        = fixture_pos( $out_chrono, 'data-pfoa-adoption-event="uuid-1"', 'chrono-shield: older event placed' );
$pos_new        = fixture_pos( $out_chrono, 'data-pfoa-adoption-event="uuid-2"', 'chrono-shield: newer event placed' );
fixture_assert( $pos_new < $pos_old, 'chrono-shield: newer event leads despite older event owning the earliest anchor' );
fixture_assert( false === strpos( $out_chrono, 'gallery-1-23235' ), 'chrono-shield: anchor standalone node still suppressed' );

// (3) Anchor == member image invalidates fail-closed (chronological path).
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23262 );
$out_invalid = pfoa_present_adoption_events( $content );
$pos_event   = fixture_pos( $out_invalid, 'data-pfoa-adoption-event="uuid-1"', 'anchor==member: wrapper rendered' );
$pos_s1      = fixture_pos( $out_invalid, 'gallery-1-100', 'anchor==member: S1 present' );
fixture_assert( $pos_event < $pos_s1, 'anchor==member: event still leads chronologically; invalid anchor cannot displace' );
fixture_assert( false === strpos( $out_invalid, 'gallery-1-23262' ), 'anchor==member: member item still suppressed as member' );
fixture_assert( false !== strpos( $out_invalid, 'gallery-1-23235' ), 'anchor==member: invalidated anchor node survives (not suppressed)' );
$pos_anchor_node = fixture_pos( $out_invalid, 'gallery-1-23235', 'anchor==member: invalidated node pos' );
fixture_assert( $pos_anchor_node > $pos_event, 'anchor==member: invalidated node quarantined after the event with other survivors' );

// Anchor == member of ANOTHER event also invalidates.
$GLOBALS['fixture_event_ids'] = array( 24607, 24608 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23264 );
fixture_set_event( 24608, 'uuid-2', '2026-02-01', array( fixture_member( 23264, 'Gamma' ), fixture_member( 23265, 'Delta' ) ) );
$content2 = fixture_gallery( array( 100, 23264, 101, 23262, 23263, 23265, 102 ) );
$out_cross = pfoa_present_adoption_events( $content2 );
fixture_assert( false !== strpos( $out_cross, 'data-pfoa-adoption-event="uuid-1"' ), 'cross-event anchor==member: uuid-1 rendered' );
$pos_e1 = fixture_pos( $out_cross, 'data-pfoa-adoption-event="uuid-1"', 'cross-event anchor==member: uuid-1 pos' );
$pos_e2 = fixture_pos( $out_cross, 'data-pfoa-adoption-event="uuid-2"', 'cross-event anchor==member: uuid-2 pos' );
fixture_assert( $pos_e1 < $pos_e2, 'cross-event anchor==member: March event still precedes February event (date DESC, anchor cannot displace)' );

// (4) Shared anchor invalidates BOTH events; the shared node survives.
$GLOBALS['fixture_event_ids'] = array( 24607, 24608 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23235 );
fixture_set_event( 24608, 'uuid-2', '2026-02-01', array( fixture_member( 23264, 'Gamma' ), fixture_member( 23265, 'Delta' ) ), 23235 );
$content3 = fixture_gallery( array( 100, 23235, 101, 23262, 23263, 23264, 23265, 102 ) );
$out_shared = pfoa_present_adoption_events( $content3 );
fixture_assert( false !== strpos( $out_shared, 'gallery-1-23235' ), 'shared-anchor: invalidated anchor node survives (no suppression)' );
fixture_assert( 1 === substr_count( $out_shared, 'data-pfoa-adoption-event="uuid-1"' ), 'shared-anchor: uuid-1 renders once' );
fixture_assert( 1 === substr_count( $out_shared, 'data-pfoa-adoption-event="uuid-2"' ), 'shared-anchor: uuid-2 renders once (no merge)' );
$pos_e1 = fixture_pos( $out_shared, 'data-pfoa-adoption-event="uuid-1"', 'shared-anchor: uuid-1 pos' );
$pos_e2 = fixture_pos( $out_shared, 'data-pfoa-adoption-event="uuid-2"', 'shared-anchor: uuid-2 pos' );
$pos_s1 = fixture_pos( $out_shared, 'gallery-1-100', 'shared-anchor: S1 pos' );
fixture_assert( $pos_e1 < $pos_e2 && $pos_e2 < $pos_s1, 'shared-anchor: chronological events lead; shared node quarantined after with survivors' );

// (5) Missing anchor (not in gallery DOM) falls back byte-identical to no-anchor.
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 99999 );
$out_missing = pfoa_present_adoption_events( $content );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1 );
$out_noanchor = pfoa_present_adoption_events( $content );
fixture_assert( $out_missing === $out_noanchor, 'missing-anchor: output byte-identical to no-anchor behavior' );
$pos_event = fixture_pos( $out_missing, 'data-pfoa-adoption-event="uuid-1"', 'missing-anchor: wrapper rendered' );
$pos_s1    = fixture_pos( $out_missing, 'gallery-1-100', 'missing-anchor: S1 present' );
fixture_assert( $pos_event < $pos_s1, 'missing-anchor: chronological placement preserved (event leads, survivors after)' );

// (6) Malformed meta falls back byte-identical to no-anchor.
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 'not-an-id' );
$out_malformed_str = pfoa_present_adoption_events( $content );
fixture_assert( $out_malformed_str === $out_noanchor, 'malformed-meta (string): byte-identical to no-anchor' );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, array( 23235 ) );
$out_malformed_arr = pfoa_present_adoption_events( $content );
fixture_assert( $out_malformed_arr === $out_noanchor, 'malformed-meta (array): byte-identical to no-anchor' );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 0 );
$out_zero = pfoa_present_adoption_events( $content );
fixture_assert( $out_zero === $out_noanchor, 'zero anchor: byte-identical to no-anchor' );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, -23235 );
$out_negative = pfoa_present_adoption_events( $content );
fixture_assert( $out_negative === $out, 'negative anchor: absint semantics match explicit positive anchor' );

// (7) No-anchor baseline behavior unchanged: chronological placement,
// member suppression, and prepend fallback when no gallery exists.
$pos_event = fixture_pos( $out_noanchor, 'data-pfoa-adoption-event="uuid-1"', 'no-anchor: wrapper rendered' );
$pos_s1    = fixture_pos( $out_noanchor, 'gallery-1-100', 'no-anchor: S1 present' );
$pos_anchor_item = fixture_pos( $out_noanchor, 'gallery-1-23235', 'no-anchor: unclaimed 23235 present' );
$pos_s2    = fixture_pos( $out_noanchor, 'gallery-1-101', 'no-anchor: S2 present' );
$pos_s3    = fixture_pos( $out_noanchor, 'gallery-1-102', 'no-anchor: S3 present' );
fixture_assert( $pos_event < $pos_s1 && $pos_s1 < $pos_anchor_item && $pos_anchor_item < $pos_s2 && $pos_s2 < $pos_s3, 'no-anchor: event leads chronologically; all survivors quarantined after in relative order' );
fixture_assert( false === strpos( $out_noanchor, 'gallery-1-23262' ), 'no-anchor: member suppression unchanged' );
$out_plain = pfoa_present_adoption_events( '<p>Hello</p>' );
fixture_assert( false !== strpos( $out_plain, 'data-pfoa-adoption-event="uuid-1"' ), 'no-anchor fallback: event renders without gallery' );
fixture_assert( strpos( $out_plain, 'data-pfoa-adoption-event="uuid-1"' ) < strpos( $out_plain, 'Hello' ), 'no-anchor fallback: event prepended before content' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

// Source-level guards: canonical getter preferred, raw meta fallback, no
// writes — plus the 0.1.126 suppression-only contract: chronological
// sequential insertion present, anchor-ordered insertion gone.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, 'pfoa_adoption_event_get_legacy_anchor_image_id' ), 'canonical anchor getter preferred when present' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_legacy_anchor_image_id' ), 'raw anchor meta fallback present' );
fixture_assert( false !== strpos( $functions_source, 'suppression-only' ), 'anchors are suppression-only (never placement)' );
fixture_assert( false !== strpos( $functions_source, 'canonical_gallery' ), 'chronological sequential insertion into the canonical container' );
fixture_assert( false === strpos( $functions_source, 'ordered_uuids' ), 'anchor-ordered insertion removed (no legacy-order re-sort)' );
fixture_assert( false === strpos( $functions_source, 'event_anchors' ), 'per-event anchor position map removed (cannot displace chronology)' );

fwrite( STDOUT, "PASS: theme adoption event legacy anchor fixture (suppression-only, chrono-shield, anchor==member invalid, shared-anchor invalid, missing-anchor fallback, malformed-meta fallback, quarantine, no-anchor unchanged, no writes)\n" );
