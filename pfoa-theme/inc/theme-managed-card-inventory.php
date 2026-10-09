<?php
/**
 * Theme-owned managed-card source inventory (INACTIVE).
 *
 * INACTIVE — do NOT require from functions.php, no hooks, no writes, no assets.
 * Read-only byte inventory of stored card markup for one listed source page.
 * It finds the single stored list wrapper and reports each stored article
 * element in stored order with exact byte offsets and verbatim slices. The
 * kind label is a candidate signal only and settles nothing about which
 * element came from where; manual elements are listed, never changed.
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

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_INVENTORY_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_INVENTORY_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_inventory_managed_cards' ) ) {
	/**
	 * Inventory stored card articles for one listed source page.
	 *
	 * Pure string scan over the given content: byte-safe length, position and
	 * slice helpers plus pattern matching with byte offsets. No platform
	 * access, no hooks, no storage reads, no writes, no file operations.
	 *
	 * Only source page 20323 (int or digit string) is accepted. The content
	 * must be a string holding exactly one <div ... class="...pfoa-cat-cards...">
	 * wrapper, located with balanced tag scanning so nested blocks inside are
	 * honoured. Each <article>...</article> element fully inside the wrapper
	 * is reported in stored order with its byte span, verbatim slice, class
	 * list, stored id marker, descendant dialog slugs and a candidate kind:
	 * id-marked when an id marker is present, else legacy-dialog when dialog
	 * slugs are present, else manual.
	 *
	 * The whole inventory fails (ok false) on any structural doubt: missing,
	 * doubled or unclosed wrapper, unclosed or nested article elements,
	 * overlapping spans, or elements crossing the wrapper edge.
	 *
	 * @param int|string $source_page_id Listed source page id (20323 only).
	 * @param mixed      $post_content   Raw stored content string.
	 * @return array Success array with ok, source_page_id, container, cards
	 *               and count; or failure array with ok false and error
	 *               code/message. Codes: unsupported_source, invalid_content,
	 *               no_container, ambiguous_container, malformed_container,
	 *               malformed_article, nested_article, overlapping_cards,
	 *               ambiguous_structure.
	 */
	function pfoa_theme_inventory_managed_cards( $source_page_id, $post_content ): array {
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

		$open_div_pattern = '/<div\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$div_count        = preg_match_all( $open_div_pattern, $content, $div_matches, PREG_OFFSET_CAPTURE );
		if ( false === $div_count ) {
			return $fail( 'malformed_container', 'Stored list wrapper scan failed.' );
		}

		$candidates = array();
		foreach ( $div_matches[0] as $match ) {
			$tag         = (string) $match[0];
			$offset      = (int) $match[1];
			$class_value = null;
			if ( preg_match( '/class\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $tag, $class_match ) ) {
				if ( isset( $class_match[1] ) && '' !== $class_match[1] ) {
					$class_value = (string) $class_match[1];
				} elseif ( isset( $class_match[2] ) && '' !== $class_match[2] ) {
					$class_value = (string) $class_match[2];
				} elseif ( isset( $class_match[3] ) ) {
					$class_value = (string) $class_match[3];
				}
			}
			if ( is_string( $class_value ) && false !== strpos( $class_value, 'pfoa-cat-cards' ) ) {
				$candidates[] = array(
					'start'    => $offset,
					'open_end' => $offset + strlen( $tag ),
				);
			}
		}

		if ( 0 === count( $candidates ) ) {
			return $fail( 'no_container', 'Stored list wrapper not found.' );
		}
		if ( 1 !== count( $candidates ) ) {
			return $fail( 'ambiguous_container', 'More than one stored list wrapper found.' );
		}

		$container_start = (int) $candidates[0]['start'];
		$inner_start     = (int) $candidates[0]['open_end'];

		$any_div_pattern = '/<\/?div\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$all_count       = preg_match_all( $any_div_pattern, $content, $all_div_matches, PREG_OFFSET_CAPTURE );
		if ( false === $all_count ) {
			return $fail( 'malformed_container', 'Stored list wrapper scan failed.' );
		}

		$depth         = 1;
		$inner_end     = -1;
		$container_end = -1;
		foreach ( $all_div_matches[0] as $match ) {
			$tag    = (string) $match[0];
			$offset = (int) $match[1];
			if ( $offset < $inner_start ) {
				continue;
			}
			$is_close = ( strlen( $tag ) > 1 && '/' === $tag[1] );
			$is_empty = (bool) preg_match( '/\/\s*>$/', $tag );
			if ( $is_close ) {
				$depth--;
			} elseif ( ! $is_empty ) {
				$depth++;
			}
			if ( 0 === $depth ) {
				$inner_end     = $offset;
				$container_end = $offset + strlen( $tag );
				break;
			}
			if ( $depth < 0 ) {
				break;
			}
		}
		if ( $depth < 0 ) {
			return $fail( 'malformed_container', 'Stored list wrapper tags do not line up.' );
		}
		if ( $container_end < 0 ) {
			return $fail( 'malformed_container', 'Stored list wrapper is not closed.' );
		}

		$raw_div_count = preg_match_all( '/<\/?div\b/i', $content, $raw_div_matches, PREG_OFFSET_CAPTURE );
		if ( false === $raw_div_count ) {
			return $fail( 'malformed_container', 'Stored list wrapper scan failed.' );
		}
		$known_div = array();
		foreach ( $all_div_matches[0] as $match ) {
			$known_div[ (int) $match[1] ] = true;
		}
		foreach ( $raw_div_matches[0] as $match ) {
			$offset = (int) $match[1];
			if ( $offset >= $container_start && $offset < $container_end && ! isset( $known_div[ $offset ] ) ) {
				return $fail( 'malformed_container', 'Stored list wrapper holds a broken tag.' );
			}
		}

		$article_pattern = '/<\/?article\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i';
		$article_count   = preg_match_all( $article_pattern, $content, $article_matches, PREG_OFFSET_CAPTURE );
		if ( false === $article_count ) {
			return $fail( 'malformed_article', 'Stored card scan failed.' );
		}

		$raw_article_count = preg_match_all( '/<\/?article\b/i', $content, $raw_article_matches, PREG_OFFSET_CAPTURE );
		if ( false === $raw_article_count ) {
			return $fail( 'malformed_article', 'Stored card scan failed.' );
		}
		$known_article = array();
		foreach ( $article_matches[0] as $match ) {
			$known_article[ (int) $match[1] ] = true;
		}
		foreach ( $raw_article_matches[0] as $match ) {
			$offset = (int) $match[1];
			if ( ! isset( $known_article[ $offset ] ) ) {
				return $fail( 'malformed_article', 'Stored card holds a broken tag.' );
			}
		}

		$elements   = array();
		$open_start = null;
		$open_tag   = null;
		foreach ( $article_matches[0] as $match ) {
			$tag      = (string) $match[0];
			$offset   = (int) $match[1];
			$is_close = ( strlen( $tag ) > 1 && '/' === $tag[1] );
			$is_empty = (bool) preg_match( '/\/\s*>$/', $tag );
			if ( $is_empty && ! $is_close ) {
				return $fail( 'malformed_article', 'Stored card element is not a paired article.' );
			}
			if ( ! $is_close ) {
				if ( null !== $open_start ) {
					return $fail( 'nested_article', 'Stored card elements nest inside each other.' );
				}
				$open_start = $offset;
				$open_tag   = $tag;
			} else {
				if ( null === $open_start ) {
					return $fail( 'malformed_article', 'Stored card holds a stray closing tag.' );
				}
				$elements[] = array(
					'start'    => (int) $open_start,
					'end'      => $offset + strlen( $tag ),
					'open_tag' => (string) $open_tag,
				);
				$open_start = null;
				$open_tag   = null;
			}
		}
		if ( null !== $open_start ) {
			return $fail( 'malformed_article', 'Stored card element is not closed.' );
		}

		$inside = array();
		foreach ( $elements as $element ) {
			$start = (int) $element['start'];
			$end   = (int) $element['end'];
			if ( $start >= $inner_start && $end <= $inner_end ) {
				$inside[] = $element;
				continue;
			}
			if ( $end <= $container_start || $start >= $container_end ) {
				continue;
			}
			return $fail( 'ambiguous_structure', 'Stored card element crosses the list wrapper edge.' );
		}

		$total_inside = count( $inside );
		for ( $i = 1; $i < $total_inside; $i++ ) {
			$prev_start = (int) $inside[ $i - 1 ]['start'];
			$prev_end   = (int) $inside[ $i - 1 ]['end'];
			$this_start = (int) $inside[ $i ]['start'];
			$this_end   = (int) $inside[ $i ]['end'];
			if ( $this_start === $prev_start && $this_end === $prev_end ) {
				return $fail( 'ambiguous_structure', 'Stored card elements share one byte span.' );
			}
			if ( $this_start < $prev_end ) {
				return $fail( 'overlapping_cards', 'Stored card elements overlap.' );
			}
		}

		$cards = array();
		foreach ( $inside as $index => $element ) {
			$start = (int) $element['start'];
			$end   = (int) $element['end'];
			$slice = substr( $content, $start, $end - $start );
			if ( $slice !== substr( $content, $start, $end - $start ) ) {
				return $fail( 'ambiguous_structure', 'Stored card slice does not match its byte span.' );
			}

			$classes  = array();
			$open_tag = (string) $element['open_tag'];
			if ( preg_match( '/class\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>\/]+))/i', $open_tag, $class_match ) ) {
				$raw_class = '';
				if ( isset( $class_match[1] ) && '' !== $class_match[1] ) {
					$raw_class = (string) $class_match[1];
				} elseif ( isset( $class_match[2] ) && '' !== $class_match[2] ) {
					$raw_class = (string) $class_match[2];
				} elseif ( isset( $class_match[3] ) ) {
					$raw_class = (string) $class_match[3];
				}
				$parts = preg_split( '/\s+/', $raw_class );
				if ( is_array( $parts ) ) {
					foreach ( $parts as $part ) {
						if ( '' !== $part ) {
							$classes[] = (string) $part;
						}
					}
				}
			}

			$profile_id = null;
			if ( preg_match( '/data-pfoa-profile-id\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>\/]+))/i', $open_tag, $id_match ) ) {
				$raw_id = '';
				if ( isset( $id_match[1] ) && '' !== $id_match[1] ) {
					$raw_id = (string) $id_match[1];
				} elseif ( isset( $id_match[2] ) && '' !== $id_match[2] ) {
					$raw_id = (string) $id_match[2];
				} elseif ( isset( $id_match[3] ) ) {
					$raw_id = (string) $id_match[3];
				}
				if ( '' !== $raw_id ) {
					$profile_id = (string) $raw_id;
				}
			}

			$dialog_slugs = array();
			$anchor_count = preg_match_all( '/<a\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $slice, $anchor_matches );
			if ( false !== $anchor_count && isset( $anchor_matches[0] ) && is_array( $anchor_matches[0] ) ) {
				foreach ( $anchor_matches[0] as $anchor_tag ) {
					$anchor_tag = (string) $anchor_tag;
					if ( preg_match( '/data-pfoa-cat-dialog\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>\/]+))/i', $anchor_tag, $dialog_match ) ) {
						$raw_slug = '';
						if ( isset( $dialog_match[1] ) && '' !== $dialog_match[1] ) {
							$raw_slug = (string) $dialog_match[1];
						} elseif ( isset( $dialog_match[2] ) && '' !== $dialog_match[2] ) {
							$raw_slug = (string) $dialog_match[2];
						} elseif ( isset( $dialog_match[3] ) ) {
							$raw_slug = (string) $dialog_match[3];
						}
						$dialog_slugs[] = (string) $raw_slug;
					}
				}
			}

			$has_profile_id = ( null !== $profile_id );
			$has_dialog     = false;
			foreach ( $dialog_slugs as $slug_value ) {
				if ( '' !== $slug_value ) {
					$has_dialog = true;
					break;
				}
			}
			if ( $has_profile_id ) {
				$kind = 'id-marked';
			} elseif ( $has_dialog ) {
				$kind = 'legacy-dialog';
			} else {
				$kind = 'manual';
			}

			$cards[] = array(
				'index'          => (int) $index,
				'start'          => $start,
				'end'            => $end,
				'slice'          => $slice,
				'profile_id'     => $profile_id,
				'dialog_slugs'   => $dialog_slugs,
				'classes'        => $classes,
				'has_profile_id' => $has_profile_id,
				'has_dialog'     => $has_dialog,
				'kind'           => $kind,
			);
		}

		return array(
			'ok'             => true,
			'source_page_id' => 20323,
			'container'      => array(
				'start' => $container_start,
				'end'   => $container_end,
			),
			'cards'          => $cards,
			'count'          => count( $cards ),
		);
	}
}
