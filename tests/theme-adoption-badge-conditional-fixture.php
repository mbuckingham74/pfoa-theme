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

// Strict reciprocal bonded doubles: 701/702 bonded (Sugar/Spice analogue)
// AND 901/902 bonded (Alpha/Beta analogue); 801/802 explicitly NOT
// bonded (Frost/Lightning analogue); cross-double 701/801 is NOT reciprocal.
function pfoa_cat_is_bonded( $cat_id ) {
	return in_array( (int) $cat_id, array( 701, 702, 901, 902 ), true );
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	$cat_id = (int) $cat_id;
	if ( in_array( $cat_id, array( 701, 702 ), true ) ) {
		return array( 701, 702 );
	}
	if ( in_array( $cat_id, array( 901, 902 ), true ) ) {
		return array( 901, 902 );
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
	fixture_require( 1 === substr_count( $case_out, 'data-pfoa-adoption-event="' . $case_spec['uuid'] . '"' ), 'Single/bonded/pair/trio gallery', 'gallery: ' . $case_name . ' renders once (expected 1 wrapper for ' . $case_spec['uuid'] . ')' );

	if ( 'single' === $case_name ) {
		fixture_require( false === strpos( $case_out, 'bonded-badge' ), 'Single gallery', 'expected no red bonded badge on singleton, found bonded-badge markup' );
		fixture_require( false === strpos( $case_out, 'together-badge' ), 'Single gallery', 'expected no green together badge on singleton, found together-badge markup' );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event--bonded' ), 'Single gallery', 'expected no --bonded class on singleton' );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event--together' ), 'Single gallery', 'expected no --together class on singleton' );
	} elseif ( 'bonded' === $case_name ) {
		fixture_require( false !== strpos( $case_out, 'pfoa-adoption-event--bonded' ), 'Bonded Pair gallery', 'expected --bonded modifier class on true bonded pair' );
		fixture_require( 1 === substr_count( $case_out, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair gallery', 'expected exactly 1 red bonded badge, found ' . substr_count( $case_out, 'pfoa-adoption-event-bonded-badge' ) );
		fixture_require( 1 === substr_count( $case_out, 'Bonded Pair' ), 'Bonded Pair gallery', 'expected exactly 1 red "Bonded Pair" text node' );
		fixture_require( false !== strpos( $case_out, 'adopted together as a bonded pair' ), 'Bonded Pair gallery', 'expected bonded screen-reader suffix' );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair gallery', 'expected 0 green together badges on bonded pair, found green markup (universal-green regression)' );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event--together' ), 'Bonded Pair gallery', 'expected no --together class on bonded pair' );
	} else {
		fixture_require( false !== strpos( $case_out, 'pfoa-adoption-event--together' ), 'Adopted Together gallery', 'expected --together modifier class on ' . $case_name );
		fixture_require( 1 === substr_count( $case_out, 'pfoa-adoption-event-together-badge' ), 'Adopted Together gallery', 'expected exactly 1 green together badge on ' . $case_name . ', found ' . substr_count( $case_out, 'pfoa-adoption-event-together-badge' ) );
		fixture_require( 1 === substr_count( $case_out, 'Adopted Together' ), 'Adopted Together gallery', 'expected exactly 1 green "Adopted Together" text on ' . $case_name );
		fixture_require( false !== strpos( $case_out, 'in the same adoption event' ), 'Adopted Together gallery', 'expected together screen-reader suffix on ' . $case_name );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event-bonded-badge' ), 'Adopted Together gallery', 'expected 0 red bonded badges on non-bonded ' . $case_name );
		fixture_require( false === strpos( $case_out, 'pfoa-adoption-event--bonded' ), 'Adopted Together gallery', 'expected no --bonded class on non-bonded ' . $case_name );
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
	fixture_require( '' !== $card_out, 'Single/bonded/pair/trio card', 'card: ' . $case_name . ' renders markup' );
	fixture_require( 1 === substr_count( $card_out, 'data-pfoa-adoption-event="' . $case_spec['uuid'] . '"' ), 'Single/bonded/pair/trio card', 'card: ' . $case_name . ' wrapper once' );

	if ( 'single' === $case_name ) {
		fixture_require( false === strpos( $card_out, 'bonded-badge' ), 'Single card', 'expected no red bonded badge on singleton card, found bonded-badge markup' );
		fixture_require( false === strpos( $card_out, 'together-badge' ), 'Single card', 'expected no green together badge on singleton card, found together-badge markup' );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event--bonded' ), 'Single card', 'expected no --bonded class on singleton card' );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event--together' ), 'Single card', 'expected no --together class on singleton card' );
	} elseif ( 'bonded' === $case_name ) {
		fixture_require( false !== strpos( $card_out, 'pfoa-adoption-event--bonded' ), 'Bonded Pair card', 'expected --bonded modifier class on true bonded card' );
		fixture_require( 1 === substr_count( $card_out, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair card', 'expected exactly 1 red bonded badge on card, found ' . substr_count( $card_out, 'pfoa-adoption-event-bonded-badge' ) );
		fixture_require( 1 === substr_count( $card_out, 'Bonded Pair' ), 'Bonded Pair card', 'expected exactly 1 red "Bonded Pair" text node on card' );
		fixture_require( false !== strpos( $card_out, 'adopted together as a bonded pair' ), 'Bonded Pair card', 'expected bonded screen-reader suffix on card' );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair card', 'expected 0 green together badges on bonded card, found green markup (universal-green regression)' );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event--together' ), 'Bonded Pair card', 'expected no --together class on bonded card' );
	} else {
		fixture_require( false !== strpos( $card_out, 'pfoa-adoption-event--together' ), 'Adopted Together card', 'expected --together modifier class on card ' . $case_name );
		fixture_require( 1 === substr_count( $card_out, 'pfoa-adoption-event-together-badge' ), 'Adopted Together card', 'expected exactly 1 green together badge on card ' . $case_name . ', found ' . substr_count( $card_out, 'pfoa-adoption-event-together-badge' ) );
		fixture_require( 1 === substr_count( $card_out, 'Adopted Together' ), 'Adopted Together card', 'expected exactly 1 green "Adopted Together" text on card ' . $case_name );
		fixture_require( false !== strpos( $card_out, 'in the same adoption event' ), 'Adopted Together card', 'expected together screen-reader suffix on card ' . $case_name );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event-bonded-badge' ), 'Adopted Together card', 'expected 0 red bonded badges on non-bonded card ' . $case_name );
		fixture_require( false === strpos( $card_out, 'pfoa-adoption-event--bonded' ), 'Adopted Together card', 'expected no --bonded class on non-bonded card ' . $case_name );
	}
}

// ---- Reciprocal doubles: second bonded double 901/902, reversed order,
// and non-reciprocal negatives prove strict reciprocal validation. ----
$GLOBALS['fixture_event_ids'] = array( 21 );
fixture_set_event( 21, 'uuid-badge-bonded2', '2026-10-02', array( fixture_member( 901, 'Alpha', 901 ), fixture_member( 902, 'Beta', 902 ) ) );
$out_bonded2 = pfoa_present_adoption_events( fixture_gallery( array( 901, 902 ) ) );
fixture_require( 1 === substr_count( $out_bonded2, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair gallery', 'expected exactly 1 red bonded badge for second double 901/902, found ' . substr_count( $out_bonded2, 'pfoa-adoption-event-bonded-badge' ) );
fixture_require( false === strpos( $out_bonded2, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair gallery', 'expected 0 green together badges for second double 901/902 (universal-green regression)' );
fixture_require( false !== strpos( $out_bonded2, 'pfoa-adoption-event--bonded' ), 'Bonded Pair gallery', 'expected --bonded class for second double 901/902' );

// Reversed member order (702/701) is still the same reciprocal pair -> red.
$GLOBALS['fixture_event_ids'] = array( 22 );
fixture_set_event( 22, 'uuid-badge-bonded-rev', '2026-10-01', array( fixture_member( 702, 'Spice', 702 ), fixture_member( 701, 'Sugar', 701 ) ) );
$out_rev = pfoa_present_adoption_events( fixture_gallery( array( 702, 701 ) ) );
fixture_require( 1 === substr_count( $out_rev, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair gallery', 'expected exactly 1 red bonded badge for reversed order 702/701' );
fixture_require( false === strpos( $out_rev, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair gallery', 'expected 0 green together badges for reversed order 702/701' );

// Cross-double 701/801 shares one bonded ID but is NOT reciprocal -> green.
$GLOBALS['fixture_event_ids'] = array( 23 );
fixture_set_event( 23, 'uuid-badge-nonrecip', '2026-09-30', array( fixture_member( 701, 'Sugar', 701 ), fixture_member( 801, 'Frost', 801 ) ) );
$out_nonrecip = pfoa_present_adoption_events( fixture_gallery( array( 701, 801 ) ) );
fixture_require( 1 === substr_count( $out_nonrecip, 'pfoa-adoption-event-together-badge' ), 'Adopted Together gallery', 'expected exactly 1 green together badge for non-reciprocal 701/801' );
fixture_require( false === strpos( $out_nonrecip, 'pfoa-adoption-event-bonded-badge' ), 'Adopted Together gallery', 'expected 0 red bonded badges for non-reciprocal 701/801' );

// Trio containing both bonded IDs 901+902 plus a third is 3+ => never
// bonded -> exactly 1 green.
$GLOBALS['fixture_event_ids'] = array( 24 );
fixture_set_event( 24, 'uuid-badge-trio-bonded-ids', '2026-09-29', array( fixture_member( 901, 'Alpha', 901 ), fixture_member( 902, 'Beta', 902 ), fixture_member( 903, 'Gamma', 903 ) ) );
$out_trio_ids = pfoa_present_adoption_events( fixture_gallery( array( 901, 902, 903 ) ) );
fixture_require( 1 === substr_count( $out_trio_ids, 'pfoa-adoption-event-together-badge' ), 'Adopted Together gallery', 'expected exactly 1 green together badge for trio holding bonded IDs (3+ never bonded)' );
fixture_require( false === strpos( $out_trio_ids, 'pfoa-adoption-event-bonded-badge' ), 'Adopted Together gallery', 'expected 0 red bonded badges for trio holding bonded IDs' );
fixture_require( false !== strpos( $out_trio_ids, 'pfoa-adoption-event--together' ), 'Adopted Together gallery', 'expected --together class for trio holding bonded IDs' );

// Card-path parity for the second double 901/902 (bonded_pair=true -> red)
// and for a non-bonded double (bonded_pair=false -> green).
$card_bonded2 = pfoa_render_recently_adopted_event_card(
	array(
		'id'          => 101,
		'date'        => '2026-10-10',
		'uuid'        => 'uuid-card-bonded2',
		'members'     => array( fixture_card_member( 221, 'Alpha' ), fixture_card_member( 222, 'Beta' ) ),
		'bonded_pair' => true,
	)
);
fixture_require( 1 === substr_count( $card_bonded2, 'pfoa-adoption-event-bonded-badge' ), 'Bonded Pair card', 'expected exactly 1 red bonded badge on second-double card 901/902' );
fixture_require( false === strpos( $card_bonded2, 'pfoa-adoption-event-together-badge' ), 'Bonded Pair card', 'expected 0 green together badges on second-double card 901/902' );

$card_nonrecip = pfoa_render_recently_adopted_event_card(
	array(
		'id'          => 102,
		'date'        => '2026-10-09',
		'uuid'        => 'uuid-card-nonrecip',
		'members'     => array( fixture_card_member( 223, 'Sugar' ), fixture_card_member( 224, 'Frost' ) ),
		'bonded_pair' => false,
	)
);
fixture_require( 1 === substr_count( $card_nonrecip, 'pfoa-adoption-event-together-badge' ), 'Adopted Together card', 'expected exactly 1 green together badge on non-bonded card' );
fixture_require( false === strpos( $card_nonrecip, 'pfoa-adoption-event-bonded-badge' ), 'Adopted Together card', 'expected 0 red bonded badges on non-bonded card' );

// ---- Combined public counts: 1 red + 2 green per path. ----
$GLOBALS['fixture_event_ids'] = array( 11, 12, 13, 14 );
foreach ( $gallery_cases as $case_spec ) {
	fixture_set_event( $case_spec['event_id'], $case_spec['uuid'], $case_spec['date'], $case_spec['members'] );
}
$gallery_all = pfoa_present_adoption_events( fixture_gallery( array( 111, 701, 702, 801, 802, 901, 902, 903 ) ) );
fixture_require( 1 === substr_count( $gallery_all, 'pfoa-adoption-event-bonded-badge' ), 'Combined gallery counts', 'expected exactly 1 red badge in combined gallery, found ' . substr_count( $gallery_all, 'pfoa-adoption-event-bonded-badge' ) );
fixture_require( 2 === substr_count( $gallery_all, 'pfoa-adoption-event-together-badge' ), 'Combined gallery counts', 'expected exactly 2 green badges in combined gallery, found ' . substr_count( $gallery_all, 'pfoa-adoption-event-together-badge' ) );

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
fixture_require( 1 === substr_count( $cards_all, 'pfoa-adoption-event-bonded-badge' ), 'Combined card counts', 'expected exactly 1 red badge across combined cards, found ' . substr_count( $cards_all, 'pfoa-adoption-event-bonded-badge' ) );
fixture_require( 2 === substr_count( $cards_all, 'pfoa-adoption-event-together-badge' ), 'Combined card counts', 'expected exactly 2 green badges across combined cards, found ' . substr_count( $cards_all, 'pfoa-adoption-event-together-badge' ) );

// ---- CSS contract: both badge colors keep their exact colors + scope. ----
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_require( 1 === preg_match( '/--pfoa-color-brand:\s*#934244/', $css ), 'CSS brand red', 'expected --pfoa-color-brand: #934244 intact' );
fixture_require( 1 === preg_match( '/--pfoa-accent:\s*#057205/', $css ), 'CSS accent green', 'expected --pfoa-accent: #057205 intact' );
fixture_require( 1 === preg_match( '/--pfoa-cta-primary:\s*var\(--pfoa-accent\)/', $css ), 'CSS CTA green linkage', 'expected --pfoa-cta-primary: var(--pfoa-accent) linkage intact' );
fixture_require( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-bonded-badge\s*\{\s*background:\s*var\(--pfoa-color-brand\)/', $css ), 'CSS Recently Adopted red scope', 'expected recently-adopted red badge keeps brand red' );
fixture_require( 1 === preg_match( '/\.pfoa-recently-adopted \.pfoa-adoption-event-together-badge\s*\{\s*background:\s*var\(--pfoa-cta-primary\)/', $css ), 'CSS Recently Adopted green scope', 'expected recently-adopted green badge keeps CTA green' );
fixture_require( false !== strpos( $css, 'body.page-id-22514 .pfoa-adoption-event-bonded-badge' ), 'CSS gallery red scope', 'expected gallery red badge scoped to body.page-id-22514' );
fixture_require( false !== strpos( $css, 'body.page-id-22514 .pfoa-adoption-event-together-badge' ), 'CSS gallery green scope', 'expected gallery green badge scoped to body.page-id-22514' );
fixture_require( 1 === preg_match( '/body\.page-id-22514 \.pfoa-adoption-event-bonded-badge\s*\{\s*background:\s*var\(--pfoa-color-brand\)/', $css ), 'CSS gallery red color', 'expected gallery red badge background var(--pfoa-color-brand) (#934244)' );
fixture_require( 1 === preg_match( '/body\.page-id-22514 \.pfoa-adoption-event-together-badge\s*\{\s*background:\s*var\(--pfoa-cta-primary\)/', $css ), 'CSS gallery green color', 'expected gallery green badge background var(--pfoa-cta-primary) (#057205)' );
fixture_require( false !== strpos( $css, 'body.page-id-20323 .pfoa-recently-adopted' ), 'CSS Recently Adopted page scope', 'expected recently-adopted block scoped to body.page-id-20323' );

// No writes, no throws, read-only meta access.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during badge rendering' );

fwrite( STDOUT, "PASS: theme conditional adoption badges (single none, bonded red 701/702 + 901/902 incl. reversed, non-reciprocal/trio green) on gallery + recently-adopted paths; combined 1 red + 2 green per path; CSS colors + gallery/recently scopes intact; no writes\n" );
