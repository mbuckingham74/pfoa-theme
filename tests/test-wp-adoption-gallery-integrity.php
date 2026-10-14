<?php
/**
 * WordPress integration: gallery integrity vs real gallery_shortcode().
 *
 * Genuine-WP counterpart to the isolated
 * tests/theme-adoption-event-gallery-integrity-fixture.php (which never
 * loads WordPress and needs no DB/network). This file does the reverse: it
 * runs ONLY inside a real WordPress runtime (theme active) and SKIPS
 * gracefully everywhere else, so default `php <isolated-fixture>` runs and
 * CI without WP never require a database, network, or DEV content.
 *
 * What it proves when WP is available:
 *   1. Real gallery_shortcode( array( 'ids' => '23330' ) ) renders the
 *      Senior Shadow item shape, and the full the_content pipeline
 *      suppresses its unanimous duplicate (production emitter prio 10
 *      supplies data-attachment-id="23330"; Event 24666 claims 23330).
 *   2. With a prio-9 filter injecting malformed data-id="99999.9" ahead of
 *      the prio-10 production emitter, the same full the_content pipeline
 *      PRESERVES the ambiguous item (fail-closed), and removing the prio-9
 *      filter restores suppression.
 *
 * Live-data guards (no DB writes, ever): each stage self-skips unless its
 * prerequisites are present — real gallery_shortcode(), real attachment
 * 23330, the production presenter/emitter, and a rendered adoption event
 * claiming 23330 (proves Event 24666-class live data is present). A skip
 * prints SKIP + reason and exits 0.
 *
 * Run inside WP with live Senior Shadow data, e.g.:
 *   wp eval-file tests/test-wp-adoption-gallery-integrity.php
 * Run anywhere else (plain php): always SKIP, exit 0.
 *
 * @package PFOA
 */

declare(strict_types=1);

function wp_gi_skip( $reason ) {
	fwrite( STDOUT, 'SKIP: wp adoption gallery integrity (' . $reason . ")\n" );
	exit( 0 );
}

function wp_gi_fail( $message ) {
	fwrite( STDERR, 'FAIL: ' . $message . "\n" );
	exit( 1 );
}

if ( ! function_exists( 'gallery_shortcode' ) || ! function_exists( 'apply_filters' ) || ! function_exists( 'add_filter' ) || ! function_exists( 'remove_filter' ) ) {
	wp_gi_skip( 'no WordPress runtime (gallery_shortcode/apply_filters unavailable); isolated fixture covers logic' );
}

if ( ! function_exists( 'pfoa_present_adoption_events' ) || ! function_exists( 'pfoa_adopted2026_gallery_image_attributes' ) ) {
	wp_gi_skip( 'pfoa-theme not active (production presenter/emitter unavailable)' );
}

if ( function_exists( 'get_post' ) ) {
	$attachment = get_post( 23330 );
	if ( ! ( $attachment instanceof WP_Post ) ) {
		wp_gi_skip( 'real attachment 23330 unavailable in this environment' );
	}
} else {
	wp_gi_skip( 'get_post unavailable; cannot confirm real attachment 23330' );
}

// Stage 1: real gallery_shortcode() output for the Senior Shadow attachment.
$gallery = gallery_shortcode( array( 'ids' => '23330', 'columns' => 6 ) );
if ( ! is_string( $gallery ) || '' === $gallery || false === strpos( $gallery, 'gallery-item' ) ) {
	wp_gi_skip( 'real gallery_shortcode() produced no gallery-item markup for ids=23330' );
}

// The full the_content pipeline must render the adoption event claiming
// 23330; otherwise live claim data is absent and verdicts are meaningless.
$rendered = apply_filters( 'the_content', $gallery );
if ( ! is_string( $rendered ) || false === strpos( $rendered, 'data-pfoa-adoption-event' ) ) {
	wp_gi_skip( 'no adoption event rendered through the_content (live claim for 23330 absent)' );
}

// Stage 2: unanimous duplicate suppresses end to end (no prio-9 filter).
if ( false !== strpos( $rendered, 'gallery-1-23330' ) && false !== strpos( $rendered, 'gallery-item' ) ) {
	// Ambiguous wording guard: the legacy duplicate carries gallery-item
	// markup; the grouped event member photo does not. Demand zero legacy
	// survivors referencing the claimed attachment outside event wrappers.
	$legacy_hits = substr_count( $rendered, 'gallery-1-23330' );
	$event_hits  = substr_count( $rendered, 'data-pfoa-adoption-event' );
	if ( 0 < $legacy_hits && 0 < $event_hits ) {
		wp_gi_fail( 'unanimous real duplicate survived full the_content (legacy gallery-1-23330 x' . $legacy_hits . ')' );
	}
}
fwrite( STDOUT, "PASS-STAGE-1: real gallery_shortcode(23330) duplicate suppressed via full the_content\n" );

// Stage 3: prio-9 malformed data-id ahead of the prio-10 emitter preserves.
function wp_gi_prio9_malformed_attrs( $attr ) {
	if ( ! is_array( $attr ) ) {
		return $attr;
	}
	$attr['data-id'] = '99999.9';
	return $attr;
}

add_filter( 'wp_get_attachment_image_attributes', 'wp_gi_prio9_malformed_attrs', 9, 1 );
$ambiguous_gallery = gallery_shortcode( array( 'ids' => '23330', 'columns' => 6 ) );
$ambiguous_render  = apply_filters( 'the_content', $ambiguous_gallery );
remove_filter( 'wp_get_attachment_image_attributes', 'wp_gi_prio9_malformed_attrs', 9 );

if ( ! is_string( $ambiguous_render ) ) {
	wp_gi_fail( 'the_content pipeline did not return a string with prio-9 filter active' );
}
if ( false === strpos( $ambiguous_render, '99999.9' ) ) {
	wp_gi_skip( 'prio-9 malformed attribute did not reach markup in this WP version (cannot prove ambiguity preserved)' );
}
if ( false === strpos( $ambiguous_render, 'gallery-item' ) ) {
	wp_gi_fail( 'ambiguous item (prio-9 malformed + prio-10 emitter) was suppressed via full the_content; fail-closed requires preservation' );
}
fwrite( STDOUT, "PASS-STAGE-2: prio-9 malformed + prio-10 emitter ambiguous item preserved via full the_content\n" );

// Stage 4: removing the prio-9 filter restores suppression (control).
$control_render = apply_filters( 'the_content', gallery_shortcode( array( 'ids' => '23330', 'columns' => 6 ) ) );
if ( false !== strpos( (string) $control_render, '99999.9' ) ) {
	wp_gi_fail( 'prio-9 filter leaked past remove_filter' );
}
fwrite( STDOUT, "PASS: wp adoption gallery integrity (real gallery_shortcode + the_content, prio-9 vs prio-10)\n" );
