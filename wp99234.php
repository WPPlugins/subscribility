<?php
/**
 * Plugin Name: Troly
 * Plugin URI: https://wordpress.org/plugins/subscribility/
 * Description: Manage and fulfil your sales of wine, beers and other crafted beverages, through clubs and other direct-to-consumer sales channels.
 * Version: 2.1.7
 * Author: Troly
 * Author URI: http://troly.io
 * Text Domain: wp99234
 * Domain Path: /i18n/languages/
 *
 * @package WP99234
 * @category Core
 * @author Troly
 */

/**
 * @TODO Testing
 *
 * Product Update In Subs pushes to WP
 *  -- How to test?
 *
 * @TODO - Roadmap
 *
 * -- Create products to enable users to register as a member in Troly
 *
 * -- Give the user the option change their stored CC details in checkout, user profile and wp-admin profile.
 *
 * -- Stock level Integration.
 *  -- Stock levels are pulled in with the product import (and pushed from subs on change, including an order event.)
 *
 *
 * @TODO - Unit Tests for all classes and functions.
 *
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}


/**
 * Main Troly Class
 */
final class WP99234 {

    /**
     * wp99234 version.
     *
     * @var string 
     */
    public $version = '1.3';
    public $db_version = '1.0';


    /**
     * The single instance of the class.
     *
     * @var wp99234
     * @since 2.1
     */
    protected static $_instance = null;

    /**
     * The administration options
     *.
     * @var WS_Admin
     */
    var $_admin = null;

    /**
     * The API handle
     *
     * @var WS_Api 
     */
    var $_api = null;

    /**
     * 
     *
     * @var WS_Products
     */
    var  $_products = null;

    /**
     *
     *
     * @var WS_Woocommerce
     */
    var $_woocommerce = null;

    /**
     *
     *
     * @var WS_Users
     */
    var $_users = null;

    /**
     *
     *
     * @var WPS_Registration
     */
    var $_registration = null;

    /**
     *
     *
     * @var WS_Newsletter
     */
    var $_newsletter = null;

    /**
     *
     *
     * @var WS_Company
     */
    var $_company = null;

    /**
     *
     *
     * @var WS_Template
     */
    var $template = null;

    /**
     *
     *
     * @var WS_Prices
     */
    var $_prices = null;

    /**
     * Generic errors array.
     *
     * @var WS_Errors
     */
    var $_errors = array();

    /**
     * Logger
     *
     * @var WS_Logger
     */
    var $_logger = null;


    /**
     * WP99234 Constructor.
     */
    private function __construct() {

        $this->define_constants();
        $this->check_requirements();
        $this->includes();
        $this->init_hooks();

        do_action( 'wp99234_loaded' );
    }

    /**
     * Ensure the server environment is valid to run this plugin.
     */
    private function check_requirements() {

        if( version_compare(PHP_VERSION, '5.4.0', '<' ) ){
            $this->errors[] = __( 'Troly requires PHP to be version 5.4 or higher to function correctly.' );
        }

        if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' )))) {
            $this->errors[] = __( 'WooCommerce has to be installed for this plugin to function.' );
        }
        
        $stored_dbversion = get_option( 'wp99234_db_version' );

        if( ! $stored_dbversion ){
            add_option( 'wp99234_db_version', WP99234_DBVERSION );
        }

        if( $stored_dbversion !== WP99234_DBVERSION ){
            $this->handle_db_update( $stored_dbversion , WP99234_DBVERSION );
        }
    }

    /**
     * Defines some commonly used things used in this plugin.
     */
    private function define_constants() {

        $this->define( 'WP99234_ABSPATH', trailingslashit( WP_PLUGIN_DIR . '/' . str_replace(basename( __FILE__ ) , "" , plugin_basename( __FILE__ ) ) ) );
        $this->define( 'WP99234_URI', str_replace( array( 'http://', 'https://' ), '//', trailingslashit( WP_PLUGIN_URL . '/' . str_replace(basename( __FILE__ ) , "" , plugin_basename( __FILE__ ) ) ) ) );
        $this->define( 'WP99234_DBVERSION', $this->db_version );
        $this->define( 'WP99234_VERSION', $this->version );

    }

    /**
     * Includes the required files from the lib directory and bootstraps the classes.
     *
     * @since  1.3
     */
    function includes() {
        
        include_once( 'includes/admin/controllers/class-wp99234-admin.php' );

        $this->_admin = new WP99234_Admin();


        //include_once( 'includes/class-wp99234-menus.php' );
        //PHP Compatibility functions.
        include_once( 'includes/common/functions/php_compat.php' );

        include_once( 'includes/common/functions/class-wp99234-functions.php' );

        include_once( 'includes/common/models/class-wp99234-price.php' );

        include_once( 'includes/frontend/controllers/class-wp99234-api.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-clubs.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-forms.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-newsletter-forms.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-prices.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-products.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-registration-forms.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-users.php' );

        include_once( 'includes/frontend/controllers/class-wp99234-wc-filter.php' );

        include_once( 'includes/frontend/controllers/class-wp99234-template.php' );

        $this->_products = new WP99234_Products ();
        $this->_users = new WP99234_Users ();
        $this->_registration = new WP99234_Registration_Forms ();
        $this->_newsletter = new WP99234_Newsletter_Forms ();
        $this->_clubs = new WP99234_Clubs ();
        $this->_prices = new WP99234_Prices ();

        $this->template = new WP99234_Template();

        $this->_woocommerce = new WP99234_WC_Filter ();

        try{
            $this->_api = new WP99234_Api ();
        } catch(  WP99234_Api_Exception $e ) {
            $this->errors[] = $e->getMessage();
            wp99234_log_troly($e->getMessage(), false, 'Plugin Load', 'Plugin includes loading', $e->getMessage());
        }

        //Load the composer autoload file
        include_once( 'vendor/autoload.php' );

        //Cloudinary setup
        Cloudinary::config( array(
            'cloud_name' => 'subscribility-p'
        ));

        //Initlalize the logger class.
        $this->logger = new Katzgrau\KLogger\Logger( __DIR__ . '/logs', 'error' );

        //Set log level to debug.
        $this->logger->setLogLevelThreshold( 'debug' );
    }

    /**
     * Hook into actions and filters.
     *
     * @since  1.3
     */
    function init_hooks() {

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ));

        add_action( 'woocommerce_init', array( $this, 'woocommerce_init' ));
        add_filter( 'woocommerce_payment_gateways', array( $this, 'payment_gateways' ) );
        add_filter( 'woocommerce_shipping_methods', array( $this, 'shipping_methods' ) );

        add_action( 'wp_enqueue_scripts', array ( $this, 'wp_styles' ));
        add_action( 'wp_enqueue_scripts', array ( $this, 'wp_scripts' ));
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );

        //add_action( 'init', array( $this, 'init_late' ), 20 );

    }

    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url() {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Define constant if not already set.
     *
     * @param  string $name
     * @param  string|bool $value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }


    /**
     * plugins_loaded hook.
     */
    function plugins_loaded() {

        //Load the i18n textdomain.
        load_plugin_textdomain( 'wp99234', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n' );

    }

    /**
     * Bootstraps the custom shipping method class.
     */
    function woocommerce_init() {
        include_once( 'includes/frontend/controllers/class-wp99234-wc-shipping-method.php' );
        include_once( 'includes/frontend/controllers/class-wp99234-wc-payment-gateway.php' );
    }

    /**
     * Initialize the custom payment gateway.
     *
     * @param $methods
     *
     * @return array
     */
    function payment_gateways( $methods ) {

        if( class_exists( 'WP99234_WC_Payment_Gateway' ) ){
            $methods[] = 'WP99234_WC_Payment_Gateway';
        }

        return $methods;

    }

    /**
     * Initializes a custom shipping method.
     *
     * @param $methods
     *
     * @return array
     */
    function shipping_methods( $methods ) {

        if( class_exists( 'WP99234_WC_Shipping_Method' ) ){
            $methods['wp99234_shipping_method'] = 'WP99234_WC_Shipping_Method';
        }

        return $methods;

    }

    /**
     * Late Init hook. Add a session cookie for the user.
     */
    function init_late() {

        if( ! is_user_logged_in() && ! is_admin() ){
            if( isset( WC()->session ) ){
                WC()->session->set_customer_session_cookie( true );
            }
        }
    }

    /**
     * Main WP Subs Instance
     *
     * Ensures only one instance of WP Subs is loaded or can be loaded.
     *
     * @return WP99234 - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function wp_styles() {

    }
    /**
     * Enqueue plugin specific JS
     */
    public function wp_scripts () {

        wp_enqueue_script( 'wp99234_frontend_js', WP99234_URI . 'includes/frontend/assets/js/wp99234_frontend.js', $deps = array(),$ver = false, true );

        if( is_page( wc_get_page_id( 'checkout' ) ) ){
            wp_enqueue_script( 'wp99234_websocket_rails', WP99234_URI . 'includes/frontend/assets/js/WebSocketRails.js' );
            wp_enqueue_script( 'wp99234_checkout', WP99234_URI . 'includes/frontend/assets/js/wp99234_checkout.js' );
        }

        wp_register_script( 'jquery-payment', WP99234_URI . 'includes/frontend/assets/js/jquery-payment/jquery.payment.min.js' );

        wp_enqueue_style( 'wp99234_frontend_css', WP99234_URI . 'includes/frontend/assets/css/wp99234_frontend.css' );

    }

    /**
     * Enqueue plugin specific CSS
     */
    public function admin_styles () {

        $screen         = get_current_screen();
        $screen_id      = $screen ? $screen->id : '';
        $jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

        // Register admin styles
        wp_register_style( 'wp99234_admin_styles', WP99234()->plugin_url() . '/includes/admin/assets/css/wp99234-admin.css', array(), WP99234_VERSION );
        //wp_register_style( 'woocommerce_admin_menu_styles', WC()->plugin_url() . '/assets/css/menu.css', array(), WC_VERSION );
        wp_register_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
        //wp_register_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

        // Sitewide menu CSS
        wp_enqueue_style( 'woocommerce_admin_menu_styles' );

        // Admin styles for WC pages only
        if ( in_array( $screen_id, wp99234_get_screen_ids() ) ) {
            wp_enqueue_style( 'woocommerce_admin_styles' );
            //wp_enqueue_style( 'jquery-ui-style' );
        }
        wp_enqueue_style( 'wp99234_admin_styles' );

    }

    public function admin_scripts() {

        $screen       = get_current_screen();
        $screen_id    = $screen ? $screen->id : '';
        $wp99234_screen_id = sanitize_title( __( 'Troly', 'wp99234' ) );
        //$suffix       = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        $suffix = '';

        //wp_register_script( 'wp99234_admin', WP99234()->plugin_url() . '/include/assets/js/admin/wp99234_admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ), WP99234_VERSION );
        wp_register_script( 'wp99234_admin', WP99234()->plugin_url() . '/includes/admin/assets/js/wp99234-admin' . $suffix . '.js', array( 'jquery', 'jquery-ui-core', 'jquery-tiptip' ), WP99234_VERSION );
        wp_register_script( 'jquery-tiptip', WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), WC_VERSION, true );

        wp_enqueue_script( 'wp99234_admin' );
    }

    /**
     * Get a var dump as a string for logging purposes.
     *
     * @param $var
     *
     * @return string
     */
    public function get_var_dump( $var ){

        ob_start();
        var_dump( $var );
        $out = ob_get_contents();
        ob_end_clean();

        return $out;

    }

    /**
     * Handle Database version updates.
     *
     * @param $from
     * @param $to
     */
    public function handle_db_update( $from,  $to ){
        /** (SEB) Is this the best place for this to live? **/
    }

    /**
     * Deliver a message for an SSE listener.
     *
     * @param $id
     * @param $message
     * @param string $event
     * @param int $progress
     */
    function send_sse_message( $id, $message, $event = 'message', $progress = 0 ) {

        ob_end_clean();

        $d = array( 'message' => $message, 'progress' => $progress );

        echo "event: $event" . PHP_EOL;
        echo "id: $id" . PHP_EOL;
        echo "data: " . json_encode( $d ) . PHP_EOL;
        echo PHP_EOL;

        if ( ob_get_level() ){
            //PUSH THE data out by all FORCE POSSIBLE
            //ob_end_clean();
            ob_flush();
            flush();
        }

    }

}

/**
 * Function to call when referencing the main object.
 *
 * @return WP99234
 */
function WP99234() {
    return WP99234::instance();
}

// Global for backwards compatibility.
$GLOBALS['WP99234'] = WP99234();



/**
 * Add store pickup field to the checkout
 **/ 
add_action('woocommerce_before_checkout_billing_form', 'wp99234_store_pickup_checkout_field');
function wp99234_store_pickup_checkout_field( $checkout ) {
    echo '<div id="pickup-field"><h5>'.__('Store Pickup: ').'</h5>';
    woocommerce_form_field( 'store_pickup', array(
        'type'          => 'checkbox',
        'class'         => array('input-checkbox'),
        'label'         => __('I want to pick my order up.'),
        ), $checkout->get_value( 'store_pickup' ));
    echo '</div>';
}
add_action('wp_footer', 'wp99234_add_pickup_script_footer');
function wp99234_add_pickup_script_footer() { ?>
	<script>
	jQuery('#store_pickup').change(function() {
		if(jQuery(this).is(':checked')){
			jQuery.ajax({
				type: 'POST',
				url : '<?php echo plugin_dir_url( __FILE__ );?>includes/common/functions/set_storepickup.php',
				cache: false,
				data : {'action': 'set_store_pickup'},
				success: function(data) {
					location.reload();
				}
			});
		}else{
			jQuery.ajax({
				type: 'POST',
				url : '<?php echo plugin_dir_url( __FILE__ ) ;?>includes/common/functions/set_storepickup.php',
				cache: false,
				data : {'action': 'unset_store_pickup'},
				success: function(data) {
					location.reload();
				}
			});
		}
	});
	</script>
<?php
}

/**
 * Exporting order to Subs after order is processed rather than when processing payment
 * this allows us to export ALL orders to Subs, including ones with a $0 value that normally
 * wouldn't be exported due to the payment processing not running when an orders total value is $0
 **/
add_action( 'woocommerce_checkout_order_processed', 'wp99234_export_order_to_subs', 10, 2);

function wp99234_export_order_to_subs($order_id, $posted_data) {  
  WP99234()->_woocommerce->export_order($order_id);
}


/**
 * Adding action for a cron task to be used when exporting products
 * Required due to different maximum script execution times and some
 * sites will kill the export script before it completes normally
 **/
add_action( 'wp99234_cron_export_products', 'cron_export_products_to_subs' );

function cron_export_products_to_subs($product_ids) {
  
  $reporting_options = get_option('wp99234_reporting_sync');
  $message = 'Started exporting products';
  $exported = 0;
  $failed = 0;
  
  foreach( $product_ids as $id ){
    
    if( WP99234()->_products->export_product( $id ) ){
      $exported++;
    } else {       
      $failed++;
      $message .= '\nCould not export product ( ID: ' . $id . ' )';
    }
  }
  
  $message .= '\nProduct export completed successfully with ' . $exported . ' products exported and ' . $failed . ' which failed to export.';  
  
  if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
      wp99234_log_troly($message, $success = true, 'Export', 'Bulk Products Export', $message);
  }
}

/**
 * Adding action for a cron task to be used when exporting users
 * Required due to different maximum script execution times and some
 * sites will kill the export script before it completes normally
 **/
add_action( 'wp99234_cron_export_users', 'cron_export_users_to_subs' );

function cron_export_users_to_subs($user_ids) {
  
  $reporting_options = get_option('wp99234_reporting_sync');
  $message = 'Started exporting users in cron task';
  $exported = 0;
  $failed = 0;
  $total = count($users);
  
  foreach( $user_ids as $id ){
    
    $results = WP99234()->_users->export_user( $id, null, array(), true );

    if( !$results || is_array( $results ) ){
      $failed++;
      $message .= '\nCustomer with id ' . $id . ' failed to export.';
    } else {
      $exported++;
    }
  }
  
  $message .= '\nUser export completed successfully with ' . $exported . ' users exported and ' . $failed . ' which failed to export.';  
  
  if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
      wp99234_log_troly($message, $success = true, 'Export', 'Bulk Users Export', $message);
  }
}


/**
 * Adding custom columns to products listing admin page
 **/
//add_action('manage_edit-product_columns', 'wp99234_custom_product_listing_columns');
//add_action('manage_product_posts_custom_column', 'wp99234_custom_product_listing_columns_content', 10, 2);
//
//function wp99234_custom_product_listing_columns($columns) {
//	$columns['last_updated_at'] = __( 'Last Updated From Troly (UTC)' );
//	
//	return $columns;
//}
//
//function wp99234_custom_product_listing_columns_content($column, $postid) {
//  
//	switch ($column) {
//  		case 'last_updated_at':
//			echo get_post_meta($postid, 'last_updated_by_subs', true);
//			break;
//  		default:
//			break;
//	}
//}

/**
 * Adding custom columns to users listing admin page
 **/
add_filter('manage_users_columns', 'wp99234_custom_user_listing_columns');
add_filter('manage_users_custom_column', 'wp99234_custom_user_listing_columns_content', 10, 3);

function wp99234_custom_user_listing_columns($columns) {
	$columns['last_updated_at'] = __( 'Last Updated From Troly (UTC+0)' );
	
	return $columns;
}

function wp99234_custom_user_listing_columns_content($val, $column, $userid) {
  
	switch ($column) {
  		case 'last_updated_at':
			return get_user_meta($userid, 'last_updated_by_subs', true);
			break;
  		default:
			break;
	}
}

/**
 * Adding warning notice to admin pages if Troly settings are not configured
 **/
add_action('admin_notices', 'wp99234_settings_blank_warning');

function wp99234_settings_blank_warning() {
  
	$consumer = get_option('wp99234_consumer_key');
	$resource = get_option('wp99234_resource_key');
	$check_no = get_option('wp99234_check_no');
	
	if (empty($consumer) || empty($resource) || empty($check_no)) {
  	
  		$class = 'notice notice-warning';
  		$message = "Troly settings are not properly configured which will cause the plugin to work unexpectedly. <a href='/wp-admin/admin.php?page=wp99234&tab=remote'>Click Here</a> to configure.";
  	
  		printf('<div class="%1$s"><p>%2$s</p></div>', $class, $message);
	}
}

/**
 * Display a legal drinking age disclaimer if selected to display from settings.
 * This will display the disclaimer either as a website overlay on initial load,
 * or as a wc_print_notice on the woocommerce checkout page.
 */
add_action('init', 'wp99234_check_disclaimer_display');

function wp99234_check_disclaimer_display() {
  
	$display_disclaimer = get_option('wp99234_display_legal_drinking_disclaimer');
	
	switch($display_disclaimer) {
		case 'overlay':
			add_action('get_header', 'wp99234_overlay_legal_drinking_age_disclaimer');
			break;
		case 'checkout':
			add_action('woocommerce_before_checkout_form', 'wp99234_checkout_legal_drinking_age_disclaimer');
			break;
		default:
			break;
	}
}

/**
 * Display legal disclaimer as a notice on checkout page.
 */
function wp99234_overlay_legal_drinking_age_disclaimer() {
  
	$disclaimer_message = get_option('wp99234_legal_drinking_disclaimer_text');
  
	if (!empty($disclaimer_message)) {
	
		$is_first_visit = true;
		
		if ( (isset($_COOKIE['_wp99234_age_disclaimer']) && $_COOKIE['_wp99234_age_disclaimer'] == 'accepted') || is_user_logged_in()) {
			$is_first_visit = false;
		}
		
		if ($is_first_visit) {
			$html_output = "<section id='wp99234-disclaimer_overlay' style='position:fixed; top:0; right:0; bottom:0; left:0; z-index:9000; overflow-y:scroll; background:rgba(40,40,40,.75); padding:30px;'>";
			$html_output .= "  <div style='position:relative; margin:30px auto; width:40%; min-height:50%; padding:30px; padding-bottom:60px; background-color:rgba(255,255,255,.95);'>";
			$html_output .= "    <div>$disclaimer_message</div>";
			$html_output .= "    <div style='position:absolute; bottom:10px; left:30px; right:30px;'>";
			$html_output .= "      <div style='text-align:center;'>";
			$html_output .= "        <button class='woocommerce-Button button' onclick='window.location.href = \"http://www.google.com\";'>Exit</button>&nbsp;";
			$html_output .= "        <button class='woocommerce-Button button' onclick='remove_overlay()'>I agree</button>";
			$html_output .= "      </div>";
			$html_output .= "    </div>";
			$html_output .= "  </div>";
			$html_output .= "</section>";
			
			$html_output .= "<script>";
			$html_output .= "  document.body.style.overflow = 'hidden';";
			$html_output .= "  function remove_overlay() {";
			$html_output .= "    document.getElementById('wp99234-disclaimer_overlay').style.display = 'none';";
			$html_output .= "    document.body.style.overflow = 'scroll';";
			$html_output .= "    var expdate = new Date(new Date().getTime() +  (1000*60*60*24*28));";
			$html_output .= "      document.cookie = '_wp99234_age_disclaimer=accepted;expires=' + expdate + ';path=/'"; 
			$html_output .= "  }";
			$html_output .= "</script>";
      
			echo $html_output; 
		}
	}
}

/**
 * Display legal disclaimer as a notice on checkout page.
 */
function wp99234_checkout_legal_drinking_age_disclaimer() {
		
	$disclaimer_message = get_option('wp99234_legal_drinking_disclaimer_text');
	
	if (!empty($disclaimer_message)) {
		wc_print_notice($disclaimer_message, 'notice');
	}
}

/**
 * Add fields to the woocommerce my account 'account details' tab, specifically CC details
 */
add_action('woocommerce_edit_account_form', 'wp99234_add_billing_inputs_to_account_menu', 0);

function wp99234_add_billing_inputs_to_account_menu() {
  
	$use_existing_checkbox = '<input type="checkbox" id="use_existing_card" name="use_existing_card" checked="checked" value="yes">';
	
	$existing_card = get_user_meta(get_current_user_id(), 'cc_number', true);
	
	$cc_name = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">';
	$cc_name .= '<label for="cc_name">Name on card</label>';
	$cc_name .= '<input type="text" maxlength="20" class="woocommerce-Input input-text" name="cc_name" id="cc_name"></p>';
	
	$cc_number = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">';
	$cc_number .= '<label for="cc_name">Card Number</label>';
	$cc_number .= '<input type="tel" inputmode="numeric" class="woocommerce-Input input-text" name="cc_number" id="cc_number" placeholder="•••• •••• •••• ••••"></p>';
	
	$cc_exp = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-first">';
	$cc_exp .= '<label for="cc_name">Expiry (MM/YY)</label>';
	$cc_exp .= '<input type="tel" inputmode="numeric" class="woocommerce-Input input-text" name="cc_exp" id="cc_exp" placeholder="MM / YY"></p>';
	
	$cc_cvv = '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-last">';
	$cc_cvv .= '<label for="cc_name">Card Code</label>';
	$cc_cvv .= '<input type="tel" maxlength="4" inputmode="numeric" class="woocommerce-Input input-text" name="cc_cvv" id="cc_cvv" placeholder="CVC"></p>';
	
	echo '<fieldset class="wc-credit-card-form wc-payment-form"><legend>Billing Details</legend>';
	echo $use_existing_checkbox;
	echo "Use your existing card ($existing_card)";
	echo '<div id="hidden_cc_form">';
	echo '<p>The details entered here will be stored securely for future use.</p>';
	echo $cc_name;
	echo $cc_number;
	echo $cc_exp;
	echo $cc_cvv;
	echo '</div>';
	echo '</fieldset>';
	echo '<div class="clear"></div>';
			
	do_action('wp99234_preferences_form');
}

/**
 * Disable billing fields on checkout form
 **/
if(isset($_SESSION['pickup']) && ($_SESSION['pickup'] == 1)){
	add_action('woocommerce_checkout_init','wp99234_disable_billing_shipping');
	add_filter( 'woocommerce_package_rates', 'wp99234_pickup_shipping_rate', 10 );
}
function wp99234_disable_billing_shipping($checkout){
	echo '<script>jQuery( document ).ready( function($){$("#store_pickup").prop("checked", true);});</script>';
	$checkout->checkout_fields['shipping']=array();
	unset( $checkout->checkout_fields['billing']['billing_country'] );
	unset( $checkout->checkout_fields['billing']['billing_company'] );
	unset( $checkout->checkout_fields['billing']['billing_address_1'] );
	unset( $checkout->checkout_fields['billing']['billing_address_2'] );
	unset( $checkout->checkout_fields['billing']['billing_city'] );
	unset( $checkout->checkout_fields['billing']['billing_state'] );
	unset( $checkout->checkout_fields['billing']['billing_postcode'] );
	return $checkout;
}
/**
 * Shipping charges on pickup
 **/
function wp99234_pickup_shipping_rate( $rates ) {
	foreach ( $rates as $rate ) {
		$cost = $rate->cost;
		$rate->cost = 0;
	}
	return $rates;
}

/**
 * Check woocommerce dependency
 **/
if ( ! class_exists( 'WC_CPInstallCheck' ) ) {
	class WC_CPInstallCheck {
		static function install() {
		/**
		* Check if WooCommerce is active
		**/
			if( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )){
				// Deactivate the plugin
				deactivate_plugins(__FILE__);		
				// Throw an error in the wordpress admin console
				$error_message = __('This plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/" target="_blank">WooCommerce</a> plugin to be active!', 'woocommerce');
				die($error_message);
			}
		}
	}
}
register_activation_hook( __FILE__, array('WC_CPInstallCheck', 'install') );
/**
 * Check woocommerce dependency
 **/

 
/**
* Create troly log
**/
function wp99234_log_troly($message, $success, $type, $what, $details, $end = false) {
  
	$new_log = WP_CONTENT_DIR . '/subs_log.csv';
	
	$csv = fopen($new_log, 'a');
	
	fputcsv($csv, array(date('d/m/Y g:i A'), $type, $what, $details));
	
	fclose($csv);
}

if(isset($_REQUEST['wp99234_reset_log']) && ($_REQUEST['wp99234_reset_log'] == 1)){
	wp99234_reset_troly_log_files();
}

function wp99234_reset_troly_log_files() {
  
	$log_reset = true;
	
	$new_log = WP_CONTENT_DIR . '/subs_log.csv';
	
	if (($csv = fopen($new_log, 'w')) !== FALSE) {
		fputcsv($csv, array(date('d/m/Y g:i A'), 'Reset', 'Log File Reset', 'Successfully reset log file'));
		fclose($csv);
	} else {
		$log_reset = false;
	}
  
	return $log_reset;
}