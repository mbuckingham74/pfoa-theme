<?php
/**
 * Focused fixture for the inactive theme-owned managed-card byte splice.
 *
 * Requires pfoa-theme/inc/theme-managed-card-byte-splice.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. Content is built by the helpers
 * below, mirroring the baseline static card markup, and owned/manual spans
 * are computed from exact byte offsets with strpos/substr/strlen.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-byte-splice.php';

function bs_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function bs_assert_success( $result, $label ) {
	bs_assert( is_array( $result ), $label . ': result is array' );
	bs_assert( array( 'success', 'projected_content', 'failure_reason', 'replaced_count' ) === array_keys( $result ), $label . ': keys exact' );
	bs_assert( true === $result['success'], $label . ': success true' );
	bs_assert( is_string( $result['projected_content'] ), $label . ': projected string' );
	bs_assert( null === $result['failure_reason'], $label . ': reason null' );
	bs_assert( is_int( $result['replaced_count'] ), $label . ': count int' );
}

function bs_assert_failure( $result, $code, $label ) {
	bs_assert( is_array( $result ), $label . ': result is array' );
	bs_assert( array( 'success', 'projected_content', 'failure_reason', 'replaced_count' ) === array_keys( $result ), $label . ': keys exact' );
	bs_assert( false === $result['success'], $label . ': success false' );
	bs_assert( null === $result['projected_content'], $label . ': no partial output' );
	bs_assert( $code === $result['failure_reason'], $label . ': code is ' . $code . ' got ' . var_export( $result['failure_reason'], true ) );
	bs_assert( 0 === $result['replaced_count'], $label . ': count 0' );
}

function bs_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function bs_manual_card() {
	return '<article class="note"><p>Editorial note</p></article>';
}

function bs_wrap( $inner ) {
	return '<p>Intro</p><div class="pfoa-cat-cards">' . $inner . '</div><p>Outro</p>';
}

function bs_span( $content, $needle, $from = 0 ) {
	$start = strpos( $content, $needle, $from );
	bs_assert( false !== $start, 'span found for needle' );
	return array(
		'start' => $start,
		'end'   => $start + strlen( $needle ),
		'slice' => $needle,
	);
}

function bs_owned( $content, $card, $profile_id, $slot, $from = 0 ) {
	$span                 = bs_span( $content, $card, $from );
	$span['profile_id'] = (string) $profile_id;
	$span['slot']       = (string) $slot;
	$span['kind']       = 'id-owned';
	return $span;
}

function bs_classify( $owned, $manual = array() ) {
	return array(
		'ok'             => true,
		'source_page_id' => 20323,
		'owned'          => $owned,
		'manual'         => $manual,
		'count_owned'    => count( $owned ),
		'count_manual'   => count( $manual ),
	);
}

function bs_record( $owned_span, $html ) {
	return array(
		'profile_id' => $owned_span['profile_id'],
		'slot'       => $owned_span['slot'],
		'start'      => $owned_span['start'],
		'end'        => $owned_span['end'],
		'html'       => $html,
	);
}

function bs_splice( $source, $content, $classification, $records ) {
	return pfoa_theme_splice_managed_cards( $source, $content, $classification, $records );
}

bs_assert( function_exists( 'pfoa_theme_splice_managed_cards' ), 'splice function exists' );

// (a) Single replacement with exact projected bytes.
$card_a  = bs_card( 101, 'fluffy', 'Fluffy' );
$content = bs_wrap( $card_a );
$owned   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$records = array( bs_record( $owned[0], '<section data-new="1">Fluffy new</section>' ) );
$result  = bs_splice( 20323, $content, bs_classify( $owned ), $records );
bs_assert_success( $result, 'a-single' );
$expected = substr( $content, 0, $owned[0]['start'] ) . '<section data-new="1">Fluffy new</section>' . substr( $content, $owned[0]['end'] );
bs_assert( $expected === $result['projected_content'], 'a-single: exact bytes' );
bs_assert( 1 === $result['replaced_count'], 'a-single: count' );
bs_assert( false !== strpos( $result['projected_content'], '<p>Intro</p>' ), 'a-single: intro kept' );
bs_assert( false !== strpos( $result['projected_content'], '<p>Outro</p>' ), 'a-single: outro kept' );
bs_assert( false === strpos( $result['projected_content'], 'data-pfoa-profile-id' ), 'a-single: stored slice gone' );

// Digit-string source is accepted and matches.
$result_str = bs_splice( '20323', $content, bs_classify( $owned ), $records );
bs_assert( true === $result_str['success'] && $expected === $result_str['projected_content'], 'a-string-source: same bytes' );

// Minimal classification shape (owned/manual only, no ok flag) is rejected.
$minimal = array( 'owned' => $owned, 'manual' => array() );
bs_assert_failure( bs_splice( 20323, $content, $minimal, $records ), 'invalid_classification', 'a-minimal-shape' );

// (a2) Verified classification contract: exact successful classifier shape only.
$legacy_owned        = $owned;
$legacy_owned[0]     = $owned[0];
$legacy_owned[0]['kind'] = 'legacy-proven';
$legacy_records = array( bs_record( $legacy_owned[0], '<section data-new="1">Fluffy new</section>' ) );
$legacy_result  = bs_splice( 20323, $content, bs_classify( $legacy_owned ), $legacy_records );
bs_assert_success( $legacy_result, 'a2-legacy-kind' );
bs_assert( $expected === $legacy_result['projected_content'], 'a2-legacy-kind: exact bytes' );
bs_assert( 1 === $legacy_result['replaced_count'], 'a2-legacy-kind: count' );

// Missing ok rejected.
$no_ok = bs_classify( $owned );
unset( $no_ok['ok'] );
bs_assert_failure( bs_splice( 20323, $content, $no_ok, $records ), 'invalid_classification', 'a2-missing-ok' );

// Truthy non-true ok (1) rejected.
$ok_one       = bs_classify( $owned );
$ok_one['ok'] = 1;
bs_assert_failure( bs_splice( 20323, $content, $ok_one, $records ), 'invalid_classification', 'a2-ok-one' );

// Missing source_page_id rejected.
$no_source = bs_classify( $owned );
unset( $no_source['source_page_id'] );
bs_assert_failure( bs_splice( 20323, $content, $no_source, $records ), 'invalid_classification', 'a2-missing-source' );

// Wrong source_page_id int rejected.
$bad_source                    = bs_classify( $owned );
$bad_source['source_page_id'] = 99999;
bs_assert_failure( bs_splice( 20323, $content, $bad_source, $records ), 'invalid_classification', 'a2-wrong-source' );

// String '20323' source_page_id rejected (must be int strict).
$str_source                    = bs_classify( $owned );
$str_source['source_page_id'] = '20323';
bs_assert_failure( bs_splice( 20323, $content, $str_source, $records ), 'invalid_classification', 'a2-string-source-id' );

// Missing manual array rejected.
$no_manual = bs_classify( $owned );
unset( $no_manual['manual'] );
bs_assert_failure( bs_splice( 20323, $content, $no_manual, $records ), 'invalid_classification', 'a2-missing-manual' );

// Manual non-array rejected.
$manual_str           = bs_classify( $owned );
$manual_str['manual'] = 'x';
bs_assert_failure( bs_splice( 20323, $content, $manual_str, $records ), 'invalid_classification', 'a2-manual-string' );

// Incorrect count_owned rejected (off by one).
$bad_owned_count                 = bs_classify( $owned );
$bad_owned_count['count_owned'] = count( $owned ) + 1;
bs_assert_failure( bs_splice( 20323, $content, $bad_owned_count, $records ), 'invalid_classification', 'a2-count-owned-high' );

// Non-int count_owned rejected.
$bad_owned_type                 = bs_classify( $owned );
$bad_owned_type['count_owned'] = '1';
bs_assert_failure( bs_splice( 20323, $content, $bad_owned_type, $records ), 'invalid_classification', 'a2-count-owned-string' );

// Incorrect count_manual rejected.
$bad_manual_count                  = bs_classify( $owned );
$bad_manual_count['count_manual'] = 1;
bs_assert_failure( bs_splice( 20323, $content, $bad_manual_count, $records ), 'invalid_classification', 'a2-count-manual-high' );

// Non-int count_manual rejected.
$bad_manual_type                  = bs_classify( $owned );
$bad_manual_type['count_manual'] = '0';
bs_assert_failure( bs_splice( 20323, $content, $bad_manual_type, $records ), 'invalid_classification', 'a2-count-manual-string' );

// Missing owned kind rejected.
$no_kind = $owned;
unset( $no_kind[0]['kind'] );
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $no_kind ), $records ), 'invalid_classification', 'a2-missing-kind' );

// Unsupported owned kinds rejected.
foreach ( array( 'manual', 'id-incomplete', '', null ) as $bad_kind ) {
	$bad_kind_owned       = $owned;
	$bad_kind_owned[0]   = $owned[0];
	$bad_kind_owned[0]['kind'] = $bad_kind;
	bs_assert_failure(
		bs_splice( 20323, $content, bs_classify( $bad_kind_owned ), $records ),
		'invalid_classification',
		'a2-bad-kind-' . var_export( $bad_kind, true )
	);
}

// (b) Multiple replacements; caller order does not matter (offsets cannot shift).
$card_b   = bs_card( 102, 'mittens', 'Mittens' );
$content  = bs_wrap( $card_a . '<p>Between</p>' . $card_b );
$owned_b1 = bs_owned( $content, $card_a, 101, 'slot-a' );
$owned_b2 = bs_owned( $content, $card_b, 102, 'slot-b', $owned_b1['end'] );
$records  = array( bs_record( $owned_b2, '<b>B-new</b>' ), bs_record( $owned_b1, '<b>A-new</b>' ) );
$result   = bs_splice( 20323, $content, bs_classify( array( $owned_b1, $owned_b2 ) ), $records );
bs_assert_success( $result, 'b-multi' );
$expected = substr( $content, 0, $owned_b1['start'] ) . '<b>A-new</b>'
	. substr( $content, $owned_b1['end'], $owned_b2['start'] - $owned_b1['end'] ) . '<b>B-new</b>'
	. substr( $content, $owned_b2['end'] );
bs_assert( $expected === $result['projected_content'], 'b-multi: exact bytes' );
bs_assert( 2 === $result['replaced_count'], 'b-multi: count' );
bs_assert( false !== strpos( $result['projected_content'], '<p>Between</p>' ), 'b-multi: gap bytes kept' );

// (c) Mixed managed + manual: manual bytes stay exactly in place.
$manual_card = bs_manual_card();
$content     = bs_wrap( $card_a . $manual_card . $card_b );
$owned_c1    = bs_owned( $content, $card_a, 101, 'slot-a' );
$manual_c    = bs_span( $content, $manual_card, $owned_c1['end'] );
$owned_c2    = bs_owned( $content, $card_b, 102, 'slot-b', $manual_c['end'] );
$records     = array( bs_record( $owned_c1, '<i>A2</i>' ), bs_record( $owned_c2, '<i>B2</i>' ) );
$result      = bs_splice( 20323, $content, bs_classify( array( $owned_c1, $owned_c2 ), array( $manual_c ) ), $records );
bs_assert_success( $result, 'c-mixed' );
bs_assert( 2 === $result['replaced_count'], 'c-mixed: count' );
bs_assert( false !== strpos( $result['projected_content'], $manual_card ), 'c-mixed: manual slice kept' );
$expected = substr( $content, 0, $owned_c1['start'] ) . '<i>A2</i>'
	. substr( $content, $owned_c1['end'], $owned_c2['start'] - $owned_c1['end'] ) . '<i>B2</i>'
	. substr( $content, $owned_c2['end'] );
bs_assert( $expected === $result['projected_content'], 'c-mixed: exact bytes' );
bs_assert( strpos( $result['projected_content'], $manual_card ) === strpos( $expected, $manual_card ), 'c-mixed: manual position kept' );

// (d) Editorial byte preservation around a single swap.
bs_assert( 0 === strpos( $result['projected_content'], '<p>Intro</p>' ), 'd-editorial: intro at offset zero' );
bs_assert( '</p>' === substr( $result['projected_content'], strlen( $result['projected_content'] ) - 4, 4 ), 'd-editorial: outro tail kept' );

// (e) Unicode plus whitespace/newline preservation.
$unicode_card = bs_card( 104, 'nono', 'Müller — café ☃ Ñoño' );
$content      = "<p>Intro</p>\n\t<div class=\"pfoa-cat-cards\">\n  " . $unicode_card . "\n\t" . $card_b . "\n</div><p>Outro ✓</p>";
$owned_e1     = bs_owned( $content, $unicode_card, 104, 'slot-u' );
$owned_e2     = bs_owned( $content, $card_b, 102, 'slot-b', $owned_e1['end'] );
$records      = array( bs_record( $owned_e1, '<u>U</u>' ), bs_record( $owned_e2, '<u>V</u>' ) );
$result       = bs_splice( 20323, $content, bs_classify( array( $owned_e1, $owned_e2 ) ), $records );
bs_assert_success( $result, 'e-unicode' );
bs_assert( $owned_e1['end'] - $owned_e1['start'] === strlen( $owned_e1['slice'] ), 'e-unicode: byte length via strlen' );
$expected = substr( $content, 0, $owned_e1['start'] ) . '<u>U</u>'
	. substr( $content, $owned_e1['end'], $owned_e2['start'] - $owned_e1['end'] ) . '<u>V</u>'
	. substr( $content, $owned_e2['end'] );
bs_assert( $expected === $result['projected_content'], 'e-unicode: exact bytes' );
bs_assert( false !== strpos( $result['projected_content'], "\n\t" ), 'e-unicode: whitespace kept' );
bs_assert( false !== strpos( $result['projected_content'], 'Outro ✓' ), 'e-unicode: tail bytes kept' );

// (f) Adjacent owned spans (first end === second start) splice cleanly.
$content  = bs_wrap( $card_a . $card_b );
$owned_f1 = bs_owned( $content, $card_a, 101, 'slot-a' );
$owned_f2 = bs_owned( $content, $card_b, 102, 'slot-b', $owned_f1['end'] );
bs_assert( $owned_f1['end'] === $owned_f2['start'], 'f-adjacent: spans touch' );
$records = array( bs_record( $owned_f1, '<p>One</p>' ), bs_record( $owned_f2, '<p>Two</p>' ) );
$result  = bs_splice( 20323, $content, bs_classify( array( $owned_f1, $owned_f2 ) ), $records );
bs_assert_success( $result, 'f-adjacent' );
$expected = substr( $content, 0, $owned_f1['start'] ) . '<p>One</p><p>Two</p>' . substr( $content, $owned_f2['end'] );
bs_assert( $expected === $result['projected_content'], 'f-adjacent: exact bytes' );

// (g) Explicitly supplied empty string clears its span (array_key_exists, not empty()).
$content = bs_wrap( $card_a );
$owned   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$records = array( bs_record( $owned[0], '' ) );
bs_assert( array_key_exists( 'html', $records[0] ), 'g-empty: html key present' );
$result = bs_splice( 20323, $content, bs_classify( $owned ), $records );
bs_assert_success( $result, 'g-empty' );
$expected = substr( $content, 0, $owned[0]['start'] ) . substr( $content, $owned[0]['end'] );
bs_assert( $expected === $result['projected_content'], 'g-empty: span cleared' );
bs_assert( 1 === $result['replaced_count'], 'g-empty: count' );

// A record without the html key fails instead of clearing.
$no_html = $records[0];
unset( $no_html['html'] );
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $owned ), array( $no_html ) ), 'invalid_replacement', 'g-missing-html-key' );

// A non-string html value fails.
$bad_html = bs_record( $owned[0], '' );
$bad_html['html'] = 42;
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $owned ), array( $bad_html ) ), 'invalid_replacement', 'g-non-string-html' );

// (h) Missing record for an owned span fails with no output.
$content  = bs_wrap( $card_a . $card_b );
$owned_h1 = bs_owned( $content, $card_a, 101, 'slot-a' );
$owned_h2 = bs_owned( $content, $card_b, 102, 'slot-b', $owned_h1['end'] );
bs_assert_failure(
	bs_splice( 20323, $content, bs_classify( array( $owned_h1, $owned_h2 ) ), array( bs_record( $owned_h1, '<x/>' ) ) ),
	'missing_replacement',
	'h-missing'
);

// (i) The same claim twice fails, even when it matches an owned span.
$content = bs_wrap( $card_a );
$owned   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$one     = bs_record( $owned[0], '<x/>' );
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $owned ), array( $one, $one ) ), 'duplicate_replacement', 'i-duplicate' );

// (j) Stale owned slice fails (bytes moved under the classification).
$stale_owned    = $owned;
$stale_owned[0] = $owned[0];
$stale_owned[0]['slice'] .= ' ';
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $stale_owned ), array( $one ) ), 'stale_span', 'j-stale-slice' );

// A stale manual slice fails too.
$manual_span = bs_span( bs_wrap( $card_a . $manual_card ), $manual_card );
$manual_span['slice'] .= '?';
$shifted_manual_content = bs_wrap( $card_a . $manual_card );
$shifted_owned = array( bs_owned( $shifted_manual_content, $card_a, 101, 'slot-a' ) );
bs_assert_failure(
	bs_splice( 20323, $shifted_manual_content, bs_classify( $shifted_owned, array( $manual_span ) ), array( bs_record( $shifted_owned[0], '<x/>' ) ) ),
	'stale_span',
	'j-stale-manual'
);

// (k) Owned span touching a manual span fails.
$content   = bs_wrap( $card_a . $manual_card );
$owned_k   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$manual_k  = bs_span( $content, $manual_card, $owned_k[0]['end'] );
$overlap_k = array(
	'start' => $owned_k[0]['end'] - 4,
	'end'   => $manual_k['end'],
	'slice' => substr( $content, $owned_k[0]['end'] - 4, $manual_k['end'] - $owned_k[0]['end'] + 4 ),
);
bs_assert_failure(
	bs_splice( 20323, $content, bs_classify( $owned_k, array( $overlap_k ) ), array( bs_record( $owned_k[0], '<x/>' ) ) ),
	'manual_overlap',
	'k-manual-overlap'
);

// (l) Extra record with no owned span fails.
$content = bs_wrap( $card_a );
$owned   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$extra   = array(
	'profile_id' => '999',
	'slot'       => 'slot-ghost',
	'start'      => $owned[0]['start'],
	'end'        => $owned[0]['end'],
	'html'       => '<ghost/>',
);
bs_assert_failure(
	bs_splice( 20323, $content, bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ), $extra ) ),
	'unrecognized_replacement',
	'l-extra'
);

// Right offsets but wrong slot fail the four-part match.
$wrong_slot = bs_record( $owned[0], '<x/>' );
$wrong_slot['slot'] = 'slot-other';
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $owned ), array( $wrong_slot ) ), 'unrecognized_replacement', 'l-wrong-slot' );

// Right identity but shifted offsets fail the four-part match.
$shifted = bs_record( $owned[0], '<x/>' );
$shifted['start'] = $shifted['start'] + 1;
$shifted['end']   = $shifted['end'] + 1;
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $owned ), array( $shifted ) ), 'unrecognized_replacement', 'l-shifted-offsets' );

// (m) Source, content and classification guards, all without partial output.
bs_assert_failure( bs_splice( 99999, $content, bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ) ) ), 'unsupported_source', 'm-source-int' );
bs_assert_failure( bs_splice( '99999', $content, bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ) ) ), 'unsupported_source', 'm-source-string' );
bs_assert_failure( bs_splice( null, $content, bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ) ) ), 'unsupported_source', 'm-source-null' );
bs_assert_failure( bs_splice( 20323, null, bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ) ) ), 'invalid_content', 'm-content-null' );
bs_assert_failure( bs_splice( 20323, array( 'x' ), bs_classify( $owned ), array( bs_record( $owned[0], '<x/>' ) ) ), 'invalid_content', 'm-content-array' );
bs_assert_failure( bs_splice( 20323, $content, null, array( bs_record( $owned[0], '<x/>' ) ) ), 'invalid_classification', 'm-class-null' );
bs_assert_failure( bs_splice( 20323, $content, array( 'manual' => array() ), array() ), 'invalid_classification', 'm-class-no-owned' );
bs_assert_failure(
	bs_splice( 20323, $content, array( 'ok' => false, 'owned' => $owned, 'manual' => array() ), array( bs_record( $owned[0], '<x/>' ) ) ),
	'invalid_classification',
	'm-class-not-ok'
);

// (n) Malformed and out-of-range owned spans fail before anything is built.
$malformed_owned    = $owned;
$malformed_owned[0] = $owned[0];
$malformed_owned[0]['start'] = (string) $owned[0]['start'];
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $malformed_owned ), array() ), 'malformed_span', 'n-string-start' );

$oob_owned    = $owned;
$oob_owned[0] = $owned[0];
$oob_owned[0]['end'] = strlen( $content ) + 50;
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $oob_owned ), array() ), 'out_of_range', 'n-end-oob' );

$neg_owned    = $owned;
$neg_owned[0] = $owned[0];
$neg_owned[0]['start'] = -3;
bs_assert_failure( bs_splice( 20323, $content, bs_classify( $neg_owned ), array() ), 'out_of_range', 'n-negative-start' );

// (o) Owned spans overlapping each other fail.
$content   = bs_wrap( $card_a );
$owned_o1  = bs_owned( $content, $card_a, 101, 'slot-a' );
$owned_o2  = array(
	'start'      => $owned_o1['start'] + 2,
	'end'        => $owned_o1['end'],
	'slice'      => substr( $content, $owned_o1['start'] + 2, $owned_o1['end'] - $owned_o1['start'] - 2 ),
	'profile_id' => '102',
	'slot'       => 'slot-b',
	'kind'       => 'id-owned',
);
$records_o = array(
	bs_record( $owned_o1, '<x/>' ),
	array(
		'profile_id' => '102',
		'slot'       => 'slot-b',
		'start'      => $owned_o2['start'],
		'end'        => $owned_o2['end'],
		'html'       => '<y/>',
	),
);
bs_assert_failure( bs_splice( 20323, $content, bs_classify( array( $owned_o1, $owned_o2 ) ), $records_o ), 'overlapping_spans', 'o-owned-overlap' );

// (p) Strict record types: int profile id and string offsets never equal their twins.
$strict = bs_record( $owned_o1, '<x/>' );
$strict['profile_id'] = 101;
bs_assert_failure( bs_splice( 20323, $content, bs_classify( array( $owned_o1 ) ), array( $strict ) ), 'invalid_replacement', 'p-int-profile' );
$strict = bs_record( $owned_o1, '<x/>' );
$strict['start'] = (string) $owned_o1['start'];
bs_assert_failure( bs_splice( 20323, $content, bs_classify( array( $owned_o1 ) ), array( $strict ) ), 'invalid_replacement', 'p-string-start' );

// (q) Zero owned spans with zero records succeeds and returns the content untouched.
$content = bs_wrap( $manual_card );
$manual  = array( bs_span( $content, $manual_card ) );
$result  = bs_splice( 20323, $content, bs_classify( array(), $manual ), array() );
bs_assert_success( $result, 'q-empty' );
bs_assert( $content === $result['projected_content'], 'q-empty: content untouched' );
bs_assert( 0 === $result['replaced_count'], 'q-empty: count 0' );

// Zero owned spans but one record fails.
bs_assert_failure(
	bs_splice( 20323, $content, bs_classify( array(), $manual ), array( $extra ) ),
	'unrecognized_replacement',
	'q-extra-on-empty'
);

// Caller HTML is taken verbatim, never derived from stored bytes.
$content = bs_wrap( $card_a );
$owned   = array( bs_owned( $content, $card_a, 101, 'slot-a' ) );
$verbatim = '<article data-pfoa-profile-id="101">A &amp; B — “quoted” ☃</article>';
$result  = bs_splice( 20323, $content, bs_classify( $owned ), array( bs_record( $owned[0], $verbatim ) ) );
bs_assert_success( $result, 'q-verbatim' );
bs_assert( false !== strpos( $result['projected_content'], $verbatim ), 'q-verbatim: html untouched' );

// (r) No hooks/reads/writes: static file-content bans on the splice file.
$splice_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-byte-splice.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'WP_Query', 'get_posts', 'get_option', 'update_option', '$wpdb', 'file_put_contents', 'file_get_contents', 'unlink', 'get_post' ) as $needle ) {
	bs_assert( false === strpos( $splice_source, $needle ), 'splice file has no ' . $needle );
}
// The engine legitimately names caller html records and machine codes such as
// missing_replacement; strip that stem before banning subsystem words.
$stripped = str_replace( 'replacement', '', $splice_source );
foreach ( array( 'placement', 'bond', 'render', 'compose', 'proof', 'lifecycle', 'query', 'meta' ) as $needle ) {
	bs_assert( false === stripos( $stripped, $needle ), 'splice file has no ' . $needle );
}

// (s) The splice file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
bs_assert( false === strpos( $functions_source, 'theme-managed-card-byte-splice' ), 'functions.php does not require splice file' );

fwrite( STDOUT, "PASS: theme managed card byte splice fixture with verified classification contract (single, multiple reversed, mixed managed/manual, editorial bytes, unicode+whitespace, adjacent spans, explicit empty clear, missing/duplicate/stale/manual-overlap/extra/unrecognized failures with null output, malformed/out-of-range/overlapping spans, strict record types, empty plan, verbatim html, classification contract, no hooks/reads/writes)\n" );
