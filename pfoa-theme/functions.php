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
 * Scoped to the PFOA Site > Homepage screen only (toplevel + submenu hook
 * suffixes); the media library is loaded there and nowhere else.
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function pfoa_homepage_cards_admin_assets( $hook ) {
	if ( ! in_array( $hook, array( 'toplevel_page_pfoa-site', 'pfoa-site_page_pfoa-homepage' ), true ) ) {
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
 * Register the PFOA Site top-level menu and its Homepage screen.
 *
 * The menu uses the edit_pages capability so anyone who can edit pages sees
 * it; per-page access is enforced inside the screen and save callbacks with
 * current_user_can( 'edit_page', $front_id ).
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
		'pfoa-homepage',
		'pfoa_site_homepage_page'
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
	if ( ! in_array( $hook, array( 'toplevel_page_pfoa-site', 'pfoa-site_page_pfoa-homepage' ), true ) ) {
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
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-homepage' ) );
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
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-homepage' ) );
		exit;
	}

	if ( 'page' !== get_post_type( $front_id ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=pfoa-homepage' ) );
		exit;
	}

	if ( ! current_user_can( 'edit_page', $front_id ) ) {
		wp_die( esc_html__( 'You do not have permission to edit the homepage.', 'pfoa-theme' ) );
	}

	$raw = isset( $_POST['pfoa_hero'] ) && is_array( $_POST['pfoa_hero'] ) ? wp_unslash( $_POST['pfoa_hero'] ) : array();

	update_post_meta( $front_id, PFOA_HERO_META_KEY, pfoa_sanitize_hero( $raw ) );

	wp_safe_redirect( add_query_arg( 'pfoa-homepage-updated', '1', admin_url( 'admin.php?page=pfoa-homepage' ) ) );
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
