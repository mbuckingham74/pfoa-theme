<?php
/**
 * Empty state for archives and search results.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="no-results not-found" aria-labelledby="no-results-title">
	<header class="page-header">
		<h1 id="no-results-title" class="entry-title"><?php esc_html_e( 'Nothing found', 'pfoa-theme' ); ?></h1>
	</header>
	<p><?php esc_html_e( 'There is no content to display here yet. Try another search or return to the homepage.', 'pfoa-theme' ); ?></p>
</section>
