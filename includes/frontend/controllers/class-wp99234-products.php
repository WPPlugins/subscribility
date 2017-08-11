<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// sell_uom, sort_weight, split_ols, subproducts, te_divider, winemaking
// is_split, total_sales,
    define( 'WP99234_TAG_VISIBLE'     , 105 );
    define( 'WP99234_TAG_OUT_OF_STOCK', 108 );

/**
 * Class WP99234_Products
 */

class WP99234_Products {

    var $products_endpoint;

    var $products_post_type;

    var $category_taxonomy_name;

    var $tag_taxonomy_name;

    function __construct(){

        $this->products_endpoint = WP99234_Api::$endpoint . 'products.json';

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );

        add_action( 'init', array( $this, 'on_init' ) );

        add_action( 'wp_ajax_subs_import_products', array( $this, 'on_ajax_subs_import_products' ) );
        add_action( 'wp_ajax_subs_export_products', array( $this, 'on_ajax_subs_export_products' ) );

        add_action( 'wp_insert_post', array( $this, 'on_insert_post' ), 10, 3 );
    }

    /**
     * Define the tag ID's used in Subs so we can look for them on import, or use them wherever we need to.
     */

    /**
     * Plugins Loaded hook, sets up the post types, and handles various logic around WooCommerce being installed.
     */
    function plugins_loaded(){

        $this->products_post_type     = 'product';
        $this->category_taxonomy_name = 'product_cat';
        $this->tag_taxonomy_name      = 'product_tag';

    }

    function on_init(){}
  
    /**
     * Handle bulk imports
     */
    function handle_bulk_import( $is_sse = false ){

        define( 'WP99234_DOING_BULK_PRODUCT_IMPORT', true );

        $page = 1;

        // @Jp : For testing purpose
        //$limit_per_call = 100;
        $limit_per_call = 50;

        $start_time = time();

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Starting Product Import';
      
        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, __( 'Starting Product Import', 'wp99234' ) );
        }

        $endpoint = esc_url( add_query_arg( array(
            'visible_online' => 'true',
            'archived' => 'false',
            'l' => $limit_per_call,
            'p' => $page
        ), $this->products_endpoint ) );

        // @Jp : For testing purpose
        //$endpoint = 'https://stagingsubs.herokuapp.com/products.json';

        // Determines if we will be able to proceed after talking to the end point...
        $import_allowed = true;
        $response = WP99234()->_api->_call( $endpoint );
        
        if ( $is_sse )
          WP99234()->send_sse_message( $start_time, __( 'Processing response from Troly...', 'wp99234' ));
        
        wp99234_log_troly($response, false, 'Import', 'Bulk Products Import');
        
        

        if( ! $response ){
            if( $is_sse ){
                WP99234()->send_sse_message( $start_time, __( 'Could not import products. Invalid response received. Please try again.', 'wp99234' ), 'fatal' );
            } else {
                WP99234()->_admin->add_notice( __( 'Could not import products. Invalid response received.', 'wp99234' ), 'fatal' );
            }
          
            $message .= '\nCould not import products. Invalid response received.';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Import', 'Bulk Products Import', $message);
                
            }
          $import_allowed = false;
        }

        if( $response->count <= 0 ){

            if( $is_sse ){
                WP99234()->send_sse_message( $start_time, __( 'It appears you have no products that can be shown online.', 'wp99234' ));
                WP99234()->send_sse_message( $start_time, __( 'Ensure the <strong>Visible Online</strong> tag is enabled for your each of your products.', 'wp99234' ));
                WP99234()->send_sse_message( $start_time, __( '', 'wp99234' ));
                WP99234()->send_sse_message( $start_time, __( 'Archived products are automatically removed from this list at time of archival', 'wp99234' ), 'fatal' );
            } else {
                WP99234()->_admin->add_notice( __( 'Could not import products, No products found.', 'wp99234' ), 'fatal' );
            }

            $message .= '\nCould not import products. No products found.';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Import', 'Bulk Products Import', $message);
            }
          $import_allowed = false;
        }
        
        if(!$import_allowed)
          return;

        /**
         * Gather ALL products to be imported into one array, this may be via multiple calls to subs
         */
        $imported = 0;

        $total_to_import = $response->count;
        
        /* Tell the user what is going on... */
        if( $is_sse )
            WP99234()->send_sse_message( $start_time, __( "$total_to_import products - in total - be imported...", 'wp99234' ));

        $results_to_import = $response->results;

        $ready_to_process = count( $results_to_import );
        
            

        // @Jp : Paging does not work in staging ..
        while( $total_to_import > $ready_to_process ){

            $page++;

            $endpoint = esc_url( add_query_arg( array(
                'visible_online' => 'true',
                'archived' => 'false',
                'l' => $limit_per_call,
                'p' => $page
            ), $this->products_endpoint ) );

            $response = WP99234()->_api->_call( $endpoint );

            $results_to_import = array_merge( $results_to_import, $response->results );

            $ready_to_process = count( $results_to_import );

        }

        //Remove duplicates.
        $results_to_import = array_map( 'unserialize', array_unique( array_map( 'serialize', $results_to_import ) ) );

        //$non_prepacked_composites = array();

        $all_products_assoc = array();
		
        /**
         * Import all products in one foreach loop.
         */
        $imported = 0;
        $progress = 0;
        $failed = array();
        foreach( $results_to_import as $product ){
          
            /* Tell the user what is going on... */
            if( $is_sse )
                WP99234()->send_sse_message( $start_time, __( "&gt; <i>$product->name</i>", 'wp99234' ));
              
            $post = $this->import_woocommerce_product( $product );

            $imported++;

            $progress = number_format( ( $imported / count( $total_to_import ) ) * 100, 2 );

            //Set the post to the product object so it can be used in membership price calculations.
            $product->wp_post = $post;

            if( ! $post ){

                WP99234()->logger->error( sprintf( 'An error has occurred. The product "%s" could not be imported.', $product->name ) );

                $failed[] = $product->name;

                if( ! $is_sse ){
                    WP99234()->_admin->add_notice( sprintf( __( 'The product "%s" could not be imported.', 'wp99234' ), $product->name ));
                }
                
                $message .= '\nAn error has occurred. The product ' . $product->name . '  could not be imported.';
              
                continue;

            }

            if( is_wp_error( $post ) ){

                WP99234()->logger->error( sprintf( 'A WordPress error occurred creating "%s". This product could not be imported. (%s)', $product->name, $post->get_error_message() ) );

                $failed[] = $product->name;

                if( ! $is_sse ){
                    WP99234()->_admin->add_notice( sprintf( __( 'A WordPress error occurred creating "%s"." could not be imported. (%s)', 'wp99234' ), $product->name, $post->get_error_message() ), 'error' );
                }

                $message .= '\nA WordPress error occurred creating ' . $product->name . ' could not be imported: ' . $post->get_error_message();
              
                continue;

            }

            if( $is_sse ){
              WP99234()->send_sse_message( $start_time, '', 'message', $progress );
            }
            
            $all_products_assoc[$product->id] = $product;

        }

        /**
         * Handle the calculation of membership prices here, as its more efficient when we already have all the product data in one array.
         */
        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'Successfully updated WooCommerce data store for ' . $imported . ' products', 'message', $progress );
            WP99234()->send_sse_message( $start_time, __( 'Calculating prices....' ), 'message', 100 );
        }
        //Handle the price calculations.
        WP99234()->_prices->calculate_membership_prices_for_products( $all_products_assoc );

        $message .= '\nCalculating product prices...';

        if( $is_sse ){
            WP99234()->send_sse_message( $start_time, __( sprintf( '%s products had prices successfully updated', (string)$imported ), 'wp99234' ) );
        } else {
            WP99234()->_admin->add_notice( __( sprintf( '%s products were successfully imported.', (string)$imported ), 'wp99234' ), 'success' );
        }

        $message .= '\nSuccessfully imported and updated pricing of ' . $imported . ' products';
      
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Import', 'Bulk Products Import', $message);
        }
      
        update_option( 'wp99234_product_import_has_run', true );

        if ( $is_sse ){
            WP99234()->send_sse_message( $start_time, 'TERMINATE', 'message', 100 );
        } else {
            wp_redirect( admin_url( 'edit.php?post_type=' . WP99234()->_products->products_post_type ) );
        }

        exit;

    }

    function register_post_types(){

        /**
         * Product Category
         */
        $labels = array(
            'name'                       => _x( 'Categories', 'Taxonomy General Name', 'wp99234' ),
            'singular_name'              => _x( 'Category', 'Taxonomy Singular Name', 'wp99234' ),
            'menu_name'                  => __( 'Categories', 'wp99234' ),
            'all_items'                  => __( 'All Categories', 'wp99234' ),
            'parent_item'                => __( 'Parent Category', 'wp99234' ),
            'parent_item_colon'          => __( 'Parent Category:', 'wp99234' ),
            'new_item_name'              => __( 'New Category Name', 'wp99234' ),
            'add_new_item'               => __( 'Add New Category', 'wp99234' ),
            'edit_item'                  => __( 'Edit Category', 'wp99234' ),
            'update_item'                => __( 'Update Category', 'wp99234' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'wp99234' ),
            'search_items'               => __( 'Search categories', 'wp99234' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'wp99234' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'wp99234' ),
            'not_found'                  => __( 'No categories found', 'wp99234' ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array( 'slug' => 'subs_category' )
        );
        register_taxonomy( $this->category_taxonomy_name, array( 'wp99234_product' ), $args );

        /**
         * Product Tags
         */
        $labels = array(
            'name'                       => _x( 'Tags', 'Taxonomy General Name', 'wp99234' ),
            'singular_name'              => _x( 'Tag', 'Taxonomy Singular Name', 'wp99234' ),
            'menu_name'                  => __( 'Tags', 'wp99234' ),
            'all_items'                  => __( 'All Tags', 'wp99234' ),
            'parent_item'                => __( 'Parent Tag', 'wp99234' ),
            'parent_item_colon'          => __( 'Parent Tag:', 'wp99234' ),
            'new_item_name'              => __( 'New Tag Name', 'wp99234' ),
            'add_new_item'               => __( 'Add New Tag', 'wp99234' ),
            'edit_item'                  => __( 'Edit Tag', 'wp99234' ),
            'update_item'                => __( 'Update Tag', 'wp99234' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'wp99234' ),
            'search_items'               => __( 'Search tags', 'wp99234' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'wp99234' ),
            'choose_from_most_used'      => __( 'Choose from the most used tags', 'wp99234' ),
            'not_found'                  => __( 'No tags found', 'wp99234' ),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'rewrite'                    => array( 'slug' => 'subs_tag' )
        );
        register_taxonomy( $this->tag_taxonomy_name, array( 'wp99234_product' ), $args );

        /**
         * Product Post Type
         */
        $labels = array(
            'name'               => _x( 'Products', 'Post Type General Name', 'wp99234' ),
            'singular_name'      => _x( 'Product', 'Post Type Singular Name', 'wp99234' ),
            'menu_name'          => __( 'Products', 'wp99234' ),
            'parent_item_colon'  => __( 'Parent Product:', 'wp99234' ),
            'all_items'          => __( 'All Products', 'wp99234' ),
            'view_item'          => __( 'View Product', 'wp99234' ),
            'add_new_item'       => __( 'Add New Product', 'wp99234' ),
            'add_new'            => __( 'Add New', 'wp99234' ),
            'edit_item'          => __( 'Edit Product', 'wp99234' ),
            'update_item'        => __( 'Update Product', 'wp99234' ),
            'search_items'       => __( 'Search Products', 'wp99234' ),
            'not_found'          => __( 'No products found', 'wp99234' ),
            'not_found_in_trash' => __( 'No products found in Trash', 'wp99234' ),
        );
        $rewrite = array(
            'slug'                => 'products',
            'with_front'          => true,
            'pages'               => true,
            'feeds'               => true,
        );
        $args = array(
            'label'               => __( 'wp99234_product', 'wp99234' ),
            'description'         => __( 'Products imported from Troly', 'wp99234' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'hierarchical'        => true,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => $rewrite,
            'capability_type'     => 'page',
            'taxonomies'          => array( $this->category_taxonomy_name, $this->tag_taxonomy_name )
        );
        register_post_type( $this->products_post_type, $args );

    }

    function product_data_map(){

        return array(
            'post_title'   => 'name',
            'post_content' => 'description',
        );

    }

	 // Array format: (WP attribute name => SUBS attribute name)
    function product_meta_map(){

        return array(
            'subs_id'      => 'id',
            '_sku'         => 'product_number',
            'barcode_url'  => 'barcode_url',
            'foods'        => 'foods',
            'cellar_until' => 'cellar_until',
            //'price'        => 'price',
            'price_case'   => 'price_case',
            'price_6pk'    => 'price_6pk',
            'hero_img'     => 'hero_img',
            'tagline'      => 'tagline',
            'award_1'      => 'award_1',
            'award_2'      => 'award_2',
            'award_3'      => 'award_3',
            'award_4'      => 'award_4',
            'vintage'      => 'vintage',
            'tasting'      => 'tasting',
            'avg_rating'   => 'avg_rating',
            'rating_count' => 'rating_count',
            'qr_code'      => 'qr_code',
            '_winemaking'  => 'winemaking',
            '_sort_weight' => 'sort_weight',
            'weight'       => 'weight',
            '_sell_uom'    => 'sell_uom',
            '_te_divider'  => 'te_divider',
            '_split_ols'   => 'split_ols',
            //'product_prices'  => 'product_prices',
            //'_subproducts' => 'subproducts',
            '_variety'      => 'variety'
        );

    }

    /**
     * Validate that the data is valid for use in inserting products.
     *
     * @param $data
     *
     * @return bool
     */
    function validate_product_data( $data ){

        if( ! $data->id || ! $data->name ){
            WP99234()->logger->error( 'No ID or Name Provided' );
            return false;
        }

        /**
         * Woocommerce products need a price.
         */


        if( ! $data->price || ! $data->tags ){
            WP99234()->logger->error( 'No Price or Tags Provided' );
            return false;
        }

        return true;

    }

    /**
     * Import a Woocommerce product.
     */
    function import_woocommerce_product( $product ){

        //Start with a product import.
        $wp_product = $this->import_product( $product );

        $product->wp_post = $wp_product;

        $product_id = $wp_product->ID;

        if( is_wp_error( $wp_product ) ){
            return $wp_product;
        }

        $product->is_split = false;

        /* We are not being passed the subproducts during the import process
		  if( $product->split_ols == true && is_array( $product->subproducts ) && ! empty( $product->subproducts ) ){
            $product->is_split = true;
        }

        if( (float)$product->price == 0 && is_array( $product->subproducts ) && ! empty( $product->subproducts ) ){
            $product->is_split = true;
        }*/

        //Get the product price to use if there is no membership price, or if the user is not logged in.
        if( $product->is_split ){
            $price = WP99234()->_prices->get_split_composite_price( $product );
        } else {
            $price = $product->price;
        }

        /**
         * Membership prices are handled in 1 bulk operation during a bulk import, no need to run up the bill here.
         */
        if( ! defined( 'WP99234_DOING_BULK_PRODUCT_IMPORT' ) || ! WP99234_DOING_BULK_PRODUCT_IMPORT ){
            WP99234()->_prices->calculate_membership_prices_for_single_product( $product );
        }

        /**
         * Handle the calculation of bulk discount prices.
         */
        if( $product->is_split && ( ! defined( 'WP99234_DOING_BULK_PRODUCT_IMPORT' ) || ! WP99234_DOING_BULK_PRODUCT_IMPORT ) ){

            $bulk_prices = WP99234()->_prices->calculate_bulk_discount_prices_for_product( $product );

            if( $bulk_prices['6pack'] > 0 ){
                update_post_meta( $product_id, 'price_6pk', $bulk_prices['6pack'] );
            }

            if( $bulk_prices['case'] > 0 ){
                update_post_meta( $product_id, 'price_case', $bulk_prices['case'] );
            }

        }

        update_post_meta( $product_id, '_price'         , $price             );
        update_post_meta( $product_id, '_regular_price' , $price             );
        update_post_meta( $product_id, '_is_split'      , $product->is_split );

        /**
         * Tax Status
         *
         * Tag: Inclusive
         * Tag: Exclusive
         * Tag: Exempt
         * @TODO - Handle Tax Status.
         */

        return get_post( $product_id );

    }

    /**
     * Import the given data to a product.
     *
     * @param $product
     *
     * @return int|WP_Error
     */
    function import_product( $product ){

        if( ! defined( 'WP99234_DOING_PRODUCT_IMPORT' ) ){
            define( 'WP99234_DOING_PRODUCT_IMPORT', true );
        };

        /**
         * Create the product post
         */
        $post_data = array(
            'post_type'   => $this->products_post_type,
            'post_status' => 'publish'
        );

        //Handle a product being updated.
        if( $current_product = $this->get_by_subs_id( $product->id ) ){

	        // set the post id to this one
            $post_data['ID'] = $current_product->ID;
            // Make sure we do NOT override the menu_order of products on import
            $post_data['menu_order'] = $current_product->menu_order;
	        // remove any existing relations to terms
	        $taxonomies = array($this->category_taxonomy_name, $this->tag_taxonomy_name);
	        wp_delete_object_term_relationships( $current_product->ID, $taxonomies);

        }

        foreach( $this->product_data_map() as $key => $val ){

            //Handle the HTML tags coming in from subs.
            if( $key == 'post_content' ){
                $_val = wp_kses_post( $product->{$val} );
            } else {
                $_val = wp_kses( $product->{$val}, array() );
            }

            $post_data[$key] = $_val;

        }

        //Comment Status Open
        $post_data['comment_status'] = 'open';

        $product_id = wp_insert_post( $post_data, true );

        if( is_wp_error( $product_id ) ){
            return $product_id;
        }

        /**
         * Make the product_prices an associative array with the membership_type_id as the key to enable easy searching later.
         */
        if( isset( $product->product_prices ) && is_array( $product->product_prices ) && ! empty( $product->product_prices ) ){
            $product_prices_raw = $product->product_prices;

            $_prices = array();

            foreach( $product_prices_raw as $product_price_raw ){
                $_prices[$product_price_raw->membership_type_id] = $product_price_raw;
            }

            $product->product_prices = $_prices;

        }

        /**
         * make the tags an associative array so that searching through the tags later becomes easier.
         * We can look for a specific tag ID without cycling through all of them.
         */
        if( isset( $product->tags ) && is_array( $product->tags ) && ! empty( $product->tags ) ){

            $product_tags_raw = $product->tags;

            $_tags = array();

            foreach( $product_tags_raw as $raw_tag ){
                $_tags[$raw_tag->id] = $raw_tag;
            }

            $product->tags = $_tags;

        }

        /**
         * Handle Meta Data
         * copy the data from the subs product, or set it to an empty string  if it's not set in subs
         */
        foreach( $this->product_meta_map() as $key => $val ){
            if( isset( $product->{$val} ) && ! is_null( $product->{$val} ) ){
                update_post_meta( $product_id, $key, $product->{$val} );
            }else{
	            update_post_meta( $product_id, $key, '' );
            }
        }
		
        // Special case for meta data - we want to store the last time this product was updated BY Subs
        update_post_meta($product_id, 'last_updated_by_subs', date('d/m/Y g:i A'));

        /**
         * Handle Product categories
         */
        $categories = array_map( 'trim', explode( ',', $product->category ) );

        if( is_array( $categories ) ){

            $cat_ids = array();

            foreach( $categories as $category ){

                $term = get_term_by( 'name', $category, $this->category_taxonomy_name );

                if( $term ){

                    $cat_ids[] = $term->term_id;

                } else {

                    //Term doesn't exist, we need to add it in.
                    $term = wp_insert_term( str_replace( ',', '&#44;', $category ), $this->category_taxonomy_name );

                    if( ! is_wp_error( $term ) ){
                        $cat_ids[] = $term['term_id'];
                    }

                }

            }

            wp_set_object_terms( $product_id, $cat_ids, $this->category_taxonomy_name );

        }

        /**
         * Handle Product Tags
         */
        $possible_tag_cats = array(
            'custom_1',
            'custom_2',
            'custom_3',
            'custom_4',
            'custom_5',
            'product-config',
            'grape-variety',
            'award-type',
            'wine-region',
            'wine-type',
            'container',
            'closure'
        );

        //ensure we have all possible tag parents.
        $tag_parents = array();

        foreach( $possible_tag_cats as $tag_toplevel ){

            $term = get_term_by( 'slug', $tag_toplevel, $this->tag_taxonomy_name );

            if( $term ){
                $tag_parents[$tag_toplevel] = $term->term_id;
            } else {
                $term = wp_insert_term( $tag_toplevel, $this->tag_taxonomy_name );

                if( ! is_wp_error( $term ) ){
                    $tag_parents[$tag_toplevel] = $term['term_id'];
                } else {
                    WP99234()->logger->error( 'Unable to insert term. ' . $term->get_error_message() );
                }
            }

        }

        //Gather and insert the tags as children of the correct parent.
        if( is_array( $product->tags ) ){

            $tag_ids = array();

            foreach( $product->tags as $tag ){

                $term = get_term_by( 'name', $tag->name, $this->tag_taxonomy_name );

                if( $term ){

                    $tag_ids[] = $term->term_id;

                } else {

                    $term = wp_insert_term( $tag->name, $this->tag_taxonomy_name, array(
                        'parent' => $tag_parents[$tag->category]
                    ) );

                    if( ! is_wp_error( $term ) ){
                        $tag_ids[] = $term['term_id'];
                    }

                }

            }

            /**
             * Handle Stock Status
             */
            if( isset( $product->tags[WP99234_TAG_OUT_OF_STOCK] ) ){
                update_post_meta( $product_id, '_stock_status', 'outofstock' );
            } else {
                update_post_meta( $product_id, '_stock_status', 'instock' );
            }

            /**
             * Handle Product Visibility
             */
            if( isset( $product->tags[WP99234_TAG_VISIBLE] ) ){
                update_post_meta( $product_id, '_visibility', 'visible' );
            } else {
                update_post_meta( $product_id, '_visibility', 'hidden' );
            }

            wp_set_object_terms( $product_id, $tag_ids, $this->tag_taxonomy_name );

        }

        /**
         * Strip any deprecated meta fields here.
         */
        $meta_fields_to_remove = array(
            'sell_uom',
            'sort_weight',
            'split_ols',
            'subproducts',
            'te_divider',
            'winemaking',
            'is_split'
        );


        $meta_fields_to_remove[] = 'price';


        foreach( $meta_fields_to_remove as $key ){
            delete_post_meta( $product_id, $key );
        }

        return get_post( $product_id );

    }

    /**
     * Import a single product via the WP product ID
     *
     * @param $product_id
     */
    function import_by_product_id( $product_id ){

        $subs_id = get_post_meta( $product_id, 'subs_id', true );

        if( $subs_id ){
            $endpoint = $this->get_single_product_endpoint( $subs_id );

            $results = WP99234()->_api->_call( $endpoint );

            $post = false;

            if( isset( $results->name ) ){
                $post = $this->import_woocommerce_product( $results );
                //    $post = $this->import_product( $results );
            }

            if( $post ){
                WP99234()->_admin->add_notice( __( 'Product was successfully imported from troly.', 'wp99234' ), 'success' );
            } else {
                WP99234()->_admin->add_notice( __( 'Product was unable to import from troly.', 'wp99234' ), 'fatal' );
            }

        }

    }

    /**
     * Retrieve a single product based on the given troly ID.
     *
     * @param $subs_id
     *
     * @return bool
     */
    function get_by_subs_id( $subs_id ){

        $args = array(
            'post_type'      => $this->products_post_type,
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key'     => 'subs_id',
                    'value'   => (int)$subs_id
                )
            )
        );
        $query = new WP_Query( $args );

        if( ! $query->have_posts() ){
            return false;
        }

        return $query->posts[0];

    }

    /**
     * Get an array of all products.
     *
     * @return array|bool
     */
    function get_all_products(){

        $args = array(
            'post_type'      => $this->products_post_type,
            'posts_per_page' => -1,
        );
        $query = new WP_Query( $args );

        if( ! $query->have_posts() ){
            return false;
        }

        return $query->posts;

    }

    /**
     * Get the endpoint to import a single product based on the given subs ID.
     *
     * @param $subs_id
     *
     * @return bool|string
     */
    function get_single_product_endpoint( $subs_id ){

        if( $subs_id ){
            return sprintf( '%sproducts/%s', WP99234_Api::$endpoint, $subs_id );
        }

        return false;

    }

    /**
     * Handle an AJAX call to import the users via SUBS api.
     */
    function on_ajax_subs_import_products(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        if( ! wp_verify_nonce( $_REQUEST['nonce'], 'subs_import_products' ) ){
            WP99234()->send_sse_message( 0, __( 'Invalid Request', 'wp99234' ) );
            exit;
        }

        $this->handle_bulk_import( true );

        exit;

    }

    /**
     * Handle bulk export of products to subs.
     */
    function on_ajax_subs_export_products(){

        header( 'Content-Type: text/event-stream' );
        header( 'Cache-Control: no-cache' ); // recommended to prevent caching of event data.

        if( ! wp_verify_nonce( $_REQUEST['nonce'], 'subs_export_products' ) ){
            WP99234()->send_sse_message( 0, __( 'Invalid Request', 'wp99234' ) );
            exit;
        }

        $args = array(
            'post_type'      => WP99234()->_products->products_post_type,
            'post_status'    => 'publish',
            'posts_per_page' => - 1
        );
        $query = new WP_Query( $args );

        $reporting_options = get_option('wp99234_reporting_sync');
        $message = 'Started exporting products';
      
        if( ! $query->have_posts() ){
            WP99234()->send_sse_message( 0, __( 'No products to export!', 'wp99234' ) );
          
            $message .= '\nNo products found to export, export aborted';
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Export', 'Bulk Products Export', $message);
            }
          
            exit;
        }

        $start_time = time();

        $total_to_export = count( $query->posts );
        $next_set = 0;
        $cron_increment_run_time = time() + 60;
        $product_ids = array();
      
        foreach ( $query->posts as $products ) {
          $product_ids[] = $products->ID;
        }

        WP99234()->send_sse_message( $start_time, 'Queuing export of ' . $total_to_export . ' products.', 'message', 100 );
        $message .= '\nQueuing export of ' . $total_to_export . ' products.';      
      
        while ($next_set < $total_to_export) {
          
          $slice = array_slice($product_ids, $next_set, 5);
          
          // should always exist but just an extra check
          if (isset($slice)) {
            // schedule a one time cron task to run and export the above products slice
            wp_schedule_single_event( $cron_increment_run_time, 'wp99234_cron_export_products', array($slice) );
          }
          
          $cron_increment_run_time += 600;
          $next_set += 5;
          
        };

        WP99234()->send_sse_message( $start_time, 'Product export has been scheduled to run and will complete over the day.', 'message', 100 );

        WP99234()->send_sse_message( $start_time, 'TERMINATE', 'message', 100 );
        
        $message .= '\nProduct export has been successfully scheduled to run and export ' . $total_to_export . ' products.';
      
        if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
            wp99234_log_troly($message, $success = true, 'Export', 'Bulk Products Export', $message);
        }

        update_option( 'wp99234_product_export_has_run', true );

        exit;

    }

    /**
     * Hooked to wp_insert_post
     */
    function on_insert_post( $post_ID, $post, $update ){

        //Not to run on the auto-save.
        if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
            return;
        }

        //Only operating on products.
        if( $post->post_type !== WP99234()->_products->products_post_type ){
            return;
        }

        //No need to export on import, this could cause recursion over the network...
        if( defined( 'WP99234_DOING_PRODUCT_IMPORT' ) && WP99234_DOING_PRODUCT_IMPORT ){
            return;
        }

        if( $update ){
            $this->export_product( $post_ID );
        } else {
            WP99234()->_admin->add_notice( __( 'New products can not be exported to Troly. You should create new products there. They will be imported automatically to WordPress.', 'wp99234' ), 'fatal' );
            wp_delete_post( $post_ID );
            wp_redirect( admin_url() );
        }

    }

    /**
     * Export the given product ID back to subs.
     *
     * @param $product_id
     *
     * @return bool
     */
    function export_product( $product_id ){

        $product = get_post( $product_id ) ;

        $product_meta = get_post_meta( $product_id );
      
		  // Data array format: (SUBS attribute name => WP attribute value)

        $data = array(
            'name'           => $product->post_title,
            'description'    => $product->post_content,
            'id'             => $product_meta[ 'subs_id' ][ 0 ],
            'product_number' => $product_meta[ '_sku' ][ 0 ],
            'barcode_url'    => $product_meta[ 'barcode_url' ][ 0 ],
            'foods'          => $product_meta[ 'foods' ][ 0 ],
            'cellar_until'   => $product_meta[ 'cellar_until' ][ 0 ],
            'price'          => $product_meta[ '_price' ][ 0 ],
            'price_case'     => $product_meta[ 'price_case' ][ 0 ],
            'price_6pk'      => $product_meta[ 'price_6pk' ][ 0 ],
            'hero_img'       => $product_meta[ 'hero_img' ][ 0 ],
            'tagline'        => $product_meta[ 'tagline' ][ 0 ],
            'award_1'        => $product_meta[ 'award_1' ][ 0 ],
            'award_2'        => $product_meta[ 'award_2' ][ 0 ],
            'award_3'        => $product_meta[ 'award_3' ][ 0 ],
            'award_4'        => $product_meta[ 'award_4' ][ 0 ],
            'vintage'        => $product_meta[ 'vintage' ][ 0 ],
            'tasting'        => $product_meta[ 'tasting' ][ 0 ],
            'avg_rating'     => $product_meta[ 'avg_rating' ][ 0 ],
            'rating_count'   => $product_meta[ 'rating_count' ][ 0 ],
            'qr_code'        => $product_meta[ 'qr_code' ][ 0 ],
            'winemaking'     => $product_meta[ '_winemaking' ][ 0 ],
            'sort_weight'    => $product_meta[ '_sort_weight' ][ 0 ],
            'weight'         => $product_meta[ 'weight' ][ 0 ],
            'sell_uom'       => $product_meta[ '_sell_uom' ][ 0 ],
            'split_ols'      => $product_meta[ '_split_ols' ][ 0 ],
            'te_divider'     => $product_meta[ '_te_divider' ][ 0 ],
            'subproducts'    => $product_meta[ '_subproducts' ][ 0 ],
            'variety'        => $product_meta[ '_variety' ][ 0 ],
        );


        /**
         * Add Categories.
         */
        $categories = wp_get_post_terms( $product_id, WP99234()->_products->category_taxonomy_name );

        if( $categories && ! empty( $categories ) ){

            $_cats = array();

            foreach( $categories as $category ){
                $_cats[] = $category->name;
            }

            $cats_string = implode( ', ', $_cats );

            $data['category'] = $cats_string;

        }

        $reporting_options = get_option('wp99234_reporting_sync');
        $subs_id = get_post_meta( $product_id, 'subs_id', true );

        if( $subs_id ){
            $endpoint = sprintf( '%sproducts/%s.json', WP99234_Api::$endpoint, $subs_id );
            $method = 'PUT';
            $message = 'Updating product (id: ' . $product_id . ', subs_id: ' . $subs_id . ') on Troly';
        } else {
            $endpoint = sprintf( '%sproducts.json', WP99234_Api::$endpoint );
            $method = 'POST';
            $message = 'Exporting product (id: ' . $product_id . ') to Troly';
        }

        $results = WP99234()->_api->_call( $endpoint, $data, $method );

        if( ! $results ){
            WP99234()->_admin->add_notice( __( 'Could not push the product to Troly. Please try saving again.', 'wp99234' ), 'fatal' );
            WP99234()->logger->error( 'Invalid result from API. Called ' . $endpoint );
          
            if( $subs_id ){
                $message .= '\nUpdating product failed because an invalid response was received';
            } else {
                $message .= '\nExporting product failed because an invalid response was received';
            }
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Export', 'Product Export to Subs', $message);
            }
          
            return false;
        }

        if( isset( $results->id ) ){
            update_post_meta( $product_id, 'subs_id', $results->id );
        }

        $errors = (array)$results->errors;

        if( ! empty( $errors ) ){
            foreach( $errors as $error ){
                WP99234()->_admin->add_notice( $error, 'error' );
                WP99234()->logger->error( $error );
            }
          
            if( $subs_id ){
                $message .= '\nUpdating product failed because of: ' . $error;
            } else {
                $message .= '\nExporting product failed because of: ' . $error;
            }
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = false, 'Export', 'Product Export to Subs', $message);
            }
          
            return false;
        } else {

            WP99234()->_admin->add_notice( __( 'Product updates were successfully pushed to Troly.', 'wp99234' ), 'success' );
          
            if( $subs_id ){
                $message .= '\nSuccessfully updated product on Troly';
            } else {
                $message .= '\nSuccessfully exported product to Troly';
            }
          
            if ($reporting_options == 'verbose' || $reporting_options == 'medium') {
                wp99234_log_troly($message, $success = true, 'Export', 'Product Export to Subs', $message);
            }
          
            return true;

        }

    }

}