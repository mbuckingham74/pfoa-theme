<?php
/**
 * The site footer.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$footer_site_name        = get_bloginfo( 'name' );
$footer_description      = get_bloginfo( 'description' );
$footer_hours            = get_theme_mod( 'pfoa_footer_hours', '11:00 am–4:00 pm Tuesday–Saturday, by appointment.' );
$footer_mailing_address  = get_theme_mod( 'pfoa_footer_mailing_address', "P.O. Box 404\nSequim, WA 98382" );
$footer_physical_address = get_theme_mod( 'pfoa_footer_physical_address', "257509 Highway 101\nPort Angeles, WA" );
$footer_phone            = get_theme_mod( 'pfoa_footer_phone', '(360) 452-0414' );
$footer_fax              = get_theme_mod( 'pfoa_footer_fax', '(360) 452-0412' );
$footer_phone_href       = preg_replace( '/[^0-9+]/', '', $footer_phone );
$footer_facebook_url     = get_theme_mod( 'pfoa_footer_facebook_url', '' );
$footer_instagram_url    = get_theme_mod( 'pfoa_footer_instagram_url', '' );
$has_explore_menu        = has_nav_menu( 'footer' );
$has_support_menu        = has_nav_menu( 'footer_support' );
$has_legal_menu          = has_nav_menu( 'footer_legal' );
?>
	<footer id="colophon" class="site-footer">
		<div class="site-footer__inner">
			<div class="site-footer__grid">
				<section class="site-footer__group site-footer__identity" aria-labelledby="footer-identity-title">
					<h2 id="footer-identity-title" class="screen-reader-text">
						<?php
						printf(
							esc_html__( '%s identity', 'pfoa-theme' ),
							esc_html( $footer_site_name )
						);
						?>
					</h2>
					<div class="site-footer__brand">
						<?php
						if ( has_custom_logo() ) {
							the_custom_logo();
						} else {
							printf(
								'<a class="site-footer__title" href="%1$s" rel="home">%2$s</a>',
								esc_url( home_url( '/' ) ),
								esc_html( $footer_site_name )
							);
						}
						?>
					</div>
					<?php if ( $footer_description ) : ?>
						<p class="site-footer__description"><?php echo esc_html( $footer_description ); ?></p>
					<?php endif; ?>
				</section>

				<?php if ( $has_explore_menu ) : ?>
					<section class="site-footer__group site-footer__navigation-group" aria-labelledby="footer-navigation-title">
						<h2 id="footer-navigation-title" class="site-footer__heading"><?php esc_html_e( 'Explore PFOA', 'pfoa-theme' ); ?></h2>
						<nav class="site-footer__navigation" aria-labelledby="footer-navigation-title">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer',
									'menu_id'       => 'footer-menu',
									'menu_class'    => 'footer-menu',
									'container'     => false,
									'fallback_cb'   => false,
									'depth'         => 2,
								)
							);
							?>
						</nav>
					</section>
				<?php endif; ?>

				<section class="site-footer__group site-footer__contact-group" aria-labelledby="footer-contact-title">
					<h2 id="footer-contact-title" class="site-footer__heading"><?php esc_html_e( 'Visit & contact', 'pfoa-theme' ); ?></h2>
					<address class="site-footer__contact">
						<?php if ( $footer_mailing_address ) : ?>
							<p>
								<strong><?php esc_html_e( 'Mailing address', 'pfoa-theme' ); ?></strong>
								<span><?php echo nl2br( esc_html( $footer_mailing_address ) ); ?></span>
							</p>
						<?php endif; ?>

						<?php if ( $footer_physical_address ) : ?>
							<p>
								<strong><?php esc_html_e( 'Visit us', 'pfoa-theme' ); ?></strong>
								<span><?php echo nl2br( esc_html( $footer_physical_address ) ); ?></span>
							</p>
						<?php endif; ?>

						<?php if ( $footer_hours ) : ?>
							<p>
								<strong><?php esc_html_e( 'Hours', 'pfoa-theme' ); ?></strong>
								<span><?php echo nl2br( esc_html( $footer_hours ) ); ?></span>
							</p>
						<?php endif; ?>

						<?php if ( $footer_phone ) : ?>
							<p>
								<strong><?php esc_html_e( 'Phone', 'pfoa-theme' ); ?></strong>
								<?php if ( $footer_phone_href ) : ?>
									<a href="<?php echo esc_attr( 'tel:' . $footer_phone_href ); ?>"><?php echo esc_html( $footer_phone ); ?></a>
								<?php else : ?>
									<span><?php echo esc_html( $footer_phone ); ?></span>
								<?php endif; ?>
							</p>
						<?php endif; ?>

						<?php if ( $footer_fax ) : ?>
							<p>
								<strong><?php esc_html_e( 'Fax', 'pfoa-theme' ); ?></strong>
								<span><?php echo esc_html( $footer_fax ); ?></span>
							</p>
						<?php endif; ?>
					</address>
				</section>

				<section class="site-footer__group site-footer__support-group" aria-labelledby="footer-support-title">
					<h2 id="footer-support-title" class="site-footer__heading"><?php esc_html_e( 'Connect & support', 'pfoa-theme' ); ?></h2>
					<p class="site-footer__support-copy"><?php esc_html_e( 'Help care for animals in our community.', 'pfoa-theme' ); ?></p>
					<a class="site-footer__donate pfoa-button" href="<?php echo esc_url( pfoa_get_donation_url() ); ?>">
						<?php esc_html_e( 'Donate to PFOA', 'pfoa-theme' ); ?>
					</a>

					<?php if ( $has_support_menu ) : ?>
						<nav class="site-footer__support-navigation" aria-label="<?php esc_attr_e( 'Ways to help', 'pfoa-theme' ); ?>">
							<?php
							wp_nav_menu(
								array(
									'theme_location' => 'footer_support',
									'menu_id'       => 'footer-support-menu',
									'menu_class'    => 'footer-menu',
									'container'     => false,
									'fallback_cb'   => false,
									'depth'         => 2,
								)
							);
							?>
						</nav>
					<?php endif; ?>

					<?php if ( $footer_facebook_url || $footer_instagram_url ) : ?>
						<nav class="site-footer__social-navigation" aria-label="<?php esc_attr_e( 'Social links', 'pfoa-theme' ); ?>">
							<ul class="site-footer__social">
								<?php if ( $footer_facebook_url ) : ?>
									<li><a class="site-footer__social-link" href="<?php echo esc_url( $footer_facebook_url ); ?>"><?php esc_html_e( 'Facebook', 'pfoa-theme' ); ?></a></li>
								<?php endif; ?>
								<?php if ( $footer_instagram_url ) : ?>
									<li><a class="site-footer__social-link" href="<?php echo esc_url( $footer_instagram_url ); ?>"><?php esc_html_e( 'Instagram', 'pfoa-theme' ); ?></a></li>
								<?php endif; ?>
							</ul>
						</nav>
					<?php endif; ?>
				</section>
			</div>

			<div class="site-footer__legal-row">
				<div class="site-info">
					<p>
						<span aria-hidden="true">©</span>
						<?php echo esc_html( wp_date( 'Y' ) ); ?>
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php echo esc_html( $footer_site_name ); ?></a>
					</p>
				</div>

				<?php if ( $has_legal_menu ) : ?>
					<nav class="site-footer__legal-navigation" aria-label="<?php esc_attr_e( 'Legal and site links', 'pfoa-theme' ); ?>">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer_legal',
								'menu_id'       => 'footer-legal-menu',
								'menu_class'    => 'footer-menu site-footer__legal-menu',
								'container'     => false,
								'fallback_cb'   => false,
								'depth'         => 1,
							)
						);
						?>
					</nav>
				<?php endif; ?>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
