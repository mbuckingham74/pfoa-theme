<?php
/**
 * Focused fixture for the inactive theme-owned Cat Profile detail compat renderer.
 *
 * Requires pfoa-theme/inc/theme-cat-profile-compat-renderer.php directly
 * (never the functions.php monolith) with WordPress doubles stubbed inline.
 * No network, database, or lifecycle dependency.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

$GLOBALS['fixture_db_touched']       = false;
$GLOBALS['fixture_hook_registered']  = false;
$GLOBALS['fixture_forbidden_read']   = false;
$GLOBALS['fixture_gallery_calls']    = array();
$GLOBALS['fixture_thumb_alt']        = null;
$GLOBALS['fixture_thumb_args']       = array();
$GLOBALS['fixture_oembed_false']     = false;
$GLOBALS['fixture_oembed_url']       = '';
$GLOBALS['fixture_empty_thumb_ids']  = array();
$GLOBALS['fixture_titles']           = array(
	42  => 'Whiskers',
	43  => 'Mittens',
	44  => 'Shadow',
	45  => 'Fluffy Live',
	46  => 'Pepper',
);
$GLOBALS['fixture_slugs']            = array(
	42 => 'whiskers',
	43 => 'mittens',
	44 => 'shadow',
	45 => 'fluffy-live',
	46 => 'pepper',
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
	$GLOBALS['fixture_thumb_alt']  = isset( $attr['alt'] ) ? $attr['alt'] : null;
	if ( in_array( $id, $GLOBALS['fixture_empty_thumb_ids'], true ) ) {
		return '';
	}
	$alt = isset( $attr['alt'] ) ? (string) $attr['alt'] : '';
	return '<img src="https://example.com/primary-' . $id . '.jpg" alt="' . esc_attr( $alt ) . '" />';
}
function apply_filters( $hook, $value ) {
	return $value;
}
function wp_oembed_get( $url, $args = array() ) {
	$GLOBALS['fixture_oembed_url'] = (string) $url;
	if ( $GLOBALS['fixture_oembed_false'] ) {
		return false;
	}
	return '<iframe src="' . esc_attr( (string) $url ) . '"></iframe>';
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

// Gallery delegation double: records input, preserves order verbatim.
function pfoa_theme_render_cat_gallery_compat( array $profile_data ): string {
	$GLOBALS['fixture_gallery_calls'][] = $profile_data;
	return '<div class="gallery-delegated">GALLERY</div>';
}

require dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-profile-compat-renderer.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_base_complete( $post_id = 42 ) {
	return array(
		'schema_version'  => '1.0',
		'post_id'         => $post_id,
		'post_type'       => 'pfoa_cat',
		'description_raw' => '<p>Hello</p>',
		'record_status'   => '',
		'publication'     => array( 'complete' => true, 'templated' => false, 'has_required_card_image' => true ),
		'lifecycle'       => array( 'effectively_pending' => false ),
		'youtube_id'      => '',
		'legacy'          => null,
	);
}

function fixture_base_incomplete( $post_id = 43 ) {
	return array(
		'schema_version'  => '1.0',
		'post_id'         => $post_id,
		'post_type'       => 'pfoa_cat',
		'description_raw' => '',
		'record_status'   => 'Available',
		'publication'     => array( 'complete' => false, 'templated' => false, 'has_required_card_image' => false ),
		'lifecycle'       => array( 'effectively_pending' => false ),
		'youtube_id'      => '',
		'legacy'          => array(
			'status'  => 'Available',
			'members' => array( 'Alpha', 'Beta' ),
		),
	);
}

// Renderer entry point exists and guard constant is set.
fixture_assert( function_exists( 'pfoa_theme_render_cat_profile_compat' ), 'renderer function exists' );
fixture_assert( defined( 'PFOA_THEME_CAT_PROFILE_COMPAT_RENDERER_LOADED' ), 'loaded guard constant defined' );

// (1) Complete happy path: wrapper only, title, image, description.
$profile = fixture_base_complete( 42 );
$html    = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( '' !== $html, 'complete: non-empty output' );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile" data-pfoa-cat-profile="whiskers">' ), 'complete: wrapper with slug' );
fixture_assert( false !== strpos( $html, '<h1 class="pfoa-cat-profile-title">Whiskers</h1>' ), 'complete: h1 title' );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile-image">' ), 'complete: image wrapper present' );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile-description"><p>Hello</p></div>' ), 'complete: description wrapper' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-status-pending' ), 'complete: no pending badge when false' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-video' ), 'complete: no video when empty id' );
fixture_assert( false === strpos( $html, '<main' ), 'complete: no main shell' );
fixture_assert( false === strpos( $html, '<article' ), 'complete: no article shell' );
fixture_assert( false === strpos( $html, '<header' ), 'complete: no header shell' );
fixture_assert( false === strpos( $html, 'entry-content' ), 'complete: no entry-content shell' );

// (2) Incomplete with legacy present (record_status drives status).
$profile = fixture_base_incomplete( 43 );
$html    = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<h1 class="pfoa-cat-profile-title">Mittens</h1>' ), 'incomplete: title' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-profile-status">Available</p>' ), 'incomplete: status shown' );
fixture_assert( false !== strpos( $html, '<ul class="pfoa-cat-profile-members"' ), 'incomplete: members ul' );
fixture_assert( false !== strpos( $html, '<li>Alpha</li>' ), 'incomplete: member Alpha' );
fixture_assert( false !== strpos( $html, '<li>Beta</li>' ), 'incomplete: member Beta' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-profile-note">Full profile coming soon.</p>' ), 'incomplete: coming-soon note' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-image' ), 'incomplete: no image' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-description' ), 'incomplete: no description' );

// (2b) Grouped incomplete with record_status='Pending' and matching legacy.status.
$profile                  = fixture_base_incomplete( 43 );
$profile['record_status']  = 'Pending';
$profile['legacy']['status'] = 'Pending';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-profile-status">Pending</p>' ), 'grouped-pending: status shown' );
fixture_assert( false !== strpos( $html, '<ul class="pfoa-cat-profile-members"' ), 'grouped-pending: members ul' );
fixture_assert( false !== strpos( $html, '<li>Alpha</li>' ), 'grouped-pending: member Alpha' );
fixture_assert( false !== strpos( $html, '<li>Beta</li>' ), 'grouped-pending: member Beta' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-profile-note">Full profile coming soon.</p>' ), 'grouped-pending: coming-soon note' );

// (3) Incomplete legacy null with populated record_status: status + note rendered, members omitted.
$profile                 = fixture_base_incomplete( 43 );
$profile['record_status'] = 'Available';
$profile['legacy']       = null;
$html                    = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<h1 class="pfoa-cat-profile-title">Mittens</h1>' ), 'legacy-null: title still present' );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-profile-status">Available</p>' ), 'legacy-null: status rendered from record_status' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-members' ), 'legacy-null: members omitted' );
fixture_assert( false !== strpos( $html, 'Full profile coming soon.' ), 'legacy-null: coming-soon still present' );

// (3b) Incomplete legacy present but empty record_status/members: both omitted, note remains.
$profile                  = fixture_base_incomplete( 43 );
$profile['record_status']  = '';
$profile['legacy']        = array( 'status' => '', 'members' => array() );
$html                     = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-status' ), 'legacy-empty: status omitted when empty string' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-members' ), 'legacy-empty: members omitted when empty list' );
fixture_assert( false !== strpos( $html, 'Full profile coming soon.' ), 'legacy-empty: coming-soon still present' );

// (3c) Empty record_status with legacy null: no status <p>, note remains, members omitted.
$profile                  = fixture_base_incomplete( 43 );
$profile['record_status']  = '';
$profile['legacy']        = null;
$html                     = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-status' ), 'empty-status-null-legacy: no status p' );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-members' ), 'empty-status-null-legacy: members omitted' );
fixture_assert( false !== strpos( $html, 'Full profile coming soon.' ), 'empty-status-null-legacy: coming-soon still present' );

// (3d) Empty record_status with legacy present: no status <p>, members still render, note remains.
$profile                  = fixture_base_incomplete( 43 );
$profile['record_status']  = '';
$profile['legacy']        = array( 'status' => 'Available', 'members' => array( 'Alpha', 'Beta' ) );
$html                     = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-status' ), 'empty-status-with-legacy: no status p' );
fixture_assert( false !== strpos( $html, '<li>Alpha</li>' ), 'empty-status-with-legacy: members still render' );
fixture_assert( false !== strpos( $html, 'Full profile coming soon.' ), 'empty-status-with-legacy: coming-soon still present' );

// (4) Templated does not fork markup.
$plain_complete = fixture_base_complete( 42 );
$tmpl_complete  = $plain_complete;
$tmpl_complete['publication']['templated'] = true;
fixture_assert( pfoa_theme_render_cat_profile_compat( $plain_complete ) === pfoa_theme_render_cat_profile_compat( $tmpl_complete ), 'templated: complete output identical' );
$plain_incomplete = fixture_base_incomplete( 43 );
$tmpl_incomplete  = $plain_incomplete;
$tmpl_incomplete['publication']['templated'] = true;
fixture_assert( pfoa_theme_render_cat_profile_compat( $plain_incomplete ) === pfoa_theme_render_cat_profile_compat( $tmpl_incomplete ), 'templated: incomplete output identical' );

// (5) Pending on/off.
$profile = fixture_base_complete( 42 );
$profile['lifecycle']['effectively_pending'] = true;
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( 1 === substr_count( $html, '<p class="pfoa-cat-profile-status pfoa-cat-profile-status-pending">Adoption pending</p>' ), 'pending: badge shown once when true' );
$profile['lifecycle']['effectively_pending'] = false;
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-status-pending' ), 'pending: badge absent when false' );

// (6) Primary image present/empty with live-title alt.
$GLOBALS['fixture_empty_thumb_ids'] = array();
$profile = fixture_base_complete( 45 );
$html    = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( 'Fluffy Live' === $GLOBALS['fixture_thumb_alt'], 'image: live-title alt passed to thumbnail' );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile-image">' ), 'image: wrapper present when image non-empty' );
$GLOBALS['fixture_titles'][45] = 'Fluffy Renamed';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( 'Fluffy Renamed' === $GLOBALS['fixture_thumb_alt'], 'image: alt tracks live title' );
fixture_assert( false !== strpos( $html, 'Fluffy Renamed' ), 'image: heading tracks live title' );
$GLOBALS['fixture_titles'][45] = 'Fluffy Live';
$GLOBALS['fixture_empty_thumb_ids'] = array( 45 );
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-image' ), 'image: wrapper omitted when thumbnail empty' );
$GLOBALS['fixture_empty_thumb_ids'] = array();

// (7) Description variants.
$profile = fixture_base_complete( 42 );
$profile['description_raw'] = '   ';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-description' ), 'description: empty omitted' );
$profile['description_raw'] = '<p class="pfoa-cat-special-needs"><strong>Special needs:</strong>   </p>';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-description' ), 'description: special-needs empty removed' );
fixture_assert( false === strpos( $html, 'Special needs:' ), 'description: special-needs empty text gone' );
$profile['description_raw'] = '<p class="pfoa-cat-special-needs"><strong>Special needs:</strong> Needs meds</p>';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<p class="pfoa-cat-special-needs"><strong>Special needs:</strong><br>Needs meds</p>' ), 'description: special-needs populated gets br' );
$profile['description_raw'] = '<p>Just text</p>';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile-description"><p>Just text</p></div>' ), 'description: untagged passthrough' );

// (8) Gallery delegation order preserved.
$GLOBALS['fixture_gallery_calls'] = array();
$GLOBALS['fixture_oembed_false']  = false;
$profile = fixture_base_complete( 42 );
$profile['description_raw'] = '<p>Body</p>';
$profile['youtube_id'] = 'dQw4w9WgXcQ';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( 1 === count( $GLOBALS['fixture_gallery_calls'] ), 'gallery: delegated once' );
fixture_assert( $profile === $GLOBALS['fixture_gallery_calls'][0], 'gallery: receives full profile data verbatim' );
fixture_assert( false !== strpos( $html, '<div class="gallery-delegated">GALLERY</div>' ), 'gallery: delegated markup present' );
$pos_desc = strpos( $html, 'pfoa-cat-profile-description' );
$pos_gal  = strpos( $html, 'gallery-delegated' );
$pos_vid  = strpos( $html, 'pfoa-cat-profile-video' );
fixture_assert( false !== $pos_desc && false !== $pos_gal && false !== $pos_vid, 'gallery: all three sections present' );
fixture_assert( $pos_desc < $pos_gal && $pos_gal < $pos_vid, 'gallery: description < gallery < video order preserved' );

// (9) Video valid/invalid-ID/oembed-false.
$profile = fixture_base_complete( 42 );
$profile['youtube_id'] = 'dQw4w9WgXcQ';
$GLOBALS['fixture_oembed_false'] = false;
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false !== strpos( $html, '<div class="pfoa-cat-profile-video">' ), 'video: wrapper present for valid id' );
fixture_assert( false !== strpos( $html, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ), 'video: correct watch url' );
$profile['youtube_id'] = 'short';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-video' ), 'video: invalid id yields no wrapper' );
$profile['youtube_id'] = 'dQw4w9WgXcQ;DROP';
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-video' ), 'video: injection id yields no wrapper' );
$profile['youtube_id'] = 'dQw4w9WgXcQ';
$GLOBALS['fixture_oembed_false'] = true;
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, 'pfoa-cat-profile-video' ), 'video: oembed false yields empty' );
$GLOBALS['fixture_oembed_false'] = false;

// (10) XSS escaping.
$GLOBALS['fixture_titles'][46] = '<script>alert("t")</script>';
$GLOBALS['fixture_slugs'][46]  = 'a"b<c>';
$profile = fixture_base_complete( 46 );
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, '<script>' ), 'xss: raw script absent in title' );
fixture_assert( false !== strpos( $html, esc_html( '<script>alert("t")</script>' ) ), 'xss: title escaped' );
fixture_assert( false !== strpos( $html, 'data-pfoa-cat-profile="a&quot;b&lt;c&gt;"' ), 'xss: slug attr escaped' );
$profile = fixture_base_incomplete( 46 );
$profile['record_status'] = '<b>Bad</b>';
$profile['legacy'] = array(
	'status'  => '<b>Bad</b>',
	'members' => array( '<img src=x onerror=alert(1)>' ),
);
$html = pfoa_theme_render_cat_profile_compat( $profile );
fixture_assert( false === strpos( $html, '<b>Bad</b>' ), 'xss: raw status html absent' );
fixture_assert( false !== strpos( $html, esc_html( '<b>Bad</b>' ) ), 'xss: status escaped' );
fixture_assert( false === strpos( $html, '<img src=x' ), 'xss: raw member html absent' );
fixture_assert( false !== strpos( $html, esc_html( '<img src=x onerror=alert(1)>' ) ), 'xss: member escaped' );
$GLOBALS['fixture_titles'][46] = 'Pepper';
$GLOBALS['fixture_slugs'][46]  = 'pepper';

// (11) Missing data => ''.
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'schema_version' => '2.0', 'post_id' => 42, 'post_type' => 'pfoa_cat' ) ), 'missing: bad schema' );
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'post_id' => 42, 'post_type' => 'pfoa_cat' ) ), 'missing: no schema' );
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'schema_version' => '1.0', 'post_type' => 'pfoa_cat' ) ), 'missing: no post_id' );
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'schema_version' => '1.0', 'post_id' => 0, 'post_type' => 'pfoa_cat' ) ), 'missing: zero post_id' );
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'schema_version' => '1.0', 'post_id' => 42, 'post_type' => 'post' ) ), 'missing: wrong type' );
fixture_assert( '' === pfoa_theme_render_cat_profile_compat( array( 'schema_version' => '1.0', 'post_id' => 42 ) ), 'missing: no post_type' );

// (12) Heading h1/h2/invalid=>h1.
$profile = fixture_base_complete( 42 );
$html = pfoa_theme_render_cat_profile_compat( $profile, 'h1' );
fixture_assert( false !== strpos( $html, '<h1 class="pfoa-cat-profile-title">' ), 'heading: h1 honoured' );
$html = pfoa_theme_render_cat_profile_compat( $profile, 'h2' );
fixture_assert( false !== strpos( $html, '<h2 class="pfoa-cat-profile-title">' ), 'heading: h2 honoured' );
fixture_assert( false === strpos( $html, '<h1 class="pfoa-cat-profile-title">' ), 'heading: h2 replaces h1' );
$html = pfoa_theme_render_cat_profile_compat( $profile, 'h3' );
fixture_assert( false !== strpos( $html, '<h1 class="pfoa-cat-profile-title">' ), 'heading: invalid falls back to h1' );
$html = pfoa_theme_render_cat_profile_compat( $profile, 'div' );
fixture_assert( false === strpos( $html, '<div class="pfoa-cat-profile-title">' ), 'heading: div not used as heading' );
$profile_inc = fixture_base_incomplete( 43 );
$html = pfoa_theme_render_cat_profile_compat( $profile_inc, 'h2' );
fixture_assert( false !== strpos( $html, '<h2 class="pfoa-cat-profile-title">Mittens</h2>' ), 'heading: incomplete h2 honoured' );

// (13) No writes, no hooks, no forbidden storage reads at require/render time.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during render' );
fixture_assert( false === $GLOBALS['fixture_hook_registered'], 'no hook registrations at require/render time' );
fixture_assert( false === $GLOBALS['fixture_forbidden_read'], 'no forbidden storage reads during render' );
$source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/inc/theme-cat-profile-compat-renderer.php' );
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
fixture_assert( false === strpos( $functions_source, 'theme-cat-profile-compat-renderer' ), 'functions.php does not require new file' );

fwrite( STDOUT, "PASS: theme cat profile compat renderer fixture (complete, incomplete record_status, grouped pending, legacy-null status, empty status, templated, pending, image, description, gallery, video, escaping, missing, heading, no-writes, no-hooks)\n" );
