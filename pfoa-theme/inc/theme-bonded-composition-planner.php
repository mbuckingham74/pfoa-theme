<?php
/**
 * Theme-owned bonded-pair composition planner (INACTIVE).
 *
 * INACTIVE — not required by functions.php, no hooks/shortcodes/filters/actions,
 * no writes, do NOT require until cutover authorises.
 *
 * Purpose: read-only composition of ordered neutral provider snapshots
 * (schema 1.0, as produced by the compat selector) into display units:
 * single-profile individuals and two-profile bonded units. The planner never
 * queries, never renders, and never moves records; it only groups the input
 * it is given, preserving caller unit placement.
 *
 * Input contract: an ordered list of neutral schema 1.0 snapshot arrays.
 * Only these neutral facts are consulted: schema_version, post_id,
 * post_type, post_status, name (presence only), bonded.valid,
 * bonded.partner_id, bonded.pair, lifecycle.bonded_state,
 * lifecycle.eligible_for_adoptable, publication.has_required_card_image,
 * and legacy.members (grouped only when a valid nonempty member list).
 * No other snapshot content is read for bonding decisions, and no outside
 * state is consulted.
 *
 * Bonding rules:
 * - A snapshot is a grouped legacy record ONLY when legacy is an array
 *   carrying legacy.members as a valid nonempty list of member names.
 *   Source-only or label-only legacy metadata without grouped members is
 *   not grouped and stays eligible for ordinary bonded composition.
 *   Malformed legacy.members data fails the whole plan. A genuine grouped
 *   record is always one single individual unit, never bonded, and never
 *   split; its own bonded claim, if any, is not acted on, and a
 *   non-grouped candidate naming a grouped profile as partner fails as a
 *   half link (the grouped side can never reciprocate).
 * - Coherent unbonded facts are required of every non-grouped
 *   non-candidate: bonded.valid === false, bonded.partner_id === 0,
 *   bonded.pair empty, and lifecycle.bonded_state === 'unbonded'. Any
 *   invalid, half-linked, conflicting, or inconsistent bonded claim fails
 *   the whole plan instead of falling back to an individual unit; bonded
 *   ties are never repaired, cleared, or changed.
 * - Bonded candidacy requires ALL of: bonded.valid === true,
 *   lifecycle.bonded_state === 'bonded', post_status === 'publish',
 *   publication.has_required_card_image === true,
 *   lifecycle.eligible_for_adoptable === true, bonded.partner_id an int
 *   above zero distinct from self, and bonded.pair an array of exactly two
 *   distinct positive ints containing the snapshot's own post_id.
 * - Anything not a candidate is an individual unit; adjacent unbonded
 *   snapshots never merge.
 * - Pairing is strict and fails the whole plan rather than emitting a half
 *   pair: the partner must be present in the input, both sides must agree
 *   on membership, both sides must independently satisfy every candidacy
 *   gate (a bonded member that misses the published, image, or eligibility
 *   gate fails the plan instead of collapsing to an individual), and the
 *   two partners must sit next to each other in input order.
 * - Canonical discipline: both partners must carry byte-identical ordered
 *   pair arrays. Set-equal pairs in different order fail as conflicting
 *   membership so that canonical order stays provider-authored. Inside an
 *   accepted bonded unit the snapshots (and member ids) follow pair order,
 *   pair[0] first, while the unit itself keeps its input placement.
 *
 * Check order (first match wins): missing_required_fields,
 * duplicate_profile_id, missing_bonded_partner,
 * conflicting_pair_membership, half_link_or_inconsistent_metadata,
 * nonadjacent_partners_require_movement.
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

if ( defined( 'PFOA_THEME_BONDED_COMPOSITION_PLANNER_LOADED' ) ) {
	return;
}

define( 'PFOA_THEME_BONDED_COMPOSITION_PLANNER_LOADED', true );

if ( ! function_exists( 'pfoa_theme_plan_bonded_composition' ) ) {
	/**
	 * Group ordered neutral snapshots into individual/bonded display units.
	 *
	 * Pure and deterministic: no outside reads, no writes, no ordering
	 * changes apart from canonical intra-pair member order. Units follow
	 * input order by the lowest member index.
	 *
	 * @param array $snapshots Ordered neutral schema 1.0 snapshot arrays.
	 * @return array Array with 'status' ('success' or 'failure'), 'units'
	 *               (ordered unit arrays, empty on failure), 'error' (null
	 *               on success, machine code on failure), and 'reason'
	 *               (null on success, machine code on failure). Each unit
	 *               holds 'type' ('individual' or 'bonded'), 'member_ids'
	 *               (int list in canonical order), and 'snapshots' (the
	 *               original snapshot arrays in canonical order).
	 */
	function pfoa_theme_plan_bonded_composition( array $snapshots ): array {
		$failure = function ( $code ) {
			return array(
				'status' => 'failure',
				'units'  => array(),
				'error'  => $code,
				'reason' => $code,
			);
		};

	$by_id      = array();
	$is_grouped = array();

	// Grouped means a genuine grouped legacy record: legacy.members is a
	// valid nonempty list of member names. Source-only or label-only
	// legacy metadata without grouped members is not grouped.
	$classify_legacy = function ( $legacy ) {
		if ( null === $legacy ) {
			return 'single';
		}

		if ( ! is_array( $legacy ) ) {
			return 'malformed';
		}

		if ( ! array_key_exists( 'members', $legacy ) ) {
			return 'single';
		}

		if ( ! is_array( $legacy['members'] ) ) {
			return 'malformed';
		}

		if ( 0 === count( $legacy['members'] ) ) {
			return 'single';
		}

		foreach ( $legacy['members'] as $member_name ) {
			if ( ! is_string( $member_name ) || '' === trim( $member_name ) ) {
				return 'malformed';
			}
		}

		return 'grouped';
	};

		foreach ( $snapshots as $index => $snapshot ) {
			if ( ! is_array( $snapshot ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'schema_version', $snapshot ) || '1.0' !== $snapshot['schema_version'] ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'post_id', $snapshot ) || ! is_int( $snapshot['post_id'] ) || $snapshot['post_id'] <= 0 ) {
				return $failure( 'missing_required_fields' );
			}

			$post_id = $snapshot['post_id'];

			if ( isset( $by_id[ $post_id ] ) ) {
				return $failure( 'duplicate_profile_id' );
			}

			$by_id[ $post_id ] = $index;

			if ( ! array_key_exists( 'post_type', $snapshot ) || 'pfoa_cat' !== $snapshot['post_type'] ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'post_status', $snapshot ) || ! is_string( $snapshot['post_status'] ) || '' === $snapshot['post_status'] ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'name', $snapshot ) || ! is_string( $snapshot['name'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'bonded', $snapshot ) || ! is_array( $snapshot['bonded'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			$bonded = $snapshot['bonded'];

			if ( ! array_key_exists( 'valid', $bonded ) || ! is_bool( $bonded['valid'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'partner_id', $bonded ) || ! is_int( $bonded['partner_id'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'pair', $bonded ) || ! is_array( $bonded['pair'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'lifecycle', $snapshot ) || ! is_array( $snapshot['lifecycle'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			$lifecycle = $snapshot['lifecycle'];

			if ( ! array_key_exists( 'bonded_state', $lifecycle ) || ! is_string( $lifecycle['bonded_state'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'eligible_for_adoptable', $lifecycle ) || ! is_bool( $lifecycle['eligible_for_adoptable'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			if ( ! array_key_exists( 'publication', $snapshot ) || ! is_array( $snapshot['publication'] ) ) {
				return $failure( 'missing_required_fields' );
			}

			$publication = $snapshot['publication'];

			if ( ! array_key_exists( 'has_required_card_image', $publication ) || ! is_bool( $publication['has_required_card_image'] ) ) {
				return $failure( 'missing_required_fields' );
			}

		if ( ! array_key_exists( 'legacy', $snapshot ) ) {
			return $failure( 'missing_required_fields' );
		}

		$legacy_class = $classify_legacy( $snapshot['legacy'] );

		if ( 'malformed' === $legacy_class ) {
			return $failure( 'missing_required_fields' );
		}

		$is_grouped[ $index ] = 'grouped' === $legacy_class;
	}

	// Null means not a candidate; a two-int list means candidate pair.
	$candidate_pair = function ( array $snapshot ) use ( $classify_legacy ) {
		if ( 'grouped' === $classify_legacy( $snapshot['legacy'] ) ) {
			return null;
		}

			$bonded      = $snapshot['bonded'];
			$lifecycle   = $snapshot['lifecycle'];
			$publication = $snapshot['publication'];

			if ( true !== $bonded['valid'] ) {
				return null;
			}

			if ( 'bonded' !== $lifecycle['bonded_state'] ) {
				return null;
			}

			if ( 'publish' !== $snapshot['post_status'] ) {
				return null;
			}

			if ( true !== $publication['has_required_card_image'] ) {
				return null;
			}

			if ( true !== $lifecycle['eligible_for_adoptable'] ) {
				return null;
			}

			$self      = $snapshot['post_id'];
			$partner   = $bonded['partner_id'];
			$pair_list = array_values( $bonded['pair'] );

			if ( $partner <= 0 || $partner === $self ) {
				return null;
			}

			if ( 2 !== count( $pair_list ) ) {
				return null;
			}

			if ( ! is_int( $pair_list[0] ) || ! is_int( $pair_list[1] ) ) {
				return null;
			}

			if ( $pair_list[0] <= 0 || $pair_list[1] <= 0 ) {
				return null;
			}

			if ( $pair_list[0] === $pair_list[1] ) {
				return null;
			}

			if ( $pair_list[0] !== $self && $pair_list[1] !== $self ) {
				return null;
			}

			return $pair_list;
		};

		$is_candidate = array();
		$pairs        = array();

		foreach ( $snapshots as $index => $snapshot ) {
			$pair = $candidate_pair( $snapshot );

			if ( null === $pair ) {
				$is_candidate[ $index ] = false;
			} else {
				$is_candidate[ $index ] = true;
				$pairs[ $index ]        = $pair;
			}
		}

		foreach ( $snapshots as $index => $snapshot ) {
			if ( ! $is_candidate[ $index ] ) {
				continue;
			}

			$self    = $snapshot['post_id'];
			$partner = $snapshot['bonded']['partner_id'];

			if ( ! isset( $by_id[ $partner ] ) ) {
				return $failure( 'missing_bonded_partner' );
			}

			$partner_index    = $by_id[ $partner ];
			$partner_snapshot = $snapshots[ $partner_index ];

			$own_set = array( $self, $partner );
			sort( $own_set );

			$own_pair_sorted = $pairs[ $index ];
			sort( $own_pair_sorted );

			if ( $own_pair_sorted !== $own_set ) {
				return $failure( 'conflicting_pair_membership' );
			}

			if ( $is_grouped[ $partner_index ] ) {
				return $failure( 'half_link_or_inconsistent_metadata' );
			}

			$other_pair_raw = array_values( $partner_snapshot['bonded']['pair'] );

			if ( array() !== $other_pair_raw ) {
				$other_sorted = $other_pair_raw;
				sort( $other_sorted );

				if ( $other_sorted !== $own_set ) {
					return $failure( 'conflicting_pair_membership' );
				}

				if ( $other_pair_raw !== $pairs[ $index ] ) {
					return $failure( 'conflicting_pair_membership' );
				}
			}

			if ( null === $candidate_pair( $partner_snapshot ) ) {
				return $failure( 'half_link_or_inconsistent_metadata' );
			}

			if ( $partner_snapshot['bonded']['partner_id'] !== $self ) {
				return $failure( 'half_link_or_inconsistent_metadata' );
			}

			if ( 1 !== abs( $partner_index - $index ) ) {
				return $failure( 'nonadjacent_partners_require_movement' );
			}
		}

		// A profile id named inside a unit pair may only be claimed by the
		// two unit members; any third claimant conflicts.
		$claimants = array();

		foreach ( $snapshots as $index => $snapshot ) {
			if ( $is_grouped[ $index ] ) {
				continue;
			}

			foreach ( array_values( $snapshot['bonded']['pair'] ) as $claimed ) {
				if ( ! is_int( $claimed ) || $claimed <= 0 ) {
					continue;
				}

				if ( ! isset( $claimants[ $claimed ] ) ) {
					$claimants[ $claimed ] = array();
				}

				if ( ! in_array( $index, $claimants[ $claimed ], true ) ) {
					$claimants[ $claimed ][] = $index;
				}
			}
		}

		foreach ( $snapshots as $index => $snapshot ) {
			if ( ! $is_candidate[ $index ] ) {
				continue;
			}

			$self    = $snapshot['post_id'];
			$partner = $snapshot['bonded']['partner_id'];

			foreach ( array( $self, $partner ) as $member ) {
				if ( ! isset( $claimants[ $member ] ) ) {
					continue;
				}

				foreach ( $claimants[ $member ] as $claimant ) {
					if ( $claimant !== $index && $claimant !== $by_id[ $partner ] ) {
						return $failure( 'conflicting_pair_membership' );
					}
				}
			}
		}

		// Non-grouped non-candidates must carry coherent unbonded facts;
		// any other bonded trace fails rather than falling back to single.
		foreach ( $snapshots as $index => $snapshot ) {
			if ( $is_grouped[ $index ] || $is_candidate[ $index ] ) {
				continue;
			}

			$bonded    = $snapshot['bonded'];
			$lifecycle = $snapshot['lifecycle'];

			if ( false !== $bonded['valid'] || 0 !== $bonded['partner_id'] || array() !== array_values( $bonded['pair'] ) || 'unbonded' !== $lifecycle['bonded_state'] ) {
				return $failure( 'half_link_or_inconsistent_metadata' );
			}
		}

		$units    = array();
		$consumed = array();

		foreach ( $snapshots as $index => $snapshot ) {
			if ( isset( $consumed[ $index ] ) ) {
				continue;
			}

			if ( ! $is_candidate[ $index ] ) {
				$units[] = array(
					'type'       => 'individual',
					'member_ids' => array( $snapshot['post_id'] ),
					'snapshots'  => array( $snapshot ),
				);
				continue;
			}

			$pair_ordered = $pairs[ $index ];
			$first_index  = $by_id[ $pair_ordered[0] ];
			$second_index = $by_id[ $pair_ordered[1] ];

			$units[] = array(
				'type'       => 'bonded',
				'member_ids' => array( $pair_ordered[0], $pair_ordered[1] ),
				'snapshots'  => array( $snapshots[ $first_index ], $snapshots[ $second_index ] ),
			);

			$consumed[ $first_index ]  = true;
			$consumed[ $second_index ] = true;
		}

		return array(
			'status' => 'success',
			'units'  => $units,
			'error'  => null,
			'reason' => null,
		);
	}
}
