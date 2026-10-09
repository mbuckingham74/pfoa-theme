<?php
/**
 * Focused fixture for the inactive theme-owned compat card selector.
 *
 * Requires pfoa-theme/inc/theme-cat-cards-compat-selector.php directly
 * (never the functions.php monolith) with WordPress doubles stubbed inline.
 * No network, database, or lifecycle dependency. Single-process: the
 * missing-dependency gate is verified by asserting the selector source holds
 * fail-closed guards for every collaborator, since live doubles cannot be
 * undefined mid-process; the invalid-snapshot cases act as the runtime
 * failure proxies.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' ) ) {
	define( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION', '1.0' );
}

$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_forbidden_read']  = false;
$GLOBALS['fixture_posts']           = array();
$GLOBALS['fixture_snapshots']       = array();

class WP_Post {
	public $ID = 0;
	public $post_name = '';
	public $post_title = '';
	public $post_type = '';
	public $post_status = '';
	public $post_date = '';
}

class WP_Query {
	public $posts = array();

	public function __construct( $args = array() ) {
		$pool = isset( $GLOBALS['fixture_posts'] ) && is_array( $GLOBALS['fixture_posts'] )
			? $GLOBALS['fixture_posts']
			: array();

		$filtered = array();

		foreach ( $pool as $candidate ) {
			if ( ! $candidate instanceof WP_Post ) {
				continue;
			}

			if ( isset( $args['post_type'] ) && $candidate->post_type !== $args['post_type'] ) {
				continue;
			}

			if ( isset( $args['post_status'] ) && $candidate->post_status !== $args['post_status'] ) {
				continue;
			}

			$filtered[] = $candidate;
		}

		if ( isset( $args['post_name__in'] ) && is_array( $args['post_name__in'] ) ) {
			$by_slug = array();

			foreach ( $filtered as $candidate ) {
				if ( ! isset( $by_slug[ $candidate->post_name ] ) ) {
					$by_slug[ $candidate->post_name ] = $candidate;
				}
			}

			$ordered = array();

			foreach ( $args['post_name__in'] as $wanted ) {
				$key = (string) $wanted;

				if ( isset( $by_slug[ $key ] ) ) {
					$ordered[] = $by_slug[ $key ];
				}
			}

			$this->posts = $ordered;

			return;
		}

		$orderby = isset( $args['orderby'] ) ? $args['orderby'] : null;
		$order   = isset( $args['order'] ) ? strtoupper( (string) $args['order'] ) : 'DESC';

		if ( is_array( $orderby ) ) {
			usort(
				$filtered,
				function ( $a, $b ) {
					$date_a = (string) $a->post_date;
					$date_b = (string) $b->post_date;

					if ( $date_a !== $date_b ) {
						return strcmp( $date_b, $date_a );
					}

					return (int) $b->ID - (int) $a->ID;
				}
			);
		} elseif ( 'title' === $orderby ) {
			usort(
				$filtered,
				function ( $a, $b ) use ( $order ) {
					$compared = strcasecmp( (string) $a->post_title, (string) $b->post_title );

					if ( 0 !== $compared ) {
						return ( 'ASC' === $order ) ? $compared : -$compared;
					}

					$delta = (int) $a->ID - (int) $b->ID;

					return ( 'ASC' === $order ) ? $delta : -$delta;
				}
			);
		}

		$this->posts = array_values( $filtered );
	}
}

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
function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['fixture_forbidden_read'] = true;
	return '';
}
function metadata_exists( $type, $id, $key ) {
	$GLOBALS['fixture_forbidden_read'] = true;
	return false;
}

function pfoa_cat_migrated_baseline_slugs() {
	return array(
		'mary-paul',
		'bongo',
		'athena-hunter',
		'sonny',
		'shadow',
		'oliver-isaac',
		'spencer-thompson-wilbur',
		'sugar-spice',
		'picasso',
		'martone',
		'newest-kittens',
		'storm-tempest',
		'sable-mom',
	);
}

function pfoa_cat_get_profile_data( $post_id ) {
	$id = (int) $post_id;

	if ( ! isset( $GLOBALS['fixture_snapshots'][ $id ] ) ) {
		return null;
	}

	return $GLOBALS['fixture_snapshots'][ $id ];
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-cards-compat-selector.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_make_post( $id, $slug, $title, $type = 'pfoa_cat', $status = 'publish', $date = '2026-01-01 10:00:00' ) {
	$post              = new WP_Post();
	$post->ID          = (int) $id;
	$post->post_name   = (string) $slug;
	$post->post_title  = (string) $title;
	$post->post_type   = (string) $type;
	$post->post_status = (string) $status;
	$post->post_date   = (string) $date;

	return $post;
}

function fixture_make_snapshot( $id, $title, $overrides = array() ) {
	$snapshot = array(
		'schema_version' => '1.0',
		'post_id'        => (int) $id,
		'post_type'      => 'pfoa_cat',
		'post_status'    => 'publish',
		'name'           => (string) $title,
		'publication'    => array(
			'has_required_card_image' => true,
		),
		'lifecycle'      => array(
			'eligible_for_adoptable' => true,
			'pending'                => false,
			'effectively_pending'    => false,
		),
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

function fixture_seed_pool() {
	$GLOBALS['fixture_posts']     = array();
	$GLOBALS['fixture_snapshots'] = array();

	$defs = array(
		array( 4, 'sonny', 'Sonny', 'pfoa_cat', 'publish', '2026-01-04 10:00:00' ),
		array( 11, 'alpha-new', 'Alpha', 'pfoa_cat', 'publish', '2026-07-01 10:00:00' ),
		array( 30, 'adopted-cat', 'Adopted Cat', 'pfoa_cat', 'publish', '2026-04-01 10:00:00' ),
		array( 2, 'bongo', 'Bongo', 'pfoa_cat', 'publish', '2026-01-02 10:00:00' ),
		array( 31, 'pending-cat', 'Pending Cat', 'pfoa_cat', 'publish', '2026-05-01 10:00:00' ),
		array( 1, 'mary-paul', 'Mary Paul', 'pfoa_cat', 'publish', '2026-01-01 10:00:00' ),
		array( 10, 'zara-new', 'Zara', 'pfoa_cat', 'publish', '2026-06-01 10:00:00' ),
		array( 3, 'athena-hunter', 'Athena Hunter', 'pfoa_cat', 'publish', '2026-01-03 10:00:00' ),
		array( 32, 'noimage-cat', 'Noimage Cat', 'pfoa_cat', 'publish', '2026-03-01 10:00:00' ),
		array( 33, 'invalid-life', 'Invalid Life', 'pfoa_cat', 'publish', '2026-02-01 10:00:00' ),
		array( 20, 'draft-cat', 'Draft Cat', 'pfoa_cat', 'draft', '2026-08-01 10:00:00' ),
		array( 21, 'trash-cat', 'Trash Cat', 'pfoa_cat', 'trash', '2026-08-02 10:00:00' ),
	);

	foreach ( $defs as $def ) {
		$GLOBALS['fixture_posts'][] = fixture_make_post( $def[0], $def[1], $def[2], $def[3], $def[4], $def[5] );

		$overrides = array();

		if ( 'draft' === $def[4] ) {
			$overrides['post_status'] = 'draft';
		} elseif ( 'trash' === $def[4] ) {
			$overrides['post_status'] = 'trash';
		}

		$GLOBALS['fixture_snapshots'][ (int) $def[0] ] = fixture_make_snapshot( (int) $def[0], $def[2], $overrides );
	}

	$GLOBALS['fixture_snapshots'][30]['lifecycle']['eligible_for_adoptable'] = false;
	$GLOBALS['fixture_snapshots'][31]['lifecycle']['pending']                = true;
	$GLOBALS['fixture_snapshots'][31]['lifecycle']['effectively_pending']     = true;
	$GLOBALS['fixture_snapshots'][32]['publication']['has_required_card_image'] = false;
	$GLOBALS['fixture_snapshots'][33]['lifecycle']['eligible_for_adoptable'] = false;
}

function fixture_snapshot_ids( $result ) {
	$ids = array();

	if ( ! isset( $result['snapshots'] ) || ! is_array( $result['snapshots'] ) ) {
		return $ids;
	}

	foreach ( $result['snapshots'] as $snapshot ) {
		$ids[] = isset( $snapshot['post_id'] ) ? (int) $snapshot['post_id'] : 0;
	}

	return $ids;
}

fixture_seed_pool();

// Selector entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_select_compat_cards' ), 'selector function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_CARDS_COMPAT_SELECTOR_LOADED' ), 'loaded guard constant defined' );

// (1) Bare ordering: newcomers first in query order, then fixed baseline order.
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'success' === $result['status'], 'bare: success status' );
fixture_assert( null === $result['error'], 'bare: null error' );
fixture_assert( array( 11, 10, 31, 1, 2, 3, 4 ) === fixture_snapshot_ids( $result ), 'bare: newcomers (Alpha, Zara, Pending) then baseline (Mary, Bongo, Athena, Sonny)' );

// (2) Bare excludes adopted, imageless, invalid-lifecycle, draft, and trash records.
$ids = fixture_snapshot_ids( $result );
foreach ( array( 30, 32, 33, 20, 21 ) as $excluded ) {
	fixture_assert( ! in_array( $excluded, $ids, true ), 'bare: excludes id ' . $excluded );
}

// (3) Pending inclusion: pending flags never disqualify while eligible.
fixture_assert( in_array( 31, $ids, true ), 'bare: pending record included' );
$pending_snapshot = null;
foreach ( $result['snapshots'] as $snapshot ) {
	if ( 31 === (int) $snapshot['post_id'] ) {
		$pending_snapshot = $snapshot;
	}
}
fixture_assert( is_array( $pending_snapshot ), 'bare: pending snapshot present' );
fixture_assert( true === $pending_snapshot['lifecycle']['pending'], 'bare: pending flag true on included snapshot' );
fixture_assert( true === $pending_snapshot['lifecycle']['effectively_pending'], 'bare: effectively-pending flag true on included snapshot' );
fixture_assert( true === $pending_snapshot['lifecycle']['eligible_for_adoptable'], 'bare: pending snapshot eligible' );

// (4) Explicit order: seeds first in seed order, remaining by title ASC.
$result = pfoa_theme_select_compat_cards( array( 'sonny', 'mary-paul' ) );
fixture_assert( 'success' === $result['status'], 'explicit: success status' );
fixture_assert( array( 4, 1, 11, 3, 2, 31, 10 ) === fixture_snapshot_ids( $result ), 'explicit: Sonny, Mary, then Alpha, Athena Hunter, Bongo, Pending Cat, Zara' );

// (5) Explicit dedupe: repeats and padding collapse, survivors keep seed order.
$result = pfoa_theme_select_compat_cards( array( 'mary-paul', 'sonny', 'mary-paul', ' sonny ', '' ) );
fixture_assert( array( 1, 4, 11, 3, 2, 31, 10 ) === fixture_snapshot_ids( $result ), 'explicit: deduped seeds Mary, Sonny then title ASC remainder' );

// (6) Slugs are ordering hints, not membership restrictions: a single seed still appends the rest.
$result = pfoa_theme_select_compat_cards( array( 'sonny' ) );
$ids    = fixture_snapshot_ids( $result );
fixture_assert( 4 === $ids[0], 'hint: seeded Sonny first' );
fixture_assert( 7 === count( $ids ), 'hint: all other eligible appended' );
fixture_assert( array( 4, 11, 3, 2, 1, 31, 10 ) === $ids, 'hint: Sonny then title ASC remainder (Alpha, Athena, Bongo, Mary, Pending, Zara)' );

// (7) Unknown slugs are ignored without failing the selection.
$result = pfoa_theme_select_compat_cards( array( 'ghost-slug', 'bongo' ) );
$ids    = fixture_snapshot_ids( $result );
fixture_assert( 'success' === $result['status'], 'unknown: success status' );
fixture_assert( 2 === $ids[0], 'unknown: known seed Bongo first' );
fixture_assert( 7 === count( $ids ), 'unknown: ghost slug appends nothing and removes nothing' );

// (8) Missing required-image flag skips that record only (no failure).
fixture_seed_pool();
unset( $GLOBALS['fixture_snapshots'][10]['publication']['has_required_card_image'] );
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'success' === $result['status'], 'image-missing: success status' );
fixture_assert( array( 11, 31, 1, 2, 3, 4 ) === fixture_snapshot_ids( $result ), 'image-missing: Zara skipped, rest intact' );

// (9) Missing lifecycle flag skips that record only (no failure).
fixture_seed_pool();
unset( $GLOBALS['fixture_snapshots'][10]['lifecycle']['eligible_for_adoptable'] );
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'success' === $result['status'], 'lifecycle-missing: success status' );
fixture_assert( array( 11, 31, 1, 2, 3, 4 ) === fixture_snapshot_ids( $result ), 'lifecycle-missing: Zara skipped, rest intact' );

// (10) Schema mismatch fails the whole selection closed (no partial list).
fixture_seed_pool();
$GLOBALS['fixture_snapshots'][2]['schema_version'] = '2.0';
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'failure' === $result['status'], 'schema: failure status' );
fixture_assert( array() === $result['snapshots'], 'schema: empty snapshots' );
fixture_assert( is_string( $result['error'] ) && '' !== $result['error'], 'schema: non-empty error code' );

// (11) Null snapshot fails the whole selection closed.
fixture_seed_pool();
unset( $GLOBALS['fixture_snapshots'][1] );
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'failure' === $result['status'], 'null: failure status' );
fixture_assert( array() === $result['snapshots'], 'null: empty snapshots' );

// (12) ID mismatch fails the whole selection closed.
fixture_seed_pool();
$GLOBALS['fixture_snapshots'][3]['post_id'] = 9999;
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'failure' === $result['status'], 'id: failure status' );
fixture_assert( array() === $result['snapshots'], 'id: empty snapshots' );

// (13) Status mismatch fails the whole selection closed.
fixture_seed_pool();
$GLOBALS['fixture_snapshots'][4]['post_status'] = 'draft';
$result = pfoa_theme_select_compat_cards( array() );
fixture_assert( 'failure' === $result['status'], 'status: failure status' );
fixture_assert( array() === $result['snapshots'], 'status: empty snapshots' );

// (14) Missing dependencies fail closed: single-process cannot undefine live
// doubles, so assert the selector source holds fail-closed guards for every
// collaborator; cases (10)-(13) above act as the runtime failure proxies.
fixture_seed_pool();
$selector_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-cards-compat-selector.php' );
fixture_assert( false !== strpos( $selector_source, "class_exists( 'WP_Query' )" ), 'missing dep: guards query layer' );
fixture_assert( false !== strpos( $selector_source, "function_exists( 'pfoa_cat_get_profile_data' )" ), 'missing dep: guards neutral provider' );
fixture_assert( false !== strpos( $selector_source, 'pfoa_cat_migrated_baseline_slugs' ), 'baseline: prefers provider baseline helper' );
fixture_assert( false !== strpos( $selector_source, "'sable-mom'" ), 'baseline: internal fallback copy present' );
fixture_assert( false !== strpos( $selector_source, "'posts_per_page' => -1" ), 'query: unbounded count base arg present' );
fixture_assert( false !== strpos( $selector_source, "'no_found_rows'" ), 'query: no-totals base arg present' );
fixture_assert( false !== strpos( $selector_source, "'post_name__in'" ), 'query: seeded slug arg present' );

// (15) No hooks, writes, or forbidden reads at require/select time.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during selection' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/select time' );
fixture_assert( false === $GLOBALS['fixture_forbidden_read'], 'no forbidden storage reads during selection' );
$doc_end = strpos( $selector_source, '*/' );
$code    = ( false === $doc_end ) ? $selector_source : substr( $selector_source, $doc_end + 2 );
$code_body = (string) preg_replace( "/if\s*\(\s*!\s*defined\(\s*'ABSPATH'\s*\)\s*\)\s*\{\s*exit;\s*\}/", '', $code );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_', 'template_redirect', 'query_vars' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'selector code has no ' . $needle );
}
foreach ( array( 'get_post_meta', 'metadata_exists', '_pfoa_', '$wpdb', 'do_action' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'selector code has no ' . $needle );
}
foreach ( array( 'update_option', 'update_post_meta', 'delete_option', 'delete_post_meta', 'wp_insert', 'wp_update', 'wp_delete', 'wp_enqueue_', 'get_post(', 'echo', 'exit' ) as $needle ) {
	fixture_assert( false === strpos( $code_body, $needle ), 'selector code has no ' . $needle );
}
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-cards-compat-selector' ), 'functions.php does not require selector file' );

fwrite( STDOUT, "PASS: theme cat cards compat selector fixture (bare newcomer/baseline order, explicit seed + title ASC, published-only, adopted exclusion + pending inclusion, image/lifecycle skips, schema/null/id/status fail-closed, guarded deps, no hooks/writes/reads)\n" );
