<?php
/**
 * Troly WP99234 Settings Page.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Settings_Page' ) ) :

/**
 * WC_Settings_Page.
 */
abstract class WP99234_Settings_Page {

	/**
	 * Setting page id.
	 *
	 * @var string
	 */
	protected $id = '';

	/**
	 * Setting page label.
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Constructor.
	 */
	public function _construct() {
		add_filter( 'wp99234_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wp99234_sections_' . $this->id, array( $this, 'output_sections' ) );
		add_action( 'wp99234_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wp99234_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Add this page to settings.
	 */
	public function add_settings_page( $pages ) {
		$pages[ $this->id ] = $this->label;

		return $pages;
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		return apply_filters( 'wp99234_get_settings_' . $this->id, array() );
	}

	/**
	 * Get sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return apply_filters( 'wp99234_get_sections_' . $this->id, array() );
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		$settings = $this->get_settings();

		WP99234_Admin_Settings::output_fields( $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		$settings = $this->get_settings();
		WP99234_Admin_Settings::save_fields( $settings );

		if ( $current_section ) {
			do_action( 'wp99234_update_options_' . $this->id . '_' . $current_section );
		}
	}
}

endif;
