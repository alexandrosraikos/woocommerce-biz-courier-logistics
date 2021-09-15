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
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-integration.php';
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
	 * 
	 * Display Biz Warehouse option to include each product in the stock synchronisation process.
	 *
	 * @since    1.2.0
	 * @uses 	 get_post_meta()
	 * @uses 	 biz_stock_sync_column_html()
	 */
	function biz_product_inventory_options()
	{
		global $post;

		// Print the checkbox.
		echo '<div class="product_biz_stock_sync">';
		woocommerce_wp_checkbox(
			array(
				'id' => '_biz_stock_sync',
				'label' => __('Biz Warehouse', 'wc-biz-courier-logistics'),
				'description' => __('Select this option if the product is stored in your Biz warehouse.', 'wc-biz-courier-logistics'),
				'value' => get_post_meta($post->ID, '_biz_stock_sync', true)
			)
		);
		echo '</div>';

		// Print additional stock synchronisation status.
		if (get_post_meta($post->ID, '_biz_stock_sync', true) == 'yes') {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

			echo '<div class="biz_sync-indicator-container"><div class="biz_sync-indicator-title">' . __('Biz status', 'wc-biz-courier-logistics') . ': </div>';

			biz_stock_sync_column_html(get_post_meta($post->ID, '_biz_stock_sync_status', true));

			echo '</div>';
		}
	}


	/**
	 * 
	 * Handle product option persistence for the Biz warehouse stock synchronisation.
	 *
	 * @since    1.2.0
	 * @uses 	 get_post_meta()
	 * @uses 	 update_post_meta()
	 * @uses 	 delete_post_meta()
	 */
	function biz_save_product_inventory_options($post_id)
	{
		$product = wc_get_product($post_id);

		// On SKU modification.
		if ($_POST['_sku'] != $product->get_sku()) {
			if (get_post_meta($post_id, '_biz_stock_sync', true) == 'yes') {
				update_post_meta($post_id, '_biz_stock_sync_status', 'pending');
			}
		}

		// On stock sync preference change.
		if (!empty($_POST['_biz_stock_sync'])) {
			update_post_meta($post_id, '_biz_stock_sync', $_POST['_biz_stock_sync']);
			update_post_meta($post_id, '_biz_stock_sync_status', 'pending');
		} else {
			update_post_meta($post_id, '_biz_stock_sync', 'no');
			delete_post_meta($post_id, '_biz_stock_sync_status');
		}
	}


	/**
	 * 
	 * Display Biz Warehouse option to include each product variation in the stock synchronisation process.
	 *
	 * @since    1.2.0
	 * @uses 	 get_post_meta()
	 * @uses 	 biz_stock_sync_column_html()
	 */
	function biz_variation_inventory_options($loop, $variation_data, $variation)
	{
?>
		<label class="tips" data-tip="<?php _e('Select this option if the product is stored in your Biz warehouse.', 'wc-biz-courier-logistics'); ?>">
			<?php _e('Biz Warehouse', 'wc-biz-courier-logistics'); ?>
			<input type="checkbox" class="checkbox variable_checkbox" name="_biz_stock_sync[<?php echo esc_attr($loop); ?>]" <?php echo (get_post_meta($variation->ID, '_biz_stock_sync', true) == 'yes' ? 'checked' : ''); ?> />
		</label>
<?php
		if (get_post_meta($variation->ID, '_biz_stock_sync', true) == 'yes') {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

			echo '<div class="biz_sync-variation-indicator-title">' . __('Biz status', 'wc-biz-courier-logistics') . ': </div>';

			biz_stock_sync_column_html(get_post_meta($variation->ID, '_biz_stock_sync_status', true));
		}
	}


	/**
	 * 
	 * Handle product variation option persistence for the Biz warehouse stock synchronisation.
	 *
	 * @since    1.2.0
	 * @uses 	 update_post_meta()
	 * @uses 	 delete_post_meta()
	 */
	function biz_save_variation_inventory_options($variation_id, $i)
	{
		$variation = wc_get_product($variation_id);

		// If stock sync is enabled.
		if (!empty($_POST['_biz_stock_sync'][$i])) {
			update_post_meta($variation_id, '_biz_stock_sync', $_POST['_biz_stock_sync'][$i] == 'on' ? 'yes' : 'no');
			update_post_meta($variation_id, '_biz_stock_sync_status', 'pending');
		}
		// If stock sync is disabled.
		else {
			update_post_meta($variation_id, '_biz_stock_sync', 'no');
			delete_post_meta($variation_id, '_biz_stock_sync_status');
		}

		// If SKU changed.
		if ($_POST['variable_sku'][$i] != $variation->get_sku()) {
			update_post_meta($variation_id, '_biz_stock_sync_status', 'pending');
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

		// Get SKUs from all products.
		$products = wc_get_products(array(
			'limit' => -1,
		));
		$all_skus = array();
		if (!empty($products)) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-stock-synchronization.php';
			foreach ($products as $product) {
				$all_skus = array_merge($all_skus, get_all_related_skus($product));
			}
		}

		// Attempt stock synchronization using all SKUs.
		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-stock-synchronization.php';

			biz_stock_sync($all_skus);
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

		// Enqeue & localize synchronization button script.
		wp_enqueue_script('wc-biz-courier-logistics-stock-sync', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-stock-sync.js', array('jquery'));
		wp_localize_script('wc-biz-courier-logistics-stock-sync', "ajax_prop", array(
			"ajax_url" => admin_url('admin-ajax.php'),
			"nonce" => wp_create_nonce('ajax_stock_sync_validation'),
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

				if (get_post_meta($product_post_id, '_biz_stock_sync', true) == 'yes') {
					$status = get_post_meta($product_post_id, '_biz_stock_sync_status', true);
				} else {
					$status = 'disabled';
				}

				// Get children variations' synchronization status.
				$children_ids = $product->get_children();
				if (!empty($children_ids)) {
					foreach ($children_ids as $child_id) {
						if (get_post_meta($child_id, '_biz_stock_sync', true) == 'no') {
							continue;
						}
						$child_status = get_post_meta($child_id, '_biz_stock_sync_status', true);
						if ($status == 'synced' && $child_status == 'not-synced') {
							$status = 'partial';
							continue;
						}
						if ($status == 'not-synced' && $child_status == 'synced') {
							$status = 'partial';
							continue;
						}
						if ($child_status == 'pending') {
							$status = 'pending';
							continue;
						}
					}
				};

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
		if (!class_exists('Biz_Shipping_Method')) require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipping-method.php';
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
	 * Handles shipment creation AJAX requests from authorized users.
	 *
	 * @since    1.0.0
	 */
	function biz_send_shipment_handler()
	{
		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_send_shipment_validation')) {
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to send shipment.'
			]));
		}

		// Send shipment using POST data and handle errors.
		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
			if (biz_send_shipment(intval($_POST['order_id']))) die("OK");
		} catch (UnexpectedValueException $e) {
			$error_code = $e->getMessage();
			switch ($error_code) {
				case 'no-products-error':
					$error_description = __("There are no products in this order.",'wc-biz-courier-logistics');
					break;
				case 'stock-error':
					$error_description = __("There isn't enough stock to ship some products.",'wc-biz-courier-logistics');
					break;
				case 'metrics-error':
					$error_description = __("Please make sure all products in the order have their weight & dimensions registered.",'wc-biz-courier-logistics');
					break;
				case 'sku-error':
					$error_description = __("Some products were not found in the Biz warehouse.", 'wc-biz-courier-logistics');
					break;
				case 'recipient-info-error':
					$error_description = __("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields: First name, last name, phone number, e-mail address, address line #1, city, postal code and country.", 'wc-biz-courier-logistics');
					break;
				default:
					$error_description = __("An unknown error occured while processing order information.", 'wc-biz-courier-logistics');
			}
			die(json_encode([
				'error-code' => $error_code,
				'error-description' => $error_description
			]));
		} catch (LogicException $e) {
			$error_code = $e->getMessage();
			switch ($error_code) {
				case "voucher-exists-error":
					$error_description = __("A voucher already exists for this order.", 'wc-biz-courier-logistics');
					break;
				default:
					$error_description = __("An unknown error occured after processing order information.", 'wc-biz-courier-logistics');
			}
			die(json_encode([
				'error-code' => $error_code,
				'error-description' => $error_description
			]));
		} catch (ErrorException $e) {
			$error_code = $e->getMessage();
			switch ($error_code) {
				case 'biz-response-data-error':
					$error_description = __("Response from Biz could not be read, please check your warehouse shipments from their official application.",'wc-biz-courier-logistics');
					break;
				case 'biz-auth-error':
					$error_description = __("There was an error with your Biz credentials.",'wc-biz-courier-logistics');
					break;
				case 'biz-recipient-info-error':
					$error_description = __("There was a problem registering recipient information with Biz. Please check your recipient information entries.",'wc-biz-courier-logistics');
					break;
				case 'biz-package-data-error':
					$error_description = __("There was a problem with your order's items while submitting their information to Biz.", 'wc-biz-courier-logistics');
					break;
				default:
					$error_description = __("An unknown Biz error occured after submitting the shipment information.", 'wc-biz-courier-logistics');
			}
			die(json_encode([
				'error-code' => $error_code,
				'error-description' => $error_description
			]));
		} catch (SoapFault $f) {
			die(json_encode([
				'error_code' => 'connection-error',
				'error_description' => __('There was a connection error while trying to contact Biz Courier. More information: ', 'wc-biz-courier-logistics') . $f->getMessage()
			]));
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
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to modify shipment.'
			]));
		}

		// Attempt to send shipment using POST data.
		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
			if ($_POST['shipment_modification_message'] == 'cancel') {
				if (biz_cancel_shipment(intval($_POST['order_id']))) die("OK");
			} else {
				if (biz_modify_shipment(intval($_POST['order_id']), $_POST['shipment_modification_message'], false)) die("OK");
			}
		} catch (UnexpectedValueException $e) {
			die(json_encode([
				'error-code' => $e->getMessage(),
				'error-description' => 'There was a problem retrieving the voucher for this order.'
			]));
		} catch (ErrorException $e) {
			die(json_encode([
				'error_code' => $e->getMessage(),
				'error_description' => 'An error was encountered by Biz Courier while processing the order update message.'
			]));
		} catch (SoapFault $f) {
			die(json_encode([
				'error_code' => 'connection-error',
				'error_description' => 'There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage()
			]));
		}
	}

	/**
	 * Handles manual voucher addition AJAX requests from authorized users.
	 *
	 * @since    1.2.0
	 */
	function biz_add_shipment_voucher_handler()
	{

		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_add_shipment_voucher_validation')) {
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to add shipment voucher.'
			]));
		}

		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';

			// Validate voucher.
			$status_report = biz_shipment_status($_POST['voucher']);
			if (!empty($status_report)) {
				update_post_meta($_POST['order_id'], '_biz_voucher', $_POST['voucher']);
				if (biz_conclude_order_status($_POST['order_id'], $status_report)) die("OK");
			}
		} catch (ErrorException $e) {
			die(json_encode([
				'error_code' => $e->getMessage(),
				'error_description' => 'An error was encountered by Biz Courier while processing the voucher. Is it a valid shipment voucher?'
			]));
		} catch (SoapFault $f) {
			die(json_encode([
				'error_code' => 'connection-error',
				'error_description' => 'There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage()
			]));
		}

		die();
	}

	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @since    1.2.0
	 */
	function biz_delete_shipment_voucher_handler()
	{

		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_delete_shipment_voucher_validation')) {
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to delete the shipment voucher.'
			]));
		}

		if (delete_post_meta($_POST['order_id'], '_biz_voucher')) die("OK");
		else {
			die(json_encode([
				'error-code' => 'unknown-error',
				'error-description' => 'The shipment voucher could not be deleted.'
			]));
		}
	}

	/**
	 * Handles manual voucher editing AJAX requests from authorized users.
	 *
	 * @since    1.2.0
	 */
	function biz_edit_shipment_voucher_handler()
	{

		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_edit_shipment_voucher_validation')) {
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to edit the shipment voucher.'
			]));
		}

		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';

			// Validate voucher.
			$report = biz_shipment_status($_POST['voucher']);

			// Update order information.
			update_post_meta($_POST['order_id'], '_biz_voucher', $_POST['voucher']);
			die("OK");
		} catch (ErrorException $e) {
			die(json_encode([
				'error_code' => $e->getMessage(),
				'error_description' => 'An error was encountered by Biz Courier while processing the voucher. Is it a valid shipment voucher?'
			]));
		} catch (SoapFault $f) {
			die(json_encode([
				'error_code' => 'connection-error',
				'error_description' => 'There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage()
			]));
		}
	}


	// TODO: Check automation logic again.
	/**
	 * Handles shipment modification on order change.
	 *
	 * @since    1.0.0
	 */
	function biz_order_changed_handler($id, $from, $to)
	{
		// Automate shipment functionality.
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		// Automatic creation.
		if (($biz_settings['automatic_shipment_creation'] ?? 'disabled') != 'disabled') {
			if (
				substr($biz_settings['automatic_shipment_creation'], 3) == $to
			) {
				// Send shipment using POST data and handle errors.
				try {
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
					biz_send_shipment($id);
				} catch (UnexpectedValueException $e) {
					$error_code = $e->getMessage();
					switch ($error_code) {
						case 'no-products-error':
							$error_description = __("There are no products in this order.",'wc-biz-courier-logistics');
							break;
						case 'stock-error':
							$error_description = __("There isn't enough stock to ship some products.",'wc-biz-courier-logistics');
							break;
						case 'metrics-error':
							$error_description = __("Please make sure all products in the order have their weight & dimensions registered.",'wc-biz-courier-logistics');
							break;
						case 'sku-error':
							$error_description = __("Some products were not found in the Biz warehouse.", 'wc-biz-courier-logistics');
							break;
						case 'recipient-info-error':
							$error_description = __("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields: First name, last name, phone number, e-mail address, address line #1, city, postal code and country.", 'wc-biz-courier-logistics');
							break;
						default:
							$error_description = __("An unknown error occured while processing order information.", 'wc-biz-courier-logistics');
					}
				} catch (LogicException $e) {
					$error_code = $e->getMessage();
					switch ($error_code) {
						case "voucher-exists-error":
							$error_description = __("A voucher already exists for this order.", 'wc-biz-courier-logistics');
							break;
						default:
							$error_description = __("An unknown error occured after processing order information.", 'wc-biz-courier-logistics');
					}
				} catch (ErrorException $e) {
					$error_code = $e->getMessage();
					switch ($error_code) {
						case 'biz-response-data-error':
							$error_description = __("Response from Biz could not be read, please check your warehouse shipments from their official application.",'wc-biz-courier-logistics');
							break;
						case 'biz-auth-error':
							$error_description = __("There was an error with your Biz credentials.",'wc-biz-courier-logistics');
							break;
						case 'biz-recipient-info-error':
							$error_description = __("There was a problem registering recipient information with Biz. Please check your recipient information entries.",'wc-biz-courier-logistics');
							break;
						case 'biz-package-data-error':
							$error_description = __("There was a problem with your order's items while submitting their information to Biz.", 'wc-biz-courier-logistics');
							break;
						default:
							$error_description = __("An unknown Biz error occured after submitting the shipment information.", 'wc-biz-courier-logistics');
					}
				} catch (SoapFault $f) {
					$error_description = __('There was a connection error while trying to contact Biz Courier. More information: ', 'wc-biz-courier-logistics') . $f->getMessage();
				}

				if(!empty($error_description)) update_post_meta($id, '_biz_internal_error',$error_description);
			}
		}

		// Automatic cancellation.
		if (($biz_settings['automatic_shipment_cancellation'] ?? 'disabled') != 'disabled') {
			if (
				substr($biz_settings['automatic_shipment_cancellation'], 3) == $to &&
				substr($biz_settings['automatic_shipment_cancellation'], 3) != $from
			) {
				try {
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
					biz_cancel_shipment($id);
				} catch (UnexpectedValueException $e) {
					error_log($e->getMessage() . ': There was a problem retrieving the voucher for this order.');
				} catch (ErrorException $e) {
					error_log($e->getMessage() . ': An error was encountered by Biz Courier while processing the order update message.');
				} catch (SoapFault $f) {
					error_log('connection-error: There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage());
				}
			}
		}
	}



	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @since    1.2.0
	 */
	function biz_synchronize_order_handler()
	{

		// Verify WordPress generated nonce.
		if (!wp_verify_nonce($_POST['nonce'], 'ajax_synchronize_order_validation')) {
			die(json_encode([
				'error-code' => 'auth-error',
				'error-description' => 'Unverified request to edit the shipment voucher.'
			]));
		}

		try {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
			if (biz_conclude_order_status($_POST['order_id'])) die("OK");
		} catch (ErrorException $e) {
			die(json_encode([
				'error_code' => $e->getMessage(),
				'error_description' => 'An error was encountered by Biz Courier while processing the voucher. Is it a valid shipment voucher?'
			]));
		} catch (SoapFault $f) {
			die(json_encode([
				'error_code' => 'connection-error',
				'error_description' => 'There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage()
			]));
		}
	}

	function add_biz_order_voucher_column($columns){
		$columns['biz-voucher'] = __("Biz shipment voucher", 'wc-biz-courier-logistics');
		return $columns;
	}

	function biz_order_voucher_column($column, $post_id) {
		switch($column) {
			case 'biz-voucher':
				$voucher = get_post_meta($post_id,'_biz_voucher',true);
				if (empty($voucher)) {
					echo '<span>-</span>';
				}
				else {
					echo '<a href="https://trackit.bizcourier.eu/app/'.get_locale().'/'.$voucher.'" target="blank">'.$voucher.'</a>';
				}
			break;
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
			function prepare_scripts_new_shipment($order_id)
			{
				// Enqueue and localize send new shipment.
				wp_enqueue_script('wc-biz-courier-logistics-new-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-new-shipment.js', array('jquery'));
				wp_localize_script('wc-biz-courier-logistics-new-shipment', "ajax_prop", array(
					"ajax_url" => admin_url('admin-ajax.php'),
					"send_shipment_nonce" => wp_create_nonce('ajax_send_shipment_validation'),
					"add_shipment_voucher_nonce" => wp_create_nonce('ajax_add_shipment_voucher_validation'),
					"order_id" => $order_id,
					"add_voucher_message" => __("Insert the shipment's voucher number from Biz Courier in the field below.", "wc-biz-courier-logistics")
				));
			}

			function prepare_scripts_existing_shipment($order_id)
			{
				// Enqueue and localize button scripts.
				wp_enqueue_script('wc-biz-courier-logistics-existing-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-existing-shipment.js', array('jquery'));
				wp_localize_script('wc-biz-courier-logistics-existing-shipment', "ajax_prop", array(
					"ajax_url" => admin_url('admin-ajax.php'),
					"modify_shipment_nonce" => wp_create_nonce('ajax_modify_shipment_validation'),
					"synchronize_order_nonce" => wp_create_nonce('ajax_synchronize_order_validation'),
					"edit_shipment_voucher_nonce" => wp_create_nonce('ajax_edit_shipment_voucher_validation'),
					"delete_shipment_voucher_nonce" => wp_create_nonce('ajax_delete_shipment_voucher_validation'),
					"order_id" => $order_id,
					"cancellation_request_confirmation" => __("Are you sure you want to request the cancellation of this Biz shipment? If you want to send it again, you will receive a new tracking code.", "wc-biz-courier-logistics"),
					"modification_message" => __("Please insert the message you want to send to Biz about the shipment.", "wc-biz-courier-logistics"),
					"edit_voucher_message" => __("Insert the new shipment voucher number from Biz Courier in the field below.", "wc-biz-courier-logistics"),
					"delete_confirmation" => __("Are you sure you want to delete the shipment voucher from this order?", "wc-biz-courier-logistics")
				));
			}

			// Get order and any voucher data.
			$order = wc_get_order($post->ID);
			$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
			try {
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
				if (!empty($voucher)) {
					$report = biz_shipment_status($voucher);
				}
			} catch (ErrorException $e) {
				die(json_encode([
					'error_code' => $e->getMessage(),
					'error_description' => 'An error was encountered by Biz Courier while processing the voucher.'
				]));
			} catch (SoapFault $f) {
				die(json_encode([
					'error_code' => 'connection-error',
					'error_description' => 'There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage()
				]));
			}
			if (!empty($voucher)) prepare_scripts_existing_shipment($order->get_id());
			else prepare_scripts_new_shipment($order->get_id());
			$potential_internal_error = get_post_meta($order->get_id(), '_biz_internal_error',true);
			if (!empty($potential_internal_error)) {
				delete_post_meta($order->get_id(), '_biz_internal_error');
			}
			biz_shipment_status_tracking_metabox_html($order->get_status(), $voucher ?? null, $report ?? null, $potential_internal_error);
		}

		// Ensure the administrator is on the "Edit" screen and not "Add".
		if (get_current_screen()->action != 'add') {

			// Add the meta box.
			add_meta_box('wc-biz-courier-logistics_send_shipment_meta_box', __('Biz Courier status', 'wc-biz-courier-logistics'), 'biz_shipment_meta_box', 'shop_order', 'side', 'high');
		}
	}


	/**
	 * Check for cancelled & completed orders.
	 *
	 * @since    1.2.0
	 */
	function biz_cron_order_status_checking()
	{
		$biz_settings = get_option('woocommerce_biz_integration_settings');
		if ($biz_settings['automatic_order_status_updating'] == 'yes') {
			$orders = wc_get_orders(array(
				'status' => array('wc-processing'),
				'return' => 'ids'
			));
			foreach ($orders as $order_id) {
				$voucher = get_post_meta($order_id, '_biz_voucher', true);
				if (!empty($voucher)) {
					try {
						require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
						biz_conclude_order_status($order_id);
					} catch (ErrorException $e) {
						error_log($e->getMessage() . ': ' . 'An error was encountered by Biz Courier while processing the voucher.');
					} catch (SoapFault $f) {
						error_log('connection-error: There was a connection error while trying to contact Biz Courier. More information: ' . $f->getMessage());
					}
				} else {
					error_log("voucher-error: The voucher used to automatically update order information is empty.");
				}
			}
		}
	}
}
