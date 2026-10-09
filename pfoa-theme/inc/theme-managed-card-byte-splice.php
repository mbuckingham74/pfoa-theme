<?php
/**
 * Theme-owned managed-card in-memory byte splice (INACTIVE).
 *
 * INACTIVE — do NOT require from functions.php, no hooks, no writes, no assets.
 * Pure in-memory span switch for one listed source page. It takes already
 * classified owned spans plus caller-supplied HTML strings and emits the
 * complete projected page string, leaving every byte outside approved owned
 * spans exactly as stored. No platform access, no hooks, no storage reads,
 * no writes, no file operations.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * Strict typing (documented): only the source page id accepts int or digit
 * string equivalence (20323). Everywhere else types are exact. Owned span
 * profile ids and slots are strings compared with ===, and span offsets are
 * ints compared with ===. An int 101 never equals a string '101', and a
 * string '10' never equals an int 10. Caller HTML is taken verbatim from
 * each record's 'html' key; an explicitly supplied empty string clears its
 * span, while a missing or non-string 'html' value fails the whole call.
 * Nothing is built from stored slices; stored bytes are only checked.
 *
 * Failure codes (failure_reason; projected_content is null on failure):
 * unsupported_source, invalid_content, invalid_classification,
 * malformed_span, out_of_range, stale_span, overlapping_spans,
 * manual_overlap, invalid_replacement, duplicate_replacement,
 * missing_replacement, unrecognized_replacement.
 *
 * Classification contract: only the exact successful output of
 * pfoa_theme_classify_managed_card_ownership is accepted. Callers must
 * take that trusted classifier output as-is and must not fabricate it.
 * Required keys with strict types: 'ok' === true, 'source_page_id' ===
 * 20323 int, 'owned' list, 'manual' list, 'count_owned' int ===
 * count(owned), 'count_manual' int === count(manual). Each owned entry
 * carries int start, int end, string slice, digit string profile id,
 * string slot and string kind; kind is only 'id-owned' or
 * 'legacy-proven'. Any deviation fails with invalid_classification and
 * null output before any HTML assembly.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_BYTE_SPLICE_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_BYTE_SPLICE_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_splice_managed_cards' ) ) {
	/**
	 * Splice caller HTML into classified owned spans in memory.
	 *
	 * Pure string assembly over the given content with byte-safe length and
	 * slice helpers plus read-only pattern checks for id and slot shapes. No
	 * platform access, no hooks, no storage reads, no writes, no file
	 * operations. Every owned span is checked against the original bytes
	 * before anything is assembled, and the whole call fails without partial
	 * output on any doubt.
	 *
	 * Only source page 20323 (int or digit string) is accepted. The
	 * classification must be the exact trusted classifier output described
	 * above: ok, source_page_id, owned, manual, count_owned and count_manual
	 * with matching counts. Each owned span holds int start, int end, string
	 * slice, string profile id, string slot and string kind
	 * ('id-owned' or 'legacy-proven'), and each manual span holds int
	 * start, int end and string slice. Caller HTML records each hold string
	 * profile id, string slot, int start, int end and string html, matched
	 * on all four of profile id, slot, start and end with ===.
	 *
	 * @param int|string $source_page_id   Listed source page id (20323 only).
	 * @param mixed      $original_content Raw stored content string.
	 * @param mixed      $classification   Ownership result with owned and manual span lists.
	 * @param mixed      $replacements     Caller HTML records, one per owned span.
	 * @return array Success array with success true, projected_content string,
	 *               failure_reason null and replaced_count int; or failure array
	 *               with success false, projected_content null, failure_reason
	 *               code and replaced_count 0.
	 */
	function pfoa_theme_splice_managed_cards( $source_page_id, $original_content, $classification, $replacements ): array {
		$fail = function ( $code ) {
			return array(
				'success'           => false,
				'projected_content' => null,
				'failure_reason'    => (string) $code,
				'replaced_count'    => 0,
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
			return $fail( 'unsupported_source' );
		}

		if ( ! is_string( $original_content ) ) {
			return $fail( 'invalid_content' );
		}
		$content     = $original_content;
		$content_len = strlen( $content );

		if ( ! is_array( $classification ) ) {
			return $fail( 'invalid_classification' );
		}
		$required_keys = array( 'ok', 'source_page_id', 'owned', 'manual', 'count_owned', 'count_manual' );
		foreach ( $required_keys as $required_key ) {
			if ( ! array_key_exists( $required_key, $classification ) ) {
				return $fail( 'invalid_classification' );
			}
		}
		if ( true !== $classification['ok'] ) {
			return $fail( 'invalid_classification' );
		}
		if ( 20323 !== $classification['source_page_id'] ) {
			return $fail( 'invalid_classification' );
		}
		if ( ! is_array( $classification['owned'] ) || ! is_array( $classification['manual'] ) ) {
			return $fail( 'invalid_classification' );
		}
		$owned_input  = $classification['owned'];
		$manual_input = $classification['manual'];
		if ( ! is_int( $classification['count_owned'] ) || count( $owned_input ) !== $classification['count_owned'] ) {
			return $fail( 'invalid_classification' );
		}
		if ( ! is_int( $classification['count_manual'] ) || count( $manual_input ) !== $classification['count_manual'] ) {
			return $fail( 'invalid_classification' );
		}

		$slot_pattern = '/\A[A-Za-z0-9_-]{1,64}\z/';
		$id_pattern   = '/\A[1-9][0-9]*\z/';

		$position = 0;
		foreach ( $owned_input as $list_key => $list_entry ) {
			if ( $list_key !== $position || ! is_array( $list_entry ) ) {
				return $fail( 'invalid_classification' );
			}
			$position++;
		}
		$position = 0;
		foreach ( $manual_input as $list_key => $list_entry ) {
			if ( $list_key !== $position || ! is_array( $list_entry ) ) {
				return $fail( 'invalid_classification' );
			}
			$position++;
		}

		$owned = array();
		foreach ( $owned_input as $entry ) {
			if ( ! array_key_exists( 'kind', $entry )
				|| ! is_string( $entry['kind'] )
				|| ( 'id-owned' !== $entry['kind'] && 'legacy-proven' !== $entry['kind'] )
			) {
				return $fail( 'invalid_classification' );
			}
			if ( ! array_key_exists( 'start', $entry )
				|| ! array_key_exists( 'end', $entry )
				|| ! array_key_exists( 'slice', $entry )
				|| ! array_key_exists( 'profile_id', $entry )
				|| ! array_key_exists( 'slot', $entry )
				|| ! is_int( $entry['start'] )
				|| ! is_int( $entry['end'] )
				|| ! is_string( $entry['slice'] )
				|| ! is_string( $entry['profile_id'] )
				|| ! is_string( $entry['slot'] )
				|| 1 !== preg_match( $id_pattern, $entry['profile_id'] )
				|| 1 !== preg_match( $slot_pattern, $entry['slot'] )
			) {
				return $fail( 'malformed_span' );
			}
			$start = $entry['start'];
			$end   = $entry['end'];
			if ( $start < 0 || $end <= $start || $end > $content_len ) {
				return $fail( 'out_of_range' );
			}
			if ( substr( $content, $start, $end - $start ) !== $entry['slice'] ) {
				return $fail( 'stale_span' );
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
				return $fail( 'malformed_span' );
			}
			$start = $entry['start'];
			$end   = $entry['end'];
			if ( $start < 0 || $end <= $start || $end > $content_len ) {
				return $fail( 'out_of_range' );
			}
			if ( substr( $content, $start, $end - $start ) !== $entry['slice'] ) {
				return $fail( 'stale_span' );
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
		foreach ( $ranked_owned as $span ) {
			if ( $span['start'] < $prev_end ) {
				return $fail( 'overlapping_spans' );
			}
			$prev_end = $span['end'];
		}

		$ranked_manual = $manual;
		usort( $ranked_manual, $by_start );
		$prev_end = 0;
		foreach ( $ranked_manual as $span ) {
			if ( $span['start'] < $prev_end ) {
				return $fail( 'overlapping_spans' );
			}
			$prev_end = $span['end'];
		}

		foreach ( $owned as $owned_span ) {
			foreach ( $manual as $manual_span ) {
				if ( $owned_span['start'] < $manual_span['end'] && $manual_span['start'] < $owned_span['end'] ) {
					return $fail( 'manual_overlap' );
				}
			}
		}

		if ( ! is_array( $replacements ) ) {
			return $fail( 'invalid_replacement' );
		}
		$position = 0;
		foreach ( $replacements as $list_key => $list_entry ) {
			if ( $list_key !== $position || ! is_array( $list_entry ) ) {
				return $fail( 'invalid_replacement' );
			}
			$position++;
		}

		$owned_by_key = array();
		foreach ( $owned as $index => $owned_span ) {
			$span_key = $owned_span['profile_id'] . "\0" . $owned_span['slot'] . "\0" . $owned_span['start'] . "\0" . $owned_span['end'];
			if ( array_key_exists( $span_key, $owned_by_key ) ) {
				return $fail( 'overlapping_spans' );
			}
			$owned_by_key[ $span_key ] = $index;
		}

		$seen_keys     = array();
		$html_by_owned = array();
		foreach ( $replacements as $record ) {
			if ( ! array_key_exists( 'profile_id', $record )
				|| ! array_key_exists( 'slot', $record )
				|| ! array_key_exists( 'start', $record )
				|| ! array_key_exists( 'end', $record )
				|| ! array_key_exists( 'html', $record )
				|| ! is_string( $record['profile_id'] )
				|| ! is_string( $record['slot'] )
				|| ! is_int( $record['start'] )
				|| ! is_int( $record['end'] )
				|| ! is_string( $record['html'] )
				|| 1 !== preg_match( $id_pattern, $record['profile_id'] )
				|| 1 !== preg_match( $slot_pattern, $record['slot'] )
				|| $record['end'] <= $record['start']
			) {
				return $fail( 'invalid_replacement' );
			}
			$record_key = $record['profile_id'] . "\0" . $record['slot'] . "\0" . $record['start'] . "\0" . $record['end'];
			if ( array_key_exists( $record_key, $seen_keys ) ) {
				return $fail( 'duplicate_replacement' );
			}
			$seen_keys[ $record_key ] = true;
			if ( ! array_key_exists( $record_key, $owned_by_key ) ) {
				return $fail( 'unrecognized_replacement' );
			}
			foreach ( $manual as $manual_span ) {
				if ( $record['start'] < $manual_span['end'] && $manual_span['start'] < $record['end'] ) {
					return $fail( 'manual_overlap' );
				}
			}
			$html_by_owned[ $owned_by_key[ $record_key ] ] = $record['html'];
		}
		if ( count( $html_by_owned ) !== count( $owned ) ) {
			return $fail( 'missing_replacement' );
		}

		$ranked = array();
		foreach ( $owned as $index => $owned_span ) {
			$ranked[] = array(
				'start' => $owned_span['start'],
				'end'   => $owned_span['end'],
				'html'  => $html_by_owned[ $index ],
			);
		}
		usort( $ranked, $by_start );

		$projected = '';
		$cursor    = 0;
		foreach ( $ranked as $span ) {
			$projected .= substr( $content, $cursor, $span['start'] - $cursor ) . $span['html'];
			$cursor     = $span['end'];
		}
		$projected .= substr( $content, $cursor );

		return array(
			'success'           => true,
			'projected_content' => $projected,
			'failure_reason'    => null,
			'replaced_count'    => count( $owned ),
		);
	}
}
