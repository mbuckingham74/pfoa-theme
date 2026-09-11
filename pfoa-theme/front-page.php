<?php
/**
 * The front page template.
 *
 * The structural homepage regions are intentionally content-light. The
 * assigned static front Page remains the loop context for the homepage, but
 * its stored editor body is not rendered here; ordinary Page templates retain
 * the normal the_content() pipeline.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="site-main front-page-content">
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php get_template_part( 'template-parts/homepage', 'hero' ); ?>

			<?php get_template_part( 'template-parts/homepage', 'pathways' ); ?>
			<?php get_template_part( 'template-parts/homepage', 'adoption' ); ?>
			<?php get_template_part( 'template-parts/homepage', 'news-events' ); ?>
			<?php get_template_part( 'template-parts/homepage', 'promos' ); ?>
			<?php get_template_part( 'template-parts/homepage', 'partners' ); ?>
		<?php endwhile; ?>
	<?php else : ?>
		<div class="content-shell">
			<?php get_template_part( 'template-parts/content', 'none' ); ?>
		</div>
	<?php endif; ?>
</main>
<?php
get_footer();
