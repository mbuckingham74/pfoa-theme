<?php
/**
 * Focused fixture for the bonded-pair spotlight destination (PFOA 0.1.203).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency. Covers: pfoa_get_hero_bonded_pair_link() eligible shape
 * (?bonded-pair=<lower>-<upper>#bonded-pair-spotlight, Adoptable Bonded
 * Pair title), ineligible fallback to the bare listing URL, query parser
 * accept/reject table, renderer output contract (spotlight section, both
 * member names + profile links, View All Adoptable Cats bare link),
 * renderer fail-closed on ineligible pairs, prepend filter placement and
 * page/query guards, and source-level contracts (priority-5 filter, CSS,
 * focus script).
 *
 * Run with:
 *   php tests/theme-bonded-pair-spotlight-fixture.php
 *
 * Exits nonzero on any failure.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
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
		public $post_status = '';
		/** @var string */
		public $post_content = '';
	}
}

$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_is_target_page']  = true;
$GLOBALS['fixture_pair']            = array( 24396, 24401 );
$GLOBALS['fixture_lifecycle_ok']    = true;
$GLOBALS['fixture_titles']          = array(
	24396 => 'Oliver',
	24401 => 'Isaac',
	20323 => 'Adoptable Cats',
);
$GLOBALS['fixture_thumbs']          = array(
	24396 => '<img src="https://example.org/oliver.jpg" alt="Oliver">',
	24401 => '<img src="https://example.org/isaac.jpg" alt="Isaac">',
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
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function absint( $value ) {
	return abs( (int) $value );
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
function is_admin() {
	return false;
}
function is_feed() {
	return false;
}
function in_the_loop() {
	return true;
}
function is_main_query() {
	return true;
}
function is_page( $page = '' ) {
	return (bool) $GLOBALS['fixture_is_target_page'];
}
function fixture_make_post( $id, $type, $slug, $status = 'publish' ) {
	$post              = new WP_Post();
	$post->ID          = (int) $id;
	$post->post_type   = (string) $type;
	$post->post_name   = (string) $slug;
	$post->post_status = (string) $status;
	return $post;
}
function get_post( $post = null ) {
	if ( null === $post ) {
		if ( $GLOBALS['fixture_is_target_page'] ) {
			return fixture_make_post( 20323, 'page', 'adoptablecats2' );
		}
		return fixture_make_post( 9999, 'page', 'elsewhere' );
	}
	return null;
}
function get_page_by_path( $path, $output = 'OBJECT', $post_type = 'page' ) {
	if ( 'adoptablecats2' === (string) $path ) {
		return fixture_make_post( 20323, 'page', 'adoptablecats2' );
	}
	return null;
}
function get_permalink( $post ) {
	if ( $post instanceof WP_Post ) {
		if ( 20323 === (int) $post->ID ) {
			return 'https://example.org/adoptablecats2/';
		}
		return 'https://example.org/?p=' . (int) $post->ID;
	}
	$id = (int) $post;
	if ( isset( $GLOBALS['fixture_titles'][ $id ] ) ) {
		return 'https://example.org/pfoa-cat/' . strtolower( $GLOBALS['fixture_titles'][ $id ] ) . '/';
	}
	return '';
}
function get_the_title( $post = 0 ) {
	$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	if ( isset( $GLOBALS['fixture_titles'][ $id ] ) ) {
		return $GLOBALS['fixture_titles'][ $id ];
	}
	return '';
}
function get_post_status( $post ) {
	$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	if ( 20323 === $id ) {
		return 'publish';
	}
	if ( isset( $GLOBALS['fixture_titles'][ $id ] ) ) {
		return 'publish';
	}
	return false;
}
function get_post_type( $post ) {
	$id = $post instanceof WP_Post ? (int) $post->ID : (int) $post;
	if ( 20323 === $id ) {
		return 'page';
	}
	if ( isset( $GLOBALS['fixture_titles'][ $id ] ) ) {
		return 'pfoa_cat';
	}
	return false;
}
function get_the_post_thumbnail( $post_id, $size = 'post-thumbnail', $attr = '' ) {
	$id = (int) $post_id;
	return isset( $GLOBALS['fixture_thumbs'][ $id ] ) ? $GLOBALS['fixture_thumbs'][ $id ] : '';
}
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
	return '';
}
function metadata_exists( $meta_type, $object_id, $meta_key ) {
	return false;
}
function get_post_meta( $post_id, $key = '', $single = false ) {
	return $single ? '' : array();
}
function pfoa_cat_get_bonded_pair( $id ) {
	$id   = (int) $id;
	$pair = $GLOBALS['fixture_pair'];
	if ( in_array( $id, $pair, true ) ) {
		return $pair;
	}
	return array( $id );
}
function pfoa_cat_is_lifecycle_eligible_for_adoptable( $id ) {
	return (bool) $GLOBALS['fixture_lifecycle_ok'];
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$listing = 'https://example.org/adoptablecats2/';

// (1) Query parser: accept + normalize, reject everything else.
$_GET['bonded-pair'] = '24396-24401';
fixture_assert( array( 24396, 24401 ) === pfoa_get_bonded_pair_spotlight_ids(), 'parser: ascending pair accepted' );
fixture_assert( array( 24396, 24401 ) === pfoa_is_bonded_pair_spotlight_requested(), 'requested helper mirrors parser' );
$_GET['bonded-pair'] = '24401-24396';
fixture_assert( array( 24396, 24401 ) === pfoa_get_bonded_pair_spotlight_ids(), 'parser: descending pair normalized ascending' );
foreach ( array( '', 'abc', '24396', '24396-', '-24401', '24396-24396', '0-24401', '24396-0', '1-2-3', '24396_24401', '  ' ) as $bad ) {
	$_GET['bonded-pair'] = $bad;
	fixture_assert( array() === pfoa_get_bonded_pair_spotlight_ids(), 'parser rejects ' . var_export( $bad, true ) );
}
unset( $_GET['bonded-pair'] );
fixture_assert( array() === pfoa_get_bonded_pair_spotlight_ids(), 'parser: missing query returns empty' );

// (2) Eligible hero link: spotlight URL shape + Adoptable title.
$GLOBALS['fixture_pair']         = array( 24396, 24401 );
$GLOBALS['fixture_lifecycle_ok'] = true;
$link = pfoa_get_hero_bonded_pair_link( 24396, 24401 );
fixture_assert( $listing . '?bonded-pair=24396-24401#bonded-pair-spotlight' === $link['url'], 'eligible link: spotlight URL shape, got ' . $link['url'] );
fixture_assert( false !== strpos( $link['title'], 'Adoptable Bonded Pair' ), 'eligible link: Adoptable title' );
fixture_assert( false !== strpos( $link['title'], 'Oliver & Isaac' ), 'eligible link: dynamic pair names' );
$link_rev = pfoa_get_hero_bonded_pair_link( 24401, 24396 );
fixture_assert( $link['url'] === $link_rev['url'], 'eligible link: order-independent URL' );

// (3) Ineligible pair falls back to the bare listing (no query, no anchor).
$GLOBALS['fixture_pair'] = array( 24396 );
$fallback = pfoa_get_hero_bonded_pair_link( 24396, 24401 );
fixture_assert( $listing === $fallback['url'], 'ineligible link: bare listing fallback, got ' . $fallback['url'] );
fixture_assert( false === strpos( $fallback['url'], 'bonded-pair=' ), 'ineligible link: no spotlight query' );
$GLOBALS['fixture_pair']          = array( 24396, 24401 );
$GLOBALS['fixture_lifecycle_ok']  = false;
$fallback_life = pfoa_get_hero_bonded_pair_link( 24396, 24401 );
fixture_assert( $listing === $fallback_life['url'], 'lifecycle-ineligible link: bare listing fallback' );
$GLOBALS['fixture_lifecycle_ok'] = true;

// (4) Renderer: eligible pair emits the full spotlight contract.
$spot = pfoa_render_bonded_pair_spotlight( 24401, 24396 );
fixture_assert( false !== strpos( $spot, 'id="bonded-pair-spotlight"' ), 'renderer: spotlight section id' );
fixture_assert( false !== strpos( $spot, 'aria-labelledby="bonded-pair-spotlight-title"' ), 'renderer: labelledby' );
fixture_assert( false !== strpos( $spot, 'tabindex="-1"' ), 'renderer: focusable section' );
fixture_assert( false !== strpos( $spot, 'Oliver &amp; Isaac — Adoptable Bonded Pair' ), 'renderer: dynamic heading' );
fixture_assert( false !== strpos( $spot, 'Adoptable Bonded Pair' ), 'renderer: kicker text' );
fixture_assert( false !== strpos( $spot, 'https://example.org/pfoa-cat/oliver/' ), 'renderer: Oliver profile URL' );
fixture_assert( false !== strpos( $spot, 'https://example.org/pfoa-cat/isaac/' ), 'renderer: Isaac profile URL' );
fixture_assert( false !== strpos( $spot, 'View Oliver&#039;s full profile' ) || false !== strpos( $spot, 'View Oliver' ), 'renderer: Oliver profile label' );
fixture_assert( false !== strpos( $spot, 'View Isaac' ), 'renderer: Isaac profile label' );
fixture_assert( false !== strpos( $spot, 'oliver.jpg' ) && false !== strpos( $spot, 'isaac.jpg' ), 'renderer: both photos inline' );
fixture_assert( false !== strpos( $spot, 'View All Adoptable Cats' ), 'renderer: listing action label' );
fixture_assert( false !== strpos( $spot, 'href="' . $listing . '"' ), 'renderer: listing action uses bare URL' );

// (5) Renderer fail-closed: ineligible pairs emit nothing.
$GLOBALS['fixture_pair'] = array( 24396 );
fixture_assert( '' === pfoa_render_bonded_pair_spotlight( 24396, 24401 ), 'renderer: broken bond returns empty' );
$GLOBALS['fixture_pair']         = array( 24396, 24401 );
$GLOBALS['fixture_lifecycle_ok'] = false;
fixture_assert( '' === pfoa_render_bonded_pair_spotlight( 24396, 24401 ), 'renderer: lifecycle-ineligible returns empty' );
$GLOBALS['fixture_lifecycle_ok'] = true;
fixture_assert( '' === pfoa_render_bonded_pair_spotlight( 24396, 24396 ), 'renderer: equal IDs return empty' );
fixture_assert( '' === pfoa_render_bonded_pair_spotlight( 0, 24401 ), 'renderer: zero ID returns empty' );

// (6) Prepend filter: spotlight on top on the target page, untouched otherwise.
$GLOBALS['fixture_is_target_page'] = true;
$_GET['bonded-pair']               = '24396-24401';
$prepended = pfoa_prepend_bonded_pair_spotlight( '<p>Listing body.</p>' );
fixture_assert( 0 === strpos( $prepended, '<section id="bonded-pair-spotlight"' ), 'prepend: spotlight leads content' );
fixture_assert( false !== strpos( $prepended, '<p>Listing body.</p>' ), 'prepend: original content preserved' );
$_GET['bonded-pair'] = 'nope';
fixture_assert( '<p>Listing body.</p>' === pfoa_prepend_bonded_pair_spotlight( '<p>Listing body.</p>' ), 'prepend: bad query leaves content unchanged' );
unset( $_GET['bonded-pair'] );
fixture_assert( '<p>Listing body.</p>' === pfoa_prepend_bonded_pair_spotlight( '<p>Listing body.</p>' ), 'prepend: absent query leaves content unchanged' );
$_GET['bonded-pair']              = '24396-24401';
$GLOBALS['fixture_pair']          = array( 24396 );
fixture_assert( '<p>Listing body.</p>' === pfoa_prepend_bonded_pair_spotlight( '<p>Listing body.</p>' ), 'prepend: ineligible pair leaves content unchanged' );
$GLOBALS['fixture_pair']          = array( 24396, 24401 );
$GLOBALS['fixture_is_target_page'] = false;
fixture_assert( '<p>Listing body.</p>' === pfoa_prepend_bonded_pair_spotlight( '<p>Listing body.</p>' ), 'prepend: off-target page leaves content unchanged' );
$GLOBALS['fixture_is_target_page'] = true;
unset( $_GET['bonded-pair'] );

// (7) Source-level contracts: priority-5 filter, CSS, focus script.
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, "add_filter( 'the_content', 'pfoa_prepend_bonded_pair_spotlight', 5 )" ), 'source: spotlight filter at priority 5' );
fixture_assert( false !== strpos( $functions_source, '?bonded-pair=' ), 'source: hero link uses spotlight query shape' );
$css = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/style.css' );
fixture_assert( false !== strpos( $css, '.pfoa-bonded-spotlight' ), 'css: spotlight styles present' );
fixture_assert( false !== strpos( $css, 'PFOA 0.1.203' ), 'css: 0.1.203 marker present' );
$js_path = dirname( __DIR__ ) . '/pfoa-theme/assets/js/pfoa-bonded-pair-spotlight.js';
fixture_assert( file_exists( $js_path ), 'js: focus script exists' );
$js = (string) file_get_contents( $js_path );
fixture_assert( false !== strpos( $js, 'bonded-pair-spotlight' ), 'js: targets spotlight section' );
fixture_assert( false !== strpos( $js, 'bonded-pair=' ), 'js: query-guarded' );

// No writes during evaluation.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during spotlight evaluation' );

fwrite( STDOUT, "PASS: theme bonded-pair spotlight fixture (parser accept/normalize/reject, eligible spotlight URL + Adoptable title, bare-listing fallback, renderer contract + fail-closed, prepend guards, source/css/js contracts, no writes)\n" );
