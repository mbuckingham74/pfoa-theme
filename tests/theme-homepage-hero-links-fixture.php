<?php
/**
 * Fixture: homepage hero external links + featured slide pinning.
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers:
 *
 * - pfoa_sanitize_hero_external_url(): accepts absolute http/https URLs
 *   (trims whitespace); rejects javascript:/ftp:/relative/empty/hostless
 *   input to '' (no link).
 * - Legacy internal compat: pfoa_sanitize_hero() preserves stored
 *   link_page_id values and derives link_type internal when a destination
 *   is stored, none otherwise (no migration; bonded-pair tokens untouched
 *   by the resolver fallback).
 * - Type switching retention: sanitizer stores link_page_id AND link_url
 *   regardless of the active link_type.
 * - pfoa_get_hero_slide_link(): internal (revalidated eligibility),
 *   external (headline/fallback/host title, external flag), none/invalid
 *   (empty destination).
 * - pfoa_find_hero_invalid_external_links(): flags 'single' and 1-based
 *   slide numbers with invalid external URLs.
 * - pfoa_get_hero_pinned_slide_id(): active timed, indefinite, expired,
 *   out-of-range, and invalid-image pins (expired/invalid resolve to 0 so
 *   the template falls back to rotation with slides intact).
 * - pfoa_resolve_hero_pin_for_save(): fresh expiry on new pin/slide
 *   change/indefinite-to-timed change/missing future expiry; preserved
 *   expires_at + created_at on unrelated saves with unchanged pin+mode and
 *   a valid future expires_at in the hidden field.
  * - style.css version 0.1.212; no DB writes.
 *
 * Run with:
 *   php tests/theme-homepage-hero-links-fixture.php
 *
 * Exits nonzero on any failure.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
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
	}
}

$GLOBALS['fixture_db_touched'] = false;
$GLOBALS['fixture_now']         = 1790000000;
$GLOBALS['fixture_posts']       = array(
	101 => array( 'status' => 'publish', 'type' => 'page', 'title' => 'Visit Us' ),
	102 => array( 'status' => 'publish', 'type' => 'page', 'title' => 'Adopt' ),
	103 => array( 'status' => 'draft', 'type' => 'page', 'title' => 'Draft Page' ),
	201 => array( 'status' => 'publish', 'type' => 'pfoa_cat', 'title' => 'Mittens' ),
	202 => array( 'status' => 'publish', 'type' => 'pfoa_cat', 'title' => 'Shadow' ),
	203 => array( 'status' => 'publish', 'type' => 'pfoa_cat', 'title' => 'Luna' ),
);
$GLOBALS['fixture_cat_complete'] = array(
	202 => '0',
);
$GLOBALS['fixture_images'] = array(
	11 => true,
	12 => true,
	13 => true,
);
$GLOBALS['fixture_hero_meta'] = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {}
function add_shortcode( $tag, $callback ) {}
function apply_filters( $hook, $value = null ) {
	return $value;
}
function __( $text, $domain = null ) {
	return (string) $text;
}
function esc_html__( $text, $domain = null ) {
	return (string) $text;
}
function esc_attr__( $text, $domain = null ) {
	return (string) $text;
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_url( $url ) {
	return (string) $url;
}
function esc_url_raw( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	if ( preg_match( '/[\s<>"\']/', $url ) ) {
		return '';
	}

	if ( preg_match( '/^([a-z][a-z0-9+\-.]*):/i', $url, $matches ) ) {
		$scheme = strtolower( $matches[1] );

		if ( 'http' !== $scheme && 'https' !== $scheme ) {
			return '';
		}

		return $url;
	}

	if ( '/' === $url[0] ) {
		return $url;
	}

	return 'http://' . $url;
}
function sanitize_key( $key ) {
	return strtolower( (string) preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function absint( $value ) {
	return abs( (int) $value );
}
function current_time( $type ) {
	if ( 'timestamp' === $type ) {
		return (int) $GLOBALS['fixture_now'];
	}

	return gmdate( 'Y-m-d H:i:s', (int) $GLOBALS['fixture_now'] );
}
function get_post_status( $post_id ) {
	$id = (int) $post_id;

	return isset( $GLOBALS['fixture_posts'][ $id ] ) ? $GLOBALS['fixture_posts'][ $id ]['status'] : false;
}
function get_post_type( $post_id ) {
	$id = (int) $post_id;

	return isset( $GLOBALS['fixture_posts'][ $id ] ) ? $GLOBALS['fixture_posts'][ $id ]['type'] : false;
}
function get_permalink( $post_id ) {
	return 'https://example.com/?p=' . (int) $post_id;
}
function get_the_title( $post_id ) {
	$id = (int) $post_id;

	if ( isset( $GLOBALS['fixture_posts'][ $id ] ) ) {
		return $GLOBALS['fixture_posts'][ $id ]['title'];
	}

	return '';
}
function metadata_exists( $meta_type, $object_id, $meta_key ) {
	return array_key_exists( (int) $object_id, $GLOBALS['fixture_cat_complete'] );
}
function get_post_meta( $post_id, $key = '', $single = false ) {
	$id = (int) $post_id;

	if ( 999 === $id && '_pfoa_hero' === (string) $key ) {
		return $single ? $GLOBALS['fixture_hero_meta'] : array( $GLOBALS['fixture_hero_meta'] );
	}

	if ( array_key_exists( $id, $GLOBALS['fixture_cat_complete'] ) && '_pfoa_cat_profile_complete' === (string) $key ) {
		return $single ? $GLOBALS['fixture_cat_complete'][ $id ] : array( $GLOBALS['fixture_cat_complete'][ $id ] );
	}

	return $single ? '' : array();
}
function post_type_exists( $post_type ) {
	return false;
}
function get_posts( $args = array() ) {
	return array();
}
function get_page_by_path( $path, $output = OBJECT, $post_type = 'page' ) {
	return null;
}
function wp_attachment_is_image( $attachment_id ) {
	$id = (int) $attachment_id;

	if ( array_key_exists( $id, $GLOBALS['fixture_images'] ) ) {
		return (bool) $GLOBALS['fixture_images'][ $id ];
	}

	return true;
}
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
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
function wp_update_post( $postarr, $wp_error = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
}
function wp_delete_post( $post_id, $force = false ) {
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

function fixture_hero_base( $overrides = array() ) {
	$hero = array(
		'mode'                 => 'carousel',
		'headline'             => 'Global Headline',
		'image_id'             => 0,
		'image_link_page_id'   => 0,
		'carousel_slide_count' => 3,
		'carousel_interval_ms' => 5000,
		'carousel_items'       => array(
			array( 'id' => 11, 'headline' => 'Slide One' ),
			array( 'id' => 12, 'headline' => 'Slide Two' ),
			array( 'id' => 13, 'headline' => 'Slide Three' ),
		),
		'ctas'                 => array(),
	);

	foreach ( $overrides as $key => $value ) {
		$hero[ $key ] = $value;
	}

	return $hero;
}

// --- External URL sanitize: accept. ---

fixture_assert(
	'https://partner.example.org/cats?utm_source=pfoa' === pfoa_sanitize_hero_external_url( 'https://partner.example.org/cats?utm_source=pfoa' ),
	'external url: absolute https with query accepted'
);
fixture_assert(
	'http://example.com/some/path' === pfoa_sanitize_hero_external_url( 'http://example.com/some/path' ),
	'external url: absolute http accepted'
);
fixture_assert(
	'https://example.com/trimmed' === pfoa_sanitize_hero_external_url( '  https://example.com/trimmed  ' ),
	'external url: surrounding whitespace trimmed'
);

// --- External URL sanitize: reject. ---

fixture_assert( '' === pfoa_sanitize_hero_external_url( 'javascript:alert(1)' ), 'external url: javascript: rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( 'ftp://files.example.com/x' ), 'external url: ftp rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( '/relative/path' ), 'external url: relative path rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( 'not a url' ), 'external url: spaces rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( '' ), 'external url: empty rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( 'https://' ), 'external url: hostless rejected' );
fixture_assert( '' === pfoa_sanitize_hero_external_url( array( 'https://example.com/' ) ), 'external url: non-string rejected' );

// --- Legacy internal compat (no link_type/link_url keys). ---

$legacy = pfoa_sanitize_hero(
	fixture_hero_base(
		array(
			'carousel_items' => array(
				array( 'id' => 11, 'link_page_id' => 101, 'headline' => 'Legacy Page' ),
				array( 'id' => 12, 'link_page_id' => 201, 'headline' => 'Legacy Cat' ),
				array( 'id' => 13, 'headline' => 'Legacy None' ),
			),
		)
	)
);

fixture_assert( 101 === $legacy['carousel_items'][0]['link_page_id'], 'legacy: page link_page_id preserved' );
fixture_assert( 'internal' === $legacy['carousel_items'][0]['link_type'], 'legacy: stored destination resolves internal' );
fixture_assert( '' === $legacy['carousel_items'][0]['link_url'], 'legacy: link_url defaults empty' );
fixture_assert( 201 === $legacy['carousel_items'][1]['link_page_id'], 'legacy: complete cat profile preserved as internal' );
fixture_assert( 'internal' === $legacy['carousel_items'][1]['link_type'], 'legacy: cat destination resolves internal' );
fixture_assert( 'none' === $legacy['carousel_items'][2]['link_type'], 'legacy: row without destination resolves none' );
fixture_assert( 0 === $legacy['carousel_items'][2]['link_page_id'], 'legacy: row without destination keeps 0' );

$legacy_single = pfoa_sanitize_hero( fixture_hero_base( array( 'image_link_page_id' => 102 ) ) );
fixture_assert( 102 === $legacy_single['image_link_page_id'], 'legacy single: image_link_page_id preserved' );
fixture_assert( 'internal' === $legacy_single['image_link_type'], 'legacy single: resolves internal' );

$legacy_draft = pfoa_sanitize_hero(
	fixture_hero_base( array( 'carousel_items' => array( array( 'id' => 11, 'link_page_id' => 103 ) ) ) )
);
fixture_assert( 0 === $legacy_draft['carousel_items'][0]['link_page_id'], 'legacy: draft page rejected to 0' );
fixture_assert( 'none' === $legacy_draft['carousel_items'][0]['link_type'], 'legacy: rejected destination resolves none' );

// --- Type switching retention: both destinations stored regardless of active type. ---

$retained = pfoa_sanitize_hero(
	fixture_hero_base(
		array(
			'image_link_type'    => 'external',
			'image_link_page_id' => 101,
			'image_link_url'     => 'https://single.example/kept',
			'carousel_items'     => array(
				array(
					'id'           => 11,
					'link_type'    => 'external',
					'link_page_id' => 101,
					'link_url'     => 'https://a.example/kept',
					'headline'     => 'H',
				),
				array(
					'id'           => 12,
					'link_type'    => 'internal',
					'link_page_id' => 102,
					'link_url'     => 'https://b.example/kept',
				),
				array(
					'id'           => 13,
					'link_type'    => 'none',
					'link_page_id' => 101,
					'link_url'     => 'https://c.example/kept',
				),
			),
		)
	)
);

fixture_assert( 'https://a.example/kept' === $retained['carousel_items'][0]['link_url'], 'retention: external row keeps url' );
fixture_assert( 101 === $retained['carousel_items'][0]['link_page_id'], 'retention: external row keeps internal destination' );
fixture_assert( 'https://b.example/kept' === $retained['carousel_items'][1]['link_url'], 'retention: internal row keeps url for later switch' );
fixture_assert( 'https://c.example/kept' === $retained['carousel_items'][2]['link_url'], 'retention: none row keeps url for later switch' );
fixture_assert( 101 === $retained['carousel_items'][2]['link_page_id'], 'retention: none row keeps internal destination' );
fixture_assert( 'https://single.example/kept' === $retained['image_link_url'], 'retention: single image keeps url' );
fixture_assert( 101 === $retained['image_link_page_id'], 'retention: single image keeps internal destination' );

$invalid_external = pfoa_sanitize_hero(
	fixture_hero_base(
		array(
			'image_link_type' => 'external',
			'image_link_url'  => 'javascript:evil()',
			'carousel_items'  => array(
				array( 'id' => 11, 'link_type' => 'external', 'link_url' => 'ftp://x.example/y' ),
			),
		)
	)
);
fixture_assert( '' === $invalid_external['image_link_url'], 'invalid external single: sanitizes to empty (no link)' );
fixture_assert( '' === $invalid_external['carousel_items'][0]['link_url'], 'invalid external slide: sanitizes to empty (no link)' );

// --- Link type resolver. ---

fixture_assert( 'external' === pfoa_resolve_hero_link_type( array( 'link_type' => 'external', 'link_page_id' => 0 ) ), 'resolver: explicit external passes' );
fixture_assert( 'none' === pfoa_resolve_hero_link_type( array( 'link_type' => 'none', 'link_page_id' => 101 ) ), 'resolver: explicit none passes' );
fixture_assert( 'internal' === pfoa_resolve_hero_link_type( array( 'link_type' => 'internal', 'link_page_id' => 0 ) ), 'resolver: explicit internal passes' );
fixture_assert( 'internal' === pfoa_resolve_hero_link_type( array( 'link_page_id' => 101 ) ), 'resolver: legacy numeric destination internal' );
fixture_assert( 'internal' === pfoa_resolve_hero_link_type( array( 'link_page_id' => 'pair:1:2' ) ), 'resolver: legacy pair token internal' );
fixture_assert( 'none' === pfoa_resolve_hero_link_type( array( 'link_page_id' => 0 ) ), 'resolver: legacy zero none' );
fixture_assert( 'none' === pfoa_resolve_hero_link_type( array() ), 'resolver: empty item none' );
fixture_assert( 'internal' === pfoa_resolve_hero_link_type( array( 'link_type' => 'bogus', 'link_page_id' => 101 ) ), 'resolver: unknown type falls back to legacy' );
fixture_assert( 'none' === pfoa_resolve_hero_link_type( 'not-an-array' ), 'resolver: non-array none' );

// --- Slide link resolver. ---

$internal_link = pfoa_get_hero_slide_link( array( 'link_type' => 'internal', 'link_page_id' => 101 ) );
fixture_assert( 'https://example.com/?p=101' === $internal_link['url'], 'slide link: internal url resolves' );
fixture_assert( 'Visit Us' === $internal_link['title'], 'slide link: internal title resolves' );
fixture_assert( false === $internal_link['external'], 'slide link: internal not flagged external' );

$revalidated = pfoa_get_hero_slide_link( array( 'link_type' => 'internal', 'link_page_id' => 103 ) );
fixture_assert( '' === $revalidated['url'] && '' === $revalidated['title'], 'slide link: ineligible internal revalidates to empty' );

$external_headline = pfoa_get_hero_slide_link(
	array( 'link_type' => 'external', 'link_url' => 'https://partner.example.org/cats', 'headline' => 'Slide Headline' )
);
fixture_assert( 'https://partner.example.org/cats' === $external_headline['url'], 'slide link: external url returned' );
fixture_assert( 'Slide Headline' === $external_headline['title'], 'slide link: external title prefers slide headline' );
fixture_assert( true === $external_headline['external'], 'slide link: external flagged' );

$external_fallback = pfoa_get_hero_slide_link(
	array( 'link_type' => 'external', 'link_url' => 'https://partner.example.org/cats' ),
	'Fallback Headline'
);
fixture_assert( 'Fallback Headline' === $external_fallback['title'], 'slide link: external title falls back to supplied headline' );

$external_host = pfoa_get_hero_slide_link( array( 'link_type' => 'external', 'link_url' => 'https://partner.example.org/cats' ) );
fixture_assert( 'partner.example.org' === $external_host['title'], 'slide link: external title falls back to url host' );

$none_link = pfoa_get_hero_slide_link( array( 'link_type' => 'none', 'link_page_id' => 101, 'link_url' => 'https://a.example/kept' ) );
fixture_assert( '' === $none_link['url'] && false === $none_link['external'], 'slide link: none resolves empty' );

$invalid_link = pfoa_get_hero_slide_link( array( 'link_type' => 'external', 'link_url' => 'javascript:evil()' ) );
fixture_assert( '' === $invalid_link['url'], 'slide link: invalid external resolves empty' );

$legacy_link = pfoa_get_hero_slide_link( array( 'link_page_id' => 101 ) );
fixture_assert( 'https://example.com/?p=101' === $legacy_link['url'] && false === $legacy_link['external'], 'slide link: legacy row resolves internal' );

// --- Invalid external link finder (admin feedback). ---

$flagged = pfoa_find_hero_invalid_external_links(
	array(
		'image_link_type' => 'external',
		'image_link_url'  => 'javascript:evil()',
		'carousel_items'  => array(
			array( 'link_type' => 'external', 'link_url' => 'https://ok.example/fine' ),
			array( 'link_type' => 'external', 'link_url' => 'ftp://bad.example/x' ),
			array( 'link_type' => 'internal', 'link_page_id' => 101 ),
			array( 'link_type' => 'external', 'link_url' => '' ),
		),
	)
);
fixture_assert( array( 'single', '2' ) === $flagged, 'invalid finder: flags single + slide 2 only' );
fixture_assert( array() === pfoa_find_hero_invalid_external_links( array() ), 'invalid finder: clean input empty' );

// --- Featured pin resolution. ---

$now = (int) $GLOBALS['fixture_now'];

fixture_assert( 0 === pfoa_get_hero_pinned_slide_id( fixture_hero_base() ), 'pin: no pin resolves 0' );
fixture_assert(
	0 === pfoa_get_hero_pinned_slide_id( fixture_hero_base( array( 'featured_pin' => array( 'slide' => 0, 'mode' => 'none' ) ) ) ),
	'pin: explicit none resolves 0'
);
fixture_assert(
	12 === pfoa_get_hero_pinned_slide_id(
		fixture_hero_base( array( 'featured_pin' => array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => $now + 100 ) ) )
	),
	'pin: active timed resolves attachment id'
);
fixture_assert(
	0 === pfoa_get_hero_pinned_slide_id(
		fixture_hero_base( array( 'featured_pin' => array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => $now - 10 ) ) )
	),
	'pin: expired timed resolves 0 (fallback to rotation)'
);
fixture_assert(
	11 === pfoa_get_hero_pinned_slide_id(
		fixture_hero_base( array( 'featured_pin' => array( 'slide' => 1, 'mode' => 'indefinite' ) ) )
	),
	'pin: indefinite resolves attachment id'
);
fixture_assert(
	0 === pfoa_get_hero_pinned_slide_id(
		fixture_hero_base( array( 'featured_pin' => array( 'slide' => 4, 'mode' => 'timed', 'expires_at' => $now + 100 ) ) )
	),
	'pin: out-of-range slide resolves 0 (preserved fallback)'
);

$GLOBALS['fixture_images'][12] = false;
fixture_assert(
	0 === pfoa_get_hero_pinned_slide_id(
		fixture_hero_base( array( 'featured_pin' => array( 'slide' => 2, 'mode' => 'indefinite' ) ) )
	),
	'pin: invalid image resolves 0 (fallback to rotation)'
);
$GLOBALS['fixture_images'][12] = true;

// Out-of-range pin is preserved in storage (not deleted) even though it resolves 0.
$preserved_range = pfoa_sanitize_hero(
	fixture_hero_base( array( 'featured_pin' => array( 'slide' => 4, 'mode' => 'timed', 'expires_at' => $now + 50 ) ) )
);
fixture_assert( 4 === $preserved_range['featured_pin']['slide'], 'pin: out-of-range slide preserved in storage' );
fixture_assert( $now + 50 === $preserved_range['featured_pin']['expires_at'], 'pin: sanitizer never rewrites expires_at' );

// --- Featured pin save semantics. ---

$items = array(
	array( 'id' => 11 ),
	array( 'id' => 12 ),
	array( 'id' => 13 ),
);

$new_pin = pfoa_resolve_hero_pin_for_save( array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => 0 ), array(), $items, $now );
fixture_assert( $now + DAY_IN_SECONDS === $new_pin['expires_at'], 'pin save: newly set timed pin gets fresh expiry' );
fixture_assert( $now === $new_pin['created_at'], 'pin save: newly set pin stamps created_at' );
fixture_assert( 12 === $new_pin['attachment_id'], 'pin save: attachment snapshot stored' );

$stored_pin = array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => $now + 3600, 'created_at' => $now - 7200, 'attachment_id' => 12 );
$kept_pin   = pfoa_resolve_hero_pin_for_save( $stored_pin, $stored_pin, $items, $now );
fixture_assert( $now + 3600 === $kept_pin['expires_at'], 'pin save: unrelated save preserves original expires_at (no extension)' );
fixture_assert( $now - 7200 === $kept_pin['created_at'], 'pin save: unrelated save preserves created_at' );

$moved_pin = pfoa_resolve_hero_pin_for_save(
	array( 'slide' => 1, 'mode' => 'timed', 'expires_at' => $now + 3600 ),
	$stored_pin,
	$items,
	$now
);
fixture_assert( $now + DAY_IN_SECONDS === $moved_pin['expires_at'], 'pin save: slide change computes fresh expiry' );
fixture_assert( 11 === $moved_pin['attachment_id'], 'pin save: slide change snapshots new attachment' );

$converted_pin = pfoa_resolve_hero_pin_for_save(
	array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => 0 ),
	array( 'slide' => 2, 'mode' => 'indefinite', 'expires_at' => 0, 'created_at' => $now - 100 ),
	$items,
	$now
);
fixture_assert( $now + DAY_IN_SECONDS === $converted_pin['expires_at'], 'pin save: indefinite-to-timed computes fresh expiry' );

$expired_stored = array( 'slide' => 2, 'mode' => 'timed', 'expires_at' => $now - 5, 'created_at' => $now - 9000, 'attachment_id' => 12 );
$renewed_pin    = pfoa_resolve_hero_pin_for_save( $expired_stored, $expired_stored, $items, $now );
fixture_assert( $now + DAY_IN_SECONDS === $renewed_pin['expires_at'], 'pin save: no valid future expiry computes fresh expiry' );

$indef_pin = pfoa_resolve_hero_pin_for_save( array( 'slide' => 1, 'mode' => 'indefinite' ), array(), $items, $now );
fixture_assert( 0 === $indef_pin['expires_at'] && $now === $indef_pin['created_at'], 'pin save: indefinite stores no expiry' );

$indef_kept = pfoa_resolve_hero_pin_for_save(
	array( 'slide' => 1, 'mode' => 'indefinite' ),
	array( 'slide' => 1, 'mode' => 'indefinite', 'expires_at' => 0, 'created_at' => $now - 4000, 'attachment_id' => 11 ),
	$items,
	$now
);
fixture_assert( $now - 4000 === $indef_kept['created_at'], 'pin save: indefinite keeps created_at on unrelated save' );

$cleared_pin = pfoa_resolve_hero_pin_for_save( array( 'slide' => 0, 'mode' => 'timed' ), $stored_pin, $items, $now );
fixture_assert( 0 === $cleared_pin['slide'] && 'none' === $cleared_pin['mode'], 'pin save: slide 0 clears to none' );

// --- pfoa_get_hero legacy round-trip through post meta. ---

$GLOBALS['fixture_hero_meta'] = array(
	'mode'                 => 'carousel',
	'headline'             => 'Stored',
	'carousel_slide_count' => 3,
	'carousel_items'       => array(
		array( 'id' => 11, 'link_page_id' => 101, 'headline' => 'Stored Slide' ),
		array( 'id' => 12, 'link_page_id' => 0 ),
	),
);

$stored_hero = pfoa_get_hero( 999 );
fixture_assert( 'internal' === $stored_hero['carousel_items'][0]['link_type'], 'get_hero: stored legacy row resolves internal' );
fixture_assert( 101 === $stored_hero['carousel_items'][0]['link_page_id'], 'get_hero: stored link_page_id preserved' );
fixture_assert( 'none' === $stored_hero['carousel_items'][1]['link_type'], 'get_hero: stored unlinked row resolves none' );
fixture_assert( 'none' === $stored_hero['featured_pin']['mode'], 'get_hero: missing pin defaults to none' );

	// style.css: version 0.1.212.
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
	fixture_assert( 1 === preg_match( '/Version:\s*0\.1\.212/', $css ), 'css: theme version 0.1.212' );

// No writes during helper evaluation.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during hero evaluation' );

fwrite( STDOUT, "PASS: theme homepage hero links fixture (external accept/reject, legacy internal compat, type retention, slide-link resolver, invalid-link finder, pin resolve/save semantics, css 0.1.212, no writes)\n" );
