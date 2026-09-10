<?php
/**
 * Template Name: PFOA Page Compatibility
 * Template Post Type: page
 *
 * A deliberately small Page template for Pages that retain a historical
 * template filename. It uses the ordinary WordPress Page loop and delegates
 * the presentation to the shared Page content part so existing embeds,
 * galleries, links, and plugin output can run unchanged. That shared part
 * owns the title, the_content(), and Page-link rendering.
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
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part(
				'template-parts/content',
				'page',
				array(
					'legacy_compatibility' => true,
				)
			);
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
