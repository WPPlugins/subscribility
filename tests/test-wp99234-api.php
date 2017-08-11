<?php

class WP99234_Api_Test extends WP_UnitTestCase {

    var $apiObj;

    function setup() {

        require_once 'WP99234.php';

        $this->apiObj = new WP99234_Api( 123456 );

        parent::setup();

    }

    function tearDown(){

        remove_all_filters( 'pre_http_request' );

        parent::tearDown();

    }

    public static function requestReturningNonJson(){

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => 17,
                'connection' => 'close',
                'content-type' => 'application/json'
            ),
            'body' => 'Not Json Response',
            'response' => array(
                'code' => 200,
                'messsage' => 'OK'
            ),
            'cookies' => array()
        );

    }

    public static function requestReturningInvalidJson(){

        $body = json_encode( array(
            'invalid_json' => 1
        ) );

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => strlen( $body ),
                'connection' => 'close',
                'content-type' => 'application/json'
            ),
            'body' => $body,
            'response' => array(
                'code' => 200,
                'messsage' => 'OK'
            ),
            'cookies' => array()
        );

    }

    public static function requestReturningInvalidContentType(){

        $body = '{
    "id": 13,
    "name": "2011 Angullong Cabernet Merlot",
    "description": "\"A handy offering from Angullong here. Always pleased to see aspirational pricing eschewed for something more realistic and appropriate too.\"   |    Wine Profile Colour  Dark plum colour with a...",
    "product_number": null,
    "barcode_url": "/assets/bar-code-example.jpg",
    "price": "19.0",
    "stock": "50.0",
    "stock_uom": "b",
    "created_at": "2014-12-02T13:12:02.461+11:00",
    "company_id": 2,
    "foods": "Lamb, Turkey",
    "cellar_until": "2018",
    "price_case": "20.0",
    "price_6pk": "22.0",
    "stock_location_2": "0.0",
    "stock_location_3": "0.0",
    "stock_location_4": "0.0",
    "stock_location_5": "0.0",
    "hero_img": {"hero_img": {"url": null, "thumbnail": {"url": null}}},
    "tagline": null,
    "award_1": null,
    "award_2": null,
    "award_3": null,
    "award_4": null,
    "vintage": null,
    "available_after": null,
    "tasting": null,
    "avg_rating": 0.0,
    "rating_count": null,
    "qr_code": {"qr_code": {"url": null, "thumbnail": {"url": null}}},
    "winemaking": null,
    "stock_uom_2": "b",
    "stock_uom_3": "b",
    "stock_uom_4": "b",
    "stock_uom_5": "b",
    "category": "Wine",
    "sort_weight": null,
    "weight": "1250.0",
    "split_ols": true,
    "te_divider": null,
    "token": "rzUwtstZdHC333drk9pJ",
    "sell_uom": "b",
    "classname": "Product",
    "tags": [
        {
            "id": 309,
            "name": "Bottle 3L (Jeroboam)",
            "category": "container",
            "usage": "product",
            "classname": "Tag"
        },
        {"id": 201, "name": "Screwcap", "category": "closure", "usage": "product", "classname": "Tag"},
        {"id": 405, "name": "Port/Fortified", "category": "wine-type", "usage": null, "classname": "Tag"},
        {"id": 539, "name": "Montepulciano", "category": "grape-variety", "usage": null, "classname": "Tag"},
        {"id": 1106, "name": "Beechworth, VIC", "category": "wine-region", "usage": null, "classname": "Tag"},
        {
            "id": 103,
            "name": "Includes GST",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        },
        {
            "id": 104,
            "name": "Visible in Tasting",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        }
    ]
}';

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => strlen( $body ),
                'connection' => 'close',
                'content-type' => 'application/pdf'
            ),
            'body' => $body,
            'response' => array(
                'code' => 200,
                'message' => 'OK'
            ),
            'cookies' => array()
        );


    }

    public static function requestReturningInvalidContentLength(){

        $body = '{
    "id": 13,
    "name": "2011 Angullong Cabernet Merlot",
    "description": "\"A handy offering from Angullong here. Always pleased to see aspirational pricing eschewed for something more realistic and appropriate too.\"   |    Wine Profile Colour  Dark plum colour with a...",
    "product_number": null,
    "barcode_url": "/assets/bar-code-example.jpg",
    "price": "19.0",
    "stock": "50.0",
    "stock_uom": "b",
    "created_at": "2014-12-02T13:12:02.461+11:00",
    "company_id": 2,
    "foods": "Lamb, Turkey",
    "cellar_until": "2018",
    "price_case": "20.0",
    "price_6pk": "22.0",
    "stock_location_2": "0.0",
    "stock_location_3": "0.0",
    "stock_location_4": "0.0",
    "stock_location_5": "0.0",
    "hero_img": {"hero_img": {"url": null, "thumbnail": {"url": null}}},
    "tagline": null,
    "award_1": null,
    "award_2": null,
    "award_3": null,
    "award_4": null,
    "vintage": null,
    "available_after": null,
    "tasting": null,
    "avg_rating": 0.0,
    "rating_count": null,
    "qr_code": {"qr_code": {"url": null, "thumbnail": {"url": null}}},
    "winemaking": null,
    "stock_uom_2": "b",
    "stock_uom_3": "b",
    "stock_uom_4": "b",
    "stock_uom_5": "b",
    "category": "Wine",
    "sort_weight": null,
    "weight": "1250.0",
    "split_ols": true,
    "te_divider": null,
    "token": "rzUwtstZdHC333drk9pJ",
    "sell_uom": "b",
    "classname": "Product",
    "tags": [
        {
            "id": 309,
            "name": "Bottle 3L (Jeroboam)",
            "category": "container",
            "usage": "product",
            "classname": "Tag"
        },
        {"id": 201, "name": "Screwcap", "category": "closure", "usage": "product", "classname": "Tag"},
        {"id": 405, "name": "Port/Fortified", "category": "wine-type", "usage": null, "classname": "Tag"},
        {"id": 539, "name": "Montepulciano", "category": "grape-variety", "usage": null, "classname": "Tag"},
        {"id": 1106, "name": "Beechworth, VIC", "category": "wine-region", "usage": null, "classname": "Tag"},
        {
            "id": 103,
            "name": "Includes GST",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        },
        {
            "id": 104,
            "name": "Visible in Tasting",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        }
    ]
}';

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => 1,
                'connection' => 'close',
                'content-type' => 'application/json'
            ),
            'body' => $body,
            'response' => array(
                'code' => 200,
                'messsage' => 'OK'
            ),
            'cookies' => array()
        );

    }

    public static function requestReturningError(){

        $body = json_encode( array(
            'errors' => array(
                'Invalid API key'
            )
        ) );

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => 1,
                'connection' => 'close',
                'content-type' => 'application/json'
            ),
            'body' => $body,
            'response' => array(
                'code' => 200,
                'messsage' => 'OK'
            ),
            'cookies' => array()
        );

    }

    public static function validRequest(){

        $body = '{
    "id": 13,
    "name": "2011 Angullong Cabernet Merlot",
    "description": "\"A handy offering from Angullong here. Always pleased to see aspirational pricing eschewed for something more realistic and appropriate too.\"   |    Wine Profile Colour  Dark plum colour with a...",
    "product_number": null,
    "barcode_url": "/assets/bar-code-example.jpg",
    "price": "19.0",
    "stock": "50.0",
    "stock_uom": "b",
    "created_at": "2014-12-02T13:12:02.461+11:00",
    "company_id": 2,
    "foods": "Lamb, Turkey",
    "cellar_until": "2018",
    "price_case": "20.0",
    "price_6pk": "22.0",
    "stock_location_2": "0.0",
    "stock_location_3": "0.0",
    "stock_location_4": "0.0",
    "stock_location_5": "0.0",
    "hero_img": {"hero_img": {"url": null, "thumbnail": {"url": null}}},
    "tagline": null,
    "award_1": null,
    "award_2": null,
    "award_3": null,
    "award_4": null,
    "vintage": null,
    "available_after": null,
    "tasting": null,
    "avg_rating": 0.0,
    "rating_count": null,
    "qr_code": {"qr_code": {"url": null, "thumbnail": {"url": null}}},
    "winemaking": null,
    "stock_uom_2": "b",
    "stock_uom_3": "b",
    "stock_uom_4": "b",
    "stock_uom_5": "b",
    "category": "Wine",
    "sort_weight": null,
    "weight": "1250.0",
    "split_ols": true,
    "te_divider": null,
    "token": "rzUwtstZdHC333drk9pJ",
    "sell_uom": "b",
    "classname": "Product",
    "tags": [
        {
            "id": 309,
            "name": "Bottle 3L (Jeroboam)",
            "category": "container",
            "usage": "product",
            "classname": "Tag"
        },
        {"id": 201, "name": "Screwcap", "category": "closure", "usage": "product", "classname": "Tag"},
        {"id": 405, "name": "Port/Fortified", "category": "wine-type", "usage": null, "classname": "Tag"},
        {"id": 539, "name": "Montepulciano", "category": "grape-variety", "usage": null, "classname": "Tag"},
        {"id": 1106, "name": "Beechworth, VIC", "category": "wine-region", "usage": null, "classname": "Tag"},
        {
            "id": 103,
            "name": "Includes GST",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        },
        {
            "id": 104,
            "name": "Visible in Tasting",
            "category": "product-config",
            "usage": "product_biz_logic",
            "classname": "Tag"
        }
    ]
}';

        return array(
            'headers' => array(
                'date'   => 'Thu, 30 Sep 2010 15:16:36 GMT',
                'server' => 'Apache',
                'x-powered-by' => 'PHP/5.3.3',
                'x-server' => '10.90.6.243',
                'expires' => 'Thu, 30 Sep 2010 03:16:36 GMT',
                'cache-control' => array(
                    'no-store, no-cache, must-revalidate',
                    'post-check=0, pre-check=0'
                ),
                'vary' => 'Accept-Encoding',
                'content-length' => strlen( $body ),
                'connection' => 'close',
                'content-type' => 'application/json'
            ),
            'body' => $body,
            'response' => array(
                'code' => 200,
                'messsage' => 'OK'
            ),
            'cookies' => array()
        );

    }

    public function testApiLoaded(){

        //Test API Loaded
        $this->assertTrue( is_object( $this->apiObj ), 'WP99234()->_api Does Not Exist.' );

    }

    public function testCallThrowsWhenContentBodyIsNotJson() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'requestReturningNonJson' ) );

        $this->setExpectedException( 'WP99234_Api_Exception', WP99234_INVALID_REQUEST );

        if( is_object( $this->apiObj ) ){
            $this->apiObj->_call( array() );
        }

    }

    public function testCallThrowsWhenInvalidContentType() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'requestReturningInvalidContentType' ) );

        $this->setExpectedException( 'WP99234_Api_Exception', WP99234_INVALID_REQUEST );

        if( is_object( $this->apiObj ) ){
            $this->apiObj->_call( array() );
        }

    }

    public function testCallThrowsWhenInvalidJson() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'requestReturningInvalidJson' ) );

        $this->setExpectedException( 'WP99234_Api_Exception', WP99234_INVALID_REQUEST );

        if( is_object( $this->apiObj ) ){
            $this->apiObj->_call( array() );
        }

    }

    public function testCallThrowsWhenInvalidContentLength() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'requestReturningInvalidContentLength' ) );

        $this->setExpectedException( 'WP99234_Api_Exception', WP99234_INVALID_REQUEST );

        if( is_object( $this->apiObj ) ){
            $this->apiObj->_call( array() );
        }

    }

    public function testCallThrowsWhenErrorsReturned() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'requestReturningError' ) );

        $this->setExpectedException( 'WP99234_Api_Exception', WP99234_INVALID_REQUEST );

        if( is_object( $this->apiObj ) ){
            $this->apiObj->_call( array() );
        }

    }

    public function testCallSucceedsWithValidResponse() {

        add_filter( 'pre_http_request', array( __CLASS__ , 'validRequest' ) );

        if( is_object( $this->apiObj ) ){

            $results = $this->apiObj->_call( array() );

            $this->assertObjectHasAttribute( 'id', $results );
            $this->assertObjectHasAttribute( 'name', $results );

        }

    }

}