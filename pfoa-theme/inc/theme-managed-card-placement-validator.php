<?php
/**
 * Theme-owned managed-card placement validator (INACTIVE).
 *
 * INACTIVE — do NOT require from functions.php, no hooks, no writes. Pure
 * validator reconciling caller placement records against successful
 * classifier output for source page 20323 only. No Cat Profile facts, no
 * copied fields, no HTML.
 *
 * Read-only reconciliation of caller-supplied placement records (identity +
 * byte anchors plus an optional dialog slug for legacy-proven spans) against
 * the exact successful output of pfoa_theme_classify_managed_card_ownership.
 * Every owned and manual span is re-checked against the given content bytes;
 * caller records carry anchors only, never slices, titles or markup. Any
 * doubt fails the whole call with a machine-readable error code and no
 * partial acceptance. Pure string/bytes only: byte-safe length, position and
 * slice helpers plus read-only pattern matching. No hooks, no filters, no
 * shortcodes, no platform access, no storage reads, no writes, no file
 * operations, no title/image/adjacency inference.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * Strict typing (documented): only the source page id accepts int or digit
 * string equivalence (20323). Everywhere else types are exact. Owned span
 * profile ids and slots are strings compared with ===, and span offsets are
 * ints compared with ===. An int 101 never equals a string '101'.
 *
 * Caller placement records hold string profile_id (digit pattern), string
 * slot (slot pattern), int start, int end (end > start, inside content) and
 * an optional slug (absent, null or '' means none; when present it must be a
 * string matching the slug pattern). Records carrying copied-field or HTML
 * keys (html, slice, title, name, image, caption, status, link, markup,
 * content) are rejected.
 *
 * Failure codes: unsupported_source, invalid_content,
 * invalid_classification, invalid_placements, malformed_placement,
 * duplicate_claim, missing_placement, stale_anchor, conflicting_claims,
 * orphan_placement, unproven_legacy, ordering_mismatch,
 * ambiguous_ownership.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_PLACEMENT_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_PLACEMENT_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_validate_managed_card_placement' ) ) {
	/**
	 * Validate caller placement records against classified owned spans.
	 *
	 * Pure reconciliation over the given content with byte-safe length,
	 * position and slice helpers plus read-only pattern checks. No platform
	 * access, no hooks, no storage reads, no writes, no file operations.
	 * The classification must be the exact successful output of
	 * pfoa_theme_classify_managed_card_ownership; caller records must each
	 * match one owned span on profile id, slot, start and end with ===.
	 * Legacy-proven spans additionally require the caller slug to equal the
	 * dialog slug re-derived from the owned slice bytes; id-owned spans must
	 * not carry a slug. Placements must arrive in stored (start-sorted) order
	 * and must cover every owned span exactly once while leaving every
	 * manual span untouched.
	 *
	 * @param int|string $source_page_id Listed source page id (20323 only).
	 * @param mixed      $classification Successful ownership result array.
	 * @param mixed      $placements     Caller placement record list.
	 * @param mixed      $post_content   Raw stored content string.
	 * @return array Success array with ok, source_page_id, placements, count
	 *               and manual_count; or failure array with ok false and
	 *               error code/message only (no partial acceptance).
	 */
	function pfoa_theme_validate_managed_card_placement( $source_page_id, $classification, $placements, $post_content ): array {
		$fail = function ( $code, $message ) {
			return array(
				'ok'    => false,
				'error' => array(
					'code'    => (string) $code,
					'message' => (string) $message,
				),
			);
		};

		$allowed_source = 20323;
		$source_id      = null;
		if ( is_int( $source_page_id ) ) {
			$source_id = $source_page_id;
		} elseif ( is_string( $source_page_id ) ) {
			$trimmed = trim( $source_page_id );
			if ( '' !== $trimmed && ctype_digit( $trimmed ) ) {
				$source_id = (int) $trimmed;
			}
		}
		if ( $allowed_source !== $source_id ) {
			return $fail( 'unsupported_source', 'Source page is not the listed managed-card source.' );
		}

		if ( ! is_string( $post_content ) ) {
			return $fail( 'invalid_content', 'Post content must be a string.' );
		}
		$content     = $post_content;
		$content_len = strlen( $content );

		if ( ! is_array( $classification ) ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}
		$required_keys = array( 'ok', 'source_page_id', 'owned', 'manual', 'count_owned', 'count_manual' );
		foreach ( $required_keys as $required_key ) {
			if ( ! array_key_exists( $required_key, $classification ) ) {
				return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
			}
		}
		if ( true !== $classification['ok'] ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}
		if ( 20323 !== $classification['source_page_id'] ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}
		if ( ! is_array( $classification['owned'] ) || ! is_array( $classification['manual'] ) ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}
		$owned_input  = $classification['owned'];
		$manual_input = $classification['manual'];
		if ( ! is_int( $classification['count_owned'] ) || count( $owned_input ) !== $classification['count_owned'] ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}
		if ( ! is_int( $classification['count_manual'] ) || count( $manual_input ) !== $classification['count_manual'] ) {
			return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
		}

		$position = 0;
		foreach ( $owned_input as $list_key => $list_entry ) {
			if ( $list_key !== $position || ! is_array( $list_entry ) ) {
				return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
			}
			$position++;
		}
		$position = 0;
		foreach ( $manual_input as $list_key => $list_entry ) {
			if ( $list_key !== $position || ! is_array( $list_entry ) ) {
				return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
			}
			$position++;
		}

		$id_pattern   = '/\A[1-9][0-9]*\z/';
		$slot_pattern = '/\A[A-Za-z0-9_-]{1,64}\z/';
		$slug_pattern = '/\A[a-z0-9-]+\z/';

		$owned = array();
		foreach ( $owned_input as $entry ) {
			if ( ! array_key_exists( 'start', $entry )
				|| ! array_key_exists( 'end', $entry )
				|| ! array_key_exists( 'slice', $entry )
				|| ! array_key_exists( 'profile_id', $entry )
				|| ! array_key_exists( 'slot', $entry )
				|| ! array_key_exists( 'kind', $entry )
				|| ! is_int( $entry['start'] )
				|| ! is_int( $entry['end'] )
				|| ! is_string( $entry['slice'] )
				|| ! is_string( $entry['profile_id'] )
				|| ! is_string( $entry['slot'] )
				|| ! is_string( $entry['kind'] )
				|| 1 !== preg_match( $id_pattern, $entry['profile_id'] )
				|| 1 !== preg_match( $slot_pattern, $entry['slot'] )
				|| ( 'id-owned' !== $entry['kind'] && 'legacy-proven' !== $entry['kind'] )
			) {
				return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
			}
			$start = $entry['start'];
			$end   = $entry['end'];
			if ( $start < 0 || $end <= $start || $end > $content_len ) {
				return $fail( 'stale_anchor', 'A classified span no longer lines up with the stored content.' );
			}
			if ( substr( $content, $start, $end - $start ) !== $entry['slice'] ) {
				return $fail( 'stale_anchor', 'A classified span no longer lines up with the stored content.' );
			}
			$owned[] = array(
				'start'      => $start,
				'end'        => $end,
				'slice'      => $entry['slice'],
				'profile_id' => $entry['profile_id'],
				'slot'       => $entry['slot'],
				'kind'       => $entry['kind'],
			);
		}

		$manual = array();
		foreach ( $manual_input as $entry ) {
			if ( ! array_key_exists( 'start', $entry )
				|| ! array_key_exists( 'end', $entry )
				|| ! array_key_exists( 'slice', $entry )
				|| ! is_int( $entry['start'] )
				|| ! is_int( $entry['end'] )
				|| ! is_string( $entry['slice'] )
			) {
				return $fail( 'invalid_classification', 'Classification is not a successful ownership result.' );
			}
			$start = $entry['start'];
			$end   = $entry['end'];
			if ( $start < 0 || $end <= $start || $end > $content_len ) {
				return $fail( 'stale_anchor', 'A classified span no longer lines up with the stored content.' );
			}
			if ( substr( $content, $start, $end - $start ) !== $entry['slice'] ) {
				return $fail( 'stale_anchor', 'A classified span no longer lines up with the stored content.' );
			}
			$manual[] = array(
				'start' => $start,
				'end'   => $end,
				'slice' => $entry['slice'],
			);
		}

		$by_start = function ( $a, $b ) {
			if ( $a['start'] === $b['start'] ) {
				if ( $a['end'] === $b['end'] ) {
					return 0;
				}
				return $a['end'] < $b['end'] ? -1 : 1;
			}
			return $a['start'] < $b['start'] ? -1 : 1;
		};

		$ranked_owned = $owned;
		usort( $ranked_owned, $by_start );
		$prev_end = 0;
		$first    = true;
		foreach ( $ranked_owned as $span ) {
			if ( ! $first && $span['start'] < $prev_end ) {
				return $fail( 'ambiguous_ownership', 'Classified owned spans overlap.' );
			}
			$first    = false;
			$prev_end = $span['end'];
		}

		foreach ( $owned as $owned_span ) {
			foreach ( $manual as $manual_span ) {
				if ( $owned_span['start'] < $manual_span['end'] && $manual_span['start'] < $owned_span['end'] ) {
					return $fail( 'ambiguous_ownership', 'A classified owned span overlaps a manual span.' );
				}
			}
		}

		if ( ! is_array( $placements ) ) {
			return $fail( 'invalid_placements', 'Placements must be a list of placement records.' );
		}
		$position = 0;
		foreach ( $placements as $list_key => $list_entry ) {
			if ( $list_key !== $position ) {
				return $fail( 'invalid_placements', 'Placements must be a list of placement records.' );
			}
			$position++;
		}

		$allowed_record_keys = array( 'profile_id', 'slot', 'start', 'end', 'slug' );
		foreach ( $placements as $record ) {
			if ( ! is_array( $record ) ) {
				return $fail( 'malformed_placement', 'A placement record is malformed.' );
			}
			foreach ( array_keys( $record ) as $record_key ) {
				if ( ! in_array( $record_key, $allowed_record_keys, true ) ) {
					return $fail( 'malformed_placement', 'A placement record carries an unsupported field.' );
				}
			}
			if ( ! array_key_exists( 'profile_id', $record )
				|| ! array_key_exists( 'slot', $record )
				|| ! array_key_exists( 'start', $record )
				|| ! array_key_exists( 'end', $record )
				|| ! is_string( $record['profile_id'] )
				|| ! is_string( $record['slot'] )
				|| ! is_int( $record['start'] )
				|| ! is_int( $record['end'] )
				|| 1 !== preg_match( $id_pattern, $record['profile_id'] )
				|| 1 !== preg_match( $slot_pattern, $record['slot'] )
				|| $record['end'] <= $record['start']
			) {
				return $fail( 'malformed_placement', 'A placement record is malformed.' );
			}
			if ( $record['start'] < 0 || $record['end'] > $content_len ) {
				return $fail( 'malformed_placement', 'A placement record holds bad offsets.' );
			}
			if ( array_key_exists( 'slug', $record ) ) {
				$slug_value = $record['slug'];
				if ( null !== $slug_value && '' !== $slug_value ) {
					if ( ! is_string( $slug_value ) || 1 !== preg_match( $slug_pattern, $slug_value ) ) {
						return $fail( 'malformed_placement', 'A placement record holds a bad slug.' );
					}
				}
			}
		}

		$seen_identity = array();
		$seen_anchor   = array();
		foreach ( $placements as $record ) {
			$identity_key = $record['profile_id'] . "\0" . $record['slot'];
			if ( isset( $seen_identity[ $identity_key ] ) ) {
				return $fail( 'duplicate_claim', 'One profile claims one slot twice.' );
			}
			$seen_identity[ $identity_key ] = true;
			$anchor_key                      = $record['start'] . ':' . $record['end'];
			if ( isset( $seen_anchor[ $anchor_key ] ) ) {
				return $fail( 'duplicate_claim', 'One byte span is claimed twice.' );
			}
			$seen_anchor[ $anchor_key ] = true;
		}

		$owned_by_full     = array();
		$owned_by_anchor   = array();
		$owned_by_identity = array();
		foreach ( $owned as $index => $owned_span ) {
			$owned_by_full[ $owned_span['profile_id'] . "\0" . $owned_span['slot'] . "\0" . $owned_span['start'] . "\0" . $owned_span['end'] ] = $index;
			$owned_by_anchor[ $owned_span['start'] . ':' . $owned_span['end'] ]                                                             = $index;
			$owned_by_identity[ $owned_span['profile_id'] . "\0" . $owned_span['slot'] ]                                                   = $index;
		}

		$owner_of_placement = array();
		foreach ( $placements as $placement_index => $record ) {
			$full_key = $record['profile_id'] . "\0" . $record['slot'] . "\0" . $record['start'] . "\0" . $record['end'];
			if ( array_key_exists( $full_key, $owned_by_full ) ) {
				$owner_of_placement[ $placement_index ] = $owned_by_full[ $full_key ];
				continue;
			}
			$anchor_key = $record['start'] . ':' . $record['end'];
			if ( array_key_exists( $anchor_key, $owned_by_anchor ) ) {
				return $fail( 'conflicting_claims', 'A placement anchor matches an owned span with a different identity.' );
			}
			$identity_key = $record['profile_id'] . "\0" . $record['slot'];
			if ( array_key_exists( $identity_key, $owned_by_identity ) ) {
				return $fail( 'stale_anchor', 'A placement identity matches an owned span on a different anchor.' );
			}
			$touches_manual = false;
			foreach ( $manual as $manual_span ) {
				if ( $record['start'] < $manual_span['end'] && $manual_span['start'] < $record['end'] ) {
					$touches_manual = true;
					break;
				}
			}
			if ( $touches_manual ) {
				return $fail( 'conflicting_claims', 'A placement touches a manual span.' );
			}
			foreach ( $owned as $owned_span ) {
				if ( $record['start'] < $owned_span['end'] && $owned_span['start'] < $record['end'] ) {
					return $fail( 'conflicting_claims', 'A placement overlaps an owned span it does not match.' );
				}
			}
			return $fail( 'orphan_placement', 'A placement matches no owned span.' );
		}

		if ( count( $placements ) < count( $owned ) ) {
			return $fail( 'missing_placement', 'An owned span has no placement.' );
		}
		if ( count( $placements ) > count( $owned ) ) {
			return $fail( 'orphan_placement', 'A placement matches no owned span.' );
		}

		$agreed_slug = function ( $slice ) use ( $slug_pattern ) {
			$found = array();
			$anchor_count = preg_match_all( '/<a\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $slice, $anchor_matches );
			if ( false === $anchor_count || ! isset( $anchor_matches[0] ) || ! is_array( $anchor_matches[0] ) ) {
				return null;
			}
			foreach ( $anchor_matches[0] as $anchor_tag ) {
				if ( 1 === preg_match( '/data-pfoa-cat-dialog\s*=\s*("[^"]*"|\'[^\']*\')/i', (string) $anchor_tag, $dialog_match ) ) {
					$token = (string) $dialog_match[1];
					$inner = substr( $token, 1, strlen( $token ) - 2 );
					if ( '' !== $inner ) {
						$found[] = $inner;
					}
				}
			}
			if ( count( $found ) < 2 ) {
				return null;
			}
			if ( 1 !== count( array_unique( $found ) ) ) {
				return null;
			}
			if ( 1 !== preg_match( $slug_pattern, $found[0] ) ) {
				return null;
			}
			return $found[0];
		};

		foreach ( $placements as $placement_index => $record ) {
			$owned_span = $owned[ $owner_of_placement[ $placement_index ] ];
			$has_slug   = array_key_exists( 'slug', $record ) && is_string( $record['slug'] ) && '' !== $record['slug'];
			if ( 'legacy-proven' === $owned_span['kind'] ) {
				$agreed = $agreed_slug( $owned_span['slice'] );
				$wanted = $has_slug ? $record['slug'] : null;
				if ( null === $wanted || $wanted !== $agreed ) {
					return $fail( 'unproven_legacy', 'A legacy placement lacks its proven dialog slug.' );
				}
			} elseif ( $has_slug ) {
				return $fail( 'conflicting_claims', 'An id-owned placement must not carry a dialog slug.' );
			}
		}

		$ranked = $owned;
		usort( $ranked, $by_start );
		$prev_start = null;
		foreach ( $placements as $record ) {
			if ( null !== $prev_start && $record['start'] <= $prev_start ) {
				return $fail( 'ordering_mismatch', 'Placements are not in stored order.' );
			}
			$prev_start = $record['start'];
		}
		foreach ( $placements as $order => $record ) {
			$want = $ranked[ $order ]['profile_id'] . "\0" . $ranked[ $order ]['slot'];
			$got  = $record['profile_id'] . "\0" . $record['slot'];
			if ( $got !== $want ) {
				return $fail( 'ordering_mismatch', 'Placements are not in stored order.' );
			}
		}

		$placement_of_owned = array();
		foreach ( $owner_of_placement as $placement_index => $owned_index ) {
			$placement_of_owned[ $owned_index ] = $placement_index;
		}
		$accepted = array();
		foreach ( $ranked as $order => $ranked_span ) {
			$owned_index = $owned_by_identity[ $ranked_span['profile_id'] . "\0" . $ranked_span['slot'] ];
			$record      = $placements[ $placement_of_owned[ $owned_index ] ];
			$accepted[]  = array(
				'profile_id' => $record['profile_id'],
				'slot'       => $record['slot'],
				'start'      => $record['start'],
				'end'        => $record['end'],
				'order'      => (int) $order,
				'kind'       => $ranked_span['kind'],
			);
		}

		return array(
			'ok'             => true,
			'source_page_id' => 20323,
			'placements'     => $accepted,
			'count'          => count( $accepted ),
			'manual_count'   => count( $manual ),
		);
	}
}
