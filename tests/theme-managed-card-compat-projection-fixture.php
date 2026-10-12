<?php
/**
 * Focused fixture for the inactive theme-owned managed-card compat projection.
 *
 * Requires pfoa-theme/inc/theme-managed-card-inventory.php,
 * pfoa-theme/inc/theme-managed-card-ownership-classifier.php,
 * pfoa-theme/inc/theme-managed-card-placement-validator.php,
 * pfoa-theme/inc/theme-managed-card-byte-splice.php and
 * pfoa-theme/inc/theme-managed-card-compat-projection.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. Content is built by the helpers
 * below, mirroring the baseline static card markup, and spans are computed
 * from exact byte offsets with strpos/substr/strlen plus md5 fingerprints.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-inventory.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-dialog-attribute.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-ownership-classifier.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-placement-validator.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-byte-splice.php';
require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-compat-projection.php';

function cp_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function cp_pass( $label ) {
	fwrite( STDOUT, "PASS: {$label}\n" );
}

function cp_assert_success( $result, $label ) {
	cp_assert( is_array( $result ), $label . ': result is array' );
	cp_assert(
		array( 'ok', 'source_page_id', 'projected_content', 'replaced_count', 'owned_count', 'manual_count' ) === array_keys( $result ),
		$label . ': keys exact'
	);
	cp_assert( true === $result['ok'], $label . ': ok true' );
	cp_assert( 20323 === $result['source_page_id'], $label . ': source int 20323' );
	cp_assert( is_string( $result['projected_content'] ), $label . ': projected string' );
	cp_assert( is_int( $result['replaced_count'] ), $label . ': replaced int' );
	cp_assert( is_int( $result['owned_count'] ), $label . ': owned int' );
	cp_assert( is_int( $result['manual_count'] ), $label . ': manual int' );
}

function cp_assert_failure( $result, $stage, $label ) {
	cp_assert( is_array( $result ), $label . ': result is array' );
	cp_assert( array( 'ok', 'error', 'projected_content' ) === array_keys( $result ), $label . ': keys exact ok+error' );
	cp_assert( false === $result['ok'], $label . ': ok false' );
	cp_assert( isset( $result['error'] ) && is_array( $result['error'] ), $label . ': error block' );
	cp_assert(
		array( 'code', 'message', 'stage' ) === array_keys( $result['error'] ),
		$label . ': error keys exact'
	);
	cp_assert( is_string( $result['error']['code'] ) && '' !== $result['error']['code'], $label . ': code present' );
	cp_assert( is_string( $result['error']['message'] ) && '' !== $result['error']['message'], $label . ': message present' );
	cp_assert( $stage === $result['error']['stage'], $label . ': stage is ' . $stage . ' got ' . var_export( $result['error']['stage'], true ) );
	cp_assert( null === $result['projected_content'], $label . ': no partial HTML' );
}

function cp_id_card( $id, $slug, $title ) {
	return '<article class="pfoa-cat-card" data-pfoa-profile-id="' . $id . '">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function cp_legacy_card( $slug, $title ) {
	return '<article class="pfoa-cat-card">'
		. '<a class="pfoa-cat-card-media" href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '"><img src="https://example.test/' . $slug . '.jpg" alt=""></a>'
		. '<h3 class="pfoa-cat-card-title"><a href="https://example.test/' . $slug . '/" data-pfoa-cat-dialog="' . $slug . '">' . $title . '</a></h3>'
		. '</article>';
}

function cp_manual_card( $text = 'Editorial note' ) {
	return '<article class="note"><p>' . $text . '</p></article>';
}

function cp_wrap( $inner ) {
	return '<p>Intro</p><div class="pfoa-cat-cards">' . $inner . '</div><p>Outro</p>';
}

function cp_span_of( $content, $needle, $from = 0 ) {
	$start = strpos( $content, $needle, $from );
	cp_assert( false !== $start, 'span found for needle' );
	return array(
		'start' => $start,
		'end'   => $start + strlen( $needle ),
		'slice' => substr( $content, $start, strlen( $needle ) ),
	);
}

function cp_proof( $content, $card_html, $profile_id, $slot, $slug = null, $from = 0 ) {
	$span = cp_span_of( $content, $card_html, $from );
	cp_assert( $span['slice'] === $card_html, 'proof slice verbatim' );
	$proof = array(
		'start'       => $span['start'],
		'end'         => $span['end'],
		'profile_id'  => (string) $profile_id,
		'slot'        => (string) $slot,
		'slice'       => $span['slice'],
		'fingerprint' => md5( $span['slice'] ),
	);
	if ( null !== $slug ) {
		$proof['slug'] = (string) $slug;
	}
	return $proof;
}

function cp_placement_for( $proof, $with_slug = false ) {
	$record = array(
		'profile_id' => $proof['profile_id'],
		'slot'       => $proof['slot'],
		'start'      => $proof['start'],
		'end'        => $proof['end'],
	);
	if ( $with_slug && isset( $proof['slug'] ) ) {
		$record['slug'] = $proof['slug'];
	}
	return $record;
}

function cp_replacement_for( $proof, $html ) {
	return array(
		'profile_id' => $proof['profile_id'],
		'slot'       => $proof['slot'],
		'start'      => $proof['start'],
		'end'        => $proof['end'],
		'html'       => $html,
	);
}

function cp_project( $source, $content, $proofs, $placements, $replacements ) {
	return pfoa_theme_project_managed_cards_compat( $source, $content, $proofs, $placements, $replacements );
}

cp_assert( function_exists( 'pfoa_theme_project_managed_cards_compat' ), 'coordinator function exists' );
cp_assert( defined( 'PFOA_THEME_MANAGED_CARD_COMPAT_PROJECTION_LOADED' ), 'coordinator guard constant defined' );

// (a) Complete successful composition: 2 owned (id-complete + legacy-proven
// with slug) + 1 manual preserved.
$card_a     = cp_id_card( 101, 'fluffy', 'Fluffy' );
$card_b     = cp_legacy_card( 'mittens', 'Mittens' );
$manual_html = cp_manual_card();
$content    = cp_wrap( $card_a . $manual_html . $card_b );
$proof_a    = cp_proof( $content, $card_a, 101, 'slot-a' );
$proof_b    = cp_proof( $content, $card_b, 102, 'slot-b', 'mittens', $proof_a['end'] );
$manual_span = cp_span_of( $content, $manual_html, $proof_a['end'] );
cp_assert( $manual_span['slice'] === $manual_html, 'a-manual: slice verbatim' );
$placements   = array( cp_placement_for( $proof_a ), cp_placement_for( $proof_b, true ) );
$replacements = array(
	cp_replacement_for( $proof_a, '<section data-new="a">A new</section>' ),
	cp_replacement_for( $proof_b, '<section data-new="b">B new</section>' ),
);
$result = cp_project( 20323, $content, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert_success( $result, 'a-complete' );
cp_assert( 2 === $result['replaced_count'], 'a-complete: replaced_count 2' );
cp_assert( 2 === $result['owned_count'], 'a-complete: owned_count 2' );
cp_assert( 1 === $result['manual_count'], 'a-complete: manual_count 1' );
$expected = substr( $content, 0, $proof_a['start'] )
	. '<section data-new="a">A new</section>'
	. substr( $content, $proof_a['end'], $proof_b['start'] - $proof_a['end'] )
	. '<section data-new="b">B new</section>'
	. substr( $content, $proof_b['end'] );
cp_assert( $expected === $result['projected_content'], 'a-complete: byte-exact projection' );
cp_assert( 0 === strpos( $result['projected_content'], '<p>Intro</p>' ), 'a-complete: prefix at offset zero' );
cp_assert( '</p>' === substr( $result['projected_content'], strlen( $result['projected_content'] ) - 4, 4 ), 'a-complete: suffix tail kept' );
cp_assert( false !== strpos( $result['projected_content'], $manual_html ), 'a-complete: manual preserved' );
cp_assert(
	strpos( $result['projected_content'], $manual_html ) === strpos( $expected, $manual_html ),
	'a-complete: manual position kept'
);
cp_assert(
	strpos( $result['projected_content'], '<section data-new="a">' ) < strpos( $result['projected_content'], $manual_html ),
	'a-complete: ordering a < manual'
);
cp_assert(
	strpos( $result['projected_content'], $manual_html ) < strpos( $result['projected_content'], '<section data-new="b">' ),
	'a-complete: ordering manual < b'
);
cp_assert( false === strpos( $result['projected_content'], 'data-pfoa-profile-id="101"' ), 'a-complete: stored slice A gone' );
cp_pass( 'a-complete successful composition with 2 owned + 1 manual, byte-exact prefix/suffix/editorial, ordering, counts' );

// Digit-string source is accepted and matches.
$result_str = cp_project( '20323', $content, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert_success( $result_str, 'a-string-source' );
cp_assert( $expected === $result_str['projected_content'], 'a-string-source: same bytes' );
cp_assert( 20323 === $result_str['source_page_id'], 'a-string-source: normalised int id' );
cp_pass( 'a-string-source digit-string accepted with int normalisation' );

// Trimmed digit-string source is accepted.
$result_trim = cp_project( '  20323  ', $content, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert_success( $result_trim, 'a-trimmed-source' );
cp_assert( $expected === $result_trim['projected_content'], 'a-trimmed-source: same bytes' );
cp_pass( 'a-trimmed-source whitespace-padded source accepted' );

// Unsupported source fails before any pipeline work.
$bad_source = cp_project( 99999, $content, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert_failure( $bad_source, 'request', 'a-bad-source' );
cp_pass( 'a-bad-source unsupported source rejected' );

// (b) Manual content preservation with unicode bytes.
$unicode_text = 'Müller — café ☃ Ñoño';
$unicode_manual = cp_manual_card( $unicode_text );
$content_u    = cp_wrap( $card_a . $unicode_manual );
$proof_u      = cp_proof( $content_u, $card_a, 101, 'slot-a' );
$placements_u = array( cp_placement_for( $proof_u ) );
$replacements_u = array( cp_replacement_for( $proof_u, '<u>U-new</u>' ) );
$result_u = cp_project( 20323, $content_u, array( $proof_u ), $placements_u, $replacements_u );
cp_assert_success( $result_u, 'b-unicode' );
cp_assert( 1 === $result_u['replaced_count'], 'b-unicode: replaced_count 1' );
cp_assert( 1 === $result_u['manual_count'], 'b-unicode: manual_count 1' );
$expected_u = substr( $content_u, 0, $proof_u['start'] ) . '<u>U-new</u>' . substr( $content_u, $proof_u['end'] );
cp_assert( $expected_u === $result_u['projected_content'], 'b-unicode: byte-exact projection' );
cp_assert( false !== strpos( $result_u['projected_content'], $unicode_text ), 'b-unicode: unicode bytes preserved' );
cp_assert( false !== strpos( $result_u['projected_content'], $unicode_manual ), 'b-unicode: unicode manual slice kept' );
cp_assert( 0 === strpos( $result_u['projected_content'], '<p>Intro</p>' ), 'b-unicode: prefix kept' );
cp_assert( '</p>' === substr( $result_u['projected_content'], strlen( $result_u['projected_content'] ) - 4, 4 ), 'b-unicode: suffix kept' );
cp_assert( $proof_u['end'] - $proof_u['start'] === strlen( $proof_u['slice'] ), 'b-unicode: byte length via strlen' );
cp_pass( 'b-unicode manual preservation with multibyte bytes' );

// (c) Incorrect proofs reject: fingerprint mismatch and slice mismatch.
$bad_fp          = $proof_a;
$bad_fp['fingerprint'] = '00000000000000000000000000000000';
cp_assert( $bad_fp['fingerprint'] !== md5( $proof_a['slice'] ), 'c-fp: fingerprint actually wrong' );
$result_c1 = cp_project( 20323, $content, array( $bad_fp, $proof_b ), $placements, $replacements );
cp_assert_failure( $result_c1, 'classify', 'c-fingerprint' );
cp_pass( 'c-fingerprint mismatch rejected' );

$bad_slice          = $proof_a;
$bad_slice['slice'] = $proof_a['slice'] . ' ';
$result_c2 = cp_project( 20323, $content, array( $bad_slice, $proof_b ), $placements, $replacements );
cp_assert_failure( $result_c2, 'classify', 'c-slice' );
cp_pass( 'c-slice mismatch rejected' );

// (d) Missing placements reject.
$result_d = cp_project( 20323, $content, array( $proof_a, $proof_b ), array( $placements[0] ), $replacements );
cp_assert_failure( $result_d, 'validate', 'd-missing-placement' );
cp_pass( 'd-missing placement rejected' );

// (e) Overlapping/stale spans reject: shift content by 1 byte so every stored
// offset goes stale.
$stale_content = ' ' . $content;
$result_e = cp_project( 20323, $stale_content, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert( false === $result_e['ok'], 'e-stale: ok false' );
cp_assert( null === $result_e['projected_content'], 'e-stale: no partial HTML' );
cp_assert( isset( $result_e['error']['stage'] ) && is_string( $result_e['error']['stage'] ), 'e-stale: stage present' );
cp_pass( 'e-stale 1-byte shift rejected with no partial HTML' );

// (f) Duplicate replacements reject.
$dup_records = array( $replacements[0], $replacements[0] );
$result_f = cp_project( 20323, $content, array( $proof_a, $proof_b ), $placements, $dup_records );
cp_assert_failure( $result_f, 'splice', 'f-duplicate' );
cp_pass( 'f-duplicate replacements rejected' );

// (g) Malformed content rejects (no container).
$result_g = cp_project( 20323, '<p>No wrapper here</p>', array(), array(), array() );
cp_assert_failure( $result_g, 'inventory', 'g-no-container' );
cp_pass( 'g-malformed content with no container rejected' );

// Non-string content rejects at request stage.
$result_g2 = cp_project( 20323, null, array( $proof_a, $proof_b ), $placements, $replacements );
cp_assert_failure( $result_g2, 'request', 'g-invalid-content' );
cp_pass( 'g-invalid non-string content rejected' );

// (h) Downstream validator failures reject with no partial HTML.
$wrong_slug_placements   = $placements;
$wrong_slug_placements[1] = array(
	'profile_id' => $proof_b['profile_id'],
	'slot'       => $proof_b['slot'],
	'start'      => $proof_b['start'],
	'end'        => $proof_b['end'],
	'slug'       => 'wrong-slug',
);
$result_h1 = cp_project( 20323, $content, array( $proof_a, $proof_b ), $wrong_slug_placements, $replacements );
cp_assert_failure( $result_h1, 'validate', 'h-slug-mismatch' );
cp_pass( 'h-slug mismatch rejected with null output' );

$extra_placement = array(
	'profile_id' => '999',
	'slot'       => 'slot-ghost',
	'start'      => $proof_a['start'],
	'end'        => $proof_a['end'],
);
$result_h2 = cp_project(
	20323,
	$content,
	array( $proof_a, $proof_b ),
	array( $placements[0], $placements[1], $extra_placement ),
	$replacements
);
cp_assert_failure( $result_h2, 'validate', 'h-extra-placement' );
cp_pass( 'h-extra placement rejected with null output' );

// (i) No writes/hooks/public activation: banned tokens absent in coordinator file.
$compat_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-managed-card-compat-projection.php' );
cp_assert( '' !== $compat_source, 'i-source: coordinator source readable' );
$ban_check = str_replace( 'profile_id', '', $compat_source );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_', 'wp_enqueue', 'get_posts', 'WP_Query', 'get_option', 'update_option', 'wp_insert', 'wp_update', '$wpdb', 'file_', 'unlink', 'do_action', 'apply_filters' ) as $needle ) {
	cp_assert( false === strpos( $ban_check, $needle ), 'coordinator file has no ' . $needle );
}
cp_pass( 'i-bans coordinator file carries no hooks/storage/writes tokens' );

// (j) The coordinator file stays inactive: functions.php must not require it.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
cp_assert( false === strpos( $functions_source, 'theme-managed-card-compat-projection' ), 'functions.php does not require projection file' );
cp_pass( 'j-inactive functions.php does not require projection file' );

// (k) The coordinator file defines only expected symbols.
cp_assert( true === defined( 'PFOA_THEME_MANAGED_CARD_COMPAT_PROJECTION_LOADED' ), 'k-guard: constant defined' );
$found = array();
if ( 1 === preg_match_all( '/function\s+([A-Za-z0-9_]+)/', $compat_source, $matches ) && isset( $matches[1] ) ) {
	$found = $matches[1];
}
cp_assert( array( 'pfoa_theme_project_managed_cards_compat' ) === array_values( $found ), 'k-symbols: only coordinator function defined' );
cp_pass( 'k-symbols coordinator file defines only expected symbols' );

fwrite( STDOUT, "PASS: theme managed card compat projection fixture (complete 2-owned+manual composition byte-exact, unicode manual, bad proofs, missing placements, 1-byte stale shift, duplicate replacements, malformed content, slug/extra validator failures with null output, no hooks/writes, inactive, expected symbols only)\n" );
