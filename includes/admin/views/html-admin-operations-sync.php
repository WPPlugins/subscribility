<script>
    jQuery( document ).ready( function($){

        $( '.background_process_button_import' ).on( 'click', function(){

            var ResultsWrapper = document.getElementById('sse_results_wrapper_import_id');
            var VisibilityWrapper = document.getElementById('sse_results_visibility_import_id');

            var LogWrapper = $( ResultsWrapper ).find( '.sse_log_wrapper' );
            var ResultsBox = $( '<div style="height:200px; overflow:auto;" class="results_box"></div>' );

            var ProgressWrapper = $( ResultsWrapper ).find( '.sse_progress_wrapper' );
            var ProgressBar = $( '<div style="height:100%; background:#CCC; width:0;" class="progress_bar"></div>' )

            $( LogWrapper ).empty().append( ResultsBox );
            $( ProgressWrapper ).empty().append( ProgressBar );

            //ResultsWrapper.show();
            ResultsWrapper.style.display = "block";
            VisibilityWrapper.style.display = "block";

            source = new EventSource( $( this ).data( 'event_source') );

            source.addEventListener('message' , function(e) {

                var result = JSON.parse( e.data );

                if ( e.data.search ( 'TERMINATE' ) != - 1 ) {
                    event_add_log ( 'Process has successfully completed.' );
                    source.close ();
                } else {
                    event_add_log( result.message );
                }

                $( ProgressBar ).css({
                    width : result.progress + '%'
                });

            });

            source.addEventListener('fatal' , function(e) {
                var result = JSON.parse( e.data );
                event_add_log( result.message );
                
                // This should only be fatal!
                source.close();
                event_add_log( "Process halted.");
            });

            function event_add_log ( message ) {

                if( message && message.length > 0 ){

                    var msg = $( '<span style="opacity:0;">' + message + '<br /></span>' );

                    $( ResultsBox ).append( msg );

                    $( ResultsBox ).scrollTop( $( ResultsBox )[0].scrollHeight );

                    $( msg ).fadeTo( 250, 1 );

                }

            }

        });


        $( '.background_process_button_export' ).on( 'click', function(){

            var ResultsWrapper = document.getElementById('sse_results_wrapper_export_id');
            var VisibilityWrapper = document.getElementById('sse_results_visibility_export_id');

            var LogWrapper = $( ResultsWrapper ).find( '.sse_log_wrapper' );
            var ResultsBox = $( '<div style="height:200px; overflow:auto;" class="results_box"></div>' );

            var ProgressWrapper = $( ResultsWrapper ).find( '.sse_progress_wrapper' );
            var ProgressBar = $( '<div style="height:100%; background:#CCC; width:0;" class="progress_bar"></div>' )

            $( LogWrapper ).empty().append( ResultsBox );
            $( ProgressWrapper ).empty().append( ProgressBar );

            //ResultsWrapper.show();
            ResultsWrapper.style.display = "block";
            VisibilityWrapper.style.display = "block";

            source = new EventSource( $( this ).data( 'event_source') );

            source.addEventListener('message' , function(e) {

                var result = JSON.parse( e.data );

                if ( e.data.search ( 'TERMINATE' ) != - 1 ) {
                    event_add_log ( 'Process has successfully completed.' );
                    source.close ();
                } else {
                    event_add_log( result.message );
                }

                $( ProgressBar ).css({
                    width : result.progress + '%'
                });

            });

            source.addEventListener('error' , function(e) {

                event_add_log ( 'An error has occurred. Please try again.' );

                //kill the object ?
                source.close();

            });

            function event_add_log ( message ) {

                if( message && message.length > 0 ){

                    var msg = $( '<span style="opacity:0;">' + message + '<br /></span>' );

                    $( ResultsBox ).append( msg );

                    $( ResultsBox ).scrollTop( $( ResultsBox )[0].scrollHeight );

                    $( msg ).fadeTo( 250, 1 );

                }

            }

        });
    });

    function toggle_visibility(idx, idy) {
        var e = document.getElementById(idx);

        if(e.style.display == 'none') {
            e.style.display = 'block';
        }
        else {
            e.style.display = 'none';
        }

        var a = document.getElementById(idy);

        if(a.innerText == 'hide') {
            a.innerText = 'show';
        }
        else {
            a.innerText = 'hide';
        }
    }

</script>

<style>

    .sse_results_wrapper{
        display:none;
        width:440px;
        margin-top: 10px;
        border: 1px solid rgb(204, 204, 204);
    }

    .sse_progress_wrapper{
        width:100%;
        height:20px;
        border-top:1px solid #CCC;
    }

    .results_box span{
        padding:3px 10px;
        display:block;
    }

    .sync_title {
        float: left;
        margin-right: 6px;
        font-size: large;
        font-weight: 600;
    }

    .sync_title_more {
        position:relative;
        top: 2px;
        font-size: smaller;
        font-weight: 700;
        color: gray;
    }

    .sync_image {
        position: relative;
        left: -4px;
        margin-top: 30px;
    }

    .sync_button_container {
        float: left;
        margin-top:25px;
        margin-right: 10px;
    }

    a.button.sync_button:hover {
        background: #458de3;
        border-color: #006799;
        color: #fff;
    }

    .sync_clear {
        clear: both;
    }

    .sse_results_visibility {
        position: relative;
        width:440px;
        display: none;
    }

    .a_visibility {
        float: right;
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

    .a_visibility:active {
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

    .a_visibility:focus {
        outline: none;
        border-color: inherit;
        -webkit-box-shadow: none;
        box-shadow: none;
        text-decoration: none;
    }

</style>


<div class="wrap">

    <br />
    <br />

    <div class="sync_title"><?php _e( 'Import', 'wp99234' ); ?></div><div class="sync_title_more">(<?php _e( 'Pull from Troly. This will create new or update existing records.', 'wp99234' ); ?>)</div>

    <div class="sync_image"><img src="<?php echo WP99234()->plugin_url() ?>/includes/admin/assets/images/subs_to_wp.png"></div>

    <div id="trigger_membership_import" class="sync_button_container">

        <?php $url = add_query_arg( array(
            'do_wp99234_import_membership_types' => 1
        ), admin_url( 'admin.php?page=wp99234' ) ); ?>

        <?php $import_memberships_url = add_query_arg( array(
            'action' => 'subs_import_memberships',
            'nonce'  => wp_create_nonce( 'subs_import_memberships' )
        ), admin_url( 'admin-ajax.php' ) ); ?>

        <a class="button sync_button background_process_button_import" data-event_source="<?php echo esc_url_raw( $import_memberships_url ); ?>" href="javascript:void(0)"><?php _e( 'Import memberships / clubs', 'wp99234' ); ?></a>

    </div>

    <?php
    $can_see_product_import = false;

    if( get_option( 'wp99234_product_import_has_run' ) == true ){
        if( current_user_can( 'manage_wp99234_products' ) ){
            $can_see_product_import = true;
        }
    } else {
        $can_see_product_import = true;
    }

    ?>

    <?php if( $can_see_product_import ): ?>
        <div id="run_product_import" class="sync_button_container">

            <?php $import_products_url = add_query_arg( array(
                'action' => 'subs_import_products',
                'nonce'  => wp_create_nonce( 'subs_import_products' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_product_import_button" class="button sync_button background_process_button_import" href="javascript:void(0)" data-event_source="<?php echo esc_url_raw( $import_products_url ); ?>"><?php _e( 'Import products', 'wp99234' ); ?></a>

        </div>
    <?php endif; ?>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_user_import = false;

    if( get_option( 'wp99234_user_import_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_user_import = true;
        }
    } else {
        $can_see_user_import = true;
    }
    ?>

    <?php if( $can_see_user_import ): ?>
        <div id="run_user_import" class="sync_button_container">

            <?php $import_users_url = add_query_arg( array(
                'action' => 'subs_import_users',
                'nonce'  => wp_create_nonce( 'subs_import_users' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_user_import_button" class="button sync_button background_process_button_import" data-event_source="<?php echo esc_url_raw( $import_users_url ); ?>" href="javascript:void(0)"><?php _e( 'Import customers', 'wp99234' ); ?></a>

        </div>
    <?php endif; ?>

    <div class="sync_clear">&nbsp;</div>

    <div id="sse_results_wrapper_import_id" class="sse_results_wrapper">
        <div id="sse_log_wrapper_import_id" class="sse_log_wrapper" style="width:100%;"></div>
        <div class="sse_progress_wrapper"></div>
    </div>
    <div id="sse_results_visibility_import_id" class="sse_results_visibility">
        <a class="a_visibility" id="sse_visibility_import_a_id" onclick="toggle_visibility('sse_log_wrapper_import_id', 'sse_visibility_import_a_id');" href="javascript:void(0)">hide</a>
    </div>

    <br />
    <br />
    <br />

    <div class="sync_title"><?php _e( 'Export', 'wp99234' ); ?></div><div class="sync_title_more">(<?php _e( 'Push to Troly. This will create new or update existing records.', 'wp99234' ); ?>)</div>

    <div class="sync_image"><img src="<?php echo WP99234()->plugin_url() ?>/includes/admin/assets/images/wp_to_subs.png"></div>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_user_export = false;

    if( get_option( 'wp99234_user_export_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_user_export = true;
        }
    } else {
        $can_see_user_export = true;
    }
    ?>

    <?php if( $can_see_user_export ): ?>

        <div id="run_user_export" class="sync_button_container">

            <?php $export_users_url = add_query_arg( array(
                'action' => 'subs_export_users',
                'nonce'  => wp_create_nonce( 'subs_export_users' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_user_export_button" class="button sync_button background_process_button_export" data-event_source="<?php echo esc_url_raw( $export_users_url ); ?>" href="javascript:void(0)"><?php _e( 'Export customers', 'wp99234' ); ?></a>

        </div>

    <?php endif; ?>

    <?php

    //Users can only see the import button if they ran the initial import or if no import has been run.
    $can_see_product_export = false;

    if( get_option( 'wp99234_product_export_has_run' ) === true ) {
        if ( current_user_can( 'manage_wp99234_users' ) ) {
            $can_see_product_export = true;
        }
    } else {
        $can_see_product_export = true;
    }
    ?>

    <?php if( $can_see_user_export ): ?>

        <div id="run_product_export" class="sync_button_container">

            <?php $export_products_url = add_query_arg( array(
                'action' => 'subs_export_products',
                'nonce'  => wp_create_nonce( 'subs_export_products' )
            ), admin_url( 'admin-ajax.php' ) ); ?>

            <a id="run_product_export_button" class="button sync_button background_process_button_export" data-event_source="<?php echo esc_url_raw( $export_products_url ); ?>" href="javascript:void(0)"><?php _e( 'Export products', 'wp99234' ); ?></a>

        </div>

    <?php endif; ?>

    <div class="sync_clear">&nbsp;</div>

    <div id="sse_results_wrapper_export_id" class="sse_results_wrapper">
        <div id="sse_log_wrapper_export_id" class="sse_log_wrapper" style="width:100%;"></div>
        <div class="sse_progress_wrapper"></div>
    </div>
    <div id="sse_results_visibility_export_id" class="sse_results_visibility">
        <a class="a_visibility" id="sse_visibility_export_a_id" onclick="toggle_visibility('sse_log_wrapper_export_id', 'sse_visibility_export_a_id');" href="javascript:void(0)">hide</a>
    </div>

</div>