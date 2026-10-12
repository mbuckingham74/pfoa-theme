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
		pv_assert( array( 'profile_id', 'slot', 'start', 'end', 'slug', 'order', 'kind' ) === array_keys( $entry ), $label . ': entry keys exact' );
		pv_assert( is_string( $entry['profile_id'] ), $label . ': profile string' );
		pv_assert( is_string( $entry['slot'] ), $label . ': slot string' );
		pv_assert( is_int( $entry['start'] ) && is_int( $entry['end'] ), $label . ': offsets int' );
		pv_assert( $position - 1 === $entry['order'], $label . ': order stored' );
		pv_assert( 'id-owned' === $entry['kind'] || 'legacy-proven' === $entry['kind'], $label . ': kind known' );
		if ( 'legacy-proven' === $entry['kind'] ) {
			pv_assert( is_string( $entry['slug'] ) && 1 === preg_match( '/\A[a-z0-9-]+\z/', $entry['slug'] ), $label . ': legacy slug preserved' );
		} else {
			pv_assert( null === $entry['slug'], $label . ': id-owned slug null' );
		}
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

function pv_legacy_card_unquoted( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog=' . $slug . '><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog=' . $slug . '>' . $title . '</a></h3>'
		. '</article>';
}

function pv_manual_card_text( $text ) {
	return '<article class="note"><p>' . $text . '</p></article>';
}

function pv_hand_owned( $content, $slice_html, $profile_id, $slot, $kind ) {
	$start = strpos( $content, $slice_html );
	pv_assert( false !== $start, 'hand owned anchor found' );
	return array(
		'start'      => $start,
		'end'        => $start + strlen( $slice_html ),
		'slice'      => $slice_html,
		'profile_id' => (string) $profile_id,
		'slot'       => (string) $slot,
		'kind'       => (string) $kind,
	);
}

function pv_hand_classification( $owned_entries, $manual_entries ) {
	return array(
		'ok'             => true,
		'source_page_id' => 20323,
		'owned'          => $owned_entries,
		'manual'         => $manual_entries,
		'count_owned'    => count( $owned_entries ),
		'count_manual'   => count( $manual_entries ),
	);
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
pv_assert( array_key_exists( 'slug', $result['placements'][0] ) && null === $result['placements'][0]['slug'], '1: id-owned accepted slug null' );

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
$result3 = pv_validate( 20323, $trusted3, array( pv_placement( $trusted3['owned'][0], 'shadow' ) ), $content3 );
pv_assert_success( $result3, '3-legacy-slug' );
pv_assert( 'shadow' === $result3['placements'][0]['slug'], '3: legacy accepted slug is historical dialog slug' );
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

// (10a) Two different profile IDs sharing one legacy slug fail conflicting_claims.
$card_l1      = pv_legacy_card( 'shadow', 'Shadow One' );
$card_l2      = pv_legacy_card( 'shadow', 'Shadow Two' );
$content_ab   = pv_wrap( $card_l1 . $card_l2 );
$inv_ab       = pfoa_theme_inventory_managed_cards( 20323, $content_ab );
pv_assert( true === $inv_ab['ok'], '10a: inventory ok' );
$trusted_ab   = pv_trusted( $content_ab, array( pv_proof( $inv_ab['cards'][0], 77, 'slot-a', 'shadow' ), pv_proof( $inv_ab['cards'][1], 88, 'slot-b', 'shadow' ) ), '10a' );
$placements_ab = array( pv_placement( $trusted_ab['owned'][0], 'shadow' ), pv_placement( $trusted_ab['owned'][1], 'shadow' ) );
pv_assert_failure( pv_validate( 20323, $trusted_ab, $placements_ab, $content_ab ), 'conflicting_claims', '10a-shared-slug-diff-profile' );

// (10b) Same profile + same slug in distinct valid slots passes.
$trusted_ab2 = pv_trusted( $content_ab, array( pv_proof( $inv_ab['cards'][0], 77, 'slot-a', 'shadow' ), pv_proof( $inv_ab['cards'][1], 77, 'slot-b', 'shadow' ) ), '10b' );
$result_ab2  = pv_validate( 20323, $trusted_ab2, array( pv_placement( $trusted_ab2['owned'][0], 'shadow' ), pv_placement( $trusted_ab2['owned'][1], 'shadow' ) ), $content_ab );
pv_assert_success( $result_ab2, '10b-same-profile-shared-slug' );
pv_assert( 'shadow' === $result_ab2['placements'][0]['slug'] && 'shadow' === $result_ab2['placements'][1]['slug'], '10b: both historical slugs preserved' );

// (10c) Unquoted valid legacy dialog attributes pass.
$card_uq    = pv_legacy_card_unquoted( 'shadow', 'Shadow' );
$content_uq = pv_wrap( $card_uq );
$inv_uq     = pfoa_theme_inventory_managed_cards( 20323, $content_uq );
pv_assert( true === $inv_uq['ok'], '10c: inventory ok' );
$trusted_uq = pv_trusted( $content_uq, array( pv_proof( $inv_uq['cards'][0], 77, 'slot-legacy', 'shadow' ) ), '10c' );
pv_assert( 'legacy-proven' === $trusted_uq['owned'][0]['kind'], '10c: trusted kind' );
$result_uq = pv_validate( 20323, $trusted_uq, array( pv_placement( $trusted_uq['owned'][0], 'shadow' ) ), $content_uq );
pv_assert_success( $result_uq, '10c-unquoted-legacy' );
pv_assert( 'shadow' === $result_uq['placements'][0]['slug'], '10c: unquoted slug preserved' );

// (10d) Malformed / prefixed / conflicting dialog attributes fail unproven_legacy.
$prefixed_card = '<article class="pfoa-cat-card">'
	. '<a class="pfoa-cat-card-media" href="https://example.test/shadow/" x-data-pfoa-cat-dialog="shadow"><img src="https://example.test/shadow.jpg" alt=""></a>'
	. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/shadow/" x-data-pfoa-cat-dialog="shadow">Shadow</a></h3>'
	. '</article>';
$content_fx = pv_wrap( $prefixed_card );
$class_fx   = pv_hand_classification( array( pv_hand_owned( $content_fx, $prefixed_card, 77, 'slot-legacy', 'legacy-proven' ) ), array() );
pv_assert_failure( pv_validate( 20323, $class_fx, array( pv_placement( $class_fx['owned'][0], 'shadow' ) ), $content_fx ), 'unproven_legacy', '10d-prefixed-attr' );
$suffixed_card = '<article class="pfoa-cat-card">'
	. '<a class="pfoa-cat-card-media" href="https://example.test/shadow/" data-pfoa-cat-dialog-extra="shadow"><img src="https://example.test/shadow.jpg" alt=""></a>'
	. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/shadow/" data-pfoa-cat-dialog-extra="shadow">Shadow</a></h3>'
	. '</article>';
$content_sx = pv_wrap( $suffixed_card );
$class_sx   = pv_hand_classification( array( pv_hand_owned( $content_sx, $suffixed_card, 77, 'slot-legacy', 'legacy-proven' ) ), array() );
pv_assert_failure( pv_validate( 20323, $class_sx, array( pv_placement( $class_sx['owned'][0], 'shadow' ) ), $content_sx ), 'unproven_legacy', '10d-suffixed-attr' );
$conflict_card = '<article class="pfoa-cat-card">'
	. '<a class="pfoa-cat-card-media" href="https://example.test/shadow/" data-pfoa-cat-dialog="shadow"><img src="https://example.test/shadow.jpg" alt=""></a>'
	. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/mittens/" data-pfoa-cat-dialog="mittens">Mittens</a></h3>'
	. '</article>';
$content_cx = pv_wrap( $conflict_card );
$class_cx   = pv_hand_classification( array( pv_hand_owned( $content_cx, $conflict_card, 77, 'slot-legacy', 'legacy-proven' ) ), array() );
pv_assert_failure( pv_validate( 20323, $class_cx, array( pv_placement( $class_cx['owned'][0], 'shadow' ) ), $content_cx ), 'unproven_legacy', '10d-conflicting-dialog' );
$badslug_card = '<article class="pfoa-cat-card">'
	. '<a class="pfoa-cat-card-media" href="https://example.test/shadow/" data-pfoa-cat-dialog="Shadow!"><img src="https://example.test/shadow.jpg" alt=""></a>'
	. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/shadow/" data-pfoa-cat-dialog="Shadow!">Shadow</a></h3>'
	. '</article>';
$content_bx = pv_wrap( $badslug_card );
$class_bx   = pv_hand_classification( array( pv_hand_owned( $content_bx, $badslug_card, 77, 'slot-legacy', 'legacy-proven' ) ), array() );
pv_assert_failure( pv_validate( 20323, $class_bx, array( pv_placement( $class_bx['owned'][0], 'shadow' ) ), $content_bx ), 'unproven_legacy', '10d-bad-slug-pattern' );
$single_card = '<article class="pfoa-cat-card">'
	. '<a class="pfoa-cat-card-media" href="https://example.test/shadow/" data-pfoa-cat-dialog="shadow"><img src="https://example.test/shadow.jpg" alt=""></a>'
	. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/shadow/">Shadow</a></h3>'
	. '</article>';
$content_gx = pv_wrap( $single_card );
$class_gx   = pv_hand_classification( array( pv_hand_owned( $content_gx, $single_card, 77, 'slot-legacy', 'legacy-proven' ) ), array() );
pv_assert_failure( pv_validate( 20323, $class_gx, array( pv_placement( $class_gx['owned'][0], 'shadow' ) ), $content_gx ), 'unproven_legacy', '10d-single-dialog' );

// (10e) Duplicate manual spans fail ambiguous_ownership.
$dup_manual_class = $trusted4;
$dup_manual_class['manual'][] = $trusted4['manual'][0];
$dup_manual_class['count_manual'] = 2;
pv_assert_failure( pv_validate( 20323, $dup_manual_class, array( pv_placement( $trusted4['owned'][0] ) ), $content4 ), 'ambiguous_ownership', '10e-dup-manual' );

// (10f) Partial-overlap and nested manual spans fail ambiguous_ownership.
$base_manual  = $trusted4['manual'][0];
$partial_span = array(
	'start' => $base_manual['start'] + 2,
	'end'   => $base_manual['end'] + 5,
	'slice' => substr( $content4, $base_manual['start'] + 2, ( $base_manual['end'] + 5 ) - ( $base_manual['start'] + 2 ) ),
);
$partial_class = $trusted4;
$partial_class['manual'][] = $partial_span;
$partial_class['count_manual'] = 2;
pv_assert_failure( pv_validate( 20323, $partial_class, array( pv_placement( $trusted4['owned'][0] ) ), $content4 ), 'ambiguous_ownership', '10f-partial-manual' );
$nested_span = array(
	'start' => $base_manual['start'] + 2,
	'end'   => $base_manual['end'] - 2,
	'slice' => substr( $content4, $base_manual['start'] + 2, ( $base_manual['end'] - 2 ) - ( $base_manual['start'] + 2 ) ),
);
$nested_class = $trusted4;
$nested_class['manual'][] = $nested_span;
$nested_class['count_manual'] = 2;
pv_assert_failure( pv_validate( 20323, $nested_class, array( pv_placement( $trusted4['owned'][0] ) ), $content4 ), 'ambiguous_ownership', '10f-nested-manual' );

// (10g) Adjacent non-overlapping manual spans stay valid.
$note1     = pv_manual_card_text( 'First note' );
$note2     = pv_manual_card_text( 'Second note' );
$content_g = pv_wrap( $card_a . $note1 . $note2 );
$inv_g     = pfoa_theme_inventory_managed_cards( 20323, $content_g );
pv_assert( true === $inv_g['ok'], '10g: inventory ok' );
$trusted_g = pv_trusted( $content_g, array( pv_proof( $inv_g['cards'][0], 101, 'slot-a' ) ), '10g' );
pv_assert( 2 === $trusted_g['count_manual'], '10g: two manual spans' );
pv_assert( $trusted_g['manual'][0]['end'] === $trusted_g['manual'][1]['start'], '10g: manual spans edge-touch' );
$result_g = pv_validate( 20323, $trusted_g, array( pv_placement( $trusted_g['owned'][0] ) ), $content_g );
pv_assert_success( $result_g, '10g-adjacent-manual' );
pv_assert( 2 === $result_g['manual_count'], '10g: manual count reported' );

// (10h) Accepted legacy output carries the historical dialog slug (asserted in 3/10b/10c above).

// (10i) Accepted id-owned output carries a null slug (asserted in 1 above via pv_assert_success).

// (10j) Normalized accepted output (profile_id/slot/start/end/slug) revalidates.
$content_j = pv_wrap( $card_a . $card_l );
$inv_j     = pfoa_theme_inventory_managed_cards( 20323, $content_j );
pv_assert( true === $inv_j['ok'], '10j: inventory ok' );
$trusted_j = pv_trusted( $content_j, array( pv_proof( $inv_j['cards'][0], 101, 'slot-a' ), pv_proof( $inv_j['cards'][1], 77, 'slot-legacy', 'shadow' ) ), '10j' );
$result_j  = pv_validate( 20323, $trusted_j, array( pv_placement( $trusted_j['owned'][0] ), pv_placement( $trusted_j['owned'][1], 'shadow' ) ), $content_j );
pv_assert_success( $result_j, '10j-mixed' );
pv_assert( null === $result_j['placements'][0]['slug'] && 'shadow' === $result_j['placements'][1]['slug'], '10j: mixed slugs' );
$normalized = array();
foreach ( $result_j['placements'] as $accepted_entry ) {
	$normalized[] = array(
		'profile_id' => $accepted_entry['profile_id'],
		'slot'       => $accepted_entry['slot'],
		'start'      => $accepted_entry['start'],
		'end'        => $accepted_entry['end'],
		'slug'       => $accepted_entry['slug'],
	);
}
pv_assert_success( pv_validate( 20323, $trusted_j, $normalized, $content_j ), '10j-renormalized' );

// (r) No hooks/reads/writes: static file-content bans on the validator file.
$validator_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-placement-validator.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'WP_Query', 'get_posts', 'get_option', 'update_option', 'wp_insert', '$wpdb', 'file_put_contents', 'file_get_contents', 'unlink', 'get_post' ) as $needle ) {
	pv_assert( false === strpos( $validator_source, $needle ), 'validator file has no ' . $needle );
}

// (s) The validator file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
pv_assert( false === strpos( $functions_source, 'theme-managed-card-placement-validator' ), 'functions.php does not require validator file' );

fwrite( STDOUT, "PASS: theme managed card placement validator fixture (valid single id-owned, multiple stored order, shuffled ordering mismatch, legacy slug proven/missing/wrong, id slug conflict, manual preserved and claimed, duplicate identity/anchor, missing/wrong/extra placements, stale shifted anchors and edited content, invalid source/classification/placements/malformed records, fail-closed shapes, shared-slug conflicting claims, same-profile shared slug, unquoted legacy attrs, malformed/prefixed/conflicting dialog attrs, duplicate/partial/nested manual overlap, adjacent manual, historical slug in accepted output, null slug for id-owned, renormalized accepted output, no hooks/reads/writes)\n" );
