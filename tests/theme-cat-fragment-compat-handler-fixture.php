<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile fragment compat handler.
 *
 * Requires pfoa-theme/inc/theme-cat-fragment-compat-handler.php directly
 * (never the functions.php monolith) with WordPress doubles stubbed inline.
 * No network, database, or lifecycle dependency. Single-process: the
 * missing-dependency gate is verified by asserting the handler source holds
 * function_exists guards for all three collaborators, since live functions
 * cannot be undefined mid-process; invalid-schema/empty-renderer cases act
 * as the runtime 503 proxies.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

ob_start();

$GLOBALS['fixture_db_touched']       = false;
$GLOBALS['fixture_hook_registered']  = false;
$GLOBALS['fixture_output']           = '';
$GLOBALS['fixture_empty_renderer']   = false;
$GLOBALS['fixture_renderer_headings'] = array();
$GLOBALS['fixture_titles']           = array(
	42 => 'Whiskers',
);
$GLOBALS['fixture_posts']            = array();
$GLOBALS['fixture_profile_data']     = array();

if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

class WP_Post {
	public $ID = 0;
	public $post_type = '';
	public $post_status = '';
}

function fixture_make_post( $id, $post_type, $post_status ) {
	$post              = new WP_Post();
	$post->ID          = (int) $id;
	$post->post_type   = (string) $post_type;
	$post->post_status = (string) $post_status;
	return $post;
}

$GLOBALS['fixture_posts'] = array(
	'whiskers'   => fixture_make_post( 42, 'pfoa_cat', 'publish' ),
	'mittens'    => fixture_make_post( 43, 'pfoa_cat', 'draft' ),
	'stray-page' => fixture_make_post( 44, 'page', 'publish' ),
);

function fixture_base_complete_data( $post_id = 42 ) {
	return array(
		'schema_version' => '1.0',
		'post_id'        => $post_id,
		'post_type'      => 'pfoa_cat',
		'post_status'    => 'publish',
		'publication'    => array( 'complete' => true ),
	);
}

$GLOBALS['fixture_profile_data'] = array(
	42 => fixture_base_complete_data( 42 ),
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
function get_page_by_path( $path, $output = OBJECT, $post_type = 'page' ) {
	$slug = (string) $path;
	if ( ! isset( $GLOBALS['fixture_posts'][ $slug ] ) ) {
		return null;
	}
	return $GLOBALS['fixture_posts'][ $slug ];
}
function pfoa_cat_get_profile_data( $post_id ) {
	$id = (int) $post_id;
	if ( ! isset( $GLOBALS['fixture_profile_data'][ $id ] ) ) {
		return null;
	}
	return $GLOBALS['fixture_profile_data'][ $id ];
}
function pfoa_theme_render_cat_profile_compat( array $profile_data, string $heading_tag = 'h1' ): string {
	$GLOBALS['fixture_renderer_headings'][] = $heading_tag;
	if ( $GLOBALS['fixture_empty_renderer'] ) {
		return '';
	}
	$tag   = ( 'h2' === $heading_tag ) ? 'h2' : 'h1';
	$id    = isset( $profile_data['post_id'] ) ? (int) $profile_data['post_id'] : 0;
	$title = isset( $GLOBALS['fixture_titles'][ $id ] ) ? $GLOBALS['fixture_titles'][ $id ] : 'Title ' . $id;
	return '<div class="pfoa-cat-profile"><' . $tag . ' class="pfoa-cat-profile-title">' . $title . '</' . $tag . '></div>';
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-fragment-compat-handler.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

// Handler entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_handle_cat_fragment_compat' ), 'handler function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_FRAGMENT_COMPAT_HANDLER_LOADED' ), 'loaded guard constant defined' );

// (1) Valid published/complete => 200 with profile markup and h2.
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'whiskers' );
fixture_assert( 200 === $result['status'], 'valid: 200 status' );
fixture_assert( '' !== trim( (string) $result['body'] ), 'valid: non-empty body' );
fixture_assert( false !== strpos( (string) $result['body'], 'pfoa-cat-profile' ), 'valid: profile markup present' );
fixture_assert( false !== strpos( (string) $result['body'], '<h2' ), 'valid: h2 heading present' );

// (2) h2 parity: handler body === direct renderer h2 output.
$direct = pfoa_theme_render_cat_profile_compat( $GLOBALS['fixture_profile_data'][42], 'h2' );
fixture_assert( $direct === $result['body'], 'parity: handler body matches direct h2 render' );
fixture_assert( 'h2' === end( $GLOBALS['fixture_renderer_headings'] ), 'parity: handler delegates with h2' );

// (3) Invalid slug => 404 with no profile markup.
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'Bad_Slug!' );
fixture_assert( 404 === $result['status'], 'bad slug: 404 status' );
fixture_assert( '' === $result['body'], 'bad slug: empty body' );
fixture_assert( false === strpos( (string) $result['body'], 'pfoa-cat-profile' ), 'bad slug: no profile markup' );

// (4) Wrong method => 405.
$result = pfoa_theme_handle_cat_fragment_compat( 'POST', 'whiskers' );
fixture_assert( 405 === $result['status'], 'wrong method: 405 status' );
fixture_assert( '' === $result['body'], 'wrong method: empty body' );

// (5) Missing post (null) => 404; unpublished (draft) => 404; wrong type => 404.
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'ghost' );
fixture_assert( 404 === $result['status'], 'missing post: 404 status' );
fixture_assert( '' === $result['body'], 'missing post: empty body' );
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'mittens' );
fixture_assert( 404 === $result['status'], 'draft post: 404 status' );
fixture_assert( '' === $result['body'], 'draft post: empty body' );
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'stray-page' );
fixture_assert( 404 === $result['status'], 'wrong post type: 404 status' );

// (6) Incomplete (complete false) => 404.
$GLOBALS['fixture_profile_data'][42]['publication']['complete'] = false;
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'whiskers' );
fixture_assert( 404 === $result['status'], 'incomplete: 404 status' );
fixture_assert( '' === $result['body'], 'incomplete: empty body' );
$GLOBALS['fixture_profile_data'][42] = fixture_base_complete_data( 42 );

// (7) Mismatched post_id (999 vs 42) => 503.
$GLOBALS['fixture_profile_data'][42]['post_id'] = 999;
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'whiskers' );
fixture_assert( 503 === $result['status'], 'mismatched post_id: 503 status' );
fixture_assert( '' === $result['body'], 'mismatched post_id: empty body' );
$GLOBALS['fixture_profile_data'][42] = fixture_base_complete_data( 42 );

// (8) Invalid schema (2.0) => 503.
$GLOBALS['fixture_profile_data'][42]['schema_version'] = '2.0';
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'whiskers' );
fixture_assert( 503 === $result['status'], 'invalid schema: 503 status' );
fixture_assert( '' === $result['body'], 'invalid schema: empty body' );
$GLOBALS['fixture_profile_data'][42] = fixture_base_complete_data( 42 );

// (9) Missing dependency: single-process cannot undefine live doubles, so
// assert the handler source holds function_exists guards for every
// collaborator; cases (8) and (10) above/below act as runtime 503 proxies.
$handler_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-fragment-compat-handler.php' );
fixture_assert( false !== strpos( $handler_source, "function_exists( 'pfoa_cat_get_profile_data' )" ), 'missing dep: guards profile-data provider' );
fixture_assert( false !== strpos( $handler_source, "function_exists( 'pfoa_theme_render_cat_profile_compat' )" ), 'missing dep: guards compat renderer' );
fixture_assert( false !== strpos( $handler_source, "function_exists( 'get_page_by_path' )" ), 'missing dep: guards page resolver' );

// (10) Empty renderer => 503.
$GLOBALS['fixture_empty_renderer'] = true;
$result = pfoa_theme_handle_cat_fragment_compat( 'GET', 'whiskers' );
fixture_assert( 503 === $result['status'], 'empty renderer: 503 status' );
fixture_assert( '' === $result['body'], 'empty renderer: empty body' );
$GLOBALS['fixture_empty_renderer'] = false;

// (11) No hooks/output/writes: tripwires quiet, output buffer empty,
// forbidden tokens absent from handler code, functions.php untouched.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during handling' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/handle time' );
$captured = (string) ob_get_clean();
fixture_assert( '' === $captured, 'no handler output' );
$doc_end = strpos( $handler_source, '*/' );
$code    = ( false === $doc_end ) ? $handler_source : substr( $handler_source, $doc_end + 2 );
// Exempt the single ABSPATH guard block (required `exit;`); all other
// `exit` occurrences must be absent.
$code_body = (string) preg_replace( "/if\s*\(\s*!\s*defined\(\s*'ABSPATH'\s*\)\s*\)\s*\{\s*exit;\s*\}/", '', $code );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_', 'query_vars', 'template_redirect', 'header(', 'status_header', 'nocache_headers', 'echo', 'exit', 'wp_die', 'wp_enqueue_', 'update_option', 'wp_insert', 'wp_update', 'wp_delete', 'update_post_meta', 'delete_', 'get_post_meta', 'metadata_exists', '_pfoa_', 'do_action', '$wpdb', 'pfoa_cat_render_', 'pfoa_cat_get_record', 'pfoa_cat_is_profile_complete' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'handler code has no ' . $needle );
}
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-fragment-compat-handler' ), 'functions.php does not require new file' );

fwrite( STDOUT, "PASS: theme cat fragment compat handler fixture (valid 200 + h2 markup, h2 parity, bad slug 404, POST 405, missing/draft/type 404, incomplete 404, mismatched id 503, bad schema 503, guarded deps, empty renderer 503, no hooks/output/writes)\n" );
