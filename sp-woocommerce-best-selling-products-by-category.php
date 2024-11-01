<?php
/*
Plugin Name: WP WooCommerce Best Selling Products by Category
Plugin URL: https://www.wponlinesupport.com/plugins/
Description: Display WooCommerce best selling products by category. Also work with Gutenberg shortcode block.
WC tested up to: 3.5.2
Version: 1.2
Author: WP OnlineSupport
Author URI: https://www.wponlinesupport.com
Contributors: WP OnlineSupport
*/

// `bestselling_product_categories` shortcode
add_shortcode( 'bestselling_product_categories', 'sp_bestselling_products' );
function sp_bestselling_products($atts){

	global $woocommerce_loop;

	extract(shortcode_atts(array(
		'cats' 		=> '',	
		'tax' 		=> 'product_cat',	
		'per_cat' 	=> '3',	
		'columns' 	=> '3',	
	), $atts));
 
	if(empty($cats)) {
		$terms = get_terms( 'product_cat', array('hide_empty' => true, 'fields' => 'ids'));
		$cats = implode(',', $terms);
	}

	$cats = explode(',', $cats);

	if(empty($cats)) {
		return '';
	}

	ob_start();

	foreach($cats as $cat) {
 
		// get the product category
		$term = get_term( $cat, $tax);
		$product_visibility_term_ids = wc_get_product_visibility_term_ids();
		// setup query
		$args = array(
			'post_type' 			=> 'product',
			'post_status' 			=> 'publish',
			'ignore_sticky_posts'   => 1,
			'posts_per_page'		=> $per_cat,			
			'meta_key' 		 		=> 'total_sales',
			'orderby' 		 		=> 'meta_value_num',
			'tax_query' => array(				
				array(
					'taxonomy' => $tax,
					'field' => 'id',
					'terms' => $cat,
				)
			),
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => is_search() ? $product_visibility_term_ids['exclude-from-search'] : $product_visibility_term_ids['exclude-from-catalog'],
				'operator' => 'NOT IN',
			),
			'meta_query' => array(				
				// get only products marked as featured
				array(
					'key' 		=> 'total_sales',
					'value' 	=> 0,
					'compare' 	=> '>',
				)
			)
		);

		// set woocommerce columns
		$woocommerce_loop['columns'] = $columns;
 
		// query database
		$products = new WP_Query( $args );
 
		$woocommerce_loop['columns'] = $columns;
 
		if ( $products->have_posts() ) :
 
			woocommerce_product_loop_start();
 
				while ( $products->have_posts() ) : $products->the_post();
 
					if( wbpbc_wc_version() ) {
						wc_get_template_part( 'content', 'product' );
					} else {
						woocommerce_get_template_part( 'content', 'product' );	
					}
 
				endwhile; // end of the loop
 
			woocommerce_product_loop_end();
 
		endif;
 
		wp_reset_postdata();
	}
 
	return '<div class="wbpbc-wrapper woocommerce columns-' . $columns . '">' . ob_get_clean() . '</div>';
}

// Function to check woocommerce version
function wbpbc_wc_version($version = '3.0') {
	global $woocommerce;

	if( version_compare( $woocommerce->version, $version, ">=" ) ) {
		return true;
	}
	return false;
}
