<?php

/**
 * Plugin Name:       WooBiz
 * Plugin URI:        https://github.com/alexandrosraikos/woobiz
 * Description:       Ενσωματώστε το WooCommerce με την αποθήκη σας Biz Courier.
 * Version:           1.0.0
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * WC requires at least: 5.2.2
 * WC tested up to: 5.2.2
 * Author:            Αλέξανδρος Ράικος
 * Author URI:        https://www.araikos.gr/
 * License:           GNU General Public License v3.0
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       woobiz
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
define('WOOBIZ_VERSION', '1.0.0');

// Check for active WooCommerce.
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-woobiz-activator.php
	 */
	function activate_woobiz()
	{
		require_once plugin_dir_path(__FILE__) . 'includes/class-woobiz-activator.php';
		WooBiz_Activator::activate();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-woobiz-deactivator.php
	 */
	function deactivate_woobiz()
	{
		require_once plugin_dir_path(__FILE__) . 'includes/class-woobiz-deactivator.php';
		WooBiz_Deactivator::deactivate();
	}

	register_activation_hook(__FILE__, 'activate_woobiz');
	register_deactivation_hook(__FILE__, 'deactivate_woobiz');

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require plugin_dir_path(__FILE__) . 'includes/class-woobiz.php';

	/**
	 * Begins execution of the plugin.
	 *
	 * Since everything within the plugin is registered via hooks,
	 * then kicking off the plugin from this point in the file does
	 * not affect the page life cycle.
	 *
	 * @since    1.0.0
	 */
	function run_woobiz()
	{

		$plugin = new WooBiz();
		$plugin->run();
	}
	run_woobiz();
}
