<?php
/**
 * Troly WP99234 Admin Menu definition.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP99234_Admin_Menu' ) ) :

/**
 * WP99234_Admin_Menu Class.
 */
class WP99234_Admin_Menu {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'operations_menu' ), 20 );
	}

	/**
    * Create the administration menu.
    */
	public function admin_menu() {

        $this->admin_pagehook = add_menu_page( __( 'Troly Welcome', 'wp99234' ), __( 'Troly', 'wp99234' ), 'manage_options', 'wp99234-operations', array( $this, 'admin_page_html' ), '', 56 );

	}

	/**
	* Load in the HTML template.
	*/
	function admin_page_html(){
		$this->operations_page_html();
	}

		/**
	 * Add menu item.
	 */
	public function settings_menu() {

        $settings_page = add_submenu_page( 'wp99234-operations', __( 'Troly Settings', 'wp99234' ),  __( 'Settings', 'wp99234' ) , 'manage_options', 'wp99234', array( $this, 'settings_page_html' ) );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}
	

	public function settings_page_init() {
		
	}
	/**
	 * Init the settings page.
	 */
	public function settings_page_html() {
		WP99234_Admin_Settings::output();
	}


		/**
	 * Add menu item.
	 */
	public function operations_menu() {
		
		$settings_page = add_submenu_page( 'wp99234-operations', __( 'Troly Operations', 'wp99234' ),  __( 'Operations', 'wp99234' ) , 'manage_options', 'wp99234-operations', array( $this, 'operations_page_html' ) );

		//add_action( 'load-' . $settings_page, array( $this, 'operations_page_init' ) );
	}
	

	public function operations_page_init() {
		print __FUNCTION__;
	}
	/**
	 * Init the settings page.
	 */
	public function operations_page_html() {
		WP99234_Admin_operations::output();
	}
}

endif;

return new WP99234_Admin_Menu();
