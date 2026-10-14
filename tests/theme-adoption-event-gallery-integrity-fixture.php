<?php
/**
 * Gallery-integrity fixture: B1/B2/B3 fail-closed corrections (0.1.143).
 *
 * Requires pfoa-theme/functions.php directly (never the plugin repo) with
 * WordPress doubles stubbed inline. No network, database, DEV, or provider
 * dependency.
 *
 * What changed (0.1.142 -> 0.1.143): the legacy .gallery-item identity is
 * now ONE auditable result from pfoa_collect_gallery_item_identity()
 * (valid IDs vs recognized-but-invalid count vs absent/unrelated ignored)
 * with NO numeric sentinels anywhere. pfoa_valid_claimed_attachment_id()
 * returns int|null (null, never 0, when invalid) with no 10-digit limit
 * (overflow-safe via PHP_INT_MAX length + lexicographic + round-trip).
 * Suppress a legacy item only when every recognized occurrence is valid,
 * all valid IDs agree (exactly 1 distinct), and that ID is Event-claimed.
 *
 *   B1 every collector records invalid recognized occurrences (data attrs
 *      via hasAttribute/hasAttributeNS absent-vs-present split; wp-image-
 *      invalid suffix; ARIA IDREF invalid suffix; caption invalid suffix).
 *   B2 bare `attachment_id` keys without `=`/value are recognized-invalid;
 *      query-only parsing, urldecoded names, every repeat processed.
 *   B3 strict /\A[1-9][0-9]*\z/ on the exact raw token, no 10-digit limit,
 *      outside-int-domain values invalid and never discovery-eligible.
 *
 * RED/GREEN contract:
 *   On 0.1.142 logic this fixture FAILS (RED, exit 1) and on 0.1.143 it
 *   PASSES (GREEN, exit 0). RED was verified by stashing the functions.php
 *   fix and running this fixture against the 0.1.142 tree:
 *     git stash push -- pfoa-theme/functions.php
 *     php tests/theme-adoption-event-gallery-integrity-fixture.php
 *     # -> exit 1, first line: FAIL: B3.0: validator accepts 12345678901
 *     #    (10-digit limit rejects the valid 11-digit ID; every invalid
 *     #    token also returns int(0) instead of null, tripping the
 *     #    null-never-0 assertions)
 *     git stash pop
 *     php tests/theme-adoption-event-gallery-integrity-fixture.php
 *     # -> PASS (this run, exit 0)
 *   Per-case presenter-level RED on 0.1.142 was verified with a scratch
 *   probe (same stubs, old presenter, Event 24666->23330 unless noted):
 *     B1.1/B1.2/B1.3/B1.4/B1.5/B1.6 old=SUPPRESSED, new=SURVIVES (the
 *       invalid half was silently discarded, then the lone valid token
 *       suppressed the item);
 *     B2.1 bare+valid old=SUPPRESSED, new=SURVIVES (bare key skipped);
 *     B2.4 empty+valid and B3.3B valid+outside-domain old=SURVIVES,
 *       new=SURVIVES (href sentinels already preserved these; sensitivity
 *       is proven by mutation M1 instead);
 *     SOL prio-9 malformed + prio-10 emitter old=SUPPRESSED,
 *       new=SURVIVES (malformed data-id discarded);
 *     B3.1 claimed PHP_INT_MAX + single malformed href old=SUPPRESSED,
 *       new=SURVIVES (sentinel PHP_INT_MAX-0 collided with the claim);
 *     B3.2 11-digit unanimous old=SURVIVES, new=SUPPRESSED (10-digit
 *       limit dropped the valid identity).
 *   The M1/M2/M3 mutation checks below additionally simulate
 *   disabled-malformed-tracking / disabled-bare-handling /
 *   trim-then-accept logic on EVERY run and assert each simulation produces
 *   the buggy (would-suppress / would-accept) outcome, proving the
 *   corresponding production assertions are sensitive to those mutations.
 *
 * Coverage:
 *   B1.1-B1.6 valid+invalid pairs per collector (each must preserve) +
 *      unrelated-class/ARIA positive controls (still suppress).
 *   B2 bare / encoded-name / repeated unanimous+conflict / empty-value /
 *      unrelated-keys / fragment cases.
 *   B3 validator unit battery (null-never-0) + sentinel-collision
 *      (claimed PHP_INT_MAX + single malformed href must preserve) +
 *      large-ID (11-digit unanimous must suppress) + outside-domain
 *      (never valid, never discovery-eligible, always preserves).
 *   SOL prio-9 malformed data-id="99999.9" + prio-10 production emitter
 *      data-attachment-id="23330" ambiguous item preserves via BOTH the
 *      direct shortcode-shape path and the full the_content path.
 *   SHADOW original captionless Senior Shadow unanimous item (production
 *      emitter identity only) still suppresses via the_content.
 *   M1/M2/M3 negative mutation checks (see above).
 *
 * Run with:
 *   php tests/theme-adoption-event-gallery-integrity-fixture.php
 *
 * Exits nonzero on any failure.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
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
	}
}

$GLOBALS['fixture_hook_registered'] = false;
$GLOBALS['fixture_db_touched']      = false;
$GLOBALS['fixture_event_ids']       = array();
$GLOBALS['fixture_meta']            = array();
$GLOBALS['fixture_is_page_target']  = true;
$GLOBALS['fixture_filters']         = array();

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
}
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['fixture_hook_registered'] = true;
	$GLOBALS['fixture_filters'][]        = array( $hook, $callback, $priority, $accepted_args );
}
function apply_filters( $hook, $value ) {
	if ( 'the_content' === $hook && function_exists( 'pfoa_present_adoption_events' ) ) {
		return pfoa_present_adoption_events( $value );
	}
	return $value;
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
function delete_post_meta( $post_id, $key ) {
	$GLOBALS['fixture_db_touched'] = true;
	return false;
}
function wp_insert_post( $postarr, $wp_error = false ) {
	$GLOBALS['fixture_db_touched'] = true;
	return 0;
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
	return ! empty( $GLOBALS['fixture_is_page_target'] );
}
function get_post( $post = null ) {
	if ( null === $post ) {
		$page            = new WP_Post();
		if ( empty( $GLOBALS['fixture_is_page_target'] ) ) {
			$page->ID        = 99999;
			$page->post_type = 'page';
			$page->post_name = 'elsewhere';
			return $page;
		}
		$page->ID        = 22514;
		$page->post_type = 'page';
		$page->post_name = 'adopted2026';
		return $page;
	}
	$id = (int) $post;
	if ( isset( $GLOBALS['fixture_meta'][ $id ] ) ) {
		$event            = new WP_Post();
		$event->ID        = $id;
		$event->post_type = 'pfoa_adoption_event';
		return $event;
	}
	return null;
}
function get_posts( $args = array() ) {
	return $GLOBALS['fixture_event_ids'];
}
function get_post_meta( $post_id, $key = '', $single = false ) {
	$id = (int) $post_id;
	if ( ! isset( $GLOBALS['fixture_meta'][ $id ] ) || ! array_key_exists( (string) $key, $GLOBALS['fixture_meta'][ $id ] ) ) {
		return $single ? '' : array();
	}
	$value = $GLOBALS['fixture_meta'][ $id ][ (string) $key ];
	return $single ? $value : array( $value );
}
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}
function esc_html__( $text, $domain = null ) {
	return (string) $text;
}
function wp_attachment_is_image( $attachment_id ) {
	return true;
}
function wp_get_attachment_link( $attachment_id, $size = 'thumbnail', $permalink = false, $icon = false ) {
	$id = (int) $attachment_id;
	return '<a href="https://example.com/full' . $id . '.jpg"><img src="https://example.com/med' . $id . '.jpg" alt=""></a>';
}

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

function fixture_set_event( $event_id, $uuid, $date, $members, $anchor = '__absent__' ) {
	$GLOBALS['fixture_meta'][ (int) $event_id ] = array(
		'_pfoa_event_year'    => '2026',
		'_pfoa_event_date'    => (string) $date,
		'_pfoa_event_uuid'    => (string) $uuid,
		'_pfoa_event_members' => $members,
	);
	if ( '__absent__' !== $anchor ) {
		$GLOBALS['fixture_meta'][ (int) $event_id ]['_pfoa_event_legacy_anchor_image_id'] = $anchor;
	}
}

function fixture_member( $image_id, $name ) {
	return array(
		'image_id'          => (int) $image_id,
		'cat_name_at_event' => (string) $name,
		'caption'           => (string) $name,
		'cat_post_id'       => 0,
	);
}

function fixture_present( $inner ) {
	return pfoa_present_adoption_events( '<div class="gallery galleryid-22514 gallery-columns-6">' . $inner . '</div>' );
}

function fixture_assert_survives( $out, $marker, $label ) {
	fixture_assert( false !== strpos( (string) $out, (string) $marker ), $label . ' fails closed — ambiguous node survives (saw ' . $marker . ')' );
}

function fixture_assert_suppressed( $out, $marker, $label ) {
	fixture_assert( false === strpos( (string) $out, (string) $marker ), $label . ' suppresses unanimous duplicate (marker ' . $marker . ' gone)' );
}

// Direct production-collection probe: parse ONE .gallery-item and run the
// production collector (no presenter involved).
$GLOBALS['fixture_collector_used'] = false;

function fixture_identity( $item_html ) {
	fixture_assert( function_exists( 'pfoa_collect_gallery_item_identity' ), 'collection: production collector exists (RED on baseline without the fix)' );
	$dom = new DOMDocument( '1.0', 'UTF-8' );
	$ok  = @$dom->loadHTML( '<html><body><div id="w">' . $item_html . '</div></body></html>' );
	fixture_assert( $ok, 'collection harness loads' );
	$xp   = new DOMXPath( $dom );
	$list = $xp->query( '//*[@id="w"]/*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]' );
	fixture_assert( $list instanceof DOMNodeList && 1 === $list->length, 'collection harness finds exactly one item' );
	$item = $list->item( 0 );
	fixture_assert( $item instanceof DOMElement, 'collection harness item is an element' );
	$GLOBALS['fixture_collector_used'] = true;
	return pfoa_collect_gallery_item_identity( $item, $xp );
}

// 0.1.142-logic href replica: pairs without "=" are SKIPPED (the B2 gap).
function fixture_old_href_collect( $query ) {
	$ids = array();
	foreach ( explode( '&', (string) $query ) as $pair ) {
		$eq = strpos( (string) $pair, '=' );
		if ( false === $eq ) {
			continue;
		}
		$name = urldecode( (string) substr( (string) $pair, 0, (int) $eq ) );
		if ( 'attachment_id' !== $name ) {
			continue;
		}
		$value   = urldecode( (string) substr( (string) $pair, (int) $eq + 1 ) );
		$claimed = pfoa_valid_claimed_attachment_id( $value );
		if ( null !== $claimed ) {
			$ids[ $claimed ] = true;
		}
	}
	return $ids;
}

fixture_assert( function_exists( 'pfoa_present_adoption_events' ), 'present function exists' );
fixture_assert( function_exists( 'pfoa_valid_claimed_attachment_id' ), 'validator exists' );
fixture_assert( function_exists( 'pfoa_adopted2026_gallery_image_attributes' ), 'identity filter exists' );

// ---------------------------------------------------------------------------
// Default claim context: Senior Shadow Event 24666 claims attachment 23330.
// ---------------------------------------------------------------------------
define( 'FIXTURE_GI_UUID_A', '9fe76269-c9bb-4024-aef0-ad9fc51e5b57' );
$GLOBALS['fixture_event_ids'] = array( 24666 );
fixture_set_event( 24666, FIXTURE_GI_UUID_A, '2026-06-19', array( fixture_member( 23330, 'Shadow' ) ) );

// ---------------------------------------------------------------------------
// B3.0 validator unit battery: int on valid, null (never 0) on invalid.
// ---------------------------------------------------------------------------
foreach ( array( array( '1', 1 ), array( '23330', 23330 ), array( '12345678901', 12345678901 ), array( (string) PHP_INT_MAX, PHP_INT_MAX ) ) as $valid_case ) {
	$got = pfoa_valid_claimed_attachment_id( $valid_case[0] );
	fixture_assert( $valid_case[1] === $got, 'B3.0: validator accepts ' . $valid_case[0] );
}
$huge_domain = str_repeat( '9', 30 );
foreach ( array( '0', '', ' ', '23330 ', ' 23330', "23330\n", '023330', '-5', '+5', '23330.9', '2.333e4', 'abc', PHP_INT_MAX . '0', '99999999999999999999999', $huge_domain ) as $bad_token ) {
	$got = pfoa_valid_claimed_attachment_id( $bad_token );
	fixture_assert( null === $got, 'B3.0: validator rejects token (' . strlen( (string) $bad_token ) . ' chars) with null, never 0' );
	fixture_assert( ! is_int( $got ), 'B3.0: invalid token is not an int sentinel' );
}

// ---------------------------------------------------------------------------
// B1: every collector records invalid recognized occurrences (each case
// pairs a VALID 23330 signal with an INVALID signal and must preserve;
// 0.1.142 silently discarded the invalid half and suppressed -> RED).
// ---------------------------------------------------------------------------
$b11 = '<figure class="gallery-item"><img data-attachment-id="23330" data-id="23330.9" src="https://example.com/medB11.jpg" alt=""></figure>';
$id_b11 = fixture_identity( $b11 );
fixture_assert( isset( $id_b11['valid_ids'][23330] ) && 0 < $id_b11['invalid'] && 2 <= $id_b11['recognized'], 'B1.1 collection: valid 23330 plus fractional invalid recorded' );
fixture_assert_survives( fixture_present( $b11 ), 'medB11', 'B1.1: valid+fractional data attrs' );

$b12 = '<figure class="gallery-item"><img data-id="23330" data-attachment-id="0" src="https://example.com/medB12.jpg" alt=""></figure>';
$id_b12 = fixture_identity( $b12 );
fixture_assert( isset( $id_b12['valid_ids'][23330] ) && 0 < $id_b12['invalid'], 'B1.2 collection: valid 23330 plus zero invalid recorded' );
fixture_assert_survives( fixture_present( $b12 ), 'medB12', 'B1.2: valid+zero data attrs' );

$b13 = '<figure class="gallery-item"><img data-id="23330" src="https://example.com/medB13.jpg" alt=""><figcaption id="gallery-1-abc">Shadow</figcaption></figure>';
$id_b13 = fixture_identity( $b13 );
fixture_assert( isset( $id_b13['valid_ids'][23330] ) && 0 < $id_b13['invalid'], 'B1.3 collection: valid 23330 plus malformed caption recorded' );
fixture_assert_survives( fixture_present( $b13 ), 'medB13', 'B1.3: valid+malformed caption' );

$b14 = '<figure class="gallery-item"><img data-id="23330" aria-describedby="gallery-1-xyz" src="https://example.com/medB14.jpg" alt=""></figure>';
$id_b14 = fixture_identity( $b14 );
fixture_assert( isset( $id_b14['valid_ids'][23330] ) && 0 < $id_b14['invalid'], 'B1.4 collection: valid 23330 plus malformed ARIA recorded' );
fixture_assert_survives( fixture_present( $b14 ), 'medB14', 'B1.4: valid+malformed ARIA' );

$b15 = '<figure class="gallery-item"><img data-attachment-id="23330" data-id="23330' . "\n" . '" src="https://example.com/medB15.jpg" alt=""></figure>';
$id_b15 = fixture_identity( $b15 );
fixture_assert( isset( $id_b15['valid_ids'][23330] ) && 0 < $id_b15['invalid'], 'B1.5 collection: valid 23330 plus newline-tainted invalid recorded' );
fixture_assert_survives( fixture_present( $b15 ), 'medB15', 'B1.5: valid+newline data-id' );

$b16 = '<figure class="gallery-item"><img class="attachment-medium wp-image-23330 wp-image-abc" src="https://example.com/medB16.jpg" alt=""></figure>';
$id_b16 = fixture_identity( $b16 );
fixture_assert( isset( $id_b16['valid_ids'][23330] ) && 0 < $id_b16['invalid'], 'B1.6 collection: one class attr with valid+invalid tokens recorded' );
fixture_assert_survives( fixture_present( $b16 ), 'medB16', 'B1.6: multiple conflicting tokens in one source' );

// B1 positive controls: unrelated classes/ARIA are ignored, unanimity holds.
$pos_class = '<figure class="gallery-item"><img class="attachment-medium size-medium foo-bar" data-id="23330" src="https://example.com/medB1PosC.jpg" alt=""></figure>';
$id_posc  = fixture_identity( $pos_class );
fixture_assert( 1 === count( $id_posc['valid_ids'] ) && 0 === $id_posc['invalid'], 'B1-POS-C collection: unrelated classes ignored' );
fixture_assert_suppressed( fixture_present( $pos_class ), 'medB1PosC', 'B1-POS-C: unrelated classes preserved-through (still suppresses)' );

$pos_aria = '<figure class="gallery-item"><img data-id="23330" aria-describedby="photo description-text" src="https://example.com/medB1PosA.jpg" alt=""></figure>';
$id_posa  = fixture_identity( $pos_aria );
fixture_assert( 1 === count( $id_posa['valid_ids'] ) && 0 === $id_posa['invalid'], 'B1-POS-A collection: unrelated ARIA ignored' );
fixture_assert_suppressed( fixture_present( $pos_aria ), 'medB1PosA', 'B1-POS-A: unrelated ARIA preserved-through (still suppresses)' );

// Absent attributes are ignored, not invalid: a lone data-attachment-id
// still suppresses, and a signal-free item is unmatched (0 recognized).
$lone_data = '<figure class="gallery-item"><img data-attachment-id="23330" src="https://example.com/medB1Lone.jpg" alt=""></figure>';
$id_lone   = fixture_identity( $lone_data );
fixture_assert( 1 === count( $id_lone['valid_ids'] ) && 0 === $id_lone['invalid'] && 1 === $id_lone['recognized'], 'B1-LONE collection: absent attrs ignored, lone present attr recognized' );
fixture_assert_suppressed( fixture_present( $lone_data ), 'medB1Lone', 'B1-LONE: lone data-attribute duplicate' );

$bare_item = '<figure class="gallery-item"><img src="https://example.com/medB1Bare.jpg" alt=""></figure>';
$id_bare   = fixture_identity( $bare_item );
fixture_assert( array() === $id_bare['valid_ids'] && 0 === $id_bare['invalid'] && 0 === $id_bare['recognized'], 'B1-BARE collection: signal-free item is unmatched (absent, not invalid)' );
fixture_assert_survives( fixture_present( $bare_item ), 'medB1Bare', 'B1-BARE: unmatched item survives' );

// ---------------------------------------------------------------------------
// B2: href query parsing (bare / encoded / repeated / empty / unrelated /
// fragment). Event still claims 23330.
// ---------------------------------------------------------------------------
$b21 = '<figure class="gallery-item"><a href="https://example.com/?attachment_id"><img data-id="23330" src="https://example.com/medB21.jpg" alt=""></a></figure>';
$id_b21 = fixture_identity( $b21 );
fixture_assert( isset( $id_b21['valid_ids'][23330] ) && 0 < $id_b21['invalid'], 'B2.1 collection: bare key is recognized-invalid' );
fixture_assert_survives( fixture_present( $b21 ), 'medB21', 'B2.1: bare attachment_id key' );

$b22 = '<figure class="gallery-item"><a href="https://example.com/?attachment%5Fid=99999"><img data-id="23330" src="https://example.com/medB22.jpg" alt=""></a></figure>';
$id_b22 = fixture_identity( $b22 );
fixture_assert( isset( $id_b22['valid_ids'][23330] ) && isset( $id_b22['valid_ids'][99999] ), 'B2.2 collection: urldecoded name counts (conflict recorded)' );
fixture_assert_survives( fixture_present( $b22 ), 'medB22', 'B2.2: encoded attachment_id name' );

$b23u = '<figure class="gallery-item"><a href="https://example.com/?attachment_id=23330&attachment_id=23330"><img src="https://example.com/medB23U.jpg" alt=""></a></figure>';
$id_b23u = fixture_identity( $b23u );
fixture_assert( 1 === count( $id_b23u['valid_ids'] ) && 0 === $id_b23u['invalid'] && 2 <= $id_b23u['recognized'], 'B2.3U collection: every unanimous repeat processed' );
fixture_assert_suppressed( fixture_present( $b23u ), 'medB23U', 'B2.3U: unanimous repeated params' );

$b23c = '<figure class="gallery-item"><a href="https://example.com/?attachment_id=23330&attachment_id=99999"><img src="https://example.com/medB23C.jpg" alt=""></a></figure>';
$id_b23c = fixture_identity( $b23c );
fixture_assert( 2 === count( $id_b23c['valid_ids'] ), 'B2.3C collection: conflicting repeats break unanimity' );
fixture_assert_survives( fixture_present( $b23c ), 'medB23C', 'B2.3C: conflicting repeated params' );

$b24 = '<figure class="gallery-item"><a href="https://example.com/?attachment_id="><img data-id="23330" src="https://example.com/medB24.jpg" alt=""></a></figure>';
$id_b24 = fixture_identity( $b24 );
fixture_assert( isset( $id_b24['valid_ids'][23330] ) && 0 < $id_b24['invalid'], 'B2.4 collection: empty value is recognized-invalid' );
fixture_assert_survives( fixture_present( $b24 ), 'medB24', 'B2.4: empty attachment_id value' );

$b25 = '<figure class="gallery-item"><a href="https://example.com/?foo=99999&size=large"><img data-id="23330" src="https://example.com/medB25.jpg" alt=""></a></figure>';
$id_b25 = fixture_identity( $b25 );
fixture_assert( 1 === count( $id_b25['valid_ids'] ) && 0 === $id_b25['invalid'] && 1 === $id_b25['recognized'], 'B2.5 collection: unrelated keys ignored' );
fixture_assert_suppressed( fixture_present( $b25 ), 'medB25', 'B2.5: unrelated query keys' );

$b26 = '<figure class="gallery-item"><a href="https://example.com/#section?attachment_id=99999"><img data-id="23330" src="https://example.com/medB26.jpg" alt=""></a></figure>';
$id_b26 = fixture_identity( $b26 );
fixture_assert( 1 === count( $id_b26['valid_ids'] ) && 0 === $id_b26['invalid'] && 1 === $id_b26['recognized'], 'B2.6 collection: fragment never counts' );
fixture_assert_suppressed( fixture_present( $b26 ), 'medB26', 'B2.6: fragment ?attachment_id= is not query identity' );

// ---------------------------------------------------------------------------
// B3: sentinel-collision + large-ID + outside-domain (presenter level).
// ---------------------------------------------------------------------------
// B3.1 sentinel collision: event claims PHP_INT_MAX; a single malformed
// href must preserve. On 0.1.142 the malformed occurrence injected the
// sentinel PHP_INT_MAX - 0 == the claimed ID and the item was suppressed.
$GLOBALS['fixture_event_ids'] = array( 24668 );
fixture_set_event( 24668, 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee', '2026-06-20', array( fixture_member( PHP_INT_MAX, 'Sentinel' ) ) );
$b31 = '<figure class="gallery-item"><a href="https://example.com/?attachment_id=99999.9"><img src="https://example.com/medB31.jpg" alt=""></a></figure>';
$id_b31 = fixture_identity( $b31 );
fixture_assert( array() === $id_b31['valid_ids'] && 0 < $id_b31['invalid'], 'B3.1 collection: malformed href is invalid, never a matchable ID' );
fixture_assert_survives( fixture_present( $b31 ), 'medB31', 'B3.1: sentinel-collision malformed href never matches claimed PHP_INT_MAX' );

// B3.2 large ID: 11-digit claim (rejected by the old 10-digit limit) with a
// unanimous multi-signal item must suppress.
$GLOBALS['fixture_event_ids'] = array( 24669 );
fixture_set_event( 24669, 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff', '2026-06-21', array( fixture_member( 12345678901, 'Big' ) ) );
$b32 = '<figure class="gallery-item"><a href="https://example.com/?attachment_id=12345678901"><img class="wp-image-12345678901" data-attachment-id="12345678901" aria-describedby="gallery-1-12345678901" src="https://example.com/medB32.jpg" alt=""></a><figcaption id="gallery-1-12345678901">Big</figcaption></figure>';
$id_b32 = fixture_identity( $b32 );
fixture_assert( 1 === count( $id_b32['valid_ids'] ) && isset( $id_b32['valid_ids'][12345678901] ) && 0 === $id_b32['invalid'], 'B3.2 collection: 11-digit unanimous identity is valid' );
fixture_assert_suppressed( fixture_present( $b32 ), 'medB32', 'B3.2: large-ID unanimous duplicate' );

// B3.3 outside-domain: a 30-digit token is never valid and never eligible
// to match discovery, alone or beside a valid agreeing token.
$GLOBALS['fixture_event_ids'] = array( 24666 );
fixture_set_event( 24666, FIXTURE_GI_UUID_A, '2026-06-19', array( fixture_member( 23330, 'Shadow' ) ) );
$b33a = '<figure class="gallery-item"><img data-id="' . $huge_domain . '" src="https://example.com/medB33A.jpg" alt=""></figure>';
$id_b33a = fixture_identity( $b33a );
fixture_assert( array() === $id_b33a['valid_ids'] && 0 < $id_b33a['invalid'], 'B3.3A collection: outside-domain token is invalid, never matchable' );
fixture_assert_survives( fixture_present( $b33a ), 'medB33A', 'B3.3A: outside-domain token alone' );

$b33b = '<figure class="gallery-item"><a href="https://example.com/?attachment_id=' . $huge_domain . '"><img data-id="23330" src="https://example.com/medB33B.jpg" alt=""></a></figure>';
$id_b33b = fixture_identity( $b33b );
fixture_assert( isset( $id_b33b['valid_ids'][23330] ) && 0 < $id_b33b['invalid'], 'B3.3B collection: outside-domain sibling blocks unanimity' );
fixture_assert_survives( fixture_present( $b33b ), 'medB33B', 'B3.3B: invalid never eligible to match discovery' );

// ---------------------------------------------------------------------------
// SOL real case: Event 24666 -> 23330 with a prio-9 malformed
// data-id="99999.9" plus the prio-10 production emitter
// data-attachment-id="23330". The ambiguous item must preserve via BOTH the
// direct shortcode-shape path and the full the_content path. On 0.1.142 the
// malformed data-id was silently discarded and the item suppressed -> RED.
// ---------------------------------------------------------------------------
function fixture_prio9_malformed_attrs( $attr ) {
	if ( ! is_array( $attr ) ) {
		return $attr;
	}
	$attr['data-id'] = '99999.9';
	return $attr;
}

$sol_base = array(
	'class' => 'attachment-medium size-medium',
	'src'   => 'https://example.com/medSolPrio9.jpg',
	'alt'   => '',
);
$sol_after_prio9 = fixture_prio9_malformed_attrs( $sol_base );
fixture_assert( '99999.9' === (string) $sol_after_prio9['data-id'], 'SOL setup: prio-9 filter injects malformed data-id' );
$sol_attachment     = new WP_Post();
$sol_attachment->ID = 23330;
$sol_final          = pfoa_adopted2026_gallery_image_attributes( $sol_after_prio9, $sol_attachment, 'medium' );
fixture_assert( is_array( $sol_final ), 'SOL setup: production emitter returns attributes' );
fixture_assert( '23330' === (string) $sol_final['data-attachment-id'], 'SOL setup: prio-10 emitter adds authoritative data-attachment-id' );
fixture_assert( '99999.9' === (string) $sol_final['data-id'], 'SOL setup: ambiguous pair coexists pre-presentation' );
fixture_assert( (string) $sol_final['src'] === (string) $sol_base['src'] && (string) $sol_final['class'] === (string) $sol_base['class'], 'SOL setup: emitter additive-only' );
$sol_parts = array();
foreach ( $sol_final as $sol_key => $sol_value ) {
	$sol_parts[] = $sol_key . '="' . esc_attr( $sol_value ) . '"';
}
$sol_item = '<figure class="gallery-item"><div class="gallery-icon landscape"><a href="https://example.com/uploads/2026/07/ShadowWeb4040.jpg"><img ' . implode( ' ', $sol_parts ) . ' /></a></div></figure>';
$sol_id   = fixture_identity( $sol_item );
fixture_assert( isset( $sol_id['valid_ids'][23330] ) && 0 < $sol_id['invalid'], 'SOL collection: emitter valid + prio-9 malformed recorded' );
$sol_direct = fixture_present( $sol_item );
fixture_assert_survives( $sol_direct, 'medSolPrio9', 'SOL: direct shortcode-shape path preserves ambiguous item' );
fixture_assert_survives( $sol_direct, 'data-id="99999.9"', 'SOL: direct path keeps malformed evidence' );
$sol_full = apply_filters( 'the_content', '<div class="gallery galleryid-22514 gallery-columns-6">' . $sol_item . '</div>' );
fixture_assert_survives( $sol_full, 'medSolPrio9', 'SOL: full the_content path preserves ambiguous item' );
fixture_assert_survives( $sol_full, 'data-id="99999.9"', 'SOL: full path keeps malformed evidence' );

// ---------------------------------------------------------------------------
// SHADOW positive: original captionless Senior Shadow unanimous item
// (production emitter identity only) still suppresses via the_content.
// ---------------------------------------------------------------------------
$shadow_attrs = array(
	'class' => 'attachment-medium size-medium',
	'src'   => 'https://example.com/uploads/2026/07/ShadowWeb4040-300x300.jpg',
	'alt'   => '',
);
$shadow_filtered = pfoa_adopted2026_gallery_image_attributes( $shadow_attrs, $sol_attachment, 'medium' );
$shadow_parts    = array();
foreach ( $shadow_filtered as $shadow_key => $shadow_value ) {
	$shadow_parts[] = $shadow_key . '="' . esc_attr( $shadow_value ) . '"';
}
$shadow_item = '<figure class="gallery-item"><div class="gallery-icon landscape"><a href="https://example.com/uploads/2026/07/ShadowWeb4040.jpg"><img ' . implode( ' ', $shadow_parts ) . ' /></a></div></figure>';
$shadow_id   = fixture_identity( $shadow_item );
fixture_assert( 1 === count( $shadow_id['valid_ids'] ) && isset( $shadow_id['valid_ids'][23330] ) && 0 === $shadow_id['invalid'], 'SHADOW collection: unanimous emitter identity' );
$shadow_out = apply_filters( 'the_content', '<div class="gallery galleryid-22514 gallery-columns-6">' . $shadow_item . '</div>' );
fixture_assert( 1 === substr_count( $shadow_out, 'data-pfoa-adoption-event="' . FIXTURE_GI_UUID_A . '"' ), 'SHADOW: Senior Shadow event renders once' );
fixture_assert( false === strpos( $shadow_out, 'ShadowWeb4040' ), 'SHADOW: unanimous captionless duplicate still suppressed' );

// ---------------------------------------------------------------------------
// CONTRACT (after the behavioral batteries so a 0.1.142 RED run fails on
// behavior first): the production collector exists and was exercised, and
// no numeric sentinels remain in production source (the invalid signal is
// null, never 0; no PHP_INT_MAX ambiguity sequence encoded as an ID).
// ---------------------------------------------------------------------------
fixture_assert( function_exists( 'pfoa_collect_gallery_item_identity' ), 'collection function exists (RED on baseline without the fix)' );
fixture_assert( true === $GLOBALS['fixture_collector_used'], 'contract: behavioral batteries exercised the production collector' );
$functions_source = (string) file_get_contents( dirname( __DIR__ ) . '/pfoa-theme/functions.php' );
fixture_assert( false !== strpos( $functions_source, 'function pfoa_collect_gallery_item_identity' ), 'source: single auditable identity collector present' );
fixture_assert( false === strpos( $functions_source, 'PHP_INT_MAX - $identity_ambiguous_seq' ), 'source: PHP_INT_MAX ambiguity sentinel removed' );
fixture_assert( false === strpos( $functions_source, '$identity_ambiguous_seq' ), 'source: ambiguity sequence removed' );
fixture_assert( false !== strpos( $functions_source, "'invalid'" ), 'source: recognized-but-invalid is counted, not encoded' );

// ---------------------------------------------------------------------------
// M1/M2/M3 negative mutation checks: each simulates a disabled guard and
// asserts the simulation produces the buggy outcome, proving the
// production assertions above fail under that mutation.
// ---------------------------------------------------------------------------
// M1 disabling malformed-tracking (valid-only collection, 0.1.142-style
// silent discard for non-href sources): B1.1 would look unanimous.
fixture_assert( 1 === count( $id_b11['valid_ids'] ) && isset( $id_b11['valid_ids'][23330] ), 'M1: valid-only view of B1.1 is unanimous 23330 (mutation would SUPPRESS — B1.1 fails)' );
fixture_assert( 0 < $id_b11['invalid'], 'M1: production invalid tracking is what preserves B1.1' );

// M2 disabling bare-query handling (0.1.142 `continue` on missing `=`):
// the bare key vanishes and B2.1 would look unanimous.
$mut_href_bare = fixture_old_href_collect( 'attachment_id' );
fixture_assert( array() === $mut_href_bare, 'M2: old parser sees zero IDs in a bare key (mutation would SUPPRESS B2.1 — B2.1 fails)' );
fixture_assert( 0 < $id_b21['invalid'], 'M2: production bare-key tracking is what preserves B2.1' );

// M3 disabling invalidity protection via trim-then-accept: "23330 " would
// validate after trim and a whitespace-tainted href-only item would match.
$trim_candidate = '23330 ';
fixture_assert( null !== pfoa_valid_claimed_attachment_id( trim( $trim_candidate ) ), 'M3: trim-then-accept validates the tainted token (mutation would SUPPRESS — D4-style cases fail)' );
fixture_assert( null === pfoa_valid_claimed_attachment_id( $trim_candidate ), 'M3: production rejects the raw tainted token' );
$cast_candidate = '23330.9';
fixture_assert( 23330 === (int) $cast_candidate, 'M3: cast-first accepts the fractional token (mutation would SUPPRESS — F1-style cases fail)' );
fixture_assert( null === pfoa_valid_claimed_attachment_id( $cast_candidate ), 'M3: production rejects the raw fractional token' );

// No writes across all mutations.
fixture_assert( false === $GLOBALS['fixture_db_touched'], 'no DB writes during presentation' );

fwrite( STDOUT, "PASS: theme adoption event gallery integrity fixture (B1 invalid-tracking x6, B2 query parsing, B3 validator/sentinel/large/outside-domain, SOL prio-9+prio-10 both paths, SHADOW unanimous suppression, M1/M2/M3 mutations, no writes)\n" );
