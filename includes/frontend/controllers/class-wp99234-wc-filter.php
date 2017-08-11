<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * (SEB) 
 * The name "Endpoint" Seems tremendously confusing here... 
 * I believe it shoudl change to someting
 * more apprioriate.
 */
class WP99234_WC_Filter {

    var $order_api_endpoint;

    function __construct(){

        $this->setup_actions();
        $this->setup_filters();

        $this->order_api_endpoint = WP99234_Api::$endpoint . 'orders.json'; /** why does this connect to orders? should this not be generic? **/

    }

    /**
     * Setup Woocommerce specific actions.
     */
    function setup_actions(){

        //woocommerce_checkout_init // 10/03/17 no longer being used but the hook reference is good so keeping it commented out.
        //add_action( 'woocommerce_checkout_init', array( $this, 'on_woocommerce_checkout_init' ) );

        //Disable functionality for unentitled users.
        add_action( 'load-edit.php', array( $this, 'load_edit_page' ) );
        add_action( 'load-post.php', array( $this, 'load_post_page' ) );
        add_action( 'load-post-new.php', array( $this, 'load_post_new_page' ) );

        ///Disable Woocommerce Emails.
        //add_action( 'woocommerce_email', array( $this, 'disable_emails' ) );

        //woocommerce_admin_order_actions_end
        add_action( 'woocommerce_admin_order_actions_end', array( $this, 'after_order_actions' ), 10, 1 );

        //admin_init
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        //woocommerce_before_template_part  checkout/thankyou.php
        add_action( 'woocommerce_before_template_part', array( $this, 'before_template_part' ), 10, 4 );

        //check_wp99234_payment_status
        add_action( 'wp_ajax_check_wp99234_payment_status', array( $this, 'check_wp99234_payment_status' ) );

        add_action( 'wp_insert_comment', array( $this, 'handle_ratings_and_reviews' ), 10, 2 );

    }

    /**
     * Setup woocommerce specific filters.
     */
    function setup_filters(){

        //Remove the 2nd line address from billing and shipping fields to ensure data is compatible with woocommerce.
        add_filter( 'woocommerce_billing_fields', array( $this, 'filter_billing_fields' ) );
        add_filter( 'woocommerce_shipping_fields', array( $this, 'filter_shipping_fields' ) );

        add_filter( 'woocommerce_register_post_type_shop_order', array( $this, 'filter_shop_order_args' ) );
        add_filter( 'woocommerce_register_post_type_product', array( $this, 'filter_product_args' ) );

        //No email classes, No Emails.
        //add_filter( 'woocommerce_email_classes', '__return_empty_array' );

        //woocommerce_get_price
        add_filter( 'woocommerce_product_get_price', array( $this, 'filter_get_price' ), 10, 2 );

        //woocommerce_new_customer_data
        add_filter( 'woocommerce_new_customer_data', array( $this, 'filter_new_customer_data' ) );

        //wc_get_template_part
        add_filter( 'wc_get_template', array( $this, 'filter_wc_get_template' ), 10, 5 );

    }

    function admin_init(){

        if( isset( $_GET['export_order_to_subs'] ) && wp_verify_nonce( $_GET['_nonce'], 'wp99234_export_order' ) ){

            $order_id = (int)$_GET['export_order_to_subs'];

            $this->export_order( $order_id );

        }

    }

    /**
     * Enforce guest checkout disabled.
     *
     * @param $checkout
     */
    public function on_woocommerce_checkout_init( $checkout ){

        if( $checkout->enable_guest_checkout ){
            update_option( 'woocommerce_enable_guest_checkout', 'no' );
            $checkout->enable_guest_checkout = false;
        }

    }
	
    /**
     * Filter the billing fields displayed during checkout etc.
     *
     * @param $fields
     *
     * @return mixed
     */
    function filter_billing_fields( $fields ){

        if( isset( $fields['billing_address_2'] ) ){
            unset( $fields['billing_address_2'] );
        }

        return $fields;

    }

    /**
     * Filter the shipping fields displayed during checkout etc.
     *
     * @param $fields
     *
     * @return mixed
     */
    function filter_shipping_fields( $fields ){

        if( isset( $fields['shipping_address_2'] ) ){
            unset( $fields['shipping_address_2'] );
        }

        return $fields;

    }

    /**
     * Filter the shop_order post type args.
     *
     * Hides the UI for the Orders.
     *
     * @param $args
     *
     * @return mixed
     */
    function filter_shop_order_args( $args ){

        if( current_user_can( 'manage_wp99234_products' ) ){
            return $args;
        }

        $args['show_ui'] = false;

        return $args;

    }

    /**
     * Filter the product post type args.
     *
     * Hides the UI for the Products.
     *
     * @param $args
     *
     * @return mixed
     */
    function filter_product_args( $args ){

        if( current_user_can( 'manage_wp99234_products' ) ){
            return $args;
        }

        $args['show_ui'] = false;

        return $args;

    }

    /**
     * Ensure that new customer created in WP has the login name set as their email address.
     *
     * @param $data
     *
     * @return mixed
     */
    function filter_new_customer_data( $data ){

        $data['user_login'] = $data['user_email'];

        return  $data;

    }

    /**
     * load-edit.php hook, disables management for products and orders.
     */
    function load_edit_page(){

        if( current_user_can( 'manage_wp99234_products' ) ){
            return;
        }

        $redirect = false;

        if( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'shop_order' ){
            WP99234()->_admin->add_notice( __( 'All orders are managed in troly and have been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( isset( $_REQUEST['post_type'] ) && $_REQUEST['post_type'] === 'product' ){
            WP99234()->_admin->add_notice( __( 'All products are managed in troly and have been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( $redirect ){
            wp_redirect( admin_url() );
            exit;
        }

    }

    /**
     * load-post.php hook. disables management for products and orders.
     */
    function load_post_page(){
        global $typenow;

        if( current_user_can( 'manage_wp99234_products' ) ){
            return;
        }

        $redirect = false;

        if( $typenow == 'shop_order' ){
            WP99234()->_admin->add_notice( __( 'All orders are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( $typenow == 'product' ){
            WP99234()->_admin->add_notice( __( 'All products are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( $redirect ){
            wp_redirect( admin_url() );
            exit;
        }

    }

    /**
     * load-post-new.php hook, disables management for products and orders.
     */
    function load_post_new_page(){
        global $typenow;

        if( current_user_can( 'manage_wp99234_products' ) ){
            return;
        }

        $redirect = false;

        if( $typenow == 'shop_order' ){
            WP99234()->_admin->add_notice( __( 'All orders are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( $typenow == 'product' ){
            WP99234()->_admin->add_notice( __( 'All products are managed in Troly. Management has been disabled on this website.', 'wp99234' ), 'error' );
            $redirect = true;
        }

        if( $redirect ){
            wp_redirect( admin_url() );
            exit;
        }

    }

    /**
     * Programatically disable all emails from woocommerce.
     *
     * The user, admin, order, stock, user notifications etc are all handled by troly
     *
     * @param $email_class
     */
    function disable_emails( $email_class ) {

        remove_all_actions( 'woocommerce_low_stock_notification' );
        remove_all_actions( 'woocommerce_no_stock_notification' );
        remove_all_actions( 'woocommerce_product_on_backorder_notification' );

        remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification' );
        remove_all_actions( 'woocommerce_order_status_pending_to_completed_notification' );
        remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification' );
        remove_all_actions( 'woocommerce_order_status_failed_to_processing_notification' );
        remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification' );
        remove_all_actions( 'woocommerce_order_status_failed_to_completed_notification' );
        remove_all_actions( 'woocommerce_order_status_failed_to_on-hold_notification' );

        remove_all_actions( 'woocommerce_order_status_pending_to_processing_notification' );
        remove_all_actions( 'woocommerce_order_status_pending_to_on-hold_notification' );
        remove_all_actions( 'woocommerce_order_status_completed_notification' );
        remove_all_actions( 'woocommerce_new_customer_note_notification' );

    }

    /**
     * Filter the displayed price.
     *
     * Membership prices override the bulk purchases pricing.
     *
     * @TODO - Store this in a transient. Be sure to clear it when updating the products.
     *
     * @param $price
     * @param $product
     *
     * @return mixed
     */
    function filter_get_price( $price, $product ){

//        //Frontend calcs only.
//        if( ! defined( 'DOING_AJAX' ) ){
//            if( is_admin() ){
//                return $price;
//            }
//        }

        /**
         * If we are in the cart or checkout, then we need to find the product in the cart to be able to apply the bulk discounts to the displayed price.
         */
        if( ( defined( 'WOOCOMMERCE_CART' ) && WOOCOMMERCE_CART ) || ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) ) {

            $valid_item_key = WC()->cart->generate_cart_id( $product->id, '', array(), array() );

            $cart_item_key = WC()->cart->find_product_in_cart( $valid_item_key );

            if( $cart_item_key ){

                $cart_item = WC()->cart->get_cart_item( $cart_item_key );

                $quantity = WC()->cart->cart_contents_count;

                $price_6pack = get_post_meta( $cart_item['product_id'], 'price_6pk' , true );
                $price_case  = get_post_meta( $cart_item['product_id'], 'price_case', true );

					 // Only use the specified 6 pack/case price if is positive
                if( $quantity >= 12 && is_numeric( $price_case ) && $price_case > 0){
                    $price = (float)$price_case;
                } elseif( $quantity >= 6 && is_numeric( $price_6pack ) && $price_6pack > 0){
                    $price = (float)$price_6pack;
                }

            }

        }

		  // User is logged in - we can attempt to get membership product prices
        if( is_user_logged_in() ){

            $current_memberships = get_user_meta( get_current_user_id(), 'current_memberships', true );

            // Don't lookup membership prices if this user is not a current member
            if ( !is_array( $current_memberships ) || empty( $current_memberships )) {
                return $price;
            }
				
            $member_prices = WP99234()->_prices->get_membership_prices_for_product( $product->id, $current_memberships );

            // Member prices is an array.
            if(!is_array( $member_prices ) || empty( $member_prices ) ){
                return $price;
            }

            $using_member_price = false;

            foreach( $current_memberships as $current_membership ){

                //Get the membership ID we are looking at
                $membership_type_id = $current_membership->membership_type_id;

                //Sort through the prices for the product
                foreach( $member_prices as $member_price ){

                    //If we are looking at a membership type that the user is associated with, we can use the price.
                    if( $member_price->membership_id == $membership_type_id ){

                        //Don't use prices that are 0. Allowing free stuff should be done with coupons not a globally set price.
                        if( $member_price->price <= 0 ){
                            continue;
                        }

                        // If no member price already used, then we can use it
                        if( ! $using_member_price ){
                            $price = (float)$member_price->price;
                            $using_member_price = true;
                        } else {
                            // If we already have a member price, we need to ensure we are using the cheapest one.
                            if( (float)$member_price->price < $price ){
                                $price = (float)$member_price->price;
                            }
                        }

                    }

                }

            }

        }

        return $price;

    }

    /**
     * After Order Actions hook. Adds a button in WP to export the order to SUBS if it wasn't exported automatically.
     *
     * @param $order
     */
    function after_order_actions( $order ){

        $subs_id = get_post_meta( $order->id, 'subs_id', true );

        if( $subs_id ){
            return;
        }

        $url = add_query_arg( array(
            'export_order_to_subs' => $order->id,
            '_nonce' => wp_create_nonce( 'wp99234_export_order' )
        ), admin_url( 'edit.php?post_type=shop_order' ) );

        printf( '<br /><a class="button tips" href="%s" data-tip="%s">%s</a>', esc_url( $url ), __( 'Export Order To Troly', 'wp99234' ), __( 'Export Order', 'wp99234' ) );

    }
  
    /**
     * Export the order to SUBS
     *
     * @param $order_id
     *
     * @return bool
     * @throws Exception
     */
    public function export_order( $order_id ){

        $subs_id = get_post_meta( $order_id, 'subs_id', true );

        if( $subs_id ){
            if( is_admin() ){
                WP99234()->_admin->add_notice( __( 'Order has already been pushed to Troly.', 'wp99234' ), 'error' );
            }
            return false;
        }

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Starting export of order to Troly';
      
        $order = new WC_Order( $order_id );

        $customer = $order->get_user();
        $subs_user_id = '';
      
        // We have a guest customer, we need to send a request to create this customer on Subs before proceeding
        if( !$customer ){
          
			$message .= '\nguest checkout detected, attempting to create a new customer on Subs before proceeding';
			
			$customer_data = array(
					'customer' => array(
					'fname' => $order->billing_first_name,
					'lname' => $order->billing_last_name,
					'email' => $order->billing_email,
					'phone' => $order->billing_phone,
					'company_name' => $order->billing_company,
					'delivery_address' => $order->billing_address_1,
					'delivery_suburb' => $order->billing_city,
					'delivery_postcode' => $order->billing_postcode,
					'delivery_state' => $order->billing_state,
					'delivery_country' => WC()->countries->countries[ $order->billing_country ],
					'notify_shipments' => '@|mail'
				)
			);
			
			if(isset($_POST) && !isset($_POST['use_existing_card']) && isset($_POST['wp99234_payment_gateway-card-number'])) {
			
				$cc_exp = str_replace(' ', '', $_POST['wp99234_payment_gateway-card-expiry']);
				$cc_exp = explode('/', $cc_exp);
				
				if (count($cc_exp) > 1) {
					$customer_data['customer']['cc_name'] = $_POST['wp99234_payment_gateway-card-name'];
					$customer_data['customer']['cc_number'] = $_POST['wp99234_payment_gateway-card-number'];
					$customer_data['customer']['cc_exp_month'] = $cc_exp[0];
					$customer_data['customer']['cc_exp_year'] = $cc_exp[1];
					$customer_data['customer']['cc_cvv'] = $_POST['wp99234_payment_gateway-card-cvc'];
				}
			}
			
			$response = WP99234()->_api->_call( WP99234()->_users->users_create_endpoint, $customer_data, 'POST' );
			
			$errs = (array)$response->errors;
			
			if ($response && empty($errs)) {
				$subs_user_id = $response->id;
				$message .= '\n New customer created successfully on Subs from guest customer info';
			} else {
				$message .= '\n New customer could not be created and order processing has failed';
				
				if ($reporting_options == "verbose" || $reporting_options == "minimum") {
					wp99234_log_troly($message, $success = false, 'Export', 'Order Export to Subs', $message);
				}
				
				if( is_admin() ){
					WP99234()->_admin->add_notice( __( 'Could not retrieve the customer for the order.', 'wp99234' ), 'error' );
					return false;
				}
				
				throw new Exception( __( 'There was an error processing your order, please try again shortly.', 'wp99234' ) );
			}
        } else {
			$subs_user_id = get_user_meta( $customer->ID, 'subs_id', true );
        }

        /**
         * Submit the order to SUBS.
         */		
		$message .= '\nGetting order info to export';

		// method used when we swap to woocommerce local pickup shipping zone option
		// rather than our own inbuilt one.
		//$shipping_methods = array_shift($order->get_items('shipping'));
		//$shipping_method = explode(":", $shipping_methods['method_id']);
		//  
		//if($shipping_method[0] != "local_pickup"){
    
		if($_SESSION['pickup']!=1){
			$order_data = array(
				'order' => array(
					'customer_id'  => $subs_user_id,
					'source'       => 'web',
					'status'       => 'draft',
					'fname'        => $order->billing_first_name,
					'lname'        => $order->billing_last_name,
					'company_name' => $order->billing_company_name,
					'user_id'      => '',
					'total_qty'    => count( $order->get_items() ),
					'orderlines'   => array(),
					'shipment_date' => date( 'd M Y' ),
				)
			);
		}else{
			$order_data = array(
				'order' => array(
					'customer_id'  => $subs_user_id,
					'source'       => 'web',
					'status'       => 'draft',
					'fname'        => $order->billing_first_name,
					'lname'        => $order->billing_last_name,
					'company_name' => $order->billing_company_name,
					'user_id'      => '',
					'total_qty'    => count( $order->get_items() ),
					'orderlines'   => array(),
					'shipment_date' => ''
				)
			);
		}      
      
		$message .= '\nGetting orderlines for the order';
      
        //Set the order lines from the order items.
        foreach( $order->get_items() as $key => $item ){
        
            $qty = apply_filters('wp99234_set_product_packaging', $qty, $item);
            
            //Example add_filter to change quantity above::
            //
            //function set_product_packaging($qty, $item) {
            //
            //  $qty = $item['qty']; // default value
            //
            //	$post_id = $item['product_id'];
            //
            //	// Edit $qty here however needed
            //	$qty = $qty * 12;
            //  
            //  return $qty;
            //}
            //
            //add_filter('wp99234_set_product_packaging', 'set_product_packaging', 1, 2);
          
            if (!isset($qty)) {
                $qty = $item['qty'];
            }
          
            $order_data[ 'order' ][ 'orderlines' ][ ] = array(
                'name'       => $item['name'],
                'qty'        => $qty,
                'product_id' => get_post_meta( (int)$item['product_id'], 'subs_id', true )
            );
            unset($qty); 
        }      
      
        // Get the total calculated discount amount from woocommerce
        $total_discount = 0;
        
        foreach( $order->get_items('coupon') as $key => $item) {
            $total_discount += $item['discount_amount'];
        }
        
        //DISCOUNT_PRODUCT_IDS = [50, 51, 52, 53, 54]
        
        if (isset($total_discount) && $total_discount > 0) {
            $order_data['order']['orderlines'][] = array( 
                'name' => 'Discount amount',
                'price' => -$total_discount,
                'product_id' => 50
            );
        }
      
        $message .= '\nExporting order to Troly';
      
        $response = WP99234()->_api->_call( $this->order_api_endpoint, $order_data, 'POST' );
			
        //set the subs order ID
        update_post_meta( $order_id, 'subs_id', $response->id );

        //Enforce the final price
        if( $response && isset( $response->total_value ) && $response->total_value > 0 ){
            update_post_meta( $order_id, '_order_total', $response->total_value );
        }

        if( $response && isset( $response->total_value ) && $response->total_value > 0 ){
            update_post_meta( $order_id, '_order_tax', $response->total_tax1 + $response->total_tax2 );
        }

        $errs = (array)$response->errors;

        if( ! is_admin() || defined( 'DOING_AJAX' ) ){

            /**
             * If the order fails, display a generic order failure message telling the user that they will be contacted shortly.
             */
            if( ! $response || ! empty( $errs ) ){

                //mark the order on hold
                //$order->update_status( 'on-hold', __( 'Troly payment failed.', 'wp99234' ) );

                //Log the errors
                WP99234()->logger->error( 'Troly payment errors. ' . var_export( $response->errors, true ) );
              
                $message .= '\nExport failed, Troly payment errors. ' . var_export($response->errors, true);
                   
                if ($reporting_options == "verbose" || $reporting_options == "minimum") {
                    wp99234_log_troly($message, $success = false, 'Export', 'Order Export to Subs', $message);
                }
              
                //Get the hell out of Dodge
                throw new \Exception( __( 'There was an error processing your payment. We will contact you shortly.', 'wp99234' ) );

            } else {

                //wc_add_notice( __( 'Your payment has been successful.', 'wp99234' ), 'success' );

//                $order->update_status( 'processing', __( 'Troly payment succeeded.', 'wp99234' ) );
//
//                //WP99234()->logger->info( 'Troly payment for order id: ' . $order->id . ' succeeded' );

                //$order->payment_complete();

                $message .= '\nOrder successfully exported to Troly';
                   
                if ($reporting_options == "verbose" || $reporting_options == "minimum") {
                    wp99234_log_troly($message, $success = true, 'Export', 'Order Export to Subs', $message);
                }
              
                // Trigger the charge manually as a $0 order does not get paid by woocommerce
                // and then the order does not get confirmed and won't show up in the Subs order list
                // by manually triggering the 'payment' we can confirm the order and have it displayed.
                if ($response->total_value == 0) {
                  WP99234()->_woocommerce->trigger_charge_on_order( $order_id, "charge" );
                }
              
            }

            // Reduce stock levels
            $order->reduce_order_stock();
          
            //Return subs ID
            return $response->id;

        } else {

            if( ! $response || ! empty( $errs ) ){

                WP99234()->_admin->add_notice( __( 'Order failed to push to Troly. Please check the error logs for details.', 'wp99234' ), 'error' );
              
                $message .= '\nExport failed, Troly payment errors. ' . var_export($response->errors, true);
              
                if ($reporting_options == "verbose" || $reporting_options == "minimum") {
                    wp99234_log_troly($message, $success = false, 'Export', 'Order Export to Subs', $message);
                }

            } else {

                WP99234()->_admin->add_notice( __( 'Order pushed successfully to Troly', 'wp99234' ), 'success' );

                $message .= '\nOrder successfully exported to Troly';
              
                if ($reporting_options == "verbose" || $reporting_options == "minimum") {
                    wp99234_log_troly($message, $success = true, 'Export', 'Order Export to Subs', $message);
                }
              
                //$order->update_status( 'processing', __( 'Troly payment succeeded.', 'wp99234' ) );

            }

        }

    }

    /**
     * Triggers the actual auth charge on the order.
     *
     * Returns the websocket channel, or throws an exception on failure.
     *
     * @param $order_id
     *
     * @return bool
     * @throws Exception
     */
    function trigger_charge_on_order( $order_id, $payment_type ){

        $subs_id = get_post_meta( $order_id, 'subs_id', true );

        $order = new WC_Order( $order_id );

        if( ! $subs_id || ! $order ){
            return false;
        }

        $endpoint = $this->get_order_update_endpoint( $order_id );

        $data = array(
            'status' => 'confirmed',
            'id'     => $subs_id,
            'order'  => array(
                'status'       => 'confirmed',
                'payment_type' => $payment_type
            )
        );

        $results = WP99234()->_api->_call( $endpoint, $data, 'PUT' );

        if( isset( $results->channel ) ){
            return $results->channel;
        } else {
            throw new \Exception( __( 'An error has occurred. You will be contacted as soon as possible.', 'wp99234' ) );
        }

    }

    /**
     * Retrieve a WC_Order object based on the subs_id.
     *
     * @param $subs_id
     *
     * @return mixed
     */
    function get_order_by_subs_id( $subs_id ){

        $args = array(
            'post_type'      => 'shop_order',
            'meta_query'     => array(
                array(
                    'key'     => 'subs_id',
                    'value'   => $subs_id,
                    'compare' => '=',
                ),
            ),
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'page'           => 1
        );
        $query = new WP_Query( $args );

        if( $query->have_posts() ){
            $post = array_shift( $query->posts );
            return new WC_Order( $post->ID );
        }

        return false;

    }

    /**
     * Get the subs endpoint to update the given order.
     *
     * @param $order_id
     * @return string
     */
    function get_order_update_endpoint( $order_id ){

        $subs_id = get_post_meta( $order_id, 'subs_id', true );

        if( $subs_id ) {
            $endpoint = sprintf( WP99234_Api::$endpoint . 'orders/%s', $subs_id );
        } else {
            $endpoint = $this->order_api_endpoint;
        }

        return $endpoint;

    }

    /**
     * woocommerce_before_template_part
     *
     * Load up the websocket processing script if there is a ws_channel to subscribe to on the thankyou page.
     *
     * @param $template_name
     * @param $template_path
     * @param $located
     * @param $args
     */
    function before_template_part( $template_name, $template_path, $located, $args ){

        if( $template_name == 'checkout/thankyou.php' ){

            if( isset( $_GET['ws_channel'] ) ){

                include WP99234_ABSPATH . 'includes/frontend/assets/websocket_script.php';

            }

        }

    }


    /**
     * Check SUBS for the payment status of an order.
     */
    function check_wp99234_payment_status(){

        $order_id = absint( $_POST['order_id'] );

        $order = new WC_Order( $order_id );

        if( ! $order ){
            exit;
        }

        $subs_id = get_post_meta( $order_id, 'subs_id', true );

        $endpoint = sprintf( "%sorders/%s.json", WP99234_Api::$endpoint, $subs_id );

        $results = WP99234()->_api->_call( $endpoint );

        header('Content-Type: application/json');

        echo json_encode( $results );

        exit;

    }

    /**
     * Handle the pushing of product ratings and reviews to SUBS.
     */
    function handle_ratings_and_reviews( $id, $comment ){
        global $post;

        if( $post->post_type != WP99234()->_products->products_post_type ){
            return;
        }

        $subs_id = get_post_meta( $post->ID, 'subs_id', true );

        if( ! $subs_id ){
            return;
        }

        if(  isset( $_POST['rating'] ) ){
            $rating = (float)$_POST['rating'];
        }

        $author = get_user_by( 'email', $comment->comment_author_email );

        $data = array(
            'val'          => ( $rating > 0 ) ? $rating : false ,
            'product_id'   => $subs_id,
            'comment'      => $comment->comment_content
        );

        if( $author ){

            $author_subs_id = get_user_meta( $author->ID, 'subs_id', true );

            $data['customer_id'] = ( $author_subs_id ) ? $author_subs_id : null ;

        } else {
            $data['customer_id'] = null;
        }

        // /products/:product_id/ratings.json
        $endpoint = sprintf( '%sproducts/%s/ratings.json', WP99234_Api::$endpoint, $subs_id );

        $results = WP99234()->_api->_call( $endpoint, $data, 'POST' );

        if( ! $results ){
            WP99234()->logger->error( 'Invalid result from API. Called ' . $endpoint );
        }

        $errors = (array)$results->errors;

        if( ! empty( $errors ) ){
            foreach( $errors as $error ){
                WP99234()->logger->error( $error );
            }
            return false;
        }

        WP99234()->_products->import_product( $results->product );

    }

    /**
     * Filter the wc_get_template so we can override the rating template.
     *
     * @param $located
     * @param $template_name
     * @param $args
     * @param $template_path
     * @param $default_path
     *
     * @return string
     */
    function filter_wc_get_template( $located, $template_name, $args, $template_path, $default_path ){

        if( $template_name == 'loop/rating.php' ){
            $located = WP99234()->template->locate_template( 'rating.php' );
        }

        return $located;
    }

    /**
     * Get the rating count for the given product
     *
     * @param $product
     *
     * @return float
     */
    function get_rating_count( $product ){

        $meta = get_post_meta( $product->ID, 'avg_rating', true );

        if( $meta && is_numeric( $meta ) ){
            return (float)$meta;
        }

        return $product->get_average_rating();

    }

    /**
     * Get the average rating for the given product.
     *
     * @param $product
     *
     * @return float'
     */
    function get_average_rating( $product ){

        $meta = get_post_meta( $product->ID, 'rating_count', true );

        if( $meta && is_numeric( $meta ) ){
            return (float)$meta;
        }

        return $product->get_rating_count();

    }

}