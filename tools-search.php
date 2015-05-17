<?php

// Tweaked from Post Meta Searcher by Luke Rollans

$currSS->disable_direct_access();

function modify_wp_search_where( $where ) {
	
	if( is_search() ) {
		
		global $wpdb, $wp;
		
		$where = preg_replace(
			"/($wpdb->posts.post_title (LIKE '%{$wp->query_vars['s']}%'))/i",
			"$0 OR (($wpdb->postmeta.meta_key='ss_product_tags') and ($wpdb->postmeta.meta_value LIKE '%{$wp->query_vars['s']}%' ))",
			$where
			);
		
		add_filter( 'posts_join_request', 'modify_wp_search_join' );
		add_filter( 'posts_distinct_request', 'modify_wp_search_distinct' );
	}
	
	return $where;
	
}
add_action( 'posts_where_request', 'modify_wp_search_where' );

function modify_wp_search_join( $join ) {

	global $wpdb;
	
	return $join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
	
}

function modify_wp_search_distinct( $distinct ) {

	return 'DISTINCT';
	
}

?>