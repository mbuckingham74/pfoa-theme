<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile listing card compat renderer.
 *
 * Requires pfoa-theme/inc/theme-cat-card-compat-renderer.php directly
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

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-card-compat-renderer.php';

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

// Renderer entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_render_cat_card_compat' ), 'renderer function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_CARD_COMPAT_RENDERER_LOADED' ), 'loaded guard constant defined' );

// (1) Single complete happy path: linked media + title, status, no note.
$profile = fixture_base_single( 42, true );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( '' !== $html, 'single-complete: non-empty output' );
fixture_assert( false !== strpos( $html, '<article class="pfoa-cat-card" data-pfoa-profile-id="42">' ), 'single-complete: article class + profile id' );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-incomplete' ), 'single-complete: no incomplete class' );
fixture_assert( false !== strpos( $html, '<a class="pfoa-cat-card-media" href="https://example.com/cat/42/" data-pfoa-cat-dialog="whiskers">' ), 'single-complete: linked media with dialog slug' );
fixture_assert( false !== strpos( $html, '<h3 class="pfoa-cat-card-title"><a href="https://example.com/cat/42/" data-pfoa-cat-dialog="whiskers">Whiskers</a></h3>' ), 'single-complete: linked title falls back to live title' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-card-status">Available</p>' ), 'single-complete: status shown' );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-note' ), 'single-complete: no coming-soon note' );
fixture_assert( 42 === $GLOBALS['fixture_thumb_args']['id'] && 'medium' === $GLOBALS['fixture_thumb_args']['size'], 'single-complete: thumbnail requested for post at medium' );
fixture_assert( 'pfoa-cat-card-thumb' === $GLOBALS['fixture_thumb_args']['attr']['class'], 'single-complete: thumb class passed' );

// (1b) Single complete with explicit stored label wins over title.
$profile = fixture_base_single( 42, true );
$profile['legacy'] = array(
	'members'                  => array(),
	'card_labels'              => array( 'Whiskers the Brave' ),
	'resolved_member_card_ids' => array(),
);
$html = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false !== strpos( $html, '>Whiskers the Brave</a></h3>' ), 'single-complete: explicit label wins' );

// (1c) Single complete with blank stored label falls back to title.
$profile = fixture_base_single( 42, true );
$profile['legacy'] = array(
	'members'                  => array(),
	'card_labels'              => array( '   ' ),
	'resolved_member_card_ids' => array(),
);
$html = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false !== strpos( $html, '>Whiskers</a></h3>' ), 'single-complete: blank label falls back to title' );

// (2) Single incomplete: plain div/h3, no links/dialog, status + note.
$profile = fixture_base_single( 43, false );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false !== strpos( $html, '<article class="pfoa-cat-card pfoa-cat-card-incomplete" data-pfoa-profile-id="43">' ), 'single-incomplete: incomplete class + profile id' );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-card-media">' ), 'single-incomplete: plain media div' );
fixture_assert( false !== strpos( $html, '<h3 class="pfoa-cat-card-title">Mittens</h3>' ), 'single-incomplete: plain title' );
fixture_assert( false === strpos( $html, '<a ' ), 'single-incomplete: no anchors at all' );
fixture_assert( false === strpos( $html, 'data-pfoa-cat-dialog' ), 'single-incomplete: no dialog attrs' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-card-status">Available</p>' ), 'single-incomplete: status shown' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-card-note">Full profile coming soon.</p>' ), 'single-incomplete: coming-soon note' );

// (2b) Single incomplete with empty status: no status line, note remains.
$profile = fixture_base_single( 43, false, '' );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-status' ), 'single-incomplete-empty-status: no status line' );
fixture_assert( false !== strpos( $html, 'Full profile coming soon.' ), 'single-incomplete-empty-status: note remains' );

// (3) Missing/invalid Card Image: single thumbnail empty => ''.
$GLOBALS['fixture_empty_thumb_ids'] = array( 44 );
$profile = fixture_base_single( 44, true );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'single: empty thumbnail yields empty string' );
$GLOBALS['fixture_empty_thumb_ids'] = array();

// (3b) Unmet Card Image requirement => '' even when complete.
$profile = fixture_base_single( 42, true );
$profile['publication']['has_required_card_image'] = false;
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'single: unmet card image requirement yields empty string' );
$profile = fixture_base_single( 43, false );
$profile['publication']['has_required_card_image'] = false;
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'single-incomplete: unmet card image requirement yields empty string' );

// (3c) Fail-closed: missing/invalid flag yields '' even when complete.
$profile = fixture_base_single( 42, true );
unset( $profile['publication']['has_required_card_image'] );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'single: missing card image flag yields empty string' );
foreach ( array( 0, '', null, 1, '1' ) as $bad_flag ) {
	$profile = fixture_base_single( 42, true );
	$profile['publication']['has_required_card_image'] = $bad_flag;
	fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'single: non-true card image flag yields empty string' );
}

// (4) Multi complete pair: stored order, explicit + fallback labels, pair classes, no status/note.
$profile = fixture_base_multi( 42, true, array( 'Alpha', 'Beta' ), array( 101, 102 ), array( 'Alfie', '' ) );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( 2 === substr_count( $html, '<article class="pfoa-cat-card' ), 'multi-complete: two cards' );
fixture_assert( false !== strpos( $html, '<article class="pfoa-cat-card pfoa-cat-pair-left" data-pfoa-profile-id="42">' ), 'multi-complete: first card pair-left' );
fixture_assert( false !== strpos( $html, '<article class="pfoa-cat-card pfoa-cat-pair-right" data-pfoa-profile-id="42">' ), 'multi-complete: second card pair-right' );
$pos_alfie = strpos( $html, '>Alfie</a></h3>' );
$pos_beta  = strpos( $html, '>Beta</a></h3>' );
fixture_assert( false !== $pos_alfie && false !== $pos_beta && $pos_alfie < $pos_beta, 'multi-complete: stored order preserved (explicit Alfie, fallback Beta)' );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-status' ), 'multi-complete: no status line beneath member cards' );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-note' ), 'multi-complete: no coming-soon note' );
fixture_assert( 4 === substr_count( $html, 'data-pfoa-cat-dialog="whiskers"' ), 'multi-complete: dialog attrs on media + title per card' );
fixture_assert( false !== strpos( $html, 'href="https://example.com/cat/42/"' ), 'multi-complete: canonical permalink links' );
fixture_assert( 101 === $GLOBALS['fixture_attach_args']['id'] || 102 === $GLOBALS['fixture_attach_args']['id'], 'multi-complete: member card attachment requested' );
fixture_assert( 'medium' === $GLOBALS['fixture_attach_args']['size'], 'multi-complete: member thumb at medium' );
fixture_assert( 'pfoa-cat-card-thumb' === $GLOBALS['fixture_attach_args']['attr']['class'], 'multi-complete: member thumb class passed' );

// (5) Multi incomplete: plain markup, notes, no links/dialog, pair classes kept.
$profile = fixture_base_multi( 43, false, array( 'Alpha', 'Beta' ), array( 101, 102 ) );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( 2 === substr_count( $html, 'pfoa-cat-card-incomplete' ), 'multi-incomplete: both cards incomplete' );
fixture_assert( 2 === substr_count( $html, '<p class="pfoa-cat-card-note">Full profile coming soon.</p>' ), 'multi-incomplete: note per card' );
fixture_assert( false === strpos( $html, '<a ' ), 'multi-incomplete: no anchors' );
fixture_assert( false === strpos( $html, 'data-pfoa-cat-dialog' ), 'multi-incomplete: no dialog attrs' );
fixture_assert( false !== strpos( $html, 'pfoa-cat-pair-left' ), 'multi-incomplete: pair-left kept' );
fixture_assert( false !== strpos( $html, 'pfoa-cat-pair-right' ), 'multi-incomplete: pair-right kept' );
fixture_assert( false === strpos( $html, 'pfoa-cat-card-status' ), 'multi-incomplete: no status line' );

// (6) Three members: no pair classes.
$profile = fixture_base_multi( 42, true, array( 'A', 'B', 'C' ), array( 101, 102, 103 ) );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( 3 === substr_count( $html, '<article class="pfoa-cat-card' ), 'multi-three: three cards' );
fixture_assert( false === strpos( $html, 'pfoa-cat-pair-left' ), 'multi-three: no pair-left' );
fixture_assert( false === strpos( $html, 'pfoa-cat-pair-right' ), 'multi-three: no pair-right' );

// (7) Invalid members skipped: zero id, non-attachment, empty thumb.
$profile = fixture_base_multi( 42, true, array( 'Keep', 'Zero', 'Wrong', 'Empty' ), array( 101, 0, 102, 103 ) );
$GLOBALS['fixture_non_attach_ids']  = array( 102 );
$GLOBALS['fixture_empty_attach_ids'] = array( 103 );
$html = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( 1 === substr_count( $html, '<article class="pfoa-cat-card' ), 'multi-invalid: only valid member renders' );
fixture_assert( false !== strpos( $html, '>Keep</a></h3>' ), 'multi-invalid: valid member kept' );
fixture_assert( false === strpos( $html, '>Zero<' ), 'multi-invalid: zero-id member skipped' );
fixture_assert( false === strpos( $html, '>Wrong<' ), 'multi-invalid: non-attachment member skipped' );
fixture_assert( false === strpos( $html, '>Empty<' ), 'multi-invalid: empty-thumb member skipped' );
$GLOBALS['fixture_non_attach_ids']   = array();
$GLOBALS['fixture_empty_attach_ids'] = array();

// (7b) All members invalid => ''.
$profile = fixture_base_multi( 42, true, array( 'Zero', 'Wrong' ), array( 0, 0 ) );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( $profile ), 'multi-all-invalid: empty string' );

// (8) XSS escaping: title, slug, status, member, label.
$GLOBALS['fixture_titles'][45] = '<script>alert("t")</script>';
$GLOBALS['fixture_slugs'][45]  = 'a"b<c>';
$profile = fixture_base_single( 45, true, '<b>Bad</b>' );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false === strpos( $html, '<script>' ), 'xss: raw script absent in single title' );
fixture_assert( false !== strpos( $html, esc_html( '<script>alert("t")</script>' ) ), 'xss: single title escaped' );
fixture_assert( false !== strpos( $html, 'data-pfoa-cat-dialog="a&quot;b&lt;c&gt;"' ), 'xss: slug attr escaped' );
fixture_assert( false === strpos( $html, '<b>Bad</b>' ), 'xss: raw status html absent' );
fixture_assert( false !== strpos( $html, esc_html( '<b>Bad</b>' ) ), 'xss: status escaped' );
$profile = fixture_base_multi( 45, true, array( '<img src=x onerror=alert(1)>' ), array( 101 ), array( '<i>Lab</i>' ) );
$html    = pfoa_theme_render_cat_card_compat( $profile );
fixture_assert( false === strpos( $html, '<img src=x' ), 'xss: raw member html absent' );
fixture_assert( false === strpos( $html, '<i>Lab</i>' ), 'xss: raw label html absent' );
fixture_assert( false !== strpos( $html, esc_html( '<i>Lab</i>' ) ), 'xss: label escaped' );
$GLOBALS['fixture_titles'][45] = 'Pepper';
$GLOBALS['fixture_slugs'][45]  = 'pepper';

// (9) Missing data => ''.
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'schema_version' => '2.0', 'post_id' => 42, 'post_type' => 'pfoa_cat' ) ), 'missing: bad schema' );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'post_id' => 42, 'post_type' => 'pfoa_cat' ) ), 'missing: no schema' );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'schema_version' => '1.0', 'post_type' => 'pfoa_cat' ) ), 'missing: no post_id' );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'schema_version' => '1.0', 'post_id' => 0, 'post_type' => 'pfoa_cat' ) ), 'missing: zero post_id' );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'schema_version' => '1.0', 'post_id' => 42, 'post_type' => 'post' ) ), 'missing: wrong type' );
fixture_assert( '' === pfoa_theme_render_cat_card_compat( array( 'schema_version' => '1.0', 'post_id' => 42 ) ), 'missing: no post_type' );

// (10) No writes, no hooks, no forbidden storage reads at require/render time.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during render' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/render time' );
fixture_assert( false === $GLOBALS['fixture_forbidden_read'], 'no forbidden storage reads during render' );
$source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-card-compat-renderer.php' );
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
fixture_assert( false === strpos( $functions_source, 'theme-cat-card-compat-renderer' ), 'functions.php does not require new file' );

fwrite( STDOUT, "PASS: theme cat card compat renderer fixture (single complete/incomplete, labels, card image, missing/invalid image, multi order/labels/pair, skips, status, links+dialog, escaping, missing, no-writes, no-hooks)\n" );
