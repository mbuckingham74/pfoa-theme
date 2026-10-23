<?php
/**
 * Focused fixture for the Potholders page content compatibility contract
 * (PFOA 0.1.217, page 17894 / slug potholders-2 only).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: pfoa_potholders_content_compat() mailto repair for a
 * bare scram@gmail.com address (already-linked addresses untouched,
 * idempotent); exact approved wording of both team paragraphs; legacy
 * gallery moved before the OUR MICROWAVEABLE BOWLS heading; fail-closed
 * passthrough when markers are absent; strict page scoping (other pages,
 * including Dine Out, Pet Tidings page 4799, and Home Front page 11290,
 * pass through byte-identical); admin/feed/loop guards; style.css version
 * 0.1.217 with a page-id-17894-scoped gallery matte (object-fit cover,
 * neutral background, no image removal); and no DB writes.
 *
 * Run with:
 *   php tests/theme-potholders-content-fixture.php
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

$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_is_admin']        = false;
$GLOBALS['fixture_is_feed']         = false;
$GLOBALS['fixture_in_loop']         = true;
$GLOBALS['fixture_is_main_query']   = true;
$GLOBALS['fixture_page_id']         = 0;
$GLOBALS['fixture_page_slug']       = '';

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
function is_admin() {
	return (bool) $GLOBALS['fixture_is_admin'];
}
function is_feed() {
	return (bool) $GLOBALS['fixture_is_feed'];
}
function in_the_loop() {
	return (bool) $GLOBALS['fixture_in_loop'];
}
function is_main_query() {
	return (bool) $GLOBALS['fixture_is_main_query'];
}
function is_page( $page = '' ) {
	if ( is_int( $page ) || ( is_string( $page ) && '' !== $page && preg_match( '/^\d+$/', $page ) ) ) {
		return (int) $page === (int) $GLOBALS['fixture_page_id'];
	}
	if ( is_string( $page ) && '' !== $page ) {
		return strtolower( $page ) === strtolower( (string) $GLOBALS['fixture_page_slug'] );
	}
	return false;
}
function get_post( $post = null ) {
	if ( null !== $post ) {
		return null;
	}
	$page            = new WP_Post();
	$page->ID        = (int) $GLOBALS['fixture_page_id'];
	$page->post_type = 'page';
	$page->post_name = (string) $GLOBALS['fixture_page_slug'];
	return $page;
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_target_context( $id, $slug ) {
	$GLOBALS['fixture_is_admin']      = false;
	$GLOBALS['fixture_is_feed']      = false;
	$GLOBALS['fixture_in_loop']      = true;
	$GLOBALS['fixture_is_main_query'] = true;
	$GLOBALS['fixture_page_id']      = (int) $id;
	$GLOBALS['fixture_page_slug']    = (string) $slug;
}

fixture_assert( function_exists( 'pfoa_potholders_content_compat' ), 'helper exists' );

$rsquo = "’";

// Stored-style source mirroring live DEV: curly apostrophes, old
// year/amount, "pot holders" (two words), one bare address, one linked
// address, and the gallery trailing the microwave-bowls copy.
$source  = '<h4><span><strong>OUR POTHOLDER TEAM</strong></span></h4>';
$source .= '<p><span>The money we raise by making and selling our pot holders helps support PFOA' . $rsquo . 's feline shelter, their pet food bank, and their outreach and adoption programs. (In 18 years we' . $rsquo . 've raised over $350,000!)</span></p>';
$source .= '<p><span>We have a great group of seamstresses, but we can' . $rsquo . 't keep up with demand! All materials and supplies are provided, and we will give a short training course in how to make our pot holders. And we have fun doing this great project!</span></p>';
$source .= '<p><span>If you would like more information, please call Sue Neal at 360-775-6810 / <a href="mailto:shcram@gmail.com">shcram@gmail.com</a>. Or write to scram@gmail.com any time.</span></p>';
$source .= '<h4><span><strong>OUR MICROWAVEABLE BOWLS</strong></span></h4>';
$source .= '<p><span>Specific vendors who also carry our microwaveable bowls are <span><strong>Petsense </strong>and <strong>Olympic Cellars </strong>in Port Angeles.</span></span></p>';
$source .= '<div id="gallery-1" class="gallery"><figure><p><a href="https://example.test/full.jpg"><img src="https://example.test/thumb.jpg" alt=""></a></p></figure><figure><p><a href="https://example.test/full2.jpg"><img src="https://example.test/thumb2.jpg" alt=""></a></p></figure></div>';

$para1_exact = 'The money we raise by making and selling our potholders helps support PFOA\'s feline shelter, their pet food bank, and their outreach and adoption programs. (In 21 years we\'ve raised over $405,000!)';
$para2_exact = 'We have a great group of seamstresses, but we can\'t keep up with demand! All materials and supplies are provided, and we will give a short training course in how to make our potholders. And we have fun doing this great project!';

// Target page by ID.
fixture_target_context( 17894, 'potholders-2' );
$out = pfoa_potholders_content_compat( $source );
fixture_assert( is_string( $out ), 'target: string output' );
fixture_assert( false !== strpos( $out, $para1_exact ), 'target: para1 exact wording' );
fixture_assert( false !== strpos( $out, $para2_exact ), 'target: para2 exact wording' );
fixture_assert( false === strpos( $out, 'pot holders' ), 'target: no two-word pot holders remains' );
fixture_assert( false === strpos( $out, '$350,000' ), 'target: old amount gone' );
fixture_assert( 1 === substr_count( $out, '<a href="mailto:shcram@gmail.com">shcram@gmail.com</a>' ), 'target: existing mailto untouched, not double-wrapped' );
fixture_assert( 1 === substr_count( $out, '<a href="mailto:scram@gmail.com">scram@gmail.com</a>' ), 'target: bare address gains mailto' );
$gallery_pos = strpos( $out, '<div id="gallery-1"' );
$bowls_pos   = strpos( $out, 'OUR MICROWAVEABLE BOWLS' );
fixture_assert( false !== $gallery_pos && false !== $bowls_pos, 'target: gallery and bowls heading present' );
fixture_assert( $gallery_pos < $bowls_pos, 'target: gallery moved before microwave-bowls copy' );

// Idempotent: second pass is a fixed point.
fixture_assert( $out === pfoa_potholders_content_compat( $out ), 'target: idempotent second pass' );

// Target page by slug (fresh context, non-matching ID).
fixture_target_context( 9999, 'potholders-2' );
$out_slug = pfoa_potholders_content_compat( $source );
fixture_assert( false !== strpos( $out_slug, $para1_exact ), 'slug scope: para1 corrected' );
fixture_assert( $gallery_pos === strpos( $out_slug, '<div id="gallery-1"' ) || strpos( $out_slug, '<div id="gallery-1"' ) < strpos( $out_slug, 'OUR MICROWAVEABLE BOWLS' ), 'slug scope: gallery before bowls' );

// Non-target pages pass through byte-identical (Dine Out, Pet Tidings,
// Home Front, generic).
foreach ( array(
	array( 123, 'about' ),
	array( 7777, 'dine-out' ),
	array( 4799, 'pettidings' ),
	array( 11290, 'fromthehomefront-2' ),
) as $other ) {
	fixture_target_context( $other[0], $other[1] );
	fixture_assert( $source === pfoa_potholders_content_compat( $source ), ' scope: ' . $other[1] . ' untouched' );
}

// Admin, feed, and out-of-loop contexts pass through.
fixture_target_context( 17894, 'potholders-2' );
$GLOBALS['fixture_is_admin'] = true;
fixture_assert( $source === pfoa_potholders_content_compat( $source ), 'admin: untouched' );
$GLOBALS['fixture_is_admin'] = false;
$GLOBALS['fixture_is_feed']  = true;
fixture_assert( $source === pfoa_potholders_content_compat( $source ), 'feed: untouched' );
$GLOBALS['fixture_is_feed'] = false;
$GLOBALS['fixture_in_loop'] = false;
fixture_assert( $source === pfoa_potholders_content_compat( $source ), 'out-of-loop: untouched' );
$GLOBALS['fixture_in_loop'] = true;

// Marker-free content on the target page passes through.
fixture_target_context( 17894, 'potholders-2' );
$plain = '<p><span>Just a plain paragraph.</span></p>';
fixture_assert( $plain === pfoa_potholders_content_compat( $plain ), 'target without markers: unchanged' );
fixture_assert( '' === pfoa_potholders_content_compat( '' ), 'empty: unchanged' );

// style.css: version 0.1.217 with a page-id-17894-scoped gallery matte.
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_assert( 1 === preg_match( '/Version:\s*0\.1\.217/', $css ), 'css: theme version 0.1.217' );
fixture_assert( 1 === preg_match( '/PFOA 0\.1\.217/', $css ), 'css: 0.1.217 block marker' );
fixture_assert( false !== strpos( $css, 'body.page-id-17894' ), 'css: potholders page scope present' );
fixture_assert( 1 === preg_match( '/body\.page-id-17894.*?object-fit:\s*cover/s', $css ), 'css: gallery thumbs use object-fit cover' );
fixture_assert( 1 === preg_match( '/body\.page-id-17894.*?background:\s*#f4f1ea/s', $css ), 'css: gallery neutral matte' );

// No writes during evaluation.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during evaluation' );

fwrite( STDOUT, "PASS: theme potholders content fixture (mailto repair, exact wording, gallery reorder, strict page scope, admin/feed/loop guards, css 0.1.217 matte, no writes)\n" );
