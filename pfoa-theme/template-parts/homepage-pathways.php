<?php
/**
 * Homepage primary action pathways.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$adoptable_cats_page = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
$home_front_page     = pfoa_get_page_by_paths( array( 'news-announcements', 'fromthehomefront-2' ) );
$potholders_page     = pfoa_get_page_by_paths( array( 'potholders-2' ) );
$pet_tidings_page    = pfoa_get_page_by_paths( array( 'pettidings' ) );
$wishlist_page       = pfoa_get_page_by_paths( array( 'wishlist' ) );
$pathways            = array(
	array(
		'key'   => 'adoptable-cats',
		'label' => __( 'Adoptable Cats', 'pfoa-theme' ),
		'url'   => $adoptable_cats_page ? get_permalink( $adoptable_cats_page ) : '',
	),
	array(
		'key'   => 'home-front',
		'label' => __( 'From the Home Front', 'pfoa-theme' ),
		'url'   => $home_front_page ? get_permalink( $home_front_page ) : '',
	),
	array(
		'key'   => 'potholders',
		'label' => __( 'Pot Holders', 'pfoa-theme' ),
		'url'   => $potholders_page ? get_permalink( $potholders_page ) : '',
	),
	array(
		'key'   => 'pet-tidings',
		'label' => __( 'Pet Tidings', 'pfoa-theme' ),
		'url'   => $pet_tidings_page ? get_permalink( $pet_tidings_page ) : '',
	),
	array(
		'key'   => 'wishlist',
		'label' => __( 'Wish List', 'pfoa-theme' ),
		'url'   => $wishlist_page ? get_permalink( $wishlist_page ) : '',
	),
);
?>
<section id="homepage-pathways" class="homepage-section homepage-pathways">
	<div class="content-container">

		<ul class="homepage-pathways__grid">
			<?php foreach ( $pathways as $pathway ) : ?>
				<li class="homepage-pathways__item">
					<?php if ( $pathway['url'] ) : ?>
						<a class="homepage-pathway homepage-pathway--<?php echo esc_attr( $pathway['key'] ); ?> homepage-pathway__link" href="<?php echo esc_url( $pathway['url'] ); ?>">
					<?php else : ?>
						<div class="homepage-pathway homepage-pathway--<?php echo esc_attr( $pathway['key'] ); ?> homepage-pathway--unavailable">
					<?php endif; ?>

						<span class="homepage-pathway__media" aria-hidden="true">
							<span class="homepage-pathway__media-mark"><?php echo esc_html( substr( $pathway['label'], 0, 1 ) ); ?></span>
						</span>
						<h3 class="homepage-pathway__title"><?php echo esc_html( $pathway['label'] ); ?></h3>
						<?php if ( $pathway['url'] ) : ?>
							<span class="homepage-pathway__action">
								<span><?php esc_html_e( 'Explore', 'pfoa-theme' ); ?></span>
								<span class="homepage-pathway__arrow" aria-hidden="true">→</span>
							</span>
						<?php else : ?>
							<span class="homepage-pathway__status"><?php esc_html_e( 'Destination to be configured', 'pfoa-theme' ); ?></span>
						<?php endif; ?>

					<?php if ( $pathway['url'] ) : ?>
						</a>
					<?php else : ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
