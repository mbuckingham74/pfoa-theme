<?php
/**
 * The site footer.
 *
 * Compact footer: public hours, mailing and physical addresses (all
 * editable via PFOA Site > Footer, stored in the pfoa_footer option), a lazy
 * interactive Safe Haven map, and a restrained copyright line.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer = function_exists( 'pfoa_get_footer' ) ? pfoa_get_footer() : array();

$footer_hours_heading    = isset( $footer['public_hours']['heading'] ) ? $footer['public_hours']['heading'] : __( 'Public Hours', 'pfoa-theme' );
$footer_hours            = isset( $footer['public_hours']['body'] ) ? $footer['public_hours']['body'] : __( '11:00 am–4:00 pm Tuesday–Saturday, by appointment.', 'pfoa-theme' );
$footer_mailing_heading  = isset( $footer['mailing_address']['heading'] ) ? $footer['mailing_address']['heading'] : __( 'Mailing Address', 'pfoa-theme' );
$footer_mailing_address  = isset( $footer['mailing_address']['body'] ) ? $footer['mailing_address']['body'] : __( "P.O. Box 404\nSequim, WA 98382", 'pfoa-theme' );
$footer_physical_heading = isset( $footer['physical_address']['heading'] ) ? $footer['physical_address']['heading'] : __( 'Physical Address', 'pfoa-theme' );
$footer_physical_address = isset( $footer['physical_address']['body'] ) ? $footer['physical_address']['body'] : __( "257509 Hwy 101\nPort Angeles, WA", 'pfoa-theme' );
$footer_map_heading      = isset( $footer['map']['heading'] ) ? $footer['map']['heading'] : __( 'Map', 'pfoa-theme' );
$footer_map_token        = pfoa_footer_map_public_token();
$footer_directions       = 'https://www.google.com/maps/dir/?api=1&destination=48.0928417%2C-123.2492278';
$footer_copyright        = isset( $footer['copyright_text'] ) ? $footer['copyright_text'] : __( 'Peninsula Friends of Animals. All Rights Reserved.', 'pfoa-theme' );
?>
	<footer id="colophon" class="site-footer site-footer--compact">
		<div class="site-footer__inner">
			<div class="site-footer__grid">
				<section class="site-footer__group" aria-labelledby="footer-hours-title">
					<h2 id="footer-hours-title" class="site-footer__heading"><?php echo esc_html( $footer_hours_heading ); ?></h2>
					<?php if ( $footer_hours ) : ?>
						<p class="site-footer__text"><?php echo nl2br( esc_html( $footer_hours ) ); ?></p>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-mailing-title">
					<h2 id="footer-mailing-title" class="site-footer__heading"><?php echo esc_html( $footer_mailing_heading ); ?></h2>
					<?php if ( $footer_mailing_address ) : ?>
						<address class="site-footer__text site-footer__address"><?php echo nl2br( esc_html( $footer_mailing_address ) ); ?></address>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-physical-title">
					<h2 id="footer-physical-title" class="site-footer__heading"><?php echo esc_html( $footer_physical_heading ); ?></h2>
					<?php if ( $footer_physical_address ) : ?>
						<address class="site-footer__text site-footer__address"><?php echo nl2br( esc_html( $footer_physical_address ) ); ?></address>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-map-title">
					<h2 id="footer-map-title" class="site-footer__heading"><?php echo esc_html( $footer_map_heading ); ?></h2>
					<div class="pfoa-footer-map" data-pfoa-footer-map
						data-token="<?php echo esc_attr( $footer_map_token ); ?>"
						data-longitude="-123.2492278" data-latitude="48.0928417">
						<div class="pfoa-footer-map__canvas" role="region"
							aria-label="<?php esc_attr_e( 'Interactive map showing Safe Haven', 'pfoa-theme' ); ?>" hidden></div>
						<p class="pfoa-footer-map__status site-footer__text" role="status" hidden></p>
						<div class="pfoa-footer-map__details site-footer__text">
							<strong><?php esc_html_e( 'Safe Haven - Peninsula Friends of Animals', 'pfoa-theme' ); ?></strong>
							<address><?php esc_html_e( '257509 Highway 101', 'pfoa-theme' ); ?><br><?php esc_html_e( 'Port Angeles, WA', 'pfoa-theme' ); ?></address>
							<a href="<?php echo esc_url( $footer_directions ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Get Directions', 'pfoa-theme' ); ?><span class="screen-reader-text"><?php esc_html_e( ' (opens in a new tab)', 'pfoa-theme' ); ?></span></a>
						</div>
					</div>
				</section>
			</div>

			<div class="site-footer__legal-row">
				<div class="site-info">
					<p><?php printf( esc_html__( '© %1$s %2$s', 'pfoa-theme' ), esc_html( wp_date( 'Y' ) ), esc_html( $footer_copyright ) ); ?></p>
				</div>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
