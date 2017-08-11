<?php
/**
 * Class WP99234_Api
 *
 * API Wrapper class to interact with the troly API and pass the incoming requests to the wp99234-api to an appropriate controller.
 *
 * @package wp99234
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WP99234_Api {

    /**
     * Top level endpoint for the SUBS API.
     *
     * @var string
     */

    // @Jp : added staging vars for testing purpose
    static $endpoint = 'https://app.troly.io/';
    //static $endpoint = 'https://app.troly.io/';

    /**
     * Top level hostname for troly
     */
    static $hostname = 'app.troly.io';
    //static $hostname = 'app.troly.io';

    /**
     * API Version Number
     *
     * @var string
     */
    static $version = '1.1';

    /**
     * The Auth Consumer for API connectivity
     *
     * @var string|void
     */
    var $auth_consumer;

    /**
     * The Auth Resource for API connectivity
     *
     * @var string|void
     */
    var $auth_resource;

    /**
     * The Auth Key for API connectivity
     *
     * @var string|void
     */
    var $auth_key;

    /**
     * The Auth Version to use when communicating with subs.
     *
     * @var string
     */
    var $auth_version = '1.1';

    /**
     * API Request Errors Holder.
     *
     * @var
     */
    var $errors = array();

    /**
     * The API Server.
     *
     * @var
     */
    var $server;

    /**
     * Validates that there is enough information to run the API (eg API Keys etc are all entered etc)
     */
    function __construct( $consumer = false, $resource = false ){

        /**
         * Define i18N API Errors Strings.
         */
        define( 'WP99234_INVALID_REQUEST', __( 'Invalid Request', 'wp99234' ) );
        define( 'WP99234_UNAUTHORISED', __( 'Unauthorised', 'wp99234' ) );
        define( 'WP99234_NO_CREDS', sprintf( __( 'The credentials for Troly are missing or invalid. Please check your <a href="%s">Troly settings</a>.', 'wp99234' ), admin_url( 'admin.php?page=wp99234' ) ) );

        /**
         * Set the consumer and resource details.
         */
        if( ! $consumer ){
            $consumer = get_option( 'wp99234_consumer_key' );
        }

        if( ! $resource ){
            $resource = get_option( 'wp99234_resource_key' );
        }

        if( ! $consumer || empty( $consumer ) || ! $resource || empty( $resource ) ){
            throw new WP99234_Api_Exception( WP99234_NO_CREDS );
        }

        $this->auth_resource = $resource;
        $this->auth_consumer = $consumer;

        /**
         * Initialize appropriate wordpress hooks
         */
        add_action( 'init', array( $this, 'add_endpoint' ), 0 );
        add_action( 'init', array( $this, 'on_init' ) );
        // add query vars
        add_filter( 'query_vars', array( $this, 'add_query_vars'), 0 );
        // handle REST API requests
        add_action( 'parse_request', array( $this, 'maybe_handle_request'), 0 );

    }

    public function add_endpoint(){

        // REST API
        add_rewrite_rule( '^wp99234-api/1.([0-9]{1})/(.*)?', 'index.php?wp99234-api-version=1.$matches[1]&wp99234-api-route=$matches[2]', 'top' );

        // WC API for payment gateway IPNs, etc
        add_rewrite_endpoint( 'wp99234-api', EP_ALL );

    }

    /**
     * init hook
     */
    public function on_init(){}



    /**
     * Add Query Vars
     *
     * @return array
     */
    public function add_query_vars( $vars ){
        $vars[] = 'wp99234-api';
        $vars[] = 'wp99234-api-route';
        $vars[] = 'wp99234-api-version';

        return $vars;
    }

    /**
     * Determines if there is a current API request, and triggers the API Route if required.
     */
    public function maybe_handle_request(){

        global $wp;

        if(isset($wp->query_vars['wp99234-api-route'])){
			$wproute = explode( '/', $wp->query_vars['wp99234-api-route'] );

		}elseif(isset($wp->query_vars['pagename'])){
			$wproute = explode( '/', $wp->query_vars['pagename'] );
		}
		if ( ! empty( $wp->query_vars['wp99234-api-version'] ) ) {
            $wp->query_vars['wp99234-api-version'] = $wp->query_vars['wp99234-api-version'];
        }
		
        if ( ! empty( $wp->query_vars['wp99234-api-route'] ) ) {
            $wp->query_vars['wp99234-api-route'] = $wp->query_vars['wp99234-api-route']; 
        }

        //If we have no route, we have no request.
        if( ! isset( $wp->query_vars['wp99234-api-route'] ) ){
			return;
        }
		
        WP99234()->logger->debug( 'Request Received: ' . $wp->query_vars['wp99234-api-route'] );

        $this->includes();

        $_map = $this->api_classmap();

        if( ! isset( $_map[$wp->query_vars['wp99234-api-route']] ) ){
            WP99234()->logger->error( 'Invalid request. No Route.' );

            http_response_code( 400 );

            echo json_encode( array(
                'result' => 0,
                'data'   => array(
                    WP99234_INVALID_REQUEST
                )
            ) );

            exit;

        }
        $this->server = new $_map[$wp->query_vars['wp99234-api-route']];
        if( $this->server->authenticate() ){   
            $this->server->serve_request( $wproute );
        }

    }
 
    function includes(){

        require_once 'api/abstract-wp99234-api-server.php';
        require_once 'api/class-wp99234-api-products.php';
        require_once 'api/class-wp99234-api-customers.php';
        require_once 'api/class-wp99234-api-orders.php';
        require_once 'api/class-wp99234-api-membership-types.php'; 
    }

    function api_classmap(){

        return array(
            'products'  => 'WP99234_Api_Products',
            'customers' => 'WP99234_Api_Customers',
            'orders'    => 'WP99234_Api_Orders',
            'membership_types'    => 'WP99234_Api_Membership_Types'
        );

    }

    /**
     * Triggers a POST request to the API endpoint using wp_remote_post
     *
     * @param string|bool $url
     * @param array $data
     *
     * @return array|mixed
     */
    public function _call( $url = false, $data = array(), $method = "GET" ){

        /**
         * Setup the WP Remote Post parameters
         */
        $query = json_encode( $data );

        //Build the auth key.
        $timestamp = time();

        $this->auth_key = $this->generate_key( $timestamp );

        $authheader = sprintf( 'Token version=%s, timestamp=%s, consumer=%s, resource=%s, key=%s', $this->auth_version, $timestamp, $this->auth_consumer, $this->auth_resource, $this->auth_key );

        $headers = array(
            'Content-Type: application/json',
            'X-Authorization: ' . $authheader
        );

        if( ! empty( $data ) ){
            $headers[] = 'Content-Length: ' . strlen( $query );
        }

        if( ! $url ){
            $url = self::$endpoint;
        }

        if( defined( 'WP99234_DEBUG_API_HEADERS' ) && WP99234_DEBUG_API_HEADERS ){
            $url = 'http://htests.herokuapp.com/';
        }

	    /**
	     * use curl to send
	     */
        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER,true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

        switch( $method ){

            case 'PUT':
                curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT'  );
                curl_setopt( $ch, CURLOPT_POSTFIELDS   , $query );
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
                break;

            case 'GET':
            default:
                curl_setopt( $ch, CURLOPT_HTTPGET    , true );
                curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
                break;

        }

        // @Jp : added for testing purpose
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec( $ch );
        $_response = json_decode( $response );

        // @Jp : added for testing purpose
        //$tmperr = curl_error( $ch );
		
        if( defined( 'WP99234_Api_DEBUG' ) && WP99234_Api_DEBUG ){

            $info = curl_getinfo( $ch );

            WP99234()->logger->notice( sprintf( 'API Call ( %s / %s )', $method, $url ) );
            WP99234()->logger->notice( WP99234()->get_var_dump( $data ) );
            WP99234()->logger->notice( WP99234()->get_var_dump( $info ) );
            WP99234()->logger->notice( 'MEM USAGE: ' . memory_get_usage() );
            WP99234()->logger->notice( 'MEM USAGE REAL: ' . memory_get_usage( true ) );

            WP99234()->logger->notice( 'API Response:' );

            if( $_response ){
                WP99234()->logger->notice( var_export( $_response, true ) );
            } else {
                WP99234()->logger->notice( var_export( $response, true ) );
            }

        }

        $response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if( defined( 'WP99234_DEBUG_API_HEADERS' ) && WP99234_DEBUG_API_HEADERS ){
            echo $response;
            die;
        }

        if( $_response ){
            return $_response;
        } else {
            WP99234()->logger->error( 'Invalid response from troly (Not JSON). Response received below. Called URL: ' . $method  .' / ' . $url . ' Response Code: ' . $response_code );
            WP99234()->logger->error( $response );
        }

        return false;

    }


    function generate_key( $timestamp ){

        return sha1(
            $timestamp .
            $this->auth_consumer .
            dechex( (float)$this->auth_version * 100 ) .
            $this->auth_resource
        ) . substr( $timestamp, -2 );

    }

    function get_formatted_country_name( $code ){

        $geoip = new WC_Geo_IP();

        $key = array_search( $code, $geoip->GEOIP_COUNTRY_CODES );

        if( $key > 0 ){

            $name = $geoip->GEOIP_COUNTRY_NAMES[$key];

            return $name;

        }

        return $code;

    }

    function get_formatted_country_code( $name ){

        $geoip = new WC_Geo_IP();

        $key = array_search( $name, $geoip->GEOIP_COUNTRY_NAMES );

        if( $key > 0 ){

            $code = $geoip->GEOIP_COUNTRY_CODES[$key];

            return $code;

        }

        return $name;

    }

}

/**
 * Create an extension of the basic Exception class.
 *
 * Class WP99234_Api_Exception
 */
class WP99234_Api_Exception extends Exception {}
