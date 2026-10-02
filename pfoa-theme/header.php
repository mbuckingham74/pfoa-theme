<?php
/**
 * The site header.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'no-js' ); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#primary"><?php esc_html_e( 'Skip to content', 'pfoa-theme' ); ?></a>
<div id="page" class="site">
	<header id="masthead" class="site-header">
		<?php if ( has_nav_menu( 'header_utility' ) ) : ?>
		<div class="site-header__utility">
			<nav class="header-utility-navigation" aria-label="<?php esc_attr_e( 'Utility menu', 'pfoa-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'header_utility',
						'menu_id'        => 'header-utility-menu',
						'menu_class'     => 'utility-menu',
						'container'      => false,
						'fallback_cb'    => false,
						'depth'          => 1,
					)
				);
				?>
			</nav>
		</div>
		<?php endif; ?>
		<div class="site-header__inner">
			<div class="site-branding">
				<?php
				if ( has_custom_logo() ) {
					the_custom_logo();
				} else {
					printf(
						'<a class="site-title" href="%1$s" rel="home">%2$s</a>',
						esc_url( home_url( '/' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
				}
				?>
			</div>

			<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary menu', 'pfoa-theme' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'menu_id'       => 'primary-menu',
						'menu_class'    => 'primary-menu',
						'container'     => false,
						'fallback_cb'   => 'pfoa_primary_menu_fallback',
						'walker'        => new PFOA_Navigation_Walker(),
					)
				);
				?>
				<?php if ( has_nav_menu( 'header_utility' ) ) : ?>
				<div class="site-header__utility--mobile">
					<nav class="header-utility-navigation" aria-label="<?php esc_attr_e( 'Utility menu', 'pfoa-theme' ); ?>">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'header_utility',
								'menu_id'        => 'header-utility-menu-mobile',
								'menu_class'     => 'utility-menu',
								'container'      => false,
								'fallback_cb'    => false,
								'depth'          => 1,
							)
						);
						?>
					</nav>
				</div>
				<?php endif; ?>
			</nav>

			<?php
			$pfoa_header_donate_label = __( 'Donate', 'pfoa-theme' );
			$pfoa_header_donate_url   = pfoa_get_donation_url();

			if ( function_exists( 'pfoa_get_header' ) ) {
				$pfoa_header_settings = pfoa_get_header();

				if ( isset( $pfoa_header_settings['donate']['label'] ) && '' !== trim( (string) $pfoa_header_settings['donate']['label'] ) ) {
					$pfoa_header_donate_label = $pfoa_header_settings['donate']['label'];
				}

				if ( isset( $pfoa_header_settings['donate']['url'] ) && '' !== trim( (string) $pfoa_header_settings['donate']['url'] ) ) {
					$pfoa_header_donate_url = $pfoa_header_settings['donate']['url'];
				}
			}
			?>
			<a class="site-header__donate" href="<?php echo esc_url( $pfoa_header_donate_url ); ?>">
				<?php echo esc_html( $pfoa_header_donate_label ); ?>
			</a>

			<button class="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" data-open-label="<?php esc_attr_e( 'Open primary menu', 'pfoa-theme' ); ?>" data-close-label="<?php esc_attr_e( 'Close primary menu', 'pfoa-theme' ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Open primary menu', 'pfoa-theme' ); ?></span>
				<span class="menu-toggle__icon" aria-hidden="true"></span>
			</button>
		</div>
	</header>
