<?php
/**
 * Theme-owned managed-card ownership classifier (INACTIVE).
 *
 * INACTIVE — do NOT require from functions.php, no hooks, no writes, no assets.
 * Read-only classifier that sorts inventoried stored card spans into owned
 * spans (backed by trusted out-of-band proofs) and untouched manual spans.
 * Every marker, class token and dialog signal is re-derived from the exact
 * stored slice bytes; inventory labels are never treated as proof. Any doubt
 * fails the whole source with a machine-readable error code. Pure string
 * scan only: byte-safe length, position and slice helpers plus read-only
 * pattern matching with byte offsets. No platform access, no hooks, no
 * storage reads, no writes, no file operations.
 *
 * Activation contract: do NOT require this file from functions.php and do NOT
 * wire any hook until the cutover spec explicitly authorises it.
 *
 * @package PFOA
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_OWNERSHIP_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_OWNERSHIP_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_classify_managed_card_ownership' ) ) {
	/**
	 * Classify inventoried card spans into owned and manual spans.
	 *
	 * Pure string scan over the given content with byte-safe length,
	 * position and slice helpers plus read-only pattern matching. No
	 * platform access, no hooks, no storage reads, no writes, no file
	 * operations. Inventory labels (kind, profile id, dialog slugs,
	 * classes) are candidate signals only and are never treated as proof;
	 * everything is re-derived from the verbatim slice bytes.
	 *
	 * Only source page 20323 (int or digit string) is accepted. Each
	 * inventory card span is checked against the content bytes, then each
	 * slice is checked for exact markers, exact class tokens and the
	 * recognized generated structure:
	 *
	 * - ID-owned complete: exactly one valid quoted numeric marker on
	 *   the article open tag, article carries the card token, and both the
	 *   media anchor and the title heading anchor carry valid dialog slugs
	 *   that agree.
	 * - ID-owned incomplete: exactly one valid quoted numeric marker on
	 *   the article open tag, article carries the card and incomplete
	 *   tokens, a media division holds a thumbnail image, the title
	 *   heading is plain (no anchor), a note paragraph is present, an
	 *   optional status paragraph is allowed on single cards only, and no
	 *   dialog anchor is present anywhere.
	 * - Proven legacy: no valid marker, article carries the card token, and
	 *   both anchors carry valid dialog slugs that agree on one slug, plus
	 *   a trusted proof carrying that same slug.
	 * - Manual: no valid marker, no dialog slugs at all, and the article
	 *   does not carry the card token.
	 *
	 * Owned spans additionally require exactly one trusted proof on the
	 * same span with a matching slice and fingerprint. Manual spans must
	 * carry no proof. Any doubt fails the whole call.
	 *
	 * @param int|string $source_page_id   Listed source page id (20323 only).
	 * @param mixed      $inventory_result Successful inventory result array.
	 * @param mixed      $trusted_proofs   List of trusted proof records.
	 * @param mixed      $post_content     Raw stored content string.
	 * @return array Success array with ok, source_page_id, owned, manual,
	 *               count_owned and count_manual; or failure array with ok
	 *               false and error code/message. Codes: unsupported_source,
	 *               invalid_content, invalid_inventory, invalid_proofs,
	 *               malformed_marker, spoofed_marker, ambiguous_card,
	 *               missing_proof, stale_proof, conflicting_claims,
	 *               duplicate_slot, unsupported_structure, invalid_offsets,
	 *               slice_mismatch, fingerprint_mismatch, orphan_proof.
	 */
	function pfoa_theme_classify_managed_card_ownership( $source_page_id, $inventory_result, $trusted_proofs, $post_content ): array {
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

		if ( ! is_array( $inventory_result )
			|| ! array_key_exists( 'ok', $inventory_result )
			|| true !== $inventory_result['ok']
			|| ! array_key_exists( 'source_page_id', $inventory_result )
			|| 20323 !== $inventory_result['source_page_id']
			|| ! array_key_exists( 'container', $inventory_result )
			|| ! is_array( $inventory_result['container'] )
			|| ! array_key_exists( 'cards', $inventory_result )
			|| ! is_array( $inventory_result['cards'] )
			|| ! array_key_exists( 'count', $inventory_result )
			|| ! is_int( $inventory_result['count'] )
		) {
			return $fail( 'invalid_inventory', 'Inventory result is not a successful managed-card inventory.' );
		}
		$container = $inventory_result['container'];
		if ( ! array_key_exists( 'start', $container )
			|| ! is_int( $container['start'] )
			|| ! array_key_exists( 'end', $container )
			|| ! is_int( $container['end'] )
		) {
			return $fail( 'invalid_inventory', 'Inventory result is not a successful managed-card inventory.' );
		}
		$cards      = $inventory_result['cards'];
		$card_count = count( $cards );
		if ( $card_count !== $inventory_result['count'] ) {
			return $fail( 'invalid_inventory', 'Inventory result is not a successful managed-card inventory.' );
		}
		$list_position = 0;
		foreach ( $cards as $list_key => $list_card ) {
			if ( $list_key !== $list_position ) {
				return $fail( 'invalid_inventory', 'Inventory result is not a successful managed-card inventory.' );
			}
			$list_position++;
			if ( ! is_array( $list_card )
				|| ! array_key_exists( 'index', $list_card )
				|| ! is_int( $list_card['index'] )
				|| ! array_key_exists( 'start', $list_card )
				|| ! is_int( $list_card['start'] )
				|| ! array_key_exists( 'end', $list_card )
				|| ! is_int( $list_card['end'] )
				|| ! array_key_exists( 'slice', $list_card )
				|| ! is_string( $list_card['slice'] )
			) {
				return $fail( 'invalid_inventory', 'Inventory result is not a successful managed-card inventory.' );
			}
		}

		if ( ! is_array( $trusted_proofs ) ) {
			return $fail( 'invalid_proofs', 'Trusted proofs must be a list of proof records.' );
		}
		$proof_position = 0;
		foreach ( $trusted_proofs as $proof_key => $proof ) {
			if ( $proof_key !== $proof_position ) {
				return $fail( 'invalid_proofs', 'Trusted proofs must be a list of proof records.' );
			}
			$proof_position++;
			if ( ! is_array( $proof )
				|| ! array_key_exists( 'start', $proof )
				|| ! is_int( $proof['start'] )
				|| ! array_key_exists( 'end', $proof )
				|| ! is_int( $proof['end'] )
				|| $proof['end'] <= $proof['start']
				|| ! array_key_exists( 'profile_id', $proof )
				|| ! is_string( $proof['profile_id'] )
				|| 1 !== preg_match( '/\A[1-9][0-9]*\z/', $proof['profile_id'] )
				|| ! array_key_exists( 'slot', $proof )
				|| ! is_string( $proof['slot'] )
				|| 1 !== preg_match( '/\A[A-Za-z0-9_-]{1,64}\z/', $proof['slot'] )
				|| ! array_key_exists( 'slice', $proof )
				|| ! is_string( $proof['slice'] )
				|| ! array_key_exists( 'fingerprint', $proof )
				|| ! is_string( $proof['fingerprint'] )
				|| 1 !== preg_match( '/\A[0-9a-f]{32}\z/', $proof['fingerprint'] )
			) {
				return $fail( 'invalid_proofs', 'Trusted proofs must be a list of proof records.' );
			}
			if ( array_key_exists( 'slug', $proof )
				&& ( ! is_string( $proof['slug'] )
					|| 1 !== preg_match( '/\A[a-z0-9-]+\z/', $proof['slug'] ) )
			) {
				return $fail( 'invalid_proofs', 'Trusted proofs must be a list of proof records.' );
			}
		}

		$allowed_tokens = array( 'pfoa-cat-card', 'pfoa-cat-card-media', 'pfoa-cat-card-title', 'pfoa-cat-card-thumb', 'pfoa-cat-card-status', 'pfoa-cat-card-note', 'pfoa-cat-card-incomplete', 'pfoa-cat-pair-left', 'pfoa-cat-pair-right' );
		$id_pattern     = '/(?<![A-Za-z0-9_-])data-pfoa-profile-id\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>\/]+)/i';
		$class_pattern  = '/class\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>\/]+)/i';
		$anchor_pattern = '/<a\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$dialog_pattern = '/(?<![A-Za-z0-9_-])data-pfoa-cat-dialog\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>\/]+)/i';
		$h3_pattern     = '/<h3\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$div_pattern    = '/<div\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$p_pattern      = '/<p\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$img_pattern    = '/<img\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$slug_pattern   = '/\A[a-z0-9-]+\z/';

		$derived = array();
		foreach ( $cards as $card ) {
			$start = $card['start'];
			$end   = $card['end'];
			$slice = $card['slice'];
			if ( $end <= $start || $start < 0 || $end > $content_len ) {
				return $fail( 'invalid_offsets', 'Card byte span is outside the stored content.' );
			}
			if ( $slice !== substr( $content, $start, $end - $start ) ) {
				return $fail( 'slice_mismatch', 'Card slice does not match its byte span.' );
			}

			$open_tag = null;
			if ( 1 === preg_match( '/<article\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $slice, $open_match, PREG_OFFSET_CAPTURE ) ) {
				$open_tag = (string) $open_match[0][0];
			}
			if ( null === $open_tag ) {
				return $fail( 'unsupported_structure', 'Card is not a readable article element.' );
			}
			if ( 1 !== preg_match( '/<\/article\s*>\s*\z/i', $slice ) ) {
				return $fail( 'unsupported_structure', 'Card is not a readable article element.' );
			}

			if ( false !== stripos( $slice, 'data-fake-profile-id' ) ) {
				return $fail( 'spoofed_marker', 'Card holds a spoofed profile marker.' );
			}
			if ( 1 === preg_match( '/(?<![A-Za-z0-9_-])data-pfoa-profile-id-/i', $slice ) ) {
				return $fail( 'spoofed_marker', 'Card holds a spoofed profile marker.' );
			}

			$open_valid = array();
			if ( preg_match_all( $id_pattern, $open_tag, $open_id_matches, PREG_SET_ORDER ) ) {
				foreach ( $open_id_matches as $one ) {
					$token = (string) $one[1];
					$first = strlen( $token ) > 0 ? $token[0] : '';
					if ( '"' !== $first && '\'' !== $first ) {
						return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
					}
					$inner = substr( $token, 1, strlen( $token ) - 2 );
					if ( 1 !== preg_match( '/\A[1-9][0-9]*\z/', $inner ) ) {
						return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
					}
					$open_valid[] = $inner;
				}
			}
			if ( count( $open_valid ) > 1 ) {
				return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
			}
			$slice_valid_count = 0;
			if ( preg_match_all( $id_pattern, $slice, $slice_id_matches, PREG_SET_ORDER ) ) {
				foreach ( $slice_id_matches as $one ) {
					$token = (string) $one[1];
					$first = strlen( $token ) > 0 ? $token[0] : '';
					if ( ( '"' === $first || '\'' === $first )
						&& 1 === preg_match( '/\A[1-9][0-9]*\z/', substr( $token, 1, strlen( $token ) - 2 ) )
					) {
						$slice_valid_count++;
					} else {
						return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
					}
				}
			}
			if ( $slice_valid_count > 1 ) {
				return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
			}
			if ( 0 === count( $open_valid ) && false !== stripos( $slice, 'profile-id' ) ) {
				return $fail( 'malformed_marker', 'Card holds a malformed profile marker.' );
			}

			if ( preg_match_all( $class_pattern, $slice, $class_matches, PREG_SET_ORDER ) ) {
				foreach ( $class_matches as $one ) {
					$token = (string) $one[1];
					$first = strlen( $token ) > 0 ? $token[0] : '';
					$inner = ( '"' === $first || '\'' === $first ) ? substr( $token, 1, strlen( $token ) - 2 ) : $token;
					$parts = preg_split( '/\s+/', $inner );
					if ( ! is_array( $parts ) ) {
						continue;
					}
					foreach ( $parts as $part ) {
						$part = (string) $part;
						if ( '' === $part ) {
							continue;
						}
						if ( false !== strpos( $part, 'pfoa-cat-card' )
							&& ! in_array( $part, $allowed_tokens, true )
						) {
							return $fail( 'spoofed_marker', 'Card holds a spoofed generated class token.' );
						}
					}
				}
			}

		$article_has_token     = false;
		$article_parts         = array();
		if ( 1 === preg_match( $class_pattern, $open_tag, $article_class_match ) ) {
			$token = (string) $article_class_match[1];
			$first = strlen( $token ) > 0 ? $token[0] : '';
			$inner = ( '"' === $first || '\'' === $first ) ? substr( $token, 1, strlen( $token ) - 2 ) : $token;
			$parts = preg_split( '/\s+/', $inner );
			if ( is_array( $parts ) ) {
				foreach ( $parts as $part ) {
					if ( '' !== (string) $part ) {
						$article_parts[] = (string) $part;
					}
				}
				if ( in_array( 'pfoa-cat-card', $article_parts, true ) ) {
					$article_has_token = true;
				}
			}
		}
		$article_is_incomplete = in_array( 'pfoa-cat-card-incomplete', $article_parts, true );
		$article_pair_count    = 0;
		if ( in_array( 'pfoa-cat-pair-left', $article_parts, true ) ) {
			$article_pair_count++;
		}
		if ( in_array( 'pfoa-cat-pair-right', $article_parts, true ) ) {
			$article_pair_count++;
		}

			$anchor_count = preg_match_all( $anchor_pattern, $slice, $anchor_matches, PREG_OFFSET_CAPTURE );
			if ( false === $anchor_count ) {
				return $fail( 'unsupported_structure', 'Card anchors cannot be read.' );
			}
			$raw_anchor_count = preg_match_all( '/<a(?![A-Za-z0-9])/i', $slice, $raw_anchor_matches );
			if ( false === $raw_anchor_count || $raw_anchor_count !== $anchor_count ) {
				return $fail( 'unsupported_structure', 'Card anchors cannot be read.' );
			}

			$dialog_all      = array();
			$dialog_nonempty = array();
			$media_valid     = false;
			$anchor_tags     = isset( $anchor_matches[0] ) && is_array( $anchor_matches[0] ) ? $anchor_matches[0] : array();
			foreach ( $anchor_tags as $anchor_match ) {
				$anchor_tag = (string) $anchor_match[0];
				if ( 1 === preg_match( $dialog_pattern, $anchor_tag, $dialog_match ) ) {
					$token = (string) $dialog_match[1];
					$first = strlen( $token ) > 0 ? $token[0] : '';
					$slug  = ( '"' === $first || '\'' === $first ) ? substr( $token, 1, strlen( $token ) - 2 ) : $token;
					$dialog_all[] = $slug;
					if ( '' !== $slug ) {
						$dialog_nonempty[] = $slug;
					}
					if ( 1 === preg_match( $slug_pattern, $slug )
						&& 1 === preg_match( $class_pattern, $anchor_tag, $anchor_class_match )
					) {
						$ctoken = (string) $anchor_class_match[1];
						$cfirst = strlen( $ctoken ) > 0 ? $ctoken[0] : '';
						$cinner = ( '"' === $cfirst || '\'' === $cfirst ) ? substr( $ctoken, 1, strlen( $ctoken ) - 2 ) : $ctoken;
						$cparts = preg_split( '/\s+/', $cinner );
						if ( is_array( $cparts ) && in_array( 'pfoa-cat-card-media', $cparts, true ) ) {
							$media_valid = true;
						}
					}
				}
			}

			$h3_count = preg_match_all( $h3_pattern, $slice, $h3_matches, PREG_OFFSET_CAPTURE );
			if ( false === $h3_count ) {
				return $fail( 'unsupported_structure', 'Card headings cannot be read.' );
			}
			$raw_h3_count = preg_match_all( '/<h3(?![A-Za-z0-9])/i', $slice, $raw_h3_matches );
			if ( false === $raw_h3_count || $raw_h3_count !== $h3_count ) {
				return $fail( 'unsupported_structure', 'Card headings cannot be read.' );
			}
		$title_valid = false;
		$title_plain = false;
		$h3_tags     = isset( $h3_matches[0] ) && is_array( $h3_matches[0] ) ? $h3_matches[0] : array();
			foreach ( $h3_tags as $h3_match ) {
				$h3_tag    = (string) $h3_match[0];
				$h3_offset = (int) $h3_match[1];
				$h3_end    = $h3_offset + strlen( $h3_tag );
				$close_pos = stripos( $slice, '</h3', $h3_end );
				if ( false === $close_pos ) {
					return $fail( 'unsupported_structure', 'Card headings cannot be read.' );
				}
				$has_title_token = false;
				if ( 1 === preg_match( $class_pattern, $h3_tag, $h3_class_match ) ) {
					$ttoken = (string) $h3_class_match[1];
					$tfirst = strlen( $ttoken ) > 0 ? $ttoken[0] : '';
					$tinner = ( '"' === $tfirst || '\'' === $tfirst ) ? substr( $ttoken, 1, strlen( $ttoken ) - 2 ) : $ttoken;
					$tparts = preg_split( '/\s+/', $tinner );
					if ( is_array( $tparts ) && in_array( 'pfoa-cat-card-title', $tparts, true ) ) {
						$has_title_token = true;
					}
				}
				if ( ! $has_title_token ) {
					continue;
				}
				$inner = substr( $slice, $h3_end, $close_pos - $h3_end );
			if ( 0 === preg_match( '/<a(?![A-Za-z0-9])/i', $inner ) ) {
				$title_plain = true;
			}
				if ( preg_match_all( $anchor_pattern, $inner, $inner_anchors, PREG_SET_ORDER ) ) {
					foreach ( $inner_anchors as $inner_one ) {
						$inner_tag = (string) $inner_one[0];
						if ( 1 === preg_match( $dialog_pattern, $inner_tag, $inner_dialog ) ) {
							$itoken = (string) $inner_dialog[1];
							$ifirst = strlen( $itoken ) > 0 ? $itoken[0] : '';
							$islug  = ( '"' === $ifirst || '\'' === $ifirst ) ? substr( $itoken, 1, strlen( $itoken ) - 2 ) : $itoken;
							if ( 1 === preg_match( $slug_pattern, $islug ) ) {
								$title_valid = true;
								break;
							}
						}
					}
				}
				if ( $title_valid ) {
					break;
				}
			}

			$div_count = preg_match_all( $div_pattern, $slice, $div_matches, PREG_OFFSET_CAPTURE );
		if ( false === $div_count ) {
			return $fail( 'unsupported_structure', 'Card media cannot be read.' );
		}
		$raw_div_count = preg_match_all( '/<div(?![A-Za-z0-9])/i', $slice, $raw_div_matches );
		if ( false === $raw_div_count || $raw_div_count !== $div_count ) {
			return $fail( 'unsupported_structure', 'Card media cannot be read.' );
		}
		$p_count = preg_match_all( $p_pattern, $slice, $p_matches, PREG_SET_ORDER );
		if ( false === $p_count ) {
			return $fail( 'unsupported_structure', 'Card notes cannot be read.' );
		}
		$raw_p_count = preg_match_all( '/<p(?![A-Za-z0-9])/i', $slice, $raw_p_matches );
		if ( false === $raw_p_count || $raw_p_count !== $p_count ) {
			return $fail( 'unsupported_structure', 'Card notes cannot be read.' );
		}
		$img_count = preg_match_all( $img_pattern, $slice, $img_matches );
		if ( false === $img_count ) {
			return $fail( 'unsupported_structure', 'Card images cannot be read.' );
		}
		$raw_img_count = preg_match_all( '/<img(?![A-Za-z0-9])/i', $slice, $raw_img_matches );
		if ( false === $raw_img_count || $raw_img_count !== $img_count ) {
			return $fail( 'unsupported_structure', 'Card images cannot be read.' );
		}

		$media_div_ok = false;
		$div_tags     = isset( $div_matches[0] ) && is_array( $div_matches[0] ) ? $div_matches[0] : array();
		foreach ( $div_tags as $div_match ) {
			$div_tag = (string) $div_match[0];
			$div_off = (int) $div_match[1];
			if ( 1 !== preg_match( $class_pattern, $div_tag, $div_class_match ) ) {
				continue;
			}
			$dctoken = (string) $div_class_match[1];
			$dcfirst = strlen( $dctoken ) > 0 ? $dctoken[0] : '';
			$dcinner = ( '"' === $dcfirst || '\'' === $dcfirst ) ? substr( $dctoken, 1, strlen( $dctoken ) - 2 ) : $dctoken;
			$dcparts = preg_split( '/\s+/', $dcinner );
			if ( ! is_array( $dcparts ) || ! in_array( 'pfoa-cat-card-media', $dcparts, true ) ) {
				continue;
			}
			$div_end        = $div_off + strlen( $div_tag );
			$div_close_pos  = stripos( $slice, '</div', $div_end );
			if ( false === $div_close_pos ) {
				continue;
			}
			$div_inner = substr( $slice, $div_end, $div_close_pos - $div_end );
			if ( 1 === preg_match( $img_pattern, $div_inner ) ) {
				$media_div_ok = true;
				break;
			}
		}

		$note_found   = false;
		$status_found = false;
		foreach ( $p_matches as $p_one ) {
			$p_tag = (string) $p_one[0];
			if ( 1 !== preg_match( $class_pattern, $p_tag, $p_class_match ) ) {
				continue;
			}
			$pctoken = (string) $p_class_match[1];
			$pcfirst = strlen( $pctoken ) > 0 ? $pctoken[0] : '';
			$pcinner = ( '"' === $pcfirst || '\'' === $pcfirst ) ? substr( $pctoken, 1, strlen( $pctoken ) - 2 ) : $pctoken;
			$pcparts = preg_split( '/\s+/', $pcinner );
			if ( ! is_array( $pcparts ) ) {
				continue;
			}
			if ( in_array( 'pfoa-cat-card-note', $pcparts, true ) ) {
				$note_found = true;
			}
			if ( in_array( 'pfoa-cat-card-status', $pcparts, true ) ) {
				$status_found = true;
			}
		}

		$agreed = null;
			if ( count( $dialog_nonempty ) >= 2 && 1 === count( array_unique( $dialog_nonempty ) ) ) {
				$candidate = (string) $dialog_nonempty[0];
				if ( 1 === preg_match( $slug_pattern, $candidate ) ) {
					$agreed = $candidate;
				}
			}

		$stored_id = count( $open_valid ) === 1 ? (string) $open_valid[0] : null;
		if ( null !== $stored_id ) {
			if ( $article_is_incomplete ) {
				if ( ! $article_has_token
					|| $article_pair_count > 1
					|| 0 !== count( $dialog_all )
					|| $media_valid
					|| $title_valid
					|| ! $media_div_ok
					|| ! $title_plain
					|| ! $note_found
					|| ( $status_found && $article_pair_count > 0 )
				) {
					return $fail( 'ambiguous_card', 'Card ownership cannot be settled.' );
				}
				$derived[] = array(
					'start'     => $start,
					'end'       => $end,
					'slice'     => $slice,
					'stored_id' => $stored_id,
					'agreed'    => null,
					'category'  => 'id-incomplete',
				);
			} elseif ( ! $article_has_token || ! $media_valid || ! $title_valid || null === $agreed ) {
				return $fail( 'ambiguous_card', 'Card ownership cannot be settled.' );
			} else {
				$derived[] = array(
					'start'     => $start,
					'end'       => $end,
					'slice'     => $slice,
					'stored_id' => $stored_id,
					'agreed'    => $agreed,
					'category'  => 'id',
				);
			}
		} elseif ( $article_has_token && $media_valid && $title_valid && null !== $agreed ) {
				$derived[] = array(
					'start'     => $start,
					'end'       => $end,
					'slice'     => $slice,
					'stored_id' => null,
					'agreed'    => $agreed,
					'category'  => 'legacy',
				);
			} elseif ( ! $article_has_token && 0 === count( $dialog_all ) ) {
				$derived[] = array(
					'start'     => $start,
					'end'       => $end,
					'slice'     => $slice,
					'stored_id' => null,
					'agreed'    => null,
					'category'  => 'manual',
				);
			} else {
				return $fail( 'ambiguous_card', 'Card ownership cannot be settled.' );
			}
		}

		$card_spans = array();
		foreach ( $derived as $item ) {
			$card_spans[ $item['start'] . ':' . $item['end'] ] = true;
		}
		$proofs_by_span = array();
		foreach ( $trusted_proofs as $proof ) {
			$span_key = $proof['start'] . ':' . $proof['end'];
			if ( ! isset( $proofs_by_span[ $span_key ] ) ) {
				$proofs_by_span[ $span_key ] = array();
			}
			$proofs_by_span[ $span_key ][] = $proof;
		}
		foreach ( $proofs_by_span as $span_key => $span_proofs ) {
			if ( ! isset( $card_spans[ $span_key ] ) ) {
				return $fail( 'orphan_proof', 'A trusted proof matches no inventoried card.' );
			}
		}

		$owned  = array();
		$manual = array();
		foreach ( $derived as $item ) {
			$span_key = $item['start'] . ':' . $item['end'];
			$matches  = isset( $proofs_by_span[ $span_key ] ) ? $proofs_by_span[ $span_key ] : array();
			if ( 'manual' === $item['category'] ) {
				if ( 0 !== count( $matches ) ) {
					return $fail( 'conflicting_claims', 'A manual span carries a trusted claim.' );
				}
				$manual[] = array(
					'start' => $item['start'],
					'end'   => $item['end'],
					'slice' => $item['slice'],
				);
				continue;
			}
			if ( 0 === count( $matches ) ) {
				return $fail( 'missing_proof', 'An owned span has no trusted proof.' );
			}
			if ( 1 !== count( $matches ) ) {
				return $fail( 'conflicting_claims', 'A card span carries conflicting trusted claims.' );
			}
		$proof = $matches[0];
		if ( 'id' === $item['category'] || 'id-incomplete' === $item['category'] ) {
				if ( $proof['profile_id'] !== $item['stored_id'] ) {
					return $fail( 'conflicting_claims', 'Trusted claim disagrees with the stored marker.' );
				}
			} elseif ( ! array_key_exists( 'slug', $proof ) || $proof['slug'] !== $item['agreed'] ) {
				return $fail( 'conflicting_claims', 'Trusted claim disagrees with the proven dialog slug.' );
			}
			if ( $proof['slice'] !== $item['slice'] ) {
				return $fail( 'stale_proof', 'Trusted proof no longer matches the stored slice.' );
			}
			if ( md5( $item['slice'] ) !== strtolower( (string) $proof['fingerprint'] ) ) {
				return $fail( 'fingerprint_mismatch', 'Trusted proof fingerprint does not match the stored slice.' );
			}
			$owned[] = array(
				'start'      => $item['start'],
				'end'        => $item['end'],
				'slice'      => $item['slice'],
				'profile_id' => (string) $proof['profile_id'],
			'slot'       => (string) $proof['slot'],
			'kind'       => ( 'id' === $item['category'] || 'id-incomplete' === $item['category'] ) ? 'id-owned' : 'legacy-proven',
			);
		}

		$seen_slots = array();
		foreach ( $owned as $entry ) {
			$slot_key = $entry['profile_id'] . "\0" . $entry['slot'];
			if ( isset( $seen_slots[ $slot_key ] ) ) {
				return $fail( 'duplicate_slot', 'One profile claims one slot on two spans.' );
			}
			$seen_slots[ $slot_key ] = true;
		}

		return array(
			'ok'             => true,
			'source_page_id' => 20323,
			'owned'          => $owned,
			'manual'         => $manual,
			'count_owned'    => count( $owned ),
			'count_manual'   => count( $manual ),
		);
	}
}
