<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile card-list compat renderer.
 *
 * Requires pfoa-theme/inc/theme-cat-cards-compat-renderer.php directly
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
$GLOBALS['fixture_forbidden_read']  = false;
$GLOBALS['fixture_thumb_args']      = array();
$GLOBALS['fixture_attach_args']     = array();
$GLOBALS['fixture_titles']          = array(
	42 => 'Whiskers',
	43 => 'Mittens',
	44 => 'Shadow',
	45 => 'Pepper',
	46 => 'Socks',
);
$GLOBALS['fixture_slugs']           = array(
	42 => 'whiskers',
	43 => 'mittens',
	44 => 'shadow',
	45 => 'pepper',
	46 => 'socks',
);
$GLOBALS['fixture_empty_thumb_ids'] = array();
$GLOBALS['fixture_empty_attach_ids'] = array();
$GLOBALS['fixture_non_attach_ids']  = array();

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
function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['fixture_forbidden_read'] = true;
	return '';
}
function metadata_exists( $type, $id, $key ) {
	$GLOBALS['fixture_forbidden_read'] = true;
	return false;
}
function get_post_field( $field, $post_id ) {
	if ( 'post_name' !== $field ) {
		return '';
	}
	$id = (int) $post_id;
	return isset( $GLOBALS['fixture_slugs'][ $id ] ) ? $GLOBALS['fixture_slugs'][ $id ] : '';
}
function get_the_title( $post_id = 0 ) {
	$id = (int) $post_id;
	if ( isset( $GLOBALS['fixture_titles'][ $id ] ) ) {
		return $GLOBALS['fixture_titles'][ $id ];
	}
	return 'Title ' . $id;
}
function get_the_post_thumbnail( $post_id, $size = 'post-thumbnail', $attr = array() ) {
	$id = (int) $post_id;
	$GLOBALS['fixture_thumb_args'] = array( 'id' => $id, 'size' => $size, 'attr' => $attr );
	if ( in_array( $id, $GLOBALS['fixture_empty_thumb_ids'], true ) ) {
		return '';
	}
	return '<img src="https://example.com/card-' . $id . '.jpg" class="pfoa-cat-card-thumb" alt="" />';
}
function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
	$id = (int) $attachment_id;
	$GLOBALS['fixture_attach_args'] = array( 'id' => $id, 'size' => $size, 'icon' => $icon, 'attr' => $attr );
	if ( in_array( $id, $GLOBALS['fixture_empty_attach_ids'], true ) ) {
		return '';
	}
	return '<img src="https://example.com/member-' . $id . '.jpg" class="pfoa-cat-card-thumb" alt="" />';
}
function get_post_type( $post_id = 0 ) {
	$id = (int) $post_id;
	if ( in_array( $id, $GLOBALS['fixture_non_attach_ids'], true ) ) {
		return 'post';
	}
	return 'attachment';
}
function get_permalink( $post_id = 0 ) {
	return 'https://example.com/cat/' . (int) $post_id . '/';
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
function __( $text, $domain = 'default' ) {
	return (string) $text;
}
function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( $text );
}
function esc_html__( $text, $domain = 'default' ) {
	return esc_html( $text );
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-cards-compat-renderer.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_base_single( $post_id, $complete, $status = 'Available' ) {
	return array(
		'schema_version' => '1.0',
		'post_id'        => $post_id,
		'post_type'      => 'pfoa_cat',
		'record_status'  => $status,
		'publication'    => array(
			'complete'                => $complete,
			'templated'               => false,
			'has_required_card_image' => true,
		),
		'card_image_id'  => 901,
		'legacy'         => null,
	);
}

function fixture_base_multi( $post_id, $complete, $members, $card_ids, $labels = array() ) {
	return array(
		'schema_version' => '1.0',
		'post_id'        => $post_id,
		'post_type'      => 'pfoa_cat',
		'record_status'  => 'Available',
		'publication'    => array(
			'complete'                => $complete,
			'templated'               => false,
			'has_required_card_image' => true,
		),
		'card_image_id'  => 0,
		'legacy'         => array(
			'members'                  => $members,
			'member_ids'               => array(),
			'member_cards'             => array(),
			'has_member_cards'         => true,
			'card_labels'              => $labels,
			'resolved_card_labels'     => array(),
			'resolved_member_card_ids' => $card_ids,
			'source'                   => '',
			'status'                   => 'Available',
		),
	);
}

// Renderer entry points exist and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_render_cat_cards_compat' ), 'list renderer function exists' );
fixture_assert( function_exists( 'pfoa_theme_render_cat_dialog_shell_compat' ), 'dialog shell function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_CARDS_COMPAT_RENDERER_LOADED' ), 'loaded guard constant defined' );

// (a) Ordering preserved: three profiles render in given order.
$profiles = array(
	fixture_base_single( 42, true ),
	fixture_base_single( 43, false ),
	fixture_base_single( 44, true ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
$pos_42 = strpos( $html, 'data-pfoa-profile-id="42"' );
$pos_43 = strpos( $html, 'data-pfoa-profile-id="43"' );
$pos_44 = strpos( $html, 'data-pfoa-profile-id="44"' );
fixture_assert( false !== $pos_42 && false !== $pos_43 && false !== $pos_44, 'ordering: all three profiles render' );
fixture_assert( $pos_42 < $pos_43 && $pos_43 < $pos_44, 'ordering: caller order preserved' );
fixture_assert( 0 === strpos( $html, '<div class="pfoa-cat-cards">' ), 'ordering: wrapper opens first' );
fixture_assert( '</div>' === substr( $html, -6 ), 'ordering: wrapper closes last' );

// (a2) Reverse input renders in reverse (no sorting).
$profiles = array(
	fixture_base_single( 44, true ),
	fixture_base_single( 42, true ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
fixture_assert( strpos( $html, 'data-pfoa-profile-id="44"' ) < strpos( $html, 'data-pfoa-profile-id="42"' ), 'ordering: reversed input stays reversed' );

// (b) Empty list returns the exact empty message.
$html = pfoa_theme_render_cat_cards_compat( array() );
fixture_assert( '<p class="pfoa-cat-cards-empty">No cat profiles found.</p>' === $html, 'empty: exact empty message' );

// (c) Rendering reuses per-profile compat: single complete + multi pair.
$profiles = array(
	fixture_base_single( 42, true ),
	fixture_base_multi( 43, true, array( 'Alpha', 'Beta' ), array( 101, 102 ), array( 'Alfie', '' ) ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
fixture_assert( 0 === strpos( $html, '<div class="pfoa-cat-cards">' ), 'reuse: wrapper div opens' );
fixture_assert( 3 === substr_count( $html, '<article class="pfoa-cat-card' ), 'reuse: one single card plus two member cards' );
fixture_assert( false !== strpos( $html, '>Whiskers</a></h3>' ), 'reuse: single card label inside wrapper' );
fixture_assert( false !== strpos( $html, '>Alfie</a></h3>' ), 'reuse: member explicit label inside wrapper' );
fixture_assert( false !== strpos( $html, '>Beta</a></h3>' ), 'reuse: member fallback label inside wrapper' );

// (c2) Non-array entries are skipped without disturbing order.
$profiles = array(
	fixture_base_single( 42, true ),
	'not-a-profile',
	42,
	null,
	fixture_base_single( 44, true ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
fixture_assert( false !== strpos( $html, 'data-pfoa-profile-id="42"' ), 'skip: first profile kept' );
fixture_assert( false !== strpos( $html, 'data-pfoa-profile-id="44"' ), 'skip: last profile kept' );
fixture_assert( strpos( $html, 'data-pfoa-profile-id="42"' ) < strpos( $html, 'data-pfoa-profile-id="44"' ), 'skip: order kept around skips' );

// (d) Dialog shell markup exact.
$shell = pfoa_theme_render_cat_dialog_shell_compat();
fixture_assert( false !== strpos( $shell, '<div id="pfoa-cat-dialog" class="pfoa-cat-dialog" role="dialog" aria-modal="true" aria-label="Cat profile" hidden>' ), 'dialog: shell opens with id/class/role/aria-modal/label/hidden' );
fixture_assert( false !== strpos( $shell, '<div class="pfoa-cat-dialog-container">' ), 'dialog: container present' );
fixture_assert( false !== strpos( $shell, '<button type="button" class="pfoa-cat-dialog-close" data-pfoa-cat-dialog-close aria-label="Close">×</button>' ), 'dialog: close button exact' );
fixture_assert( false !== strpos( $shell, '<div class="pfoa-cat-dialog-body" data-pfoa-cat-dialog-body aria-live="polite" tabindex="0"></div>' ), 'dialog: body exact' );
fixture_assert( '</div></div>' === substr( $shell, -12 ), 'dialog: containers close' );

// (d2) No duplicates: second call returns ''.
fixture_assert( '' === pfoa_theme_render_cat_dialog_shell_compat(), 'dialog: second call empty (no duplicates)' );

// (d3) Card list does NOT contain the dialog shell (per-card dialog trigger
// attributes are expected; the shell container/body must be absent).
$html = pfoa_theme_render_cat_cards_compat( array( fixture_base_single( 42, true ) ) );
fixture_assert( false === strpos( $html, 'id="pfoa-cat-dialog"' ), 'list: no dialog shell id inside card list' );
fixture_assert( false === strpos( $html, 'pfoa-cat-dialog-container' ), 'list: no dialog container inside card list' );
fixture_assert( false === strpos( $html, 'pfoa-cat-dialog-body' ), 'list: no dialog body inside card list' );

// (e) Required-image gate via singular renderer: fail-closed.
$profile = fixture_base_single( 42, true );
unset( $profile['publication']['has_required_card_image'] );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'gate: missing flag yields empty string' );
$profile = fixture_base_single( 42, true );
$profile['publication']['has_required_card_image'] = false;
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'gate: false yields empty string' );
foreach ( array( 0, '', null ) as $bad_flag ) {
	$profile = fixture_base_single( 42, true );
	$profile['publication']['has_required_card_image'] = $bad_flag;
	fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'gate: non-true flag yields empty string' );
}
$profile = fixture_base_single( 42, true );
$profile['publication']['has_required_card_image'] = true;
fixture_assert( '' !== pfoa_theme_render_cat_card_compat( $profile ), 'gate: true renders non-empty' );

// (e2) List renderer skips gated profiles but preserves order of the rest.
$gated = fixture_base_single( 43, true );
$gated['publication']['has_required_card_image'] = false;
$profiles = array(
	fixture_base_single( 42, true ),
	$gated,
	fixture_base_single( 44, true ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
fixture_assert( false !== strpos( $html, 'data-pfoa-profile-id="42"' ), 'gate-list: first profile kept' );
fixture_assert( false === strpos( $html, 'data-pfoa-profile-id="43"' ), 'gate-list: gated profile skipped' );
fixture_assert( false !== strpos( $html, 'data-pfoa-profile-id="44"' ), 'gate-list: last profile kept' );
fixture_assert( strpos( $html, 'data-pfoa-profile-id="42"' ) < strpos( $html, 'data-pfoa-profile-id="44"' ), 'gate-list: order preserved around skip' );

// (e3) Missing flag inside a list entry also skips that entry only.
$missing = fixture_base_single( 43, true );
unset( $missing['publication']['has_required_card_image'] );
$profiles = array(
	fixture_base_single( 42, true ),
	$missing,
	fixture_base_single( 44, true ),
);
$html = pfoa_theme_render_cat_cards_compat( $profiles );
fixture_assert( false === strpos( $html, 'data-pfoa-profile-id="43"' ), 'gate-list: missing-flag entry skipped' );
fixture_assert( strpos( $html, 'data-pfoa-profile-id="42"' ) < strpos( $html, 'data-pfoa-profile-id="44"' ), 'gate-list: order preserved around missing-flag skip' );

// (f) No writes, no hooks, no forbidden storage reads at require/render time.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during render' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/render time' );
fixture_assert( false === $GLOBALS['fixture_forbidden_read'], 'no forbidden storage reads during render' );
$source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-cards-compat-renderer.php' );
foreach ( array( 'add_action', 'add_filter', 'add_shortcode', 'register_' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
foreach ( array( 'update_option', 'wp_insert', 'update_post_meta', 'delete_' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
foreach ( array( 'pfoa_cat_', 'get_post_meta', 'metadata_exists', '_pfoa_cat_', '_pfoa_' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
foreach ( array( 'post_status', 'do_action', '$wpdb' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
foreach ( array( 'wp_insert', 'wp_update', 'wp_delete' ) as $needle ) {
	fixture_assert( false === strpos( $source, $needle ), 'file source has no ' . $needle );
}
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-cards-compat-renderer' ), 'functions.php does not require card-list file' );
fixture_assert( false === strpos( $functions_source, 'theme-cat-card-compat-renderer' ), 'functions.php does not require per-profile file' );

fwrite( STDOUT, "PASS: theme cat cards compat renderer fixture (ordering, empty message, per-profile reuse, dialog shell exact + no-duplicate + list-excludes-dialog, required-image gate fail-closed, no-writes, no-hooks)\n" );
