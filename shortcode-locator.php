<?php
/**
Plugin Name: Shortcode Locator
Plugin URI: http://travislop.es/plugins/shortcode-locator/
Description: Quickly locate what and where shortcodes are being used
Version: 1.1.1
Author: travislopes
Author URI: http://travislop.es
 **/

/**
 * Shortcode Locator.
 *
 * @since     1.0
 * @author    Travis Lopes
 * @copyright Copyright (c) 2016, Travis Lopes
 */
class Shortcode_Locator {

	/**
	 * Defines the plugin slug.
	 *
	 * @since  1.1
	 * @access public
	 * @var    string $_slug The slug used for this plugin.
	 */
	public $slug = 'shortcode_locator';

	/**
	 * Defines the full path to this class file.
	 *
	 * @since  1.1
	 * @access protected
	 * @var    string $full_path The full path.
	 */
	protected $full_path = __FILE__;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	private static $_instance = null;

	/**
	 * Get instance of this class.
	 *
	 * @since  1.0
	 * @access public
	 * @static
	 *
	 * @return $_instance
	 */
	public static function get_instance() {

		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}

	/**
	 * Constructor.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function __construct() {

		// Load required classes.
		require_once 'includes/class-shortcode-locator-settings.php';
		require_once 'includes/class-shortcode-locator-table.php';

		// Initialize settings.
		$settings = shortcode_locator_settings();

		// Register plugin action links.
		add_filter( 'plugin_action_links_'. plugin_basename( $this->full_path ), array( $this, 'plugin_action_links' ) );

		// Register plugin pages.
		add_action( 'admin_menu', array( $this, 'register_plugin_page' ) );
		add_action( 'admin_menu', array( $settings, 'register_settings_page' ) );

		// Add shortcode post column.
		add_filter( 'manage_posts_columns', array( $this, 'add_posts_column' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'render_posts_column' ), 10, 2 );

	}





	// # SETTINGS ------------------------------------------------------------------------------------------------------

	/**
	 * Links display on the pluginspage.
	 *
	 * @since  1.1
	 * @access public
	 * @param  array $links An array of plugin action links.
	 *
	 * @return array
	 */
	public function plugin_action_links( $links ) {

		// Prepare plugin links.
		$plugin_links = array( '<a href="'. admin_url( 'options-general.php?page='. shortcode_locator_settings()->slug ) .'">' . esc_html__( 'Settings', 'shortcode-locator' ) . '</a>' );

		return array_merge( $plugin_links, $links );

	}





	// # PLUGIN PAGE ---------------------------------------------------------------------------------------------------

	/**
	 * Register plugin page.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function register_plugin_page() {

		// Add plugin page to Tools menu.
		add_submenu_page(
			'tools.php', // Parent slug.
			$this->plugin_page_title(), // Page title.
			$this->plugin_page_title(), // Menu title.
			'edit_posts', // Capability.
			$this->slug, // Menu slug.
			array( $this, 'render_plugin_page' ) // Page function.
		);

	}

	/**
	 * Render and display plugin page.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function render_plugin_page() {

		// Start page.
		echo '<div class="wrap">';

		// Display page title.
		echo '<h2>'. $this->plugin_page_title() .'</h2>';

		// Display shortcodes table.
		$table = new Shortcode_Locator_Table();
		$table->prepare_items();
		$table->display();

		// End page.
		echo '</div>';

	}

	/**
	 * Prepares plugin page title.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @return string
	 */
	public function plugin_page_title() {

		return esc_html__( 'Shortcode Locator', 'shortcode-locator' );

	}





	// # POSTS PAGE ----------------------------------------------------------------------------------------------------

	/**
	 * Add shortcode column to posts list.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param array  $columns   An array of column names.
	 * @param string $post_type The post type slug.
	 *
	 * @return array
	 */
	public function add_posts_column( $columns, $post_type ) {

		// Get allowed post types.
		$allowed_post_types = shortcode_locator_settings()->get_setting( 'display_column' );

		// If this post type is not on the display columns list, return columns.
		if ( ! in_array( $post_type, $allowed_post_types ) ) {
			return $columns;
		}

		// Add columns.
		$columns['shortcodes'] = esc_html__( 'Shortcodes Located', 'shortcode-locator' );

		return $columns;

	}

	/**
	 * Display shortcode column on posts list.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $column  The name of the column to display.
	 * @param int    $post_id The current post ID.
	 */
	public function render_posts_column( $column, $post_id ) {

		// Get post content.
		$post_content = get_post_field( 'post_content', $post_id );

		// Get shortcodes in post.
		$shortcodes = $this->get_shortcodes_for_post( $post_content );

		// If no shortcodes were found, return.
		if ( empty( $shortcodes ) ) {
			return;
		}

		// Escape shortcodes.
		$shortcodes = array_map( 'esc_html', $shortcodes );

		// Display shortcodes.
		echo implode( '<br />', $shortcodes );

	}

	/**
	 * Get available post types.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @return array
	 */
	public function get_post_types() {

		// Initialize post types array.
		$post_types = array();

		// Define excluded post types.
		$excluded_post_types = array( 'attachment', 'revision', 'nav_menu_item' );
		$excluded_post_types = apply_filters( 'shortcode_locator_excluded_post_types', $excluded_post_types );

		// Get registered post types.
		$registered_post_types = get_post_types( array(), 'objects' );

		// Loop through registered post types.
		foreach ( $registered_post_types as $post_type ) {

			// If post type is excluded, skip it.
			if ( in_array( $post_type->name, $excluded_post_types ) ) {
				continue;
			}

			// Add post type to return array.
			$post_types[ $post_type->name ] = $post_type->label;

		}

		return $post_types;

	}





	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Get shortcodes within post content.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @param string $post_content Post content.
	 * @param array  $shortcode    Shortcode to search for. Defaults to null.
	 *
	 * @return array
	 */
	public function get_shortcodes_for_post( $post_content, $shortcode = null ) {

		// Initialize shortcodes return array.
		$shortcodes = array();

		// Prepare shortcode regex.
		$shortcode_regex = get_shortcode_regex( $shortcode );

		// Locate shortcodes.
		preg_match_all( '/'. $shortcode_regex .'/', $post_content, $located );

		// If no shortcodes were located, return.
		if ( empty( $located[0] ) ) {
			return $shortcodes;
		}

		// Loop through located shortcodes.
		foreach ( $located[0] as $i => $shortcode_string ) {

			// Add to return array.
			$shortcodes[] = $shortcode_string;

		}

		return $shortcodes;

	}

}

/**
 * Returns an instance of the Shortcode_Locator class.
 *
 * @see    Shortcode_Locator::get_instance()
 *
 * @return object Shortcode_Locator
 */
function shortcode_locator() {
	return Shortcode_Locator::get_instance();
}

shortcode_locator();
