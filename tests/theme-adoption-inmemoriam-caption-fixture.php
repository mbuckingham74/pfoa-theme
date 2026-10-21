<?php
/**
 * Focused fixture for the In Memoriam parent gallery mobile caption
 * correction (PFOA 0.1.161, B-MEM-05).
 *
 * Verifies the narrowly scoped responsive CSS owned by the PFOA theme:
 * page-id-5421 photo-grid table reflows 6-col -> 3-col at <=600px and
 * caption spans override the inherited overflow-wrap:anywhere with
 * word-boundary wrapping. No network, database, DEV, or provider
 * dependency. CSS source checks only; content/presentation parity is
 * verified separately in DEV browser QA.
 *
 * Scope contract enforced here:
 * - every 5421 table.aligncenter rule is prefixed body.page-id-5421;
 * - wrapping override is overflow-wrap:break-word + word-break:normal
 *   on td span and td span a;
 * - reflow (display:grid + repeat(3,1fr)) lives inside exactly one
 *   @media (max-width:600px) block;
 * - td keeps display:block width:auto padding; img stays responsive
 *   (width:100% height:auto max-width:150px);
 * - no page-id-5421 table.aligncenter grid rules exist outside that
 *   600px media query (desktop >600px untouched).
 *
 * Run with:
 *   php tests/theme-adoption-inmemoriam-caption-fixture.php
 *
 * Exits nonzero on any failure.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$css_path = dirname( __DIR__ ) . '/pfoa-theme/style.css';
fixture_assert( file_exists( $css_path ), 'css: style.css exists' );
$css = (string) file_get_contents( $css_path );
fixture_assert( '' !== $css, 'css: style.css readable' );

// Version gate for this release (0.1.161 block preserved; header now 0.1.176).
	fixture_assert( 1 === preg_match( '/Version:\s*0\.1\.176/', $css ), 'css: theme version 0.1.176' );

// Release marker comment present.
fixture_assert( false !== strpos( $css, 'PFOA 0.1.161' ), 'css: 0.1.161 release marker present' );
fixture_assert( false !== strpos( $css, 'page-id-5421' ), 'css: page-id-5421 scope present' );

// Caption wrapping override: both span and span a selectors, break-word + normal.
fixture_assert(
	1 === preg_match( '/body\.page-id-5421[^{]*table\.aligncenter td span,\s*body\.page-id-5421[^{]*table\.aligncenter td span a\s*\{\s*overflow-wrap:\s*break-word;\s*word-break:\s*normal;/', $css ),
	'css: caption wrapping override break-word/normal on td span + span a'
);

// No page-id-5421 caption rule may retain anywhere (strip comments: the
// explanatory header names the inherited guard).
$pos_5421 = strpos( $css, '/* PFOA 0.1.161' );
fixture_assert( false !== $pos_5421, 'css: 0.1.161 block located' );
// Bound the 0.1.161 block at the next release marker so later releases
// (e.g. 0.1.171 with its own narrow queries) never leak into this scope.
$next_marker = strpos( $css, '/* PFOA 0.1.', (int) $pos_5421 + 12 );
$block_len   = false !== $next_marker ? (int) $next_marker - (int) $pos_5421 : 8000;
$block_161   = substr( $css, (int) $pos_5421, $block_len );
$block_161_code = (string) preg_replace( '/\/\*.*?\*\//s', '', $block_161 );
fixture_assert( false === strpos( $block_161_code, 'anywhere' ), 'css: 5421 block never uses anywhere' );

// Single 600px media query owns the reflow.
$media_count = preg_match_all( '/@media\s*\(\s*max-width:\s*600px\s*\)/', $block_161, $m );
fixture_assert( 1 === $media_count, 'css: exactly one max-width:600px query in 0.1.161 block' );
fixture_assert( false !== strpos( $block_161, '@media (max-width: 600px)' ), 'css: 600px breakpoint exact' );

// 3-column reflow inside the scoped block.
fixture_assert(
	1 === preg_match( '/body\.page-id-5421[^{]*table\.aligncenter tr\s*\{\s*display:\s*grid;\s*grid-template-columns:\s*repeat\(3,\s*1fr\)/', $block_161 ),
	'css: tr grid repeat(3,1fr) in 5421 scope'
);

// Cell + image treatment inside the scoped block.
fixture_assert(
	1 === preg_match( '/body\.page-id-5421[^{]*table\.aligncenter td\s*\{\s*display:\s*block;\s*width:\s*auto;\s*padding:\s*4px;/', $block_161 ),
	'css: td display:block width:auto padding:4px in 5421 scope'
);
fixture_assert(
	1 === preg_match( '/body\.page-id-5421[^{]*table\.aligncenter td img\s*\{\s*width:\s*100%;\s*height:\s*auto;\s*max-width:\s*150px;/', $block_161 ),
	'css: td img responsive 100%/auto/150px in 5421 scope'
);

// Desktop preservation: no 5421 table.aligncenter display:grid outside a
// max-width:600px query. Strip all 600px media blocks, then assert absence.
$stripped = $css;
// Remove every @media (max-width: 600px) { ... } balanced block (single level + nested rules).
$offset = 0;
while ( false !== ( $at = strpos( $stripped, '@media (max-width: 600px)', $offset ) ) ) {
	$open = strpos( $stripped, '{', $at );
	fixture_assert( false !== $open, 'css: media block opens' );
	$depth = 0;
	$len   = strlen( $stripped );
	$close = null;
	for ( $i = (int) $open; $i < $len; $i++ ) {
		if ( '{' === $stripped[ $i ] ) {
			$depth++;
		} elseif ( '}' === $stripped[ $i ] ) {
			$depth--;
			if ( 0 === $depth ) {
				$close = $i;
				break;
			}
		}
	}
	fixture_assert( null !== $close, 'css: media block closes' );
	$stripped = substr( $stripped, 0, $at ) . substr( $stripped, (int) $close + 1 );
	$offset   = (int) $at;
}
fixture_assert(
	1 !== preg_match( '/body\.page-id-5421[^{]*table\.aligncenter[^{]*\{[^}]*display:\s*grid/', $stripped ),
	'css: no 5421 table grid outside max-width:600px (desktop untouched)'
);
fixture_assert(
	1 !== preg_match( '/body\.page-id-5421[^{]*table\.aligncenter td span[^{]*\{[^}]*overflow-wrap/', $stripped ),
	'css: no 5421 caption wrapping override outside max-width:600px'
);

// The inherited global anywhere guard must remain for general content
// (this fix overrides it narrowly, never removes it).
fixture_assert(
	1 === preg_match( '/\.page-content \.entry-content\s*\{\s*min-width:\s*0;\s*overflow-wrap:\s*anywhere;/', $css ),
	'css: global anywhere guard intact (narrow override only)'
);

fwrite( STDOUT, "PASS: theme adoption inmemoriam caption fixture (0.1.161 scoped 600px 3-col reflow, break-word captions, td/img treatment, desktop untouched, global guard intact)\n" );
