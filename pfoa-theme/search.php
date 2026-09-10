<?php
/**
 * The template for displaying search results.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$search_query = get_search_query();

get_header();
?>
<main id="primary" class="site-main">
	<div class="content-shell">
		<header class="page-header">
			<h1 class="page-title">
				<?php
				printf(
					esc_html__( 'Search results for: %s', 'pfoa-theme' ),
					esc_html( $search_query )
				);
				?>
			</h1>
			<?php get_search_form(); ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content', 'search' );
				endwhile;
				?>
			</div>

			<?php get_template_part( 'template-parts/pagination' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
