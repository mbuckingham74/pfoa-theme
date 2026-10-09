<?php
/**
 * Focused fixture for the inactive theme-owned managed-card ownership classifier.
 *
 * Requires pfoa-theme/inc/theme-managed-card-inventory.php and
 * pfoa-theme/inc/theme-managed-card-ownership-classifier.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. Content is built by the helpers
 * below, mirroring the baseline static card markup, and trusted proofs are
 * computed from exact byte spans with substr/md5.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-inventory.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-ownership-classifier.php';

function oc_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function oc_assert_failure( $result, $code, $label ) {
	oc_assert( is_array( $result ), $label . ': result is array' );
	oc_assert( array_key_exists( 'ok', $result ) && false === $result['ok'], $label . ': ok is false' );
	oc_assert( isset( $result['error'] ) && is_array( $result['error'] ), $label . ': error block present' );
	oc_assert( $code === $result['error']['code'], $label . ': code is ' . $code . ' got ' . ( isset( $result['error']['code'] ) ? $result['error']['code'] : '?' ) );
	oc_assert( isset( $result['error']['message'] ) && is_string( $result['error']['message'] ) && '' !== $result['error']['message'], $label . ': message present' );
}

function oc_id_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function oc_legacy_card( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function oc_manual_card() {
	return '<article class="note"><p>Editorial note</p></article>';
}

function oc_wrap_cards( $inner ) {
	return '<p>Intro</p><div class="pfoa-cat-cards">' . $inner . '</div><p>Outro</p>';
}

function oc_mixed_slug_card( $id, $slug_a, $slug_b, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug_a . '/" data-pfoa-cat-dialog="' . $slug_a . '"><img src="https://example.test/' . $slug_a . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug_b . '/" data-pfoa-cat-dialog="' . $slug_b . '">' . $title . '</a></h3>'
		. '</article>';
}

function oc_single_dialog_card( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function oc_id_no_anchors_card( $id ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '"><p>No anchors here</p></article>';
}

function oc_token_no_dialog_card() {
	return '<article class="pfoa-cat-card"><p>Token but no dialog anchors</p></article>';
}

function oc_unclosed_heading_card() {
	return '<article class="note"><h3>Unclosed heading</article>';
}

function oc_thumb_img( $slug ) {
	return '<img src="https://example.test/' . $slug . '.jpg" class="pfoa-cat-card-thumb" alt="">';
}

function oc_complete_thumb_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . oc_thumb_img( $slug ) . '</a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function oc_complete_status_card( $id, $slug, $title, $status ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . oc_thumb_img( $slug ) . '</a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '<p class="pfoa-cat-card-status">' . $status . '</p>'
		. '</article>';
}

function oc_incomplete_card( $id, $slug, $title, $status = null ) {
	$html = '<article class="pfoa-cat-card pfoa-cat-card-incomplete" data-pfoa-profile-id="' . $id . '">';
	$html .= '<div class="pfoa-cat-card-media">' . oc_thumb_img( $slug ) . '</div>';
	$html .= '<h3 class="pfoa-cat-card-title">' . $title . '</h3>';
	if ( null !== $status ) {
		$html .= '<p class="pfoa-cat-card-status">' . $status . '</p>';
	}
	$html .= '<p class="pfoa-cat-card-note">Full profile coming soon.</p>';
	return $html . '</article>';
}

function oc_incomplete_pair_card( $id, $side, $slug, $title ) {
	$pair = 'left' === $side ? ' pfoa-cat-pair-left' : ' pfoa-cat-pair-right';
	return '<article class="pfoa-cat-card pfoa-cat-card-incomplete' . $pair . '" data-pfoa-profile-id="' . $id . '">'
		. '<div class="pfoa-cat-card-media">' . oc_thumb_img( $slug ) . '</div>'
		. '<h3 class="pfoa-cat-card-title">' . $title . '</h3>'
		. '<p class="pfoa-cat-card-note">Full profile coming soon.</p>'
		. '</article>';
}

function oc_incomplete_dialog_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card pfoa-cat-card-incomplete" data-pfoa-profile-id="' . $id . '">'
		. '<div class="pfoa-cat-card-media">' . oc_thumb_img( $slug ) . '</div>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '<p class="pfoa-cat-card-note">Full profile coming soon.</p>'
		. '</article>';
}

function oc_spoofed_suffix_card() {
	return '<article class="pfoa-cat-card pfoa-cat-card-xyz" data-pfoa-profile-id="101">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">' . oc_thumb_img( 'fluffy' ) . '</a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">Fluffy</a></h3>'
		. '</article>';
}

function oc_spoofed_media_card() {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="101">'
		. '<a class="pfoa-cat-card-media-evil" href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">' . oc_thumb_img( 'fluffy' ) . '</a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">Fluffy</a></h3>'
		. '</article>';
}

function oc_spoofed_thumb_card() {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="101">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy"><img src="https://example.test/fluffy.jpg" class="pfoa-cat-card-thumb-evil" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">Fluffy</a></h3>'
		. '</article>';
}

function oc_proof( $card, $profile_id, $slot, $slug = null ) {
	$proof = array(
		'start'       => $card['start'],
		'end'         => $card['end'],
		'profile_id'  => (string) $profile_id,
		'slot'        => (string) $slot,
		'slice'       => $card['slice'],
		'fingerprint' => md5( $card['slice'] ),
	);
	if ( null !== $slug ) {
		$proof['slug'] = (string) $slug;
	}
	return $proof;
}

function oc_inventory_ok( $content, $label ) {
	$inv = pfoa_theme_inventory_managed_cards( 20323, $content );
	oc_assert( true === $inv['ok'], $label . ': inventory ok' );
	return $inv;
}

function oc_classify( $source, $inv, $proofs, $content ) {
	return pfoa_theme_classify_managed_card_ownership( $source, $inv, $proofs, $content );
}

oc_assert( function_exists( 'pfoa_theme_classify_managed_card_ownership' ), 'classifier function exists' );
oc_assert( function_exists( 'pfoa_theme_inventory_managed_cards' ), 'inventory function exists' );

// (a) Valid ID-owned single card.
$content = oc_wrap_cards( oc_id_card( 101, 'fluffy', 'Fluffy' ) );
$inv     = oc_inventory_ok( $content, 'a-id' );
$result  = oc_classify( 20323, $inv, array( oc_proof( $inv['cards'][0], 101, 'slot-a' ) ), $content );
oc_assert( true === $result['ok'], 'a-id: ok' );
oc_assert( 20323 === $result['source_page_id'], 'a-id: source' );
oc_assert( 1 === $result['count_owned'], 'a-id: count owned' );
oc_assert( 0 === $result['count_manual'], 'a-id: count manual' );
oc_assert( 1 === count( $result['owned'] ), 'a-id: owned length' );
oc_assert( array( 'start', 'end', 'slice', 'profile_id', 'slot', 'kind' ) === array_keys( $result['owned'][0] ), 'a-id: owned keys exact' );
oc_assert( '101' === $result['owned'][0]['profile_id'], 'a-id: profile' );
oc_assert( 'slot-a' === $result['owned'][0]['slot'], 'a-id: slot' );
oc_assert( 'id-owned' === $result['owned'][0]['kind'], 'a-id: kind' );
oc_assert( $inv['cards'][0]['start'] === $result['owned'][0]['start'], 'a-id: start byte exact' );
oc_assert( substr( $content, $result['owned'][0]['start'], $result['owned'][0]['end'] - $result['owned'][0]['start'] ) === $result['owned'][0]['slice'], 'a-id: slice byte exact' );

// Numeric-string source is accepted.
$result_str = oc_classify( '20323', $inv, array( oc_proof( $inv['cards'][0], 101, 'slot-a' ) ), $content );
oc_assert( true === $result_str['ok'], 'a-string-source: ok' );
oc_assert( 1 === $result_str['count_owned'], 'a-string-source: count' );

// (a2) Inventory labels are never proof: tampered kind/profile/dialog still classifies.
$tampered = $inv;
$tampered['cards'][0]['kind']         = 'manual';
$tampered['cards'][0]['profile_id']   = null;
$tampered['cards'][0]['dialog_slugs'] = array();
$tampered['cards'][0]['classes']      = array();
$result   = oc_classify( 20323, $tampered, array( oc_proof( $inv['cards'][0], 101, 'slot-a' ) ), $content );
oc_assert( true === $result['ok'], 'a2-rederive: ok' );
oc_assert( 'id-owned' === $result['owned'][0]['kind'], 'a2-rederive: kind re-derived' );
oc_assert( '101' === $result['owned'][0]['profile_id'], 'a2-rederive: profile re-derived' );

// (b) Valid proven legacy single card.
$content = oc_wrap_cards( oc_legacy_card( 'mittens', 'Mittens' ) );
$inv     = oc_inventory_ok( $content, 'b-legacy' );
$result  = oc_classify( 20323, $inv, array( oc_proof( $inv['cards'][0], 77, 'slot-legacy', 'mittens' ) ), $content );
oc_assert( true === $result['ok'], 'b-legacy: ok' );
oc_assert( 1 === $result['count_owned'], 'b-legacy: count owned' );
oc_assert( '77' === $result['owned'][0]['profile_id'], 'b-legacy: profile from proof only' );
oc_assert( 'legacy-proven' === $result['owned'][0]['kind'], 'b-legacy: kind' );

// (c) Manual untouched with empty proofs.
$content = oc_wrap_cards( oc_manual_card() );
$inv     = oc_inventory_ok( $content, 'c-manual' );
$result  = oc_classify( 20323, $inv, array(), $content );
oc_assert( true === $result['ok'], 'c-manual: ok' );
oc_assert( 0 === $result['count_owned'], 'c-manual: owned 0' );
oc_assert( 1 === $result['count_manual'], 'c-manual: manual 1' );
oc_assert( array( 'start', 'end', 'slice' ) === array_keys( $result['manual'][0] ), 'c-manual: manual keys exact' );
oc_assert( substr( $content, $result['manual'][0]['start'], $result['manual'][0]['end'] - $result['manual'][0]['start'] ) === $result['manual'][0]['slice'], 'c-manual: manual slice exact' );

// (d) Mixed managed + manual in stored order.
$content = oc_wrap_cards( oc_id_card( 101, 'fluffy', 'Fluffy' ) . oc_manual_card() . oc_legacy_card( 'mittens', 'Mittens' ) );
$inv     = oc_inventory_ok( $content, 'd-mixed' );
$proofs  = array(
	oc_proof( $inv['cards'][0], 101, 'slot-a' ),
	oc_proof( $inv['cards'][2], 102, 'slot-b', 'mittens' ),
);
$result  = oc_classify( 20323, $inv, $proofs, $content );
oc_assert( true === $result['ok'], 'd-mixed: ok' );
oc_assert( 2 === $result['count_owned'], 'd-mixed: owned 2' );
oc_assert( 1 === $result['count_manual'], 'd-mixed: manual 1' );
oc_assert( 'id-owned' === $result['owned'][0]['kind'], 'd-mixed: first owned kind' );
oc_assert( 'legacy-proven' === $result['owned'][1]['kind'], 'd-mixed: second owned kind' );
oc_assert( $result['owned'][0]['start'] < $result['manual'][0]['start'], 'd-mixed: order owned/manual' );
oc_assert( $result['manual'][0]['start'] < $result['owned'][1]['start'], 'd-mixed: order manual/owned' );

// (e) Genuine multi-card same profile with distinct slots (+ same slot reused by another profile).
$content = oc_wrap_cards( oc_id_card( 101, 'fluffy', 'Fluffy' ) . oc_id_card( 101, 'mittens', 'Mittens' ) . oc_legacy_card( 'shadow', 'Shadow' ) );
$inv     = oc_inventory_ok( $content, 'e-multi' );
$proofs  = array(
	oc_proof( $inv['cards'][0], 101, 'slot-a' ),
	oc_proof( $inv['cards'][1], 101, 'slot-b' ),
	oc_proof( $inv['cards'][2], 103, 'slot-a', 'shadow' ),
);
$result  = oc_classify( 20323, $inv, $proofs, $content );
oc_assert( true === $result['ok'], 'e-multi: ok' );
oc_assert( 3 === $result['count_owned'], 'e-multi: owned 3' );
oc_assert( '101' === $result['owned'][0]['profile_id'] && '101' === $result['owned'][1]['profile_id'], 'e-multi: shared profile' );

// (f) Duplicate slot on two spans fails.
$dup_proofs = array(
	oc_proof( $inv['cards'][0], 101, 'slot-a' ),
	oc_proof( $inv['cards'][1], 101, 'slot-a' ),
	oc_proof( $inv['cards'][2], 103, 'slot-a', 'shadow' ),
);
oc_assert_failure( oc_classify( 20323, $inv, $dup_proofs, $content ), 'duplicate_slot', 'f-duplicate-slot' );

// (g) Same span claimed by two proofs fails (even same profile, distinct slots).
$conflict_proofs   = array( oc_proof( $inv['cards'][0], 101, 'slot-a' ), oc_proof( $inv['cards'][0], 101, 'slot-b' ) );
$single_content    = oc_wrap_cards( oc_id_card( 101, 'fluffy', 'Fluffy' ) );
$single_inv        = oc_inventory_ok( $single_content, 'g-single' );
oc_assert_failure( oc_classify( 20323, $single_inv, $conflict_proofs, $single_content ), 'conflicting_claims', 'g-two-proofs-one-span' );

// Proof profile differs from stored marker fails.
oc_assert_failure( oc_classify( 20323, $single_inv, array( oc_proof( $single_inv['cards'][0], 999, 'slot-a' ) ), $single_content ), 'conflicting_claims', 'g-profile-mismatch' );

// Proof on a manual span fails.
$manual_content = oc_wrap_cards( oc_manual_card() );
$manual_inv     = oc_inventory_ok( $manual_content, 'g-manual' );
$manual_proof   = oc_proof( $manual_inv['cards'][0], 101, 'slot-a' );
oc_assert_failure( oc_classify( 20323, $manual_inv, array( $manual_proof ), $manual_content ), 'conflicting_claims', 'g-proof-on-manual' );

// Legacy proof without slug fails; legacy proof with wrong slug fails.
$legacy_content = oc_wrap_cards( oc_legacy_card( 'mittens', 'Mittens' ) );
$legacy_inv     = oc_inventory_ok( $legacy_content, 'g-legacy' );
oc_assert_failure( oc_classify( 20323, $legacy_inv, array( oc_proof( $legacy_inv['cards'][0], 77, 'slot-x' ) ), $legacy_content ), 'conflicting_claims', 'g-legacy-no-slug' );
oc_assert_failure( oc_classify( 20323, $legacy_inv, array( oc_proof( $legacy_inv['cards'][0], 77, 'slot-x', 'fluffy' ) ), $legacy_content ), 'conflicting_claims', 'g-legacy-wrong-slug' );

// (h) Missing proofs fail; never resolved from slug/title alone.
oc_assert_failure( oc_classify( 20323, $single_inv, array(), $single_content ), 'missing_proof', 'h-missing-id' );
oc_assert_failure( oc_classify( 20323, $legacy_inv, array(), $legacy_content ), 'missing_proof', 'h-missing-legacy' );

// (i) Stale proof (tampered slice) fails.
$stale       = oc_proof( $single_inv['cards'][0], 101, 'slot-a' );
$stale['slice'] .= ' ';
oc_assert_failure( oc_classify( 20323, $single_inv, array( $stale ), $single_content ), 'stale_proof', 'i-stale-slice' );

// (j) Conflicting media/title slugs fail.
$mixed_content = oc_wrap_cards( oc_mixed_slug_card( 101, 'fluffy', 'mittens', 'Fluffy' ) );
$mixed_inv     = oc_inventory_ok( $mixed_content, 'j-mixed-slugs' );
oc_assert_failure( oc_classify( 20323, $mixed_inv, array(), $mixed_content ), 'ambiguous_card', 'j-mixed-slugs' );

// Single dialog slug fails.
$single_dialog_content = oc_wrap_cards( oc_single_dialog_card( 'fluffy', 'Fluffy' ) );
$single_dialog_inv     = oc_inventory_ok( $single_dialog_content, 'j-single-dialog' );
oc_assert_failure( oc_classify( 20323, $single_dialog_inv, array(), $single_dialog_content ), 'ambiguous_card', 'j-single-dialog' );

// ID marker without anchors fails.
$id_bare_content = oc_wrap_cards( oc_id_no_anchors_card( 101 ) );
$id_bare_inv     = oc_inventory_ok( $id_bare_content, 'j-id-bare' );
oc_assert_failure( oc_classify( 20323, $id_bare_inv, array(), $id_bare_content ), 'ambiguous_card', 'j-id-no-anchors' );

// Card token without dialog anchors fails.
$token_bare_content = oc_wrap_cards( oc_token_no_dialog_card() );
$token_bare_inv     = oc_inventory_ok( $token_bare_content, 'j-token-bare' );
oc_assert_failure( oc_classify( 20323, $token_bare_inv, array(), $token_bare_content ), 'ambiguous_card', 'j-token-no-dialog' );

// (k) Malformed markers fail.
$malformed_cases = array(
	'k-unquoted'    => '<article class="pfoa-cat-card" data-pfoa-profile-id=101>',
	'k-empty'       => '<article class="pfoa-cat-card" data-pfoa-profile-id="">',
	'k-zero-lead'   => '<article class="pfoa-cat-card" data-pfoa-profile-id="007">',
	'k-zero'        => '<article class="pfoa-cat-card" data-pfoa-profile-id="0">',
	'k-nonnumeric'  => '<article class="pfoa-cat-card" data-pfoa-profile-id="abc">',
	'k-text-mention' => '<article class="pfoa-cat-card"><p>profile-id note</p>',
);
foreach ( $malformed_cases as $label => $open ) {
	$inner = $open
		. '<a class="pfoa-cat-card-media" href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy"><img src="https://example.test/fluffy.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">Fluffy</a></h3>'
		. '</article>';
	$cased_content = oc_wrap_cards( $inner );
	$cased_inv     = oc_inventory_ok( $cased_content, $label );
	oc_assert_failure( oc_classify( 20323, $cased_inv, array(), $cased_content ), 'malformed_marker', $label );
}

// (l) Spoofed markers and classes fail.
$spoof_inner = array(
	'l-fake-attr'  => '<article class="pfoa-cat-card" data-fake-profile-id="101">',
	'l-dash-attr'  => '<article class="pfoa-cat-card" data-pfoa-profile-id-extra="101">',
	'l-class-extra' => '<article class="pfoa-cat-cards-extra" data-pfoa-profile-id="101">',
	'l-class-plural' => '<article class="pfoa-cat-cards">',
);
foreach ( $spoof_inner as $label => $open ) {
	$inner = $open
		. '<a class="pfoa-cat-card-media" href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy"><img src="https://example.test/fluffy.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/fluffy/" data-pfoa-cat-dialog="fluffy">Fluffy</a></h3>'
		. '</article>';
	$cased_content = oc_wrap_cards( $inner );
	$cased_inv     = oc_inventory_ok( $cased_content, $label );
	oc_assert_failure( oc_classify( 20323, $cased_inv, array(), $cased_content ), 'spoofed_marker', $label );
}

// (m) Exact byte + fingerprint verification with unicode; tampered variants fail.
$unicode_content = oc_wrap_cards( oc_id_card( 104, 'nono', 'Müller — café ☃ Ñoño' ) );
$unicode_inv     = oc_inventory_ok( $unicode_content, 'm-unicode' );
$unicode_card    = $unicode_inv['cards'][0];
oc_assert( $unicode_card['end'] - $unicode_card['start'] === strlen( $unicode_card['slice'] ), 'm-unicode: byte length via strlen' );
oc_assert( strpos( $unicode_content, $unicode_card['slice'] ) === $unicode_card['start'], 'm-unicode: byte position via strpos' );
$unicode_result = oc_classify( 20323, $unicode_inv, array( oc_proof( $unicode_card, 104, 'slot-u' ) ), $unicode_content );
oc_assert( true === $unicode_result['ok'], 'm-unicode: ok' );
oc_assert( '104' === $unicode_result['owned'][0]['profile_id'], 'm-unicode: profile' );
oc_assert( false !== strpos( $unicode_result['owned'][0]['slice'], 'Müller — café ☃ Ñoño' ), 'm-unicode: bytes preserved' );

$tampered_slice_proof = oc_proof( $unicode_card, 104, 'slot-u' );
$tampered_slice_proof['slice'] = str_replace( 'Müller', 'Muller', $tampered_slice_proof['slice'] );
oc_assert_failure( oc_classify( 20323, $unicode_inv, array( $tampered_slice_proof ), $unicode_content ), 'stale_proof', 'm-tampered-slice' );

$tampered_fp_proof               = oc_proof( $unicode_card, 104, 'slot-u' );
$tampered_fp_proof['fingerprint'] = md5( 'something-else-entirely' );
oc_assert( $tampered_fp_proof['fingerprint'] !== md5( $unicode_card['slice'] ), 'm-tampered-fp: fingerprints differ' );
oc_assert_failure( oc_classify( 20323, $unicode_inv, array( $tampered_fp_proof ), $unicode_content ), 'fingerprint_mismatch', 'm-tampered-fingerprint' );

// (n) Orphan proof fails.
$orphan_card  = $unicode_card;
$orphan_proof = array(
	'start'       => $orphan_card['end'] + 1,
	'end'         => $orphan_card['end'] + 9,
	'profile_id'  => '104',
	'slot'        => 'slot-orphan',
	'slice'       => '12345678',
	'fingerprint' => md5( '12345678' ),
);
oc_assert_failure( oc_classify( 20323, $unicode_inv, array( oc_proof( $unicode_card, 104, 'slot-u' ), $orphan_proof ), $unicode_content ), 'orphan_proof', 'n-orphan' );

// (o) Unsupported structure (unclosed heading inside an otherwise plain card).
$broken_content = oc_wrap_cards( oc_unclosed_heading_card() );
$broken_inv     = oc_inventory_ok( $broken_content, 'o-broken' );
oc_assert_failure( oc_classify( 20323, $broken_inv, array(), $broken_content ), 'unsupported_structure', 'o-unclosed-h3' );

// (p) Source, content, inventory and offset guards.
oc_assert_failure( oc_classify( 99999, $single_inv, array( oc_proof( $single_inv['cards'][0], 101, 'slot-a' ) ), $single_content ), 'unsupported_source', 'p-source-int' );
oc_assert_failure( oc_classify( '99999', $single_inv, array( oc_proof( $single_inv['cards'][0], 101, 'slot-a' ) ), $single_content ), 'unsupported_source', 'p-source-string' );
oc_assert_failure( oc_classify( 20323, $single_inv, array( oc_proof( $single_inv['cards'][0], 101, 'slot-a' ) ), null ), 'invalid_content', 'p-content-null' );
oc_assert_failure( oc_classify( 20323, $single_inv, array( oc_proof( $single_inv['cards'][0], 101, 'slot-a' ) ), array( 'x' ) ), 'invalid_content', 'p-content-array' );
oc_assert_failure( oc_classify( 20323, array( 'ok' => false, 'error' => array( 'code' => 'x', 'message' => 'y' ) ), array(), $single_content ), 'invalid_inventory', 'p-inv-failed' );
oc_assert_failure( oc_classify( 20323, null, array(), $single_content ), 'invalid_inventory', 'p-inv-null' );
$bad_count_inv = $single_inv;
$bad_count_inv['count'] = 99;
oc_assert_failure( oc_classify( 20323, $bad_count_inv, array(), $single_content ), 'invalid_inventory', 'p-inv-count' );
$shifted_inv = $single_inv;
$shifted_inv['cards'][0]['start'] = $shifted_inv['cards'][0]['start'] + 1;
oc_assert_failure( oc_classify( 20323, $shifted_inv, array(), $single_content ), 'slice_mismatch', 'p-slice-shifted' );
$oob_inv = $single_inv;
$oob_inv['cards'][0]['end'] = strlen( $single_content ) + 50;
oc_assert_failure( oc_classify( 20323, $oob_inv, array(), $single_content ), 'invalid_offsets', 'p-offsets-oob' );

// (q) Invalid proof shapes fail.
$good_proof = oc_proof( $single_inv['cards'][0], 101, 'slot-a' );
$bad_proofs = array(
	'q-not-array'   => 'nope',
	'q-missing-slot' => array(
		'start'       => $good_proof['start'],
		'end'         => $good_proof['end'],
		'profile_id'  => '101',
		'slice'       => $good_proof['slice'],
		'fingerprint' => $good_proof['fingerprint'],
	),
	'q-int-profile' => array_merge( $good_proof, array( 'profile_id' => 101 ) ),
	'q-zero-profile' => array_merge( $good_proof, array( 'profile_id' => '0' ) ),
	'q-leading-zero' => array_merge( $good_proof, array( 'profile_id' => '007' ) ),
	'q-empty-slot'  => array_merge( $good_proof, array( 'slot' => '' ) ),
	'q-space-slot'  => array_merge( $good_proof, array( 'slot' => 'a b' ) ),
	'q-long-slot'   => array_merge( $good_proof, array( 'slot' => str_repeat( 'a', 65 ) ) ),
	'q-upper-fp'    => array_merge( $good_proof, array( 'fingerprint' => strtoupper( $good_proof['fingerprint'] ) ) ),
	'q-short-fp'    => array_merge( $good_proof, array( 'fingerprint' => 'abc' ) ),
	'q-string-start' => array_merge( $good_proof, array( 'start' => (string) $good_proof['start'] ) ),
	'q-end-before'  => array_merge( $good_proof, array( 'end' => $good_proof['start'] ) ),
	'q-upper-slug'  => array_merge( $good_proof, array( 'slug' => 'Fluffy' ) ),
	'q-int-slug'    => array_merge( $good_proof, array( 'slug' => 5 ) ),
);
foreach ( $bad_proofs as $label => $bad ) {
	oc_assert_failure( oc_classify( 20323, $single_inv, array( $bad ), $single_content ), 'invalid_proofs', $label );
}
oc_assert_failure( oc_classify( 20323, $single_inv, array( 'slot-a' => $good_proof ), $single_content ), 'invalid_proofs', 'q-non-list' );
oc_assert_failure( oc_classify( 20323, $single_inv, 'nope', $single_content ), 'invalid_proofs', 'q-proofs-not-array' );

// (t) Complete single with real thumbnail class stays ID-owned.
$t_content = oc_wrap_cards( oc_complete_thumb_card( 101, 'fluffy', 'Fluffy' ) );
$t_inv     = oc_inventory_ok( $t_content, 't-thumb' );
$t_result  = oc_classify( 20323, $t_inv, array( oc_proof( $t_inv['cards'][0], 101, 'slot-a' ) ), $t_content );
oc_assert( true === $t_result['ok'], 't-thumb: ok' );
oc_assert( 'id-owned' === $t_result['owned'][0]['kind'], 't-thumb: kind' );
oc_assert( '101' === $t_result['owned'][0]['profile_id'], 't-thumb: profile' );

// (u) Complete single with status paragraph stays ID-owned.
$u_content = oc_wrap_cards( oc_complete_status_card( 101, 'fluffy', 'Fluffy', 'Napping' ) );
$u_inv     = oc_inventory_ok( $u_content, 'u-status' );
$u_result  = oc_classify( 20323, $u_inv, array( oc_proof( $u_inv['cards'][0], 101, 'slot-a' ) ), $u_content );
oc_assert( true === $u_result['ok'], 'u-status: ok' );
oc_assert( 'id-owned' === $u_result['owned'][0]['kind'], 'u-status: kind' );
oc_assert( '101' === $u_result['owned'][0]['profile_id'], 'u-status: profile' );

// (v) Incomplete single is ID-owned.
$v_content = oc_wrap_cards( oc_incomplete_card( 102, 'shadow', 'Shadow' ) );
$v_inv     = oc_inventory_ok( $v_content, 'v-incomplete' );
$v_result  = oc_classify( 20323, $v_inv, array( oc_proof( $v_inv['cards'][0], 102, 'slot-a' ) ), $v_content );
oc_assert( true === $v_result['ok'], 'v-incomplete: ok' );
oc_assert( 1 === $v_result['count_owned'], 'v-incomplete: count' );
oc_assert( 'id-owned' === $v_result['owned'][0]['kind'], 'v-incomplete: kind' );
oc_assert( '102' === $v_result['owned'][0]['profile_id'], 'v-incomplete: profile' );
oc_assert( array( 'start', 'end', 'slice', 'profile_id', 'slot', 'kind' ) === array_keys( $v_result['owned'][0] ), 'v-incomplete: owned keys exact' );

// Incomplete single with status paragraph stays ID-owned.
$vs_content = oc_wrap_cards( oc_incomplete_card( 102, 'shadow', 'Shadow', 'Resting' ) );
$vs_inv     = oc_inventory_ok( $vs_content, 'v-incomplete-status' );
$vs_result  = oc_classify( 20323, $vs_inv, array( oc_proof( $vs_inv['cards'][0], 102, 'slot-a' ) ), $vs_content );
oc_assert( true === $vs_result['ok'], 'v-status: ok' );
oc_assert( 'id-owned' === $vs_result['owned'][0]['kind'], 'v-status: kind' );

// (w) Incomplete two-member grouped pair shares one profile with distinct slots.
$w_content = oc_wrap_cards( oc_incomplete_pair_card( 105, 'left', 'tom', 'Tom' ) . oc_incomplete_pair_card( 105, 'right', 'jerry', 'Jerry' ) );
$w_inv     = oc_inventory_ok( $w_content, 'w-pair' );
$w_proofs  = array(
	oc_proof( $w_inv['cards'][0], 105, 'slot-left' ),
	oc_proof( $w_inv['cards'][1], 105, 'slot-right' ),
);
$w_result  = oc_classify( 20323, $w_inv, $w_proofs, $w_content );
oc_assert( true === $w_result['ok'], 'w-pair: ok' );
oc_assert( 2 === $w_result['count_owned'], 'w-pair: count' );
oc_assert( 'id-owned' === $w_result['owned'][0]['kind'] && 'id-owned' === $w_result['owned'][1]['kind'], 'w-pair: kinds' );
oc_assert( '105' === $w_result['owned'][0]['profile_id'] && '105' === $w_result['owned'][1]['profile_id'], 'w-pair: shared profile' );

// Incomplete pair card carrying a status paragraph fails (status is single only).
$pair_status_inner = '<article class="pfoa-cat-card pfoa-cat-card-incomplete pfoa-cat-pair-left" data-pfoa-profile-id="105">'
	. '<div class="pfoa-cat-card-media">' . oc_thumb_img( 'tom' ) . '</div>'
	. '<h3 class="pfoa-cat-card-title">Tom</h3>'
	. '<p class="pfoa-cat-card-status">Resting</p>'
	. '<p class="pfoa-cat-card-note">Full profile coming soon.</p>'
	. '</article>';
$pair_status_content = oc_wrap_cards( $pair_status_inner );
$pair_status_inv     = oc_inventory_ok( $pair_status_content, 'w-pair-status' );
oc_assert_failure( oc_classify( 20323, $pair_status_inv, array(), $pair_status_content ), 'ambiguous_card', 'w-pair-status' );

// (x) Incomplete without trusted proof fails; proof profile mismatch fails.
oc_assert_failure( oc_classify( 20323, $v_inv, array(), $v_content ), 'missing_proof', 'x-incomplete-missing' );
oc_assert_failure( oc_classify( 20323, $v_inv, array( oc_proof( $v_inv['cards'][0], 999, 'slot-a' ) ), $v_content ), 'conflicting_claims', 'x-incomplete-mismatch' );

// Unmarked incomplete cards are never owned (no ID marker fails closed).
$bare_incomplete_inner = '<article class="pfoa-cat-card pfoa-cat-card-incomplete">'
	. '<div class="pfoa-cat-card-media">' . oc_thumb_img( 'tom' ) . '</div>'
	. '<h3 class="pfoa-cat-card-title">Tom</h3>'
	. '<p class="pfoa-cat-card-note">Full profile coming soon.</p>'
	. '</article>';
$bare_incomplete_content = oc_wrap_cards( $bare_incomplete_inner );
$bare_incomplete_inv     = oc_inventory_ok( $bare_incomplete_content, 'x-bare-incomplete' );
oc_assert_failure( oc_classify( 20323, $bare_incomplete_inv, array(), $bare_incomplete_content ), 'ambiguous_card', 'x-bare-incomplete' );

// (y) Incomplete with unexpected dialog anchor fails.
$y_content = oc_wrap_cards( oc_incomplete_dialog_card( 102, 'shadow', 'Shadow' ) );
$y_inv     = oc_inventory_ok( $y_content, 'y-dialog' );
oc_assert_failure( oc_classify( 20323, $y_inv, array(), $y_content ), 'ambiguous_card', 'y-incomplete-dialog' );

// (z) Spoofed suffixed generated classes fail.
$z1_content = oc_wrap_cards( oc_spoofed_suffix_card() );
$z1_inv     = oc_inventory_ok( $z1_content, 'z-spoof-suffix' );
oc_assert_failure( oc_classify( 20323, $z1_inv, array(), $z1_content ), 'spoofed_marker', 'z-spoof-suffix' );
$z2_content = oc_wrap_cards( oc_spoofed_media_card() );
$z2_inv     = oc_inventory_ok( $z2_content, 'z-spoof-media' );
oc_assert_failure( oc_classify( 20323, $z2_inv, array(), $z2_content ), 'spoofed_marker', 'z-spoof-media' );
$z3_content = oc_wrap_cards( oc_spoofed_thumb_card() );
$z3_inv     = oc_inventory_ok( $z3_content, 'z-spoof-thumb' );
oc_assert_failure( oc_classify( 20323, $z3_inv, array(), $z3_content ), 'spoofed_marker', 'z-spoof-thumb' );

// (r) No hooks/queries/writes: static file-content bans on the classifier file.
$classifier_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-ownership-classifier.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'WP_Query', 'get_posts', 'get_option', 'update_option', 'wp_insert', 'wp_update', '$wpdb', 'file_put_contents', 'unlink', 'get_post', 'meta', 'placement', 'bond', 'composition', 'lifecycle', 'resolver' ) as $needle ) {
	oc_assert( false === strpos( $classifier_source, $needle ), 'classifier file has no ' . $needle );
}

// (s) The classifier file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
oc_assert( false === strpos( $functions_source, 'theme-managed-card-ownership-classifier' ), 'functions.php does not require classifier file' );

fwrite( STDOUT, "PASS: theme managed card ownership classifier fixture (valid id-owned, proven legacy, manual untouched, mixed order, shared profile distinct slots, duplicate/conflicting claims, missing/stale proofs, conflicting slugs, malformed/spoofed markers and classes, generated complete with thumbnail and status, generated incomplete single with and without status, incomplete grouped pair, incomplete missing/mismatched proofs, unmarked incomplete fails closed, incomplete dialog ambiguity, spoofed generated classes, unicode byte and fingerprint checks, orphan proofs, unsupported structure, source/content/inventory/proof guards, no hooks/queries/writes)\n" );
