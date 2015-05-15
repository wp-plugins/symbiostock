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
function ss_do_cron($maxload=20) {
	if (!$maxload) {
		$maxload = $GLOBALS['currSS']->ss_maxload;
	}

	do_action('ss_cron_start',$maxload);

	// Failsafe - prevent attack
	if (get_option('ss_cron_last') && (time()-get_option('ss_cron_last') < 5)) exit();
	update_option( 'ss_cron_last', time());

	$ftpcronning = get_option('ss_image_cronning');
	$dlurlcronning = get_option('ss_dlurl_cronning');
	$thumbregencronning = get_option('ss_thumb_regen_cronning');
	$cleandircronning = get_option('ss_cleandir_cronning');

	if ($ftpcronning == 0) $runftp = 1;
	elseif ((time()-$ftpcronning) > 3600) $runftp = 1;	// In case it did not finish, restart process

	if ((time()-$dlurlcronning) > 600) $rundlurl = 1;	// every 10 minutes

	if ((time()-$cleandircronning) > 86400) $runcleandir = 1;	// every day

	if ($thumbregencronning == 0) $runthumbregen = 1;
	elseif ((time()-$thumbregencronning) > 3600) $runthumbregen = 1;	// In case it did not finish, restart process

	if (isset($runftp)) {
		$loadrate = 1;
		update_option( 'ss_image_cronning', time());
		$numimagesparsed = ss_process_ftp(10);
		$maxload -= ceil($numimagesparsed*$loadrate);
		print $numimagesparsed.'_images|';
		update_option( 'ss_image_cronning', 0);
	} 
	if (isset($runthumbregen)) {
		$loadrate = 1;
		update_option( 'ss_thumb_regen_cronning', time());
		$maxload -= ceil(ss_update_thumbnails(round($maxload*(1/$loadrate)))*$loadrate);
		update_option( 'ss_thumb_regen_cronning', 0);
	}
	if (isset($rundlurl)) {
		update_option( 'ss_dlurl_cronning', time());
		ss_update_dlspecs();
	}

	if (isset($runcleandir)) {
		if ($maxload >= 1) {
			update_option( 'ss_cleandir_cronning', time());
//			ss_clean_dir();
			$maxload -= 1;
		}
	}

	do_action('ss_cron_end',$maxload);

	exit();
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
	if ($regenkey = get_option('ss_regen_thumbnails')) {
		require_once( trailingslashit(ABSPATH) . 'wp-admin/includes/image.php' );
		$myposts = $GLOBALS['currSS']->get_all_products();

		foreach ( $myposts as $currpost ) {
			if ($i >= $limit) break;
			if (get_post_meta($currpost, 'ss_last_thumbnail_regen', true) < $regenkey) {
				ss_redo_thumbnails($currpost);
				$i++;
			}
		}
		if (($limit > 0) && !$i) update_option('ss_regen_thumbnails', 0);
	}
	print $i.'_thumbnails|';
	return $i;
}

// Update all download urls to match licenses in the case of manual additions and removals
// Update all download limits to match global settings
function ss_update_dlspecs() {
	$args = array('post_status' => 'any', 'post_type' => 'product_variation', 'posts_per_page'=> 9999999999999999,'orderby' => 'rand','fields' => 'ids');
	$myposts = get_posts($args);
	$i = 0;
	foreach ( $myposts as $currvariation ) {
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
		$dlimit = get_post_meta($currvariation, '_download_limit', true);
		$dexpiry = get_post_meta($currvariation, '_download_expiry', true);

		$currdlimit = get_option( 'ss_download_limit');
		$currdexpiry = get_option( 'ss_download_expiry');

		if ($dlimit != $currdlimit) {
			update_post_meta($currvariation, '_download_limit',$currdlimit);
			$i++;
		}
		if ($dexpiry != $currdexpiry) {
			update_post_meta($currvariation, '_download_expiry',$currdexpiry);
			$i++;
		}
	}
	print $i.'_dlspecs';
}

?>