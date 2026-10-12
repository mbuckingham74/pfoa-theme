<?php
/**
 * Theme-owned managed-card dialog attribute reader (INACTIVE).
 *
 * INACTIVE — do NOT require from functions.php, no hooks, no writes, no assets.
 * Pure tag-level reader for the data-pfoa-cat-dialog attribute on one anchor
 * open tag. The tag is tokenized character by character so quoted values are
 * skipped entirely and text inside quotes is never parsed as an attribute.
 * The dialog name must match exactly (case-insensitive); prefixed variants
 * such as x:data-pfoa-cat-dialog, x.data-pfoa-cat-dialog, x-data-pfoa-cat-dialog
 * or foo-data-pfoa-cat-dialog are rejected. Single, double-quoted and unquoted
 * values are accepted; quoted values must be properly closed and unquoted
 * values consume the full run up to whitespace or `>` (including `/`, so a
 * value like history/intruder is never truncated and fails the slug check).
 * Values must match the lowercase slug pattern; duplicates (even identical
 * values), missing `=` signs and missing/incomplete values are rejected.
 * Any doubt reports duplicate or malformed so callers fail closed.
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

if ( ! defined( 'PFOA_THEME_MANAGED_CARD_DIALOG_ATTRIBUTE_LOADED' ) ) {
	define( 'PFOA_THEME_MANAGED_CARD_DIALOG_ATTRIBUTE_LOADED', true );
}

if ( ! function_exists( 'pfoa_theme_read_dialog_attribute' ) ) {
	/**
	 * Read the dialog attribute from one anchor open tag.
	 *
	 * Pure string scan over the given tag text with byte-safe length,
	 * position and slice helpers. No platform access, no hooks, no storage
	 * reads, no writes, no file operations.
	 *
	 * @param mixed $anchor_tag Anchor open tag text (e.g. `<a ...>`).
	 * @return array Array with status (none|single|duplicate|malformed),
	 *               slug (valid slug string on single, else null), raw (first
	 *               raw value or null on none), hits (dialog-looking occurrence
	 *               count) and values (raw value per occurrence, evidence for
	 *               callers even when rejected).
	 */
	function pfoa_theme_read_dialog_attribute( $anchor_tag ): array {
		$none = array(
			'status' => 'none',
			'slug'   => null,
			'raw'    => null,
			'hits'   => 0,
			'values' => array(),
		);
		$as_malformed = function ( $values ) {
			$flat = array();
			if ( is_array( $values ) ) {
				foreach ( array_values( $values ) as $one ) {
					$flat[] = is_string( $one ) ? $one : '';
				}
			}
			if ( 0 === count( $flat ) ) {
				$flat[] = '';
			}
			return array(
				'status' => 'malformed',
				'slug'   => null,
				'raw'    => (string) $flat[0],
				'hits'   => count( $flat ),
				'values' => $flat,
			);
		};
		if ( ! is_string( $anchor_tag ) ) {
			return $as_malformed( array() );
		}
		$tag = $anchor_tag;
		$len = strlen( $tag );
		if ( $len < 3 || '<' !== $tag[0] || ( 'a' !== $tag[1] && 'A' !== $tag[1] ) ) {
			return $as_malformed( array() );
		}
		$third = $tag[2];
		if ( ( 'A' <= $third && $third <= 'Z' )
			|| ( 'a' <= $third && $third <= 'z' )
			|| ( '0' <= $third && $third <= '9' )
		) {
			return $as_malformed( array() );
		}
		$is_space = function ( $ch ) {
			return ' ' === $ch
				|| "\t" === $ch
				|| "\n" === $ch
				|| "\r" === $ch
				|| "\f" === $ch
				|| "\v" === $ch;
		};
		$is_name = function ( $ch ) {
			return ( 'A' <= $ch && $ch <= 'Z' )
				|| ( 'a' <= $ch && $ch <= 'z' )
				|| ( '0' <= $ch && $ch <= '9' )
				|| '_' === $ch
				|| '-' === $ch
				|| '.' === $ch
				|| ':' === $ch;
		};
		$slugs  = array();
		$values = array();
		$bad    = 0;
		$pos    = 2;
		while ( true ) {
			while ( $pos < $len && $is_space( $tag[ $pos ] ) ) {
				$pos++;
			}
			if ( $pos >= $len ) {
				return $as_malformed( $values );
			}
			$char = $tag[ $pos ];
			if ( '>' === $char ) {
				break;
			}
			if ( '/' === $char ) {
				$pos++;
				continue;
			}
			$name_start = $pos;
			while ( $pos < $len && $is_name( $tag[ $pos ] ) ) {
				$pos++;
			}
			if ( $pos === $name_start ) {
				$values[] = '';
				return $as_malformed( $values );
			}
			$name  = substr( $tag, $name_start, $pos - $name_start );
			$lower = strtolower( $name );
			$exact = ( 'data-pfoa-cat-dialog' === $lower );
			$looks = ( false !== strpos( $lower, 'data-pfoa-cat-dialog' ) );
			while ( $pos < $len && $is_space( $tag[ $pos ] ) ) {
				$pos++;
			}
			if ( $pos < $len && '=' === $tag[ $pos ] ) {
				$pos++;
				while ( $pos < $len && $is_space( $tag[ $pos ] ) ) {
					$pos++;
				}
				if ( $pos >= $len ) {
					$values[] = '';
					return $as_malformed( $values );
				}
				$quote = $tag[ $pos ];
				if ( '"' === $quote || '\'' === $quote ) {
					$close = strpos( $tag, $quote, $pos + 1 );
					if ( false === $close ) {
						$values[] = '';
						return $as_malformed( $values );
					}
					$raw = substr( $tag, $pos + 1, $close - $pos - 1 );
					$pos = $close + 1;
				} else {
					$value_start = $pos;
					while ( $pos < $len && ! $is_space( $tag[ $pos ] ) && '>' !== $tag[ $pos ] ) {
						$pos++;
					}
					if ( $pos === $value_start ) {
						$values[] = '';
						return $as_malformed( $values );
					}
					$raw = substr( $tag, $value_start, $pos - $value_start );
				}
				if ( $exact ) {
					$values[] = $raw;
					if ( 1 === preg_match( '/\A[a-z0-9-]+\z/', $raw ) ) {
						$slugs[] = $raw;
					} else {
						$bad++;
					}
				} elseif ( $looks ) {
					$values[] = $raw;
					$bad++;
				}
			} elseif ( $exact || $looks ) {
				$values[] = '';
				$bad++;
			}
		}
		if ( 0 === count( $values ) ) {
			return $none;
		}
		if ( $bad > 0 ) {
			return $as_malformed( $values );
		}
		if ( count( $slugs ) > 1 ) {
			$flat = array_values( $slugs );
			return array(
				'status' => 'duplicate',
				'slug'   => null,
				'raw'    => (string) $flat[0],
				'hits'   => count( $flat ),
				'values' => $flat,
			);
		}
		$only = (string) $slugs[0];
		return array(
			'status' => 'single',
			'slug'   => $only,
			'raw'    => $only,
			'hits'   => 1,
			'values' => array( $only ),
		);
	}
}
