<?php

$currSS->disable_direct_access();

// Extra processes

// Return watermark
if (isset($_GET['ss_wm'])) {
	add_action('init', 'ss_getwm',1);
	function ss_getwm() {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$filetype = finfo_file($finfo, $GLOBALS['currSS']->ss_watermark_loc);
		finfo_close($finfo);

		header("Content-Type: ".$filetype);
		readfile($GLOBALS['currSS']->ss_watermark_loc);
		exit();
	}
}

// Process cron
add_action('init', 'ss_do_cron');
function ss_do_cron($maxload=5) {
	if (!$maxload && ($maxload !== 0)) {
		$maxload = get_option('ss_maxload');
	}
	if (!$maxload && ($maxload !== 0)) {
		$maxload = $GLOBALS['currSS']->ss_maxload;
	}
?>
<style>
.memcl {
display:inline-block;
padding:10px 20px;
border:1px solid gray;
margin:10px 20px;
}
.actio {
display:inline-block;
padding:10px 20px;
border:1px solid blue;
margin:10px 20px;
}
</style>
<?php

	do_action('ss_cron_start',$maxload);

	$maxload = ss_single_cron($maxload);

	do_action('ss_cron_end',$maxload);

	exit();
}

function ss_single_cron($maxload) {

	// Failsafe - prevent attack
	if (ss_get_option('ss_cron_last') && (time()-ss_get_option('ss_cron_last') < 5)) {
		echo '<div class="actio">Pause between requests</div>';
		exit();
	}
	ss_update_option( 'ss_cron_last', time());

	$ftpcronning = ss_get_option('ss_image_cronning');
	$dlurlcronning = ss_get_option('ss_dlurl_cronning');
	$thumbregencronning = ss_get_option('ss_thumb_regen_cronning');
	$cleandircronning = ss_get_option('ss_cleandir_cronning');

	if ($ftpcronning == 0) $runftp = 1;
	elseif ((time()-$ftpcronning) > 600) $runftp = 1;	// In case it did not finish, restart process

	if ((time()-$dlurlcronning) > 6000) $rundlurl = 1;	// every 100 minutes

	if ((time()-$cleandircronning) > 86400) $runcleandir = 1;	// every day

	if ($thumbregencronning == 0) $runthumbregen = 1;
	elseif ((time()-$thumbregencronning) > 600) $runthumbregen = 1;	// In case it did not finish, restart process

	echo '<div class="actio">Blog: '.get_current_blog_id().'</div>';
	echo '<div class="memcl">Memory at Start: '.round(memory_get_usage ()/1000000).'M</div>';

	if (isset($runall) || isset($runcleandir)) {
		if (!$runall) $loadrate = 100;
		echo '<div class="actio">Indexing Tags</div>';
		ss_index_tags($maxload);
		ss_update_option( 'ss_cleandir_cronning', time());
		$maxload -= $loadrate;
	}

	if (isset($runall) || isset($runftp)) {
		$loadrate = 1;
		ss_update_option( 'ss_image_cronning', time());
		$numimagesparsed = ss_process_ftp(round($maxload*(1/$loadrate))*$loadrate);
		$maxload -= ceil($numimagesparsed*$loadrate);
		echo '<div class="actio">'.$numimagesparsed.' new/updated images parsed</div>';
		ss_update_option( 'ss_image_cronning', 0);
	} 

	if (isset($runall) || isset($runthumbregen)) {
		$loadrate = 1;
		ss_update_option( 'ss_thumb_regen_cronning', time());
		$maxload -= ceil(ss_update_thumbnails(round($maxload*(1/$loadrate)))*$loadrate);
		ss_update_option( 'ss_thumb_regen_cronning', 0);
	}

	if (isset($runall) || isset($rundlurl)) {
		ss_update_dlspecs();
		ss_update_option( 'ss_dlurl_cronning', time());
	}

	ss_clear_cache();

	echo '<div class="memcl">Memory at End: '.round(memory_get_usage ()/1000000).'M</div>';
	echo '<div class="memcl">Memory Peak: '.round(memory_get_peak_usage ()/1000000).'M</div>';
	print "<hr>";

	return $maxload;
}

// Index the product tags for a more efficient search
function ss_index_tags($maxload) {
	$myposts = ss_get_all_products();

	for ($i=0;$i<count($myposts);$i++) {
		$terms = implode(' ',wp_get_object_terms($myposts[$i], 'product_tag',array('fields' => 'names')));
		ss_update_post_meta($myposts[$i], 'ss_product_tags',$terms);

		// An action hook for critical product checks
		ss_do_product_updates_critical($myposts[$i]);
	}
}

function ss_clean_dir() {
	$mediafiles = scandir($GLOBALS['currSS']->ss_media_dir);
	foreach ($mediafiles as $currfile) {
		if (is_dir($GLOBALS['currSS']->ss_media_dir.$currfile)) continue;
		if (!trim(str_replace('.','',$currfile))) continue;
		if ($currfile == '.htaccess') continue;
		if (strstr($currfile,'ss_debug_')) continue;

		$filename = pathinfo($currfile,PATHINFO_FILENAME);
		$filename = explode('_',$filename);
		$productid = $filename[2];
		if (!get_post_status($productid)) {
			unlink($GLOBALS['currSS']->ss_media_dir.$currfile);
		}
	}
}

// Update watermarks for products where necessary
function ss_update_thumbnails($limit=10) {
	$i = 0;
	if ($regenkey = ss_get_option('ss_regen_thumbnails')) {
		require_once( trailingslashit(ABSPATH) . 'wp-admin/includes/image.php' );
		$myposts = ss_get_all_products();

		foreach ( $myposts as $currpost ) {
			if ($i >= $limit) break;
			if (ss_get_post_meta($currpost, 'ss_last_thumbnail_regen',true) < $regenkey) {
				ss_redo_thumbnails($currpost);
				$i++;
			}

			// An action hook for non-critical product checks
			ss_do_product_updates_notcritical($postid);
		}
		if (($limit > 0) && !$i) ss_update_option('ss_regen_thumbnails', 0);
	}
	echo '<div class="actio">'.$i.' thumbnails regenerated</div>';
	return $i;
}

// Update all download urls to match licenses in the case of manual additions and removals
// Update all download limits to match global settings
function ss_update_dlspecs() {
	$myposts = ss_get_product_vars();
	$i = 0;
	foreach ( $myposts as $currvariation ) {

		// First check if license has parent - TBR JUNE15
		$parent = ss_get_post_meta($currvariation, 'attribute_pa_license',true);
		global $wpdb;
		if (!$parent || !$wpdb->get_results( "SELECT name FROM $wpdb->terms WHERE slug='".$parent."' LIMIT 1", ARRAY_N )) {
			$productid = wp_get_post_parent_id($currvariation);
			wp_delete_post($currvariation);
			$GLOBALS['currSS']->ss_refresh_product($productid);
			$i++;
			continue;
		}

		// First check download URLs
		$curr = ss_get_dl_url_hash($currvariation);
		$stored = get_post_meta($currvariation, '_downloadable_files');

		if (is_array($curr)) {
			$currhash = key($curr);
			$currfname = $curr[$currhash]['name'];
		}
		if (is_array($stored[0])) {
			$storedhash = key($stored[0]);
			$storedfname = $stored[0][$storedhash]['name'];
		}
		if (!$storedhash || !$storedfname || ($currhash != $storedhash) || ($currfname != $storedfname)) {
			ss_update_dl_url($currvariation);
			$i++;
		}

		// Next check download limits to match global settings
		$dlimit = ss_get_post_meta($currvariation, '_download_limit',true);
		$dexpiry = ss_get_post_meta($currvariation, '_download_expiry',true);

		$currdlimit = ss_get_option( 'ss_download_limit');
		$currdexpiry = ss_get_option( 'ss_download_expiry');

		if ($dlimit != $currdlimit) {
			ss_update_post_meta($currvariation, '_download_limit',$currdlimit);
			$i++;
		}
		if ($dexpiry != $currdexpiry) {
			ss_update_post_meta($currvariation, '_download_expiry',$currdexpiry);
			$i++;
		}
	}
	echo '<div class="actio">'.$i.' integrity fixes done</div>';
}


function ss_do_product_updates_critical($postid) {
	// Ensure all woocommerce necessary meta exists - move this to non-critical after a month - TBR JUNE15
	if (!ss_get_post_meta($postid,'total_sales')) ss_update_post_meta($postid, 'total_sales', '');
	if (!ss_get_post_meta($postid,'_visibility')) ss_update_post_meta($postid, '_visibility', 'visible');
}

function ss_do_product_updates_notcritical($postid) {
	$ch = ss_get_post_meta($postid, 'ss_media_height', true);
	$cw = ss_get_post_meta($postid, 'ss_media_width', true);

	if (!$ch || !$cw) {
		if ($fileloc = ss_check_image_product($postid)) {
			$image = new Imagick($fileloc);
			$ch = $image->getImageHeight();
			$cw = $image->getImageWidth();
			ss_update_post_meta($postid, 'ss_media_height', $ch);
			ss_update_post_meta($postid, 'ss_media_width', $cw);
		}
	}
}

?>