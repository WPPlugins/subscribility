<?php
/**
 * Troly WP99234 Remote Settings.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Settings_Remote' ) ) :

/**
 * WC_Admin_Settings_General.
 */
class WP99234_Settings_Remote extends WP99234_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'remote';
		$this->label = __( 'Connect to Troly', 'wp99234' );

		add_filter( 'wp99234_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
		add_action( 'wp99234_settings_' . $this->id, array( $this, 'output' ) );
		add_action( 'wp99234_settings_save_' . $this->id, array( $this, 'save' ) );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {

		$settings = apply_filters( 'wp99234_remote_settings', array(

			array( 'title' => __( 'Account Access', 'sp99234' ), 'type' => 'title', 'desc' => __('The flowing values are used to communicate with Troly. Each can be found in the <a href="http://app.troly.io/a/single?addon=Wordpress">Addons</a> page of your account.'), 'id' => 'general_options' ),
			array(
				'title'    => __( 'Consumer key', 'wp99234' ),
				'desc'     => __( 'Provided by Troly to uniquely identify your Website.', 'wp99234' ),
				'id'       => 'wp99234_consumer_key',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' =>  true,
			),

			array(
				'title'    => __( 'Resource Key', 'wp99234' ),
				'desc'     => __( 'Provided by Troly, uniquely identifies the data accessed.', 'wp99234' ),
				'id'       => 'wp99234_resource_key',
				'default'  => '',
				'type'     => 'text',
				'css'      => 'min-width: 350px;',
				'desc_tip' =>  true,
			),

			array(
				'title'    => __( 'Check Number', 'wp99234' ),
				'desc'     => __( 'Provided by Troly, uniquely validates the above two.', 'wp99234' ),
				'id'       => 'wp99234_check_no',
				'css'      => 'min-width: 350px;',
				'default'  => '',
				'type'     => 'text',
				'desc_tip' => true
			),


			array( 'type' => 'sectionend', 'id' => 'general_options'),


		) );

		return apply_filters( 'wp99234_remote_settings_' . $this->id, $settings );
	}

}

endif;

return new WP99234_Settings_Remote();
