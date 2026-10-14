<?php
/**
 * Focused fixture for the Senior Shadow caption-less legacy duplicate
 * (Step-4 stable-identity unanimity hardening + Adopted 2026 identity filter).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency.
 *
 * REAL production data (per Sol independent QA 2026-10-14):
 *   Senior Shadow Event 24666, UUID 9fe76269-c9bb-4024-aef0-ad9fc51e5b57,
 *     attachment 23330, 2026-06-19.
 *   Second Shadow Event 24646, UUID d5ad93d2-7509-42b0-b965-129947f789fe,
 *     attachment 23174, 2026-06-19 (proves same-name independence).
 *   Real captionless gallery_shortcode() output shape for attachment 23330:
 *     figure.gallery-item > div.gallery-icon.landscape >
 *     a[href=file URL] > img.attachment-medium.size-medium with NO
 *     wp-image-*, NO figcaption/aria, NO data-attachment-id/data-id,
 *     NO ?attachment_id=. The data-attachment-id on the GREEN-path item
 *     below is emitted by the NEW production filter
 *     pfoa_adopted2026_gallery_image_attributes() (called directly); the
 *     no-filter item is the verbatim real shape and survives as unmatched.
 *
 * RED/GREEN contract:
 *   On the baseline presenter without the filter/fix (e03c005) this fixture
 *   FAILS: the production filter function does not exist and the
 *   filter-enabled real duplicate survives (zero-duplicate assertions trip).
 *   With the fix it PASSES: filter-disabled real output survives as
 *   unmatched (proving identity comes from the filter, not guessing),
 *   filter-enabled real output is suppressed exactly once.
 *
 * Coverage:
 *   R1 real captionless shape, filter DISABLED (verbatim WP output):
 *     survives as unmatched (RED-baseline proof).
 *   R2 real captionless shape, filter ENABLED (production filter output):
 *     zero duplicate legacy photos after dedup, via the complete
 *     the_content path (apply_filters stub -> production presenter).
 *   R3 independence: both Shadow Events render once each, correct date,
 *     date-DESC/ID-DESC order despite ascending storage order.
 *   C1-C4 Sol conflict battery (each must preserve, fail-closed):
 *     data-attachment-id vs data-id on one img; two wp-image tokens on one
 *     img; two imgs disagreeing in one item; primary caption/aria vs
 *     fallback class disagreeing.
 *   C5 unanimous multi-signal agreement still suppresses (positive control).
 *   C6 lone data-attribute agreement still suppresses (supplementary unit).
 *   F1-F4 malformed/fractional IDs never match: "23330.9" via data-id,
 *     class token, and href query; plus "0", "023330", "-5", "", "abc",
 *     "23330 " battery (no truncation, no cast).
 *   D1-D5 Sol re-QA strict battery (0.1.142; each FAILS on 0.1.141):
 *     repeated href conflict vs unanimous repeats, ARIA IDREF-list
 *     conflict + ARIA-only positive, terminal-newline token,
 *     whitespace-tainted href, fragment-vs-query; plus href-only
 *     positive. Conflict cases assert preserve-count 1 (not disappear).
 *   Dispatch fidelity (UNIT): emitter wp_get_attachment_image_attributes
 *     prio 10 + 3 args, presenter the_content prio 950 (1-arg hook fails).
 *   INTEGRATION: end-to-end apply_filters('the_content') suppression +
 *     preservation; real gallery_shortcode()/the_content proven by the Sol
 *     real-WP harness (cited inline) — explicit skip here, no DB/network.
 *   U1 legitimate unmatched photos (100, 102) remain visible, quarantined
 *     after events in relative order.
 *   M2/M3/M5 legacy supplementary: missing date, wrong date detectable,
 *     image_id=0 placeholder. M6 same-name distinct IDs in one event.
 *   S2 data-integrity: real RFC 4122 UUIDs, date validity, exact-int claim,
 *     snapshot-only reads, presentation-only (no storage writes).
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
$GLOBALS['fixture_is_page_target']  = true;
$GLOBALS['fixture_filters']         = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
	$GLOBALS['fixture_filters'][]        = array( $hook, $callback, $priority, $accepted_args );
}
function apply_filters( $hook, $value ) {
	if ( 'the_content' === $hook && function_exists( 'pfoa_present_adoption_events' ) ) {
		return pfoa_present_adoption_events( $value );
	}
	return $value;
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
	return ! empty( $GLOBALS['fixture_is_page_target'] );
}
function get_post( $post = null ) {
	if ( null === $post ) {
		$page            = new WP_Post();
		if ( empty( $GLOBALS['fixture_is_page_target'] ) ) {
			$page->ID        = 99999;
			$page->post_type = 'page';
			$page->post_name = 'elsewhere';
			return $page;
		}
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

// Real captionless WordPress gallery item for Senior Shadow attachment
// 23330: figure.gallery-item > div.gallery-icon.landscape > a[href=file] >
// img.attachment-medium.size-medium. No figcaption[id], no aria-describedby,
// no wp-image-*, no data-id, and (when $with_identity is false) no
// data-attachment-id — verbatim real shape. When $with_identity is true the
// img attributes pass through the PRODUCTION filter
// pfoa_adopted2026_gallery_image_attributes(), which adds the authoritative
// data-attachment-id="23330". data-pfoa-attachment-title is a title, never
// identity, and must be ignored by the presenter either way.
function fixture_real_shadow_item( $with_identity ) {
	$img_attrs = array(
		'class'                      => 'attachment-medium size-medium',
		'src'                        => 'https://example.com/uploads/2026/07/ShadowWeb4040-300x300.jpg',
		'alt'                        => '',
		'data-pfoa-attachment-title' => 'ShadowWeb4040',
	);
	if ( $with_identity ) {
		fixture_assert( function_exists( 'pfoa_adopted2026_gallery_image_attributes' ), 'filter: production identity filter exists (RED on baseline without the fix)' );
		$attachment     = new WP_Post();
		$attachment->ID = 23330;
		$filtered       = pfoa_adopted2026_gallery_image_attributes( $img_attrs, $attachment, 'medium' );
		fixture_assert( is_array( $filtered ), 'filter: returns attribute array' );
		fixture_assert( isset( $filtered['data-attachment-id'] ) && '23330' === (string) $filtered['data-attachment-id'], 'filter: emits authoritative data-attachment-id="23330"' );
		foreach ( $img_attrs as $key => $value ) {
			fixture_assert( isset( $filtered[ $key ] ) && (string) $filtered[ $key ] === (string) $value, 'filter: additive only, leaves ' . $key . ' untouched' );
		}
		$img_attrs = $filtered;
	}
	$parts = array();
	foreach ( $img_attrs as $key => $value ) {
		$parts[] = $key . '="' . esc_attr( $value ) . '"';
	}
	return '<figure class="gallery-item"><div class="gallery-icon landscape"><a href="https://example.com/uploads/2026/07/ShadowWeb4040.jpg"><img ' . implode( ' ', $parts ) . ' /></a></div></figure>';
}

function fixture_disagreeing_data_attrs_item( $claimed_id, $other_id ) {
	$c = (int) $claimed_id;
	$d = (int) $other_id;
	return '<figure class="gallery-item"><img data-attachment-id="' . $c . '" data-id="' . $d . '" src="https://example.com/med' . $c . '.jpg" alt=""></figure>';
}

function fixture_two_class_tokens_item( $claimed_id, $other_id ) {
	$c = (int) $claimed_id;
	$d = (int) $other_id;
	return '<figure class="gallery-item"><img class="attachment-medium size-medium wp-image-' . $c . ' wp-image-' . $d . '" src="https://example.com/med' . $c . '.jpg" alt=""></figure>';
}

function fixture_two_imgs_disagree_item( $claimed_id, $other_id ) {
	$c = (int) $claimed_id;
	$d = (int) $other_id;
	return '<figure class="gallery-item"><img class="wp-image-' . $c . '" src="https://example.com/med' . $c . '.jpg" alt=""><img class="wp-image-' . $d . '" src="https://example.com/med' . $d . '.jpg" alt=""></figure>';
}

function fixture_primary_vs_class_disagree_item( $claimed_id, $other_id ) {
	$c = (int) $claimed_id;
	$d = (int) $other_id;
	return '<figure class="gallery-item"><img class="wp-image-' . $d . '" aria-describedby="gallery-1-' . $c . '" src="https://example.com/med' . $c . '.jpg" alt=""><figcaption id="gallery-1-' . $c . '">Shadow</figcaption></figure>';
}

function fixture_unanimous_item( $claimed_id ) {
	$c = (int) $claimed_id;
	return '<figure class="gallery-item"><a href="https://example.com/?attachment_id=' . $c . '"><img class="wp-image-' . $c . '" data-attachment-id="' . $c . '" aria-describedby="gallery-1-' . $c . '" src="https://example.com/med' . $c . '.jpg" alt=""></a><figcaption id="gallery-1-' . $c . '">Shadow</figcaption></figure>';
}

function fixture_pos( $haystack, $needle, $label ) {
	$pos = strpos( (string) $haystack, (string) $needle );
	fixture_assert( false !== $pos, $label . ': marker found (' . $needle . ')' );
	return (int) $pos;
}

fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'present function exists' );
fixture_assert( function_exists( 'pfoa_adopted2026_gallery_image_attributes' ), 'identity filter exists (RED on baseline without the fix)' );

// Filter registration: production filter must be registered on the
// wp_get_attachment_image_attributes hook (presentation path wiring).
// UNIT: hook dispatch fidelity — priority 10 with 3 accepted args, so the
// real callback receives ($attr, $attachment, $size); a 1-arg registration
// must FAIL this fixture (dispatch fidelity gap closed).
$filter_registered = false;
foreach ( $GLOBALS['fixture_filters'] as $registered ) {
	if ( 'wp_get_attachment_image_attributes' === $registered[0] && 'pfoa_adopted2026_gallery_image_attributes' === $registered[1] ) {
		$filter_registered = true;
		fixture_assert( 10 === (int) $registered[2], 'filter: emitter registered at priority 10 (dispatch fidelity)' );
		fixture_assert( 3 === (int) $registered[3], 'filter: emitter registered with 3 accepted args (dispatch fidelity; 1-arg registration fails)' );
	}
}
fixture_assert( $filter_registered, 'filter: registered on wp_get_attachment_image_attributes (RED on baseline)' );

// UNIT: presenter dispatch fidelity — pfoa_present_adoption_events must be
// registered on the_content at priority 950.
$presenter_registered = false;
foreach ( $GLOBALS['fixture_filters'] as $registered ) {
	if ( 'the_content' === $registered[0] && 'pfoa_present_adoption_events' === $registered[1] ) {
		$presenter_registered = true;
		fixture_assert( 950 === (int) $registered[2], 'presenter: registered on the_content at priority 950 (dispatch fidelity)' );
	}
}
fixture_assert( $presenter_registered, 'presenter: registered on the_content (RED on baseline)' );

// ---------------------------------------------------------------------------
// Filter unit checks: scoping + additive-only + input guards.
// ---------------------------------------------------------------------------
$filter_attachment     = new WP_Post();
$filter_attachment->ID = 23330;

$GLOBALS['fixture_is_page_target'] = false;
$filter_out_of_scope               = pfoa_adopted2026_gallery_image_attributes( array( 'class' => 'attachment-medium size-medium' ), $filter_attachment, 'medium' );
fixture_assert( is_array( $filter_out_of_scope ) && ! isset( $filter_out_of_scope['data-attachment-id'] ), 'filter: out-of-scope page returns attributes unchanged' );
$GLOBALS['fixture_is_page_target'] = true;

$filter_non_array = pfoa_adopted2026_gallery_image_attributes( 'not-an-array', $filter_attachment, 'medium' );
fixture_assert( 'not-an-array' === $filter_non_array, 'filter: non-array attributes returned unchanged' );

$filter_zero_attachment     = new WP_Post();
$filter_zero_attachment->ID = 0;
$filter_zero_result         = pfoa_adopted2026_gallery_image_attributes( array( 'class' => 'x' ), $filter_zero_attachment, 'medium' );
fixture_assert( ! isset( $filter_zero_result['data-attachment-id'] ), 'filter: zero attachment ID returns attributes unchanged' );

$filter_plain_result = pfoa_adopted2026_gallery_image_attributes( array( 'class' => 'x' ), null, 'medium' );
fixture_assert( ! isset( $filter_plain_result['data-attachment-id'] ), 'filter: non-WP_Post attachment returns attributes unchanged' );

// ---------------------------------------------------------------------------
// R1/R2/R3 Senior Shadow real-markup pair via the complete the_content path.
// Events: 24666 (uuid 9fe76269-, attachment 23330) + 24646 (uuid d5ad93d2-,
// attachment 23174), both 2026-06-19, stored ascending to prove storage
// order cannot displace the date-DESC/ID-DESC rule (24666 first).
// ---------------------------------------------------------------------------
define( 'FIXTURE_SHADOW_UUID_A', '9fe76269-c9bb-4024-aef0-ad9fc51e5b57' );
define( 'FIXTURE_SHADOW_UUID_B', 'd5ad93d2-7509-42b0-b965-129947f789fe' );

$GLOBALS['fixture_event_ids'] = array( 24646, 24666 );
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '2026-06-19', array( fixture_member( 23330, 'Shadow' ) ) );
fixture_set_event( 24646, FIXTURE_SHADOW_UUID_B, '2026-06-19', array( fixture_member( 23174, 'Shadow (Kitten 4040)' ) ) );

// R1: filter DISABLED — verbatim real captionless output carries no stable
// identity, so the duplicate MUST survive as unmatched (RED-baseline proof:
// without the filter there is nothing to dedupe on).
$disabled_content = '<div class="gallery galleryid-22514 gallery-columns-6">'
	. fixture_gallery_item( 100 )
	. fixture_real_shadow_item( false )
	. fixture_gallery_item( 23174 )
	. fixture_gallery_item( 102 )
	. '</div>';
$disabled_out = apply_filters( 'the_content', $disabled_content );

fixture_assert( 1 === substr_count( $disabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'R1: Senior Shadow event renders exactly once without filter identity' );
fixture_assert( 1 === substr_count( $disabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_B . '"' ), 'R1: second Shadow event renders exactly once without filter identity' );
fixture_assert( false !== strpos( $disabled_out, 'ShadowWeb4040' ), 'R1: verbatim captionless duplicate survives as unmatched without filter identity (RED-baseline proof)' );
fixture_assert( 3 === substr_count( $disabled_out, 'gallery-item' ), 'R1: three legacy survivors remain (real item + 100 + 102; claimed 23174 suppressed)' );

// R2: filter ENABLED — the same real item with production-filter identity is
// deduplicated; zero duplicate legacy photos remain. Full the_content path.
$enabled_content = '<div class="gallery galleryid-22514 gallery-columns-6">'
	. fixture_gallery_item( 100 )
	. fixture_real_shadow_item( true )
	. fixture_gallery_item( 23174 )
	. fixture_gallery_item( 102 )
	. '</div>';
$enabled_out = apply_filters( 'the_content', $enabled_content );

$pos_shadow_a = fixture_pos( $enabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"', 'R2: Senior Shadow wrapper rendered' );
$pos_shadow_b = fixture_pos( $enabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_B . '"', 'R2: second Shadow wrapper rendered' );
fixture_assert( 1 === substr_count( $enabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'R2: Senior Shadow renders exactly once (attachment 23330)' );
fixture_assert( 1 === substr_count( $enabled_out, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_B . '"' ), 'R2: second Shadow renders exactly once (attachment 23174)' );
fixture_assert( 2 === substr_count( $enabled_out, '<p class="pfoa-adoption-event-date">June 19, 2026</p>' ), 'R2: both cards show the stored June 19, 2026 date' );
fixture_assert( 2 === substr_count( $enabled_out, '<h2 class="pfoa-adoption-event-title">Shadow</h2>' ), 'R2: both same-name events keep independent Shadow titles (no merge)' );
fixture_assert( $pos_shadow_a < $pos_shadow_b, 'R2: same-date tie breaks by event ID DESC (24666 before 24646) despite ascending storage order' );
fixture_assert( 2 === substr_count( $enabled_out, 'pfoa-adoption-event-member"' ), 'R2: two single-member events render two member photos' );
fixture_assert( false === strpos( $enabled_out, 'ShadowWeb4040' ), 'R2: zero duplicate legacy photos after dedup (GREEN; RED on baseline)' );
fixture_assert( false === strpos( $enabled_out, 'gallery-1-23174' ), 'R2: claimed legacy node 23174 suppressed' );
fixture_assert( 2 === substr_count( $enabled_out, 'gallery-item' ), 'R2: exactly two legacy survivors remain (100, 102)' );
$pos_s100 = fixture_pos( $enabled_out, 'gallery-1-100', 'R2: survivor 100 present' );
$pos_s102 = fixture_pos( $enabled_out, 'gallery-1-102', 'R2: survivor 102 present' );
fixture_assert( $pos_shadow_b < $pos_s100 && $pos_s100 < $pos_s102, 'R2: survivors 100, 102 quarantined after events in original relative order' );
fixture_assert( 2 === substr_count( $enabled_out, 'data-pfoa-adoption-event="' ), 'R2: no-silent-extras check (exactly two event wrappers)' );

// ---------------------------------------------------------------------------
// S2 data-integrity (real production identifiers).
// ---------------------------------------------------------------------------
fixture_assert( FIXTURE_SHADOW_UUID_A !== FIXTURE_SHADOW_UUID_B, 'uuid-uniqueness: Shadow event UUIDs distinct' );
fixture_assert( 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', FIXTURE_SHADOW_UUID_A ), 'uuid-validity: Senior Shadow UUID is RFC 4122 form' );
fixture_assert( 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', FIXTURE_SHADOW_UUID_B ), 'uuid-validity: second Shadow UUID is RFC 4122 form' );
fixture_assert( false !== strtotime( '2026-06-19' ), 'date-validity: 2026-06-19 parseable' );
fixture_assert( 'June 19, 2026' === pfoa_adoption_event_format_date( '2026-06-19' ), 'date-render: stored date formats without mutation' );
fixture_assert( 23330 === (int) $GLOBALS['fixture_meta'][24666]['_pfoa_event_members'][0]['image_id'], 'exact-int-claim: snapshot image_id 23330 is an exact int' );
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'snapshot-only: no DB writes during presentation' );

$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_members' ), 'snapshot-only: members read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_uuid' ), 'snapshot-only: UUID read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, '_pfoa_event_date' ), 'snapshot-only: date read from stored snapshot meta' );
fixture_assert( false !== strpos( $functions_source, 'claimed_ids' ), 'exact-int-claim: member claim map present' );
fixture_assert( false !== strpos( $functions_source, 'fail-closed' ), 'fail-closed: conflict validation documented' );
fixture_assert( false !== strpos( $functions_source, 'presentation only' ), 'presentation-only: Step-4 path is presentation-only' );
// Step-4 stable-identity fallback contract.
fixture_assert( false !== strpos( $functions_source, 'wp-image-' ), 'fallback-contract: wp-image-<ID> class fallback present' );
fixture_assert( false !== strpos( $functions_source, 'data-attachment-id' ), 'fallback-contract: data-attachment-id fallback present' );
fixture_assert( false !== strpos( $functions_source, 'attachment_id=' ), 'fallback-contract: ?attachment_id= href fallback present' );
fixture_assert( false !== strpos( $functions_source, 'wp_get_attachment_image_attributes' ), 'filter-contract: identity filter hook present' );
fixture_assert( false !== strpos( $functions_source, 'pfoa_adopted2026_gallery_image_attributes' ), 'filter-contract: identity filter function present' );

// ---------------------------------------------------------------------------
// C1-C6 Sol conflict battery (event 24666 claims 23330; each ambiguous or
// malformed node must survive; unanimous nodes are suppressed).
// ---------------------------------------------------------------------------
$GLOBALS['fixture_event_ids'] = array( 24666 );
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '2026-06-19', array( fixture_member( 23330, 'Shadow' ) ) );

function fixture_conflict_gallery( $item ) {
	return '<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . $item . fixture_gallery_item( 102 ) . '</div>';
}

// C1: data-attachment-id vs data-id disagree on the same img.
$out_c1 = pfoa_present_adoption_events( fixture_conflict_gallery( fixture_disagreeing_data_attrs_item( 23330, 99999 ) ) );
fixture_assert( 1 === substr_count( $out_c1, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C1: event renders once with disagreeing data attributes present' );
fixture_assert( false !== strpos( $out_c1, 'data-attachment-id="23330"' ) && false !== strpos( $out_c1, 'data-id="99999"' ), 'C1: disagreeing data attributes fail closed — ambiguous node survives (no silent removal)' );

// C2: multiple wp-image tokens disagree on the same img.
$out_c2 = pfoa_present_adoption_events( fixture_conflict_gallery( fixture_two_class_tokens_item( 23330, 99999 ) ) );
fixture_assert( 1 === substr_count( $out_c2, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C2: event renders once with disagreeing class tokens present' );
fixture_assert( false !== strpos( $out_c2, 'wp-image-23330' ), 'C2: disagreeing class tokens fail closed — ambiguous node survives (no silent removal)' );

// C3: two imgs disagree inside one gallery item.
$out_c3 = pfoa_present_adoption_events( fixture_conflict_gallery( fixture_two_imgs_disagree_item( 23330, 99999 ) ) );
fixture_assert( 1 === substr_count( $out_c3, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C3: event renders once with disagreeing images present' );
fixture_assert( false !== strpos( $out_c3, 'wp-image-99999' ), 'C3: disagreeing images fail closed — ambiguous node survives (no silent removal)' );

// C4: primary caption/aria vs fallback class disagree.
$out_c4 = pfoa_present_adoption_events( fixture_conflict_gallery( fixture_primary_vs_class_disagree_item( 23330, 99999 ) ) );
fixture_assert( 1 === substr_count( $out_c4, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C4: event renders once with primary-vs-fallback conflict present' );
fixture_assert( false !== strpos( $out_c4, 'gallery-1-23330' ), 'C4: primary-vs-fallback conflict fails closed — ambiguous node survives (no silent removal)' );

// C5: unanimous multi-signal agreement still suppresses (positive control).
$out_c5 = pfoa_present_adoption_events( fixture_conflict_gallery( fixture_unanimous_item( 23330 ) ) );
fixture_assert( 1 === substr_count( $out_c5, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C5: event renders once with unanimous duplicate present' );
fixture_assert( false === strpos( $out_c5, 'gallery-1-23330' ) && false === strpos( $out_c5, 'wp-image-23330' ) && false === strpos( $out_c5, 'data-attachment-id="23330"' ) && false === strpos( $out_c5, 'attachment_id=23330' ), 'C5: unanimous duplicate suppressed across all signals' );

// C6: lone data-attribute duplicate still suppresses (supplementary unit).
$out_c6 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . '<figure class="gallery-item"><img data-attachment-id="23330" src="https://example.com/med23330.jpg" alt=""></figure>' . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_c6, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'C6: event renders once with data-attr duplicate present' );
fixture_assert( false === strpos( $out_c6, 'data-attachment-id="23330"' ), 'C6: data-attribute duplicate of 23330 suppressed' );

// ---------------------------------------------------------------------------
// F1-F4 malformed/fractional IDs never match (event 24666 claims 23330).
// ---------------------------------------------------------------------------

// F1: fractional data-id must not truncate to the claimed ID.
$out_f1 = pfoa_present_adoption_events(
	fixture_conflict_gallery( '<figure class="gallery-item"><img data-id="23330.9" src="https://example.com/medF1.jpg" alt=""></figure>' )
);
fixture_assert( false !== strpos( $out_f1, 'data-id="23330.9"' ), 'F1: fractional data-id never matches — node survives (no truncation to 23330)' );

// F2: fractional class token must not truncate to the claimed ID.
$out_f2 = pfoa_present_adoption_events(
	fixture_conflict_gallery( '<figure class="gallery-item"><img class="attachment-medium wp-image-23330.9" src="https://example.com/medF2.jpg" alt=""></figure>' )
);
fixture_assert( false !== strpos( $out_f2, 'wp-image-23330.9' ), 'F2: fractional class token never matches — node survives (no truncation to 23330)' );

// F3: fractional href token must not truncate to the claimed ID.
$out_f3 = pfoa_present_adoption_events(
	fixture_conflict_gallery( '<figure class="gallery-item"><a href="https://example.com/?attachment_id=23330.9"><img src="https://example.com/medF3.jpg" alt=""></a></figure>' )
);
fixture_assert( false !== strpos( $out_f3, 'attachment_id=23330.9' ), 'F3: fractional href token never matches — node survives (no truncation to 23330)' );

// F4: malformed battery — "0", "023330", "-5", "", "abc", "23330 " all
// rejected; every node survives.
$malformed_gallery = '<div class="gallery galleryid-22514 gallery-columns-6">'
	. '<figure class="gallery-item"><img data-id="0" src="https://example.com/medF4a.jpg" alt=""></figure>'
	. '<figure class="gallery-item"><img data-id="023330" src="https://example.com/medF4b.jpg" alt=""></figure>'
	. '<figure class="gallery-item"><img data-id="-5" src="https://example.com/medF4c.jpg" alt=""></figure>'
	. '<figure class="gallery-item"><img data-id="" src="https://example.com/medF4d.jpg" alt=""></figure>'
	. '<figure class="gallery-item"><img data-id="abc" src="https://example.com/medF4e.jpg" alt=""></figure>'
	. '<figure class="gallery-item"><img data-id="23330 " src="https://example.com/medF4f.jpg" alt=""></figure>'
	. '</div>';
$out_f4 = pfoa_present_adoption_events( $malformed_gallery );
foreach ( array( 'medF4a', 'medF4b', 'medF4c', 'medF4d', 'medF4e', 'medF4f' ) as $malformed_marker ) {
	fixture_assert( false !== strpos( $out_f4, $malformed_marker ), 'F4: malformed ID node survives (' . $malformed_marker . ')' );
}

// ---------------------------------------------------------------------------
// M2/M3/M5/M6 supplementary mutations (isolated; stubs only, no writes).
// ---------------------------------------------------------------------------

// M2: missing date — event still renders once, fail-closed (no drop, no throw).
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '', array( fixture_member( 23330, 'Shadow' ) ) );
$out_m2 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m2, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'M2: missing-date event still renders exactly once' );
fixture_assert( false !== strpos( $out_m2, 'gallery-1-100' ), 'M2: survivors intact when date missing' );

// M3: wrong date is detectable — rendered text follows the stored date.
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '2026-01-01', array( fixture_member( 23330, 'Shadow' ) ) );
$out_m3 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( false !== strpos( $out_m3, 'January 1, 2026' ), 'M3: wrong stored date renders visibly (wrongness detectable)' );
fixture_assert( false === strpos( $out_m3, 'June 19, 2026' ), 'M3: correct date absent when stored date is wrong (detector fires)' );

// M5: missing image_id=0 — wrapper renders, nothing claimed, survivors intact.
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '2026-06-19', array( fixture_member( 0, 'Shadow' ) ) );
$out_m5 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m5, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'M5: image_id=0 event still renders exactly once' );
fixture_assert( false !== strpos( $out_m5, 'Photo unavailable' ), 'M5: missing photo renders placeholder (detectable, not silent)' );
fixture_assert( false !== strpos( $out_m5, 'gallery-1-100' ) && false !== strpos( $out_m5, 'gallery-1-102' ), 'M5: survivors intact when image_id missing' );

// M6: duplicated name with distinct IDs — no name-based merge/dedupe.
$GLOBALS['fixture_event_ids'] = array( 24611 );
fixture_set_event( 24611, '11111111-2222-4333-8444-555555555555', '2026-05-01', array( fixture_member( 24001, 'Shadow' ), fixture_member( 24002, 'Shadow' ) ) );
$out_m6 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . fixture_gallery_item( 24001 ) . fixture_gallery_item( 24002 ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_m6, 'data-pfoa-adoption-event="11111111-2222-4333-8444-555555555555"' ), 'M6: same-name event renders exactly once (no merge by name)' );
fixture_assert( 2 === substr_count( $out_m6, 'pfoa-adoption-event-member"' ), 'M6: both distinct-ID photos render (no dedupe by name)' );
fixture_assert( false === strpos( $out_m6, 'gallery-1-24001' ) && false === strpos( $out_m6, 'gallery-1-24002' ), 'M6: both distinct IDs suppressed by exact ID, not by name' );

// ---------------------------------------------------------------------------
// D1-D5 Sol re-QA strict-identifier battery, 0.1.142 (event 24666 claims
// 23330). Each case FAILS on 0.1.141 and PASSES after the fix. UNIT: each
// exercises the production presenter directly on synthetic item markup.
// ---------------------------------------------------------------------------
$GLOBALS['fixture_event_ids'] = array( 24666 );
fixture_set_event( 24666, FIXTURE_SHADOW_UUID_A, '2026-06-19', array( fixture_member( 23330, 'Shadow' ) ) );

// D1 repeated query conflict: two DIFFERENT attachment_id params plus a
// valid data-id must preserve (conflict, not silent removal) — and the
// survivor count must be 1 (preserved), not 0 (disappeared).
$out_d1 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6"><figure class="gallery-item"><a href="/?attachment_id=23330&attachment_id=99999"><img data-id="23330" src="https://example.com/medD1.jpg" alt=""></a></figure></div>'
);
fixture_assert( 1 === substr_count( $out_d1, 'gallery-item' ), 'D1: conflicting repeated href params preserve the item (1 survivor, not 0/disappeared)' );
fixture_assert( false !== strpos( $out_d1, 'medD1' ), 'D1: conflicting repeated href params leave the legitimate legacy image in place' );

// D1b unanimous repeats: two IDENTICAL 23330 params with no other signal
// are unanimous and suppress (inverse of D1; Sol href_unanimous_multiple).
$out_d1b = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6"><figure class="gallery-item"><a href="/?attachment_id=23330&attachment_id=23330"><img src="https://example.com/medD1b.jpg" alt=""></a></figure></div>'
);
fixture_assert( 1 === substr_count( $out_d1b, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'D1b: event renders once with unanimous repeated href params present' );
fixture_assert( false === strpos( $out_d1b, 'medD1b' ), 'D1b: unanimous repeated href params (23330+23330, no other signal) suppress' );

// D2 ARIA IDREF-list conflict: data-id 23330 plus TWO gallery references
// in one aria-describedby must preserve (second identity is not ignored).
$out_d2 = pfoa_present_adoption_events(
	fixture_conflict_gallery( '<figure class="gallery-item"><img data-id="23330" aria-describedby="gallery-1-23330 gallery-1-99999" src="https://example.com/medD2.jpg" alt=""></figure>' )
);
fixture_assert( 1 === substr_count( $out_d2, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'D2: event renders once with ARIA IDREF conflict present' );
fixture_assert( false !== strpos( $out_d2, 'medD2' ), 'D2: conflicting ARIA IDREF list fails closed — ambiguous node survives' );

// D2b ARIA-only positive: a lone aria-describedby="gallery-1-23330" with no
// data/class/href signal, matching the claim, suppresses (so disabling the
// ARIA collector fails this fixture).
$out_d2b = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . '<figure class="gallery-item"><img aria-describedby="gallery-1-23330" src="https://example.com/medD2b.jpg" alt=""></figure>' . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_d2b, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'D2b: event renders once with ARIA-only duplicate present' );
fixture_assert( false === strpos( $out_d2b, 'medD2b' ), 'D2b: ARIA-only duplicate of 23330 suppressed (ARIA collector indispensable)' );

// D3 terminal newline: data-id "23330\n" (actual 0x0A) is malformed and
// must preserve ($-anchor would accept it; \z rejects it).
$out_d3 = pfoa_present_adoption_events(
	fixture_conflict_gallery( '<figure class="gallery-item"><img data-id="23330' . "\n" . '" src="https://example.com/medD3.jpg" alt=""></figure>' )
);
fixture_assert( false !== strpos( $out_d3, 'medD3' ), 'D3: terminal-newline data-id never matches — node survives (exact-token boundary)' );

// D4 whitespace-tainted href: attachment_id "23330 " (trailing space) is
// malformed — never truncated to 23330 — and must preserve (sentinel,
// not silent removal and not suppression via a sibling token).
$out_d4 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6"><figure class="gallery-item"><a href="/?attachment_id=23330 "><img src="https://example.com/medD4.jpg" alt=""></a></figure></div>'
);
fixture_assert( 1 === substr_count( $out_d4, 'gallery-item' ), 'D4: whitespace-tainted href preserves the item (1 survivor, not suppressed)' );
fixture_assert( false !== strpos( $out_d4, 'medD4' ), 'D4: whitespace-tainted href value never truncates to the claimed ID' );

// D5 fragment: ?attachment_id= inside a URL FRAGMENT (#section?...) is not
// a query identifier and must yield zero IDs — the item preserves.
$out_d5 = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6"><figure class="gallery-item"><a href="https://example.com/#section?attachment_id=23330"><img src="https://example.com/medD5.jpg" alt=""></a></figure></div>'
);
fixture_assert( 1 === substr_count( $out_d5, 'gallery-item' ), 'D5: fragment ?attachment_id= is not query identity — item survives (1 survivor)' );
fixture_assert( false !== strpos( $out_d5, 'medD5' ), 'D5: fragment identifier never matches the claim' );

// Href-only positive: a lone ?attachment_id=23330 with no other signal,
// matching the claim, suppresses (so disabling the href collector fails).
$out_href_pos = pfoa_present_adoption_events(
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . '<figure class="gallery-item"><a href="/?attachment_id=23330"><img src="https://example.com/medHrefPos.jpg" alt=""></a></figure>' . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( 1 === substr_count( $out_href_pos, 'data-pfoa-adoption-event="' . FIXTURE_SHADOW_UUID_A . '"' ), 'HREF-POS: event renders once with href-only duplicate present' );
fixture_assert( false === strpos( $out_href_pos, 'medHrefPos' ), 'HREF-POS: href-only duplicate of 23330 suppressed (href collector indispensable)' );

// ---------------------------------------------------------------------------
// INTEGRATION: full the_content pipeline vs UNIT presenter coverage.
// (a) Real-WP gallery_shortcode() is NOT available in this stub harness
// (no WP loaded, no DB/network): direct-shortcode execution is an explicit
// SKIP here — real gallery_shortcode() + full the_content execution was
// verified by the Sol harness in real WordPress 7.1.3 (see REPORT §1:
// RED 1/1/1 → GREEN 1/1/0, direct shortcode + presenter and full
// the_content, plus emitter-disabled 1/1/1 control).
// (b) INTEGRATION via the stubbed the_content pipeline: the COMPLETE
// apply_filters('the_content') path (not a direct presenter call) must
// suppress the unanimous filter-enabled real item and preserve the
// conflicting item end to end, with the dispatch registrations above
// (the_content prio 950; emitter prio 10 + 3 args) proving the wiring the
// pipeline depends on.
// ---------------------------------------------------------------------------
fixture_assert( ! function_exists( 'gallery_shortcode' ), 'INTEGRATION: documents harness limit — no real gallery_shortcode() here (Sol real-WP evidence cited above)' );
$integration_suppress = apply_filters(
	'the_content',
	'<div class="gallery galleryid-22514 gallery-columns-6">' . fixture_gallery_item( 100 ) . fixture_real_shadow_item( true ) . fixture_gallery_item( 102 ) . '</div>'
);
fixture_assert( false === strpos( $integration_suppress, 'ShadowWeb4040' ), 'INTEGRATION: full the_content pipeline suppresses the filter-enabled unanimous real item' );
$integration_preserve = apply_filters(
	'the_content',
	'<div class="gallery galleryid-22514 gallery-columns-6"><figure class="gallery-item"><a href="/?attachment_id=23330&attachment_id=99999"><img data-id="23330" src="https://example.com/medInt.jpg" alt=""></a></figure></div>'
);
fixture_assert( false !== strpos( $integration_preserve, 'medInt' ), 'INTEGRATION: full the_content pipeline preserves the conflicting item end to end' );

// No writes across all mutations.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

fwrite( STDOUT, "PASS: theme adoption event senior shadow fixture (real captionless shape + filter identity via the_content, unanimity conflicts, fractional rejection, independence, no writes)\n" );
