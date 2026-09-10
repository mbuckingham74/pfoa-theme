<?php
/**
 * Homepage hero structure.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_hero_image    = has_post_thumbnail();
$hero_classes      = array( 'homepage-section', 'homepage-hero' );
$hero_title        = get_the_title();
$hero_title        = $hero_title ? $hero_title : get_bloginfo( 'name' );
$adoption_page     = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
$volunteering_page = pfoa_get_page_by_paths( array( 'volunteering' ) );
$adoption_url      = $adoption_page ? get_permalink( $adoption_page ) : '';
$volunteering_url  = $volunteering_page ? get_permalink( $volunteering_page ) : '';
$hero_primary_url    = $adoption_url ? $adoption_url : $volunteering_url;
$hero_primary_label  = $adoption_url ? __( 'Adopt', 'pfoa-theme' ) : __( 'Volunteer', 'pfoa-theme' );
$hero_secondary_url  = $adoption_url ? $volunteering_url : '';

if ( ! $has_hero_image ) {
	$hero_classes[] = 'homepage-hero--no-image';
}
?>
<section id="homepage-hero" class="<?php echo esc_attr( implode( ' ', $hero_classes ) ); ?>" aria-labelledby="homepage-hero-title">
	<?php if ( $has_hero_image ) : ?>
		<div class="homepage-hero__media">
			<?php
				the_post_thumbnail(
					'full',
					array(
						'class'    => 'homepage-hero__image',
						'loading'  => 'eager',
						'decoding' => 'async',
					)
				);
			?>
			<span class="homepage-hero__scrim" aria-hidden="true"></span>
		</div>
	<?php endif; ?>

	<div class="wide-container homepage-hero__inner">
		<div class="homepage-hero__content">
			<p class="homepage-hero__site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 id="homepage-hero-title" class="homepage-hero__title"><?php echo esc_html( $hero_title ); ?></h1>

			<?php if ( $hero_primary_url ) : ?>
				<div class="homepage-hero__actions">
					<a class="pfoa-button homepage-hero__primary" href="<?php echo esc_url( $hero_primary_url ); ?>">
						<?php echo esc_html( $hero_primary_label ); ?>
					</a>

					<?php if ( $hero_secondary_url ) : ?>
						<a class="pfoa-button homepage-hero__secondary" href="<?php echo esc_url( $hero_secondary_url ); ?>">
							<?php esc_html_e( 'Volunteer', 'pfoa-theme' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
