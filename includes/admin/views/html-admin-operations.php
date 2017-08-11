<div class="wrap woocommerce wp99234">
        <?php
        wp_nonce_field( 'wp99234_admin_operations_' . $current_tab );
        self::show_messages();
        ?>
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper wp99234-nav-tab-wrapper">
            <?php
            foreach ( $tabs as $name => $label ) {
                echo '<a href="' . admin_url( 'admin.php?page=wp99234-operations&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
            }
            ?>
        </nav>
        <h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>

        <?php
        include_once( 'html-admin-operations-'.$current_tab.'.php' );
        ?>
</div>