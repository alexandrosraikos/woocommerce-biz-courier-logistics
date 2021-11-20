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
		$this->version = '1.4.0';
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

		/**
		 * The class responsible for custom exceptions.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-exceptions.php';
		

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
		// TODO @alexandrosraikos: Log and test all cases thoroughly (#37).

		$plugin_admin = new WC_Biz_Courier_Logistics_Admin($this->get_WC_Biz_Courier_Logistics(), $this->get_version());

		/**
		 * General
		 * ----------------
		 */

		/** Interface hooks */
		$this->loader->add_action('init', $plugin_admin, 'check_minimum_requirements');
		$this->loader->add_filter('admin_notices', $plugin_admin, 'internal_error_notice');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
		$this->loader->add_filter(
			'plugin_action_links_wc-biz-courier-logistics/wc-biz-courier-logistics.php',
			$plugin_admin,
			'plugin_action_links'
		);
		$this->loader->add_filter('plugin_row_meta', $plugin_admin, 'plugin_row_meta', 10, 2);

		/**
		 * Integration
		 * ----------------
		 */

		/** Extensions */
		$this->loader->add_action('woocommerce_integrations_init', $plugin_admin, 'biz_integration');
		$this->loader->add_filter('woocommerce_integrations', $plugin_admin, 'add_biz_integration');

		/** Interface hooks */
		$this->loader->add_filter('admin_notices', $plugin_admin, 'biz_settings_notice');

		/** 
		 * Product Management
		 * ----------------
		 */

		/** Interface hooks */
		$this->loader->add_action('woocommerce_product_options_inventory_product_data', $plugin_admin, 'add_product_biz_warehouse_option');
		$this->loader->add_action('woocommerce_variation_options', $plugin_admin, 'add_product_variation_biz_warehouse_option', 10, 3);
		$this->loader->add_action('manage_posts_extra_tablenav', $plugin_admin, 'add_product_stock_synchronize_all_button', 20, 1);
		$this->loader->add_filter('manage_edit-product_columns', $plugin_admin, 'add_product_stock_status_indicator_column');
		$this->loader->add_action('manage_product_posts_custom_column', $plugin_admin, 'product_stock_status_indicator_column', 10, 2);

		/** Handler hooks */
		$this->loader->add_action('woocommerce_process_product_meta', $plugin_admin, 'save_product_biz_warehouse_option', 10, 1);
		$this->loader->add_action('woocommerce_save_product_variation', $plugin_admin, 'save_product_variation_biz_warehouse_option', 10, 2);

		/** AJAX handler hooks */
		$this->loader->add_action('wp_ajax_product_stock_synchronization_all', $plugin_admin, 'product_stock_synchronization_all');

		/** 
		 * Shipping Method
		 * ----------------
		 */

		/** Extensions */
		$this->loader->add_action('woocommerce_shipping_init', $plugin_admin, 'biz_shipping_method');
		$this->loader->add_filter('woocommerce_shipping_methods', $plugin_admin, 'add_biz_shipping_method');

		/** 
		 * Shipment Management
		 * ----------------
		 */

		/** Interface hooks */
		$this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_shipment_management_meta_box');
		$this->loader->add_filter('manage_edit-shop_order_columns', $plugin_admin, 'add_shipment_voucher_column');
		$this->loader->add_action('manage_shop_order_posts_custom_column', $plugin_admin, 'shipment_voucher_column', 10, 2);
		// TODO @alexandrosraikos: Add Biz Warehouse indicator column on order items panel. (#32)

		/** Handler hooks */
		$this->loader->add_action('woocommerce_order_status_changed', $plugin_admin, 'order_status_change_handler', 10, 3);

		/** AJAX handler hooks */
		$this->loader->add_action('wp_ajax_biz_shipment_send', $plugin_admin, 'shipment_creation_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_modification_request', $plugin_admin, 'shipment_modification_request_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_cancellation_request', $plugin_admin, 'shipment_cancellation_request_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_add_voucher', $plugin_admin, 'shipment_add_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_edit_voucher', $plugin_admin, 'shipment_edit_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_delete_voucher', $plugin_admin, 'shipment_delete_voucher_handler');
		$this->loader->add_action('wp_ajax_biz_shipment_synchronize_order', $plugin_admin, 'shipment_synchronize_order_handler');

		/** Cron hooks */
		add_filter(
			'cron_schedules',
			function ($schedules) {
				$schedules['ten_minutes'] = array(
					'interval' => 300,
					'display' => 'Every 5 minutes.'
				);
				return $schedules;
			}
		);
		$this->loader->add_action('shipment_status_cron_handler_hook', $plugin_admin, 'shipment_status_cron_handler');
		if (!wp_next_scheduled('shipment_status_cron_handler_hook')) {
			wp_schedule_event(time(), 'ten_minutes', 'shipment_status_cron_handler_hook');
		}

		/** Utility hooks */
		$this->loader->add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', $plugin_admin, 'shipment_voucher_custom_query_var_handler', 10, 2 );
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

		/**
		 * General
		 * ----------------
		 */

		/** Interface hooks */
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

		/** 
		 * Shipping Method
		 * ----------------
		 */

		/** Interface hooks */
		$this->loader->add_action('woocommerce_cart_calculate_fees', $plugin_public, 'add_biz_cod_fee', 10, 1);
		$this->loader->add_action('wp_footer', $plugin_public, 'biz_checkout_refresh');
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

	/**
	 * Make an authorized request to the Biz Courier API via SOAP.
	 * 
	 * @param string $wsdl_url The url to WSDL file.
	 * @param string $method The method to call within the WSDL.
	 * @param array $data The data to include in the request.
	 * @param bool $authorized Whether this request requires authorization.
	 * @param callable $completion The callback to complete the procedure.
	 * @param callable $rejection The custom rejection procedure.
	 * @param bool $no_crm Omit the CRM code from the authentication data for authorized requests.
	 * 
	 * @return array If no `$completion` is defined.
	 * 
	 * @throws RuntimeException When there are no credentials registered.
	 * @throws SoapFault When there is a connection error.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function contactBizCourierAPI(string $wsdl_url, string $method, array $data, bool $authorized, ?callable $completion = NULL, ?callable $rejection = NULL, ?bool $no_crm = false)
	{
		// For authorized requests.
		if ($authorized) {

			/** @var string[] $biz_settings The persistent Biz integration settings. */
			$biz_settings = get_option('woocommerce_biz_integration_settings');

			// Check for existing credentials.
			if (
				empty($biz_settings['account_number']) ||
				empty($biz_settings['warehouse_crm']) ||
				empty($biz_settings['username']) ||
				empty($biz_settings['password'])
			) {
				throw new RuntimeException(__(`Please set up your Biz Courier & Logistics credentials before submitting a shipment.`, 'wc-biz-courier-logistics'));
			}

			// Append to data array.
			$data = array_merge([
				'Code' => $biz_settings['account_number'],
				'CRM' => $biz_settings['warehouse_crm'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password']
			], $data);

			// Remove CRM code if not required.
			if($no_crm) array_splice($data, 1, 1);
		}

		/** @var SoapClient $client The SOAP client with exceptions enabled. */
		$client = new SoapClient($wsdl_url, [
			'trace' => 1,
			'exceptions' =>	true,
			'encoding' => 'UTF-8'
		]);

		/** @var Object $response The API response. */
		$response = $client->__soapCall($method, $data);
		$response = json_decode(json_encode($response), true);

		// Handle response.
		if (($response['Error'] ?? 0) == 0) {
			if ($completion != NULL) {
				$completion($response);
			} else {
				return $response;
			}
		} else {
			if ($rejection != NULL) {
				$rejection($response);
			} else throw new ErrorException($response['Error']);
		}
	}

	/**
	 * Ensure the string is in UTF-8 format.
	 * 
	 * @param string $string The string.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.2
	 */
	public static function ensure_utf8(string $string): string
	{
		return (mb_detect_encoding($string) == 'UTF-8') ? $string : utf8_encode($string);
	}

	/**
	 * Truncate text to the desired character limit.
	 *
	 * @param string $string The text to be truncated.
	 * @param int $length The maximum length.
	 * 
	 * @return	string The text truncated to the desired length (40 characters is default).
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since   1.0.0
	 * @version	1.4.0
	 */
	public static function truncate_field(string $string, int $length = 40)
	{
		// Ensure UTF-8 encoding.
		$string = WC_Biz_Courier_Logistics::ensure_utf8($string);

		// Return the truncated string.
		return (mb_strlen($string, 'UTF-8') > $length) ? mb_substr($string, 0, $length - 1) . "." : $string;
	}
}
