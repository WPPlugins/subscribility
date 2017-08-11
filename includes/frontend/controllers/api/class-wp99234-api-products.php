<?php

/**
 * Class to handle product updates and management through the WP99234 API.
 *
 * Class WP99234_Api_Products
 */
class WP99234_Api_Products extends WP99234_Api_Server {

    /**
     * Object Constructor
     */
    function __construct(){

        define( 'WP99234_INVALID_SUBS_PRODUCT_ID', __( 'Invalid Subs Product ID', 'wp99234' ) );

        $this->accepted_methods = array( 'GET', 'PUT' );

        parent::__construct();

    }

    /**
     * Serve the current API Request.
     *
     * @param $route
     */
    function serve_request( $route ){

        array_shift( $route );

        if( $this->method == 'PUT' ){
            $this->response = $this->update_product( $this->body );
            $this->respond();
        }

        if( $this->method == 'GET' ){
            $this->response = $this->get_products( $route );
            $this->respond();
        }

        WP99234()->logger->error( 'Unable to serve request. No method found.' );
        $this->errors[] = WP99234_INVALID_REQUEST;
        $this->respond();

    }

    /**
     * Update a product with the given data.
     *
     * @param $data
     *
     * @return array|bool
     */
    function update_product( $data ){
      
        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Updating product (id: ' . $data->id . ', name: ' . $data->name . ') with changes from Troly';

        if( defined( 'WP_DEBUG' ) && WP_DEBUG ){

            WP99234()->logger->debug( 'Updating Product...' );

            ob_start();
            var_dump( $data );
            $str = ob_get_contents();
            ob_end_clean();

            WP99234()->logger->debug( $str );

        }


        if( ! WP99234()->_products->validate_product_data( $data ) ){
            $this->errors[] = WP99234_INVALID_REQUEST;
            $this->respond();
        }

        $product = WP99234()->_products->import_woocommerce_product( $data );
        //$product = WP99234()->_products->import_product( $data );
      
        if( is_wp_error( $product ) ){
            
            $message .= '\nProduct failed to import. The following error occurred: ' . $product->get_error_message();
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Import', 'Product update from Subs', $message);
            }
          
            WP99234()->logger->error( printf( 'Product failed to import. The following error occurred: %s', $product->get_error_message() ) );
            return false;
        }

        $message .= '\nProduct successfully imported';
      
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Import', 'Product update from Subs', $message);
        }
      
        return array(
            'product' => $product,
            'subs_id' => $data->id
        );

    }

    /**
     * Get products via the API, Will get a single product if passed a subs ID.
     *
     * @param $data
     *
     * @return array|bool
     */
    function get_products( $data ){

        $product = false;

        if( isset( $data[0] ) ){

            $product = WP99234()->_products->get_by_subs_id( $data[0] );

            if( ! $product ){
                $this->errors[] = WP99234_INVALID_SUBS_PRODUCT_ID;
                $this->respond();
            }

        }

        if( $product ){
            return $product;
        }

        return WP99234()->_products->get_all_products();

    }


}