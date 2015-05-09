<?php

$currSS->disable_direct_access();

function ss_resize_sold_image($fileurl,$width,$height,$noupscale=1) {
	if (!$width) $width = 9999999999999;
	if (!$height) $height = 9999999999999;

	$image = new Imagick();
	$image->setResolution(1200,1200);
	if (!$image->readImage($fileurl)) return false;

	$iWidth = $image->getImageWidth();
	$iHeight = $image->getImageHeight();
	if ($iWidth/$iHeight > $width/$height) {
		if (!($noupscale && ($width > $iWidth))) {
			if ($width < $GLOBALS['currSS']->ss_minimum_img_scale) $width = $GLOBALS['currSS']->ss_minimum_img_scale;
			$image->resizeImage($width, 0, Imagick::FILTER_LANCZOS,1);
		}
	}
	else {
		if (!($noupscale && ($height > $iHeight))) {
			if ($height < $GLOBALS['currSS']->ss_minimum_img_scale) $height = $GLOBALS['currSS']->ss_minimum_img_scale;
			$image->resizeImage(0, $height, Imagick::FILTER_LANCZOS,1);
		}
	}

	$image->setImageFormat("jpg");
	return $image;
}

function ss_make_watermark($fileurl) {
	$curr = get_option('shop_single_image_size');
	$width = $curr['width'];
	$height = $curr['height'];
	$crop = $curr['crop'];

	$image = new Imagick();
	$image->setResolution(300,300);
	if (!$image->readImage($fileurl)) return false;
 
	$watermark = new Imagick();
	$watermark->setResolution(300,300);
	if (!$watermark->readImage($GLOBALS['currSS']->ss_watermark_loc)) return false;

	// Ensure width and height of target product image is not too small
	if ($width < $GLOBALS['currSS']->ss_minimum_img_scale) $width = $GLOBALS['currSS']->ss_minimum_img_scale;
	if ($height < $GLOBALS['currSS']->ss_minimum_img_scale) $height = $GLOBALS['currSS']->ss_minimum_img_scale;

	// Resize image for product image
	if (!$crop) $image->thumbnailImage($width, $height, true);
	else $image->cropThumbnailImage($width, $height);

	$iWidth = $image->getImageWidth();
	$iHeight = $image->getImageHeight();

	// get watermark box
	$boxpercent = get_option('ss_watermarkpercent');
	if ($boxpercent > 100) $boxpercent = 100;
	if ($boxpercent < 1) $boxpercent = 1;
	$twidth = round($boxpercent*$iWidth/100);
	$theight = round($boxpercent*$iHeight/100);

	// Fit watermark in that box
	$wWidth = $watermark->getImageWidth();
	$wHeight = $watermark->getImageHeight();

	if ($wWidth/$wHeight > $twidth/$theight) {
		if ($twidth < $GLOBALS['currSS']->ss_minimum_img_scale) $twidth = $GLOBALS['currSS']->ss_minimum_img_scale;
		$watermark->scaleImage($twidth, 0);
	}
	else {
		if ($theight < $GLOBALS['currSS']->ss_minimum_img_scale) $theight = $GLOBALS['currSS']->ss_minimum_img_scale;
		$watermark->scaleImage(0, $theight);
	}

	$wWidth = $watermark->getImageWidth();
	$wHeight = $watermark->getImageHeight();

	// calculate the position
	$x = ($iWidth - $wWidth) / 2;
	$y = ($iHeight - $wHeight) / 2;

	// Set the colorspace to the same value
	$image->setImageColorspace($watermark->getImageColorspace());

	$image->compositeImage($watermark, imagick::COMPOSITE_OVER, $x, $y);

	$draw = new ImagickDraw();
	$draw->setResolution(300,300);
    $draw->setFillColor('white');
    $draw->setStrokeWidth(1);
    $draw->setFillOpacity(.4);

	$x = ($iWidth/2)*.9;
	$bufferx = $x-$x*.9;
	$buffery = $y-$y*.9;
	$cornerbuffer = 0;
    $draw->line($cornerbuffer, $cornerbuffer, $x-$bufferx, $y-$buffery);
    $draw->line($iWidth-$cornerbuffer, $cornerbuffer, $iWidth-$x+$bufferx, $y-$buffery);
    $draw->line($cornerbuffer, $iHeight-$cornerbuffer, $x-$bufferx, $iHeight-$y+$buffery);
    $draw->line($iWidth-$cornerbuffer, $iHeight-$cornerbuffer, $iWidth-$x+$bufferx, $iHeight-$y+$buffery);

    $image->drawImage($draw);

	$draw = new ImagickDraw();
	$draw->setResolution(300,300);
    $draw->setFillColor('black');
    $draw->setStrokeWidth(1);
    $draw->setFillOpacity(.1);

	$x = ($iWidth/2)*.9;
	$bufferx = $x-$x*.9;
	$buffery = $y-$y*.9;
	$cornerbuffer = 0;
	$blackbuff = 1;
    $draw->line($cornerbuffer+$blackbuff, $cornerbuffer, $x-$bufferx+$blackbuff, $y-$buffery);
    $draw->line($iWidth-$cornerbuffer-$blackbuff, $cornerbuffer, $iWidth-$x+$bufferx-$blackbuff, $y-$buffery);
    $draw->line($cornerbuffer+$blackbuff, $iHeight-$cornerbuffer, $x-$bufferx+$blackbuff, $iHeight-$y+$buffery);
    $draw->line($iWidth-$cornerbuffer-$blackbuff, $iHeight-$cornerbuffer, $iWidth-$x+$bufferx-$blackbuff, $iHeight-$y+$buffery);

    $image->drawImage($draw);

	$image->setImageFormat("jpg");

	return $image;
}

function ss_attach_images($fileurl, $postid,$title=''){
    require_once(ABSPATH . '/wp-admin/includes/file.php');
    require_once(ABSPATH . '/wp-admin/includes/media.php');
    require_once(ABSPATH . '/wp-admin/includes/image.php');
    require_once(ABSPATH . '/wp-includes/pluggable.php');

	$tmpfilename = $postid."_".microtime().".jpg";
	if ($image = ss_make_watermark($fileurl)) {
		$image->writeImage($GLOBALS['currSS']->ss_tmp_dir.$tmpfilename);
		$image->clear();

		$image = $GLOBALS['currSS']->ss_tmp_dir.$tmpfilename;

		if (!$title) $title = get_the_title($postid);

		if (!$title) $title = pathinfo($fileurl,PATHINFO_FILENAME);
		$title .= '.jpg';

		$array = array( //array to mimic $_FILES
	            'name' => $title,
	            'type' => 'image/jpeg', //yes, thats sloppy, see my text further down on this topic
	            'tmp_name' => $image, //this field passes the actual path to the image
	            'error' => 0, //normally, this is used to store an error, should the upload fail. but since this isnt actually an instance of $_FILES we can default it to zero here
	            'size' => filesize($image) //returns image filesize in bytes
		);

		$attachmentid = media_handle_sideload($array, $postid); //the actual image processing, that is, move to upload directory, generate thumbnails and image sizes and writing into the database happens here
		set_post_thumbnail($postid, $attachmentid);
	}
}

function ss_redo_thumbnails($postid) {
	require_once( trailingslashit(ABSPATH) . 'wp-admin/includes/image.php' );
	$post_thumbnail_id = get_post_thumbnail_id( $postid );
	$fullsizepath = get_attached_file( $post_thumbnail_id );

	if (!$fullsizepath) {
		$fileurl = $GLOBALS['currSS']->ss_media_dir.get_post_meta($postid, 'ss_media_filename', true);
		ss_attach_images($fileurl,$postid);
		update_post_meta($postid, 'ss_last_thumbnail_regen', time());
		return;
	}

	if ($image = ss_make_watermark($GLOBALS['currSS']->ss_media_dir.get_post_meta($postid, 'ss_media_filename', true))) {
		$image->writeImage($fullsizepath);
		$image->clear();
		$attach_data = wp_generate_attachment_metadata( $post_thumbnail_id, $fullsizepath );
		wp_update_attachment_metadata( $post_thumbnail_id,  $attach_data );
		update_post_meta($postid, 'ss_last_thumbnail_regen', time());
	}
}

?>