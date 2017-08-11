<?php

/**
* This is the websockets js code to handle the "update" window
* (SEB) TODO, should probably be moved to includes/assets/js really. check on the PHP vars (do they all work ok)
*/

$channel = esc_js( $_GET['ws_channel'] );

if( ! isset( $args['order'] ) ){
    return '';
}

$order = $args['order'];

?>
<script type="text/javascript">

    /**
     Object { type: "start" } 62:370:13
     Object { message: "Processing payments", type: "update" } 62:375:13
     Object { message: "Generating payments", type: "update" } 62:375:13
     Object { progress: 0, completion: 1, type: "progress" } 62:375:13
     Object { progress: 1, completion: 1, type: "progress" } 62:375:13
     Object { ok: 1, fail: 0, message: "sockets.transactions-processed", type: "update", final: true } 62:375:13
     Object { type: "end" }
     */

    jQuery( document ).ready( function($){

        var WSURL = 'app.troly.io/websocket';

        var WSCHANNEL = '<?php echo $channel; ?>';

        var WP_AJAXURL = '<?php echo admin_url( 'admin-ajax.php' ); ?>';

        $( '.woocommerce' ).addClass( 'woocommerce_main' );

        $( '.woocommerce_main' ).block({
            message: null, overlayCSS: { background: '#fff', opacity: 0.6 }
        });

        // connect to server like normal
        var dispatcher = new WebSocketRails( WSURL );

        //console.log( dispatcher )

        // subscribe to the channel
        var channel = dispatcher.subscribe( WSCHANNEL );

        var ResultsBox = $( '<div id="ws_results" class="woocommerce"></div>' );

        if ($('#wpsubs_results').length >= 1 ) {
            $('#wpsubs_results').before( ResultsBox ); 
        } else {
            $( '.woocommerce_main' ).before( ResultsBox );    
        }

        channel.on_failure = function( test ){
            //log_result( 'Websocket Connection Failed' );
            unblock_ui();
        }

        channel.on_success = function( connection ){
            //log_result( 'Websocket Connection Succeeded' );
        }

        channel.bind('control', function(data) {
            // console.log( data );
            //log_result('Control channel event received: ' + data);
        });

        channel.bind('update', function(data) {
            // console.log( data );
            //log_result('Update channel event received: ' + data);
        });

        function unblock_ui(){
            dispatcher.disconnect();
            $( '.woocommerce_main' ).unblock();
            //log_result( 'Disconnected' );
        }

        function display_success(){
            log_result( '<div class="woocommerce-message"><?php _e( 'Payment was successful', 'wp99234' ); ?></div>' );
        }

        function display_error( text ){
            log_result( '<div class="woocommerce-error">' + text + '</div>' );
        }

        function log_result( result ){
            // console.log( result );
            var str = jQuery( '<p>' + result + '</p>' );
            ResultsBox.append( str );
        }

        //If no response in 15 seconds, we may have missed the boat. Query the order status and move on.
        setTimeout(function(){

            $.post( WP_AJAXURL, {
                action    : 'check_wp99234_payment_status',
                order_id  : <?php echo $order->id; ?>
            }, function( resp ){

                //none pending auth paid declined error
                if( resp.payment_status == 'auth' || resp.payment_status == 'paid' ) {

                    display_success();

                } else if( resp.payment_status == 'pending' ) {

                    display_error( '<?php _e( 'Your payment is queued for processing. You will be notified of the results.', 'wp99234' ); ?>' );

                } else {

                    var text = '<?php _e( 'There was an error processing your payment. We will contact you shortly.', 'wp99234' ); ?>';

                    if( resp.payment_status_details != null ){
                        text += '<br />( Ref: ' + resp.payment_status_details + ' )';
                    }

                    display_error( text )

                }

                unblock_ui();

            });

        }, 15000);

    });

</script>
