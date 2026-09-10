<?php
/**
 * Homepage hero structure.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_hero_image = has_post_thumbnail();
$hero_classes   = array( 'homepage-section', 'homepage-hero' );

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
		</div>
	<?php endif; ?>

	<div class="wide-container homepage-hero__inner">
		<div class="homepage-hero__content">
			<p class="homepage-hero__site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 id="homepage-hero-title" class="homepage-hero__title"><?php echo esc_html( get_the_title() ); ?></h1>
		</div>
	</div>
</section>
