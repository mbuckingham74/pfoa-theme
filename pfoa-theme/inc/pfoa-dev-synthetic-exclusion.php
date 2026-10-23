<?php
/**
 * PFOA DEV-ONLY TEMPORARY SYNTHETIC LIFECYCLE QA EXCLUSION — REMOVE AT FINAL PRE-LAUNCH CLEANUP.
 *
 * Purpose: hide the synthetic lifecycle QA content from DEV public presentation
 * before volunteer UAT, without touching the database. All records, revisions,
 * attachments, return history, receipts, and evidence stay intact in wp-admin.
 *
 * Scope (exact verified IDs only — see docs/dev-synthetic-exclusion-removal.md):
 * - 14 synthetic QA Cat Profiles (publish, post_type pfoa_cat):
 *   25351, 25347, 25341, 25338, 25335, 25332, 25327, 25324, 25321,
 *   25316, 25313, 25309, 25304, 25294.
 * - 12 synthetic QA Adoption Events (publish, post_type pfoa_adoption_event,
 *   all _pfoa_event_year 2026):
 *   25302, 25303, 25312, 25319, 25320, 25330, 25331, 25344, 25345,
 *   25346, 25350, 25355.
 *
 * Activation: DEV host only — home/siteurl host is dev.safehavenpfoa.org.
 * (wp_get_environment_type() is 'production' on DEV, so it MUST NOT be used
 * as the gate.) Production is byte-identical: every guard returns early when
 * the host gate is off. Admin, feeds, and REST are never filtered.
 *
 * Surfaces:
 * 1. Adoptable Cats (page 20323 / adoptablecats2) stored static card markup
 *    — article.pfoa-cat-card[data-pfoa-profile-id] nodes removed via
 *    the_content @900 (before bonded-pair grouping @999).
 * 2. Adopted 2026 dynamic prepend (pfoa_present_adoption_events) — denied
 *    event IDs and denied member cat IDs skipped in-loop.
 * 3. Recently Adopted (pfoa_get_recently_adopted_events +
 *    pfoa_recently_adopted_is_currently_adopted) — same skip + currency
 *    helper returns false (drop) for denied cats.
 * 4. Direct public access — template_redirect serves the theme 404 for
 *    denied single pfoa_cat pages and ?pfoa_cat_fragment=<denied-slug>.
 *
 * Removal: delete this file, remove its require_once in functions.php, remove
 * the three marked in-loop guards, bump the version, redeploy. Full steps in
 * docs/dev-synthetic-exclusion-removal.md. No DB cleanup happens here.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'PFOA_DEV_SYNTHETIC_EXCLUSION_LOADED' ) ) {
	return;
}

define( 'PFOA_DEV_SYNTHETIC_EXCLUSION_LOADED', true );

/**
 * Exact verified synthetic QA Cat Profile IDs (publish pfoa_cat on DEV).
 *
 * @return int[]
 */
function pfoa_dev_synthetic_excluded_cat_ids() {
	return array(
		25351, 25347, 25341, 25338, 25335, 25332, 25327, 25324,
		25321, 25316, 25313, 25309, 25304, 25294,
	);
}

/**
 * Exact verified synthetic QA Adoption Event IDs (publish, year 2026).
 *
 * @return int[]
 */
function pfoa_dev_synthetic_excluded_event_ids() {
	return array(
		25302, 25303, 25312, 25319, 25320, 25330, 25331, 25344,
		25345, 25346, 25350, 25355,
	);
}

/**
 * Denied profile slugs (for the ?pfoa_cat_fragment= endpoint).
 *
 * @return string[]
 */
function pfoa_dev_synthetic_excluded_cat_slugs() {
	return array(
		'qa-test-solo-qa-lc-20261022-01',
		'qa-test-joint-a-qa-lc-20261022-01',
		'qa-test-joint-b-qa-lc-20261022-01',
		'qa-test-stagger-a-qa-lc-20261022-01',
		'qa-test-stagger-b-qa-lc-20261022-01',
		'qa-test-together-a-qa-lc-20261022-01',
		'qa-test-together-b-qa-lc-20261022-01',
		'qa-test-together-c-qa-lc-20261022-01',
		'qa-test-same-day-qa-lcd01-20261022-reg',
		'qa-test-after-adoption-qa-lcd01-20261022-reg',
		'qa-test-bond-a-qa-lcd01-20261022-reg',
		'qa-test-bond-b-qa-lcd01-20261022-reg',
		'qa-test-corrected-retry-qa-lcd01-20261022-reg',
		'qa-test-lcd01-r02-dev-20261023-091525-396be284',
	);
}

/**
 * DEV-host gate. True only when the site home URL host is the DEV host.
 * Intentionally ignores wp_get_environment_type() (DEV reports 'production').
 *
 * @return bool
 */
function pfoa_dev_synthetic_exclusion_active() {
	if ( function_exists( 'get_option' ) ) {
		$home = (string) get_option( 'home', '' );

		if ( '' !== $home ) {
			$host = (string) parse_url( $home, PHP_URL_HOST );

			if ( 'dev.safehavenpfoa.org' === strtolower( $host ) ) {
				return true;
			}

			return false;
		}
	}

	if ( isset( $_SERVER['HTTP_HOST'] ) ) {
		return 'dev.safehavenpfoa.org' === strtolower( (string) $_SERVER['HTTP_HOST'] );
	}

	return false;
}

/**
 * Whether a cat profile ID is in the denied synthetic set (host-gated).
 *
 * @param int $cat_id Cat post ID.
 * @return bool
 */
function pfoa_dev_synthetic_is_excluded_cat( $cat_id ) {
	if ( ! pfoa_dev_synthetic_exclusion_active() ) {
		return false;
	}

	return in_array( (int) $cat_id, pfoa_dev_synthetic_excluded_cat_ids(), true );
}

/**
 * Whether an adoption event ID is in the denied synthetic set (host-gated).
 *
 * @param int $event_id Event post ID.
 * @return bool
 */
function pfoa_dev_synthetic_is_excluded_event( $event_id ) {
	if ( ! pfoa_dev_synthetic_exclusion_active() ) {
		return false;
	}

	return in_array( (int) $event_id, pfoa_dev_synthetic_excluded_event_ids(), true );
}

/**
 * Adoptable Cats static markup filter (page 20323 only, public only).
 * Removes article.pfoa-cat-card[data-pfoa-profile-id=<denied>] nodes.
 * Presentation only — post_content, revisions, and meta are untouched.
 *
 * @param string $content Filtered post content.
 * @return string
 */
function pfoa_dev_exclude_synthetic_cards( $content ) {
	if ( ! pfoa_dev_synthetic_exclusion_active() ) {
		return $content;
	}

	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( false === strpos( $content, 'data-pfoa-profile-id' ) ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 20323 ) || is_page( 'adoptablecats2' ) ) ) {
		$is_target_page = true;
	} elseif ( function_exists( 'get_post' ) ) {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			if ( 20323 === (int) $post->ID ) {
				$is_target_page = true;
			} elseif ( isset( $post->post_name ) && 'adoptablecats2' === $post->post_name ) {
				$is_target_page = true;
			}
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		return $content;
	}

	$denied = pfoa_dev_synthetic_excluded_cat_ids();

	$prev_libxml = libxml_use_internal_errors( true );
	$dom         = new DOMDocument( '1.0', 'UTF-8' );

	if ( function_exists( 'mb_encode_numericentity' ) ) {
		$fragment = mb_encode_numericentity( $content, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' );
	} else {
		$fragment = $content;
	}

	$loaded = $dom->loadHTML(
		'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="pfoa-dev-synth-wrapper">' . $fragment . '</div></body></html>'
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$xpath = new DOMXPath( $dom );

	$wrapper_list = $xpath->query( '//div[@id="pfoa-dev-synth-wrapper"]' );

	if ( ! $wrapper_list instanceof DOMNodeList || 0 === $wrapper_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$wrapper = $wrapper_list->item( 0 );

	$card_list = $xpath->query(
		'.//article[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card ")][@data-pfoa-profile-id]',
		$wrapper
	);

	if ( ! $card_list instanceof DOMNodeList || 0 === $card_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$doomed  = array();
	$removed = 0;

	foreach ( $card_list as $card ) {
		if ( ! $card instanceof DOMElement ) {
			continue;
		}

		$pid = (int) $card->getAttribute( 'data-pfoa-profile-id' );

		if ( in_array( $pid, $denied, true ) ) {
			$doomed[] = $card;
		}
	}

	foreach ( $doomed as $node ) {
		if ( null !== $node->parentNode ) {
			$node->parentNode->removeChild( $node );
			++$removed;
		}
	}

	if ( 0 === $removed ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$out = '';

	foreach ( $wrapper->childNodes as $child ) {
		$out .= $dom->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $prev_libxml );

	return $out;
}

/**
 * Direct public access guard for denied synthetic profiles.
 * Serves the theme 404 on public single pfoa_cat pages and the
 * ?pfoa_cat_fragment=<denied-slug> endpoint. Admin/REST/feeds untouched.
 *
 * @return void
 */
function pfoa_dev_block_synthetic_profiles() {
	if ( ! pfoa_dev_synthetic_exclusion_active() ) {
		return;
	}

	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	// Fragment endpoint (?pfoa_cat_fragment=<slug>) served by the plugin.
	$fragment_slug = '';

	if ( isset( $_GET['pfoa_cat_fragment'] ) ) {
		$fragment_slug = strtolower( trim( (string) wp_unslash( $_GET['pfoa_cat_fragment'] ) ) );
	} elseif ( function_exists( 'get_query_var' ) ) {
		$fragment_slug = strtolower( trim( (string) get_query_var( 'pfoa_cat_fragment', '' ) ) );
	}

	if ( '' !== $fragment_slug && in_array( $fragment_slug, pfoa_dev_synthetic_excluded_cat_slugs(), true ) ) {
		status_header( 404 );
		nocache_headers();

		if ( function_exists( 'get_template_part' ) ) {
			global $wp_query;

			if ( $wp_query instanceof WP_Query ) {
				$wp_query->set_404();
			}

			get_template_part( 'template-parts/content', 'none' );
		}

		exit;
	}

	if ( ! function_exists( 'is_singular' ) || ! is_singular( 'pfoa_cat' ) ) {
		return;
	}

	$queried = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;

	if ( 0 < $queried && in_array( $queried, pfoa_dev_synthetic_excluded_cat_ids(), true ) ) {
		global $wp_query;

		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
		nocache_headers();

		$template = function_exists( 'get_404_template' ) ? get_404_template() : '';

		if ( '' !== $template && file_exists( $template ) ) {
			include $template;
		}

		exit;
	}
}

if ( function_exists( 'add_filter' ) ) {
	// Runs before bonded-pair grouping (999) so grouping sees the cleaned list.
	add_filter( 'the_content', 'pfoa_dev_exclude_synthetic_cards', 900 );
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'template_redirect', 'pfoa_dev_block_synthetic_profiles', 1 );
}
