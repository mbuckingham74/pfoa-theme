<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile single COMPAT router.
 *
 * Requires pfoa-theme/inc/theme-cat-single-compat-router.php directly
 * (never the functions.php monolith) with WordPress doubles stubbed inline.
 * No network, database, or lifecycle dependency. Single-process: the
 * missing-dependency gates are verified by asserting the router source holds
 * function_exists guards for every collaborator, since live functions
 * cannot be undefined mid-process; invalid-readiness cases act as the
 * runtime fail-closed proxies.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

ob_start();

$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_is_cat_single']   = true;

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
function is_singular( $post_type = '' ) {
	if ( 'pfoa_cat' !== $post_type ) {
		return false;
	}
	return (bool) $GLOBALS['fixture_is_cat_single'];
}
function pfoa_cat_get_profile_data( $post_id ) {
	return array(
		'schema_version' => '1.0',
		'post_id'        => (int) $post_id,
		'post_type'      => 'pfoa_cat',
	);
}
function pfoa_theme_render_cat_profile_compat( array $profile_data, string $heading_tag = 'h1' ): string {
	return '<div class="pfoa-cat-profile"></div>';
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-single-compat-router.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_valid_compat_readiness() {
	return array(
		'request_state'  => 'false',
		'requested_mode' => 'compat',
		'readiness'      => 'compat',
		'compat_ready'   => true,
		'current_ready'  => false,
	);
}

function fixture_unavailable_readiness() {
	return array(
		'request_state'  => 'false',
		'requested_mode' => 'compat',
		'readiness'      => 'unavailable',
		'compat_ready'   => false,
		'current_ready'  => false,
	);
}

// Router entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_select_cat_single_compat_template' ), 'router function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_SINGLE_COMPAT_ROUTER_LOADED' ), 'loaded guard constant defined' );

$fallback       = '/tmp/pfoa-fallback-single.php';
$compat_file    = (string) realpath( dirname( __DIR__ ) . '/pfoa-theme/inc/templates/theme-cat-single-compat-template.php' );
fixture_assert( '' !== $compat_file && file_exists( $compat_file ), 'existing COMPAT template file present' );

// (1) Valid COMPAT request selects the existing COMPAT template.
$GLOBALS['fixture_is_cat_single'] = true;
$selected = pfoa_theme_select_cat_single_compat_template( $fallback, fixture_valid_compat_readiness() );
fixture_assert( $selected !== $fallback, 'valid: compat selected over fallback' );
fixture_assert( (string) realpath( $selected ) === $compat_file, 'valid: selects existing COMPAT template file' );
fixture_assert( 'theme-cat-single-compat-template.php' === basename( $selected ), 'valid: compat template basename' );

// (2) Unavailable readiness (current diagnostic cut) never selects COMPAT.
$selected = pfoa_theme_select_cat_single_compat_template( $fallback, fixture_unavailable_readiness() );
fixture_assert( $selected === $fallback, 'unavailable: fallback unchanged' );

// (3) Invalid readiness shapes fail closed to the incoming template.
$invalid_cases = array(
	'null'              => null,
	'string'            => 'compat',
	'bool'              => true,
	'int'               => 1,
	'empty-array'       => array(),
	'missing-mode'      => array( 'readiness' => 'compat', 'compat_ready' => true ),
	'missing-readiness' => array( 'requested_mode' => 'compat', 'compat_ready' => true ),
	'missing-flag'      => array( 'requested_mode' => 'compat', 'readiness' => 'compat' ),
	'mode-current'      => array( 'request_state' => 'true', 'requested_mode' => 'current', 'readiness' => 'compat', 'compat_ready' => true ),
	'mode-unavailable'  => array( 'request_state' => 'invalid', 'requested_mode' => 'unavailable', 'readiness' => 'unavailable', 'compat_ready' => false ),
	'readiness-current' => array( 'request_state' => 'true', 'requested_mode' => 'current', 'readiness' => 'current', 'compat_ready' => false ),
	'flag-false'        => array( 'request_state' => 'false', 'requested_mode' => 'compat', 'readiness' => 'compat', 'compat_ready' => false ),
	'flag-truthy'       => array( 'request_state' => 'false', 'requested_mode' => 'compat', 'readiness' => 'compat', 'compat_ready' => 1 ),
	'flag-string'       => array( 'request_state' => 'false', 'requested_mode' => 'compat', 'readiness' => 'compat', 'compat_ready' => 'yes' ),
	'request-invalid'   => array( 'request_state' => 'invalid', 'requested_mode' => 'compat', 'readiness' => 'compat', 'compat_ready' => true ),
	'mode-case'         => array( 'request_state' => 'false', 'requested_mode' => 'Compat', 'readiness' => 'compat', 'compat_ready' => true ),
	'readiness-case'    => array( 'request_state' => 'false', 'requested_mode' => 'compat', 'readiness' => 'Compat', 'compat_ready' => true ),
);
foreach ( $invalid_cases as $label => $readiness ) {
	$seen = pfoa_theme_select_cat_single_compat_template( $fallback, $readiness );
	fixture_assert( $seen === $fallback, 'invalid readiness (' . $label . '): fallback unchanged' );
}

// (4) Unsupported requests fail closed: non-singular and empty template.
$GLOBALS['fixture_is_cat_single'] = false;
$seen = pfoa_theme_select_cat_single_compat_template( $fallback, fixture_valid_compat_readiness() );
fixture_assert( $seen === $fallback, 'non-singular: fallback unchanged' );
$GLOBALS['fixture_is_cat_single'] = true;
$seen = pfoa_theme_select_cat_single_compat_template( '', fixture_valid_compat_readiness() );
fixture_assert( '' === $seen, 'empty template: empty unchanged (no selection)' );

// (5) Missing dependencies: single-process cannot undefine live doubles, so
// assert the router source holds function_exists guards for every
// collaborator and the template file probe; cases (3) above act as the
// runtime fail-closed proxies.
$router_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-single-compat-router.php' );
fixture_assert( false !== strpos( $router_source, "function_exists( 'is_singular' )" ), 'missing dep: guards singular probe' );
fixture_assert( false !== strpos( $router_source, "function_exists( 'pfoa_cat_get_profile_data' )" ), 'missing dep: guards profile-data provider' );
fixture_assert( false !== strpos( $router_source, "function_exists( 'pfoa_theme_render_cat_profile_compat' )" ), 'missing dep: guards compat renderer' );
fixture_assert( false !== strpos( $router_source, 'theme-cat-single-compat-template.php' ), 'missing dep: references existing COMPAT template' );
fixture_assert( false !== strpos( $router_source, 'file_exists(' ), 'missing dep: probes template file before selection' );

// (6) Router consumes the supplied decision only: no readiness recalculation,
// no profile/shape inspection, no rendering in the code body.
$doc_end   = strpos( $router_source, '*/' );
$code      = ( false === $doc_end ) ? $router_source : substr( $router_source, $doc_end + 2 );
$code_body = (string) preg_replace( "/if\s*\(\s*!\s*defined\(\s*'ABSPATH'\s*\)\s*\)\s*\{\s*exit;\s*\}/", '', $code );
foreach ( array( 'pfoa_theme_resolve_presentation_readiness', 'pfoa_theme_freeze_presentation_readiness', 'pfoa_theme_get_frozen_presentation_readiness', 'pfoa_theme_presentation_readiness', 'PFOA_THEME_PRESENTATION' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'router code has no ' . $needle );
}
foreach ( array( 'publication', 'schema_version', 'eligible_for_adoptable', 'has_required_card_image', 'pfoa_cat_get_profile_data(' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'router code has no ' . $needle );
}
fixture_assert( 1 === substr_count( $code_body, 'pfoa_theme_render_cat_profile_compat' ), 'router references renderer only in the dependency guard' );
foreach ( array( 'pfoa_cat_render_', 'pfoa_cat_get_record', 'pfoa_cat_is_profile_complete', 'get_post_meta', 'metadata_exists', '_pfoa_' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'router code has no ' . $needle );
}

// (7) No hooks/output/writes: tripwires quiet, output buffer empty,
// forbidden tokens absent from router code, functions.php untouched.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during routing' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/route time' );
$captured = (string) ob_get_clean();
fixture_assert( '' === $captured, 'no router output' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_', 'template_redirect', 'query_vars', 'status_header', 'nocache_headers', 'header(', 'echo', 'exit', 'wp_die', 'wp_enqueue_', 'update_option', 'wp_insert', 'wp_update', 'wp_delete', 'update_post_meta', 'delete_', 'do_action', '$wpdb', '$wp_query', 'get_posts', 'WP_Query' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'router code has no ' . $needle );
}
fixture_assert( false === strpos( $code_body, "add_filter( 'template_include'" ), 'router registers no template_include filter' );
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-single-compat-router' ), 'functions.php does not require new file' );

fwrite( STDOUT, "PASS: theme cat single compat router fixture (valid compat selection, unavailable/invalid readiness fail-closed, non-singular/empty fail-closed, guarded deps, supplied-decision-only, no hooks/output/writes)\n" );
