<?php
/**
 * Troly WP99234 General Settings.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Settings_General' ) ) :

/**
 * WP99234_Settings_General defines the general configurations
 */
class WP99234_Settings_General extends WP99234_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id    = 'general';
		$this->label = __( 'Options', 'wp99234' );

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

		$settings = apply_filters( 'wp99234_general_settings', array(
			
			array( 'title' => __( 'Miscellaneous', 'wp99234' ), 'type' => 'title', 'desc' => __( 'The following options allow you to customise how the Troly plugin will interact with your website.', 'wp99234' ), 'id' => 'miscellaneous_options' ),


			array(
				'title'    => __( 'Display a legal drinking age disclaimer', 'wp99234' ),
				'desc'     => __( 'Choose to and how to display a legal drinking age disclaimer on your website.', 'wp99234' ),
				'id'       => 'wp99234_display_legal_drinking_disclaimer',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'select',
				'desc_tip' => true,
				'options'  => array(
					'no' => __("Don't display a disclaimer (default)", 'wp99234'),
					'overlay' => __('Website overlay when first visiting the site', 'wp99234'),
					'checkout' => __('Notice on the checkout page', 'wp99234')
				)
			),
			
			array(
				'title'    => __( 'Message to display for disclaimer above', 'wp99234' ),
				'desc'     => __( 'Use this box to set the message to display for the legal drinking age disclaimer above.', 'wp99234' ),
				'id'       => 'wp99234_legal_drinking_disclaimer_text',
				'css'      => 'min-width:350px; min-height:80px;',
				'default'  => '',
				'type'     => 'textarea',
				'desc_tip' =>  true,
			),
      
			array( 'type' => 'sectionend', 'id' => 'miscellaneous_options'),

			array( 'title' => __( 'Product Display', 'wp99234' ), 'type' => 'title', 'desc' => __( 'The following options allow you to choose between using Troly product information, or setting product information in woocommerce.', 'wp99234' ), 'id' => 'product_display_options' ),


			array(
				'title'    => __( 'Use WooCommerce product images', 'wp99234' ),
				'desc'     => __( 'Choose between setting product images in WooCommerce or using Troly images.', 'wp99234' ),
				'id'       => 'wp99234_use_wc_product_images',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'checkbox',
				'desc_tip' =>  true,
			),
      
			array( 'type' => 'sectionend', 'id' => 'product_display_options'),
      
			array( 'title' => __( 'Data Synchronisation', 'wp99234' ), 'type' => 'title', 'desc' => __( 'The following options control the flow of information between your Website and Troly.', 'wp99234' ), 'id' => 'sync_options' ),


			array(
				'title'    => __( 'Products', 'wp99234' ),
				'desc'     => __( 'Select which way your products are syncing between Troly and Woocommerce.', 'wp99234' ),
				'id'       => 'wp99234_product_sync',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'select',
				'desc_tip' =>  true,
				'options'  => array(
					'both'=>__('Send AND receive products (Troly ↔ Website)','wp99234'),
					'push'=>__('Only send products (Troly ← Website)','wp99234'),
					'pull' =>__('Only receive products (Troly → Website)','wp99234'),)
			),
			
			array(
				'title'    => __( 'Customers', 'wp99234' ),
				'desc'     => __( 'Select which way your customers are syncing between Troly and Woocommerce.', 'wp99234' ),
				'id'       => 'wp99234_customer_sync',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'select',
				'desc_tip' =>  true,
				'options'  => array(
					'both'=>__('Send AND receive customers (Troly ↔ Website)','wp99234'),
					'push'=>__('Only send customers (Troly ← Website)','wp99234'),
					'pull' =>__('Only receive customers (Troly → Website)','wp99234'),)
			),
			/* TODO: we need a setting to associate club members to a different wp user type */

			array(
				'title'    => __( 'Clubs', 'wp99234' ),
				'desc'     => __( 'Shows how your clubs will be synced between Troly and Woocommerce', 'wp99234' ),
				'id'       => 'wp99234_club_sync',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'select',
				'desc_tip' =>  true,
				'options'  => array(
					'both'=>__('Send AND receive clubs (Troly ↔ Website)','wp99234'),
					// 'push'=>__('Only send clubs (Troly ← Website)','wp99234'),
					// 'pull' =>__('Only receive clubs (Troly → Website)','wp99234')
					)

			),

			array( 'type' => 'sectionend', 'id' => 'sync_options'),

			array( 'title' => __( 'Reporting', 'wp99234' ), 'type' => 'title', 'desc' => __( 'Define the type and also amount of information being displayed in the runtime console.', 'wp99234' ), 'id' => 'log_options' ),

			array(
				'title'    => __( 'Transaction tracking', 'wp99234' ),
				'desc'     => __( 'Select what should appear in the logs. Used only for troubleshooting.', 'wp99234' ),
				'id'       => 'wp99234_reporting_sync',
				'css'      => 'min-width:350px;',
				'default'  => '',
				'type'     => 'select',
				'desc_tip' =>  true,
				'options'  => array(
					'minimum'=>__('Orders and payment only','wp99234'),
					'medium' =>__('Products, clubs and customers','wp99234'),
					'verbose'=>__('Full details','wp99234'),)
			),

			array( 'type' => 'sectionend', 'id' => 'log_options'),

		) );

		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		
		$settings = $this->get_settings();

		WP99234_Admin_Settings::save_fields( $settings );
	}

}

endif;

return new WP99234_Settings_General();
