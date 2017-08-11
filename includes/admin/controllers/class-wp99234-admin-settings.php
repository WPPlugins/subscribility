<?php
/**
 * Admin -> Settings Configuration  This is where ther various tabs for all settings are loded
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Admin_Settings' ) ) :

/**
 * WP99234_Admin_Settings Class.
 */
class WP99234_Admin_Settings extends WP99234_Admin_Page {

	/**
	 * Setting pages.
	 *
	 * @var array
	 */
	private static $settings = array();

	/**
	 * Include the settings page classes.
	 */
	public static function get_settings_pages() {
		
		if ( empty( self::$settings ) ) {
			$settings = array();

			include_once( 'class-wp99234-admin-settings-page.php' );

			$settings[] = include( 'class-wp99234-admin-settings-general.php' );
			$settings[] = include( 'class-wp99234-admin-settings-remote.php' );

			self::$settings = apply_filters( 'wp99234_get_settings_pages', $settings );
		}

		return self::$settings;
	}

	public static function output() {
		global $current_section, $current_tab;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		do_action( 'wp99234_settings_start' );

		wp_enqueue_script( 'woocommerce_settings', WC()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'select2' ), WC()->version, true );

		/*wp_localize_script( 'woocommerce_settings', 'woocommerce_settings_params', array(
			'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'woocommerce' )
		) );*/

		// Include settings pages
		self::get_settings_pages();

		// Get current tab/section
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( $_GET['tab'] );
		//$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );

		// Save settings if data has been posted
		if ( ! empty( $_POST ) ) {
			self::save();
		}

		// Add any posted messages
		if ( ! empty( $_GET['wp99234_error'] ) ) {
			self::add_error( stripslashes( $_GET['wp99234_error'] ) );
		}

		if ( ! empty( $_GET['wp99234_message'] ) ) {
			self::add_message( stripslashes( $_GET['wp99234_message'] ) );
		}

		// Get tabs for the settings page
		$tabs = apply_filters( 'wp99234_settings_tabs_array', array() );

		include_once plugin_dir_path( __FILE__ ) . '../views/html-admin-settings.php';
	}

	public static function save() {
		global $current_tab;

		//if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'wp99234_admin_settings_' . $current_tab ) ) {
			//die( __( 'Action failed. Please refresh the page and retry.', 'wp99234' ) );
		//}

		// Trigger actions
		do_action( 'wp99234_settings_save_' . $current_tab );
		//do_action( 'woocommerce_update_options_' . $current_tab );
		//do_action( 'woocommerce_update_options' );

		self::add_message( __( 'Your settings have been saved.', 'wp99234' ) );
		
		// Clear any unwanted data and flush rules
		//delete_transient( 'woocommerce_cache_excluded_uris' );
		//WC()->query->init_query_vars();
		//WC()->query->add_endpoints();
		//flush_rewrite_rules();

		do_action( 'wp99234_settings_saved' );
	}

}

endif;

return new WP99234_Admin_Settings();