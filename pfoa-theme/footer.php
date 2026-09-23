<?php
/**
 * The site footer.
 *
 * Compact footer: public hours, mailing and physical addresses (all
 * Customizer-driven), a map placeholder (destination pending — no external
 * URL, image, or iframe is invented here), and a restrained copyright line.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_hours            = get_theme_mod( 'pfoa_footer_hours', '11:00 am–4:00 pm Tuesday–Saturday, by appointment.' );
$footer_mailing_address  = get_theme_mod( 'pfoa_footer_mailing_address', "P.O. Box 404\nSequim, WA 98382" );
$footer_physical_address = get_theme_mod( 'pfoa_footer_physical_address', "257509 Highway 101\nPort Angeles, WA" );
?>
	<footer id="colophon" class="site-footer site-footer--compact">
		<div class="site-footer__inner">
			<div class="site-footer__grid">
				<section class="site-footer__group" aria-labelledby="footer-hours-title">
					<h2 id="footer-hours-title" class="site-footer__heading"><?php esc_html_e( 'Public Hours', 'pfoa-theme' ); ?></h2>
					<?php if ( $footer_hours ) : ?>
						<p class="site-footer__text"><?php echo nl2br( esc_html( $footer_hours ) ); ?></p>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-mailing-title">
					<h2 id="footer-mailing-title" class="site-footer__heading"><?php esc_html_e( 'Mailing Address', 'pfoa-theme' ); ?></h2>
					<?php if ( $footer_mailing_address ) : ?>
						<address class="site-footer__text site-footer__address"><?php echo nl2br( esc_html( $footer_mailing_address ) ); ?></address>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-physical-title">
					<h2 id="footer-physical-title" class="site-footer__heading"><?php esc_html_e( 'Physical Address', 'pfoa-theme' ); ?></h2>
					<?php if ( $footer_physical_address ) : ?>
						<address class="site-footer__text site-footer__address"><?php echo nl2br( esc_html( $footer_physical_address ) ); ?></address>
					<?php endif; ?>
				</section>

				<section class="site-footer__group" aria-labelledby="footer-map-title">
					<h2 id="footer-map-title" class="site-footer__heading"><?php esc_html_e( 'Map', 'pfoa-theme' ); ?></h2>
					<p class="site-footer__text site-footer__map-note"><?php esc_html_e( 'Map pending.', 'pfoa-theme' ); ?></p>
				</section>
			</div>

			<div class="site-footer__legal-row">
				<div class="site-info">
					<p><?php printf( esc_html__( '© %1$s Peninsula Friends of Animals. All Rights Reserved.', 'pfoa-theme' ), esc_html( wp_date( 'Y' ) ) ); ?></p>
				</div>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
