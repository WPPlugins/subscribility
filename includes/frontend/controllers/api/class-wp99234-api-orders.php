<?php


class WP99234_Api_Orders extends WP99234_Api_Server {

    /**
     * Object Constructor
     */
    function __construct(){

        define( 'WP99234_INVALID_SUBS_CUST_ID', __( 'Invalid Subs Customer ID', 'wp99234' ) );

        $this->accepted_methods = array( 'PUT', 'GET' );

        parent::__construct();

    }

    /**
     * Serve the current request.
     *
     * @param $route
     */
    function serve_request( $route ){

        //Remove the orders part, we are already there.
        array_shift( $route );

        if( $this->method == 'PUT' ){
            $this->response = $this->update();
            $this->respond();
        }

        if( $this->method == 'GET' ){
            $this->response = $this->get( $route );
            $this->respond();
        }

        WP99234()->logger->error( 'Unable to serve request. No method found.' );
        $this->errors[] = WP99234_INVALID_REQUEST;
        $this->respond();

    }


    /**
     * Update an order status based on the given data.
     *
     * @return array 'order' => WC_Order
     */
    function update(){

        if( $this->method !== 'PUT' ){
            $this->errors[] = WP99234_INVALID_REQUEST;
            $this->respond();
        }
      
        $request_data = $this->body;

        $subs_id = $request_data->order->id;
      
        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Updating order (id: ' . $subs_id . ') with data from Troly';

        $order = WP99234()->_woocommerce->get_order_by_subs_id( $subs_id );

        if( ! $order ){
          
            $message .= '\nOrder could not be found, update failed.';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'minimum') {
                wp99234_log_troly($message, $success = false, 'Import', 'Order update from Subs', $message);
            }
          
            $this->errors[] = __( 'Order was not found', 'wp99234' );
            $this->respond();
        }

        $subs_status = $request_data->order->status;

        //draft confirmed in-progress completed cancelled template
        //pending processing on-hold completed cancelled refunded failed
        $_map = array(
            'draft'       => 'pending',
            'confirmed'   => 'processing',
            'in-progress' => 'processing',
            'completed'   => 'completed',
            'cancelled'   => 'cancelled',
            'template'    => 'completed'
        );

        if( isset( $_map[$subs_status] ) ){
            $order->update_status( $_map[$subs_status] );
        }

        $message .= '\nOrder successfully updated';
      
        if ($reporting_options == 'verbose' || $reporting_options == 'minimum') {
            wp99234_log_troly($message, $success = true, 'Import', 'Order update from Subs', $message);
        }
      
        return array(
            'order' => $order
        );

    }

    /**
     * Retrieves the order object based on the SUBS ID
     *
     * @param $route
     *
     * @return mixed
     */
    function get( $route ){


        if( ! is_array( $route ) || ! is_numeric( $route[0] ) ){
            $this->errors = WP99234_INVALID_REQUEST;
            $this->respond();
        }

        $subs_id = (int)$route[0];

        $order = WP99234()->_woocommerce->get_order_by_subs_id( $subs_id );

        if( ! $order ){
            $this->errors[] = __( 'Order was not found', 'wp99234' );
            $this->respond();
        }

        return $order;

    }


}