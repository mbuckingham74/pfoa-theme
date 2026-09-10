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
			</nav>

			<a class="site-header__donate" href="<?php echo esc_url( pfoa_get_donation_url() ); ?>">
				<?php esc_html_e( 'Donate', 'pfoa-theme' ); ?>
			</a>

			<button class="menu-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" data-open-label="<?php esc_attr_e( 'Open primary menu', 'pfoa-theme' ); ?>" data-close-label="<?php esc_attr_e( 'Close primary menu', 'pfoa-theme' ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Open primary menu', 'pfoa-theme' ); ?></span>
				<span class="menu-toggle__icon" aria-hidden="true"></span>
			</button>
		</div>
	</header>
