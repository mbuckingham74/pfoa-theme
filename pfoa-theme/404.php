<?php
/**
 * The template for displaying 404 pages.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="site-main">
	<div class="content-shell">
		<section class="not-found" aria-labelledby="page-title">
			<header class="page-header">
				<h1 id="page-title" class="page-title"><?php esc_html_e( 'Page not found', 'pfoa-theme' ); ?></h1>
			</header>
			<p><?php esc_html_e( 'The page you requested could not be found. Try a search or return to the homepage.', 'pfoa-theme' ); ?></p>
			<?php get_search_form(); ?>
			<p>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<?php esc_html_e( 'Return to the homepage', 'pfoa-theme' ); ?>
				</a>
			</p>
		</section>
	</div>
</main>
<?php
get_footer();
