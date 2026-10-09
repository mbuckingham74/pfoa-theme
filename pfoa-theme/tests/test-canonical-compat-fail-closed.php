<?php
/**
 * Fail-closed fixture for the inactive canonical Cat Profile compat template.
 *
 * Self-contained: run the full matrix with WP stubs via:
 *   php pfoa-theme/tests/test-canonical-compat-fail-closed.php
 * (no PFOA_SCENARIO = runner mode, spawns one worker process per scenario).
 *
 * A single scenario can be run directly with:
 *   PFOA_SCENARIO=<name> php pfoa-theme/tests/test-canonical-compat-fail-closed.php
 *
 * Scenarios: complete | incomplete | empty_renderer | mismatched_post_id |
 *             invalid_schema | null_data | no_provider | no_renderer | static
 *
 * Exits nonzero on any failure; every scenario prints a SCENARIO ... PASS/FAIL line.
 *
 * @package PFOA
 */

declare(strict_types=1);

const PFOA_COMPAT_TEMPLATE_FILE = __DIR__ . '/../inc/templates/theme-cat-single-compat-template.php';

$worker_scenario = getenv( 'PFOA_SCENARIO' );

if ( ! is_string( $worker_scenario ) || '' === $worker_scenario ) {
	// Runner mode: one worker process per scenario (workers define different
	// stub sets, so each needs a fresh process).
	$scenarios = array(
		'complete',
		'incomplete',
		'empty_renderer',
		'mismatched_post_id',
		'invalid_schema',
		'null_data',
		'no_provider',
		'no_renderer',
		'static',
	);
	$failed    = 0;
	foreach ( $scenarios as $name ) {
		putenv( 'PFOA_SCENARIO=' . $name );
		$lines = array();
		$code  = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' 2>&1', $lines, $code );
		foreach ( $lines as $line ) {
			echo $line . "\n";
		}
		if ( 0 !== $code ) {
			++$failed;
		}
	}
	putenv( 'PFOA_SCENARIO' );
	if ( 0 === $failed ) {
		echo "MATRIX: all " . count( $scenarios ) . " scenarios PASS\n";
		exit( 0 );
	}
	echo 'MATRIX: ' . $failed . ' scenario(s) FAIL' . "\n";
	exit( 1 );
}

$scenario = $worker_scenario;

define( 'ABSPATH', '/tmp/pfoa-compat-fixture/' );

class WP_Post {
	/** @var int */
	public $ID = 0;
	/** @var string */
	public $post_type = '';
}

$GLOBALS['fixture_posts']   = array();
$GLOBALS['fixture_index']   = 0;
$GLOBALS['fixture_current'] = null;
$GLOBALS['fixture_data']    = null;
$GLOBALS['renderer_calls']  = array();
$GLOBALS['gallery_calls']   = array();

function have_posts(): bool {
	return $GLOBALS['fixture_index'] < count( $GLOBALS['fixture_posts'] );
}

function the_post(): void {
	$GLOBALS['fixture_current'] = $GLOBALS['fixture_posts'][ $GLOBALS['fixture_index'] ];
	++$GLOBALS['fixture_index'];
}

function get_the_ID(): int {
	return (int) $GLOBALS['fixture_current']['ID'];
}

function get_post() {
	$post            = new WP_Post();
	$post->ID        = (int) $GLOBALS['fixture_current']['ID'];
	$post->post_type = (string) $GLOBALS['fixture_current']['post_type'];
	return $post;
}

function get_header(): void {
	echo '<!--FIXTURE-HEADER-->';
}

function get_footer(): void {
	echo '<!--FIXTURE-FOOTER-->';
}

function fixture_profile_data( bool $complete ): array {
	return array(
		'schema_version' => '1.0',
		'post_id'        => 42,
		'post_type'      => 'pfoa_cat',
		'publication'    => array(
			'complete'  => $complete,
			'templated' => false,
		),
	);
}

if ( 'no_provider' !== $scenario ) {
	function pfoa_cat_get_profile_data( int $post_id ): ?array {
		$data = $GLOBALS['fixture_data'];
		return is_array( $data ) ? $data : null;
	}
}

if ( 'no_renderer' !== $scenario ) {
	function pfoa_theme_render_cat_profile_compat( array $profile_data, string $heading_tag = 'h1' ): string {
		$GLOBALS['renderer_calls'][] = array( $profile_data, $heading_tag );
		if ( 'empty_renderer' === getenv( 'PFOA_SCENARIO' ) ) {
			return '';
		}
		// Mirror the real renderer contract: complete records delegate to gallery.
		$gallery = '';
		if ( function_exists( 'pfoa_theme_render_cat_gallery_compat' ) ) {
			$gallery = pfoa_theme_render_cat_gallery_compat( $profile_data );
		}
		return '<div class="pfoa-cat-profile-stub">' . $gallery . '</div>';
	}

	function pfoa_theme_render_cat_gallery_compat( array $profile_data ): string {
		$GLOBALS['gallery_calls'][] = $profile_data;
		return '<div class="pfoa-cat-gallery-stub"></div>';
	}
}

define( 'PFOA_CAT_POST_TYPE', 'pfoa_cat' );

$GLOBALS['fixture_posts'] = array( array( 'ID' => 42, 'post_type' => 'pfoa_cat' ) );

switch ( $scenario ) {
	case 'complete':
		$GLOBALS['fixture_data'] = fixture_profile_data( true );
		break;
	case 'incomplete':
	case 'empty_renderer':
		$GLOBALS['fixture_data'] = fixture_profile_data( false );
		break;
	case 'mismatched_post_id':
		$GLOBALS['fixture_data']            = fixture_profile_data( true );
		$GLOBALS['fixture_data']['post_id'] = 999;
		break;
	case 'invalid_schema':
		$GLOBALS['fixture_data']                   = fixture_profile_data( true );
		$GLOBALS['fixture_data']['schema_version'] = '2.0';
		break;
	case 'null_data':
	case 'no_provider':
	case 'no_renderer':
		$GLOBALS['fixture_data'] = null;
		break;
}

$failures = array();

function check( string $label, bool $cond ): void {
	global $failures;
	echo ( $cond ? '  ok: ' : '  NOT-OK: ' ) . $label . "\n";
	if ( ! $cond ) {
		$failures[] = $label;
	}
}

if ( 'static' === $scenario ) {
	$src = file_get_contents( PFOA_COMPAT_TEMPLATE_FILE );
	check( 'template file exists', is_string( $src ) && '' !== $src );
	if ( is_string( $src ) && '' !== $src ) {
		foreach ( array( 'ABSPATH', 'get_header', 'get_footer', 'have_posts', 'the_post', 'get_the_ID', 'pfoa-cat-single', 'pfoa_cat_get_profile_data', 'pfoa_theme_render_cat_profile_compat', "'h1'", 'PFOA_CAT_POST_TYPE', 'schema_version', 'post_id', 'INACTIVE', 'template_include' ) as $needle ) {
			check( 'template contains ' . $needle, false !== strpos( $src, $needle ) );
		}
		check( 'header states NOT wired', false !== stripos( $src, 'not wired' ) );
		foreach ( array( 'add_action', 'add_filter', 'get_post_meta', 'update_post_meta', 'wp_update', 'wp_insert', 'wp_delete', 'wp_enqueue_', 'pfoa_cat_render_' ) as $banned ) {
			check( 'template has no ' . $banned, false === strpos( $src, $banned ) );
		}
	}
} else {
	ob_start();
	include PFOA_COMPAT_TEMPLATE_FILE;
	$output = ob_get_clean();
	if ( ! is_string( $output ) ) {
		$output = '';
	}

	switch ( $scenario ) {
		case 'complete':
		case 'incomplete':
			check( 'header emitted', false !== strpos( $output, '<!--FIXTURE-HEADER-->' ) );
			check( 'main wrapper present', false !== strpos( $output, '<main class="pfoa-cat-single">' ) );
			check( 'detail rendering present', false !== strpos( $output, 'pfoa-cat-profile-stub' ) );
			check( 'gallery rendering present', false !== strpos( $output, 'pfoa-cat-gallery-stub' ) );
			check( 'footer emitted', false !== strpos( $output, '<!--FIXTURE-FOOTER-->' ) );
			check(
				'header before main before footer',
				strpos( $output, '<!--FIXTURE-HEADER-->' ) < strpos( $output, '<main class="pfoa-cat-single">' )
				&& strpos( $output, '<main class="pfoa-cat-single">' ) < strpos( $output, '<!--FIXTURE-FOOTER-->' )
			);
			check( 'detail renderer called once', 1 === count( $GLOBALS['renderer_calls'] ) );
			if ( 1 === count( $GLOBALS['renderer_calls'] ) ) {
				check( 'renderer got h1 heading', 'h1' === $GLOBALS['renderer_calls'][0][1] );
				check( 'renderer got data as-is (pass-through)', $GLOBALS['fixture_data'] === $GLOBALS['renderer_calls'][0][0] );
			}
			check( 'gallery renderer reached via delegation', 1 === count( $GLOBALS['gallery_calls'] ) );
			break;
		case 'empty_renderer':
			check( 'empty render: renderer was called once', 1 === count( $GLOBALS['renderer_calls'] ) );
			check( 'empty render: zero bytes emitted', '' === $output );
			check( 'empty render: no header bytes', false === strpos( $output, '<!--FIXTURE-HEADER-->' ) );
			check( 'empty render: no footer bytes', false === strpos( $output, '<!--FIXTURE-FOOTER-->' ) );
			check( 'empty render: no main wrapper', false === strpos( $output, '<main' ) );
			break;
		case 'mismatched_post_id':
		case 'invalid_schema':
		case 'null_data':
		case 'no_provider':
		case 'no_renderer':
			check( 'fail-closed: zero bytes emitted', '' === $output );
			check( 'fail-closed: no header bytes', false === strpos( $output, '<!--FIXTURE-HEADER-->' ) );
			check( 'fail-closed: no footer bytes', false === strpos( $output, '<!--FIXTURE-FOOTER-->' ) );
			check( 'fail-closed: no main wrapper', false === strpos( $output, '<main' ) );
			check( 'fail-closed: detail renderer never called', 0 === count( $GLOBALS['renderer_calls'] ) );
			break;
	}
}

if ( array() === $failures ) {
	echo "SCENARIO {$scenario}: PASS\n";
	exit( 0 );
}

echo 'SCENARIO ' . $scenario . ': FAIL (' . count( $failures ) . ")\n";
exit( 1 );
