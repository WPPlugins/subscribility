<?php
/**
 * Admin View: Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap woocommerce wp99234">
	<form method="<?php echo esc_attr( apply_filters( 'wp99234_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<?php

			wp_nonce_field( 'wp99234_admin_settings_' . $current_tab );
			self::show_messages();

		?>
		<nav class="nav-tab-wrapper woo-nav-tab-wrapper wp99234-nav-tab-wrapper">
			<?php

				foreach ( $tabs as $name => $label ) {
					echo '<a href="' . admin_url( 'admin.php?page=wp99234&tab=' . $name ) . '" class="nav-tab ' . ( $current_tab == $name ? 'nav-tab-active' : '' ) . '">' . $label . '</a>';
				}
				do_action( 'wp99234_settings_tabs' );
			?>
		</nav>
		<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
		<?php
			do_action( 'wp99234_settings_' . $current_tab );
		?>
		<p class="submit">
			<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
				<input name="save" class="button-primary woocommerce-save-button wp99234-save-button" type="submit" value="<?php esc_attr_e( 'Save changes', 'wp99234' ); ?>" />
			<?php endif; ?>
			<?php wp_nonce_field( 'wp99234' ); ?>
		</p>
	</form>
</div>
