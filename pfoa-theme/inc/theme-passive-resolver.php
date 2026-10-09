<?php
/**
 * Theme-side passive capability declaration and inert readiness resolver.
 *
 * DIAGNOSTIC-ONLY. This file:
 *
 *  - declares a versioned passive capability statement for the theme bundle,
 *  - resolves a deterministic presentation-readiness snapshot, and
 *  - freezes that snapshot request-locally at init:100.
 *
 * It never renders, registers, gates, suppresses, or transfers anything:
 * no shortcodes, no content/template filters, no redirects, no enqueues,
 * no storage reads or writes, and no lifecycle/activation checks. The
 * legacy plugin owns all public output in this cut, so readiness is always
 * reported as 'unavailable' and both compat/current ready flags stay false
 * on every path.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_PASSIVE_RESOLVER_LOADED' ) ) {
	define( 'PFOA_THEME_PASSIVE_RESOLVER_LOADED', true );
}

if ( ! defined( 'PFOA_THEME_PASSIVE_CAPABILITIES_VERSION' ) ) {
	define( 'PFOA_THEME_PASSIVE_CAPABILITIES_VERSION', '1.0' );
}

if ( ! function_exists( 'pfoa_theme_get_passive_capabilities' ) ) {
	/**
	 * Versioned passive capability declaration for the theme bundle.
	 *
	 * Deterministic and side-effect free: fixed key order, scalar values
	 * plus one string array, no database/hook/storage reads. Safe to call
	 * on any request.
	 *
	 * @return array
	 */
	function pfoa_theme_get_passive_capabilities() {
		$theme_version = '0.0.0';
		if ( function_exists( 'wp_get_theme' ) ) {
			$theme = wp_get_theme();
			if ( is_object( $theme ) && method_exists( $theme, 'get' ) ) {
				$candidate = $theme->get( 'Version' );
				if ( is_string( $candidate ) && '' !== $candidate ) {
					$theme_version = $candidate;
				}
			}
		}

		$capabilities_version = defined( 'PFOA_THEME_PASSIVE_CAPABILITIES_VERSION' ) ? PFOA_THEME_PASSIVE_CAPABILITIES_VERSION : '1.0';
		if ( ! is_string( $capabilities_version ) || '' === $capabilities_version ) {
			$capabilities_version = '1.0';
		}

		return array(
			'capabilities_version'  => $capabilities_version,
			'theme_version'         => $theme_version,
			'compat_implemented'    => false,
			'current_implemented'   => false,
			'compat_ready'          => false,
			'current_ready'         => false,
			'available_components'  => array(),
			'bundle_complete'       => false,
			'notes'                 => 'diagnostic-only; legacy plugin owns output',
		);
	}
}

if ( ! function_exists( 'pfoa_theme_resolve_presentation_readiness' ) ) {
	/**
	 * Deterministic diagnostic resolver for presentation readiness.
	 *
	 * Pure function of its inputs plus guarded probes: strict boolean
	 * handling of the PFOA_THEME_PRESENTATION switch (undefined/false maps
	 * to the compat path, true to the current path, any non-boolean to
	 * unavailable), guarded reads of Cat Profiles / Gallery passive
	 * capabilities, fail-closed legacy ownership detection, and an
	 * incomplete bundle signal. Never true while the legacy plugin owns
	 * output; compat_ready and current_ready stay false on all paths in
	 * this cut. Performs no rendering, registration, gating, or I/O.
	 *
	 * @param mixed      $request_override     Optional request switch override (bool expected; other types report invalid).
	 * @param array|null $caps_override        Optional theme capability declaration override for diagnostics.
	 * @param array|null $cat_caps_override    Optional Cat Profiles passive capabilities override.
	 * @param array|null $gallery_caps_override Optional Gallery passive capabilities override.
	 * @param mixed      $bundle_override      Optional bundle-complete override (bool expected).
	 * @return array
	 */
	function pfoa_theme_resolve_presentation_readiness( $request_override = null, $caps_override = null, $cat_caps_override = null, $gallery_caps_override = null, $bundle_override = null ) {
		// a) Request switch: strict boolean only, no truthiness.
		if ( null !== $request_override ) {
			$candidate = $request_override;
			$is_absent = false;
		} elseif ( ! defined( 'PFOA_THEME_PRESENTATION' ) ) {
			$candidate = null;
			$is_absent = true;
		} else {
			$candidate = PFOA_THEME_PRESENTATION;
			$is_absent = false;
		}

		if ( $is_absent ) {
			$request_state  = 'absent';
			$requested_mode = 'compat';
		} elseif ( true === $candidate ) {
			$request_state  = 'true';
			$requested_mode = 'current';
		} elseif ( false === $candidate ) {
			$request_state  = 'false';
			$requested_mode = 'compat';
		} else {
			$request_state  = 'invalid';
			$requested_mode = 'unavailable';
		}

		// b) Cat Profiles neutral schema: function + versioned schema guard.
		$cat_neutral_available = (
			function_exists( 'pfoa_cat_get_profile_data' )
			&& defined( 'PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION' )
			&& '1.0' === PFOA_CAT_PROFILE_DATA_SCHEMA_VERSION
		);

		// c) Cat Profiles passive capabilities, guarded (throws/missing keys => missing).
		$cat_caps = null;
		if ( null !== $cat_caps_override ) {
			if ( is_array( $cat_caps_override ) ) {
				$cat_caps = $cat_caps_override;
			}
		} elseif ( function_exists( 'pfoa_cat_get_passive_capabilities' ) ) {
			try {
				$reported = pfoa_cat_get_passive_capabilities();
				if ( is_array( $reported ) ) {
					$cat_caps = $reported;
				}
			} catch ( Throwable $throwable ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Missing means unavailable.
				$cat_caps = null;
			}
		}

		$cat_passive_available = (
			is_array( $cat_caps )
			&& array_key_exists( 'capabilities_version', $cat_caps )
		);

		$cat_ownership = $cat_passive_available && isset( $cat_caps['public_ownership'] ) && is_string( $cat_caps['public_ownership'] )
			? strtolower( trim( $cat_caps['public_ownership'] ) )
			: '';
		$cat_passive_satisfied = (
			$cat_passive_available
			&& array_key_exists( 'neutral_only', $cat_caps )
			&& true === $cat_caps['neutral_only']
			&& in_array( $cat_ownership, array( 'neutral', 'theme-neutral' ), true )
		);

		// Neutral data is satisfied only when the schema is present and the
		// Cat Profiles passive report itself flags neutral data available.
		// This resolver never fetches profile data (no ID context, no DB).
		$cat_neutral_satisfied = (
			$cat_neutral_available
			&& $cat_passive_available
			&& array_key_exists( 'neutral_data_available', $cat_caps )
			&& true === $cat_caps['neutral_data_available']
		);

		// d) Gallery passive capabilities, guarded (throws/missing keys => missing).
		$gallery_caps = null;
		if ( null !== $gallery_caps_override ) {
			if ( is_array( $gallery_caps_override ) ) {
				$gallery_caps = $gallery_caps_override;
			}
		} elseif ( function_exists( 'pfoa_gallery_get_passive_capabilities' ) ) {
			try {
				$reported = pfoa_gallery_get_passive_capabilities();
				if ( is_array( $reported ) ) {
					$gallery_caps = $reported;
				}
			} catch ( Throwable $throwable ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Missing means unavailable.
				$gallery_caps = null;
			}
		}

		$gallery_available = (
			is_array( $gallery_caps )
			&& array_key_exists( 'capabilities_version', $gallery_caps )
		);

		$gallery_ownership = $gallery_available && isset( $gallery_caps['cat_profile_ownership'] ) && is_string( $gallery_caps['cat_profile_ownership'] )
			? strtolower( trim( $gallery_caps['cat_profile_ownership'] ) )
			: '';
		$gallery_satisfied = (
			$gallery_available
			&& array_key_exists( 'ordinary_gallery_available', $gallery_caps )
			&& true === $gallery_caps['ordinary_gallery_available']
			&& array_key_exists( 'cat_profile_gallery_owned', $gallery_caps )
			&& true === $gallery_caps['cat_profile_gallery_owned']
			&& in_array( $gallery_ownership, array( 'neutral', 'theme-neutral', 'theme' ), true )
			&& ( ! array_key_exists( 'photobox_dependency_verified', $gallery_caps ) || true === $gallery_caps['photobox_dependency_verified'] )
		);

		// e) Bundle: incomplete theme bundles are never ready in this cut.
		if ( null !== $bundle_override ) {
			$bundle_complete = ( true === $bundle_override );
		} else {
			$bundle_complete = false;
		}

		// Theme capability declaration override is diagnostic context only.
		$theme_caps = pfoa_theme_get_passive_capabilities();
		if ( is_array( $caps_override ) ) {
			$theme_caps = $caps_override;
		}
		$theme_caps_version = isset( $theme_caps['capabilities_version'] ) ? $theme_caps['capabilities_version'] : null;

		// f) Legacy ownership: guarded probes; unavailable probes => owned (fail-closed).
		$probes_available = (
			function_exists( 'shortcode_exists' )
			&& function_exists( 'has_filter' )
			&& function_exists( 'has_action' )
		);
		if ( ! $probes_available ) {
			$legacy_plugin_owns_output = true;
		} else {
			$legacy_signals = false;
			if ( shortcode_exists( 'pfoa_cat_cards' ) ) {
				$legacy_signals = true;
			}
			if ( false !== has_filter( 'the_content', 'pfoa_cat_filter_adoptable_content' ) ) {
				$legacy_signals = true;
			}
			if ( false !== has_filter( 'the_content', 'pfoa_cat_filter_lifecycle_content' ) ) {
				$legacy_signals = true;
			}
			if ( false !== has_filter( 'template_include', 'pfoa_cat_template_include' ) ) {
				$legacy_signals = true;
			}
			if ( false !== has_action( 'template_redirect', 'pfoa_cat_serve_fragment' ) ) {
				$legacy_signals = true;
			}
			if ( $cat_passive_available && array_key_exists( 'legacy_public_renderers_registered', $cat_caps ) && true === $cat_caps['legacy_public_renderers_registered'] ) {
				$legacy_signals = true;
			}
			$legacy_plugin_owns_output = $legacy_signals;
		}

		// g) Fixed-order, scalar-only result (+ string arrays). Readiness is
		// 'unavailable' on every path in this cut.
		$missing_capabilities      = array();
		$incompatible_capabilities = array();

		if ( 'invalid' === $request_state ) {
			$missing_capabilities[]      = 'request-switch';
			$incompatible_capabilities[] = 'request-invalid';
		}
		if ( ! $cat_neutral_available ) {
			$missing_capabilities[] = 'cat-neutral-schema';
		}
		if ( ! $cat_passive_available ) {
			$missing_capabilities[] = 'cat-passive';
		} elseif ( ! $cat_passive_satisfied ) {
			$incompatible_capabilities[] = 'cat-passive-legacy-mode';
		}
		if ( ! $gallery_available ) {
			$missing_capabilities[] = 'gallery-passive';
		} elseif ( ! $gallery_satisfied ) {
			$incompatible_capabilities[] = 'gallery-external-ownership';
		}
		if ( ! $bundle_complete ) {
			$missing_capabilities[] = 'theme-bundle';
		}
		if ( $legacy_plugin_owns_output ) {
			$incompatible_capabilities[] = 'legacy-plugin-owns-output';
		}
		if ( '1.0' !== $theme_caps_version ) {
			$incompatible_capabilities[] = 'theme-capabilities-version-mismatch';
		}

		$diagnostic_parts   = array();
		$diagnostic_parts[] = 'request=' . $request_state . ' (mode=' . $requested_mode . ')';
		$diagnostic_parts[] = 'cat-neutral=' . ( $cat_neutral_available ? ( $cat_neutral_satisfied ? 'satisfied' : 'present-unsatisfied' ) : 'missing' );
		$diagnostic_parts[] = 'cat-passive=' . ( $cat_passive_available ? ( $cat_passive_satisfied ? 'satisfied' : 'legacy-mode' ) : 'missing' );
		$diagnostic_parts[] = 'gallery=' . ( $gallery_available ? ( $gallery_satisfied ? 'satisfied' : 'external' ) : 'missing' );
		$diagnostic_parts[] = 'bundle=' . ( $bundle_complete ? 'complete' : 'incomplete' );
		$diagnostic_parts[] = $legacy_plugin_owns_output ? 'legacy-plugin-owns-output' : 'legacy-plugin-absent';
		if ( ! empty( $missing_capabilities ) ) {
			$diagnostic_parts[] = 'missing: ' . implode( ', ', $missing_capabilities );
		}
		if ( ! empty( $incompatible_capabilities ) ) {
			$diagnostic_parts[] = 'incompatible: ' . implode( ', ', $incompatible_capabilities );
		}
		$diagnostic_parts[] = 'readiness=unavailable (diagnostic-only; no presentation change)';
		$diagnostic          = implode( '; ', $diagnostic_parts );

		return array(
			'request_state'              => $request_state,
			'requested_mode'             => $requested_mode,
			'cat_neutral_available'      => (bool) $cat_neutral_available,
			'cat_passive_available'      => (bool) $cat_passive_available,
			'cat_neutral_satisfied'      => (bool) $cat_neutral_satisfied,
			'cat_passive_satisfied'      => (bool) $cat_passive_satisfied,
			'gallery_available'          => (bool) $gallery_available,
			'gallery_satisfied'          => (bool) $gallery_satisfied,
			'bundle_complete'            => (bool) $bundle_complete,
			'legacy_plugin_owns_output'  => (bool) $legacy_plugin_owns_output,
			'compat_ready'               => false,
			'current_ready'              => false,
			'readiness'                  => 'unavailable',
			'missing_capabilities'       => $missing_capabilities,
			'incompatible_capabilities'  => $incompatible_capabilities,
			'diagnostic'                 => $diagnostic,
		);
	}
}

if ( ! function_exists( 'pfoa_theme_freeze_presentation_readiness' ) ) {
	/**
	 * Freeze the readiness snapshot request-locally (init:100 target).
	 *
	 * Stores the resolved snapshot in $GLOBALS only if not already set;
	 * the resolver itself never memoizes (pending-not-cached before this
	 * freeze). Performs no rendering, registration, or gating.
	 *
	 * @return array
	 */
	function pfoa_theme_freeze_presentation_readiness() {
		if ( isset( $GLOBALS['pfoa_theme_presentation_readiness'] ) && is_array( $GLOBALS['pfoa_theme_presentation_readiness'] ) ) {
			return $GLOBALS['pfoa_theme_presentation_readiness'];
		}
		$snapshot = pfoa_theme_resolve_presentation_readiness();
		$GLOBALS['pfoa_theme_presentation_readiness'] = $snapshot;
		return $snapshot;
	}
}

if ( ! function_exists( 'pfoa_theme_get_frozen_presentation_readiness' ) ) {
	/**
	 * Return the frozen snapshot, or null when still pending (pre init:100).
	 *
	 * @return array|null
	 */
	function pfoa_theme_get_frozen_presentation_readiness() {
		if ( isset( $GLOBALS['pfoa_theme_presentation_readiness'] ) && is_array( $GLOBALS['pfoa_theme_presentation_readiness'] ) ) {
			return $GLOBALS['pfoa_theme_presentation_readiness'];
		}
		return null;
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'init', 'pfoa_theme_freeze_presentation_readiness', 100 );
}
