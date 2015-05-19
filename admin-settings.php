<?php

$currSS->disable_direct_access();

ss_create_Settings::init();
class ss_create_Settings {
    public static function init() {
		add_action( 'admin_menu',  __CLASS__ . '::add_ss_settings_page' );
    }

	function add_ss_settings_page() {
		add_submenu_page('edit.php?post_type=product', 'Symbiostock Settings', 'Settings', 'manage_product_terms', 'manage_ss_settings', __CLASS__ .'::ss_settings_page');
	}

	function ss_settings_page() {
		$GLOBALS['currSS']->issspage = 1;

		if(isset($_POST['fpass'])) {
			do_action('ss_start_options_receive');

			if (isset($_POST['ss_download_limit'])) update_option('ss_download_limit',sanitize_text_field($_POST['ss_download_limit']));
			if (isset($_POST['ss_download_expiry'])) update_option('ss_download_expiry',sanitize_text_field($_POST['ss_download_expiry']));
			if (isset($_POST['ss_watermarkpercent'])) update_option('ss_watermarkpercent',sanitize_text_field($_POST['ss_watermarkpercent']));
			update_option('ss_woo_product_page',sanitize_text_field($_POST['ss_woo_product_page']));
			update_option('ss_simplify_interface',sanitize_text_field($_POST['ss_simplify_interface']));
			update_option('ss_auto_publish',sanitize_text_field($_POST['ss_auto_publish']));
			update_option('ss_pingback',sanitize_text_field($_POST['ss_pingback']));
			update_option('ss_default_update_metadata',sanitize_text_field($_POST['ss_default_update_metadata']));

			if (!$_POST['ss_maxload']) $_POST['ss_maxload'] = 5;
			if ($_POST['ss_maxload'] > 40) $_POST['ss_maxload'] = 40;
			if ($_POST['ss_maxload'] < 1) $_POST['ss_maxload'] = 1;
			update_option('ss_maxload',sanitize_text_field($_POST['ss_maxload']));

			if (isset($_POST['ss_reset_watermark'])) {
				copy($GLOBALS['currSS']->ss_default_watermark_loc, $GLOBALS['currSS']->ss_watermark_loc);
			}
			if (is_uploaded_file($_FILES["ss_new_watermark"]["tmp_name"])) {
				if ((ss_get_mime_type($_FILES["ss_new_watermark"]["tmp_name"]) == 'image/png') && (filesize($_FILES["ss_new_watermark"]["tmp_name"])< 200000)) {
					move_uploaded_file($_FILES["ss_new_watermark"]["tmp_name"], $GLOBALS['currSS']->ss_watermark_loc);
				} else {
					unlink($_FILES["ss_new_watermark"]["tmp_name"]);
					$err=1;
				}
			}

			do_action('ss_end_options_receive');
		}
?>
<?php if (isset($err)) { ?>
		<div class="error">
			<p><?php print __('Sorry, that file did not register as a PNG or was too large in size.','ss'); ?></p>
		</div>
<?php } ?>
<div class="wrap woocommerce">
	<form method="post" id="mainform" action="" enctype="multipart/form-data">
<input type="hidden" name="fpass" value="1">
		<h3>Symbiostock Settings</h3><table class="form-table">

<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_download_limit">Download Limit</label>
									</th>
						<td class="forminp forminp-number">
							<input
								name="ss_download_limit"
								id="ss_download_limit"
								type="number"
								style=""
								value="<?php print get_option('ss_download_limit'); ?>"
								class=""
								placeholder=""
																/> <span class="description">Number of times media can be downloaded once purchased. Leave blank for unlimited.</span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_download_expiry">Download Expiry</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_download_expiry"
								id="ss_download_expiry"
								type="number"
								style=""
								value="<?php print get_option('ss_download_expiry'); ?>"
								class=""
								placeholder=""
																/> <span class="description">Number of days media is available for downloaded once purchased. Leave blank for unlimited.</span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_simplify_interface">Personalize Interface</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_simplify_interface"
								id="ss_simplify_interface"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_simplify_interface')) print 'checked'; ?>
																/> <span class="description">Simplify administrative interface to focus on media sales. <b>(Recommended)</b></span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_woo_product_page">Personalize Product Page</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_woo_product_page"
								id="ss_woo_product_page"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_woo_product_page')) print 'checked'; ?>
																/> <span class="description">Add media information to product page.</span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="">Symbiostock Artist Network</label>
													</th>
						<td class="forminp forminp-number">
							<span class="description"><a href="http://www.symbiostock.org/artist-network-submit/" target="_blank">Include your store</a> in the <a href="http://www.symbiostock.org/artist-network/" target="_blank"><img src="<?php print $GLOBALS['currSS']->ss_web_assets_dir; ?>ss_fullico.png" style='max-width:100px;vertical-align:bottom;padding-right:2px;padding-left:2px;'></a> Artist Network <b>(Recommended)</b></span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_pingback">Artist Network Inclusion</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_pingback"
								id="ss_pingback"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_pingback')) print 'checked'; ?>
																/> <span class="description">Permits the Symbiostock Artist Network to confirm that your site is running Symbiostock. <b>(Recommended)</b></span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_auto_publish">Auto-publish Products</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_auto_publish"
								id="ss_auto_publish"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_auto_publish')) print 'checked'; ?>
																/> <span class="description">Scheduler will auto-publish media with meta content. <b>(Recommended)</b></span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_default_update_metadata">Save Metadata in Images</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_default_update_metadata"
								id="ss_default_update_metadata"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_default_update_metadata')) print 'checked'; ?>
																/> <span class="description">If enabled, JPEG media's metadata will be updated with your WordPress edits upon saving. Can be disabled on an individual basis.</span>						</td>
					</tr>
</table><br><h3>Watermark</h3>
<img src="<?php print $GLOBALS['currSS']->ss_cron_loc; ?>&ss_wm=1" style='max-width:400px;max-height:250px;padding-top:10px;'>
<table class="form-table"><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_watermarkpercent">Watermark to image size ratio (in percent)</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_watermarkpercent"
								id="ss_watermarkpercent"
								type="number"
								style=""
								value="<?php print get_option('ss_watermarkpercent'); ?>"
								class=""
								placeholder=""
																/> <span class="description">This is the size of the watermark that will be imposed upon each stock image preview. Min: 1, Max:100.</span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_watermarkpercent">Upload new watermark</label>
													</th>
						<td class="forminp forminp-number">
							<input type="file" id="ss_new_watermark" name="ss_new_watermark"><br><span class="description">Replace the current watermark with a new one. Thumbnails will be regenerated systematically through the maintenance process. PNG format, 150kb max. Recommended opacity: 15-30%.</span>						</td>
					</tr><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_download_expiry">Reset watermark</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_reset_watermark"
								id="ss_reset_watermark"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
																/> <span class="description">Resets the media watermark.</span>						</td>
					</tr></table>

<?php do_action('ss_end_options'); ?>

		<p class="submit">
							<input name="save" class="button-primary" type="submit" value="Save changes" /></p>
	</form>
</div>
<?php
	}
}

add_action('ss_end_options','ss_display_details');
function ss_display_details() {
?>
<br><h3>FTP Upload directory</h3>
<input type="text" value="<?php echo $GLOBALS['currSS']->ss_media_upload_dir; ?>" readonly size='100'/><br><br>
Upload all media files here via <a href="https://filezilla-project.org/" target="_blank">FTP</a>. Files will be processed systematically and added into the system.<br><br>
<br><h3>Maintenance command (cron job) <a href="<?php print $GLOBALS['currSS']->ss_cron_loc; ?>" target="_blank">(Run now)</a></h3>
<input type="text" value="<?php echo 'curl --silent \''.$GLOBALS['currSS']->ss_cron_loc.'\' &>/dev/null'; ?>" readonly size='100'/><br><br>
Run once a minute via operating system scheduler for all FTP processing and other maintenance actions.<br>
<table class="form-table"><tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_maxload">Max Processor Load</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_maxload"
								id="ss_maxload"
								type="number"
								style=""
								value="<?php print get_option('ss_maxload'); ?>"
								class=""
								placeholder=""
																/> <span class="description">This determines how many images the processor parses in one go. Min: 1, Max:40.</span>						</td>
					</tr>
</table>
<?php
}

add_action('ss_end_options','ss_display_envvars');
function ss_display_envvars() {
?>
<br><h3>Environmental Variables</h3>
<table class="form-table">
<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_default_update_metadata">Maximum Execution Time</label>
													</th>
						<td class="forminp forminp-number">
<?php
	$reddy = '';
	if(ini_get('max_execution_time') < 60) {
		$reddy = '1';
	}
?>
<div class="ss_envvar <?php if ($reddy) print 'ss_envimportant'; ?>"><?php if (!$reddy) print '<span class="dashicons dashicons-yes"></span>'; else print '<span class="dashicons dashicons-flag"></span>'; ?><?php print ini_get('max_execution_time'); ?></div> &nbsp; <span class="description">Your max_execution_time should be set to over 60, ideally over 300. This is set in php.ini and determines the maximum amount of time a script can run. <a href="http://www.symbiostock.org/docs/increasing-the-maximum-execution-time/" target="_blank">Documentation</a>. <?php if ($reddy) print '<b>You are likely to experience unpredictable results if this is not fixed.</b>'; ?></span>
</td>
					</tr>
<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_default_update_metadata">Memory Limit</label>
													</th>
						<td class="forminp forminp-number">
<?php
	$reddy = '';
	if(ini_get('memory_limit') < 64) {
		$reddy = '1';
	}
?>
<div class="ss_envvar <?php if ($reddy) print 'ss_envimportant'; ?>"><?php if (!$reddy) print '<span class="dashicons dashicons-yes"></span>'; else print '<span class="dashicons dashicons-flag"></span>'; ?><?php print ini_get('memory_limit'); ?></div> &nbsp; <span class="description">Your memory_limit should be set to over 64, ideally over 200. This is set in php.ini and also in wp-config.php and determines the maximum amount of memory a script can use. The more media you have, the higher this needs to be. <a href="http://www.symbiostock.org/docs/increasing-the-memory-limit/" target="_blank">Documentation</a>. <?php if ($reddy) print '<b>You are likely to experience unpredictable results if this is not fixed.</b>'; ?></span>
</td>
					</tr>
<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_default_update_metadata">Exec Commands</label>
													</th>
						<td class="forminp forminp-number">
<?php
	$disabled = explode(',', ini_get('disable_functions'));

	$cmd = 'Enabled';
	if(in_array('shell_exec', $disabled)) {
		$reddy = '1';
		$cmd = 'Disabled';
	}
?>
<div class="ss_envvar <?php if ($reddy) print 'ss_envimportant'; ?>"><?php if (!$reddy) print '<span class="dashicons dashicons-yes"></span>'; else print '<span class="dashicons dashicons-flag"></span>'; ?><?php print $cmd; ?></div> &nbsp; <span class="description">The shell_exec command is used to read and write metadata. Without this enabled, you will not be able to read or write metadata from your media. <?php if ($reddy) print '<b>You currently cannot read or write metadata from images. Please contact your web-host regarding enabling this.</b>'; ?></span>
<style>
.ss_envvar {
display:inline-block;
padding:8px 13px;
border: #85FF5C;
background-color: #E0FFD6;
margin:10px 0px;
}
.ss_envimportant {
border: #FF6666;
background-color: #FFD1D1;
}
</style>
</td>
					</tr>
</table>
<?php
}

?>