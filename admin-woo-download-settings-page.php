<?php

$currSS->disable_direct_access();

// Remove extra download types, rename Redirect only
add_filter('woocommerce_downloadable_products_settings', 'ss_manage_downloads');

function ss_manage_downloads( $types ) {
	$arr['redirect'] = 'Symbiostock managed';
	$types[1]['options'] = $arr;
	$types[1]['desc'] = 'File downloads are managed by Symbiostock.';
	return apply_filters('ss_manage_digital_downloads', $types);
}

?>