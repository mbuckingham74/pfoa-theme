<?php
/**
 * Homepage promotional and informational tiles.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$promo_items  = array();
$donation_url = pfoa_get_homepage_donation_url();

if ( $donation_url ) {
	$promo_items[] = array(
		'key'         => 'donate',
		'eyebrow'     => __( 'Support PFOA', 'pfoa-theme' ),
		'media_word'  => __( 'Donate', 'pfoa-theme' ),
		'media_note'  => __( 'Ways to give', 'pfoa-theme' ),
		'title'       => __( 'Donate', 'pfoa-theme' ),
		'description' => __( 'Find the approved destination for giving to PFOA.', 'pfoa-theme' ),
		'link_label'  => __( 'Support PFOA', 'pfoa-theme' ),
		'url'         => $donation_url,
	);
}

$promo_page_definitions = array(
	array(
		'key'            => 'potholders',
		'paths'          => array( 'potholders-2' ),
		'eyebrow'        => __( 'PFOA information', 'pfoa-theme' ),
		'media_word'     => __( 'Potholders', 'pfoa-theme' ),
		'media_note'     => __( 'Explore the page', 'pfoa-theme' ),
		'fallback_title' => __( 'Potholders', 'pfoa-theme' ),
		'link_label'     => __( 'Explore Potholders', 'pfoa-theme' ),
	),
	array(
		'key'            => 'wishlist',
		'paths'          => array( 'wishlist' ),
		'eyebrow'        => __( 'PFOA information', 'pfoa-theme' ),
		'media_word'     => __( 'Wish List', 'pfoa-theme' ),
		'media_note'     => __( 'Explore the page', 'pfoa-theme' ),
		'fallback_title' => __( 'Wish List', 'pfoa-theme' ),
		'link_label'     => __( 'Explore the Wish List', 'pfoa-theme' ),
	),
);

foreach ( $promo_page_definitions as $promo_definition ) {
	$promo_page = pfoa_get_page_by_paths( $promo_definition['paths'] );
	$promo_url  = $promo_page ? get_permalink( $promo_page ) : '';

	if ( ! $promo_page || ! $promo_url ) {
		continue;
	}

	$promo_title = get_the_title( $promo_page );
	$promo_title = $promo_title ? $promo_title : $promo_definition['fallback_title'];

	$promo_items[] = array(
		'key'         => $promo_definition['key'],
		'eyebrow'     => $promo_definition['eyebrow'],
		'media_word'  => $promo_definition['media_word'],
		'media_note'  => $promo_definition['media_note'],
		'title'       => $promo_title,
		'description' => sprintf(
			/* translators: %s: the title of an existing PFOA Page. */
			__( 'Explore the information PFOA maintains on %s.', 'pfoa-theme' ),
			$promo_title
		),
		'link_label'  => $promo_definition['link_label'],
		'url'         => $promo_url,
	);
}

/* Do not leave an orphan heading or emit a self-link when no safe destination exists. */
if ( ! $promo_items ) {
	return;
}
?>
<section id="homepage-promos" class="homepage-section homepage-promos" aria-labelledby="homepage-promos-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<p class="homepage-section__eyebrow"><?php esc_html_e( 'Practical support', 'pfoa-theme' ); ?></p>
			<h2 id="homepage-promos-title" class="homepage-section__title"><?php esc_html_e( 'More ways to help', 'pfoa-theme' ); ?></h2>
			<p class="homepage-section__intro"><?php esc_html_e( 'Explore additional PFOA information and support destinations.', 'pfoa-theme' ); ?></p>
		</header>

		<ul class="homepage-promos__grid">
			<?php foreach ( $promo_items as $promo ) : ?>
				<li class="homepage-promos__item">
					<a class="homepage-promo homepage-promo--<?php echo esc_attr( $promo['key'] ); ?> homepage-promo__link" href="<?php echo esc_url( $promo['url'] ); ?>">
						<span class="homepage-promo__media" aria-hidden="true">
							<span class="homepage-promo__media-word"><?php echo esc_html( $promo['media_word'] ); ?></span>
							<span class="homepage-promo__media-note"><?php echo esc_html( $promo['media_note'] ); ?></span>
						</span>
						<span class="homepage-promo__body">
							<span class="homepage-card__eyebrow"><?php echo esc_html( $promo['eyebrow'] ); ?></span>
							<h3 class="homepage-promo__title"><?php echo esc_html( $promo['title'] ); ?></h3>
							<span class="homepage-promo__description"><?php echo esc_html( $promo['description'] ); ?></span>
							<span class="pfoa-text-link homepage-promo__action"><?php echo esc_html( $promo['link_label'] ); ?></span>
						</span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
