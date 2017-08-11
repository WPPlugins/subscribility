<?php
/**
 * Wrapper class to handle the registration form
 */

class WP99234_Newsletter_Forms extends WP99234_Forms {

    var $submit_name = 'newsletter_submit';

    var $nonce_name = '_wp99234_newsletter_nonce';

    var $nonce_action = 'wp99234_handle_newsletter';

    var $template = 'newsletter_form.php';

    var $errors = array();

    function __construct(){
        parent::__construct();
    }

    /**
     * Handle the form submission.
     *
     * @return bool
     */
    function handle_submit(){

        if( ! isset( $_POST[$this->nonce_name] ) ){
            return false;
        }

        if( ! wp_verify_nonce( $_POST[$this->nonce_name], $this->nonce_action ) ){
            return false;
        }

//        if( is_user_logged_in() ){
//            //Error out here??
//        }

        $fields = array(
            'first_name' => array(
                'required' => __( 'Please enter your first name', 'wp99234' ),
            ),
            'reg_email' => array(
                //'email_exists' => __( 'Your email address is already registered. Please login.', 'wp99234' ),
                'is_email'     => __( 'Please enter a valid email address.', 'wp99234' ),
                'required' => __( 'Please enter your email address', 'wp99234' ),
            )
        );

        $data = array();

        foreach( $fields as $key => $validation ){

            $value = ( isset( $_POST[$key] ) ) ? sanitize_text_field( $_POST[$key] ) : '' ;

            if( ! empty( $validation ) ){
                $value = $this->validate_field( $key, $value, $validation );
            }

            $data[$key] = $value;

        }

        if( email_exists( $data['reg_email'] ) ){
            $this->errors[] = __( 'That email address is already registered.', 'wp99234' );
        }

        //If we have errors, GTFO
        if( ! empty( $this->errors ) ){
            return false;
        }

        /**
         * Build the data to send to SUBS
         */
        $post_data = array(
            'customer' => array(
                'fname'              => $data[ 'first_name' ],
                'email'              => $data[ 'reg_email' ],
                'notify_newsletters' => '@'
            )
        );

        $endpoint = WP99234()->_users->get_update_endpoint_for_user_id( false );

        $results = WP99234()->_api->_call( $endpoint, $post_data, 'POST' );

        //If they are a new user, import them from the SUBS data.
        if( $results && isset( $results->id ) ){

            $errors = (array)$results->errors;

            if( ! empty( $errors ) ){
                wc_add_notice( 'Your registration could not be processed, Please contact us if you wish to proceed.', 'error' );
                return false;
            }

            //Always import the user so that the membership data is saved, address is validated and saved as their delivery address even if they already exist..
            WP99234()->_users->import_user( $results );

            wc_add_notice( 'Thank you for registering, Your registration has been successfully processed.', 'success' );

        } else {

            wc_add_notice( 'An unknown error has occurred. Please try again.', 'error' );

        }

    }

}