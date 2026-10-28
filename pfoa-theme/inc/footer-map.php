<?php
/**
 * Shared footer map configuration and asset loading.
 *
 * Configure a public pk. token through wp-config.php (PFOA_MAPBOX_PUBLIC_TOKEN)
 * or the pfoa_mapbox_public_token WordPress option. Never accept secret tokens.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Return only a browser-safe public token. */
function pfoa_footer_map_public_token() {
	$token = defined( 'PFOA_MAPBOX_PUBLIC_TOKEN' ) ? PFOA_MAPBOX_PUBLIC_TOKEN : get_option( 'pfoa_mapbox_public_token', '' );
	return is_string( $token ) && 0 === strpos( trim( $token ), 'pk.' ) ? trim( $token ) : '';
}

/** Enqueue the small loader; Mapbox JavaScript is fetched near the footer. */
function pfoa_footer_map_enqueue() {
	$uri     = get_template_directory_uri();
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'pfoa-footer-map', $uri . '/assets/css/footer-map.css', array( 'pfoa-style' ), $version );
	if ( ! pfoa_footer_map_public_token() ) {
		return;
	}
	wp_enqueue_style( 'pfoa-mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.33.0/mapbox-gl.css', array(), '3.33.0' );
	wp_enqueue_script( 'pfoa-footer-map', $uri . '/assets/js/footer-map.js', array(), $version, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	wp_add_inline_script(
		'pfoa-footer-map',
		'window.pfoaFooterMap = ' . wp_json_encode(
			array(
				'scriptUrl'   => 'https://api.mapbox.com/mapbox-gl-js/v3.33.0/mapbox-gl.js',
				'unavailable' => __( 'Interactive map unavailable. Use the address and directions below.', 'pfoa-theme' ),
				'mapLabel'    => __( 'Safe Haven map. Use arrow keys to pan and plus or minus to zoom.', 'pfoa-theme' ),
				'markerLabel' => __( 'Safe Haven: show address and directions', 'pfoa-theme' ),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'pfoa_footer_map_enqueue', 20 );
