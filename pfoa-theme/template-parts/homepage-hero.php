<?php
/**
 * Site hero structure.
 *
 * Content is editable via the "Hero Section" meta box on the page assigned
 * as the site homepage (stored as post meta). When nothing has been saved,
 * the legacy effective content renders: the page featured image (or the
 * theme fallback banner) with no headline, and the Adopt/Volunteer
 * destinations. Each carousel slide owns its own headline; an empty slide
 * headline shows no headline. The buttons stay
 * fixed while carousel images change behind them.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$hero = pfoa_get_hero( get_the_ID() );

// No global headline: each carousel slide owns its own headline.
// Featured/fallback rendering shows no headline (empty h1 kept for aria-labelledby).
$hero_title = '';
$hero_ctas  = ( isset( $hero['ctas'] ) && is_array( $hero['ctas'] ) ) ? $hero['ctas'] : array();

$uploads           = wp_get_upload_dir();
$fallback_hero     = trailingslashit( $uploads['baseurl'] ) . '2026/09/A1KittensBanner.jpg';
$has_fallback_hero = ! empty( $fallback_hero );

$carousel_slides = array();
$carousel_slide_count = isset( $hero['carousel_slide_count'] ) ? pfoa_sanitize_hero_carousel_slide_count( $hero['carousel_slide_count'] ) : 3;

{
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

		// Dedupe by attachment ID; skip repeats.
		foreach ( $carousel_slides as $existing_slide ) {
			if ( $existing_slide['id'] === $slide_id ) {
				continue 2;
			}
		}

		$slide_link_page_id = ( is_array( $raw_item ) && isset( $raw_item['link_page_id'] ) ) ? $raw_item['link_page_id'] : 0;

		$slide_headline = ( is_array( $raw_item ) && isset( $raw_item['headline'] ) ) ? trim( $raw_item['headline'] ) : '';
		// Each slide keeps its own headline, even when empty (no global fallback).

		$slide_link = pfoa_get_hero_slide_link( is_array( $raw_item ) ? $raw_item : array( 'link_page_id' => $slide_link_page_id ), $slide_headline );

		$slide_brightness = ( is_array( $raw_item ) && isset( $raw_item['brightness'] ) ) ? pfoa_sanitize_hero_brightness( $raw_item['brightness'] ) : 100;

		$slide_headline_scale = ( is_array( $raw_item ) && isset( $raw_item['headline_scale'] ) ) ? pfoa_sanitize_hero_headline_scale( $raw_item['headline_scale'] ) : 100;

		$slide_headline_color = ( is_array( $raw_item ) && isset( $raw_item['headline_color'] ) ) ? pfoa_sanitize_hero_headline_color( $raw_item['headline_color'] ) : 'white';

		$slide_zoom = ( is_array( $raw_item ) && isset( $raw_item['zoom'] ) ) ? pfoa_sanitize_hero_zoom( $raw_item['zoom'] ) : 100;

		$slide_position = ( is_array( $raw_item ) && isset( $raw_item['position'] ) ) ? pfoa_sanitize_hero_position( $raw_item['position'] ) : 'center center';

		$carousel_slides[] = array(
			'id'             => $slide_id,
			'link_url'       => $slide_link['url'],
			'link_title'     => $slide_link['title'],
			'link_external'  => ! empty( $slide_link['external'] ),
			'headline'       => $slide_headline,
			'brightness'     => $slide_brightness,
			'headline_scale' => $slide_headline_scale,
			'headline_color' => $slide_headline_color,
			'zoom'           => $slide_zoom,
			'position'       => $slide_position,
		);

		// Cap at 5 stored slots, render only the first N selected.
		if ( count( $carousel_slides ) >= 5 ) {
			break;
		}
	}

	$carousel_slides = array_slice( $carousel_slides, 0, $carousel_slide_count );
}

// Featured slide pinning: when a valid pin resolves, render ONLY that slide
// as a single static hero (saved image/headline/destination/zoom/position
// preserved) with rotation controls and rotation data attributes hidden.
// pfoa_get_hero_pinned_slide_id() re-validates server-side on every request
// (image, range, timed expiry), so an expired/invalid/missing-image pin
// silently falls back to the configured rotation with all slides intact.
//
// Page-cache limitation: expiration is evaluated in PHP per uncached
// request. A host, CDN, or plugin serving full-page cache may keep showing
// the pinned hero (or the rotation) until the cached page expires or is
// flushed; no cache flush or no-cache handling is added here, so a timed pin
// can remain visible up to one cache TTL past expiry on cached pages.
$pinned_slide_id = pfoa_get_hero_pinned_slide_id( $hero );
$is_pinned       = false;

if ( $pinned_slide_id ) {
	foreach ( $carousel_slides as $candidate_slide ) {
		if ( $candidate_slide['id'] === $pinned_slide_id ) {
			$carousel_slides = array( $candidate_slide );
			$is_pinned       = true;
			break;
		}
	}
}

$is_carousel = array() !== $carousel_slides;

$carousel_interval_ms = isset( $hero['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $hero['carousel_interval_ms'] ) : 5000;

$hero_title_scale = 100;
$hero_title_color = 'white';

if ( $is_carousel ) {
	// Pinned static slide shows that slide's headline; an empty slide
	// headline renders an empty h1 (no headline) while still applying the
	// slide's scale/color classes.
	$hero_title = $carousel_slides[0]['headline'];
	$hero_title_scale = isset( $carousel_slides[0]['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_slides[0]['headline_scale'] ) : 100;
	$hero_title_color = isset( $carousel_slides[0]['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $carousel_slides[0]['headline_color'] ) : 'white';
}

$has_featured_image = has_post_thumbnail();

$hero_classes = array( 'homepage-section', 'homepage-hero' );

if ( $is_carousel ) {
	$hero_classes[] = 'homepage-hero--carousel';
}

if ( $is_pinned ) {
	$hero_classes[] = 'homepage-hero--pinned';
}

if ( ! $is_carousel && ! $has_featured_image && ! $has_fallback_hero ) {
	$hero_classes[] = 'homepage-hero--no-image';
}

// PFOA 0.1.215 — Dine Out promo slide identifier (National Cat Day / Black
// Bear Diner). The promo artwork carries embedded text (date + 8am-8pm
// hours) that cover-crops at 390px mobile widths. Slides are
// admin-configured attachments, so identify this slide by content signals
// (headline, attachment title/alt/caption/description/file, or its external
// Facebook destination) and tag only the matching slide. Other slides,
// pause/resume, the Facebook destination, and desktop presentation are
// untouched.
if ( ! function_exists( 'pfoa_is_dine_out_promo_slide' ) ) {
	/**
	 * Whether a carousel slide is the Dine Out promo artwork.
	 *
	 * @param int   $slide_id Attachment ID.
	 * @param array $slide    Carousel slide row (headline/link_* keys).
	 * @return bool
	 */
	function pfoa_is_dine_out_promo_slide( $slide_id, $slide ) {
		$needles = array( 'dine out', 'dine-out', 'dineout', 'black bear', 'blackbear', 'black-bear', 'cat day', 'cat-day', 'national cat' );

		$haystacks = array();

		if ( is_array( $slide ) ) {
			if ( isset( $slide['headline'] ) ) {
				$haystacks[] = (string) $slide['headline'];
			}

			if ( isset( $slide['link_url'] ) ) {
				$haystacks[] = (string) $slide['link_url'];
			}
		}

		$slide_id = absint( $slide_id );

		if ( $slide_id && function_exists( 'get_post' ) ) {
			$attachment = get_post( $slide_id );

			if ( $attachment ) {
				$haystacks[] = (string) $attachment->post_title;
				$haystacks[] = (string) $attachment->post_excerpt;
				$haystacks[] = (string) $attachment->post_content;
				$haystacks[] = (string) $attachment->post_name;

				if ( function_exists( 'get_post_meta' ) ) {
					$haystacks[] = (string) get_post_meta( $slide_id, '_wp_attachment_image_alt', true );
				}

				if ( function_exists( 'get_attached_file' ) ) {
					$haystacks[] = (string) get_attached_file( $slide_id );
				}
			}
		}

		foreach ( $haystacks as $haystack ) {
			$text = strtolower( $haystack );

			if ( '' === $text ) {
				continue;
			}

			// Facebook destination alone is not enough to tag (other slides
			// may link out); require a promo keyword alongside it, or match
			// on headline/attachment text directly.
			foreach ( $needles as $needle ) {
				if ( false !== strpos( $text, $needle ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
?>
<section id="homepage-hero" class="<?php echo esc_attr( implode( ' ', $hero_classes ) ); ?>" aria-labelledby="homepage-hero-title">
	<?php if ( $is_carousel ) : ?>
		<div class="homepage-hero__media" id="homepage-hero-media"<?php echo $is_pinned ? '' : ' data-pfoa-hero-carousel data-carousel-interval="' . esc_attr( (string) $carousel_interval_ms ) . '"'; ?>>
		<?php foreach ( $carousel_slides as $slide_index => $slide ) : ?>
			<?php
			$slide_id    = $slide['id'];
			$slide_label = '' !== $slide['link_title'] ? sprintf( __( 'Open page: %s', 'pfoa-theme' ), $slide['link_title'] ) : __( 'Open linked page', 'pfoa-theme' );

			if ( ! empty( $slide['link_external'] ) ) {
				$slide_label = sprintf(
					/* translators: %s: external link title or host. */
					__( 'Open external link: %s, opens in new tab', 'pfoa-theme' ),
					'' !== $slide['link_title'] ? $slide['link_title'] : $slide['link_url']
				);
			}
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
			$zoom_factors = array(
				50  => '0.5',
				60  => '0.6',
				70  => '0.7',
				80  => '0.8',
				90  => '0.9',
				100 => '1',
				110 => '1.1',
				120 => '1.2',
				130 => '1.3',
				140 => '1.4',
				150 => '1.5',
			);
			$slide_zoom = isset( $slide['zoom'] ) ? (int) $slide['zoom'] : 100;
			if ( ! isset( $zoom_factors[ $slide_zoom ] ) ) {
				$slide_zoom = 100;
			}
			$zoom_factor = $zoom_factors[ $slide_zoom ];
			$allowed_positions = array(
				'top left',
				'top center',
				'top right',
				'center left',
				'center center',
				'center right',
				'bottom left',
				'bottom center',
				'bottom right',
			);
			$slide_position = isset( $slide['position'] ) ? $slide['position'] : 'center center';
			if ( ! in_array( $slide_position, $allowed_positions, true ) ) {
				$slide_position = 'center center';
			}
			$slide_bg_url = wp_get_attachment_image_url( $slide_id, 'full' );
			$slide_class  = 'homepage-hero__slide' . ( 0 === $slide_index ? ' is-active' : '' ) . ( $slide_zoom < 100 ? ' is-zoom-out' : '' );
			if ( function_exists( 'pfoa_is_dine_out_promo_slide' ) && pfoa_is_dine_out_promo_slide( $slide_id, $slide ) ) {
				$slide_class .= ' homepage-hero__slide--dine-out';
			}
			?>
			<div class="<?php echo esc_attr( $slide_class ); ?>"<?php echo 0 === $slide_index ? '' : ' aria-hidden="true"'; ?> data-headline="<?php echo esc_attr( $slide['headline'] ); ?>" data-headline-scale="<?php echo esc_attr( (string) ( isset( $slide['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $slide['headline_scale'] ) : 100 ) ); ?>" data-headline-color="<?php echo esc_attr( isset( $slide['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $slide['headline_color'] ) : 'white' ); ?>" data-zoom="<?php echo esc_attr( (string) $slide_zoom ); ?>" data-position="<?php echo esc_attr( $slide_position ); ?>" style="--pfoa-hero-brightness:<?php echo esc_attr( $brightness_factor ); ?>;--pfoa-hero-zoom:<?php echo esc_attr( $zoom_factor ); ?>;--pfoa-hero-position:<?php echo esc_attr( $slide_position ); ?>;<?php echo $slide_bg_url ? '--pfoa-hero-bg:url(\'' . esc_url( $slide_bg_url ) . '\');' : ''; ?>">
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
						<a class="homepage-hero__media-link" href="<?php echo esc_url( $slide['link_url'] ); ?>" aria-label="<?php echo esc_attr( $slide_label ); ?>"<?php echo ! empty( $slide['link_external'] ) ? ' target="_blank" rel="noopener noreferrer"' : ''; ?><?php echo 0 === $slide_index ? '' : ' tabindex="-1"'; ?>></a>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
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
			<?php // H1 kept always for aria-labelledby; empty $hero_title renders no headline (featured/fallback and empty slide). ?>
			<h1 id="homepage-hero-title" class="homepage-hero__title<?php echo $is_carousel ? ' scale-' . esc_attr( (string) $hero_title_scale ) . ' headline-' . esc_attr( $hero_title_color ) : ''; ?>"><?php echo esc_html( $hero_title ); ?></h1>

			<?php if ( $hero_ctas ) : ?>
				<div class="homepage-hero__actions">
					<?php $cta_position = 0; ?>
					<?php foreach ( $hero_ctas as $hero_cta ) : ?>
					<?php
					$cta_label = isset( $hero_cta['label'] ) ? $hero_cta['label'] : '';
					$cta_url   = function_exists( 'pfoa_get_hero_cta_url' ) ? pfoa_get_hero_cta_url( $hero_cta ) : ( isset( $hero_cta['url'] ) ? $hero_cta['url'] : '' );

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

	<?php if ( $is_carousel && ! $is_pinned ) : ?>
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
