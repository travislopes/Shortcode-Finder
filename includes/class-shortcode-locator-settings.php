<?php

/**
 * Shortcode Locator settings.
 *
 * @since     1.0
 * @author    Travis Lopes
 * @copyright Copyright (c) 2016, Travis Lopes
 */
class Shortcode_Locator_Settings {

	/**
	 * Defines the settings page slug.
	 *
	 * @since  1.1
	 * @access public
	 * @var    string $slug The slug used for the settings page.
	 */
	public $slug = 'shortcode_locator_settings';

	/**
	 * Option name where settings are stored.
	 *
	 * @since  1.0
	 * @access private
	 * @var    string $option_name The slug used for the settings page.
	 */
	private $option_name = 'shortcode_locator';

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





	// # SETTINGS PAGE -------------------------------------------------------------------------------------------------

	/**
	 * Register settings page.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function register_settings_page() {

		// Add plugin page to Tools menu.
		add_submenu_page(
			'options-general.php', // Parent slug.
			$this->settings_page_title(), // Page title.
			shortcode_locator()->plugin_page_title(), // Menu title.
			'edit_posts', // Capability.
			$this->slug, // Menu slug.
			array( $this, 'render_settings_page' ) // Page function.
		);

	}

	/**
	 * Render and display settings page.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function render_settings_page() {

		// Initialize HTML string.
		$html = '';

		// Start page.
		$html .= '<div class="wrap">';

		// Display page title.
		$html .= '<h2>'. $this->settings_page_title() .'</h2>';

		// Save settings.
		$html .= $this->maybe_save_settings();
		
		// Get settings and post types.
		$settings   = $this->get_settings();
		$post_types = shortcode_locator()->get_post_types();

		// Start form.
		$html .= '<form method="post">';
		$html .= wp_nonce_field( $this->slug, '_wpnonce', true, false );

		// Start form table.
		$html .= '<table class="form-table">';

		// Start post columns setting.
		$html .= '<tr><th scope="row">' . esc_html__( 'Display shortcodes column on post types', 'shortcode-locator' ) .'</th><td><fieldset>';

		// Loop through post types.
		foreach( shortcode_locator()->get_post_types() as $name => $label ) {

			// Get checked state.
			$checked = in_array( $name, $settings['display_column'] );
			
			// Get field ID.
			$id = 'display_column_' . esc_attr( $name );
			
			// Start label.
			$html .= '<label for="' . $id . '">';
			
			// Display checkbox.
			$html .= '<input name="display_column[]" type="checkbox" id="' . $id . '" value="' . esc_attr( $name ) . '" ' . ( $checked ? 'checked': '' ) . ' />';
			
			// Display post type label.
			$html .= esc_html( $label );
			
			// End label.
			$html .= '</label><br />';

		}

		// End post columns setting.
		$html .= '</fieldset></td></tr>';

		// End form table.
		$html .= '</table>';

		// Display submit button.
		$html .= get_submit_button( esc_html__( 'Save Settings', 'shortcode-locator' ) );

		// End form and page.
		$html .= '</form></div>';

		echo $html;

	}

	/**
	 * Maybe save settings.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function maybe_save_settings() {

		// If the form was not submitted, exit.
		if ( empty( $_POST ) ) {
			return;
		}

		// Verify the nonce.
		check_admin_referer( $this->slug );

		// Prepare the new settings.
		$new_settings = array( 'display_column' => $_POST['display_column'] );
		
		// Sanitize the settings.
		$new_settings['display_column'] = array_map( 'sanitize_text_field', $new_settings['display_column'] );

		// Update settings.
		$updated = $this->update_settings( $new_settings );

		// Display settings update result.
		if ( $updated ) {
			echo '<div class="updated"><p>' . esc_html__( 'Settings have been saved.', 'shortcode-locator' ) . '</p></div>';
		} else {
			echo '<div class="error"><p>' . esc_html__( 'Settings could not be saved.', 'shortcode-locator' ) . '</p></div>';
		}

	}

	/**
	 * Prepares settings page title.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @return string
	 */
	public function settings_page_title() {

		return esc_html__( 'Shortcode Locator Settings', 'shortcode-locator' );

	}





	// # HELPERS -------------------------------------------------------------------------------------------------------

	/**
	 * Get all settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_settings() {

		// Get default settings.
		$default_settings = array( 'display_column' => array() );

		// Get settings.
		$settings = get_option( $this->option_name );

		// If settings were found, return decoded settings.
		return $settings ? json_decode( $settings, true ) : $default_settings;

	}

	/**
	 * Get a setting.
	 *
	 * @since  1.1
	 * @access public
	 *
	 * @param  string $setting_name Settings name.
	 *
	 * @return mixed
	 */
	public function get_setting( $setting_name = '' ) {

		// Get settings.
		$settings = $this->get_settings();

		// If setting name is empty, return all settings.
		if ( empty( $setting_name ) ) {
			return $settings;
		}

		return isset( $settings[ $setting_name ] ) ? $settings[ $setting_name ] : null;

	}

	/**
	 * Update settings.
	 *
	 * @since  1.0
	 * @access public

	 * @param  array $settings Settings being updated.
	 *
	 * @return bool
	 */
	public function update_settings( $settings ) {

		// Prepare settings.
		$settings = json_encode( $settings );

		// Save settings.
		return update_option( $this->option_name, $settings );

	}

}


/**
 * Returns an instance of the Shortcode_Locator_Settings class.
 *
 * @see    Shortcode_Locator_Settings::get_instance()
 *
 * @return object Shortcode_Locator_Settings
 */
function shortcode_locator_settings() {
	return Shortcode_Locator_Settings::get_instance();
}
