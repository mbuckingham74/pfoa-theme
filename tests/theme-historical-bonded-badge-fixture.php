<?php
/**
 * LC-D02 historical bonded-badge preservation fixture.
 *
 * Proves pfoa_adoption_event_is_bonded_pair_for_display() honors the valid,
 * explicitly stored classification for authentic two-member events
 * independently of the cats' current bond relationship, while keeping
 * legacy compatibility for events without authoritative classification
 * and failing closed on ambiguous/malformed data.
 *
 * Reader path: defines a pfoa_tx_read_stored_classification() stub mirroring
 * the plugin contract (absent/valid/invalid/duplicate) backed by
 * $GLOBALS['fixture_class_rows']. Strict bond doubles are controllable via
 * $GLOBALS['fixture_bonded_pairs'] so "currently bonded" vs "later
 * unbonded" is simulated without touching stored history.
 *
 * Gallery path: pfoa_present_adoption_events() end-to-end for the QA
 * event shape (stored bonded_pair, cats later unbonded => Bonded Pair;
 * stored adopted_together, cats later bonded => Adopted Together).
 *
 * Requires pfoa-theme/functions.php directly with WordPress doubles
 * stubbed inline. No network, database, DEV, or provider dependency.
 * Exits nonzero on any failure.
 *
 * Run with:
 *   php tests/theme-historical-bonded-badge-fixture.php
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
// event_id => null (absent) | array of stored rows (mirrors meta rows).
$GLOBALS['fixture_class_rows'] = array();
// List of canonical bonded pairs currently "live", e.g. array(array(701,702)).
$GLOBALS['fixture_bonded_pairs'] = array();

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

/**
 * Canonical stored-classification reader stub (plugin contract mirror).
 *
 * @param int $event_id Event ID.
 * @return array{state: string, value: string|null}
 */
function pfoa_tx_read_stored_classification( $event_id ) {
	$event_id = (int) $event_id;
	if ( ! array_key_exists( $event_id, $GLOBALS['fixture_class_rows'] ) || null === $GLOBALS['fixture_class_rows'][ $event_id ] ) {
		return array( 'state' => 'absent', 'value' => null );
	}
	$rows = $GLOBALS['fixture_class_rows'][ $event_id ];
	if ( ! is_array( $rows ) || array() === $rows ) {
		return array( 'state' => 'absent', 'value' => null );
	}
	if ( 1 === count( $rows ) && ( '' === $rows[0] || null === $rows[0] ) ) {
		return array( 'state' => 'absent', 'value' => null );
	}
	if ( 1 !== count( $rows ) ) {
		return array( 'state' => 'duplicate', 'value' => is_scalar( $rows[0] ) ? (string) $rows[0] : '?' );
	}
	$allowed = array( 'singleton', 'bonded_pair', 'adopted_together' );
	if ( ! in_array( $rows[0], $allowed, true ) ) {
		return array( 'state' => 'invalid', 'value' => is_scalar( $rows[0] ) ? (string) $rows[0] : '?' );
	}
	return array( 'state' => 'valid', 'value' => $rows[0] );
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

// Controllable strict bond doubles (legacy path + adoptable-card parity).
function pfoa_cat_is_bonded( $cat_id ) {
	$cat_id = (int) $cat_id;
	foreach ( $GLOBALS['fixture_bonded_pairs'] as $pair ) {
		if ( is_array( $pair ) && in_array( $cat_id, array_map( 'intval', $pair ), true ) ) {
			return true;
		}
	}
	return false;
}
function pfoa_cat_get_bonded_pair( $cat_id ) {
	$cat_id = (int) $cat_id;
	foreach ( $GLOBALS['fixture_bonded_pairs'] as $pair ) {
		if ( is_array( $pair ) && in_array( $cat_id, array_map( 'intval', $pair ), true ) ) {
			return array_map( 'intval', array_values( $pair ) );
		}
	}
	return array();
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

fixture_assert( function_exists( 'pfoa_adoption_event_is_bonded_pair_for_display' ), 'predicate exists' );
fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'gallery path exists' );

// ---- Predicate matrix (LC-D02 regression 1-9). ----
$pass_count = 0;
function check_predicate( $label, $event_id, $bond_ids, $expected ) {
	global $pass_count;
	$actual = pfoa_adoption_event_is_bonded_pair_for_display( $event_id, $bond_ids );
	fixture_require(
		$expected === $actual,
		'LC-D02 predicate',
		$label . ': expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true )
	);
	++$pass_count;
}

// 1. Stored bonded_pair, cats currently bonded => Bonded Pair.
$GLOBALS['fixture_bonded_pairs'] = array( array( 701, 702 ) );
$GLOBALS['fixture_class_rows'][101] = array( 'bonded_pair' );
check_predicate( '1 stored bonded_pair + currently bonded', 101, array( 701, 702 ), true );

// 2. Stored bonded_pair, cats later unbonded => Bonded Pair (LC-D02 core).
$GLOBALS['fixture_bonded_pairs'] = array();
$GLOBALS['fixture_class_rows'][102] = array( 'bonded_pair' );
check_predicate( '2 stored bonded_pair + later unbonded', 102, array( 701, 702 ), true );

// 3. Stored bonded_pair, one/both returned (bond cleared) => preserved.
$GLOBALS['fixture_bonded_pairs'] = array();
$GLOBALS['fixture_class_rows'][103] = array( 'bonded_pair' );
check_predicate( '3 stored bonded_pair + returned/unbonded', 103, array( 701, 702 ), true );

// 4. Stored bonded_pair, participant later readopted => preserved.
$GLOBALS['fixture_bonded_pairs'] = array();
$GLOBALS['fixture_class_rows'][104] = array( 'bonded_pair' );
check_predicate( '4 stored bonded_pair + readopted', 104, array( 701, 702 ), true );

// 5. Stored adopted_together, cats unbonded => Adopted Together (false).
$GLOBALS['fixture_bonded_pairs'] = array();
$GLOBALS['fixture_class_rows'][105] = array( 'adopted_together' );
check_predicate( '5 stored adopted_together + unbonded', 105, array( 801, 802 ), false );

// 6. Stored adopted_together, cats subsequently bonded => still Together.
$GLOBALS['fixture_bonded_pairs'] = array( array( 801, 802 ) );
$GLOBALS['fixture_class_rows'][106] = array( 'adopted_together' );
check_predicate( '6 stored adopted_together + later bonded (no upgrade)', 106, array( 801, 802 ), false );

// 7. Three-cat event => never bonded (count gate).
$GLOBALS['fixture_bonded_pairs'] = array( array( 901, 902 ) );
$GLOBALS['fixture_class_rows'][107] = array( 'adopted_together' );
check_predicate( '7 trio count gate', 107, array( 901, 902, 903 ), false );

// 8. Singleton => never bonded (count gate).
$GLOBALS['fixture_class_rows'][108] = array( 'singleton' );
check_predicate( '8 singleton count gate', 108, array( 701 ), false );
check_predicate( '8b singleton pair-input with singleton stored', 108, array( 701, 702 ), false );

// 9. Legacy/malformed conservative behavior.
$GLOBALS['fixture_bonded_pairs'] = array( array( 701, 702 ) );
unset( $GLOBALS['fixture_class_rows'][109] );
check_predicate( '9a absent + currently bonded => legacy true', 109, array( 701, 702 ), true );
$GLOBALS['fixture_bonded_pairs'] = array();
check_predicate( '9b absent + unbonded => legacy false', 109, array( 701, 702 ), false );
$GLOBALS['fixture_bonded_pairs'] = array( array( 701, 702 ) );
$GLOBALS['fixture_class_rows'][110] = array( 'bogus_pair' );
check_predicate( '9c invalid stored => conservative false', 110, array( 701, 702 ), false );
$GLOBALS['fixture_class_rows'][111] = array( 'bonded_pair', 'bonded_pair' );
check_predicate( '9d duplicate rows => conservative false', 111, array( 701, 702 ), false );
$GLOBALS['fixture_class_rows'][112] = array( '' );
check_predicate( '9e empty sentinel (absent) + bonded => legacy true', 112, array( 701, 702 ), true );
$GLOBALS['fixture_class_rows'][113] = array( 'bonded_pair' );
check_predicate( '9f zero/duplicate member IDs => false even when stored bonded', 113, array( 0, 702 ), false );
check_predicate( '9g same ID twice => false', 113, array( 701, 701 ), false );

// 10. Current adoptable bonded-pair validation unchanged (strict helpers
// still gate the absent-classification path).
$GLOBALS['fixture_bonded_pairs'] = array( array( 701, 702 ) );
unset( $GLOBALS['fixture_class_rows'][114] );
check_predicate( '10 absent + reciprocal bonded => true (adoptable parity)', 114, array( 701, 702 ), true );

// ---- Gallery end-to-end (Adopted 2026 path). ----
// QA shape: stored bonded_pair, cats later UNBONDED => red Bonded Pair.
$GLOBALS['fixture_bonded_pairs'] = array();
$GLOBALS['fixture_class_rows'][201] = array( 'bonded_pair' );
$GLOBALS['fixture_event_ids']       = array( 201 );
fixture_set_event( 201, 'uuid-lcd02-bonded', '2026-10-21', array( fixture_member( 701, 'Stagger A', 701 ), fixture_member( 702, 'Stagger B', 702 ) ) );
$out_bonded = pfoa_present_adoption_events( fixture_gallery( array( 701, 702 ) ) );
fixture_require( 1 === substr_count( $out_bonded, 'pfoa-adoption-event-bonded-badge' ), 'LC-D02 gallery', 'stored bonded_pair + unbonded renders exactly 1 red bonded badge' );
fixture_require( false === strpos( $out_bonded, 'pfoa-adoption-event-together-badge' ), 'LC-D02 gallery', 'stored bonded_pair + unbonded renders 0 green badges' );

// Stored adopted_together, cats later BONDED => green Adopted Together.
$GLOBALS['fixture_bonded_pairs'] = array( array( 801, 802 ) );
$GLOBALS['fixture_class_rows'][202] = array( 'adopted_together' );
$GLOBALS['fixture_event_ids']       = array( 202 );
fixture_set_event( 202, 'uuid-lcd02-together', '2026-10-22', array( fixture_member( 801, 'Together A', 801 ), fixture_member( 802, 'Together B', 802 ) ) );
$out_together = pfoa_present_adoption_events( fixture_gallery( array( 801, 802 ) ) );
fixture_require( 1 === substr_count( $out_together, 'pfoa-adoption-event-together-badge' ), 'LC-D02 gallery', 'stored adopted_together + later bonded renders exactly 1 green badge' );
fixture_require( false === strpos( $out_together, 'pfoa-adoption-event-bonded-badge' ), 'LC-D02 gallery', 'stored adopted_together + later bonded renders 0 red badges' );

fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during LC-D02 rendering' );

fwrite( STDOUT, "PASS: LC-D02 historical bonded badge ({$pass_count} predicate checks + 2 gallery paths); no writes\n" );
