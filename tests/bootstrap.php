<?php

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _wp99234_manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp99234.php';
}
tests_add_filter( 'muplugins_loaded', '_wp99234_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';

activate_plugin( 'wp99234/wp99234.php' );

