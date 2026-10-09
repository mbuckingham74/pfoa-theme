<?php
/**
 * Focused fixture for the inactive theme-owned managed-card inventory.
 *
 * Requires pfoa-theme/inc/theme-managed-card-inventory.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. The inventory input is a plain
 * source page id plus a raw stored content string built by the helpers
 * below, mirroring the baseline static card markup.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-inventory.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_assert_failure( $result, $code, $label ) {
	fixture_assert( is_array( $result ), $label . ': result is array' );
	fixture_assert( array_key_exists( 'ok', $result ) && false === $result['ok'], $label . ': ok is false' );
	fixture_assert( isset( $result['error'] ) && is_array( $result['error'] ), $label . ': error block present' );
	fixture_assert( $code === $result['error']['code'], $label . ': code is ' . $code );
}

function fixture_id_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function fixture_legacy_card( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function fixture_manual_card() {
	return '<article class="note"><p>Editorial note</p></article>';
}

function fixture_wrap_cards( $inner ) {
	return '<p>Intro</p><div class="pfoa-cat-cards">' . $inner . '</div><p>Outro</p>';
}

// (a) One ID-marked card: offsets/slice exact, signals, kind, order 0.
$single  = fixture_wrap_cards( fixture_id_card( 101, 'fluffy', 'Fluffy' ) );
$result  = pfoa_theme_inventory_managed_cards( 20323, $single );
fixture_assert( true === $result['ok'], 'a-single: ok' );
fixture_assert( 20323 === $result['source_page_id'], 'a-single: source id' );
fixture_assert( 1 === $result['count'], 'a-single: count' );
fixture_assert( 1 === count( $result['cards'] ), 'a-single: cards length' );
$card = $result['cards'][0];
fixture_assert( 0 === $card['index'], 'a-single: index 0' );
fixture_assert( '101' === $card['profile_id'], 'a-single: profile id' );
fixture_assert( true === $card['has_profile_id'], 'a-single: has profile id' );
fixture_assert( array( 'fluffy', 'fluffy' ) === $card['dialog_slugs'], 'a-single: dialog slugs in order' );
fixture_assert( true === $card['has_dialog'], 'a-single: has dialog' );
fixture_assert( array( 'pfoa-cat-card' ) === $card['classes'], 'a-single: classes' );
fixture_assert( 'id-marked' === $card['kind'], 'a-single: kind' );
fixture_assert( strpos( $single, '<article' ) === $card['start'], 'a-single: start byte offset' );
fixture_assert( substr( $single, $card['start'], $card['end'] - $card['start'] ) === $card['slice'], 'a-single: slice exact' );
fixture_assert( $card['end'] - $card['start'] === strlen( $card['slice'] ), 'a-single: byte length' );
fixture_assert( strpos( $single, '<div' ) === $result['container']['start'], 'a-single: container start' );
fixture_assert( '<div' === substr( $single, $result['container']['start'], 4 ), 'a-single: container opens with div' );
fixture_assert( '</div>' === substr( $single, $result['container']['end'] - 6, 6 ), 'a-single: container closes with div' );

// Numeric-string source id is accepted for the listed source.
$result_str = pfoa_theme_inventory_managed_cards( '20323', $single );
fixture_assert( true === $result_str['ok'], 'a-string-source: ok' );
fixture_assert( 1 === $result_str['count'], 'a-string-source: count' );

// (b) Multiple cards in stored order.
$multi   = fixture_wrap_cards( fixture_id_card( 101, 'fluffy', 'Fluffy' ) . fixture_id_card( 102, 'mittens', 'Mittens' ) . fixture_id_card( 103, 'shadow', 'Shadow' ) );
$result  = pfoa_theme_inventory_managed_cards( 20323, $multi );
fixture_assert( true === $result['ok'], 'b-multi: ok' );
fixture_assert( 3 === $result['count'], 'b-multi: count' );
fixture_assert( '101' === $result['cards'][0]['profile_id'], 'b-multi: first id' );
fixture_assert( '102' === $result['cards'][1]['profile_id'], 'b-multi: second id' );
fixture_assert( '103' === $result['cards'][2]['profile_id'], 'b-multi: third id' );
fixture_assert( $result['cards'][0]['start'] < $result['cards'][1]['start'], 'b-multi: order 0<1' );
fixture_assert( $result['cards'][1]['start'] < $result['cards'][2]['start'], 'b-multi: order 1<2' );
fixture_assert( $result['cards'][0]['end'] <= $result['cards'][1]['start'], 'b-multi: spans do not overlap 0/1' );
fixture_assert( $result['cards'][1]['end'] <= $result['cards'][2]['start'], 'b-multi: spans do not overlap 1/2' );
foreach ( $result['cards'] as $check ) {
	fixture_assert( substr( $multi, $check['start'], $check['end'] - $check['start'] ) === $check['slice'], 'b-multi: slice exact' );
	fixture_assert( 'id-marked' === $check['kind'], 'b-multi: kind id-marked' );
}

// (c) Legacy dialog-marked card: no id marker, media+title anchors share one slug.
$legacy  = fixture_wrap_cards( fixture_legacy_card( 'mittens', 'Mittens' ) );
$result  = pfoa_theme_inventory_managed_cards( 20323, $legacy );
fixture_assert( true === $result['ok'], 'c-legacy: ok' );
fixture_assert( 1 === $result['count'], 'c-legacy: count' );
$card = $result['cards'][0];
fixture_assert( null === $card['profile_id'], 'c-legacy: no profile id' );
fixture_assert( false === $card['has_profile_id'], 'c-legacy: has_profile_id false' );
fixture_assert( true === in_array( 'mittens', $card['dialog_slugs'], true ), 'c-legacy: dialog slugs contain slug' );
fixture_assert( true === $card['has_dialog'], 'c-legacy: has dialog' );
fixture_assert( 'legacy-dialog' === $card['kind'], 'c-legacy: kind' );
fixture_assert( substr( $legacy, $card['start'], $card['end'] - $card['start'] ) === $card['slice'], 'c-legacy: slice exact' );

// (d) Manual/unmarked card: plain article, no markers.
$manual  = fixture_wrap_cards( fixture_manual_card() );
$result  = pfoa_theme_inventory_managed_cards( 20323, $manual );
fixture_assert( true === $result['ok'], 'd-manual: ok' );
fixture_assert( 1 === $result['count'], 'd-manual: count' );
$card = $result['cards'][0];
fixture_assert( null === $card['profile_id'], 'd-manual: null profile id' );
fixture_assert( array() === $card['dialog_slugs'], 'd-manual: empty dialog slugs' );
fixture_assert( false === $card['has_profile_id'], 'd-manual: has_profile_id false' );
fixture_assert( false === $card['has_dialog'], 'd-manual: has_dialog false' );
fixture_assert( 'manual' === $card['kind'], 'd-manual: kind' );

// (e) Mixed manual + generated candidates: order preserved, kinds correct.
$mixed   = fixture_wrap_cards( fixture_id_card( 101, 'fluffy', 'Fluffy' ) . fixture_manual_card() . fixture_legacy_card( 'mittens', 'Mittens' ) );
$result  = pfoa_theme_inventory_managed_cards( 20323, $mixed );
fixture_assert( true === $result['ok'], 'e-mixed: ok' );
fixture_assert( 3 === $result['count'], 'e-mixed: count' );
fixture_assert( 'id-marked' === $result['cards'][0]['kind'], 'e-mixed: first kind' );
fixture_assert( 'manual' === $result['cards'][1]['kind'], 'e-mixed: second kind' );
fixture_assert( 'legacy-dialog' === $result['cards'][2]['kind'], 'e-mixed: third kind' );
fixture_assert( $result['cards'][0]['start'] < $result['cards'][1]['start'], 'e-mixed: order 0<1' );
fixture_assert( $result['cards'][1]['start'] < $result['cards'][2]['start'], 'e-mixed: order 1<2' );

// (f) Exact offset/byte preservation with non-ASCII content.
$unicode = fixture_wrap_cards( fixture_id_card( 104, 'nono', 'Müller — café ☃ Ñoño' ) );
$result  = pfoa_theme_inventory_managed_cards( 20323, $unicode );
fixture_assert( true === $result['ok'], 'f-unicode: ok' );
fixture_assert( 1 === $result['count'], 'f-unicode: count' );
$card = $result['cards'][0];
fixture_assert( substr( $unicode, $card['start'], $card['end'] - $card['start'] ) === $card['slice'], 'f-unicode: slice exact' );
fixture_assert( $card['end'] - $card['start'] === strlen( $card['slice'] ), 'f-unicode: byte length via strlen' );
fixture_assert( strpos( $unicode, $card['slice'] ) === $card['start'], 'f-unicode: byte position via strpos' );
fixture_assert( false !== strpos( $card['slice'], 'Müller — café ☃ Ñoño' ), 'f-unicode: bytes preserved' );
$container_slice = substr( $unicode, $result['container']['start'], $result['container']['end'] - $result['container']['start'] );
fixture_assert( 0 === strpos( $container_slice, '<div' ), 'f-unicode: container slice opens with div' );
fixture_assert( strlen( $container_slice ) === $result['container']['end'] - $result['container']['start'], 'f-unicode: container byte length' );
foreach ( $result['cards'] as $check ) {
	fixture_assert( $check['start'] >= $result['container']['start'], 'f-unicode: card inside container start' );
	fixture_assert( $check['end'] <= $result['container']['end'], 'f-unicode: card inside container end' );
}

// (g) Unsupported source id fails.
$result = pfoa_theme_inventory_managed_cards( 99999, $single );
fixture_assert_failure( $result, 'unsupported_source', 'g-unsupported-int' );
$result = pfoa_theme_inventory_managed_cards( '99999', $single );
fixture_assert_failure( $result, 'unsupported_source', 'g-unsupported-string' );

// (g2) Non-string content fails.
$result = pfoa_theme_inventory_managed_cards( 20323, null );
fixture_assert_failure( $result, 'invalid_content', 'g2-null' );
$result = pfoa_theme_inventory_managed_cards( 20323, array( 'x' ) );
fixture_assert_failure( $result, 'invalid_content', 'g2-array' );

// (h) Malformed / nested / overlapping inputs fail the whole inventory.
$nested = fixture_wrap_cards( '<article class="a"><article class="b"></article></article>' );
fixture_assert_failure( pfoa_theme_inventory_managed_cards( 20323, $nested ), 'nested_article', 'h-nested' );

$unclosed = fixture_wrap_cards( '<article class="a"><p>x</p>' );
fixture_assert_failure( pfoa_theme_inventory_managed_cards( 20323, $unclosed ), 'malformed_article', 'h-unclosed' );

$crossing = '<div class="pfoa-cat-cards"><article class="pfoa-cat-card"><p>x</p></div><p>y</p></article>';
$result   = pfoa_theme_inventory_managed_cards( 20323, $crossing );
fixture_assert( false === $result['ok'], 'h-crossing: ok is false' );
fixture_assert( in_array( $result['error']['code'], array( 'malformed_article', 'overlapping_cards', 'ambiguous_structure' ), true ), 'h-crossing: machine code' );

$truncated = '<div class="pfoa-cat-cards"><article class="pfoa-cat-card"><p>x</p></article';
$result    = pfoa_theme_inventory_managed_cards( 20323, $truncated );
fixture_assert( false === $result['ok'], 'h-truncated: ok is false' );

$open_container = '<div class="pfoa-cat-cards"><article class="x"><p>t</p></article>';
fixture_assert_failure( pfoa_theme_inventory_managed_cards( 20323, $open_container ), 'malformed_container', 'h-open-container' );

fixture_assert_failure( pfoa_theme_inventory_managed_cards( 20323, '<p>No wrapper here</p>' ), 'no_container', 'h-no-container' );

$doubled = fixture_wrap_cards( fixture_id_card( 101, 'fluffy', 'Fluffy' ) ) . fixture_wrap_cards( fixture_id_card( 102, 'mittens', 'Mittens' ) );
fixture_assert_failure( pfoa_theme_inventory_managed_cards( 20323, $doubled ), 'ambiguous_container', 'h-ambiguous-container' );

// (i) No hooks/queries/writes: static file-content bans on the inventory file.
$inventory_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-inventory.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_setting', 'register_post', 'register_block', 'get_posts', 'WP_Query', 'get_option', 'update_option', 'wp_insert', 'wp_update', '$wpdb', 'wpdb', 'file_put_contents', 'file_get_contents', 'unlink' ) as $needle ) {
	fixture_assert( false === strpos( $inventory_source, $needle ), 'inventory file has no ' . $needle );
}

// (j) No ownership assertion: inventory reports candidates only.
foreach ( array( 'is_owned', 'assert_ownership', 'map_legacy', 'placement', 'bond' ) as $needle ) {
	fixture_assert( false === stripos( $inventory_source, $needle ), 'inventory file has no ' . $needle );
}

// (k) The inventory file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-managed-card-inventory' ), 'functions.php does not require inventory file' );

fwrite( STDOUT, "PASS: theme managed card inventory fixture (single id-marked, stored order, legacy dialog, manual, mixed, byte offsets + unicode, unsupported source, invalid content, nested/unclosed/crossing/truncated/container errors, no hooks/queries/writes, candidate signals only)\n" );
