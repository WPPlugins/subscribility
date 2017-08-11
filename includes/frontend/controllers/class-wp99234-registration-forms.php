<?php
/**
 * Wrapper class to handle the registration form
 */

class WP99234_Registration_Forms extends WP99234_Forms {

    var $submit_name = 'registration_submit';

    var $nonce_name = '_wp99234_registration_nonce';

    var $nonce_action = 'wp99234_handle_registration';

    var $template = 'registration_form.php';

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

        $fields = array(
            'first_name' => array(
                'required' => __( 'Please enter your first name', 'wp99234' ),
            ),
            'last_name' => array(
                'required' => __( 'Please enter your last name', 'wp99234' ),
            ),
            'reg_email' => array(
                //'email_exists' => __( 'Your email address is already registered. Please login.', 'wp99234' ),
                'is_email'     => __( 'Please enter a valid email address.', 'wp99234' ),
                'required' => __( 'Please enter your email address', 'wp99234' ),
            ),
            'phone' => array(),
            'mobile' => array(),
            'company_name' => array(),
            'shipping_address_1' => array(
                'required' => __( 'Please enter your Shipping address.', 'wp99234' ),
            ),
            'shipping_suburb' => array(
                'required' => __( 'Please enter your shipping suburb.', 'wp99234' ),
            ),
            'shipping_postcode' => array(
                'required'   => __( 'Please enter your shipping postcode', 'wp99234' ),
                'numeric' => __( 'Please enter a valid postcode.', 'wp99234' ),
            ),
            'shipping_state' => array(
                'required' => __( 'Please enter a valid state.', 'wp99234' ),
            ),
            'shipping_country' => array(
                'required' => __( 'Please enter a valid country.', 'wp99234' ),
            ),
            'use_existing_card' => array(),
            'selected_membership' => array(
                'required'   => __( 'Please select a membership option', 'wp99234' ),
                'is_numeric' => __( 'Invalid membership option.', 'wp99234' )
            ),
            'shipping_instructions' => array()
        );
      
        if(!is_user_logged_in()){
            $fields['user_pass'] = array(
                'required' => __( 'Please enter password', 'wp99234' ),
            );
            $fields['conf_pass'] = array(
                'required' => __( 'Please confirm your password', 'wp99234' ),
            );
        }   
        //CC Data if the user wants to update it or doesn't have an existing one.
        if( ! isset( $_POST['use_existing_card'] ) || $_POST['use_existing_card'] !== 'yes' ){
            $fields['cc_name'] = array(
                'required' => __( 'Please enter the name on your card', 'wp99234' ),
            );
            $fields['cc_number'] = array(
                'required' => __( 'Please enter your credit card number.', 'wp99234' ),
            );
            $fields['cc_exp'] = array(
                'required' => __( 'Please enter your card expiry date.', 'wp99234' ),
                'contains' => array( 
                  'check_val' => '/', 
                  'error_msg' => __( 'Incorrect format for credit card expiry date. Please enter the expiry date in the format MM/YY.', 'wp99234' )
                ),
            );
        }

        $data = array();

        foreach( $fields as $key => $validation ){

            $value = ( isset( $_POST[$key] ) ) ? sanitize_text_field( $_POST[$key] ) : '' ;

            if( ! empty( $validation ) ){
                $value = $this->validate_field( $key, $value, $validation );
            }

            $data[$key] = $value;

        }

        if (empty($_POST['phone']) && empty($_POST['mobile'])) {
            wc_add_notice( __( 'Please enter at least one contact number.', 'wp99234' ), 'error' );
            return false;
        }
      
        if( is_user_logged_in() ){
            if( $this->user_is_registered_for_membership( get_current_user_id(), $data['selected_membership'] ) ){
                wc_add_notice( __( 'You are already registered for that membership. Please contact us if you have any issues.', 'wp99234' ), 'error' );
                return false;
            }
        }

        //If we have errors, GTFO
        if( ! empty( $this->errors ) ){
            return false;
        }

        /**
         *
         */

        $membership_obj = new StdClass(); 
        $membership_obj->membership_type_id = $data['selected_membership'];
        if(!is_user_logged_in()){
            $password= wp_hash_password($data[ 'user_pass' ]);
        }else{
            global $current_user;
            $current_user = wp_get_current_user();
            $password= $current_user->user_pass;
        }

        $post_data = array(
            'customer' => array(
                'fname'                  => $data[ 'first_name' ],
                'lname'                  => $data[ 'last_name' ],
                'email'                  => $data[ 'reg_email' ],
                'phone'                  => $data[ 'phone' ],
                'mobile'                 => $data[ 'mobile' ],
                'password'               => $password,
                'company_name'           => $data[ 'company_name' ],
                'delivery_address'       => $data[ 'shipping_address_1' ],
                'delivery_suburb'        => $data[ 'shipping_suburb' ],
                'delivery_postcode'      => $data[ 'shipping_postcode' ],
                'delivery_state'         => $data[ 'shipping_state' ],
                'delivery_country'       => WP99234()->_api->get_formatted_country_name( $data[ 'shipping_country' ] ),
                'delivery_instructions'  => $data[ 'shipping_instructions' ],
                'memberships_attributes' => array(
                    0 => $membership_obj
                )
            )
        );



        if( $data['use_existing_card'] == '' ){

            $exp_array = explode( '/', str_replace( ' ', '', wp_kses( $data['cc_exp'], array() ) ) );

            $exp_month   = $exp_array[0];
            $exp_year    = $exp_array[1];

            if (!is_numeric($exp_month) || !is_numeric($exp_year)) {
              wc_add_notice( __( 'Incorrect format for credit card expiry date. Please enter the expiry date in the format MM/YY.', 'wp99234' ), 'error' );
              return false;
            }
          
            $post_data['customer']['cc_name']      = $data['cc_name'];
            $post_data['customer']['cc_number']    = $data['cc_number'];
            $post_data['customer']['cc_exp_month'] = $exp_month;
            $post_data['customer']['cc_exp_year']  = $exp_year;

        }

        if (isset($_POST['customers_tags'])) {
          
          $post_data['customer']['customers_tags'] = array();
          
          foreach ($_POST['customers_tags'] as $tag) {
            $post_data['customer']['customers_tags'][] = $tag;
          }
          
        }

        $user_id = false;
        $subs_id = false;
        $method = 'POST';

        if( is_user_logged_in() ){
            $user_id = get_current_user_id();
        }

        if( ! $user_id ){
            $user_id = email_exists( $data['reg_email'] );
        }

        if( $user_id ){

            //Mark the user as updating if they are logged in (already a member ).
            $subs_id = get_user_meta( $user_id, 'subs_id', true );

            if( $subs_id ){
                $post_data['customer']['id'] = $subs_id;
                $method = 'PUT';
            }

        }
      
        if (!$subs_id) {
            // registration form forces membership so we can safely add them to all notifications
            $post_data['customer']['notify_newsletters'] = '@|mail';
            $post_data['customer']['notify_shipments'] = '@|mail';
            $post_data['customer']['notify_payments'] = '@|mail';
        }

        $endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $subs_id );
        $results = WP99234()->_api->_call( $endpoint, $post_data, $method );
        //If they are a new user, import them from the SUBS data.
        if( $results && isset( $results->id ) ){

            $errors = (array)$results->errors;

            if( ! empty( $errors ) ){
                wc_add_notice( 'Your registration could not be processed, Please contact us if you wish to proceed.', 'error' );
                return false;
            }
            
            //Always import the user so that the membership data is saved, address is validated and saved as their delivery address even if they already exist..
            $userId = WP99234()->_users->import_user( $results,$data[ 'user_pass' ]);
            wc_add_notice( 'Thank you for registering, Your registration has been successfully processed.', 'success' );
             if(isset($_POST)){
                wp_set_current_user($userId);
                wp_set_auth_cookie($userId);
                // #FIXME: We used to redirect to this page and append a question mark (?)
                // Probably to avoid refreshing from posting the data again. 
                // This prevents Woocommerce notices from being displayed, so was removed. 
                // wp_redirect(""); 
             }
        }else {
            wc_add_notice( 'An unknown error has occurred. Please try again.', 'error' );
        }

    }

}
