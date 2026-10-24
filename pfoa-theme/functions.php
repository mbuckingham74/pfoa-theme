<?php
/**
 * Theme setup and asset loading.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'PFOA_THEME_PASSIVE_RESOLVER_LOADED' ) ) {
	$resolver_file = __DIR__ . '/inc/theme-passive-resolver.php';
	if ( file_exists( $resolver_file ) ) {
		require_once $resolver_file;
	}
}

// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP
// (docs/dev-synthetic-exclusion-removal.md). Presentation only, DEV host only.
if ( ! defined( 'PFOA_DEV_SYNTHETIC_EXCLUSION_LOADED' ) ) {
	$dev_synth_file = __DIR__ . '/inc/pfoa-dev-synthetic-exclusion.php';
	if ( file_exists( $dev_synth_file ) ) {
		require_once $dev_synth_file;
	}
}

/**
 * Configure theme supports and menu locations.
 *
 * @return void
 */
function pfoa_setup() {
	load_theme_textdomain( 'pfoa-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 160,
			'width'       => 480,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
	add_theme_support(
		'html5',
		array(
			'caption',
			'comment-form',
			'comment-list',
			'gallery',
			'search-form',
			'script',
			'style',
		)
	);

	register_nav_menus(
		array(
			'primary'        => esc_html__( 'Primary Menu', 'pfoa-theme' ),
			'header_utility' => esc_html__( 'Header Utility', 'pfoa-theme' ),
			'footer'         => esc_html__( 'Footer Menu — Explore PFOA', 'pfoa-theme' ),
			'footer_support' => esc_html__( 'Footer Menu — Ways to Help', 'pfoa-theme' ),
			'footer_legal'   => esc_html__( 'Footer Legal Menu', 'pfoa-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'pfoa_setup' );

/**
 * Enqueue the theme stylesheet.
 *
 * @return void
 */
function pfoa_enqueue_assets() {
	$theme = wp_get_theme();
	$navigation_script = get_template_directory() . '/assets/js/navigation.js';

	wp_enqueue_style(
		'pfoa-style',
		get_stylesheet_uri(),
		array(),
		$theme->get( 'Version' )
	);

	if ( file_exists( $navigation_script ) ) {
		wp_enqueue_script(
			'pfoa-navigation',
			get_template_directory_uri() . '/assets/js/navigation.js',
			array(),
			(string) filemtime( $navigation_script ),
			array( 'strategy' => 'defer' )
		);
	}

	// PFOA 0.1.121 — presentation only: Adopted Gallery decorative filler
	// placement (AG-09). Tiny layout script, adopted2026 page only. It
	// inserts at most one noninteractive filler panel per incomplete gallery
	// row, never reorders or edits event nodes, and no-ops without events.
	if ( function_exists( 'is_page' ) && ( is_page( 22514 ) || is_page( 'adopted2026' ) ) ) {
		$filler_script = get_template_directory() . '/assets/js/pfoa-adopted-gallery-filler.js';

		if ( file_exists( $filler_script ) ) {
			wp_enqueue_script(
				'pfoa-adopted-gallery-filler',
				get_template_directory_uri() . '/assets/js/pfoa-adopted-gallery-filler.js',
				array(),
				(string) filemtime( $filler_script ),
				array( 'strategy' => 'defer' )
			);
		}
	}

	// PFOA 0.1.156 — presentation only: Pet Tidings disclosure auto-open
	// (page 4799 only). Collapsed by default; the script sets open=true when
	// the URL carries pfoa_pt_done or the disclosure contains form feedback.
	if ( function_exists( 'is_page' ) && is_page( 4799 ) ) {
		$disclosure_script = get_template_directory() . '/assets/js/pfoa-pet-tidings-disclosure.js';

		if ( file_exists( $disclosure_script ) ) {
			wp_enqueue_script(
				'pfoa-pet-tidings-disclosure',
				get_template_directory_uri() . '/assets/js/pfoa-pet-tidings-disclosure.js',
				array(),
				(string) filemtime( $disclosure_script ),
				array( 'strategy' => 'defer' )
			);
		}
	}

	// PFOA 0.1.203 — presentation only: bonded-pair spotlight focus
	// (adoptablecats2 page only, and only when ?bonded-pair=<a>-<b> parses).
	// Server HTML already shows the spotlight at the top; the script only
	// moves keyboard focus to it and ensures the anchor. No-ops otherwise,
	// and safe with JS disabled.
	if ( function_exists( 'is_page' ) && ( is_page( 20323 ) || is_page( 'adoptablecats2' ) ) ) {
		if ( function_exists( 'pfoa_is_bonded_pair_spotlight_requested' ) && array() !== pfoa_is_bonded_pair_spotlight_requested() ) {
			$spotlight_script = get_template_directory() . '/assets/js/pfoa-bonded-pair-spotlight.js';

			if ( file_exists( $spotlight_script ) ) {
				wp_enqueue_script(
					'pfoa-bonded-pair-spotlight',
					get_template_directory_uri() . '/assets/js/pfoa-bonded-pair-spotlight.js',
					array(),
					(string) filemtime( $spotlight_script ),
					array( 'strategy' => 'defer' )
				);
			}
		}
	}

	// PFOA 0.1.216 — presentation only: From the Home Front archive links
	// (page 11290 / fromthehomefront-2 only) open in a separate browser tab
	// so the archive listing stays available. Same-tab navigation forces a
	// full reload of the heavy article (large images + YouTube embed) and
	// loses the reader's archive position. Scoped to archive-listing anchors
	// only (div.pfoa-hf-archive rows plus the retired p.pfoa-hf-archive__list
	// variant); no-ops without archive links.
	if ( function_exists( 'is_page' ) && ( is_page( 11290 ) || is_page( 'fromthehomefront-2' ) ) ) {
		$hf_archive_script = get_template_directory() . '/assets/js/pfoa-home-front-archive.js';

		if ( file_exists( $hf_archive_script ) ) {
			wp_enqueue_script(
				'pfoa-home-front-archive',
				get_template_directory_uri() . '/assets/js/pfoa-home-front-archive.js',
				array(),
				(string) filemtime( $hf_archive_script ),
				array( 'strategy' => 'defer' )
			);
		}
	}

	if ( is_front_page() ) {
		$front_page_id = (int) get_option( 'page_on_front' );

		if ( $front_page_id ) {
			$front_hero = pfoa_get_hero( $front_page_id );

			if ( array() !== $front_hero['carousel_ids'] ) {
				$hero_script = get_template_directory() . '/assets/js/homepage-hero.js';

				if ( file_exists( $hero_script ) ) {
					wp_enqueue_script(
						'pfoa-homepage-hero',
						get_template_directory_uri() . '/assets/js/homepage-hero.js',
						array(),
						(string) filemtime( $hero_script ),
						array( 'strategy' => 'defer' )
					);
				}
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'pfoa_enqueue_assets' );

/**
 * Add the small amount of site configuration owned by the theme.
 *
 * The destination is intentionally a URL setting rather than a payment
 * setting. Payment-provider configuration remains outside the theme and can
 * continue to be managed by WordPress or an approved plugin.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager instance.
 * @return void
 */
function pfoa_customize_register( $wp_customize ) {
	$wp_customize->add_section(
		'pfoa_header',
		array(
			'title'    => esc_html__( 'PFOA Header', 'pfoa-theme' ),
			'priority' => 30,
		)
	);

	$wp_customize->add_setting(
		'pfoa_donate_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_donate_url',
		array(
			'label'       => esc_html__( 'Donate destination', 'pfoa-theme' ),
			'description' => esc_html__( 'Enter the approved donation or donation-information URL. Leave blank to use the site\'s Membership page until a final destination is configured.', 'pfoa-theme' ),
			'section'     => 'pfoa_header',
			'type'        => 'url',
		)
	);

	$wp_customize->add_section(
		'pfoa_footer',
		array(
			'title'    => esc_html__( 'PFOA Footer', 'pfoa-theme' ),
			'priority' => 35,
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_hours',
		array(
			'default'           => '11:00 am–4:00 pm Tuesday–Saturday, by appointment.',
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_hours',
		array(
			'label'       => esc_html__( 'Public hours', 'pfoa-theme' ),
			'description' => esc_html__( 'Use line breaks if needed. Leave blank to omit hours from the footer.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_mailing_address',
		array(
			'default'           => "P.O. Box 404\nSequim, WA 98382",
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_mailing_address',
		array(
			'label'       => esc_html__( 'Mailing address', 'pfoa-theme' ),
			'description' => esc_html__( 'Use line breaks between address lines. Leave blank to omit this address.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_physical_address',
		array(
			'default'           => "257509 Hwy 101\nPort Angeles, WA",
			'sanitize_callback' => 'sanitize_textarea_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_physical_address',
		array(
			'label'       => esc_html__( 'Physical address', 'pfoa-theme' ),
			'description' => esc_html__( 'Use line breaks between address lines. Leave blank to omit this address.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_phone',
		array(
			'default'           => '(360) 452-0414',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_phone',
		array(
			'label'   => esc_html__( 'Main phone', 'pfoa-theme' ),
			'section' => 'pfoa_footer',
			'type'    => 'text',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_fax',
		array(
			'default'           => '(360) 452-0412',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_fax',
		array(
			'label'       => esc_html__( 'Fax', 'pfoa-theme' ),
			'description' => esc_html__( 'Leave blank to omit fax information from the footer.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_facebook_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_facebook_url',
		array(
			'label'       => esc_html__( 'Facebook URL', 'pfoa-theme' ),
			'description' => esc_html__( 'Enter the approved Facebook URL, or leave blank to omit the link.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'url',
		)
	);

	$wp_customize->add_setting(
		'pfoa_footer_instagram_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'pfoa_footer_instagram_url',
		array(
			'label'       => esc_html__( 'Instagram URL', 'pfoa-theme' ),
			'description' => esc_html__( 'Enter the approved Instagram URL, or leave blank to omit the link.', 'pfoa-theme' ),
			'section'     => 'pfoa_footer',
			'type'        => 'url',
		)
	);
}
add_action( 'customize_register', 'pfoa_customize_register' );

/**
 * Return the safe internal fallback used for the header Donate action.
 *
 * @return string
 */
function pfoa_get_donation_fallback_url() {
	$membership_page = get_page_by_path( 'membership' );

	if ( $membership_page instanceof WP_Post ) {
		$membership_url = get_permalink( $membership_page );

		if ( $membership_url ) {
			return $membership_url;
		}
	}

	return home_url( '/' );
}

/**
 * Return the configured Donate destination without embedding payment data.
 *
 * @return string
 */
function pfoa_get_donation_url() {
	$donation_url = get_theme_mod( 'pfoa_donate_url', '' );

	return $donation_url ? $donation_url : pfoa_get_donation_fallback_url();
}

/**
 * Return a safe Donate destination for homepage components.
 *
 * Unlike the header/footer compatibility fallback, homepage action cards must
 * not become a self-link when no configured or published destination exists.
 *
 * @return string
 */
function pfoa_get_homepage_donation_url() {
	$donation_url = get_theme_mod( 'pfoa_donate_url', '' );

	if ( $donation_url ) {
		return $donation_url;
	}

	$membership_page = pfoa_get_page_by_paths( array( 'membership' ) );

	if ( $membership_page ) {
		$membership_url = get_permalink( $membership_page );

		if ( $membership_url ) {
			return $membership_url;
		}
	}

	return '';
}

/**
 * Find the first published Page matching one of the supplied slugs.
 *
 * This keeps homepage teasers pointed at existing WordPress Pages without
 * creating a theme-owned content model or assuming that every destination is
 * present on every installation.
 *
 * @param string[] $paths Candidate Page slugs, in preference order.
 * @return WP_Post|null
 */
function pfoa_get_page_by_paths( $paths ) {
	foreach ( (array) $paths as $path ) {
		$page = get_page_by_path( trim( (string) $path, '/' ), OBJECT, 'page' );

		if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
			return $page;
		}
	}

	return null;
}

/**
 * Whether a post ID is an eligible internal page-link destination.
 *
 * Eligible: a published WordPress Page. Drafts, trashed pages, and
 * non-page post types are rejected so selectors can never persist an
 * unusable destination.
 *
 * @param mixed $post_id Candidate destination.
 * @return bool
 */
function pfoa_is_page_link_eligible( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return false;
	}

	if ( function_exists( 'get_post_status' ) && 'publish' !== get_post_status( $post_id ) ) {
		return false;
	}

	if ( function_exists( 'get_post_type' ) && 'page' !== get_post_type( $post_id ) ) {
		return false;
	}

	return true;
}

/**
 * Sanitize a page-selector destination.
 *
 * Accepts only a published Page ID; anything else becomes 0 (no link).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_page_link_id( $value ) {
	$page_id = absint( $value );

	if ( ! pfoa_is_page_link_eligible( $page_id ) ) {
		return 0;
	}

	return $page_id;
}

/**
 * Map a legacy internal URL to its published Page ID.
 *
 * Same-host (or relative) URLs resolve through url_to_postid() when
 * available with a get_page_by_path() fallback so child-page paths
 * (parent/child) keep working. External hosts return 0. Full-path
 * mismatches return 0 rather than guessing a last-segment match so
 * ambiguous selectors are reported instead of silently remapped.
 *
 * @param mixed $url Raw stored URL.
 * @return int Published Page ID, or 0 when external/unmatched.
 */
function pfoa_migrate_url_to_page_id( $url ) {
	if ( ! is_string( $url ) ) {
		return 0;
	}

	$url = trim( $url );

	if ( '' === $url ) {
		return 0;
	}

	if ( function_exists( 'url_to_postid' ) ) {
		$mapped = (int) url_to_postid( $url );

		if ( $mapped && pfoa_is_page_link_eligible( $mapped ) ) {
			return $mapped;
		}
	}

	$parts = parse_url( $url );

	if ( ! is_array( $parts ) ) {
		return 0;
	}

	if ( isset( $parts['host'] ) && '' !== $parts['host'] ) {
		$url_host = strtolower( (string) $parts['host'] );
		$home_host = '';

		if ( function_exists( 'home_url' ) ) {
			$home = home_url( '/' );
			$parsed_home = parse_url( $home );
			if ( is_array( $parsed_home ) && isset( $parsed_home['host'] ) ) {
				$home_host = strtolower( (string) $parsed_home['host'] );
			}
		}

		$known_hosts = array( 'dev.safehavenpfoa.org', 'safehavenpfoa.org', 'www.safehavenpfoa.org' );

		if ( '' !== $home_host ) {
			if ( $url_host !== $home_host && ! in_array( $url_host, $known_hosts, true ) ) {
				return 0;
			}
		} elseif ( ! in_array( $url_host, $known_hosts, true ) ) {
			return 0;
		}
	}

	$path = isset( $parts['path'] ) ? (string) $parts['path'] : $url;
	$path = trim( $path, '/' );

	if ( '' === $path ) {
		if ( function_exists( 'get_option' ) ) {
			$front = (int) get_option( 'page_on_front' );

			if ( $front && pfoa_is_page_link_eligible( $front ) ) {
				return $front;
			}
		}

		return 0;
	}

	if ( ! function_exists( 'get_page_by_path' ) ) {
		return 0;
	}

	$page = get_page_by_path( $path, OBJECT, 'page' );

	if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
		return (int) $page->ID;
	}

	return 0;
}

/**
 * Return published Pages as hierarchical selector choices.
 *
 * Siblings order by menu_order then title; children follow their parent
 * in depth-first order. Labels use the full ancestor breadcrumb
 * ("Parent / Child") so child pages are clearly distinguished by title
 * and hierarchy even when leaf titles collide.
 *
 * @return array[] Each item: array( 'id' => int, 'label' => string, 'depth' => int ).
 */
function pfoa_get_page_selector_choices() {
	if ( ! function_exists( 'get_pages' ) ) {
		return array();
	}

	$pages = get_pages(
		array(
			'sort_column' => 'menu_order, post_title',
		)
	);

	if ( ! is_array( $pages ) ) {
		return array();
	}

	$by_id = array();

	foreach ( $pages as $page ) {
		if ( ! $page instanceof WP_Post ) {
			continue;
		}

		if ( 'publish' !== $page->post_status ) {
			continue;
		}

		if ( isset( $page->post_type ) && '' !== $page->post_type && 'page' !== $page->post_type ) {
			continue;
		}

		$by_id[ (int) $page->ID ] = $page;
	}

	if ( array() === $by_id ) {
		return array();
	}

	$children = array();

	foreach ( $by_id as $id => $page ) {
		$parent = isset( $page->post_parent ) ? (int) $page->post_parent : 0;

		if ( ! isset( $by_id[ $parent ] ) ) {
			$parent = 0;
		}

		$children[ $parent ][] = $page;
	}

	foreach ( $children as $parent_id => $siblings ) {
		usort(
			$children[ $parent_id ],
			function ( $a, $b ) {
				$a_order = isset( $a->menu_order ) ? (int) $a->menu_order : 0;
				$b_order = isset( $b->menu_order ) ? (int) $b->menu_order : 0;

				if ( $a_order !== $b_order ) {
					return $a_order <=> $b_order;
				}

				$a_title = isset( $a->post_title ) ? (string) $a->post_title : '';
				$b_title = isset( $b->post_title ) ? (string) $b->post_title : '';

				return strcasecmp( $a_title, $b_title );
			}
		);
	}

	$choices = array();

	$walk = null;
	$walk = function ( $parent_id, $breadcrumb, $depth ) use ( &$walk, &$children, &$choices ) {
		if ( empty( $children[ $parent_id ] ) ) {
			return;
		}

		foreach ( $children[ $parent_id ] as $page ) {
			$title = isset( $page->post_title ) ? (string) $page->post_title : '';

			if ( '' === trim( $title ) ) {
				$title = sprintf( __( '(no title) #%d', 'pfoa-theme' ), (int) $page->ID );
			}

			$label = '' !== $breadcrumb ? $breadcrumb . ' / ' . $title : $title;

			$choices[] = array(
				'id'    => (int) $page->ID,
				'label' => $label,
				'depth' => (int) $depth,
			);

			$walk( (int) $page->ID, $label, $depth + 1 );
		}
	};

	$walk( 0, '', 0 );

	return $choices;
}

/**
 * Render a pages-only destination selector.
 *
 * Published Pages only (no Cat Profiles, no bonded pairs): internal
 * navigation destinations resolve to Page IDs and permalinks are derived
 * with get_permalink() at render time so slug changes apply automatically.
 *
 * @param string $name     Select field name.
 * @param int    $selected Currently selected Page ID (0 = no link).
 * @param string $id       Optional select element ID.
 * @return void
 */
function pfoa_render_page_selector( $name, $selected = 0, $id = '' ) {
	$selected = absint( $selected );
	$choices  = pfoa_get_page_selector_choices();
	?>
	<select name="<?php echo esc_attr( $name ); ?>"<?php echo '' !== $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?>>
		<option value="0"<?php selected( $selected, 0 ); ?>><?php echo esc_html__( '— No link —', 'pfoa-theme' ); ?></option>
		<?php foreach ( $choices as $choice ) : ?>
			<option value="<?php echo esc_attr( (string) $choice['id'] ); ?>"<?php selected( $selected, $choice['id'] ); ?>><?php echo esc_html( $choice['label'] ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

/**
 * Return the effective destination URL for a homepage card.
 *
 * Page-ID destinations resolve dynamically with get_permalink() so page
 * slug changes apply automatically. Legacy stored URLs keep rendering
 * (with an in-memory URL-to-ID migration first) so pre-migration rows
 * never go blank; unmatched/external legacy URLs fall back to the stored
 * URL verbatim.
 *
 * @param mixed $card Card row.
 * @return string
 */
function pfoa_get_homepage_card_url( $card ) {
	if ( ! is_array( $card ) ) {
		return '';
	}

	$page_id = isset( $card['page_id'] ) ? absint( $card['page_id'] ) : 0;

	if ( $page_id && pfoa_is_page_link_eligible( $page_id ) ) {
		$url = get_permalink( $page_id );

		if ( $url ) {
			return $url;
		}
	}

	$legacy_url = isset( $card['url'] ) ? trim( (string) $card['url'] ) : '';

	if ( '' !== $legacy_url ) {
		$migrated = pfoa_migrate_url_to_page_id( $legacy_url );

		if ( $migrated && pfoa_is_page_link_eligible( $migrated ) ) {
			$migrated_url = get_permalink( $migrated );

			if ( $migrated_url ) {
				return $migrated_url;
			}
		}

		return $legacy_url;
	}

	return '';
}

/**
 * Return the effective destination URL for a hero shortcut button (CTA).
 *
 * Same dynamic-resolution contract as homepage cards: Page IDs resolve
 * with get_permalink(); legacy URLs migrate in memory; unmatched legacy
 * URLs fall back verbatim so nothing breaks before migration.
 *
 * @param mixed $cta CTA row.
 * @return string
 */
function pfoa_get_hero_cta_url( $cta ) {
	if ( ! is_array( $cta ) ) {
		return '';
	}

	$page_id = isset( $cta['page_id'] ) ? absint( $cta['page_id'] ) : 0;

	if ( $page_id && pfoa_is_page_link_eligible( $page_id ) ) {
		$url = get_permalink( $page_id );

		if ( $url ) {
			return $url;
		}
	}

	$legacy_url = isset( $cta['url'] ) ? trim( (string) $cta['url'] ) : '';

	if ( '' !== $legacy_url ) {
		$migrated = pfoa_migrate_url_to_page_id( $legacy_url );

		if ( $migrated && pfoa_is_page_link_eligible( $migrated ) ) {
			$migrated_url = get_permalink( $migrated );

			if ( $migrated_url ) {
				return $migrated_url;
			}
		}

		return $legacy_url;
	}

	return '';
}

/**
 * Option name storing the editable homepage pathway cards.
 *
 * @var string
 */
define( 'PFOA_HOMEPAGE_CARDS_OPTION', 'pfoa_homepage_cards' );

/**
 * Return the theme-owned default homepage pathway cards.
 *
 * URLs resolve through pfoa_get_page_by_paths() so a fresh install renders
 * the same destinations as the previous hard-coded template without any
 * manual re-entry. Cards whose page is missing resolve to an empty URL and
 * render the template's unavailable state.
 *
 * @return array[]
 */
function pfoa_get_homepage_card_defaults() {
	$adoptable_cats_page = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
	$home_front_page     = pfoa_get_page_by_paths( array( 'fromthehomefront-2', 'news-announcements' ) );
	$potholders_page     = pfoa_get_page_by_paths( array( 'potholders-2' ) );
	$pet_tidings_page    = pfoa_get_page_by_paths( array( 'pettidings' ) );
	$wishlist_page       = pfoa_get_page_by_paths( array( 'wishlist' ) );

	return array(
		array(
			'key'      => 'adoptable-cats',
			'title'    => __( 'Adoptable Cats', 'pfoa-theme' ),
			'mark'     => 'A',
			'page_id'  => $adoptable_cats_page ? (int) $adoptable_cats_page->ID : 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'home-front',
			'title'    => __( 'From the Home Front', 'pfoa-theme' ),
			'mark'     => 'F',
			'page_id'  => $home_front_page ? (int) $home_front_page->ID : 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'potholders',
			'title'    => __( 'Pot Holders', 'pfoa-theme' ),
			'mark'     => 'P',
			'page_id'  => $potholders_page ? (int) $potholders_page->ID : 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'pet-tidings',
			'title'    => __( 'Pet Tidings', 'pfoa-theme' ),
			'mark'     => 'P',
			'page_id'  => $pet_tidings_page ? (int) $pet_tidings_page->ID : 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'wishlist',
			'title'    => __( 'Wish List', 'pfoa-theme' ),
			'mark'     => 'W',
			'page_id'  => $wishlist_page ? (int) $wishlist_page->ID : 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
	);
}

/**
 * Sanitize the homepage cards option value. No raw HTML is allowed.
 *
 * @param mixed $value Raw submitted value.
 * @return array[]
 */
function pfoa_sanitize_homepage_cards( $value ) {
	$cards = array();

	if ( ! is_array( $value ) ) {
		return $cards;
	}

	foreach ( $value as $card ) {
		if ( ! is_array( $card ) ) {
			continue;
		}

		$title    = isset( $card['title'] ) ? sanitize_text_field( $card['title'] ) : '';
		$mark     = isset( $card['mark'] ) ? sanitize_text_field( $card['mark'] ) : '';
		$button   = isset( $card['button'] ) ? sanitize_text_field( $card['button'] ) : '';
		$page_id  = isset( $card['page_id'] ) ? pfoa_sanitize_page_link_id( $card['page_id'] ) : 0;
		$url      = isset( $card['url'] ) ? esc_url_raw( trim( (string) $card['url'] ) ) : '';
		$key      = isset( $card['key'] ) ? sanitize_title( $card['key'] ) : '';
		$image_id = isset( $card['image_id'] ) ? absint( $card['image_id'] ) : 0;

		if ( 0 === $page_id && '' !== $url ) {
			$page_id = pfoa_migrate_url_to_page_id( $url );
		}

		if ( $page_id ) {
			$url = '';
		}

		if ( '' === $key ) {
			$key = sanitize_title( $title );
		}

		if ( '' === $key ) {
			$key = 'card-' . ( count( $cards ) + 1 );
		}

		if ( function_exists( 'mb_substr' ) ) {
			$mark = mb_substr( $mark, 0, 1 );
		} else {
			$mark = substr( $mark, 0, 1 );
		}

		if ( '' === $title && 0 === $page_id && '' === $url && '' === $button && '' === $mark ) {
			continue;
		}

		$cards[] = array(
			'key'      => $key,
			'title'    => $title,
			'mark'     => $mark,
			'page_id'  => $page_id,
			'url'      => $url,
			'button'   => '' === $button ? __( 'Explore', 'pfoa-theme' ) : $button,
			'image_id' => $image_id,
		);
	}

	return $cards;
}

/**
 * Return the effective homepage pathway cards: saved option, else defaults.
 *
 * @return array[]
 */
function pfoa_get_homepage_cards() {
	$saved = get_option( PFOA_HOMEPAGE_CARDS_OPTION, array() );

	if ( is_array( $saved ) && array() !== $saved ) {
		return pfoa_sanitize_homepage_cards( $saved );
	}

	return pfoa_get_homepage_card_defaults();
}

/**
 * Register the homepage cards setting.
 *
 * @return void
 */
function pfoa_homepage_cards_admin_init() {
	register_setting(
		'pfoa_homepage_cards_group',
		PFOA_HOMEPAGE_CARDS_OPTION,
		array(
			'sanitize_callback' => 'pfoa_sanitize_homepage_cards',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'pfoa_homepage_cards_admin_init' );

/**
 * Homepage Cards admin location (consolidated 0.1.21).
 *
 * The standalone Appearance > Homepage Cards screen was removed. Cards are
 * now edited in the single authoritative location PFOA Site > Homepage via
 * pfoa_homepage_cards_section(). No add_theme_page/add_submenu_page
 * registration remains here on purpose; stored option data is untouched.
 *
 * Capability note: cards still require edit_theme_options (same check as
 * before, enforced in pfoa_homepage_cards_handle_post() and the section
 * renderer). PFOA Site > Homepage itself requires edit_pages, so editors who
 * can edit pages but not theme options see the Hero Section only. This keeps
 * the previous security boundary instead of widening card editing to all
 * page editors.
 *
 * @return void
 */

/**
 * Enqueue the Homepage Cards image picker assets.
 *
 * Scoped to the PFOA Site > Homepage screen only (toplevel hook suffix);
 * the media library is loaded there and nowhere else.
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function pfoa_homepage_cards_admin_assets( $hook ) {
	if ( 'toplevel_page_pfoa-site' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$script = get_template_directory() . '/assets/js/admin-homepage-cards.js';

	if ( file_exists( $script ) ) {
		wp_enqueue_script(
			'pfoa-homepage-cards-admin',
			get_template_directory_uri() . '/assets/js/admin-homepage-cards.js',
			array( 'jquery' ),
			(string) filemtime( $script ),
			true
		);
	}
}
add_action( 'admin_enqueue_scripts', 'pfoa_homepage_cards_admin_assets' );

/**
 * Handle add/remove/reorder/save submissions for the Homepage Cards page.
 *
 * Plain PHP + submit only: every action posts the full field set back to the
 * same page, the requested mutation is applied, and the sanitized result is
 * stored with update_option().
 *
 * @return void
 */
function pfoa_homepage_cards_handle_post() {
	if ( ! isset( $_POST['pfoa_homepage_cards_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	check_admin_referer( 'pfoa_homepage_cards_save', 'pfoa_homepage_cards_nonce' );

	$raw   = isset( $_POST[ PFOA_HOMEPAGE_CARDS_OPTION ] ) && is_array( $_POST[ PFOA_HOMEPAGE_CARDS_OPTION ] ) ? wp_unslash( $_POST[ PFOA_HOMEPAGE_CARDS_OPTION ] ) : array();
	$cards = pfoa_sanitize_homepage_cards( $raw );

	if ( isset( $_POST['pfoa_cards_add'] ) ) {
		$cards[] = array(
			'key'      => '',
			'title'    => '',
			'mark'     => '',
			'page_id'  => 0,
			'url'      => '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		);
	} elseif ( isset( $_POST['pfoa_cards_remove'] ) && is_array( $_POST['pfoa_cards_remove'] ) ) {
		$keys = array_map( 'absint', array_keys( $_POST['pfoa_cards_remove'] ) );
		$key  = reset( $keys );
		if ( false !== $key && isset( $cards[ $key ] ) ) {
			unset( $cards[ $key ] );
			$cards = array_values( $cards );
		}
	} elseif ( isset( $_POST['pfoa_cards_up'] ) && is_array( $_POST['pfoa_cards_up'] ) ) {
		$keys = array_map( 'absint', array_keys( $_POST['pfoa_cards_up'] ) );
		$key  = reset( $keys );
		if ( false !== $key && $key > 0 && isset( $cards[ $key ] ) && isset( $cards[ $key - 1 ] ) ) {
			$tmp                 = $cards[ $key - 1 ];
			$cards[ $key - 1 ]   = $cards[ $key ];
			$cards[ $key ]       = $tmp;
		}
	} elseif ( isset( $_POST['pfoa_cards_down'] ) && is_array( $_POST['pfoa_cards_down'] ) ) {
		$keys = array_map( 'absint', array_keys( $_POST['pfoa_cards_down'] ) );
		$key  = reset( $keys );
		if ( false !== $key && isset( $cards[ $key ] ) && isset( $cards[ $key + 1 ] ) ) {
			$tmp               = $cards[ $key + 1 ];
			$cards[ $key + 1 ] = $cards[ $key ];
			$cards[ $key ]     = $tmp;
		}
	}

	update_option( PFOA_HOMEPAGE_CARDS_OPTION, $cards );

	add_settings_error(
		'pfoa_homepage_cards_messages',
		'pfoa_homepage_cards_saved',
		esc_html__( 'Homepage cards updated.', 'pfoa-theme' ),
		'success'
	);
}

/**
 * Render the Homepage Cards section (PFOA Site > Homepage, below Hero).
 *
 * Same fields and behavior as the former standalone Appearance page: field
 * names pfoa_homepage_cards[N][key/title/mark/image_id/page_id/button], actions
 * pfoa_cards_add/save/up/down/remove, nonce pfoa_homepage_cards_save /
 * pfoa_homepage_cards_nonce, storage via update_option() in
 * pfoa_homepage_cards_handle_post(). Separate save form from the Hero
 * Section is intentional. Legacy stored URLs migrate in memory to Page IDs;
 * unmatched legacy URLs render as a note with a hidden fallback so saving
 * never silently drops them.
 *
 * Capability is still edit_theme_options, so page editors without that cap
 * simply do not see this section (see consolidated-location note above).
 *
 * @return void
 */
function pfoa_homepage_cards_section() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	pfoa_homepage_cards_handle_post();

	$cards = pfoa_get_homepage_cards();
	?>
		<h2><?php esc_html_e( 'Homepage Cards', 'pfoa-theme' ); ?></h2>
		<p><?php esc_html_e( 'Edit the cards shown in the homepage pathways section, in display order. Leave the list empty and save to restore the five default cards.', 'pfoa-theme' ); ?></p>
		<?php settings_errors( 'pfoa_homepage_cards_messages' ); ?>
		<form method="post" action="">
			<?php wp_nonce_field( 'pfoa_homepage_cards_save', 'pfoa_homepage_cards_nonce' ); ?>
			<style>
.pfoa-homepage-cards-list{list-style:none;margin:0 0 12px;padding:0;max-width:100%;}
.pfoa-homepage-cards-list .pfoa-homepage-cards-item{margin:0 0 12px;padding:0;max-width:100%;}
.pfoa-homepage-cards-list .pfoa-homepage-card-card{background:#fff;border:1px solid #c8c0b5;border-radius:6px;max-width:100%;box-sizing:border-box;overflow:hidden;}
.pfoa-homepage-cards-list .pfoa-homepage-card-summary{display:flex;align-items:center;gap:10px;padding:10px 12px;margin:0;cursor:pointer;list-style:none;max-width:100%;box-sizing:border-box;}
.pfoa-homepage-cards-list .pfoa-homepage-card-summary::-webkit-details-marker{display:none;}
.pfoa-homepage-cards-list .pfoa-homepage-card-summary::before{content:"\25b8";flex:none;color:#50575e;font-size:14px;line-height:1;}
.pfoa-homepage-cards-list .pfoa-homepage-card-card[open] .pfoa-homepage-card-summary::before{content:"\25be";}
.pfoa-homepage-cards-list .pfoa-homepage-card-summary:focus-visible{outline:2px solid #2271b1;outline-offset:2px;}
.pfoa-homepage-cards-list .pfoa-homepage-card-badge{flex:none;font-weight:600;white-space:nowrap;}
.pfoa-homepage-cards-list .pfoa-homepage-card-thumb{flex:none;width:44px;height:44px;}
.pfoa-homepage-cards-list .pfoa-homepage-card-thumb img{width:44px;height:44px;object-fit:cover;display:block;border-radius:4px;}
.pfoa-homepage-cards-list .pfoa-homepage-card-empty{display:block;width:44px;height:44px;box-sizing:border-box;border:1px dashed #c8c0b5;border-radius:4px;background:#f6f5f2;}
.pfoa-homepage-cards-list .pfoa-homepage-card-title{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;}
.pfoa-homepage-cards-list .pfoa-homepage-card-destination{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#50575e;}
.pfoa-homepage-cards-list .pfoa-homepage-card-legacy{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#50575e;font-style:italic;font-size:12px;}
.pfoa-homepage-cards-list .pfoa-homepage-card-button{flex:none;font-size:12px;line-height:1.6;padding:2px 10px;border-radius:999px;border:1px solid #dcdcde;white-space:nowrap;max-width:220px;overflow:hidden;text-overflow:ellipsis;}
.pfoa-homepage-cards-list .pfoa-homepage-card-body{padding:4px 12px 12px;border-top:1px solid #dcdcde;max-width:100%;box-sizing:border-box;}
.pfoa-homepage-cards-list .pfoa-homepage-card-section{padding:10px 0;max-width:100%;}
.pfoa-homepage-cards-list .pfoa-homepage-card-section + .pfoa-homepage-card-section{border-top:1px solid #dcdcde;}
.pfoa-homepage-cards-list .pfoa-homepage-card-heading{margin:0 0 8px;font-size:13px;line-height:1.4;}
.pfoa-homepage-cards-list .pfoa-homepage-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px 12px;max-width:100%;}
.pfoa-homepage-cards-list .pfoa-homepage-card-body label{display:block;margin:8px 0;max-width:100%;}
.pfoa-homepage-cards-list .pfoa-homepage-card-grid label{margin:0;}
.pfoa-homepage-cards-list .pfoa-homepage-card-body input[type="text"],.pfoa-homepage-cards-list .pfoa-homepage-card-body select{max-width:100%;box-sizing:border-box;}
.pfoa-homepage-cards-list .pfoa-homepage-card-actions{display:flex;gap:8px;flex-wrap:wrap;max-width:100%;}
</style>
			<ul class="pfoa-homepage-cards-list">
					<?php foreach ( $cards as $index => $card ) : ?>
					<?php
					$card_image_id = isset( $card['image_id'] ) ? absint( $card['image_id'] ) : 0;
					$card_preview  = $card_image_id ? wp_get_attachment_image( $card_image_id, array( 80, 80 ) ) : '';
					$card_page_id  = isset( $card['page_id'] ) ? absint( $card['page_id'] ) : 0;
					$card_legacy_url = isset( $card['url'] ) ? trim( (string) $card['url'] ) : '';
					$card_summary_thumb = $card_preview ? $card_preview : '<span class="pfoa-homepage-card-empty" aria-hidden="true"></span>';
					$card_summary_title = '' !== trim( (string) $card['title'] ) ? $card['title'] : __( '— No title —', 'pfoa-theme' );
					$card_summary_destination = __( '— No link —', 'pfoa-theme' );
					if ( $card_page_id ) {
						$card_linked_title = get_the_title( $card_page_id );
						if ( '' !== trim( (string) $card_linked_title ) ) {
							$card_summary_destination = $card_linked_title;
						}
					}
					$card_summary_button = isset( $card['button'] ) && '' !== trim( (string) $card['button'] ) ? $card['button'] : '—';
					?>
						<li class="pfoa-homepage-cards-item">
							<details class="pfoa-homepage-card-card"<?php echo 0 === $index ? ' open' : ''; ?>>
								<summary class="pfoa-homepage-card-summary">
									<span class="pfoa-homepage-card-badge"><?php printf( esc_html__( 'Card %d', 'pfoa-theme' ), $index + 1 ); ?></span>
									<span class="pfoa-homepage-card-thumb"><?php echo $card_summary_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
									<span class="pfoa-homepage-card-title"><?php echo esc_html( $card_summary_title ); ?></span>
									<span class="pfoa-homepage-card-destination"><?php echo esc_html( $card_summary_destination ); ?></span>
									<?php if ( 0 === $card_page_id && '' !== $card_legacy_url ) : ?>
										<span class="pfoa-homepage-card-legacy"><?php printf( esc_html__( 'Legacy URL: %s', 'pfoa-theme' ), esc_html( $card_legacy_url ) ); ?></span>
									<?php endif; ?>
									<span class="pfoa-homepage-card-button"><?php echo esc_html( $card_summary_button ); ?></span>
								</summary>
								<div class="pfoa-homepage-card-body">
									<div class="pfoa-homepage-card-grid">
										<div>
											<input type="hidden" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][key]" value="<?php echo esc_attr( isset( $card['key'] ) ? $card['key'] : '' ); ?>" />
											<label><?php esc_html_e( 'Title', 'pfoa-theme' ); ?><br />
												<input type="text" class="regular-text pfoa-homepage-card-title-input" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $card['title'] ); ?>" />
											</label>
											<label><?php esc_html_e( 'Mark', 'pfoa-theme' ); ?><br />
												<input type="text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][mark]" value="<?php echo esc_attr( $card['mark'] ); ?>" size="2" maxlength="1" />
											</label>
											<label><?php esc_html_e( 'Button', 'pfoa-theme' ); ?><br />
												<input type="text" class="pfoa-homepage-card-button-input" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][button]" value="<?php echo esc_attr( $card['button'] ); ?>" />
											</label>
										</div>
										<div class="pfoa-cards-image-cell">
											<strong><?php esc_html_e( 'Image', 'pfoa-theme' ); ?></strong><br />
											<input type="hidden" class="pfoa-cards-image-id" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][image_id]" value="<?php echo esc_attr( (string) $card_image_id ); ?>" />
											<span class="pfoa-cards-image-preview"><?php echo $card_preview ? $card_preview : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
											<button type="button" class="button pfoa-cards-image-select" data-select-label="<?php esc_attr_e( 'Select Image', 'pfoa-theme' ); ?>" data-replace-label="<?php esc_attr_e( 'Replace', 'pfoa-theme' ); ?>"><?php echo $card_preview ? esc_html__( 'Replace', 'pfoa-theme' ) : esc_html__( 'Select Image', 'pfoa-theme' ); ?></button>
											<button type="button" class="button pfoa-cards-image-remove"<?php echo $card_preview ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
										</div>
										<div>
											<strong><?php esc_html_e( 'Destination Page', 'pfoa-theme' ); ?></strong><br />
											<?php pfoa_render_page_selector( PFOA_HOMEPAGE_CARDS_OPTION . '[' . $index . '][page_id]', $card_page_id ); ?>
											<?php if ( 0 === $card_page_id && '' !== $card_legacy_url ) : ?>
												<p class="description"><?php printf( esc_html__( 'Legacy URL: %s — select a page to migrate.', 'pfoa-theme' ), esc_html( $card_legacy_url ) ); ?></p>
												<input type="hidden" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $card_legacy_url ); ?>" />
											<?php endif; ?>
										</div>
									</div>
									<p class="pfoa-homepage-card-actions">
										<button type="submit" class="button" name="pfoa_cards_up[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
										<button type="submit" class="button" name="pfoa_cards_down[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
										<button type="submit" class="button" name="pfoa_cards_remove[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
									</p>
								</div>
							</details>
						</li>
					<?php endforeach; ?>
					<?php if ( array() === $cards ) : ?>
						<li class="pfoa-homepage-cards-item"><?php esc_html_e( 'No cards saved. Add one below or save to restore the defaults.', 'pfoa-theme' ); ?></li>
					<?php endif; ?>
			</ul>
			<p class="submit">
				<button type="submit" class="button" name="pfoa_cards_add" value="1"><?php esc_html_e( 'Add', 'pfoa-theme' ); ?></button>
				<button type="submit" class="button button-primary" name="pfoa_cards_save" value="1"><?php esc_html_e( 'Save Cards', 'pfoa-theme' ); ?></button>
			</p>
		</form>
	<?php
}

/**
 * Backward-compatibility wrapper for the former Appearance page callback.
 *
 * No menu registers this anymore; kept so any direct call still renders the
 * same cards UI. New code should rely on pfoa_homepage_cards_section() inside
 * PFOA Site > Homepage.
 *
 * @return void
 */
function pfoa_homepage_cards_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Homepage Cards', 'pfoa-theme' ); ?></h1>
		<?php pfoa_homepage_cards_section(); ?>
	</div>
	<?php
}

/**
 * Meta key storing the editable homepage hero as a single array.
 *
 * Shape: array(
 *   'mode'               => 'carousel' (legacy stored 'single' values normalize to 'carousel'; no mode control remains),
 *   'headline'           => string,
 *   'image_id'           => int (legacy Single Image attachment ID, stored-shape BC only; always 0 after save, no longer edited),
 *   'image_link_page_id' => int|string (legacy Single Image destination, stored-shape BC only; always 0 after save, no longer edited),
 *   'image_link_type'    => 'internal' | 'external' | 'none' (legacy Single Image link type, stored-shape BC only; always 'none' after save, no longer edited),
 *   'image_link_url'     => string (legacy Single Image external URL, stored-shape BC only; always '' after save, no longer edited),
 *   'featured_pin'       => array( 'slide' => 0|1-8 (1-based position at save; 0 = normal rotation), 'attachment_id' => int (snapshot), 'mode' => 'none'|'timed'|'indefinite', 'expires_at' => int (UTC timestamp, site-local via current_time), 'created_at' => int ),
 *   'carousel_ids'       => int[] (attachment IDs, display order; derived from carousel_items),
 *   'carousel_items'     => array[] of array( 'id' => int, 'link_page_id' => int|string (page/Cat Profile post ID or 'pair:<a>:<b>' bonded-pair token), 'link_type' => 'internal'|'external'|'none' (missing legacy rows resolve as internal when link_page_id is non-zero, else none), 'link_url' => string (absolute http/https URL used when link_type is 'external'), 'headline' => string, 'brightness' => 100|110|120|130, 'headline_scale' => 50|60|70|80|90|100|110|120, 'headline_color' => 'white'|'black', 'zoom' => 50|60|70|80|90|100|110|120|130|140|150, 'position' => 'top left'|'top center'|'top right'|'center left'|'center center'|'center right'|'bottom left'|'bottom center'|'bottom right' (space-separated CSS object-position values; 'center' alias normalizes to 'center center'; missing/invalid resolves to 'center center') ),
 *   'carousel_interval_ms' => 3500|5000|7000 (ms each carousel image stays visible; missing/invalid resolves to 5000),
 *   'ctas'               => array[] of array( 'label' => string, 'page_id' => int (published Page ID; 0 = no link), 'url' => string (legacy fallback only when page_id is 0; '' otherwise) ),
 * ).
 *
 * @var string
 */
define( 'PFOA_HERO_META_KEY', '_pfoa_hero' );

/**
 * Return the default homepage hero values.
 *
 * CTA destinations resolve through pfoa_get_page_by_paths() so a fresh
 * install renders the same Adopt/Volunteer destinations as the previous
 * hard-coded template without any manual re-entry.
 *
 * @return array
 */
function pfoa_get_hero_defaults() {
	$adoption_page     = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );
	$volunteering_page = pfoa_get_page_by_paths( array( 'volunteering' ) );

	$ctas = array();

	if ( $adoption_page ) {
		$ctas[] = array(
			'label'   => __( 'Adopt', 'pfoa-theme' ),
			'page_id' => (int) $adoption_page->ID,
			'url'     => '',
		);
	}

	if ( $volunteering_page ) {
		$ctas[] = array(
			'label'   => __( 'Volunteer', 'pfoa-theme' ),
			'page_id' => (int) $volunteering_page->ID,
			'url'     => '',
		);
	}

	return array(
		'mode'               => 'carousel',
		// Global hero headline removed: each carousel slide owns its own
		// headline. The 'headline' key stays for stored-shape BC (legacy
		// stored values are dropped on next save); single-image/pinned
		// rendering no longer reads it.
		'headline'           => '',
		// Legacy Single Image controls removed: the carousel is the sole
		// administrative source for hero images. These keys stay for
		// stored-shape BC (legacy stored values are dropped on next save).
		'image_id'           => 0,
		'image_link_page_id' => 0,
		'image_link_type'    => 'none',
		'image_link_url'     => '',
		'featured_pin'       => array(
			'slide'         => 0,
			'attachment_id' => 0,
			'mode'          => 'none',
			'expires_at'    => 0,
			'created_at'    => 0,
		),
		'carousel_ids'       => array(),
		'carousel_items'     => array(),
		'carousel_slide_count' => 3,
		'carousel_interval_ms' => 5000,
		'ctas'               => $ctas,
	);
}

/**
 * Whether a Cat Profile is complete for hero-link eligibility (HP-02).
 *
 * Mirrors the canonical pfoa-cat-profiles convention: profiles with no
 * explicitly stored state default to complete; only an explicitly stored
 * non-'1' value (thumbnail-only / coming-soon) counts as incomplete.
 *
 * @param int $post_id Cat Profile post ID.
 * @return bool
 */
function pfoa_is_hero_link_cat_complete( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id ) {
		return false;
	}

	if ( function_exists( 'pfoa_cat_is_profile_complete' ) ) {
		return (bool) pfoa_cat_is_profile_complete( $post_id );
	}

	if ( ! metadata_exists( 'post', $post_id, '_pfoa_cat_profile_complete' ) ) {
		return true;
	}

	return '1' === (string) get_post_meta( $post_id, '_pfoa_cat_profile_complete', true );
}

/**
 * Whether a post ID is an eligible hero image destination (HP-02).
 *
 * Eligible: published WordPress pages, plus published complete
 * pfoa_cat profiles. Anything else is rejected.
 *
 * @param int $post_id Candidate destination post ID.
 * @return bool
 */
function pfoa_is_hero_link_destination_eligible( $post_id ) {
	$post_id = absint( $post_id );

	if ( ! $post_id || 'publish' !== get_post_status( $post_id ) ) {
		return false;
	}

	$post_type = get_post_type( $post_id );

	if ( 'page' === $post_type ) {
		return true;
	}

	if ( 'pfoa_cat' === $post_type && pfoa_is_hero_link_cat_complete( $post_id ) ) {
		return true;
	}

	return false;
}

/**
 * Return published, complete Cat Profiles for hero-link selectors (HP-02).
 *
 * Alphabetically ordered by title. Returns an empty array when the
 * pfoa_cat post type is unavailable.
 *
 * @return WP_Post[]
 */
function pfoa_get_hero_link_cat_profiles() {
	if ( ! post_type_exists( 'pfoa_cat' ) ) {
		return array();
	}

	$cats = get_posts(
		array(
			'post_type'   => 'pfoa_cat',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);

	if ( ! is_array( $cats ) ) {
		return array();
	}

	$eligible = array();

	foreach ( $cats as $cat ) {
		if ( ! $cat instanceof WP_Post ) {
			continue;
		}

		if ( pfoa_is_hero_link_cat_complete( $cat->ID ) ) {
			$eligible[] = $cat;
		}
	}

	return $eligible;
}

/**
 * Build the canonical storage token for a bonded-pair hero destination.
 *
 * IDs normalize to ascending numeric order so 'pair:24396:24401' and
 * 'pair:24401:24396' describe the same destination (Oliver & Isaac).
 *
 * @param int $a First Cat Profile post ID.
 * @param int $b Second Cat Profile post ID.
 * @return string Token like 'pair:24396:24401'.
 */
function pfoa_hero_bonded_pair_token( $a, $b ) {
	$a = absint( $a );
	$b = absint( $b );

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	return 'pair:' . $a . ':' . $b;
}

/**
 * Parse a bonded-pair hero destination token.
 *
 * @param mixed $value Raw stored value.
 * @return int[] Two ascending post IDs, or an empty array when not a pair token.
 */
function pfoa_parse_hero_bonded_pair_token( $value ) {
	if ( ! is_string( $value ) ) {
		return array();
	}

	if ( 1 !== preg_match( '/^pair:(\d+):(\d+)$/', trim( $value ), $matches ) ) {
		return array();
	}

	$a = absint( $matches[1] );
	$b = absint( $matches[2] );

	if ( ! $a || ! $b || $a === $b ) {
		return array();
	}

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	return array( $a, $b );
}

/**
 * Whether two Cat Profiles form an eligible bonded-pair hero destination.
 *
 * Both profiles must be published, complete Cat Profiles whose canonical
 * bonded relationship (pfoa_cat_get_bonded_pair, both directions) resolves
 * to exactly this pair, and both must be publicly available for the
 * Adoptable Cats listing.
 *
 * @param int $a First Cat Profile post ID.
 * @param int $b Second Cat Profile post ID.
 * @return bool
 */
function pfoa_is_hero_bonded_pair_eligible( $a, $b ) {
	$a = absint( $a );
	$b = absint( $b );

	if ( ! $a || ! $b || $a === $b ) {
		return false;
	}

	foreach ( array( $a, $b ) as $member_id ) {
		if ( 'publish' !== get_post_status( $member_id ) || 'pfoa_cat' !== get_post_type( $member_id ) ) {
			return false;
		}

		if ( ! pfoa_is_hero_link_cat_complete( $member_id ) ) {
			return false;
		}
	}

	if ( ! function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
		return false;
	}

	$want = array( $a, $b );
	sort( $want );

	foreach ( array( $a, $b ) as $member_id ) {
		$pair = pfoa_cat_get_bonded_pair( $member_id );

		if ( ! is_array( $pair ) ) {
			return false;
		}

		$ids = array();

		foreach ( $pair as $entry ) {
			if ( $entry instanceof WP_Post ) {
				$entry = $entry->ID;
			}

			if ( is_numeric( $entry ) && (int) $entry > 0 ) {
				$ids[] = (int) $entry;
			}
		}

		$ids = array_values( array_unique( $ids ) );
		sort( $ids );

		if ( $ids !== $want ) {
			return false;
		}
	}

	if ( function_exists( 'pfoa_cat_is_lifecycle_eligible_for_adoptable' ) ) {
		foreach ( array( $a, $b ) as $member_id ) {
			if ( ! pfoa_cat_is_lifecycle_eligible_for_adoptable( $member_id ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Return adoptable bonded pairs for hero-link selectors.
 *
 * Each pair is enumerated from the canonical Cat Profiles bonded
 * relationship API and filtered to publicly available pairs. Display order
 * is alphabetical by the 'First & Second' title (lower post ID first, which
 * matches the Adoptable Cats combined-card title order).
 *
 * @return array[] Each entry: array( 'a' => int, 'b' => int, 'title' => string, 'token' => string ).
 */
function pfoa_get_hero_bonded_pairs() {
	if ( ! post_type_exists( 'pfoa_cat' ) || ! function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
		return array();
	}

	$cats = get_posts(
		array(
			'post_type'   => 'pfoa_cat',
			'post_status' => 'publish',
			'numberposts' => -1,
			'orderby'     => 'title',
			'order'       => 'ASC',
			'fields'      => 'ids',
		)
	);

	if ( ! is_array( $cats ) ) {
		return array();
	}

	$seen  = array();
	$pairs = array();

	foreach ( $cats as $cat_id ) {
		$cat_id = absint( $cat_id );

		if ( ! $cat_id || isset( $seen[ $cat_id ] ) ) {
			continue;
		}

		$raw = pfoa_cat_get_bonded_pair( $cat_id );

		if ( ! is_array( $raw ) ) {
			continue;
		}

		$ids = array();

		foreach ( $raw as $entry ) {
			if ( $entry instanceof WP_Post ) {
				$entry = $entry->ID;
			}

			if ( is_numeric( $entry ) && (int) $entry > 0 ) {
				$ids[] = (int) $entry;
			}
		}

		$ids = array_values( array_unique( $ids ) );

		if ( 2 !== count( $ids ) || ! in_array( $cat_id, $ids, true ) ) {
			continue;
		}

		sort( $ids );

		$seen[ $ids[0] ] = true;
		$seen[ $ids[1] ] = true;

		if ( ! pfoa_is_hero_bonded_pair_eligible( $ids[0], $ids[1] ) ) {
			continue;
		}

		$pairs[] = array(
			'a'     => $ids[0],
			'b'     => $ids[1],
			'title' => get_the_title( $ids[0] ) . ' & ' . get_the_title( $ids[1] ),
			'token' => pfoa_hero_bonded_pair_token( $ids[0], $ids[1] ),
		);
	}

	usort(
		$pairs,
		function ( $x, $y ) {
			return strcasecmp( (string) $x['title'], (string) $y['title'] );
		}
	);

	return $pairs;
}

/**
 * Return the Adoptable Cats listing page, if published.
 *
 * Resolves by slug with a legacy ID fallback so bonded-pair hero links keep
 * working if the page is ever re-created under a different ID.
 *
 * @return WP_Post|null
 */
function pfoa_get_adoptable_cats_page() {
	$page = pfoa_get_page_by_paths( array( 'adoptablecats2' ) );

	if ( $page instanceof WP_Post ) {
		return $page;
	}

	$fallback = get_post( 20323 );

	if ( $fallback instanceof WP_Post && 'page' === $fallback->post_type && 'publish' === $fallback->post_status ) {
		return $fallback;
	}

	return null;
}

/**
 * Return the stable fragment anchor for a bonded-pair combined card.
 *
 * Matches the id attribute added to each rendered bonded-pair card on the
 * Adoptable Cats page ('bonded-pair-<lower>-<upper>').
 *
 * @param int $a First Cat Profile post ID.
 * @param int $b Second Cat Profile post ID.
 * @return string Anchor without '#', or an empty string when invalid.
 */
function pfoa_get_hero_bonded_pair_anchor( $a, $b ) {
	$a = absint( $a );
	$b = absint( $b );

	if ( ! $a || ! $b || $a === $b ) {
		return '';
	}

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	return 'bonded-pair-' . $a . '-' . $b;
}

/**
 * Return the clickable destination for a bonded-pair hero image.
 *
 * Re-validated at render time: a pair that is still bonded and publicly
 * available deep-links to its focused spotlight at the top of the Adoptable
 * Cats page; a changed or unavailable pair falls back to the Adoptable Cats
 * listing rather than linking to an invalid target.
 *
 * @param int $a First Cat Profile post ID.
 * @param int $b Second Cat Profile post ID.
 * @return array Array with 'url' and 'title' keys (empty strings when none).
 */
function pfoa_get_hero_bonded_pair_link( $a, $b ) {
	$empty = array(
		'url'   => '',
		'title' => '',
	);

	$adoptables = pfoa_get_adoptable_cats_page();

	if ( ! $adoptables ) {
		return $empty;
	}

	$listing_url = get_permalink( $adoptables );

	if ( ! $listing_url ) {
		return $empty;
	}

	if ( ! pfoa_is_hero_bonded_pair_eligible( $a, $b ) ) {
		return array(
			'url'   => $listing_url,
			'title' => get_the_title( $adoptables ),
		);
	}

	$a = absint( $a );
	$b = absint( $b );

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	$title = get_the_title( $a ) . ' & ' . get_the_title( $b );

	return array(
		'url'   => $listing_url . '?bonded-pair=' . $a . '-' . $b . '#bonded-pair-spotlight',
		// translators: %s: bonded-pair display title, e.g. 'Oliver & Isaac'.
		'title' => sprintf( __( '%s — Adoptable Bonded Pair', 'pfoa-theme' ), $title ),
	);
}

/**
 * Parse the bonded-pair spotlight query for the Adoptable Cats listing.
 *
 * Accepts only '?bonded-pair=<lower>-<upper>' with two distinct positive
 * IDs, normalized to ascending order. Anything else (missing, malformed,
 * equal, or zero IDs) returns an empty array so the listing renders
 * unchanged.
 *
 * @return int[] Two ascending post IDs, or an empty array when not requested.
 */
function pfoa_get_bonded_pair_spotlight_ids() {
	if ( ! isset( $_GET['bonded-pair'] ) || ! is_string( $_GET['bonded-pair'] ) ) {
		return array();
	}

	if ( 1 !== preg_match( '/^(\d+)-(\d+)$/', trim( $_GET['bonded-pair'] ), $matches ) ) {
		return array();
	}

	$a = absint( $matches[1] );
	$b = absint( $matches[2] );

	if ( ! $a || ! $b || $a === $b ) {
		return array();
	}

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	return array( $a, $b );
}

/**
 * Whether the current request asks for a bonded-pair spotlight.
 *
 * Thin wrapper around pfoa_get_bonded_pair_spotlight_ids() for call sites
 * that only need the parsed IDs (empty array when not requested).
 *
 * @return int[] Two ascending post IDs, or an empty array when not requested.
 */
function pfoa_is_bonded_pair_spotlight_requested() {
	return pfoa_get_bonded_pair_spotlight_ids();
}

/**
 * Render the bonded-pair spotlight for the top of the Adoptable Cats page.
 *
 * Membership is resolved dynamically from the canonical bonded relationship
 * and re-validated on every render via
 * pfoa_is_hero_bonded_pair_eligible(): ineligible or no-longer-adoptable
 * pairs return an empty string so the listing renders unchanged. Server-
 * rendered HTML works with JS disabled; the companion script only moves
 * keyboard focus to the section.
 *
 * @param int $a First Cat Profile post ID.
 * @param int $b Second Cat Profile post ID.
 * @return string Spotlight HTML, or an empty string when ineligible.
 */
function pfoa_render_bonded_pair_spotlight( $a, $b ) {
	$a = absint( $a );
	$b = absint( $b );

	if ( ! $a || ! $b || $a === $b ) {
		return '';
	}

	if ( $a > $b ) {
		$tmp = $a;
		$a   = $b;
		$b   = $tmp;
	}

	if ( ! pfoa_is_hero_bonded_pair_eligible( $a, $b ) ) {
		return '';
	}

	$adoptables = pfoa_get_adoptable_cats_page();

	if ( ! $adoptables ) {
		return '';
	}

	$listing_url = get_permalink( $adoptables );

	if ( ! $listing_url ) {
		return '';
	}

	$name_a = get_the_title( $a );
	$name_b = get_the_title( $b );

	/* translators: %s: bonded-pair display title, e.g. 'Oliver & Isaac'. */
	$heading = sprintf( __( '%s — Adoptable Bonded Pair', 'pfoa-theme' ), $name_a . ' & ' . $name_b );

	$members_html = '';

	foreach ( array( $a, $b ) as $member_id ) {
		$member_name    = get_the_title( $member_id );
		$profile_url   = get_permalink( $member_id );
		$photo_html    = '';
		$photo_fallback = '';

		if ( function_exists( 'get_the_post_thumbnail' ) ) {
			$photo_html = (string) get_the_post_thumbnail(
				$member_id,
				'medium',
				array(
					'class' => 'pfoa-bonded-spotlight__photo',
					/* translators: %s: cat name. */
					'alt'   => sprintf( __( '%s photo', 'pfoa-theme' ), $member_name ),
				)
			);
		}

		if ( '' === $photo_html && function_exists( 'pfoa_cat_single_card_image_id' ) && function_exists( 'wp_get_attachment_image' ) ) {
			$fallback_id = (int) pfoa_cat_single_card_image_id( $member_id );

			if ( $fallback_id > 0 ) {
				$photo_fallback = (string) wp_get_attachment_image(
					$fallback_id,
					'medium',
					false,
					array(
						'class' => 'pfoa-bonded-spotlight__photo',
						/* translators: %s: cat name. */
						'alt'   => sprintf( __( '%s photo', 'pfoa-theme' ), $member_name ),
					)
				);
			}
		}

		$photo = '' !== $photo_html ? $photo_html : $photo_fallback;

		$members_html .= '<li class="pfoa-bonded-spotlight__member">';

		if ( $profile_url && '' !== $photo ) {
			/* translators: %s: cat name. */
			$members_html .= '<a class="pfoa-bonded-spotlight__photo-link" href="' . esc_url( $profile_url ) . '" aria-label="' . esc_attr( sprintf( __( "View %s's full profile", 'pfoa-theme' ), $member_name ) ) . '">' . $photo . '</a>';
		} elseif ( '' !== $photo ) {
			$members_html .= $photo;
		}

		$members_html .= '<p class="pfoa-bonded-spotlight__name">' . esc_html( $member_name ) . '</p>';

		if ( $profile_url ) {
			/* translators: %s: cat name. */
			$members_html .= '<a class="pfoa-bonded-spotlight__profile-link" href="' . esc_url( $profile_url ) . '">' . esc_html( sprintf( __( "View %s's full profile", 'pfoa-theme' ), $member_name ) ) . '</a>';
		}

		$members_html .= '</li>';
	}

	$html  = '<section id="bonded-pair-spotlight" class="pfoa-bonded-spotlight" aria-labelledby="bonded-pair-spotlight-title" tabindex="-1">';
	$html .= '<p class="pfoa-bonded-spotlight__kicker">' . esc_html__( 'Adoptable Bonded Pair', 'pfoa-theme' ) . '</p>';
	$html .= '<h2 id="bonded-pair-spotlight-title" class="pfoa-bonded-spotlight__title">' . esc_html( $heading ) . '</h2>';
	$html .= '<ul class="pfoa-bonded-spotlight__members">' . $members_html . '</ul>';
	$html .= '<p class="pfoa-bonded-spotlight__actions"><a class="pfoa-button" href="' . esc_url( $listing_url ) . '">' . esc_html__( 'View All Adoptable Cats', 'pfoa-theme' ) . '</a></p>';
	$html .= '</section>';

	return $html;
}

/**
 * Prepend the bonded-pair spotlight to the Adoptable Cats listing content.
 *
 * Runs early on the_content (priority 5, before the combined-card merger at
 * 999) and only on the adoptable-cats page main loop. Invalid, ineligible,
 * or absent query pairs leave $content untouched.
 *
 * @param string $content Filtered post content.
 * @return string Spotlight-prepended content, or unchanged content.
 */
function pfoa_prepend_bonded_pair_spotlight( $content ) {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 20323 ) || is_page( 'adoptablecats2' ) ) ) {
		$is_target_page = true;
	} else {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			if ( 20323 === (int) $post->ID ) {
				$is_target_page = true;
			} elseif ( isset( $post->post_name ) && 'adoptablecats2' === $post->post_name ) {
				$is_target_page = true;
			}
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	$ids = pfoa_get_bonded_pair_spotlight_ids();

	if ( 2 !== count( $ids ) ) {
		return $content;
	}

	$spotlight = pfoa_render_bonded_pair_spotlight( $ids[0], $ids[1] );

	if ( '' === $spotlight ) {
		return $content;
	}

	return $spotlight . $content;
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'the_content', 'pfoa_prepend_bonded_pair_spotlight', 5 );
}

/**
 * Render the hero image destination selector (HP-02).
 *
 * Reusable Pages + Cat Profiles grouped selector replacing the previous
 * page-only wp_dropdown_pages() calls. Field names and the _pfoa_hero
 * storage format are unchanged.
 *
 * @param string $name     Select field name (may contain __INDEX__ for the JS template).
 * @param int    $selected Currently selected destination post ID (0 = no link).
 * @param string $id       Optional select element ID (empty omits the attribute).
 * @return void
 */
/**
 * Render the hero image destination selector.
 *
 * Reusable Pages + Cat Profiles + Bonded Pairs grouped selector replacing
 * the previous page-only wp_dropdown_pages() calls. Pages and individual
 * Cat Profiles store as integer post IDs; bonded pairs store as
 * 'pair:<lower-id>:<upper-id>' tokens. Field names are unchanged.
 *
 * @param string     $name     Select field name (may contain __INDEX__ for the JS template).
 * @param int|string $selected Currently selected destination (post ID, pair token, or 0 = no link).
 * @param string     $id       Optional select element ID (empty omits the attribute).
 * @return void
 */
function pfoa_render_hero_link_selector( $name, $selected = 0, $id = '' ) {
	$selected = is_string( $selected ) ? trim( $selected ) : absint( $selected );

	if ( '' === $selected ) {
		$selected = 0;
	}

	$page_choices = pfoa_get_page_selector_choices();

	$cats  = pfoa_get_hero_link_cat_profiles();
	$pairs = pfoa_get_hero_bonded_pairs();
	?>
	<select name="<?php echo esc_attr( $name ); ?>"<?php echo '' !== $id ? ' id="' . esc_attr( $id ) . '"' : ''; ?>>
		<option value="0"<?php selected( $selected, 0 ); ?>><?php echo esc_html__( '— No link —', 'pfoa-theme' ); ?></option>
		<optgroup label="<?php esc_attr_e( 'Pages', 'pfoa-theme' ); ?>">
			<?php foreach ( $page_choices as $choice ) : ?>
				<option value="<?php echo esc_attr( (string) $choice['id'] ); ?>"<?php selected( $selected, $choice['id'] ); ?>><?php echo esc_html( $choice['label'] ); ?></option>
			<?php endforeach; ?>
		</optgroup>
		<?php if ( array() !== $cats ) : ?>
			<optgroup label="<?php esc_attr_e( 'Cat Profiles', 'pfoa-theme' ); ?>">
				<?php foreach ( $cats as $cat ) : ?>
					<option value="<?php echo esc_attr( (string) $cat->ID ); ?>"<?php selected( $selected, $cat->ID ); ?>><?php echo esc_html( sprintf( '%s (#%d)', $cat->post_title, $cat->ID ) ); ?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php endif; ?>
		<?php if ( array() !== $pairs ) : ?>
			<optgroup label="<?php esc_attr_e( 'Bonded Pairs', 'pfoa-theme' ); ?>">
				<?php foreach ( $pairs as $pair ) : ?>
					<option value="<?php echo esc_attr( $pair['token'] ); ?>"<?php selected( $selected, $pair['token'] ); ?>><?php echo esc_html( sprintf( '%s — Bonded Pair', $pair['title'] ) ); ?></option>
				<?php endforeach; ?>
			</optgroup>
		<?php endif; ?>
	</select>
	<?php
}

/**
 * Sanitize an optional hero image destination (pages, Cat Profiles, bonded pairs).
 *
 * A published page or a published, complete pfoa_cat profile is accepted as
 * an integer post ID; a currently bonded and publicly available pair is
 * accepted as a normalized 'pair:<lower-id>:<upper-id>' token. Anything
 * else becomes 0 (not clickable).
 *
 * @param mixed $value Raw submitted value.
 * @return int|string Post ID, pair token, or 0.
 */
function pfoa_sanitize_hero_link_page_id( $value ) {
	$pair = pfoa_parse_hero_bonded_pair_token( is_string( $value ) ? $value : '' );

	if ( array() !== $pair ) {
		if ( ! pfoa_is_hero_bonded_pair_eligible( $pair[0], $pair[1] ) ) {
			return 0;
		}

		return pfoa_hero_bonded_pair_token( $pair[0], $pair[1] );
	}

	$page_id = absint( $value );

	if ( ! pfoa_is_hero_link_destination_eligible( $page_id ) ) {
		return 0;
	}

	return $page_id;
}

/**
 * Return the clickable destination for a hero image, if valid.
 *
 * Re-validated at render time so a destination that was unpublished,
 * trashed, or marked incomplete after saving stops being clickable
 * without any re-save. Bonded-pair tokens resolve to the pair's combined
 * card on the Adoptable Cats page, falling back to the listing itself when
 * the pair is no longer bonded or publicly available.
 *
 * @param int|string $page_id Destination post ID, pair token, or 0.
 * @return array Array with 'url' and 'title' keys (empty strings when none).
 */
function pfoa_get_hero_link( $page_id ) {
	$pair = pfoa_parse_hero_bonded_pair_token( is_string( $page_id ) ? $page_id : '' );

	if ( array() !== $pair ) {
		return pfoa_get_hero_bonded_pair_link( $pair[0], $pair[1] );
	}

	$page_id = absint( $page_id );

	if ( ! pfoa_is_hero_link_destination_eligible( $page_id ) ) {
		return array(
			'url'   => '',
			'title' => '',
		);
	}

	$url = get_permalink( $page_id );

	if ( ! $url ) {
		return array(
			'url'   => '',
			'title' => '',
		);
	}

	return array(
		'url'   => $url,
		'title' => get_the_title( $page_id ),
	);
}

/**
 * Sanitize a hero external link URL.
 *
 * Accepts only absolute http/https URLs: the value is trimmed, passed
 * through esc_url_raw(), and must parse with an http/https scheme and a
 * host. Anything else (relative paths, other schemes such as javascript: or
 * ftp:, unparseable input, empty strings) sanitizes to '' (no link) so an
 * invalid external URL can never render a clickable destination.
 *
 * @param mixed $value Raw submitted value.
 * @return string Sanitized absolute URL, or '' when invalid.
 */
function pfoa_sanitize_hero_external_url( $value ) {
	if ( ! is_string( $value ) ) {
		if ( is_numeric( $value ) ) {
			$value = (string) $value;
		} else {
			return '';
		}
	}

	$url = trim( $value );

	if ( '' === $url ) {
		return '';
	}

	$url = esc_url_raw( $url );

	if ( '' === $url ) {
		return '';
	}

	$parts = parse_url( $url );

	if ( ! is_array( $parts ) || ! isset( $parts['scheme'], $parts['host'] ) || '' === $parts['host'] ) {
		return '';
	}

	$scheme = strtolower( (string) $parts['scheme'] );

	if ( 'http' !== $scheme && 'https' !== $scheme ) {
		return '';
	}

	return $url;
}

/**
 * Resolve the effective link destination type for a hero image item.
 *
 * Explicit 'internal' | 'external' | 'none' values pass through. Rows that
 * predate link_type/link_url (no key, null, or an unknown value) resolve as
 * internal when a link_page_id destination is stored and none otherwise, so
 * legacy rows keep their existing behavior without a migration.
 *
 * @param mixed $item Hero image item array (carousel item or single-image fields).
 * @return string 'internal', 'external', or 'none'.
 */
function pfoa_resolve_hero_link_type( $item ) {
	if ( is_array( $item ) && isset( $item['link_type'] ) ) {
		$type = strtolower( trim( (string) $item['link_type'] ) );

		if ( in_array( $type, array( 'internal', 'external', 'none' ), true ) ) {
			return $type;
		}
	}

	$stored = ( is_array( $item ) && isset( $item['link_page_id'] ) ) ? $item['link_page_id'] : 0;

	if ( is_string( $stored ) ) {
		$stored = trim( $stored );

		if ( '' !== $stored && '0' !== $stored ) {
			return 'internal';
		}

		return 'none';
	}

	return absint( $stored ) ? 'internal' : 'none';
}

/**
 * Return the clickable destination for a hero image item, if valid.
 *
 * Shared by carousel slides and the single image. Internal destinations
 * re-validate through pfoa_get_hero_link() (existing page/Cat Profile/
 * bonded-pair behavior unchanged). External destinations return the
 * sanitized URL with the slide headline as title (falling back to the
 * supplied headline, then the URL host). None/empty/invalid resolves to an
 * empty destination.
 *
 * @param mixed  $item              Hero image item array with link_type/link_page_id/link_url/headline.
 * @param string $fallback_headline Headline to use when the item has none (slide headline, then URL host).
 * @return array Array with 'url', 'title', and 'external' (bool) keys.
 */
function pfoa_get_hero_slide_link( $item, $fallback_headline = '' ) {
	$empty = array(
		'url'      => '',
		'title'    => '',
		'external' => false,
	);

	if ( ! is_array( $item ) ) {
		return $empty;
	}

	$type = pfoa_resolve_hero_link_type( $item );

	if ( 'external' === $type ) {
		$url = pfoa_sanitize_hero_external_url( isset( $item['link_url'] ) ? $item['link_url'] : '' );

		if ( '' === $url ) {
			return $empty;
		}

		$title = isset( $item['headline'] ) ? trim( (string) $item['headline'] ) : '';

		if ( '' === $title ) {
			$title = trim( (string) $fallback_headline );
		}

		if ( '' === $title ) {
			$host  = (string) parse_url( $url, PHP_URL_HOST );
			$title = '' !== $host ? $host : $url;
		}

		return array(
			'url'      => $url,
			'title'    => $title,
			'external' => true,
		);
	}

	if ( 'internal' === $type ) {
		$link = pfoa_get_hero_link( isset( $item['link_page_id'] ) ? $item['link_page_id'] : 0 );

		return array(
			'url'      => $link['url'],
			'title'    => $link['title'],
			'external' => false,
		);
	}

	return $empty;
}

/**
 * Sanitize the featured-pin portion of the hero value (structural only).
 *
 * Slide clamps to 0-8 (0 = normal rotation); mode allows none/timed/
 * indefinite. A zero slide or 'none' mode normalizes to the all-zero pin.
 * Expiry/creation timestamps pass through as integers here; save-time
 * semantics (fresh vs. preserved expiry) are applied by
 * pfoa_resolve_hero_pin_for_save() in the save handler, never by this
 * sanitizer, so pfoa_get_hero() round-trips stored pins untouched.
 *
 * @param mixed $value Raw submitted featured_pin value.
 * @return array Sanitized pin array with slide/attachment_id/mode/expires_at/created_at keys.
 */
function pfoa_sanitize_hero_featured_pin( $value ) {
	$none = array(
		'slide'         => 0,
		'attachment_id' => 0,
		'mode'          => 'none',
		'expires_at'    => 0,
		'created_at'    => 0,
	);

	if ( ! is_array( $value ) ) {
		return $none;
	}

	$slide = isset( $value['slide'] ) ? absint( $value['slide'] ) : 0;

	if ( $slide < 0 || $slide > 8 ) {
		$slide = 0;
	}

	$mode = isset( $value['mode'] ) ? sanitize_key( (string) $value['mode'] ) : 'none';

	if ( ! in_array( $mode, array( 'none', 'timed', 'indefinite' ), true ) ) {
		$mode = 'none';
	}

	if ( 0 === $slide || 'none' === $mode ) {
		return $none;
	}

	return array(
		'slide'         => $slide,
		'attachment_id' => isset( $value['attachment_id'] ) ? absint( $value['attachment_id'] ) : 0,
		'mode'          => $mode,
		'expires_at'    => isset( $value['expires_at'] ) ? absint( $value['expires_at'] ) : 0,
		'created_at'    => isset( $value['created_at'] ) ? absint( $value['created_at'] ) : 0,
	);
}

/**
 * Apply featured-pin save semantics for the Save Homepage workflow.
 *
 * Snapshot attachment_id comes from the sanitized carousel items at the
 * pinned 1-based position (0 when missing/invalid image; render then falls
 * back to rotation). Timed pins keep the original expires_at (and
 * created_at) when an unrelated Homepage save resubmits the unchanged pin
 * with the still-valid future expires_at in the hidden field — the expiry
 * is NOT extended. A fresh expires_at (now + DAY_IN_SECONDS, site-local via
 * current_time) is computed only when the pin is newly set, the slide
 * changes, the mode changes from indefinite to timed, or no valid future
 * expiry was submitted.
 *
 * @param mixed $submitted_pin  Sanitized submitted featured_pin (hidden expires_at included).
 * @param mixed $stored_pin     Previously stored featured_pin, if any.
 * @param array $carousel_items Sanitized carousel items (display order).
 * @param int   $now            Current time as current_time('timestamp').
 * @return array Final featured_pin array to store.
 */
function pfoa_resolve_hero_pin_for_save( $submitted_pin, $stored_pin, $carousel_items, $now ) {
	$now = (int) $now;
	$pin = pfoa_sanitize_hero_featured_pin( $submitted_pin );

	if ( 'none' === $pin['mode'] ) {
		return $pin;
	}

	if ( ! is_array( $carousel_items ) ) {
		$carousel_items = array();
	}

	$attachment_id = 0;
	$position      = $pin['slide'] - 1;

	if ( isset( $carousel_items[ $position ] ) && is_array( $carousel_items[ $position ] ) ) {
		$candidate = isset( $carousel_items[ $position ]['id'] ) ? absint( $carousel_items[ $position ]['id'] ) : 0;

		if ( $candidate && wp_attachment_is_image( $candidate ) ) {
			$attachment_id = $candidate;
		}
	}

	$pin['attachment_id'] = $attachment_id;

	$stored = pfoa_sanitize_hero_featured_pin( $stored_pin );

	if ( 'timed' === $pin['mode'] ) {
		if ( 'timed' === $stored['mode'] && $stored['slide'] === $pin['slide'] && $stored['expires_at'] > $now && $pin['expires_at'] === $stored['expires_at'] ) {
			$pin['created_at'] = $stored['created_at'] ? $stored['created_at'] : $now;

			return $pin;
		}

		$pin['expires_at'] = $now + DAY_IN_SECONDS;
		$pin['created_at'] = $now;

		return $pin;
	}

	$pin['expires_at'] = 0;

	if ( 'indefinite' === $stored['mode'] && $stored['slide'] === $pin['slide'] && $stored['created_at'] ) {
		$pin['created_at'] = $stored['created_at'];
	} else {
		$pin['created_at'] = $now;
	}

	return $pin;
}

/**
 * Return the pinned slide's attachment ID when a valid pin resolves, else 0.
 *
 * Evaluated server-side on every request: valid only when the pin mode is
 * not none, the 1-based slide position falls within carousel_slide_count,
 * the stored carousel item at that position holds a valid image, and a
 * timed pin has not yet expired (expires_at > current_time('timestamp'));
 * indefinite pins carry no expiry. Anything else returns 0 so the template
 * silently falls back to normal rotation with all slides intact.
 *
 * Page-cache limitation: this check runs in PHP per uncached request. A
 * host, CDN, or plugin serving full-page cache may keep showing the pinned
 * hero (or the rotation) until the cached page expires or is flushed — no
 * cache flush or no-cache handling is added here, so a timed pin can remain
 * visible up to one cache TTL past expiry on cached pages.
 *
 * @param array $hero Sanitized hero value.
 * @return int Pinned attachment ID, or 0 when no pin resolves.
 */
function pfoa_get_hero_pinned_slide_id( $hero ) {
	if ( ! is_array( $hero ) || ! isset( $hero['featured_pin'] ) || ! is_array( $hero['featured_pin'] ) ) {
		return 0;
	}

	$pin = pfoa_sanitize_hero_featured_pin( $hero['featured_pin'] );

	if ( 'none' === $pin['mode'] ) {
		return 0;
	}

	$slide_count = isset( $hero['carousel_slide_count'] ) ? pfoa_sanitize_hero_carousel_slide_count( $hero['carousel_slide_count'] ) : 3;

	if ( $pin['slide'] < 1 || $pin['slide'] > $slide_count ) {
		return 0;
	}

	if ( 'timed' === $pin['mode'] && (int) $pin['expires_at'] <= (int) current_time( 'timestamp' ) ) {
		return 0;
	}

	$items = ( isset( $hero['carousel_items'] ) && is_array( $hero['carousel_items'] ) ) ? array_values( $hero['carousel_items'] ) : array();
	$position = $pin['slide'] - 1;

	if ( ! isset( $items[ $position ] ) || ! is_array( $items[ $position ] ) ) {
		return 0;
	}

	$attachment_id = isset( $items[ $position ]['id'] ) ? absint( $items[ $position ]['id'] ) : 0;

	if ( ! $attachment_id || ! wp_attachment_is_image( $attachment_id ) ) {
		return 0;
	}

	return $attachment_id;
}

/**
 * Scan a raw submitted hero value for external URLs that sanitize to empty.
 *
 * Used by the save handler to surface admin feedback (query-flagged notice
 * naming the slide number) without blocking the rest of the
 * save; the invalid URL itself still sanitizes to '' (no link).
 *
 * @param mixed $raw Raw submitted pfoa_hero value (unslashed).
 * @return string[] 1-based slide numbers (as strings) with invalid external URLs.
 */
function pfoa_find_hero_invalid_external_links( $raw ) {
	$bad = array();

	if ( ! is_array( $raw ) ) {
		return $bad;
	}

	if ( isset( $raw['carousel_items'] ) && is_array( $raw['carousel_items'] ) ) {
		$position = 0;

		foreach ( $raw['carousel_items'] as $carousel_item ) {
			$position++;

			if ( ! is_array( $carousel_item ) ) {
				continue;
			}

			$item_type = isset( $carousel_item['link_type'] ) ? strtolower( trim( (string) $carousel_item['link_type'] ) ) : '';

			if ( 'external' !== $item_type ) {
				continue;
			}

			$item_url = isset( $carousel_item['link_url'] ) ? trim( (string) $carousel_item['link_url'] ) : '';

			if ( '' !== $item_url && '' === pfoa_sanitize_hero_external_url( $item_url ) ) {
				$bad[] = (string) $position;
			}
		}
	}

	return $bad;
}

/**
 * Sanitize a per-image hero carousel brightness value.
 *
 * Only 100, 110, 120, 130 are accepted; anything else becomes 100 (Original).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_brightness( $value ) {
	$brightness = absint( $value );

	if ( ! in_array( $brightness, array( 100, 110, 120, 130 ), true ) ) {
		return 100;
	}

	return $brightness;
}

/**
 * Sanitize a per-image hero carousel zoom value.
 *
 * Only 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150 are accepted;
 * anything else becomes 100 (Default).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_zoom( $value ) {
	$zoom = absint( $value );

	if ( ! in_array( $zoom, array( 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150 ), true ) ) {
		return 100;
	}

	return $zoom;
}

/**
 * Sanitize a per-image hero carousel image position value.
 *
 * Canonical tokens are space-separated CSS object-position values so they
 * map directly to `object-position` (e.g. 'top left', 'center center').
 * Hyphenated equivalents ('top-left') and single-word shorthands ('top',
 * 'left', 'right', 'bottom', 'center') are accepted as aliases and
 * normalized to their canonical space-separated form. Anything else
 * becomes 'center center' (CSS equivalent of 'center').
 *
 * @param mixed $value Raw submitted value.
 * @return string
 */
function pfoa_sanitize_hero_position( $value ) {
	$position = strtolower( trim( (string) $value ) );
	$position = preg_replace( '/\s+/', ' ', $position );

	$aliases = array(
		'top-left'     => 'top left',
		'top-center'   => 'top center',
		'top'          => 'top center',
		'top-right'    => 'top right',
		'center-left'  => 'center left',
		'left'         => 'center left',
		'center-center' => 'center center',
		'center'       => 'center center',
		'center-right' => 'center right',
		'right'        => 'center right',
		'bottom-left'  => 'bottom left',
		'bottom-center' => 'bottom center',
		'bottom'       => 'bottom center',
		'bottom-right' => 'bottom right',
	);

	if ( isset( $aliases[ $position ] ) ) {
		$position = $aliases[ $position ];
	}

	$allowed = array(
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

	if ( ! in_array( $position, $allowed, true ) ) {
		return 'center center';
	}

	return $position;
}

/**
 * Sanitize a per-image hero carousel headline size value.
 *
 * Only 50, 60, 70, 80, 90, 100, 110, 120 are accepted; anything else becomes 100 (Default).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_headline_scale( $value ) {
	$scale = absint( $value );

	if ( ! in_array( $scale, array( 50, 60, 70, 80, 90, 100, 110, 120 ), true ) ) {
		return 100;
	}

	return $scale;
}

/**
 * Sanitize a per-image hero carousel headline color value.
 *
 * Only 'white' (#FFFFFF, default) and 'black' (#000000) are accepted;
 * anything else becomes 'white'.
 *
 * @param mixed $value Raw submitted value.
 * @return string
 */
function pfoa_sanitize_hero_headline_color( $value ) {
	if ( ! isset( $value ) ) {
		return 'white';
	}

	$color = strtolower( trim( (string) $value ) );

	if ( 'black' === $color || '#000000' === $color ) {
		return 'black';
	}

	if ( 'white' === $color || '#ffffff' === $color ) {
		return 'white';
	}

	return 'white';
}

/**
 * Sanitize the homepage hero carousel auto-advance interval.
 *
 * Only 3500, 5000, 7000 are accepted; anything else becomes 5000 (5 seconds).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_carousel_interval_ms( $value ) {
	$interval = absint( $value );

	if ( ! in_array( $interval, array( 3500, 5000, 7000 ), true ) ) {
		return 5000;
	}

	return $interval;
}

/**
 * Sanitize the homepage hero carousel slide count.
 *
 * Only 2, 3, 4, 5 are accepted; anything else becomes 3 (current default).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_carousel_slide_count( $value ) {
	$count = absint( $value );

	if ( ! in_array( $count, array( 2, 3, 4, 5, 6, 7, 8 ), true ) ) {
		return 3;
	}

	return $count;
}

/**
 * Sanitize a homepage hero value. No raw HTML is allowed.
 *
 * @param mixed $value Raw submitted value (expected unslashed).
 * @return array
 */
function pfoa_sanitize_hero( $value ) {
	$hero = array(
		'mode'               => 'carousel',
		'headline'           => '',
		'image_id'           => 0,
		'image_link_page_id' => 0,
		'image_link_type'    => 'none',
		'image_link_url'     => '',
		'featured_pin'       => array(
			'slide'         => 0,
			'attachment_id' => 0,
			'mode'          => 'none',
			'expires_at'    => 0,
			'created_at'    => 0,
		),
		'carousel_ids'       => array(),
		'carousel_items'     => array(),
		'carousel_slide_count' => 3,
		'carousel_interval_ms' => 5000,
		'ctas'               => array(),
	);

	if ( ! is_array( $value ) ) {
		return $hero;
	}

	// Legacy Single Image controls removed: the carousel is the sole
	// administrative source for hero images. Submitted mode/image values
	// are ignored and legacy stored values are dropped on next save.
	$hero['mode'] = 'carousel';

	// Global hero headline removed: the submitted headline is ignored and
	// legacy stored values are dropped on next save. Each carousel slide
	// owns its own headline; pinned rendering shows the pinned slide's
	// headline, and rotation shows each slide's own headline.
	$hero['headline'] = '';

	$hero['image_id']           = 0;
	$hero['image_link_page_id'] = 0;
	$hero['image_link_type']    = 'none';
	$hero['image_link_url']     = '';

	$hero['featured_pin'] = pfoa_sanitize_hero_featured_pin( isset( $value['featured_pin'] ) ? $value['featured_pin'] : array() );

	$hero['carousel_interval_ms'] = isset( $value['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $value['carousel_interval_ms'] ) : 5000;

	$hero['carousel_slide_count'] = isset( $value['carousel_slide_count'] ) ? pfoa_sanitize_hero_carousel_slide_count( $value['carousel_slide_count'] ) : 3;

	$carousel_items = array();

	if ( isset( $value['carousel_items'] ) && is_array( $value['carousel_items'] ) ) {
		foreach ( $value['carousel_items'] as $carousel_item ) {
			if ( ! is_array( $carousel_item ) ) {
				continue;
			}

			$carousel_id = isset( $carousel_item['id'] ) ? absint( $carousel_item['id'] ) : 0;

			if ( ! $carousel_id || ! wp_attachment_is_image( $carousel_id ) ) {
				continue;
			}

			$is_duplicate = false;

			foreach ( $carousel_items as $existing_item ) {
				if ( $existing_item['id'] === $carousel_id ) {
					$is_duplicate = true;
					break;
				}
			}

			if ( $is_duplicate ) {
				continue;
			}

			$item_link_page_id = isset( $carousel_item['link_page_id'] ) ? pfoa_sanitize_hero_link_page_id( $carousel_item['link_page_id'] ) : 0;

			$carousel_items[] = array(
				'id'             => $carousel_id,
				'link_page_id'   => $item_link_page_id,
				'link_type'      => pfoa_resolve_hero_link_type(
					array(
						'link_type'    => isset( $carousel_item['link_type'] ) ? $carousel_item['link_type'] : null,
						'link_page_id' => $item_link_page_id,
					)
				),
				'link_url'       => isset( $carousel_item['link_url'] ) ? pfoa_sanitize_hero_external_url( $carousel_item['link_url'] ) : '',
				'headline'       => isset( $carousel_item['headline'] ) ? sanitize_text_field( $carousel_item['headline'] ) : '',
				'brightness'     => isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100,
				'headline_scale' => isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100,
				'headline_color' => isset( $carousel_item['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $carousel_item['headline_color'] ) : 'white',
				'zoom'           => isset( $carousel_item['zoom'] ) ? pfoa_sanitize_hero_zoom( $carousel_item['zoom'] ) : 100,
				'position'       => isset( $carousel_item['position'] ) ? pfoa_sanitize_hero_position( $carousel_item['position'] ) : 'center center',
			);
		}
	}

	if ( array() === $carousel_items && isset( $value['carousel_ids'] ) && is_array( $value['carousel_ids'] ) ) {
		foreach ( $value['carousel_ids'] as $carousel_id ) {
			$carousel_id = absint( $carousel_id );

			if ( ! $carousel_id || ! wp_attachment_is_image( $carousel_id ) ) {
				continue;
			}

			$is_duplicate = false;

			foreach ( $carousel_items as $existing_item ) {
				if ( $existing_item['id'] === $carousel_id ) {
					$is_duplicate = true;
					break;
				}
			}

			if ( $is_duplicate ) {
				continue;
			}

			$carousel_items[] = array(
				'id'             => $carousel_id,
				'link_page_id'   => 0,
				'link_type'      => 'none',
				'link_url'       => '',
				'headline'       => '',
				'brightness'     => 100,
				'headline_scale' => 100,
				'headline_color' => 'white',
				'zoom'           => 100,
				'position'       => 'center center',
			);
		}
	}

	// Cap at 8 slots (first 8 in submitted order). Inactive slides beyond
	// the selected carousel_slide_count are preserved, not dropped.
	$carousel_items = array_slice( $carousel_items, 0, 8 );

	$hero['carousel_items'] = $carousel_items;
	$hero['carousel_ids']   = array();

	foreach ( $carousel_items as $carousel_item ) {
		$hero['carousel_ids'][] = $carousel_item['id'];
	}

	if ( isset( $value['ctas'] ) && is_array( $value['ctas'] ) ) {
		foreach ( $value['ctas'] as $cta ) {
			if ( ! is_array( $cta ) ) {
				continue;
			}

			$label   = isset( $cta['label'] ) ? sanitize_text_field( $cta['label'] ) : '';
			$page_id = isset( $cta['page_id'] ) ? pfoa_sanitize_page_link_id( $cta['page_id'] ) : 0;
			$url     = isset( $cta['url'] ) ? esc_url_raw( trim( (string) $cta['url'] ) ) : '';

			if ( 0 === $page_id && '' !== $url ) {
				$page_id = pfoa_migrate_url_to_page_id( $url );
			}

			if ( $page_id ) {
				$url = '';
			}

			if ( '' === $label && 0 === $page_id && '' === $url ) {
				continue;
			}

			$hero['ctas'][] = array(
				'label'   => $label,
				'page_id' => $page_id,
				'url'     => $url,
			);
		}
	}

	return $hero;
}

/**
 * Return the effective homepage hero for a page.
 *
 * When no hero meta has been saved, the legacy effective content applies:
 * default Adopt/Volunteer destinations with no headline. Image resolution
 * (featured image, then theme fallback banner) happens
 * in the template so the hero never renders empty. Each carousel slide owns
 * its own headline; an empty slide headline shows no headline.
 *
 * @param int $post_id Page ID.
 * @return array
 */
function pfoa_get_hero( $post_id ) {
	$defaults = pfoa_get_hero_defaults();
	$saved    = get_post_meta( $post_id, PFOA_HERO_META_KEY, true );

	if ( ! is_array( $saved ) || array() === $saved ) {
		return $defaults;
	}

	$hero = pfoa_sanitize_hero( $saved );

	return $hero;
}

/**
 * Return the assigned static front page ID, or 0 when none is usable.
 *
 * A usable front page is a published page assigned under Settings > Reading.
 *
 * @return int
 */
function pfoa_homepage_front_id() {
	$front_id = (int) get_option( 'page_on_front' );

	if ( ! $front_id || 'page' !== get_post_type( $front_id ) || 'publish' !== get_post_status( $front_id ) ) {
		return 0;
	}

	return $front_id;
}

/**
 * Option name storing the editable site footer.
 *
 * Single option. Shape: array(
 *   'public_hours'     => array( 'heading' => string, 'body' => string ),
 *   'mailing_address'  => array( 'heading' => string, 'body' => string ),
 *   'physical_address' => array( 'heading' => string, 'body' => string ),
 *   'map'              => array( 'heading' => string, 'body' => string ),
 *   'copyright_text'   => string,
 * ). Plain text only; the map stays plain text (no iframe/embed).
 *
 * @var string
 */
define( 'PFOA_FOOTER_OPTION', 'pfoa_footer' );

/**
 * Return the theme-owned default footer values.
 *
 * Defaults reproduce the 0.1.22 hard-coded footer exactly so a fresh
 * install renders identical content without any save.
 *
 * @return array
 */
function pfoa_get_footer_defaults() {
	return array(
		'public_hours'     => array(
			'heading' => __( 'Public Hours', 'pfoa-theme' ),
			'body'    => __( '11:00 am–4:00 pm Tuesday–Saturday, by appointment.', 'pfoa-theme' ),
		),
		'mailing_address'  => array(
			'heading' => __( 'Mailing Address', 'pfoa-theme' ),
			'body'    => __( "P.O. Box 404\nSequim, WA 98382", 'pfoa-theme' ),
		),
		'physical_address' => array(
			'heading' => __( 'Physical Address', 'pfoa-theme' ),
			'body'    => __( "257509 Hwy 101\nPort Angeles, WA", 'pfoa-theme' ),
		),
		'map'              => array(
			'heading' => __( 'Map', 'pfoa-theme' ),
			'body'    => __( 'Map pending.', 'pfoa-theme' ),
		),
		'copyright_text'   => __( 'Peninsula Friends of Animals. All Rights Reserved.', 'pfoa-theme' ),
	);
}

/**
 * Sanitize the footer option value. No raw HTML is allowed.
 *
 * @param mixed $value Raw submitted value (expected unslashed).
 * @return array
 */
function pfoa_sanitize_footer( $value ) {
	$defaults = pfoa_get_footer_defaults();
	$footer   = $defaults;

	if ( ! is_array( $value ) ) {
		return $footer;
	}

	foreach ( array( 'public_hours', 'mailing_address', 'physical_address', 'map' ) as $key ) {
		if ( isset( $value[ $key ] ) && is_array( $value[ $key ] ) ) {
			$heading = isset( $value[ $key ]['heading'] ) ? sanitize_text_field( $value[ $key ]['heading'] ) : '';
			$body    = isset( $value[ $key ]['body'] ) ? sanitize_textarea_field( $value[ $key ]['body'] ) : '';

			$footer[ $key ] = array(
				'heading' => '' === trim( $heading ) ? $defaults[ $key ]['heading'] : trim( $heading ),
				'body'    => trim( $body ),
			);
		}
	}

	$copyright = isset( $value['copyright_text'] ) ? sanitize_text_field( $value['copyright_text'] ) : '';
	$footer['copyright_text'] = '' === trim( $copyright ) ? $defaults['copyright_text'] : trim( $copyright );

	return $footer;
}

/**
 * Return the effective footer: saved option merged over defaults.
 *
 * Missing keys fall back to defaults so partial saves never render empty.
 *
 * @return array
 */
function pfoa_get_footer() {
	$defaults = pfoa_get_footer_defaults();
	$saved    = get_option( PFOA_FOOTER_OPTION, array() );

	if ( ! is_array( $saved ) || array() === $saved ) {
		return $defaults;
	}

	return pfoa_sanitize_footer( $saved );
}

/**
 * Register the footer setting.
 *
 * @return void
 */
function pfoa_footer_admin_init() {
	register_setting(
		'pfoa_footer_group',
		PFOA_FOOTER_OPTION,
		array(
			'sanitize_callback' => 'pfoa_sanitize_footer',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'pfoa_footer_admin_init' );

/**
 * Handle save submissions for the PFOA Site > Footer screen.
 *
 * Plain PHP + submit only: the form posts the full field set back to the
 * same page, the sanitized result is stored with update_option().
 *
 * @return void
 */
function pfoa_footer_handle_post() {
	if ( ! isset( $_POST['pfoa_footer_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'pfoa_footer_save', 'pfoa_footer_nonce' );

	$raw    = isset( $_POST[ PFOA_FOOTER_OPTION ] ) && is_array( $_POST[ PFOA_FOOTER_OPTION ] ) ? wp_unslash( $_POST[ PFOA_FOOTER_OPTION ] ) : array();
	$footer = pfoa_sanitize_footer( $raw );

	update_option( PFOA_FOOTER_OPTION, $footer );

	add_settings_error(
		'pfoa_footer_messages',
		'pfoa_footer_saved',
		esc_html__( 'Footer updated.', 'pfoa-theme' ),
		'success'
	);
}

/**
 * Render the PFOA Site > Footer screen.
 *
 * Straightforward fields: text inputs for headings + copyright text,
 * textareas for bodies. Plain text only; multiline bodies support line
 * breaks.
 *
 * Menu access requires edit_pages; saving requires edit_theme_options or
 * manage_options (same boundary as the Homepage Cards section).
 *
 * @return void
 */
function pfoa_site_footer_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the footer.', 'pfoa-theme' ) );
	}

	pfoa_footer_handle_post();

	$footer = pfoa_get_footer();
	$groups = array(
		'public_hours'     => __( 'Public Hours', 'pfoa-theme' ),
		'mailing_address'  => __( 'Mailing Address', 'pfoa-theme' ),
		'physical_address' => __( 'Physical Address', 'pfoa-theme' ),
		'map'              => __( 'Map', 'pfoa-theme' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Footer', 'pfoa-theme' ); ?></h1>
		<p><?php esc_html_e( 'Edit the site footer content. Plain text only.', 'pfoa-theme' ); ?></p>
		<?php settings_errors( 'pfoa_footer_messages' ); ?>
		<form method="post" action="">
			<?php wp_nonce_field( 'pfoa_footer_save', 'pfoa_footer_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $groups as $key => $label ) : ?>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( 'pfoa-footer-' . $key . '-heading' ); ?>"><?php echo esc_html( sprintf( __( '%s heading', 'pfoa-theme' ), $label ) ); ?></label></th>
							<td><input type="text" id="<?php echo esc_attr( 'pfoa-footer-' . $key . '-heading' ); ?>" class="regular-text" name="<?php echo esc_attr( PFOA_FOOTER_OPTION ); ?>[<?php echo esc_attr( $key ); ?>][heading]" value="<?php echo esc_attr( $footer[ $key ]['heading'] ); ?>" /></td>
						</tr>
						<tr>
							<th scope="row"><label for="<?php echo esc_attr( 'pfoa-footer-' . $key . '-body' ); ?>"><?php echo esc_html( sprintf( __( '%s text', 'pfoa-theme' ), $label ) ); ?></label></th>
							<td><textarea id="<?php echo esc_attr( 'pfoa-footer-' . $key . '-body' ); ?>" class="large-text" rows="3" name="<?php echo esc_attr( PFOA_FOOTER_OPTION ); ?>[<?php echo esc_attr( $key ); ?>][body]"><?php echo esc_textarea( $footer[ $key ]['body'] ); ?></textarea></td>
						</tr>
					<?php endforeach; ?>
					<tr>
						<th scope="row"><label for="pfoa-footer-copyright"><?php esc_html_e( 'Copyright text', 'pfoa-theme' ); ?></label></th>
						<td><input type="text" id="pfoa-footer-copyright" class="regular-text" name="<?php echo esc_attr( PFOA_FOOTER_OPTION ); ?>[copyright_text]" value="<?php echo esc_attr( $footer['copyright_text'] ); ?>" /></td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary" name="pfoa_footer_save" value="1"><?php esc_html_e( 'Save', 'pfoa-theme' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

if ( ! defined( 'PFOA_HEADER_OPTION' ) ) {
	define( 'PFOA_HEADER_OPTION', 'pfoa_header' );
}

/**
 * Return the theme-owned default header values.
 *
 * Defaults reproduce the 0.1.23 hard-coded header exactly so a fresh
 * install renders identical content without any save: Donate label and the
 * effective donation destination helper.
 *
 * @return array
 */
function pfoa_get_header_defaults() {
	return array(
		'donate' => array(
			'label' => __( 'Donate', 'pfoa-theme' ),
			'url'   => pfoa_get_donation_url(),
		),
	);
}

/**
 * Sanitize the header option value. Only the Donate label + URL are stored
 * here; logo lives in theme_mod custom_logo and nav lives in the assigned
 * primary menu.
 *
 * @param mixed $value Raw submitted value (expected unslashed).
 * @return array
 */
function pfoa_sanitize_header( $value ) {
	$defaults = pfoa_get_header_defaults();
	$header   = $defaults;

	if ( ! is_array( $value ) ) {
		return $header;
	}

	$donate = isset( $value['donate'] ) && is_array( $value['donate'] ) ? $value['donate'] : array();

	$label = isset( $donate['label'] ) ? sanitize_text_field( $donate['label'] ) : '';
	$header['donate']['label'] = '' === trim( $label ) ? $defaults['donate']['label'] : trim( $label );

	$url = isset( $donate['url'] ) ? esc_url_raw( trim( $donate['url'] ) ) : '';
	$header['donate']['url'] = '' === $url ? $defaults['donate']['url'] : $url;

	return $header;
}

/**
 * Return the effective header: saved option merged over defaults.
 *
 * Missing keys fall back to defaults so partial saves never render empty.
 *
 * @return array
 */
function pfoa_get_header() {
	$defaults = pfoa_get_header_defaults();
	$saved    = get_option( PFOA_HEADER_OPTION, array() );

	if ( ! is_array( $saved ) || array() === $saved ) {
		return $defaults;
	}

	return pfoa_sanitize_header( $saved );
}

/**
 * Register the header setting.
 *
 * @return void
 */
function pfoa_header_admin_init() {
	register_setting(
		'pfoa_header_group',
		PFOA_HEADER_OPTION,
		array(
			'sanitize_callback' => 'pfoa_sanitize_header',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'pfoa_header_admin_init' );

/**
 * Return the menu ID assigned to the primary location, or 0 when none.
 *
 * @return int
 */
function pfoa_header_primary_menu_id() {
	$locations = get_nav_menu_locations();

	if ( empty( $locations['primary'] ) ) {
		return 0;
	}

	$menu_id = absint( $locations['primary'] );

	if ( ! $menu_id || ! wp_get_nav_menu_object( $menu_id ) ) {
		return 0;
	}

	return $menu_id;
}

/**
 * Handle save submissions for the PFOA Site > Header screen.
 *
 * Plain PHP + submit only: the form posts back to the same page. Donate
 * fields are stored in option pfoa_header; the logo is stored in theme_mod
 * custom_logo; navigation edits apply to the existing primary menu.
 *
 * @return void
 */
function pfoa_header_handle_post() {
	if ( ! isset( $_POST['pfoa_header_nonce'] ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'pfoa_header_save', 'pfoa_header_nonce' );

	$raw    = isset( $_POST[ PFOA_HEADER_OPTION ] ) && is_array( $_POST[ PFOA_HEADER_OPTION ] ) ? wp_unslash( $_POST[ PFOA_HEADER_OPTION ] ) : array();
	$header = pfoa_sanitize_header( $raw );

	update_option( PFOA_HEADER_OPTION, $header );

	if ( isset( $_POST['pfoa_header_logo_remove'] ) && '1' === (string) $_POST['pfoa_header_logo_remove'] ) {
		remove_theme_mod( 'custom_logo' );
	} elseif ( isset( $_POST['pfoa_header_logo_id'] ) ) {
		$logo_id = absint( $_POST['pfoa_header_logo_id'] );

		if ( $logo_id > 0 ) {
			set_theme_mod( 'custom_logo', $logo_id );
		} else {
			remove_theme_mod( 'custom_logo' );
		}
	}

	$menu_id = pfoa_header_primary_menu_id();

	if ( $menu_id ) {
		$posted_nav = isset( $_POST['pfoa_header_nav'] ) && is_array( $_POST['pfoa_header_nav'] ) ? wp_unslash( $_POST['pfoa_header_nav'] ) : array();
		$delete_ids = isset( $_POST['pfoa_header_nav_delete'] ) && is_array( $_POST['pfoa_header_nav_delete'] ) ? array_map( 'absint', wp_unslash( $_POST['pfoa_header_nav_delete'] ) ) : array();

		foreach ( $delete_ids as $delete_id ) {
			if ( ! $delete_id ) {
				continue;
			}

			$item = get_post( $delete_id );

			if ( $item && 'nav_menu_item' === $item->post_type ) {
				wp_delete_post( $delete_id, true );
			}
		}

		$position = 1;

		foreach ( $posted_nav as $item_id => $fields ) {
			$item_id = absint( $item_id );

			if ( ! $item_id || in_array( $item_id, $delete_ids, true ) ) {
				continue;
			}

			$item = get_post( $item_id );

			if ( ! $item || 'nav_menu_item' !== $item->post_type ) {
				continue;
			}

			if ( ! is_array( $fields ) ) {
				$position++;
				continue;
			}

			$parent_id = isset( $fields['parent'] ) ? absint( $fields['parent'] ) : (int) get_post_meta( $item_id, '_menu_item_menu_item_parent', true );

			if ( $parent_id === $item_id ) {
				$parent_id = 0;
			}

			$args = array(
				'menu-item-parent-id' => $parent_id,
				'menu-item-position'  => $position,
			);

			$label = isset( $fields['label'] ) ? trim( sanitize_text_field( $fields['label'] ) ) : '';

			if ( '' !== $label ) {
				$args['menu-item-title'] = $label;
			}

			$url = isset( $fields['url'] ) ? esc_url_raw( trim( $fields['url'] ) ) : '';

			if ( '' !== $url ) {
				$args['menu-item-url'] = $url;
			}

			wp_update_nav_menu_item( $menu_id, $item_id, $args );

			$position++;
		}

		$new_item = isset( $_POST['pfoa_header_nav_new'] ) && is_array( $_POST['pfoa_header_nav_new'] ) ? wp_unslash( $_POST['pfoa_header_nav_new'] ) : array();
		$new_label = isset( $new_item['label'] ) ? trim( sanitize_text_field( $new_item['label'] ) ) : '';
		$new_url   = isset( $new_item['url'] ) ? esc_url_raw( trim( $new_item['url'] ) ) : '';

		if ( '' !== $new_label && '' !== $new_url ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'  => $new_label,
					'menu-item-url'    => $new_url,
					'menu-item-status' => 'publish',
					'menu-item-type'   => 'custom',
				)
			);
		}
	}

	add_settings_error(
		'pfoa_header_messages',
		'pfoa_header_saved',
		esc_html__( 'Header updated.', 'pfoa-theme' ),
		'success'
	);
}

/**
 * Enqueue media scripts only on the PFOA Site > Header screen.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function pfoa_header_admin_assets( $hook_suffix ) {
	if ( ! isset( $_GET['page'] ) || 'pfoa-site-header' !== (string) $_GET['page'] ) {
		return;
	}

	if ( ! current_user_can( 'edit_pages' ) ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'pfoa_header_admin_assets' );

/**
 * Render the PFOA Site > Header screen.
 *
 * Sections: Site Logo (custom_logo preview + Choose/Replace/Remove),
 * Navigation (ordered rows for the existing primary menu), Donate Button
 * (label + destination). Plain POST to the same page.
 *
 * Menu access requires edit_pages; saving requires edit_theme_options or
 * manage_options (same boundary as the Footer screen).
 *
 * @return void
 */
function pfoa_site_header_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the header.', 'pfoa-theme' ) );
	}

	pfoa_header_handle_post();

	$header    = pfoa_get_header();
	$logo_id   = absint( get_theme_mod( 'custom_logo', 0 ) );
	$menu_id   = pfoa_header_primary_menu_id();
	$menu_items = $menu_id ? wp_get_nav_menu_items( $menu_id, array( 'orderby' => 'menu_order', 'order' => 'ASC' ) ) : false;

	if ( is_array( $menu_items ) ) {
		usort(
			$menu_items,
			function ( $a, $b ) {
				return (int) $a->menu_order - (int) $b->menu_order;
			}
		);
	}

	$items_by_parent = array();

	if ( is_array( $menu_items ) ) {
		foreach ( $menu_items as $menu_item ) {
			$parent = (int) get_post_meta( $menu_item->ID, '_menu_item_menu_item_parent', true );
			$items_by_parent[ $parent ][] = $menu_item;
		}
	}

	$ordered_items = array();
	$render_branch = null;
	$render_branch = function ( $parent_id, $depth ) use ( &$render_branch, &$ordered_items, $items_by_parent ) {
		if ( empty( $items_by_parent[ $parent_id ] ) ) {
			return;
		}

		foreach ( $items_by_parent[ $parent_id ] as $menu_item ) {
			$ordered_items[] = array(
				'item'  => $menu_item,
				'depth' => $depth,
			);
			$render_branch( (int) $menu_item->ID, $depth + 1 );
		}
	};
	$render_branch( 0, 0 );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Header', 'pfoa-theme' ); ?></h1>
		<p><?php esc_html_e( 'Edit the site logo, navigation, and donate button. No layout or styling settings here.', 'pfoa-theme' ); ?></p>
		<?php settings_errors( 'pfoa_header_messages' ); ?>
		<form method="post" action="" id="pfoa-header-form">
			<?php wp_nonce_field( 'pfoa_header_save', 'pfoa_header_nonce' ); ?>
			<h2><?php esc_html_e( 'Site Logo', 'pfoa-theme' ); ?></h2>
			<div id="pfoa-header-logo-preview">
				<?php
				if ( $logo_id ) {
					echo wp_get_attachment_image( $logo_id, 'medium' );
				} else {
					echo '<p>' . esc_html__( 'No logo set. The site title is shown instead.', 'pfoa-theme' ) . '</p>';
				}
				?>
			</div>
			<p>
				<input type="hidden" id="pfoa-header-logo-id" name="pfoa_header_logo_id" value="<?php echo esc_attr( (string) $logo_id ); ?>" />
				<input type="hidden" id="pfoa-header-logo-remove" name="pfoa_header_logo_remove" value="0" />
				<button type="button" class="button" id="pfoa-header-logo-choose"><?php $logo_id ? esc_html_e( 'Replace logo', 'pfoa-theme' ) : esc_html_e( 'Choose logo', 'pfoa-theme' ); ?></button>
				<?php if ( $logo_id ) : ?>
					<button type="button" class="button" id="pfoa-header-logo-clear"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
				<?php endif; ?>
			</p>
			<h2><?php esc_html_e( 'Navigation', 'pfoa-theme' ); ?></h2>
			<?php if ( ! $menu_id || ! is_array( $menu_items ) ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'No menu is assigned to the Primary location, so the theme currently shows a fallback Home link. Assign a menu under Appearance → Menus to edit navigation here.', 'pfoa-theme' ); ?></p></div>
			<?php elseif ( array() === $ordered_items ) : ?>
				<p><?php esc_html_e( 'The primary menu has no items yet.', 'pfoa-theme' ); ?></p>
			<?php else : ?>
				<table class="widefat striped" id="pfoa-header-nav-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Destination', 'pfoa-theme' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $ordered_items as $entry ) : ?>
							<?php
							$nav_item  = $entry['item'];
							$nav_depth = (int) $entry['depth'];
							$nav_parent = (int) get_post_meta( $nav_item->ID, '_menu_item_menu_item_parent', true );
							$nav_url = $nav_item->url ? $nav_item->url : get_post_meta( $nav_item->ID, '_menu_item_url', true );
							?>
							<tr data-item-id="<?php echo esc_attr( (string) $nav_item->ID ); ?>">
								<td>
									<?php echo str_repeat( '&mdash; ', $nav_depth ); ?>
									<label class="screen-reader-text" for="<?php echo esc_attr( 'pfoa-header-nav-label-' . $nav_item->ID ); ?>"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></label>
									<input type="text" id="<?php echo esc_attr( 'pfoa-header-nav-label-' . $nav_item->ID ); ?>" class="regular-text" name="pfoa_header_nav[<?php echo esc_attr( (string) $nav_item->ID ); ?>][label]" value="<?php echo esc_attr( $nav_item->title ); ?>" />
									<input type="hidden" name="pfoa_header_nav[<?php echo esc_attr( (string) $nav_item->ID ); ?>][parent]" value="<?php echo esc_attr( (string) $nav_parent ); ?>" />
								</td>
								<td>
									<label class="screen-reader-text" for="<?php echo esc_attr( 'pfoa-header-nav-url-' . $nav_item->ID ); ?>"><?php esc_html_e( 'Destination', 'pfoa-theme' ); ?></label>
									<input type="url" id="<?php echo esc_attr( 'pfoa-header-nav-url-' . $nav_item->ID ); ?>" class="regular-text" name="pfoa_header_nav[<?php echo esc_attr( (string) $nav_item->ID ); ?>][url]" value="<?php echo esc_attr( $nav_url ); ?>" />
								</td>
								<td>
									<button type="button" class="button pfoa-header-nav-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
									<button type="button" class="button pfoa-header-nav-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
									<button type="button" class="button pfoa-header-nav-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
			<div id="pfoa-header-nav-delete-wrap"></div>
			<h3><?php esc_html_e( 'Add Navigation Item', 'pfoa-theme' ); ?></h3>
			<p>
				<label for="pfoa-header-nav-new-label"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></label>
				<input type="text" id="pfoa-header-nav-new-label" class="regular-text" name="pfoa_header_nav_new[label]" value="" />
			</p>
			<p>
				<label for="pfoa-header-nav-new-url"><?php esc_html_e( 'Destination', 'pfoa-theme' ); ?></label>
				<input type="url" id="pfoa-header-nav-new-url" class="regular-text" name="pfoa_header_nav_new[url]" value="" placeholder="https://" />
			</p>
			<h2><?php esc_html_e( 'Donate Button', 'pfoa-theme' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="pfoa-header-donate-label"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></label></th>
						<td><input type="text" id="pfoa-header-donate-label" class="regular-text" name="<?php echo esc_attr( PFOA_HEADER_OPTION ); ?>[donate][label]" value="<?php echo esc_attr( $header['donate']['label'] ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="pfoa-header-donate-url"><?php esc_html_e( 'Destination', 'pfoa-theme' ); ?></label></th>
						<td><input type="url" id="pfoa-header-donate-url" class="regular-text" name="<?php echo esc_attr( PFOA_HEADER_OPTION ); ?>[donate][url]" value="<?php echo esc_attr( $header['donate']['url'] ); ?>" /></td>
					</tr>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" class="button button-primary" name="pfoa_header_save" value="1"><?php esc_html_e( 'Save Header', 'pfoa-theme' ); ?></button>
			</p>
		</form>
		<script type="text/javascript">
		(function() {
			var logoChoose = document.getElementById( 'pfoa-header-logo-choose' );
			var logoClear = document.getElementById( 'pfoa-header-logo-clear' );
			var logoId = document.getElementById( 'pfoa-header-logo-id' );
			var logoRemove = document.getElementById( 'pfoa-header-logo-remove' );
			var logoPreview = document.getElementById( 'pfoa-header-logo-preview' );
			var frame = null;

			if ( logoChoose && logoId && window.wp && window.wp.media ) {
				logoChoose.addEventListener( 'click', function() {
					if ( frame ) {
						frame.open();
						return;
					}

					frame = window.wp.media( {
						title: '<?php echo esc_js( __( 'Choose site logo', 'pfoa-theme' ) ); ?>',
						button: { text: '<?php echo esc_js( __( 'Use as logo', 'pfoa-theme' ) ); ?>' },
						library: { type: 'image' },
						multiple: false
					} );

					frame.on( 'select', function() {
						var attachment = frame.state().get( 'selection' ).first().toJSON();

						if ( ! attachment || ! attachment.id ) {
							return;
						}

						logoId.value = String( attachment.id );

						if ( logoRemove ) {
							logoRemove.value = '0';
						}

						if ( logoPreview ) {
							var previewUrl = ( attachment.sizes && attachment.sizes.medium && attachment.sizes.medium.url ) ? attachment.sizes.medium.url : attachment.url;
							logoPreview.innerHTML = '<img src="' + previewUrl + '" alt="" style="max-width:300px;height:auto;" />';
						}
					} );

					frame.open();
				} );
			}

			if ( logoClear && logoId && logoPreview ) {
				logoClear.addEventListener( 'click', function() {
					logoId.value = '0';

					if ( logoRemove ) {
						logoRemove.value = '1';
					}

					logoPreview.innerHTML = '<p><?php echo esc_js( __( 'Logo will be removed on save. The site title is shown instead.', 'pfoa-theme' ) ); ?></p>';
				} );
			}

			var table = document.getElementById( 'pfoa-header-nav-table' );

			if ( table ) {
				var tbody = table.querySelector( 'tbody' );
				var deleteWrap = document.getElementById( 'pfoa-header-nav-delete-wrap' );

				tbody.addEventListener( 'click', function( event ) {
					var button = event.target.closest( 'button' );

					if ( ! button ) {
						return;
					}

					var row = event.target.closest( 'tr' );

					if ( ! row ) {
						return;
					}

					if ( button.classList.contains( 'pfoa-header-nav-up' ) ) {
						var prev = row.previousElementSibling;

						if ( prev ) {
							tbody.insertBefore( row, prev );
						}
					} else if ( button.classList.contains( 'pfoa-header-nav-down' ) ) {
						var next = row.nextElementSibling;

						if ( next ) {
							tbody.insertBefore( next, row );
						}
					} else if ( button.classList.contains( 'pfoa-header-nav-remove' ) ) {
						var itemId = row.getAttribute( 'data-item-id' );

						if ( itemId && deleteWrap ) {
							var input = document.createElement( 'input' );
							input.type = 'hidden';
							input.name = 'pfoa_header_nav_delete[]';
							input.value = itemId;
							deleteWrap.appendChild( input );
						}

						row.remove();
					}
				} );
			}
		})();
		</script>
	</div>
	<?php
}

/**
 * Register the PFOA Site top-level menu and its Homepage screen.
 *
 * The menu uses the edit_pages capability so anyone who can edit pages sees
 * it; per-page access is enforced inside the screen and save callbacks with
 * current_user_can( 'edit_page', $front_id ).
 *
 * The submenu reuses the top-level slug so WordPress replaces the automatic
 * duplicate entry with the single visible Homepage item.
 *
 * @return void
 */
function pfoa_site_admin_menu() {
	add_menu_page(
		esc_html__( 'PFOA Site', 'pfoa-theme' ),
		esc_html__( 'PFOA Site', 'pfoa-theme' ),
		'edit_pages',
		'pfoa-site',
		'pfoa_site_homepage_page',
		'dashicons-admin-home',
		59
	);

	add_submenu_page(
		'pfoa-site',
		esc_html__( 'Homepage', 'pfoa-theme' ),
		esc_html__( 'Homepage', 'pfoa-theme' ),
		'edit_pages',
		'pfoa-site',
		'pfoa_site_homepage_page'
	);

	add_submenu_page(
		'pfoa-site',
		esc_html__( 'Header', 'pfoa-theme' ),
		esc_html__( 'Header', 'pfoa-theme' ),
		'edit_pages',
		'pfoa-site-header',
		'pfoa_site_header_page'
	);

	add_submenu_page(
		'pfoa-site',
		esc_html__( 'Footer', 'pfoa-theme' ),
		esc_html__( 'Footer', 'pfoa-theme' ),
		'edit_pages',
		'pfoa-site-footer',
		'pfoa_site_footer_page'
	);
}
add_action( 'admin_menu', 'pfoa_site_admin_menu' );

/**
 * Render the PFOA Site > Homepage screen (Hero Section + Homepage Cards).
 *
 * Section (1) Hero: unchanged form posting to admin-post.php action
 * pfoa_homepage_hero_save. Storage stays exactly where the page meta box
 * kept it: post meta key _pfoa_hero on the page assigned as the static front
 * page. Nothing is stored in options and post_content is never touched.
 *
 * Section (2) Homepage Cards: rendered below the hero via
 * pfoa_homepage_cards_section() with its own separate save form. Storage
 * stays in option pfoa_homepage_cards via update_option()/get_option().
 *
 * @return void
 */
function pfoa_site_homepage_page() {
	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$front_id = pfoa_homepage_front_id();

	if ( isset( $_GET['pfoa-homepage-updated'] ) ) {
		add_settings_error(
			'pfoa_homepage_messages',
			'pfoa_homepage_saved',
			esc_html__( 'Homepage updated.', 'pfoa-theme' ),
			'success'
		);
	}

	if ( isset( $_GET['pfoa-hero-link-warning'] ) && is_string( $_GET['pfoa-hero-link-warning'] ) ) {
		$warning_tokens = array_filter( array_map( 'trim', explode( ',', wp_unslash( $_GET['pfoa-hero-link-warning'] ) ) ) );

		foreach ( $warning_tokens as $warning_token ) {
			if ( ctype_digit( $warning_token ) && (int) $warning_token >= 1 ) {
				add_settings_error(
					'pfoa_homepage_messages',
					'pfoa_hero_link_slide_invalid_' . $warning_token,
					sprintf(
						/* translators: %d: 1-based carousel slide number. */
						esc_html__( 'Slide %d: the external URL was invalid, so its link was removed. The rest of the homepage saved normally.', 'pfoa-theme' ),
						(int) $warning_token
					),
					'warning'
				);
			}
		}
	}

	$can_edit_hero = $front_id && current_user_can( 'edit_page', $front_id );

	if ( $front_id && ! current_user_can( 'edit_page', $front_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$hero         = $front_id ? pfoa_get_hero( $front_id ) : pfoa_get_hero_defaults();
	$carousel_ids = ( isset( $hero['carousel_ids'] ) && is_array( $hero['carousel_ids'] ) ) ? $hero['carousel_ids'] : array();
	$carousel_interval_ms = isset( $hero['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $hero['carousel_interval_ms'] ) : 5000;
	$carousel_slide_count = isset( $hero['carousel_slide_count'] ) ? pfoa_sanitize_hero_carousel_slide_count( $hero['carousel_slide_count'] ) : 3;
	$carousel_items = array();

		if ( isset( $hero['carousel_items'] ) && is_array( $hero['carousel_items'] ) && array() !== $hero['carousel_items'] ) {
		foreach ( $hero['carousel_items'] as $carousel_item ) {
			if ( ! is_array( $carousel_item ) ) {
				continue;
			}

			$carousel_items[] = array(
				'id'             => isset( $carousel_item['id'] ) ? absint( $carousel_item['id'] ) : 0,
				'link_page_id'   => isset( $carousel_item['link_page_id'] ) ? ( is_string( $carousel_item['link_page_id'] ) ? trim( $carousel_item['link_page_id'] ) : absint( $carousel_item['link_page_id'] ) ) : 0,
				'link_type'      => pfoa_resolve_hero_link_type(
					array(
						'link_type'    => isset( $carousel_item['link_type'] ) ? $carousel_item['link_type'] : null,
						'link_page_id' => isset( $carousel_item['link_page_id'] ) ? ( is_string( $carousel_item['link_page_id'] ) ? trim( $carousel_item['link_page_id'] ) : absint( $carousel_item['link_page_id'] ) ) : 0,
					)
				),
				'link_url'       => isset( $carousel_item['link_url'] ) ? (string) $carousel_item['link_url'] : '',
				'headline'       => isset( $carousel_item['headline'] ) ? $carousel_item['headline'] : '',
				'brightness'     => isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100,
				'headline_scale' => isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100,
				'headline_color' => isset( $carousel_item['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $carousel_item['headline_color'] ) : 'white',
				'zoom'           => isset( $carousel_item['zoom'] ) ? pfoa_sanitize_hero_zoom( $carousel_item['zoom'] ) : 100,
				'position'       => isset( $carousel_item['position'] ) ? pfoa_sanitize_hero_position( $carousel_item['position'] ) : 'center center',
			);
		}
	} else {
		foreach ( $carousel_ids as $carousel_id ) {
			$carousel_items[] = array(
				'id'             => absint( $carousel_id ),
				'link_page_id'   => 0,
				'link_type'      => 'none',
				'link_url'       => '',
				'headline'       => '',
				'brightness'     => 100,
				'headline_scale' => 100,
				'headline_color' => 'white',
				'zoom'           => 100,
				'position'       => 'center center',
			);
		}
	}

	// Always show max(N, stored) editable rows (capped at 8): empty slots
	// render as image-less rows with their own picker. Imageless rows submit
	// id=0, which the sanitizer drops, so storage keeps only valid slides
	// while the form re-expands to N on every load.
	while ( count( $carousel_items ) < $carousel_slide_count && count( $carousel_items ) < 8 ) {
		$carousel_items[] = array(
			'id'             => 0,
			'link_page_id'   => 0,
			'link_type'      => 'none',
			'link_url'       => '',
			'headline'       => '',
			'brightness'     => 100,
			'headline_scale' => 100,
			'headline_color' => 'white',
			'zoom'           => 100,
			'position'       => 'center center',
		);
	}

	$ctas         = ( isset( $hero['ctas'] ) && is_array( $hero['ctas'] ) ) ? $hero['ctas'] : array();

	$featured_pin      = pfoa_sanitize_hero_featured_pin( isset( $hero['featured_pin'] ) ? $hero['featured_pin'] : array() );
	$pinned_slide_id   = pfoa_get_hero_pinned_slide_id( $hero );
	$pinned_slide_headline = '';

	if ( $featured_pin['slide'] >= 1 && isset( $carousel_items[ $featured_pin['slide'] - 1 ] ) && is_array( $carousel_items[ $featured_pin['slide'] - 1 ] ) ) {
		$pinned_slide_headline = isset( $carousel_items[ $featured_pin['slide'] - 1 ]['headline'] ) ? trim( (string) $carousel_items[ $featured_pin['slide'] - 1 ]['headline'] ) : '';
	}

	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Homepage', 'pfoa-theme' ); ?></h1>
		<?php settings_errors( 'pfoa_homepage_messages' ); ?>
		<?php if ( ! $front_id ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Set Settings → Reading → static front page to a published page before editing the hero here. Homepage cards below can still be edited.', 'pfoa-theme' ); ?></p></div>
		<?php elseif ( $can_edit_hero ) : ?>
		<p><?php printf( esc_html__( 'Editing the homepage: %s', 'pfoa-theme' ), esc_html( get_the_title( $front_id ) ) ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="pfoa_homepage_hero_save" />
			<?php wp_nonce_field( 'pfoa_hero_save', 'pfoa_hero_nonce' ); ?>
			<h2><?php esc_html_e( 'Hero Section', 'pfoa-theme' ); ?></h2>
			<h3><?php esc_html_e( 'Hero Media', 'pfoa-theme' ); ?></h3>
		<p><?php esc_html_e( 'Edit the hero section shown at the top of the site homepage. Each carousel image has its own headline; images without one show no headline. The buttons stay fixed while carousel images change behind them.', 'pfoa-theme' ); ?></p>
		<p class="submit">
			<button type="submit" class="button button-primary" name="pfoa_hero_save" value="1"><?php esc_html_e( 'Save Homepage', 'pfoa-theme' ); ?></button>
		</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="pfoa-hero-carousel-slide-count"><?php esc_html_e( 'Number of Carousel Slides', 'pfoa-theme' ); ?></label></th>
			<td>
				<select id="pfoa-hero-carousel-slide-count" name="pfoa_hero[carousel_slide_count]">
					<option value="2"<?php selected( $carousel_slide_count, 2 ); ?>><?php esc_html_e( '2 slides', 'pfoa-theme' ); ?></option>
					<option value="3"<?php selected( $carousel_slide_count, 3 ); ?>><?php esc_html_e( '3 slides', 'pfoa-theme' ); ?></option>
					<option value="4"<?php selected( $carousel_slide_count, 4 ); ?>><?php esc_html_e( '4 slides', 'pfoa-theme' ); ?></option>
					<option value="5"<?php selected( $carousel_slide_count, 5 ); ?>><?php esc_html_e( '5 slides', 'pfoa-theme' ); ?></option>
					<option value="6"<?php selected( $carousel_slide_count, 6 ); ?>><?php esc_html_e( '6 slides', 'pfoa-theme' ); ?></option>
					<option value="7"<?php selected( $carousel_slide_count, 7 ); ?>><?php esc_html_e( '7 slides', 'pfoa-theme' ); ?></option>
					<option value="8"<?php selected( $carousel_slide_count, 8 ); ?>><?php esc_html_e( '8 slides', 'pfoa-theme' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'How many of the carousel slots below show on the homepage (2–8). Slots beyond this count are kept — they will show if the count is increased.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Featured slide', 'pfoa-theme' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Featured slide', 'pfoa-theme' ); ?></legend>
					<label>
						<input type="radio" name="pfoa_hero[featured_pin][slide]" value="0"<?php checked( $featured_pin['slide'], 0 ); ?> />
						<?php esc_html_e( 'Normal Carousel Rotation', 'pfoa-theme' ); ?>
					</label><br />
					<?php for ( $pin_option = 1; $pin_option <= $carousel_slide_count; $pin_option++ ) : ?>
						<label>
							<input type="radio" name="pfoa_hero[featured_pin][slide]" value="<?php echo esc_attr( (string) $pin_option ); ?>"<?php checked( $featured_pin['slide'], $pin_option ); ?> />
							<?php printf( esc_html__( 'Pin Slide %d', 'pfoa-theme' ), $pin_option ); ?>
						</label><br />
					<?php endfor; ?>
				</fieldset>
				<div class="pfoa-hero-pin-mode"<?php echo $featured_pin['slide'] ? '' : ' hidden'; ?>>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Pin duration', 'pfoa-theme' ); ?></legend>
						<label>
							<input type="radio" name="pfoa_hero[featured_pin][mode]" value="timed"<?php checked( $featured_pin['slide'] ? $featured_pin['mode'] : 'timed', 'timed' ); ?> />
							<?php esc_html_e( '24 hours', 'pfoa-theme' ); ?>
						</label>
						<label>
							<input type="radio" name="pfoa_hero[featured_pin][mode]" value="indefinite"<?php checked( $featured_pin['slide'] ? $featured_pin['mode'] : 'timed', 'indefinite' ); ?> />
							<?php esc_html_e( 'Until Unpinned', 'pfoa-theme' ); ?>
						</label>
					</fieldset>
					<input type="hidden" name="pfoa_hero[featured_pin][expires_at]" value="<?php echo esc_attr( (string) $featured_pin['expires_at'] ); ?>" />
				</div>
				<p class="description">
					<?php if ( ! $featured_pin['slide'] || 'none' === $featured_pin['mode'] ) : ?>
						<?php esc_html_e( 'Normal carousel rotation.', 'pfoa-theme' ); ?>
					<?php elseif ( $pinned_slide_id ) : ?>
						<?php
						if ( 'timed' === $featured_pin['mode'] ) {
							printf(
								/* translators: 1: slide number, 2: slide headline, 3: local expiry date/time. */
								esc_html__( 'Pinned: Slide %1$d — %2$s, expires %3$s.', 'pfoa-theme' ),
								(int) $featured_pin['slide'],
								'' !== $pinned_slide_headline ? $pinned_slide_headline : sprintf( __( 'Slide %d', 'pfoa-theme' ), (int) $featured_pin['slide'] ),
								date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $featured_pin['expires_at'] )
							);
						} else {
							printf(
								/* translators: 1: slide number, 2: slide headline. */
								esc_html__( 'Pinned: Slide %1$d — %2$s, shows until unpinned.', 'pfoa-theme' ),
								(int) $featured_pin['slide'],
								'' !== $pinned_slide_headline ? $pinned_slide_headline : sprintf( __( 'Slide %d', 'pfoa-theme' ), (int) $featured_pin['slide'] )
							);
						}
						?>
					<?php elseif ( 'timed' === $featured_pin['mode'] && (int) $featured_pin['expires_at'] <= (int) current_time( 'timestamp' ) ) : ?>
						<?php esc_html_e( 'Pin expired — returns to rotation.', 'pfoa-theme' ); ?>
					<?php elseif ( (int) $featured_pin['slide'] > (int) $carousel_slide_count ) : ?>
						<?php esc_html_e( 'Pinned slide is beyond the current slide count — rotation continues normally.', 'pfoa-theme' ); ?>
					<?php else : ?>
						<?php esc_html_e( 'Pinned slide has no valid image — rotation continues normally.', 'pfoa-theme' ); ?>
					<?php endif; ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Carousel images', 'pfoa-theme' ); ?></th>
			<td>
				<style>
.pfoa-hero-carousel-list{list-style:none;margin:0 0 12px;padding:0;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-carousel-item{margin:0 0 12px;padding:0;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-slide-card{background:#fff;border:1px solid #c8c0b5;border-radius:6px;max-width:100%;box-sizing:border-box;overflow:hidden;}
.pfoa-hero-carousel-list .pfoa-hero-carousel-item.is-inactive .pfoa-hero-slide-card,.pfoa-hero-carousel-list .pfoa-hero-slide-card.is-inactive{opacity:.55;background:#f6f5f2;}
.pfoa-hero-carousel-list .pfoa-hero-slide-summary{display:flex;align-items:center;gap:10px;padding:10px 12px;margin:0;cursor:pointer;list-style:none;max-width:100%;box-sizing:border-box;}
.pfoa-hero-carousel-list .pfoa-hero-slide-summary::-webkit-details-marker{display:none;}
.pfoa-hero-carousel-list .pfoa-hero-slide-summary::before{content:"\25b8";flex:none;color:#50575e;font-size:14px;line-height:1;}
.pfoa-hero-carousel-list .pfoa-hero-slide-card[open] .pfoa-hero-slide-summary::before{content:"\25be";}
.pfoa-hero-carousel-list .pfoa-hero-slide-summary:focus-visible{outline:2px solid #2271b1;outline-offset:2px;}
.pfoa-hero-carousel-list .pfoa-hero-slide-badge{flex:none;font-weight:600;white-space:nowrap;}
.pfoa-hero-carousel-list .pfoa-hero-slide-thumb{flex:none;width:44px;height:44px;}
.pfoa-hero-carousel-list .pfoa-hero-slide-thumb img{width:44px;height:44px;object-fit:cover;display:block;border-radius:4px;}
.pfoa-hero-carousel-list .pfoa-hero-slide-empty{display:block;width:44px;height:44px;box-sizing:border-box;border:1px dashed #c8c0b5;border-radius:4px;background:#f6f5f2;}
.pfoa-hero-carousel-list .pfoa-hero-slide-headline{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;}
.pfoa-hero-carousel-list .pfoa-hero-slide-link{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#50575e;}
.pfoa-hero-carousel-list .pfoa-hero-slide-status{flex:none;font-size:12px;line-height:1.6;padding:2px 10px;border-radius:999px;border:1px solid #dcdcde;white-space:nowrap;}
.pfoa-hero-carousel-list .pfoa-hero-slide-status.is-active{background:#e7f5ea;color:#1d5c2f;border-color:#b6dfc0;}
.pfoa-hero-carousel-list .pfoa-hero-slide-status.is-inactive{background:#f0f0f1;color:#50575e;font-style:italic;}
.pfoa-hero-carousel-list .pfoa-hero-slide-pinned{flex:none;font-size:12px;line-height:1.6;padding:2px 10px;border-radius:999px;border:1px solid #e9ad4e;background:#fdf3e3;color:#713033;white-space:nowrap;}
.pfoa-hero-carousel-list .pfoa-hero-slide-body{padding:4px 12px 12px;border-top:1px solid #dcdcde;max-width:100%;box-sizing:border-box;}
.pfoa-hero-carousel-list .pfoa-hero-slide-section{padding:10px 0;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-slide-section + .pfoa-hero-slide-section{border-top:1px solid #dcdcde;}
.pfoa-hero-carousel-list .pfoa-hero-slide-heading{margin:0 0 8px;font-size:13px;line-height:1.4;}
.pfoa-hero-carousel-list .pfoa-hero-slide-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px 12px;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-slide-body label{display:block;margin:8px 0;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-slide-grid label{margin:0;}
.pfoa-hero-carousel-list .pfoa-hero-slide-body input[type="text"],.pfoa-hero-carousel-list .pfoa-hero-slide-body select{max-width:100%;box-sizing:border-box;}
.pfoa-hero-carousel-list .pfoa-hero-carousel-preview{display:block;margin:8px 0;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-slide-actions{display:flex;gap:8px;flex-wrap:wrap;max-width:100%;}
.pfoa-hero-carousel-list .pfoa-hero-carousel-inactive-note{display:none;font-style:italic;color:#6f6963;margin:8px 0 0;}
.pfoa-hero-carousel-list .pfoa-hero-carousel-item.is-inactive .pfoa-hero-carousel-inactive-note,.pfoa-hero-carousel-list .pfoa-hero-slide-card.is-inactive .pfoa-hero-carousel-inactive-note{display:block;}
.pfoa-hero-carousel-list .pfoa-hero-slide-save-status{font-size:12px;line-height:2;color:#50575e;white-space:nowrap;}
.pfoa-hero-carousel-list .pfoa-hero-slide-save-status.is-unsaved{color:#8a5a00;font-weight:600;}
.pfoa-hero-carousel-list .pfoa-hero-slide-save-status.is-saved{color:#1d5c2f;font-weight:600;}
.pfoa-hero-carousel-list .pfoa-hero-slide-save-status.is-error{color:#b32d2e;font-weight:600;}
</style>
				<ul class="pfoa-hero-carousel-list" data-next-index="<?php echo esc_attr( (string) count( $carousel_items ) ); ?>" data-slide-count="<?php echo esc_attr( (string) $carousel_slide_count ); ?>">
					<?php foreach ( $carousel_items as $carousel_index => $carousel_item ) : ?>
						<?php
						$carousel_id        = absint( $carousel_item['id'] );
						$carousel_link      = isset( $carousel_item['link_page_id'] ) ? $carousel_item['link_page_id'] : 0;

						if ( ! is_string( $carousel_link ) ) {
							$carousel_link = absint( $carousel_link );
						}
						$carousel_link_type = pfoa_resolve_hero_link_type(
							array(
								'link_type'    => isset( $carousel_item['link_type'] ) ? $carousel_item['link_type'] : null,
								'link_page_id' => $carousel_link,
							)
						);
						$carousel_link_url = isset( $carousel_item['link_url'] ) ? trim( (string) $carousel_item['link_url'] ) : '';
					$carousel_headline  = isset( $carousel_item['headline'] ) ? $carousel_item['headline'] : '';
				$carousel_brightness = isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100;
				$carousel_headline_scale = isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100;
				$carousel_headline_color = isset( $carousel_item['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $carousel_item['headline_color'] ) : 'white';
				$carousel_zoom = isset( $carousel_item['zoom'] ) ? pfoa_sanitize_hero_zoom( $carousel_item['zoom'] ) : 100;
				$carousel_position = isset( $carousel_item['position'] ) ? pfoa_sanitize_hero_position( $carousel_item['position'] ) : 'center center';
						$carousel_preview   = $carousel_id ? wp_get_attachment_image( $carousel_id, array( 80, 80 ) ) : '';
						$carousel_field_base = 'pfoa_hero[carousel_items][' . $carousel_index . ']';
						$is_inactive_slot = $carousel_index >= $carousel_slide_count;
						?>
						<li class="pfoa-hero-carousel-item<?php echo $is_inactive_slot ? ' is-inactive' : ''; ?>" data-pfoa-slide-key="<?php echo esc_attr( (string) $carousel_id ); ?>">
							<?php
							$carousel_summary_thumb    = $carousel_preview ? $carousel_preview : '<span class="pfoa-hero-slide-empty" aria-hidden="true"></span>';
							$carousel_summary_headline = '' !== trim( (string) $carousel_headline ) ? $carousel_headline : __( '— No headline —', 'pfoa-theme' );
							$carousel_link_label       = __( '— No link —', 'pfoa-theme' );
							$carousel_link_found       = false;

							if ( is_string( $carousel_link ) && '' !== $carousel_link && '0' !== $carousel_link ) {
								foreach ( pfoa_get_hero_bonded_pairs() as $carousel_pair ) {
									if ( isset( $carousel_pair['token'], $carousel_pair['title'] ) && $carousel_link === (string) $carousel_pair['token'] ) {
										$carousel_link_label = sprintf( '%s — Bonded Pair', $carousel_pair['title'] );
										$carousel_link_found = true;
										break;
									}
								}
							} elseif ( absint( $carousel_link ) ) {
								foreach ( pfoa_get_hero_link_cat_profiles() as $carousel_cat ) {
									if ( $carousel_cat instanceof WP_Post && absint( $carousel_link ) === (int) $carousel_cat->ID ) {
										$carousel_link_label = sprintf( '%s (#%d)', $carousel_cat->post_title, $carousel_cat->ID );
										$carousel_link_found = true;
										break;
									}
								}

								if ( ! $carousel_link_found ) {
									$carousel_linked_title = get_the_title( absint( $carousel_link ) );

									if ( '' !== trim( (string) $carousel_linked_title ) ) {
										$carousel_link_label = $carousel_linked_title;
									}
								}
							}

							if ( 'external' === $carousel_link_type && '' !== $carousel_link_url ) {
								$carousel_link_label = strlen( $carousel_link_url ) > 48 ? substr( $carousel_link_url, 0, 47 ) . '…' : $carousel_link_url;
							}
							?>
							<details class="pfoa-hero-slide-card<?php echo $is_inactive_slot ? ' is-inactive' : ''; ?>"<?php echo 0 === $carousel_index ? ' open' : ''; ?>>
								<summary class="pfoa-hero-slide-summary">
									<span class="pfoa-hero-slide-badge pfoa-hero-carousel-slot"><?php printf( esc_html__( 'Slide %d', 'pfoa-theme' ), $carousel_index + 1 ); ?></span>
									<span class="pfoa-hero-slide-thumb"><?php echo $carousel_summary_thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
									<span class="pfoa-hero-slide-headline"><?php echo esc_html( $carousel_summary_headline ); ?></span>
									<span class="pfoa-hero-slide-link"><?php echo esc_html( $carousel_link_label ); ?></span>
									<span class="pfoa-hero-slide-status<?php echo $is_inactive_slot ? ' is-inactive' : ' is-active'; ?>"><?php echo $is_inactive_slot ? esc_html__( 'Inactive', 'pfoa-theme' ) : esc_html__( 'Active', 'pfoa-theme' ); ?></span>
									<?php if ( $pinned_slide_id && $carousel_id === $pinned_slide_id ) : ?>
										<span class="pfoa-hero-slide-pinned"><?php esc_html_e( 'Pinned', 'pfoa-theme' ); ?></span>
									<?php endif; ?>
								</summary>
								<div class="pfoa-hero-slide-body">
									<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide content', 'pfoa-theme' ); ?>">
										<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Content', 'pfoa-theme' ); ?></h4>
							<span class="pfoa-hero-carousel-preview"><?php echo $carousel_preview ? $carousel_preview : esc_html__( 'No image selected yet.', 'pfoa-theme' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
							<input type="hidden" class="pfoa-hero-carousel-id" name="<?php echo esc_attr( $carousel_field_base ); ?>[id]" value="<?php echo esc_attr( (string) $carousel_id ); ?>" />
							<button type="button" class="button pfoa-hero-carousel-select"><?php echo $carousel_preview ? esc_html__( 'Replace Image', 'pfoa-theme' ) : esc_html__( 'Select Image', 'pfoa-theme' ); ?></button>
								<fieldset class="pfoa-hero-link-type-group">
									<legend><?php esc_html_e( 'Link destination type', 'pfoa-theme' ); ?></legend>
									<label>
										<input type="radio" class="pfoa-hero-link-type-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[link_type]" value="internal"<?php checked( $carousel_link_type, 'internal' ); ?> />
										<?php esc_html_e( 'Internal', 'pfoa-theme' ); ?>
									</label>
									<label>
										<input type="radio" class="pfoa-hero-link-type-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[link_type]" value="external"<?php checked( $carousel_link_type, 'external' ); ?> />
										<?php esc_html_e( 'External URL', 'pfoa-theme' ); ?>
									</label>
									<label>
										<input type="radio" class="pfoa-hero-link-type-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[link_type]" value="none"<?php checked( $carousel_link_type, 'none' ); ?> />
										<?php esc_html_e( 'No Link', 'pfoa-theme' ); ?>
									</label>
								</fieldset>
								<span class="pfoa-hero-link-internal-wrap"<?php echo 'internal' === $carousel_link_type ? '' : ' style="display:none;"'; ?>>
									<label class="pfoa-hero-carousel-link">
										<?php esc_html_e( 'Image destination', 'pfoa-theme' ); ?>
										<?php pfoa_render_hero_link_selector( $carousel_field_base . '[link_page_id]', $carousel_link ); ?>
									</label>
								</span>
								<label class="pfoa-hero-link-external-wrap"<?php echo 'external' === $carousel_link_type ? '' : ' style="display:none;"'; ?>>
									<?php esc_html_e( 'External URL', 'pfoa-theme' ); ?>
									<input type="url" class="regular-text pfoa-hero-link-url-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[link_url]" value="<?php echo esc_attr( $carousel_link_url ); ?>" placeholder="https://" />
								</label>
							<label class="pfoa-hero-carousel-headline">
								<?php esc_html_e( 'Hero headline', 'pfoa-theme' ); ?>
								<input type="text" class="regular-text pfoa-hero-carousel-headline-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[headline]" value="<?php echo esc_attr( $carousel_headline ); ?>" />
							</label>
								<p class="description"><?php esc_html_e( 'Optional. Leave blank to show no headline.', 'pfoa-theme' ); ?></p>
							</section>
							<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide appearance', 'pfoa-theme' ); ?>">
								<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Appearance', 'pfoa-theme' ); ?></h4>
								<div class="pfoa-hero-slide-grid">
							<label class="pfoa-hero-carousel-brightness">
								<?php esc_html_e( 'Image brightness', 'pfoa-theme' ); ?>
								<select name="<?php echo esc_attr( $carousel_field_base ); ?>[brightness]">
									<option value="100"<?php selected( $carousel_brightness, 100 ); ?>><?php esc_html_e( 'Original (100%)', 'pfoa-theme' ); ?></option>
									<option value="110"<?php selected( $carousel_brightness, 110 ); ?>><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
									<option value="120"<?php selected( $carousel_brightness, 120 ); ?>><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
									<option value="130"<?php selected( $carousel_brightness, 130 ); ?>><?php esc_html_e( '130%', 'pfoa-theme' ); ?></option>
								</select>
							</label>
						<label class="pfoa-hero-carousel-headline-size">
							<?php esc_html_e( 'Headline size', 'pfoa-theme' ); ?>
							<select name="<?php echo esc_attr( $carousel_field_base ); ?>[headline_scale]">
								<option value="50"<?php selected( $carousel_headline_scale, 50 ); ?>><?php esc_html_e( '50%', 'pfoa-theme' ); ?></option>
								<option value="60"<?php selected( $carousel_headline_scale, 60 ); ?>><?php esc_html_e( '60%', 'pfoa-theme' ); ?></option>
								<option value="70"<?php selected( $carousel_headline_scale, 70 ); ?>><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
								<option value="80"<?php selected( $carousel_headline_scale, 80 ); ?>><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
								<option value="90"<?php selected( $carousel_headline_scale, 90 ); ?>><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
								<option value="100"<?php selected( $carousel_headline_scale, 100 ); ?>><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
								<option value="110"<?php selected( $carousel_headline_scale, 110 ); ?>><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
								<option value="120"<?php selected( $carousel_headline_scale, 120 ); ?>><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
							</select>
						</label>
						<label class="pfoa-hero-carousel-headline-color">
							<?php esc_html_e( 'Headline color', 'pfoa-theme' ); ?>
							<select name="<?php echo esc_attr( $carousel_field_base ); ?>[headline_color]">
								<option value="white"<?php selected( $carousel_headline_color, 'white' ); ?>><?php esc_html_e( 'White', 'pfoa-theme' ); ?></option>
								<option value="black"<?php selected( $carousel_headline_color, 'black' ); ?>><?php esc_html_e( 'Black', 'pfoa-theme' ); ?></option>
							</select>
						</label>
						<label class="pfoa-hero-carousel-zoom">
							<?php esc_html_e( 'Image zoom', 'pfoa-theme' ); ?>
							<select name="<?php echo esc_attr( $carousel_field_base ); ?>[zoom]">
								<option value="50"<?php selected( $carousel_zoom, 50 ); ?>><?php esc_html_e( '50%', 'pfoa-theme' ); ?></option>
								<option value="60"<?php selected( $carousel_zoom, 60 ); ?>><?php esc_html_e( '60%', 'pfoa-theme' ); ?></option>
								<option value="70"<?php selected( $carousel_zoom, 70 ); ?>><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
								<option value="80"<?php selected( $carousel_zoom, 80 ); ?>><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
								<option value="90"<?php selected( $carousel_zoom, 90 ); ?>><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
								<option value="100"<?php selected( $carousel_zoom, 100 ); ?>><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
								<option value="110"<?php selected( $carousel_zoom, 110 ); ?>><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
								<option value="120"<?php selected( $carousel_zoom, 120 ); ?>><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
								<option value="130"<?php selected( $carousel_zoom, 130 ); ?>><?php esc_html_e( '130%', 'pfoa-theme' ); ?></option>
								<option value="140"<?php selected( $carousel_zoom, 140 ); ?>><?php esc_html_e( '140%', 'pfoa-theme' ); ?></option>
								<option value="150"<?php selected( $carousel_zoom, 150 ); ?>><?php esc_html_e( '150%', 'pfoa-theme' ); ?></option>
							</select>
						</label>
						<label class="pfoa-hero-carousel-position">
							<?php esc_html_e( 'Image position', 'pfoa-theme' ); ?>
							<select name="<?php echo esc_attr( $carousel_field_base ); ?>[position]">
								<option value="top left"<?php selected( $carousel_position, 'top left' ); ?>><?php esc_html_e( 'Top left', 'pfoa-theme' ); ?></option>
								<option value="top center"<?php selected( $carousel_position, 'top center' ); ?>><?php esc_html_e( 'Top center', 'pfoa-theme' ); ?></option>
								<option value="top right"<?php selected( $carousel_position, 'top right' ); ?>><?php esc_html_e( 'Top right', 'pfoa-theme' ); ?></option>
								<option value="center left"<?php selected( $carousel_position, 'center left' ); ?>><?php esc_html_e( 'Center left', 'pfoa-theme' ); ?></option>
								<option value="center center"<?php selected( $carousel_position, 'center center' ); ?>><?php esc_html_e( 'Center (default)', 'pfoa-theme' ); ?></option>
								<option value="center right"<?php selected( $carousel_position, 'center right' ); ?>><?php esc_html_e( 'Center right', 'pfoa-theme' ); ?></option>
								<option value="bottom left"<?php selected( $carousel_position, 'bottom left' ); ?>><?php esc_html_e( 'Bottom left', 'pfoa-theme' ); ?></option>
								<option value="bottom center"<?php selected( $carousel_position, 'bottom center' ); ?>><?php esc_html_e( 'Bottom center', 'pfoa-theme' ); ?></option>
								<option value="bottom right"<?php selected( $carousel_position, 'bottom right' ); ?>><?php esc_html_e( 'Bottom right', 'pfoa-theme' ); ?></option>
							</select>
						</label>
									<p class="description"><?php esc_html_e( 'Lowering the slide count only dims the extra slots — removing a row deletes it.', 'pfoa-theme' ); ?></p>
								</div>
							</section>
							<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide actions', 'pfoa-theme' ); ?>">
								<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></h4>
								<div class="pfoa-hero-slide-actions">
							<button type="button" class="button pfoa-hero-carousel-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
							<button type="button" class="button pfoa-hero-carousel-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
							<button type="button" class="button pfoa-hero-carousel-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
							<button type="button" class="button button-primary pfoa-hero-slide-save"><?php esc_html_e( 'Save Slide', 'pfoa-theme' ); ?></button>
							<span class="pfoa-hero-slide-save-status" role="status" aria-live="polite"></span>
								</div>
								<p class="pfoa-hero-carousel-inactive-note"><?php esc_html_e( 'Inactive — will show if count increased', 'pfoa-theme' ); ?></p>
							</section>
								</div>
							</details>
						</li>
					<?php endforeach; ?>
				</ul>
				<template id="pfoa-hero-carousel-template">
					<li class="pfoa-hero-carousel-item" data-pfoa-slide-key="0">
						<details class="pfoa-hero-slide-card" open>
							<summary class="pfoa-hero-slide-summary">
								<span class="pfoa-hero-slide-badge pfoa-hero-carousel-slot"></span>
								<span class="pfoa-hero-slide-thumb"><span class="pfoa-hero-slide-empty" aria-hidden="true"></span></span>
								<span class="pfoa-hero-slide-headline"><?php esc_html_e( '— No headline —', 'pfoa-theme' ); ?></span>
								<span class="pfoa-hero-slide-link"><?php esc_html_e( '— No link —', 'pfoa-theme' ); ?></span>
								<span class="pfoa-hero-slide-status is-active"><?php esc_html_e( 'Active', 'pfoa-theme' ); ?></span>
							</summary>
							<div class="pfoa-hero-slide-body">
								<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide content', 'pfoa-theme' ); ?>">
									<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Content', 'pfoa-theme' ); ?></h4>
						<span class="pfoa-hero-carousel-preview"></span>
						<input type="hidden" class="pfoa-hero-carousel-id" name="pfoa_hero[carousel_items][__INDEX__][id]" value="0" />
						<button type="button" class="button pfoa-hero-carousel-select"><?php esc_html_e( 'Select Image', 'pfoa-theme' ); ?></button>
						<fieldset class="pfoa-hero-link-type-group">
							<legend><?php esc_html_e( 'Link destination type', 'pfoa-theme' ); ?></legend>
							<label>
								<input type="radio" class="pfoa-hero-link-type-input" name="pfoa_hero[carousel_items][__INDEX__][link_type]" value="internal" />
								<?php esc_html_e( 'Internal', 'pfoa-theme' ); ?>
							</label>
							<label>
								<input type="radio" class="pfoa-hero-link-type-input" name="pfoa_hero[carousel_items][__INDEX__][link_type]" value="external" />
								<?php esc_html_e( 'External URL', 'pfoa-theme' ); ?>
							</label>
							<label>
								<input type="radio" class="pfoa-hero-link-type-input" name="pfoa_hero[carousel_items][__INDEX__][link_type]" value="none" checked="checked" />
								<?php esc_html_e( 'No Link', 'pfoa-theme' ); ?>
							</label>
						</fieldset>
						<span class="pfoa-hero-link-internal-wrap" style="display:none;">
							<label class="pfoa-hero-carousel-link">
								<?php esc_html_e( 'Image destination', 'pfoa-theme' ); ?>
								<?php pfoa_render_hero_link_selector( 'pfoa_hero[carousel_items][__INDEX__][link_page_id]', 0 ); ?>
							</label>
						</span>
						<label class="pfoa-hero-link-external-wrap" style="display:none;">
							<?php esc_html_e( 'External URL', 'pfoa-theme' ); ?>
							<input type="url" class="regular-text pfoa-hero-link-url-input" name="pfoa_hero[carousel_items][__INDEX__][link_url]" value="" placeholder="https://" />
						</label>
					<label class="pfoa-hero-carousel-headline">
						<?php esc_html_e( 'Hero headline', 'pfoa-theme' ); ?>
						<input type="text" class="regular-text pfoa-hero-carousel-headline-input" name="pfoa_hero[carousel_items][__INDEX__][headline]" value="" />
					</label>
							<p class="description"><?php esc_html_e( 'Optional. Leave blank to show no headline.', 'pfoa-theme' ); ?></p>
						</section>
						<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide appearance', 'pfoa-theme' ); ?>">
							<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Appearance', 'pfoa-theme' ); ?></h4>
							<div class="pfoa-hero-slide-grid">
					<label class="pfoa-hero-carousel-brightness">
						<?php esc_html_e( 'Image brightness', 'pfoa-theme' ); ?>
						<select name="pfoa_hero[carousel_items][__INDEX__][brightness]">
							<option value="100" selected="selected"><?php esc_html_e( 'Original (100%)', 'pfoa-theme' ); ?></option>
							<option value="110"><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
							<option value="120"><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
							<option value="130"><?php esc_html_e( '130%', 'pfoa-theme' ); ?></option>
						</select>
					</label>
				<label class="pfoa-hero-carousel-headline-size">
					<?php esc_html_e( 'Headline size', 'pfoa-theme' ); ?>
					<select name="pfoa_hero[carousel_items][__INDEX__][headline_scale]">
						<option value="50"><?php esc_html_e( '50%', 'pfoa-theme' ); ?></option>
						<option value="60"><?php esc_html_e( '60%', 'pfoa-theme' ); ?></option>
						<option value="70"><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
						<option value="80"><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
						<option value="90"><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
						<option value="100" selected="selected"><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
						<option value="110"><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
						<option value="120"><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
					</select>
				</label>
				<label class="pfoa-hero-carousel-headline-color">
					<?php esc_html_e( 'Headline color', 'pfoa-theme' ); ?>
					<select name="pfoa_hero[carousel_items][__INDEX__][headline_color]">
						<option value="white" selected="selected"><?php esc_html_e( 'White', 'pfoa-theme' ); ?></option>
						<option value="black"><?php esc_html_e( 'Black', 'pfoa-theme' ); ?></option>
					</select>
				</label>
				<label class="pfoa-hero-carousel-zoom">
					<?php esc_html_e( 'Image zoom', 'pfoa-theme' ); ?>
					<select name="pfoa_hero[carousel_items][__INDEX__][zoom]">
						<option value="50"><?php esc_html_e( '50%', 'pfoa-theme' ); ?></option>
						<option value="60"><?php esc_html_e( '60%', 'pfoa-theme' ); ?></option>
						<option value="70"><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
						<option value="80"><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
						<option value="90"><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
						<option value="100" selected="selected"><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
						<option value="110"><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
						<option value="120"><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
						<option value="130"><?php esc_html_e( '130%', 'pfoa-theme' ); ?></option>
						<option value="140"><?php esc_html_e( '140%', 'pfoa-theme' ); ?></option>
						<option value="150"><?php esc_html_e( '150%', 'pfoa-theme' ); ?></option>
					</select>
				</label>
				<label class="pfoa-hero-carousel-position">
					<?php esc_html_e( 'Image position', 'pfoa-theme' ); ?>
					<select name="pfoa_hero[carousel_items][__INDEX__][position]">
						<option value="top left"><?php esc_html_e( 'Top left', 'pfoa-theme' ); ?></option>
						<option value="top center"><?php esc_html_e( 'Top center', 'pfoa-theme' ); ?></option>
						<option value="top right"><?php esc_html_e( 'Top right', 'pfoa-theme' ); ?></option>
						<option value="center left"><?php esc_html_e( 'Center left', 'pfoa-theme' ); ?></option>
						<option value="center center" selected="selected"><?php esc_html_e( 'Center (default)', 'pfoa-theme' ); ?></option>
						<option value="center right"><?php esc_html_e( 'Center right', 'pfoa-theme' ); ?></option>
						<option value="bottom left"><?php esc_html_e( 'Bottom left', 'pfoa-theme' ); ?></option>
						<option value="bottom center"><?php esc_html_e( 'Bottom center', 'pfoa-theme' ); ?></option>
						<option value="bottom right"><?php esc_html_e( 'Bottom right', 'pfoa-theme' ); ?></option>
					</select>
				</label>
						<p class="description"><?php esc_html_e( 'Lowering the slide count only dims the extra slots — removing a row deletes it.', 'pfoa-theme' ); ?></p>
							</div>
						</section>
						<section class="pfoa-hero-slide-section" aria-label="<?php esc_attr_e( 'Slide actions', 'pfoa-theme' ); ?>">
							<h4 class="pfoa-hero-slide-heading"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></h4>
							<div class="pfoa-hero-slide-actions">
						<button type="button" class="button pfoa-hero-carousel-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-carousel-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-carousel-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
						<button type="button" class="button button-primary pfoa-hero-slide-save"><?php esc_html_e( 'Save Slide', 'pfoa-theme' ); ?></button>
						<span class="pfoa-hero-slide-save-status" role="status" aria-live="polite"></span>
							</div>
							<p class="pfoa-hero-carousel-inactive-note"><?php esc_html_e( 'Inactive — will show if count increased', 'pfoa-theme' ); ?></p>
						</section>
							</div>
						</details>
					</li>
				</template>
				<button type="button" class="button pfoa-hero-carousel-add"><?php esc_html_e( 'Add Images', 'pfoa-theme' ); ?></button>
				<p class="description"><?php esc_html_e( 'Shown in order, up to the Number of Carousel Slides above (max 8 slots). Empty slots have their own Select Image button. Use Move Up and Move Down to reorder; the buttons stay the same on every image. Lowering the slide count only dims the extra slots — removing a row deletes it.', 'pfoa-theme' ); ?></p>
				<p class="pfoa-hero-carousel-limit-notice" style="display:none;color:#b32d2e;" role="status"><?php esc_html_e( 'Maximum 8 carousel slots. Remove a slot before adding another.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pfoa-hero-carousel-interval"><?php esc_html_e( 'Carousel timing', 'pfoa-theme' ); ?></label></th>
			<td>
				<select id="pfoa-hero-carousel-interval" name="pfoa_hero[carousel_interval_ms]">
					<option value="3500"<?php selected( $carousel_interval_ms, 3500 ); ?>><?php esc_html_e( '3.5 seconds', 'pfoa-theme' ); ?></option>
					<option value="5000"<?php selected( $carousel_interval_ms, 5000 ); ?>><?php esc_html_e( '5 seconds', 'pfoa-theme' ); ?></option>
					<option value="7000"<?php selected( $carousel_interval_ms, 7000 ); ?>><?php esc_html_e( '7 seconds', 'pfoa-theme' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Time each carousel image remains visible before advancing automatically.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
	</table>
 	<h4><?php esc_html_e( 'Shortcut buttons', 'pfoa-theme' ); ?></h4>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Destination Page', 'pfoa-theme' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></th>
			</tr>
		</thead>
		<tbody class="pfoa-hero-ctas-body" data-next-index="<?php echo esc_attr( (string) count( $ctas ) ); ?>">
			<?php foreach ( $ctas as $index => $cta ) : ?>
				<?php
				$cta_page_id    = isset( $cta['page_id'] ) ? absint( $cta['page_id'] ) : 0;
				$cta_legacy_url = isset( $cta['url'] ) ? trim( (string) $cta['url'] ) : '';

				if ( 0 === $cta_page_id && '' !== $cta_legacy_url ) {
					$cta_page_id = pfoa_migrate_url_to_page_id( $cta_legacy_url );
				}
				?>
				<tr class="pfoa-hero-cta-row">
					<td>
						<input type="text" class="regular-text" name="pfoa_hero[ctas][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( isset( $cta['label'] ) ? $cta['label'] : '' ); ?>" />
					</td>
					<td>
						<?php pfoa_render_page_selector( 'pfoa_hero[ctas][' . $index . '][page_id]', $cta_page_id ); ?>
						<?php if ( 0 === $cta_page_id && '' !== $cta_legacy_url ) : ?>
							<p class="description"><?php printf( esc_html__( 'Legacy URL: %s — select a page to migrate.', 'pfoa-theme' ), esc_html( $cta_legacy_url ) ); ?></p>
							<input type="hidden" name="pfoa_hero[ctas][<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $cta_legacy_url ); ?>" />
						<?php endif; ?>
					</td>
					<td>
						<button type="button" class="button pfoa-hero-cta-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-cta-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-cta-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p>
		<button type="button" class="button pfoa-hero-cta-add"><?php esc_html_e( 'Add Button', 'pfoa-theme' ); ?></button>
	</p>
			<p class="submit">
				<button type="submit" class="button button-primary" name="pfoa_hero_save" value="1"><?php esc_html_e( 'Save Homepage', 'pfoa-theme' ); ?></button>
			</p>
		</form>
		<?php endif; ?>
		<hr />
		<?php pfoa_homepage_cards_section(); ?>
	</div>
	<?php
}

/**
 * Enqueue the Hero Section picker assets.
 *
 * Scoped to the PFOA Site > Homepage screen only; the media library is loaded
 * there and nowhere else.
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function pfoa_hero_admin_assets( $hook ) {
	if ( 'toplevel_page_pfoa-site' !== $hook ) {
		return;
	}

	wp_enqueue_media();

	$script = get_template_directory() . '/assets/js/admin-homepage-hero.js';

	if ( file_exists( $script ) ) {
		wp_enqueue_script(
			'pfoa-hero-admin',
			get_template_directory_uri() . '/assets/js/admin-homepage-hero.js',
			array( 'jquery' ),
			(string) filemtime( $script ),
			true
		);

		wp_localize_script(
			'pfoa-hero-admin',
			'pfoaHeroAdmin',
			array(
				'moveUp'           => __( 'Move Up', 'pfoa-theme' ),
				'moveDown'         => __( 'Move Down', 'pfoa-theme' ),
				'remove'           => __( 'Remove', 'pfoa-theme' ),
				'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
				'slideNonce'       => wp_create_nonce( 'pfoa_hero_slide_save' ),
				'frontId'          => pfoa_homepage_front_id(),
				'saveSlide'        => __( 'Save Slide', 'pfoa-theme' ),
				'saving'           => __( 'Saving…', 'pfoa-theme' ),
				'saved'            => __( 'Saved.', 'pfoa-theme' ),
				'saveFailed'       => __( 'Save failed. Please try again.', 'pfoa-theme' ),
				'unsavedChanges'   => __( 'Unsaved changes', 'pfoa-theme' ),
				'newSlotHomepage'  => __( 'This is a new slide. Click Save Homepage first to add it, then edit it per-slide.', 'pfoa-theme' ),
				'conflictHomepage' => __( 'Slides changed elsewhere. Reload the page and use Save Homepage to keep every change.', 'pfoa-theme' ),
				'linkWarning'      => __( '— External URL was invalid, so its link was removed.', 'pfoa-theme' ),
				'noLinkLabel'      => __( '— No link —', 'pfoa-theme' ),
				'ctaPages'         => pfoa_get_page_selector_choices(),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'pfoa_hero_admin_assets' );

/**
 * Save the Hero Section from the PFOA Site > Homepage screen.
 *
 * Handles the admin-post submission: same nonce action/field names as before
 * ('pfoa_hero_save' / 'pfoa_hero_nonce'), same pfoa_hero[...] field names and
 * sanitizer, stored only as post meta on the assigned static front page.
 * post_content is never touched.
 *
 * @return void
 */
function pfoa_homepage_hero_save() {
	if ( ! isset( $_POST['pfoa_hero_nonce'] ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-site' ) );
		exit;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pfoa_hero_nonce'] ) ), 'pfoa_hero_save' ) ) {
		wp_die( esc_html__( 'Security check failed. Please try again.', 'pfoa-theme' ) );
	}

	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$front_id = pfoa_homepage_front_id();

	if ( ! $front_id ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-site' ) );
		exit;
	}

	if ( 'page' !== get_post_type( $front_id ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-site' ) );
		exit;
	}

	if ( ! current_user_can( 'edit_page', $front_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$raw = isset( $_POST['pfoa_hero'] ) && is_array( $_POST['pfoa_hero'] ) ? wp_unslash( $_POST['pfoa_hero'] ) : array();

	$invalid_links = pfoa_find_hero_invalid_external_links( $raw );
	$sanitized     = pfoa_sanitize_hero( $raw );

	$previous   = get_post_meta( $front_id, PFOA_HERO_META_KEY, true );
	$stored_pin = ( is_array( $previous ) && isset( $previous['featured_pin'] ) ) ? $previous['featured_pin'] : array();

	$sanitized['featured_pin'] = pfoa_resolve_hero_pin_for_save(
		isset( $sanitized['featured_pin'] ) ? $sanitized['featured_pin'] : array(),
		$stored_pin,
		isset( $sanitized['carousel_items'] ) ? $sanitized['carousel_items'] : array(),
		current_time( 'timestamp' )
	);

	update_post_meta( $front_id, PFOA_HERO_META_KEY, $sanitized );

	$redirect = add_query_arg( 'pfoa-homepage-updated', '1', admin_url( 'admin.php?page=pfoa-site' ) );

	if ( array() !== $invalid_links ) {
		$redirect = add_query_arg( 'pfoa-hero-link-warning', implode( ',', $invalid_links ), $redirect );
	}

	wp_safe_redirect( $redirect );
	exit;
}
add_action( 'admin_post_pfoa_homepage_hero_save', 'pfoa_homepage_hero_save' );

/**
 * AJAX handler: save a single existing carousel slide in place.
 *
 * Structural changes (add/remove/reorder) are rejected with an actionable
 * error directing the admin to Save Homepage; only the matched slide's
 * config fields are replaced. Everything else in the stored hero array
 * (featured_pin verbatim — no pin expiry re-resolution — mode, ctas,
 * interval, slide count) is preserved.
 *
 * Request (POST):
 *   nonce, post_id, slide_key ("0" = new slot), position (int DOM index),
 *   known_ids (array of DOM-ordered attachment ids),
 *   id, link_type, link_page_id, link_url, headline,
 *   brightness, headline_scale, headline_color, zoom, image_position.
 *
 * Error codes (responseJSON.data.code): new_slot, conflict, not_found,
 * moved, duplicate, forbidden, no_front_page, front_mismatch, save_failed.
 *
 * @return void
 */
function pfoa_hero_save_slide() {
	if ( ! check_ajax_referer( 'pfoa_hero_slide_save', 'nonce', false ) ) {
		wp_send_json_error(
			array(
				'code'    => 'forbidden',
				'message' => __( 'Security check failed. Reload the page and try again.', 'pfoa-theme' ),
			),
			403
		);
	}

	if ( ! current_user_can( 'edit_pages' ) ) {
		wp_send_json_error(
			array(
				'code'    => 'forbidden',
				'message' => __( 'You do not have permission to edit the homepage.', 'pfoa-theme' ),
			),
			403
		);
	}

	$expected_front = pfoa_homepage_front_id();

	if ( ! $expected_front ) {
		wp_send_json_error(
			array(
				'code'    => 'no_front_page',
				'message' => __( 'No static front page is assigned. Set one under Settings > Reading first.', 'pfoa-theme' ),
			),
			400
		);
	}

	$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

	if ( $post_id !== $expected_front ) {
		wp_send_json_error(
			array(
				'code'    => 'front_mismatch',
				'message' => __( 'This slide belongs to a different page. Reload and try again.', 'pfoa-theme' ),
			),
			400
		);
	}

	$front_id = $expected_front;

	if ( 'page' !== get_post_type( $front_id ) || ! current_user_can( 'edit_page', $front_id ) ) {
		wp_send_json_error(
			array(
				'code'    => 'forbidden',
				'message' => __( 'You do not have permission to edit the homepage.', 'pfoa-theme' ),
			),
			403
		);
	}

	$raw_post = wp_unslash( $_POST );

	$slide_key_raw = isset( $raw_post['slide_key'] ) ? trim( (string) $raw_post['slide_key'] ) : '';
	$slide_key     = absint( $slide_key_raw );
	$raw_id        = isset( $raw_post['id'] ) ? absint( $raw_post['id'] ) : 0;

	if ( '' === $slide_key_raw || '0' === $slide_key_raw || 0 === $slide_key || 0 === $raw_id ) {
		wp_send_json_error(
			array(
				'code'    => 'new_slot',
				'message' => __( 'This is a new slide. Click Save Homepage first to add it, then edit it per-slide.', 'pfoa-theme' ),
			),
			400
		);
	}

	$position = isset( $raw_post['position'] ) ? absint( $raw_post['position'] ) : 0;

	$known_ids = array();

	if ( isset( $raw_post['known_ids'] ) && is_array( $raw_post['known_ids'] ) ) {
		foreach ( $raw_post['known_ids'] as $known_id ) {
			$known_ids[] = absint( $known_id );
		}
	}

	$hero = pfoa_get_hero( $front_id );

	$stored_items = ( isset( $hero['carousel_items'] ) && is_array( $hero['carousel_items'] ) ) ? array_values( $hero['carousel_items'] ) : array();

	$stored_ids = array();

	foreach ( $stored_items as $stored_item ) {
		$stored_ids[] = isset( $stored_item['id'] ) ? absint( $stored_item['id'] ) : 0;
	}

	$found = -1;

	foreach ( $stored_ids as $index => $stored_id ) {
		if ( $stored_id === $slide_key ) {
			$found = $index;
			break;
		}
	}

	if ( -1 === $found ) {
		wp_send_json_error(
			array(
				'code'    => 'not_found',
				'message' => __( 'This slide is no longer saved. Reload the page and use Save Homepage.', 'pfoa-theme' ),
			),
			404
		);
	}

	if ( $found !== (int) $position ) {
		wp_send_json_error(
			array(
				'code'    => 'moved',
				'message' => __( 'This slide was reordered without saving. Use Save Homepage to save the new order first.', 'pfoa-theme' ),
			),
			409
		);
	}

	// Concurrency guard: the DOM order must match stored order, except the
	// edited card itself may carry a replacement image id, and empty (id=0)
	// slots never persist so they are ignored on both sides. Any other
	// drift (reorder/add/remove elsewhere) is a structural change.
	$expected_ids = $stored_ids;
	$expected_ids[ $found ] = $raw_id;

	$known_nonzero = array_values(
		array_filter(
			$known_ids,
			function ( $id ) {
				return 0 !== $id;
			}
		)
	);

	$expected_nonzero = array_values(
		array_filter(
			$expected_ids,
			function ( $id ) {
				return 0 !== $id;
			}
		)
	);

	if ( $known_nonzero !== $expected_nonzero ) {
		wp_send_json_error(
			array(
				'code'    => 'conflict',
				'message' => __( 'Slides changed elsewhere (reordered, added, or removed). Reload the page and use Save Homepage to keep every change.', 'pfoa-theme' ),
			),
			409
		);
	}

	// Same per-field rules as pfoa_sanitize_hero() carousel items.
	$new_id = absint( isset( $raw_post['id'] ) ? $raw_post['id'] : 0 );
	$new_id = ( $new_id && wp_attachment_is_image( $new_id ) ) ? $new_id : 0;

	if ( ! $new_id ) {
		wp_send_json_error(
			array(
				'code'    => 'new_slot',
				'message' => __( 'Removing a slide image is a structural change. Click Save Homepage first.', 'pfoa-theme' ),
			),
			400
		);
	}

	foreach ( $stored_ids as $index => $stored_id ) {
		if ( $index !== $found && $stored_id === $new_id ) {
			wp_send_json_error(
				array(
					'code'    => 'duplicate',
					'message' => __( 'This image is already used by another slide. Choose a different image.', 'pfoa-theme' ),
				),
				400
			);
		}
	}

	$item_link_page_id = isset( $raw_post['link_page_id'] ) ? pfoa_sanitize_hero_link_page_id( $raw_post['link_page_id'] ) : 0;

	$new_slide = array(
		'id'             => $new_id,
		'link_page_id'   => $item_link_page_id,
		'link_type'      => pfoa_resolve_hero_link_type(
			array(
				'link_type'    => isset( $raw_post['link_type'] ) ? $raw_post['link_type'] : null,
				'link_page_id' => $item_link_page_id,
			)
		),
		'link_url'       => isset( $raw_post['link_url'] ) ? pfoa_sanitize_hero_external_url( $raw_post['link_url'] ) : '',
		'headline'       => isset( $raw_post['headline'] ) ? sanitize_text_field( $raw_post['headline'] ) : '',
		'brightness'     => isset( $raw_post['brightness'] ) ? pfoa_sanitize_hero_brightness( $raw_post['brightness'] ) : 100,
		'headline_scale' => isset( $raw_post['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $raw_post['headline_scale'] ) : 100,
		'headline_color' => isset( $raw_post['headline_color'] ) ? pfoa_sanitize_hero_headline_color( $raw_post['headline_color'] ) : 'white',
		'zoom'           => isset( $raw_post['zoom'] ) ? pfoa_sanitize_hero_zoom( $raw_post['zoom'] ) : 100,
		'position'       => isset( $raw_post['image_position'] ) ? pfoa_sanitize_hero_position( $raw_post['image_position'] ) : 'center center',
	);

	// Mirror the Save Homepage warning pattern: an invalid external URL is
	// saved with the link removed; the client surfaces the notice.
	$link_warning      = false;
	$submitted_url_raw = isset( $raw_post['link_url'] ) ? trim( (string) $raw_post['link_url'] ) : '';

	if ( 'external' === $new_slide['link_type'] && '' !== $submitted_url_raw && '' === $new_slide['link_url'] ) {
		$link_warning = true;
	}

	$hero['carousel_items'][ $found ] = $new_slide;
	$hero['carousel_items']            = array_values( $hero['carousel_items'] );
	$hero['carousel_ids']              = array();

	foreach ( $hero['carousel_items'] as $carousel_item ) {
		$hero['carousel_ids'][] = isset( $carousel_item['id'] ) ? absint( $carousel_item['id'] ) : 0;
	}

	$updated = update_post_meta( $front_id, PFOA_HERO_META_KEY, $hero );

	if ( ! $updated ) {
		$recheck = get_post_meta( $front_id, PFOA_HERO_META_KEY, true );
		$matches = is_array( $recheck )
			&& isset( $recheck['carousel_items'] )
			&& is_array( $recheck['carousel_items'] )
			&& isset( $recheck['carousel_items'][ $found ] )
			&& is_array( $recheck['carousel_items'][ $found ] )
			&& absint( $recheck['carousel_items'][ $found ]['id'] ) === $new_id
			&& $recheck['carousel_items'][ $found ] == $new_slide; // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- sanitized array equality tolerates int/string id juggling.

		if ( ! $matches ) {
			wp_send_json_error(
				array(
					'code'    => 'save_failed',
					'message' => __( 'Save failed. Please try again.', 'pfoa-theme' ),
				),
				500
			);
		}
	}

	// Summary mirrors the PHP card render (headline fallback + truncated link label).
	$summary_headline = '' !== trim( (string) $new_slide['headline'] ) ? $new_slide['headline'] : __( '— No headline —', 'pfoa-theme' );
	$summary_link     = __( '— No link —', 'pfoa-theme' );
	$link_found       = false;
	$stored_link      = $new_slide['link_page_id'];

	if ( is_string( $stored_link ) && '' !== $stored_link && '0' !== $stored_link ) {
		foreach ( pfoa_get_hero_bonded_pairs() as $pair ) {
			if ( isset( $pair['token'], $pair['title'] ) && $stored_link === (string) $pair['token'] ) {
				/* translators: %s: bonded pair title. */
				$summary_link = sprintf( __( '%s — Bonded Pair', 'pfoa-theme' ), $pair['title'] );
				$link_found   = true;
				break;
			}
		}
	} elseif ( absint( $stored_link ) ) {
		foreach ( pfoa_get_hero_link_cat_profiles() as $cat ) {
			if ( $cat instanceof WP_Post && absint( $stored_link ) === (int) $cat->ID ) {
				/* translators: 1: cat profile title, 2: post ID. */
				$summary_link = sprintf( __( '%s (#%d)', 'pfoa-theme' ), $cat->post_title, $cat->ID );
				$link_found   = true;
				break;
			}
		}

		if ( ! $link_found ) {
			$linked_title = get_the_title( absint( $stored_link ) );

			if ( '' !== trim( (string) $linked_title ) ) {
				$summary_link = $linked_title;
			}
		}
	}

	if ( 'external' === $new_slide['link_type'] && '' !== $new_slide['link_url'] ) {
		$summary_link = strlen( $new_slide['link_url'] ) > 48 ? substr( $new_slide['link_url'], 0, 47 ) . '…' : $new_slide['link_url'];
	}

	wp_send_json_success(
		array(
			'slide'        => $new_slide,
			'position'     => $found,
			'summary'      => array(
				'headline' => $summary_headline,
				'link'     => $summary_link,
			),
			'link_warning' => $link_warning,
		)
	);
}
add_action( 'wp_ajax_pfoa_hero_save_slide', 'pfoa_hero_save_slide' );

/**
 * Render a minimal recovery menu when no Primary Menu has been assigned.
 *
 * This is only a no-menu fallback; normal navigation always comes from the
 * WordPress-managed Primary Menu location.
 *
 * @param stdClass $args wp_nav_menu() arguments.
 * @return void
 */
function pfoa_primary_menu_fallback( $args ) {
	$menu_id    = ! empty( $args->menu_id ) ? $args->menu_id : 'primary-menu';
	$menu_class = ! empty( $args->menu_class ) ? $args->menu_class : 'primary-menu';
	?>
	<ul id="<?php echo esc_attr( $menu_id ); ?>" class="<?php echo esc_attr( $menu_class ); ?>">
		<li class="menu-item menu-item-home">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'pfoa-theme' ); ?></a>
		</li>
	</ul>
	<?php
}

/**
 * Add disclosure controls beside menu items that have children.
 *
 * The walker keeps the parent anchor emitted by WordPress intact and adds a
 * separate button, so a real parent Page remains navigable at every depth.
 */
class PFOA_Navigation_Walker extends Walker_Nav_Menu {
	/**
	 * IDs for submenus opened by each current menu depth.
	 *
	 * @var string[]
	 */
	protected $submenu_ids = array();

	/**
	 * Whether each menu item has descendants in the current walk.
	 *
	 * @var bool[]
	 */
	protected $item_has_children = array();

	/**
	 * Record child ownership before the parent walker renders the item.
	 *
	 * Some WordPress runtimes do not expose the computed has_children value on
	 * the start_el() arguments. The walker already receives the authoritative
	 * children map, so use it to keep disclosure controls in the rendered menu.
	 *
	 * @param WP_Post     $element           Current menu item.
	 * @param WP_Post[][] $children_elements Remaining child items by walker ID.
	 * @param int         $max_depth         Maximum walk depth.
	 * @param int         $depth             Current menu depth.
	 * @param array       $args              Menu arguments passed by the walker.
	 * @param string      $output            Used to append the markup.
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		$item_id = $this->get_item_id( $element );

		if ( $item_id ) {
			$can_render_children = 0 === (int) $max_depth || (int) $max_depth > ( (int) $depth + 1 );
			$this->item_has_children[ $item_id ] = $can_render_children && ! empty( $children_elements[ $item_id ] );
		}

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Return the identifier WordPress uses to index this walker's child map.
	 *
	 * @param WP_Post $item Menu item.
	 * @return int
	 */
	protected function get_item_id( $item ) {
		$id_field = isset( $this->db_fields['id'] ) ? $this->db_fields['id'] : 'ID';

		return is_object( $item ) && isset( $item->{$id_field} ) ? absint( $item->{$id_field} ) : 0;
	}

	/**
	 * Start a submenu level with an ID controlled by its disclosure button.
	 *
	 * @param string   $output Used to append the markup.
	 * @param int      $depth  Current menu depth.
	 * @param stdClass $args   Menu arguments.
	 * @return void
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$indent     = str_repeat( "\t", $depth );
		$classes    = array( 'sub-menu' );
		$class_names = implode( ' ', apply_filters( 'nav_menu_submenu_css_class', $classes, $args, $depth ) );
		$submenu_id = isset( $this->submenu_ids[ $depth ] ) ? $this->submenu_ids[ $depth ] : 'pfoa-submenu-' . wp_unique_id();

		$output .= "\n{$indent}<ul id=\"" . esc_attr( $submenu_id ) . '" class="' . esc_attr( $class_names ) . "\">\n";
	}

	/**
	 * Append a disclosure button after a parent link.
	 *
	 * @param string   $output Used to append the markup.
	 * @param WP_Post  $item   Current menu item.
	 * @param int      $depth  Current menu depth.
	 * @param stdClass $args   Menu arguments.
	 * @param int      $id     Current menu item ID.
	 * @return void
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		parent::start_el( $output, $item, $depth, $args, $id );

		$item_id = $this->get_item_id( $item );

		if ( empty( $this->item_has_children[ $item_id ] ) ) {
			return;
		}

		$submenu_id = 'pfoa-submenu-' . $item_id . '-' . absint( $depth );
		$label      = sprintf(
			/* translators: %s: menu item title. */
			__( 'Open submenu for %s', 'pfoa-theme' ),
			wp_strip_all_tags( $item->title )
		);
		$close_label = sprintf(
			/* translators: %s: menu item title. */
			__( 'Close submenu for %s', 'pfoa-theme' ),
			wp_strip_all_tags( $item->title )
		);

		$this->submenu_ids[ $depth ] = $submenu_id;
		$output .= '<button class="submenu-toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr( $submenu_id ) . '" data-open-label="' . esc_attr( $label ) . '" data-close-label="' . esc_attr( $close_label ) . '">';
		$output .= '<span class="screen-reader-text">' . esc_html( $label ) . '</span>';
		$output .= '<span class="submenu-toggle__icon" aria-hidden="true"></span>';
		$output .= '</button>';
	}
}

/**
 * IMPLEMENTATION A: present bonded cat pairs as a single card (presentation only).
 *
 * Frontend-only transform for the main page content of page 20323 / slug
 * adoptablecats2. Runs late on the_content so existing Cat Profiles
 * content/lifecycle filters have already run. Filter output only; never
 * modifies post_content, queries Cat Profiles metadata, duplicates business
 * logic, or changes JS.
 *
 * Runtime bonded markup (from Cat Profiles plugin) is adjacent siblings:
 * article.pfoa-cat-card.pfoa-cat-pair-left immediately followed by
 * article.pfoa-cat-card.pfoa-cat-pair-right with the same non-empty
 * data-pfoa-cat-dialog slug.
 *
 * @param string $content Filtered post content.
 * @return string Unchanged content unless a valid pair is detected.
 */
function pfoa_present_bonded_pairs( $content ) {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	$has_legacy_marker     = false !== strpos( $content, 'pfoa-cat-pair-left' );
	$has_structured_marker = false !== strpos( $content, 'data-pfoa-profile-id' );

	if ( ! $has_legacy_marker && ! $has_structured_marker ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 20323 ) || is_page( 'adoptablecats2' ) ) ) {
		$is_target_page = true;
	} else {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			if ( 20323 === (int) $post->ID ) {
				$is_target_page = true;
			} elseif ( isset( $post->post_name ) && 'adoptablecats2' === $post->post_name ) {
				$is_target_page = true;
			}
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		return $content;
	}

	$prev_libxml = libxml_use_internal_errors( true );

	$dom = new DOMDocument( '1.0', 'UTF-8' );

	if ( function_exists( 'mb_encode_numericentity' ) ) {
		$fragment = mb_encode_numericentity( $content, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' );
	} else {
		$fragment = $content;
	}

	$loaded = $dom->loadHTML(
		'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="pfoa-bonded-wrapper">' . $fragment . '</div></body></html>'
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$xpath = new DOMXPath( $dom );

	$wrapper_list = $xpath->query( '//div[@id="pfoa-bonded-wrapper"]' );

	if ( ! $wrapper_list instanceof DOMNodeList || 0 === $wrapper_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$wrapper = $wrapper_list->item( 0 );

	$left_list = $xpath->query(
		'.//article[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-pair-left ")]',
		$wrapper
	);

	// Snapshot NodeList into an array to avoid live-list mutation issues.
	// An empty legacy set is not an early return: structured bonded-pair
	// grouping below may still apply.
	$left_nodes = array();

	if ( $left_list instanceof DOMNodeList && 0 < $left_list->length ) {
		foreach ( $left_list as $left_node ) {
			if ( $left_node instanceof DOMElement ) {
				$left_nodes[] = $left_node;
			}
		}
	}

	$changed = false;

	foreach ( $left_nodes as $left ) {
		// Skip nodes already detached by an earlier replacement.
		if ( null === $left->parentNode ) {
			continue;
		}

		if ( 'article' !== strtolower( $left->nodeName ) ) {
			continue;
		}

		// Dialog slug lives on descendant links, not on the pair article itself.
		$left_dialog = '';

		$left_media_dialog_list = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")][@data-pfoa-cat-dialog]', $left );

		if ( $left_media_dialog_list instanceof DOMNodeList && 0 < $left_media_dialog_list->length ) {
			$left_media_dialog_node = $left_media_dialog_list->item( 0 );

			if ( $left_media_dialog_node instanceof DOMElement ) {
				$left_dialog = trim( (string) $left_media_dialog_node->getAttribute( 'data-pfoa-cat-dialog' ) );
			}
		}

		if ( '' === $left_dialog ) {
			$left_title_dialog_list = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]//a[@data-pfoa-cat-dialog]', $left );

			if ( $left_title_dialog_list instanceof DOMNodeList && 0 < $left_title_dialog_list->length ) {
				$left_title_dialog_node = $left_title_dialog_list->item( 0 );

				if ( $left_title_dialog_node instanceof DOMElement ) {
					$left_dialog = trim( (string) $left_title_dialog_node->getAttribute( 'data-pfoa-cat-dialog' ) );
				}
			}
		}

		if ( '' === $left_dialog ) {
			continue;
		}

		// Adjacent siblings only: next *element* sibling must be the right half.
		$right = $left->nextSibling;

		while ( $right && XML_ELEMENT_NODE !== $right->nodeType ) {
			$right = $right->nextSibling;
		}

		if ( ! $right instanceof DOMElement ) {
			continue;
		}

		if ( 'article' !== strtolower( $right->nodeName ) ) {
			continue;
		}

		$right_class = ' ' . preg_replace( '/\s+/', ' ', (string) $right->getAttribute( 'class' ) ) . ' ';

		if ( false === strpos( $right_class, ' pfoa-cat-pair-right ' ) ) {
			continue;
		}

		$right_dialog = '';

		$right_media_dialog_list = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")][@data-pfoa-cat-dialog]', $right );

		if ( $right_media_dialog_list instanceof DOMNodeList && 0 < $right_media_dialog_list->length ) {
			$right_media_dialog_node = $right_media_dialog_list->item( 0 );

			if ( $right_media_dialog_node instanceof DOMElement ) {
				$right_dialog = trim( (string) $right_media_dialog_node->getAttribute( 'data-pfoa-cat-dialog' ) );
			}
		}

		if ( '' === $right_dialog ) {
			$right_title_dialog_list = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]//a[@data-pfoa-cat-dialog]', $right );

			if ( $right_title_dialog_list instanceof DOMNodeList && 0 < $right_title_dialog_list->length ) {
				$right_title_dialog_node = $right_title_dialog_list->item( 0 );

				if ( $right_title_dialog_node instanceof DOMElement ) {
					$right_dialog = trim( (string) $right_title_dialog_node->getAttribute( 'data-pfoa-cat-dialog' ) );
				}
			}
		}

		if ( '' === $right_dialog || $right_dialog !== $left_dialog ) {
			continue;
		}

		$left_media_list  = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")]', $left );
		$right_media_list = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")]', $right );
		$left_title_list  = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]', $left );
		$right_title_list = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]', $right );

		if ( ! $left_media_list instanceof DOMNodeList || 0 === $left_media_list->length ) {
			continue;
		}

		if ( ! $right_media_list instanceof DOMNodeList || 0 === $right_media_list->length ) {
			continue;
		}

		if ( ! $left_title_list instanceof DOMNodeList || 0 === $left_title_list->length ) {
			continue;
		}

		if ( ! $right_title_list instanceof DOMNodeList || 0 === $right_title_list->length ) {
			continue;
		}

		$left_media  = $left_media_list->item( 0 );
		$right_media = $right_media_list->item( 0 );
		$left_title  = $left_title_list->item( 0 );
		$right_title = $right_title_list->item( 0 );

		if ( ! $left_media instanceof DOMElement || ! $right_media instanceof DOMElement ) {
			continue;
		}

		if ( ! $left_title instanceof DOMElement || ! $right_title instanceof DOMElement ) {
			continue;
		}

		$left_img_list  = $xpath->query( './/img', $left_media );
		$right_img_list = $xpath->query( './/img', $right_media );

		if ( ! $left_img_list instanceof DOMNodeList || 0 === $left_img_list->length ) {
			continue;
		}

		if ( ! $right_img_list instanceof DOMNodeList || 0 === $right_img_list->length ) {
			continue;
		}

		// Visible rendered text only.
		$raw_name         = trim( (string) $left_title->textContent );
		$relationship     = trim( (string) $right_title->textContent );
		$shared_name      = preg_replace( '/[\s\x{00A0}]*[-\x{2013}\x{2014}][\s\x{00A0}]*$/u', '', $raw_name );
		$shared_name      = trim( (string) $shared_name );

		if ( '' === $shared_name || '' === $relationship ) {
			continue;
		}

		// Shared profile link prefers left-half values (halves should match).
		$shared_href = trim( (string) $left_media->getAttribute( 'href' ) );

		if ( '' === $shared_href ) {
			$shared_href = trim( (string) $right_media->getAttribute( 'href' ) );
		}

		if ( '' === $shared_href ) {
			continue;
		}

		$shared_profile_id = trim( (string) $left_media->getAttribute( 'data-pfoa-profile-id' ) );

		if ( '' === $shared_profile_id ) {
			$shared_profile_id = trim( (string) $right_media->getAttribute( 'data-pfoa-profile-id' ) );
		}

		// Stable fragment anchor for hero bonded-pair deep links. Both halves
		// carry their member's profile ID on the media link; the anchor uses
		// the normalized ascending pair so it matches the hero resolution.
		$bonded_anchor = '';

		$left_profile_id  = trim( (string) $left_media->getAttribute( 'data-pfoa-profile-id' ) );
		$right_profile_id = trim( (string) $right_media->getAttribute( 'data-pfoa-profile-id' ) );

		if ( '' !== $left_profile_id && '' !== $right_profile_id && $left_profile_id !== $right_profile_id && ctype_digit( $left_profile_id ) && ctype_digit( $right_profile_id ) ) {
			$bonded_anchor = pfoa_get_hero_bonded_pair_anchor( (int) $left_profile_id, (int) $right_profile_id );
		}

		// Build replacement via DOM methods so text/attributes stay escaped.
		$bonded = $dom->createElement( 'article' );
		$bonded->setAttribute( 'class', 'pfoa-cat-card pfoa-cat-bonded-card pfoa-cat-bonded-card--bonded' );
		$bonded->setAttribute( 'data-pfoa-cat-dialog', $left_dialog );

		if ( '' !== $shared_profile_id ) {
			$bonded->setAttribute( 'data-pfoa-profile-id', $shared_profile_id );
		}

		if ( '' !== $bonded_anchor ) {
			// tabindex -1 keeps the fragment target out of the tab order while
			// letting keyboard and assistive-tech users land on the card.
			$bonded->setAttribute( 'id', $bonded_anchor );
			$bonded->setAttribute( 'tabindex', '-1' );
		}

		$photos = $dom->createElement( 'div' );
		$photos->setAttribute( 'class', 'pfoa-cat-bonded-photos' );
		$bonded->appendChild( $photos );

		// Preserve both member media links (href + dialog + profile-id) and
		// images (src/srcset/sizes/alt) in original member order.
		$media_nodes = array( $left_media, $right_media );

		foreach ( $media_nodes as $media_node ) {
			$media_clone = $media_node->cloneNode( true );

			$existing_media_class = trim( (string) $media_clone->getAttribute( 'class' ) );

			if ( false === strpos( ' ' . $existing_media_class . ' ', ' pfoa-cat-bonded-photo ' ) ) {
				$media_clone->setAttribute( 'class', trim( $existing_media_class . ' pfoa-cat-bonded-photo' ) );
			}

			// Import into the current document context (same $dom, but keeps
			// ownership explicit if parsing context ever changes).
			$photos->appendChild( $media_clone );
		}

		$footer = $dom->createElement( 'div' );
		$footer->setAttribute( 'class', 'pfoa-cat-bonded-footer' );
		$bonded->appendChild( $footer );

		$name_heading = $dom->createElement( 'h3' );
		$name_heading->setAttribute( 'class', 'pfoa-cat-bonded-title' );
		$footer->appendChild( $name_heading );

		$name_link = $dom->createElement( 'a' );
		$name_link->setAttribute( 'href', $shared_href );
		$name_link->setAttribute( 'data-pfoa-cat-dialog', $left_dialog );

		if ( '' !== $shared_profile_id ) {
			$name_link->setAttribute( 'data-pfoa-profile-id', $shared_profile_id );
		}

		$name_link->textContent = $shared_name;
		$name_heading->appendChild( $name_link );

		// PFOA 0.1.83 — presentation only: shared "Bonded Pair" badge inside
		// the existing title (pair name stays primary). Absolutely positioned
		// by CSS so title/card geometry is unchanged; the visually-hidden
		// suffix gives assistive-tech context. Replaces the old plain
		// p.pfoa-cat-bonded-relationship line so there is one presentation only.
		$name_heading->appendChild( $dom->createTextNode( ' ' ) );
		$bonded_badge = $dom->createElement( 'span' );
		$bonded_badge->setAttribute( 'class', 'pfoa-cat-bonded-badge' );
		$bonded_badge->appendChild( $dom->createTextNode( esc_html__( 'Bonded Pair', 'pfoa-theme' ) ) );
		$bonded_sr = $dom->createElement( 'span' );
		$bonded_sr->setAttribute( 'class', 'screen-reader-text' );
		$bonded_sr->appendChild( $dom->createTextNode( ' ' . esc_html__( 'available together as a bonded pair', 'pfoa-theme' ) ) );
		$bonded_badge->appendChild( $bonded_sr );
		$name_heading->appendChild( $bonded_badge );

		$parent = $left->parentNode;

		if ( ! $parent ) {
			continue;
		}

		// Both halves must share the same parent (true adjacent siblings).
		if ( $right->parentNode !== $parent ) {
			continue;
		}

		$parent->insertBefore( $bonded, $left );
		$parent->removeChild( $left );
		$parent->removeChild( $right );

		$changed = true;
	}

	// Structured bonded-pair grouping: combine plain individual cards whose
	// profiles form a canonical bonded pair per the Cat Profiles API. The
	// legacy halves above are untouched; this only touches plain individual
	// cards. Never fatals and never fails the whole page: any unusable pair
	// leaves its individuals unchanged.
	$structured_consumed = array();

	if ( function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
		$structured_list = $xpath->query(
			'.//article[descendant::a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")][@data-pfoa-profile-id] or @data-pfoa-profile-id]',
			$wrapper
		);

		$structured_candidates = array();

		if ( $structured_list instanceof DOMNodeList && 0 < $structured_list->length ) {
			foreach ( $structured_list as $structured_node ) {
				if ( ! $structured_node instanceof DOMElement ) {
					continue;
				}

				if ( 'article' !== strtolower( $structured_node->nodeName ) ) {
					continue;
				}

				$structured_class = ' ' . preg_replace( '/\s+/', ' ', (string) $structured_node->getAttribute( 'class' ) ) . ' ';

				if ( false !== strpos( $structured_class, ' pfoa-cat-pair-left ' ) ) {
					continue;
				}

				if ( false !== strpos( $structured_class, ' pfoa-cat-pair-right ' ) ) {
					continue;
				}

				if ( false !== strpos( $structured_class, ' pfoa-cat-bonded-card ' ) ) {
					continue;
				}

				$structured_candidates[] = $structured_node;
			}
		}

		// Map profile ID -> card article element by scanning candidates. The
		// ID lives on the descendant media anchor primarily, falling back to
		// the article itself.
		$profile_to_card = array();

		foreach ( $structured_candidates as $candidate ) {
			if ( null === $candidate->parentNode ) {
				continue;
			}

			$candidate_profile_id = '';

			$candidate_media_list = $xpath->query(
				'.//a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")][@data-pfoa-profile-id]',
				$candidate
			);

			if ( $candidate_media_list instanceof DOMNodeList && 0 < $candidate_media_list->length ) {
				$candidate_media_node = $candidate_media_list->item( 0 );

				if ( $candidate_media_node instanceof DOMElement ) {
					$candidate_profile_id = trim( (string) $candidate_media_node->getAttribute( 'data-pfoa-profile-id' ) );
				}
			}

			if ( '' === $candidate_profile_id ) {
				$candidate_profile_id = trim( (string) $candidate->getAttribute( 'data-pfoa-profile-id' ) );
			}

			if ( '' === $candidate_profile_id || ! ctype_digit( $candidate_profile_id ) ) {
				continue;
			}

			$candidate_id = (int) $candidate_profile_id;

			if ( 0 >= $candidate_id ) {
				continue;
			}

			if ( ! isset( $profile_to_card[ $candidate_id ] ) ) {
				$profile_to_card[ $candidate_id ] = $candidate;
			}
		}

		$structured_ids = array_keys( $profile_to_card );

		// PFOA 0.1.169 — stored document order index (static page markup
		// order). $profile_to_card is built by scanning candidates in XPath
		// document order, so its keys already run document-first; the flip
		// gives each post ID its document position for pair ordering below.
		$structured_position = array();

		foreach ( $structured_ids as $structured_position_index => $structured_position_id ) {
			$structured_position[ (int) $structured_position_id ] = $structured_position_index;
		}

		foreach ( $structured_ids as $structured_id ) {
			$structured_id = (int) $structured_id;

			if ( isset( $structured_consumed[ $structured_id ] ) ) {
				continue;
			}

			if ( ! isset( $profile_to_card[ $structured_id ] ) ) {
				continue;
			}

			$pair_raw = pfoa_cat_get_bonded_pair( $structured_id );

			if ( ! is_array( $pair_raw ) ) {
				continue;
			}

			// Normalize defensively: entries may be ints/numeric strings,
			// WP_Post objects, or arrays/objects with ID keys.
			$pair_ids = array();

			foreach ( $pair_raw as $pair_entry ) {
				$pair_entry_id = 0;

				if ( is_numeric( $pair_entry ) ) {
					$pair_entry_id = (int) $pair_entry;
				} elseif ( $pair_entry instanceof WP_Post ) {
					$pair_entry_id = (int) $pair_entry->ID;
				} elseif ( is_array( $pair_entry ) ) {
					foreach ( array( 'ID', 'id', 'post_id', 'postId', 'postID' ) as $pair_key ) {
						if ( isset( $pair_entry[ $pair_key ] ) && is_numeric( $pair_entry[ $pair_key ] ) ) {
							$pair_entry_id = (int) $pair_entry[ $pair_key ];
							break;
						}
					}
				} elseif ( is_object( $pair_entry ) ) {
					foreach ( array( 'ID', 'id', 'post_id', 'postId', 'postID' ) as $pair_key ) {
						if ( isset( $pair_entry->{$pair_key} ) && is_numeric( $pair_entry->{$pair_key} ) ) {
							$pair_entry_id = (int) $pair_entry->{$pair_key};
							break;
						}
					}
				}

				if ( 0 < $pair_entry_id ) {
					$pair_ids[] = $pair_entry_id;
				}
			}

			$pair_ids = array_values( array_unique( $pair_ids ) );

			if ( 2 !== count( $pair_ids ) ) {
				continue;
			}

			if ( ! in_array( $structured_id, $pair_ids, true ) ) {
				continue;
			}

			// PFOA 0.1.169 — pair identity comes from the API, but presentation
			// order follows the stored document order, NOT the API canonical
			// CatID-first order. The card first in the stored static markup
			// provides the photo sequence, title text order, and title/article
			// attrs. Stored order matches Production chronology (Mary before
			// Paul, Athena before Hunter, Oliver before Isaac); canonical
			// CatID-first order would render Paul & Mary and Isaac & Oliver.
			$canon_a = $pair_ids[0];
			$canon_b = $pair_ids[1];

			$pos_a = isset( $structured_position[ $canon_a ] ) ? $structured_position[ $canon_a ] : PHP_INT_MAX;
			$pos_b = isset( $structured_position[ $canon_b ] ) ? $structured_position[ $canon_b ] : PHP_INT_MAX;

			if ( $pos_b < $pos_a ) {
				$first_id  = $canon_b;
				$second_id = $canon_a;
			} else {
				$first_id  = $canon_a;
				$second_id = $canon_b;
			}

			if ( isset( $structured_consumed[ $first_id ] ) || isset( $structured_consumed[ $second_id ] ) ) {
				continue;
			}

			if ( ! isset( $profile_to_card[ $first_id ] ) || ! isset( $profile_to_card[ $second_id ] ) ) {
				continue;
			}

			$first_card  = $profile_to_card[ $first_id ];
			$second_card = $profile_to_card[ $second_id ];

			if ( null === $first_card->parentNode || null === $second_card->parentNode ) {
				continue;
			}

			if ( $first_card->parentNode !== $second_card->parentNode ) {
				continue;
			}

			$first_media_list   = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")]', $first_card );
			$second_media_list  = $xpath->query( './/a[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-media ")]', $second_card );
			$first_title_list   = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]', $first_card );
			$second_title_list  = $xpath->query( './/h3[contains(concat(" ", normalize-space(@class), " "), " pfoa-cat-card-title ")]', $second_card );

			if ( ! $first_media_list instanceof DOMNodeList || 0 === $first_media_list->length ) {
				continue;
			}

			if ( ! $second_media_list instanceof DOMNodeList || 0 === $second_media_list->length ) {
				continue;
			}

			if ( ! $first_title_list instanceof DOMNodeList || 0 === $first_title_list->length ) {
				continue;
			}

			if ( ! $second_title_list instanceof DOMNodeList || 0 === $second_title_list->length ) {
				continue;
			}

			$first_media  = $first_media_list->item( 0 );
			$second_media = $second_media_list->item( 0 );
			$first_title  = $first_title_list->item( 0 );
			$second_title = $second_title_list->item( 0 );

			if ( ! $first_media instanceof DOMElement || ! $second_media instanceof DOMElement ) {
				continue;
			}

			if ( ! $first_title instanceof DOMElement || ! $second_title instanceof DOMElement ) {
				continue;
			}

			$first_img_list  = $xpath->query( './/img', $first_media );
			$second_img_list = $xpath->query( './/img', $second_media );

			if ( ! $first_img_list instanceof DOMNodeList || 0 === $first_img_list->length ) {
				continue;
			}

			if ( ! $second_img_list instanceof DOMNodeList || 0 === $second_img_list->length ) {
				continue;
			}

			// Full member titles (no trailing-dash stripping for structured cards).
			$first_name  = trim( (string) $first_title->textContent );
			$second_name = trim( (string) $second_title->textContent );

			if ( '' === $first_name || '' === $second_name ) {
				continue;
			}

			// PFOA 0.1.169 — document-first member provides the title link +
			// article attrs (href/dialog/profile-id from its own card).
			$first_href         = trim( (string) $first_media->getAttribute( 'href' ) );
			$first_dialog       = trim( (string) $first_media->getAttribute( 'data-pfoa-cat-dialog' ) );
			$first_profile_attr = trim( (string) $first_media->getAttribute( 'data-pfoa-profile-id' ) );

			if ( '' === $first_href || '' === $first_dialog || '' === $first_profile_attr ) {
				$first_title_link_list = $xpath->query( './/a[@href]', $first_title );

				if ( $first_title_link_list instanceof DOMNodeList && 0 < $first_title_link_list->length ) {
					$first_title_link = $first_title_link_list->item( 0 );

					if ( $first_title_link instanceof DOMElement ) {
						if ( '' === $first_href ) {
							$first_href = trim( (string) $first_title_link->getAttribute( 'href' ) );
						}

						if ( '' === $first_dialog ) {
							$first_dialog = trim( (string) $first_title_link->getAttribute( 'data-pfoa-cat-dialog' ) );
						}

						if ( '' === $first_profile_attr ) {
							$first_profile_attr = trim( (string) $first_title_link->getAttribute( 'data-pfoa-profile-id' ) );
						}
					}
				}
			}

			if ( '' === $first_profile_attr ) {
				$first_profile_attr = (string) $first_id;
			}

			if ( '' === $first_href ) {
				continue;
			}

		// Build replacement via DOM methods so text/attributes stay escaped.
		$structured_bonded = $dom->createElement( 'article' );
		$structured_bonded->setAttribute( 'class', 'pfoa-cat-card pfoa-cat-bonded-card pfoa-cat-bonded-card--bonded' );

		if ( '' !== $first_dialog ) {
			$structured_bonded->setAttribute( 'data-pfoa-cat-dialog', $first_dialog );
		}

		$structured_bonded->setAttribute( 'data-pfoa-profile-id', $first_profile_attr );

		// Stable fragment anchor for hero bonded-pair deep links, matching the
		// hero resolution ('bonded-pair-<lower>-<upper>'). tabindex -1 keeps
		// the target out of the tab order while remaining focusable on
		// fragment navigation.
		$structured_anchor = pfoa_get_hero_bonded_pair_anchor( $first_id, $second_id );

		if ( '' !== $structured_anchor ) {
			$structured_bonded->setAttribute( 'id', $structured_anchor );
			$structured_bonded->setAttribute( 'tabindex', '-1' );
		}

			$structured_photos = $dom->createElement( 'div' );
			$structured_photos->setAttribute( 'class', 'pfoa-cat-bonded-photos' );
			$structured_bonded->appendChild( $structured_photos );

			// Preserve both member media links (href + dialog + profile-id) and
			// images (src/srcset/sizes/alt) in stored document order. Each
			// clone carries its own member's data-pfoa-profile-id: singles
			// carry the ID on the article, not the media link, so the ID is
			// propagated explicitly. The article keeps the document-first
			// member's attrs only (never duplicated per member).
			$structured_media_nodes = array(
				array( $first_media, $first_id ),
				array( $second_media, $second_id ),
			);

			foreach ( $structured_media_nodes as $structured_media_pair ) {
				$structured_media_node = $structured_media_pair[0];
				$structured_member_id  = (int) $structured_media_pair[1];

				if ( ! $structured_media_node instanceof DOMElement ) {
					continue;
				}

				$structured_media_clone = $structured_media_node->cloneNode( true );

				$structured_media_class = trim( (string) $structured_media_clone->getAttribute( 'class' ) );

				if ( false === strpos( ' ' . $structured_media_class . ' ', ' pfoa-cat-bonded-photo ' ) ) {
					$structured_media_clone->setAttribute( 'class', trim( $structured_media_class . ' pfoa-cat-bonded-photo' ) );
				}

				if ( 0 < $structured_member_id ) {
					$structured_media_clone->setAttribute( 'data-pfoa-profile-id', (string) $structured_member_id );
				}

				$structured_photos->appendChild( $structured_media_clone );
			}

			$structured_footer = $dom->createElement( 'div' );
			$structured_footer->setAttribute( 'class', 'pfoa-cat-bonded-footer' );
			$structured_bonded->appendChild( $structured_footer );

			$structured_heading = $dom->createElement( 'h3' );
			$structured_heading->setAttribute( 'class', 'pfoa-cat-bonded-title' );
			$structured_footer->appendChild( $structured_heading );

			$structured_link = $dom->createElement( 'a' );
			$structured_link->setAttribute( 'href', $first_href );

			if ( '' !== $first_dialog ) {
				$structured_link->setAttribute( 'data-pfoa-cat-dialog', $first_dialog );
			}

			$structured_link->setAttribute( 'data-pfoa-profile-id', $first_profile_attr );

			$structured_link->textContent = $first_name . ' & ' . $second_name;
			$structured_heading->appendChild( $structured_link );

			// PFOA 0.1.83 — presentation only: shared "Bonded Pair" badge
			// inside the existing title (pair name stays primary). Absolutely
			// positioned by CSS so title/card geometry is unchanged; the
			// visually-hidden suffix gives assistive-tech context. Replaces
			// the old plain p.pfoa-cat-bonded-relationship line so there is
			// one presentation only.
			$structured_heading->appendChild( $dom->createTextNode( ' ' ) );
			$structured_badge = $dom->createElement( 'span' );
			$structured_badge->setAttribute( 'class', 'pfoa-cat-bonded-badge' );
			$structured_badge->appendChild( $dom->createTextNode( esc_html__( 'Bonded Pair', 'pfoa-theme' ) ) );
			$structured_sr = $dom->createElement( 'span' );
			$structured_sr->setAttribute( 'class', 'screen-reader-text' );
			$structured_sr->appendChild( $dom->createTextNode( ' ' . esc_html__( 'available together as a bonded pair', 'pfoa-theme' ) ) );
			$structured_badge->appendChild( $structured_sr );
			$structured_heading->appendChild( $structured_badge );

			$structured_parent = $first_card->parentNode;

			if ( ! $structured_parent ) {
				continue;
			}

			if ( $second_card->parentNode !== $structured_parent ) {
				continue;
			}

			// PFOA 0.1.169 — anchor at the document-first card so the combined
			// card keeps the pair's stored position (no reordering).
			$structured_parent->insertBefore( $structured_bonded, $first_card );
			$structured_parent->removeChild( $first_card );
			$structured_parent->removeChild( $second_card );

			$structured_consumed[ $first_id ]  = true;
			$structured_consumed[ $second_id ] = true;

			$changed = true;
		}
	}

	if ( ! $changed ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$output = '';

	foreach ( $wrapper->childNodes as $child ) {
		$output .= $dom->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $prev_libxml );

	return $output;
}

add_filter( 'the_content', 'pfoa_present_bonded_pairs', 999 );

/**
 * Adopted 2026 gallery: transitional adoption-event rendering.
 *
 * Page-specific (page 22514 / adopted2026). Renders published
 * pfoa_adoption_event records for event year 2026 from immutable event
 * snapshots ONLY: _pfoa_event_uuid, _pfoa_event_date / _pfoa_event_year,
 * and the canonical ordered _pfoa_event_members rows (order, image_id,
 * caption, cat_name_at_event, cat_post_id). Canonical
 * pfoa_adoption_event_get_* readers are preferred with direct snapshot
 * meta under identical keys as fallback. Never from pfoa_cat, never from
 * current profile thumbnails/galleries, never from attachment
 * post_excerpt, never inferred membership/grouping.
 *
 * Runs at priority 950, BEFORE the legacy adopted bonded-pair
 * presentation (1000) and the adoptable companion (999), so figures
 * claimed by published event snapshots are removed from the rendered
 * legacy gallery before legacy grouping/inference runs. The legacy
 * grouping itself is never rewritten. With zero published 2026 events the
 * filter returns $content byte-identical (no DOM work at all).
 *
 * Per event: one .pfoa-adoption-event wrapper carrying the event UUID in
 * data-pfoa-adoption-event (never a post ID as public identity), a neutral
 * combined-name heading (no relationship wording, no historical facts),
 * and one visible figure per member in canonical stored order, each with
 * its own snapshot image/caption. Multi-member events also carry
 * .pfoa-adoption-event-multi plus .pfoa-adoption-event-count-{2,3,n} so
 * the grouped outer card spans 2 (pairs) or 3 (trios and larger) standard
 * grid tracks; single-member markup is unchanged. A repeated cat across
 * events renders in
 * every event; events are never deduped. A missing/unusable snapshot
 * image renders a restrained placeholder with the snapshot caption/name;
 * the event is never silently dropped and the current profile image is
 * never substituted. pfoa_cat receives no presentation metadata.
 *
 * Event markup deliberately uses no .gallery / .gallery-item classes and
 * emits no gallery-N-ID / aria-describedby signals, so the legacy
 * discovery rules can never claim event figures. Member links are
 * standard wp_get_attachment_link() file links, which the PFOA Gallery
 * Lightbox serves as standalone one-image sets.
 *
 * @param string $content Post content.
 * @return string Event section plus (possibly claim-stripped) content.
 */
function pfoa_adoption_event_member_media( $image_id, $caption = '' ) {
	$image_id = (int) $image_id;
	$caption  = is_string( $caption ) ? trim( (string) $caption ) : '';

	if ( 0 < $image_id && function_exists( 'wp_attachment_is_image' ) && ! wp_attachment_is_image( $image_id ) ) {
		$image_id = 0;
	}

	if ( 0 < $image_id && function_exists( 'wp_get_attachment_link' ) ) {
		$linked_image = wp_get_attachment_link( $image_id, 'medium', false, false );

		if ( is_string( $linked_image ) && '' !== trim( $linked_image ) && false !== strpos( $linked_image, '<img' ) ) {
			// PFOA 0.1.79 — presentation only: override the generated
			// <img> alt and the lightbox link's data-pfoa-caption with the
			// already-resolved snapshot presentation caption (caption,
			// fallback cat_name_at_event). Attachment ID, URLs, thumb,
			// href, CSS, and visible markup are unchanged; no figcaption
			// is added here.
			if ( '' !== $caption ) {
				$alt = esc_attr( $caption );

				$linked_image = (string) preg_replace_callback(
					'/<img\b[^>]*>/i',
					function ( $matches ) use ( $alt ) {
						$img = $matches[0];

						if ( preg_match( '/\salt=(["\']).*?\1/i', $img ) ) {
							$img = (string) preg_replace( '/\salt=(["\']).*?\1/i', ' alt="' . $alt . '"', $img );
						} else {
							$img = (string) preg_replace( '/<img\b/i', '<img alt="' . $alt . '"', $img, 1 );
						}

						return $img;
					},
					$linked_image,
					1
				);

				$linked_image = (string) preg_replace_callback(
					'/<a\b/i',
					function () use ( $alt ) {
						return '<a data-pfoa-caption="' . $alt . '"';
					},
					$linked_image,
					1
				);
			}

			return $linked_image;
		}
	}

	return '<span class="pfoa-adoption-event-thumb-missing"><span class="pfoa-adoption-event-missing-text">Photo unavailable</span></span>';
}

/**
 * Format a stored adoption-event date for visible presentation.
 *
 * Presentation only: reads nothing, writes nothing. Returns '' for empty
 * input, a localized "F j, Y" string for parseable dates, or the raw trimmed
 * string when unparseable. Callers escape the return value.
 *
 * @param string $date_raw Stored `_pfoa_event_date` value.
 * @return string Display date (unescaped).
 */
function pfoa_adoption_event_format_date( $date_raw ) {
	$date_raw = trim( (string) $date_raw );

	if ( '' === $date_raw ) {
		return '';
	}

	$timestamp = strtotime( $date_raw );

	if ( false === $timestamp ) {
		return $date_raw;
	}

	if ( function_exists( 'date_i18n' ) ) {
		return date_i18n( 'F j, Y', $timestamp );
	}

	return date( 'F j, Y', $timestamp );
}

/**
 * Presentation only: strip a trailing administrative qualifier from an
 * event snapshot name (e.g. "Shadow (Kitten 4040)" -> "Shadow").
 *
 * Stored `_pfoa_event_members[].cat_name_at_event` snapshots are never
 * rewritten; `cat_post_id`, `cat_id_at_event`, UUID, date, and photo are
 * untouched. Only the in-memory display copy is cleaned so the public
 * card shows the cat name once without the admin Cat ID.
 *
 * @param string $name_raw Stored snapshot name.
 * @return string Display name (unescaped).
 */
function pfoa_adoption_event_display_name( $name_raw ) {
	$name_raw = trim( (string) $name_raw );

	if ( '' === $name_raw ) {
		return '';
	}

	$stripped = (string) preg_replace( '/\s*\([^()]*\b(?:Kitten|Cat(?:\s+ID)?|ID)\b[^()]*\d[^()]*\)\s*$/i', '', $name_raw );

	$stripped = trim( $stripped );

	if ( '' !== $stripped ) {
		return $stripped;
	}

	return $name_raw;
}

/**
 * Adopted 2026 gallery identity — strict claimed-ID validator (0.1.143).
 *
 * Presentation only. Returns the positive attachment ID as int, or null when
 * the raw token is not an exact canonical positive integer (no numeric
 * sentinel: invalid is null, never 0, so invalid can never match discovery). Full-string
 * match via /\A[1-9][0-9]*\z/ (absolute end, NOT $: a terminal newline such
 * as "23330\n" is invalid). The token is NEVER trimmed before validation,
 * so whitespace-tainted tokens ("23330 ", " 23330") are invalid. No partial
 * or fractional acceptance ("23330.9", "023330", "-5", "+5", "2.333e4" are
 * invalid). Overflow-safe with no arbitrary digit limit: tokens longer than
 * the decimal form of PHP_INT_MAX are rejected, equal-length tokens above
 * it are rejected lexicographically, and the (int) cast is round-trip
 * checked, so the cast below can never wrap and values outside the int
 * domain are invalid (fail closed in the caller: preserve the legacy item).
 * Malformed input must fail closed in the caller (preserve the legacy item).
 *
 * @param mixed $raw Raw candidate token (attribute value, query value, …).
 * @return int|null Positive attachment ID, or null when invalid (no numeric sentinel).
 */
function pfoa_valid_claimed_attachment_id( $raw ) {
	$token = (string) $raw;

	if ( 1 !== preg_match( '/\A[1-9][0-9]*\z/', $token ) ) {
		return null;
	}

	$max_token = (string) PHP_INT_MAX;

	if ( strlen( $token ) > strlen( $max_token ) ) {
		return null;
	}

	if ( strlen( $token ) === strlen( $max_token ) && 0 < strcmp( $token, $max_token ) ) {
		return null;
	}

	$claimed = (int) $token;

	if ( (string) $claimed !== $token ) {
		return null;
	}

	if ( 0 >= $claimed ) {
		return null;
	}

	return $claimed;
}

/**
 * Adopted 2026 gallery identity — single auditable per-item result (0.1.143).
 *
 * Presentation only. Collects EVERY recognized identity occurrence on one
 * legacy .gallery-item into a single auditable result distinguishing three
 * states: valid IDs (exact canonical positive integers eligible for
 * discovery matching), recognized-but-invalid occurrences (fail-closed
 * evidence that always preserves the item), and absent/unrelated signals
 * (ignored: missing attributes, unrelated classes/ARIA tokens, unrelated
 * query keys, URL fragments). No numeric sentinels anywhere: invalid input
 * is counted, never encoded as an ID, so a malformed token can never match
 * discovery and malformed evidence never silently vanishes.
 *
 * Recognized sources (each occurrence counted):
 * - img/figure/item data-attachment-id + data-id when PRESENT
 *   (hasAttribute/hasAttributeNS separates absent→ignore from
 *   present-empty/malformed→invalid; the raw value is never trimmed, so
 *   "23330 ", "23330\n", "", "0", and "23330.9" are invalid).
 * - img class wp-image-<suffix> tokens (unrelated classes ignored; a
 *   recognized prefix with an invalid suffix→invalid; multiple tokens in
 *   one class attribute are each counted, so conflicts break unanimity).
 * - img aria-describedby IDREF lists split on whitespace (unrelated tokens
 *   ignored; gallery-<n>-<suffix> recognized, invalid suffix including
 *   malformed ARIA→invalid).
 * - figcaption/dd[id] captions (gallery-<n>-<suffix> recognized; malformed
 *   caption→invalid).
 * - EVERY ?attachment_id query parameter of EVERY a[href] (query component
 *   only via parse_url PHP_URL_QUERY, so fragments never count; exploded
 *   on & with names urldecoded so encoded names count; EVERY repeated
 *   occurrence processed; bare keys without "=" or value, empty values,
 *   and malformed values→invalid; unrelated keys ignored; values are never
 *   trimmed before validation).
 *
 * @param DOMElement $item_node Legacy .gallery-item node.
 * @param DOMXPath   $xpath     XPath bound to the owner document.
 * @return array Validated identity result with keys valid_ids
 *               (array<int, true>), invalid (int count of
 *               recognized-but-invalid occurrences), recognized (int count
 *               of all recognized occurrences).
 */
function pfoa_collect_gallery_item_identity( $item_node, $xpath ) {
	$result = array(
		'valid_ids'  => array(),
		'invalid'    => 0,
		'recognized' => 0,
	);

	if ( ! $item_node instanceof DOMElement || ! $xpath instanceof DOMXPath ) {
		return $result;
	}

	$record_token = function( $raw ) use ( &$result ) {
		$result['recognized']++;

		$claimed = pfoa_valid_claimed_attachment_id( $raw );

		if ( null === $claimed ) {
			$result['invalid']++;
			return;
		}

		$result['valid_ids'][ $claimed ] = true;
	};

	$node_has_attribute = function( $node, $name ) {
		if ( ! $node instanceof DOMElement ) {
			return false;
		}

		if ( $node->hasAttribute( $name ) ) {
			return true;
		}

		if ( method_exists( $node, 'hasAttributeNS' ) ) {
			foreach ( array( null, '', '*' ) as $namespace ) {
				$present = false;

				if ( null === $namespace ) {
					$present = $node->hasAttributeNS( null, $name );
				} elseif ( '' === $namespace ) {
					$present = $node->hasAttributeNS( '', $name );
				} else {
					$present = $node->hasAttributeNS( '*', $name );
				}

				if ( $present ) {
					return true;
				}
			}
		}

		return false;
	};

	$record_data_attributes = function( $node ) use ( $node_has_attribute, $record_token ) {
		if ( ! $node instanceof DOMElement ) {
			return;
		}

		foreach ( array( 'data-attachment-id', 'data-id' ) as $data_attr ) {
			if ( ! $node_has_attribute( $node, $data_attr ) ) {
				continue;
			}

			$record_token( (string) $node->getAttribute( $data_attr ) );
		}
	};

	$caption_list = $xpath->query( './/*[self::figcaption or self::dd][@id]', $item_node );

	if ( $caption_list instanceof DOMNodeList && 0 < $caption_list->length ) {
		foreach ( $caption_list as $caption_node ) {
			if ( ! $caption_node instanceof DOMElement ) {
				continue;
			}

			$caption_id = (string) $caption_node->getAttribute( 'id' );

			if ( 1 !== preg_match( '/\Agallery-[0-9]+-(.*)\z/s', $caption_id, $caption_matches ) ) {
				continue;
			}

			$record_token( (string) $caption_matches[1] );
		}
	}

	$aria_list = $xpath->query( './/img[@aria-describedby]', $item_node );

	if ( $aria_list instanceof DOMNodeList && 0 < $aria_list->length ) {
		foreach ( $aria_list as $aria_node ) {
			if ( ! $aria_node instanceof DOMElement ) {
				continue;
			}

			$aria_tokens = preg_split( '/\s+/', trim( (string) $aria_node->getAttribute( 'aria-describedby' ) ) );

			if ( ! is_array( $aria_tokens ) ) {
				continue;
			}

			foreach ( $aria_tokens as $aria_token ) {
				if ( '' === (string) $aria_token ) {
					continue;
				}

				if ( 1 !== preg_match( '/\Agallery-[0-9]+-(.*)\z/s', (string) $aria_token, $aria_matches ) ) {
					continue;
				}

				$record_token( (string) $aria_matches[1] );
			}
		}
	}

	$fallback_img_list = $xpath->query( './/img', $item_node );

	if ( $fallback_img_list instanceof DOMNodeList && 0 < $fallback_img_list->length ) {
		foreach ( $fallback_img_list as $fallback_img ) {
			if ( ! $fallback_img instanceof DOMElement ) {
				continue;
			}

			$class_raw = trim( (string) $fallback_img->getAttribute( 'class' ) );

			if ( '' !== $class_raw ) {
				$class_tokens = preg_split( '/\s+/', $class_raw );

				if ( is_array( $class_tokens ) ) {
					foreach ( $class_tokens as $class_token ) {
						if ( 0 !== strpos( (string) $class_token, 'wp-image-' ) ) {
							continue;
						}

						$record_token( (string) substr( (string) $class_token, 9 ) );
					}
				}
			}

			$record_data_attributes( $fallback_img );
		}
	}

	$fallback_figure_list = $xpath->query( './/figure', $item_node );

	if ( $fallback_figure_list instanceof DOMNodeList && 0 < $fallback_figure_list->length ) {
		foreach ( $fallback_figure_list as $fallback_figure ) {
			$record_data_attributes( $fallback_figure );
		}
	}

	$record_data_attributes( $item_node );

	$fallback_anchor_list = $xpath->query( './/a[@href]', $item_node );

	if ( $fallback_anchor_list instanceof DOMNodeList && 0 < $fallback_anchor_list->length ) {
		foreach ( $fallback_anchor_list as $fallback_anchor ) {
			if ( ! $fallback_anchor instanceof DOMElement ) {
				continue;
			}

			$fallback_href = (string) $fallback_anchor->getAttribute( 'href' );

			// Query component only: a fragment such as
			// /#section?attachment_id=23330 carries NO query and yields
			// zero recognized occurrences (parse_url returns null/false/'').
			$href_query = parse_url( $fallback_href, PHP_URL_QUERY );

			if ( ! is_string( $href_query ) || '' === $href_query ) {
				continue;
			}

			// EVERY repeated attachment_id= occurrence is processed (no
			// 1=== gate): identical repeats stay unanimous, conflicting
			// repeats break unanimity via distinct valid IDs.
			$href_pairs = explode( '&', $href_query );

			foreach ( $href_pairs as $href_pair ) {
				$href_eq = strpos( (string) $href_pair, '=' );

				if ( false === $href_eq ) {
					$href_name = urldecode( (string) $href_pair );

					if ( 'attachment_id' !== $href_name ) {
						continue;
					}

					// Bare key without "=" or value: recognized-invalid.
					$result['recognized']++;
					$result['invalid']++;
					continue;
				}

				$href_name = urldecode( (string) substr( (string) $href_pair, 0, (int) $href_eq ) );

				if ( 'attachment_id' !== $href_name ) {
					continue;
				}

				$href_value = urldecode( (string) substr( (string) $href_pair, (int) $href_eq + 1 ) );

				// Never trim-then-accept: the raw value goes to the strict
				// validator, so "23330 " is malformed, not 23330.
				$record_token( $href_value );
			}
		}
	}

	return $result;
}

// PFOA 0.1.209 — presentation only: display predicate for the bonded-pair
// badge shared by pfoa_present_adoption_events() and
// pfoa_get_recently_adopted_events(). Theme-local; no plugin/contract
// changes, no Cat ID reads/writes, no ID invention, no writes of any kind.
// Historical preservation (LC-D02): a valid, explicitly stored
// classification for an authentic two-member event wins outright,
// independently of the cats' current bond relationship. Stored
// bonded_pair => true (Bonded Pair); stored adopted_together or singleton
// => false (Adopted Together via the callers' non-bonded multi-cat rule,
// never upgraded by a later bond). Ambiguous or malformed stored data
// (invalid value, duplicate rows) resolves conservatively to false
// (fail-closed, never a bonded badge). Events with NO authoritative stored
// classification (absent/empty, unknown event) fall through to the legacy
// strict predicate below (pfoa_cat_is_bonded() on both sides +
// pfoa_cat_get_bonded_pair() canonical equality), so legacy events render
// exactly as before. Any check failing => false (fail-closed).
// @param int   $event_id Adoption event post ID (0 when unknown).
// @param array $bond_ids Two int cat post IDs in member order.
// @return bool True when the event displays the Bonded Pair badge.
function pfoa_adoption_event_is_bonded_pair_for_display( $event_id, $bond_ids ) {
	$event_id = (int) $event_id;

	if ( ! is_array( $bond_ids ) || 2 !== count( $bond_ids ) ) {
		return false;
	}

	$bond_ids = array_values( $bond_ids );
	$cat_a    = (int) $bond_ids[0];
	$cat_b    = (int) $bond_ids[1];

	if ( 0 >= $cat_a || 0 >= $cat_b || $cat_a === $cat_b ) {
		return false;
	}

	// Authoritative stored classification wins when present. Read-only
	// single-row meta or the canonical stored-classification reader when
	// present; never writes, never infers, never consults live bonds here.
	if ( 0 < $event_id ) {
		if ( function_exists( 'pfoa_tx_read_stored_classification' ) ) {
			$stored = pfoa_tx_read_stored_classification( $event_id );

			if ( is_array( $stored ) && isset( $stored['state'], $stored['value'] ) ) {
				if ( 'valid' === $stored['state'] ) {
					return ( 'bonded_pair' === $stored['value'] );
				}

				if ( 'invalid' === $stored['state'] || 'duplicate' === $stored['state'] ) {
					return false;
				}
				// 'absent' (or any other state) falls through to legacy.
			} elseif ( is_string( $stored ) ) {
				$stored_trimmed = trim( $stored );

				if ( '' === $stored_trimmed ) {
					// Absent-equivalent: fall through to legacy.
				} elseif ( 'bonded_pair' === $stored_trimmed ) {
					return true;
				} elseif ( 'adopted_together' === $stored_trimmed || 'singleton' === $stored_trimmed ) {
					return false;
				} else {
					return false;
				}
			}
		} elseif ( function_exists( 'get_post_meta' ) ) {
			$rows = get_post_meta( $event_id, '_pfoa_event_classification', false );

			if ( is_array( $rows ) && array() !== $rows ) {
				if ( 1 === count( $rows ) && ( '' === $rows[0] || null === $rows[0] ) ) {
					// Absent-equivalent legacy sentinel: fall through.
				} elseif ( 1 !== count( $rows ) ) {
					return false;
				} elseif ( 'bonded_pair' === $rows[0] ) {
					return true;
				} elseif ( 'adopted_together' === $rows[0] || 'singleton' === $rows[0] ) {
					return false;
				} else {
					return false;
				}
			}
			// Absent (no rows): fall through to legacy.
		}
	}
	// Legacy compatibility: no authoritative stored classification, so
	// the pre-0.1.168 strict predicate runs unchanged (requires valid
	// established Cat IDs both sides via pfoa_cat_is_bonded()).
	if ( function_exists( 'pfoa_cat_is_bonded' ) && function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
		if ( pfoa_cat_is_bonded( $cat_a ) && pfoa_cat_is_bonded( $cat_b ) ) {
			$bond_pair = pfoa_cat_get_bonded_pair( $cat_a );

			if ( is_array( $bond_pair ) ) {
				$bond_pair   = array_map( 'intval', array_values( $bond_pair ) );
				$bond_sorted = array( $cat_a, $cat_b );

				sort( $bond_pair, SORT_NUMERIC );
				sort( $bond_sorted, SORT_NUMERIC );

				if ( $bond_pair === $bond_sorted ) {
					return true;
				}
			}
		}
	}

	return false;
}

function pfoa_present_adoption_events( $content ) {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 22514 ) || is_page( 'adopted2026' ) ) ) {
		$is_target_page = true;
	} elseif ( function_exists( 'get_post' ) ) {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			if ( 22514 === (int) $post->ID ) {
				$is_target_page = true;
			} elseif ( isset( $post->post_name ) && 'adopted2026' === $post->post_name ) {
				$is_target_page = true;
			}
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	if ( ! function_exists( 'get_posts' ) || ! function_exists( 'get_post_meta' ) ) {
		return $content;
	}

	// Step 1: query published adoption events for event year 2026. Final
	// ordering (adoption date DESC, event post ID DESC) is applied in PHP
	// via the canonical readers so storage order can never misorder.
	$event_ids = get_posts(
		array(
			'post_type'      => 'pfoa_adoption_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'     => '_pfoa_event_year',
					'value'   => '2026',
					'compare' => '=',
				),
			),
		)
	);

	if ( ! is_array( $event_ids ) || empty( $event_ids ) ) {
		return $content;
	}

	// Step 2: read each event from immutable snapshots only. No gallery
	// early-out here on purpose: events render even when the legacy
	// gallery is absent or holds none of the snapshot images.
	$events = array();

	foreach ( $event_ids as $event_id_raw ) {
		$event_id = (int) $event_id_raw;

		if ( 0 >= $event_id ) {
			continue;
		}

		// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP.
		if ( function_exists( 'pfoa_dev_synthetic_is_excluded_event' ) && pfoa_dev_synthetic_is_excluded_event( $event_id ) ) {
			continue;
		}

		if ( function_exists( 'get_post' ) ) {
			$event_post = get_post( $event_id );

			if ( ! $event_post instanceof WP_Post || 'pfoa_adoption_event' !== $event_post->post_type ) {
				continue;
			}
		}

		if ( function_exists( 'pfoa_adoption_event_get_year' ) ) {
			$event_year = (string) pfoa_adoption_event_get_year( $event_id );
		} else {
			$event_year = (string) get_post_meta( $event_id, '_pfoa_event_year', true );
		}

		if ( '2026' !== trim( $event_year ) ) {
			continue;
		}

		if ( function_exists( 'pfoa_adoption_event_get_date' ) ) {
			$event_date = (string) pfoa_adoption_event_get_date( $event_id );
		} else {
			$event_date = (string) get_post_meta( $event_id, '_pfoa_event_date', true );
		}

		if ( function_exists( 'pfoa_adoption_event_get_uuid' ) ) {
			$event_uuid = (string) pfoa_adoption_event_get_uuid( $event_id );
		} else {
			$event_uuid = (string) get_post_meta( $event_id, '_pfoa_event_uuid', true );
		}

		$event_uuid = trim( $event_uuid );

		if ( '' === $event_uuid ) {
			continue;
		}

		if ( function_exists( 'pfoa_adoption_event_get_members' ) ) {
			$members_raw = pfoa_adoption_event_get_members( $event_id );
		} else {
			$members_raw = get_post_meta( $event_id, '_pfoa_event_members', true );
		}

		if ( ! is_array( $members_raw ) || empty( $members_raw ) ) {
			continue;
		}

		$members = array();

		foreach ( array_values( $members_raw ) as $member_raw ) {
			if ( ! is_array( $member_raw ) ) {
				continue;
			}

			// PFOA Shadow kitten display correction — presentation only:
			// strip a trailing administrative qualifier (e.g. " (Kitten
			// 4040)") from the in-memory display name so the public card
			// shows "Shadow" once. Stored snapshot, cat_post_id,
			// cat_id_at_event, UUID, date, and photo are untouched.
			$member_image = isset( $member_raw['image_id'] ) ? (int) $member_raw['image_id'] : 0;
			$member_name  = isset( $member_raw['cat_name_at_event'] ) ? pfoa_adoption_event_display_name( $member_raw['cat_name_at_event'] ) : '';
			$member_cap   = isset( $member_raw['caption'] ) ? trim( (string) $member_raw['caption'] ) : '';
			$member_cat   = isset( $member_raw['cat_post_id'] ) ? (int) $member_raw['cat_post_id'] : 0;

			if ( '' === $member_cap ) {
				$member_cap = $member_name;
			}

			$members[] = array(
				'image_id'    => 0 < $member_image ? $member_image : 0,
				'caption'     => $member_cap,
				'name'        => $member_name,
				'cat_post_id' => 0 < $member_cat ? $member_cat : 0,
			);
		}

		if ( empty( $members ) ) {
			continue;
		}

		// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP.
		if ( function_exists( 'pfoa_dev_synthetic_is_excluded_cat' ) ) {
			$dev_synth_hit = false;

			foreach ( $members as $dev_synth_member ) {
				if ( is_array( $dev_synth_member ) && isset( $dev_synth_member['cat_post_id'] ) && pfoa_dev_synthetic_is_excluded_cat( (int) $dev_synth_member['cat_post_id'] ) ) {
					$dev_synth_hit = true;
					break;
				}
			}

			if ( $dev_synth_hit ) {
				continue;
			}
		}

		// PFOA 0.1.120 — presentation only: optional legacy-gallery anchor
		// image ID read per event (never written, never inferred). The
		// canonical reader is preferred when present; otherwise the raw
		// `_pfoa_event_legacy_anchor_image_id` meta is used. Non-numeric,
		// zero, and negative values all normalize to 0 (no anchor), which
		// keeps the pre-0.1.120 rendering path. Conflict validation
		// (anchor==member, shared anchor) runs fail-closed below after
		// ordering; a missing DOM node falls back naturally at placement.
		if ( function_exists( 'pfoa_adoption_event_get_legacy_anchor_image_id' ) ) {
			$anchor_raw = pfoa_adoption_event_get_legacy_anchor_image_id( $event_id );
		} else {
			$anchor_raw = get_post_meta( $event_id, '_pfoa_event_legacy_anchor_image_id', true );
		}

		$legacy_anchor = 0;

		if ( is_string( $anchor_raw ) ) {
			$anchor_raw = trim( $anchor_raw );
		}

		if ( is_int( $anchor_raw ) || is_float( $anchor_raw ) || ( is_string( $anchor_raw ) && is_numeric( $anchor_raw ) ) ) {
			$legacy_anchor = (int) abs( (float) $anchor_raw );

			if ( 0 >= $legacy_anchor ) {
				$legacy_anchor = 0;
			}
		}

		$events[] = array(
			'id'            => $event_id,
			'date'          => trim( (string) $event_date ),
			'uuid'          => $event_uuid,
			'members'       => $members,
			'legacy_anchor' => $legacy_anchor,
		);
	}

	if ( empty( $events ) ) {
		return $content;
	}

	// Adoption date DESC, event post ID DESC tie-break. Each event renders
	// exactly once; a repeated cat across events is never deduped.
	usort(
		$events,
		function ( $a, $b ) {
			$date_cmp = strcmp( (string) $b['date'], (string) $a['date'] );

			if ( 0 !== $date_cmp ) {
				return $date_cmp;
			}

			if ( (int) $b['id'] === (int) $a['id'] ) {
				return 0;
			}

			return (int) $b['id'] > (int) $a['id'] ? 1 : -1;
		}
	);

	// PFOA 0.1.120 — presentation only: fail-closed conflict validation
	// for the optional legacy anchors read above (no throws, no writes).
	// An anchor equal to any member image_id in ANY event is invalid (the
	// anchor must be a standalone legacy node, never a member image), and
	// an anchor shared by two events invalidates both (first-wins would
	// otherwise merge two events at one node). Invalidated anchors reset
	// to 0 so those events render exactly as before (member-order anchor
	// + fallback). Member claiming ($claimed_ids) is untouched.
	$all_member_ids = array();

	foreach ( $events as $event ) {
		foreach ( $event['members'] as $event_member ) {
			if ( is_array( $event_member ) && isset( $event_member['image_id'] ) && 0 < (int) $event_member['image_id'] ) {
				$all_member_ids[ (int) $event_member['image_id'] ] = true;
			}
		}
	}

	$anchor_counts = array();

	foreach ( $events as $event ) {
		$event_anchor = isset( $event['legacy_anchor'] ) ? (int) $event['legacy_anchor'] : 0;

		if ( 0 < $event_anchor && ! isset( $all_member_ids[ $event_anchor ] ) ) {
			$anchor_counts[ $event_anchor ] = isset( $anchor_counts[ $event_anchor ] ) ? (int) $anchor_counts[ $event_anchor ] + 1 : 1;
		}
	}

	foreach ( $events as $event_index => $event ) {
		$event_anchor = isset( $event['legacy_anchor'] ) ? (int) $event['legacy_anchor'] : 0;

		if ( 0 >= $event_anchor ) {
			continue;
		}

		if ( isset( $all_member_ids[ $event_anchor ] ) ) {
			$events[ $event_index ]['legacy_anchor'] = 0;
			continue;
		}

		if ( ! isset( $anchor_counts[ $event_anchor ] ) || 1 !== (int) $anchor_counts[ $event_anchor ] ) {
			$events[ $event_index ]['legacy_anchor'] = 0;
		}
	}

	// Step 3: build event markup from snapshots and collect the claimed
	// snapshot image IDs for legacy suppression below.
	$claimed_ids = array();
	$events_html = '<section class="pfoa-adoption-events" aria-label="Adopted cats">';

	foreach ( $events as $event ) {
		$member_count = count( $event['members'] );
		$count_class  = 3 < $member_count ? 'n' : (string) $member_count;

		// PFOA 0.1.82 — presentation only: bonded-pair indicator for TRUE
		// bonded 2-member events only. PFOA 0.1.209 routes the predicate
		// through pfoa_adoption_event_is_bonded_pair_for_display()
		// (valid stored classification wins; absent falls back to the
		// strict helpers fail-closed). 3+ member events are NEVER
		// labelled bonded. No bond data, event meta, or stored facts are
		// written; no Cat IDs read or invented.
		$is_bonded_pair = false;

		if ( 2 === $member_count ) {
			$bond_ids = array();

			foreach ( $event['members'] as $bond_member ) {
				$bond_ids[] = ( is_array( $bond_member ) && isset( $bond_member['cat_post_id'] ) ) ? (int) $bond_member['cat_post_id'] : 0;
			}

			$gate_event_id  = ( is_array( $event ) && isset( $event['id'] ) ) ? (int) $event['id'] : 0;
			$is_bonded_pair = pfoa_adoption_event_is_bonded_pair_for_display( $gate_event_id, $bond_ids );
		}

		// PFOA 0.1.86 — presentation only: adopted-together indicator for
		// non-bonded multi-cat events only. Single-cat => false; true-bonded
		// 2-member => false; all other multi-cat (2+ non-bonded incl. 3+) =>
		// true. No meta reads beyond the bonded helpers above, no writes.
		$is_adopted_together = ( 2 <= $member_count && ! $is_bonded_pair );

		$names = array();

		foreach ( $event['members'] as $event_member ) {
			if ( '' !== $event_member['name'] ) {
				$names[] = $event_member['name'];
			}
		}

		// PFOA 0.1.79 — presentation only: every wrapper carries its count
		// class (count-1 for singles) so the style.css
		// .gallery > .pfoa-adoption-event-count-{1,2,3,n} selectors match.
		// Multi markup (multi + count-2/3/n order) is unchanged.
		$multi_class = 1 < $member_count ? ' pfoa-adoption-event-multi pfoa-adoption-event-count-' . $count_class : ' pfoa-adoption-event-count-1';

		// PFOA 0.1.82 — presentation only: bonded modifier class for true
		// bonded pairs; badge markup joins the combined title below.
		$bonded_class = $is_bonded_pair ? ' pfoa-adoption-event--bonded' : '';

		// PFOA 0.1.86 — presentation only: together modifier class for
		// non-bonded multi-cat events; badge markup joins the title below.
		$together_class = $is_adopted_together ? ' pfoa-adoption-event--together' : '';

		$events_html .= '<div class="pfoa-adoption-event' . $multi_class . $bonded_class . $together_class . '" data-pfoa-adoption-event="' . esc_attr( $event['uuid'] ) . '">';

		// PFOA 0.1.76 — presentation only: single-cat events keep the
		// combined heading above the image (image->name); multi-member
		// events render the same combined heading once below the
		// member-image grid (image->names). Names order/composition,
		// member order, images, links/lightbox, and the 0.1.75
		// per-member figcaption gate below are unchanged.
		$combined_title = '';

		if ( ! empty( $names ) ) {
			$combined_title = '<h2 class="pfoa-adoption-event-title">' . implode( ' &amp; ', array_map( 'esc_html', $names ) );

		// PFOA 0.1.82 — presentation only: visible "Bonded Pair" badge
		// inside the existing title (names stay primary). Absolutely
		// positioned by CSS so title/card geometry is unchanged; the
		// visually-hidden suffix gives assistive-tech context.
		// PFOA 0.1.86 — presentation only: green "Adopted Together"
		// badge for non-bonded multi-cat events; single-cat => no badge,
		// true-bonded 2-member => red badge via the branch above.
		if ( $is_bonded_pair ) {
			$combined_title .= ' <span class="pfoa-adoption-event-bonded-badge">' . esc_html__( 'Bonded Pair', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'adopted together as a bonded pair', 'pfoa-theme' ) . '</span></span>';
		} elseif ( $is_adopted_together ) {
			$combined_title .= ' <span class="pfoa-adoption-event-together-badge">' . esc_html__( 'Adopted Together', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'in the same adoption event', 'pfoa-theme' ) . '</span></span>';
		}

			$combined_title .= '</h2>';
		}

		// PFOA 0.1.121 — presentation only: consistent visible historical
		// adoption date beneath the event title (AG metadata). Sourced from
		// the already-read stored `_pfoa_event_date`; no new reads, no
		// writes, no domain changes. Unparseable values render raw.
		$event_date_text = pfoa_adoption_event_format_date( isset( $event['date'] ) ? $event['date'] : '' );
		$event_date_html = '';

		if ( '' !== $event_date_text ) {
			$event_date_html = '<p class="pfoa-adoption-event-date">' . esc_html( $event_date_text ) . '</p>';
		}

		// PFOA 0.1.125 — presentation only: every event renders members
		// first, then the combined title + date below the photographs
		// (image->names for singles and multis alike). Solo titles/dates
		// previously rendered above the member figure, outside the only
		// bordered box, so six solos showed name + date outside the card
		// while multis enclosed all correctly. Stored captions, names,
		// order, links/lightbox, and metadata are unchanged.
		$single_name = '';

		if ( 1 === $member_count && ! empty( $names ) ) {
			$single_name = (string) $names[0];
		}

		$events_html .= '<div class="pfoa-adoption-event-members pfoa-adoption-event-members-' . $count_class . '">';

		foreach ( $event['members'] as $event_member ) {
			if ( 0 < $event_member['image_id'] ) {
				$claimed_ids[ $event_member['image_id'] ] = true;
			}

			$events_html .= '<figure class="pfoa-adoption-event-member">';
			$events_html .= '<div class="pfoa-adoption-event-thumb">' . pfoa_adoption_event_member_media( $event_member['image_id'], $event_member['caption'] ) . '</div>';

			// PFOA 0.1.75 — presentation only: multi-member events show the
			// combined heading only; per-member figcaptions are suppressed
			// from markup while stored snapshots stay untouched.
			// PFOA 0.1.125 — presentation only: single-cat events keep
			// their caption in markup always (alt, data-pfoa-caption, and
			// figcaption preserved for lightbox/a11y). When the stored
			// caption redundantly repeats the solo title name, hide it
			// visually with screen-reader-text only — never delete.
			if ( '' !== $event_member['caption'] && 1 >= $member_count ) {
				$caption_class = 'pfoa-adoption-event-caption';

				if ( '' !== $single_name && 0 === strcasecmp( trim( (string) $event_member['caption'] ), trim( $single_name ) ) ) {
					$caption_class .= ' screen-reader-text';
				}

				$events_html .= '<figcaption class="' . $caption_class . '">' . esc_html( $event_member['caption'] ) . '</figcaption>';
			}

			$events_html .= '</figure>';
		}

		$events_html .= '</div>';

		if ( '' !== $combined_title ) {
			$events_html .= $combined_title . $event_date_html;
		}

		$events_html .= '</div>';
	}

	$events_html .= '</section>';

	// PFOA 0.1.120 — presentation only: collect the validated explicit
	// anchors (member $claimed_ids stays untouched). Anchors join discovery
	// + removal only (suppression-only since 0.1.126, never placement), so
	// a valid anchor's standalone .gallery-item is suppressed while the
	// grouped event still renders its own member images inside
	// .pfoa-adoption-event. Media Library attachments are never deleted,
	// only DOM-removed.
	$anchor_ids = array();

	foreach ( $events as $event ) {
		$event_anchor = isset( $event['legacy_anchor'] ) ? (int) $event['legacy_anchor'] : 0;

		if ( 0 >= $event_anchor || isset( $anchor_ids[ $event_anchor ] ) ) {
			continue;
		}

		$anchor_ids[ $event_anchor ] = true;
	}

	$discovery_ids = $claimed_ids;

	foreach ( $anchor_ids as $anchor_id => $ignored ) {
		$discovery_ids[ $anchor_id ] = true;
	}

	// Step 4: suppress claimed figures from the rendered legacy gallery
	// (exact attachment-ID match only) and insert event wrappers
	// chronologically into the canonical container below (PFOA 0.1.79
	// shared grid; fallback: before remaining content). Without DOM support
	// the events still render (prepended) while the legacy gallery is left
	// untouched.
	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		return $events_html . $content;
	}

	$prev_libxml = libxml_use_internal_errors( true );

	$dom = new DOMDocument( '1.0', 'UTF-8' );

	if ( function_exists( 'mb_encode_numericentity' ) ) {
		$fragment = mb_encode_numericentity( $content, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' );
	} else {
		$fragment = $content;
	}

	$loaded = $dom->loadHTML(
		'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="pfoa-adoption-events-wrapper">' . $fragment . '</div></body></html>'
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $events_html . $content;
	}

	$xpath = new DOMXPath( $dom );

	$wrapper_list = $xpath->query( '//div[@id="pfoa-adoption-events-wrapper"]' );

	if ( ! $wrapper_list instanceof DOMNodeList || 0 === $wrapper_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $events_html . $content;
	}

	$wrapper = $wrapper_list->item( 0 );

	// Removal uses the same discovery rules as the legacy presentation
	// (figure/dl.gallery-item as a direct child of .gallery, attachment ID
	// only from figcaption[id^=gallery-N-ID] and/or img[aria-describedby])
	// but strips every exact claimed match: no workflow claims, no
	// cat/name/caption/adjacency dedupe, no ambiguity logic.
	// attachment IDs found in the legacy scan are suppressed by exact
	// match only; scan positions never influence event placement.
	if ( ! empty( $discovery_ids ) ) {
		$item_list = $xpath->query(
			'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]',
			$wrapper
		);

		if ( $item_list instanceof DOMNodeList && 0 < $item_list->length ) {
			$removable = array();

			foreach ( $item_list as $item_node ) {

				if ( ! $item_node instanceof DOMElement ) {
					continue;
				}

				$tag = strtolower( $item_node->nodeName );

				if ( 'figure' !== $tag && 'dl' !== $tag ) {
					continue;
				}

				$parent = $item_node->parentNode;

				if ( ! $parent instanceof DOMElement ) {
					continue;
				}

				$parent_class = ' ' . preg_replace( '/\s+/', ' ', (string) $parent->getAttribute( 'class' ) ) . ' ';

				if ( false === strpos( $parent_class, ' gallery ' ) ) {
					continue;
				}

			// PFOA Senior Shadow hardening (0.1.143) — presentation only: single
			// auditable identity result per legacy item, fail-closed.
			// pfoa_collect_gallery_item_identity() gathers EVERY recognized
			// occurrence (caption ids, ARIA IDREF lists, wp-image- class
			// tokens, img/figure/item data attributes, EVERY ?attachment_id
			// query parameter) into one result distinguishing valid IDs from
			// recognized-but-invalid occurrences; absent/unrelated signals
			// are ignored and never counted. No numeric sentinels anywhere:
			// recognized-but-invalid evidence is counted, never encoded as
			// an ID, so malformed input can never match discovery and never
			// silently vanishes. Suppress only when every recognized
			// occurrence is valid, all valid IDs agree (exactly 1 distinct),
			// and that ID is Event-claimed in $discovery_ids below. Any
			// invalid occurrence or conflict preserves the item via continue
			// (fail-closed), including primary caption/aria vs fallback
			// disagreement and disagreement within a single source. Gate
			// removal identically; no name/caption/src-basename fuzzy match.
			// Covers the wp-image-, data-attachment-id, and attachment_id=
			// fallbacks inside the unanimous set.
			$identity = pfoa_collect_gallery_item_identity( $item_node, $xpath );

			if ( 0 < $identity['invalid'] ) {
				continue;
			}

			$identity_distinct = array_keys( $identity['valid_ids'] );

			if ( 1 !== count( $identity_distinct ) ) {
				continue;
			}

			$attachment_id = (int) $identity_distinct[0];

				// PFOA 0.1.120 — presentation only: discovery/removal covers
				// member claims plus validated explicit anchors (same
				// attachment-ID rules; anchors never collide with member IDs
				// after the fail-closed validation above).
				if ( 0 < $attachment_id && isset( $discovery_ids[ $attachment_id ] ) ) {
					$removable[] = $item_node;
				}
			}

			// PFOA 0.1.126 — presentation only: removal without insertion
			// references. Legacy anchors are suppression-only (AG-03/AG-04);
			// placement below is chronological and never consumes anchor
			// positions, so no successor reference is resolved here.
			foreach ( $removable as $removable_node ) {
				if ( null !== $removable_node->parentNode ) {
					$removable_node->parentNode->removeChild( $removable_node );
				}
			}
		}
	}

	// PFOA 0.1.126 — presentation only: legacy anchors are
	// suppression-only and never set position. The validated anchors above
	// join discovery + removal (a valid anchor's standalone gallery item is
	// suppressed while its grouped event renders its own member images), but
	// no per-event anchor position is derived: placement below iterates
	// $events directly in adoption-date-DESC order (AG-03/AG-04/AG-08).

	// Import each event wrapper individually through a second document so
	// snapshot captions/names survive encoding intact. The outer section is
	// not imported (shared-grid flow); each
	// div.pfoa-adoption-event[data-pfoa-adoption-event] enters the legacy
	// gallery on its own so one wrapper stays one logical/lightbox group
	// and events never merge. The section's "Adopted cats" labeling moves
	// onto each wrapper as role="group" + aria-label (the section element
	// itself is dropped, never flattened via CSS).
	$event_dom = new DOMDocument( '1.0', 'UTF-8' );

	if ( function_exists( 'mb_encode_numericentity' ) ) {
		$event_fragment = mb_encode_numericentity( $events_html, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' );
	} else {
		$event_fragment = $events_html;
	}

	$event_loaded = $event_dom->loadHTML(
		'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="pfoa-adoption-events-insert">' . $event_fragment . '</div></body></html>'
	);

	$event_nodes  = array();
	$event_labels = array();

	foreach ( $events as $event ) {
		$label_names = array();

		foreach ( $event['members'] as $event_member ) {
			if ( '' !== $event_member['name'] ) {
				$label_names[] = $event_member['name'];
			}
		}

		$label_date = pfoa_adoption_event_format_date( isset( $event['date'] ) ? $event['date'] : '' );
		$label_text = empty( $label_names ) ? 'Adopted cats' : 'Adopted cats: ' . implode( ' & ', $label_names );

		if ( '' !== $label_date ) {
			$label_text .= ' — ' . $label_date;
		}

		$event_labels[ $event['uuid'] ] = $label_text;
	}

	if ( $event_loaded ) {
		$event_xpath = new DOMXPath( $event_dom );

		$event_wrapper_list = $event_xpath->query( '//div[@id="pfoa-adoption-events-insert"]' );

		if ( $event_wrapper_list instanceof DOMNodeList && 0 < $event_wrapper_list->length ) {
			$event_wrapper = $event_wrapper_list->item( 0 );

			$event_item_list = $event_xpath->query(
				'.//div[contains(concat(" ", normalize-space(@class), " "), " pfoa-adoption-event ") and @data-pfoa-adoption-event]',
				$event_wrapper
			);

			if ( $event_item_list instanceof DOMNodeList && 0 < $event_item_list->length ) {
				foreach ( $event_item_list as $event_item ) {
					if ( ! $event_item instanceof DOMElement ) {
						continue;
					}

					$item_uuid = trim( (string) $event_item->getAttribute( 'data-pfoa-adoption-event' ) );

					if ( '' === $item_uuid || isset( $event_nodes[ $item_uuid ] ) ) {
						continue;
					}

					$imported = $dom->importNode( $event_item, true );

					if ( ! $imported instanceof DOMElement ) {
						continue;
					}

					$imported->setAttribute( 'role', 'group' );

					if ( isset( $event_labels[ $item_uuid ] ) ) {
						$imported->setAttribute( 'aria-label', $event_labels[ $item_uuid ] );
					}

					$event_nodes[ $item_uuid ] = $imported;
				}
			}
		}
	}

	if ( empty( $event_nodes ) ) {
		$output = '';

		foreach ( $wrapper->childNodes as $child ) {
			$output .= $dom->saveHTML( $child );
		}

		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );

		return $events_html . ( '' !== $output ? $output : $content );
	}

	// PFOA 0.1.126 — presentation only: chronological sequential insertion
	// (AG-03 strict reverse chronology, AG-04 immutable sequence, AG-08
	// chronology-first packing). Events enter the canonical container —
	// the first remaining .gallery under the wrapper — consecutively in
	// $events order (adoption date DESC, event post ID DESC), which is the
	// visual reading order. Legacy scan positions NEVER influence
	// placement: anchors are suppression-only (see discovery above) and
	// cannot displace, reorder, or interleave events. Legacy-only
	// survivors (unclaimed gallery items, which carry no event dates) are
	// quarantined AFTER all events in their original relative order, so
	// they can never invent chronology by interleaving. Other galleries on
	// the page are left untouched; DOM order equals visual reading order.
	$canonical_gallery = null;

	$gallery_list = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery ")]',
		$wrapper
	);

	if ( $gallery_list instanceof DOMNodeList && 0 < $gallery_list->length ) {
		foreach ( $gallery_list as $gallery_node ) {
			if ( $gallery_node instanceof DOMElement ) {
				$canonical_gallery = $gallery_node;
				break;
			}
		}
	}

	if ( $canonical_gallery instanceof DOMElement ) {
		// Snapshot surviving gallery items inside the canonical container
		// in document order first (DOMNodeList is live), detach them, then
		// re-append events followed by survivors in original relative
		// order. Items in any other gallery stay exactly where they are.
		$survivor_snapshot = array();

		$survivor_list = $xpath->query(
			'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]',
			$canonical_gallery
		);

		if ( $survivor_list instanceof DOMNodeList && 0 < $survivor_list->length ) {
			foreach ( $survivor_list as $survivor_node ) {
				$survivor_snapshot[] = $survivor_node;
			}
		}

		$survivors = array();

		foreach ( $survivor_snapshot as $survivor_node ) {
			if ( ! $survivor_node instanceof DOMElement ) {
				continue;
			}

			$survivor_tag = strtolower( $survivor_node->nodeName );

			if ( 'figure' !== $survivor_tag && 'dl' !== $survivor_tag ) {
				continue;
			}

			if ( null !== $survivor_node->parentNode ) {
				$survivor_node->parentNode->removeChild( $survivor_node );
			}

			$survivors[] = $survivor_node;
		}

		foreach ( $events as $event ) {
			if ( ! isset( $event_nodes[ $event['uuid'] ] ) ) {
				continue;
			}

			$canonical_gallery->appendChild( $event_nodes[ $event['uuid'] ] );
		}

		foreach ( $survivors as $survivor_node ) {
			$canonical_gallery->appendChild( $survivor_node );
		}
	} else {
		// No gallery container: keep the prior fallback shape (events
		// before remaining content) but in chronological $events order.
		// Inserting each event before the same first-child reference keeps
		// the sequence stable; a single event is simply prepended.
		$fallback_ref = $wrapper->firstChild;

		foreach ( $events as $event ) {
			if ( ! isset( $event_nodes[ $event['uuid'] ] ) ) {
				continue;
			}

			$wrapper->insertBefore( $event_nodes[ $event['uuid'] ], $fallback_ref );
		}
	}

	$output = '';

	foreach ( $wrapper->childNodes as $child ) {
		$output .= $dom->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $prev_libxml );

	if ( '' === $output ) {
		return $events_html . $content;
	}

	return $output;
}

add_filter( 'the_content', 'pfoa_present_adoption_events', 950 );

/**
 * Adopted 2026 gallery identity — presentation only.
 *
 * Page-scoped wp_get_attachment_image_attributes filter that adds the
 * authoritative attachment identity (data-attachment-id) from the supplied
 * attachment WP_Post->ID before gallery HTML reaches the presenter, so the
 * caption-less legacy fallback can see the real captionless item without
 * guessing from filenames or captions. Additive attribute only: src, class,
 * alt, href, and lightbox behavior are untouched. Out of scope, non-array,
 * or non-positive-ID inputs return $attr unchanged. No storage writes.
 */
if ( ! function_exists( 'pfoa_adopted2026_gallery_image_attributes' ) ) {
	function pfoa_adopted2026_gallery_image_attributes( $attr, $attachment, $size = '' ) {
		if ( ! is_array( $attr ) ) {
			return $attr;
		}

		$attachment_id = 0;

		if ( $attachment instanceof WP_Post ) {
			$attachment_id = (int) $attachment->ID;
		}

		if ( 0 >= $attachment_id ) {
			return $attr;
		}

		$is_target_page = false;

		if ( function_exists( 'is_page' ) && ( is_page( 22514 ) || is_page( 'adopted2026' ) ) ) {
			$is_target_page = true;
		} elseif ( function_exists( 'get_post' ) ) {
			$post = get_post();

			if ( $post instanceof WP_Post ) {
				if ( 22514 === (int) $post->ID ) {
					$is_target_page = true;
				} elseif ( isset( $post->post_name ) && 'adopted2026' === $post->post_name ) {
					$is_target_page = true;
				}
			}
		}

		if ( ! $is_target_page ) {
			return $attr;
		}

		$attr['data-attachment-id'] = (string) $attachment_id;

		return $attr;
	}
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'wp_get_attachment_image_attributes', 'pfoa_adopted2026_gallery_image_attributes', 10, 3 );
}

/**
 * Currency + liveness resolver for one Recently Adopted member snapshot.
 *
 * Read-only: never writes postmeta/options. Returns true (keep), false
 * (exclude), or null (keep fail-open + reported). Results are cached per
 * cat_id + candidate event ID in $filter_state['cache']; unresolvable or
 * excluded members append {code,event_id,cat_post_id,detail} to
 * $filter_state['report'].
 *
 * Currency first: when pfoa_cat_get_current_adoption_event_id() exists and
 * returns > 0, the cat appears ONLY under that event ID (mismatch =
 * superseded/false). Otherwise forward-scan fallback over the selector's
 * currency map (newest valid event containing the cat, date DESC ID DESC).
 *
 * Liveness second: pfoa_cat_is_adopted() when available is definitive;
 * otherwise a stored '_pfoa_cat_adopted' === '1' proves adopted; otherwise
 * pfoa_cat_get_profile_data() lifecycle.eligible_for_adoptable === true
 * proves returned. Absence of proof is NOT proof of return: fail-open null
 * + 'snapshot-unavailable'. cat_post_id 0 snapshots are retained + reported
 * 'unresolvable-anonymous'. effectively_pending/record_status are never
 * used as filters.
 *
 * @param int   $cat_id             Member cat_post_id (0 = anonymous snapshot).
 * @param int   $candidate_event_id Valid event ID holding this snapshot.
 * @param array $filter_state       In/out: currency_map, cache, report.
 * @return bool|null True keep, false exclude, null keep fail-open.
 */
function pfoa_recently_adopted_is_currently_adopted( $cat_id, $candidate_event_id, &$filter_state ) {
	$cat_id             = (int) $cat_id;
	$candidate_event_id = (int) $candidate_event_id;

	if ( ! is_array( $filter_state ) ) {
		$filter_state = array();
	}

	if ( ! isset( $filter_state['currency_map'] ) || ! is_array( $filter_state['currency_map'] ) ) {
		$filter_state['currency_map'] = array();
	}

	if ( ! isset( $filter_state['cache'] ) || ! is_array( $filter_state['cache'] ) ) {
		$filter_state['cache'] = array();
	}

	if ( ! isset( $filter_state['report'] ) || ! is_array( $filter_state['report'] ) ) {
		$filter_state['report'] = array();
	}

	if ( 0 >= $cat_id ) {
		$filter_state['report'][] = array(
			'code'        => 'unresolvable-anonymous',
			'event_id'    => $candidate_event_id,
			'cat_post_id' => 0,
			'detail'      => 'Member snapshot carries no cat_post_id; retained fail-open.',
		);

		return null;
	}

	$cache_key = $cat_id . ':' . $candidate_event_id;

	if ( array_key_exists( $cache_key, $filter_state['cache'] ) ) {
		return $filter_state['cache'][ $cache_key ];
	}

	// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP.
	if ( function_exists( 'pfoa_dev_synthetic_is_excluded_cat' ) && pfoa_dev_synthetic_is_excluded_cat( $cat_id ) ) {
		$filter_state['report'][] = array(
			'code'        => 'dev-synthetic-excluded',
			'event_id'    => $candidate_event_id,
			'cat_post_id' => $cat_id,
			'detail'      => 'DEV-only temporary synthetic QA exclusion; record preserved.',
		);

		$filter_state['cache'][ $cache_key ] = false;

		return false;
	}

	// Currency: authoritative current-event link first, forward-scan map
	// (newest valid event containing the cat) otherwise.
	$current_event_id = 0;
	$currency_source  = 'forward-scan';

	if ( function_exists( 'pfoa_cat_get_current_adoption_event_id' ) ) {
		$current_event_id = (int) pfoa_cat_get_current_adoption_event_id( $cat_id );
		$currency_source  = 'current-event-link';
	}

	if ( 0 >= $current_event_id && isset( $filter_state['currency_map'][ $cat_id ] ) ) {
		$current_event_id = (int) $filter_state['currency_map'][ $cat_id ];
		$currency_source  = 'forward-scan';
	}

	if ( 0 < $current_event_id && $current_event_id !== $candidate_event_id ) {
		$filter_state['report'][] = array(
			'code'        => 'superseded',
			'event_id'    => $candidate_event_id,
			'cat_post_id' => $cat_id,
			'detail'      => 'Current adoption event is ' . $current_event_id . ' (' . $currency_source . '); candidate ' . $candidate_event_id . ' superseded.',
		);

		$filter_state['cache'][ $cache_key ] = false;

		return false;
	}

	// Liveness (return detection): plugin helper is definitive when present.
	if ( function_exists( 'pfoa_cat_is_adopted' ) ) {
		$adopted = (bool) pfoa_cat_is_adopted( $cat_id );

		if ( ! $adopted ) {
			$filter_state['report'][] = array(
				'code'        => 'returned',
				'event_id'    => $candidate_event_id,
				'cat_post_id' => $cat_id,
				'detail'      => 'pfoa_cat_is_adopted() is false; treated as returned.',
			);
		}

		$filter_state['cache'][ $cache_key ] = $adopted;

		return $adopted;
	}

	// Without the helper, a stored flag still proves adopted, but its
	// absence proves nothing (legacy/backfill snapshots may lack flags).
	if ( function_exists( 'get_post_meta' ) ) {
		$adopted_raw = get_post_meta( $cat_id, '_pfoa_cat_adopted', true );

		if ( '1' === (string) $adopted_raw ) {
			$filter_state['cache'][ $cache_key ] = true;

			return true;
		}
	}

	if ( function_exists( 'pfoa_cat_get_profile_data' ) ) {
		$profile_data = pfoa_cat_get_profile_data( $cat_id );

		if ( is_array( $profile_data ) && isset( $profile_data['lifecycle'] ) && is_array( $profile_data['lifecycle'] ) && array_key_exists( 'eligible_for_adoptable', $profile_data['lifecycle'] ) ) {
			if ( true === $profile_data['lifecycle']['eligible_for_adoptable'] ) {
				$filter_state['report'][] = array(
					'code'        => 'returned',
					'event_id'    => $candidate_event_id,
					'cat_post_id' => $cat_id,
					'detail'      => 'Profile lifecycle.eligible_for_adoptable is true; treated as returned.',
				);

				$filter_state['cache'][ $cache_key ] = false;

				return false;
			}

			$filter_state['cache'][ $cache_key ] = true;

			return true;
		}
	}

	$filter_state['report'][] = array(
		'code'        => 'snapshot-unavailable',
		'event_id'    => $candidate_event_id,
		'cat_post_id' => $cat_id,
		'detail'      => 'No liveness signal resolvable; retained fail-open.',
	);

	$filter_state['cache'][ $cache_key ] = null;

	return null;
}

/**
 * Recently adopted strip (page 20323): newest adoption events, cross-year.
 *
 * Presentation only, read-only wrt adoption events and cat profiles.
 * Queries published pfoa_adoption_event posts with NO year filter and NO
 * filter on current Adoptable/Adopted state, keeps only events with a valid
 * historical YYYY-MM-DD adoption date, orders by adoption date DESC +
 * event post ID DESC (same deterministic rule as
 * pfoa_present_adoption_events()), then applies return/re-adoption
 * filtering per event type via pfoa_recently_adopted_is_currently_adopted()
 * (singletons and bonded pairs drop whole on any false member;
 * Adopted-Together multi events drop only false members), and fills
 * forward past excluded events until $limit survivors are collected
 * (fewer when fewer qualify). Never slice-then-filter. No hardcoded IDs.
 *
 * Membership, bonding, photos, and dates are NOT reinterpreted here: member
 * snapshots use the canonical pfoa_adoption_event_get_* readers (with the
 * same _pfoa_event_* fallbacks), the stored member order
 * (image_id/caption/cat_name_at_event/cat_post_id) is preserved, and
 * rendering reuses pfoa_adoption_event_member_media() and
 * pfoa_adoption_event_format_date(). Bonded-pair classification is
 * preserved in each event's 'bonded_pair' data via the same strict
 * reciprocal helpers, but the public badge is always the green "Adopted
 * Together" on multi-cat events; singletons stay badge-free.
 *
 * @param int $limit Max events to return (default 4).
 * @return array Sorted (date DESC, ID DESC) event arrays, at most $limit.
 */
function pfoa_get_recently_adopted_events( $limit = 4 ) {
	$limit = (int) $limit;

	if ( 1 > $limit ) {
		$limit = 4;
	}

	if ( ! function_exists( 'get_posts' ) || ! function_exists( 'get_post_meta' ) ) {
		return array();
	}

	// Same query shape as pfoa_present_adoption_events() but with NO year
	// meta filter: cross-year coverage, final ordering applied in PHP below.
	$event_ids = get_posts(
		array(
			'post_type'      => 'pfoa_adoption_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	if ( ! is_array( $event_ids ) || empty( $event_ids ) ) {
		return array();
	}

	$today      = function_exists( 'current_time' ) ? (string) current_time( 'Y-m-d' ) : gmdate( 'Y-m-d' );
	$candidates = array();

	foreach ( $event_ids as $event_id_raw ) {
		$event_id = (int) $event_id_raw;

		if ( 0 >= $event_id ) {
			continue;
		}

		// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP.
		if ( function_exists( 'pfoa_dev_synthetic_is_excluded_event' ) && pfoa_dev_synthetic_is_excluded_event( $event_id ) ) {
			continue;
		}

		if ( function_exists( 'get_post' ) ) {
			$event_post = get_post( $event_id );

			if ( ! $event_post instanceof WP_Post || 'pfoa_adoption_event' !== $event_post->post_type ) {
				continue;
			}
		}

		// Canonical date reader preferred; raw meta fallback (mirrors
		// pfoa_present_adoption_events()). Only strict YYYY-MM-DD
		// historical dates qualify.
		if ( function_exists( 'pfoa_adoption_event_get_date' ) ) {
			$event_date = (string) pfoa_adoption_event_get_date( $event_id );
		} else {
			$event_date = (string) get_post_meta( $event_id, '_pfoa_event_date', true );
		}

		$event_date = trim( $event_date );

		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event_date ) ) {
			continue;
		}

		$event_timestamp = strtotime( $event_date );

		if ( false === $event_timestamp ) {
			continue;
		}

		// Reject impossible calendar dates (e.g. 2026-02-30, which
		// strtotime() would roll over) and future-dated events.
		if ( gmdate( 'Y-m-d', $event_timestamp ) !== $event_date ) {
			continue;
		}

		if ( $event_date > $today ) {
			continue;
		}

		if ( function_exists( 'pfoa_adoption_event_get_uuid' ) ) {
			$event_uuid = (string) pfoa_adoption_event_get_uuid( $event_id );
		} else {
			$event_uuid = (string) get_post_meta( $event_id, '_pfoa_event_uuid', true );
		}

		$event_uuid = trim( $event_uuid );

		if ( '' === $event_uuid ) {
			continue;
		}

		if ( function_exists( 'pfoa_adoption_event_get_members' ) ) {
			$members_raw = pfoa_adoption_event_get_members( $event_id );
		} else {
			$members_raw = get_post_meta( $event_id, '_pfoa_event_members', true );
		}

		if ( ! is_array( $members_raw ) || empty( $members_raw ) ) {
			continue;
		}

		// Member snapshot shape mirrors pfoa_present_adoption_events():
		// stored order, image_id, caption (fallback cat_name_at_event),
		// name, cat_post_id. No profile reads, no writes.
		$members = array();

		foreach ( array_values( $members_raw ) as $member_raw ) {
			if ( ! is_array( $member_raw ) ) {
				continue;
			}

			$member_image = isset( $member_raw['image_id'] ) ? (int) $member_raw['image_id'] : 0;
			$member_name  = isset( $member_raw['cat_name_at_event'] ) ? trim( (string) $member_raw['cat_name_at_event'] ) : '';
			$member_cap   = isset( $member_raw['caption'] ) ? trim( (string) $member_raw['caption'] ) : '';
			$member_cat   = isset( $member_raw['cat_post_id'] ) ? (int) $member_raw['cat_post_id'] : 0;

			if ( '' === $member_cap ) {
				$member_cap = $member_name;
			}

			$members[] = array(
				'image_id'    => 0 < $member_image ? $member_image : 0,
				'caption'     => $member_cap,
				'name'        => $member_name,
				'cat_post_id' => 0 < $member_cat ? $member_cat : 0,
			);
		}

		if ( empty( $members ) ) {
			continue;
		}

		// PFOA DEV-ONLY TEMPORARY SYNTHETIC EXCLUSION — REMOVE AT FINAL CLEANUP.
		if ( function_exists( 'pfoa_dev_synthetic_is_excluded_cat' ) ) {
			$dev_synth_hit = false;

			foreach ( $members as $dev_synth_member ) {
				if ( is_array( $dev_synth_member ) && isset( $dev_synth_member['cat_post_id'] ) && pfoa_dev_synthetic_is_excluded_cat( (int) $dev_synth_member['cat_post_id'] ) ) {
					$dev_synth_hit = true;
					break;
				}
			}

			if ( $dev_synth_hit ) {
				continue;
			}
		}

		// Bonded-pair classification preserved in data exactly as in
		// pfoa_present_adoption_events(): PFOA 0.1.209 routes the
		// predicate through pfoa_adoption_event_is_bonded_pair_for_display()
		// (valid stored classification wins; absent falls back to the
		// strict helpers fail-closed); 3+ member events never bonded. The
		// public badge below is conditional (0.1.86): red "Bonded Pair"
		// for true bonded pairs, green "Adopted Together" for non-bonded
		// multi-cat events, none for singletons.
		$member_count   = count( $members );
		$is_bonded_pair = false;

		if ( 2 === $member_count ) {
			$bond_ids = array();

			foreach ( $members as $bond_member ) {
				$bond_ids[] = ( is_array( $bond_member ) && isset( $bond_member['cat_post_id'] ) ) ? (int) $bond_member['cat_post_id'] : 0;
			}

			$is_bonded_pair = pfoa_adoption_event_is_bonded_pair_for_display( $event_id, $bond_ids );
		}

		$candidates[] = array(
			'id'          => $event_id,
			'date'        => $event_date,
			'uuid'        => $event_uuid,
			'members'     => $members,
			'bonded_pair' => $is_bonded_pair,
		);
	}

	if ( empty( $candidates ) ) {
		return array();
	}

	// Same deterministic rule as pfoa_present_adoption_events(): adoption
	// date DESC, event post ID DESC tie-break.
	usort(
		$candidates,
		function ( $a, $b ) {
			$date_cmp = strcmp( (string) $b['date'], (string) $a['date'] );

			if ( 0 !== $date_cmp ) {
				return $date_cmp;
			}

			if ( (int) $b['id'] === (int) $a['id'] ) {
				return 0;
			}

			return (int) $b['id'] > (int) $a['id'] ? 1 : -1;
		}
	);

	// Currency map over ALL valid events (newest first wins): cat_post_id
	// => newest valid event ID containing it. Feeds the forward-scan
	// fallback inside pfoa_recently_adopted_is_currently_adopted().
	$filter_state = array(
		'currency_map' => array(),
		'cache'        => array(),
		'report'       => array(),
	);

	foreach ( $candidates as $candidate_event ) {
		if ( ! is_array( $candidate_event ) || ! isset( $candidate_event['members'] ) || ! is_array( $candidate_event['members'] ) ) {
			continue;
		}

		foreach ( $candidate_event['members'] as $map_member ) {
			$map_cat = ( is_array( $map_member ) && isset( $map_member['cat_post_id'] ) ) ? (int) $map_member['cat_post_id'] : 0;

			if ( 0 < $map_cat && ! isset( $filter_state['currency_map'][ $map_cat ] ) ) {
				$filter_state['currency_map'][ $map_cat ] = (int) $candidate_event['id'];
			}
		}
	}

	// Per-type filtering in sorted order, collecting survivors until the
	// limit (fill past exclusions; never slice-then-filter).
	$survivors = array();

	foreach ( $candidates as $candidate_event ) {
		if ( count( $survivors ) >= $limit ) {
			break;
		}

		if ( ! is_array( $candidate_event ) || ! isset( $candidate_event['members'] ) || ! is_array( $candidate_event['members'] ) || empty( $candidate_event['members'] ) ) {
			continue;
		}

		$event_members = array_values( $candidate_event['members'] );
		$member_count  = count( $event_members );
		$event_id      = (int) $candidate_event['id'];

		if ( 1 === $member_count ) {
			$single_cat = isset( $event_members[0]['cat_post_id'] ) ? (int) $event_members[0]['cat_post_id'] : 0;

			if ( false === pfoa_recently_adopted_is_currently_adopted( $single_cat, $event_id, $filter_state ) ) {
				continue;
			}

			$survivors[] = $candidate_event;
			continue;
		}

		if ( 2 === $member_count && ! empty( $candidate_event['bonded_pair'] ) ) {
			$bond_a = isset( $event_members[0]['cat_post_id'] ) ? (int) $event_members[0]['cat_post_id'] : 0;
			$bond_b = isset( $event_members[1]['cat_post_id'] ) ? (int) $event_members[1]['cat_post_id'] : 0;

			// Bonded pairs keep whole or drop whole; never downgrade to a
			// singleton.
			if ( false === pfoa_recently_adopted_is_currently_adopted( $bond_a, $event_id, $filter_state ) ) {
				continue;
			}

			if ( false === pfoa_recently_adopted_is_currently_adopted( $bond_b, $event_id, $filter_state ) ) {
				continue;
			}

			$survivors[] = $candidate_event;
			continue;
		}

		// Adopted-Together (multi, not a strict bonded pair): drop only
		// false members, retain true/null in stored order. The bonded_pair
		// flag is left as-is so badge/count follow automatically.
		$kept_members = array();

		foreach ( $event_members as $together_member ) {
			$together_cat = ( is_array( $together_member ) && isset( $together_member['cat_post_id'] ) ) ? (int) $together_member['cat_post_id'] : 0;

			if ( false !== pfoa_recently_adopted_is_currently_adopted( $together_cat, $event_id, $filter_state ) ) {
				$kept_members[] = $together_member;
			}
		}

		if ( empty( $kept_members ) ) {
			continue;
		}

		$candidate_event['members'] = array_values( $kept_members );
		$survivors[]                = $candidate_event;
	}

	if ( function_exists( 'apply_filters' ) ) {
		apply_filters( 'pfoa_recently_adopted_unresolvable', $filter_state['report'] );
	}

	$GLOBALS['pfoa_recently_adopted_unresolvable_report'] = $filter_state['report'];

	if ( ! empty( $filter_state['report'] ) && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		$report_json = function_exists( 'wp_json_encode' ) ? (string) wp_json_encode( $filter_state['report'] ) : (string) json_encode( $filter_state['report'] );
		error_log( 'pfoa_recently_adopted_unresolvable: ' . $report_json ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	return $survivors;
}

/**
 * Render one recently-adopted event card.
 *
 * Presentation only: card markup mirrors pfoa_present_adoption_events()
 * (same div.pfoa-adoption-event[.multi][data-pfoa-adoption-event] wrapper,
 * combined ' & ' title, conditional badge (red "Bonded Pair" for true
 * bonded pairs, green "Adopted Together" for non-bonded multi-cat
 * events, none for singletons), historical date line, member grid via
 * pfoa_adoption_event_member_media()). role="group" + aria-label are set
 * inline here because the shortcode path has no DOM-insertion pass.
 *
 * @param array $event Event array from pfoa_get_recently_adopted_events().
 * @return string Card markup (escaped) or '' when unusable.
 */
function pfoa_render_recently_adopted_event_card( $event ) {
	if ( ! is_array( $event ) || empty( $event['members'] ) || ! is_array( $event['members'] ) ) {
		return '';
	}

	if ( ! isset( $event['uuid'] ) || '' === trim( (string) $event['uuid'] ) ) {
		return '';
	}

	$member_count   = count( $event['members'] );
	$count_class    = 3 < $member_count ? 'n' : (string) $member_count;
	$is_bonded_pair = ! empty( $event['bonded_pair'] );

	$names = array();

	foreach ( $event['members'] as $event_member ) {
		if ( is_array( $event_member ) && isset( $event_member['name'] ) && '' !== $event_member['name'] ) {
			$names[] = $event_member['name'];
		}
	}

	$multi_class    = 1 < $member_count ? ' pfoa-adoption-event-multi pfoa-adoption-event-count-' . $count_class : ' pfoa-adoption-event-count-1';
	$bonded_class   = $is_bonded_pair ? ' pfoa-adoption-event--bonded' : '';
	$together_class = ( 2 <= $member_count && ! $is_bonded_pair ) ? ' pfoa-adoption-event--together' : '';

	$label_date = pfoa_adoption_event_format_date( isset( $event['date'] ) ? $event['date'] : '' );
	$label_text = empty( $names ) ? 'Adopted cats' : 'Adopted cats: ' . implode( ' & ', $names );

	if ( '' !== $label_date ) {
		$label_text .= ' — ' . $label_date;
	}

	$card_html = '<div class="pfoa-adoption-event' . $multi_class . $bonded_class . $together_class . '" data-pfoa-adoption-event="' . esc_attr( $event['uuid'] ) . '" role="group" aria-label="' . esc_attr( $label_text ) . '">';

	$combined_title = '';

	if ( ! empty( $names ) ) {
		$combined_title = '<h2 class="pfoa-adoption-event-title">' . implode( ' &amp; ', array_map( 'esc_html', $names ) );

		// Conditional badge (0.1.86 parity with pfoa_present_adoption_events):
		// true bonded pair => red Bonded Pair; non-bonded multi-cat
		// (2+ incl. 3+) => green Adopted Together; singleton => no badge.
		// Classification ($is_bonded_pair from upstream bonded_pair data)
		// is untouched — presentation only.
		if ( $is_bonded_pair ) {
			$combined_title .= ' <span class="pfoa-adoption-event-bonded-badge">' . esc_html__( 'Bonded Pair', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'adopted together as a bonded pair', 'pfoa-theme' ) . '</span></span>';
		} elseif ( 2 <= $member_count ) {
			$combined_title .= ' <span class="pfoa-adoption-event-together-badge">' . esc_html__( 'Adopted Together', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'in the same adoption event', 'pfoa-theme' ) . '</span></span>';
		}

		$combined_title .= '</h2>';
	}

	$event_date_text = pfoa_adoption_event_format_date( isset( $event['date'] ) ? $event['date'] : '' );
	$event_date_html = '';

	if ( '' !== $event_date_text ) {
		$event_date_html = '<p class="pfoa-adoption-event-date">' . esc_html( $event_date_text ) . '</p>';
	}

	$single_name = '';

	if ( 1 === $member_count && ! empty( $names ) ) {
		$single_name = (string) $names[0];
	}

	$card_html .= '<div class="pfoa-adoption-event-members pfoa-adoption-event-members-' . $count_class . '">';

	foreach ( $event['members'] as $event_member ) {
		if ( ! is_array( $event_member ) ) {
			continue;
		}

		$member_image   = isset( $event_member['image_id'] ) ? (int) $event_member['image_id'] : 0;
		$member_caption = isset( $event_member['caption'] ) ? (string) $event_member['caption'] : '';

		$card_html .= '<figure class="pfoa-adoption-event-member">';
		$card_html .= '<div class="pfoa-adoption-event-thumb">' . pfoa_adoption_event_member_media( $member_image, $member_caption ) . '</div>';

		if ( '' !== $member_caption && 1 >= $member_count ) {
			$caption_class = 'pfoa-adoption-event-caption';

			if ( '' !== $single_name && 0 === strcasecmp( trim( $member_caption ), trim( $single_name ) ) ) {
				$caption_class .= ' screen-reader-text';
			}

			$card_html .= '<figcaption class="' . $caption_class . '">' . esc_html( $member_caption ) . '</figcaption>';
		}

		$card_html .= '</figure>';
	}

	$card_html .= '</div>';

	if ( '' !== $combined_title ) {
		$card_html .= $combined_title . $event_date_html;
	}

	$card_html .= '</div>';

	return $card_html;
}

/**
 * Shortcode [pfoa_recently_adopted]: newest adoption events strip.
 *
 * Presentation only. Attributes: limit (default 4). Returns '' when no
 * qualifying events exist. Usable anywhere shortcodes render; intended for
 * page 20323 replacing the hardcoded recent-adoptions table.
 *
 * @param array|string $atts Shortcode attributes.
 * @return string Section markup (escaped) or ''.
 */
function pfoa_recently_adopted_events( $atts ) {
	$atts = shortcode_atts(
		array(
			'limit' => 4,
		),
		$atts,
		'pfoa_recently_adopted'
	);

	$events = pfoa_get_recently_adopted_events( isset( $atts['limit'] ) ? (int) $atts['limit'] : 4 );

	if ( empty( $events ) ) {
		return '';
	}

	$section_html = '<section class="pfoa-adoption-events pfoa-recently-adopted" aria-label="' . esc_attr__( 'Recently adopted cats', 'pfoa-theme' ) . '">';

	foreach ( $events as $event ) {
		$section_html .= pfoa_render_recently_adopted_event_card( $event );
	}

	$section_html .= '</section>';

	return $section_html;
}

add_shortcode( 'pfoa_recently_adopted', 'pfoa_recently_adopted_events' );

/**
 * Adopted 2026 gallery: group workflow-managed bonded pairs.
 *
 * Page-specific (page 22514 / adopted2026) companion to
 * pfoa_present_bonded_pairs(). Parses the rendered core gallery, maps each
 * rendered attachment to exactly one workflow-managed pfoa_cat row
 * (_pfoa_aw_adopted2026_image_id + managed id + inserted flag), validates the
 * canonical bonded pair via pfoa_cat_get_bonded_pair() symmetrically on both
 * sides, and wraps currently-adjacent figure pairs in a
 * div.pfoa-adopted-bonded-group. Moves existing nodes only; never clones IDs,
 * never re-sorts, never repairs, never uses raw bonded meta. Returns $content
 * unchanged on any failure and never throws/fatals.
 *
 * @param string $content Post content.
 * @return string Possibly-grouped content.
 */
function pfoa_present_adopted_bonded_pairs( $content ) {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( false === strpos( $content, 'gallery-item' ) ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 22514 ) || is_page( 'adopted2026' ) ) ) {
		$is_target_page = true;
	} elseif ( function_exists( 'get_post' ) ) {
		$post = get_post();

		if ( $post instanceof WP_Post ) {
			if ( 22514 === (int) $post->ID ) {
				$is_target_page = true;
			} elseif ( isset( $post->post_name ) && 'adopted2026' === $post->post_name ) {
				$is_target_page = true;
			}
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
		return $content;
	}

	if ( ! function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
		return $content;
	}

	if ( ! function_exists( 'get_posts' ) || ! function_exists( 'get_post_meta' ) ) {
		return $content;
	}

	$prev_libxml = libxml_use_internal_errors( true );

	$dom = new DOMDocument( '1.0', 'UTF-8' );

	if ( function_exists( 'mb_encode_numericentity' ) ) {
		$fragment = mb_encode_numericentity( $content, array( 0x80, 0x10FFFF, 0, 0x1FFFFF ), 'UTF-8' );
	} else {
		$fragment = $content;
	}

	$loaded = $dom->loadHTML(
		'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="pfoa-adopted-wrapper">' . $fragment . '</div></body></html>'
	);

	if ( ! $loaded ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$xpath = new DOMXPath( $dom );

	$wrapper_list = $xpath->query( '//div[@id="pfoa-adopted-wrapper"]' );

	if ( ! $wrapper_list instanceof DOMNodeList || 0 === $wrapper_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$wrapper = $wrapper_list->item( 0 );

	// Step 1: attachment discovery. Map attachment ID => figure.gallery-item
	// node for figures that are direct children of a .gallery container. The
	// ID comes only from figcaption[id="gallery-<instance>-<ID>"] and/or
	// img[aria-describedby="gallery-<instance>-<ID>"]; never from filename,
	// caption text, position, or parent. A classic dl.gallery-item / dd
	// caption fallback is accepted with the same ID rules.
	$item_list = $xpath->query(
		'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]',
		$wrapper
	);

	if ( ! $item_list instanceof DOMNodeList || 0 === $item_list->length ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$attachment_to_figure = array();
	$ambiguous_ids        = array();
	$ordered_items        = array();

	foreach ( $item_list as $item_node ) {
		if ( ! $item_node instanceof DOMElement ) {
			continue;
		}

		$tag = strtolower( $item_node->nodeName );

		if ( 'figure' !== $tag && 'dl' !== $tag ) {
			continue;
		}

		$parent = $item_node->parentNode;

		if ( ! $parent instanceof DOMElement ) {
			continue;
		}

		$parent_class = ' ' . preg_replace( '/\s+/', ' ', (string) $parent->getAttribute( 'class' ) ) . ' ';

		if ( false === strpos( $parent_class, ' gallery ' ) ) {
			continue;
		}

		$from_caption = 0;
		$from_img     = 0;

		$caption_list = $xpath->query( './/*[self::figcaption or self::dd][@id]', $item_node );

		if ( $caption_list instanceof DOMNodeList && 0 < $caption_list->length ) {
			foreach ( $caption_list as $caption_node ) {
				if ( ! $caption_node instanceof DOMElement ) {
					continue;
				}

				$caption_id = trim( (string) $caption_node->getAttribute( 'id' ) );

				if ( 1 === preg_match( '/^gallery-\d+-(\d+)$/', $caption_id, $caption_matches ) ) {
					$from_caption = (int) $caption_matches[1];
					break;
				}
			}
		}

		$img_list = $xpath->query( './/img[@aria-describedby]', $item_node );

		if ( $img_list instanceof DOMNodeList && 0 < $img_list->length ) {
			foreach ( $img_list as $img_node ) {
				if ( ! $img_node instanceof DOMElement ) {
					continue;
				}

				$describedby = trim( (string) $img_node->getAttribute( 'aria-describedby' ) );

				if ( 1 === preg_match( '/^gallery-\d+-(\d+)$/', $describedby, $img_matches ) ) {
					$from_img = (int) $img_matches[1];
					break;
				}
			}
		}

		// Both signals present but disagreeing means ambiguous: skip.
		if ( 0 < $from_caption && 0 < $from_img && $from_caption !== $from_img ) {
			continue;
		}

		$attachment_id = 0 < $from_caption ? $from_caption : $from_img;

		if ( 0 >= $attachment_id ) {
			continue;
		}

		if ( isset( $ambiguous_ids[ $attachment_id ] ) ) {
			continue;
		}

		if ( isset( $attachment_to_figure[ $attachment_id ] ) ) {
			// Rendered twice: attachment no longer maps 1:1, exclude it.
			unset( $attachment_to_figure[ $attachment_id ] );
			$ambiguous_ids[ $attachment_id ] = true;
			continue;
		}

		$attachment_to_figure[ $attachment_id ] = $item_node;
		$ordered_items[]                         = array(
			'att'  => $attachment_id,
			'node' => $item_node,
		);
	}

	if ( ! empty( $ambiguous_ids ) ) {
		$ordered_items = array_values(
			array_filter(
				$ordered_items,
				function ( $ordered_item ) use ( $ambiguous_ids ) {
					return ! isset( $ambiguous_ids[ $ordered_item['att'] ] );
				}
			)
		);
	}

	if ( empty( $attachment_to_figure ) || empty( $ordered_items ) ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	// Step 2: workflow -> cat mapping with a single bounded lookup. Each
	// rendered attachment must be claimed by exactly one pfoa_cat row whose
	// image id, managed id, and inserted flag all verify; anything else
	// (historical, manual, pre-existing) is left untouched.
	$gallery_ids = array_keys( $attachment_to_figure );

	$cat_rows = get_posts(
		array(
			'post_type'      => 'pfoa_cat',
			'post_status'    => 'any',
			'posts_per_page' => count( $gallery_ids ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_pfoa_aw_adopted2026_image_id',
					'value'   => $gallery_ids,
					'compare' => 'IN',
				),
			),
		)
	);

	if ( ! is_array( $cat_rows ) || empty( $cat_rows ) ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$managed_meta_keys  = array( '_pfoa_aw_adopted2026_managed_id', '_pfoa_aw_adopted2026_managed_image_id' );
	$inserted_meta_keys = array( '_pfoa_aw_adopted2026_inserted', '_pfoa_aw_adopted2026_is_inserted' );

	$claims = array();

	foreach ( $cat_rows as $cat_row ) {
		$cat_id = (int) $cat_row;

		if ( 0 >= $cat_id ) {
			continue;
		}

		$image_id_raw = get_post_meta( $cat_id, '_pfoa_aw_adopted2026_image_id', true );

		if ( ! is_numeric( $image_id_raw ) ) {
			continue;
		}

		$claimed_att = (int) $image_id_raw;

		if ( ! isset( $attachment_to_figure[ $claimed_att ] ) ) {
			continue;
		}

		$inserted_ok = false;

		foreach ( $inserted_meta_keys as $inserted_key ) {
			$inserted_raw = get_post_meta( $cat_id, $inserted_key, true );

			if ( '' === $inserted_raw && ! is_numeric( $inserted_raw ) ) {
				continue;
			}

			if ( '1' === (string) $inserted_raw ) {
				$inserted_ok = true;
				break;
			}

			// A present-but-not-'1' inserted flag disqualifies this row.
			$inserted_ok = false;
			break;
		}

		if ( ! $inserted_ok ) {
			continue;
		}

		$managed_seen = false;
		$managed_ok   = false;

		foreach ( $managed_meta_keys as $managed_key ) {
			$managed_raw = get_post_meta( $cat_id, $managed_key, true );

			if ( '' === $managed_raw && ! is_numeric( $managed_raw ) ) {
				continue;
			}

			$managed_seen = true;

			if ( is_numeric( $managed_raw ) && (int) $managed_raw === $claimed_att ) {
				$managed_ok = true;
				break;
			}

			// A present-but-mismatched managed id disqualifies this row.
			$managed_ok = false;
			break;
		}

		// No separate managed key stored: the workflow image id itself is
		// the managed id (already matched to the attachment above).
		if ( ! $managed_seen ) {
			$managed_ok = true;
		}

		if ( ! $managed_ok ) {
			continue;
		}

		if ( ! isset( $claims[ $claimed_att ] ) ) {
			$claims[ $claimed_att ] = array();
		}

		$claims[ $claimed_att ][] = $cat_id;
	}

	$attachment_to_cat = array();
	$cat_to_attachment = array();

	foreach ( $claims as $claimed_att => $claiming_cats ) {
		$claiming_cats = array_values( array_unique( array_map( 'intval', (array) $claiming_cats ) ) );

		if ( 1 !== count( $claiming_cats ) ) {
			continue;
		}

		$single_cat = (int) $claiming_cats[0];

		if ( 0 >= $single_cat ) {
			continue;
		}

		if ( isset( $cat_to_attachment[ $single_cat ] ) ) {
			// One cat claiming two attachments: ambiguous, drop both.
			$other_att = $cat_to_attachment[ $single_cat ];
			unset( $attachment_to_cat[ $other_att ] );
			unset( $cat_to_attachment[ $single_cat ] );
			$ambiguous_ids[ $claimed_att ] = true;
			$ambiguous_ids[ $other_att ]   = true;
			continue;
		}

		$attachment_to_cat[ $claimed_att ] = $single_cat;
		$cat_to_attachment[ $single_cat ]  = $claimed_att;
	}

	if ( empty( $attachment_to_cat ) ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	// Normalizes a pfoa_cat_get_bonded_pair() return value (ints, numeric
	// strings, WP_Post objects, or arrays/objects with ID keys) into a
	// sorted list of unique positive IDs for set comparison.
	$normalize_pair = function ( $pair_raw ) {
		if ( ! is_array( $pair_raw ) ) {
			return array();
		}

		$pair_ids = array();

		foreach ( $pair_raw as $pair_entry ) {
			$pair_entry_id = 0;

			if ( is_numeric( $pair_entry ) ) {
				$pair_entry_id = (int) $pair_entry;
			} elseif ( $pair_entry instanceof WP_Post ) {
				$pair_entry_id = (int) $pair_entry->ID;
			} elseif ( is_array( $pair_entry ) ) {
				foreach ( array( 'ID', 'id', 'post_id', 'postId', 'postID' ) as $pair_key ) {
					if ( isset( $pair_entry[ $pair_key ] ) && is_numeric( $pair_entry[ $pair_key ] ) ) {
						$pair_entry_id = (int) $pair_entry[ $pair_key ];
						break;
					}
				}
			} elseif ( is_object( $pair_entry ) ) {
				foreach ( array( 'ID', 'id', 'post_id', 'postId', 'postID' ) as $pair_key ) {
					if ( isset( $pair_entry->{$pair_key} ) && is_numeric( $pair_entry->{$pair_key} ) ) {
						$pair_entry_id = (int) $pair_entry->{$pair_key};
						break;
					}
				}
			}

			if ( 0 < $pair_entry_id ) {
				$pair_ids[] = $pair_entry_id;
			}
		}

		$pair_ids = array_values( array_unique( $pair_ids ) );
		sort( $pair_ids );

		return $pair_ids;
	};

	// Steps 3-5: canonical validation, adjacency safety, wrapper grouping.
	$consumed = array();
	$changed  = false;

	foreach ( $ordered_items as $ordered_item ) {
		$att_a = (int) $ordered_item['att'];

		if ( isset( $consumed[ $att_a ] ) || isset( $ambiguous_ids[ $att_a ] ) ) {
			continue;
		}

		if ( ! isset( $attachment_to_cat[ $att_a ] ) || ! isset( $attachment_to_figure[ $att_a ] ) ) {
			continue;
		}

		$node_a = $attachment_to_figure[ $att_a ];
		$cat_a  = (int) $attachment_to_cat[ $att_a ];

		if ( null === $node_a->parentNode ) {
			continue;
		}

		// Step 3: canonical validation. Cat-domain first (the API speaks cat
		// IDs): the pair for cat A must be exactly two unique IDs containing
		// A, the partner cat must map independently to another rendered
		// attachment, and its pair must be the same set. An attachment-domain
		// fallback applies the identical symmetric proof. Raw bonded meta is
		// never consulted, adjacency never implies pairing, nothing is
		// repaired.
		$att_b = 0;

		$pair_cats = $normalize_pair( pfoa_cat_get_bonded_pair( $cat_a ) );

		if ( 2 === count( $pair_cats ) && in_array( $cat_a, $pair_cats, true ) ) {
			$other_cat = (int) ( (int) $pair_cats[0] === $cat_a ? $pair_cats[1] : $pair_cats[0] );

			if ( 0 < $other_cat && $other_cat !== $cat_a && isset( $cat_to_attachment[ $other_cat ] ) ) {
				$candidate_b = (int) $cat_to_attachment[ $other_cat ];

				if ( 0 < $candidate_b && $candidate_b !== $att_a && isset( $attachment_to_figure[ $candidate_b ] ) && ! isset( $ambiguous_ids[ $candidate_b ] ) ) {
					$mirror_cats = $normalize_pair( pfoa_cat_get_bonded_pair( $other_cat ) );

					if ( $mirror_cats === $pair_cats ) {
						$att_b = $candidate_b;
					}
				}
			}
		} else {
			$pair_atts = $normalize_pair( pfoa_cat_get_bonded_pair( $att_a ) );

			if ( 2 === count( $pair_atts ) && in_array( $att_a, $pair_atts, true ) ) {
				$candidate_b = (int) ( (int) $pair_atts[0] === $att_a ? $pair_atts[1] : $pair_atts[0] );

				if ( 0 < $candidate_b && $candidate_b !== $att_a && isset( $attachment_to_figure[ $candidate_b ] ) && ! isset( $ambiguous_ids[ $candidate_b ] ) ) {
					if ( isset( $attachment_to_cat[ $candidate_b ] ) ) {
						$cat_b = (int) $attachment_to_cat[ $candidate_b ];

						if ( 0 < $cat_b && $cat_b !== $cat_a ) {
							$mirror_atts = $normalize_pair( pfoa_cat_get_bonded_pair( $candidate_b ) );

							if ( $mirror_atts === $pair_atts ) {
								$att_b = $candidate_b;
							}
						}
					}
				}
			}
		}

		if ( 0 >= $att_b ) {
			continue;
		}

		if ( isset( $consumed[ $att_b ] ) ) {
			continue;
		}

		if ( ! isset( $attachment_to_figure[ $att_b ] ) ) {
			continue;
		}

		$node_b = $attachment_to_figure[ $att_b ];

		if ( null === $node_b->parentNode ) {
			continue;
		}

		if ( $node_a->parentNode !== $node_b->parentNode ) {
			continue;
		}

		// Step 4: adjacency safety. The two figures must currently be
		// adjacent direct siblings (nearest element sibling either side).
		// Current DOM order is preserved; nothing is re-sorted.
		$first_node  = null;
		$second_node = null;

		$next = $node_a->nextSibling;

		while ( $next && XML_ELEMENT_NODE !== $next->nodeType ) {
			$next = $next->nextSibling;
		}

		if ( $next === $node_b ) {
			$first_node  = $node_a;
			$second_node = $node_b;
		} else {
			$prev = $node_a->previousSibling;

			while ( $prev && XML_ELEMENT_NODE !== $prev->nodeType ) {
				$prev = $prev->previousSibling;
			}

			if ( $prev === $node_b ) {
				$first_node  = $node_b;
				$second_node = $node_a;
			} else {
				continue;
			}
		}

		$gallery_parent = $first_node->parentNode;

		if ( ! $gallery_parent instanceof DOMElement ) {
			continue;
		}

		if ( $second_node->parentNode !== $gallery_parent ) {
			continue;
		}

		// Step 5: wrapper. One div.pfoa-adopted-bonded-group as a direct
		// child of the .gallery at the first figure's position, holding the
		// two existing figure nodes in current order. Nodes are moved, never
		// cloned, so IDs/handlers/inner markup stay exactly as rendered.
		$group = $dom->createElement( 'div' );
		$group->setAttribute( 'class', 'pfoa-adopted-bonded-group' );
		$group->setAttribute( 'role', 'group' );
		$group->setAttribute( 'aria-label', 'Bonded pair' );

		$gallery_parent->insertBefore( $group, $first_node );
		$group->appendChild( $first_node );
		$group->appendChild( $second_node );

		// Step 5b: shared footer (presentation only, 0.1.54). Read the two
		// existing individual caption texts ONLY from their current
		// .gallery-caption elements in rendered pair order; never from
		// profile titles, attachment titles, filenames, or alt text. If
		// both are non-empty after trim, append one shared footer after
		// the figures and mark the member captions accessible-only. Any
		// blank caption or DOM failure leaves the group as-is.
		$footer_figures     = array( $first_node, $second_node );
		$footer_captions    = array();
		$footer_caption_els = array();
		$footer_ok          = true;

		foreach ( $footer_figures as $footer_figure ) {
			if ( ! $footer_figure instanceof DOMElement ) {
				$footer_ok = false;
				break;
			}

			$caption_match = $xpath->query(
				'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-caption ")]',
				$footer_figure
			);

			if ( ! $caption_match instanceof DOMNodeList || 0 === $caption_match->length ) {
				$footer_ok = false;
				break;
			}

			$caption_el = $caption_match->item( 0 );

			if ( ! $caption_el instanceof DOMElement ) {
				$footer_ok = false;
				break;
			}

			$caption_text = trim( (string) $caption_el->textContent );

			if ( '' === $caption_text ) {
				$footer_ok = false;
				break;
			}

			$footer_captions[]    = $caption_text;
			$footer_caption_els[] = $caption_el;
		}

		if ( $footer_ok && 2 === count( $footer_captions ) && 2 === count( $footer_caption_els ) ) {
			$footer_node = $dom->createElement( 'div' );

			if ( $footer_node instanceof DOMElement ) {
				$footer_node->setAttribute( 'class', 'pfoa-adopted-bonded-footer' );

				$title_node = $dom->createElement( 'p' );

				if ( $title_node instanceof DOMElement ) {
					$title_node->setAttribute( 'class', 'pfoa-adopted-bonded-title' );
					$title_node->appendChild( $dom->createTextNode( $footer_captions[0] . ' & ' . $footer_captions[1] ) );
					$footer_node->appendChild( $title_node );
				}

				$rel_node = $dom->createElement( 'p' );

				if ( $rel_node instanceof DOMElement ) {
					$rel_node->setAttribute( 'class', 'pfoa-adopted-bonded-relationship' );
					$rel_node->appendChild( $dom->createTextNode( 'Bonded pair' ) );
					$footer_node->appendChild( $rel_node );
				}

				if ( 2 === $footer_node->childNodes->length ) {
					$group->appendChild( $footer_node );

					foreach ( $footer_caption_els as $footer_caption_el ) {
						$existing_classes = $footer_caption_el->getAttribute( 'class' );

						if ( false === strpos( ' ' . $existing_classes . ' ', ' pfoa-adopted-bonded-member-caption ' ) ) {
							$footer_caption_el->setAttribute( 'class', trim( $existing_classes . ' pfoa-adopted-bonded-member-caption' ) );
						}
					}
				}
			}
		}

		$consumed[ $att_a ] = true;
		$consumed[ $att_b ] = true;

		$changed = true;
	}

	if ( ! $changed ) {
		libxml_clear_errors();
		libxml_use_internal_errors( $prev_libxml );
		return $content;
	}

	$output = '';

	foreach ( $wrapper->childNodes as $child ) {
		$output .= $dom->saveHTML( $child );
	}

	libxml_clear_errors();
	libxml_use_internal_errors( $prev_libxml );

	return $output;
}

add_filter( 'the_content', 'pfoa_present_adopted_bonded_pairs', 1000 );

/**
 * PFOA 0.1.156 — presentation only: auto-open the Pet Tidings preferences
 * disclosure (page 4799 only) when form feedback is pending, so the result
 * is visible even without JS.
 *
 * Adds the open attribute to details.pfoa-pt-disclosure when the request
 * carries ?pfoa_pt_done (prefs redirect) or $GLOBALS['pfoa_pt_pending'] is
 * non-empty (form repopulated with errors/success in the same request).
 * Narrowly scoped to page 4799 main-loop content; returns $content
 * unchanged otherwise. No content edits, no URL/label changes, no output
 * of user data — attribute addition only.
 *
 * @param string $content Post content.
 * @return string Possibly-opened content.
 */
function pfoa_pet_tidings_disclosure_open( $content ) {
	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $content;
	}

	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( false === strpos( $content, 'pfoa-pt-disclosure' ) ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && is_page( 4799 ) ) {
		$is_target_page = true;
	} elseif ( function_exists( 'get_post' ) ) {
		$post = get_post();

		if ( $post instanceof WP_Post && 4799 === (int) $post->ID ) {
			$is_target_page = true;
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	$should_open = isset( $_GET['pfoa_pt_done'] );

	if ( ! $should_open && ! empty( $GLOBALS['pfoa_pt_pending'] ) ) {
		$should_open = true;
	}

	if ( ! $should_open ) {
		return $content;
	}

	$opened = preg_replace_callback(
		'/<details\b([^>]*?)>/i',
		function ( $matches ) {
			$attrs = isset( $matches[1] ) ? $matches[1] : '';

			if ( false === strpos( $attrs, 'pfoa-pt-disclosure' ) ) {
				return $matches[0];
			}

			if ( preg_match( '/\bopen\b/i', $attrs ) ) {
				return $matches[0];
			}

			return '<details' . $attrs . ' open>';
		},
		$content
	);

	return is_string( $opened ) ? $opened : $content;
}

add_filter( 'the_content', 'pfoa_pet_tidings_disclosure_open', 1001 );

/**
 * Decide whether the shared Page content part should render the page title.
 *
 * OPT-OUT shared-path fix (PFOA 0.1.160): render the title on ALL Pages
 * except explicit exclusions. Returns false when any hold:
 *   - is_front_page() (the static front page renders via front-page.php);
 *   - current page ID or slug is in the exclusion set (defence in depth
 *     against re-imports/renames), filterable via
 *     `pfoa_page_title_excluded_pages` returning an array of IDs/slugs;
 *   - stored post_content of the current post already contains a
 *     case-insensitive `<h1[\s>]` (covers content-owned H1 pages + any
 *     future content-H1 page).
 * Else true. Safe outside the loop (get_post() null checks).
 *
 * @return bool
 */
function pfoa_should_render_page_title() {
	if ( function_exists( 'is_front_page' ) && is_front_page() ) {
		return false;
	}

	$excluded_ids   = array( 5421, 4159, 5656, 20323, 22514 );
	$excluded_slugs = array( 'inmemoriam', 'sponsoredanimals', 'adoptionstories', 'adoptablecats2', 'adopted2026' );

	if ( function_exists( 'apply_filters' ) ) {
		$filtered = apply_filters( 'pfoa_page_title_excluded_pages', array_merge( $excluded_ids, $excluded_slugs ) );

		if ( is_array( $filtered ) ) {
			$excluded_ids   = array();
			$excluded_slugs = array();

			foreach ( $filtered as $entry ) {
				if ( is_int( $entry ) || ( is_string( $entry ) && preg_match( '/^\d+$/', $entry ) ) ) {
					$excluded_ids[] = (int) $entry;
				} elseif ( is_string( $entry ) && '' !== $entry ) {
					$excluded_slugs[] = strtolower( $entry );
				}
			}
		}
	}

	$post = function_exists( 'get_post' ) ? get_post() : null;

	if ( $post ) {
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;

		if ( $post_id && in_array( $post_id, $excluded_ids, true ) ) {
			return false;
		}

		$slug = isset( $post->post_name ) ? strtolower( (string) $post->post_name ) : '';

		if ( '' !== $slug && in_array( $slug, $excluded_slugs, true ) ) {
			return false;
		}

		$content = isset( $post->post_content ) ? (string) $post->post_content : '';

		if ( '' !== $content && 1 === preg_match( '/<h1[\s>]/i', $content ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Make core Table block scroll regions keyboard-focusable (PFOA 0.1.173,
 * P1-04 companion to the figure.wp-block-table scroll-surface CSS).
 *
 * On small screens wide tables scroll internally inside
 * figure.wp-block-table. A bare figure is not keyboard-focusable, so
 * keyboard users could not reach the overflow. This adds tabindex="0" with
 * role="region" and a generic label to core/table figures that do not
 * already carry a tabindex. Native table semantics are untouched; no
 * markup, order, link, or content changes.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string
 */
function pfoa_table_scroll_region_attrs( $block_content, $block ) {
	if ( ! is_string( $block_content ) || '' === $block_content ) {
		return $block_content;
	}

	if ( ! is_array( $block ) || 'core/table' !== ( $block['blockName'] ?? '' ) ) {
		return $block_content;
	}

	if ( 1 === preg_match( '/<figure[^>]*\stabindex\s*=/i', $block_content ) ) {
		return $block_content;
	}

	$replaced = preg_replace(
		'/<figure(\s+class="wp-block-table")/',
		'<figure tabindex="0" role="region" aria-label="Scrollable table"$1',
		$block_content,
		1
	);

	return is_string( $replaced ) ? $replaced : $block_content;
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'render_block', 'pfoa_table_scroll_region_attrs', 10, 2 );
}

/**
 * PFOA 0.1.217 — Potholders page content compatibility (page 17894 /
 * slug potholders-2 only; Dine Out, Pet Tidings, and Home Front untouched).
 *
 * Render-time corrections for the Potholders page; stored post_content is
 * never modified. Each step is independent and fail-closed (content passes
 * through unchanged when its markers are absent):
 *   (a) a bare scram@gmail.com address (shcram@ variant included) that is
 *       not already inside an anchor becomes a proper mailto: link;
 *   (b) the two team paragraphs use the exact approved wording, including
 *       "potholders" (one word) and "(In 21 years we've raised over
 *       $405,000!)";
 *   (c) the legacy gallery block is moved before the "OUR MICROWAVEABLE
 *       BOWLS" heading so the potholder gallery reads with the potholder
 *       section instead of trailing the microwave-bowls copy.
 *
 * Narrowly scoped to the potholders main-loop content; returns $content
 * unchanged otherwise (including admin, feeds, and every other page).
 *
 * @param string $content Post content.
 * @return string Possibly-corrected content.
 */
function pfoa_potholders_content_compat( $content ) {
	if ( ! is_string( $content ) || '' === $content ) {
		return $content;
	}

	if ( function_exists( 'is_admin' ) && is_admin() ) {
		return $content;
	}

	if ( function_exists( 'is_feed' ) && is_feed() ) {
		return $content;
	}

	if ( function_exists( 'in_the_loop' ) && ! in_the_loop() ) {
		return $content;
	}

	if ( function_exists( 'is_main_query' ) && ! is_main_query() ) {
		return $content;
	}

	$is_target_page = false;

	if ( function_exists( 'is_page' ) && ( is_page( 17894 ) || is_page( 'potholders-2' ) ) ) {
		$is_target_page = true;
	} elseif ( function_exists( 'get_post' ) ) {
		$post = get_post();

		if ( $post instanceof WP_Post && ( 17894 === (int) $post->ID || 'potholders-2' === strtolower( (string) $post->post_name ) ) ) {
			$is_target_page = true;
		}
	}

	if ( ! $is_target_page ) {
		return $content;
	}

	// (a) Bare scram@gmail.com addresses become mailto: links. Occurrences
	// already inside an anchor are left untouched (idempotent).
	$anchor_spans = array();

	if ( preg_match_all( '/<a\b[^>]*>.*?<\/a\s*>/is', $content, $anchor_matches, PREG_OFFSET_CAPTURE ) ) {
		foreach ( $anchor_matches[0] as $anchor_match ) {
			$anchor_spans[] = array( (int) $anchor_match[1], (int) $anchor_match[1] + strlen( $anchor_match[0] ) );
		}
	}

	$email_hits = array();

	if ( preg_match_all( '/[A-Za-z0-9._%+\-]*scram@gmail\.com/i', $content, $email_matches, PREG_OFFSET_CAPTURE ) ) {
		$email_hits = $email_matches[0];
	}

	if ( array() !== $email_hits ) {
		$rebuilt = '';
		$cursor  = 0;

		foreach ( $email_hits as $hit ) {
			$email_text  = (string) $hit[0];
			$email_start = (int) $hit[1];
			$email_end   = $email_start + strlen( $email_text );

			if ( $email_start < $cursor ) {
				continue;
			}

			$inside_anchor = false;

			foreach ( $anchor_spans as $span ) {
				if ( $email_start >= $span[0] && $email_end <= $span[1] ) {
					$inside_anchor = true;
					break;
				}
			}

			$rebuilt .= substr( $content, $cursor, $email_start - $cursor );

			if ( $inside_anchor ) {
				$rebuilt .= $email_text;
			} else {
				$rebuilt .= '<a href="mailto:' . $email_text . '">' . $email_text . '</a>';
			}

			$cursor = $email_end;
		}

		$rebuilt .= substr( $content, $cursor );
		$content  = $rebuilt;
	}

	// (b) Exact approved wording for the two team paragraphs. The source
	// may use a curly apostrophe (U+2019), a straight apostrophe, or an
	// HTML entity; the replacement uses the exact ASCII wording.
	$apos = "(?:\xE2\x80\x99|'|&rsquo;|&#8217;)";

	$sentence_replacements = array(
		'/The money we raise by making and selling our pot holders helps support PFOA' . $apos . 's feline shelter, their pet food bank, and their outreach and adoption programs\. \(In \d+ years we' . $apos . 've raised over \$[\d,]+!\)/u' => 'The money we raise by making and selling our potholders helps support PFOA\'s feline shelter, their pet food bank, and their outreach and adoption programs. (In 21 years we\'ve raised over \$405,000!)',
		'/We have a great group of seamstresses, but we can' . $apos . 't keep up with demand! All materials and supplies are provided, and we will give a short training course in how to make our pot holders\. And we have fun doing this great project!/u' => 'We have a great group of seamstresses, but we can\'t keep up with demand! All materials and supplies are provided, and we will give a short training course in how to make our potholders. And we have fun doing this great project!',
	);

	foreach ( $sentence_replacements as $pattern => $replacement ) {
		$replaced = preg_replace( $pattern, $replacement, $content, 1 );

		if ( is_string( $replaced ) ) {
			$content = $replaced;
		}
	}

	// (c) Move the legacy gallery block before the microwave-bowls
	// heading. The gallery nests figures, never divs, so a depth scan
	// finds its balanced close; anything unexpected leaves the content
	// untouched.
	if ( 1 === preg_match( '/<div[^>]*\bid\s*=\s*["\']gallery-\d+["\'][^>]*>/i', $content, $gallery_open, PREG_OFFSET_CAPTURE ) ) {
		$gallery_start    = (int) $gallery_open[0][1];
		$gallery_scan     = $gallery_start + strlen( $gallery_open[0][0] );
		$gallery_depth    = 1;
		$gallery_end      = null;
		$gallery_scans    = 0;
		$content_length   = strlen( $content );

		while ( 0 < $gallery_depth && $gallery_scan < $content_length && $gallery_scans < 500 ) {
			$gallery_scans++;

			$next_open  = stripos( $content, '<div', $gallery_scan );
			$next_close = stripos( $content, '</div', $gallery_scan );

			if ( false === $next_close ) {
				break;
			}

			if ( false !== $next_open && $next_open < $next_close ) {
				$gallery_depth++;
				$gallery_scan = $next_open + 4;
				continue;
			}

			$close_bracket = strpos( $content, '>', $next_close );

			if ( false === $close_bracket ) {
				break;
			}

			$gallery_depth--;
			$gallery_scan = $close_bracket + 1;

			if ( 0 === $gallery_depth ) {
				$gallery_end = $gallery_scan;
			}
		}

		if ( null !== $gallery_end && 1 === preg_match( '/<h[1-6][^>]*>.*?OUR MICROWAVEABLE BOWLS.*?<\/h[1-6]\s*>/is', $content, $bowls_heading, PREG_OFFSET_CAPTURE ) ) {
			$heading_start = (int) $bowls_heading[0][1];

			if ( $gallery_start > $heading_start ) {
				$gallery_html = substr( $content, $gallery_start, $gallery_end - $gallery_start );
				$content      = substr( $content, 0, $heading_start ) . $gallery_html . substr( $content, $heading_start, $gallery_start - $heading_start ) . substr( $content, $gallery_end );
			}
		}
	}

	return $content;
}

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'the_content', 'pfoa_potholders_content_compat', 1002 );
}
