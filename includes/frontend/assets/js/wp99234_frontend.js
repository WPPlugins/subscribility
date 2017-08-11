jQuery( document ).ready( function($){
  
    $( '.selected_membership_radio' ).css( {
        'position' : 'absolute',
        'top' : 0,
        'left' : '-1000px'
    });

    //@TODO - Optimise this
    $( '.select_membership' ).on( 'click', function(e){
        e.preventDefault();

        $( this ).prev( 'input' ).click();

        $( '.membership_option.selected' ).removeClass( 'selected' );
        $( '.membership_option.inactive' ).removeClass( 'inactive' );

        $( '.membership_option .select_membership' ).each( function(){
            $( this ).text( $( this ).data( 'original_text' ) );
        });

        $( this ).parent( '.membership_option' ).addClass( 'selected' );

        $( '.membership_option' ).not( $( this ).parent( '.membership_option' ) ).addClass( 'inactive' );

        var button = $( this );

        button.text( $( this ).data( 'selected_text' ) );// = 'TEST';

    });

    //Hide the CC form if required
    $( '#hidden_cc_form' ).hide();
    $("#hidden_cc_form :input").removeAttr('required');

    $( '#use_existing_card' ).on( 'change', function(){
        if( $( this ).is( ':checked' ) ){
            $( '#hidden_cc_form' ).hide();
            $("#hidden_cc_form :input").removeAttr('required');
        } else {
            $( '#hidden_cc_form' ).show();
            $("#hidden_cc_form :input").attr('required', '1');
        }
    });

    $( 'body' ).on( 'load', function(){

        $('input[name=cc_exp]').payment('formatCardExpiry');
        $('input[name=cc_number]').payment('formatCardNumber');

        $('input[name=cc_number]' ).on( 'blur', function(){
            if( ! jQuery.payment.validateCardNumber( $( this ).val() ) ){
                $( this ).addClass( 'invalid' )
            } else {
                $( this ).removeClass( 'invalid' )
            }
        });

    } )

    $( 'a.subs-toggle-member-benefits' ).click(function(e) {
        e.preventDefault(); 
        $( this ).next( 'p.membership-benefits' ).toggle('linear'); 
    })
	$('#confirm_password').on('keyup', function () {
		if ($(this).val() == $('#password').val()) {
			$("#member_submit").prop("disabled", false);
			$('#message').html('');
		} else {
			$('#message').html('Password not matching').css('color', 'red');
			$("#member_submit").prop("disabled", true);
		}
	});	

});