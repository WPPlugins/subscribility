<?php $product = $GLOBALS['post']; ?>

<div id="product_meta">

    <?php

    $metas = apply_filters( 'wp99234_displayed_meta', array(
        'description' => array(
            'title'   => __( 'Product Description', 'wp99234' ),
            'content' => get_the_content()
        ),
        'tasting' => array(
            'title'   => __( 'Tasting Notes', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'tasting' )
        ),
        'vintage' =>  array(
            'title'   => __( 'Vintage', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'vintage' )
        ),
        'prices' => array(
            'title'   => __( 'Prices', 'wp99234' ),
            'content' => WP99234()->template->price_list()
        ),

        'cellar_until' =>  array(
            'title'   => __( 'Cellar Until', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'cellar_until' )
        ),
        'foods' => array(
            'title'   => __( 'Matching Foods', 'wp99234' ),
            'content' => WP99234()->template->get_var( 'foods' )
        ),
        'awards' => array(
            'title'   => __( 'Awards', 'wp99234' ),
            'content' => WP99234()->template->awards_list()
        ),
//        'winemaking' => array(
//            'title'   => __( 'Winemaking', 'wp99234' ),
//            'content' => WP99234()->template->get_var( 'winemaking' )
//        ),

        'categories' => array(
            'title'   => __( 'Categories', 'wp99234'),
            'content' => get_the_term_list( $product->ID, WP99234()->_products->category_taxonomy_name, '', ' - ', '' )
        ),
        'tags' => array(
            'title'   => __( 'Tags', 'wp99234'),
            'content' => get_the_term_list( $product->ID, WP99234()->_products->tag_taxonomy_name, '', ' - ', '' )
        ),

    ) );

    foreach( $metas as $key => $meta ){

        if( $meta['content'] && ! empty( $meta['content'] ) ):

            ?>

            <div class="wp99234_meta_item <?php esc_attr_e( $key ); ?>">

                <h4 class="wp99234_meta_title"><?php esc_html_e( $meta['title'] ); ?></h4>

                <?php echo $meta['content']; ?>

            </div>

        <?php

        endif;

    }
    ?>

</div>