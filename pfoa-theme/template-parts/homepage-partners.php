<?php
/**
 * Homepage partners and supporters region.
 *
 * Partner media and outbound destinations require an approved current roster.
 * The presentation hook remains empty until PFOA supplies approved names,
 * attachment IDs, and any permitted outbound URLs.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$partners_page  = pfoa_get_page_by_paths( array( 'businesspartners' ) );
$partners_url   = $partners_page ? get_permalink( $partners_page ) : '';
$partners_label = $partners_page ? get_the_title( $partners_page ) : '';
$partners_label = $partners_label ? $partners_label : __( 'View business partners information', 'pfoa-theme' );
$partner_items  = apply_filters( 'pfoa_homepage_partner_items', array() );
$partner_items  = is_array( $partner_items ) ? $partner_items : array();
$valid_partners = array();

foreach ( $partner_items as $partner_item ) {
	if ( ! is_array( $partner_item ) || empty( $partner_item['name'] ) ) {
		continue;
	}

	$partner_name = trim( wp_strip_all_tags( (string) $partner_item['name'] ) );

	if ( ! $partner_name ) {
		continue;
	}

	$valid_partners[] = array(
		'name'     => $partner_name,
		'image_id' => isset( $partner_item['image_id'] ) ? absint( $partner_item['image_id'] ) : 0,
		'url'      => ! empty( $partner_item['url'] ) ? esc_url( $partner_item['url'] ) : '',
	);
}
?>
<section id="homepage-partners" class="homepage-section homepage-partners" aria-labelledby="homepage-partners-title">
	<div class="content-container">
		<header class="homepage-section__header">
			<p class="homepage-section__eyebrow"><?php esc_html_e( 'With appreciation', 'pfoa-theme' ); ?></p>
			<h2 id="homepage-partners-title" class="homepage-section__title"><?php esc_html_e( 'Partners and supporters', 'pfoa-theme' ); ?></h2>
			<p class="homepage-section__intro"><?php esc_html_e( 'A dedicated space for approved PFOA partner and supporter identities.', 'pfoa-theme' ); ?></p>
		</header>

		<?php if ( $valid_partners ) : ?>
			<ul class="homepage-partners__grid">
				<?php foreach ( $valid_partners as $partner ) : ?>
					<?php
					$partner_image = $partner['image_id'] ? wp_get_attachment_image(
						$partner['image_id'],
						'medium',
						false,
						array(
							'class'   => 'homepage-partner__logo',
							'alt'     => $partner['name'],
							'loading' => 'lazy',
						)
					) : '';
					$partner_tag   = $partner['url'] ? 'a' : 'div';
					$partner_attrs = $partner['url'] ? ' href="' . esc_url( $partner['url'] ) . '"' : '';
					?>
					<li class="homepage-partners__item">
						<<?php echo esc_html( $partner_tag ); ?> class="homepage-partner__link"<?php echo $partner_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attribute is assembled from esc_url(). ?> >
							<?php if ( $partner_image ) : ?>
								<?php echo $partner_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?>
							<?php else : ?>
								<span class="homepage-partner__name"><?php echo esc_html( $partner['name'] ); ?></span>
							<?php endif; ?>
						</<?php echo esc_html( $partner_tag ); ?>>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="homepage-partners__empty">
				<p class="homepage-empty-state"><?php esc_html_e( 'Approved partner logos and links will be added here when the roster and image permissions are confirmed.', 'pfoa-theme' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( $partners_page && $partners_url ) : ?>
			<p class="homepage-partners__link-wrap">
				<a class="pfoa-text-link" href="<?php echo esc_url( $partners_url ); ?>">
					<?php echo esc_html( $partners_label ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</section>
