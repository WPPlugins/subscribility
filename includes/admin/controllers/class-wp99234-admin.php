<?php
/**
 * Troly Admin
 *
 * Manage the various configuration and functions available to administrators.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Admin' ) ) :

/**
 * WP99234_Admin class.
 */
class WP99234_Admin {

    var $admin_pagehook; 
    /**
     * Constructor.
     */
    public function __construct() {

        add_action( 'init', array( $this, 'includes' ) );

        add_action( 'admin_init', array( $this, 'buffer' ), 1 );
        add_action( 'admin_init', array( $this, 'check_configuration' ));
        add_action( 'admin_init', array( $this, 'prevent_admin_access' ));

        add_action( 'admin_init', array( $this, 'admin_init' ));

        
        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	  	
        add_filter( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );
    }

    public function check_configuration() {

        $consumer_key = get_option( 'wp99234_consumer_key' );
        $resource_key = get_option( 'wp99234_resource_key' );
        $check_no = get_option( 'wp99234_check_no' );

        if ($consumer_key == '' || $resource_key == '' || $check_no == '') {
            $this->errors[] = __( 'Please visit the <a href="admin.php?page=wp99234&tab=remote">Settings > Remote</a> page to complete the Troly plugin configuration.' );
       // } else {
         //   echo "$consumer_key | $resource_key | $check_no";
        }

    }



    /*public function __construct() {

        $this->includes();

        $this->load_menu();

        //$this->admin_init();
        $this->admin_notices();

        print "CCCC";
        die();


        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
        add_action( 'save_post', array( $this, 'on_save_post' ), 10, 3 );
        add_filter( 'post_row_actions', array( $this, 'filter_row_actions' ), 10, 2 );

        add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );

    }*/


    /**
     * Output buffering allows admin screens to make redirects later on.
     */
    public function buffer() {
        ob_start();
    }

    /**
     * Include any classes we need within admin.
     */
    public function includes() {
        include_once( 'class-wp99234-admin-page.php' );
        include_once( 'class-wp99234-admin-settings.php' );
        include_once( 'class-wp99234-admin-operations.php' );
        include_once( 'class-wp99234-admin-menu.php' );
    }
	
    /**
     * Prevent any user who cannot 'edit_posts' (subscribers, customers etc) from accessing admin.
     */
    public function prevent_admin_access() {
        $prevent_access = false;

        if ( 'yes' === get_option( 'wp99234_lock_down_admin', 'yes' ) && ! is_ajax() && basename( $_SERVER["SCRIPT_FILENAME"] ) !== 'admin-post.php' && ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_wp99234' ) ) {
            $prevent_access = true;
        }

        $prevent_access = apply_filters( 'wp99234_prevent_admin_access', $prevent_access );

        if ( $prevent_access ) {
            wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }
    }

    /**
     * Display errors in wp-admin.
     */
    protected function admin_notices() {

        $allowed_err_html = array(
            'a'      => array(
                'href'  => array(),
                'title' => array()
            ),
            'br'     => array(),
            'em'     => array(),
            'strong' => array(),
        );

        $msgs = get_option( 'wp99234_admin_notices' );

        if( ! empty( $msgs ) ){

            foreach( $msgs as $msg ){

                switch( $msg['type'] ):

                    case 'error':

                        echo sprintf( '<div class="error"><p>%s</p></div>', wp_kses( $msg['msg'], $allowed_err_html ) );

                        break;

                    case 'success':

                        echo sprintf( '<div class="updated"><p>%s</p></div>', wp_kses( $msg['msg'], $allowed_err_html ) );

                        break;

                    case 'notice':
                    default:

                        echo sprintf( '<div class="update-nag"><p>%s</p></div>', wp_kses( $msg['msg'], $allowed_err_html ) );

                        break;

                endswitch;

            }

            //Remove them from display.
            $this->clear_notices();

        }

        /*if ( ! empty( WP99234()->errors ) ) {
            foreach ( WP99234()->errors as $err ) {
                echo '<div class="error"><p>' . wp_kses( $err, $allowed_err_html ) . '</p></div>';
            }
        }*/

    }

    /**
     * Setup the adminisration settings using the WordPress Settings API.
     */
    function admin_init() {

		// Allow the current user to access product management if they could do a bulk import
		if (current_user_can( 'manage_options' ) && !current_user_can( 'manage_wp99234_products' )) {
			wp_get_current_user()->add_cap( 'manage_wp99234_products' );
		}

		
        register_setting( $this->admin_pagehook, 'wp99234_consumer_key' );
        register_setting( $this->admin_pagehook, 'wp99234_ressource_key' );
        register_setting( $this->admin_pagehook, 'wp99234_check_no' );

        /**
         * Handle bulk product import from wp-admin if ?do_wp99234_initial_product_import=1
         */
        if( current_user_can( 'manage_options' ) && isset( $_GET['do_wp99234_initial_product_import'] ) && $_GET['do_wp99234_initial_product_import'] == 1 ) {
            WP99234()->_products->handle_bulk_import();
        }

        /**
         * Handle bulk user import from wp-admin if ?do_wp99234_initial_user_import=1
         *
         * @deprecated, this should be done from the troly options page.
         */
//        if( current_user_can( 'manage_options' ) && isset( $_GET['do_wp99234_initial_user_import'] ) && $_GET['do_wp99234_initial_user_import'] == 1 ){
//            WP99234()->_users->handle_bulk_import();
//        }

        /**
         * Get the current membership types on command
         */
        if( current_user_can( 'manage_options' ) && isset( $_GET['do_wp99234_import_membership_types'] ) && $_GET['do_wp99234_import_membership_types'] == 1 ){
            WP99234()->_company->get_company_membership_types();
        }

        /**
         * Handle a timestamp check.
         */
        if( current_user_can( 'manage_options' ) && isset( $_GET['do_wp99234_timestamp_check'] ) && $_GET['do_wp99234_timestamp_check'] == 1 ){
            $this->check_timestamp();
        }

        /**
         * Handle single product import
         */
        if( current_user_can( 'manage_options' ) && isset( $_GET['wp99234_import_single_product'] ) ){

            if( wp_verify_nonce( $_GET['_wp99234_nonce'], 'wp99234_import_product' ) ){
                WP99234()->_products->import_by_product_id( absint( $_GET['wp99234_import_single_product'] ) );
            }

        }

    }

    /**
     * Wrapper function to output a text-tyoe field (text, password, number etc. Will not work for a radio, checkbox or textarea form field)
     *
     * @param $args
     */
    function get_settings_field( $args ){

        $val = get_option( $args['name'] );

        echo '<input type="' . esc_attr( $args['type'] ) . '" name="' . esc_attr( $args['name'] ) . '" value="' . esc_attr( $val ) . '" />';

    }

    /**
     * Add a message to admin notices.
     *
     * @param $msg
     * @param $type
     */
    function add_notice( $msg, $type ){

        $opts = get_option( 'wp99234_admin_notices' );

        if( ! $opts ){
            $opts = array();
        }

        $opts[] = array(
            'type' => $type,
            'msg' => $msg
        );

        update_option( 'wp99234_admin_notices', $opts );

    }

    /**
     * Clear admin notices
     */
    function clear_notices(){
        update_option( 'wp99234_admin_notices', array() );
    }


    /**
     * Add custom meta boxes to wp-admin.
     */
    function add_meta_boxes( $post_type, $post ){

        if( ! in_array( $post_type, array( 'post', 'page' ) ) ){
            return;
        }

        add_meta_box(
            'wp99234_membership_settings',
            __( 'Membership Settings', 'wp99234' ),
            array( $this, 'render_membership_settings_meta_box' ),
            null,
            'advanced',
            'default',
            $post
        );

    }

    /**
     * Render the HTML in the membership settings meta box.
     *
     * @param $post
     */
    function render_membership_settings_meta_box( $post ){

        $is_hidden_content = get_post_meta( $post->ID, 'wp99234_hide_content', true );

        ?>
        <p class="form-row">
            <label for="wp99234_hide_content">
                <input type="checkbox" <?php checked( 1, $is_hidden_content ); ?> value="1" name="wp99234_hide_content" />
                <?php _e( 'Allow only members to access this content.', 'wp99234' ); ?>
            </label>
        </p>
        <?php

    }

    /**
     * Sanitize and save the custom post meta.
     *
     * @param $post_ID
     * @param $post
     * @param $update
     */
    function on_save_post( $post_ID, $post, $update ){

        if( ! in_array( $post->post_type, array( 'post', 'page' ) ) ){
            return;
        }

        if( isset( $_POST['wp99234_hide_content'] ) ){
            update_post_meta( $post_ID, 'wp99234_hide_content', 1 );
        } else {
            delete_post_meta( $post_ID, 'wp99234_hide_content' );
        }

    }
  
    /**
     * Filter woocommerce product row actions to display additional fields (or remove others).
     *
     * @param $actions
     * @param $post
     *
     * @return array
     */	
    function filter_row_actions( $actions, $post ){

        if( $post->post_type == WP99234()->_products->products_post_type ){
            $actions['wp99234_subs_product_id'] = sprintf(
              'Subs ID: %s, Last Updated (UTC+0): %s', 
              get_post_meta($post->ID, 'subs_id', true), 
              get_post_meta($post->ID, 'last_updated_by_subs', true)
            );
		  
            //$actions['wp99234_import_product'] = sprintf(
            //    '<a href="%s">%s</a>',
            //    esc_url( add_query_arg( array( 'wp99234_import_single_product' => $post->ID, '_wp99234_nonce' => wp_create_nonce( 'wp99234_import_product' ), ) ) ),
            //    __( 'Import From Subs', 'wp99234' )
            //);

        }

        return $actions;
    }

    /**
     * Save admin fields.
     *
     * Loops though the woocommerce options array and outputs each field.
     *
     * @param array $options Options array to output
     * @return bool
     */
    public static function save_fields( $options ) {
        if ( empty( $_POST ) ) {
            return false;
        }

        // Options to update will be stored here and saved later.
        $update_options = array();

        // Loop options and get values to save.
        foreach ( $options as $option ) {
            if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) ) {
                continue;
            }

            // Get posted value.
            if ( strstr( $option['id'], '[' ) ) {
                parse_str( $option['id'], $option_name_array );
                $option_name  = current( array_keys( $option_name_array ) );
                $setting_name = key( $option_name_array[ $option_name ] );
                $raw_value    = isset( $_POST[ $option_name ][ $setting_name ] ) ? wp_unslash( $_POST[ $option_name ][ $setting_name ] ) : null;
            } else {
                $option_name  = $option['id'];
                $setting_name = '';
                $raw_value    = isset( $_POST[ $option['id'] ] ) ? wp_unslash( $_POST[ $option['id'] ] ) : null;
            }

            // Format the value based on option type.
            switch ( $option['type'] ) {
                case 'checkbox' :
                    $value = is_null( $raw_value ) ? 'no' : 'yes';
                    break;
                case 'textarea' :
                    $value = wp_kses_post( trim( $raw_value ) );
                    break;
                case 'multiselect' :
                case 'multi_select_countries' :
                    $value = array_filter( array_map( 'wc_clean', (array) $raw_value ) );
                    break;
                case 'image_width' :
                    $value = array();
                    if ( isset( $raw_value['width'] ) ) {
                        $value['width']  = wc_clean( $raw_value['width'] );
                        $value['height'] = wc_clean( $raw_value['height'] );
                        $value['crop']   = isset( $raw_value['crop'] ) ? 1 : 0;
                    } else {
                        $value['width']  = $option['default']['width'];
                        $value['height'] = $option['default']['height'];
                        $value['crop']   = $option['default']['crop'];
                    }
                    break;
                default :
                    $value = wc_clean( $raw_value );
                    break;
            }

            /**
             * Fire an action when a certain 'type' of field is being saved.
             * @deprecated 2.4.0 - doesn't allow manipulation of values!
             */
            if ( has_action( 'woocommerce_update_option_' . sanitize_title( $option['type'] ) ) ) {
                _deprecated_function( 'The woocommerce_update_option_X action', '2.4.0', 'woocommerce_admin_settings_sanitize_option filter' );
                do_action( 'woocommerce_update_option_' . sanitize_title( $option['type'] ), $option );
                continue;
            }

            /**
             * Sanitize the value of an option.
             * @since 2.4.0
             */
            $value = apply_filters( 'woocommerce_admin_settings_sanitize_option', $value, $option, $raw_value );

            /**
             * Sanitize the value of an option by option name.
             * @since 2.4.0
             */
            $value = apply_filters( "woocommerce_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

            if ( is_null( $value ) ) {
                continue;
            }

            // Check if option is an array and handle that differently to single values.
            if ( $option_name && $setting_name ) {
                if ( ! isset( $update_options[ $option_name ] ) ) {
                    $update_options[ $option_name ] = get_option( $option_name, array() );
                }
                if ( ! is_array( $update_options[ $option_name ] ) ) {
                    $update_options[ $option_name ] = array();
                }
                $update_options[ $option_name ][ $setting_name ] = $value;
            } else {
                $update_options[ $option_name ] = $value;
            }

            /**
             * Fire an action before saved.
             * @deprecated 2.4.0 - doesn't allow manipulation of values!
             */
            do_action( 'woocommerce_update_option', $option );
        }

        // Save all options in our array.
        foreach ( $update_options as $name => $value ) {
            update_option( $name, $value );
        }

        return true;
    }





    /**
     * Add an error message for display in admin on save.
     * @param string $error
     */
    public function add_error( $error ) {
        $this->errors[] = $error;
    }

    /**
     * Get admin error messages.
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Display admin error messages.
     */
    public function display_errors() {
        if ( $this->get_errors() ) {
            echo '<div id="woocommerce_errors" class="error notice is-dismissible">';
            foreach ( $this->get_errors() as $error ) {
                echo '<p>' . wp_kses_post( $error ) . '</p>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Change the admin footer text on Troly admin pages.
     *
     * @since  1.2
     * @param  string $footer_text
     * @return string
     */
    public function admin_footer_text( $footer_text ) {
        
        $current_screen = get_current_screen();
        $wp99234_pages  = wp99234_get_screen_ids();

        if ( isset( $current_screen->id ) && apply_filters( 'wp99234_display_admin_footer_text', in_array( $current_screen->id, $wp99234_pages ) ) ) {
            // Change the footer text
            if ( ! get_option( 'wp99234_admin_footer_text_rated' ) ) {
                $footer_text = sprintf( __( 'If you like <strong>Troly</strong> please leave us a %s&#9733;&#9733;&#9733;&#9733;&#9733;%s rating. A massive thank you from Troly in advance!', 'wp99234' ), '<a href="https://wordpress.org/support/view/plugin-reviews/subscribility?filter=5#postform" target="_blank" class="wc-rating-link wp99234-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'wp99234' ) . '">', '</a>' );
                wc_enqueue_js( "
                    jQuery( 'a.wc-rating-link' ).click( function() {
                        jQuery.post( '" . WC()->ajax_url() . "', { action: 'wp99234_rated' } );
                        jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                    });
                " );
            } else {
                $footer_text = __( 'Thank you for selling with Troly.', 'wp99234' );
            }
        }

        return $footer_text;
    }
}

endif;

return new WP99234_Admin();