<?php
/**
 * Focused regression fixture for the custom front-page presentation boundary.
 *
 * This is a source-level check. It has no WordPress, network, staging, or
 * production dependency and verifies that the front-page loop remains intact
 * while ordinary Page rendering retains the normal content pipeline.
 *
 * @package PFOA
 */

declare( strict_types=1 );

$repo_dir = dirname( __DIR__ );
$front    = file_get_contents( $repo_dir . '/pfoa-theme/front-page.php' );
$page     = file_get_contents( $repo_dir . '/pfoa-theme/page.php' );
$builder  = file_get_contents( $repo_dir . '/pfoa-theme/template_builder.php' );
$content  = file_get_contents( $repo_dir . '/pfoa-theme/template-parts/content-page.php' );
$style    = file_get_contents( $repo_dir . '/pfoa-theme/style.css' );

function fixture_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

fixture_assert( false !== $front, 'front-page.php can be read' );
fixture_assert( false !== $page, 'page.php can be read' );
fixture_assert( false !== $builder, 'template_builder.php can be read' );
fixture_assert( false !== $content, 'content-page.php can be read' );
fixture_assert( false !== $style, 'style.css can be read' );

fixture_assert( false === strpos( $front, 'homepage-editorial' ), 'front page does not load the legacy editorial partial' );
fixture_assert( false === strpos( $front, 'the_content();' ), 'front page does not render the legacy editor body' );
fixture_assert( false === strpos( $front, 'get_the_content(' ), 'front page does not inspect editor content to gate legacy output' );
fixture_assert(
	preg_match( '/if \( have_posts\(\) \).*?while \( have_posts\(\) \).*?the_post\(\)/s', $front ) === 1,
	'front page retains a valid main loop and current-post context'
);

$regions = array(
	'hero',
	'pathways',
	'adoption',
	'news-events',
	'promos',
	'partners',
);
$last_position = -1;
foreach ( $regions as $region ) {
	$position = strpos( $front, "'template-parts/homepage', '{$region}" );
	fixture_assert( false !== $position, "homepage-{$region} template part remains present" );
	fixture_assert( $position > $last_position, "homepage-{$region} template part remains in intended order" );
	$last_position = $position;
}

fixture_assert( false !== strpos( $page, "'legacy_compatibility' => false" ), 'ordinary Page template uses the shared Page part in normal mode' );
fixture_assert( false !== strpos( $builder, "'legacy_compatibility' => true" ), 'compatibility bridge uses the shared Page part in compatibility mode' );
fixture_assert( false !== strpos( $builder, "'template-parts/content',\n\t\t\t\t'page'" ), 'compatibility bridge shared-renders normal Page content' );
fixture_assert( false !== strpos( $content, 'the_content();' ), 'shared Page content part still calls the_content()' );
fixture_assert( false === strpos( $style, 'homepage-editorial' ), 'obsolete editorial CSS was removed rather than hidden' );

fwrite( STDOUT, "PASS: focused front-page presentation fixture (loop/context, section order, Page compatibility, no CSS hiding hack)\n" );
