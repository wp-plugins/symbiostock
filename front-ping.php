<?php

$currSS->disable_direct_access();

if (get_option('ss_pingback')) add_action('init','ss_pingback');

function ss_pingback() {
	if (isset($_GET['ss_ping'])) {
		$ping = get_plugin_data(dirname( __FILE__ ).'/'.plugin_basename( dirname( __FILE__ ) ).'.php');
		print $ping['Name'].'|'.$ping['Version'];
		exit();
	}
}

?>