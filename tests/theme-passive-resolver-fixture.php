<?php
/**
 * Focused fixture for the theme passive capabilities + inert resolver.
 *
 * Diagnostic-only: requires pfoa-theme/inc/theme-passive-resolver.php
 * directly (never the functions.php monolith) with WordPress doubles
 * stubbed inline. No network, database, or lifecycle dependency.
 *
 * @package PFOA
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['fixture_actions']         = array();
$GLOBALS['fixture_shortcodes']      = array();
$GLOBALS['fixture_filters']         = array();
$GLOBALS['fixture_actions_present'] = array();
$GLOBALS['fixture_db_touched']      = false;

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_actions'][] = array(
		'hook'     => (string) $hook,
		'callback' => $callback,
		'priority' => (int) $priority,
	);
}
function apply_filters( $tag, $value ) {
	return $value;
}
function shortcode_exists( $tag ) {
	return ! empty( $GLOBALS['fixture_shortcodes'][ (string) $tag ] );
}
function has_filter( $tag, $callback = false ) {
	if ( false === $callback ) {
		return false;
	}
	$key = (string) $tag . '|' . (string) $callback;
	return isset( $GLOBALS['fixture_filters'][ $key ] ) ? $GLOBALS['fixture_filters'][ $key ] : false;
}
function has_action( $tag, $callback = false ) {
	if ( false === $callback ) {
		return false;
	}
	$key = (string) $tag . '|' . (string) $callback;
	return isset( $GLOBALS['fixture_actions_present'][ $key ] ) ? $GLOBALS['fixture_actions_present'][ $key ] : false;
}
function __( $value ) {
	return (string) $value;
}
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

// Storage/enqueue tripwires: the resolver must never touch these.
function get_post() {
	$GLOBALS['fixture_db_touched'] = true;
	return null;
}
function get_option() {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_option() {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function get_posts() {
	$GLOBALS['fixture_db_touched'] = true;
	return array();
}
function wp_enqueue_script() {
	$GLOBALS['fixture_db_touched'] = true;
}
function wp_enqueue_style() {
	$GLOBALS['fixture_db_touched'] = true;
}
function wp_register_script() {
	$GLOBALS['fixture_db_touched'] = true;
}
function wp_register_style() {
	$GLOBALS['fixture_db_touched'] = true;
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-passive-resolver.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_subprocess_resolve( $setup_php, $label ) {
	$resolver = dirname( __DIR__ ) . '/pfoa-theme/inc/theme-passive-resolver.php';
	$code     = 'define( \'ABSPATH\', \'/tmp/pfoa-fixture/\' ); ' . $setup_php . ' require ' . var_export( $resolver, true ) . '; $snapshot = pfoa_theme_resolve_presentation_readiness(); echo json_encode( $snapshot );';
	$binary   = ( defined( 'PHP_BINARY' ) && '' !== PHP_BINARY ) ? PHP_BINARY : 'php';
	$output   = array();
	$exit     = 0;
	exec( $binary . ' -r ' . escapeshellarg( $code ), $output, $exit );
	fixture_assert( 0 === $exit, $label . ': subprocess exits 0' );
	$decoded = json_decode( implode( "\n", $output ), true );
	fixture_assert( is_array( $decoded ), $label . ': subprocess emits readiness JSON' );
	return $decoded;
}

$expected_caps_keys = array(
	'capabilities_version',
	'theme_version',
	'compat_implemented',
	'current_implemented',
	'compat_ready',
	'current_ready',
	'available_components',
	'bundle_complete',
	'notes',
);

$expected_result_keys = array(
	'request_state',
	'requested_mode',
	'cat_neutral_available',
	'cat_passive_available',
	'cat_neutral_satisfied',
	'cat_passive_satisfied',
	'gallery_available',
	'gallery_satisfied',
	'bundle_complete',
	'legacy_plugin_owns_output',
	'compat_ready',
	'current_ready',
	'readiness',
	'missing_capabilities',
	'incompatible_capabilities',
	'diagnostic',
);

// Pending before init:100: nothing frozen yet.
fixture_assert( null === pfoa_theme_get_frozen_presentation_readiness(), 'frozen readiness is null before freeze (pending)' );
fixture_assert( ! isset( $GLOBALS['pfoa_theme_presentation_readiness'] ), 'no snapshot global before freeze' );

// Capabilities: fixed key order, scalar-only, nothing claimed ready.
$caps = pfoa_theme_get_passive_capabilities();
fixture_assert( array_keys( $caps ) === $expected_caps_keys, 'capabilities expose fixed key order' );
fixture_assert( '1.0' === $caps['capabilities_version'], 'capabilities version is 1.0' );
fixture_assert( '0.0.0' === $caps['theme_version'], 'theme version falls back without wp_get_theme' );
fixture_assert( false === $caps['compat_implemented'] && false === $caps['current_implemented'], 'no presentation implemented' );
fixture_assert( false === $caps['compat_ready'] && false === $caps['current_ready'], 'capabilities claim no readiness' );
fixture_assert( array() === $caps['available_components'], 'no ready components claimed' );
fixture_assert( false === $caps['bundle_complete'], 'bundle incomplete' );
fixture_assert( is_string( $caps['notes'] ) && '' !== $caps['notes'], 'capabilities carry a notes string' );
$caps_again = pfoa_theme_get_passive_capabilities();
fixture_assert( $caps === $caps_again && json_encode( $caps ) === json_encode( $caps_again ), 'capabilities deterministic' );

// (1) Switch absent/false/true/invalid via override (constant undefined here).
$switch_cases = array(
	array(
		'label' => 'absent',
		'value' => '__absent__',
		'state' => 'absent',
		'mode'  => 'compat',
	),
	array(
		'label' => 'false',
		'value' => false,
		'state' => 'false',
		'mode'  => 'compat',
	),
	array(
		'label' => 'true',
		'value' => true,
		'state' => 'true',
		'mode'  => 'current',
	),
	array(
		'label' => 'invalid',
		'value' => 'yes',
		'state' => 'invalid',
		'mode'  => 'unavailable',
	),
);
foreach ( $switch_cases as $case ) {
	$snapshot = ( '__absent__' === $case['value'] )
		? pfoa_theme_resolve_presentation_readiness()
		: pfoa_theme_resolve_presentation_readiness( $case['value'] );
	fixture_assert( array_keys( $snapshot ) === $expected_result_keys, $case['label'] . ': result exposes fixed key order' );
	fixture_assert( $case['state'] === $snapshot['request_state'], $case['label'] . ': request_state is ' . $case['state'] );
	fixture_assert( $case['mode'] === $snapshot['requested_mode'], $case['label'] . ': requested_mode is ' . $case['mode'] );
	fixture_assert( 'unavailable' === $snapshot['readiness'], $case['label'] . ': readiness unavailable' );
	fixture_assert( false === $snapshot['compat_ready'] && false === $snapshot['current_ready'], $case['label'] . ': compat/current stay false' );
	foreach ( $snapshot as $key => $value ) {
		if ( in_array( $key, array( 'missing_capabilities', 'incompatible_capabilities' ), true ) ) {
			fixture_assert( is_array( $value ), $case['label'] . ': ' . $key . ' is an array' );
			foreach ( $value as $entry ) {
				fixture_assert( is_string( $entry ), $case['label'] . ': ' . $key . ' holds strings' );
			}
		} elseif ( 'diagnostic' === $key ) {
			fixture_assert( is_string( $value ) && '' !== $value, $case['label'] . ': diagnostic is a non-empty string' );
		} else {
			fixture_assert( is_scalar( $value ), $case['label'] . ': ' . $key . ' is scalar' );
		}
	}
}

// Resolver never memoizes: direct calls leave the snapshot global unset.
fixture_assert( ! isset( $GLOBALS['pfoa_theme_presentation_readiness'] ), 'resolver does not populate global (pending-not-cached)' );

// (2) Missing Cat Profiles (no doubles defined yet).
fixture_assert( false === function_exists( 'pfoa_cat_get_passive_capabilities' ), 'cat passive doubles absent before legacy scenario' );
fixture_assert( false === function_exists( 'pfoa_cat_get_profile_data' ), 'cat profile doubles absent before legacy scenario' );
$base = pfoa_theme_resolve_presentation_readiness();
fixture_assert( false === $base['cat_neutral_available'], 'cat neutral unavailable without profile API' );
fixture_assert( false === $base['cat_passive_available'], 'cat passive unavailable without plugin API' );
fixture_assert( false === $base['cat_neutral_satisfied'] && false === $base['cat_passive_satisfied'], 'cat satisfaction false when missing' );
fixture_assert( in_array( 'cat-passive', $base['missing_capabilities'], true ), 'missing list contains cat-passive' );
fixture_assert( in_array( 'cat-neutral-schema', $base['missing_capabilities'], true ), 'missing list contains cat-neutral-schema' );

// (3) Missing Gallery.
fixture_assert( false === function_exists( 'pfoa_gallery_get_passive_capabilities' ), 'gallery doubles absent' );
fixture_assert( false === $base['gallery_available'], 'gallery unavailable without plugin API' );
fixture_assert( false === $base['gallery_satisfied'], 'gallery satisfaction false when missing' );
fixture_assert( in_array( 'gallery-passive', $base['missing_capabilities'], true ), 'missing list contains gallery-passive' );

// Clean theme without legacy signals: ownership false, still never ready.
fixture_assert( false === $base['legacy_plugin_owns_output'], 'no ownership without legacy signals' );
fixture_assert( false === $base['bundle_complete'], 'bundle incomplete by default' );
fixture_assert( in_array( 'theme-bundle', $base['missing_capabilities'], true ), 'missing list contains theme-bundle' );

// (5) Incomplete bundle override: never ready.
$bundle_cut = pfoa_theme_resolve_presentation_readiness( null, null, null, null, false );
fixture_assert( false === $bundle_cut['bundle_complete'], 'bundle override false stays incomplete' );
fixture_assert( 'unavailable' === $bundle_cut['readiness'], 'incomplete bundle never ready' );
fixture_assert( false === $bundle_cut['compat_ready'] && false === $bundle_cut['current_ready'], 'incomplete bundle keeps compat/current false' );

// (6) Deterministic: identical calls match strictly, JSON-equal, same key order.
$first  = pfoa_theme_resolve_presentation_readiness();
$second = pfoa_theme_resolve_presentation_readiness();
fixture_assert( $first === $second, 'identical resolver calls are strictly identical' );
fixture_assert( json_encode( $first ) === json_encode( $second ), 'identical resolver calls are JSON-equal' );
fixture_assert( array_keys( $first ) === array_keys( $second ), 'identical resolver calls keep key order' );

// (7) No public hook/rendering changes from this file.
fixture_assert( 1 === count( $GLOBALS['fixture_actions'] ), 'exactly one action registered by resolver file' );
$registered = $GLOBALS['fixture_actions'][0];
fixture_assert( 'init' === $registered['hook'], 'sole hook is init' );
fixture_assert( 'pfoa_theme_freeze_presentation_readiness' === $registered['callback'], 'sole callback is the freeze function' );
fixture_assert( 100 === $registered['priority'], 'sole hook priority is 100' );
foreach ( $GLOBALS['fixture_actions'] as $action ) {
	fixture_assert( ! in_array( $action['hook'], array( 'the_content', 'template_include', 'template_redirect', 'wp_footer', 'wp_enqueue_scripts' ), true ), 'no rendering hook registered' );
}
fixture_assert( ! function_exists( 'add_filter' ), 'no filter API touched' );
fixture_assert( ! function_exists( 'pfoa_theme_render' ) && ! function_exists( 'pfoa_theme_compat' ) && ! function_exists( 'pfoa_gallery_render' ), 'no renderers defined' );

// Freeze semantics: store once, return cached, expose via getter, never clobbered.
$frozen_once = pfoa_theme_freeze_presentation_readiness();
fixture_assert( is_array( $frozen_once ) && 'unavailable' === $frozen_once['readiness'], 'freeze returns unavailable snapshot' );
fixture_assert( isset( $GLOBALS['pfoa_theme_presentation_readiness'] ) && $GLOBALS['pfoa_theme_presentation_readiness'] === $frozen_once, 'freeze stores snapshot request-locally' );
fixture_assert( pfoa_theme_freeze_presentation_readiness() === $frozen_once, 'second freeze returns cached snapshot' );
fixture_assert( pfoa_theme_get_frozen_presentation_readiness() === $frozen_once, 'getter returns frozen snapshot' );
$divergent = pfoa_theme_resolve_presentation_readiness( true );
fixture_assert( 'current' === $divergent['requested_mode'], 'override resolve still computes divergently' );
fixture_assert( $GLOBALS['pfoa_theme_presentation_readiness'] === $frozen_once, 'direct resolve never clobbers frozen snapshot' );

// Constant-defined switch paths via subprocesses (constants are per-process).
$subprocess_cases = array(
	array(
		'label' => 'constant-absent',
		'setup' => '',
		'state' => 'absent',
		'mode'  => 'compat',
	),
	array(
		'label' => 'constant-false',
		'setup' => "define( 'PFOA_THEME_PRESENTATION', false ); ",
		'state' => 'false',
		'mode'  => 'compat',
	),
	array(
		'label' => 'constant-true',
		'setup' => "define( 'PFOA_THEME_PRESENTATION', true ); ",
		'state' => 'true',
		'mode'  => 'current',
	),
	array(
		'label' => 'constant-invalid',
		'setup' => "define( 'PFOA_THEME_PRESENTATION', 'current' ); ",
		'state' => 'invalid',
		'mode'  => 'unavailable',
	),
);
foreach ( $subprocess_cases as $case ) {
	$seen = fixture_subprocess_resolve( $case['setup'], $case['label'] );
	fixture_assert( $case['state'] === $seen['request_state'], $case['label'] . ': request_state is ' . $case['state'] );
	fixture_assert( $case['mode'] === $seen['requested_mode'], $case['label'] . ': requested_mode is ' . $case['mode'] );
	fixture_assert( 'unavailable' === $seen['readiness'], $case['label'] . ': readiness unavailable' );
	fixture_assert( false === $seen['compat_ready'] && false === $seen['current_ready'], $case['label'] . ': compat/current stay false' );
	// Probes are undefined in the bare subprocess: fail-closed ownership holds.
	fixture_assert( true === $seen['legacy_plugin_owns_output'], $case['label'] . ': fail-closed ownership without probes' );
}

// Neutral schema positive path (late doubles defined at runtime so the
// missing-API assertions above observe a clean environment).
$GLOBALS['fixture_enable_neutral_doubles'] = true;
if ( ! defined( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' ) ) {
	define( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION', '1.0' );
}
if ( ! empty( $GLOBALS['fixture_enable_neutral_doubles'] ) ) {
	eval(
		'function pfoa_cat_get_profile_data( $profile_id ) { return array( \'id\' => (int) $profile_id ); }'
	);
}
$neutral = pfoa_theme_resolve_presentation_readiness();
fixture_assert( true === $neutral['cat_neutral_available'], 'neutral schema detected when profile API present' );
fixture_assert( 'unavailable' === $neutral['readiness'], 'neutral presence alone never flips readiness' );

// (4) Legacy ownership detected via hooks + legacy passive report.
$GLOBALS['fixture_enable_legacy_doubles'] = true;
if ( ! defined( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' ) ) {
	define( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION', '1.0' );
}
if ( ! empty( $GLOBALS['fixture_enable_legacy_doubles'] ) ) {
	eval(
		'function pfoa_cat_get_passive_capabilities() { return array( ' .
		'\'capabilities_version\' => \'1.0\', ' .
		'\'neutral_schema\' => \'1.0\', ' .
		'\'neutral_data_available\' => false, ' .
		'\'public_ownership\' => \'legacy-plugin\', ' .
		'\'neutral_only\' => false, ' .
		'\'legacy_public_renderers_registered\' => true ); }'
	);
}
$GLOBALS['fixture_shortcodes']['pfoa_cat_cards']                            = true;
$GLOBALS['fixture_filters']['the_content|pfoa_cat_filter_adoptable_content']  = 20;
$GLOBALS['fixture_filters']['the_content|pfoa_cat_filter_lifecycle_content']  = 25;
$GLOBALS['fixture_filters']['template_include|pfoa_cat_template_include']     = 10;
$GLOBALS['fixture_actions_present']['template_redirect|pfoa_cat_serve_fragment'] = 1;
$legacy = pfoa_theme_resolve_presentation_readiness();
fixture_assert( true === $legacy['cat_passive_available'], 'legacy cat passive report detected' );
fixture_assert( false === $legacy['cat_passive_satisfied'], 'legacy cat passive report not satisfied' );
fixture_assert( true === $legacy['legacy_plugin_owns_output'], 'legacy ownership detected' );
fixture_assert( in_array( 'legacy-plugin-owns-output', $legacy['incompatible_capabilities'], true ), 'incompatible list flags legacy ownership' );
fixture_assert( 'unavailable' === $legacy['readiness'], 'legacy ownership keeps readiness unavailable' );
fixture_assert( false === $legacy['compat_ready'] && false === $legacy['current_ready'], 'legacy ownership keeps compat/current false' );

// No storage/enqueue residue across every resolver path above.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'resolver leaves no DB/enqueue residue' );

fwrite( STDOUT, "PASS: theme passive resolver fixture (switch x4, missing cat/gallery, legacy ownership, bundle, determinism, hooks, freeze)\n" );
