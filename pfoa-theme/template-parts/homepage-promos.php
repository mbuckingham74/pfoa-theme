<?php
/**
 * Homepage promotional and informational tiles.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$promo_pages = array(
	pfoa_get_page_by_paths( array( 'potholders-2' ) ),
	pfoa_get_page_by_paths( array( 'wishlist' ) ),
);
?>
<section id="homepage-promos" class="homepage-section homepage-promos" aria-labelledby="homepage-promos-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<h2 id="homepage-promos-title" class="homepage-section__title"><?php esc_html_e( 'More ways to help', 'pfoa-theme' ); ?></h2>
		</header>

		<div class="homepage-promos__grid">
			<article class="homepage-promo homepage-promo--donate">
				<div class="homepage-promo__media" aria-hidden="true"></div>
				<h3 class="homepage-promo__title"><?php esc_html_e( 'Donate', 'pfoa-theme' ); ?></h3>
				<a class="pfoa-text-link" href="<?php echo esc_url( pfoa_get_donation_url() ); ?>">
					<?php esc_html_e( 'Support PFOA', 'pfoa-theme' ); ?>
				</a>
			</article>

			<?php foreach ( $promo_pages as $promo_page ) : ?>
				<?php $promo_url = $promo_page ? get_permalink( $promo_page ) : ''; ?>
				<?php if ( ! $promo_page || ! $promo_url ) : ?>
					<?php continue; ?>
				<?php endif; ?>

				<article class="homepage-promo">
					<div class="homepage-promo__media" aria-hidden="true"></div>
					<h3 class="homepage-promo__title"><?php echo esc_html( get_the_title( $promo_page ) ); ?></h3>
					<a class="pfoa-text-link" href="<?php echo esc_url( $promo_url ); ?>">
						<?php esc_html_e( 'Learn more', 'pfoa-theme' ); ?>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
