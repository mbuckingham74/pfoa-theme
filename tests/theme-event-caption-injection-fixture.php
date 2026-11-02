<?php
/**
 * Event-caption injection fixture: F01 stored-caption attribute injection
 * (PFOA 0.1.278).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency.
 *
 * Defect: pfoa_adoption_event_member_media() HTML-escaped the caption with
 * esc_attr() and then concatenated it into a preg_replace() REPLACEMENT
 * string. esc_attr() neutralizes markup delimiters but leaves "$1",
 * "${1}", "$0", and backslash sequences intact, and preg_replace()
 * interprets those as backreferences AFTER escaping. The group-1 match is
 * the quote delimiter itself, so a caption such as
 *   $1 onmouseover=$1alert(1)$1
 * expanded into  alt="" onmouseover="alert(1)""  — a stored event caption
 * rendering as an executable image attribute on Adopted 2026 / Recently
 * Adopted output.
 *
 * Fix: both inner substitutions build their replacement inside a
 * preg_replace_callback() callback, so caption text is never replacement-
 * program syntax. The data-pfoa-caption substitution already used a
 * callback and is covered here as a no-regression guard.
 *
 * RED/GREEN contract:
 *   On 0.1.277 logic this fixture FAILS (RED, exit 1) and on 0.1.278 it
 *   PASSES (GREEN, exit 0). RED was verified by stashing the functions.php
 *   fix and running this fixture against the 0.1.277 tree:
 *     git stash push -- pfoa-theme/functions.php
 *     php tests/theme-event-caption-injection-fixture.php
 *     # -> exit 1, first line: FAIL: hostile caption '...' produced an
 *     #    event-handler attribute
 *     git stash pop
 *     php tests/theme-event-caption-injection-fixture.php
 *     # -> PASS (this run, exit 0)
 *
 * Coverage:
 *   H1-H5 hostile replacement-metachar captions ($1, $0, ${1}, \1,
 *      repeated) on BOTH image branches (existing alt / no alt): no
 *      attribute starting with "on" may appear on any element.
 *   Q1 plain quote-breakout caption (no metacharacters): still neutralized
 *      by esc_attr(), no handler attribute.
 *   L1-L6 legitimate captions (punctuation, quotes, ampersand, dollar
 *      amounts, backslashes, unicode): DOM-decoded alt and
 *      data-pfoa-caption round-trip EXACTLY to the input caption.
 *   E1 empty caption: linked markup passes through untouched.
 *   S1 source guard: no plain preg_replace() with an alt/img pattern
 *      remains anywhere in functions.php.
 *
 * Run with:
 *   php tests/theme-event-caption-injection-fixture.php
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
	}
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_shortcode( $tag, $callback ) {}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function wp_attachment_is_image( $attachment_id ) {
	return true;
}
function wp_get_attachment_link( $attachment_id, $size = 'thumbnail', $permalink = false, $icon = false ) {
	return (string) $GLOBALS['fixture_link_markup'];
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

/**
 * Render one caption through production code and return parsed elements.
 *
 * @param string $caption  Raw stored caption.
 * @param string $img_tag  Inner <img> markup to embed in the stub link.
 * @return array{html: string, img: DOMElement, anchor: DOMElement}
 */
function fixture_render_caption( $caption, $img_tag ) {
	$GLOBALS['fixture_link_markup'] = '<a href="https://example.com/full.jpg">' . $img_tag . '</a>';
	$html = pfoa_adoption_event_member_media( 123, (string) $caption );
	fixture_assert( is_string( $html ), 'harness: renderer returns string' );

	$dom = new DOMDocument( '1.0', 'UTF-8' );
	// XML-encoding preamble forces libxml to treat the fragment as UTF-8
	// (loadHTML otherwise assumes ISO-8859-1 and mangles multibyte
	// captions on getAttribute round-trips).
	$ok  = @$dom->loadHTML( '<?xml encoding="utf-8" ?><html><body><div id="w">' . $html . '</div></body></html>' );
	fixture_assert( $ok, 'harness: output parses as HTML' );
	$xp     = new DOMXPath( $dom );
	$imgs   = $xp->query( '//*[@id="w"]//img' );
	$anchors = $xp->query( '//*[@id="w"]//a' );
	fixture_assert( $imgs instanceof DOMNodeList && 1 === $imgs->length, 'harness: exactly one <img> in output' );
	fixture_assert( $anchors instanceof DOMNodeList && 1 === $anchors->length, 'harness: exactly one <a> in output' );
	$img = $imgs->item( 0 );
	$anchor = $anchors->item( 0 );
	fixture_assert( $img instanceof DOMElement && $anchor instanceof DOMElement, 'harness: elements found' );
	return array( 'html' => $html, 'img' => $img, 'anchor' => $anchor );
}

/**
 * Assert no element in the rendered output carries an event-handler
 * attribute (the F01 exploit shape), regardless of caption content.
 */
function fixture_assert_no_handler( $html, $label ) {
	$dom = new DOMDocument( '1.0', 'UTF-8' );
	$ok  = @$dom->loadHTML( '<?xml encoding="utf-8" ?><html><body><div id="w">' . $html . '</div></body></html>' );
	fixture_assert( $ok, $label . ': output parses' );
	$xp = new DOMXPath( $dom );
	$els = $xp->query( '//*[@id="w"]//*' );
	fixture_assert( $els instanceof DOMNodeList, $label . ': xpath runs' );
	foreach ( $els as $el ) {
		fixture_assert( $el instanceof DOMElement, $label . ': node is element' );
		foreach ( $el->attributes as $attr ) {
			fixture_assert(
				0 !== stripos( (string) $attr->nodeName, 'on' ),
				$label . ": hostile caption produced an event-handler attribute ({$attr->nodeName})"
			);
		}
	}
}

$img_with_alt = '<img src="https://example.com/med.jpg" alt="old caption" width="300" height="200" />';
$img_no_alt   = '<img src="https://example.com/med.jpg" width="300" height="200" />';

// H1-H5: hostile replacement-program captions, both image branches.
$hostile = array(
	'H1 group-1 quote breakout' => '$1 onmouseover=$1alert(1)$1',
	'H2 whole-match breakout'   => '$0 onfocus=$0alert(1)$0',
	'H3 braced group breakout'  => '${1}onmouseover=${1}alert(1)',
	'H4 backslash group'        => '\\1 onclick=\\1alert(1)\\1',
	'H5 repeated reference'     => '$1$1$1 onload=$1alert(1)$1',
);
foreach ( $hostile as $label => $caption ) {
	foreach ( array( 'with-alt' => $img_with_alt, 'no-alt' => $img_no_alt ) as $branch => $img_tag ) {
		$r = fixture_render_caption( $caption, $img_tag );
		fixture_assert_no_handler( $r['html'], $label . " [{$branch}]" );
		// The hostile text must survive only as inert alt text, byte-identical.
		fixture_assert(
			$r['img']->getAttribute( 'alt' ) === $caption,
			$label . " [{$branch}]: alt round-trips hostile caption as inert text"
		);
		fixture_assert(
			$r['anchor']->getAttribute( 'data-pfoa-caption' ) === $caption,
			$label . " [{$branch}]: data-pfoa-caption round-trips hostile caption as inert text"
		);
	}
}

// Q1: plain quote breakout without metacharacters (esc_attr layer).
$r = fixture_render_caption( '" onmouseover="alert(1)', $img_with_alt );
fixture_assert_no_handler( $r['html'], 'Q1 plain quote breakout' );
fixture_assert(
	$r['img']->getAttribute( 'alt' ) === '" onmouseover="alert(1)',
	'Q1: alt round-trips quoted caption as inert text'
);

// L1-L6: legitimate editorial captions preserved exactly, both branches.
$legit = array(
	'L1 punctuation' => 'Whiskers (Kitten 4040) — the explorer!',
	'L2 quotes/amp'  => 'Tom & Jerry — "the explorers" <3',
	'L3 dollars'     => '$5 adoption fee; $10 reward (no tricks)',
	'L4 backslash'   => 'C:\\cats\\pics\\whiskers.jpg',
	'L5 unicode'     => 'Møøse — señor gato ✓',
	'L6 percents'    => '100% good boy (yes, 100%)',
);
foreach ( $legit as $label => $caption ) {
	foreach ( array( 'with-alt' => $img_with_alt, 'no-alt' => $img_no_alt ) as $branch => $img_tag ) {
		$r = fixture_render_caption( $caption, $img_tag );
		fixture_assert_no_handler( $r['html'], $label . " [{$branch}]" );
		fixture_assert(
			$r['img']->getAttribute( 'alt' ) === $caption,
			$label . " [{$branch}]: alt preserves legitimate caption"
		);
		fixture_assert(
			$r['anchor']->getAttribute( 'data-pfoa-caption' ) === $caption,
			$label . " [{$branch}]: data-pfoa-caption preserves legitimate caption"
		);
	}
}

// E1: empty caption leaves linked markup untouched.
$GLOBALS['fixture_link_markup'] = '<a href="https://example.com/full.jpg">' . $img_with_alt . '</a>';
$untouched = pfoa_adoption_event_member_media( 123, '' );
fixture_assert(
	$untouched === (string) $GLOBALS['fixture_link_markup'],
	'E1: empty caption passes linked markup through untouched'
);

// S1: source guard — no plain preg_replace() with an alt/img pattern
// remains (caption text must never be a replacement string).
$src = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( '' !== $src, 'S1: functions.php readable' );
fixture_assert(
	false === strpos( $src, "preg_replace( '/\\salt" ),
	'S1: no plain preg_replace() alt substitution remains'
);
fixture_assert(
	false === strpos( $src, "preg_replace( '/<img" ),
	'S1: no plain preg_replace() img substitution remains'
);

fwrite( STDOUT, "PASS: theme event caption injection fixture (0.1.278 callback replacement; H1-H5/Q1 inert on both branches; L1-L6 captions byte-preserved; empty passthrough; no plain preg_replace alt/img substitution)\n" );
