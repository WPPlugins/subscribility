<?php
/**
 * Class WP99234_Users
 *
 * Class to handle the user synchronisation between Wordpress and Troly
 *
 * @package wp99234
 */
class WP99234_Users {

  var $users_create_endpoint;
  
  var $users_endpoint;

    var $current_user_update_endpoint = false;

    function __construct(){

        $this->setup_hooks();

    }

    function setup_hooks(){

        add_action( 'init', array( $this, 'on_init' ) );

        add_action( 'profile_update', array( $this, 'export_user' ), 10, 2 );
        add_action( 'user_register' , array( $this, 'export_user' ), 10, 1 );

//        add_action( 'show_user_profile', array( $this, 'display_extra_profile_fields' ) );
//        add_action( 'edit_user_profile', array( $this, 'display_extra_profile_fields' ) );

        add_filter( 'woocommerce_customer_meta_fields', array( $this, 'filter_customer_meta_fields' ) );

        add_action( 'woocommerce_checkout_order_processed', array( $this, 'on_checkout_order_processed' ), 10, 2 );

        add_action( 'wp_login', array( $this, 'on_login' ), 10, 2 );

        //Import users via admin-ajax.
        add_action( 'wp_ajax_subs_import_users', array( $this, 'on_ajax_subs_import_users' ) );

        //Export users via admin-ajax.
        add_action( 'wp_ajax_subs_export_users', array( $this, 'on_ajax_subs_export_users' ) );

        //authenticate
        //add_action( 'authenticate', array( $this, 'handle_authentication' ), 50, 3 );

    }

    function on_init(){

        $this->users_create_endpoint = WP99234_Api::$endpoint . 'customers';
      
        $this->users_endpoint = WP99234_Api::$endpoint . 'customers.json';

        //If we are logged in, get us the endpoint to update the user.
        if( is_user_logged_in() ){

            $user_subs_id = get_user_meta( get_current_user_id(), 'subs_id', true );

            if( $user_subs_id ){
                $this->current_user_update_endpoint = WP99234_Api::$endpoint . sprintf( 'customers/%s.json', $user_subs_id );
            }

        }

    }
  
    /**
     * Import a customer into wordpress.
     *
     * @param $user_data
     *
     * @return int|WP_Error
     */
    function import_user( $user_data ,$pass=''){

        if( ! defined( 'WP99234_DOING_SUBS_USER_IMPORT' ) ){
            define( 'WP99234_DOING_SUBS_USER_IMPORT', true );
        }

        $_user_data = array(
            'first_name'           => $user_data->fname,
            'last_name'            => $user_data->lname,
            'user_email'           => $user_data->email,
            'user_login'           => $user_data->email,
            'show_admin_bar_front' => false
        );

        $is_update = false;

        $user_id = false;

        //Look first by SUBS ID
        $user = $this->get_user_by_subs_id( $user_data->id );

        if( $user ){
            $user_id = $user->ID;
        }

        //Else the email
        if( ! $user_id ){
            $user_id = email_exists( $user_data->email );
        }

        //Else the username.
        if( ! $user_id ){
            $user_id = username_exists( $user_data->email );

            //Why would 2 similar function return a different error result??
            if( $user_id === null ){
                $user_id = false;
            }
        }

        //If we have no user, generate a password and ensure they are a customer.
        if( $user_id === false ){
            //$pass = wp_generate_password();
            $_user_data['user_pass'] = $pass;
            $_user_data['role'] = 'customer';
        } else {
            //Tag the user id in WP so that user will be updated instead of creating a new one.
            $_user_data['ID'] = $user_id; //Update the user.
            $pass = false;
            $is_update = true;
        }

        if( $is_update ){
            $user_id = wp_update_user( $_user_data );
        } else {
            $user_id = wp_insert_user( $_user_data );
        }

        if( is_wp_error( $user_id ) ){
            return $user_id;
        }

        /**
         * Ensure that all the users current memberships are in the current companies memberships.
         *
         * Also make the user memberships data an associative array so processing and searching later becomes much easier.
         */
        if( isset( $user_data->current_memberships ) && is_array( $user_data->current_memberships ) ){

            //@TODO - store this in an object somewhere so we can avoid unnecessary DB lookups.
            $current_company_memberships = get_option( 'wp99234_company_membership_types' );

            $current_memberships_raw = $user_data->current_memberships;

            $current_memberships = array();

            /**
             * Cycle through the memberships for the user from SUBS, ensure that the membership is in the current company memberships.
             * If it is, add it to the memberships array with the ID as the array key.
             */
            foreach( $current_memberships_raw as $current_membership_raw ){

                if( isset( $current_company_memberships[$current_membership_raw->membership_type_id] ) ){
                    $current_memberships[$current_membership_raw->membership_type_id] = $current_membership_raw;
                }

            }

            //Set the memberships to the data to be saved as meta.
            $user_data->current_memberships = $current_memberships;

        }

    // Iterate through the data map and insert all mapped meta
    foreach( $this->user_meta_map() as $key => $field ){

            if( strpos( $key, 'country') > 0 ){
                $val = WP99234()->_api->get_formatted_country_code( $user_data->{$field} );
            } else {
                if( isset( $user_data->{$field} ) ){
                    $val = $user_data->{$field};
                } else {
                    $val = '';
                }
            }

            update_user_meta( $user_id, $key, $val );

    }

      // Add metas that are required for wc and are not present in subs data
//	    update_user_meta( $user_id, 'billing_country', 'AU' );
//	    update_user_meta( $user_id, 'shipping_country', 'AU' );

        // Add the subs_id to the user meta
        update_user_meta( $user_id, 'subs_id', $user_data->id );
    
        // Add the last time this user was updated by subs to user meta
        update_user_meta( $user_id, 'last_updated_by_subs', date('d/m/Y g:i A') );

        //Handle address logic
        if( $user_data->same_billing == true ){
            update_user_meta( $user_id, 'billing_address_1', $user_data->delivery_address  );
            update_user_meta( $user_id, 'billing_city'     , $user_data->delivery_suburb   );
            update_user_meta( $user_id, 'billing_postcode' , $user_data->delivery_postcode );
            update_user_meta( $user_id, 'billing_state'    , $user_data->delivery_state    );
            update_user_meta( $user_id, 'billing_country'  , WP99234()->_api->get_formatted_country_code( $user_data->delivery_country  ) );
        }

        // flag whether or not the user has CC details stored in SUBS.
        if( isset( $user_data->cc_number ) && strpos( $user_data->cc_number, '#' ) !== false ){
            update_user_meta( $user_id, 'has_subs_cc_data', true );
        }

        return $user_id;

    }

    /**
     * Updates a customers data in wordpress.
     *
     * @param $user_id
     * @param $user_data
     */
    function update_customer_metadata( $user_id, $user_data ){

        foreach( $this->user_meta_map() as $key => $field ){

            if( is_object( $user_data ) ){
                $value = ( isset( $user_data->{$key} ) ) ? $user_data->{$key} : '';
            } elseif( is_array( $user_data ) ){
                $value = ( isset( $user_data[$key] ) ) ? $user_data[$key] : '';
            }

            update_user_meta( $user_id, $key, $value );

        }

        return $user_id;

    }

    /**
     *
     * @hooked profile_update
     * @param $user_id
     * @param $old_user_data
     */
    function on_user_update( $user_id, $old_user_data ){

        $this->export_user( $user_id, $old_user_data );

    }

//    /**
//     *
//     */
//    function on_user_create(){
//
//    }

    /**
     * Export a user to SUBS.
     *
     * Pass true to the $quiet param to disable admin messages.
     *
     * @param $user_id
     * @param null $old_user_data
     * @param array $override_data
     * @param $quiet
     *
     * @return array|bool|mixed
     */
    function export_user( $user_id, $old_user_data = null, $override_data = array(), $quiet = false  ){

        $user = get_user_by( 'id', $user_id );

        if( ! $user ){
            return false;
        }

        //If we are checking out and haven't yet reached the order_processed hook, skip this.
        if( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ){
            if( ! defined( 'WP99234_ALLOW_USER_UPDATE' ) || ! WP99234_ALLOW_USER_UPDATE ){
                return false;
            }
        }

        //If we are importing users, this is unecessary.
        if( ( defined( 'WP99234_DOING_SUBS_USER_IMPORT' ) && WP99234_DOING_SUBS_USER_IMPORT ) ){
            return;
        }

        $subs_id = get_user_meta( $user_id, 'subs_id', true );
      
        $meta = array(
            'fname'                 => 'billing_first_name',
            'lname'                 => 'billing_last_name',
            'email'                 => 'billing_email',
            'gender'                => 'gender',
            'phone'                 => 'billing_phone',
            'birthday'              => 'birthday',
            'notify_shipments'      => 'notify_shipments',
            'notify_payments'       => 'notify_payments',
            'notify_newsletters'    => 'notify_newsletters',
            'notify_renewals'       => 'notify_renewals',
            'delivery_instructions' => 'delivery_instructions',
            'same_billing'          => 'same_billing',
            'mobile'                => 'mobile',
            'company_name'          => 'billing_company_name',
        );

        //Allow CC fields to be updated if checking out and not using the existing card.
        if( ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) && ( ! isset( $_POST['use_existing_card'] ) ) ){
            $meta['cc_name']      = 'cc_name';
            $meta['cc_number']    = 'cc_number';
            $meta['cc_exp_month'] = 'cc_exp_month';
            $meta['cc_exp_year']  = 'cc_exp_year';
        }

        $user_data = array(
            'customer' => array()
        );

        /**
         * Build the user meta data into the customer array.
         */
        foreach( $meta as $key => $meta_field ){

            //Use the override data over the meta-data
            $value = ( isset( $override_data[$meta_field] ) ) ? $override_data[$meta_field] : $user->{$meta_field};

            //We need to validate that gender is in the list of m, f or -
            if( $key == 'gender' ){

                if( ! $value || empty( $value ) || ! in_array( $value, array( 'm', 'f', '-' ) ) ){
                    $value = '-';
                }

            }

            $user_data['customer'][$key] = $value ;

        }
        
        if (!$subs_id) {
          // always notify of shipments
          $user_data['customer']['notify_shipments'] = '@|mail';
          
          // if they are a member lets also notify of newsletters and payments
          if (isset($user->current_memberships)) {
            $user_data['customer']['notify_newsletters'] = '@|mail';
            $user_data['customer']['notify_payments'] = '@|mail';
          }
        }
      
        if(!isset($_POST['use_existing_card']) && !isset($meta['cc_number']) && isset($_POST) && isset($_POST['cc_number'])) {
          
          $cc_exp = explode('/', $_POST['cc_exp']);
          
          if (count($cc_exp) > 1) {
            $user_data['customer']['cc_name'] = $_POST['cc_name'];
            $user_data['customer']['cc_number'] = $_POST['cc_number'];
            $user_data['customer']['cc_exp_month'] = $cc_exp[0];
            $user_data['customer']['cc_exp_year'] = $cc_exp[1];
            $user_data['customer']['cc_cvv'] = $_POST['cc_cvv'];
          }
        }
      
        $shipping_state = $user->shipping_state;//$this->get_formatted_state( $user->shipping_state );
        $billing_state = $user->billing_state;// $this->get_formatted_state( $user->billing_state );

        /**
         * Handle Billing and shipping logic.
         *
         * Also handles edge cases where the user has mixed data, the address doesn't get fuddled.
         */
        if( $user->shipping_address_1 && strlen( $user->shipping_address_1 ) > 0 ){
            $user_data['customer']['delivery_address']  = $user->shipping_address_1;
            $user_data['customer']['delivery_suburb']   = $user->shipping_city;
            $user_data['customer']['delivery_state']    = $shipping_state;
            $user_data['customer']['delivery_postcode'] = $user->shipping_postcode;
            $user_data['customer']['delivery_country']  = WP99234()->_api->get_formatted_country_name( $user->shipping_country );
        } else {
            $user_data['customer']['delivery_address']  = $user->billing_address_1;
            $user_data['customer']['delivery_suburb']   = $user->billing_city;
            $user_data['customer']['delivery_state']    = $billing_state;
            $user_data['customer']['delivery_postcode'] = $user->billing_postcode;
            $user_data['customer']['delivery_country']  = WP99234()->_api->get_formatted_country_name( $user->billing_country );
        }

        if( $user->same_billing != true ){
            //We need to send them both sets of details.
            $user_data['customer']['same_billing']     = false;
            $user_data['customer']['billing_address']  = $user->billing_address_1;
            $user_data['customer']['billing_suburb']   = $user->billing_city;
            $user_data['customer']['billing_state']    = $billing_state;
            $user_data['customer']['billing_postcode'] = $user->billing_postcode;
            $user_data['customer']['billing_country']  = WP99234()->_api->get_formatted_country_name( $user->billing_country );
        } else {
            //Same Billing is true.
            $user_data['customer']['same_billing'] = true;
        }
    
        if (isset($_POST['customers_tags'])) {
          
          $user_data['customer']['customers_tags'] = array();
          
          foreach ($_POST['customers_tags'] as $tag) {
            $user_data['customer']['customers_tags'][] = $tag;
          }
          
        }
      
        /**
         * Add in the raw user password.
         *
         * account_password - Woocommerce checkout
         *
         * pass2 - WP-Admin Create new user
         *
         */
        $password = false;
        if( isset( $_POST['account_password'] ) ){
            $password = trim( $_POST['account_password'] );
        }

        if( isset( $_POST['pass2'] ) ){
            $password = trim( $_POST['pass2'] );
        }

        if( $password ){
            $user_data['customer']['user_attributes']['password'] = $password;
        }

        $reporting_options = get_option('wp99234_reporting_sync');
    
        if ($subs_id) {
            $method = 'PUT';
            $message = 'Updating user (id: ' . $subs_id . ', email: ' . $user_data['customer']['email'] . ') on Troly';
        } else {
            $method = 'POST';
            $message = 'Exporting new user (email: ' . $user_data['customer']['email'] . ') to Troly';
        }
            
        $endpoint = WP99234()->_users->get_update_endpoint_for_user_id( $subs_id );
        $results = WP99234()->_api->_call( $endpoint, $user_data, $method );

        //Ensure the SUBS ID is recorded
        if( $results->id && ! $subs_id ){
            update_user_meta( $user_id, 'subs_id', $results->id );
        }

        $errors = (array)$results->errors;
    
        if( ! empty( $errors ) ){
          
            if ($subs_id) {
                $message .= '\nFailed to update user on Troly because of: ' . WP99234()->get_var_dump($errors);
            } else {
                $message .= '\nFailed to export user to Troly because of: ' . WP99234()->get_var_dump($errors);
            }
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Export', 'Customer Export to Subs', $message);
            }
          
            return $results;
        }

        //If we are checking out, save the hashed CC details and flag the user as having data.
        if( (defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT) || isset($user_data['customer']['cc_number']) ){

            if( $results->cc_number ){
                update_user_meta( $user_id, 'has_subs_cc_data', 'yes' );
                update_user_meta( $user_id, 'cc_number', $results->cc_number );
            }

        }

        if (isset($_POST['customers_tags']) && $results->customers_tags) {
            update_user_meta( $user_id, 'customers_tags', $results->customers_tags);                    
        }
      
        if( is_admin() && ! $quiet ){
            WP99234()->_admin->add_notice( __( 'User was successfully exported to Troly.', 'wp99234' ), 'success' );
        }

        if ($subs_id) {
            $message .= '\nSuccessfully updated user on Troly';
        } else {
            $message .= '\nSuccessfully exported user to Troly';
        }
      
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Export', 'Customer Export to Subs', $message);
        }
      
        return true;

    }

    /**
     * Find a user based on the given subs ID.
     *
     * @param $subs_id
     *
     * @return bool|mixed
     */
    function get_user_by_subs_id( $subs_id ){

        $user_query = new WP_User_Query( array( 'meta_key' => 'subs_id', 'meta_value' => $subs_id ) );

        $users = $user_query->get_results();

        if( $users && ! empty( $users ) ){
            return array_shift( $users );
        }

        return false;

    }

  /**
   * Get the mapping of subs user fields to wp user meta
   * @return array
   */
  function user_meta_map(){

        return array(
            'first_name'            => 'fname',
            'last_name'             => 'lname',
            'gender'                => 'gender',
            'phone'                 => 'phone',
            //'cc_name'               => 'cc_name',
            'cc_number'             => 'cc_number',
            //'cc_exp_month'          => 'cc_exp_month',
            //'cc_exp_year'           => 'cc_exp_year',
            'birthday'              => 'birthday',
            'notify_shipments'      => 'notify_shipments',
            'notify_payments'       => 'notify_payments',
            'notify_newsletters'    => 'notify_newsletters',
            'notify_renewals'       => 'notify_renewals',
            'same_billing'          => 'same_billing',
            'mobile'                => 'mobile',
            //'company_name'          => 'company_name',

            'billing_first_name'    => 'fname',
            'billing_last_name'     => 'lname',
            'billing_company'       => 'company_name',
            'shipping_first_name'   => 'fname',
            'shipping_last_name'    => 'lname',
            'shipping_company'      => 'company_name',

            'billing_address_1'     => 'billing_address',
            'billing_city'          => 'billing_suburb',
            'billing_state'         => 'billing_state',
            'billing_postcode'      => 'billing_postcode',
            'billing_country'       => 'billing_country',

            'shipping_address_1'    => 'delivery_address',
            'shipping_city'         => 'delivery_suburb',
            'shipping_state'        => 'delivery_state',
            'shipping_postcode'     => 'delivery_postcode',
            'shipping_country'      => 'delivery_country',

            'delivery_area'         => 'delivery_area',
            'billing_area'          => 'billing_area',
            'delivery_region'       => 'delivery_region',
            'billing_region'        => 'billing_region',

            'delivery_instructions' => 'delivery_instructions',

            'current_memberships'   => 'current_memberships',
            'customers_tags'        => 'customers_tags'

        );

  }

  /**
   * Handle bulk imports
   */

    /**
     * Handle Bulk Import. If is SSE event, will send appropriate messages.
     *
     * @param bool $is_sse
     */
  function handle_bulk_import( $is_sse = false ){

        //Set the importing users define.
        //define( 'WP99234_DOING_SUBS_USER_IMPORT', true );

        //This could take some time.
        @set_time_limit( 0 );

        $start_time = time();
        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Starting Customer Import';

        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'Starting Customer Import.' );
        }

        $page = 1;

        $limit_per_call = 100;

        $endpoint = esc_url_raw( add_query_arg( array(
            'l'                   => $limit_per_call,
            'current_memberships' => true,
            'p'                   => $page
        ), $this->users_endpoint ) );

    $import_is_allowed = true;
    $response = WP99234()->_api->_call( $endpoint );

    if ( $is_sse )
      WP99234()->send_sse_message( $start_time, __( 'Processing response from Troly...', 'wp99234' ));
      
      
    if( is_null( $response ) ) {
      if( $is_sse ){
          WP99234()->send_sse_message( $start_time, 'Import Failed - Invalid Response', 'fatal', 0 );
      }
      $message .= '\nImport Failed - Invalid response.';
      $import_is_allowed = false;
    } elseif($response->count <= 0){
      if( $is_sse ){
          WP99234()->send_sse_message( $start_time, 'Import Failed - No customers found', 'fatal', 0 );
      }
      $message .= '\nImport Failed - No customers found';
    }
      if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
        wp99234_log_troly($message, $success = false, 'Import', 'Bulk Customer Import', $message);
      }


        /**
         * Gather ALL users to be imported into one array, this may be via multiple calls to subs
         */

        $total_to_import = $response->count;

        $results_to_import = $response->results;

        $ready_to_process = count( $results_to_import );

        while( $total_to_import > $ready_to_process ){

            $page++;

            //Limit the paging to 100. If the site has more than 10000 users, we need to run a manual CSV import.
            if( $page >= 100 ){
                WP99234()->send_sse_message( $start_time, 'An Error has occurred.', 'error', 0 );
                $message .= '\nAn Error has occurred: too many customers, manual csv import required, import failed.';
                if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                    wp99234_log_troly($message, $success = false, 'Import', 'Bulk Customer Import', $message);
                }
                exit;
            }

            if( $is_sse ){
                WP99234()->send_sse_message( $start_time, 'Gathering Customers. Page ' . $page . ' ( ' . $ready_to_process . ' of ' . $total_to_import . ' ) .......', 'message', 0 );
                $message .= '\nGathering Customers. Page ' . $page . ' ( ' . $ready_to_process . ' of ' . $total_to_import . ' ) ...';
            }

            $endpoint = esc_url_raw( add_query_arg( array(
                'l'                   => $limit_per_call,
                'current_memberships' => true,
                'p'                   => $page
            ), $this->users_endpoint ) );

            $response = WP99234()->_api->_call( $endpoint );

            //Validate that we have a result, try again if it fails the first time.
            if( ! is_array( $response->results ) ){

                $retry_count = 0;

                while( ! is_array( $response->results ) && $retry_count < 6 ){

                    if( $is_sse ){
                        WP99234()->send_sse_message( $start_time, 'Invalid response received. Waiting 5 seconds and trying again......', 'message', 0 );
                    }

                    $message .= '\nInvalid response received. Waiting 5 seconds and trying again...';
                  
                    sleep( 5 );

                    //Try Again.
                    $response = WP99234()->_api->_call( $endpoint );

                    $retry_count++;

                }

                //if we get to this stage and we still don't have results, we can just stop.
                if( ! is_array( $response->results ) ){

                    if( $is_sse ){
                        WP99234()->send_sse_message( $start_time, 'Invalid response received. Aborting......', 'error', 0 );
                    }
         
                    $message .= '\nInvalid response received. Aborting...';
                    if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                        wp99234_log_troly($message, $success = false, 'Import', 'Bulk Customer Import', $message);
                    }
                  
                    exit;

                }

            }

            $results_to_import = array_merge( $results_to_import, $response->results );

            $ready_to_process = count( $results_to_import );

        }

        //Remove duplicates.
        $results_to_import = array_map( 'unserialize', array_unique( array_map( 'serialize', $results_to_import ) ) );

        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'Importing ' . count( $results_to_import ) . ' customers .......', 'message', 0 );
            $message .= '\nImporting ' . count($results_to_import) . ' customers ...';
        }

        $progress = 0;

        $failed_user_ids = array();

        /**
         * Import all users in one foreach loop.
         */
        $imported = 0;

    foreach( $results_to_import as $user ){

            $user_id = $this->import_user( $user );

            $imported++;

            if( is_wp_error( $user_id ) ){

                $failed_user_ids[$user->id] = $user_id->get_error_message();

                if( $is_sse ){
                    //WP99234()->send_sse_message( $start_time, 'Failed to import user ID: ' . $user->id . ' (' . $user_id->get_error_message() . ')', 'message', $progress );
                } else {
                    WP99234()->_admin->add_notice( 'Customer (' . $user->id . ') failed to import: ' . $user_id->get_error_message(), 'error' );
                }
              
                $message .= '\nCustomer ' . $user->id . ' failed to import: ' . $user_id->get_error_message();

            } else {

                if( $is_sse ){
                    if( $imported % 10 == 0 ){
                        WP99234()->send_sse_message( $start_time, 'Successfully imported ' . $imported . ' customers', 'message', $progress );
                    } else {
                        //Send a blank message, just the progress.
                        WP99234()->send_sse_message( $start_time, '', 'message', $progress );
                    }
                }

            }

            $progress = number_format( ( $imported / count( $results_to_import ) ) * 100, 2 );

    }

        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'Successfully imported ' . $imported .  ' customers.', 'message', $progress );
        } else {
            WP99234()->_admin->add_notice( $imported . ' customers were successfully imported.', 'success' );
        }
    
        $message .= '\nSuccessfulled imported ' . $imported . ' customers';
    
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Import', 'Bulk Customer Import', $message);
        }

        if( ! empty( $failed_user_ids ) ){

            WP99234()->logger->error( 'The following customers failed to import. ' );
            WP99234()->logger->error( WP99234()->get_var_dump( $failed_user_ids ) );

            if( $is_sse ){
                WP99234()->send_sse_message( $start_time, 'Some customers failed to import. Please review the logs for detailed information. Usually this is due to the customer not having an email address on file.', 'message', $progress );
                //WP99234()->send_sse_message( $start_time, WP99234()->get_var_dump( $failed_user_ids ), 'message', $progress );
            }

        }

        if ( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'TERMINATE', 'message', 100 );
        } else {
            wp_redirect( admin_url( 'users.php' ) );
        }

        //Allow the current user to run the import again if required.
        $current_user = wp_get_current_user();
        $current_user->add_cap( 'manage_wp99234_users' );

        update_option( 'wp99234_user_import_has_run', true );

        exit;

  }

    /**
     * Update a user ( Customer ) in the SUBS system.
     *
     * @param $user_data
     * @return array|mixed
     */
    function update_subs_user( $user_data ){

        $data = array(
            'customer' => $user_data
        );

        if( $this->current_user_update_endpoint !== false ){

            $results = WP99234()->_api->_call( $this->current_user_update_endpoint, $data, 'PUT' );

        } else {

            //The user doesn't exist in SUBS, lets create one.
            $results = WP99234()->_api->_call( $this->users_endpoint, $data, 'POST' );

        }

        return $results;

    }

    /**
     * Get the update endpoint for a given user ID.
     *
     * If no user ID, gets the endpoint to add a user.
     *
     * @param $user_id
     *
     * @return string
     */
    function get_update_endpoint_for_user_id( $user_id ){

        if( $user_id ){
            return WP99234_Api::$endpoint . sprintf( 'customers/%s.json', $user_id );
        } else {
            return $this->users_endpoint;
        }

    }

    function filter_customer_meta_fields( $fields ){

        $fields['extra'] = array(
            'title' => __( 'Extra Profile Information', 'wp99234' ),
            'fields' => array(
                'gender' => array(
                    'label' => __( 'Gender', 'wp99234' ),
                    'description' => 'Must be either m, f or -'
                ),
//                'phone' => array(
//                    'label' => __( 'Phone Number', 'wp99234' ),
//                    'description' => ''
//                ),
                'birthday' => array(
                    'label' => __( 'Birthday', 'wp99234' ),
                    'description' => ''
                ),
                'notify_shipments' => array(
                    'label' => __( 'Notify Shipments', 'wp99234' ),
                    'description' => ''
                ),
                'notify_payments' => array(
                    'label' => __( 'Notify Newsletter', 'wp99234' ),
                    'description' => ''
                ),
                'notify_renewals' => array(
                    'label' => __( 'Notify Renewals', 'wp99234' ),
                    'description' => ''
                ),
                'delivery_instructions' => array(
                    'label' => __( 'Delivery Instructions', 'wp99234' ),
                    'description' => ''
                ),
                'same_billing' => array(
                    'label' => __( 'Same Billing', 'wp99234' ),
                    'description' => ''
                ),
                'mobile' => array(
                    'label' => __( 'Mobile Number', 'wp99234' ),
                    'description' => ''
                ),
//                'company_name' => array(
//                    'label' => __( 'Company Name', 'wp99234' ),
//                    'description' => ''
//                )
            )
        );

        return $fields;

    }

//    function get_formatted_state( $state ){
//
//        if( ! $state || empty( $state ) ) {
//            return $state;
//        }
//
//        switch( strtolower( $state ) ){
//
//            case 'queensland':
//                return 'QLD';
//                break;
//
//            case 'new south wales':
//                return 'NSW';
//                break;
//
//            case 'australian capital territory':
//                return 'ACT';
//                break;
//
//            case 'northern territory':
//                return 'NT';
//                break;
//
//            case 'south australia':
//                return 'SA';
//                break;
//
//            case 'tasmania':
//                return 'TAS';
//                break;
//
//            case 'victoria':
//                return 'VIC';
//                break;
//
//            case 'western australia':
//                return 'WA';
//                break;
//
//            default:
//                return $state;
//                break;
//
//        }
//
//    }

    /**
     * Handle user creation / update on checkout.
     *
     * @param $order_id
     * @param $posted
     *
     * @return bool
     * @throws Exception
     */
    function on_checkout_order_processed( $order_id, $posted ){

        define( 'WP99234_ALLOW_USER_UPDATE', true );

        if( ! is_user_logged_in() ){
            WP99234()->logger->error( 'Order ' . $order_id . ' was created, and the user was not logged in.' );
            return false;
        }

        $override_data = array();

        //If we are updating our card, add it to the user override data.
        if( ! isset( $_POST['use_existing_card'] ) || $_POST['use_existing_card'] !== 'yes' ){

            if( isset( $_POST['wp99234_payment_gateway-card-number'] ) ){

                $exp_array = explode( '/', str_replace( ' ', '', wp_kses( $_POST['wp99234_payment_gateway-card-expiry'], array() ) ) );

                $exp_month   = $exp_array[0];
                $exp_year    = $exp_array[1];
                $card_number = wp_kses( $_POST[ 'wp99234_payment_gateway-card-number' ], array() );
                $card_name   = wp_kses( $_POST[ 'wp99234_payment_gateway-card-name' ]  , array() );

                $override_data = array(
                    'cc_name'      => $card_name,
                    'cc_number'    => $card_number,
                    'cc_exp_month' => $exp_month,
                    'cc_exp_year'  => $exp_year
                );

            }

        }

        $user_id = get_current_user_id();

        $order = new WC_Order( $order_id );

        $billing_address = $order->get_formatted_billing_address();
        $shipping_address = $order->get_formatted_shipping_address();

        if( $billing_address !== $shipping_address ){
            update_user_meta( $user_id, 'same_billing', false );
        } else {
            update_user_meta( $user_id, 'same_billing', true );
        }

        //Update user first / last name.
        update_user_meta( $user_id, 'first_name', $posted['billing_first_name'] );
        update_user_meta( $user_id, 'last_name', $posted['billing_last_name'] );

        //Phone and company name
        update_user_meta( $user_id, 'phone', $posted['billing_phone'] );
        update_user_meta( $user_id, 'company_name', $posted['billing_company'] );

        if( isset( $posted['order_comments'] ) ){
            update_user_meta( $user_id, 'delivery_instructions', esc_html( $posted['order_comments'] ) );
        }

        $data = $this->export_user( get_current_user_id(), null, $override_data );

        $errors = (array)$data->errors;

        if( ! empty( $errors ) ){
            throw new Exception( __( 'An error has occurred, and we could not process your payment. Please ensure your credit card details are correct and try again. You will be contacted via phone ASAP to ensure your order is processed as soon as possible.', 'wp99234' ) );

            ob_start();
            var_dump( $errors );
            $errs = ob_get_contents();
            ob_end_clean();
            WP99234()->logger->error( $errs );

        }

    }

    /**
     * Handle user membership pricing fetch if none already exist for the user.
     *
     * @notes
     * I will leave this function here as it may come in handy during the setup to authorise login using troly.
     * The code the get user membership data on login is no longer required as it is now imported on the initial import and pushed to WP when a user is updated in subs.
     *
     * @param $user_login
     * @param WP_User $user
     */
    public function on_login( $user_login, WP_User $user ){

        /*if( ! WP99234()->_api ){
            return;
        }*/

    }

    /**
     * Handle an AJAX call to import the users via SUBS api.
     */
    function on_ajax_subs_import_users(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        $this->handle_bulk_import( true );

        exit;

    }

    /**
     * Handle the export of all current customers to SUBS.
     */
    function on_ajax_subs_export_users(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        $users = get_users( array(
            'role' => 'Customer'
        ));

        $timestart = time();

        $message = 'Starting export of customers to subs, exporting ' . count($users) . ' customers';
        WP99234()->send_sse_message( $timestart, sprintf( __( 'Exporting %s users', 'wp99234' ), count( $users ) ) );

        $success = 0;

        $total_to_export = count( $users );
        $next_set = 0;
        $cron_increment_run_time = time() + 60;
        $user_ids = array();
      
        foreach ( $users as $user ) {
          $user_ids[] = $user->ID;
        }

        WP99234()->send_sse_message( $timestart, 'Queuing export of ' . $total_to_export . ' customers.', 'message', 100 );
        $message .= '\nQueuing export of ' . $total_to_export . ' customers.';      
      
        while ($next_set < $total_to_export) {
          
          $slice = array_slice($user_ids, $next_set, 5); 
          
          // should always exist but just an extra check
          if (isset($slice)) {
            // schedule a one time cron task to run and export the above users slice
            wp_schedule_single_event( $cron_increment_run_time, 'wp99234_cron_export_users', array($slice) );
          }
          
          $cron_increment_run_time += 600;
          $next_set += 5;
          
        };

        WP99234()->send_sse_message( $timestart, 'Customer export has been scheduled to run and will complete over the day.', 'message', 100 );

        WP99234()->send_sse_message( $timestart, 'TERMINATE', 'message', 100 );
        
        $message .= '\nCustomer export has been successfully scheduled to run and export ' . $total_to_export . ' customers.';
      
        $reporting_options = get_option('wp99234_reporting_sync');
        
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Export', 'Bulk Customers Export', $message);
        }
      
        update_option( 'wp99234_user_export_has_run', true );

        exit;

    }



    /**
     * Authenticate user with Subs if they have a subs_id.
     *
     * @param $user
     * @param $login
     * @param $password
     */
    function handle_authentication( $user, $login, $password ){

        $user_obj = get_user_by( 'login', $login );

        if( $user_obj ){

            $subs_id = get_user_meta( $user_obj->ID, 'subs_id', true );

            if( $subs_id && $subs_id !== '' ){

                $signin_data = array(
                    'user' => array(
                        'email'    => $user_obj->user_email,
                        'password' => (string)$password
                    )
                );

                $endpoint = sprintf( '%s/users/sign_in.json', WP99234_Api::$endpoint );

            }

        } else {
            return $user;
        }

        $break = 1;

    }
}
