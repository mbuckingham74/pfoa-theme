<?php
/**
 * Focused fixture for the inactive theme-owned managed-card placement validator.
 *
 * Requires pfoa-theme/inc/theme-managed-card-inventory.php,
 * pfoa-theme/inc/theme-managed-card-ownership-classifier.php and
 * pfoa-theme/inc/theme-managed-card-placement-validator.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. Content is built by the helpers
 * below, mirroring the baseline static card markup; trusted proofs are
 * computed from exact byte spans with substr/md5, the classifier output is
 * taken as-is, and placements are built from owned spans only.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-inventory.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-ownership-classifier.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-placement-validator.php';

function pv_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function pv_assert_success( $result, $label ) {
	pv_assert( is_array( $result ), $label . ': result is array' );
	pv_assert( array( 'ok', 'source_page_id', 'placements', 'count', 'manual_count' ) === array_keys( $result ), $label . ': keys exact' );
	pv_assert( true === $result['ok'], $label . ': ok true' );
	pv_assert( 20323 === $result['source_page_id'], $label . ': source' );
	pv_assert( is_array( $result['placements'] ), $label . ': placements list' );
	pv_assert( is_int( $result['count'] ) && count( $result['placements'] ) === $result['count'], $label . ': count matches' );
	pv_assert( is_int( $result['manual_count'] ), $label . ': manual count int' );
	$position = 0;
	foreach ( $result['placements'] as $list_key => $entry ) {
		pv_assert( $list_key === $position, $label . ': placements sequential' );
		$position++;
		pv_assert( is_array( $entry ), $label . ': entry array' );
		pv_assert( array( 'profile_id', 'slot', 'start', 'end', 'order', 'kind' ) === array_keys( $entry ), $label . ': entry keys exact' );
		pv_assert( is_string( $entry['profile_id'] ), $label . ': profile string' );
		pv_assert( is_string( $entry['slot'] ), $label . ': slot string' );
		pv_assert( is_int( $entry['start'] ) && is_int( $entry['end'] ), $label . ': offsets int' );
		pv_assert( $position - 1 === $entry['order'], $label . ': order stored' );
		pv_assert( 'id-owned' === $entry['kind'] || 'legacy-proven' === $entry['kind'], $label . ': kind known' );
	}
}

function pv_assert_failure( $result, $code, $label ) {
	pv_assert( is_array( $result ), $label . ': result is array' );
	pv_assert( array( 'ok', 'error' ) === array_keys( $result ), $label . ': keys exact ok+error' );
	pv_assert( false === $result['ok'], $label . ': ok false' );
	pv_assert( isset( $result['error'] ) && is_array( $result['error'] ), $label . ': error block' );
	pv_assert( $code === $result['error']['code'], $label . ': code is ' . $code . ' got ' . ( isset( $result['error']['code'] ) ? $result['error']['code'] : '?' ) );
	pv_assert( isset( $result['error']['message'] ) && is_string( $result['error']['message'] ) && '' !== $result['error']['message'], $label . ': message present' );
	pv_assert( ! array_key_exists( 'placements', $result ), $label . ': no partial placements' );
}

function pv_id_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function pv_legacy_card( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function pv_manual_card() {
	return '<article class="note"><p>Editorial note</p></article>';
}

function pv_wrap( $inner ) {
	return '<p>Intro</p><div class="pfoa-cat-cards">' . $inner . '</div><p>Outro</p>';
}

function pv_proof( $inv_card, $profile_id, $slot, $slug = null ) {
	$proof = array(
		'start'       => $inv_card['start'],
		'end'         => $inv_card['end'],
		'profile_id'  => (string) $profile_id,
		'slot'        => (string) $slot,
		'slice'       => $inv_card['slice'],
		'fingerprint' => md5( $inv_card['slice'] ),
	);
	if ( null !== $slug ) {
		$proof['slug'] = (string) $slug;
	}
	return $proof;
}

function pv_trusted( $content, $proofs, $label ) {
	$inv = pfoa_theme_inventory_managed_cards( 20323, $content );
	pv_assert( true === $inv['ok'], $label . ': inventory ok' );
	$classification = pfoa_theme_classify_managed_card_ownership( 20323, $inv, $proofs, $content );
	pv_assert( true === $classification['ok'], $label . ': classifier ok' );
	return $classification;
}

function pv_placement( $owned_entry, $slug = null ) {
	$record = array(
		'profile_id' => $owned_entry['profile_id'],
		'slot'       => $owned_entry['slot'],
		'start'      => $owned_entry['start'],
		'end'        => $owned_entry['end'],
	);
	if ( null !== $slug ) {
		$record['slug'] = (string) $slug;
	}
	return $record;
}

function pv_validate( $source, $classification, $placements, $content ) {
	return pfoa_theme_validate_managed_card_placement( $source, $classification, $placements, $content );
}

pv_assert( function_exists( 'pfoa_theme_validate_managed_card_placement' ), 'validator function exists' );
pv_assert( function_exists( 'pfoa_theme_classify_managed_card_ownership' ), 'classifier function exists' );
pv_assert( function_exists( 'pfoa_theme_inventory_managed_cards' ), 'inventory function exists' );

// (1) Valid single id-owned passes with exact keys/order/count.
$card_a  = pv_id_card( 101, 'fluffy', 'Fluffy' );
$content = pv_wrap( $card_a );
$inv     = pfoa_theme_inventory_managed_cards( 20323, $content );
pv_assert( true === $inv['ok'], '1: inventory ok' );
$trusted    = pv_trusted( $content, array( pv_proof( $inv['cards'][0], 101, 'slot-a' ) ), '1' );
$placements = array( pv_placement( $trusted['owned'][0] ) );
$result     = pv_validate( 20323, $trusted, $placements, $content );
pv_assert_success( $result, '1-valid-single' );
pv_assert( 1 === $result['count'], '1: count' );
pv_assert( 0 === $result['manual_count'], '1: manual count' );
pv_assert( '101' === $result['placements'][0]['profile_id'], '1: profile' );
pv_assert( 'slot-a' === $result['placements'][0]['slot'], '1: slot' );
pv_assert( 0 === $result['placements'][0]['order'], '1: order' );
pv_assert( 'id-owned' === $result['placements'][0]['kind'], '1: kind' );
pv_assert( $trusted['owned'][0]['start'] === $result['placements'][0]['start'], '1: start byte exact' );
pv_assert( $trusted['owned'][0]['end'] === $result['placements'][0]['end'], '1: end byte exact' );
pv_assert( false !== strpos( $content, substr( $content, $result['placements'][0]['start'], $result['placements'][0]['end'] - $result['placements'][0]['start'] ) ), '1: anchor inside content' );

// Digit-string source is accepted.
pv_assert_success( pv_validate( '20323', $trusted, $placements, $content ), '1-string-source' );

// (2) Multiple members: stored order passes, shuffled fails ordering_mismatch.
$card_b   = pv_id_card( 102, 'mittens', 'Mittens' );
$content2 = pv_wrap( $card_a . $card_b );
$inv2     = pfoa_theme_inventory_managed_cards( 20323, $content2 );
pv_assert( true === $inv2['ok'], '2: inventory ok' );
$trusted2 = pv_trusted( $content2, array( pv_proof( $inv2['cards'][0], 101, 'slot-a' ), pv_proof( $inv2['cards'][1], 102, 'slot-b' ) ), '2' );
$ordered  = array( pv_placement( $trusted2['owned'][0] ), pv_placement( $trusted2['owned'][1] ) );
$result2  = pv_validate( 20323, $trusted2, $ordered, $content2 );
pv_assert_success( $result2, '2-ordered' );
pv_assert( 2 === $result2['count'], '2: count' );
pv_assert( 0 === $result2['placements'][0]['order'] && 1 === $result2['placements'][1]['order'], '2: stored order' );
pv_assert( '101' === $result2['placements'][0]['profile_id'] && '102' === $result2['placements'][1]['profile_id'], '2: sequence' );
$shuffled = array( $ordered[1], $ordered[0] );
pv_assert_failure( pv_validate( 20323, $trusted2, $shuffled, $content2 ), 'ordering_mismatch', '2-shuffled' );

// (3) Legacy-proven: correct slug passes, missing/wrong slug fails unproven_legacy.
$card_l   = pv_legacy_card( 'shadow', 'Shadow' );
$content3 = pv_wrap( $card_l );
$inv3     = pfoa_theme_inventory_managed_cards( 20323, $content3 );
pv_assert( true === $inv3['ok'], '3: inventory ok' );
$trusted3 = pv_trusted( $content3, array( pv_proof( $inv3['cards'][0], 77, 'slot-legacy', 'shadow' ) ), '3' );
pv_assert( 'legacy-proven' === $trusted3['owned'][0]['kind'], '3: trusted kind' );
pv_assert_success( pv_validate( 20323, $trusted3, array( pv_placement( $trusted3['owned'][0], 'shadow' ) ), $content3 ), '3-legacy-slug' );
pv_assert_failure( pv_validate( 20323, $trusted3, array( pv_placement( $trusted3['owned'][0] ) ), $content3 ), 'unproven_legacy', '3-legacy-missing-slug' );
pv_assert_failure( pv_validate( 20323, $trusted3, array( pv_placement( $trusted3['owned'][0], 'fluffy' ) ), $content3 ), 'unproven_legacy', '3-legacy-wrong-slug' );

// Id-owned must not carry a slug.
pv_assert_failure( pv_validate( 20323, $trusted, array( pv_placement( $trusted['owned'][0], 'fluffy' ) ), $content ), 'conflicting_claims', '3-id-with-slug' );
// Empty slug on id-owned is the same as none.
pv_assert_success( pv_validate( 20323, $trusted, array( array_merge( pv_placement( $trusted['owned'][0] ), array( 'slug' => '' ) ) ), $content ), '3-id-empty-slug' );

// (4) Manual preserved: manual_count reported, claiming manual fails.
$content4 = pv_wrap( $card_a . pv_manual_card() );
$inv4     = pfoa_theme_inventory_managed_cards( 20323, $content4 );
pv_assert( true === $inv4['ok'], '4: inventory ok' );
$trusted4 = pv_trusted( $content4, array( pv_proof( $inv4['cards'][0], 101, 'slot-a' ) ), '4' );
pv_assert( 1 === $trusted4['count_manual'], '4: trusted manual' );
$result4 = pv_validate( 20323, $trusted4, array( pv_placement( $trusted4['owned'][0] ) ), $content4 );
pv_assert_success( $result4, '4-manual-kept' );
pv_assert( 1 === $result4['manual_count'], '4: manual count reported' );
$manual_span   = $trusted4['manual'][0];
$manual_claim  = array(
	'profile_id' => '999',
	'slot'       => 'slot-x',
	'start'      => $manual_span['start'],
	'end'        => $manual_span['end'],
);
pv_assert_failure( pv_validate( 20323, $trusted4, array( pv_placement( $trusted4['owned'][0] ), $manual_claim ), $content4 ), 'conflicting_claims', '4-claim-manual' );

// (5) Duplicates fail duplicate_claim.
$dup_identity = array( pv_placement( $trusted2['owned'][0] ), pv_placement( $trusted2['owned'][0] ) );
pv_assert_failure( pv_validate( 20323, $trusted2, $dup_identity, $content2 ), 'duplicate_claim', '5-dup-identity' );
$dup_anchor = array(
	pv_placement( $trusted2['owned'][0] ),
	array(
		'profile_id' => '999',
		'slot'       => 'slot-z',
		'start'      => $trusted2['owned'][0]['start'],
		'end'        => $trusted2['owned'][0]['end'],
	),
);
pv_assert_failure( pv_validate( 20323, $trusted2, $dup_anchor, $content2 ), 'duplicate_claim', '5-dup-anchor' );

// (6) Missing, wrong identity, extra placements.
$missing = array( pv_placement( $trusted2['owned'][0] ) );
pv_assert_failure( pv_validate( 20323, $trusted2, $missing, $content2 ), 'missing_placement', '6-missing' );
$wrong_identity = array(
	array(
		'profile_id' => '999',
		'slot'       => $trusted2['owned'][0]['slot'],
		'start'      => $trusted2['owned'][0]['start'],
		'end'        => $trusted2['owned'][0]['end'],
	),
	pv_placement( $trusted2['owned'][1] ),
);
pv_assert_failure( pv_validate( 20323, $trusted2, $wrong_identity, $content2 ), 'conflicting_claims', '6-wrong-profile' );
$outro_start = strpos( $content2, 'Outro' );
pv_assert( false !== $outro_start, '6: outro anchor' );
$extra = array(
	pv_placement( $trusted2['owned'][0] ),
	pv_placement( $trusted2['owned'][1] ),
	array(
		'profile_id' => '999',
		'slot'       => 'slot-extra',
		'start'      => $outro_start,
		'end'        => $outro_start + strlen( 'Outro' ),
	),
);
pv_assert_failure( pv_validate( 20323, $trusted2, $extra, $content2 ), 'orphan_placement', '6-extra' );

// (7) Stale anchors: shifted offsets and edited content.
$shifted = array( pv_placement( $trusted['owned'][0] ) );
$shifted[0]['start'] = $shifted[0]['start'] + 1;
pv_assert_failure( pv_validate( 20323, $trusted, $shifted, $content ), 'stale_anchor', '7-shifted-start' );
$shifted_end = array( pv_placement( $trusted['owned'][0] ) );
$shifted_end[0]['end'] = $shifted_end[0]['end'] - 1;
pv_assert_failure( pv_validate( 20323, $trusted, $shifted_end, $content ), 'stale_anchor', '7-shifted-end' );
$edited = str_replace( 'Fluffy', 'Fluffyy', $content );
pv_assert_failure( pv_validate( 20323, $trusted, $placements, $edited ), 'stale_anchor', '7-edited-content' );

// (8) Invalid source, classification, placements and records.
pv_assert_failure( pv_validate( 99999, $trusted, $placements, $content ), 'unsupported_source', '8-bad-source' );
pv_assert_failure( pv_validate( 'abc', $trusted, $placements, $content ), 'unsupported_source', '8-nonnumeric-source' );
pv_assert_failure( pv_validate( 20323, $trusted, $placements, array( 'nope' ) ), 'invalid_content', '8-bad-content' );
$bad_class = $trusted;
$bad_class['ok'] = false;
pv_assert_failure( pv_validate( 20323, $bad_class, $placements, $content ), 'invalid_classification', '8-class-not-ok' );
$bad_counts = $trusted;
$bad_counts['count_owned'] = 99;
pv_assert_failure( pv_validate( 20323, $bad_counts, $placements, $content ), 'invalid_classification', '8-class-count' );
pv_assert_failure( pv_validate( 20323, array( 'owned' => array() ), $placements, $content ), 'invalid_classification', '8-class-shape' );
pv_assert_failure( pv_validate( 20323, $trusted, 'not-a-list', $content ), 'invalid_placements', '8-not-list' );
pv_assert_failure( pv_validate( 20323, $trusted, array( 'a' => pv_placement( $trusted['owned'][0] ) ), $content ), 'invalid_placements', '8-assoc-list' );
$missing_key = pv_placement( $trusted['owned'][0] );
unset( $missing_key['slot'] );
pv_assert_failure( pv_validate( 20323, $trusted, array( $missing_key ), $content ), 'malformed_placement', '8-missing-key' );
$bad_type = pv_placement( $trusted['owned'][0] );
$bad_type['profile_id'] = 101;
pv_assert_failure( pv_validate( 20323, $trusted, array( $bad_type ), $content ), 'malformed_placement', '8-bad-type' );
$bad_pattern = pv_placement( $trusted['owned'][0] );
$bad_pattern['profile_id'] = '007';
pv_assert_failure( pv_validate( 20323, $trusted, array( $bad_pattern ), $content ), 'malformed_placement', '8-bad-pattern' );
$copied = pv_placement( $trusted['owned'][0] );
$copied['html'] = '<p>copied</p>';
pv_assert_failure( pv_validate( 20323, $trusted, array( $copied ), $content ), 'malformed_placement', '8-copied-html' );
$copied_slice = pv_placement( $trusted['owned'][0] );
$copied_slice['slice'] = $trusted['owned'][0]['slice'];
pv_assert_failure( pv_validate( 20323, $trusted, array( $copied_slice ), $content ), 'malformed_placement', '8-copied-slice' );
$bad_offsets = pv_placement( $trusted['owned'][0] );
$bad_offsets['end'] = $bad_offsets['start'];
pv_assert_failure( pv_validate( 20323, $trusted, array( $bad_offsets ), $content ), 'malformed_placement', '8-bad-offsets' );
$bad_slug = pv_placement( $trusted['owned'][0] );
$bad_slug['slug'] = 'Fluffy!';
pv_assert_failure( pv_validate( 20323, $trusted, array( $bad_slug ), $content ), 'malformed_placement', '8-bad-slug' );

// (9) Fail-closed shape is asserted for every failure above via pv_assert_failure
// (exact ok+error keys, ok false, code match, non-empty message, no placements key).

// (r) No hooks/reads/writes: static file-content bans on the validator file.
$validator_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-placement-validator.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'WP_Query', 'get_posts', 'get_option', 'update_option', 'wp_insert', '$wpdb', 'file_put_contents', 'file_get_contents', 'unlink', 'get_post' ) as $needle ) {
	pv_assert( false === strpos( $validator_source, $needle ), 'validator file has no ' . $needle );
}

// (s) The validator file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
pv_assert( false === strpos( $functions_source, 'theme-managed-card-placement-validator' ), 'functions.php does not require validator file' );

fwrite( STDOUT, "PASS: theme managed card placement validator fixture (valid single id-owned, multiple stored order, shuffled ordering mismatch, legacy slug proven/missing/wrong, id slug conflict, manual preserved and claimed, duplicate identity/anchor, missing/wrong/extra placements, stale shifted anchors and edited content, invalid source/classification/placements/malformed records, fail-closed shapes, no hooks/reads/writes)\n" );
