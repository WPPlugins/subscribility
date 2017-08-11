/**
 * Created by bcasey on 26/03/15.
 */
jQuery( document ).ready( function($){
  
    // Hide the CC form if required
    $( '#hidden_cc_form' ).hide();
    $("#hidden_cc_form :input").removeAttr('required');
  
    $("body").on('change', '#use_existing_card', function() {
        if( $( this ).is( ':checked' ) ){
            $( '#hidden_cc_form' ).hide();
            $("#hidden_cc_form :input").removeAttr('required');
        } else {
            $( '#hidden_cc_form' ).show();
            $("#hidden_cc_form :input").attr('required', '1');
        }
    });

    //alert( typeof WebSocketRails );

    //if( typeof WebSocketRails == 'function' ){
    //
    //    $( 'form.checkout' ).on( 'submit', function(e){
    //
    //        e.preventDefault();
    //
    //        var form = $( this );
    //
    //        $.post( wc_checkout_params.ajax_url, {
    //            'action' : 'handle_checkout',
    //            'data' : form.serialize()
    //        }, function( ret ){
    //
    //
    //
    //        } );
    //
    //    });
    //
    //
    //
    //}

});