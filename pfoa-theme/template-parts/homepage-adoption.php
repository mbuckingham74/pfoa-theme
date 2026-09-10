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
$adoption_title = $adoption_page ? get_the_title( $adoption_page ) : '';
$adoption_title = $adoption_title ? $adoption_title : __( 'Adoption information', 'pfoa-theme' );
?>
<section id="homepage-adoption" class="homepage-section homepage-adoption" aria-labelledby="homepage-adoption-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<p class="homepage-section__eyebrow"><?php esc_html_e( 'Find a companion', 'pfoa-theme' ); ?></p>
			<h2 id="homepage-adoption-title" class="homepage-section__title"><?php esc_html_e( 'Adoption', 'pfoa-theme' ); ?></h2>
			<p class="homepage-section__intro"><?php esc_html_e( 'Explore PFOA\'s adoption information and the listings maintained by the rescue.', 'pfoa-theme' ); ?></p>
		</header>

		<?php if ( $adoption_url ) : ?>
			<a class="homepage-adoption__teaser homepage-adoption__link" href="<?php echo esc_url( $adoption_url ); ?>">
		<?php else : ?>
			<div class="homepage-adoption__teaser homepage-adoption__teaser--unavailable">
		<?php endif; ?>
			<div class="homepage-adoption__media" aria-hidden="true">
				<span class="homepage-adoption__media-kicker"><?php esc_html_e( 'PFOA', 'pfoa-theme' ); ?></span>
				<span class="homepage-adoption__media-word"><?php esc_html_e( 'Adopt', 'pfoa-theme' ); ?></span>
				<span class="homepage-adoption__media-note"><?php esc_html_e( 'Adoption starts here', 'pfoa-theme' ); ?></span>
			</div>
			<div class="homepage-adoption__content">
				<p class="homepage-card__eyebrow"><?php esc_html_e( 'Adoption listings', 'pfoa-theme' ); ?></p>
				<h3 class="homepage-adoption__title"><?php echo esc_html( $adoption_title ); ?></h3>
				<p class="homepage-adoption__description">
					<?php esc_html_e( 'Start with the adoption page for the information and individual details PFOA has made available.', 'pfoa-theme' ); ?>
				</p>
				<?php if ( $adoption_url ) : ?>
					<span class="pfoa-text-link homepage-adoption__action">
						<?php esc_html_e( 'Explore adoption listings', 'pfoa-theme' ); ?>
					</span>
				<?php else : ?>
					<p class="homepage-empty-state"><?php esc_html_e( 'Adoption information is not currently available.', 'pfoa-theme' ); ?></p>
				<?php endif; ?>
			</div>
		<?php if ( $adoption_url ) : ?>
			</a>
		<?php else : ?>
			</div>
		<?php endif; ?>
	</div>
</section>
