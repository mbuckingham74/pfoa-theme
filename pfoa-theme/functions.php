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
				<button type="submit" class="button button-primary" name="pfoa_cards_save" value="1"><?php esc_html_e( 'Save', 'pfoa-theme' ); ?></button>
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
 *   'mode'         => 'single' | 'carousel',
 *   'headline'     => string,
 *   'image_id'     => int (attachment ID),
 *   'carousel_ids' => int[] (attachment IDs, display order),
 *   'ctas'         => array[] of array( 'label' => string, 'url' => string ),
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
		'mode'         => 'single',
		'headline'     => __( 'Homepage', 'pfoa-theme' ),
		'image_id'     => 0,
		'carousel_ids' => array(),
		'ctas'         => $ctas,
	);
}

/**
 * Sanitize a homepage hero value. No raw HTML is allowed.
 *
 * @param mixed $value Raw submitted value (expected unslashed).
 * @return array
 */
function pfoa_sanitize_hero( $value ) {
	$hero = array(
		'mode'         => 'single',
		'headline'     => '',
		'image_id'     => 0,
		'carousel_ids' => array(),
		'ctas'         => array(),
	);

	if ( ! is_array( $value ) ) {
		return $hero;
	}

	$mode = isset( $value['mode'] ) ? sanitize_key( $value['mode'] ) : 'single';
	$hero['mode'] = in_array( $mode, array( 'single', 'carousel' ), true ) ? $mode : 'single';

	$hero['headline'] = isset( $value['headline'] ) ? sanitize_text_field( $value['headline'] ) : '';

	$image_id = isset( $value['image_id'] ) ? absint( $value['image_id'] ) : 0;
	$hero['image_id'] = ( $image_id && wp_attachment_is_image( $image_id ) ) ? $image_id : 0;

	if ( isset( $value['carousel_ids'] ) && is_array( $value['carousel_ids'] ) ) {
		foreach ( $value['carousel_ids'] as $carousel_id ) {
			$carousel_id = absint( $carousel_id );

			if ( $carousel_id && wp_attachment_is_image( $carousel_id ) && ! in_array( $carousel_id, $hero['carousel_ids'], true ) ) {
				$hero['carousel_ids'][] = $carousel_id;
			}
		}
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
	$carousel_ids = ( isset( $hero['carousel_ids'] ) && is_array( $hero['carousel_ids'] ) ) ? $hero['carousel_ids'] : array();
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
	<p><?php esc_html_e( 'Edit the hero section shown at the top of the site homepage. The headline and buttons stay fixed while carousel images change behind them.', 'pfoa-theme' ); ?></p>
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
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Carousel images', 'pfoa-theme' ); ?></th>
			<td>
				<ul class="pfoa-hero-carousel-list">
					<?php foreach ( $carousel_ids as $carousel_id ) : ?>
						<?php
						$carousel_id      = absint( $carousel_id );
						$carousel_preview = $carousel_id ? wp_get_attachment_image( $carousel_id, array( 80, 80 ) ) : '';
						?>
						<?php if ( $carousel_preview ) : ?>
							<li class="pfoa-hero-carousel-item">
								<span class="pfoa-hero-carousel-preview"><?php echo $carousel_preview; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns escaped markup. ?></span>
								<input type="hidden" name="pfoa_hero[carousel_ids][]" value="<?php echo esc_attr( (string) $carousel_id ); ?>" />
								<button type="button" class="button pfoa-hero-carousel-up"><?php esc_html_e( 'Move Up', 'pfoa-theme' ); ?></button>
								<button type="button" class="button pfoa-hero-carousel-down"><?php esc_html_e( 'Move Down', 'pfoa-theme' ); ?></button>
								<button type="button" class="button pfoa-hero-carousel-remove"><?php esc_html_e( 'Remove', 'pfoa-theme' ); ?></button>
							</li>
						<?php endif; ?>
					<?php endforeach; ?>
				</ul>
				<button type="button" class="button pfoa-hero-carousel-add"><?php esc_html_e( 'Add Images', 'pfoa-theme' ); ?></button>
				<p class="description"><?php esc_html_e( 'Shown in order when Carousel mode is selected. Use Move Up and Move Down to reorder; the headline and buttons stay the same on every image.', 'pfoa-theme' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="pfoa-hero-headline"><?php esc_html_e( 'Hero headline', 'pfoa-theme' ); ?></label></th>
			<td>
				<input type="text" id="pfoa-hero-headline" class="regular-text" name="pfoa_hero[headline]" value="<?php echo esc_attr( $headline ); ?>" />
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
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Homepage', 'pfoa-theme' ); ?></button>
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
 * Compatibility rendering for the legacy Theme Blvd [button] shortcode.
 *
 * Historical Page content (e.g. From the Home Front) still carries shortcodes
 * such as [button link="..." color="purple" target="_blank" align="center"
 * title="..."]Label[/button] that were rendered by the Theme Blvd Shortcodes
 * plugin under the previous theme. This theme must not depend on that plugin
 * or framework; it renders the observed attributes with the theme's own
 * button visual language (.pfoa-button/.button) so the label never prints raw.
 *
 * Only the attributes actually used in content are supported: link, color,
 * target, align, and title. Unknown attributes are ignored.
 *
 * @param array       $atts    Shortcode attributes.
 * @param string|null $content Enclosed label.
 * @return string
 */
function pfoa_legacy_button_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'link'   => '',
			'color'  => '',
			'target' => '',
			'align'  => '',
			'title'  => '',
		),
		$atts,
		'button'
	);

	$url = trim( (string) $atts['link'] );

	$label = trim( wp_strip_all_tags( (string) do_shortcode( (string) $content ) ) );

	if ( '' === $label ) {
		$label = trim( wp_strip_all_tags( (string) $atts['title'] ) );
	}

	if ( '' === $label ) {
		return '';
	}

	$classes = array( 'pfoa-button', 'button', 'pfoa-button-shortcode' );

	$color = sanitize_html_class( (string) $atts['color'] );

	if ( '' !== $color ) {
		$classes[] = 'pfoa-button--' . $color;
	}

	$target = strtolower( trim( (string) $atts['target'] ) );

	if ( ! in_array( $target, array( '_blank', '_self', '_parent', '_top' ), true ) ) {
		$target = '';
	}

	$title = trim( (string) $atts['title'] );

	if ( '' === $url ) {
		return '<span class="' . esc_attr( implode( ' ', $classes ) ) . '">' . esc_html( $label ) . '</span>';
	}

	$anchor = '<a class="' . esc_attr( implode( ' ', $classes ) ) . '" href="' . esc_url( $url ) . '"';

	if ( '' !== $target ) {
		$anchor .= ' target="' . esc_attr( $target ) . '"';

		if ( '_blank' === $target ) {
			$anchor .= ' rel="noopener noreferrer"';
		}
	}

	if ( '' !== $title ) {
		$anchor .= ' title="' . esc_attr( $title ) . '"';
	}

	$anchor .= '>' . esc_html( $label ) . '</a>';

	$align = strtolower( trim( (string) $atts['align'] ) );

	if ( in_array( $align, array( 'left', 'center', 'right' ), true ) ) {
		return '<div class="pfoa-button-shortcode-align" style="text-align:' . esc_attr( $align ) . ';">' . $anchor . '</div>';
	}

	return $anchor;
}

/**
 * Register legacy content shortcodes without clobbering plugin output.
 *
 * When the Theme Blvd Shortcodes plugin (or any other plugin) already
 * provides [button], its registration wins: this runs on init, after plugins
 * have registered theirs, and skips any shortcode that already exists.
 *
 * @return void
 */
function pfoa_register_legacy_shortcodes() {
	if ( ! shortcode_exists( 'button' ) ) {
		add_shortcode( 'button', 'pfoa_legacy_button_shortcode' );
	}
}
add_action( 'init', 'pfoa_register_legacy_shortcodes' );
