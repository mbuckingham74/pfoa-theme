<?php
/**
 * Search result summary.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result_permalink = get_permalink();
$result_title     = get_the_title();
$result_excerpt   = get_the_excerpt();
?>
<article id="post-<?php echo esc_attr( get_the_ID() ); ?>" <?php post_class( 'search-result' ); ?>>
	<header class="entry-header">
		<h2 class="entry-title">
			<a href="<?php echo esc_url( $result_permalink ); ?>">
				<?php echo esc_html( $result_title ); ?>
			</a>
		</h2>
	</header>

	<div class="entry-summary">
		<?php echo wp_kses_post( $result_excerpt ); ?>
	</div>

	<footer class="entry-footer">
		<a href="<?php echo esc_url( $result_permalink ); ?>">
			<?php esc_html_e( 'Read more', 'pfoa-theme' ); ?>
		</a>
	</footer>
</article>
