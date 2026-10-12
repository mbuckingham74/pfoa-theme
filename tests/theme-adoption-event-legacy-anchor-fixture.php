<?php
/**
 * Focused fixture for the adoption-event legacy-gallery anchor (PFOA 0.1.120).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: anchor-wins placement, anchor suppression, anchor ==
 * member invalidation, shared-anchor invalidation, missing-anchor fallback,
 * malformed-meta fallback, and byte-identical no-anchor behavior.
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

// (1) Anchor-wins placement + (2) anchor suppression.
// Gallery: S1=100, anchor=23235, S2=101, members 23262/23263, S3=102.
// Valid anchor inserts before S2; member-order would insert before S3.
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23235 );
$content = fixture_gallery( array( 100, 23235, 101, 23262, 23263, 102 ) );
$out     = pfoa_present_adoption_events( $content );
$pos_event = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-1"', 'anchor-wins: wrapper rendered' );
$pos_s2    = fixture_pos( $out, 'gallery-1-101', 'anchor-wins: S2 survivor present' );
$pos_s3    = fixture_pos( $out, 'gallery-1-102', 'anchor-wins: S3 survivor present' );
fixture_assert( $pos_event < $pos_s2, 'anchor-wins: wrapper inserted at anchor (before S2), not at member order' );
fixture_assert( false === strpos( $out, 'gallery-1-23235' ), 'anchor suppression: standalone anchor item removed' );
fixture_assert( false === strpos( $out, 'gallery-1-23262' ), 'anchor-wins: member item 23262 removed' );
fixture_assert( false === strpos( $out, 'gallery-1-23263' ), 'anchor-wins: member item 23263 removed' );
fixture_assert( 2 === substr_count( $out, 'pfoa-adoption-event-member"' ), 'anchor suppression: grouped event still renders both member images' );
fixture_assert( 1 === substr_count( $out, 'data-pfoa-adoption-event="uuid-1"' ), 'anchor-wins: event renders exactly once (no merge)' );

// (3) Anchor == member image invalidates fail-closed (member-order path).
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23262 );
$out_invalid = pfoa_present_adoption_events( $content );
$pos_event   = fixture_pos( $out_invalid, 'data-pfoa-adoption-event="uuid-1"', 'anchor==member: wrapper rendered' );
$pos_s2      = fixture_pos( $out_invalid, 'gallery-1-101', 'anchor==member: S2 present' );
$pos_s3      = fixture_pos( $out_invalid, 'gallery-1-102', 'anchor==member: S3 present' );
fixture_assert( $pos_s2 < $pos_event && $pos_event < $pos_s3, 'anchor==member: invalid anchor falls back to member order (between S2 and S3)' );
fixture_assert( false === strpos( $out_invalid, 'gallery-1-23262' ), 'anchor==member: member item still suppressed as member' );
fixture_assert( false !== strpos( $out_invalid, 'gallery-1-23235' ), 'anchor==member: invalidated anchor node survives (not suppressed)' );

// Anchor == member of ANOTHER event also invalidates.
$GLOBALS['fixture_event_ids'] = array( 24607, 24608 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 23264 );
fixture_set_event( 24608, 'uuid-2', '2026-02-01', array( fixture_member( 23264, 'Gamma' ), fixture_member( 23265, 'Delta' ) ) );
$content2 = fixture_gallery( array( 100, 23264, 101, 23262, 23263, 23265, 102 ) );
$out_cross = pfoa_present_adoption_events( $content2 );
fixture_assert( false !== strpos( $out_cross, 'data-pfoa-adoption-event="uuid-1"' ), 'cross-event anchor==member: uuid-1 rendered' );
$pos_e1 = fixture_pos( $out_cross, 'data-pfoa-adoption-event="uuid-1"', 'cross-event anchor==member: uuid-1 pos' );
$pos_s2 = fixture_pos( $out_cross, 'gallery-1-101', 'cross-event anchor==member: S2 pos' );
fixture_assert( $pos_s2 < $pos_e1, 'cross-event anchor==member: uuid-1 falls back to member order (after S2)' );

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
$pos_s2 = fixture_pos( $out_shared, 'gallery-1-101', 'shared-anchor: S2 pos' );
fixture_assert( $pos_s2 < $pos_e1 && $pos_s2 < $pos_e2, 'shared-anchor: both events fall back to member order (after S2)' );

// (5) Missing anchor (not in gallery DOM) falls back byte-identical to no-anchor.
$GLOBALS['fixture_event_ids'] = array( 24607 );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1, 99999 );
$out_missing = pfoa_present_adoption_events( $content );
fixture_set_event( 24607, 'uuid-1', '2026-03-02', $members_e1 );
$out_noanchor = pfoa_present_adoption_events( $content );
fixture_assert( $out_missing === $out_noanchor, 'missing-anchor: output byte-identical to no-anchor behavior' );
$pos_event = fixture_pos( $out_missing, 'data-pfoa-adoption-event="uuid-1"', 'missing-anchor: wrapper rendered' );
$pos_s3    = fixture_pos( $out_missing, 'gallery-1-102', 'missing-anchor: S3 present' );
fixture_assert( $pos_event < $pos_s3, 'missing-anchor: member-order placement preserved' );

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

// (7) No-anchor baseline behavior unchanged: member-order placement,
// member suppression, and prepend fallback when no gallery exists.
$pos_event = fixture_pos( $out_noanchor, 'data-pfoa-adoption-event="uuid-1"', 'no-anchor: wrapper rendered' );
$pos_s2    = fixture_pos( $out_noanchor, 'gallery-1-101', 'no-anchor: S2 present' );
$pos_s3    = fixture_pos( $out_noanchor, 'gallery-1-102', 'no-anchor: S3 present' );
fixture_assert( $pos_s2 < $pos_event && $pos_event < $pos_s3, 'no-anchor: member-order placement unchanged' );
fixture_assert( false === strpos( $out_noanchor, 'gallery-1-23262' ), 'no-anchor: member suppression unchanged' );
$out_plain = pfoa_present_adoption_events( '<p>Hello</p>' );
fixture_assert( false !== strpos( $out_plain, 'data-pfoa-adoption-event="uuid-1"' ), 'no-anchor fallback: event renders without gallery' );
fixture_assert( strpos( $out_plain, 'data-pfoa-adoption-event="uuid-1"' ) < strpos( $out_plain, 'Hello' ), 'no-anchor fallback: event prepended before content' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

// Source-level guards: canonical getter preferred, raw meta fallback, no writes.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, 'pfoa_adoption_event_get_legacy_anchor_image_id' ), 'canonical anchor getter preferred when present' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_legacy_anchor_image_id' ), 'raw anchor meta fallback present' );

fwrite( STDOUT, "PASS: theme adoption event legacy anchor fixture (anchor-wins, suppression, anchor==member invalid, shared-anchor invalid, missing-anchor fallback, malformed-meta fallback, no-anchor unchanged, no writes)\n" );
