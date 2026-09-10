<?php
/**
 * Pagination for post collections.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="posts-navigation">
	<?php
	the_posts_pagination(
		array(
			'mid_size'           => 2,
			'prev_text'          => esc_html__( 'Previous', 'pfoa-theme' ),
			'next_text'          => esc_html__( 'Next', 'pfoa-theme' ),
			'screen_reader_text' => esc_html__( 'Posts navigation', 'pfoa-theme' ),
		)
	);
	?>
</div>
