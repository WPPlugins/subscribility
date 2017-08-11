<?php
/**
 * Single product template for Wp Subs.
*
* (SEB): can be deleted? > Originally, the plugin supported having no woocommerce installed, 
* where products would be pushed into a wordpress page content. This is no longer required and we are now
* enforcing the need for WooCommerce to installed.
*
 */

$product = $GLOBALS['post'];

//DO NOT REMOVE THIS LINE it prevents a recursion as this file is loaded as a replacement of the_content().
remove_filter( 'the_content', array( $this, 'the_content_filter' ) );
?>

<?php
$tagline = WP99234()->template->get_var( 'tagline' );
if( ! empty( $tagline ) ): ?>
    <div class="tagline" style="padding:10px; border-radius:5px; background-color: #FFF; box-shadow: 0 0 2px #CCC; text-align: center; margin-bottom:20px; color:#858585; font-size:0.8em;">
        <?php echo $tagline; ?>
    </div>
<?php endif; ?>

<div class="content">
    <?php the_content(); ?>
</div>

<?php include WP99234_ABSPATH . 'includes/frontend/views/product_meta.php';