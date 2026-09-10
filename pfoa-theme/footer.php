<?php
/**
 * The site footer.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
	<footer id="colophon" class="site-footer">
		<div class="site-footer__inner">
			<nav class="footer-navigation" aria-label="<?php esc_attr_e( 'Footer menu', 'pfoa-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'footer',
						'menu_id'       => 'footer-menu',
						'menu_class'    => 'footer-menu',
						'container'     => false,
						'fallback_cb'   => false,
					)
				);
				?>
			</nav>

			<div class="site-info">
				<p>
					<?php
					printf(
						esc_html__( '© %1$s %2$s', 'pfoa-theme' ),
						esc_html( wp_date( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</p>
			</div>
		</div>
	</footer>
</div>
<?php wp_footer(); ?>
</body>
</html>
