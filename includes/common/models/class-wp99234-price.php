<?php
/**
 * When a product is imported, we need to do the following pricing calculations:
 *
 * 1) Calculate the price against membership ID's
 * 2) Calculate the combined price of non-prepacked combo products
 * 3) Calculate the membership prices of non-prepacked combo products
 *
 * These are stored and maintained in the wp99234_prices DB table for easy access.
 *
 * @TODO
 * Handle calculations in the import functions
 * Handle lookups on the price filter for woocommerce.
 *
 * Example Usage.
 * --------------
 *
 * $price = new WP99234_Price();
 * $price->membership_id = 1;
 * $price->product_id = 1;
 * $price->price = 23.45
 * $price->save();
 *
 *
 * $price = new WP99234_Price( 12 );
 * $price->price = 45.25;
 * $price->save();
 *
 */

class WP99234_Price {

    /**
     * The database table to use.
     *
     * @var string
     */
    private $dbtable;

    /**
     * Unique price ID
     * @var int
     */
    var $price_id;

    /**
     * membership type ID to reference membership prices
     * @var int
     */
    var $membership_id = 0;

    /**
     * Product ID to reference products.
     * @var
     */
    var $product_id;

    /**
     * Price to use.
     * @var float
     */
    var $price;

    /**
     * Load in the data if it is passed an ID to reference.
     *
     * @param int|array $id_or_data
     */
    function __construct( $id_or_data = null ){
        global $wpdb;

        $this->dbtable = $wpdb->prefix . 'wp99234_prices';

        if( is_array( $id_or_data ) ){
            $this->populate( $id_or_data );
        } elseif( is_int( $id_or_data ) ){
            $this->load( $id_or_data );
        }

        return $this;

    }

    /**
     * Return the current DB fields.
     *
     * @return array
     */
    function get_fields(){

        return array(
            'price_id',
            'membership_id',
            'product_id',
            'price'
        );

    }


    /**
     * Populate the object with the given data.
     *
     * @param $data
     */
    function populate( $data ){

        foreach( $this->get_fields() as $field ){

            if( isset( $data[$field] ) ){
                $this->{$field} = $data[$field];
            }

        }

    }

    /**
     * Load up the data from the given price_id
     *
     * @param $id
     */
    function load( $id ){
        global $wpdb;

        $query = $wpdb->prepare( "SELECT * FROM {$this->dbtable} WHERE price_id = %s", $id );

        $row = $wpdb->get_row( $query );

        foreach( $this->get_fields() as $field ){
            $this->{$field} = $row->{$field};
        }

    }

    /**
     * Delete the current row from the database.
     *
     * @return false|int|void
     */
    function delete(){
        global $wpdb;

        if( ! $this->price_id ){
            return;
        }

        return $wpdb->delete( $this->dbtable, array( 'price_id' => $this->price_id ) );

    }

    /**
     * Save the current object to the database.
     *
     * If no price_id exists, insert a new row, else update the existing one.
     */
    function save(){

        if( $this->price_id ){
            $this->_update();
        } else {
            $this->_create();
        }

        return $this;

    }

    /**
     * Update the DB with the current object.
     */
    private function _update(){
        global $wpdb;

        $wpdb->update(
            $this->dbtable,
            array(
                'membership_id' => $this->membership_id,
                'product_id'    => $this->product_id,
                'price'         => $this->price
            ),
            array( 'price_id' => $this->price_id ),
            array(
                '%d',
                '%d',
                '%f'
            ),
            array( '%d' )
        );

    }

    /**
     * Create a database entry with the current object and set the price_id after insert.
     */
    private function _create(){
        global $wpdb;

        $wpdb->insert(
            $this->dbtable,
            array(
                'membership_id' => $this->membership_id,
                'product_id'    => $this->product_id,
                'price'         => $this->price
            ),
            array(
                '%d',
                '%d',
                '%f'
            )
        );

        $this->price_id = $wpdb->insert_id;

    }

}