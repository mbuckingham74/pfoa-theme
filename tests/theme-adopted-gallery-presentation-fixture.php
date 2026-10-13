<?php
/**
 * Focused fixture for the Adopted Gallery presentation contract (PFOA 0.1.121,
 * AG-01..AG-12).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: visible historical date beneath every event title
 * (single + multi, formatted from stored _pfoa_event_date, raw fallback),
 * strict DESC chronology with stable same-date tiebreak, per-participant
 * visible event-specific photos incl. repeats across events (never
 * cross-event suppression, never lightbox-only), one wrapper per UUID (never
 * deduped), uniform-photo shared var + square geometry in CSS, card
 * spans/grouping, filler markup contract (JS + CSS source checks), badge and
 * caption preservation, exact-ID suppression intact, and no DB writes.
 *
 * Run with:
 *   php tests/theme-adopted-gallery-presentation-fixture.php
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

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
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
fixture_assert( function_exists( 'pfoa_adoption_event_format_date' ), 'date helper exists' );

// Date helper unit checks.
fixture_assert( '' === pfoa_adoption_event_format_date( '' ), 'date helper: empty in, empty out' );
fixture_assert( 'September 19, 2026' === pfoa_adoption_event_format_date( '2026-09-19' ), 'date helper: formats stored date' );
fixture_assert( 'not-a-date' === pfoa_adoption_event_format_date( 'not-a-date' ), 'date helper: unparseable renders raw' );

// Reference-case event set: Sep trio (Spencer/Thompson/Wilbur), Jul quad
// (Amelia/Cami/Greyer/Gypsy), Jun pair, Apr pair, same-date trio + single
// for tiebreak. Distinct snapshots here so each event anchors independently;
// the repeat scenario below reuses shared images on purpose (valid repeated
// photographs, AG-01/AG-02).
$sep_trio = array( fixture_member( 501, 'Spencer' ), fixture_member( 502, 'Thompson' ), fixture_member( 503, 'Wilbur' ) );
$jul_quad = array( fixture_member( 601, 'Amelia' ), fixture_member( 602, 'Cami' ), fixture_member( 603, 'Greyer' ), fixture_member( 604, 'Gypsy' ) );
$jun_pair = array( fixture_member( 701, 'Spencer' ), fixture_member( 702, 'Thompson' ) );
$apr_pair = array( fixture_member( 703, 'Spencer' ), fixture_member( 704, 'Thompson' ) );
$same_a   = array( fixture_member( 801, 'Olyvia' ), fixture_member( 802, 'Oly' ), fixture_member( 803, 'Sally' ) );
$same_b   = array( fixture_member( 901, 'Solo' ) );

$GLOBALS['fixture_event_ids'] = array( 10, 20, 30, 40, 50, 60 );
fixture_set_event( 10, 'uuid-sep', '2026-09-19', $sep_trio );
fixture_set_event( 20, 'uuid-jul', '2026-07-03', $jul_quad );
fixture_set_event( 30, 'uuid-jun', '2026-06-17', $jun_pair );
fixture_set_event( 40, 'uuid-apr', '2026-04-28', $apr_pair );
fixture_set_event( 50, 'uuid-same-high', '2026-05-01', $same_a );
fixture_set_event( 60, 'uuid-same-low', '2026-05-01', $same_b );

// (AG-03/AG-04) Chronology-first: with a newest-first legacy gallery each
// event re-enters at its own snapshot position, so rendered order is strict
// DESC with the stable same-date tiebreak (ID DESC: event 60 before 50),
// and placement never reorders for packing.
$content = fixture_gallery( array( 100, 501, 502, 503, 601, 602, 603, 604, 701, 702, 901, 801, 802, 803, 703, 704, 102 ) );
$out     = pfoa_present_adoption_events( $content );
$pos_sep = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-sep"', 'chrono: sep trio placed' );
$pos_jul = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-jul"', 'chrono: jul quad placed' );
$pos_jun = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-jun"', 'chrono: jun pair placed' );
$pos_sb  = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-same-low"', 'chrono: same-date low placed' );
$pos_sa  = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-same-high"', 'chrono: same-date high placed' );
$pos_apr = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-apr"', 'chrono: apr pair placed' );
fixture_assert( $pos_sep < $pos_jul && $pos_jul < $pos_jun && $pos_jun < $pos_sb && $pos_sb < $pos_sa && $pos_sa < $pos_apr, 'chrono: strict DESC with stable same-date tiebreak, never reordered' );

// (AG-01/AG-02 repeats) Jun/Apr share image IDs 701/702 (same cat, same
// photograph, two occurrences): both events render independently with both
// visible photos — valid repeats are coverage, never duplicates.
$GLOBALS['fixture_event_ids'] = array( 30, 40 );
fixture_set_event( 30, 'uuid-jun', '2026-06-17', $jun_pair );
fixture_set_event( 40, 'uuid-apr', '2026-04-28', array( fixture_member( 701, 'Spencer' ), fixture_member( 702, 'Thompson' ) ) );
$out_repeat = pfoa_present_adoption_events( fixture_gallery( array( 100, 701, 702, 102 ) ) );
fixture_assert( 1 === substr_count( $out_repeat, 'data-pfoa-adoption-event="uuid-jun"' ), 'repeat: jun renders exactly once' );
fixture_assert( 1 === substr_count( $out_repeat, 'data-pfoa-adoption-event="uuid-apr"' ), 'repeat: apr renders exactly once (never deduped)' );
fixture_assert( 2 === substr_count( $out_repeat, 'med701.jpg' ), 'repeat: Spencer photo visible in both occurrences' );
fixture_assert( 2 === substr_count( $out_repeat, 'med702.jpg' ), 'repeat: Thompson photo visible in both occurrences' );

// (AG-01) One wrapper per UUID, never deduped: each exactly once.
foreach ( array( 'uuid-sep', 'uuid-jul', 'uuid-jun', 'uuid-apr', 'uuid-same-high', 'uuid-same-low' ) as $uuid ) {
	fixture_assert( 1 === substr_count( $out, 'data-pfoa-adoption-event="' . $uuid . '"' ), 'grouping: ' . $uuid . ' renders exactly once' );
}

// (AG metadata) Visible historical date beneath every event title.
foreach ( array(
	'uuid-sep' => 'September 19, 2026',
	'uuid-jul' => 'July 3, 2026',
	'uuid-jun' => 'June 17, 2026',
	'uuid-apr' => 'April 28, 2026',
) as $uuid => $date_text ) {
	$wrapper_pos = fixture_pos( $out, 'data-pfoa-adoption-event="' . $uuid . '"', 'date: ' . $uuid );
	$date_pos    = fixture_pos( $out, '<p class="pfoa-adoption-event-date">' . $date_text . '</p>', 'date: ' . $date_text );
	fixture_assert( $date_pos > $wrapper_pos, 'date: ' . $date_text . ' sits inside ' . $uuid );
	$title_pos = strpos( $out, 'pfoa-adoption-event-title', $wrapper_pos );
	fixture_assert( false !== $title_pos && $title_pos < $date_pos, 'date: beneath title in ' . $uuid );
}
fixture_assert( 6 === substr_count( $out, 'pfoa-adoption-event-date' ), 'date: every event carries a visible date' );

// (AG-02) Every participant has a visible event-specific photo: each member
// figure renders its own <img> directly on the page (not lightbox-only).
foreach ( array( 501, 502, 503, 601, 602, 603, 604, 701, 702, 703, 704, 801, 802, 803, 901 ) as $image_id ) {
	fixture_assert( 1 === substr_count( $out, 'med' . $image_id . '.jpg' ), 'coverage: image ' . $image_id . ' visible exactly once in its event' );
}
fixture_assert( 15 === substr_count( $out, 'pfoa-adoption-event-member"' ), 'coverage: all 15 member figures rendered (3+4+2+2+3+1)' );

// (AG-12) Exact-ID suppression intact: claimed snapshots leave the legacy
// gallery; unclaimed survivors (100, 102) remain exactly once.
fixture_assert( false === strpos( $out, 'gallery-1-501' ), 'suppression: claimed snapshot removed' );
fixture_assert( false !== strpos( $out, 'gallery-1-100' ), 'suppression: unclaimed survivor 100 kept' );
fixture_assert( false !== strpos( $out, 'gallery-1-102' ), 'suppression: unclaimed survivor 102 kept' );

// (AG-12) Badges/captions/associations preserved: pairs carry bonded-or-
// together modifiers, trios/quad carry together, single caption kept.
fixture_assert( false !== strpos( $out, 'Spencer &amp; Thompson &amp; Wilbur' ), 'captions: trio combined title kept' );
fixture_assert( false !== strpos( $out, 'Amelia &amp; Cami &amp; Greyer &amp; Gypsy' ), 'captions: quad combined title kept' );
fixture_assert( 1 === substr_count( $out, '<figcaption class="pfoa-adoption-event-caption">Solo</figcaption>' ), 'captions: single caption kept' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-3' ), 'grouping: trio count class' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-n' ), 'grouping: quad count class' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-1' ), 'grouping: single count class' );

// (AG-05/AG-07) CSS source contract: shared photo var, container scaling,
// square geometry, card spans, no hardcoded event IDs.
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_assert( false !== strpos( $css, '--pfoa-adopted-photo' ), 'css: shared photo var defined' );
fixture_assert( false !== strpos( $css, 'container-type: inline-size' ), 'css: gallery is a query container' );
fixture_assert( false !== strpos( $css, '100cqw' ), 'css: photo derives from gallery width' );
fixture_assert( false !== strpos( $css, '.pfoa-adoption-event-thumb img' ), 'css: event thumb rule present' );
fixture_assert( false !== strpos( $css, '.entry-content .gallery-icon img' ), 'css: legacy rule present' );
fixture_assert( false !== strpos( $css, '.pfoa-adoption-event-count-n' ), 'css: quad span rule present' );
fixture_assert( false !== strpos( $css, '.pfoa-adoption-event-date' ), 'css: date rule present' );
fixture_assert( false === strpos( $css, 'data-pfoa-adoption-event="07b77926' ), 'css: no hardcoded event IDs (§14)' );
fixture_assert( 1 === preg_match( '/--pfoa-adopted-photo:\s*123px/', $css ), 'css: 6-slot Sol fallback 123px' );
fixture_assert( 1 === preg_match( '/--pfoa-adopted-photo:\s*204px/', $css ), 'css: 4-slot Sol fallback 204px' );
fixture_assert( 1 === preg_match( '/--pfoa-adopted-photo:\s*191px/', $css ), 'css: 3-slot Sol fallback ~191px' );
fixture_assert( 1 === preg_match( '/--pfoa-adopted-photo:\s*142px/', $css ), 'css: 2-slot Sol fallback 142px' );

// (AG-06/AG-09) Filler contract: JS present, decorative-only, aria-hidden,
// data-pfoa-filler + data-pfoa-filler-span, explicit span remainder
// (gridColumnStart 'span N', gridColumnEnd 'auto', never -1), full row
// height via stretch, single black-paw inline SVG (no logo img, no
// wordmark), single-per-row with pre-clear, never touches events.
$filler_path = dirname( __DIR__ ) . '/pfoa-theme/assets/js/pfoa-adopted-gallery-filler.js';
fixture_assert( file_exists( $filler_path ), 'filler: script file exists' );
$filler = (string) file_get_contents( $filler_path );
fixture_assert( false !== strpos( $filler, 'data-pfoa-filler' ), 'filler: data-pfoa-filler marker' );
fixture_assert( false !== strpos( $filler, 'data-pfoa-filler-span' ), 'filler: data-pfoa-filler-span marker' );
fixture_assert( false !== strpos( $filler, "aria-hidden" ), 'filler: aria-hidden' );
fixture_assert( false !== strpos( $filler, 'pfoa-adopted-filler' ), 'filler: filler class' );
fixture_assert( false !== strpos( $filler, 'gridColumnStart' ), 'filler: explicit gridColumnStart' );
fixture_assert( false !== strpos( $filler, "'span '" ), 'filler: explicit span remainder (span N)' );
fixture_assert( false !== strpos( $filler, 'gridColumnEnd' ), 'filler: gridColumnEnd present' );
fixture_assert( false !== strpos( $filler, "'auto'" ), 'filler: gridColumnEnd auto' );
fixture_assert( false === strpos( $filler, "'-1'" ), 'filler: never spans to -1' );
$filler_css_pos = strpos( $css, '.pfoa-adopted-filler' );
$stretch_pos    = ( false === $filler_css_pos ) ? false : strpos( $css, 'align-self: stretch', $filler_css_pos );
fixture_assert( false !== $stretch_pos, 'css: filler full row height via stretch' );
fixture_assert( false !== stripos( $filler, '<svg' ) || false !== strpos( $filler, 'createElementNS' ), 'filler: black-paw inline SVG' );
fixture_assert( false === strpos( $filler, "createElement('img')" ), 'filler: no logo img element' );
fixture_assert( false === strpos( $filler, 'custom-logo' ), 'filler: no header-logo src dependency' );
fixture_assert( false === strpos( $filler, 'wordmark' ), 'filler: no wordmark fallback' );
fixture_assert( false === strpos( $filler, "'PFOA'" ), 'filler: no wordmark text' );
fixture_assert( false !== strpos( $filler, 'clearFillers' ), 'filler: clears before placement (no duplicates)' );
fixture_assert( false === strpos( $filler, 'removeChild(children' ), 'filler: never removes event nodes' );
fixture_assert( false === strpos( $filler, 'insertBefore(event' ), 'filler: never reorders events' );
fixture_assert( false !== strpos( $filler, 'PfoaAdoptedGalleryFiller' ), 'filler: public surface' );
fixture_assert( false !== strpos( $css, '.pfoa-adopted-filler' ), 'css: filler style present' );
fixture_assert( false !== strpos( $css, 'pointer-events: none' ), 'css: filler noninteractive' );

// Enqueue wiring: filler script enqueued presentation-only on the target page.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, 'pfoa-adopted-gallery-filler' ), 'enqueue: filler handle wired' );
fixture_assert( false !== strpos( $functions_source, 'pfoa_adoption_event_member_media' ), 'media helper intact' );
fixture_assert( false !== strpos( $functions_source, 'window.PFOAGalleryLightbox' ) || false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/assets/js/pfoa-cat-dialog-controller.js' ), 'window.PFOAGalleryLightbox' ), 'lightbox: external overlay ownership intact' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

fwrite( STDOUT, "PASS: theme adopted gallery presentation fixture (dates, DESC chronology + tiebreak, per-event coverage + repeats, grouping, badges/captions, uniform-photo CSS, filler contract, no writes)\n" );
