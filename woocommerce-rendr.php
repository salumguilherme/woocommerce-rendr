<?php
	/*
	Plugin Name: Rendr Delivery for Woocommerce
	Plugin URI: https://rendr.delivery
	Description: Offer Rendr Delivery to your customers.
	Version: 1.1
	Author: Guilherme Salum
	Author URI: https://fivecreative.com.au
	Requires at least: 5.2
    Requires PHP:      5.6
	Text Domain:       wcrendr
	License:           GPL v2 or later
	License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
	*/

	if (!defined('ABSPATH') || !defined('WPINC')) {
		exit;
	}

	// Constants
	define('WCRENDR_VERSION', '1.1');
	define('WCRENDR_DIR', rtrim(plugin_dir_path(__FILE__), "/"));
	define('WCRENDR_URL', rtrim(plugin_dir_url(__FILE__), "/"));

	require WCRENDR_DIR.'/includes/updater/plugin-update-checker.php';
	require WCRENDR_DIR.'/vendor/autoload.php';
	require WCRENDR_DIR.'/includes/plugin.php';

	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker('https://github.com/salumguilherme/woocommerce-rendr/',__FILE__, 'woocommerce-rendr');
	$myUpdateChecker->setBranch('main');

	if(!function_exists('WcRendr')) {
		function WcRendr() {
			return \WcRendr\Plugin::instance();
		}
	}

	WcRendr();

?>