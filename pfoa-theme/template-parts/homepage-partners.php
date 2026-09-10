<?php
/**
 * Homepage partners placeholder.
 *
 * Partner media and outbound destinations require an approved current roster;
 * the structure is present without inventing organizations or logos.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$partners_page = pfoa_get_page_by_paths( array( 'businesspartners' ) );
$partners_url   = $partners_page ? get_permalink( $partners_page ) : '';
?>
<section id="homepage-partners" class="homepage-section homepage-partners" aria-labelledby="homepage-partners-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<h2 id="homepage-partners-title" class="homepage-section__title"><?php esc_html_e( 'Partners and supporters', 'pfoa-theme' ); ?></h2>
		</header>

		<div class="homepage-partners__grid">
			<p class="homepage-empty-state"><?php esc_html_e( 'Approved partner logos and links will be added here when the roster is confirmed.', 'pfoa-theme' ); ?></p>
		</div>

		<?php if ( $partners_page && $partners_url ) : ?>
			<p class="homepage-partners__link-wrap">
				<a class="pfoa-text-link" href="<?php echo esc_url( $partners_url ); ?>">
					<?php echo esc_html( get_the_title( $partners_page ) ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
