<?php
/**
 * Homepage primary action pathways.
 *
 * Cards come from the Homepage Cards admin page (Appearance > Homepage
 * Cards); when nothing has been saved, the theme-owned defaults apply.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pathways = pfoa_get_homepage_cards();
?>
<section id="homepage-pathways" class="homepage-section homepage-pathways">
	<div class="content-container">

		<ul class="homepage-pathways__grid">
			<?php foreach ( $pathways as $index => $pathway ) : ?>
			<?php
			$pathway_key = isset( $pathway['key'] ) ? sanitize_html_class( $pathway['key'] ) : '';
			if ( '' === $pathway_key ) {
				$pathway_key = 'card-' . ( $index + 1 );
			}
			$pathway_image_id = isset( $pathway['image_id'] ) ? absint( $pathway['image_id'] ) : 0;
			$pathway_image    = $pathway_image_id ? wp_get_attachment_image(
				$pathway_image_id,
				'medium',
				false,
				array(
					'class'       => 'homepage-pathway__media-image',
					'alt'         => '',
					'aria-hidden' => 'true',
				)
			) : '';
			?>
				<li class="homepage-pathways__item">
					<?php if ( $pathway['url'] ) : ?>
						<a class="homepage-pathway homepage-pathway--<?php echo esc_attr( $pathway_key ); ?> homepage-pathway__link" href="<?php echo esc_url( $pathway['url'] ); ?>">
					<?php else : ?>
						<div class="homepage-pathway homepage-pathway--<?php echo esc_attr( $pathway_key ); ?> homepage-pathway--unavailable">
					<?php endif; ?>

						<span class="homepage-pathway__media" aria-hidden="true">
							<?php if ( $pathway_image ) : ?>
								<?php echo $pathway_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?>
							<?php else : ?>
								<span class="homepage-pathway__media-mark"><?php echo esc_html( $pathway['mark'] ); ?></span>
							<?php endif; ?>
						</span>
						<h3 class="homepage-pathway__title"><?php echo esc_html( $pathway['title'] ); ?></h3>
						<?php if ( $pathway['url'] ) : ?>
							<span class="homepage-pathway__action">
								<span><?php echo esc_html( $pathway['button'] ); ?></span>
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
