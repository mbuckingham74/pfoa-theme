<?php
/**
 * Focused fixture for the sitewide Page-title restoration contract
 * (PFOA 0.1.162, OPT-OUT shared-path fix).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: pfoa_should_render_page_title() renders on standard
 * Pages; all 5 exclusion IDs skip; slug fallback skips (defence in depth);
 * stored content <h1> (bare, with attributes, uppercase) suppresses;
 * is_front_page() suppresses; safe outside the loop (null post); filter
 * pfoa_page_title_excluded_pages overrides the exclusion set;
 * content-page.php source contract (conditional header markup, the_title(),
 * exactly one entry-header block); style.css version 0.1.162; no
 * display:none on .entry-header/.entry-title/h1.
 *
 * Run with:
 *   php tests/theme-adoption-page-title-fixture.php
 *
 * Exits nonzero on any failure.
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
		/** @var string */
		public $post_content = '';
	}
}

$GLOBALS['fixture_is_front_page']    = false;
$GLOBALS['fixture_post']              = null;
$GLOBALS['fixture_filter_override']   = null;
$GLOBALS['fixture_db_touched']        = false;
$GLOBALS['fixture_hook_registered']   = false;

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_shortcode( $tag, $callback ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function apply_filters( $hook, $value ) {
	if ( 'pfoa_page_title_excluded_pages' === $hook && is_array( $GLOBALS['fixture_filter_override'] ) ) {
		return $GLOBALS['fixture_filter_override'];
	}
	return $value;
}
function is_front_page() {
	return (bool) $GLOBALS['fixture_is_front_page'];
}
function get_post( $post = null ) {
	if ( null !== $post ) {
		return null;
	}
	return $GLOBALS['fixture_post'];
}
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_make_post( $id, $slug, $content ) {
	$post               = new WP_Post();
	$post->ID           = (int) $id;
	$post->post_type    = 'page';
	$post->post_name    = (string) $slug;
	$post->post_content = (string) $content;
	return $post;
}

function fixture_standard_setup() {
	$GLOBALS['fixture_is_front_page']  = false;
	$GLOBALS['fixture_filter_override'] = null;
	$GLOBALS['fixture_post']            = fixture_make_post( 123, 'about', '<p>Hello.</p>' );
}

fixture_assert( function_exists( 'pfoa_should_render_page_title' ), 'helper exists' );

// Standard page renders.
fixture_standard_setup();
fixture_assert( true === pfoa_should_render_page_title(), 'standard page renders title' );

// All 5 exclusion IDs skip (defence: ID match alone suffices).
foreach ( array( 5421, 4159, 5656, 20323, 22514 ) as $excluded_id ) {
	$GLOBALS['fixture_is_front_page']   = false;
	$GLOBALS['fixture_filter_override'] = null;
	$GLOBALS['fixture_post']             = fixture_make_post( $excluded_id, 'renamed-slug', '<p>Body.</p>' );
	fixture_assert( false === pfoa_should_render_page_title(), 'exclusion ID ' . $excluded_id . ' skips' );
}

// Slug fallback skips when the ID does not match (re-imports/renames).
foreach ( array( 'inmemoriam', 'sponsoredanimals', 'adoptionstories', 'adoptablecats2', 'adopted2026' ) as $excluded_slug ) {
	$GLOBALS['fixture_is_front_page']   = false;
	$GLOBALS['fixture_filter_override'] = null;
	$GLOBALS['fixture_post']             = fixture_make_post( 9999, (string) $excluded_slug, '<p>Body.</p>' );
	fixture_assert( false === pfoa_should_render_page_title(), 'exclusion slug ' . $excluded_slug . ' skips' );
}

// Stored content <h1> suppresses (bare, with attributes, uppercase).
foreach ( array(
	'<h1>Meet Our Adoptable Cats</h1>',
	'<h1 class="wp-block-heading">2026 ADOPTIONS</h1>',
	'<H1>Uppercase</H1>',
	'<p>Intro</p><h1 id="x">Later H1</h1>',
) as $index => $content ) {
	$GLOBALS['fixture_is_front_page']   = false;
	$GLOBALS['fixture_filter_override'] = null;
	$GLOBALS['fixture_post']             = fixture_make_post( 7777, 'future-h1-page', (string) $content );
	fixture_assert( false === pfoa_should_render_page_title(), 'content h1 case ' . $index . ' suppresses' );
}

// A content h2 alone still renders (only h1 suppresses).
$GLOBALS['fixture_is_front_page']   = false;
$GLOBALS['fixture_filter_override'] = null;
$GLOBALS['fixture_post']             = fixture_make_post( 7778, 'h2-page', '<h2>Section</h2><p>Body.</p>' );
fixture_assert( true === pfoa_should_render_page_title(), 'content h2 still renders' );

// Front page suppresses even for an ordinary page.
$GLOBALS['fixture_is_front_page']   = true;
$GLOBALS['fixture_filter_override'] = null;
$GLOBALS['fixture_post']             = fixture_make_post( 123, 'about', '<p>Hello.</p>' );
fixture_assert( false === pfoa_should_render_page_title(), 'front page suppresses' );

// Safe outside the loop: null post still returns a bool (true when not front).
$GLOBALS['fixture_is_front_page']   = false;
$GLOBALS['fixture_filter_override'] = null;
$GLOBALS['fixture_post']            = null;
fixture_assert( true === pfoa_should_render_page_title(), 'null post outside loop is safe and renders' );

// Filter overrides the exclusion set: custom ID excluded, defaults lifted.
$GLOBALS['fixture_is_front_page']   = false;
$GLOBALS['fixture_filter_override'] = array( 123 );
$GLOBALS['fixture_post']             = fixture_make_post( 123, 'about', '<p>Hello.</p>' );
fixture_assert( false === pfoa_should_render_page_title(), 'filter: custom excluded ID skips' );
$GLOBALS['fixture_post'] = fixture_make_post( 5421, 'renamed-slug', '<p>Body.</p>' );
fixture_assert( true === pfoa_should_render_page_title(), 'filter: default ID no longer excluded after override' );
$GLOBALS['fixture_filter_override'] = array();
$GLOBALS['fixture_post']             = fixture_make_post( 5421, 'renamed-slug', '<p>Body.</p>' );
fixture_assert( true === pfoa_should_render_page_title(), 'filter: empty exclusion set renders everywhere' );
$GLOBALS['fixture_filter_override'] = null;

// content-page.php source contract: conditional title header above entry-content.
$part_path = dirname( __DIR__ ) . '/pfoa-theme/template-parts/content-page.php';
fixture_assert( file_exists( $part_path ), 'part: content-page.php exists' );
$part = (string) file_get_contents( $part_path );
fixture_assert( false !== strpos( $part, 'pfoa_should_render_page_title' ), 'part: conditional helper gate present' );
fixture_assert( false !== strpos( $part, '<header class="entry-header">' ), 'part: entry-header markup present' );
fixture_assert( false !== strpos( $part, '<h1 class="entry-title"><?php the_title(); ?></h1>' ), 'part: the_title() h1 present (content-single.php precedent)' );
fixture_assert( 1 === substr_count( $part, '<header class="entry-header">' ), 'part: exactly one entry-header block (no duplicate header output)' );
fixture_assert( 1 === substr_count( $part, 'entry-header' ), 'part: single entry-header reference total' );
$gate_pos    = strpos( $part, 'pfoa_should_render_page_title' );
$content_pos = strpos( $part, '<div class="entry-content">' );
fixture_assert( false !== $gate_pos && false !== $content_pos && $gate_pos < $content_pos, 'part: title header renders above .entry-content' );

// style.css: version 0.1.195, and no display:none touches the title header.
	$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
	fixture_assert( 1 === preg_match( '/Version:\s*0\.1\.195/', $css ), 'css: theme version 0.1.195' );
fixture_assert( false !== strpos( $css, '.page-content .entry-header' ), 'css: existing page-content entry-header styling intact' );
$blocks = explode( '}', $css );
foreach ( $blocks as $block ) {
	$pieces = explode( '{', $block, 2 );
	if ( 2 !== count( $pieces ) ) {
		continue;
	}
	$selector = strtolower( $pieces[0] );
	$body     = strtolower( $pieces[1] );
	if ( false === strpos( $body, 'display' ) || false === strpos( $body, 'none' ) ) {
		continue;
	}
	if ( ! preg_match( '/display\s*:\s*none/', $body ) ) {
		continue;
	}
	fixture_assert( false === strpos( $selector, 'entry-header' ), 'css: no display:none on .entry-header (' . trim( substr( $selector, -80 ) ) . ')' );
	fixture_assert( false === strpos( $selector, 'entry-title' ), 'css: no display:none on .entry-title (' . trim( substr( $selector, -80 ) ) . ')' );
	fixture_assert( ! preg_match( '/(^|[\s,>+~])h1(\s|$|[.,:#\[])/', $selector ), 'css: no display:none on h1 (' . trim( substr( $selector, -80 ) ) . ')' );
}

// No writes during helper evaluation.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during title gating' );

 fwrite( STDOUT, "PASS: theme adoption page title fixture (standard renders, 5 ID + 5 slug exclusions, content-h1 suppression, front-page suppression, null-post safety, filter override, part markup single header, css 0.1.195 no display:none, no writes)\n" );
