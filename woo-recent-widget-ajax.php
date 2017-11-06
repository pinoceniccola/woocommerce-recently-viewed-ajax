<?php
/*
Plugin Name: WooCommerce Recent Products Widget Via Ajax
Description: Dinamically display the WooCommerce Recently Viewed Products widget via frontend/ajax, useful for caching purposes.
Version: 0.1.0
Author: Pino Ceniccola
Author URI: http://pino.ceniccola.it
Copyright: © 2017 Pino Ceniccola
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



// remove WooCommerce built-in cookie tracker
add_action( 'plugins_loaded', wpe_remove_product_view );

function wpe_remove_product_view(){
    remove_action( 'template_redirect', 'wc_track_product_view', 20 );
};



add_action( 'wp_ajax_wc_recent_products', 'wpe_recent_widget_ajax_action');
add_action( 'wp_ajax_nopriv_wc_recent_products', 'wpe_recent_widget_ajax_action');

function wpe_recent_widget_ajax_action() {

    $viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) : array();
    $viewed_products = array_reverse( array_filter( array_map( 'absint', $viewed_products ) ) );

    if ( !empty( $viewed_products ) ) {

        $number = (int)$_POST['number'];
        if ($number > 15) $number = 15;

        $query_args = array(
            'posts_per_page' => $number,
            'no_found_rows'  => 1,
            'post_status'    => 'publish',
            'post_type'      => 'product',
            'post__in'       => $viewed_products,
            'orderby'        => 'post__in',
        );
        if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'outofstock',
                    'operator' => 'NOT IN',
                ),
            );
        }

        ob_start();

        $r = new WP_Query( $query_args );

        if ( $r->have_posts() ) {

            while ( $r->have_posts() ) {
                $r->the_post();
                wc_get_template( 'content-widget-product.php'/*, $template_args*/ );
            }

        }

        $content = ob_get_clean();
        echo $content;
    }

    die();
};



add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_script( 'recently-viewed', plugins_url('js/recentlyViewed.min.js', __FILE__), array('jquery'), '0.1.0', true );
});


add_action( 'widgets_init', 'wpe_extend_recently_viewed_widget', 15 );

function wpe_extend_recently_viewed_widget() {


	if ( class_exists( 'WC_Widget_Recently_Viewed' ) ) {
		unregister_widget( 'WC_Widget_Recently_Viewed' );


	class WC_Widget_Recently_Viewed_Ajax extends WC_Widget_Recently_Viewed {
    //class WC_Widget_Recently_Viewed_Ajax extends WC_Widget {
    /*public function __construct() {
        $this->widget_cssclass    = 'woocommerce widget_recently_viewed_products_ajax';
        $this->widget_description = __( "Display a list of a customer's recently viewed products.", 'woocommerce' );
        $this->widget_id          = 'woocommerce_recently_viewed_products_ajax';
        $this->widget_name        = __( 'Dinamic Recent Viewed Products (Ajax)', 'woocommerce' );
        $this->settings           = array(
            'title'  => array(
                'type'  => 'text',
                'std'   => __( 'Recently Viewed Products', 'woocommerce' ),
                'label' => __( 'Title', 'woocommerce' ),
            ),
            'number' => array(
                'type'  => 'number',
                'step'  => 1,
                'min'   => 1,
                'max'   => '',
                'std'   => 10,
                'label' => __( 'Number of products to show', 'woocommerce' ),
            ),
        );
        parent::__construct();
    }*/
    public function widget( $args, $instance ) {
        global $product;

        $viewed_products = ! empty( $_COOKIE['woocommerce_recently_viewed'] ) ? (array) explode( '|', $_COOKIE['woocommerce_recently_viewed'] ) : array();
        $viewed_products = array_reverse( array_filter( array_map( 'absint', $viewed_products ) ) );
        //if ( empty( $viewed_products ) ) {
        //    return;
        //}
        ob_start();
        $number = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];


        $this->widget_start( $args, $instance );
        echo wp_kses_post( apply_filters( 'woocommerce_before_widget_product_list', '<ul class="product_list_widget">' ) );

        echo wp_kses_post( apply_filters( 'woocommerce_after_widget_product_list', '</ul>' ) );
        $this->widget_end( $args );

        $content = ob_get_clean();

        $ajax_url = admin_url( 'admin-ajax.php' );
        $current = (is_product()) ? $product->get_id() : 0;

        $content = str_replace('id="woocommerce_recently_viewed','style="display:none !important" data-number="'.$number.'" data-ajaxurl="'.$ajax_url.'" data-current="'.$current.'" id="woocommerce_recently_viewed', $content);
        echo $content;
    }
	}

	register_widget( 'WC_Widget_Recently_Viewed_Ajax' );
	}

}
