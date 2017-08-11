<?php

abstract class WP99234_Api_Server {

    /**
     * The Request Method of the current request
     *
     * @var
     */
    var $method;

    /**
     * The route for the current request.
     *
     * @var
     */
    var $route;

    /**
     * The args for the current request
     *
     * @var
     */
    var $args;

    /**
     * The body sent for the current request.
     *
     * @var
     */
    var $body;

    /**
     * The data to send in a JSON response.
     *
     * @var
     */
    var $response;

    /**
     * Accepted HTTP Methods.
     *
     * @var
     */
    var $accepted_methods;

    /**
     * Errors.
     *
     * @var array
     */
    var $errors = array();

    function __construct(){

        $this->method = $_SERVER['REQUEST_METHOD'];

        $this->body = $this->_get_request_body();

        //Validate the current method is accepted
        if( ! in_array( $this->method, $this->accepted_methods ) ){
            WP99234()->logger->error( 'Invalid HTTP method (' . $this->method . ')' );
            $this->errors[] = WP99234_INVALID_REQUEST;
            $this->respond();
        }

    }

    /**
     * Serve the current API request.
     */
    function serve_request( $route ){}

    private function _get_request_body(){

        if( function_exists( 'http_get_request_body ' ) ){
            return json_decode( http_get_request_body() );
        } else {
            return json_decode( @file_get_contents('php://input') );
        }

    }

    /**
     * Validate the request is coming from the SUBS API. Return false if its not, it could be related to a different plugin. No need to debug that.
     *
     * @return bool
     */
	 
     function authenticate(){

        $headers = getallheaders();
		//Build the auth key.
		$timestamp = time();
		$auth_key = WP99234()->_api->generate_key($timestamp);
		$authheader = sprintf( 'Token version=%s, timestamp=%s, consumer=%s, resource=%s, key=%s', WP99234()->_api->auth_version, $timestamp, WP99234()->_api->auth_consumer, WP99234()->_api->auth_resource, $auth_key );
		$headers['X-Authorization'] = $authheader;
		$headers['Content-Type'] = 'application/json';
		
        WP99234()->logger->error( '$header array_keys data Below.' );
        WP99234()->logger->error( WP99234()->get_var_dump( array_keys( $headers ) ) );

        //Token Header is required to do anything.
        if( array_key_exists( 'X-Authorization', $headers ) ) {

            /**
             * Validate the token header
             */
            $token_header = $headers[ 'X-Authorization' ];
            if( strpos( $token_header, 'Token ' ) !== 0 ){
                return false;
            }

            $token_header = str_replace( 'Token ', '', $token_header );

            $_token_details = explode( ', ', $token_header );
            $token = array();

            foreach ( $_token_details as $detail ) {
                $_token                = explode( '=', $detail );
                $token[ $_token[ 0 ] ] = $_token[ 1 ];
            }

            $current_timestamp = time();

            $valid_key = WP99234()->_api->generate_key( $token[ 'timestamp' ] );

            if ( $token[ 'key' ] !== $valid_key || $token[ 'consumer' ] !== WP99234()->_api->auth_consumer || $token[ 'version' ] != WP99234()->_api->auth_version || $token[ 'resource' ] != WP99234()->_api->auth_resource ) {

                WP99234()->logger->error( 'Invalid Auth Credentials.' );

                http_response_code( 401 );
                $this->errors[] = 'Unauthorized';
                $this->respond();

            }

            return true;

        }

        //No Token Header, No API Request.
        WP99234()->logger->error( 'No Auth Header was found. Received headers are below.' );
        WP99234()->logger->error( WP99234()->get_var_dump( $headers ) );
        //WP99234()->logger->error( WP99234()->get_var_dump( $_SERVER ) );

        $this->errors[] = WP99234_INVALID_REQUEST;
        $this->respond();

    }

    /**
     * Return a successful API request.
     */
    function respond(){

        header( 'Content-Type:application/json' );

        $result = 1;

        if( ! empty( $this->errors ) ){

            if( http_response_code() == 200 ){
                http_response_code( 400 );
            }

            $result = 0;

            $this->response = $this->errors;

        }

        echo json_encode( array(
            'result' => $result,
            'data'   => $this->response
        ) );

        exit;

    }
	function generate_key( $timestamp ){

        return sha1(
            $timestamp .
            $this->auth_consumer .
            dechex( (float)$this->auth_version * 100 ) .
            $this->auth_resource
        ) . substr( $timestamp, -2 );

    }

}