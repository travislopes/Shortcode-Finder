<?php
	
	/*
	Plugin Name: Shortcode Finder
	Plugin URI: http://travislop.es/plugins/shortcode-finder/
	Description: Quickly find what and where shortcodes are being used
	Version: 1.0.0
	Author: travislopes
	Author URI: http://travislop.es
	*/

	class Shortcode_Finder {
		
		public static $admin_page_slug = 'shortcode_finder';
		private static $admin_page_title = 'Shortcode Finder';
		private static $_instance = null;
	
		public static function get_instance() {
			
			if ( self::$_instance == null )
				self::$_instance = new Shortcode_Finder();
	
			return self::$_instance;
			
		}
	
		public function __construct() {
			
			/* Register admin page */
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
			
		}

		/* Register admin page */
		function register_admin_page() {
			
			add_submenu_page( 'tools.php', self::$admin_page_title, self::$admin_page_title, 'update_core', self::$admin_page_slug, array( __CLASS__, 'render_admin_page' ) );
			
		}
		
		/* Render admin page */
		function render_admin_page() {
						
			/* Load table class */
			require_once 'shortcode-finder-table.php';

			/* Open page */
			echo '<div class="wrap">';
			
			/* Page title */
			echo '<h2>'. self::$admin_page_title .'</h2>';
			
			/* Display table */
			$shortcode_finder_table = new Shortcode_Finder_Table();
			$shortcode_finder_table->prepare_items();
			$shortcode_finder_table->display();			
			
			/* Close page */
			echo '</div>';
			
		}

		/* Get shortcodes for post */
		function get_shortcodes_for_post( $post_content, $shortcode = null ) {
			
			/* Get shortcode regex */
			$shortcode_regex = ( is_null( $shortcode ) ) ? get_shortcode_regex() : self::get_specific_shortcode_regex( $shortcode );
			
			/* Search for shortcodes */
			preg_match_all( '/'. $shortcode_regex .'/', $post_content, $shortcodes_found );
			
			/* Return found shortcodes */
			return $shortcodes_found[0];
			
		}
		
		/* Get regex for specific shortcode */
		function get_specific_shortcode_regex( $shortcode ) {
			
			return
				  '\\['                              // Opening bracket
				. '(\\[?)'                           // 1: Optional second opening bracket for escaping shortcodes: [[tag]]
				. "($shortcode)"                     // 2: Shortcode name
				. '(?![\\w-])'                       // Not followed by word character or hyphen
				. '('                                // 3: Unroll the loop: Inside the opening shortcode tag
				.     '[^\\]\\/]*'                   // Not a closing bracket or forward slash
				.     '(?:'
				.         '\\/(?!\\])'               // A forward slash not followed by a closing bracket
				.         '[^\\]\\/]*'               // Not a closing bracket or forward slash
				.     ')*?'
				. ')'
				. '(?:'
				.     '(\\/)'                        // 4: Self closing tag ...
				.     '\\]'                          // ... and closing bracket
				. '|'
				.     '\\]'                          // Closing bracket
				.     '(?:'
				.         '('                        // 5: Unroll the loop: Optionally, anything between the opening and closing shortcode tags
				.             '[^\\[]*+'             // Not an opening bracket
				.             '(?:'
				.                 '\\[(?!\\/\\2\\])' // An opening bracket not followed by the closing shortcode tag
				.                 '[^\\[]*+'         // Not an opening bracket
				.             ')*+'
				.         ')'
				.         '\\[\\/\\2\\]'             // Closing shortcode tag
				.     ')?'
				. ')'
				. '(\\]?)';                          // 6: Optional second closing brocket for escaping shortcodes: [[tag]]
			
			
		}
		
	}

	Shortcode_Finder::get_instance();