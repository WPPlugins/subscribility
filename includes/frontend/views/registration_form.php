<?php
/**
 * Registration form for users to register as a club member.
 * Template Name: Membership Registration
 *
 * This template can be overridden by copying it to wp-content/themes/yourtheme/wp99234/registration_form.php.`
 *
 */

$break = 1;

$membership_options = get_option( 'wp99234_company_membership_types' );

$current_user = wp_get_current_user();

wp_enqueue_script( 'jquery-payment' );

get_header(); 
?>

<style type="text/css">

    /* clearfix hack class */
    .cfix:after {
      content: "";
      display: table;
      clear: both;
    }


    /* Membership option area  */
    .membership_option{
        border:1px solid #000;
        float:left;
        width:100%;
        background-color: #F9F9F9;
        padding:20px;
        margin-bottom:4%;
    }

    .membership_option h4{
        margin-top:0;
        margin-bottom:0;
        font-size:1.2em;
    }

    .membership_option:nth-child(2n+1){
        clear:both;
    }

    .membership_option.selected{
        border: 1px solid #CCC;
    }

    .membership_option.inactive{
        opacity: 0.6;
    }

    .membership_option_details ul{
        margin:0;
        padding:0;
        list-style: none;
    }
    @media screen and (min-width: 700px) {
        
        .membership_option:nth-child(2n){
            margin-left:4%;
        }
        .membership_option{
            width:48%;
        }
    }



    /* User details, CC details and delivery sections */
    .section.user_details,
    .section.cc_details,
    .section.delivery_details {
        width: 100%;
        box-sizing: border-box;
    }
    @media screen and (min-width: 700px) {
        
        .section.user_details,
        .section.cc_details {
            float: left;
            width: 50%;
            padding-right: 20px;
        }
        .section.delivery_details {
            float: right;
            width: 50%;
            padding-left: 20px;
        }
    }

    .section.user_details label,
    .section.cc_details label,
    .section.delivery_details label,
    .section.user_details input,
    .section.cc_details input,
    .section.delivery_details input {
        width: 100%;
    }

    #use_existing_card{
        width:auto;
    }

    /* Form button */
    #member_registration_form p.form-submit {
        text-align: right;
    }
    #member_registration_form p.form-submit input {
        width: 260px;
    }
	 
	 #membership_options .restricted {
		font-style: italic;
	 }


</style>

<form id="member_registration_form" action="" method="POST">
  
    <div class="woocommerce">
        <?php wc_print_notices(); ?>
        <p>All unmarked fields are required with at least one contact number.</p>
    </div>

    <div class="cfix">

        <div id="membership_options" class="cfix">

            <?php foreach ( apply_filters( 'wp99234_rego_form_membership_options', $membership_options ) as $membership_option ): ?>

                <?php if( ! in_array( $membership_option->visibility, array( 'public', 'restricted' ) ) ) continue; ?>

                <div class="membership_option <?php if(isset($_POST['selected_membership']) && $_POST['selected_membership']==$membership_option->id){echo 'selected';}elseif(isset($_POST['selected_membership'])){echo 'inactive';}?>">

                    <h4 class="membership_option_title"><?php echo esc_html( $membership_option->name ); ?></h4>

                    <div class="membership_option_details">

                        <?php if( ! empty( $membership_option->description ) ): ?>
                            <p><?php echo $membership_option->description; ?></p>
                            <?php if( ! empty( $membership_option->benefits ) ): ?>
                                <a href="#" class="subs-toggle-member-benefits">Show more</a>
                                <p class="membership-benefits"><?php echo $membership_option->benefits; ?></p>
                            <?php endif; ?>    
                        <?php endif; ?>
                    </div> 
                    <?php if( $membership_option->visibility == 'public' ) {  ?>

                        <input type="radio" class="selected_membership_radio" name="selected_membership" value="<?php echo $membership_option->id; ?>" <?php if(isset($_POST['selected_membership']) && $_POST['selected_membership']==$membership_option->id){echo 'checked="checked"';}?> />

                        <button class="select_membership" data-original_text="<?php _e( 'Select', 'wp99234' ); ?>" data-selected_text="<?php _e( 'Selected', 'wp99234' ); ?>" data-membership_option_id="<?php echo $membership_option->id; ?>" ><?php if(isset($_POST['selected_membership']) && $_POST['selected_membership']==$membership_option->id){ _e( 'Selected', 'wp99234' );}else{ _e( 'Select', 'wp99234' );}?></button>

                    <?php } else if ( $membership_option->visibility == 'restricted' ) { ?>
						  
                        <div class="restricted">You cannot currently sign up for this membership. Please contact us for further information.</div>

                    <?php } ?>

                </div>

            <?php endforeach; ?>

        </div>

        <div class="section user_details">

            <h4 class="section_title"><?php _e( 'Your Details', 'wp99234' ); ?></h4>

            <div class="section_content">

                <?php 
				$user_fields = array(
					'first_name' => array(
						'label' => __( 'First Name', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID, 'first_name', true ),
						'attributes' => array('required' => true)
					),
					'last_name' => array(
						'label' => __( 'Last Name', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'last_name', true ),
					),
					'reg_email' => array(
						'label' => __( 'Email', 'wp99234' ),
						'default' => ( $current_user ) ? $current_user->user_email : '' ,
						'attributes' => array('required' => true)
					),
					'phone' => array(
						'label' => __( 'Phone Number', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'phone', true ),
					),
					'mobile' => array(
						'label' => __( 'Mobile Number', 'wp99234' ),
						'default' => get_user_meta( $current_user->ID , 'mobile', true ),
					)
				);
				?>

                <?php foreach( $user_fields as $key => $user_field ){
                    WP99234()->_registration->display_field( $key, $user_field );
                } 
				?>
				
            </div>

        </div>

        <div class="section delivery_details">

            <h4 class="section_title"><?php _e( 'Delivery Details', 'wp99234' ); ?></h4>

            <div class="section_content">

                <?php $delivery_fields = array(
                    'company_name' => array(
                        'label' => __( 'Company Name (optional)', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_company', true ),
                    ),
                    'shipping_address_1' => array(
                        'label' => __( 'Delivery Address', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_address_1', true ),
                        'attributes' => array('required' => true)
                    ),
                    'shipping_suburb' => array(
                        'label' => __( 'Suburb', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_city', true ),
                        'attributes' => array('required' => true)
                    ),
                    'shipping_postcode' => array(
                        'label' => __( 'Postcode', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_postcode', true ),
                        'attributes' => array('required' => true)
                    ),
                    'shipping_state' => array(
                        'label' => __( 'State', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_state', true ),
                        'attributes' => array('required' => true)
                    ),
                    'shipping_country' => array(
                        'label' => __( 'Country', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID , 'shipping_country', true ),
                        'attributes' => array('required' => true)
                    ),
                    'shipping_instructions' => array(
                        'type'  => 'textarea',
                        'label' => __( 'Delivery notes and instructions (optional)', 'wp99234' ),
                        'default' => get_user_meta( $current_user->ID, 'delivery_instructions', true )
                    )
                ); ?>

                <?php foreach( $delivery_fields as $key => $delivery_field ){
                    WP99234()->_registration->display_field( $key, $delivery_field );
                } ?>
				<?php 
					if(!is_user_logged_in()){
						$pass_fields = array(
							'user_pass' => array(
								'label' => __( 'Password', 'wp99234' ),
								'type' => 'password' ,
								'id' => 'password' ,
								'default' => '',
								'attributes' => array('required' => true)
							),
							'conf_pass' => array(
								'label' => __( 'Confirm Password', 'wp99234' ),
								'type' => 'password' ,
								'id' => 'confirm_password' ,
								'default' => '',
								'attributes' => array('required' => true)
							)
						);
						foreach( $pass_fields as $key => $pass_field ){
							WP99234()->_registration->display_field( $key, $pass_field );
						} 
					}	
				?>
            </div>

        </div>

        <div class="section cc_details">

            <h4 class="section_title"><?php _e( 'Payment Details', 'wp99234' ); ?></h4>

            <div class="section_content">

                <?php

                $cc_fields = array(
                    'cc_name' => array(
                        'label' => __( 'Name On Card', 'wp99234' ),
                        'default' => '',
                        'attributes' => array('required' => true)
                    ),
                    'cc_number' => array(
                        'label' => __( 'Card Number', 'wp99234' ),
                        'default' =>'',
                        'attributes' => array('required' => true)
                    ),
                    'cc_exp' => array(
                        'label' => __( 'CC Exp', 'wp99234' ),
                        'default' => '' ,
                        'attributes' => array(
                            'placeholder' => 'MM / YY',
                            'required' => true
                        ),
                    )
                );

                $has_cc_details = false;

                if( is_user_logged_in() ){
                    $has_cc_meta = get_user_meta( get_current_user_id(), 'has_subs_cc_data', true );
                    if( $has_cc_meta && $has_cc_meta == true ){
                        $has_cc_details = true;
                    }
                }

                if( $has_cc_details ){
                    echo '<input type="checkbox" id="use_existing_card" name="use_existing_card" checked="checked" value="yes" /> ' . sprintf( 'Use Your existing card (%s)', get_user_meta( get_current_user_id(), 'cc_number', true ) );
                    echo '<div id="hidden_cc_form"> <p>' . __( 'The details entered here will be stored securely for future use.', 'wp99234' ) . '</p>';
                }

                foreach( $cc_fields as $key => $cc_field ){
                    WP99234()->_registration->display_field( $key, $cc_field );
                }

                if( $has_cc_details ){
                    echo '</div>';
                }

                ?>

            </div>

        </div>
      
        <?php
          do_action('wp99234_preferences_form');
        ?>

    </div>

    <p class="form-submit form-row">
	<label id='message'></label>
        <input type="hidden" name="<?php echo WP99234()->_registration->nonce_name; ?>" value="<?php echo wp_create_nonce( WP99234()->_registration->nonce_action ); ?>" />
        <input type="submit" name="<?php echo WP99234()->_registration->submit_name; ?>" value="<?php _e( 'Sign Up Now', 'wp99234' ); ?>" id="member_submit">
    </p>

</form>
