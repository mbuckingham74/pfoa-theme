<?php
/**
 * Homepage hero structure.
 *
 * Content is editable via the "Hero Section" meta box on the page assigned
 * as the site homepage (stored as post meta). When nothing has been saved,
 * the legacy effective content renders: the page featured image (or the
 * theme fallback banner), the "Homepage" headline, and the Adopt/Volunteer
 * destinations. Each carousel image can have its own headline; images
 * without one fall back to the global hero headline. The buttons stay
 * fixed while carousel images change behind them.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero = pfoa_get_hero( get_the_ID() );

$hero_mode  = ( isset( $hero['mode'] ) && 'carousel' === $hero['mode'] ) ? 'carousel' : 'single';
$hero_title = ( isset( $hero['headline'] ) && '' !== $hero['headline'] ) ? $hero['headline'] : __( 'Homepage', 'pfoa-theme' );
$hero_ctas  = ( isset( $hero['ctas'] ) && is_array( $hero['ctas'] ) ) ? $hero['ctas'] : array();
$hero_image = ( isset( $hero['image_id'] ) && wp_attachment_is_image( absint( $hero['image_id'] ) ) ) ? absint( $hero['image_id'] ) : 0;
$hero_image_link = pfoa_get_hero_link( isset( $hero['image_link_page_id'] ) ? $hero['image_link_page_id'] : 0 );

$uploads           = wp_get_upload_dir();
$fallback_hero     = trailingslashit( $uploads['baseurl'] ) . '2026/09/A1KittensBanner.jpg';
$has_fallback_hero = ! empty( $fallback_hero );

$carousel_slides = array();

if ( 'carousel' === $hero_mode ) {
	$raw_items = array();

	if ( isset( $hero['carousel_items'] ) && is_array( $hero['carousel_items'] ) && array() !== $hero['carousel_items'] ) {
		$raw_items = $hero['carousel_items'];
	} elseif ( isset( $hero['carousel_ids'] ) && is_array( $hero['carousel_ids'] ) ) {
		foreach ( $hero['carousel_ids'] as $carousel_id ) {
			$raw_items[] = array(
				'id'           => $carousel_id,
				'link_page_id' => 0,
			);
		}
	}

	foreach ( $raw_items as $raw_item ) {
		$slide_id = is_array( $raw_item ) && isset( $raw_item['id'] ) ? absint( $raw_item['id'] ) : absint( $raw_item );

		if ( ! $slide_id || ! wp_attachment_is_image( $slide_id ) ) {
			continue;
		}

		$slide_link_page_id = ( is_array( $raw_item ) && isset( $raw_item['link_page_id'] ) ) ? $raw_item['link_page_id'] : 0;
		$slide_link         = pfoa_get_hero_link( $slide_link_page_id );

		$slide_headline = ( is_array( $raw_item ) && isset( $raw_item['headline'] ) ) ? trim( $raw_item['headline'] ) : '';

		if ( '' === $slide_headline ) {
			$slide_headline = $hero_title;
		}

		$slide_brightness = ( is_array( $raw_item ) && isset( $raw_item['brightness'] ) ) ? pfoa_sanitize_hero_brightness( $raw_item['brightness'] ) : 100;

		$slide_headline_scale = ( is_array( $raw_item ) && isset( $raw_item['headline_scale'] ) ) ? pfoa_sanitize_hero_headline_scale( $raw_item['headline_scale'] ) : 100;

		$carousel_slides[] = array(
			'id'             => $slide_id,
			'link_url'       => $slide_link['url'],
			'link_title'     => $slide_link['title'],
			'headline'       => $slide_headline,
			'brightness'     => $slide_brightness,
			'headline_scale' => $slide_headline_scale,
		);
	}
}

$is_carousel = array() !== $carousel_slides;

$carousel_interval_ms = isset( $hero['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $hero['carousel_interval_ms'] ) : 5000;

$hero_title_scale = 100;

if ( $is_carousel ) {
	$hero_title = $carousel_slides[0]['headline'];
	$hero_title_scale = isset( $carousel_slides[0]['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_slides[0]['headline_scale'] ) : 100;
}

$has_custom_image   = 0 !== $hero_image;
$has_featured_image = has_post_thumbnail();

$hero_classes = array( 'homepage-section', 'homepage-hero' );

if ( $is_carousel ) {
	$hero_classes[] = 'homepage-hero--carousel';
}

if ( ! $is_carousel && ! $has_custom_image && ! $has_featured_image && ! $has_fallback_hero ) {
	$hero_classes[] = 'homepage-hero--no-image';
}
?>
<section id="homepage-hero" class="<?php echo esc_attr( implode( ' ', $hero_classes ) ); ?>" aria-labelledby="homepage-hero-title">
	<?php if ( $is_carousel ) : ?>
		<div class="homepage-hero__media" id="homepage-hero-media" data-pfoa-hero-carousel data-carousel-interval="<?php echo esc_attr( (string) $carousel_interval_ms ); ?>">
		<?php foreach ( $carousel_slides as $slide_index => $slide ) : ?>
			<?php
			$slide_id    = $slide['id'];
			$slide_label = '' !== $slide['link_title'] ? sprintf( __( 'Open page: %s', 'pfoa-theme' ), $slide['link_title'] ) : __( 'Open linked page', 'pfoa-theme' );
			$brightness_factors = array(
				100 => '1',
				110 => '1.1',
				120 => '1.2',
				130 => '1.3',
			);
			$slide_brightness   = isset( $slide['brightness'] ) ? (int) $slide['brightness'] : 100;
			if ( ! isset( $brightness_factors[ $slide_brightness ] ) ) {
				$slide_brightness = 100;
			}
			$brightness_factor = $brightness_factors[ $slide_brightness ];
			?>
			<div class="homepage-hero__slide<?php echo 0 === $slide_index ? ' is-active' : ''; ?>"<?php echo 0 === $slide_index ? '' : ' aria-hidden="true"'; ?> data-headline="<?php echo esc_attr( $slide['headline'] ); ?>" data-headline-scale="<?php echo esc_attr( (string) ( isset( $slide['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $slide['headline_scale'] ) : 100 ) ); ?>" style="--pfoa-hero-brightness:<?php echo esc_attr( $brightness_factor ); ?>;">
					<?php
					echo wp_get_attachment_image(
						$slide_id,
						'full',
						false,
						array(
							'class'         => 'homepage-hero__image',
							'loading'       => 0 === $slide_index ? 'eager' : 'lazy',
							'decoding'      => 'async',
							'fetchpriority' => 0 === $slide_index ? 'high' : 'low',
						)
					);
					?>
					<span class="homepage-hero__scrim" aria-hidden="true"></span>
					<?php if ( '' !== $slide['link_url'] ) : ?>
						<a class="homepage-hero__media-link" href="<?php echo esc_url( $slide['link_url'] ); ?>" aria-label="<?php echo esc_attr( $slide_label ); ?>"<?php echo 0 === $slide_index ? '' : ' tabindex="-1"'; ?>></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php elseif ( $has_custom_image ) : ?>
		<div class="homepage-hero__media">
			<?php
			echo wp_get_attachment_image(
				$hero_image,
				'full',
				false,
				array(
					'class'         => 'homepage-hero__image',
					'loading'       => 'eager',
					'decoding'      => 'async',
					'fetchpriority' => 'high',
				)
			);
			?>
			<span class="homepage-hero__scrim" aria-hidden="true"></span>
			<?php if ( '' !== $hero_image_link['url'] ) : ?>
				<?php
				$single_label = '' !== $hero_image_link['title'] ? sprintf( __( 'Open page: %s', 'pfoa-theme' ), $hero_image_link['title'] ) : __( 'Open linked page', 'pfoa-theme' );
				?>
				<a class="homepage-hero__media-link" href="<?php echo esc_url( $hero_image_link['url'] ); ?>" aria-label="<?php echo esc_attr( $single_label ); ?>"></a>
			<?php endif; ?>
		</div>
	<?php elseif ( $has_featured_image ) : ?>
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
	<?php elseif ( $has_fallback_hero ) : ?>
		<div class="homepage-hero__media">
			<img src="<?php echo esc_url( $fallback_hero ); ?>" class="homepage-hero__image" alt="" loading="eager" decoding="async" fetchpriority="high" />
			<span class="homepage-hero__scrim" aria-hidden="true"></span>
		</div>
	<?php endif; ?>

	<div class="wide-container homepage-hero__inner">
		<div class="homepage-hero__content">
			<p class="homepage-hero__site-name"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></p>
			<h1 id="homepage-hero-title" class="homepage-hero__title<?php echo $is_carousel ? ' scale-' . esc_attr( (string) $hero_title_scale ) : ''; ?>"><?php echo esc_html( $hero_title ); ?></h1>

			<?php if ( $hero_ctas ) : ?>
				<div class="homepage-hero__actions">
					<?php $cta_position = 0; ?>
					<?php foreach ( $hero_ctas as $hero_cta ) : ?>
						<?php
						$cta_label = isset( $hero_cta['label'] ) ? $hero_cta['label'] : '';
						$cta_url   = isset( $hero_cta['url'] ) ? $hero_cta['url'] : '';

						if ( '' === $cta_label || '' === $cta_url ) {
							continue;
						}

						$cta_class = 0 === $cta_position ? 'pfoa-button homepage-hero__primary' : 'pfoa-button homepage-hero__secondary';
						$cta_position++;
						?>
						<a class="<?php echo esc_attr( $cta_class ); ?>" href="<?php echo esc_url( $cta_url ); ?>">
							<?php echo esc_html( $cta_label ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( $is_carousel ) : ?>
		<div class="homepage-hero__controls">
			<button type="button" class="homepage-hero__nav homepage-hero__nav--prev" data-pfoa-hero-prev aria-controls="homepage-hero-media" aria-label="<?php esc_attr_e( 'Previous hero image', 'pfoa-theme' ); ?>">
				<span aria-hidden="true">&#8249;</span>
			</button>
			<button type="button" class="homepage-hero__nav homepage-hero__nav--next" data-pfoa-hero-next aria-controls="homepage-hero-media" aria-label="<?php esc_attr_e( 'Next hero image', 'pfoa-theme' ); ?>">
				<span aria-hidden="true">&#8250;</span>
			</button>
			<button type="button" class="homepage-hero__nav homepage-hero__nav--toggle" data-pfoa-hero-toggle aria-controls="homepage-hero-media" aria-label="<?php esc_attr_e( 'Pause carousel', 'pfoa-theme' ); ?>" aria-pressed="false">
				<span aria-hidden="true" data-pfoa-hero-toggle-icon>&#10074;&#10074;</span>
			</button>
		</div>
	<?php endif; ?>
</section>
