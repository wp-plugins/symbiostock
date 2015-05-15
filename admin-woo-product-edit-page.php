<?php

$currSS->disable_direct_access();

// Refresh to make sure all licenses are available to be picked
add_action('woocommerce_product_options_general_product_data','ss_add_all_licenses_to_product');

// Create new fields
function ss_add_edit_form_multipart_encoding() {
    echo ' enctype="multipart/form-data"';
}
add_action('post_edit_form_tag', 'ss_add_edit_form_multipart_encoding');

add_action('woocommerce_product_options_general_product_data','woo_add_custom_general_fields');
function woo_add_custom_general_fields() {
	global $post;
	$type = get_post_meta($post->ID, '_ss_mediatype', true);
	$modelrelease = get_post_meta($post->ID, '_ss_modelrelease', true);
	$propertyrelease = get_post_meta($post->ID, '_ss_propertyrelease', true);
	$exclusive = get_post_meta($post->ID, '_ss_exclusive', true);
	$rating = get_post_meta($post->ID, '_ss_rating', true);
	$editorial = get_post_meta($post->ID, '_ss_editorial', true);
	$updatemetadata = get_post_meta($post->ID, '_ss_update_metadata', true);

	do_action('ss_web_uploader_start',$type);

	if ($GLOBALS['currSS']->ss_canupload) { 
		if (!$type) {	// Adding new
?>
<p class="form-field _ss_reuploadimage_field "><label for="_ss_uploadnewmedia[]">Upload media:</label><input type="file" id="_ss_uploadnewmedia[]" name="_ss_uploadnewmedia[]" multiple><br>
<div style="margin:12px;border:solid #DEDEDE 1px;background-color:#F7F7F7;padding:20px;border-radius:10px;display: inline-block;max-width:400px;">
<span class="dashicons dashicons-format-gallery"></span><div style="display:inline;"><span style="font-size:14px;font-weight:bold;"> &nbsp;This page acts solely as a web interface for <u>uploading</u> your file(s):</span><br>
<br>1) Choose file(s).<br>
2) Click 'Publish'.<br>
<br>
<b>New products will be created once the scheduled maintenance process parses your uploaded file(s).</b><br><br>It is recommended you use FTP for most media uploads. FTP details are located in  Symbiostock 'Settings'.</div></div></p>
<?php 
			if ($GLOBALS['currSS']->simplify()) {
?>
<script>
jQuery(document).ready(function(){
    jQuery("#postdivrich").hide();
    jQuery("#titlediv").hide();
    jQuery("#postexcerpt").hide();
});
</script>
<?php
			}
		}
		else {	// Editing
?>
<p class="form-field _ss_mediatype_field "><label for="_ss_mediatype">Media Type</label><select id="_ss_mediatype" name="_ss_mediatype" class="select short" style="" disabled><option><?php print ucfirst($type); ?></option></select></p>
<p class="form-field _ss_mediatype_field "><label for="_ss_rating">Rating</label><select id="_ss_rating" name="_ss_rating" class="select short" style="">
<option value='1' <?php if ($rating == 1) print 'selected'; ?>>G - General</option>
<option value='2' <?php if ($rating == 2) print 'selected'; ?>>PG - Parental Guidance</option>
<option value='3' <?php if ($rating == 3) print 'selected'; ?>>A - Adults</option>
</select></p>
<p class="form-field _ss_mediatype_field "><label for="_ss_modelrelease">Releases:</label><input type="checkbox" id="_ss_modelrelease" name="_ss_modelrelease" <?php if ($modelrelease) print 'checked'; ?>> Model &nbsp; <input type="checkbox" id="_ss_propertyrelease" name="_ss_propertyrelease" <?php if ($propertyrelease) print 'checked'; ?>> Property</p>
<p class="form-field _ss_mediatype_field "><label for="_ss_exclusive">Exclusive:</label><input type="checkbox" id="_ss_exclusive" name="_ss_exclusive" <?php if ($exclusive) print 'checked'; ?>></p>
<p class="form-field _ss_mediatype_field "><label for="_ss_editorial">Editorial:</label><input type="checkbox" id="_ss_editorial" name="_ss_editorial" <?php if ($editorial) print 'checked'; ?>></p>
<p class="form-field _ss_reuploadimage_field "><label for="_ss_reuploadimage">Re-upload media:</label><input type="file" id="_ss_reuploadimage" name="_ss_reuploadimage"><br>Replacements will be processed systematically during maintenance run. <br>An alternative to using this web-form is to upload the file via FTP with the following filename: <b><?php print $GLOBALS['currSS']->ss_media_replace_prefix.$post->ID; ?></b><br> Examples: <b><?php print $GLOBALS['currSS']->ss_media_replace_prefix.$post->ID; ?></b>.jpg, <b><?php print $GLOBALS['currSS']->ss_media_replace_prefix.$post->ID; ?></b>.eps</p>
<?php
			$filename = $GLOBALS['currSS']->ss_media_dir.get_post_meta($post->ID, 'ss_media_filename', true);
			if (ss_get_mime_type($filename) == 'image/jpeg') {
?>
<p class="form-field _ss_mediatype_field "><input type="hidden" name="pass" value="1"><label for="_ss_update_metadata">Update Metadata:</label> Yes <input type="radio" id="_ss_update_metadata" name="_ss_update_metadata" value="1" <?php if ($updatemetadata == 1) print 'checked'; ?>> &nbsp; No <input type="radio" id="_ss_update_metadata" name="_ss_update_metadata" value="2" <?php if ($updatemetadata == 2) print 'checked'; ?>> &nbsp; Global Default <input type="radio" id="_ss_update_metadata" name="_ss_update_metadata" value="0" <?php if (!$updatemetadata) print 'checked'; ?>></p>
<?php
			}
		}
?>
<script>
jQuery(document).ready(function(){
    jQuery("#postexcerpt h3 span").html("Short/Meta Description");
});
</script>
<?php
	}

	do_action('ss_web_uploader_end');
}

// Save new fields

add_action('ss_init','ss_process_new_upload');
function ss_process_new_upload() {
	if (!isset($_FILES["_ss_uploadnewmedia"]) || !$GLOBALS['currSS']->ss_canupload) return;
	for ($i=0;$i<count($_FILES["_ss_uploadnewmedia"]["name"]);$i++) {
		if (move_uploaded_file($_FILES["_ss_uploadnewmedia"]["tmp_name"][$i], $GLOBALS['currSS']->ss_media_upload_dir.$_FILES["_ss_uploadnewmedia"]["name"][$i]))
			$GLOBALS['currSS']->update_notice('new_media');
	}
}

add_action('woocommerce_process_product_meta','ss_add_custom_general_fields_save');
function ss_add_custom_general_fields_save($post_id) {
	$type = get_post_meta($post_id, '_ss_mediatype', true);
	if ($type && $GLOBALS['currSS']->ss_canupload) {
		$filename = $GLOBALS['currSS']->ss_media_upload_dir.$GLOBALS['currSS']->ss_media_replace_prefix.$post_id.'.'.pathinfo($_FILES["_ss_reuploadimage"]["name"],PATHINFO_EXTENSION);
		if (move_uploaded_file($_FILES["_ss_reuploadimage"]["tmp_name"], $filename)) 
			$GLOBALS['currSS']->update_notice('updated_media');
	}
	if (isset($_POST['pass'])) {
		update_post_meta($post_id, '_ss_modelrelease', sanitize_text_field($_POST['_ss_modelrelease']));
		update_post_meta($post_id, '_ss_propertyrelease', sanitize_text_field($_POST['_ss_propertyrelease']));
		update_post_meta($post_id, '_ss_exclusive', sanitize_text_field($_POST['_ss_exclusive']));
		update_post_meta($post_id, '_ss_rating', sanitize_text_field($_POST['_ss_rating']));
		update_post_meta($post_id, '_ss_editorial', sanitize_text_field($_POST['_ss_editorial']));
		update_post_meta($post_id, '_ss_update_metadata', sanitize_text_field($_POST['_ss_update_metadata']));
	}
}

// Remove extra product types, rename variable
add_filter('product_type_selector', 'remove_variable_products');

function remove_variable_products( $types ) {
	if (!$GLOBALS['currSS']->simplify()) return $types;
	unset( $types['simple'] );
	unset( $types['external'] );
	unset( $types['grouped'] );
	$types['variable'] = __(  'Media', 'ss'  );

	return $types;
}

//Remove extra tabs
function remove_linked_products($tabs){
	if (!$GLOBALS['currSS']->simplify()) return $tabs;
    unset($tabs['inventory']);
    unset($tabs['shipping']);
//    unset($tabs['linked_product']);
    unset($tabs['attribute']);
//    unset($tabs['advanced']);
	$tabs['variations'] = array(
        'label'  => __( 'Licenses', 'ss' ),
        'target' => 'variable_product_options',
        'class'  => array( 'variations_tab', 'show_if_variable' )
	);

    return $tabs;
}
add_filter('woocommerce_product_data_tabs', 'remove_linked_products');
//Remove extra meta boxes
function remove_short_description() {
	if (!$GLOBALS['currSS']->simplify()) return;
//    remove_meta_box( 'postexcerpt', 'product', 'normal');
    remove_meta_box( 'postcustom', 'product', 'normal');
    remove_meta_box( 'commentsdiv', 'product', 'normal');
}
add_action('add_meta_boxes', 'remove_short_description',999);

?>