<?php

$currSS->disable_direct_access();

//add_filter( 'woocommerce_product_related_posts_query', 'ss_remove_woo_categories_related',10,1);
function ss_remove_woo_categories_related($query) {
	// fix WooCommerce bug with groupings
	$query["where"] = str_replace("( tt.taxonomy = 'product_cat' AND t.term_id IN ","(( tt.taxonomy = 'product_cat' AND t.term_id IN ",$query["where"]).")";
	// remove cat compare if no cat exists
	$query["where"] = str_replace("( tt.taxonomy = 'product_cat' AND t.term_id IN ( 0 ) ) OR"," ",$query["where"]);
	return $query;
}

if (get_option('ss_woo_product_page')) add_action('woocommerce_before_add_to_cart_button','ss_product_media_details');

function ss_product_media_details() {
	global $post,$product;
	$attributes = $product->get_variation_attributes();

	$editorial = get_post_meta($post->ID, '_ss_editorial', true);

	foreach ( $attributes as $name => $options ) {
		$terms = wc_get_product_terms( $post->ID, $name, array( 'fields' => 'all' ) );
?>
<script>
jQuery(document).ready(function($) {
    $(document).on("change", "select#pa_license", function() {
		$('[id^="ss_div_"]').hide();
		$("#ss_div_" + $( "select#pa_license" ).val()).show();
    });

    $(document).on("click", ".reset_variations", function() {
		$('[id^="ss_div_"]').hide();
    });
});
</script>
<?php

foreach ( $terms as $term ) {
	if ( ! in_array( $term->slug, $options ) ) {
		continue;
	}

$cres = get_option( "taxonomy_".$term->term_id );
$ch = get_post_meta($post->ID, 'ss_media_height', true);
$cw = get_post_meta($post->ID, 'ss_media_width', true);

if (!$ch || !$cw) {
	$fileloc = $GLOBALS['currSS']->ss_media_dir.get_post_meta($post->ID, 'ss_media_filename',true);
	if (file_exists($fileloc) && !is_dir($fileloc)) {
		$image = new Imagick($fileloc);
		$ch = $image->getImageHeight();
		$cw = $image->getImageWidth();
		update_post_meta($post_id, 'ss_media_height', $ch);
		update_post_meta($post_id, 'ss_media_width', $cw);
	}
}

$ctype = get_post_meta($post->ID, '_ss_mediatype', true);

if ($cres["ss_license_max_x"] && $cres["ss_license_max_y"]) {
	if ($cres["ss_license_max_x"]/$cres["ss_license_max_y"] > $cw/$ch) {
		$th = $cres["ss_license_max_y"];
		$tw = round($cres["ss_license_max_y"]/$ch*$cw);
		$cres = $tw.'x'.$th;
		if (($ctype != 'vector') && (($tw > $cw) || ($th > $ch))) $cres = $cw.'x'.$ch;
	} else {
		$tw = $cres["ss_license_max_x"];
		$th = round($cres["ss_license_max_x"]/$cw*$ch);
		$cres = $tw.'x'.$th;
		if (($ctype != 'vector') && (($tw > $cw) || ($th > $ch))) $cres = $cw.'x'.$ch;
	}
} elseif ($cres["ss_license_max_x"] && !$cres["ss_license_max_y"]) {
		$tw = $cres["ss_license_max_x"];
		$th = round($cres["ss_license_max_x"]/$cw*$ch);
		$cres = $tw.'x'.$th;
		if (($ctype != 'vector') && (($tw > $cw) || ($th > $ch))) $cres = $cw.'x'.$ch;
} elseif (!$cres["ss_license_max_x"] && $cres["ss_license_max_y"]) {
		$th = $cres["ss_license_max_y"];
		$tw = round($cres["ss_license_max_y"]/$ch*$cw);
		$cres = $tw.'x'.$th;
		if (($ctype != 'vector') && (($tw > $cw) || ($th > $ch))) $cres = $cw.'x'.$ch;
} else {
	if ($ctype == 'raster') {
		$cres = $cw.'x'.$ch;
	} elseif ($ctype == 'vector') {
		$cres = 'Full';
	}
}

$lictype = 'Commercial';
if ($editorial) {
	$lictype = 'Editorial';
}

?>



<div id="<?php print "ss_div_".$term->slug; ?>" style="display:none;">
		<div class="sse_licencing_info">
			<span class="ss_product_license_title">License Type:&nbsp;</span>
			<span><a href="<?php print get_permalink( get_page_by_path( 'licensing' ) ); ?>#<?php print $term->slug; ?>" target="_blank"><?php print $lictype; ?></a></span>		
		</div>
		<div class="sse_licencing_info">
			<span class="ss_product_resolution_title">Resolution:&nbsp;</span>
			<span><?php print $cres; ?></span>		
		</div>
</div>
<?php } } ?>
		
<?php

if (get_post_meta($post->ID, '_ss_exclusive', true)) $ss_inclusions[] = "Exclusive";
if ($editorial) $ss_inclusions[] = "Editorial use only";
if (get_post_meta($post->ID, '_ss_modelrelease', true)) $ss_inclusions[] = "Model release";
if (get_post_meta($post->ID, '_ss_propertyrelease', true)) $ss_inclusions[] = "Property release";
if (is_array($ss_inclusions)) $ss_inclusions = implode(', ', $ss_inclusions);

?>
<?php if ($ss_inclusions) { ?><div class="sse_licencing_inclu"><span class="ss_product_inclusions_title">Inclusions:&nbsp;</span> <span class="ss_product_inclusions"><?php print $ss_inclusions; ?></span></div><?php } ?>
<div style="padding-bottom:13px;"></div>

<style>
.woocommerce div.product form.cart .variations {
margin-bottom:.5em!important;
}
			.sse_licencing_info {
			font-size:14px;}
			.sse_licencing_inclu {
			font-size:12px;}
			.ss_product_resolution_title,.ss_product_license_title,.ss_product_inclusions_title {
font-weight:bold;
			}
</style>
<?php } ?>