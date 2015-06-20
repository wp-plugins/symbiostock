<?php

$currSS->disable_direct_access();

function ss_get_all_products() {
	global $wpdb;

	$querystr = "
	   SELECT $wpdb->posts.ID 
	   FROM $wpdb->posts
	   WHERE $wpdb->posts.post_type = 'product'
	   AND ( ($wpdb->posts.post_status = 'publish') OR ($wpdb->posts.post_status = 'draft') )
	   ORDER BY rand()
	";

	if ($c = $wpdb->get_results($querystr, ARRAY_N)) foreach ($c as $p) $result[] = $p[0];

	ss_clear_cache();

	if ($result) return $result;
	return array();
}

function ss_get_product_vars($parent_postid='') {
	global $wpdb;

	$querystr = "
	   SELECT $wpdb->posts.ID 
	   FROM $wpdb->posts
	   WHERE $wpdb->posts.post_type = 'product_variation'
	";

	if ($parent_postid) $querystr .= "
		AND ($wpdb->posts.post_parent = '".$parent_postid."')
	";

	$querystr .= "
		ORDER BY rand()
	";

	if ($c = $wpdb->get_results($querystr, ARRAY_N)) foreach ($c as $p) $result[] = $p[0];

	ss_clear_cache();

	if ($result) return $result;
	return array();
}

function ss_get_option($optionname) {
	global $wpdb;

	$querystr = "
	   SELECT option_value
	   FROM $wpdb->options
	   WHERE option_name = '".$optionname."'
	   LIMIT 1
	";

	$c = $wpdb->get_results($querystr, ARRAY_N);

	ss_clear_cache();

	if ($c) return maybe_unserialize($c[0][0]);
	return false;
}

function ss_update_option($optionname,$value) {
	global $wpdb;
	$serialized_value = maybe_serialize( $value );

	$t = ss_get_option($optionname);
	if ($t === $value) return;

	if ($t !== false) {
		$update_args = array('option_value' => $serialized_value);
		$update_where = array('option_name' => $optionname);
		$wpdb->update( $wpdb->options,$update_args,$update_where);
	} else {
		$insert_args = array('option_name' => $optionname,'option_value' => $serialized_value);
		$wpdb->insert( $wpdb->options,$insert_args );
	}

	ss_clear_cache();
}

function ss_get_post_meta($postid,$optionname,$astring=true) {
	global $wpdb;

	$c = $wpdb->get_results( "SELECT meta_value FROM $wpdb->postmeta WHERE post_id='".$postid."' and meta_key = '".$optionname."' LIMIT 1", ARRAY_N );

	ss_clear_cache();

	if ($c) return maybe_unserialize($c[0][0]);
	return false;
}

function ss_update_post_meta($postid,$optionname,$value) {
	global $wpdb;
	$serialized_value = maybe_serialize( $value );

	$t = ss_get_post_meta($postid,$optionname);
	if ($t === $value) return;

	if ($t !== false) {
		$update_args = array('meta_value' => $serialized_value);
		$update_where = array('post_id' => $postid,'meta_key' => $optionname);
		$wpdb->update( $wpdb->postmeta,$update_args,$update_where);
	} else {
		$insert_args = array('post_id' => $postid,'meta_key' => $optionname,'meta_value' => $serialized_value);
		$wpdb->insert( $wpdb->postmeta,$insert_args );
	}

	ss_clear_cache();
}

function ss_clear_cache() {
	global $wpdb;
	$wpdb->queries = array();
	$wpdb->flush();
	wp_cache_flush();
}

?>