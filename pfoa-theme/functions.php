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

		if ( empty( $args->has_children ) ) {
			return;
		}

		$submenu_id = 'pfoa-submenu-' . absint( $item->ID ) . '-' . absint( $depth );
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
