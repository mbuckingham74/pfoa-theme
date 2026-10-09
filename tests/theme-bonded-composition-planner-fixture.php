<?php
/**
 * Focused fixture for the inactive theme-owned bonded composition planner.
 *
 * Requires pfoa-theme/inc/theme-bonded-composition-planner.php directly
 * (never the functions.php monolith) with no WordPress dependency. No
 * network, database, or provider dependency. The planner input is a plain
 * ordered list of neutral schema 1.0 snapshot arrays built by the helper
 * below, mirroring the selector fixture style.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_db_touched']      = false;

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_shortcode( $tag, $callback ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function has_action( $hook, $callback = false ) {
	return false;
}
function has_filter( $hook, $callback = false ) {
	return false;
}
function update_option( $option, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function update_post_meta( $post_id, $key, $value = null ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-bonded-composition-planner.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_make_snapshot( $id, $name = 'Cat', $overrides = array() ) {
	$snapshot = array(
		'schema_version' => '1.0',
		'post_id'        => (int) $id,
		'post_type'      => 'pfoa_cat',
		'post_status'    => 'publish',
		'name'           => (string) $name,
		'bonded'         => array(
			'valid'      => false,
			'partner_id' => 0,
			'pair'       => array(),
		),
		'lifecycle'      => array(
			'bonded_state'           => 'single',
			'eligible_for_adoptable' => true,
		),
		'publication'    => array(
			'has_required_card_image' => true,
		),
		'legacy'         => null,
	);

	foreach ( $overrides as $key => $value ) {
		if ( is_array( $value ) && isset( $snapshot[ $key ] ) && is_array( $snapshot[ $key ] ) ) {
			$snapshot[ $key ] = array_merge( $snapshot[ $key ], $value );
		} else {
			$snapshot[ $key ] = $value;
		}
	}

	return $snapshot;
}

function fixture_make_bonded_snapshot( $id, $partner_id, $pair, $name = 'Cat' ) {
	return fixture_make_snapshot(
		$id,
		$name,
		array(
			'bonded'    => array(
				'valid'      => true,
				'partner_id' => (int) $partner_id,
				'pair'       => array( (int) $pair[0], (int) $pair[1] ),
			),
			'lifecycle' => array(
				'bonded_state' => 'bonded',
			),
		)
	);
}

function fixture_assert_failure( $result, $code, $label ) {
	fixture_assert( is_array( $result ), $label . ': result is array' );
	fixture_assert( 'failure' === $result['status'], $label . ': failure status' );
	fixture_assert( array() === $result['units'], $label . ': empty units' );
	fixture_assert( $code === $result['error'], $label . ': error code ' . $code );
	fixture_assert( $code === $result['reason'], $label . ': reason code ' . $code );
}

fixture_assert( function_exists( 'pfoa_theme_plan_bonded_composition' ), 'planner function exists' );
fixture_assert( defined( 'PFOA_THEME_BONDED_COMPOSITION_PLANNER_LOADED' ), 'loaded guard constant defined' );

// (a) Single individual yields one individual unit.
$result = pfoa_theme_plan_bonded_composition( array( fixture_make_snapshot( 1, 'Solo' ) ) );
fixture_assert( 'success' === $result['status'], 'a: success status' );
fixture_assert( null === $result['error'], 'a: null error' );
fixture_assert( null === $result['reason'], 'a: null reason' );
fixture_assert( 1 === count( $result['units'] ), 'a: one unit' );
fixture_assert( 'individual' === $result['units'][0]['type'], 'a: individual type' );
fixture_assert( array( 1 ) === $result['units'][0]['member_ids'], 'a: member id' );
fixture_assert( 1 === $result['units'][0]['snapshots'][0]['post_id'], 'a: snapshot passthrough' );

// (b) Valid reciprocal adjacent pair yields one bonded unit.
$a = fixture_make_bonded_snapshot( 101, 102, array( 101, 102 ), 'Anna' );
$b = fixture_make_bonded_snapshot( 102, 101, array( 101, 102 ), 'Bella' );
$result = pfoa_theme_plan_bonded_composition( array( $a, $b ) );
fixture_assert( 'success' === $result['status'], 'b: success status' );
fixture_assert( 1 === count( $result['units'] ), 'b: one unit' );
fixture_assert( 'bonded' === $result['units'][0]['type'], 'b: bonded type' );
fixture_assert( array( 101, 102 ) === $result['units'][0]['member_ids'], 'b: member ids' );
fixture_assert( 101 === $result['units'][0]['snapshots'][0]['post_id'], 'b: first snapshot' );
fixture_assert( 102 === $result['units'][0]['snapshots'][1]['post_id'], 'b: second snapshot' );

// (c) Canonical order: input [B,A] with pair [A,B] reorders inside the unit.
$a = fixture_make_bonded_snapshot( 101, 102, array( 101, 102 ), 'Anna' );
$b = fixture_make_bonded_snapshot( 102, 101, array( 101, 102 ), 'Bella' );
$result = pfoa_theme_plan_bonded_composition( array( $b, $a ) );
fixture_assert( 'success' === $result['status'], 'c: success status' );
fixture_assert( 1 === count( $result['units'] ), 'c: one unit' );
fixture_assert( array( 101, 102 ) === $result['units'][0]['member_ids'], 'c: canonical member ids' );
fixture_assert( 101 === $result['units'][0]['snapshots'][0]['post_id'], 'c: canonical first snapshot' );
fixture_assert( 102 === $result['units'][0]['snapshots'][1]['post_id'], 'c: canonical second snapshot' );

// (c2) Unit placement preserved: leader individual stays first.
$leader = fixture_make_snapshot( 7, 'Leader' );
$result = pfoa_theme_plan_bonded_composition( array( $leader, $b, $a ) );
fixture_assert( 'success' === $result['status'], 'c2: success status' );
fixture_assert( 2 === count( $result['units'] ), 'c2: two units' );
fixture_assert( 'individual' === $result['units'][0]['type'], 'c2: leader individual first' );
fixture_assert( array( 7 ) === $result['units'][0]['member_ids'], 'c2: leader member id' );
fixture_assert( 'bonded' === $result['units'][1]['type'], 'c2: bonded second' );
fixture_assert( array( 101, 102 ) === $result['units'][1]['member_ids'], 'c2: bonded canonical ids' );

// (d) Missing partner fails closed.
$lone = fixture_make_bonded_snapshot( 201, 202, array( 201, 202 ), 'Lone' );
$result = pfoa_theme_plan_bonded_composition( array( $lone ) );
fixture_assert_failure( $result, 'missing_bonded_partner', 'd' );

// (e1) Half link: claimant valid, partner side valid=false.
$pa = fixture_make_bonded_snapshot( 201, 202, array( 201, 202 ), 'Pa' );
$pb = fixture_make_snapshot( 202, 'Pb' );
$result = pfoa_theme_plan_bonded_composition( array( $pa, $pb ) );
fixture_assert_failure( $result, 'half_link_or_inconsistent_metadata', 'e1' );

// (e2) Half link: partner misses the image gate (no downgrade to individual).
$qa = fixture_make_bonded_snapshot( 211, 212, array( 211, 212 ), 'Qa' );
$qb = fixture_make_bonded_snapshot( 212, 211, array( 211, 212 ), 'Qb' );
$qb['publication']['has_required_card_image'] = false;
$result = pfoa_theme_plan_bonded_composition( array( $qa, $qb ) );
fixture_assert_failure( $result, 'half_link_or_inconsistent_metadata', 'e2' );

// (e3) Half link: partner names nobody back (empty pair, unilateral claim).
$ra = fixture_make_bonded_snapshot( 221, 222, array( 221, 222 ), 'Ra' );
$rb = fixture_make_snapshot(
	222,
	'Rb',
	array(
		'bonded'    => array(
			'valid'      => true,
			'partner_id' => 221,
			'pair'       => array(),
		),
		'lifecycle' => array(
			'bonded_state' => 'bonded',
		),
	)
);
$result = pfoa_theme_plan_bonded_composition( array( $ra, $rb ) );
fixture_assert_failure( $result, 'half_link_or_inconsistent_metadata', 'e3' );

// (f1) Duplicate post_id fails the whole plan.
$dup = fixture_make_snapshot( 301, 'Dup' );
$result = pfoa_theme_plan_bonded_composition( array( $dup, fixture_make_snapshot( 301, 'Dup Again' ) ) );
fixture_assert_failure( $result, 'duplicate_profile_id', 'f1' );

// (f2) Conflicting pair membership: B names a third profile.
$fa = fixture_make_bonded_snapshot( 401, 402, array( 401, 402 ), 'Fa' );
$fb = fixture_make_bonded_snapshot( 402, 401, array( 401, 403 ), 'Fb' );
$fc = fixture_make_snapshot( 403, 'Fc' );
$result = pfoa_theme_plan_bonded_composition( array( $fa, $fb, $fc ) );
fixture_assert_failure( $result, 'conflicting_pair_membership', 'f2' );

// (f3) Three-way claim on one profile id fails as conflicting.
$ga = fixture_make_bonded_snapshot( 501, 502, array( 501, 502 ), 'Ga' );
$gb = fixture_make_bonded_snapshot( 502, 501, array( 501, 502 ), 'Gb' );
$gc = fixture_make_bonded_snapshot( 503, 501, array( 503, 501 ), 'Gc' );
$result = pfoa_theme_plan_bonded_composition( array( $ga, $gb, $gc ) );
fixture_assert_failure( $result, 'conflicting_pair_membership', 'f3' );

// (f4) Set-equal pairs in different order fail canonical discipline.
$ha = fixture_make_bonded_snapshot( 601, 602, array( 601, 602 ), 'Ha' );
$hb = fixture_make_bonded_snapshot( 602, 601, array( 602, 601 ), 'Hb' );
$result = pfoa_theme_plan_bonded_composition( array( $ha, $hb ) );
fixture_assert_failure( $result, 'conflicting_pair_membership', 'f4' );

// (g) Nonadjacent partners fail and require movement.
$na = fixture_make_bonded_snapshot( 701, 702, array( 701, 702 ), 'Na' );
$nx = fixture_make_snapshot( 799, 'Between' );
$nb = fixture_make_bonded_snapshot( 702, 701, array( 701, 702 ), 'Nb' );
$result = pfoa_theme_plan_bonded_composition( array( $na, $nx, $nb ) );
fixture_assert_failure( $result, 'nonadjacent_partners_require_movement', 'g' );

// (h) Legacy grouped profile stays one individual unit next to another.
$legacy_profile = fixture_make_snapshot(
	801,
	'Legacy Group',
	array(
		'legacy' => array(
			'members' => array( 'Legacy A', 'Legacy B' ),
		),
	)
);
$neighbour = fixture_make_snapshot( 802, 'Neighbour' );
$result = pfoa_theme_plan_bonded_composition( array( $legacy_profile, $neighbour ) );
fixture_assert( 'success' === $result['status'], 'h: success status' );
fixture_assert( 2 === count( $result['units'] ), 'h: two units' );
fixture_assert( 'individual' === $result['units'][0]['type'], 'h: legacy individual' );
fixture_assert( array( 801 ) === $result['units'][0]['member_ids'], 'h: legacy member id' );
fixture_assert( 1 === count( $result['units'][0]['snapshots'] ), 'h: legacy not split' );
fixture_assert( 'individual' === $result['units'][1]['type'], 'h: neighbour individual' );

// (i) Adopted-together style nonbonded adjacency never merges.
$ua = fixture_make_snapshot( 901, 'Una' );
$ub = fixture_make_snapshot( 902, 'Uba' );
$result = pfoa_theme_plan_bonded_composition( array( $ua, $ub ) );
fixture_assert( 'success' === $result['status'], 'i: success status' );
fixture_assert( 2 === count( $result['units'] ), 'i: two units' );
fixture_assert( 'individual' === $result['units'][0]['type'], 'i: first individual' );
fixture_assert( 'individual' === $result['units'][1]['type'], 'i: second individual' );
fixture_assert( array( 901 ) === $result['units'][0]['member_ids'], 'i: first id' );
fixture_assert( array( 902 ) === $result['units'][1]['member_ids'], 'i: second id' );

// (k) Missing required fields fail the whole plan.
$base = fixture_make_snapshot( 1001, 'Base' );

$missing = $base;
unset( $missing['bonded'] );
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-bonded' );

$missing = $base;
unset( $missing['lifecycle'] );
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-lifecycle' );

$missing = $base;
unset( $missing['publication'] );
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-publication' );

$missing = $base;
unset( $missing['legacy'] );
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-legacy' );

$missing = $base;
$missing['schema_version'] = '2.0';
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-schema' );

$missing = $base;
$missing['bonded'] = array(
	'valid'      => false,
	'partner_id' => 0,
);
$result = pfoa_theme_plan_bonded_composition( array( $missing ) );
fixture_assert_failure( $result, 'missing_required_fields', 'k-pair' );

// (j) No hooks, writes, or platform calls at require/plan time.
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/plan time' );
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no writes during plan' );
fixture_assert( false === has_action( 'init', 'pfoa_theme_plan_bonded_composition' ), 'planner not hooked' );
$planner_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-bonded-composition-planner.php' );
$doc_end        = strpos( $planner_source, '*/' );
$code           = ( false === $doc_end ) ? $planner_source : substr( $planner_source, $doc_end + 2 );
$fn_doc_end     = strpos( $code, '*/' );
if ( false !== $fn_doc_end ) {
	$code = substr( $code, 0, strpos( $code, '/**' ) ) . substr( $code, $fn_doc_end + 2 );
}
$code_body = (string) preg_replace( "/if\s*\(\s*!\s*defined\(\s*'ABSPATH'\s*\)\s*\)\s*\{\s*exit;\s*\}/", '', $code );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_', 'template_redirect', 'query_vars', 'template_include' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'planner code has no ' . $needle );
}
foreach ( array( 'WP_Query', 'get_post_meta', 'metadata_exists', '_pfoa_', '$wpdb', 'do_action', 'has_action' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'planner code has no ' . $needle );
}
foreach ( array( 'update_option', 'update_post_meta', 'delete_option', 'delete_post_meta', 'wp_insert', 'wp_update', 'wp_delete', 'wp_enqueue_', 'get_post(', 'file_put_contents', 'fopen', 'echo', 'print', 'exit', 'shortcode' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'planner code has no ' . $needle );
}
foreach ( array( 'wp_' ) as $needle ) {
	fixture_assert( false === stripos( $code_body, $needle ), 'planner code has no ' . $needle );
}
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-bonded-composition-planner' ), 'functions.php does not require planner file' );

fwrite( STDOUT, "PASS: theme bonded composition planner fixture (individual, bonded, canonical order + placement, missing partner, half links, duplicates, conflicts, nonadjacent, legacy, nonbonded adjacency, missing fields, no hooks/writes/reads)\n" );
