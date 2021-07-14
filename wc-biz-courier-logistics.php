<?php

/**
 * Plugin Name:       Biz Courier & Logistics for WooCommerce
 * Plugin URI:        https://github.com/alexandrosraikos/wc-biz-courie-logistics
 * Description:       Integrate your Biz Courier warehouse with WooCommerce.
 * Version:           1.0.1
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * WC requires at least: 5.2.2
 * WC tested up to: 5.2.2
 * Author:            Alexandros Raikos
 * Author URI:        https://www.araikos.gr/en/
 * License:           GNU General Public License v3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wc-biz-courier-logistics
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WC_BIZ_COURIER_LOGISTICS_VERSION', '1.0.0');

// Check for active WooCommerce.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/wc-biz-courier-logistics-activator.php
	 */
	function activate_wc_biz_courier_logistics()
	{
		require_once plugin_dir_path(__FILE__) . 'includes/wc-biz-courier-logistics-activator.php';
		WC_Biz_Courier_Logistics_Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/wc-biz-courier-logistics-deactivator.php
	 */
	function deactivate_wc_biz_courier_logistics()
	{
		require_once plugin_dir_path(__FILE__) . 'includes/wc-biz-courier-logistics-deactivator.php';
		WC_Biz_Courier_Logistics_Deactivator::deactivate();
	}

	register_activation_hook(__FILE__, 'activate_wc_biz_courier_logistics');
	register_deactivation_hook(__FILE__, 'deactivate_wc_biz_courier_logistics');

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path(__FILE__) . 'includes/wc-biz-courier-logistics.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_wc_biz_courier_logistics()
	{

		$plugin = new WC_Biz_Courier_Logistics();
		$plugin->run();
	}
	run_wc_biz_courier_logistics();
}
