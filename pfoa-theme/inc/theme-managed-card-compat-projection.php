<?php
/**
 * Theme-owned managed-card compat projection coordinator (INACTIVE).
 *
 * INACTIVE — never load from functions.php; no hooks, no storage, no writes,
 * no network, no assets, no reads beyond the given strings.
 * Pure validate-and-assemble coordinator for listed source page 20323 only.
 * It composes the inventory scan, the ownership classifier, the placement
 * validator and the byte splice in strict order over the same raw bytes,
 * passing caller records through untouched and emitting the projected page
 * string. Manual spans, editorial bytes, membership and ordering are
 * preserved by construction; any doubt fails closed with no partial HTML.
 *
 * Activation contract: do NOT load this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_COMPAT_PROJECTION_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_COMPAT_PROJECTION_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_project_managed_cards_compat' ) ) {
	/**
	 * Project managed cards for the listed source through the compat pipeline.
	 *
	 * Strict order over the same raw bytes: inventory, then ownership
	 * classification, then placement validation, then byte splice. The source
	 * id accepts int 20323 or a trimmed digit string equal to 20323 and is
	 * normalised to int 20323 for every downstream call. Own checks stay
	 * minimal (source scope and shallow shapes); all deep checks are
	 * delegated to the composed components. Caller placement records carry
	 * identity plus byte anchors with an optional slug for legacy-proven
	 * spans; caller HTML records carry identity plus span offsets plus an
	 * html string and are passed to the splice stage unchanged once the
	 * validator approves. Nothing here mutates bytes itself.
	 *
	 * Response integrity is verified independently: a validator success is
	 * accepted only when its contract, counts, identity, anchors, ordering,
	 * kind and slug fields all agree with the classification, the caller
	 * records and the stored bytes; a splice success is accepted only when
	 * its contract, counts and exact byte-level replacement plan all agree
	 * with the stored bytes, the classified spans and the caller HTML
	 * records. Each downstream call is guarded against Throwable so no
	 * exception exposes partial HTML.
	 *
	 * @param int|string $source_page_id Listed source page id (20323 only).
	 * @param mixed      $raw_content      Raw stored content string.
	 * @param mixed      $trusted_proofs   Trusted proof record list.
	 * @param mixed      $placement_records Caller placement record list.
	 * @param mixed      $replacements     Caller HTML record list.
	 * @return array Success array with ok, source_page_id, projected_content,
	 *               replaced_count, owned_count and manual_count; or failure
	 *               array with ok false, error code/message/stage and
	 *               projected_content null. Stages: request, inventory,
	 *               classify, validate, splice.
	 */
	function pfoa_theme_project_managed_cards_compat( $source_page_id, $raw_content, $trusted_proofs, $placement_records, $replacements ): array {
		$fail = function ( $code, $message, $stage ) {
			return array(
				'ok'                => false,
				'error'             => array(
					'code'    => (string) $code,
					'message' => (string) $message,
					'stage'   => (string) $stage,
				),
				'projected_content' => null,
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
			return $fail( 'unsupported_source', 'Source page is not the listed managed-card source.', 'request' );
		}

		if ( ! is_string( $raw_content ) ) {
			return $fail( 'invalid_content', 'Raw content must be a string.', 'request' );
		}
		if ( ! is_array( $trusted_proofs ) ) {
			return $fail( 'invalid_proofs', 'Trusted proofs must be a list.', 'request' );
		}
		if ( ! is_array( $placement_records ) ) {
			return $fail( 'invalid_placements', 'Placement records must be a list.', 'request' );
		}
		if ( ! is_array( $replacements ) ) {
			return $fail( 'invalid_replacements', 'Replacement records must be a list.', 'request' );
		}

		$content     = $raw_content;
		$content_len = strlen( $content );

		$id_pattern   = '/\A[1-9][0-9]*\z/';
		$slot_pattern = '/\A[A-Za-z0-9_-]{1,64}\z/';
		$slug_pattern = '/\A[a-z0-9-]+\z/';

		$by_start = function ( $a, $b ) {
			if ( $a['start'] === $b['start'] ) {
				if ( $a['end'] === $b['end'] ) {
					return 0;
				}
				return $a['end'] < $b['end'] ? -1 : 1;
			}
			return $a['start'] < $b['start'] ? -1 : 1;
		};

		if ( ! function_exists( 'pfoa_theme_inventory_managed_cards' ) ) {
			return $fail( 'missing_dependency', 'Inventory component is not loaded.', 'inventory' );
		}
		try {
			$inventory = pfoa_theme_inventory_managed_cards( 20323, $content );
		} catch ( Throwable $e ) {
			return $fail( 'inventory_exception', 'Managed-card inventory threw an exception.', 'inventory' );
		}
		if ( ! is_array( $inventory ) || ! array_key_exists( 'ok', $inventory ) || true !== $inventory['ok'] ) {
			$code    = 'inventory_failed';
			$message = 'Managed-card inventory failed.';
			if ( is_array( $inventory ) && isset( $inventory['error'] ) && is_array( $inventory['error'] ) ) {
				if ( isset( $inventory['error']['code'] ) && is_string( $inventory['error']['code'] ) && '' !== $inventory['error']['code'] ) {
					$code = $inventory['error']['code'];
				}
				if ( isset( $inventory['error']['message'] ) && is_string( $inventory['error']['message'] ) && '' !== $inventory['error']['message'] ) {
					$message = $inventory['error']['message'];
				}
			}
			return $fail( $code, $message, 'inventory' );
		}
		if ( ! array_key_exists( 'source_page_id', $inventory ) || 20323 !== $inventory['source_page_id'] ) {
			return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
		}
		if ( ! array_key_exists( 'container', $inventory ) || ! is_array( $inventory['container'] ) ) {
			return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
		}
		if ( ! array_key_exists( 'cards', $inventory ) || ! is_array( $inventory['cards'] ) ) {
			return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
		}
		if ( ! array_key_exists( 'count', $inventory ) || ! is_int( $inventory['count'] ) || $inventory['count'] < 0 ) {
			return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
		}
		if ( count( $inventory['cards'] ) !== $inventory['count'] ) {
			return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
		}
		{
			$pos = 0;
			foreach ( $inventory['cards'] as $ck => $cv ) {
				if ( $ck !== $pos || ! is_array( $cv ) ) {
					return $fail( 'inventory_failed', 'Managed-card inventory returned a malformed response.', 'inventory' );
				}
				$pos++;
			}
		}

		if ( ! function_exists( 'pfoa_theme_classify_managed_card_ownership' ) ) {
			return $fail( 'missing_dependency', 'Ownership component is not loaded.', 'classify' );
		}
		try {
			$classification = pfoa_theme_classify_managed_card_ownership( 20323, $inventory, $trusted_proofs, $content );
		} catch ( Throwable $e ) {
			return $fail( 'classification_exception', 'Managed-card ownership classification threw an exception.', 'classify' );
		}
		if ( ! is_array( $classification ) || ! array_key_exists( 'ok', $classification ) || true !== $classification['ok'] ) {
			$code    = 'classification_failed';
			$message = 'Managed-card ownership classification failed.';
			if ( is_array( $classification ) && isset( $classification['error'] ) && is_array( $classification['error'] ) ) {
				if ( isset( $classification['error']['code'] ) && is_string( $classification['error']['code'] ) && '' !== $classification['error']['code'] ) {
					$code = $classification['error']['code'];
				}
				if ( isset( $classification['error']['message'] ) && is_string( $classification['error']['message'] ) && '' !== $classification['error']['message'] ) {
					$message = $classification['error']['message'];
				}
			}
			return $fail( $code, $message, 'classify' );
		}
		foreach ( array( 'source_page_id', 'owned', 'manual', 'count_owned', 'count_manual' ) as $rk ) {
			if ( ! array_key_exists( $rk, $classification ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
		}
		if ( 20323 !== $classification['source_page_id'] ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		if ( ! is_array( $classification['owned'] ) || ! is_array( $classification['manual'] ) ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		if ( ! is_int( $classification['count_owned'] ) || $classification['count_owned'] < 0 ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		if ( ! is_int( $classification['count_manual'] ) || $classification['count_manual'] < 0 ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		if ( count( $classification['owned'] ) !== $classification['count_owned'] ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		if ( count( $classification['manual'] ) !== $classification['count_manual'] ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}
		{
			$pos = 0;
			foreach ( $classification['owned'] as $ck => $cv ) {
				if ( $ck !== $pos || ! is_array( $cv ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$pos++;
			}
			$pos = 0;
			foreach ( $classification['manual'] as $ck => $cv ) {
				if ( $ck !== $pos || ! is_array( $cv ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$pos++;
			}
		}
		$owned_list  = $classification['owned'];
		$manual_list = $classification['manual'];
		foreach ( $owned_list as $oe ) {
			if ( ! array_key_exists( 'start', $oe ) || ! array_key_exists( 'end', $oe ) || ! array_key_exists( 'slice', $oe ) || ! array_key_exists( 'profile_id', $oe ) || ! array_key_exists( 'slot', $oe ) || ! array_key_exists( 'kind', $oe ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( ! is_int( $oe['start'] ) || ! is_int( $oe['end'] ) || ! is_string( $oe['slice'] ) || ! is_string( $oe['profile_id'] ) || ! is_string( $oe['slot'] ) || ! is_string( $oe['kind'] ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( 1 !== preg_match( $id_pattern, $oe['profile_id'] ) || 1 !== preg_match( $slot_pattern, $oe['slot'] ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( 'id-owned' !== $oe['kind'] && 'legacy-proven' !== $oe['kind'] ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( $oe['start'] < 0 || $oe['end'] <= $oe['start'] || $oe['end'] > $content_len ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( substr( $content, $oe['start'], $oe['end'] - $oe['start'] ) !== $oe['slice'] ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
		}
		foreach ( $manual_list as $me ) {
			if ( ! array_key_exists( 'start', $me ) || ! array_key_exists( 'end', $me ) || ! array_key_exists( 'slice', $me ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( ! is_int( $me['start'] ) || ! is_int( $me['end'] ) || ! is_string( $me['slice'] ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( $me['start'] < 0 || $me['end'] <= $me['start'] || $me['end'] > $content_len ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( substr( $content, $me['start'], $me['end'] - $me['start'] ) !== $me['slice'] ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
		}
		{
			$ranked_owned = $owned_list;
			usort( $ranked_owned, $by_start );
			$prev_end = 0;
			$first    = true;
			foreach ( $ranked_owned as $sp ) {
				if ( ! $first && $sp['start'] < $prev_end ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$first    = false;
				$prev_end = $sp['end'];
			}
			$ranked_manual = $manual_list;
			usort( $ranked_manual, $by_start );
			$prev_end = 0;
			$first    = true;
			foreach ( $ranked_manual as $sp ) {
				if ( ! $first && $sp['start'] < $prev_end ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$first    = false;
				$prev_end = $sp['end'];
			}
			foreach ( $owned_list as $os ) {
				foreach ( $manual_list as $ms ) {
					if ( $os['start'] < $ms['end'] && $ms['start'] < $os['end'] ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
				}
			}
		}
		try {
			if ( $classification['count_owned'] + $classification['count_manual'] !== $inventory['count'] ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			if ( count( $owned_list ) + count( $manual_list ) !== count( $inventory['cards'] ) ) {
				return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
			}
			$inv_by_span = array();
			foreach ( $inventory['cards'] as $ic ) {
				if ( ! is_array( $ic ) || ! array_key_exists( 'start', $ic ) || ! array_key_exists( 'end', $ic ) || ! array_key_exists( 'slice', $ic ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( ! is_int( $ic['start'] ) || ! is_int( $ic['end'] ) || ! is_string( $ic['slice'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( $ic['start'] < 0 || $ic['end'] <= $ic['start'] || $ic['end'] > $content_len ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( substr( $content, $ic['start'], $ic['end'] - $ic['start'] ) !== $ic['slice'] ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$inv_key = (string) $ic['start'] . "\0" . (string) $ic['end'];
				if ( array_key_exists( $inv_key, $inv_by_span ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$inv_by_span[ $inv_key ] = $ic;
			}
			$claimed             = array();
			$seen_classified     = array();
			$all_classified      = array_merge( $owned_list, $manual_list );
			foreach ( $all_classified as $ce ) {
				$ckey = (string) $ce['start'] . "\0" . (string) $ce['end'];
				if ( array_key_exists( $ckey, $seen_classified ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$seen_classified[ $ckey ] = true;
				if ( ! array_key_exists( $ckey, $inv_by_span ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( $ce['slice'] !== $inv_by_span[ $ckey ]['slice'] ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( array_key_exists( $ckey, $claimed ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$claimed[ $ckey ] = true;
			}
			foreach ( $inv_by_span as $inv_key => $inv_entry ) {
				if ( ! array_key_exists( $inv_key, $claimed ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
			}
			{
				$pos = 0;
				foreach ( $trusted_proofs as $pk => $pv ) {
					if ( $pk !== $pos || ! is_array( $pv ) ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
					$pos++;
				}
			}
			foreach ( $trusted_proofs as $pv ) {
				if ( ! array_key_exists( 'start', $pv ) || ! is_int( $pv['start'] ) || ! array_key_exists( 'end', $pv ) || ! is_int( $pv['end'] ) || $pv['end'] <= $pv['start'] ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( ! array_key_exists( 'profile_id', $pv ) || ! is_string( $pv['profile_id'] ) || 1 !== preg_match( $id_pattern, $pv['profile_id'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( ! array_key_exists( 'slot', $pv ) || ! is_string( $pv['slot'] ) || 1 !== preg_match( $slot_pattern, $pv['slot'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( ! array_key_exists( 'slice', $pv ) || ! is_string( $pv['slice'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( ! array_key_exists( 'fingerprint', $pv ) || ! is_string( $pv['fingerprint'] ) || 1 !== preg_match( '/\A[0-9a-f]{32}\z/', $pv['fingerprint'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( array_key_exists( 'slug', $pv ) && ( ! is_string( $pv['slug'] ) || 1 !== preg_match( $slug_pattern, $pv['slug'] ) ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
			}
			foreach ( $manual_list as $ms ) {
				foreach ( $trusted_proofs as $pv ) {
					if ( $pv['start'] < $ms['end'] && $ms['start'] < $pv['end'] ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
				}
			}
			$cls_allowed_tokens = array( 'pfoa-cat-card', 'pfoa-cat-card-media', 'pfoa-cat-card-title', 'pfoa-cat-card-thumb', 'pfoa-cat-card-status', 'pfoa-cat-card-note', 'pfoa-cat-card-incomplete', 'pfoa-cat-pair-left', 'pfoa-cat-pair-right' );
			$cls_id_pattern     = '/(?<![A-Za-z0-9_-])data-pfoa-profile-id\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>\/]+)/i';
			$cls_class_pattern  = '/class\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>\/]+)/i';
			$cls_anchor_pattern = '/<a\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
			$cls_dialog_ok      = function_exists( 'pfoa_theme_read_dialog_attribute' );
			foreach ( $manual_list as $ms ) {
				$mslice    = $ms['slice'];
				$mopen_tag = null;
				if ( 1 === preg_match( '/<article\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $mslice, $mopen_match, PREG_OFFSET_CAPTURE ) ) {
					$mopen_tag = (string) $mopen_match[0][0];
				}
				if ( null === $mopen_tag ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( 1 !== preg_match( '/<\/article\s*>\s*\z/i', $mslice ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( false !== stripos( $mslice, 'data-fake-profile-id' ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( 1 === preg_match( '/(?<![A-Za-z0-9_-])data-pfoa-profile-id-/i', $mslice ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$mopen_valid = array();
				if ( preg_match_all( $cls_id_pattern, $mopen_tag, $mopen_matches, PREG_SET_ORDER ) ) {
					foreach ( $mopen_matches as $one ) {
						$mtoken = (string) $one[1];
						$mfirst = strlen( $mtoken ) > 0 ? $mtoken[0] : '';
						if ( '"' !== $mfirst && '\'' !== $mfirst ) {
							return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
						}
						$minner = substr( $mtoken, 1, strlen( $mtoken ) - 2 );
						if ( 1 !== preg_match( $id_pattern, $minner ) ) {
							return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
						}
						$mopen_valid[] = $minner;
					}
				}
				if ( count( $mopen_valid ) > 1 ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$mslice_valid_count = 0;
				if ( preg_match_all( $cls_id_pattern, $mslice, $mslice_matches, PREG_SET_ORDER ) ) {
					foreach ( $mslice_matches as $one ) {
						$mtoken = (string) $one[1];
						$mfirst = strlen( $mtoken ) > 0 ? $mtoken[0] : '';
						if ( ( '"' === $mfirst || '\'' === $mfirst ) && 1 === preg_match( $id_pattern, substr( $mtoken, 1, strlen( $mtoken ) - 2 ) ) ) {
							$mslice_valid_count++;
						} else {
							return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
						}
					}
				}
				if ( $mslice_valid_count > 1 ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( 0 === count( $mopen_valid ) && false !== stripos( $mslice, 'profile-id' ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( 0 !== count( $mopen_valid ) || 0 !== $mslice_valid_count ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( preg_match_all( $cls_class_pattern, $mslice, $mclass_matches, PREG_SET_ORDER ) ) {
					foreach ( $mclass_matches as $one ) {
						$mtoken = (string) $one[1];
						$mfirst = strlen( $mtoken ) > 0 ? $mtoken[0] : '';
						$minner = ( '"' === $mfirst || '\'' === $mfirst ) ? substr( $mtoken, 1, strlen( $mtoken ) - 2 ) : $mtoken;
						$mparts = preg_split( '/\s+/', $minner );
						if ( ! is_array( $mparts ) ) {
							continue;
						}
						foreach ( $mparts as $part ) {
							$part = (string) $part;
							if ( '' === $part ) {
								continue;
							}
							if ( false !== strpos( $part, 'pfoa-cat-card' ) && ! in_array( $part, $cls_allowed_tokens, true ) ) {
								return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
							}
						}
					}
				}
				$mhas_token = false;
				if ( 1 === preg_match( $cls_class_pattern, $mopen_tag, $mart_class ) ) {
					$mtoken = (string) $mart_class[1];
					$mfirst = strlen( $mtoken ) > 0 ? $mtoken[0] : '';
					$minner = ( '"' === $mfirst || '\'' === $mfirst ) ? substr( $mtoken, 1, strlen( $mtoken ) - 2 ) : $mtoken;
					$mparts = preg_split( '/\s+/', $minner );
					$mclean = array();
					if ( is_array( $mparts ) ) {
						foreach ( $mparts as $part ) {
							if ( '' !== (string) $part ) {
								$mclean[] = (string) $part;
							}
						}
						if ( in_array( 'pfoa-cat-card', $mclean, true ) ) {
							$mhas_token = true;
						}
					}
				}
				if ( $mhas_token ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$manchor_count = preg_match_all( $cls_anchor_pattern, $mslice, $manchor_matches, PREG_OFFSET_CAPTURE );
				if ( false === $manchor_count ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$mdialog_all = array();
				$matags      = isset( $manchor_matches[0] ) && is_array( $manchor_matches[0] ) ? $manchor_matches[0] : array();
				foreach ( $matags as $amatch ) {
					$atag = (string) $amatch[0];
					if ( $cls_dialog_ok ) {
						$mread = pfoa_theme_read_dialog_attribute( $atag );
					} else {
						$mread = array( 'status' => 'malformed', 'slug' => null, 'values' => array( '' ) );
					}
					if ( ! is_array( $mread ) || ! array_key_exists( 'status', $mread ) ) {
						$mdialog_all[] = '';
						continue;
					}
					if ( 'none' === $mread['status'] ) {
						continue;
					}
					if ( 'single' === $mread['status'] && isset( $mread['slug'] ) && is_string( $mread['slug'] ) ) {
						$mdialog_all[] = $mread['slug'];
						continue;
					}
					$mevidence = isset( $mread['values'] ) && is_array( $mread['values'] ) ? $mread['values'] : array( '' );
					if ( 0 === count( $mevidence ) ) {
						$mevidence = array( '' );
					}
					foreach ( $mevidence as $one ) {
						$mdialog_all[] = is_string( $one ) ? $one : '';
					}
				}
				if ( 0 !== count( $mdialog_all ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
			}
			$cls_seen_identity = array();
			foreach ( $owned_list as $oe ) {
				$proof_matches = array();
				foreach ( $trusted_proofs as $pv ) {
					if ( $pv['start'] === $oe['start'] && $pv['end'] === $oe['end'] ) {
						$proof_matches[] = $pv;
					}
				}
				if ( 1 !== count( $proof_matches ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$oproof = $proof_matches[0];
				if ( $oproof['slice'] !== $oe['slice'] ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( md5( $oe['slice'] ) !== strtolower( (string) $oproof['fingerprint'] ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				if ( $oproof['profile_id'] !== $oe['profile_id'] || $oproof['slot'] !== $oe['slot'] ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$identity_key = $oe['profile_id'] . "\0" . $oe['slot'];
				if ( array_key_exists( $identity_key, $cls_seen_identity ) ) {
					return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
				}
				$cls_seen_identity[ $identity_key ] = true;
				if ( 'legacy-proven' === $oe['kind'] ) {
					$olslice = $oe['slice'];
					$olcount = preg_match_all( $cls_anchor_pattern, $olslice, $olmatches, PREG_OFFSET_CAPTURE );
					if ( false === $olcount || ! isset( $olmatches[0] ) || ! is_array( $olmatches[0] ) ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
					$olfound = array();
					$olok    = true;
					foreach ( $olmatches[0] as $olmatch ) {
						$oltag = (string) $olmatch[0];
						if ( $cls_dialog_ok ) {
							$olread = pfoa_theme_read_dialog_attribute( $oltag );
						} else {
							$olok = false;
							break;
						}
						if ( ! is_array( $olread ) || ! array_key_exists( 'status', $olread ) ) {
							$olok = false;
							break;
						}
						if ( 'none' === $olread['status'] ) {
							continue;
						}
						if ( 'single' === $olread['status'] && isset( $olread['slug'] ) && is_string( $olread['slug'] ) ) {
							$olfound[] = $olread['slug'];
							continue;
						}
						$olok = false;
						break;
					}
					if ( ! $olok || count( $olfound ) < 2 || 1 !== count( array_unique( $olfound ) ) || 1 !== preg_match( $slug_pattern, $olfound[0] ) ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
					if ( ! array_key_exists( 'slug', $oproof ) || ! is_string( $oproof['slug'] ) || $oproof['slug'] !== $olfound[0] ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
				} else {
					$oopen_tag = null;
					if ( 1 === preg_match( '/<article\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $oe['slice'], $oopen_match, PREG_OFFSET_CAPTURE ) ) {
						$oopen_tag = (string) $oopen_match[0][0];
					}
					if ( null === $oopen_tag ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
					$ostored    = array();
					$omalformed = false;
					if ( preg_match_all( $cls_id_pattern, $oopen_tag, $oopen_matches, PREG_SET_ORDER ) ) {
						foreach ( $oopen_matches as $one ) {
							$otoken = (string) $one[1];
							$ofirst = strlen( $otoken ) > 0 ? $otoken[0] : '';
							if ( ( '"' !== $ofirst && '\'' !== $ofirst ) || 1 !== preg_match( $id_pattern, substr( $otoken, 1, strlen( $otoken ) - 2 ) ) ) {
								$omalformed = true;
								break;
							}
							$ostored[] = substr( $otoken, 1, strlen( $otoken ) - 2 );
						}
					}
					if ( $omalformed || 1 !== count( $ostored ) ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
					if ( $ostored[0] !== $oproof['profile_id'] || $ostored[0] !== $oe['profile_id'] ) {
						return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
					}
				}
			}
		} catch ( Throwable $e ) {
			return $fail( 'classification_failed', 'Managed-card classification returned a malformed response.', 'classify' );
		}

		if ( ! function_exists( 'pfoa_theme_validate_managed_card_placement' ) ) {
			return $fail( 'missing_dependency', 'Placement component is not loaded.', 'validate' );
		}
		try {
			$validation = pfoa_theme_validate_managed_card_placement( 20323, $classification, $placement_records, $content );
		} catch ( Throwable $e ) {
			return $fail( 'validation_exception', 'Managed-card placement validation threw an exception.', 'validate' );
		}
		if ( ! is_array( $validation ) || ! array_key_exists( 'ok', $validation ) || true !== $validation['ok'] ) {
			$code    = 'validation_failed';
			$message = 'Managed-card placement validation failed.';
			if ( is_array( $validation ) && isset( $validation['error'] ) && is_array( $validation['error'] ) ) {
				if ( isset( $validation['error']['code'] ) && is_string( $validation['error']['code'] ) && '' !== $validation['error']['code'] ) {
					$code = $validation['error']['code'];
				}
				if ( isset( $validation['error']['message'] ) && is_string( $validation['error']['message'] ) && '' !== $validation['error']['message'] ) {
					$message = $validation['error']['message'];
				}
			}
			return $fail( $code, $message, 'validate' );
		}
		try {
		if ( array_keys( $validation ) !== array( 'ok', 'source_page_id', 'placements', 'count', 'manual_count' ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( 20323 !== $validation['source_page_id'] ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		$accepted = $validation['placements'];
		if ( ! is_array( $accepted ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		{
			$pos = 0;
			foreach ( $accepted as $ak => $av ) {
				if ( $ak !== $pos ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$pos++;
			}
		}
		if ( ! is_int( $validation['count'] ) || $validation['count'] < 0 ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( ! is_int( $validation['manual_count'] ) || $validation['manual_count'] < 0 ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( $validation['count'] !== count( $accepted ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( $validation['count'] !== count( $owned_list ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( $validation['count'] !== $classification['count_owned'] ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( $validation['manual_count'] !== count( $manual_list ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		if ( $validation['manual_count'] !== $classification['count_manual'] ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		{
			$pos = 0;
			foreach ( $placement_records as $pk => $pv ) {
				if ( $pk !== $pos ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$pos++;
			}
		}
		if ( $validation['count'] !== count( $placement_records ) ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}
		$owned_by_full     = array();
		$owned_by_identity = array();
		foreach ( $owned_list as $oi => $oe ) {
			$fkey = $oe['profile_id'] . "\0" . $oe['slot'] . "\0" . $oe['start'] . "\0" . $oe['end'];
			if ( array_key_exists( $fkey, $owned_by_full ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			$owned_by_full[ $fkey ] = $oi;
			$ikey                   = $oe['profile_id'] . "\0" . $oe['slot'];
			if ( array_key_exists( $ikey, $owned_by_identity ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			$owned_by_identity[ $ikey ] = $oi;
		}
		$dialog_ok = function_exists( 'pfoa_theme_read_dialog_attribute' );
		foreach ( $accepted as $ai => $acc ) {
			if ( ! is_array( $acc ) || array_keys( $acc ) !== array( 'profile_id', 'slot', 'start', 'end', 'slug', 'order', 'kind' ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( ! is_string( $acc['profile_id'] ) || 1 !== preg_match( $id_pattern, $acc['profile_id'] ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( ! is_string( $acc['slot'] ) || 1 !== preg_match( $slot_pattern, $acc['slot'] ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( ! is_int( $acc['start'] ) || ! is_int( $acc['end'] ) || $acc['end'] <= $acc['start'] ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( ! is_int( $acc['order'] ) || $acc['order'] !== $ai ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( 'id-owned' !== $acc['kind'] && 'legacy-proven' !== $acc['kind'] ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( 'id-owned' === $acc['kind'] ) {
				if ( null !== $acc['slug'] ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
			} elseif ( ! is_string( $acc['slug'] ) || 1 !== preg_match( $slug_pattern, $acc['slug'] ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( ! array_key_exists( $ai, $placement_records ) || ! is_array( $placement_records[ $ai ] ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			$req = $placement_records[ $ai ];
			if ( ! array_key_exists( 'profile_id', $req ) || ! array_key_exists( 'slot', $req ) || ! array_key_exists( 'start', $req ) || ! array_key_exists( 'end', $req ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( $acc['profile_id'] !== $req['profile_id'] || $acc['slot'] !== $req['slot'] || $acc['start'] !== $req['start'] || $acc['end'] !== $req['end'] ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			$fkey = $acc['profile_id'] . "\0" . $acc['slot'] . "\0" . $acc['start'] . "\0" . $acc['end'];
			if ( ! array_key_exists( $fkey, $owned_by_full ) ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			$owned_span = $owned_list[ $owned_by_full[ $fkey ] ];
			if ( $acc['kind'] !== $owned_span['kind'] ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( $acc['start'] < 0 || $acc['end'] > $content_len ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( substr( $content, $acc['start'], $acc['end'] - $acc['start'] ) !== $owned_span['slice'] ) {
				return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
			}
			if ( 'legacy-proven' === $acc['kind'] ) {
				if ( ! $dialog_ok ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$slice        = $owned_span['slice'];
				$anchor_count = preg_match_all( '/<a\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $slice, $am );
				if ( false === $anchor_count || ! isset( $am[0] ) || ! is_array( $am[0] ) ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$found       = array();
				$found_valid = true;
				foreach ( $am[0] as $atag ) {
					$read = pfoa_theme_read_dialog_attribute( (string) $atag );
					if ( ! is_array( $read ) || ! array_key_exists( 'status', $read ) ) {
						$found_valid = false;
						break;
					}
					if ( 'none' === $read['status'] ) {
						continue;
					}
					if ( 'single' === $read['status'] && isset( $read['slug'] ) && is_string( $read['slug'] ) ) {
						$found[] = $read['slug'];
						continue;
					}
					$found_valid = false;
					break;
				}
				if ( ! $found_valid || count( $found ) < 2 || 1 !== count( array_unique( $found ) ) || 1 !== preg_match( $slug_pattern, $found[0] ) ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$agreed = $found[0];
				if ( $acc['slug'] !== $agreed ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				if ( ! array_key_exists( 'slug', $req ) || ! is_string( $req['slug'] ) || $req['slug'] !== $agreed ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
			} else {
				if ( array_key_exists( 'slug', $req ) && is_string( $req['slug'] ) && '' !== $req['slug'] ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
			}
		}
		{
			$prev = null;
			foreach ( $accepted as $acc ) {
				if ( null !== $prev && $acc['start'] <= $prev ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$prev = $acc['start'];
			}
			$ranked = $owned_list;
			usort( $ranked, $by_start );
			foreach ( $accepted as $ai => $acc ) {
				if ( ! isset( $ranked[ $ai ] ) ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
				$want = $ranked[ $ai ];
				if ( $acc['profile_id'] !== $want['profile_id'] || $acc['slot'] !== $want['slot'] || $acc['start'] !== $want['start'] || $acc['end'] !== $want['end'] ) {
					return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
				}
			}
		}
		} catch ( Throwable $e ) {
			return $fail( 'validation_failed', 'Managed-card placement validation returned a malformed response.', 'validate' );
		}

		if ( ! function_exists( 'pfoa_theme_splice_managed_cards' ) ) {
			return $fail( 'missing_dependency', 'Splice component is not loaded.', 'splice' );
		}
		try {
			$spliced = pfoa_theme_splice_managed_cards( 20323, $content, $classification, $replacements );
		} catch ( Throwable $e ) {
			return $fail( 'splice_exception', 'Managed-card splice threw an exception.', 'splice' );
		}
		if ( ! is_array( $spliced ) || ! array_key_exists( 'success', $spliced ) || true !== $spliced['success'] ) {
			$reason = 'splice_failed';
			if ( is_array( $spliced ) && array_key_exists( 'failure_reason', $spliced ) && is_string( $spliced['failure_reason'] ) && '' !== $spliced['failure_reason'] ) {
				$reason = $spliced['failure_reason'];
			}
			return $fail( $reason, 'Managed-card splice failed: ' . $reason . '.', 'splice' );
		}
		if ( array_keys( $spliced ) !== array( 'success', 'projected_content', 'failure_reason', 'replaced_count' ) ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( null !== $spliced['failure_reason'] ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( ! is_string( $spliced['projected_content'] ) ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( ! is_int( $spliced['replaced_count'] ) || $spliced['replaced_count'] < 0 ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( $spliced['replaced_count'] !== count( $owned_list ) ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( $spliced['replaced_count'] !== $classification['count_owned'] ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		{
			$pos = 0;
			foreach ( $replacements as $rk => $rv ) {
				if ( $rk !== $pos || ! is_array( $rv ) ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$pos++;
			}
		}
		$owned_by_key = array();
		foreach ( $owned_list as $oi => $oe ) {
			$k = $oe['profile_id'] . "\0" . $oe['slot'] . "\0" . $oe['start'] . "\0" . $oe['end'];
			if ( array_key_exists( $k, $owned_by_key ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			$owned_by_key[ $k ] = $oi;
		}
		$seen_keys     = array();
		$html_by_owned = array();
		foreach ( $replacements as $rec ) {
			if ( ! array_key_exists( 'profile_id', $rec ) || ! array_key_exists( 'slot', $rec ) || ! array_key_exists( 'start', $rec ) || ! array_key_exists( 'end', $rec ) || ! array_key_exists( 'html', $rec ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			if ( ! is_string( $rec['profile_id'] ) || 1 !== preg_match( $id_pattern, $rec['profile_id'] ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			if ( ! is_string( $rec['slot'] ) || 1 !== preg_match( $slot_pattern, $rec['slot'] ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			if ( ! is_int( $rec['start'] ) || ! is_int( $rec['end'] ) || $rec['end'] <= $rec['start'] ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			if ( ! is_string( $rec['html'] ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			$rk = $rec['profile_id'] . "\0" . $rec['slot'] . "\0" . $rec['start'] . "\0" . $rec['end'];
			if ( array_key_exists( $rk, $seen_keys ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			$seen_keys[ $rk ] = true;
			if ( ! array_key_exists( $rk, $owned_by_key ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			foreach ( $manual_list as $ms ) {
				if ( $rec['start'] < $ms['end'] && $ms['start'] < $rec['end'] ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
			}
			$html_by_owned[ $owned_by_key[ $rk ] ] = $rec['html'];
		}
		if ( count( $html_by_owned ) !== count( $owned_list ) ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		{
			$ranked_owned = $owned_list;
			usort( $ranked_owned, $by_start );
			$prev_end = 0;
			foreach ( $ranked_owned as $sp ) {
				if ( $sp['start'] < $prev_end ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$prev_end = $sp['end'];
			}
			$ranked_manual = $manual_list;
			usort( $ranked_manual, $by_start );
			$prev_end = 0;
			foreach ( $ranked_manual as $sp ) {
				if ( $sp['start'] < $prev_end ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$prev_end = $sp['end'];
			}
			foreach ( $owned_list as $os ) {
				foreach ( $manual_list as $ms ) {
					if ( $os['start'] < $ms['end'] && $ms['start'] < $os['end'] ) {
						return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
					}
				}
			}
		}
		$expected = null;
		{
			$ranked = array();
			foreach ( $owned_list as $oi => $oe ) {
				if ( ! array_key_exists( $oi, $html_by_owned ) ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$ranked[] = array(
					'start' => $oe['start'],
					'end'   => $oe['end'],
					'html'  => $html_by_owned[ $oi ],
				);
			}
			usort( $ranked, $by_start );
			$built  = '';
			$cursor = 0;
			foreach ( $ranked as $sp ) {
				if ( $sp['start'] < $cursor || $sp['end'] <= $sp['start'] || $sp['end'] > $content_len ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$gap = $sp['start'] - $cursor;
				if ( $gap < 0 ) {
					return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
				}
				$built .= substr( $content, $cursor, $gap ) . $sp['html'];
				$cursor = $sp['end'];
			}
			$tail = substr( $content, $cursor );
			if ( ! is_string( $tail ) ) {
				return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
			}
			$built   .= $tail;
			$expected = $built;
		}
		if ( $expected !== $spliced['projected_content'] ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}

		$owned_count  = 0;
		$manual_count = 0;
		if ( isset( $classification['count_owned'] ) && is_int( $classification['count_owned'] ) ) {
			$owned_count = $classification['count_owned'];
		} elseif ( isset( $classification['owned'] ) && is_array( $classification['owned'] ) ) {
			$owned_count = count( $classification['owned'] );
		}
		if ( isset( $classification['count_manual'] ) && is_int( $classification['count_manual'] ) ) {
			$manual_count = $classification['count_manual'];
		} elseif ( isset( $classification['manual'] ) && is_array( $classification['manual'] ) ) {
			$manual_count = count( $classification['manual'] );
		}

		return array(
			'ok'                => true,
			'source_page_id'    => 20323,
			'projected_content' => $spliced['projected_content'],
			'replaced_count'    => $spliced['replaced_count'],
			'owned_count'       => $owned_count,
			'manual_count'      => $manual_count,
		);
	}
}
