<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile gallery compat renderer.
 *
 * Requires pfoa-theme/inc/theme-cat-gallery-compat-renderer.php directly
 * (never the functions.php monolith) with WordPress doubles stubbed inline.
 * No network, database, or lifecycle dependency.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_empty_image_ids'] = array();

$GLOBALS['fixture_attachments'] = array(
	11 => array(
		'grid'   => array( 'https://example.com/grid11.jpg', 800, 600 ),
		'medium' => array( 'https://example.com/med11.jpg', 400, 300 ),
		'full'   => 'https://example.com/full11.jpg',
	),
	12 => array(
		'grid'   => array( 'https://example.com/grid12.jpg', 600, 800 ),
		'medium' => array( 'https://example.com/med12.jpg', 300, 400 ),
		'full'   => 'https://example.com/full12.jpg',
	),
	13 => array(
		'grid'   => array( 'https://example.com/grid13.jpg', 500, 500 ),
		'medium' => array( 'https://example.com/med13.jpg', 250, 250 ),
		'full'   => 'https://example.com/full13.jpg',
	),
	14 => array(
		'grid'   => false,
		'medium' => array( 'https://example.com/med14.jpg', 640, 480 ),
		'full'   => 'https://example.com/full14.jpg',
	),
	15 => array(
		'grid'   => array( 'https://example.com/grid15.jpg?a=1&b=2', 800, 600 ),
		'medium' => array( 'https://example.com/med15.jpg', 400, 300 ),
		'full'   => 'https://example.com/full15.jpg?x=1&y=2',
	),
);

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_shortcode( $tag, $callback ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function register_post_type( $type, $args = array() ) {
	$GLOBALS['fixture_hook_registered'] = true;
	return null;
}
function register_taxonomy( $taxonomy, $object_type, $args = array() ) {
	$GLOBALS['fixture_hook_registered'] = true;
	return null;
}
function register_block_type( $name, $args = array() ) {
	$GLOBALS['fixture_hook_registered'] = true;
	return null;
}
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function delete_option( $option ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function delete_post_meta( $post_id, $key ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function wp_insert_post( $postarr, $wp_error = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
}
function wp_insert_attachment( $args = array() ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
}

function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail' ) {
	$id = (int) $attachment_id;
	if ( ! isset( $GLOBALS['fixture_attachments'][ $id ] ) ) {
		return false;
	}
	$record = $GLOBALS['fixture_attachments'][ $id ];
	$size   = (string) $size;
	if ( 'medium' === $size ) {
		return $record['medium'];
	}
	return $record['grid'];
}
function wp_get_attachment_url( $attachment_id ) {
	$id = (int) $attachment_id;
	if ( ! isset( $GLOBALS['fixture_attachments'][ $id ] ) ) {
		return false;
	}
	return $GLOBALS['fixture_attachments'][ $id ]['full'];
}
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
	$id = (int) $attachment_id;
	if ( in_array( $id, $GLOBALS['fixture_empty_image_ids'], true ) ) {
		return '';
	}
	if ( ! isset( $GLOBALS['fixture_attachments'][ $id ] ) ) {
		return '';
	}
	$alt     = isset( $attr['alt'] ) ? (string) $attr['alt'] : '';
	$title   = isset( $attr['title'] ) ? (string) $attr['title'] : '';
	$loading = isset( $attr['loading'] ) ? (string) $attr['loading'] : 'lazy';
	return sprintf(
		'<img src="%s" alt="%s" title="%s" loading="%s" />',
		esc_url( 'https://example.com/img' . $id . '.jpg' ),
		esc_attr( $alt ),
		esc_attr( $title ),
		esc_attr( $loading )
	);
}
function esc_url( $url ) {
	return htmlspecialchars( (string) $url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}
function esc_attr( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-gallery-compat-renderer.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// Renderer entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_render_cat_gallery_compat' ), 'renderer function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_GALLERY_COMPAT_RENDERER_LOADED' ), 'loaded guard constant defined' );

// (1) Ordering preserved verbatim (13, 11, 12).
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 42,
	'gallery'        => array(
		'ids'      => array( 13, 11, 12 ),
		'captions' => array(
			13 => 'Third',
			11 => 'First',
			12 => 'Second',
		),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( '' !== $html, 'ordering: non-empty output' );
$pos13 = strpos( $html, 'full13.jpg' );
$pos11 = strpos( $html, 'full11.jpg' );
$pos12 = strpos( $html, 'full12.jpg' );
fixture_assert( false !== $pos13 && false !== $pos11 && false !== $pos12, 'ordering: all three fulls present' );
fixture_assert( $pos13 < $pos11 && $pos11 < $pos12, 'ordering: stored id order preserved verbatim' );
fixture_assert( false !== strpos( $html, 'id="pfoa-cat-gallery-42"' ), 'ordering: container id uses post id' );
fixture_assert( false !== strpos( $html, 'data-pfoa-cat-gallery' ), 'ordering: provider lightbox hook attribute present' );

// (2) Caption precedence.
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 7,
	'gallery'        => array(
		'ids'      => array( 11, 12, 13 ),
		'captions' => array(
			11 => 'Shown caption',
			12 => '',
		),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( 1 === substr_count( $html, '<dd class="wp-caption-text gallery-caption">Shown caption</dd>' ), 'caption: explicit string shown once' );
fixture_assert( 1 === substr_count( $html, '<dd class="wp-caption-text gallery-caption">' ), 'caption: only one dd emitted (explicit blank + absent suppress)' );
fixture_assert( false !== strpos( $html, 'alt=""' ), 'caption: blank captions keep alt empty' );
fixture_assert( false !== strpos( $html, 'title=""' ), 'caption: blank captions keep title empty' );
// Absent key (13) must not fall back to attachment metadata: no dd, empty alt/title.
$dd_count = substr_count( $html, '<dd class="wp-caption-text gallery-caption">' );
fixture_assert( 1 === $dd_count, 'caption: absent null treated as blank with no dd and no metadata fallback' );

// (3) Escaping (XSS caption, URL).
$xss = '<script>alert("x")</script>';
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 9,
	'gallery'        => array(
		'ids'      => array( 15 ),
		'captions' => array( 15 => $xss ),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( false === strpos( $html, '<script>' ), 'escaping: raw script tag absent' );
fixture_assert( false !== strpos( $html, esc_html( $xss ) ), 'escaping: caption escaped in dd' );
fixture_assert( false !== strpos( $html, esc_attr( $xss ) ), 'escaping: caption escaped in alt/title' );
fixture_assert( false !== strpos( $html, 'full15.jpg?x=1&amp;y=2' ), 'escaping: url escaped' );

// (4) Empty gallery => ''.
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'schema_version' => '1.0', 'post_id' => 7, 'gallery' => array( 'ids' => array(), 'captions' => array() ) ) ), 'empty: ids=[] returns empty' );
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'schema_version' => '1.0', 'post_id' => 7 ) ), 'empty: missing gallery returns empty' );
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'schema_version' => '1.0', 'post_id' => 7, 'gallery' => 'nope' ) ), 'empty: non-array gallery returns empty' );
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'schema_version' => '2.0', 'post_id' => 7, 'gallery' => array( 'ids' => array( 11 ), 'captions' => array() ) ) ), 'empty: wrong schema returns empty' );
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'post_id' => 7, 'gallery' => array( 'ids' => array( 11 ), 'captions' => array() ) ) ), 'empty: missing schema returns empty' );

// (5) Invalid attachments skipped (0, negative, missing id 999).
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 7,
	'gallery'        => array(
		'ids'      => array( 0, -5, 999, 11 ),
		'captions' => array(),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( false !== strpos( $html, 'full11.jpg' ), 'invalid: valid id still renders' );
fixture_assert( 1 === substr_count( $html, '<dl class="gallery-item">' ), 'invalid: only one item after skips' );
fixture_assert( '' === pfoa_theme_render_cat_gallery_compat( array( 'schema_version' => '1.0', 'post_id' => 7, 'gallery' => array( 'ids' => array( 0, -1, 999 ), 'captions' => array() ) ) ), 'invalid: all-invalid returns empty' );

// (5b) Fallback manual <img loading=lazy> when wp_get_attachment_image returns ''.
$GLOBALS['fixture_empty_image_ids'] = array( 13 );
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 7,
	'gallery'        => array(
		'ids'      => array( 13 ),
		'captions' => array( 13 => 'Cap' ),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( false !== strpos( $html, 'loading="lazy"' ), 'fallback: manual img keeps loading=lazy' );
fixture_assert( false !== strpos( $html, 'grid13.jpg' ), 'fallback: manual img uses grid src' );
$GLOBALS['fixture_empty_image_ids'] = array();

// (5c) Medium fallback when grid size missing (id 14 has grid=false).
$profile = array(
	'schema_version' => '1.0',
	'post_id'        => 7,
	'gallery'        => array(
		'ids'      => array( 14 ),
		'captions' => array(),
	),
);
$html = pfoa_theme_render_cat_gallery_compat( $profile );
fixture_assert( '' !== $html && false !== strpos( $html, 'full14.jpg' ), 'fallback: medium size used when grid missing' );

// (6) No writes + no public hook registrations.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during render' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/render time' );
$source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-gallery-compat-renderer.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
foreach ( array( 'update_option', 'wp_insert', 'update_post_meta', 'delete_' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-gallery-compat-renderer' ), 'functions.php does not require new file' );

fwrite( STDOUT, "PASS: theme cat gallery compat renderer fixture (ordering, captions, escaping, empty, invalid, fallbacks, no-writes, no-hooks)\n" );
