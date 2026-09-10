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
?>
<section id="homepage-news-events" class="homepage-section homepage-news-events" aria-labelledby="homepage-news-events-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<h2 id="homepage-news-events-title" class="homepage-section__title"><?php esc_html_e( 'News and events', 'pfoa-theme' ); ?></h2>
		</header>

		<div class="homepage-news-events__grid">
			<article class="homepage-teaser homepage-news-teaser">
				<h3 class="homepage-teaser__title">
					<?php if ( $news_page && $news_url ) : ?>
						<a href="<?php echo esc_url( $news_url ); ?>"><?php echo esc_html( get_the_title( $news_page ) ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'News', 'pfoa-theme' ); ?>
					<?php endif; ?>
				</h3>
				<?php if ( $news_page && $news_url ) : ?>
					<a class="pfoa-text-link" href="<?php echo esc_url( $news_url ); ?>">
						<?php esc_html_e( 'Read news and announcements', 'pfoa-theme' ); ?>
					</a>
				<?php else : ?>
					<p class="homepage-empty-state"><?php esc_html_e( 'This area is ready for an approved news source.', 'pfoa-theme' ); ?></p>
				<?php endif; ?>
			</article>

			<article class="homepage-teaser homepage-events-teaser">
				<h3 class="homepage-teaser__title">
					<?php if ( $events_page && $events_url ) : ?>
						<a href="<?php echo esc_url( $events_url ); ?>"><?php echo esc_html( get_the_title( $events_page ) ); ?></a>
					<?php else : ?>
						<?php esc_html_e( 'Events', 'pfoa-theme' ); ?>
					<?php endif; ?>
				</h3>
				<?php if ( $events_page && $events_url ) : ?>
					<a class="pfoa-text-link" href="<?php echo esc_url( $events_url ); ?>">
						<?php esc_html_e( 'View events calendar', 'pfoa-theme' ); ?>
					</a>
				<?php else : ?>
					<p class="homepage-empty-state"><?php esc_html_e( 'This area is ready for an approved events source.', 'pfoa-theme' ); ?></p>
				<?php endif; ?>
			</article>
		</div>
	</div>
</section>
