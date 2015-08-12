<?php
/**
 * Plugin Name: Symbiostock
 * Plugin URI: http://www.symbiostock.org
 * Description: Symbiostock allows artists, illustrators and photographers to sell their photographs, vectors and stock images online quickly and easily.
 * Version: 1.3.1
 * Author: Robin Murarka
 * Author URI: http://www.symbiostock.org
 * Text Domain: ss
 * License: GPLv2
 */

$currSS = new ssHelper();	// Initiate helper class - will provide Symbiostock utilities throughout and initiate global constants

add_action( 'woocommerce_init', 'ss_go');	// Make sure we run symbiostock only after woocommerce is loaded
function ss_go() {
	global $currSS;

	do_action( 'ss_before_dependencies_check' );

	// Check to make sure environment is sound - if so, proceed loading Symbiostock
	if (!$currSS->missingdependencies) {

		//check for processor
		if ((isset($_GET['ss_c']) && ($_GET['ss_c'] == get_option('_cron_code'))) || (isset($_POST['ss_c']) && ($_POST['ss_c'] == get_option('_cron_code')))) define('ss_cron', 1);

		//check for download
		if (isset( $_GET['download_file'] ) && isset( $_GET['order'] ) && isset( $_GET['email'] )) define('ss_download', 1);

		do_action( 'ss_before_includes_check' );

		if (is_admin() || defined('ss_cron') || defined('ss_download')) {
			@set_time_limit(600);

			do_action( 'ss_start_core_includes' );

			require_once($currSS->ss_rootdir.'symbiostock-init.php' );	// Initialize Symbiostock for first load
			require_once($currSS->ss_rootdir.'admin-woo-download-settings-page.php' );	//Change download settings page
			require_once($currSS->ss_rootdir.'admin-woo-product-edit-page.php' );	//Change product edit interface
			require_once($currSS->ss_rootdir.'admin-global.php' );	// Change global interface look
			require_once($currSS->ss_rootdir.'admin-woo-attributes-licenses.php' );	// Change global attributes form
			require_once($currSS->ss_rootdir.'admin-settings.php' );	// Add global settings
			require_once($currSS->ss_rootdir.'tools-product-variations.php' );	// Add tools for manipulating product variations
			require_once($currSS->ss_rootdir.'tools-imagemanipulation.php' );	// Add tools for manipulating images
			require_once($currSS->ss_rootdir.'tools-ftp.php' );	// Add tools for manipulating images
			require_once($currSS->ss_rootdir.'tools-queries.php' ); // Add tools for querying database

			do_action('ss_end_core_includes');
		}
		if (defined('ss_cron') && isset($_GET['c'])) {
			require_once($currSS->ss_rootdir.'tools-cron.php' );	// Run processor
		}
		if (defined('ss_download')) {
			require_once($currSS->ss_rootdir.'tools-manage-download.php' );	// Hiijack 'Redirect only' download method for Symbiostock
		}

		do_action( 'ss_public_includes' );

		// Front end
		require_once($currSS->ss_rootdir.'front-product-page.php' );	// Add details on license selection

		// Search supercharger
		require_once($currSS->ss_rootdir.'tools-search.php' );

		// SS Ping
		require_once($currSS->ss_rootdir.'front-ping.php' );	// Provide pingback for SS

		do_action( 'ss_end_core_init' );
	}

	do_action( 'ss_init' );
}

class ssHelper {
	function ssHelper() {
		$this->disable_direct_access();
		$this->check_settings();

		$curr = wp_upload_dir();
		$this->ss_upload_dir = trailingslashit($curr['basedir']);

		// Initiation of globally used variables
		$this->ss_rootdir = trailingslashit(plugin_dir_path(__FILE__));
		$this->ss_assets_dir = $this->ss_rootdir . 'assets/';
		$this->ss_media_dir = trailingslashit(ABSPATH).'ss_media/';
		$this->ss_tmp_dir = $this->ss_media_dir.'tmp/';
		$this->ss_media_upload_dir = $this->ss_media_dir . 'new/';
		$this->ss_web_plugin_dir = trailingslashit(plugins_url('symbiostock'));
		$this->ss_web_assets_dir = $this->ss_web_plugin_dir . 'assets/';
		$this->ss_exiftool = $this->ss_rootdir . 'exiftool/exiftool';
		$this->ss_watermark_loc_old = $this->ss_rootdir . 'assets/watermark.png';
		$this->ss_watermark_loc = $this->ss_upload_dir . 'watermark.png';
		$this->ss_default_watermark_loc = $this->ss_rootdir . 'assets/ss_watermark.png';
		$this->ss_media_replace_prefix = '_ssrep_';
		$this->ss_media_failed_prefix = '_ss_failed_';
		$this->ss_canupload = 1;
		if (get_option('_cron_code')) $this->ss_cron_loc = site_url().'/?c=1&ss_c='.get_option('_cron_code');

		// required to prevent imagick from crashing when ratio is so lopsided that it makes one dimension 0 - expecting that 100:1 is a safe minimum
		$this->ss_minimum_img_scale = 100;

		// In the case an item is sold that is not raster and doesn't have x/y limits on size
		$this->ss_fallback_imagesize = 6000;

		// Maximum number of images to process during cron job
		$this->ss_maxload = 5;

		$this->userid = 1;
		$this->siteid = 1;

		do_action( 'ss_helper_init' );
	}

	// End scripting if accessed directly
	function disable_direct_access() {
		do_action( 'ss_direct_access_check' );

		if (!defined('ABSPATH')) exit(); 
	}

	// End scripting if required environment is not found
	function check_settings() {
		do_action( 'ss_check_settings_start' );

		$this->missingdependencies = 0;

		// Check for Woocommerce
		include_once(ABSPATH.'wp-admin/includes/plugin.php'); // Required for 'is_plugin_active' function (not included automatically outside admin panel)
		if (!is_plugin_active('woocommerce/woocommerce.php')) {
			$this->missingdependencies = 1;
			$this->error_notice("nowoo");
		}

		// Check for Imagick
		if(!extension_loaded('imagick') && !get_option('ss_ignoreimagick') && !isset($_GET['ss_ignoreimagick'])) {
			$this->error_notice("noimagick");
		} elseif (!$this->missingdependencies && !get_option('ss_ignorequickguide') && !isset($_GET['ss_ignorequickguide'])) {
			$this->error_notice("quickguide");
		}

		do_action( 'ss_check_settings_end' );
	}

	function update_notice($err="received") {
		add_action('admin_notices',array($this,'admin_notice_'.$err));
	}

	// All the update notice functions
	function admin_notice_received() {
		?>
		<div class="updated">
			<p><?php print __('Your changes have been saved.','ss'); ?></p>
		</div>
		<?php
	}

	function admin_notice_new_media() {
		?>
		<div class="updated">
			<p><?php print __('Your media has been uploaded - a new product will show up once the maintenance process gets to it.','ss'); ?></p>
		</div>
		<?php
	}

	function admin_notice_updated_media() {
		?>
		<div class="updated">
			<p><?php print __('Your media has been uploaded - the changes will show once the maintenance process gets to it. Metadata will NOT overwrite the current product data.','ss'); ?></p>
		</div>
		<?php
	}

	function admin_notice_unknown() {
		?>
		<div class="updated">
			<p><?php print __('An update occurred. No clue why.','ss'); ?></p>
		</div>
		<?php
	}

	function error_notice($err="unknown") {
		add_action('admin_notices',array($this,'error_notice_'.$err));
	}

	// All the error functions - must be explicitly coded for use with 'add_action' function/filter
	function error_notice_nowoo() {
		?>
		<div class="error">
			<p><?php print __('Symbiostock requires WooCommerce 2.3.13 to run. Please install <A href="https://downloads.wordpress.org/plugin/woocommerce.2.3.13.zip" target="_blank">WooCommerce 2.3.13</a>.','ss'); ?></p>
		</div>
		<?php
	}

	function error_notice_noimagick() {
		?>
		<div class="update-nag">
			<p><?php print __('Symbiostock strongly recommends the Imagick PHP extension for use. Please install or enable Imagick. Click <a href="'.admin_url( 'edit.php?post_type=product&ss_ignoreimagick=1').'">here</a> to ignore this warning and continue using the limited GD library for JPEGs only.','ss'); ?></p>
		</div>
		<?php
	}

	function error_notice_quickguide() {
		?>
		<div class="update-nag">
			<p><?php print __('Check out our <a href="http://www.symbiostock.org/docs/3-minute-guide-to-launching-your-store/" target="_blank">3 minute getting started guide</a> to jump right into using Symbiostock. Click <a href="'.admin_url( 'edit.php?post_type=product&ss_ignorequickguide=1').'">here</a> to hide this notice.','ss'); ?></p>
		</div>
		<?php
	}

	function error_notice_unknown() {
		?>
		<div class="error">
			<p><?php print __('An error occurred. No clue why.','ss'); ?></p>
		</div>
		<?php
	}

	function randString($length='') {
		if (!$length) $length = mt_rand(15, 20);
		$charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	    $str = '';
	    $count = strlen($charset);
	    while ($length--) {
	        $str .= $charset[mt_rand(0, $count-1)];
	    }
	    return $str;
	}

	function ss_refresh_product($productid) {	// must stay WITHIN class because WC_Product_Variable needs to be within class
		wc_delete_product_transients($productid);
		WC_Product_Variable::sync($productid);
		WC_Product_Variable::variable_product_sync($productid);
		WC_Product_Variable::sync_stock_status($productid);
	}

	function simplify() {
		if (!get_option('ss_simplify_interface')) return false;
		return true;
	}
}

?>