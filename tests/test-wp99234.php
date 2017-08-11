<?php

class WP99234Test extends WP_UnitTestCase {

    function setup() {

        require_once 'wp99234.php';

        parent::setup();

    }

    function testWP99234IsLoaded(){
        $test = WP99234();

        //Test Main Object
        $this->assertObjectHasAttribute( '_instance', $test );

        //Test Admin Loaded
        $this->assertTrue( is_object( WP99234()->_admin ), 'WP99234()->_admin Does Not Exist.' );

    }

}

