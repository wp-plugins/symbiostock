<?php

$currSS->disable_direct_access();

function ss_watermark_video($postid) {
	shell_exec("ffmpeg -y -t 60 -i 1120.mov -i /var/www/vhosts/symbiostock.com/httpdocs/wp-content/plugins/symbiostock/assets/ss_watermark.png -filter_complex 'scale=1400:750,overlay=x=(main_w-overlay_w)/2:y=(main_h)/1.8' -c:v libx264 -ar 22050 -s 622x350 -crf 20 video.flv");
}

add_action('ss_mid_options','ss_v_add_options');
function ss_v_add_options() {
?>
<table class="form-table">
<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="ss_v_enabled">Enable video</label>
													</th>
						<td class="forminp forminp-number">
							<input
								name="ss_v_enabled"
								id="ss_v_enabled"
								type="checkbox"
								style=""
								value="1"
								class=""
								placeholder=""
								<?php if (get_option('ss_v_enabled')) print 'checked'; ?>
																/> <span class="description">This determines whether video functions are enabled in your store.</span>						</td>
					</tr>
</table>
<?php
}

add_action('ss_end_options_receive','ss_v_process_options');
function ss_v_process_options() {
	update_option('ss_v_enabled',sanitize_text_field($_POST['ss_v_enabled']));
}

?>