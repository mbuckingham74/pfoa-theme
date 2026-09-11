<?php
/**
 * Focused regression fixture for the PFOA navigation walker.
 *
 * This uses the real PFOA_Navigation_Walker with a small stand-in for the
 * WordPress Walker_Nav_Menu traversal contract. It intentionally has no
 * network, WordPress installation, or staging dependency.
 *
 * @package PFOA
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! class_exists( 'Walker_Nav_Menu' ) ) {
	/**
	 * Minimal core walker double used only by this focused fixture.
	 */
	class Walker_Nav_Menu {
		/**
		 * Walker fields used by WordPress to index menu items.
		 *
		 * @var string[]
		 */
		public $db_fields = array(
			'parent' => 'menu_item_parent',
			'id'     => 'db_id',
		);

		/**
		 * Whether the current item has children.
		 *
		 * @var bool
		 */
		public $has_children = false;

		/**
		 * Walk representative menu items in WordPress's depth-first order.
		 *
		 * @param object[] $elements Menu items.
		 * @param int      $max_depth Maximum depth.
		 * @param mixed    ...$args Menu arguments.
		 * @return string
		 */
		public function walk( $elements, $max_depth, ...$args ) {
			$children_elements = array();
			$parent_field      = $this->db_fields['parent'];
			$top_level         = array();

			foreach ( $elements as $element ) {
				$parent_id = absint( $element->{$parent_field} );

				if ( $parent_id ) {
					$children_elements[ $parent_id ][] = $element;
				} else {
					$top_level[] = $element;
				}
			}

			$walker_args = array( (object) ( isset( $args[0] ) ? $args[0] : array() ) );
			$output      = '';

			foreach ( $top_level as $element ) {
				$this->display_element( $element, $children_elements, $max_depth, 0, $walker_args, $output );
			}

			return $output;
		}

		/**
		 * Mirror the core child traversal relevant to this walker.
		 *
		 * @param object   $element Current item.
		 * @param object[] $children_elements Child map.
		 * @param int      $max_depth Maximum depth.
		 * @param int      $depth Current depth.
		 * @param array    $args Walker arguments.
		 * @param string   $output Rendered markup.
		 * @return void
		 */
		public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
			$id_field = $this->db_fields['id'];
			$item_id  = absint( $element->{$id_field} );
			$this->has_children = ! empty( $children_elements[ $item_id ] );

			if ( isset( $args[0] ) && is_object( $args[0] ) ) {
				$args[0]->has_children = $this->has_children;
			}

			$this->start_el( $output, $element, $depth, ...array_values( $args ) );

			if ( ( 0 === (int) $max_depth || (int) $max_depth > $depth + 1 ) && isset( $children_elements[ $item_id ] ) ) {
				$this->start_lvl( $output, $depth, ...array_values( $args ) );

				foreach ( $children_elements[ $item_id ] as $child ) {
					$this->display_element( $child, $children_elements, $max_depth, $depth + 1, $args, $output );
				}

				$this->end_lvl( $output, $depth, ...array_values( $args ) );
			}

			$this->end_el( $output, $element, $depth, ...array_values( $args ) );
		}

		/**
		 * Render the base menu-item markup that the real core walker would own.
		 *
		 * @param string $output Rendered markup.
		 * @param object $item Menu item.
		 * @param int    $depth Current depth.
		 * @param object $args Walker arguments.
		 * @param int    $id Current item ID.
		 * @return void
		 */
		public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
			$class = $this->has_children ? ' menu-item-has-children' : '';
			$output .= '<li id="menu-item-' . absint( $item->db_id ) . '" class="menu-item' . $class . '"><a href="' . esc_attr( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
		}

		/**
		 * @param string $output Rendered markup.
		 * @param int    $depth Current depth.
		 * @param object $args Walker arguments.
		 * @return void
		 */
		public function start_lvl( &$output, $depth = 0, $args = null ) {
			$output .= '<ul class="sub-menu">';
		}

		/**
		 * @param string $output Rendered markup.
		 * @param int    $depth Current depth.
		 * @param object $args Walker arguments.
		 * @return void
		 */
		public function end_lvl( &$output, $depth = 0, $args = null ) {
			$output .= '</ul>';
		}

		/**
		 * @param string $output Rendered markup.
		 * @param object $item Menu item.
		 * @param int    $depth Current depth.
		 * @param object $args Walker arguments.
		 * @return void
		 */
		public function end_el( &$output, $item, $depth = 0, $args = null ) {
			$output .= '</li>';
		}
	}
}

function add_action() {}
function add_editor_style() {}
function add_theme_support() {}
function apply_filters( $tag, $value ) { return $value; }
function esc_attr( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_html__( $value ) { return (string) $value; }
function esc_url( $value ) { return (string) $value; }
function esc_url_raw( $value ) { return (string) $value; }
function load_theme_textdomain() {}
function register_nav_menus() {}
function wp_enqueue_script() {}
function wp_enqueue_style() {}
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function wp_unique_id( $prefix = '' ) { static $id = 0; return $prefix . ++$id; }
function __( $value ) { return (string) $value; }
function absint( $value ) { return abs( (int) $value ); }

require dirname( __DIR__ ) . '/pfoa-theme/functions.php';

function fixture_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$items = array_map(
	static function ( $item ) {
		return (object) $item;
	},
	array(
		array( 'db_id' => 101, 'menu_item_parent' => 0,   'title' => 'Parent A',      'url' => '/parent-a/' ),
		array( 'db_id' => 102, 'menu_item_parent' => 101, 'title' => 'Child A',       'url' => '/child-a/' ),
		array( 'db_id' => 103, 'menu_item_parent' => 0,   'title' => 'Parent B',      'url' => '#' ),
		array( 'db_id' => 104, 'menu_item_parent' => 103, 'title' => 'Parent B2',     'url' => '/parent-b2/' ),
		array( 'db_id' => 105, 'menu_item_parent' => 104, 'title' => 'Grandchild B2', 'url' => '/grandchild-b2/' ),
		array( 'db_id' => 106, 'menu_item_parent' => 103, 'title' => 'Child B',       'url' => '/child-b/' ),
	)
);

$markup = ( new PFOA_Navigation_Walker() )->walk( $items, 0, array( 'menu_id' => 'fixture-menu' ) );

libxml_use_internal_errors( true );
$document = new DOMDocument();
fixture_assert( $document->loadHTML( '<?xml encoding="UTF-8">' . $markup ), 'fixture markup parses as HTML' );
$xpath = new DOMXPath( $document );

$buttons = $xpath->query( '//button[contains(concat(" ", normalize-space(@class), " "), " submenu-toggle ")]' );
$submenus = $xpath->query( '//ul[contains(concat(" ", normalize-space(@class), " "), " sub-menu ")]' );
fixture_assert( 3 === $buttons->length, 'one disclosure button exists for each parent-with-children' );
fixture_assert( 3 === $submenus->length, 'one submenu exists for each parent-with-children' );

$controls = array();
foreach ( $buttons as $button ) {
	$controls[] = $button->getAttribute( 'aria-controls' );
}
fixture_assert( 3 === count( array_unique( $controls ) ), 'all aria-controls values are unique' );

foreach ( $controls as $control ) {
	fixture_assert( '' !== $control, 'each disclosure has an aria-controls value' );
	fixture_assert( 1 === $xpath->query( '//*[@id="' . $control . '"]' )->length, 'each aria-controls value matches one element ID' );
	fixture_assert( 1 === $xpath->query( '//*[@id="' . $control . '" and contains(concat(" ", normalize-space(@class), " "), " sub-menu ")]' )->length, 'each controlled ID belongs to a submenu' );
}

foreach ( array( 101, 103, 104 ) as $parent_id ) {
	fixture_assert( 1 === $xpath->query( '//li[@id="menu-item-' . $parent_id . '"]/button' )->length, "parent {$parent_id} has one direct disclosure button" );
}

foreach ( array( 102, 105, 106 ) as $leaf_id ) {
	fixture_assert( 0 === $xpath->query( '//li[@id="menu-item-' . $leaf_id . '"]/button' )->length, "leaf {$leaf_id} has no disclosure button" );
}

fixture_assert( 1 === $xpath->query( '//li[@id="menu-item-103"]/a[@href="#"]' )->length, 'authored # parent link remains unchanged' );

fwrite( STDOUT, "PASS: focused navigation markup fixture (3 disclosures, 3 matching submenus, no leaf disclosures)\n" );
