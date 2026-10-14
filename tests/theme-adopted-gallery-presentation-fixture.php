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
// PFOA 0.1.125 — redundant solo captions stay in markup for lightbox/a11y
// but hide visually (title already shows the same name); stored caption,
// alt, and data-pfoa-caption are never deleted.
fixture_assert( 1 === substr_count( $out, '<figcaption class="pfoa-adoption-event-caption screen-reader-text">Solo</figcaption>' ), 'captions: redundant solo caption hidden visually but kept in markup' );
fixture_assert( 0 === substr_count( $out, '<figcaption class="pfoa-adoption-event-caption">Solo</figcaption>' ), 'captions: no redundant visible solo caption' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-3' ), 'grouping: trio count class' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-n' ), 'grouping: quad count class' );
fixture_assert( false !== strpos( $out, 'pfoa-adoption-event-count-1' ), 'grouping: single count class' );

// (AG-12 universal green, PFOA 0.1.129) Singleton carries no badge; each
// of the 5 multi-cat events (2 trios, 1 quad, 2 pairs) carries exactly one
// green Adopted Together badge; no red bonded badge renders anywhere.
fixture_assert( 5 === substr_count( $out, 'pfoa-adoption-event-together-badge' ), 'badges: every multi-cat event carries the green badge (5 multis)' );
fixture_assert( 5 === substr_count( $out, 'Adopted Together' ), 'badges: green badge text on all multis' );
fixture_assert( 0 === substr_count( $out, 'pfoa-adoption-event-bonded-badge' ), 'badges: no red bonded badge on adopted gallery' );
$solo_badge_evt  = strpos( $out, 'data-pfoa-adoption-event="uuid-same-low"' );
$solo_badge_next = strpos( $out, 'data-pfoa-adoption-event="uuid-same-high"', (int) $solo_badge_evt );
$solo_badge_card = substr( $out, (int) $solo_badge_evt, (int) $solo_badge_next - (int) $solo_badge_evt );
fixture_assert( false === strpos( $solo_badge_card, 'badge' ), 'badges: singleton carries no badge' );

// (AG-12 universal green on TRUE bonded pairs, PFOA 0.1.129) A reciprocal
// bonded pair keeps its --bonded classification (class + metadata) but
// renders the same green badge — never the red one.
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, array( 701, 702 ), true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	if ( in_array( (int) $cat_id, array( 701, 702 ), true ) ) {
		return array( 701, 702 );
	}
	return array();
}
$GLOBALS['fixture_event_ids'] = array( 30 );
fixture_set_event(
	30,
	'uuid-jun-bonded',
	'2026-06-17',
	array(
		array(
			'image_id'          => 701,
			'cat_name_at_event' => 'Spencer',
			'caption'           => 'Spencer',
			'cat_post_id'       => 701,
		),
		array(
			'image_id'          => 702,
			'cat_name_at_event' => 'Thompson',
			'caption'           => 'Thompson',
			'cat_post_id'       => 702,
		),
	)
);
$out_bonded = pfoa_present_adoption_events( fixture_gallery( array( 701, 702 ) ) );
fixture_assert( false !== strpos( $out_bonded, 'pfoa-adoption-event--bonded' ), 'badges: true bonded pair keeps --bonded class' );
fixture_assert( false !== strpos( $out_bonded, 'pfoa-adoption-event-count-2' ), 'badges: bonded pair keeps count-2 class' );
fixture_assert( 1 === substr_count( $out_bonded, 'pfoa-adoption-event-together-badge' ), 'badges: bonded pair renders the green together badge' );
fixture_assert( 1 === substr_count( $out_bonded, 'Adopted Together' ), 'badges: bonded pair green badge text' );
fixture_assert( false === strpos( $out_bonded, 'pfoa-adoption-event-bonded-badge' ), 'badges: bonded pair renders no red badge' );
fixture_assert( false === strpos( $out_bonded, 'Bonded Pair' ), 'badges: no bonded-pair text on adopted gallery' );

// (PFOA 0.1.125 regression) Solo title + date remain within the solo card:
// members render first, then title, then date — all inside the same
// count-1 wrapper. The pre-fix order (title + date above members, outside
// the only bordered box) fails the members-before-title assertion.
$solo_wrapper = fixture_pos( $out, 'data-pfoa-adoption-event="uuid-same-low"', 'solo-card: wrapper found' );
$solo_members = strpos( $out, 'pfoa-adoption-event-members-1', $solo_wrapper );
$solo_title   = strpos( $out, 'pfoa-adoption-event-title', $solo_wrapper );
$solo_date    = strpos( $out, '<p class="pfoa-adoption-event-date">', $solo_wrapper );
$solo_next    = strpos( $out, 'data-pfoa-adoption-event="uuid-same-high"', $solo_wrapper );
fixture_assert( false !== $solo_members && false !== $solo_title && false !== $solo_date && false !== $solo_next, 'solo-card: members/title/date/next markers found' );
fixture_assert( $solo_wrapper < $solo_members && $solo_members < $solo_title && $solo_title < $solo_date && $solo_date < $solo_next, 'solo-card: photo, then title, then date — all inside the solo card' );
fixture_assert( false !== strpos( $out, 'data-pfoa-caption="Solo"' ), 'solo-card: lightbox caption metadata preserved' );
fixture_assert( false !== strpos( $out, 'alt="Solo"' ), 'solo-card: image alt preserved' );

// (PFOA 0.1.125 regression) Non-redundant solo captions stay visible:
// a solo whose stored caption differs from its name keeps a visible
// figcaption beneath the photo, with title + date still inside the card.
$GLOBALS['fixture_event_ids'] = array( 70 );
$GLOBALS['fixture_meta'][70]  = array(
	'_pfoa_event_year'    => '2026',
	'_pfoa_event_date'    => '2026-08-19',
	'_pfoa_event_uuid'    => 'uuid-solo-distinct',
	'_pfoa_event_members' => array(
		array(
			'image_id'          => 902,
			'cat_name_at_event' => 'Echo',
			'caption'           => 'Echo basking in the sun',
			'cat_post_id'       => 0,
		),
	),
);
$out_distinct = pfoa_present_adoption_events( fixture_gallery( array( 902 ) ) );
fixture_assert( 1 === substr_count( $out_distinct, '<figcaption class="pfoa-adoption-event-caption">Echo basking in the sun</figcaption>' ), 'solo-card: distinct solo caption stays visible' );
fixture_assert( false !== strpos( $out_distinct, '<h2 class="pfoa-adoption-event-title">Echo</h2>' ), 'solo-card: distinct solo title kept' );
$distinct_members = strpos( $out_distinct, 'pfoa-adoption-event-members-1' );
$distinct_title   = strpos( $out_distinct, 'pfoa-adoption-event-title' );
$distinct_date    = strpos( $out_distinct, '<p class="pfoa-adoption-event-date">August 19, 2026</p>' );
fixture_assert( false !== $distinct_members && false !== $distinct_title && false !== $distinct_date, 'solo-card: distinct solo markers found' );
fixture_assert( $distinct_members < $distinct_title && $distinct_title < $distinct_date, 'solo-card: distinct solo photo, then title, then date inside card' );

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

// (PFOA 0.1.125) Solo outer card shell: count-1 wrapper carries the shared
// bordered shell (photo + title + date inside one border-box) with a
// borderless inner member — never a doubled frame, never title/date
// outside the border.
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.pfoa-adoption-event-count-1[\s\S]*?background:\s*var\(--pfoa-surface-page\)/', $css ), 'css: solo outer shell background' );
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.pfoa-adoption-event-count-1[\s\S]*?border:\s*1\.5px solid #aeb4cd/', $css ), 'css: solo outer shell border' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-count-1 \.pfoa-adoption-event-member[\s\S]*?border:\s*0/', $css ), 'css: solo inner member borderless (no doubled frame)' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-count-1 \.pfoa-adoption-event-title[\s\S]*?margin:\s*10px 0 0/', $css ), 'css: solo title 10px below photo (0.1.129 photo-to-name)' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-count-1 \.pfoa-adoption-event-date[\s\S]*?margin:\s*8px 0 0/', $css ), 'css: solo date 8px beneath title (0.1.129 name-to-date)' );

// (PFOA 0.1.129 vertical rhythm) Adopted 2026 page-scoped spacing: 28px
// row gap with the shared 12px column gutter, 10px photo-to-name on multi
// titles, 8px name-to-date, two-line date reserve preserved.
fixture_assert( 1 === preg_match( '/body\.page-id-22514 \.entry-content \.gallery\s*\{[^}]*gap:\s*28px var\(--pfoa-space-2\)/', $css ), 'css: gallery row gap 28px, column gap keeps 12px gutter (0.1.129)' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-multi \.pfoa-adoption-event-title[\s\S]*?margin-top:\s*10px/', $css ), 'css: multi title 10px below photo grid (0.1.129 photo-to-name)' );
fixture_assert( 1 === preg_match( '/body\.page-id-22514 \.pfoa-adoption-event-date\s*\{[^}]*margin:\s*8px 0 0/', $css ), 'css: date 8px beneath title (0.1.129 name-to-date)' );

// (AG-07 track allocation, PFOA 0.1.124) Single coherent sizing system:
// width(N) = N * (photo + shell) + (N-1) * gap, i.e. N shells, not 1, so
// the outer width equals the full N-track grid allocation. At 1440px
// (photo 123, gap 12, shell 27) this yields 150/312/474/636.
fixture_assert( false !== strpos( $css, '(var(--pfoa-adopted-photo) + var(--pfoa-adopted-card-shell)) * 2 + var(--pfoa-adopted-gap)' ), 'css: count-2 carries 2 shells (track unit * 2 + gap)' );
fixture_assert( false !== strpos( $css, '(var(--pfoa-adopted-photo) + var(--pfoa-adopted-card-shell)) * 3 + var(--pfoa-adopted-gap) * 2' ), 'css: count-3 carries 3 shells (track unit * 3 + 2 gaps)' );
fixture_assert( false !== strpos( $css, '(var(--pfoa-adopted-photo) + var(--pfoa-adopted-card-shell)) * 4 + var(--pfoa-adopted-gap) * 3' ), 'css: count-n carries 4 shells (track unit * 4 + 3 gaps)' );
fixture_assert( false === strpos( $css, 'var(--pfoa-adopted-photo) * 2 + var(--pfoa-adopted-gap) + var(--pfoa-adopted-card-shell)' ), 'css: no competing single-shell count-2 formula' );
fixture_assert( false === strpos( $css, 'var(--pfoa-adopted-photo) * 3 + var(--pfoa-adopted-gap) * 2 + var(--pfoa-adopted-card-shell)' ), 'css: no competing single-shell count-3 formula' );
fixture_assert( false === strpos( $css, 'var(--pfoa-adopted-photo) * 4 + var(--pfoa-adopted-gap) * 3 + var(--pfoa-adopted-card-shell)' ), 'css: no competing single-shell count-n formula' );
fixture_assert( false === strpos( $css, 'var(--pfoa-adopted-photo) * 5 + var(--pfoa-adopted-gap) * 4 + var(--pfoa-adopted-card-shell)' ), 'css: no competing single-shell filler-5 formula' );
function fixture_track_width( $n, $photo, $gap, $shell ) {
	return $n * ( $photo + $shell ) + ( $n - 1 ) * $gap;
}
fixture_assert( 150 === fixture_track_width( 1, 123, 12, 27 ), 'geometry: 1440px count-1 == 150' );
fixture_assert( 312 === fixture_track_width( 2, 123, 12, 27 ), 'geometry: 1440px count-2 == 312' );
fixture_assert( 474 === fixture_track_width( 3, 123, 12, 27 ), 'geometry: 1440px count-3 == 474' );
fixture_assert( 636 === fixture_track_width( 4, 123, 12, 27 ), 'geometry: 1440px count-n == 636' );
fixture_assert( 231 === fixture_track_width( 1, 204, 12, 27 ), 'geometry: 4-slot responsive count-1 == 231' );
fixture_assert( 474 === fixture_track_width( 2, 204, 12, 27 ), 'geometry: 4-slot responsive count-2 == 474' );
fixture_assert( 218 === fixture_track_width( 1, 191, 12, 27 ), 'geometry: 3-slot responsive count-1 == 218' );
fixture_assert( 448 === fixture_track_width( 2, 191, 12, 27 ), 'geometry: 3-slot responsive count-2 == 448' );
fixture_assert( 169 === fixture_track_width( 1, 142, 12, 27 ), 'geometry: 2-slot responsive count-1 == 169' );
fixture_assert( 350 === fixture_track_width( 2, 142, 12, 27 ), 'geometry: 2-slot responsive count-2 == 350' );
// Filler parity: identical outer sizing per span, so filler-2 == 312,
// filler-3 == 474, filler-4 == 636 at 1440px.
fixture_assert( 1 === preg_match( '/pfoa-adopted-filler\[data-pfoa-filler-span="2"\][\s\S]*?\(\s*var\(--pfoa-adopted-photo\)\s*\+\s*var\(--pfoa-adopted-card-shell\)\s*\)\s*\*\s*2\s*\+\s*var\(--pfoa-adopted-gap\)/', $css ), 'css: filler-2 shares count-2 outer sizing' );
fixture_assert( 1 === preg_match( '/pfoa-adopted-filler\[data-pfoa-filler-span="3"\][\s\S]*?\(\s*var\(--pfoa-adopted-photo\)\s*\+\s*var\(--pfoa-adopted-card-shell\)\s*\)\s*\*\s*3\s*\+\s*var\(--pfoa-adopted-gap\)\s*\*\s*2/', $css ), 'css: filler-3 shares count-3 outer sizing' );
fixture_assert( 1 === preg_match( '/pfoa-adopted-filler\[data-pfoa-filler-span="4"\][\s\S]*?\(\s*var\(--pfoa-adopted-photo\)\s*\+\s*var\(--pfoa-adopted-card-shell\)\s*\)\s*\*\s*4\s*\+\s*var\(--pfoa-adopted-gap\)\s*\*\s*3/', $css ), 'css: filler-4 shares count-n outer sizing' );
// Legacy singles share the same outer box model (12px pad + 1.5px border,
// border-box global) — no divergence from the N=1 track.
fixture_assert( false !== strpos( $css, '.entry-content .gallery-item' ), 'css: legacy gallery-item rule intact' );

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

// (AG-09) Shared gallery card geometry: N-slot filler == N-slot real card.
// Border-box, shared photo/gap/shell tokens, same row height/alignment,
// no independent filler widths/margins/photo stretches, no interior gaps.
fixture_assert( false !== strpos( $filler, 'total - used' ), 'filler: span == remaining slots (total - used)' );
fixture_assert( false !== strpos( $filler, 'buildFiller(remaining)' ), 'filler: filler span consumes exact remainder' );
$filler_css_end   = ( false === $filler_css_pos ) ? false : strpos( $css, '}', (int) $stretch_pos );
$filler_css_block = ( false === $filler_css_pos || false === $filler_css_end ) ? '' : substr( $css, (int) $filler_css_pos, (int) $filler_css_end - (int) $filler_css_pos );
fixture_assert( false !== strpos( $filler_css_block, 'box-sizing: border-box' ), 'css: filler border-box (shared card geometry)' );
fixture_assert( false !== strpos( $filler_css_block, 'padding: var(--pfoa-space-2)' ), 'css: filler shell padding == event card shell token' );
fixture_assert( false !== strpos( $filler_css_block, '1.5px solid #aeb4cd' ), 'css: filler shell border == event card shell token' );
fixture_assert( false !== strpos( $filler_css_block, 'border-radius: var(--pfoa-radius)' ), 'css: filler shell radius == event card shell token' );
fixture_assert( false !== strpos( $filler_css_block, 'min-height: var(--pfoa-adopted-photo)' ), 'css: filler row height shares photo token' );
fixture_assert( false !== strpos( $css, 'gap: 28px var(--pfoa-space-2)' ), 'css: gallery 28px/12px gaps keep the shared gutter token' );
fixture_assert( false === strpos( $filler_css_block, 'gap:' ), 'css: filler has no interior gaps' );
fixture_assert( false !== strpos( $filler_css_block, 'margin: 0' ), 'css: filler no independent margins' );
fixture_assert( false === strpos( $filler_css_block, 'width: var(--pfoa-adopted-photo)' ), 'css: filler no independent photo stretch' );

// Enqueue wiring: filler script enqueued presentation-only on the target page.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, 'pfoa-adopted-gallery-filler' ), 'enqueue: filler handle wired' );
fixture_assert( false !== strpos( $functions_source, 'pfoa_adoption_event_member_media' ), 'media helper intact' );
fixture_assert( false !== strpos( $functions_source, 'window.PFOAGalleryLightbox' ) || false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/assets/js/pfoa-cat-dialog-controller.js' ), 'window.PFOAGalleryLightbox' ), 'lightbox: external overlay ownership intact' );

// (AG-03/AG-04 chronological placement, PFOA 0.1.126) Legacy scan order
// NEVER influences placement: the legacy gallery below runs oldest-first
// (ascending), yet events must still render strict newest-first. The
// pre-0.1.126 anchor-order insertion failed this (it followed the legacy
// document order); chronological sequential insertion passes.
$GLOBALS['fixture_event_ids'] = array( 101, 102, 103 );
fixture_set_event( 101, 'uuid-asc-new', '2026-09-19', array( fixture_member( 1101, 'New' ) ) );
fixture_set_event( 102, 'uuid-asc-mid', '2026-06-17', array( fixture_member( 1102, 'Mid' ) ) );
fixture_set_event( 103, 'uuid-asc-old', '2026-04-28', array( fixture_member( 1103, 'Old' ) ) );
$out_asc  = pfoa_present_adoption_events( fixture_gallery( array( 1103, 1102, 1101 ) ) );
$pos_new  = fixture_pos( $out_asc, 'data-pfoa-adoption-event="uuid-asc-new"', 'chrono-legacy: newest placed' );
$pos_mid  = fixture_pos( $out_asc, 'data-pfoa-adoption-event="uuid-asc-mid"', 'chrono-legacy: middle placed' );
$pos_old  = fixture_pos( $out_asc, 'data-pfoa-adoption-event="uuid-asc-old"', 'chrono-legacy: oldest placed' );
fixture_assert( $pos_new < $pos_mid && $pos_mid < $pos_old, 'chrono-legacy: DESC order despite ascending legacy gallery' );

// (AG-03 Batch 5 regression pairs, PFOA 0.1.126) Exact historical misorder
// cases: Salem 2026-02-26 before Stanley 2026-02-25, Wizzy 2026-01-20
// before Bluey 2026-01-18, Moshe&Zelda 2026-01-08 before Turbo
// 2026-01-02 — with the legacy gallery deliberately ordered wrong (the
// older snapshot first in every pair).
$GLOBALS['fixture_event_ids'] = array( 201, 202, 203, 204, 205, 206 );
fixture_set_event( 201, 'uuid-stanley', '2026-02-25', array( fixture_member( 1201, 'Stanley' ) ) );
fixture_set_event( 202, 'uuid-salem', '2026-02-26', array( fixture_member( 1202, 'Salem' ) ) );
fixture_set_event( 203, 'uuid-bluey', '2026-01-18', array( fixture_member( 1203, 'Bluey' ) ) );
fixture_set_event( 204, 'uuid-wizzy', '2026-01-20', array( fixture_member( 1204, 'Wizzy' ) ) );
fixture_set_event( 205, 'uuid-turbo', '2026-01-02', array( fixture_member( 1205, 'Turbo' ) ) );
fixture_set_event( 206, 'uuid-moshe-zelda', '2026-01-08', array( fixture_member( 1206, 'Moshe' ), fixture_member( 1207, 'Zelda' ) ) );
$out_pairs   = pfoa_present_adoption_events( fixture_gallery( array( 1201, 1203, 1205, 1202, 1204, 1206, 1207 ) ) );
$pos_salem   = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-salem"', 'pairs: salem placed' );
$pos_stanley = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-stanley"', 'pairs: stanley placed' );
$pos_wizzy   = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-wizzy"', 'pairs: wizzy placed' );
$pos_bluey   = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-bluey"', 'pairs: bluey placed' );
$pos_mz      = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-moshe-zelda"', 'pairs: moshe-zelda placed' );
$pos_turbo   = fixture_pos( $out_pairs, 'data-pfoa-adoption-event="uuid-turbo"', 'pairs: turbo placed' );
fixture_assert( $pos_salem < $pos_stanley, 'pairs: Salem 2026-02-26 renders before Stanley 2026-02-25' );
fixture_assert( $pos_wizzy < $pos_bluey, 'pairs: Wizzy 2026-01-20 renders before Bluey 2026-01-18' );
fixture_assert( $pos_mz < $pos_turbo, 'pairs: Moshe&Zelda 2026-01-08 renders before Turbo 2026-01-02' );
fixture_assert( $pos_stanley < $pos_wizzy, 'pairs: full DESC chain holds (Stanley before Wizzy)' );
fixture_assert( $pos_bluey < $pos_mz, 'pairs: full DESC chain holds (Bluey before Moshe&Zelda)' );
fixture_assert( 1 === substr_count( $out_pairs, 'data-pfoa-adoption-event="uuid-moshe-zelda"' ), 'pairs: multi-cat event grouped (renders once)' );

// (AG-03/AG-04/AG-08 scale, PFOA 0.1.126) 46-event gallery: 43 distinct
// dates plus a same-day trio exercising the stable ID-DESC tiebreak, with
// the legacy gallery in scrambled order and unclaimed survivors (9001,
// 9002) interleaved. Every event renders exactly once in strict DESC
// date order; survivors are quarantined after all events in relative
// order and can never displace chronology.
$GLOBALS['fixture_event_ids'] = array();
$GLOBALS['fixture_meta']      = array();
$scale_specs = array();
for ( $scale_i = 0; $scale_i < 43; $scale_i++ ) {
	$scale_id   = 500 + $scale_i;
	$scale_date = sprintf( '2026-%02d-%02d', 1 + (int) ( $scale_i / 28 ), 1 + ( $scale_i % 28 ) );
	$scale_uuid = 'uuid-scale-' . $scale_id;
	$GLOBALS['fixture_event_ids'][] = $scale_id;
	fixture_set_event( $scale_id, $scale_uuid, $scale_date, array( fixture_member( 5000 + $scale_id, 'Cat' . $scale_id ) ) );
	$scale_specs[] = array( 'uuid' => $scale_uuid, 'date' => $scale_date, 'id' => $scale_id );
}
$GLOBALS['fixture_event_ids'][] = 950;
$GLOBALS['fixture_event_ids'][] = 951;
$GLOBALS['fixture_event_ids'][] = 952;
fixture_set_event( 950, 'uuid-scale-same-low', '2026-12-25', array( fixture_member( 5950, 'SameLow' ) ) );
fixture_set_event( 951, 'uuid-scale-same-mid', '2026-12-25', array( fixture_member( 5951, 'SameMid' ) ) );
fixture_set_event( 952, 'uuid-scale-same-high', '2026-12-25', array( fixture_member( 5952, 'SameHigh' ) ) );
$scale_specs[] = array( 'uuid' => 'uuid-scale-same-low', 'date' => '2026-12-25', 'id' => 950 );
$scale_specs[] = array( 'uuid' => 'uuid-scale-same-mid', 'date' => '2026-12-25', 'id' => 951 );
$scale_specs[] = array( 'uuid' => 'uuid-scale-same-high', 'date' => '2026-12-25', 'id' => 952 );
usort(
	$scale_specs,
	function ( $a, $b ) {
		$date_cmp = strcmp( (string) $b['date'], (string) $a['date'] );
		if ( 0 !== $date_cmp ) {
			return $date_cmp;
		}
		if ( (int) $b['id'] === (int) $a['id'] ) {
			return 0;
		}
		return (int) $b['id'] > (int) $a['id'] ? 1 : -1;
	}
);
$scale_gallery_ids = array( 9001 );
foreach ( $scale_specs as $scale_spec ) {
	$scale_gallery_ids[] = 5000 + $scale_spec['id'];
}
$scale_gallery_ids[] = 9002;
$out_scale = pfoa_present_adoption_events( fixture_gallery( $scale_gallery_ids ) );
$scale_prev_pos = -1;
foreach ( $scale_specs as $scale_spec ) {
	fixture_assert( 1 === substr_count( $out_scale, 'data-pfoa-adoption-event="' . $scale_spec['uuid'] . '"' ), 'scale: ' . $scale_spec['uuid'] . ' renders exactly once (46 total, never deduped)' );
	$scale_pos = fixture_pos( $out_scale, 'data-pfoa-adoption-event="' . $scale_spec['uuid'] . '"', 'scale: ' . $scale_spec['uuid'] . ' placed' );
	fixture_assert( $scale_pos > $scale_prev_pos, 'scale: strict DESC DOM order at ' . $scale_spec['uuid'] . ' (' . $scale_spec['date'] . ')' );
	$scale_prev_pos = $scale_pos;
}
$scale_last_event = $scale_prev_pos;
$scale_surv_a     = fixture_pos( $out_scale, 'gallery-1-9001', 'scale: survivor 9001 kept' );
$scale_surv_b     = fixture_pos( $out_scale, 'gallery-1-9002', 'scale: survivor 9002 kept' );
fixture_assert( $scale_surv_a > $scale_last_event && $scale_surv_b > $scale_surv_a, 'scale: legacy survivors quarantined after all events in relative order' );
$pos_same_high = fixture_pos( $out_scale, 'data-pfoa-adoption-event="uuid-scale-same-high"', 'scale: same-day high placed' );
$pos_same_mid  = fixture_pos( $out_scale, 'data-pfoa-adoption-event="uuid-scale-same-mid"', 'scale: same-day mid placed' );
$pos_same_low  = fixture_pos( $out_scale, 'data-pfoa-adoption-event="uuid-scale-same-low"', 'scale: same-day low placed' );
fixture_assert( $pos_same_high < $pos_same_mid && $pos_same_mid < $pos_same_low, 'scale: same-day tiebreak is stable ID-DESC' );

// (AG-01/AG-02 repeat at scale, PFOA 0.1.126) The same cat adopted twice
// with the same photograph: both occurrences render with the photo
// visible in each — repeats are coverage, never duplicates, and the
// newer occurrence still leads chronologically.
$GLOBALS['fixture_event_ids'] = array( 601, 602 );
fixture_set_event( 601, 'uuid-repeat-new', '2026-06-17', array( fixture_member( 6601, 'Spencer' ), fixture_member( 6602, 'Thompson' ) ) );
fixture_set_event( 602, 'uuid-repeat-old', '2026-04-28', array( fixture_member( 6601, 'Spencer' ), fixture_member( 6602, 'Thompson' ) ) );
$out_scale_repeat = pfoa_present_adoption_events( fixture_gallery( array( 6601, 6602 ) ) );
fixture_assert( 1 === substr_count( $out_scale_repeat, 'data-pfoa-adoption-event="uuid-repeat-new"' ), 'scale-repeat: newer occurrence renders once' );
fixture_assert( 1 === substr_count( $out_scale_repeat, 'data-pfoa-adoption-event="uuid-repeat-old"' ), 'scale-repeat: older occurrence renders once (never deduped)' );
$pos_rep_new = fixture_pos( $out_scale_repeat, 'data-pfoa-adoption-event="uuid-repeat-new"', 'scale-repeat: newer placed' );
$pos_rep_old = fixture_pos( $out_scale_repeat, 'data-pfoa-adoption-event="uuid-repeat-old"', 'scale-repeat: older placed' );
fixture_assert( $pos_rep_new < $pos_rep_old, 'scale-repeat: newer occurrence leads chronologically' );
fixture_assert( 2 === substr_count( $out_scale_repeat, 'med6601.jpg' ), 'scale-repeat: reused photo visible in both occurrences' );

// (PFOA 0.1.127 card-height regression) Same-row shells share tops AND
// bottoms: the gallery grid stretches every direct card to its row height
// (never start-aligned content sizing), so a narrow single whose long date
// wraps to two lines (e.g. "September 15, 2026" in a 150px N=1 card) can
// never stand taller than its wider one-line pair neighbours (e.g. Frost &
// Lightning / Niko & Simon). The date reserves a full two-line height so
// wrapping never grows a card and one-line dates hold the same space, with
// the full date text always visible (no truncation, no ellipsis).
fixture_assert( 1 === preg_match( '/body\.page-id-22514 \.entry-content \.gallery[\s\S]*?align-items:\s*stretch/', $css ), 'css: gallery stretches row shells (tops+bottoms aligned)' );
fixture_assert( false === (bool) preg_match( '/body\.page-id-22514 \.entry-content \.gallery\s*\{[^}]*align-items:\s*start/', $css ), 'css: no start-aligned gallery grid (card-height regression)' );
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.pfoa-adoption-event[\s\S]*?align-self:\s*stretch/', $css ), 'css: event cards stretch to row height' );
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.gallery-item[\s\S]*?align-self:\s*stretch/', $css ), 'css: legacy singles stretch to row height' );
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.pfoa-adoption-event[\s\S]*?display:\s*flex/', $css ), 'css: event cards stack photo grid above title/date (flex column)' );
fixture_assert( 1 === preg_match( '/\.entry-content \.gallery > \.pfoa-adoption-event[\s\S]*?flex-direction:\s*column/', $css ), 'css: event card flex column keeps photos uniform, whitespace below date' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-date[\s\S]*?min-height:\s*2\.9em/', $css ), 'css: date reserves two-line height (wrapped dates never grow singles)' );
fixture_assert( false !== strpos( $css, 'justify-self: start' ), 'css: cards stay left-aligned in their spans (widths untouched)' );
fixture_assert( 1 === preg_match( '/\.pfoa-adoption-event-bonded-badge,\s*\n?body\.page-id-22514 \.pfoa-adoption-event-together-badge[\s\S]*?position:\s*absolute/', $css ), 'css: badges stay zero-flow absolute (row height untouched)' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

fwrite( STDOUT, "PASS: theme adopted gallery presentation fixture (dates, DESC chronology + tiebreak, per-event coverage + repeats, grouping, badges/captions, uniform-photo CSS, filler contract, no writes)\n" );
