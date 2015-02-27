<?php
	
	class Shortcode_Finder_Settings {
		
		private static $admin_page_slug = 'shortcode_finder_settings';
		private static $admin_menu_title = 'Shortcode Finder';
		private static $admin_page_title = 'Shortcode Finder Settings';
		private static $option_name = 'shortcode_finder';
		private static $_instance = null;
	
		public static function get_instance() {
			
			if ( self::$_instance == null )
				self::$_instance = new Shortcode_Finder_Settings();
	
			return self::$_instance;
			
		}
	
		public function __construct() {
						
			/* Register admin page */
			add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
				
		}

		/* Register admin page */
		function register_admin_page() {
			
			add_submenu_page( 'options-general.php', self::$admin_page_title, self::$admin_menu_title, 'update_core', self::$admin_page_slug, array( __CLASS__, 'render_admin_page' ) );
			
		}
		
		/* Render admin page */
		function render_admin_page() {

			/* Save settings where necessary */
			self::maybe_save_settings();
			
			/* Get current settings */
			$current_settings = self::get_settings();

			/* Open page */
			echo '<div class="wrap">';
			
			/* Page title */
			echo '<h2>'. self::$admin_page_title .'</h2>';
			
			/* Open form tag */
			echo '<form method="post">';
			
			/* Add nonce */
			wp_nonce_field( 'shortcode_finder_settings' );
			
			/* Open table */
			echo '<table class="form-table">';
			
			/* Add post column setting */
			echo '<tr><th scope="row">Display shortcodes column on post types</th><td><fieldset>';
			
			/* Loop through post types */
			foreach( Shortcode_Finder::$post_types as $post_type_name => $post_type_value ) {
				
				echo '<label for="display_column_'. $post_type_name .'">';
				echo '<input name="display_column[]" type="checkbox" id="display_column_'. $post_type_name .'" value="'. $post_type_name .'"'. ( ( in_array( $post_type_name, $current_settings['display_column'] ) ) ? ' checked' : '' ) .' >';
				echo ' '. $post_type_value;
				echo '</label>';
				echo '<br />';
				
			}
			
			/* Close post column setting */
			echo '</fieldset></td></tr>';
			
			/* Close table */
			echo '</table>';
			
			/* Add submit button */
			submit_button( 'Save Settings' );
			
			/* Close form tag */
			echo '</form>';
			
			/* Close page */
			echo '</div>';
			
		}
		
		/* Maybe save settings */
		function maybe_save_settings() {
			
			/* If the form wasn't submitted, exit. */
			if ( empty( $_POST ) ) return;
			
			/* Verify the nonce. */
			check_admin_referer( 'shortcode_finder_settings' );
			
			/* Prepare the new settings. */
			$new_settings = array(
				'display_column'		=>	$_POST['display_column']	
			);
			
			/* Save the settings */
			self::save_settings( $new_settings );
			
			/* Display success message */
			echo '<div class="updated"><p>Settings have been saved.</p></div>';
			
		}
		
		/* Save settings */
		function save_settings( $new_settings ) {
			
			update_option( self::$option_name, json_encode( $new_settings ) );
			
		}

		/* Get settings */
		function get_settings() {
			
			/* Get option */
			$settings = get_option( self::$option_name );
			
			/* If option does not exist, return array with default options. */
			if ( ! $settings ) {
				
				return array(
					'display_column'		=>	array()
				);
				
			}
			
			/* If option does exist, return the decoded JSON array. */
			return json_decode( $settings, true );
			
		}
		
	}

	Shortcode_Finder_Settings::get_instance();