<?php
/**
 * Homepage news and events teaser regions.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$news_page  = pfoa_get_page_by_paths( array( 'news-announcements', 'fromthehomefront-2' ) );
$events_page = pfoa_get_page_by_paths( array( 'eventscalendar' ) );
$news_url    = $news_page ? get_permalink( $news_page ) : '';
$events_url  = $events_page ? get_permalink( $events_page ) : '';

/*
 * These are entry points to existing Pages, not a generated feed. Keep the
 * section out of the document when neither safe destination is available.
 */
if ( ! $news_url && ! $events_url ) {
	return;
}

$teasers = array(
	array(
		'key'            => 'news',
		'page'           => $news_page,
		'url'            => $news_url,
		'eyebrow'        => __( 'News / announcements', 'pfoa-theme' ),
		'media_word'     => __( 'News', 'pfoa-theme' ),
		'media_note'     => __( 'Stories and updates', 'pfoa-theme' ),
		'fallback_title' => __( 'News', 'pfoa-theme' ),
		'description'    => __( 'Explore the news and announcements page maintained by PFOA.', 'pfoa-theme' ),
		'link_label'     => __( 'Read news and announcements', 'pfoa-theme' ),
	),
	array(
		'key'            => 'events',
		'page'           => $events_page,
		'url'            => $events_url,
		'eyebrow'        => __( 'Community calendar', 'pfoa-theme' ),
		'media_word'     => __( 'Events', 'pfoa-theme' ),
		'media_note'     => __( 'Gatherings and fundraisers', 'pfoa-theme' ),
		'fallback_title' => __( 'Events', 'pfoa-theme' ),
		'description'    => __( 'Explore the events calendar and fundraiser information maintained by PFOA.', 'pfoa-theme' ),
		'link_label'     => __( 'View the events calendar', 'pfoa-theme' ),
	),
);
?>
<section id="homepage-news-events" class="homepage-section homepage-news-events" aria-labelledby="homepage-news-events-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<p class="homepage-section__eyebrow"><?php esc_html_e( 'Stay connected', 'pfoa-theme' ); ?></p>
			<h2 id="homepage-news-events-title" class="homepage-section__title"><?php esc_html_e( 'News and events', 'pfoa-theme' ); ?></h2>
			<p class="homepage-section__intro"><?php esc_html_e( 'Keep up with PFOA through the pages where news, announcements, and events are maintained.', 'pfoa-theme' ); ?></p>
		</header>

		<div class="homepage-news-events__grid">
			<?php foreach ( $teasers as $teaser ) : ?>
				<?php
				$teaser_title = $teaser['page'] ? get_the_title( $teaser['page'] ) : '';
				$teaser_title = $teaser_title ? $teaser_title : $teaser['fallback_title'];
				$teaser_class = $teaser['url'] ? '' : ' homepage-teaser--unavailable';
				?>
				<article class="homepage-teaser homepage-<?php echo esc_attr( $teaser['key'] ); ?>-teaser<?php echo esc_attr( $teaser_class ); ?>">
					<?php if ( $teaser['url'] ) : ?>
						<a class="homepage-teaser__link" href="<?php echo esc_url( $teaser['url'] ); ?>">
					<?php else : ?>
						<div class="homepage-teaser__link homepage-teaser__link--static">
					<?php endif; ?>

						<div class="homepage-teaser__media" aria-hidden="true">
							<span class="homepage-teaser__media-word"><?php echo esc_html( $teaser['media_word'] ); ?></span>
							<span class="homepage-teaser__media-note"><?php echo esc_html( $teaser['media_note'] ); ?></span>
						</div>
						<div class="homepage-teaser__body">
							<span class="homepage-card__eyebrow"><?php echo esc_html( $teaser['eyebrow'] ); ?></span>
							<h3 class="homepage-teaser__title"><?php echo esc_html( $teaser_title ); ?></h3>
							<p class="homepage-teaser__description"><?php echo esc_html( $teaser['description'] ); ?></p>
							<?php if ( $teaser['url'] ) : ?>
								<span class="pfoa-text-link homepage-teaser__action"><?php echo esc_html( $teaser['link_label'] ); ?></span>
							<?php else : ?>
								<span class="homepage-empty-state"><?php esc_html_e( 'This page is not currently available.', 'pfoa-theme' ); ?></span>
							<?php endif; ?>
						</div>

					<?php if ( $teaser['url'] ) : ?>
						</a>
					<?php else : ?>
						</div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
