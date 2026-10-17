<?php
/**
 * Focused fixture for conditional adoption-event badges (PFOA 0.1.86
 * restoration): red "Bonded Pair" for true bonded pairs, green "Adopted
 * Together" for non-bonded multi-cat events, no badge for singletons.
 *
 * Covers BOTH rendering paths with the same four cases:
 *   single -> no badge,
 *   bonded pair (2 reciprocal canonical IDs) -> red Bonded Pair,
 *   2 non-bonded cats -> green Adopted Together,
 *   3+ cats -> green Adopted Together (3+ never bonded).
 *
 * Path A: pfoa_present_adoption_events() (Adopted gallery, page 22514),
 * where the bonded classification is computed live via the strict
 * reciprocal Cat Profiles helpers (fail-closed).
 *
 * Path B: pfoa_render_recently_adopted_event_card() (Recently Adopted
 * strip), where the bonded classification arrives as upstream
 * bonded_pair data (not recomputed here).
 *
 * Historical traceability (plugin owns classification truth; this fixture
 * only proves the theme renders each documented classification):
 *   Sugar/Spice manifest bonded_pair [24414,24416] -> red (bonded case),
 *   Frost/Lightning adopted_together [24515,24517] -> green (pair case),
 *   Spencer trio adopted_together -> green (trio case),
 *   Cloud/Storm uuid-new-pair 2026-10-10 (synthetic, no manifest entry)
 *     -> green pair when non-bonded,
 *   Nellie/Rocky zero hits on this branch -> nothing to render.
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Exits nonzero on any failure.
 *
 * Run with:
 *   php tests/theme-adoption-badge-conditional-fixture.php
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

function fixture_member( $image_id, $name, $cat_post_id = 0 ) {
	return array(
		'image_id'          => (int) $image_id,
		'cat_name_at_event' => (string) $name,
		'caption'           => (string) $name,
		'cat_post_id'       => (int) $cat_post_id,
	);
}

function fixture_card_member( $image_id, $name ) {
	return array(
		'image_id' => (int) $image_id,
		'name'     => (string) $name,
		'caption'  => (string) $name,
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

fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'gallery path exists' );
fixture_assert( function_exists( 'pfoa_render_recently_adopted_event_card' ), 'card path exists' );

// Strict reciprocal bonded doubles: 701/702 bonded (Sugar/Spice analogue),
// 801/802 explicitly NOT bonded (Frost/Lightning analogue).
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, array( 701, 702 ), true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	if ( in_array( (int) $cat_id, array( 701, 702 ), true ) ) {
		return array( 701, 702 );
	}
	return array();
}

// ---- Path A: gallery rendering, one event per render. ----
$gallery_cases = array(
	// Single (singleton analogue) -> no badge.
	'single'  => array(
		'event_id' => 11,
		'uuid'     => 'uuid-badge-single',
		'date'     => '2026-10-06',
		'members'  => array( fixture_member( 111, 'Solo' ) ),
	),
	// True bonded pair (Sugar/Spice analogue) -> red.
	'bonded'  => array(
		'event_id' => 12,
		'uuid'     => 'uuid-badge-bonded',
		'date'     => '2026-10-05',
		'members'  => array( fixture_member( 701, 'Sugar', 701 ), fixture_member( 702, 'Spice', 702 ) ),
	),
	// Non-bonded pair (Frost/Lightning, Cloud/Storm analogue) -> green.
	'pair'    => array(
		'event_id' => 13,
		'uuid'     => 'uuid-badge-pair',
		'date'     => '2026-10-04',
		'members'  => array( fixture_member( 801, 'Frost', 801 ), fixture_member( 802, 'Lightning', 802 ) ),
	),
	// Trio (Spencer trio analogue, 3+ never bonded) -> green.
	'trio'    => array(
		'event_id' => 14,
		'uuid'     => 'uuid-badge-trio',
		'date'     => '2026-10-03',
		'members'  => array( fixture_member( 901, 'Spencer', 901 ), fixture_member( 902, 'Thompson', 902 ), fixture_member( 903, 'Wilbur', 903 ) ),
	),
);

foreach ( $gallery_cases as $case_name => $case_spec ) {
	$GLOBALS['fixture_event_ids'] = array( $case_spec['event_id'] );
	fixture_set_event( $case_spec['event_id'], $case_spec['uuid'], $case_spec['date'], $case_spec['members'] );
	$image_ids = array();
	foreach ( $case_spec['members'] as $case_member ) {
		$image_ids[] = $case_member['image_id'];
	}
	$case_out = pfoa_present_adoption_events( fixture_gallery( $image_ids ) );
	fixture_assert( 1 === substr_count( $case_out, 'data-pfoa-adoption-event="' . $case_spec['uuid'] . '"' ), 'gallery: ' . $case_name . ' renders once' );

	if ( 'single' === $case_name ) {
		fixture_assert( false === strpos( $case_out, 'bonded-badge' ), 'gallery: single carries no red badge' );
		fixture_assert( false === strpos( $case_out, 'together-badge' ), 'gallery: single carries no green badge' );
	} elseif ( 'bonded' === $case_name ) {
		fixture_assert( false !== strpos( $case_out, 'pfoa-adoption-event--bonded' ), 'gallery: bonded pair keeps --bonded class' );
		fixture_assert( 1 === substr_count( $case_out, 'pfoa-adoption-event-bonded-badge' ), 'gallery: bonded pair renders one red badge' );
		fixture_assert( 1 === substr_count( $case_out, 'Bonded Pair' ), 'gallery: bonded pair red text' );
		fixture_assert( false !== strpos( $case_out, 'adopted together as a bonded pair' ), 'gallery: bonded pair screen-reader suffix' );
		fixture_assert( false === strpos( $case_out, 'pfoa-adoption-event-together-badge' ), 'gallery: bonded pair renders no green badge' );
	} else {
		fixture_assert( false !== strpos( $case_out, 'pfoa-adoption-event--together' ), 'gallery: ' . $case_name . ' keeps --together class' );
		fixture_assert( 1 === substr_count( $case_out, 'pfoa-adoption-event-together-badge' ), 'gallery: ' . $case_name . ' renders one green badge' );
		fixture_assert( 1 === substr_count( $case_out, 'Adopted Together' ), 'gallery: ' . $case_name . ' green text' );
		fixture_assert( false !== strpos( $case_out, 'in the same adoption event' ), 'gallery: ' . $case_name . ' screen-reader suffix' );
		fixture_assert( false === strpos( $case_out, 'pfoa-adoption-event-bonded-badge' ), 'gallery: ' . $case_name . ' renders no red badge' );
	}
}

// ---- Path B: recently-adopted card rendering, same four cases. ----
$card_cases = array(
	'single' => array(
		'uuid'        => 'uuid-card-single',
		'bonded_pair' => false,
		'members'     => array( fixture_card_member( 211, 'Solo' ) ),
	),
	'bonded' => array(
		'uuid'        => 'uuid-card-bonded',
		'bonded_pair' => true,
		'members'     => array( fixture_card_member( 212, 'Sugar' ), fixture_card_member( 213, 'Spice' ) ),
	),
	'pair'   => array(
		'uuid'        => 'uuid-card-pair',
		'bonded_pair' => false,
		'members'     => array( fixture_card_member( 214, 'Cloud' ), fixture_card_member( 215, 'Storm' ) ),
	),
	'trio'   => array(
		'uuid'        => 'uuid-card-trio',
		'bonded_pair' => false,
		'members'     => array( fixture_card_member( 216, 'Spencer' ), fixture_card_member( 217, 'Thompson' ), fixture_card_member( 218, 'Wilbur' ) ),
	),
);

foreach ( $card_cases as $case_name => $case_spec ) {
	$card_event = array(
		'id'          => 100,
		'date'        => '2026-10-10',
		'uuid'        => $case_spec['uuid'],
		'members'     => $case_spec['members'],
		'bonded_pair' => $case_spec['bonded_pair'],
	);
	$card_out   = pfoa_render_recently_adopted_event_card( $card_event );
	fixture_assert( '' !== $card_out, 'card: ' . $case_name . ' renders markup' );
	fixture_assert( 1 === substr_count( $card_out, 'data-pfoa-adoption-event="' . $case_spec['uuid'] . '"' ), 'card: ' . $case_name . ' wrapper once' );

	if ( 'single' === $case_name ) {
		fixture_assert( false === strpos( $card_out, 'bonded-badge' ), 'card: single carries no red badge' );
		fixture_assert( false === strpos( $card_out, 'together-badge' ), 'card: single carries no green badge' );
	} elseif ( 'bonded' === $case_name ) {
		fixture_assert( false !== strpos( $card_out, 'pfoa-adoption-event--bonded' ), 'card: bonded pair keeps --bonded class' );
		fixture_assert( 1 === substr_count( $card_out, 'pfoa-adoption-event-bonded-badge' ), 'card: bonded pair renders one red badge' );
		fixture_assert( 1 === substr_count( $card_out, 'Bonded Pair' ), 'card: bonded pair red text' );
		fixture_assert( false !== strpos( $card_out, 'adopted together as a bonded pair' ), 'card: bonded pair screen-reader suffix' );
		fixture_assert( false === strpos( $card_out, 'pfoa-adoption-event-together-badge' ), 'card: bonded pair renders no green badge' );
	} else {
		fixture_assert( false !== strpos( $card_out, 'pfoa-adoption-event--together' ), 'card: ' . $case_name . ' keeps --together class' );
		fixture_assert( 1 === substr_count( $card_out, 'pfoa-adoption-event-together-badge' ), 'card: ' . $case_name . ' renders one green badge' );
		fixture_assert( 1 === substr_count( $card_out, 'Adopted Together' ), 'card: ' . $case_name . ' green text' );
		fixture_assert( false !== strpos( $card_out, 'in the same adoption event' ), 'card: ' . $case_name . ' screen-reader suffix' );
		fixture_assert( false === strpos( $card_out, 'pfoa-adoption-event-bonded-badge' ), 'card: ' . $case_name . ' renders no red badge' );
	}
}

// ---- Combined public counts: 1 red + 2 green per path. ----
$GLOBALS['fixture_event_ids'] = array( 11, 12, 13, 14 );
foreach ( $gallery_cases as $case_spec ) {
	fixture_set_event( $case_spec['event_id'], $case_spec['uuid'], $case_spec['date'], $case_spec['members'] );
}
$gallery_all = pfoa_present_adoption_events( fixture_gallery( array( 111, 701, 702, 801, 802, 901, 902, 903 ) ) );
fixture_assert( 1 === substr_count( $gallery_all, 'pfoa-adoption-event-bonded-badge' ), 'gallery combined: exactly 1 red badge' );
fixture_assert( 2 === substr_count( $gallery_all, 'pfoa-adoption-event-together-badge' ), 'gallery combined: exactly 2 green badges' );

$cards_all = '';
foreach ( $card_cases as $case_spec ) {
	$cards_all .= pfoa_render_recently_adopted_event_card(
		array(
			'id'          => 100,
			'date'        => '2026-10-10',
			'uuid'        => $case_spec['uuid'],
			'members'     => $case_spec['members'],
			'bonded_pair' => $case_spec['bonded_pair'],
		)
	);
}
fixture_assert( 1 === substr_count( $cards_all, 'pfoa-adoption-event-bonded-badge' ), 'card combined: exactly 1 red badge' );
fixture_assert( 2 === substr_count( $cards_all, 'pfoa-adoption-event-together-badge' ), 'card combined: exactly 2 green badges' );

// ---- CSS contract: both badge classes keep their exact colors. ----
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_assert( 1 === preg_match( '/--pfoa-color-brand:\s*#934244/', $css ), 'css: brand red #934244 intact' );
fixture_assert( 1 === preg_match( '/--pfoa-accent:\s*#057205/', $css ), 'css: accent green #057205 intact' );
fixture_assert( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-bonded-badge\s*\{\s*background:\s*var\(--pfoa-color-brand\)/', $css ), 'css: recently-adopted red badge keeps brand red' );
fixture_assert( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-together-badge\s*\{\s*background:\s*var\(--pfoa-cta-primary\)/', $css ), 'css: recently-adopted green badge keeps CTA green' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during badge rendering' );

fwrite( STDOUT, "PASS: theme conditional adoption badges (single none, bonded red, pair/trio green) on gallery + recently-adopted paths; combined 1 red + 2 green per path; CSS colors intact; no writes\n" );
