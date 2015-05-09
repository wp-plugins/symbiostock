<?php

$currSS->disable_direct_access();

function ss_update_media_info($post_id,$fileloc) {
	if ($type = ss_get_media_type($fileloc)) {

		$finalfilename = ss_getmedianame($post_id).'.'.pathinfo($fileloc,PATHINFO_EXTENSION);
		$imgu = getimagesize($fileloc);
	
		// Update post with image information
		update_post_meta($post_id, 'ss_media_filename', $finalfilename);
		update_post_meta($post_id, 'ss_media_width', $imgu[0]);
		update_post_meta($post_id, 'ss_media_height', $imgu[1]);
		update_post_meta($post_id, '_ss_mediatype', $type);

		return $finalfilename;
	}
	return false;
}

function ss_file_failed($fileloc,$fname) {
	rename($fileloc, $GLOBALS['currSS']->ss_media_upload_dir.$GLOBALS['currSS']->ss_media_failed_prefix.$fname);
}

function ss_process_ftp($limit=5) {
	$i = 0;
	$handle = opendir($GLOBALS['currSS']->ss_media_upload_dir);
	while (($fname = readdir($handle)) && ($i < $limit)) {
		$fileloc = $GLOBALS['currSS']->ss_media_upload_dir.$fname;

		// Check if file is just dots
		if (!trim(str_replace('.','',$fname))) continue;

		// Check if file has previously failed
		if (strpos($fname,$GLOBALS['currSS']->ss_media_failed_prefix) === 0) continue;

		// Ensure file is not still being uploaded
		if ((time() - filemtime($fileloc)) < 45) continue;

		// check if file is being updated rather than added
		if (strpos($fname,$GLOBALS['currSS']->ss_media_replace_prefix) === 0) {
			$postid = pathinfo($fname,PATHINFO_FILENAME);
			$postid = str_replace($GLOBALS['currSS']->ss_media_replace_prefix,'',$postid);
			if (get_post_status($postid)) {
				if ($finalfilename = ss_update_media_info($postid,$fileloc)) {
					rename ($fileloc, $GLOBALS['currSS']->ss_media_dir.$finalfilename);
					ss_redo_thumbnails($postid);
					$i++;
					continue;
				} else {
					ss_file_failed($fileloc,$fname);
				}
			}
		}

		if (ss_get_media_type($fileloc)) {
			if ($metadata = ss_get_image_metadata($fileloc)) {
				$title = $metadata->Title;
				$description = $metadata->Description;
				$keywords = $metadata->Keywords;
			} else {
				$title = '';
				$description = '';
				$keywords = '';
			}

			$post_id = ss_create_product($title,$description,$keywords,$fileloc);
			ss_add_all_licenses_to_product($post_id);

			$finalfilename = ss_update_media_info($post_id,$fileloc);

			$licenses = 0;
			if (ss_apply_default_licenses_to_product($post_id)) $licenses = 1;

			if (get_post_meta($post_id, 'ss_media_width', true) && $metadata->Title && $licenses && get_option('ss_auto_publish')) ss_publish_post($post_id);
			rename ($fileloc, $GLOBALS['currSS']->ss_media_dir.$finalfilename);
			ss_attach_images($GLOBALS['currSS']->ss_media_dir.$finalfilename, $post_id,$metadata->Title);
			$i++;
		} else {
			ss_file_failed($fileloc,$fname);
		}
	}
	return $i;
}

function ss_get_mime_type($fileloc) {
	$finfo = finfo_open(FILEINFO_MIME_TYPE);
	$filetype = finfo_file($finfo, $fileloc);
	finfo_close($finfo);
	return $filetype;
}

function ss_get_media_type($fileloc) {
	$filetype = ss_get_mime_type($fileloc);

	if ($filetype == 'image/svg+xml' || $filetype=='application/postscript' || $filetype=='application/octet-stream') $type = 'vector';
	elseif (strpos($filetype,'image') !== false) $type = 'raster';
	if (isset($type)) return $type;
	return false;
}

function ss_publish_post($postid) {
	$my_post = array(
	      'ID'           => $postid,
	      'post_status'   => 'publish'
	);
	wp_update_post( $my_post );
}

function ss_get_image_metadata($file_name) {
        $command = $GLOBALS['currSS']->ss_exiftool . ' -json ';
        $command .= '"'.$file_name.'"';
        $results = shell_exec($command);
        if ($results && is_string($results)) {
            $meta_object = json_decode($results, false);
            return $meta_object[0];
        } else {
            return 0;
        }
}

function ss_create_product($title,$description,$tags,$fileloc,$status='draft',$postname='') {
	if (!$title && !$description && !$tags) $title = pathinfo($fileloc,PATHINFO_BASENAME);
	$post = array(
		'post_author' => $GLOBALS['currSS']->userid,
		'post_content' => $description,
		'post_status' => $status,
		'post_title' => $title,
		'post_parent' => '',
		'post_type' => 'product',
	);

	if ($postname) $post['post_name'] = $postname;

	//Create post
	$post_id = wp_insert_post( $post);

	wp_set_object_terms($post_id, $tags, 'product_tag');
	wp_set_object_terms($post_id, 'variable', 'product_type');

	update_post_meta($post_id, '_visibility', 'visible');

	return $post_id;
}

function ss_getmedianame($postid) {
	return $GLOBALS['currSS']->siteid.'_'.$GLOBALS['currSS']->userid.'_'.$postid;
}

?>