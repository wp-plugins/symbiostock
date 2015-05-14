<?php

$currSS->disable_direct_access();

// Remove excess menu pages

add_action( 'admin_menu', 'remove_taxonomy_menu_pages');
function remove_taxonomy_menu_pages() {
	if (!$GLOBALS['currSS']->simplify()) return;
//	register_taxonomy('product_tag','woocommerce_taxonomy_objects_product_tag', array('show_ui' => false));
}

add_action( 'admin_menu', 'ss_remove_submenu_excess', 999 );
function ss_remove_submenu_excess() {
	if (!$GLOBALS['currSS']->simplify()) return;
	$page = remove_submenu_page('edit.php?post_type=product', 'edit-tags.php?taxonomy=product_shipping_class&post_type=product' );
	$page = remove_submenu_page('edit.php?post_type=product', 'product_attributes' );
//  $page = remove_submenu_page('edit.php?post_type=product', 'product_tags' );
//  $page = add_submenu_page('edit.php?post_type=product', __( 'Licenses', 'ss' ), __( 'Licenses', 'ss' ), 'manage_product_terms', 'edit-tags.php?taxonomy=pa_license&post_type=product' );
}

//Symbiostock Branding

// Change titles
add_filter('admin_title', 'ss_admin_title', 10, 2);
function ss_admin_title($admin_title, $title) {
	$admin_title = str_replace('WooCommerce','Symbiostock',$admin_title);
    return $admin_title;
}

add_filter( 'admin_footer_text', 'ss_footer_text',999);
function ss_footer_text($footer_text) {
	if (isset($GLOBALS['currSS']->issspage) || stristr($footer_text,'woocommerce')) return '<a href="http://www.symbiostock.org/docs/" target="_blank">Documentation</a> | Thank you for selling with Symbiostock and WooCommerce. Please leave us a <a href="https://wordpress.org/support/view/plugin-reviews/symbiostock#postform" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a> rating if you find Symbiostock useful!';
	return $footer_text;
}

add_filter('woocommerce_register_post_type_product', 'ss_product_to_media');
function ss_product_to_media($arr) {
	$arr['labels']['name'] = __('Media','ss');
	$arr['labels']['singular_name'] = __('Media','ss');
	$arr['labels']['menu_name'] = __('Symbiostock','ss');
	$arr['labels']['add_new'] = __('Add Media','ss');
	$arr['labels']['add_new_item'] = __('Add New Media','ss');
	$arr['labels']['edit_item'] = __('Edit Media','ss');
	$arr['labels']['new_item'] = __('New Media','ss');
	$arr['labels']['view'] = __('View Media','ss');
	$arr['labels']['view_item'] = __('View Media','ss');
	$arr['labels']['search_items'] = __('Search Media','ss');
	$arr['labels']['not_found'] = __('No Media found','ss');
	$arr['labels']['not_found_in_trash'] = __('No Media found in trash','ss');
	$arr['labels']['parent'] = __('Parent Media','ss');
	return $arr;
}

add_filter('admin_menu', 'ss_change_menutitle',999);
function ss_change_menutitle($title) {
	if (!$GLOBALS['currSS']->simplify()) return;
	global $menu,$submenu;

	foreach ($menu as $key => $value) {
		if ($value[0] == 'Dashboard') $dashkey = $key;
		if ($value[0] == 'Symbiostock') $sskey = $key;
		if ($value[0] == 'WooCommerce') $wookey = $key;
	}
	$seperator = array("","read","separator1","","wp-menu-separator");
	if (isset($dashkey)) $newmenu[2] = $menu[$dashkey];
	if (isset($sskey)) $newmenu[4] = $menu[$sskey];
	if (isset($wookey)) $newmenu[3] = $menu[$wookey];
	if (isset($dashkey)) unset($menu[$dashkey]);
	if (isset($sskey)) unset($menu[$sskey]);
	if (isset($dashkey)) unset($menu[$wookey]);
	$menu = array_merge($newmenu,$menu);

	foreach ($submenu["edit.php?post_type=product"] as $key => $value) {
		if ($value[0] == 'Symbiostock') $submenu["edit.php?post_type=product"][$key][0] = 'Media';
		if ($value[0] == 'Tags') {
			$submenu["edit.php?post_type=product"][$key][0] = 'Licenses';
			$submenu["edit.php?post_type=product"][$key][2] = 'edit-tags.php?taxonomy=pa_license&post_type=product';
		}
	}
}

// Delete accompanying full image when post is deleted
add_action( 'before_delete_post', 'ss_clear_stored_image');
function ss_clear_stored_image($postid) {
	$fileloc = $GLOBALS['currSS']->ss_media_dir.get_post_meta($postid, 'ss_media_filename', true);
	if (file_exists($fileloc)) unlink($fileloc);
}

	add_action( 'admin_head', 'ss_admin_head_icon');
		function ss_admin_head_icon() {
//#adminmenu #toplevel_page_woocommerce .menu-icon-generic div.wp-menu-image:before
?>
<style type="text/css">
#adminmenu #menu-posts-product .menu-icon-post div.wp-menu-image:before {
	background-image: url('<?php print $GLOBALS['currSS']->ss_web_assets_dir; ?>ss_ico.png');
    background-size: 20px 20px;
	background-repeat:no-repeat;
	background-position: center;
	width: 20px; height: 20px;
    content:"";
}
</style>
<?php
		}

?>