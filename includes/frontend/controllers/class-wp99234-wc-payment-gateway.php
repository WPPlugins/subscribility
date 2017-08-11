<?php
/**
 * WP99234_Payments
 *
 * Custom payment gateway for WP99234
 *
 * @package wp99234
 */

class WP99234_WC_Payment_Gateway extends WC_Payment_Gateway_CC{

    var $order_api_endpoint;

    function __construct(){

        $this->id = 'wp99234_payment_gateway';

        $this->has_fields = true;

        $this->title = __( 'Credit Card', 'wp99234' );

        $this->method_title = __( 'Troly Payments', 'wp99234' );

        $this->method_description = __( 'Payments using the Troly gateway.', 'wp99234' );

        $this->supports[] = 'default_credit_card_form';

        $this->init_form_fields();

        $this->init_settings();


        add_filter( 'woocommerce_credit_card_form_fields', array( $this, 'credit_card_form_fields' ) );

        $this->order_api_endpoint = WP99234_Api::$endpoint . 'orders.json';

    }

    function init_form_fields(){

        $this->form_fields = array(
            'enabled' => array(
                'title' => __( 'Enable/Disable', 'wp99234' ),
                'type' => 'checkbox',
                'label' => __( 'Enable Troly Payments', 'wp99234' ),
                'default' => 'yes'
            )
        );

    }

    /**
     * Process a payment.
     *
     * @param int $order_id
     *
     * @return array
     * @throws Exception
     */
    function process_payment( $order_id ) {

        define( 'WP99234_DOING_ORDER', true );
      
        /**
         * Ensure the current user is logged in.
         */
        //if( ! is_user_logged_in() ){
        //    throw new \Exception( __( 'You must be logged in to order from this website', 'wp99234' ) );
        //}
      
        $order = new WC_Order( $order_id );

        //Create the order in SUBS
        try{
          
            // Should already be exported with the 'woocommerce_checkout_order_processed' hook
            // but no harm in trying again just in case, as the export_order function returns
            // if its already exported regardless.
            WP99234()->_woocommerce->export_order( $order_id );
          
            // Moved from export_order function to here due to the 'process_payment'
            // function not being ran when the cart is empty.
            // if the cart is not already empty, empty it
            if (WC()->cart->cart_contents_count > 0) {
              WC()->cart->empty_cart();
            }
          
            //Trigger the charge
            $websocket_channel = WP99234()->_woocommerce->trigger_charge_on_order( $order_id, "charge" );
          
            $return_url = $this->get_return_url( $order );

            if( $websocket_channel ){
                $return_url = esc_url_raw( add_query_arg( 'ws_channel', $websocket_channel, $return_url ) );
            }
          
            // Return thankyou redirect
            return array(
                'result'   => 'success',
                'redirect' => $return_url
            );

        } catch( \Exception $e ){
          
            wc_add_notice( $e->getMessage(), 'error' );

            return array(
                'result'   => 'failed'
            );

        }

    }

    /**
     * Displayed payment fields for Troly.
     */
    public function payment_fields() {

        $has_cc_details = false;

        if( is_user_logged_in() ){
            $has_cc_meta = get_user_meta( get_current_user_id(), 'has_subs_cc_data', true );
            if( $has_cc_meta && $has_cc_meta == true ){
                $has_cc_details = true;
            }
        }

        if( $has_cc_details ){
            echo '<input type="checkbox" id="use_existing_card" name="use_existing_card" checked="checked" value="yes" /> ' . sprintf( 'Use Your existing card (%s)', get_user_meta( get_current_user_id(), 'cc_number', true ) );
            echo '<div id="hidden_cc_form"> <p>' . __( 'The details entered here will be stored securely for future use.', 'wp99234' ) . '</p>';
        }

        //$this->credit_card_form();
		$this->form();

        if( $has_cc_details ){
            echo '</div>';
        }

    }

    /**
     * Add the CC Name to the default CC form.
     *
     * @param $fields
     *
     * @return array
     */
    function credit_card_form_fields( $fields ){

        $fields = array(
            'card-name-field' => '<p class="form-row form-row-wide">
				<label for="' . esc_attr( $this->id ) . '-card-name">' . __( 'Name on card', 'woocommerce' ) . ' <span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-card-name" class="input-text wc-credit-card-form-card-name" type="text" maxlength="20" autocomplete="off"  name="' . $this->id . '-card-name" />
			</p>',
            'card-number-field' => $fields['card-number-field'],
            'card-expiry-field' => $fields['card-expiry-field'],
            'card-cvc-field'    => $fields['card-cvc-field']
        );

        return $fields;

    }
}