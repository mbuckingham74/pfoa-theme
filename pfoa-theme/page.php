<?php
/**
 * The template for displaying all Pages.
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
					'legacy_compatibility' => false,
				)
			);
		endwhile;
		?>
	</div>
</main>
<?php
get_footer();
