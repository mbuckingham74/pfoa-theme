<?php
/**
 * Template Name: PFOA Page Compatibility
 * Template Post Type: page
 *
 * A deliberately small Page template for Pages that retain a historical
 * template filename. It uses the ordinary WordPress Page loop and content
 * filters so existing embeds, galleries, links, and plugin output can run.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<main id="primary" class="site-main">
	<div class="content-shell page-content-compatibility">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'page-content' ); ?>>
				<header class="entry-header">
					<h1 class="entry-title"><?php the_title(); ?></h1>
				</header>

				<div class="entry-content">
					<?php
					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Page', 'pfoa-theme' ) . '">',
							'after'  => '</nav>',
						)
					);
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
