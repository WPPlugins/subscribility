<?php
/**
 * Newsletter Registration Form. 
 * This creates a customer in subs with the "received newsletter" flag turned on.
 */

?>

<style>
    #newsletter_registration_form label{
        display:block;
        width:75%;
    }

    #newsletter_registration_form input[type=text]{
        width:100%;
    }

    #newsletter_registration_form .form-submit{
        margin-top:40px;
    }
</style>

<form id="newsletter_registration_form" action="#newsletter_registration_form" method="POST">

    <div class="woocommerce">
        <?php wc_print_notices(); ?>
    </div>

    <div class="cfix">

        <?php $fields = array(
            'first_name' => array(
                'label' => __( 'First Name', 'wp99234' ),
                'default' => '',
            ),
            'reg_email' => array(
                'label' => __( 'Email', 'wp99234' ),
                'default' => '',
            )
        ); ?>

        <?php foreach( $fields as $key => $field ){
            WP99234()->_newsletter->display_field( $key, $field );
        } ?>

		<p class="form-submit form-row">
			<input type="hidden" name="<?php echo WP99234()->_newsletter->nonce_name; ?>" value="<?php echo wp_create_nonce( WP99234()->_newsletter->nonce_action ); ?>" />
			<input type="submit" name="<?php echo WP99234()->_newsletter->submit_name; ?>" value="<?php _e( 'Sign Up Now', 'wp99234' ); ?>"
		</p>
      
    </div>

</form>