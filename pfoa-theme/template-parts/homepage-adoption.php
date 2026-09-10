<?php
/**
 * Homepage adoption teaser.
 *
 * The current public animal listing is Page content linked to Popup Maker
 * dialogs, not a queryable collection. Keep this as a destination teaser
 * until an approved animal source exists.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$adoption_page = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
$adoption_url   = $adoption_page ? get_permalink( $adoption_page ) : '';
?>
<section id="homepage-adoption" class="homepage-section homepage-adoption" aria-labelledby="homepage-adoption-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<h2 id="homepage-adoption-title" class="homepage-section__title"><?php esc_html_e( 'Adoption', 'pfoa-theme' ); ?></h2>
		</header>

		<div class="homepage-adoption__teaser">
			<div class="homepage-adoption__media" aria-hidden="true"></div>
			<div class="homepage-adoption__content">
				<?php if ( $adoption_page && $adoption_url ) : ?>
					<h3 class="homepage-adoption__title"><?php echo esc_html( get_the_title( $adoption_page ) ); ?></h3>
					<a class="pfoa-text-link" href="<?php echo esc_url( $adoption_url ); ?>">
						<?php esc_html_e( 'View adoption listings', 'pfoa-theme' ); ?>
					</a>
				<?php else : ?>
					<h3 class="homepage-adoption__title"><?php esc_html_e( 'Adoption listings', 'pfoa-theme' ); ?></h3>
					<p class="homepage-empty-state"><?php esc_html_e( 'This area is ready for an approved adoption content source.', 'pfoa-theme' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
