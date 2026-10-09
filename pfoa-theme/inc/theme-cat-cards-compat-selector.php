<?php
/**
 * Theme-owned compat card selection policy (INACTIVE).
 *
 * INACTIVE — not required by functions.php, no hooks/shortcodes/filters/actions,
 * no writes, do NOT require until cutover authorises.
 *
 * Purpose: theme-owned selection policy for the bare and explicit card-listing
 * shapes. Given an optional slug seed list, this selector queries published
 * pfoa_cat records through the core query object only, reads facts exclusively
 * through the neutral provider snapshot (schema 1.0), applies the
 * required-image and adoptable-lifecycle gates with strict comparisons, and
 * returns the surviving snapshots in listing order. Slugs are ordering seeds
 * only, never membership restrictions: every other eligible record is
 * appended so newly published profiles appear without editing the page.
 *
 * Baseline choice: the fixed migrated baseline order is taken from the
 * provider baseline helper whenever that helper exists; otherwise an internal
 * fallback copy of the same fixed list is used. A missing or malformed
 * provider list fails the whole selection closed instead of silently
 * substituting an ad-hoc order.
 *
 * Pending records stay listed: pending-style flags are never used as
 * filters. Lifecycle state is honoured only through the single
 * eligible_for_adoptable gate; no direct adoption or bonded inspection
 * happens here.
 *
 * Out of scope on purpose: no rendering, no bonded-unit composition, and no
 * half-pair display. Authorising a half-pair display is a separate
 * prerequisite owned by a future cutover; this selector only orders whole
 * eligible snapshots.
 *
 * Activation contract: do NOT require this file from functions.php and do
 * NOT wire any hook until the cutover spec explicitly authorises it.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_CAT_CARDS_COMPAT_SELECTOR_LOADED' ) ) {
	define( 'PFOA_THEME_CAT_CARDS_COMPAT_SELECTOR_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_select_compat_cards' ) ) {
	/**
	 * Select ordered neutral snapshots for the compat card listing.
	 *
	 * Read-only and side-effect free: queries published pfoa_cat records via
	 * the core query object with the same base arguments as the provider
	 * (type, published status, unbounded count, no pagination totals), then
	 * resolves each record through the neutral provider snapshot only.
	 *
	 * Every queried published record must yield a valid snapshot (array,
	 * schema 1.0, matching id, matching type, published status); any invalid
	 * snapshot fails the whole selection closed rather than returning a
	 * partial list. Survivors additionally require a strict
	 * has_required_card_image flag and a strict eligible_for_adoptable flag;
	 * anything else is skipped without failing the selection.
	 *
	 * Bare input (no seeds) lists newcomers first in query order, then
	 * baseline records in the fixed migrated baseline order. Seeded input is
	 * deduplicated preserving supplied order; seeded survivors render first
	 * in seed order and every remaining eligible snapshot follows sorted by
	 * title ascending (record title preferred, snapshot name as fallback).
	 *
	 * @param array $slugs Optional ordering seeds (slugs, in preference order).
	 * @return array Array with 'status' ('success' or 'failure'), 'snapshots'
	 *               (ordered neutral arrays, empty on failure), and 'error'
	 *               (null on success, string code on failure).
	 */
	function pfoa_theme_select_compat_cards( array $slugs = array() ): array {
		if ( ! class_exists( 'WP_Query' ) || ! function_exists( 'pfoa_cat_get_profile_data' ) ) {
			return array(
				'status'    => 'failure',
				'snapshots' => array(),
				'error'     => 'missing-dependency',
			);
		}

		if ( function_exists( 'pfoa_cat_migrated_baseline_slugs' ) ) {
			$provided = pfoa_cat_migrated_baseline_slugs();

			if ( ! is_array( $provided ) || array() === $provided ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'baseline-unavailable',
				);
			}

			foreach ( $provided as $candidate ) {
				if ( ! is_string( $candidate ) || '' === trim( $candidate ) ) {
					return array(
						'status'    => 'failure',
						'snapshots' => array(),
						'error'     => 'baseline-unavailable',
					);
				}
			}

			$baseline_slugs = array_values( $provided );
		} else {
			$baseline_slugs = array(
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

		if ( array() === $baseline_slugs ) {
			return array(
				'status'    => 'failure',
				'snapshots' => array(),
				'error'     => 'baseline-unavailable',
			);
		}

		$base_args = array(
			'post_type'      => 'pfoa_cat',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'no_found_rows'  => true,
		);

		$seeds = array();

		foreach ( $slugs as $raw_slug ) {
			if ( ! is_string( $raw_slug ) ) {
				continue;
			}

			$trimmed = trim( $raw_slug );

			if ( '' === $trimmed || in_array( $trimmed, $seeds, true ) ) {
				continue;
			}

			$seeds[] = $trimmed;
		}

		if ( array() === $seeds ) {
			$query = new WP_Query(
				array_merge(
					$base_args,
					array(
						'orderby' => array(
							'date' => 'DESC',
							'ID'   => 'DESC',
						),
					)
				)
			);

			$posts = isset( $query->posts ) && is_array( $query->posts ) ? $query->posts : array();

			$baseline_index  = array_flip( $baseline_slugs );
			$newcomers       = array();
			$baseline_bucket = array();

			foreach ( $posts as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				if ( 'pfoa_cat' !== $post->post_type || 'publish' !== $post->post_status ) {
					continue;
				}

				$post_slug = isset( $post->post_name ) && is_string( $post->post_name ) ? $post->post_name : '';

				if ( '' !== $post_slug && isset( $baseline_index[ $post_slug ] ) ) {
					$baseline_bucket[ (int) $baseline_index[ $post_slug ] ] = $post;
				} else {
					$newcomers[] = $post;
				}
			}

			ksort( $baseline_bucket );

			$ordered_posts = array_merge( $newcomers, array_values( $baseline_bucket ) );
			$is_explicit   = false;
		} else {
			$seeded_query = new WP_Query(
				array_merge(
					$base_args,
					array(
						'post_name__in' => $seeds,
						'orderby'       => 'post_name__in',
					)
				)
			);

			$seeded_posts = isset( $seeded_query->posts ) && is_array( $seeded_query->posts ) ? $seeded_query->posts : array();

			$rest_query = new WP_Query(
				array_merge(
					$base_args,
					array(
						'orderby' => 'title',
						'order'   => 'ASC',
					)
				)
			);

			$rest_posts = isset( $rest_query->posts ) && is_array( $rest_query->posts ) ? $rest_query->posts : array();

			$ordered_posts = array();
			$seen_ids      = array();

			foreach ( $seeded_posts as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				if ( 'pfoa_cat' !== $post->post_type || 'publish' !== $post->post_status ) {
					continue;
				}

				$post_id = (int) $post->ID;

				if ( isset( $seen_ids[ $post_id ] ) ) {
					continue;
				}

				$seen_ids[ $post_id ] = true;
				$ordered_posts[]      = $post;
			}

			foreach ( $rest_posts as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				if ( 'pfoa_cat' !== $post->post_type || 'publish' !== $post->post_status ) {
					continue;
				}

				$post_id = (int) $post->ID;

				if ( isset( $seen_ids[ $post_id ] ) ) {
					continue;
				}

				$seen_ids[ $post_id ] = true;
				$ordered_posts[]      = $post;
			}

			$is_explicit = true;
		}

		$expected_schema = '1.0';

		if ( defined( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' ) ) {
			$declared = constant( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' );

			if ( is_string( $declared ) && '' !== $declared ) {
				$expected_schema = $declared;
			}
		}

		$eligible = array();

		foreach ( $ordered_posts as $post ) {
			$post_id = (int) $post->ID;

			$snapshot = pfoa_cat_get_profile_data( $post_id );

			if ( ! is_array( $snapshot ) ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'invalid-snapshot',
				);
			}

			$schema = isset( $snapshot['schema_version'] ) ? $snapshot['schema_version'] : null;

			if ( '1.0' !== $schema && $schema !== $expected_schema ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'invalid-snapshot',
				);
			}

			$snapshot_id = isset( $snapshot['post_id'] ) ? (int) $snapshot['post_id'] : 0;

			if ( $snapshot_id !== $post_id ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'invalid-snapshot',
				);
			}

			if ( ! isset( $snapshot['post_type'] ) || 'pfoa_cat' !== $snapshot['post_type'] ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'invalid-snapshot',
				);
			}

			if ( ! isset( $snapshot['post_status'] ) || 'publish' !== $snapshot['post_status'] ) {
				return array(
					'status'    => 'failure',
					'snapshots' => array(),
					'error'     => 'invalid-snapshot',
				);
			}

			$publication = isset( $snapshot['publication'] ) && is_array( $snapshot['publication'] )
				? $snapshot['publication']
				: array();

			if ( ! array_key_exists( 'has_required_card_image', $publication ) || true !== $publication['has_required_card_image'] ) {
				continue;
			}

			$lifecycle = isset( $snapshot['lifecycle'] ) && is_array( $snapshot['lifecycle'] )
				? $snapshot['lifecycle']
				: array();

			if ( ! array_key_exists( 'eligible_for_adoptable', $lifecycle ) || true !== $lifecycle['eligible_for_adoptable'] ) {
				continue;
			}

			$eligible[] = array(
				'post'     => $post,
				'snapshot' => $snapshot,
			);
		}

		if ( $is_explicit ) {
			$by_slug = array();

			foreach ( $eligible as $entry ) {
				$entry_slug = isset( $entry['post']->post_name ) && is_string( $entry['post']->post_name )
					? $entry['post']->post_name
					: '';

				if ( '' !== $entry_slug && ! isset( $by_slug[ $entry_slug ] ) ) {
					$by_slug[ $entry_slug ] = $entry;
				}
			}

			$seeded_first = array();

			foreach ( $seeds as $seed ) {
				if ( isset( $by_slug[ $seed ] ) ) {
					$seeded_first[] = $by_slug[ $seed ];
					unset( $by_slug[ $seed ] );
				}
			}

			$remaining = array_values( $by_slug );

			usort(
				$remaining,
				function ( $a, $b ) {
					$title_a = '';
					$title_b = '';

					if ( isset( $a['post']->post_title ) && is_string( $a['post']->post_title ) && '' !== $a['post']->post_title ) {
						$title_a = $a['post']->post_title;
					} elseif ( isset( $a['snapshot']['name'] ) && is_string( $a['snapshot']['name'] ) ) {
						$title_a = $a['snapshot']['name'];
					}

					if ( isset( $b['post']->post_title ) && is_string( $b['post']->post_title ) && '' !== $b['post']->post_title ) {
						$title_b = $b['post']->post_title;
					} elseif ( isset( $b['snapshot']['name'] ) && is_string( $b['snapshot']['name'] ) ) {
						$title_b = $b['snapshot']['name'];
					}

					$compared = strcasecmp( $title_a, $title_b );

					if ( 0 !== $compared ) {
						return $compared;
					}

					return (int) $a['post']->ID - (int) $b['post']->ID;
				}
			);

			$eligible = array_merge( $seeded_first, $remaining );
		}

		$snapshots = array();

		foreach ( $eligible as $entry ) {
			$snapshots[] = $entry['snapshot'];
		}

		return array(
			'status'    => 'success',
			'snapshots' => $snapshots,
			'error'     => null,
		);
	}
}
