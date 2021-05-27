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
 * Defines the plugin column_name, version, and two examples hooks for how to
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
	 * @param      string    $WooBiz       The column_name of this plugin.
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
	 * 	Integration
	 * 	------------
	 *  This section provides the necessary functionality for initialising the custom Biz integration.
	 * 
	 */

	function biz_integration() {
		if (!class_exists('Biz_Integration')) {
		   require_once plugin_dir_path(dirname(__FILE__)).'includes/class-woobiz-integration.php';
		}
	}

	function add_biz_integration($integrations) {
	   $integrations[] = 'Biz_Integration';
	   return $integrations;
	}

	/**
	 * Displays a WordPress notice depending on Biz credential and connection status.
	 *
	 * @uses get_options()
	 * @since	1.0.0
	 */
	function biz_settings_notice()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';
		
		$in_biz_tab = false;
		if (isset($_GET['tab']) && isset($_GET['section'])) {
			$in_biz_tab = $_GET['section'] == 'biz_integration';
		}

		if (is_admin() && !$in_biz_tab) {
			$biz_settings = get_option('woocommerce_biz_integration_settings');
			if (isset($_GET['biz_error'])) {
				if ($_GET['biz_error'] == 'auth-error') {
					biz_settings_notice_invalid_html();
				} 
				elseif ($_GET['biz_error'] == 'conn-error') {
					biz_settings_notice_error_html();
				} 
			}
			elseif (
				$biz_settings['account_number'] == null ||
				$biz_settings['warehouse_crm'] == null ||
				$biz_settings['username'] == null ||
				$biz_settings['password'] == null
			) {
				biz_settings_notice_missing_html();
			}
			
		}
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
	 * @param	 WC_Product $product A WooCommerce product.
	 */
	public static function get_all_related_skus($product)
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
	 * Clear product synchronization status.
	 *
	 * All Biz Courier sync status are deleted from the products in the database.
	 *
	 * @since    1.0.0
	 */
	public static function reset_all_sync_status()
	{
		$products = wc_get_products(array(
			'posts_per_page' => -1
		));
		if (!empty($products)) {
			foreach ($products as $product) {
				delete_post_meta($product->get_id(), 'biz_sync');
			}
		}
		$variations = wc_get_products(array(
			'posts_per_page' => -1,
			'type' => 'variation'
		));
		if (!empty($variations)) {
			foreach ($variations as $variation) {
				delete_post_meta($variation->get_id(), 'biz_sync');
			}
		}
	}


	/**
	 * Synchronizes stock between given WooCommerce SKUs and Biz Courier via stored credentials. 
	 *
	 * @since    1.0.0
	 * @param	 array $skus An array of product skus formatted as strings.
	 */
	protected static function biz_stock_sync($skus)
	{
		try {
			// Connect to Biz and get all remaining stock.
			$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/prod_stock.php?wsdl", array(
				'trace' => 1,
				'encoding' => 'UTF-8',
			));
			$biz_settings = get_option('woocommerce_biz_integration_settings');
			$response = $client->__soapCall('prod_stock', array(
				'Code' => $biz_settings['account_number'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password']
			));

			if ($response[0]->Product_Code == "Wrong Authentication Data") {
				WooBiz_Admin::reset_all_sync_status();
				throw new Exception('auth-error');
			}
			
			foreach ($response as $biz_product) {
				if (in_array($biz_product->Product_Code, $skus)) {
					$product_post_id = wc_get_product_id_by_sku($biz_product->Product_Code);
					$product_post = get_post($product_post_id);
					$wc_product = wc_get_product($product_post->ID);
					if ($wc_product->managing_stock()) {
						wc_update_product_stock($wc_product, $biz_product->Remaining_Quantity, 'set');
						update_post_meta($product_post_id, 'biz_sync', 'synced');
						$wc_product->set_catalog_visibility('visible');
					} else {
						delete_post_meta($product_post_id, 'biz_sync');
						$wc_product->set_catalog_visibility('hidden');
					}
				}
			}

			foreach ($skus as $sku) {
				$retrieved_skus = array_map(function ($bp) {
					return $bp->Product_Code;
				}, $response);
				if (!in_array($sku, $retrieved_skus)) {
					$product_post_id = wc_get_product_id_by_sku($sku);
					$product_post = get_post($product_post_id);
					$wc_product = wc_get_product($product_post->ID);
					$wc_product->set_catalog_visibility('hidden');
					update_post_meta($product_post_id, 'biz_sync', 'not-synced');
				}
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}

	/**
	 * Handles stock sync AJAX requests from authorized users and initiates sync.
	 *
	 * @since    1.0.0
	 */
	function biz_stock_sync_handler()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_stock_sync_validation')) {
			die("Unverified request to synchronise stock.");
		}
		try {
			WooBiz_Admin::biz_stock_sync($_POST['product_skus']);
		} catch (Exception $e) {
			echo $e->getMessage();
			error_log("Error contacting Biz Courier - " . $e->getMessage());
		}
		die();
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

		$products = wc_get_products(array());
		$all_skus = array();
		if (!empty($products)) {
			foreach ($products as $product) {
				$all_skus = array_merge($all_skus, WooBiz_Admin::get_all_related_skus($product));
			}
		}

		wp_enqueue_script('woobiz-stock-sync', plugin_dir_url(__FILE__) . 'js/woobiz-admin-stock-sync.js', array('jquery'));
		wp_localize_script('woobiz-stock-sync', "ajax_prop", array(
			"ajax_url" => admin_url('admin-ajax.php'),
			"nonce" => wp_create_nonce('ajax_stock_sync_validation'),
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
		$columns['biz_sync'] = __('Biz Warehouse', 'woobiz');
		return $columns;
	}

	/**
	 * Add Biz Courier stock synchronisation status indicator column to each product
	 * in the All Products page.
	 *
	 * @since    1.0.0
	 */
	function biz_stock_sync_indicator_column($column_name, $product_post_id)
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';
		switch ($column_name) {
			case 'biz_sync':
				$product = wc_get_product($product_post_id);
				$status = "";

				if ($product->managing_stock() && $product->get_sku() != null) {
					$status = get_post_meta($product_post_id, 'biz_sync', true);
				}

				// Check if any children are synced.
				$children_ids = $product->get_children();
				$synced_children = true;
				if (!empty($children_ids)) {
					foreach ($children_ids as $child_id) {
						if (get_post_meta($child_id, 'biz_sync', true) == 'not-synced') {
							$synced_children = false;
						}
					}
				};
				$status = ($synced_children) ? ($status) : ("not-synced");
				biz_stock_sync_column_html($status);
		}
	}

	/**
	 * 	Shipping Method 
	 * 	------------
	 *  This section provides the necessary functionality for initialising the custom Biz shipping method.
	 * 
	 */

	 function biz_shipping_method() {
		 if (!class_exists('Biz_Shipping_Method')) {
			require_once plugin_dir_path(dirname(__FILE__)).'includes/class-woobiz-shipping-method.php';
		 }
	 }

	 function add_biz_shipping_method($methods) {
		$methods[] = 'Biz_Shipping_Method';
		return $methods;
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
	function biz_shipment_status($order)
	{
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl", array(
			'encoding' => 'UTF-8',
		));
		try {
			$result = $client->__soapCall("full_status", array("Voucher" => strval($order->get_order_number())));
		} catch (Exception $e) {
			$error = $e->getMessage();
			throw new ErrorException("There was a problem contacting Biz Courier. Details: " . $error);
		}
	}

	/**
	 * Create a Biz Courier Shipment with a given WooCommerce order.
	 *
	 * @since    1.0.0
	 */

	function biz_stock_send_shipment()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_send_shipment_validation')) {
			die("Unverified request to send shipment.");
		}
		// TODO: Connect to Biz, send shipment and receive voucher and tracking code.
	}

	/**
	 * Add Biz Courier connection indicator metabox to a single order.
	 *
	 * @since    1.0.0
	 */
	function add_biz_shipment_meta_box()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';

		function biz_shipment_meta_box($post)
		{
			$order = wc_get_order($post->ID);

			if (!empty($order->get_meta('biz_voucher'))) {
				biz_track_shipment_meta_box_html($order->get_meta('biz_voucher'));
			} else {
				wp_enqueue_script('woobiz-send-shipment', plugin_dir_url(__FILE__) . 'js/woobiz-admin-send-shipment.js', array('jquery'));
				wp_localize_script('woobiz-send-shipment', "ajax_prop", array(
					"ajax_url" => admin_url('admin-ajax.php'),
					"nonce" => wp_create_nonce('ajax_send_shipment_validation'),
					"order_id" => $order->get_id()
				));
				biz_send_shipment_meta_box_html();
			}
		}

		add_meta_box('woobiz_send_shipment_meta_box', __('Biz Courier status', 'woobiz'), 'biz_shipment_meta_box', 'shop_order', 'side', 'high');
	}
}
