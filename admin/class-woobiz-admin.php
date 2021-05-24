<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/woobiz
 * @since      1.0.0
 *
 * @package    WooBiz
 * @subpackage WooBiz/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooBiz
 * @subpackage WooBiz/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WooBiz_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $WooBiz    The ID of this plugin.
	 */
	private $WooBiz;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $WooBiz       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($WooBiz, $version)
	{
		$this->WooBiz = $WooBiz;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->WooBiz, plugin_dir_url(__FILE__) . 'css/woobiz-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->WooBiz, plugin_dir_url(__FILE__) . 'js/woobiz-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_biz_settings_tab($settings_tabs)
	{
		$settings_tabs['biz_settings_tab'] = __('Biz Courier', 'woocommerce-biz-settings-tab');
		return $settings_tabs;
	}

	/**
	 * 	WooCommerce Settings Pane for Biz Courier.
	 * 	------------
	 *  This section provides the necessary functionality to store
	 * 	Biz Courier credentials into the database for future use.
	 * 
	 */


	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function biz_settings_tab()
	{
		woocommerce_admin_fields(self::get_settings());
	}


	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_biz_settings()
	{
		woocommerce_update_options(self::get_settings());
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings()
	{

		$settings = array(
			'section_title' => array(
				'name'     => __('Biz Courier Credentials', 'woobiz'),
				'type'     => 'title',
				'desc'     => __('Insert your Biz Courier credentials here. If you are still unregistered with Biz Courier, please <a href="https://www.bizcourier.eu/ContactUs.htm" target="blank">contact us</a>.', 'woobiz'),
				'id'       => 'wc_biz_settings_tab_section_title'
			),
			'account_number' => array(
				'name' => __('Account Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your account number registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_account_number'
			),
			'head_crm' => array(
				'name' => __('Head CRM Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your head CRM number registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_head_crm'
			),
			'warehouse_crm' => array(
				'name' => __('Warehouse CRM Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your CRM number for the warehouse location assigned to this store.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_warehouse_crm'
			),
			'username' => array(
				'name' => __('Username', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your username registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_username'
			),
			'password' => array(
				'name' => __('Password', 'woobiz'),
				'type' => 'password',
				'desc' => __('Your Biz Courier merchant password.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_password'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_biz_settings_tab_section_end'
			),
		);

		return apply_filters('wc_biz_settings_tab_settings', $settings);
	}

	/**
	 * 	Order Status 
	 * 	------------
	 *  This section provides the necessary functionality for mapping, managing and displaying
	 * 	WooCommerce orders and their status to Biz Courier Shipments.
	 * 
	 */


	/**
	 * Get the status of a Biz Courier shipment using the stored voucher number.
	 *
	 * @since    1.0.0
	 */
	function biz_order_status($order)
	{
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl", array(
			'encoding' => 'UTF-8',
		));
		try {
			$result = $client->__soapCall("full_status", array("Voucher" => strval($order->get_order_number())));
		} catch (Exception $e) {
			$error = $e->getMessage();
			throw new ErrorException(__("There was a problem contacting Biz Courier. Details: ", 'woobiz') + $error);
		}
	}

	/**
	 * Create a Biz Courier Shipment with a given WooCommerce order.
	 *
	 * @since    1.0.0
	 */
	function biz_order_create($order)
	{
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl", array(
			'encoding' => 'UTF-8',
		));
		try {
			$result = $client->__soapCall("full_status", array("Voucher" => strval($order->get_order_number())));
		} catch (Exception $e) {
			$error = $e->getMessage();
			throw new ErrorException(__("There was a problem contacting Biz Courier. Details: ", 'woobiz') + $error);
		}
	}

	/**
	 * Add Biz Courier connection indicator metabox to a single order.
	 *
	 * @since    1.0.0
	 */
	function add_biz_order_meta_box()
	{
		function biz_order_meta_box($post)
		{

			// Get markup.
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';

			$order = wc_get_order($post->ID);

			/*	1. Check order meta (if biz_order_voucher exists).
					1.1. If not synced, prompt admin to sync:
			*/
			biz_order_meta_box_not_synchronized_html();
			/*
					1.2. If synced,
						1.2.1. Get order status and show to admin: 
			*/
			// TODO: Implement biz_order_status($order->get_voucher()) for front and back & handle connection errors. Then show voucher number and status on meta box.
			/*
				2. Check if POST['woobiz_sync']=true (& nonce) and do initial sync.
			*/
			// TODO: Implement biz_order_create($order), add biz_order_voucher as post meta & handle connection errors. Show error in meta box.


		}

		add_meta_box('woobiz_order_meta_box', __('Biz Courier status', 'woobiz'), 'biz_order_meta_box', 'shop_order', 'side', 'high');
	}

	/**
	 * 	Stock Synchronisation functionality.
	 * 	------------
	 *  This section provides all the functionality related to syncing stock 
	 * 	between the WooCommerce store and the items in the connected warehouse.
	 * 
	 */

	/**
	 * Gets all SKUs of a product or its variants.
	 *
	 * @since    1.0.0
	 */
	static function get_all_related_skus($product)
	{
		$skus = array();
		$variants = $product->get_children();
		if ($product->managing_stock()) {
			array_push($skus, $product->get_sku());
		}
		if (!empty($variants)) {
			foreach ($variants as $variant_id) {
				$product_variant = wc_get_product($variant_id);
				if ($product_variant->managing_stock()) {
					$variant_sku = $product_variant->get_sku();
					array_push($skus, $variant_sku);
				}
			}
		}
		return array_unique($skus);
	}

	/**
	 * Handles stock sync AJAX requests from authorized users and contacts Biz via SOAP.
	 *
	 * @since    1.0.0
	 */
	function biz_stock_sync_handler()
	{
		// TODO: Handle AJAX call and write 'biz_sync_status' meta on products.
		if( !wp_verify_nonce($_POST['nonce'],'ajax_sync_validation')) {
			die("Unverified request to synchronise stock.");
		}
		
		// TODO: SOAP request using AJAX product SKUs and stored credentials.
		
	}

	/**
	 * Creates the meta box for product pages and localises each product page's script 
	 * with the appropriate SKU parameters.
	 *
	 * @since    1.0.0
	 */
	function add_biz_stock_sync_meta_box()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';

		function biz_stock_sync_meta_box($post)
		{
			wp_enqueue_script('woobiz-stock-sync', plugin_dir_url(__FILE__) . 'js/woobiz-admin-stock-sync.js', array('jquery'));
			wp_localize_script('woobiz-stock-sync', "ajax_prop", array(
				"ajax_url" => admin_url('admin-ajax.php'),
				"nonce" => wp_create_nonce('ajax_sync_validation'),
				"product_skus" => WooBiz_Admin::get_all_related_skus(wc_get_product($post->ID))
			));

			$sync_status = get_post_meta($post->ID, "biz_sync_status");

			if ($sync_status == "error") {
				biz_stock_sync_meta_box_error_html();
			} elseif ($sync_status == "success") {
				biz_stock_sync_meta_box_success_html();
			} else {
				biz_stock_sync_meta_box_html();
			}
		}
		add_meta_box('woobiz_stock_sync_meta_box', __('Biz warehouse status', 'woobiz'), 'biz_stock_sync_meta_box', 'product', 'side');
	}

	/**
	 * Add Biz Courier remaining stock synchronization button to the All Products page.
	 *
	 * @since    1.0.0
	 */
	function add_biz_stock_sync_all_button()
	{
		global $current_screen;
		if ('product' != $current_screen->post_type) {
			return;
		}

		$products = get_posts(array(
			'post_type' => 'product',
			'posts_per_page' => -1
		));

		$all_skus = array();
		if (!empty($products)) {
			foreach ($products as $product_id) {
				$product = wc_get_product($product_id->ID);
				$all_skus = WooBiz_Admin::get_all_related_skus($product);
			}
		}

		wp_enqueue_script('woobiz-stock-sync', plugin_dir_url(__FILE__) . 'js/woobiz-admin-stock-sync.js', array('jquery'));
		wp_localize_script('woobiz-stock-sync', "ajax_prop", array(
			"ajax_url" => admin_url('admin-ajax.php'),
			"nonce" => wp_create_nonce('ajax_prop_validation'),
			"product_skus" => $all_skus
		));

		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';
		biz_stock_sync_all_button();
	}


	/**
	 * Add Biz Courier stock synchronisation status column to the All Products page.
	 *
	 * @since    1.0.0
	 */
	function add_biz_stock_sync_indicator_column($columns)
	{
		$columns['biz_sync'] = __('Biz Status', 'woobiz');
		return $columns;
	}

	/**
	 * Add Biz Courier stock synchronisation status indicator column to each product
	 * in the All Products page.
	 *
	 * @since    1.0.0
	 */
	function biz_stock_sync_indicator_column($name)
	{
		global $post;
		$status = get_post_meta($post->ID, "biz_sync_status", true);
		switch ($name) {
			case 'biz_sync':
				echo '<div class="biz_sync-indicator ' . $status . '"></div>';
		}
	}
}
