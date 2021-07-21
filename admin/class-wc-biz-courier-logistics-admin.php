<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin column_name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WC_Biz_Courier_Logistics_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $WC_Biz_Courier_Logistics    The ID of this plugin.
	 */
	private $WC_Biz_Courier_Logistics;

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
	 * @param      string    $WC_Biz_Courier_Logistics       The column_name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($WC_Biz_Courier_Logistics, $version)
	{
		$this->WC_Biz_Courier_Logistics = $WC_Biz_Courier_Logistics;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		wp_enqueue_style($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'css/wc-biz-courier-logistics-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin.js', array('jquery'), $this->version, false);
	}


	/**
	 * 	Integration
	 * 	------------
	 *  This section provides the necessary functionality for initialising the custom Biz integration.
	 * 
	 */


	/**
	 * Declare the Biz_Integration class.
	 *
	 * @since    1.0.0
	 * @uses 	 plugin_dir_path()
	 */
	function biz_integration()
	{
		if (!class_exists('Biz_Integration')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-integration.php';
		}
	}


	/**
	 * Include Biz_Integration in WooCommerce Integrations.
	 *
	 * @since    1.0.0
	 * @param 	 array $integrations The active list of WooCommerce Integrations.
	 */
	function add_biz_integration($integrations)
	{
		$integrations[] = 'Biz_Integration';
		return $integrations;
	}

	/**
	 * Displays a WordPress notice depending on Biz credential and connection status.
	 *
	 * @uses 	get_option()
	 * @uses 	biz_settings_notice_invalid_html()
	 * @uses 	biz_settings_notice_error_html()
	 * @uses 	biz_settings_notice_missing_html()
	 * @since	1.0.0
	 */
	function biz_settings_notice()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

		// Check if the administrator is already in the Biz tab.
		$in_biz_tab = false;
		if (isset($_GET['tab']) && isset($_GET['section'])) {
			$in_biz_tab = $_GET['section'] == 'biz_integration';
		}

		if (is_admin() && !$in_biz_tab) {

			// Handle undesirable credentials state.
			$biz_settings = get_option('woocommerce_biz_integration_settings');
			if (isset($_GET['biz_error'])) {
				if ($_GET['biz_error'] == 'auth-error') {
					biz_settings_notice_invalid_html();
				} elseif ($_GET['biz_error'] == 'conn-error') {
					biz_settings_notice_error_html();
				}
			} elseif (
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
	 * 	Stock Synchronisation
	 * 	------------
	 *  This section provides all the functionality related to syncing stock 
	 * 	between the WooCommerce store and the items in the connected warehouse.
	 * 
	 */

	/**
	 * Gets all SKUs of a product or its variants.
	 *
	 * @since    1.0.0
	 * @uses 	 get_children()
	 * @uses 	 wc_get_product()
	 * @param	 WC_Product $product A WooCommerce product.
	 */
	static function get_all_related_skus($product)
	{
		// Push simple product SKUs.
		$skus = array();
		if ($product->managing_stock()) {
			array_push($skus, $product->get_sku());
		}

		// Push children variation SKUs.
		$variants = $product->get_children();
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
	 * @uses 	 delete_post_meta()
	 * @uses 	 wc_get_products()
	 */
	static function reset_all_sync_status()
	{
		// Get all products.
		$products = wc_get_products(array(
			'posts_per_page' => -1
		));

		// Delete all synchronisation indicators.
		if (!empty($products)) {
			foreach ($products as $product) {
				delete_post_meta($product->get_id(), 'biz_sync');
			}
		}

		// Get all variations.
		$variations = wc_get_products(array(
			'posts_per_page' => -1,
			'type' => 'variation'
		));

		// Delete all synchronisation indicators.
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
	 * @uses 	 get_option()
	 * @uses 	 __soapCall()
	 * @uses 	 WC_Biz_Courier_Logistics_Admin::reset_all_sync_status()
	 * @uses 	 in_array()
	 * @uses 	 wc_get_product_id_by_sku()
	 * @uses 	 wc_get_product()
	 * @uses 	 get_post()
	 * @uses 	 update_post_meta()
	 * @uses 	 delete_post_meta()
	 */
	static function biz_stock_sync($skus)
	{
		try {
			// Initialize client.
			$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/prod_stock.php?wsdl", array(
				'trace' => 1,
				'encoding' => 'UTF-8',
			));

			// Get credentials settings.
			$biz_settings = get_option('woocommerce_biz_integration_settings');

			// Make SOAP call.
			$response = $client->__soapCall('prod_stock', array(
				'Code' => $biz_settings['account_number'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password']
			));

			//  Handle authorization error.
			if ($response[0]->Product_Code == "Wrong Authentication Data") {
				WC_Biz_Courier_Logistics_Admin::reset_all_sync_status();
				throw new Exception('auth-error');
			}

			// Compare with each product already in warehouse.
			foreach ($response as $biz_product) {
				if (in_array($biz_product->Product_Code, $skus)) {

					// Get the product using the SKU / Biz Product Code.
					$product_post_id = wc_get_product_id_by_sku($biz_product->Product_Code);
					$product_post = get_post($product_post_id);
					$wc_product = wc_get_product($product_post->ID);

					// Check for active stock management.
					if ($wc_product->managing_stock()) {

						// Update remaining stock quantity.
						wc_update_product_stock($wc_product, $biz_product->Remaining_Quantity, 'set');

						// Update Biz synchronization post metadata.
						update_post_meta($product_post_id, 'biz_sync', 'synced');
					} else {

						// Delete Biz synchronization post metadata.
						delete_post_meta($product_post_id, 'biz_sync');
					}
				}
			}

			// Compare with each product in the synchronization call.
			foreach ($skus as $sku) {

				// Extract SKUs.
				$retrieved_skus = array_map(function ($bp) {
					return $bp->Product_Code;
				}, $response);

				// Check for warehouse availability.
				if (!in_array($sku, $retrieved_skus)) {

					// Get the product using the SKU.
					$product_post_id = wc_get_product_id_by_sku($sku);
					$product_post = get_post($product_post_id);
					$wc_product = wc_get_product($product_post->ID);

					// Update Biz synchronization post metadata.
					update_post_meta($product_post_id, 'biz_sync', 'not-synced');

					// Update parent product for all valid variations.
					$wc_product_children_ids = $wc_product->get_children();
					if (!empty($wc_product_children_ids)) {
						$valid_children = true;
						foreach ($wc_product_children_ids as $child_id) {
							$child_sync_state = get_post_meta($child_id, 'biz_sync');
							if (isset($child_sync_state)) {
								if (!$child_sync_state) {
									$valid_children = false;
								}
							}
						}
						if ($valid_children) {
							update_post_meta($product_post_id, 'biz_sync', 'synced');
						}
					}
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
	 * @uses 	 wp_verify_nonce()
	 * @uses 	 WC_Biz_Courier_Logistics_Admin::biz_stock_sync()
	 */
	function biz_stock_sync_handler()
	{
		// Verify the WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_stock_sync_validation')) {
			die("Unverified request to synchronise stock.");
		}

		// Attempt stock synchronization using request SKUs.
		try {
			WC_Biz_Courier_Logistics_Admin::biz_stock_sync($_POST['product_skus']);
		} catch (Exception $e) {
			echo $e->getMessage();
			error_log("Error contacting Biz Courier - " . $e->getMessage());
		}

		// Return success.
		die();
	}

	/**
	 * Add Biz Courier remaining stock synchronization button to the All Products page.
	 *
	 * @since    1.0.0
	 * @uses  	 WC_Biz_Courier_Logistics_Admin::get_all_related_skus()
	 * @uses 	 wp_enqueue_script()
	 * @uses 	 wp_localize_script()
	 * @uses 	 wp_create_nonce()
	 * @uses 	 biz_stock_sync_all_button()
	 */
	function add_biz_stock_sync_all_button()
	{
		// Ensure active All Products page.
		global $current_screen;
		if ('product' != $current_screen->post_type) {
			return;
		}
		// Get SKUs from all products.
		$products = wc_get_products(array());
		$all_skus = array();
		if (!empty($products)) {
			foreach ($products as $product) {
				$all_skus = array_merge($all_skus, WC_Biz_Courier_Logistics_Admin::get_all_related_skus($product));
			}
		}

		// Enqeue & localize synchronization button script.
		wp_enqueue_script('wc-biz-courier-logistics-stock-sync', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-stock-sync.js', array('jquery'));
		wp_localize_script('wc-biz-courier-logistics-stock-sync', "ajax_prop", array(
			"ajax_url" => admin_url('admin-ajax.php'),
			"nonce" => wp_create_nonce('ajax_stock_sync_validation'),
			"product_skus" => $all_skus
		));

		// Insert button HTML.
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
		biz_stock_sync_all_button();
	}


	/**
	 * Add Biz Courier stock synchronisation status column to the All Products page.
	 *
	 * @since    1.0.0
	 * @param 	 array $columns The active list of columns in the All Products page.
	 */
	function add_biz_stock_sync_indicator_column($columns)
	{
		$columns['biz_sync'] = __('Biz Warehouse', 'wc-biz-courier-logistics');
		return $columns;
	}

	/**
	 * Add Biz Courier stock synchronisation status indicator column to each product
	 * in the All Products page.
	 *
	 * @since    1.0.0
	 * @uses 	 get_post_meta()
	 * @uses 	 wc_get_product()
	 * @uses 	 biz_stock_sync_column_html()
	 */
	function biz_stock_sync_indicator_column($column_name, $product_post_id)
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

		// Ensure Biz Status column.
		switch ($column_name) {
			case 'biz_sync':
				// Declare empty product status.
				$status = "";

				// Get product synchronisation status.
				$product = wc_get_product($product_post_id);
				if ($product->managing_stock() && $product->get_sku() != null) {
					$status = get_post_meta($product_post_id, 'biz_sync', true);
				}

				// Get children variations' synchronization status.
				$children_ids = $product->get_children();
				$synced_children = true;
				if (!empty($children_ids)) {
					foreach ($children_ids as $child_id) {
						if (get_post_meta($child_id, 'biz_sync', true) == 'not-synced') {
							$synced_children = false;
						}
					}
				};

				// Update product status.
				$status = ($synced_children) ? ($status) : ("not-synced");

				// Show HTML.
				biz_stock_sync_column_html($status);
		}
	}

	/**
	 * 	Shipping Method 
	 * 	------------
	 *  This section provides the necessary functionality for initialising the custom Biz shipping method.
	 * 
	 */


	/**
	 * Declare the Biz_Shipping_Method class.
	 *
	 * @since    1.0.0
	 */
	function biz_shipping_method()
	{
		if (!class_exists('Biz_Shipping_Method')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-shipping-method.php';
		}
	}

	/**
	 * Declare Biz_Shipping_Method class.
	 *
	 * @since    1.0.0
	 * @param 	 array $methods The active list of shipping methods.
	 */
	function add_biz_shipping_method($methods)
	{
		$methods['biz_shipping_method'] = 'Biz_Shipping_Method';
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
	 * Creates a new shipment with Biz and saves the Biz generated voucher in the order's metadata.
	 *
	 * @since    1.0.0
	 * @param	 int $order_id The ID of the WooCommerce order.
	 * @uses	 __soapCall()
	 * @uses	 wc_get_order()
	 * @uses 	 wc_get_product()
	 * @uses 	 get_post_meta()
	 * @uses 	 get_option()
	 * @uses 	 updaate_post_meta()
	 */
	static function biz_send_shipment(int $order_id, bool $automated = true)
	{
		/**
		 * Truncate text to the desired character limit.
		 *
		 * @since    1.0.0
		 * @param string $string The text to be truncated.
		 * @param int $length The maximum length.
		 */
		function truncate_field(string $string, int $length = 40)
		{
			return (strlen($string) > $length) ? substr($string, 0, $length - 1) . "." : $string;
		}

		// Get Biz credentials and shipping settings.
		$biz_settings = get_option('woocommerce_biz_integration_settings');
		$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

		// Automated process check.
		if (($automated && $biz_shipping_settings['automatic_shipment_creation'] == 'yes') || $automated == false) {
			try {
				// Initialize client.
				$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl", array(
					'trace' => 1,
					'encoding' => 'UTF-8',
				));

				// Initialize Biz item format array in product_code:quantity format.
				$shipment_products = array();

				// Initialize total volume array.
				$total_order_volume = array(
					'width' => 0,
					'height' => 0,
					'length' => 0,
					'weight' => 0
				);

				// Get order and items.
				$order = wc_get_order($order_id);
				$items = $order->get_items();

				// Check for pre-existing voucher.
				$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
				if ($voucher == false) {


					// Check for existing items in order.
					if (empty($items)) {
						throw new Exception('no-products-error');
					}

					// Handle each item included in the order.
					foreach ($items as $item) {
						$product = wc_get_product($item->get_product_id());

						// Check for active Biz synchronization status.
						if (get_post_meta($product->get_id(), 'biz_sync', true) == 'synced') {
							array_push($shipment_products, $product->get_sku() . ":" . $item->get_quantity());

							// Calculate total dimensions.
							if (
								$product->get_width() != "" &&
								$product->get_height() != "" &&
								$product->get_length() != "" &&
								$product->get_weight() != ""
							) {
								$total_order_volume['width'] += $product->get_width() * $item->get_quantity();
								$total_order_volume['height'] += $product->get_height() * $item->get_quantity();
								$total_order_volume['length'] += $product->get_length() * $item->get_quantity();
								$total_order_volume['weight'] += $product->get_weight() * $item->get_quantity();
							} else {
								throw new Exception('metrics-error');
							}
						} else {
							throw new Exception('sku-error');
						}
					}

					// Check Biz item list sufficiency.
					if (empty($shipment_products)) {
						throw new Exception('products-error');
					}

					// Get Cash On Delivery amount.
					if ($order->get_payment_method() == 'cod') {
						$cash_on_delivery = $order->get_total();
						$cash_on_delivery = number_format($cash_on_delivery, 2);
					}

					// Create SMS notification setting.
					$sms_notification = "0";
					if (isset($order->get_billing_phone())) {
						$sms_notification = ($biz_shipping_settings['biz_sms_notifications'] == "yes") ? "1" : "0"; 
					} 
					else {
						if ($biz_shipping_settings['biz_sms_notifications'] == "yes") {
							throw new Exception('sms-error');
						}
					}

					// Prepare SOAP query.
					$shipment_data = array(
						'Code' => $biz_settings['account_number'],
						'CRM' => $biz_settings['warehouse_crm'],
						'User' => $biz_settings['username'],
						'Pass' => $biz_settings['password'],
						"R_Name" => truncate_field($order->get_shipping_last_name() . " " . $order->get_shipping_first_name()),
						"R_Address" => truncate_field($order->get_shipping_address_1() . " " . $order->get_shipping_address_2()),
						"R_Area_Code" => $order->get_shipping_country(),
						"R_Area" => truncate_field($order->get_shipping_city()),
						"R_PC" => $order->get_shipping_postcode(),
						"R_Phone1" => $order->get_billing_phone(),
						"R_Phone2" => "",
						"R_Email" => truncate_field($order->get_billing_email(), 60),
						"Length" => $total_order_volume['length'], // cm int
						"Width" => $total_order_volume['width'], // cm int
						"Height" => $total_order_volume['height'], // cm int
						"Weight" => $total_order_volume['weight'], // kg int
						"Prod" => explode(":", $shipment_products[0])[0],
						"Pieces" => explode(":", $shipment_products[0])[1],
						"Multi_Prod" => implode("#", $shipment_products),
						"Cash_On_Delivery" => $cash_on_delivery,
						"Checques_On_Delivery" => "", // Unsupported.
						"Comments" => ((str_contains($order->get_shipping_method(), "Σαββάτου") || str_contains($order->get_shipping_method(), "Saturday")) ? "Saturday delivery" : "") . "\nRecipient comments: " . ($order->get_customer_note() ?? "none"),
						"Charge" => "3", // Unsupported, always 3.
						"Type" => "2", // Unsupported, always assume parcel.
						"Relative1" => "", // Unsupported.
						"Relative2" => "", // Unsupported.
						"Delivery_Time_To" => "", // Unsupported.
						"SMS" => ,
						"Special_Treatment" => "", // Unsupported.
						"Protocol" => "", // Unsupported.
						"Morning_Delivery" => (str_contains($order->get_shipping_method(), "Πρωινή") || str_contains($order->get_shipping_method(), "Morning")) ? "yes" : "",
						"Buy_Amount" => "", // Unsupported.
						"Pick_Up" => "", // Unsupported.
						"Service_Type" => "", // Unsupported.
						"Relabel" => "", // Unsupported.
						"Con_Call" => "0", // Unsupported.
						"Ins_Amount" => "" // Unsupported.
					);

					// Make SOAP call.
					$response = $client->__soapCall('newShipment', $shipment_data);

					// Handle error codes from response.
					switch ($response->Error_Code) {
						case 0:
							if (isset($response->Voucher)) {
								update_post_meta($order->get_id(), '_biz_voucher', $response->Voucher);
								if ($order->get_status() != 'processing') {
									$order->update_status('processing');
								}
								$order->add_order_note(__('The shipment was successfully registered to Biz Courier.', 'wc-biz-courier-logistics'));
							} else {
								throw new Exception('response-data-error');
							}
							break;
						case 1:
							throw new Exception('auth-error');
						case 2:
						case 3:
						case 4:
						case 5:
						case 6:
						case 10:
						case 11:
							throw new Exception('recipient-info-error');
						case 7:
						case 8:
						case 9:
						case 12:
							throw new Exception('package-data-error');
					}
				}
			} catch (SoapFault $fault) {
				throw new Exception('conn-error');
			}
		}
	}

	/**
	 * Handles shipment creation AJAX requests from authorized users.
	 *
	 * @since    1.0.0
	 */
	function biz_send_shipment_handler()
	{
		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_send_shipment_validation')) {
			die("Unverified request to send shipment.");
		}

		// Attempt to send shipment using POST data.
		try {
			WC_Biz_Courier_Logistics_Admin::biz_send_shipment(intval($_POST['order_id']), false);
		} catch (Exception $e) {
			echo $e->getMessage();
			error_log("Error contacting Biz Courier - " . $e->getMessage());
		}

		// Return success.
		die();
	}


	/**
	 * Modify a shipment with Biz.
	 *
	 * @since    1.0.0
	 */
	static function biz_modify_shipment(int $order_id, string $message = "", bool $automated = true)
	{
		try {
			
			$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');
			if (($automated && $biz_shipping_settings['automatic_shipment_cancellation'] == 'yes') || $automated == false) {
				// Initialize client.
				$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl", array(
					'trace' => 1,
					'encoding' => 'UTF-8',
				));

				// Get Biz settings.
				$biz_settings = get_option('woocommerce_biz_integration_settings');

				// Get saved order voucher.
				$order = wc_get_order($order_id);
				$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
				if ($voucher == false) {
					throw new Exception('data-error');
				}

				$biz_message = $message;
				if ($message == 'cancel') {
					$biz_message = __("Order got cancelled, please cancel the shipment.", "wc-biz-courier-logistics");
				}

				// Prepare request data.
				$modification_data = array(
					'Code' => $biz_settings['account_number'],
					'CRM' => $biz_settings['warehouse_crm'],
					'User' => $biz_settings['username'],
					'Pass' => $biz_settings['password'],
					'voucher' => $voucher,
					'modification' => utf8_encode($biz_message)
				);

				// Make SOAP call.
				$response = $client->__soapCall('modifyShipment', $modification_data);

				// Handle error codes from response.
				if ($response->Error == 0) {
					if ($message == 'cancel') {
						delete_post_meta($order->get_id(), '_biz_voucher');
						$order->add_order_note(sprintf(__("The Biz shipment with tracking code %s was cancelled.", "wc-biz-courier-logistics"), $voucher));
					} else {
						$order->add_order_note(__("Message sent to Biz: ", "wc-biz-courier-logistics") . $message . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")");
					}
				} else {
					throw new Exception($response->Error);
				}
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}

	function biz_cancel_shipment($order_id)
	{
		if (get_option('woocommerce_biz_shipping_method_settings')['automatic_shipment_cancellation'] == 'yes') {
			WC_Biz_Courier_Logistics_Admin::biz_modify_shipment($order_id, 'cancel');
		}
	}

	/**
	 * Handles shipment modification AJAX requests from authorized users.
	 *
	 * @since    1.0.0
	 */
	function biz_modify_shipment_handler()
	{
		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_modify_shipment_validation')) {
			die("Unverified request to modify shipment.");
		}

		// Attempt to send shipment using POST data.
		try {
			WC_Biz_Courier_Logistics_Admin::biz_modify_shipment(intval($_POST['order_id']), $_POST['shipment_modification_message'], false);
		} catch (Exception $e) {
			echo $e->getMessage();
			error_log("Error contacting Biz Courier - " . $e->getMessage());
		}

		// Return success.
		die();
	}

	/**
	 * Add tracking code field to e-mail order confirmation.
	 *
	 * @since    1.0.0
	 * @uses 	 get_post_meta()
	 * @param 	 array $fields The active list of email fields handled by WooCommerce.
	 * @param 	 WC_Order $order The current WooCommerce order.
	 */
	function add_biz_email_order_fields($fields, $sent_to_admin, $order)
	{
		$fields['biz_tracking_code'] = array(
			'label' => __('Tracking Code', 'wc-biz-courier-logistics'),
			'value' => get_post_meta($order->get_id(), '_biz_voucher', true),
		);
		return $fields;
	}


	/**
	 * Get the status history of a Biz Courier shipment using the stored voucher number.
	 *
	 * @since    1.0.0
	 * @uses 	 __soapCall()
	 * @param 	 string $voucher The voucher code associated with the Biz shipment.
	 */
	static function biz_shipment_status($voucher)
	{
		// Initialize SOAP client.
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl", array(
			'encoding' => 'UTF-8',
		));

		// Attempt to get the status history from Biz.
		try {
			$response = $client->__soapCall("full_status", array("Voucher" => $voucher));

			// Check for invalid voucher.
			if (empty($response)) {
				throw new Exception("voucher-error");
			}

			// Return a simplified array.
			return array_map(function ($status) {
				$single_status = array();
				$single_status['code'] = $status->Status_Code;
				$single_status['description'] = (get_locale() == 'el') ? $status->Status_Description : $status->Status_Description_En;
				$single_status['action'] = (get_locale() == 'el') ? $status->Action_Description : $status->Action_Description_En;
				$single_status['date'] = $status->Action_Time;
				$single_status['time'] = $status->Action_Date;
				return $single_status;
			}, $response);
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}

	/**
	 * Add Biz Courier connection indicator metabox to a single order.
	 *
	 * @since    1.0.0
	 * @uses wp_enqueue_script()
	 * @uses wp_localize_script()
	 */
	function add_biz_shipment_meta_box()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

		/**
		 * Print the metabox.
		 *
		 * @since    1.0.0
		 * @uses 	 wc_get_order()
		 * @uses 	 get_post_meta()
		 * @uses 	 add_meta_box()
		 * @uses 	 biz_track_shipment_meta_box_html()
		 * @uses	 biz_track_shipment_meta_box_error_html()
		 * @uses 	 wp_enqueue_script()
		 * @uses 	 wp_localize_script()
		 * @param 	 WP_Post $post The current post.
		 */
		function biz_shipment_meta_box($post)
		{
			// Get order and any voucher data.
			$order = wc_get_order($post->ID);
			$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);

			// Handle existing voucher.
			if (!empty($voucher)) {
				try {
					$status_history = WC_Biz_Courier_Logistics_Admin::biz_shipment_status($voucher);

					// Enqueue and localize button scripts.
					wp_enqueue_script('wc-biz-courier-logistics-modify-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-modify-shipment.js', array('jquery'));
					wp_localize_script('wc-biz-courier-logistics-modify-shipment', "ajax_prop", array(
						"ajax_url" => admin_url('admin-ajax.php'),
						"nonce" => wp_create_nonce('ajax_modify_shipment_validation'),
						"order_id" => $order->get_id(),
						"delete_confirmation" => __("Are you sure you want to cancel this Biz shipment? If you want to send it again, you will receive a new tracking code.", "wc-biz-courier-logistics"),
						"modification_message" => __("Please insert the message you want to send to Biz about the shipment.", "wc-biz-courier-logistics")
					));

					biz_track_shipment_meta_box_html($voucher, $status_history);
				} catch (Exception $e) {
					biz_track_shipment_meta_box_error_html($e->getMessage());
				}
			}
			// Show "Send Shipment" meta box.
			else {

				// Enqueue and localize button scripts.
				wp_enqueue_script('wc-biz-courier-logistics-send-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-send-shipment.js', array('jquery'));
				wp_localize_script('wc-biz-courier-logistics-send-shipment', "ajax_prop", array(
					"ajax_url" => admin_url('admin-ajax.php'),
					"nonce" => wp_create_nonce('ajax_send_shipment_validation'),
					"order_id" => $order->get_id()
				));

				// Print HTML.
				biz_send_shipment_meta_box_html();
			}
		}

		// Ensure the administrator is on the "Edit" screen and not "Add".
		if (get_current_screen()->action != 'add') {

			// Add the meta box.
			add_meta_box('wc-biz-courier-logistics_send_shipment_meta_box', __('Biz Courier status', 'wc-biz-courier-logistics'), 'biz_shipment_meta_box', 'shop_order', 'side', 'high');
		}
	}
}
