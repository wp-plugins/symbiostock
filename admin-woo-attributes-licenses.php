<?php

$currSS->disable_direct_access();

// Add term page
function ss_licenses_add_new_meta_field() {
	$GLOBALS['currSS']->issspage = 1;
	// this will add the custom meta field to the add new term page
	?>
	<div class="form-field">
		<label for="term_meta[ss_license_raster]"><?php _e( 'Available for raster images', 'ss' ); ?></label>
		<input type="radio" name="term_meta[ss_license_raster]" id="term_meta[ss_license_raster]" value="1">Yes
		<input type="radio" name="term_meta[ss_license_raster]" id="term_meta[ss_license_raster]" value="0">No
		<p class="description"><?php _e( 'Will be enabled for raster images (JPEG, PNG etc.) by default.','ss' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta[ss_license_vector]"><?php _e( 'Available for vectors', 'ss' ); ?></label>
		<input type="radio" name="term_meta[ss_license_vector]" id="term_meta[ss_license_vector]" value="1">Yes
		<input type="radio" name="term_meta[ss_license_vector]" id="term_meta[ss_license_vector]" value="0">No
		<p class="description"><?php _e( 'Will be enabled for vectors by default.','ss' ); ?></p>
	</div>
<?php /*
	<div class="form-field">
		<label for="term_meta[ss_license_video]"><?php _e( 'Available for videos', 'ss' ); ?></label>
		<input type="radio" name="term_meta[ss_license_video]" id="term_meta[ss_license_video]" value="1">Yes
		<input type="radio" name="term_meta[ss_license_video]" id="term_meta[ss_license_video]" value="0">No
		<p class="description"><?php _e( 'Will be enabled for videos by default.','ss' ); ?></p>
	</div>
*/ ?>
	<div class="form-field">
		<label for="term_meta[ss_license_output_vector]"><?php _e( 'Sell unaltered', 'ss' ); ?></label>
		<input type="radio" name="term_meta[ss_license_output_vector]" id="term_meta[ss_license_output_vector]" value="1">Yes
		<input type="radio" name="term_meta[ss_license_output_vector]" id="term_meta[ss_license_output_vector]" value="0">No
		<p class="description"><?php _e( 'Download raw file. If not enabled, item will be converted to raster for this license. Useful for vector sales.','ss' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta[ss_license_update_licenses]"><?php _e( 'Update licenses?', 'ss' ); ?></label>
		<input type="checkbox" name="term_meta[ss_license_update_licenses]" id="term_meta[ss_license_update_licenses]" value="1">
		<p class="description"><?php _e( 'Check this if you would like all current products to have this license added based on the defaults.','ss' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta[ss_default_license_price]"><?php _e( 'Default price', 'ss' ); ?></label>
		<input type="text" name="term_meta[ss_license_default_price]" id="term_meta[ss_default_license_price]" value="">
		<p class="description"><?php _e( 'The default price media will be assigned for this license. Can be changed by editing individual products.','ss' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta[ss_license_max_x]"><?php _e( 'Maximum width', 'ss' ); ?></label>
		<input type="text" name="term_meta[ss_license_max_x]" id="term_meta[ss_license_max_x]" value="">
		<p class="description"><?php _e( 'The maximum width in pixels this license provides for. Not applicable if "sell unaltered" is checked. 0 for max.','ss' ); ?></p>
	</div>
	<div class="form-field">
		<label for="term_meta[ss_license_max_y]"><?php _e( 'Maximum height', 'ss' ); ?></label>
		<input type="text" name="term_meta[ss_license_max_y]" id="term_meta[ss_license_max_y]" value="">
		<p class="description"><?php _e( 'The maximum height in pixels this license provides for. Not applicable if "sell unaltered" is checked. 0 for max.','ss' ); ?></p>
	</div>
<?php if ($GLOBALS['currSS']->simplify()) { ?>
<style>
div.form-field.term-parent-wrap {
display:none;
}
</style>
<?php
	}
}
add_action( 'pa_license_add_form_fields', 'ss_licenses_add_new_meta_field', 10, 2 );

// Edit term page
function ss_licenses_edit_meta_field($term) {
	$GLOBALS['currSS']->issspage = 1;
 
	// put the term ID into a variable
	$t_id = $term->term_id;
 
	// retrieve the existing value(s) for this meta field. This returns an array
	$term_meta = get_option( "taxonomy_$t_id" ); ?>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_raster]"><?php _e( 'Available for raster images', 'ss' ); ?></label></th>
		<td>
		<input type="radio" name="term_meta[ss_license_raster]" id="term_meta[ss_license_raster]" value="1" <?php checked(esc_attr( $term_meta['ss_license_raster'] )); ?>>Yes
		<input type="radio" name="term_meta[ss_license_raster]" id="term_meta[ss_license_raster]" value="0" <?php checked(esc_attr( $term_meta['ss_license_raster'] ),0); ?>>No
			<p class="description"><?php _e( 'Will be enabled for raster images (JPEG, PNG etc.) by default.','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_vector]"><?php _e( 'Available for vectors', 'ss' ); ?></label></th>
		<td>
		<input type="radio" name="term_meta[ss_license_vector]" id="term_meta[ss_license_vector]" value="1" <?php checked(esc_attr( $term_meta['ss_license_vector'] )); ?>>Yes
		<input type="radio" name="term_meta[ss_license_vector]" id="term_meta[ss_license_vector]" value="0" <?php checked(esc_attr( $term_meta['ss_license_vector'] ),0); ?>>No
			<p class="description"><?php _e( 'Will be enabled for vectors by default.','ss' ); ?></p>
		</td>
	</tr>
<?php /*
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_video]"><?php _e( 'Available for videos', 'ss' ); ?></label></th>
		<td>
		<input type="radio" name="term_meta[ss_license_video]" id="term_meta[ss_license_video]" value="1" <?php checked(esc_attr( $term_meta['ss_license_video'] )); ?>>Yes
		<input type="radio" name="term_meta[ss_license_video]" id="term_meta[ss_license_video]" value="0" <?php checked(esc_attr( $term_meta['ss_license_video'] ),0); ?>>No
			<p class="description"><?php _e( 'Will be enabled for videos by default.','ss' ); ?></p>
		</td>
	</tr>
*/ ?>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_output_vector]"><?php _e( 'Sell unaltered', 'ss' ); ?></label></th>
		<td>
		<input type="radio" name="term_meta[ss_license_output_vector]" id="term_meta[ss_license_output_vector]" value="1" <?php checked(esc_attr( $term_meta['ss_license_output_vector'] )); ?>>Yes
		<input type="radio" name="term_meta[ss_license_output_vector]" id="term_meta[ss_license_output_vector]" value="0" <?php checked(esc_attr( $term_meta['ss_license_output_vector'] ),0); ?>>No
		<p class="description"><?php _e( 'Download raw file. If not enabled, item will be converted to raster for this license. Useful for vector sales.','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_update_licenses]"><?php _e( 'Update licenses?', 'ss' ); ?></label></th>
		<td>
			<input type="checkbox" name="term_meta[ss_license_update_licenses]" id="term_meta[ss_license_update_licenses]" value="1">
			<p class="description"><?php _e( 'Check this if you would like all current products to have this license added/removed based on the defaults (may take some time).','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_default_license_price]"><?php _e( 'Default price', 'ss' ); ?></label></th>
		<td>
			<input type="text" name="term_meta[ss_default_license_price]" id="term_meta[ss_default_license_price]" value="<?php echo esc_attr( $term_meta['ss_default_license_price'] ); ?>">
			<p class="description"><?php _e( 'The default price media will be assigned for this license. Can be changed by editing individual products.','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_update_prices]"><?php _e( 'Update prices?', 'ss' ); ?></label></th>
		<td>
			<input type="checkbox" name="term_meta[ss_license_update_prices]" id="term_meta[ss_license_update_prices]" value="1">
			<p class="description"><?php _e( 'Check this if you would like to update the price of all current products that have this license based on the defaults (may take some time).','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_max_x]"><?php _e( 'Maximum width', 'ss' ); ?></label></th>
		<td>
			<input type="text" name="term_meta[ss_license_max_x]" id="term_meta[ss_license_max_x]" value="<?php echo esc_attr( $term_meta['ss_license_max_x'] ); ?>">
			<p class="description"><?php _e( 'The maximum width in pixels this license provides for. Not applicable if "sell as vector" is checked. 0 for max.','ss' ); ?></p>
		</td>
	</tr>
	<tr class="form-field">
	<th scope="row" valign="top"><label for="term_meta[ss_license_max_y]"><?php _e( 'Maximum height', 'ss' ); ?></label></th>
		<td>
			<input type="text" name="term_meta[ss_license_max_y]" id="term_meta[ss_license_max_y]" value="<?php echo esc_attr( $term_meta['ss_license_max_y'] ); ?>">
			<p class="description"><?php _e( 'The maximum height in pixels this license provides for. Not applicable if "sell as vector" is checked. 0 for max.','ss' ); ?></p>
		</td>
	</tr>
<?php
}
add_action( 'pa_license_edit_form_fields', 'ss_licenses_edit_meta_field', 10, 2 );

function ss_save_licenses_custom_meta( $term_id ) {
	if ( isset( $_POST['term_meta'] ) ) {
		$t_id = $term_id;

		$term_meta = get_option( "taxonomy_$t_id" );
		$cat_keys = array_keys( $_POST['term_meta'] );
		foreach ( $cat_keys as $key ) {
			if ( isset ( $_POST['term_meta'][$key] ) ) {
				$term_meta[$key] = sanitize_text_field($_POST['term_meta'][$key]);
			}
		}
		// Save the option array.
		update_option( "taxonomy_$t_id", $term_meta );

		$term_meta = get_option( "taxonomy_$t_id" );
		$cat_keys = array_keys( $_POST['term_meta'] );
		foreach ( $cat_keys as $key ) {
			if ( isset ( $_POST['term_meta'][$key] ) ) {
				$term_meta[$key] = $_POST['term_meta'][$key];
				if ($key == 'ss_license_update_prices') {
					$args = array( 'post_type' => 'product_variation', 'posts_per_page'=> 99999999);
					$myposts = get_posts($args);
					foreach ( $myposts as $currvariation ) {
						if (get_post_meta( $currvariation->ID, 'attribute_pa_license', true ) == $_POST['slug']) {
						    update_post_meta($currvariation->ID, '_price', sanitize_text_field($_POST['term_meta']['ss_default_license_price']));
						    update_post_meta($currvariation->ID, '_regular_price', sanitize_text_field($_POST['term_meta']['ss_default_license_price']));
						}
					}
				}
				if ($key == 'ss_license_update_licenses') {
					$args = array( 'post_type' => 'product','post_status' => array('draft', 'publish'), 'posts_per_page'=> 99999999);
					$myp = get_posts($args);
					foreach ( $myp as $currproduct ) {
						$mediatypes = array('raster','vector','video');
						foreach ($mediatypes as $mediatype) {
							if (get_post_meta( $currproduct->ID, '_ss_mediatype', true ) == $mediatype) {
								$args = array( 'post_type' => 'product_variation', 'post_parent'=> $currproduct->ID, 'posts_per_page'=> 99999999);
								$myposts = get_posts($args);
								$found = 0;
								foreach ( $myposts as $currvariation ) {
									if (get_post_meta( $currvariation->ID, 'attribute_pa_license', true ) == $_POST['slug']) {
										if (!$_POST['term_meta']['ss_license_'.$mediatype]) {
											wp_delete_post($currvariation->ID);
											$GLOBALS['currSS']->ss_refresh_product($currproduct->ID);
										}
										$found = 1;
									}
								}
								if (!$found && $_POST['term_meta']['ss_license_'.$mediatype]) {
									$licensetoadd['slug'] = sanitize_text_field($_POST['slug']);
									$licensetoadd['price'] = sanitize_text_field($_POST['term_meta']['ss_default_license_price']);
									ss_add_license_as_variation($currproduct->ID,$licensetoadd);
								}
							}
						}
					}
				}
			}
		}
	}
	delete_transient( 'wc_products_onsale' );
}  
add_action( 'edited_pa_license', 'ss_save_licenses_custom_meta', 10, 2 );
add_action( 'create_pa_license', 'ss_save_licenses_custom_meta', 10, 2 );

?>