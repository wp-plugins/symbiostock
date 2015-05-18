<?php

$currSS->disable_direct_access();

function ss_update_media_info($post_id,$fileloc) {
	if ($type = ss_get_media_type($fileloc)) {

		$finalfilename = strtolower(ss_getmedianame($post_id).'.'.pathinfo($fileloc,PATHINFO_EXTENSION));
		$imgu = getimagesize($fileloc);

		if (!$imgu[0] || !$imgu[1]) {
			if (file_exists($fileloc) && !is_dir($fileloc)) {
				$image = new Imagick($fileloc);
				$imgu[1] = $image->getImageHeight();
				$imgu[0] = $image->getImageWidth();
			}
		}

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

		if (isset($GLOBALS['currSS']->currfailed)) unset($GLOBALS['currSS']->currfailed);

		// Check if file is just dots
		if (!trim(str_replace('.','',$fname))) continue;

		// Check if file has previously failed
		if (strpos($fname,$GLOBALS['currSS']->ss_media_failed_prefix) === 0) continue;

		// Ensure file is not still being uploaded
		if ((time() - filemtime($fileloc)) < 45) continue;

		// Ensure file is not larger than 100 megs 
		if (filesize($fileloc) >= 100000000) {
			ss_file_failed($fileloc,$fname);
			continue;
		}

		do_action('ss_process_ftp_checks',$fileloc);

		if (isset($GLOBALS['currSS']->currfailed)) continue;

		// check if file is being updated rather than added
		if (strpos($fname,$GLOBALS['currSS']->ss_media_replace_prefix) === 0) {
			$postid = pathinfo($fname,PATHINFO_FILENAME);
			$postid = str_replace($GLOBALS['currSS']->ss_media_replace_prefix,'',$postid);
			if (get_post_status($postid)) {
				$oldfilename = $GLOBALS['currSS']->ss_media_dir.ss_get_post_meta($postid, 'ss_media_filename');
				if ($finalfilename = ss_update_media_info($postid,$fileloc)) {
					if (file_exists($oldfilename)) unlink($oldfilename);
					rename ($fileloc, $GLOBALS['currSS']->ss_media_dir.$finalfilename);
					ss_redo_thumbnails($postid);
					ss_write_image_metadata($postid);
					$i++;
					continue;
				} else {
					ss_file_failed($fileloc,$fname);
				}
			} else {
				ss_file_failed($fileloc,$fname);
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

			if (ss_get_post_meta($post_id, 'ss_media_width') && $metadata->Title && $licenses && ss_get_option('ss_auto_publish')) ss_publish_post($post_id);
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

function ss_really_clean_text($text) {
	$text = str_replace('<', ' <', $text);
	$text = str_replace('>', '> ', $text);
	$text = strip_tags($text);
	$text = html_entity_decode($text);
	$text = str_replace('"', ' ', $text);
	$text = preg_replace('/\s+/', ' ', $text);
	return trim($text);
}

add_action('woocommerce_process_product_meta','ss_write_image_metadata',11);
function ss_write_image_metadata($postid) {
		if (!($currpost = get_post($postid))) return;

		if (isset($_POST['_ss_update_metadata'])) {
			$tester = $_POST['_ss_update_metadata'];
		} else {
			$tester = ss_get_post_meta($postid, '_ss_update_metadata');
		}

		if ($tester == '1') $parse = 1;
		elseif (!$tester && ss_get_option('ss_default_update_metadata')) $parse = 1;

		if (!$parse) return;

		if (!($filename = ss_check_image_product($currpost->ID))) return;

		if (ss_get_mime_type($filename) != 'image/jpeg') return;

		$title = trim($currpost->post_title);
		$description = trim($currpost->post_content);
		if (trim($currpost->post_excerpt)) $description = trim($currpost->post_excerpt);

		$tags = wp_get_object_terms($currpost->ID, 'product_tag',array('fields' => 'names'));

		if (!$description) $description = $title;

		$title = ss_really_clean_text($title);
		$description = ss_really_clean_text($description);
		foreach ($tags as $key => $value) $tags[$key] = ss_really_clean_text($value);
		$tags = "-keywords=\"".implode("\" -keywords=\"",$tags)."\"";
		$subjects = str_replace("-keywords=","-subject=",$tags);

        $command = $GLOBALS['currSS']->ss_exiftool . ' -title="'.$title.'" -ObjectName="'.$title.'" -ImageDescription="'.$description.'" -Description="'.$description.'"  -Caption-Abstract="'.$description.'" '.$tags.' '.$subjects.' -overwrite_original ';
        $command .= '"'.$filename.'"';

        $results = shell_exec($command);
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
	update_post_meta($post_id, 'total_sales', '');

	return $post_id;
}

function ss_getmedianame($postid) {
	return $GLOBALS['currSS']->siteid.'_'.$GLOBALS['currSS']->userid.'_'.$postid;
}

?>