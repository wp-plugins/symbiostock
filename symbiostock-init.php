<?php

$currSS->disable_direct_access();

// SYMBIOSTOCK INITIALIZATION

add_action('ss_end_core_includes','ss_init_directories');
function ss_init_directories() {
	do_action('ss_start_init_directories');

	// Initialize media and media upload dir
	if (!file_exists($GLOBALS['currSS']->ss_media_dir)) wp_mkdir_p($GLOBALS['currSS']->ss_media_dir);
	if (!file_exists($GLOBALS['currSS']->ss_media_dir.'.htaccess')) {
		$fp = fopen($GLOBALS['currSS']->ss_media_dir . '.htaccess', 'w');
		fwrite($fp, 'deny from all
AllowOverride None');
		fclose($fp);
	}
	if (!file_exists($GLOBALS['currSS']->ss_media_upload_dir)) wp_mkdir_p($GLOBALS['currSS']->ss_media_upload_dir);
	if (file_exists($GLOBALS['currSS']->ss_media_upload_dir.'.htaccess')) {
		unlink($GLOBALS['currSS']->ss_media_upload_dir.'.htaccess');
	}
	if (!file_exists($GLOBALS['currSS']->ss_tmp_dir)) wp_mkdir_p($GLOBALS['currSS']->ss_tmp_dir);
	if (!file_exists($GLOBALS['currSS']->ss_tmp_dir.'.htaccess')) {
		$fp = fopen($GLOBALS['currSS']->ss_tmp_dir . '.htaccess', 'w');
		fwrite($fp, 'deny from all
AllowOverride None');
		fclose($fp);
	}

	// Ensure watermark exists. If not, reset.
	if (!file_exists($GLOBALS['currSS']->ss_watermark_loc) && file_exists($GLOBALS['currSS']->ss_watermark_loc_old)) copy($GLOBALS['currSS']->ss_watermark_loc_old, $GLOBALS['currSS']->ss_watermark_loc);
	elseif (!file_exists($GLOBALS['currSS']->ss_watermark_loc)) copy($GLOBALS['currSS']->ss_default_watermark_loc, $GLOBALS['currSS']->ss_watermark_loc);

	chmod ($GLOBALS['currSS']->ss_exiftool,0755);

	do_action('ss_end_init_directories');
}

// Check if thumbnails need regeneration due to watermark/size changes
add_action('init', 'ss_check_thumbnailregen');
function ss_check_thumbnailregen () {
	$ss_tofix = 0;

	// Check if thumbnails need regeneration
	if (!get_option('ss_regen_thumbnails')) {
		// first check product image settings
		$imgsizes[] = 'shop_catalog_image_size';
		$imgsizes[] = 'shop_single_image_size';
		$imgsizes[] = 'shop_thumbnail_image_size';

		foreach ($imgsizes as $imgsize) {
			if (!$ss_tofix) {
				$curr = get_option($imgsize);
				if (!$last = get_option($imgsize.'_last')) {	
					update_option($imgsize.'_last', $curr);
				} else {
					if (($last['width'] != $curr['width']) || ($last['height'] != $curr['height']) || ($last['crop'] != $curr['crop'])) {
						update_option('ss_regen_thumbnails', time());
						update_option($imgsize.'_last', $curr);
						$ss_tofix = 1;
					}
				}
			} else {
				$curr = get_option($imgsize);
				update_option($imgsize.'_last', $curr);
			}
		}
	}

	$ss_tofix = 0;

	// Check if watermark needs update
	if (!get_option('ss_regen_thumbnails')) {
		// Check if watermark has changed
		if (!$ss_tofix) {
			if (!$last = get_option('ss_watermark_lastchanged')) {	
				update_option('ss_watermark_lastchanged', filemtime($GLOBALS['currSS']->ss_watermark_loc));
			} else {
				if ($last != filemtime($GLOBALS['currSS']->ss_watermark_loc)) {
					update_option('ss_regen_thumbnails', time());
					update_option('ss_watermark_lastchanged', filemtime($GLOBALS['currSS']->ss_watermark_loc));
					$ss_tofix = 1;
				}
			}
		} else {
			update_option('ss_watermark_lastchanged', filemtime($GLOBALS['currSS']->ss_watermark_loc));
		}

		// Check if watermark ratio has changed
		if (!$ss_tofix) {
			if (!$last = get_option('ss_watermarkpercent_last')) {	
				update_option('ss_watermarkpercent_last', get_option('ss_watermarkpercent'));
			} else {
				if ($last != get_option('ss_watermarkpercent')) {
					update_option('ss_regen_thumbnails', time());
					update_option('ss_watermarkpercent_last', get_option('ss_watermarkpercent'));
				}
			}
		} else {
			update_option('ss_watermarkpercent_last', get_option('ss_watermarkpercent'));
		}
	}

    $the_page = get_page_by_path( 'licensing');
    if ( !$the_page ) {
        // Create post object
        $_p = array();
        $_p['post_title'] = 'Licensing';
        $_p['post_name'] = 'licensing';
        $_p['post_content'] = "For all licensing queries, please contact us.";
        $_p['post_status'] = 'publish';
        $_p['post_type'] = 'page';
        $_p['comment_status'] = 'closed';
        $_p['ping_status'] = 'closed';
        $_p['post_category'] = array(1);

        // Insert the post into the database
        $the_page_id = wp_insert_post( $_p );
    }
}

// Initial startup only - create licenses and set initial settings

add_action('init', 'ss_init_base_settings');
add_action('init', 'ss_init_license_attribute');
add_action('init', 'ss_init_license_variables');

function ss_init_base_settings() {
		update_option( 'woocommerce_email_footer_text', get_option('woocommerce_email_from_name').' - Powered by Symbiostock/WooCommerce');

		if (get_option('ss_init_base_settings_done')) return;
		update_option('ss_init_base_settings_done', 1);

		update_option( 'ss_watermarkpercent', 65 );
		update_option( 'ss_download_expiry', 7 );
		update_option( 'ss_download_limit', 3 );

		$curr = array( 'width' => 300, 'height' => 300, 'crop' => 0);
		update_option( 'shop_catalog_image_size', $curr );

		$curr = array( 'width' => 623, 'height' => 600, 'crop' => 0);
		update_option( 'shop_single_image_size', $curr );

		$curr = array( 'width' => 200, 'height' => 200, 'crop' => 0);
		update_option( 'shop_thumbnail_image_size', $curr );

		update_option( 'woocommerce_default_catalog_orderby', 'date' );
		update_option( 'woocommerce_manage_stock', 'no' );
		update_option( 'woocommerce_stock_format', 'no_amount' );
		update_option( 'woocommerce_calc_shipping', 0 );
		update_option( 'woocommerce_enable_shipping_calc', 0 );
		update_option( 'woocommerce_default_country', 'AU:WA' );
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_review_rating_verification_required', 'yes' );
		update_option( 'woocommerce_file_download_method', 'redirect');

		update_option( 'woocommerce_admin_footer_text_rated',1);

		update_option( 'ss_woo_product_page',0);
		update_option( 'ss_simplify_interface',1);
		update_option( 'ss_auto_publish',1);
		update_option( 'ss_pingback',1);
		update_option( 'ss_maxload',5);

		update_option( '_cron_code',substr(md5(microtime()),0,20));
}

function ss_init_license_attribute() {
		if (taxonomy_exists('pa_license')) return;
		delete_transient( 'wc_attribute_taxonomies' );
		$attributes = wc_get_attribute_taxonomies();
		foreach ($attributes as $attribute) {
			if ($attribute->attribute_name == 'license') {
				return;
			}
		}

		unset($attribute);

		global $wpdb;
		$attribute['attribute_name'] = 'license';
		$attribute['attribute_label'] = 'License';
		$attribute['attribute_type'] = 'select';
		$attribute['attribute_orderby'] = 'menu_order';
		$attribute['attribute_public'] = 1;
		$wpdb->insert( $wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute );

		$permalinks = get_option( 'woocommerce_permalinks' );

		$label = 'licenses';
		$taxonomy_data = array(
                        'hierarchical'          => true,
                        'update_count_callback' => '_update_post_term_count',
                        'labels'                => array(
                                'name'              => 'License',
                                'singular_name'     => 'License',
                                'search_items'      => sprintf( __( 'Search %s', 'woocommerce' ), $label ),
                                'all_items'         => sprintf( __( 'All %s', 'woocommerce' ), $label ),
                                'parent_item'       => sprintf( __( 'Parent %s', 'woocommerce' ), $label ),
                                'parent_item_colon' => sprintf( __( 'Parent %s:', 'woocommerce' ), $label ),
                                'edit_item'         => sprintf( __( 'Edit %s', 'woocommerce' ), $label ),
                                'update_item'       => sprintf( __( 'Update %s', 'woocommerce' ), $label ),
                                'add_new_item'      => sprintf( __( 'Add New %s', 'woocommerce' ), $label ),
                                'new_item_name'     => sprintf( __( 'New %s', 'woocommerce' ), $label )
                            ),
                        'show_ui'           => false,
                        'query_var'         => true,
                        'rewrite'           => array(
                            'slug'         => empty( $permalinks['attribute_base'] ) ? '' : trailingslashit( $permalinks['attribute_base'] ) . sanitize_title( 'license' ),
                            'with_front'   => false,
                            'hierarchical' => true
                        ),
                        'sort'              => false,
                        'public'            => true,
                        'show_in_nav_menus' => false,
                        'capabilities'      => array(
                            'manage_terms' => 'manage_product_terms',
                            'edit_terms'   => 'edit_product_terms',
                            'delete_terms' => 'delete_product_terms',
                            'assign_terms' => 'assign_product_terms',
                        )
                    );


		register_taxonomy( 'pa_license', array('product'), $taxonomy_data);

		delete_transient( 'wc_attribute_taxonomies' );

		$attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies" );
		set_transient( 'wc_attribute_taxonomies', $attribute_taxonomies );
		apply_filters( 'woocommerce_attribute_taxonomies', $attribute_taxonomies );
}

function ss_init_license_variables() {
			if (!taxonomy_exists('pa_license')) return;
			if (get_option('ss_init_license_attribute_done')) return;
			update_option('ss_init_license_attribute_done', 1);

			$term_meta = array();

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Small JPEG';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_smalljpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 2;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = 500;
			$term_meta[$currrec]['ss_license_max_y'] = 500;

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Medium JPEG';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_mediumjpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 4;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = 1000;
			$term_meta[$currrec]['ss_license_max_y'] = 1000;

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Large JPEG';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_largejpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 7;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = 3200;
			$term_meta[$currrec]['ss_license_max_y'] = 3200;

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Supersize JPEG';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_fulljpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 9;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Extended JPEG';
			$term_base[$currrec]['description'] = 'Our Extended Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. It provides additional Uses not available via our Standard License.';
			$term_base[$currrec]['slug'] = 'license_extjpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 25;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Buyout JPEG';
			$term_base[$currrec]['description'] = 'Our Buyout license permits a perpetual, exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. Once purchased, Stock Media will no longer be available for sale in any capacity.';
			$term_base[$currrec]['slug'] = 'license_buyoutjpeg';
			$term_meta[$currrec]['ss_license_raster'] = 1;
			$term_meta[$currrec]['ss_default_license_price'] = 150;
			$term_meta[$currrec]['ss_license_vector'] = '0';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Vector';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_vector';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = 12;
			$term_meta[$currrec]['ss_license_vector'] = 1;
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = 1;
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Extended Vector';
			$term_base[$currrec]['description'] = 'Our Extended Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. It provides additional Uses not available via our Standard License.';
			$term_base[$currrec]['slug'] = 'license_extvector';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = 25;
			$term_meta[$currrec]['ss_license_vector'] = 1;
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = 1;
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Buyout Vector';
			$term_base[$currrec]['description'] = 'Our Buyout license permits a perpetual, exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. Once purchased, Stock Media will no longer be available for sale in any capacity.';
			$term_base[$currrec]['slug'] = 'license_buyoutvector';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = 150;
			$term_meta[$currrec]['ss_license_vector'] = 1;
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = 1;
			$term_meta[$currrec]['ss_license_max_x'] = '0';
			$term_meta[$currrec]['ss_license_max_y'] = '0';

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Vector Supersize JPEG';
			$term_base[$currrec]['description'] = 'Our Standard Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses.';
			$term_base[$currrec]['slug'] = 'license_vectorsupersizejpeg';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = 9;
			$term_meta[$currrec]['ss_license_vector'] = 1;
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = 6000;
			$term_meta[$currrec]['ss_license_max_y'] = 6000;

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Vector Extended JPEG';
			$term_base[$currrec]['description'] = 'Our Extended Royalty-free license permits a perpetual, non-exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. It provides additional Uses not available via our Standard License.';
			$term_base[$currrec]['slug'] = 'license_vectorextjpeg';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = 25;
			$term_meta[$currrec]['ss_license_vector'] = 1;
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = 6000;
			$term_meta[$currrec]['ss_license_max_y'] = 6000;

			$currrec = count($term_meta);
			$term_base[$currrec]['title'] = 'Vector Buyout JPEG';
			$term_base[$currrec]['description'] = 'Our Buyout license permits a perpetual, exclusive, non-transferable worldwide license to use the Stock Media for Permitted Uses. Once purchased, Stock Media will no longer be available for sale in any capacity.';
			$term_base[$currrec]['slug'] = 'license_vectorbuyoutjpeg';
			$term_meta[$currrec]['ss_license_raster'] = '0';
			$term_meta[$currrec]['ss_default_license_price'] = '150';
			$term_meta[$currrec]['ss_license_vector'] = '1';
			$term_meta[$currrec]['ss_license_video'] = '0';
			$term_meta[$currrec]['ss_license_output_vector'] = '0';
			$term_meta[$currrec]['ss_license_max_x'] = '6000';
			$term_meta[$currrec]['ss_license_max_y'] = '6000';

			for ($i=0;$i<count($term_meta);$i++) {
				$term = wp_insert_term($term_base[$i]['title'], 'pa_license', array(
				    'description'=> $term_base[$i]['description'],
				    'slug' => $term_base[$i]['slug']
				  ));
				$currmeta = $term_meta[$i];
				update_option( "taxonomy_".$term['term_id'], $currmeta );
			}
}

?>