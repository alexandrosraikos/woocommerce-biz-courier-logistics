<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WC_Biz_Courier_Logistics
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WC_Biz_Courier_Logistics_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $WC_Biz_Courier_Logistics    The string used to uniquely identify this plugin.
	 */
	protected $WC_Biz_Courier_Logistics;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		$this->version = '1.3.2';
		$this->WC_Biz_Courier_Logistics = 'wc-biz-courier-logistics';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WC_Biz_Courier_Logistics_Loader. Orchestrates the hooks of the plugin.
	 * - WC_Biz_Courier_Logistics_i18n. Defines internationalization functionality.
	 * - WC_Biz_Courier_Logistics_Admin. Defines all hooks for the admin area.
	 * - WC_Biz_Courier_Logistics_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-biz-courier-logistics-public.php';

		$this->loader = new WC_Biz_Courier_Logistics_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WC_Biz_Courier_Logistics_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new WC_Biz_Courier_Logistics_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new WC_Biz_Courier_Logistics_Admin($this->get_WC_Biz_Courier_Logistics(), $this->get_version());

		$this->loader->add_action('init', $plugin_admin, 'biz_soap_extension_error');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_filter('plugin_action_links_wc-biz-courier-logistics/wc-biz-courier-logistics.php', $plugin_admin, 'biz_plugin_action_links');
		$this->loader->add_filter('plugin_row_meta', $plugin_admin, 'biz_plugin_row_meta', 10, 2);

		/**
		 *  Biz Courier Integration.
		 */
		$this->loader->add_action('woocommerce_integrations_init', $plugin_admin, 'biz_integration');
		$this->loader->add_filter('woocommerce_integrations', $plugin_admin, 'add_biz_integration');
		$this->loader->add_filter('admin_notices', $plugin_admin, 'biz_settings_notice');

		/** 
		 *  Custom Stock Synchronization.
		 */
		$this->loader->add_action('woocommerce_product_options_inventory_product_data', $plugin_admin, 'biz_product_inventory_options');
		$this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'biz_save_product_inventory_options',10,1);
		$this->loader->add_action('woocommerce_variation_options', $plugin_admin, 'biz_variation_inventory_options',10,3);
		$this->loader->add_action('woocommerce_save_product_variation', $plugin_admin, 'biz_save_variation_inventory_options',10,2);
		$this->loader->add_action('wp_ajax_biz_stock_sync', $plugin_admin, 'biz_stock_sync_handler');
		$this->loader->add_action('manage_posts_extra_tablenav', $plugin_admin, 'add_biz_stock_sync_all_button', 20, 1);
		$this->loader->add_filter('manage_edit-product_columns', $plugin_admin, 'add_biz_stock_sync_indicator_column');
		$this->loader->add_action('manage_product_posts_custom_column', $plugin_admin, 'biz_stock_sync_indicator_column', 10, 2);

		/** 
		 *  Biz Courier Shipping Method.
		 */
		$this->loader->add_action('woocommerce_shipping_init', $plugin_admin, 'biz_shipping_method');
		$this->loader->add_filter('woocommerce_shipping_methods', $plugin_admin, 'add_biz_shipping_method');

		/**
		 *  Order and shipment interactivity.
		 */
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_biz_shipment_meta_box');
		$this->loader->add_filter('manage_edit-shop_order_columns', $plugin_admin, 'add_biz_order_voucher_column');
		$this->loader->add_action('manage_shop_order_posts_custom_column', $plugin_admin, 'biz_order_voucher_column', 10, 2);
		$this->loader->add_action('wp_ajax_biz_send_shipment', $plugin_admin, 'biz_send_shipment_handler');
		$this->loader->add_action('wp_ajax_biz_modify_shipment', $plugin_admin, 'biz_modify_shipment_handler');
		$this->loader->add_action('wp_ajax_biz_add_shipment_voucher', $plugin_admin, 'biz_add_shipment_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_edit_shipment_voucher', $plugin_admin, 'biz_edit_shipment_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_delete_shipment_voucher', $plugin_admin, 'biz_delete_shipment_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_synchronize_order', $plugin_admin, 'biz_synchronize_order_handler');
		$this->loader->add_action('woocommerce_order_status_changed', $plugin_admin, 'biz_order_changed_handler',10, 3);

		// Shipment automatic updating - cron job.
		function biz_cron_order_status_checking_interval($schedules) {
			$schedules['ten_minutes'] = array(
				'interval' => 300,
				'display' => 'Every 5 minutes.'
			);
			return $schedules;
		}
		add_filter('cron_schedules',  'biz_cron_order_status_checking_interval');
		$this->loader->add_action('biz_cron_order_status_checking_hook', $plugin_admin, 'biz_cron_order_status_checking');
		if (!wp_next_scheduled('biz_cron_order_status_checking_hook')) {
			wp_schedule_event(time(), 'ten_minutes', 'biz_cron_order_status_checking_hook');
		}
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new WC_Biz_Courier_Logistics_Public($this->get_WC_Biz_Courier_Logistics(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_WC_Biz_Courier_Logistics()
	{
		return $this->WC_Biz_Courier_Logistics;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WC_Biz_Courier_Logistics_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
