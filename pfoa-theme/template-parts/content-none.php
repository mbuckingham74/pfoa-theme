<?php
/**
 * Empty state for archives and search results.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$heading_level = isset( $args['heading_level'] ) ? absint( $args['heading_level'] ) : 1;
$heading_tag   = 1 === $heading_level ? 'h1' : 'h2';
?>
<section class="no-results not-found" aria-labelledby="no-results-title">
	<header class="page-header">
		<<?php echo esc_html( $heading_tag ); ?> id="no-results-title" class="entry-title"><?php esc_html_e( 'Nothing found', 'pfoa-theme' ); ?></<?php echo esc_html( $heading_tag ); ?>>
	</header>
	<p><?php esc_html_e( 'There is no content to display here yet. Try another search or return to the homepage.', 'pfoa-theme' ); ?></p>
</section>
