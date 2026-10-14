<?php
/**
 * Focused fixture for the Senior Shadow caption-less legacy duplicate
 * (Step-4 stable-identity fallback hardening).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers:
 *
 *   S4 baseline: Event uuid-shadow / image 23270 + caption-less legacy
 *     duplicate carrying NO figcaption[id] / aria-describedby (empty
 *     post_excerpt) + unclaimed survivors 100, 102.
 *   S2 data-integrity: UUID/date validity + uniqueness, snapshot-only
 *     reads, exact-int claim, fail-closed validation, presentation-only
 *     (no storage writes; source asserts).
 *   S3 rendered: exactly-once per Event (UUID), correct visible date,
 *     date-DESC/ID-DESC order, photo counts, zero duplicate legacy node,
 *     no duplicated labels, missing caption yields no undated duplicate,
 *     survivors distinguishable + quarantined after events in relative
 *     order, no silent extras. All assertions key on UUIDs / attachment
 *     IDs, never cat names or filenames.
 *   S6 mutations (isolated, stubs only): duplicate via data-attribute,
 *     duplicate via ?attachment_id= href, disagreeing-fallback
 *     fail-closed, missing date, wrong date detectable, storage-order
 *     independence, missing image_id=0, duplicated name w/ distinct IDs.
 *
 * Pre-fix this fixture FAILS (caption-less duplicate survives: the
 * zero-duplicate-legacy assertions trip). Post-fix it PASSES.
 *
 * Run with:
 *   php tests/theme-adoption-event-senior-shadow-fixture.php
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

// Caption-less legacy node: what WP emits for a gallery item whose
// attachment has an empty post_excerpt — no figcaption[id], no
// img[aria-describedby]. Stable identity survives ONLY in the
// img wp-image-<ID> class (primary discovery sees attachment_id=0).
function fixture_shadow_item( $attachment_id ) {
	$id = (int) $attachment_id;
	return '<figure class="gallery-item"><img class="attachment-medium size-medium wp-image-' . $id . '" src="https://example.com/med' . $id . '.jpg" alt=""></figure>';
}

function fixture_shadow_data_attr_item( $attachment_id ) {
	$id = (int) $attachment_id;
	return '<figure class="gallery-item"><img data-attachment-id="' . $id . '" src="https://example.com/med' . $id . '.jpg" alt=""></figure>';
}

function fixture_shadow_href_item( $attachment_id ) {
	$id = (int) $attachment_id;
	return '<figure class="gallery-item"><a href="https://example.com/?attachment_id=' . $id . '"><img src="https://example.com/med' . $id . '.jpg" alt=""></a></figure>';
}

function fixture_shadow_disagreeing_item( $class_id, $data_id ) {
	$c = (int) $class_id;
	$d = (int) $data_id;
	return '<figure class="gallery-item"><img class="attachment-medium wp-image-' . $c . '" data-attachment-id="' . $d . '" src="https://example.com/med' . $c . '.jpg" alt=""></figure>';
}

function fixture_pos( $haystack, $needle, $label ) {
	$pos = strpos( (string) $haystack, (string) $needle );
	fixture_assert( false !== $pos, $label . ': marker found (' . $needle . ')' );
	return (int) $pos;
}

fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'present function exists' );

// ---------------------------------------------------------------------------
// S4 Senior Shadow baseline.
// Event uuid-shadow (id 24610, 2026-04-15, image 23270) + older Event
// uuid-older (id 24609, 2026-03-02, image 23263) + caption-less legacy
// duplicate of 23270 + unclaimed survivors 100, 102.
// ---------------------------------------------------------------------------
$GLOBALS['fixture_event_ids'] = array( 24610, 24609 );
fixture_set_event( 24610, 'uuid-shadow', '2026-04-15', array( fixture_member( 23270, 'Senior' ) ) );
fixture_set_event( 24609, 'uuid-older', '2026-03-02', array( fixture_member( 23263, 'Junior' ) ) );

$baseline_content = '<div class="gallery galleryid-1 gallery-columns-3">'
	. fixture_gallery_item( 100 )
	. fixture_shadow_item( 23270 )
	. fixture_gallery_item( 23263 )
	. fixture_gallery_item( 102 )
	. '</div>';
$baseline_out = pfoa_present_adoption_events( $baseline_content );

// S3 rendered assertions (UUIDs / attachment IDs only).
$pos_shadow = fixture_pos( $baseline_out, 'data-pfoa-adoption-event="uuid-shadow"', 'baseline: uuid-shadow wrapper rendered' );
$pos_older  = fixture_pos( $baseline_out, 'data-pfoa-adoption-event="uuid-older"', 'baseline: uuid-older wrapper rendered' );
fixture_assert( 1 === substr_count( $baseline_out, 'data-pfoa-adoption-event="uuid-shadow"' ), 'exactly-once: uuid-shadow renders exactly once (id 23270)' );
fixture_assert( 1 === substr_count( $baseline_out, 'data-pfoa-adoption-event="uuid-older"' ), 'exactly-once: uuid-older renders exactly once (id 23263)' );
fixture_assert( false !== strpos( $baseline_out, 'April 15, 2026' ), 'correct-date: uuid-shadow shows April 15, 2026' );
fixture_assert( false !== strpos( $baseline_out, 'March 2, 2026' ), 'correct-date: uuid-older shows March 2, 2026' );
fixture_assert( $pos_shadow < $pos_older, 'order: date-DESC/ID-DESC puts uuid-shadow (2026-04-15) before uuid-older (2026-03-02)' );
fixture_assert( 2 === substr_count( $baseline_out, 'pfoa-adoption-event-member"' ), 'photo-counts: two single-member events render two member photos' );
fixture_assert( false === strpos( $baseline_out, 'gallery-1-23270' ), 'zero-duplicate: no captioned legacy node for 23270' );
fixture_assert( false === strpos( $baseline_out, 'wp-image-23270' ), 'zero-duplicate: caption-less legacy duplicate of 23270 suppressed (missing caption must not leave a duplicate)' );
fixture_assert( false === strpos( $baseline_out, 'gallery-1-23263' ), 'zero-duplicate: member legacy node 23263 suppressed' );
fixture_assert( 2 === substr_count( $baseline_out, 'role="group"' ), 'no-duplicated-labels: two event groups, one label context each' );
fixture_assert( 2 === substr_count( $baseline_out, 'gallery-item' ), 'no-undated-duplicate: exactly two legacy survivors remain (missing caption invents no undated extra)' );
$pos_s100 = fixture_pos( $baseline_out, 'gallery-1-100', 'survivors-distinguishable: 100 present' );
$pos_s102 = fixture_pos( $baseline_out, 'gallery-1-102', 'survivors-distinguishable: 102 present' );
fixture_assert( $pos_older < $pos_s100 && $pos_s100 < $pos_s102, 'quarantine: survivors 100, 102 follow all events in original relative order' );
fixture_assert( 2 === substr_count( $baseline_out, 'data-pfoa-adoption-event="uuid-' ), 'no-silent-extras: exactly two event wrappers by UUID' );

// ---------------------------------------------------------------------------
// S2 data-integrity.
// ---------------------------------------------------------------------------
fixture_assert( 'uuid-shadow' !== 'uuid-older', 'uuid-uniqueness: event UUIDs distinct' );
fixture_assert( 1 === preg_match( '/^[A-Za-z0-9_-]+$/', 'uuid-shadow' ), 'uuid-validity: uuid-shadow well-formed' );
fixture_assert( false !== strtotime( '2026-04-15' ), 'date-validity: 2026-04-15 parseable' );
fixture_assert( false !== strtotime( '2026-03-02' ), 'date-validity: 2026-03-02 parseable' );
fixture_assert( 'April 15, 2026' === pfoa_adoption_event_format_date( '2026-04-15' ), 'date-render: stored date formats without mutation' );
fixture_assert( 23270 === (int) $GLOBALS['fixture_meta'][24610]['_pfoa_event_members'][0]['image_id'], 'exact-int-claim: snapshot image_id 23270 is an exact int' );
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'snapshot-only: no DB writes during presentation' );

$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_members' ), 'snapshot-only: members read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_uuid' ), 'snapshot-only: UUID read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_date' ), 'snapshot-only: date read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, 'claimed_ids' ), 'exact-int-claim: member claim map present' );
fixture_assert( false !== strpos( $functions_source, 'fail-closed' ), 'fail-closed: conflict validation documented' );
fixture_assert( false !== strpos( $functions_source, 'presentation only' ), 'presentation-only: Step-4 path is presentation-only' );
// Step-4 stable-identity fallback contract (absent pre-fix).
fixture_assert( false !== strpos( $functions_source, 'wp-image-' ), 'fallback-contract: wp-image-<ID> class fallback present' );
fixture_assert( false !== strpos( $functions_source, 'data-attachment-id' ), 'fallback-contract: data-attachment-id fallback present' );
fixture_assert( false !== strpos( $functions_source, 'attachment_id=' ), 'fallback-contract: ?attachment_id= href fallback present' );

// ---------------------------------------------------------------------------
// S6 mutations (each isolated; stubs only, no storage writes).
// ---------------------------------------------------------------------------

// M1a: duplicate legacy photo via data-attachment-id fallback.
$GLOBALS['fixture_event_ids'] = array( 24610 );
fixture_set_event( 24610, 'uuid-shadow', '2026-04-15', array( fixture_member( 23270, 'Senior' ) ) );
$out_m1a = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_shadow_data_attr_item( 23270 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m1a, 'data-pfoa-adoption-event="uuid-shadow"' ), 'M1a: event renders once with data-attr duplicate present' );
fixture_assert( false === strpos( $out_m1a, 'data-attachment-id="23270"' ), 'M1a: data-attribute duplicate of 23270 suppressed (detectable pre-fix as survivor)' );

// M1b: duplicate legacy photo via ?attachment_id= href fallback.
$out_m1b = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_shadow_href_item( 23270 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m1b, 'data-pfoa-adoption-event="uuid-shadow"' ), 'M1b: event renders once with href duplicate present' );
fixture_assert( false === strpos( $out_m1b, 'attachment_id=23270' ), 'M1b: href duplicate of 23270 suppressed (detectable pre-fix as survivor)' );

// M1c: disagreeing fallbacks stay fail-closed (node survives).
$out_m1c = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_shadow_disagreeing_item( 23270, 99999 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m1c, 'data-pfoa-adoption-event="uuid-shadow"' ), 'M1c: event renders once with disagreeing node present' );
fixture_assert( false !== strpos( $out_m1c, 'wp-image-23270' ), 'M1c: disagreeing fallbacks fail closed — ambiguous node survives (no silent removal)' );

// M2: missing date — event still renders once, fail-closed (no drop, no throw).
$GLOBALS['fixture_event_ids'] = array( 24610 );
fixture_set_event( 24610, 'uuid-shadow', '', array( fixture_member( 23270, 'Senior' ) ) );
$out_m2 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m2, 'data-pfoa-adoption-event="uuid-shadow"' ), 'M2: missing-date event still renders exactly once' );
fixture_assert( false !== strpos( $out_m2, 'gallery-1-100' ), 'M2: survivors intact when date missing' );

// M3: wrong date is detectable — rendered text follows the stored date.
fixture_set_event( 24610, 'uuid-shadow', '2026-01-01', array( fixture_member( 23270, 'Senior' ) ) );
$out_m3 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( false !== strpos( $out_m3, 'January 1, 2026' ), 'M3: wrong stored date renders visibly (wrongness detectable)' );
fixture_assert( false === strpos( $out_m3, 'April 15, 2026' ), 'M3: correct date absent when stored date is wrong (detector fires)' );

// M4: storage order cannot displace chronology — IDs ascending, dates descending.
$GLOBALS['fixture_event_ids'] = array( 24609, 24610 );
fixture_set_event( 24610, 'uuid-shadow', '2026-04-15', array( fixture_member( 23270, 'Senior' ) ) );
fixture_set_event( 24609, 'uuid-older', '2026-03-02', array( fixture_member( 23263, 'Junior' ) ) );
$out_m4 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 23270 ) . fixture_gallery_item( 23263 ) . fixture_gallery_item( 102 ) . '</div>'
);
$pos_m4_shadow = fixture_pos( $out_m4, 'data-pfoa-adoption-event="uuid-shadow"', 'M4: uuid-shadow placed' );
$pos_m4_older  = fixture_pos( $out_m4, 'data-pfoa-adoption-event="uuid-older"', 'M4: uuid-older placed' );
fixture_assert( $pos_m4_shadow < $pos_m4_older, 'M4: date-DESC order holds despite ascending storage order (wrong ordering detectable)' );

// M5: missing image_id=0 — wrapper renders, nothing claimed, survivors intact.
$GLOBALS['fixture_event_ids'] = array( 24610 );
fixture_set_event( 24610, 'uuid-shadow', '2026-04-15', array( fixture_member( 0, 'Senior' ) ) );
$out_m5 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m5, 'data-pfoa-adoption-event="uuid-shadow"' ), 'M5: image_id=0 event still renders exactly once' );
fixture_assert( false !== strpos( $out_m5, 'Photo unavailable' ), 'M5: missing photo renders placeholder (detectable, not silent)' );
fixture_assert( false !== strpos( $out_m5, 'gallery-1-100' ) && false !== strpos( $out_m5, 'gallery-1-102' ), 'M5: survivors intact when image_id missing' );

// M6: duplicated name with distinct IDs — no name-based merge/dedupe.
$GLOBALS['fixture_event_ids'] = array( 24611 );
fixture_set_event( 24611, 'uuid-dupe', '2026-05-01', array( fixture_member( 23270, 'Senior' ), fixture_member( 23271, 'Senior' ) ) );
$out_m6 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-1 gallery-columns-3">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 23270 ) . fixture_gallery_item( 23271 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m6, 'data-pfoa-adoption-event="uuid-dupe"' ), 'M6: same-name event renders exactly once (no merge by name)' );
fixture_assert( 2 === substr_count( $out_m6, 'pfoa-adoption-event-member"' ), 'M6: both distinct-ID photos render (no dedupe by name)' );
fixture_assert( false === strpos( $out_m6, 'gallery-1-23270' ) && false === strpos( $out_m6, 'gallery-1-23271' ), 'M6: both distinct IDs suppressed by exact ID, not by name' );

// No writes across all mutations.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

fwrite( STDOUT, "PASS: theme adoption event senior shadow fixture (caption-less duplicate suppressed, exactly-once, date-DESC, quarantine, fail-closed mutations, no writes)\n" );
