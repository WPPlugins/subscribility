<?php
/**
 * WP99234_Company class
 */
class WP99234_Clubs {

    function __construct(){

        $this->setup_actions();

    }

    function setup_actions(){

        add_action( 'wp_ajax_subs_import_memberships', array( $this, 'on_ajax_subs_import_memberships' ) );

    }

    function on_ajax_subs_import_memberships(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        if( ! wp_verify_nonce( $_REQUEST['nonce'], 'subs_import_memberships' ) ){
            WP99234()->send_sse_message( 0, __( 'Invalid Request', 'wp99234' ) );
            exit;
        }

        $this->get_company_membership_types( true );

        exit;

    }

    function get_company_membership_types( $is_sse = false ){

        $cid = get_option( 'wp99234_check_no' );

        if( ! $cid ){
            return false;
        }

        $time_started = time();

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Importing membership types / clubs';
      
        if( $is_sse ){
            WP99234()->send_sse_message( $time_started, __( 'Importing membership types.', 'wp99234' ) );
        }

        $endpoint = sprintf( '%s/companies/%s/membership_types?l=100&visibility_in[]=public&visibility_in[]=restricted&visibility_in[]=private', untrailingslashit( WP99234_Api::$endpoint ), $cid );

        $results = WP99234()->_api->_call( $endpoint );

        if( $results ){

            //Make the results an associative array to make processing users and finding prices a much easier operation later.
            $types = array();

            foreach( $results->results as $membership_type ){
                $types[$membership_type->id] = $membership_type;
            }

            update_option( 'wp99234_company_membership_types', $types );

            if( $is_sse ) {
                WP99234()->send_sse_message( $time_started, __( 'Membership types successfully imported', 'wp99234' ) );
                WP99234()->send_sse_message( $time_started, 'TERMINATE', 'message', 100 );
            } else{
                WP99234()->_admin->add_notice( __( 'Membership types successfully imported', 'wp99234' ), 'success' );

                wp_redirect( remove_query_arg( 'do_wp99234_import_membership_types' ) );
            }
            
            $message .= '\nMembership types / clubs successfully imported';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = true, 'Import', 'Memberships / Clubs Import', $message);
            }
          
            exit;

        }

        if( isset( $_GET['do_wp99234_import_membership_types'] ) ){

            if( $is_sse ){
                WP99234()->send_sse_message( $time_started, __( 'Membership types failed to import', 'wp99234' ), 'error' );
            } else{

                WP99234()->_admin->add_notice( __( 'Membership types failed to import', 'wp99234' ), 'error' );
                wp_redirect( remove_query_arg( 'do_wp99234_import_membership_types' ) );

            }
          
            $message .= '\nMembership types / clubs failed to import';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Import', 'Memberships / Clubs Import', $message);
            }

            exit;

        }

    }

}