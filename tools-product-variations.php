<?php

$currSS->disable_direct_access();

function ss_add_all_licenses_to_product($postid='') {
	if (!$postid) {
		global $post;
		$postid = $post->ID;
	}
	$thedata = Array('pa_license'=>Array(
        'name'=>'pa_license',
        'value'=>'',
        'is_visible' => '1', 
        'is_variation' => '1',
        'is_taxonomy' => '1'
	));

	$thedata = apply_filters('ss_license_attribute_to_product',$thedata);

    update_post_meta($postid,'_product_attributes',$thedata);

	$alllicenses = get_terms('pa_license', 'hide_empty=0' );
	foreach ( $alllicenses as $currlicense ) {
		$toadd[] = $currlicense->slug;
	}
	wp_set_object_terms($postid, $toadd, 'pa_license' );
}

function ss_add_license_as_variation($postid,$licensetoadd) {
	$my_post = array(
	      'post_title'    => 'Variation of ' . esc_attr(strip_tags($postid)),
	      'post_name'     => 'product-' . $postid . '-variation',
	      'post_status'   => 'publish',
	      'post_parent'   => $postid,
	      'post_type'     => 'product_variation',
	      'orderby'     => 'menu_order',
		  'order'       => 'ASC',
		  'fields'      => 'ids',
		  'numberposts' => -1,
	      'guid'          =>  home_url() . '/?product_variation=product-' . $postid . '-variation'
	);

	$my_post = apply_filters('ss_license_variation_to_product',$my_post);

    // Insert the post into the database
	$varpostid = wp_insert_post( $my_post );

    update_post_meta($varpostid, 'attribute_pa_license', $licensetoadd['slug']);
    update_post_meta($varpostid, '_price', $licensetoadd['price'] );
    update_post_meta($varpostid, '_regular_price', $licensetoadd['price'] );
    update_post_meta($varpostid, '_manage_stock', 'no' );
    update_post_meta($varpostid, '_downloadable', 'yes' );
    update_post_meta($varpostid, '_virtual', 'yes' );
    update_post_meta($varpostid, '_download_limit', get_option( 'ss_download_limit') );
    update_post_meta($varpostid, '_download_expiry', get_option( 'ss_download_expiry') );
	ss_update_dl_url($varpostid);

	$GLOBALS['currSS']->ss_refresh_product($postid);
}

function ss_update_dl_url($varpostid) {
	$files = ss_get_dl_url_hash($varpostid);

	update_post_meta($varpostid, '_downloadable_files', $files);
}

function ss_get_dl_url_hash($varpostid) {
	$p = get_post_field('post_parent', $varpostid);
	$v = $varpostid;

	$fileextension = pathinfo(get_post_meta($p, 'ss_media_filename', true),PATHINFO_EXTENSION);

	$url = $p.'-'.$v.'-'.$fileextension;

	$filename = substr(md5($p.$v),0,6).'.media';

	$file_hash = md5($url);
	$files[$file_hash] = array(
		'name' => $filename,
		'file' => $url
	);
	return apply_filters('ss_getting_dl_url_hash',$files);
}

// FOR NEW PRODUCTS -> Add default licenses based on media type
function ss_apply_default_licenses_to_product($postid) {
	$added = 0;

	// get current list of variations
	$args = array( 'post_type' => 'product_variation', 'post_parent'=> $postid , 'posts_per_page'=> 99999999,'fields' => 'ids');
	$myposts = get_posts($args);
	$currentvariations = array();
	foreach ( $myposts as $currvariation ) {
		$currentvariations[] = get_post_meta( $currvariation, 'attribute_pa_license', true );
	}

	$alllicenses = get_terms('pa_license', 'hide_empty=0' );
	$toadd = array();
	foreach ( $alllicenses as $currlicense ) {
		if (array_search($currlicense->slug,$currentvariations) !== false) continue;
		$term_meta = get_option( "taxonomy_" . $currlicense->term_id );
		if (get_post_meta( $postid, '_ss_mediatype', true ) == 'raster') {
			$currrec = count($toadd);
			if ($term_meta['ss_license_raster']) {
				$toadd[$currrec]['term_id'] = $currlicense->term_id;
				$toadd[$currrec]['slug'] = $currlicense->slug;
				$toadd[$currrec]['price'] = $term_meta['ss_default_license_price'];
			}
		}
		if (get_post_meta( $postid, '_ss_mediatype', true ) == 'vector') {
			$currrec = count($toadd);
			if ($term_meta['ss_license_vector']) {
				$toadd[$currrec]['term_id'] = $currlicense->term_id;
				$toadd[$currrec]['slug'] = $currlicense->slug;
				$toadd[$currrec]['price'] = $term_meta['ss_default_license_price'];
			}
		}
		if (get_post_meta( $postid, '_ss_mediatype', true ) == 'video') {
			$currrec = count($toadd);
			if ($term_meta['ss_license_video']) {
				$toadd[$currrec]['term_id'] = $currlicense->term_id;
				$toadd[$currrec]['slug'] = $currlicense->slug;
				$toadd[$currrec]['price'] = $term_meta['ss_default_license_price'];
			}
		}
	}

	foreach ($toadd as $licensetoadd) {
		ss_add_license_as_variation($postid,$licensetoadd);
		$added++;
    }
	return $added;
}

?>