<?php
/**
 * Assigned front Page editor content.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section id="homepage-editorial" class="homepage-section homepage-editorial" aria-labelledby="homepage-editorial-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<h2 id="homepage-editorial-title" class="homepage-section__title"><?php esc_html_e( 'Homepage content', 'pfoa-theme' ); ?></h2>
		</header>

		<article id="post-<?php the_ID(); ?>" <?php post_class( 'homepage-editorial__article page-content-compatibility' ); ?>>
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
	</div>
</section>
