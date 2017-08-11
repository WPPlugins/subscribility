<?php
/**
 * Troly WP99234 Sync Operations.
 *
 * @author      WP99234
 * @category    Admin
 * @package     WP99234/Admin
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP99234_Operations_Sync' ) ) :

/**
 * WP99234_Settings_General defines the general configurations
 */
class WP99234_Operations_Sync extends WP99234_Operations_Page {

    /**
     * Constructor.
     */
    public function __construct() {

        $this->id    = 'sync';
        $this->label = __( 'Synchronisations', 'wp99234' );

        add_filter( 'wp99234_operations_tabs_array', array( $this, 'add_operations_page' ), 20 );
    }

}

endif;

return new WP99234_Operations_Sync();
