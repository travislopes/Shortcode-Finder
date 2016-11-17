<?php

require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

/**
 * Shortcode Locator table.
 *
 * @since     1.0
 * @author    Travis Lopes
 * @copyright Copyright (c) 2016, Travis Lopes
 * @uses      WP_List_Table
 */
class Shortcode_Locator_Table extends WP_List_Table {

	/**
	 * The current list of items.
	 *
	 * @since  1.0
	 * @access public
	 * @var    array
	 */
	public $items = array();

	/**
	 * Posts to display per page.
	 *
	 * @since  1.0
	 * @access private
	 * @var    int
	 */
	private $per_page = 25;

	/**
	 * Constructor.
	 *
	 * @since  1.0
	 * @access public
	 *
     * @param array|string $args {
     *     Array or string of arguments.
     *
     *     @type string $plural   Plural value used for labels and the objects being listed.
     *                            This affects things such as CSS class-names and nonces used
     *                            in the list table, e.g. 'posts'. Default empty.
     *     @type string $singular Singular label for an object being listed, e.g. 'post'.
     *                            Default empty
     *     @type bool   $ajax     Whether the list table supports Ajax. This includes loading
     *                            and sorting data, for example. If true, the class will call
     *                            the _js_vars() method in the footer to provide variables
     *                            to any scripts handling Ajax events. Default false.
     *     @type string $screen   String containing the hook name used to determine the current
     *                            screen. If left null, the current screen will be automatically set.
     *                            Default null.
     * }
	 */
	public function __construct( $args = array() ) {

		parent::__construct(
			array(
				'singular' => 'post',
				'plural'   => 'posts',
				'ajax'     => false,
			)
		);

	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function prepare_items() {

		// Define column headers.
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		// Get available post types.
		$post_types = shortcode_locator()->get_post_types();

		// Get filters.
		$filters              = $this->get_filters();
		$filters['post_type'] = empty( $filters['post_type'] ) ? array_keys( shortcode_locator()->get_post_types() ) : $filters['post_type'];
		$filters['shortcode'] = '[' . $filters['shortcode'] . ']';

		// Get posts.
		$posts = new WP_Query(
			array(
				'order'          => $filters['order'],
				'orderby'        => $filters['orderby'],
				'paged'          => $this->get_pagenum(),
				'posts_per_page' => $this->per_page,
				'post_type'      => $filters['post_type'],
				's'              => $filters['shortcode'],
			)
		);

		// Loop through posts.
		foreach( $posts->posts as $post ) {

			// Add post to table items.
			$this->items[] = array(
				'id'         => $post->ID,
				'shortcodes' => shortcode_locator()->get_shortcodes_for_post( $post->post_content ),
				'title'      => $post->post_title,
				'post_type'  => $post_types[$post->post_type]
			);

		}

		// Define pagination arguments.
		$this->set_pagination_args(
			array(
				'per_page'		=>	$this->per_page,
				'total_items'	=>	$posts->found_posts
			)
		);

	}

	/**
	 * Get a list of columns.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_columns() {

		return array(
			'title'      =>	esc_html__( 'Post Title', 'shortcode-locator' ),
			'post_type'  => esc_html__( 'Post Type', 'shortcode-locator' ),
			'shortcodes' => esc_html__( 'Shortcodes Used', 'shortcode-locator' ),
		);

	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_sortable_columns() {

		return array(
			'title'     => array( 'title', true ),
			'post_type' => array( 'post_type', false ),
		);

	}

	/**
	 * Renders and display a column's contents.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param  object $item        Active item.
	 * @param  string $column_name Active column.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {

		return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : null;

	}

	/**
	 * Renders and display a title column.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param  object $item Active item.
	 *
	 * @return string
	 */
	protected function column_title( $item ) {

		// Prepare row actions.
		$actions = array(
			'edit' => '<a href="'. get_edit_post_link( $item['id'] ) .'">' . esc_html__( 'Edit', 'shortcode-locator' ) . '</a>',
			'view' => '<a href="'. get_permalink( $item['id'] ) .'">' . esc_html__( 'Vide', 'shortcode-locator' ) . '</a>'
		);

		return $item['title'] . $this->row_actions( $actions );

	}

	/**
	 * Renders and display a shortcode column.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param  object $item Active item.
	 *
	 * @return string
	 */
	protected function column_shortcodes( $item ) {

		// Escape shortcode strings.
		$shortcodes = array_map( 'esc_html', $item['shortcodes'] );

		return implode( '<br />', $shortcodes );

	}

	/**
	 * Generate the table navigation above or below the table.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param  string $which Table navigation placement.
	 */
	protected function display_tablenav( $which ) {

		echo '<div class="tablenav '. esc_attr( $which ) .'">';

		$this->extra_tablenav( $which );
		$this->pagination( $which );

		echo '<br class="clear" /></div>';

	}

	/**
	 * Extra controls to be displayed between bulk actions and pagination.
	 *
	 * @since  1.0
	 * @access protected
	 *
	 * @param  string $which Table navigation placement.
	 */
	protected function extra_tablenav( $which ) {

		global $shortcode_tags;
		
		// Get current filters.
		$filters = $this->get_filters();
		
		// Get registered post types and shortcodes.
		$post_types = shortcode_locator()->get_post_types();
		$shortcodes = array_keys( $shortcode_tags );

		// Initialize HTMl string.
		$html = '';

		// Start container and form.
		$html .= '<div class="alignleft actions">';
		$html .= '<form method="get">';
		$html .= '<input type="hidden" name="page" value="'. shortcode_locator()->slug .'">';

		// Start post types filter.
		$html .= '<select name="filter_post_type">';
		$html .= '<option value="">' . esc_html( 'Filter By Post Type', 'shortcode-locator' ) . '</option>';

		// Add post types as filter options.
		foreach ( $post_types as $name => $label ) {
			$html .= '<option value="' . esc_html( $name ) . '" ' . selected( $filters['post_type'], $name, false ) . '>';
			$html .= esc_html( $label );
			$html .= '</option>';
		}

		// End post types filter drop down.
		$html .= '</select>';

		// Start shortcode filter drop down.
		$html .= '<select name="filter_shortcode">';
		$html .= '<option value="">' . esc_html( 'Filter By Shortcode', 'shortcode-locator' ) . '</option>';

		// Add shortcodes as filter options.
		foreach ( $shortcodes as $shortcode ) {
			$html .= '<option value="' . esc_html( $shortcode ) . '" ' . selected( $filters['shortcode'], $shortcode, false ) . '>';
			$html .= '[' . esc_html( $shortcode ) . ']';
			$html .= '</option>';
		}

		// End shortcode filter drop down.
		$html .= '</select>';

		// Display filter button.
		$html .= '<input type="submit" id="post-query-submit" class="button" value="Filter">';

		// End form and container.
		$html .= '</form></div>';

		echo $html;

	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since  1.0
	 * @access public
	 */
	public function no_items() {

		esc_html_e( 'No posts found.', 'shortcode-locator' );

	}
	
	/**
	 * Prepares and sanitizes set filters.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function get_filters() {

		return array(
			'order'     => isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'ASC',
			'orderby'   => isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'ASC',
			'post_type' => isset( $_REQUEST['filter_post_type'] ) ? sanitize_text_field( $_REQUEST['filter_post_type'] ) : null,
			'shortcode' => isset( $_REQUEST['filter_shortcode'] ) ? sanitize_text_field( $_REQUEST['filter_shortcode'] ) : null,
		);
		
	}

}
