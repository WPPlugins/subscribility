<?php

/**
 *
 *
 *
 */
class WP99234_Prices {

    var $dbtable;

    function __construct() {
        global $wpdb;

        $this->dbtable = $wpdb->prefix . 'wp99234_prices';

        /**
         * Ensure we have a DB table.
         */
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$this->dbtable'" ) != $this->dbtable ) {
            $wpdb->query(
                'CREATE TABLE `' . $this->dbtable . '` (
                `price_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `product_id` int(11) NOT NULL,
                `membership_id` int(11) NOT NULL,
                `price` float NOT NULL,
                PRIMARY KEY (`price_id`)
                ) ENGINE=InnoDB AUTO_INCREMENT=181 DEFAULT CHARSET=latin1;'

            /**
             * (SEB) Should this not be handled by the wp99234->handle_db_update perhaps?
             * would likely need to architectural changes. to be discussed.
             */

            );
        }

    }

    /**
     * Calculates the membership prices for the products given.
     *
     * Pre-packed composite products. (split_ols != true)
     * - These use the product_prices and save the membership price directly from that.
     *
     * Non Pre-packed Composite Products (split_ols == true)
     * - These get the product_prices for each subproduct and save the price based on that calculation.
     *
     * Here be dragons.
     *
     * @param $products
     */
    public function calculate_membership_prices_for_products( $products ) {

        if ( ! is_array( $products ) ) {
            return;
        }

        $all_membership_types = get_option( 'wp99234_company_membership_types' );

        if( ! $all_membership_types ){
            if( is_admin() ){
                WP99234()->_admin->add_notice( 'Please import membership types before products.', 'error' );
                return;
            }
        }

        foreach ( $products as $product ) {
//            if( ! $product->subproducts || ! is_array( $product->subproducts ) || empty( $product->subproducts ) || $product->split_ols != true ){
//                continue;
//            }

            self::delete_prices_for_product( $product->post->ID );

            $product->is_split = get_post_meta( $product->post->ID, '_is_split', true );

            //Loop through all the membership types
            foreach ( $all_membership_types as $membership_type ) {

                $price = 0;

                if ( $product->is_split == true ) {

                    //Loop through the subproducts
                    foreach ( $product->subproducts as $subproduct ) {

                        if ( ! isset( $products[ $subproduct->product_id ] ) ) {
                            continue;
                        }

                        $subproduct_full = $products[ $subproduct->product_id ];

                        if ( isset( $subproduct_full->product_prices[ $membership_type->id ] ) ) {
                            $price += ( (float) $subproduct_full->product_prices[ $membership_type->id ]->price * $subproduct->quantity );
                        }

                    }

                } else {
                    if ( isset( $product->product_prices[ $membership_type->id ] ) ) {
                        $price = (float) $product->product_prices[ $membership_type->id ]->price;
                    }
                }
                if ( $price > 0 ) {
                    //Save the price, associated with the product ID and the membership ID
                    $priceObj = new WP99234_Price( array(
                        'membership_id' => $membership_type->id,
                        'product_id'    => $product->wp_post->ID,
                        'price'         => $price
                    ) );
                    $priceObj->save();
                }

            }

        }

    }
	
	/**
     * Get the membership prices for the products given.
     *
     * @param $product_id
     */
	function membershipPrice($product_id) {
		global $wpdb;
		$all_membership_types = get_option( 'wp99234_company_membership_types' );
		$membership_price = $wpdb->prefix . 'wp99234_prices';
		$membershipId = array();
		foreach($all_membership_types as $membership_type){
			if($membership_type->visibility=="public"){
				$membershipId[] = $membership_type->id;
			}
		}
		$prices  = implode(',',$membershipId);
		$memberPrice = $wpdb->get_results( "SELECT price FROM $membership_price WHERE  product_id =$product_id AND	membership_id IN ($prices) ORDER BY price ASC LIMIT 0,1");
		if($memberPrice[0]->price > 0){
			$membership = 'Membership Price : <span class="amount">'.get_woocommerce_currency_symbol(get_option('woocommerce_currency')).$memberPrice[0]->price.'</span>';
		}
		return $membership;
	}

    /**
     * @TODO
     * @param $products
     */
    function calculate_bulk_discounts_for_products( $products ){

//        if ( ! is_array( $products ) ) {
//            return;
//        }
//
//        foreach ( $products as $product ) {
//
//            if ( $product->is_split == true ) {
//
//                $prices = array(
//                    '6pack' => 0,
//                    'case'  => 0
//                );
//
//                foreach ( $product->subproducts as $subproduct ) {
//
//                    $subproduct_wp_post = WP99234()->_products->get_by_subs_id( $subproduct->product_id );
//
//                    if ( ! $subproduct_wp_post ) {
//                        continue;
//                    }
//
//                    $subproduct_6pack = get_post_meta( $subproduct_wp_post->id, 'price_6pk', true );
//
//                    if( is_numeric( $subproduct_6pack ) && $subproduct_6pack > 0 ){
//                        $prices['6pack'] += (float)$subproduct_6pack;
//                    }
//
//                    $subproduct_case = get_post_meta( $subproduct_wp_post->id, 'price_case', true );
//
//                    if( is_numeric( $subproduct_case ) && $subproduct_case > 0 ){
//                        $prices['case'] += (float)$subproduct_case;
//                    }
//
//                }
//
//                update_post_meta(  )
//
//            }
//
//        }

    }

    /**
     * Calculate the membership prices for a single product.
     *
     * This is triggered when there is a single product being updated (eg from the API) hence we must lookup all the associated product prices etc.
     *
     * @param $product
     */
    public function calculate_membership_prices_for_single_product( $product ) {

        $all_membership_types = get_option( 'wp99234_company_membership_types' );

        self::delete_prices_for_product( $product->wp_post->ID );

        foreach ( $all_membership_types as $membership_type ) {

            $price = 0;

            if ( $product->is_split == true ) {

                //Loop through the subproducts
                foreach ( $product->subproducts as $subproduct ) {

                    $subproduct_wp_post = WP99234()->_products->get_by_subs_id( $subproduct->product_id );

                    if ( ! $subproduct_wp_post ) {
                        continue;
                    }

                    $subproduct_membership_prices = get_post_meta( $subproduct_wp_post->ID, 'product_prices', true );

                    if ( isset( $subproduct_membership_prices[ $membership_type->id ] ) ) {
                        $price += $subproduct_membership_prices[ $membership_type->id ]->price * $subproduct->quantity;
                    }

                }


            } else {

                if ( isset( $product->product_prices[ $membership_type->id ] ) ) {
                    $price = (float)$product->product_prices[ $membership_type->id ]->price;
                }

            }

            //Save the price, associated with the product ID and the membership ID
            if ( $price > 0 ) {
                $priceObj = new WP99234_Price( array(
                    'membership_id' => $membership_type->id,
                    'product_id'    => $product->wp_post->ID,
                    'price'         => $price
                ) );
                $priceObj->save();
            }

        }

    }

    /**
     * Calculate the 6 pack and case prices for non-prepacked composite products.
     *
     * @param $product
     *
     * @return array|void
     */
    function calculate_bulk_discount_prices_for_product( $product ){

        if( ! $product->is_split ){
            return;
        }

        $prices = array(
            '6pack' => 0,
            'case'  => 0
        );

        foreach ( $product->subproducts as $subproduct ) {

            $subproduct_wp_post = WP99234()->_products->get_by_subs_id( $subproduct->product_id );

            if ( ! $subproduct_wp_post ) {
                continue;
            }

            $subproduct_6pack = get_post_meta( $subproduct_wp_post->id, 'price_6pk', true );

            if( is_numeric( $subproduct_6pack ) && $subproduct_6pack > 0 ){
                $prices['6pack'] += (float)$subproduct_6pack;
            }

            $subproduct_case = get_post_meta( $subproduct_wp_post->id, 'price_case', true );

            if( is_numeric( $subproduct_case ) && $subproduct_case > 0 ){
                $prices['case'] += (float)$subproduct_case;
            }

        }

        return $prices;

    }

    /**
     * Delete all prices for a given product_id
     *
     * May void your warranty.
     *
     * @param $product_id
     *
     * @return int
     */
    public function delete_prices_for_product( $product_id ) {
        global $wpdb;

        return $wpdb->delete( $this->dbtable, array( 'product_id' => $product_id ) );
    }

    /**
     * Get the membership prices for a product.
     *
     * @param $product_id
     * @param $memberships
     *
     * @return array|void
     */
    public function get_membership_prices_for_product( $product_id, $memberships ) {
        global $wpdb;

        if ( ! is_int( $product_id ) ) {
            return;
        }

        $membership_ids_for_query = ( is_array( $memberships ) ) ? array_keys( $memberships ) : false ;

        if( is_array( $memberships ) ){
            $sql = $wpdb->prepare( "SELECT * FROM {$this->dbtable} WHERE product_id = %d AND membership_id IN ( " . join( ', ', $membership_ids_for_query ) . " )", $product_id );
        } elseif( $memberships === 'all' ){
            $sql = $wpdb->prepare( "SELECT * FROM {$this->dbtable} WHERE product_id = %d", $product_id );
        } else {
            return false;
        }

        $prices = $wpdb->get_results( $sql, ARRAY_A );

        $_prices = array();

        foreach ( $prices as $price ) {
            $_prices[ ] = new WP99234_Price( $price );
        }

        return $_prices;

    }

    /**
     * Calculate the price for a non-prepacked composite product.
     *
     * @param $product
     *
     * @return float
     */
    public function get_split_composite_price( $product ) {

        $price = 0;

        if ( isset( $product->subproducts ) && is_array( $product->subproducts ) && ! empty( $product->subproducts ) ) {
            foreach ( $product->subproducts as $subproduct ) {
                $price += (float) $subproduct->price * $subproduct->quantity;
            }
        }

        return $price;

    }

}