<?php
/**
 * Homepage primary action pathways.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$volunteering_page = pfoa_get_page_by_paths( array( 'volunteering' ) );
$adoption_page     = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
$pathways          = array(
	array(
		'key'   => 'adopt',
		'label' => __( 'Adopt', 'pfoa-theme' ),
		'url'   => $adoption_page ? get_permalink( $adoption_page ) : '',
	),
	array(
		'key'   => 'foster',
		'label' => __( 'Foster', 'pfoa-theme' ),
		'url'   => $volunteering_page ? get_permalink( $volunteering_page ) : '',
	),
	array(
		'key'   => 'donate',
		'label' => __( 'Donate', 'pfoa-theme' ),
		'url'   => pfoa_get_homepage_donation_url(),
	),
	array(
		'key'   => 'volunteer',
		'label' => __( 'Volunteer', 'pfoa-theme' ),
		'url'   => $volunteering_page ? get_permalink( $volunteering_page ) : '',
	),
);
?>
<section id="homepage-pathways" class="homepage-section homepage-pathways" aria-labelledby="homepage-pathways-title">
	<div class="content-container">
		<header class="homepage-section__header homepage-section__header--light">
			<h2 id="homepage-pathways-title" class="homepage-section__title"><?php esc_html_e( 'Ways to help', 'pfoa-theme' ); ?></h2>
		</header>

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
