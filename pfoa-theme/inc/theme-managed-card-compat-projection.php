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

		$content = $raw_content;

		if ( ! function_exists( 'pfoa_theme_inventory_managed_cards' ) ) {
			return $fail( 'missing_dependency', 'Inventory component is not loaded.', 'inventory' );
		}
		$inventory = pfoa_theme_inventory_managed_cards( 20323, $content );
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

		if ( ! function_exists( 'pfoa_theme_classify_managed_card_ownership' ) ) {
			return $fail( 'missing_dependency', 'Ownership component is not loaded.', 'classify' );
		}
		$classification = pfoa_theme_classify_managed_card_ownership( 20323, $inventory, $trusted_proofs, $content );
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

		if ( ! function_exists( 'pfoa_theme_validate_managed_card_placement' ) ) {
			return $fail( 'missing_dependency', 'Placement component is not loaded.', 'validate' );
		}
		$validation = pfoa_theme_validate_managed_card_placement( 20323, $classification, $placement_records, $content );
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

		if ( ! function_exists( 'pfoa_theme_splice_managed_cards' ) ) {
			return $fail( 'missing_dependency', 'Splice component is not loaded.', 'splice' );
		}
		$spliced = pfoa_theme_splice_managed_cards( 20323, $content, $classification, $replacements );
		if ( ! is_array( $spliced ) || ! array_key_exists( 'success', $spliced ) || true !== $spliced['success'] ) {
			$reason = 'splice_failed';
			if ( is_array( $spliced ) && array_key_exists( 'failure_reason', $spliced ) && is_string( $spliced['failure_reason'] ) && '' !== $spliced['failure_reason'] ) {
				$reason = $spliced['failure_reason'];
			}
			return $fail( $reason, 'Managed-card splice failed: ' . $reason . '.', 'splice' );
		}
		if ( ! array_key_exists( 'projected_content', $spliced ) || ! is_string( $spliced['projected_content'] ) ) {
			return $fail( 'splice_failed', 'Managed-card splice failed: splice_failed.', 'splice' );
		}
		if ( ! array_key_exists( 'replaced_count', $spliced ) || ! is_int( $spliced['replaced_count'] ) ) {
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
