<?php
/**
 * Theme setup and asset loading.
 *
 * @package PFOA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

	if ( is_front_page() ) {
		$front_page_id = (int) get_option( 'page_on_front' );

		if ( $front_page_id ) {
			$front_hero = pfoa_get_hero( $front_page_id );

			if ( 'carousel' === $front_hero['mode'] && array() !== $front_hero['carousel_ids'] ) {
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
	$home_front_page     = pfoa_get_page_by_paths( array( 'news-announcements', 'fromthehomefront-2' ) );
	$potholders_page     = pfoa_get_page_by_paths( array( 'potholders-2' ) );
	$pet_tidings_page    = pfoa_get_page_by_paths( array( 'pettidings' ) );
	$wishlist_page       = pfoa_get_page_by_paths( array( 'wishlist' ) );

	return array(
		array(
			'key'      => 'adoptable-cats',
			'title'    => __( 'Adoptable Cats', 'pfoa-theme' ),
			'mark'     => 'A',
			'url'      => $adoptable_cats_page ? get_permalink( $adoptable_cats_page ) : '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'home-front',
			'title'    => __( 'From the Home Front', 'pfoa-theme' ),
			'mark'     => 'F',
			'url'      => $home_front_page ? get_permalink( $home_front_page ) : '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'potholders',
			'title'    => __( 'Pot Holders', 'pfoa-theme' ),
			'mark'     => 'P',
			'url'      => $potholders_page ? get_permalink( $potholders_page ) : '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'pet-tidings',
			'title'    => __( 'Pet Tidings', 'pfoa-theme' ),
			'mark'     => 'P',
			'url'      => $pet_tidings_page ? get_permalink( $pet_tidings_page ) : '',
			'button'   => __( 'Explore', 'pfoa-theme' ),
			'image_id' => 0,
		),
		array(
			'key'      => 'wishlist',
			'title'    => __( 'Wish List', 'pfoa-theme' ),
			'mark'     => 'W',
			'url'      => $wishlist_page ? get_permalink( $wishlist_page ) : '',
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
		$url      = isset( $card['url'] ) ? esc_url_raw( $card['url'] ) : '';
		$key      = isset( $card['key'] ) ? sanitize_title( $card['key'] ) : '';
		$image_id = isset( $card['image_id'] ) ? absint( $card['image_id'] ) : 0;

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

		if ( '' === $title && '' === $url && '' === $button && '' === $mark ) {
			continue;
		}

		$cards[] = array(
			'key'      => $key,
			'title'    => $title,
			'mark'     => $mark,
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
 * names pfoa_homepage_cards[N][key/title/mark/image_id/url/button], actions
 * pfoa_cards_add/save/up/down/remove, nonce pfoa_homepage_cards_save /
 * pfoa_homepage_cards_nonce, storage via update_option() in
 * pfoa_homepage_cards_handle_post(). Separate save form from the Hero
 * Section is intentional.
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
			<table class="widefat striped">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Order', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Title', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Mark', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Image', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'URL', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Button', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cards as $index => $card ) : ?>
					<?php
					$card_image_id = isset( $card['image_id'] ) ? absint( $card['image_id'] ) : 0;
					$card_preview  = $card_image_id ? wp_get_attachment_image( $card_image_id, array( 80, 80 ) ) : '';
					?>
						<tr>
							<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
							<td>
								<input type="hidden" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][key]" value="<?php echo esc_attr( isset( $card['key'] ) ? $card['key'] : '' ); ?>" />
								<input type="text" class="regular-text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $card['title'] ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][mark]" value="<?php echo esc_attr( $card['mark'] ); ?>" size="2" maxlength="1" />
							</td>
							<td class="pfoa-cards-image-cell">
								<input type="hidden" class="pfoa-cards-image-id" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][image_id]" value="<?php echo esc_attr( (string) $card_image_id ); ?>" />
								<span class="pfoa-cards-image-preview"><?php echo $card_preview ? $card_preview : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
								<button type="button" class="button pfoa-cards-image-select" data-select-label="<?php esc_attr_e( 'Select Image', 'pfoa-theme' ); ?>" data-replace-label="<?php esc_attr_e( 'Replace', 'pfoa-theme' ); ?>"><?php echo $card_preview ? esc_html__( 'Replace', 'pfoa-theme' ) : esc_html__( 'Select Image', 'pfoa-theme' ); ?></button>
								<button type="button" class="button pfoa-cards-image-remove"<?php echo $card_preview ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
							</td>
							<td>
								<input type="url" class="regular-text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( $card['url'] ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][button]" value="<?php echo esc_attr( $card['button'] ); ?>" />
							</td>
							<td>
								<button type="submit" class="button" name="pfoa_cards_up[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
								<button type="submit" class="button" name="pfoa_cards_down[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
								<button type="submit" class="button" name="pfoa_cards_remove[<?php echo esc_attr( (string) $index ); ?>]" value="1"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php if ( array() === $cards ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'No cards saved. Add one below or save to restore the defaults.', 'pfoa-theme' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
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
 *   'mode'               => 'single' | 'carousel',
 *   'headline'           => string,
 *   'image_id'           => int (attachment ID),
 *   'image_link_page_id' => int (published page ID, 0 = not clickable),
 *   'carousel_ids'       => int[] (attachment IDs, display order; derived from carousel_items),
 *   'carousel_items'     => array[] of array( 'id' => int, 'link_page_id' => int, 'headline' => string, 'brightness' => 100|110|120|130, 'headline_scale' => 70|80|90|100|110|120 ),
 *   'carousel_interval_ms' => 3500|5000|7000 (ms each carousel image stays visible; missing/invalid resolves to 5000),
 *   'ctas'               => array[] of array( 'label' => string, 'url' => string ),
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
	$adoption_url      = $adoption_page ? get_permalink( $adoption_page ) : '';
	$volunteering_url  = $volunteering_page ? get_permalink( $volunteering_page ) : '';

	$ctas = array();

	if ( $adoption_url ) {
		$ctas[] = array(
			'label' => __( 'Adopt', 'pfoa-theme' ),
			'url'   => $adoption_url,
		);
	}

	if ( $volunteering_url ) {
		$ctas[] = array(
			'label' => __( 'Volunteer', 'pfoa-theme' ),
			'url'   => $volunteering_url,
		);
	}

	return array(
		'mode'               => 'single',
		'headline'           => __( 'Homepage', 'pfoa-theme' ),
		'image_id'           => 0,
		'image_link_page_id' => 0,
		'carousel_ids'       => array(),
		'carousel_items'     => array(),
		'carousel_interval_ms' => 5000,
		'ctas'               => $ctas,
	);
}

/**
 * Sanitize an optional hero image destination page ID.
 *
 * Only a published page is accepted; anything else becomes 0 (not clickable).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_link_page_id( $value ) {
	$page_id = absint( $value );

	if ( ! $page_id || 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
		return 0;
	}

	return $page_id;
}

/**
 * Return the clickable destination for a hero image, if valid.
 *
 * Re-validated at render time so a page that was unpublished or trashed
 * after saving stops being clickable without any re-save.
 *
 * @param int $page_id Destination page ID.
 * @return array Array with 'url' and 'title' keys (empty strings when none).
 */
function pfoa_get_hero_link( $page_id ) {
	$page_id = absint( $page_id );

	if ( ! $page_id || 'page' !== get_post_type( $page_id ) || 'publish' !== get_post_status( $page_id ) ) {
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
 * Sanitize a per-image hero carousel headline size value.
 *
 * Only 70, 80, 90, 100, 110, 120 are accepted; anything else becomes 100 (Default).
 *
 * @param mixed $value Raw submitted value.
 * @return int
 */
function pfoa_sanitize_hero_headline_scale( $value ) {
	$scale = absint( $value );

	if ( ! in_array( $scale, array( 70, 80, 90, 100, 110, 120 ), true ) ) {
		return 100;
	}

	return $scale;
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
 * Sanitize a homepage hero value. No raw HTML is allowed.
 *
 * @param mixed $value Raw submitted value (expected unslashed).
 * @return array
 */
function pfoa_sanitize_hero( $value ) {
	$hero = array(
		'mode'               => 'single',
		'headline'           => '',
		'image_id'           => 0,
		'image_link_page_id' => 0,
		'carousel_ids'       => array(),
		'carousel_items'     => array(),
		'carousel_interval_ms' => 5000,
		'ctas'               => array(),
	);

	if ( ! is_array( $value ) ) {
		return $hero;
	}

	$mode = isset( $value['mode'] ) ? sanitize_key( $value['mode'] ) : 'single';
	$hero['mode'] = in_array( $mode, array( 'single', 'carousel' ), true ) ? $mode : 'single';

	$hero['headline'] = isset( $value['headline'] ) ? sanitize_text_field( $value['headline'] ) : '';

	$image_id = isset( $value['image_id'] ) ? absint( $value['image_id'] ) : 0;
	$hero['image_id'] = ( $image_id && wp_attachment_is_image( $image_id ) ) ? $image_id : 0;

	$hero['image_link_page_id'] = isset( $value['image_link_page_id'] ) ? pfoa_sanitize_hero_link_page_id( $value['image_link_page_id'] ) : 0;

	$hero['carousel_interval_ms'] = isset( $value['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $value['carousel_interval_ms'] ) : 5000;

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

			$carousel_items[] = array(
				'id'             => $carousel_id,
				'link_page_id'   => isset( $carousel_item['link_page_id'] ) ? pfoa_sanitize_hero_link_page_id( $carousel_item['link_page_id'] ) : 0,
				'headline'       => isset( $carousel_item['headline'] ) ? sanitize_text_field( $carousel_item['headline'] ) : '',
				'brightness'     => isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100,
				'headline_scale' => isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100,
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
				'headline'       => '',
				'brightness'     => 100,
				'headline_scale' => 100,
			);
		}
	}

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

			$label = isset( $cta['label'] ) ? sanitize_text_field( $cta['label'] ) : '';
			$url   = isset( $cta['url'] ) ? esc_url_raw( $cta['url'] ) : '';

			if ( '' === $label && '' === $url ) {
				continue;
			}

			$hero['ctas'][] = array(
				'label' => $label,
				'url'   => $url,
			);
		}
	}

	return $hero;
}

/**
 * Return the effective homepage hero for a page.
 *
 * When no hero meta has been saved, the legacy effective content applies:
 * headline "Homepage" with the default Adopt/Volunteer destinations. Image
 * resolution (custom image, then featured image, then theme fallback banner)
 * happens in the template so the hero never renders empty.
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

	if ( '' === $hero['headline'] ) {
		$hero['headline'] = $defaults['headline'];
	}

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

	$can_edit_hero = $front_id && current_user_can( 'edit_page', $front_id );

	if ( $front_id && ! current_user_can( 'edit_page', $front_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$hero         = $front_id ? pfoa_get_hero( $front_id ) : pfoa_get_hero_defaults();
	$mode         = in_array( $hero['mode'], array( 'single', 'carousel' ), true ) ? $hero['mode'] : 'single';
	$headline     = isset( $hero['headline'] ) ? $hero['headline'] : '';
	$image_id     = isset( $hero['image_id'] ) ? absint( $hero['image_id'] ) : 0;
	$image_link_page_id = isset( $hero['image_link_page_id'] ) ? absint( $hero['image_link_page_id'] ) : 0;
	$carousel_ids = ( isset( $hero['carousel_ids'] ) && is_array( $hero['carousel_ids'] ) ) ? $hero['carousel_ids'] : array();
	$carousel_interval_ms = isset( $hero['carousel_interval_ms'] ) ? pfoa_sanitize_hero_carousel_interval_ms( $hero['carousel_interval_ms'] ) : 5000;
	$carousel_items = array();

		if ( isset( $hero['carousel_items'] ) && is_array( $hero['carousel_items'] ) && array() !== $hero['carousel_items'] ) {
		foreach ( $hero['carousel_items'] as $carousel_item ) {
			if ( ! is_array( $carousel_item ) ) {
				continue;
			}

			$carousel_items[] = array(
				'id'             => isset( $carousel_item['id'] ) ? absint( $carousel_item['id'] ) : 0,
				'link_page_id'   => isset( $carousel_item['link_page_id'] ) ? absint( $carousel_item['link_page_id'] ) : 0,
				'headline'       => isset( $carousel_item['headline'] ) ? $carousel_item['headline'] : '',
				'brightness'     => isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100,
				'headline_scale' => isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100,
			);
		}
	} else {
		foreach ( $carousel_ids as $carousel_id ) {
			$carousel_items[] = array(
				'id'             => absint( $carousel_id ),
				'link_page_id'   => 0,
				'headline'       => '',
				'brightness'     => 100,
				'headline_scale' => 100,
			);
		}
	}

	$ctas         = ( isset( $hero['ctas'] ) && is_array( $hero['ctas'] ) ) ? $hero['ctas'] : array();
	$preview      = $image_id ? wp_get_attachment_image( $image_id, array( 80, 80 ) ) : '';

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
		<p><?php esc_html_e( 'Edit the hero section shown at the top of the site homepage. Each carousel image can have its own headline; images without one use the global hero headline. The buttons stay fixed while carousel images change behind them.', 'pfoa-theme' ); ?></p>
		<p class="submit">
			<button type="submit" class="button button-primary" name="pfoa_hero_save" value="1"><?php esc_html_e( 'Save Homepage', 'pfoa-theme' ); ?></button>
		</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Hero media mode', 'pfoa-theme' ); ?></th>
			<td>
				<fieldset>
					<label>
						<input type="radio" name="pfoa_hero[mode]" value="single"<?php checked( $mode, 'single' ); ?> />
						<?php esc_html_e( 'Single Image', 'pfoa-theme' ); ?>
					</label><br />
					<label>
						<input type="radio" name="pfoa_hero[mode]" value="carousel"<?php checked( $mode, 'carousel' ); ?> />
						<?php esc_html_e( 'Carousel', 'pfoa-theme' ); ?>
					</label>
				</fieldset>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Single image', 'pfoa-theme' ); ?></th>
			<td class="pfoa-hero-single-cell">
				<input type="hidden" class="pfoa-hero-single-id" name="pfoa_hero[image_id]" value="<?php echo esc_attr( (string) $image_id ); ?>" />
				<span class="pfoa-hero-single-preview"><?php echo $preview ? $preview : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
				<button type="button" class="button pfoa-hero-single-select"><?php echo $preview ? esc_html__( 'Replace', 'pfoa-theme' ) : esc_html__( 'Select Image', 'pfoa-theme' ); ?></button>
				<button type="button" class="button pfoa-hero-single-remove"<?php echo $preview ? '' : ' style="display:none;"'; ?>><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
				<p class="description"><?php esc_html_e( 'Shown when Single Image mode is selected. If empty, the page featured image (or the theme fallback banner) is used.', 'pfoa-theme' ); ?></p>
				<p>
					<label for="pfoa-hero-image-link"><?php esc_html_e( 'Image destination', 'pfoa-theme' ); ?></label><br />
					<?php
					wp_dropdown_pages(
						array(
							'show_option_none' => __( '— No link —', 'pfoa-theme' ),
							'option_none_value' => 0,
							'name'              => 'pfoa_hero[image_link_page_id]',
							'id'                => 'pfoa-hero-image-link',
							'selected'          => $image_link_page_id,
						)
					);
					?>
				</p>
				<p class="description"><?php esc_html_e( 'Optional. Clicking the hero image opens this page.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Carousel images', 'pfoa-theme' ); ?></th>
			<td>
				<ul class="pfoa-hero-carousel-list" data-next-index="<?php echo esc_attr( (string) count( $carousel_items ) ); ?>">
					<?php foreach ( $carousel_items as $carousel_index => $carousel_item ) : ?>
						<?php
					$carousel_id        = absint( $carousel_item['id'] );
					$carousel_link      = absint( $carousel_item['link_page_id'] );
					$carousel_headline  = isset( $carousel_item['headline'] ) ? $carousel_item['headline'] : '';
					$carousel_brightness = isset( $carousel_item['brightness'] ) ? pfoa_sanitize_hero_brightness( $carousel_item['brightness'] ) : 100;
					$carousel_headline_scale = isset( $carousel_item['headline_scale'] ) ? pfoa_sanitize_hero_headline_scale( $carousel_item['headline_scale'] ) : 100;
						$carousel_preview   = $carousel_id ? wp_get_attachment_image( $carousel_id, array( 80, 80 ) ) : '';
						$carousel_field_base = 'pfoa_hero[carousel_items][' . $carousel_index . ']';
						?>
						<?php if ( $carousel_preview ) : ?>
							<li class="pfoa-hero-carousel-item">
								<span class="pfoa-hero-carousel-preview"><?php echo $carousel_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
								<input type="hidden" class="pfoa-hero-carousel-id" name="<?php echo esc_attr( $carousel_field_base ); ?>[id]" value="<?php echo esc_attr( (string) $carousel_id ); ?>" />
								<label class="pfoa-hero-carousel-link">
									<?php esc_html_e( 'Image destination', 'pfoa-theme' ); ?>
									<?php
									wp_dropdown_pages(
										array(
											'show_option_none' => __( '— No link —', 'pfoa-theme' ),
											'option_none_value' => 0,
											'name'              => $carousel_field_base . '[link_page_id]',
											'selected'          => $carousel_link,
										)
									);
									?>
								</label>
							<label class="pfoa-hero-carousel-headline">
								<?php esc_html_e( 'Hero headline', 'pfoa-theme' ); ?>
								<input type="text" class="regular-text pfoa-hero-carousel-headline-input" name="<?php echo esc_attr( $carousel_field_base ); ?>[headline]" value="<?php echo esc_attr( $carousel_headline ); ?>" />
							</label>
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
									<option value="70"<?php selected( $carousel_headline_scale, 70 ); ?>><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
									<option value="80"<?php selected( $carousel_headline_scale, 80 ); ?>><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
									<option value="90"<?php selected( $carousel_headline_scale, 90 ); ?>><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
									<option value="100"<?php selected( $carousel_headline_scale, 100 ); ?>><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
									<option value="110"<?php selected( $carousel_headline_scale, 110 ); ?>><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
									<option value="120"<?php selected( $carousel_headline_scale, 120 ); ?>><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
								</select>
							</label>
								<p class="description"><?php esc_html_e( 'Optional. Leave blank to use the global hero headline.', 'pfoa-theme' ); ?></p>
								<button type="button" class="button pfoa-hero-carousel-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
								<button type="button" class="button pfoa-hero-carousel-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
								<button type="button" class="button pfoa-hero-carousel-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
				<template id="pfoa-hero-carousel-template">
					<li class="pfoa-hero-carousel-item">
						<span class="pfoa-hero-carousel-preview"></span>
						<input type="hidden" class="pfoa-hero-carousel-id" name="pfoa_hero[carousel_items][__INDEX__][id]" value="0" />
						<label class="pfoa-hero-carousel-link">
							<?php esc_html_e( 'Image destination', 'pfoa-theme' ); ?>
							<?php
							wp_dropdown_pages(
								array(
									'show_option_none' => __( '— No link —', 'pfoa-theme' ),
									'option_none_value' => 0,
									'name'              => 'pfoa_hero[carousel_items][__INDEX__][link_page_id]',
									'selected'          => 0,
									'echo'              => 1,
								)
							);
							?>
						</label>
					<label class="pfoa-hero-carousel-headline">
						<?php esc_html_e( 'Hero headline', 'pfoa-theme' ); ?>
						<input type="text" class="regular-text pfoa-hero-carousel-headline-input" name="pfoa_hero[carousel_items][__INDEX__][headline]" value="" />
					</label>
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
							<option value="70"><?php esc_html_e( '70%', 'pfoa-theme' ); ?></option>
							<option value="80"><?php esc_html_e( '80%', 'pfoa-theme' ); ?></option>
							<option value="90"><?php esc_html_e( '90%', 'pfoa-theme' ); ?></option>
							<option value="100" selected="selected"><?php esc_html_e( 'Default (100%)', 'pfoa-theme' ); ?></option>
							<option value="110"><?php esc_html_e( '110%', 'pfoa-theme' ); ?></option>
							<option value="120"><?php esc_html_e( '120%', 'pfoa-theme' ); ?></option>
						</select>
					</label>
						<p class="description"><?php esc_html_e( 'Optional. Leave blank to use the global hero headline.', 'pfoa-theme' ); ?></p>
						<button type="button" class="button pfoa-hero-carousel-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-carousel-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
						<button type="button" class="button pfoa-hero-carousel-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
					</li>
				</template>
				<button type="button" class="button pfoa-hero-carousel-add"><?php esc_html_e( 'Add Images', 'pfoa-theme' ); ?></button>
				<p class="description"><?php esc_html_e( 'Shown in order when Carousel mode is selected. Use Move Up and Move Down to reorder; the buttons stay the same on every image.', 'pfoa-theme' ); ?></p>
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
		<tr>
			<th scope="row"><label for="pfoa-hero-headline"><?php esc_html_e( 'Global hero headline', 'pfoa-theme' ); ?></label></th>
			<td>
				<input type="text" id="pfoa-hero-headline" class="regular-text" name="pfoa_hero[headline]" value="<?php echo esc_attr( $headline ); ?>" />
				<p class="description"><?php esc_html_e( 'Global hero headline — Used for Single Image mode and as the fallback for Carousel images without their own headline.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
	</table>
	<h4><?php esc_html_e( 'Shortcut buttons', 'pfoa-theme' ); ?></h4>
	<table class="widefat striped">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Label', 'pfoa-theme' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Destination URL', 'pfoa-theme' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></th>
			</tr>
		</thead>
		<tbody class="pfoa-hero-ctas-body" data-next-index="<?php echo esc_attr( (string) count( $ctas ) ); ?>">
			<?php foreach ( $ctas as $index => $cta ) : ?>
				<tr class="pfoa-hero-cta-row">
					<td>
						<input type="text" class="regular-text" name="pfoa_hero[ctas][<?php echo esc_attr( (string) $index ); ?>][label]" value="<?php echo esc_attr( isset( $cta['label'] ) ? $cta['label'] : '' ); ?>" />
					</td>
					<td>
						<input type="url" class="regular-text" name="pfoa_hero[ctas][<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_attr( isset( $cta['url'] ) ? $cta['url'] : '' ); ?>" />
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
				'moveUp'   => __( 'Move Up', 'pfoa-theme' ),
				'moveDown' => __( 'Move Down', 'pfoa-theme' ),
				'remove'   => __( 'Remove', 'pfoa-theme' ),
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

	update_post_meta( $front_id, PFOA_HERO_META_KEY, pfoa_sanitize_hero( $raw ) );

	wp_safe_redirect( add_query_arg( 'pfoa-homepage-updated', '1', admin_url( 'admin.php?page=pfoa-site' ) ) );
	exit;
}
add_action( 'admin_post_pfoa_homepage_hero_save', 'pfoa_homepage_hero_save' );

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

		// Build replacement via DOM methods so text/attributes stay escaped.
		$bonded = $dom->createElement( 'article' );
		$bonded->setAttribute( 'class', 'pfoa-cat-card pfoa-cat-bonded-card pfoa-cat-bonded-card--bonded' );
		$bonded->setAttribute( 'data-pfoa-cat-dialog', $left_dialog );

		if ( '' !== $shared_profile_id ) {
			$bonded->setAttribute( 'data-pfoa-profile-id', $shared_profile_id );
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

			// Canonical deterministic order comes from the API response.
			$first_id  = $pair_ids[0];
			$second_id = $pair_ids[1];

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

			// Canonical first member provides the title link + article attrs.
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

			$structured_photos = $dom->createElement( 'div' );
			$structured_photos->setAttribute( 'class', 'pfoa-cat-bonded-photos' );
			$structured_bonded->appendChild( $structured_photos );

			// Preserve both member media links (href + dialog + profile-id) and
			// images (src/srcset/sizes/alt) in canonical member order.
			$structured_media_nodes = array( $first_media, $second_media );

			foreach ( $structured_media_nodes as $structured_media_node ) {
				$structured_media_clone = $structured_media_node->cloneNode( true );

				$structured_media_class = trim( (string) $structured_media_clone->getAttribute( 'class' ) );

				if ( false === strpos( ' ' . $structured_media_class . ' ', ' pfoa-cat-bonded-photo ' ) ) {
					$structured_media_clone->setAttribute( 'class', trim( $structured_media_class . ' pfoa-cat-bonded-photo' ) );
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

		$events[] = array(
			'id'      => $event_id,
			'date'    => trim( (string) $event_date ),
			'uuid'    => $event_uuid,
			'members' => $members,
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

	// Step 3: build event markup from snapshots and collect the claimed
	// snapshot image IDs for legacy suppression below.
	$claimed_ids = array();
	$events_html = '<section class="pfoa-adoption-events" aria-label="Adopted cats">';

	foreach ( $events as $event ) {
		$member_count = count( $event['members'] );
		$count_class  = 3 < $member_count ? 'n' : (string) $member_count;

		// PFOA 0.1.82 — presentation only: bonded-pair indicator for TRUE
		// bonded 2-member events only. Strict reciprocal validation via the
		// Cat Profiles helpers (fail-closed: helpers absent, IDs missing /
		// duplicated, or any check failing leaves the event unlabelled).
		// 3+ member events are NEVER labelled bonded. No bond data, event
		// meta, or stored facts are read beyond the helpers or written.
		$is_bonded_pair = false;

		if ( 2 === $member_count && function_exists( 'pfoa_cat_is_bonded' ) && function_exists( 'pfoa_cat_get_bonded_pair' ) ) {
			$bond_ids = array();

			foreach ( $event['members'] as $bond_member ) {
				$bond_ids[] = ( is_array( $bond_member ) && isset( $bond_member['cat_post_id'] ) ) ? (int) $bond_member['cat_post_id'] : 0;
			}

			if ( 0 < $bond_ids[0] && 0 < $bond_ids[1] && $bond_ids[0] !== $bond_ids[1] ) {
				if ( pfoa_cat_is_bonded( $bond_ids[0] ) && pfoa_cat_is_bonded( $bond_ids[1] ) ) {
					$bond_pair = pfoa_cat_get_bonded_pair( $bond_ids[0] );

					if ( is_array( $bond_pair ) ) {
						$bond_pair   = array_map( 'intval', array_values( $bond_pair ) );
						$bond_sorted = $bond_ids;

						sort( $bond_pair, SORT_NUMERIC );
						sort( $bond_sorted, SORT_NUMERIC );

						if ( $bond_pair === $bond_sorted ) {
							$is_bonded_pair = true;
						}
					}
				}
			}
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
			if ( $is_bonded_pair ) {
				$combined_title .= ' <span class="pfoa-adoption-event-bonded-badge">' . esc_html__( 'Bonded Pair', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'adopted together as a bonded pair', 'pfoa-theme' ) . '</span></span>';
			} elseif ( $is_adopted_together ) {
				$combined_title .= ' <span class="pfoa-adoption-event-together-badge">' . esc_html__( 'Adopted Together', 'pfoa-theme' ) . '<span class="screen-reader-text"> ' . esc_html__( 'in the same adoption event', 'pfoa-theme' ) . '</span></span>';
			}

			$combined_title .= '</h2>';
		}

		if ( 1 >= $member_count && '' !== $combined_title ) {
			$events_html .= $combined_title;
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
			// from markup while stored snapshots stay untouched. Single-cat
			// events keep their visible caption exactly as before.
			if ( '' !== $event_member['caption'] && 1 >= $member_count ) {
				$events_html .= '<figcaption class="pfoa-adoption-event-caption">' . esc_html( $event_member['caption'] ) . '</figcaption>';
			}

			$events_html .= '</figure>';
		}

		$events_html .= '</div>';

		if ( 1 < $member_count && '' !== $combined_title ) {
			$events_html .= $combined_title;
		}

		$events_html .= '</div>';
	}

	$events_html .= '</section>';

	// Step 4: suppress claimed figures from the rendered legacy gallery
	// (exact attachment-ID match only) and insert each event wrapper at
	// its earliest claimed snapshot position inside the same legacy
	// gallery (PFOA 0.1.79 shared grid; fallback: before the first
	// remaining gallery, else prepend). Without DOM support the events
	// still render (prepended) while the legacy gallery is left untouched.
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
	// PFOA 0.1.79 — presentation only: map each claimed snapshot image to
	// its owning event (claimed IDs only, no caption/name inference; first
	// event wins on a shared image) so per-event wrappers can re-enter at
	// their earliest claimed snapshot position below.
	$claimed_event = array();

	foreach ( $events as $event ) {
		foreach ( $event['members'] as $event_member ) {
			if ( 0 < $event_member['image_id'] && ! isset( $claimed_event[ $event_member['image_id'] ] ) ) {
				$claimed_event[ $event_member['image_id'] ] = $event['uuid'];
			}
		}
	}

	// attachment ID => array( parent gallery element, next sibling,
	// order, plus ref: first surviving successor resolved pre-removal ).
	$claimed_anchors = array();
	$scan_order      = 0;

	if ( ! empty( $claimed_ids ) ) {
		$item_list = $xpath->query(
			'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery-item ")]',
			$wrapper
		);

		if ( $item_list instanceof DOMNodeList && 0 < $item_list->length ) {
			$removable = array();

			foreach ( $item_list as $item_node ) {
				$item_order = $scan_order;
				$scan_order++;

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

				if ( 0 < $from_caption && 0 < $from_img && $from_caption !== $from_img ) {
					continue;
				}

				$attachment_id = 0 < $from_caption ? $from_caption : $from_img;

				if ( 0 < $attachment_id && isset( $claimed_ids[ $attachment_id ] ) ) {
					$removable[] = $item_node;

					if ( ! isset( $claimed_anchors[ $attachment_id ] ) ) {
						$claimed_anchors[ $attachment_id ] = array(
							'parent' => $parent,
							'next'   => $item_node->nextSibling,
							'order'  => $item_order,
						);
					}
				}
			}

			// Resolve each recorded anchor's insertion reference BEFORE
			// removal: from the claimed node's raw nextSibling, walk forward
			// past any sibling queued for removal to the first surviving
			// sibling in the same gallery (null when consecutive claims run
			// to the gallery end). Surviving nodes are never removed below,
			// so the reference stays a valid insertion point.
			$removal_ids = array();

			foreach ( $removable as $removable_node ) {
				$removal_ids[ spl_object_id( $removable_node ) ] = true;
			}

			foreach ( $claimed_anchors as $anchor_id => $anchor ) {
				$successor = $anchor['next'];

				while ( $successor instanceof DOMNode
					&& isset( $removal_ids[ spl_object_id( $successor ) ] )
					&& $successor->parentNode === $anchor['parent'] ) {
					$successor = $successor->nextSibling;
				}

				if ( ! $successor instanceof DOMNode || $successor->parentNode !== $anchor['parent'] ) {
					$successor = null;
				}

				$claimed_anchors[ $anchor_id ]['ref'] = $successor;
			}

			foreach ( $removable as $removable_node ) {
				if ( null !== $removable_node->parentNode ) {
					$removable_node->parentNode->removeChild( $removable_node );
				}
			}
		}
	}

	// event UUID => earliest claimed snapshot anchor (min document order
	// over that event's member image IDs actually found in the gallery).
	$event_anchors = array();

	foreach ( $claimed_anchors as $anchor_id => $anchor ) {
		if ( ! isset( $claimed_event[ $anchor_id ] ) ) {
			continue;
		}

		$anchor_uuid = $claimed_event[ $anchor_id ];

		if ( ! isset( $event_anchors[ $anchor_uuid ] ) || $anchor['order'] < $event_anchors[ $anchor_uuid ]['order'] ) {
			$event_anchors[ $anchor_uuid ] = $anchor;
		}
	}

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

		$event_labels[ $event['uuid'] ] = empty( $label_names ) ? 'Adopted cats' : 'Adopted cats: ' . implode( ' & ', $label_names );
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

	// PFOA 0.1.79 — presentation only: shared-grid insertion. Each event
	// wrapper re-enters at its earliest claimed snapshot position inside
	// the same legacy .gallery: insertBefore the first surviving successor
	// at/after that position (resolved pre-removal past consecutive
	// claims), else append to that gallery when claims run to its end.
	// Anchors run in ascending document order so multiple events insert
	// stably and never merge. Only an event with no anchor parent (no
	// found snapshot node, or parent gone) keeps the prior behavior
	// (before the first remaining .gallery, else prepend).
	$ordered_uuids = array();

	foreach ( $events as $event_index => $event ) {
		if ( ! isset( $event_nodes[ $event['uuid'] ] ) ) {
			continue;
		}

		$ordered_uuids[] = array(
			'uuid'  => $event['uuid'],
			'order' => isset( $event_anchors[ $event['uuid'] ] ) ? (int) $event_anchors[ $event['uuid'] ]['order'] : PHP_INT_MAX,
			'index' => (int) $event_index,
		);
	}

	usort(
		$ordered_uuids,
		function ( $a, $b ) {
			if ( (int) $a['order'] !== (int) $b['order'] ) {
				return (int) $a['order'] > (int) $b['order'] ? 1 : -1;
			}

			if ( (int) $a['index'] === (int) $b['index'] ) {
				return 0;
			}

			return (int) $a['index'] > (int) $b['index'] ? 1 : -1;
		}
	);

	foreach ( $ordered_uuids as $ordered ) {
		$insert_node = $event_nodes[ $ordered['uuid'] ];
		$placed      = false;

		if ( isset( $event_anchors[ $ordered['uuid'] ] ) ) {
			$anchor        = $event_anchors[ $ordered['uuid'] ];
			$anchor_parent = $anchor['parent'];
			$anchor_ref    = isset( $anchor['ref'] ) ? $anchor['ref'] : null;
			$connected     = false;

			if ( $anchor_parent instanceof DOMElement && $anchor_parent->ownerDocument === $dom ) {
				$ancestor = $anchor_parent;

				while ( $ancestor instanceof DOMNode && $ancestor !== $wrapper ) {
					$ancestor = $ancestor->parentNode;
				}

				$connected = ( $ancestor === $wrapper );
			}

			if ( $connected ) {
				if ( $anchor_ref instanceof DOMNode && $anchor_ref->parentNode === $anchor_parent ) {
					$anchor_parent->insertBefore( $insert_node, $anchor_ref );
				} else {
					$anchor_parent->appendChild( $insert_node );
				}

				$placed = true;
			}
		}

		if ( ! $placed ) {
			$fallback_gallery = null;

			$gallery_list = $xpath->query(
				'.//*[contains(concat(" ", normalize-space(@class), " "), " gallery ")]',
				$wrapper
			);

			if ( $gallery_list instanceof DOMNodeList && 0 < $gallery_list->length ) {
				foreach ( $gallery_list as $gallery_node ) {
					if ( $gallery_node instanceof DOMElement ) {
						$fallback_gallery = $gallery_node;
						break;
					}
				}
			}

			if ( $fallback_gallery instanceof DOMElement && null !== $fallback_gallery->parentNode ) {
				$fallback_gallery->parentNode->insertBefore( $insert_node, $fallback_gallery );
			} else {
				$first_child = $wrapper->firstChild;
				$wrapper->insertBefore( $insert_node, $first_child );
			}
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
