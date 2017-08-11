<?php
/**
 * Admin Configuration
 *
 * @author      EmpireOne Group
 * @category    Admin
 * @package     Troly/Admin
 * @version     1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * Get all WooCommerce screen ids.
 *
 * @return array
 */
function wp99234_get_screen_ids () {

	$wp99234_screen_id = sanitize_title( __( 'Troly', 'wp99234' ) );
	$screen_ids   = array(
		'toplevel_page_' . $wp99234_screen_id,
        'toplevel_page_wp99234',
		$wp99234_screen_id . '_page_wp99234',
		$wp99234_screen_id . '_page_wp99234-operations',
	);

	return apply_filters( 'wp99234_screen_ids', $screen_ids );
}


function query_time_server ( $timeserver, $socket ) {
	$fp = fsockopen( $timeserver, $socket, $err, $errstr, 5 );
	# parameters: server, socket, error code, error text, timeout
	if ( $fp ) {
		fputs( $fp, "\n" );
		$timevalue = fread( $fp, 49 );
		fclose( $fp ); # close the connection
	} else {
		$timevalue = " ";
	}

	$ret    = array();
	$ret[ ] = $timevalue;
	$ret[ ] = $err;     # error code
	$ret[ ] = $errstr;  # error text

	return ( $ret );
} # function query_time_server


function check_timestamp () {

	$timeserver = "ntp.pads.ufrj.br";
	$timercvd   = $this->query_time_server( $timeserver, 37 );

	//if no error from query_time_server
	if ( ! $timercvd[ 1 ] ) {

		$current_time = time();

		$timevalue = bin2hex( $timercvd[ 0 ] );
		$timevalue = abs( HexDec( '7fffffff' ) - HexDec( $timevalue ) - HexDec( '7fffffff' ) );
		$timestamp  = $timevalue - 2208988800; # convert to UNIX epoch time stamp

		$diff = $timestamp - $current_time;

		wp_die( 'The time difference is currently ' . $diff . ' seconds.' );

	} else {
		echo "Unfortunately, the time server $timeserver could not be reached at this time. ";
		echo "$timercvd[1] $timercvd[2].<br>\n";
		exit;
	}
}