<?php
/**
 * Generic Page content.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$legacy_compatibility = ! empty( $args['legacy_compatibility'] );
$page_classes         = 'page-content' . ( $legacy_compatibility ? ' page-content-compatibility' : '' );
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( $page_classes ); ?>>
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
