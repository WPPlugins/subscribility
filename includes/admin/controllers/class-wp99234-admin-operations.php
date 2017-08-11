<?php
/**
 * Troly WP99234 Operations page class.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Admin_operations' ) ) :

/**
 * WC_Admin_Dashboard Class.
 */
class WP99234_Admin_Operations extends WP99234_Admin_Page {

    /**
     * Operations pages.
     *
     * @var array
     */
    private static $pages = array();

    /**
     * Include the operations page classes.
     */
    public static function get_operations_pages() {

        if ( empty( self::$pages ) ) {
            $pages = array();

            include_once( 'class-wp99234-admin-operations-page.php' );

            $settings[] = include( 'class-wp99234-admin-operations-sync.php' );
            $settings[] = include( 'class-wp99234-admin-operations-log.php' );

            self::$pages = apply_filters( 'wp99234_get_operations_pages', $pages );
        }

        return self::$pages;
    }

	public static function output() {
		global $current_tab;

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		do_action( 'wp99234_operations_start' );

		//wp_enqueue_script( 'woocommerce_settings', WC()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'select2' ), WC()->version, true );

        // Include settings pages
        self::get_operations_pages();

		// Get current tab/section
		$current_tab = empty( $_GET['tab'] ) ? 'sync' : sanitize_title( $_GET['tab'] );

		// Add any posted messages
		if ( ! empty( $_GET['wp99234_error'] ) ) {
			self::add_error( stripslashes( $_GET['wp99234_error'] ) );
		}

		if ( ! empty( $_GET['wp99234_message'] ) ) {
			self::add_message( stripslashes( $_GET['wp99234_message'] ) );
		}

        // Get tabs for the settings page
        $tabs = apply_filters( 'wp99234_operations_tabs_array', array() );

		include_once plugin_dir_path( __FILE__ ) . '../views/html-admin-operations.php';
	}

}

endif;

return new WP99234_Admin_Operations();