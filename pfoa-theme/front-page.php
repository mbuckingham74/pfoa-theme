<?php
/**
 * The front page template.
 *
 * The structural homepage regions are intentionally content-light. Existing
 * Page content is rendered in its own section so the assigned static front
 * Page remains editable and its legacy filters/plugins continue to run.
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

			<?php if ( trim( (string) get_the_content() ) ) : ?>
				<?php get_template_part( 'template-parts/homepage', 'editorial' ); ?>
			<?php endif; ?>

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
