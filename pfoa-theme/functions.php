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
			'default'           => "257509 Highway 101\nPort Angeles, WA",
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
			'key'    => 'adoptable-cats',
			'title'  => __( 'Adoptable Cats', 'pfoa-theme' ),
			'mark'   => 'A',
			'url'    => $adoptable_cats_page ? get_permalink( $adoptable_cats_page ) : '',
			'button' => __( 'Explore', 'pfoa-theme' ),
		),
		array(
			'key'    => 'home-front',
			'title'  => __( 'From the Home Front', 'pfoa-theme' ),
			'mark'   => 'F',
			'url'    => $home_front_page ? get_permalink( $home_front_page ) : '',
			'button' => __( 'Explore', 'pfoa-theme' ),
		),
		array(
			'key'    => 'potholders',
			'title'  => __( 'Pot Holders', 'pfoa-theme' ),
			'mark'   => 'P',
			'url'    => $potholders_page ? get_permalink( $potholders_page ) : '',
			'button' => __( 'Explore', 'pfoa-theme' ),
		),
		array(
			'key'    => 'pet-tidings',
			'title'  => __( 'Pet Tidings', 'pfoa-theme' ),
			'mark'   => 'P',
			'url'    => $pet_tidings_page ? get_permalink( $pet_tidings_page ) : '',
			'button' => __( 'Explore', 'pfoa-theme' ),
		),
		array(
			'key'    => 'wishlist',
			'title'  => __( 'Wish List', 'pfoa-theme' ),
			'mark'   => 'W',
			'url'    => $wishlist_page ? get_permalink( $wishlist_page ) : '',
			'button' => __( 'Explore', 'pfoa-theme' ),
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

		$title  = isset( $card['title'] ) ? sanitize_text_field( $card['title'] ) : '';
		$mark   = isset( $card['mark'] ) ? sanitize_text_field( $card['mark'] ) : '';
		$button = isset( $card['button'] ) ? sanitize_text_field( $card['button'] ) : '';
		$url    = isset( $card['url'] ) ? esc_url_raw( $card['url'] ) : '';
		$key    = isset( $card['key'] ) ? sanitize_title( $card['key'] ) : '';

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
			'key'    => $key,
			'title'  => $title,
			'mark'   => $mark,
			'url'    => $url,
			'button' => '' === $button ? __( 'Explore', 'pfoa-theme' ) : $button,
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
 * Add the Homepage Cards page under Appearance.
 *
 * @return void
 */
function pfoa_homepage_cards_menu() {
	add_theme_page(
		esc_html__( 'Homepage Cards', 'pfoa-theme' ),
		esc_html__( 'Homepage Cards', 'pfoa-theme' ),
		'edit_theme_options',
		'pfoa-homepage-cards',
		'pfoa_homepage_cards_page'
	);
}
add_action( 'admin_menu', 'pfoa_homepage_cards_menu' );

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
			'key'    => '',
			'title'  => '',
			'mark'   => '',
			'url'    => '',
			'button' => __( 'Explore', 'pfoa-theme' ),
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
 * Render the Homepage Cards admin page.
 *
 * @return void
 */
function pfoa_homepage_cards_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	pfoa_homepage_cards_handle_post();

	$cards = pfoa_get_homepage_cards();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Homepage Cards', 'pfoa-theme' ); ?></h1>
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
						<th scope="col"><?php esc_html_e( 'URL', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Button', 'pfoa-theme' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'pfoa-theme' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $cards as $index => $card ) : ?>
						<tr>
							<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
							<td>
								<input type="hidden" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][key]" value="<?php echo esc_attr( isset( $card['key'] ) ? $card['key'] : '' ); ?>" />
								<input type="text" class="regular-text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][title]" value="<?php echo esc_attr( $card['title'] ); ?>" />
							</td>
							<td>
								<input type="text" name="<?php echo esc_attr( PFOA_HOMEPAGE_CARDS_OPTION ); ?>[<?php echo esc_attr( (string) $index ); ?>][mark]" value="<?php echo esc_attr( $card['mark'] ); ?>" size="2" maxlength="1" />
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
							<td colspan="6"><?php esc_html_e( 'No cards saved. Add one below or save to restore the defaults.', 'pfoa-theme' ); ?></td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
			<p class="submit">
				<button type="submit" class="button" name="pfoa_cards_add" value="1"><?php esc_html_e( 'Add', 'pfoa-theme' ); ?></button>
				<button type="submit" class="button button-primary" name="pfoa_cards_save" value="1"><?php esc_html_e( 'Save', 'pfoa-theme' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

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
