<?php

$currSS->disable_direct_access();

add_action( 'woocommerce_download_file_redirect','ss_manage_file_download',1,2);

function ss_manage_file_download($file_path, $filename = '') {

	do_action('ss_start_download_process',$file_path,$filename);

	$filevars = explode('-',$file_path);

	$p = $filevars[0];
	$v = $filevars[1];
	$e = $filevars[2];

	$fileloc = $GLOBALS['currSS']->ss_media_dir.get_post_meta($p, 'ss_media_filename', true);
	$currlicense = get_post_meta($v, 'attribute_pa_license', true);
	$currlicense = get_term_by('slug',$currlicense,'pa_license');
	$licenseoptions = get_option( "taxonomy_".$currlicense->term_id);

	$filetype = get_post_meta($p, '_ss_mediatype', true);

	if (($filetype == 'unknown') || !$licenseoptions['ss_license_output_vector']) {
		$width = $licenseoptions['ss_license_max_x'];
		$height = $licenseoptions['ss_license_max_y'];

		if (!$width && !$height && (ss_get_media_type($fileloc) != 'raster')) {
			$width = $GLOBALS['currSS']->ss_fallback_imagesize;
			$height = $GLOBALS['currSS']->ss_fallback_imagesize;
		}

		$noupscale = 1;
		if ($filetype == 'vector') $noupscale = 0;

		if ($width || $height) $image = ss_resize_sold_image($fileloc,$width,$height,$noupscale);
		if (isset($image)) {

			do_action('ss_before_processed_download_process',$image);

			$image->setImageCompressionQuality(100);
//			header('Content-Type: image/'.$image->getImageFormat());
			header('Content-disposition: attachment; filename="download.jpg"');
			header('Content-Type: application/octet-stream');
			echo apply_filters('ss_send_processed_download',$image);

			do_action('ss_after_processed_download_process',$image);

			exit();
		}
	}

	do_action('ss_before_unprocessed_download_process',$fileloc);

	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$filetype = finfo_file($finfo, $fileloc);
	finfo_close($finfo);

//	header("Content-Type: ".$filetype);
	header('Content-disposition: attachment; filename="download.'.$e.'"');
	header('Content-Type: application/octet-stream');
	readfile(apply_filters('ss_send_unprocessed_download',$fileloc));

	do_action('ss_after_unprocessed_download_process',$fileloc);

	exit(); // cutoff processing to prevent built in woo method of downloading
}

?>