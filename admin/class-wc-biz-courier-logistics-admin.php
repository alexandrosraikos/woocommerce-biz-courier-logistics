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
	 * 	Generic
	 * 	------------
	 *  This section provides all the generic plugin functionality.
	 */


	/**
	 * Notify the administrator of minimum hosting requirements.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.2
	 * 
	 * @version 1.4.0
	 */
	public function check_minimum_requirements()
	{
		// Check for PHP and loaded extensions.
		if (version_compare(phpversion(), '7.4.0') < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("This version of PHP is not supported by Biz Courier & Logistics for WooCommerce. Please update to PHP 7.4.0 or later."), 'wc-biz-courier-logistics');
		}
		if (!extension_loaded('soap')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("You need to enable the soap extension in your PHP installation in order to use Biz Courier & Logistics features. Please contact your server administrator.", "wc-biz-courier-logistics"));
		}

		// Check for supported WordPress version.
		global $wp_version;
		if (version_compare($wp_version, '5.7.0') < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("This version of WordPress is not supported by Biz Courier & Logistics for WooCommerce. Please update to version 5.7.0 or later."), 'wc-biz-courier-logistics');
		}

		// Check for installed WooCommerce.
		if (!class_exists('WooCommerce')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("Biz Courier & Logistics for WooCommerce requires the WooCommerce plugin to be installed and enabled.", 'wc-biz-courier-logistics'));
		}

		// Check for supported WooCommerce version.
		if (version_compare(WC_VERSION, '5.6.0') < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("This version of WooCommerce is not supported by Biz Courier & Logistics for WooCommerce. Please update to WooCommerce 5.6.0 or later.", 'wc-biz-courier-logistics'));
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		// TODO @alexandrosraikos: Clear and organize CSS.
		wp_register_style($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'css/wc-biz-courier-logistics-admin.css', array(), $this->version, 'all');
		wp_enqueue_style($this->WC_Biz_Courier_Logistics);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		// Global script options.
		wp_enqueue_script($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin.js', array('jquery'), $this->version, false);
		wp_localize_script($this->WC_Biz_Courier_Logistics, "GlobalProperties", array(
			"ajaxEndpointURL" => admin_url('admin-ajax.php')
		));

		// Scripts for stock.
		// TODO @alexandrosraikos: Clear and organize stock relevant JS.
		wp_register_script('wc-biz-courier-logistics-stock-sync', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-stock-sync.js', array('jquery'));

		// Scripts for shipments.
		wp_register_script('wc-biz-courier-logistics-new-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-new-shipment.js', array('jquery', $this->WC_Biz_Courier_Logistics));
		wp_register_script('wc-biz-courier-logistics-existing-shipment', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-existing-shipment.js', array('jquery'));
	}


	/**
	 * Add plugin action links.
	 * Add a link to the settings page on the plugins.php page.
	 *
	 * @param  array  $links List of existing plugin action links.
	 * 
	 * @return array List of modified plugin action links.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	function plugin_action_links($actions)
	{
		return array_merge(array(
			'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')) . '">' . __('Settings', 'wc-biz-courier-logistics') . '</a>'
		), $actions);
	}

	/**
	 * Add plugin action links.
	 *
	 * Add a link to the settings page on the plugins.php page.
	 *
	 * @since 1.3.0
	 *
	 * @param  array  $links List of existing plugin row meta links.
	 * @return array         List of modified plugin row meta links.
	 */
	function plugin_row_meta($links, $file)
	{
		// TODO @alexandrosraikos: Add code documentation.
		if (strpos($file, 'wc-biz-courier-logistics.php')) {
			$links[] = '<a href="https://github.com/sponsors/alexandrosraikos" target="blank">' . __('Donate via GitHub Sponsors', 'wc-biz-courier-logistics') . '</a>';
			$links[] = '<a href="https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/blob/main/README.md" target="blank">' . __('Documentation', 'wc-biz-courier-logistics') . '</a>';
			$links[] = '<a href="https://www.araikos.gr/en/contact/" target="blank">' . __('Support', 'wc-biz-courier-logistics') . '</a>';
		}
		return $links;
	}

	/**
	 * Trigger an asynchronous internal error display on load
	 * 
	 * @uses `biz_internal_error` A WP option which displays global errors.
	 * @uses `_biz_internal_error` A post meta key which indicates internal post specific errors.
	 * 
	 * @usedby `init` WP hook.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	static function async_error_display()
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';

		/** @var string $internal_error The internal persistent error. */
		$internal_error = get_option('biz_internal_error');

		// Get and show the error.
		if (!empty($internal_error)) notice_display_html($internal_error);

		// Delete the error after shown.
		delete_option('biz_internal_error');

		// Check for any internal errors in the active post.
		if (!empty($_GET['post'])) {

			// Get and show the error.
			$internal_error = get_post_meta($_GET['post'], '_biz_internal_error', true);
			if (!empty($internal_error)) notice_display_html($internal_error, 'error');

			// Delete the error from the metadata.
			delete_post_meta($_GET['post'], '_biz_internal_error', "This is a test.");
		}
	}

	/**
	 * An error registrar for asynchronous throwing functions.
	 * 
	 * @param callable $completion The action that needs to be done.
	 * @param int? $post_id The ID of the relevant post (optional).
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	static function async_handler($completion, $post_id = null): void
	{
		try {
			// Run completion function.
			$completion();
		} catch (\Exception $e) {
			if (empty($post_id)) {
				// Register an internal error specific to the post ID.
				update_post_meta($post_id, '_biz_internal_error', $e->getMessage());
			} else {
				// Register a generic internal error.
				update_option('biz_internal_error', $e->getMessage());
			}
		}
	}

	/**
	 * The generalized handler for AJAX calls.
	 * 
	 * @param string $action The action slug used in WordPress.
	 * @param callable $completion The callback for completed data.
	 * 
	 * @return void The function simply echoes the response to the 
	 */
	static function ajax_handler($completion): void
	{
		if (!wp_verify_nonce($_POST['nonce'], $_POST['action'])) {
			http_response_code(403);
			die("Unverified request for action: " . $_POST['action']);
		}

		if (str_contains($_POST['action'], 'shipment')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
		} elseif (str_contains($_POST['action'], 'stock')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-stock.php';
		}

		// Send shipment using POST data and handle errors.
		try {
			$data = $completion(array_filter($_POST, function ($key) {
				return ($key != 'action' && $key != 'nonce');
			}, ARRAY_FILTER_USE_KEY));
			http_response_code(200);
			$data = json_encode($data);
			if ($data == false) throw new ErrorException("There was an error while encoding the data to JSON.");
			else die(json_encode($data));
		} catch (RuntimeException $e) {
			http_response_code(400);
			die($e->getMessage());
		} catch (ErrorException $e) {
			http_response_code(500);
			die($e->getMessage());
		} catch (SoapFault $f) {
			http_response_code(502);
			error_log($f->getMessage());
			die();
		}
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
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.0.0
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
	 * @param 	 array $integrations The active list of WooCommerce Integrations.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.0.0
	 */
	function add_biz_integration($integrations)
	{
		$integrations[] = 'Biz_Integration';
		return $integrations;
	}

	/**
	 * Displays a WordPress notice depending on Biz credential and connection status.
	 *
	 * @uses notice_display_html()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
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
					notice_display_html(sprintf(__("Your Biz Courier credentials are invalid. Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')));
				} elseif ($_GET['biz_error'] == 'conn-error') {
					notice_display_html(__("There was an error contacting Biz Courier, please try again later.", "wc-biz-courier-logistics"));
				}
			} elseif (
				$biz_settings['account_number'] == null ||
				$biz_settings['warehouse_crm'] == null ||
				$biz_settings['username'] == null ||
				$biz_settings['password'] == null
			) {
				notice_display_html(sprintf(__("Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')), 'warning');
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
		// TODO @alexandrosraikos: Add code documentation.
		// TODO @alexandrosraikos: Split into display.php.
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
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	function biz_save_product_inventory_options($post_id)
	{

		/** @var WC_Product $product The product. */
		$product = wc_get_product($post_id);

		// Reset synchronization status on SKU change.
		if ($_POST['_sku'] != $product->get_sku()) {
			if (get_post_meta($post_id, '_biz_stock_sync', true) == 'yes') {
				update_post_meta($post_id, '_biz_stock_sync_status', 'pending');
			}
		}

		// Reset synchronization status on Biz Warehouse option change.
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
		// TODO @alexandrosraikos: Add code documentation.
		// TODO @alexandrosraikos: Split into display.php.
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
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
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
	 * @uses WC_Biz_Courier_Logistics_Admin::biz_stock_sync()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function biz_stock_synchronization_handler()
	{
		// TODO @alexandrosraikos: Display stock level synchronization errors. (#31 - https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/issues/31)

		WC_Biz_Courier_Logistics_Admin::ajax_handler(function () {

			/** @var WC_Product[] $products An array of all products. */
			$products = wc_get_products(array(
				'limit' => -1,
			));

			/** @var string[] $all_skus An array of all SKUs. */
			$all_skus = [];
			if (!empty($products)) {
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-stock.php';
				foreach ($products as $product) {
					$all_skus = array_merge($all_skus, get_all_related_skus($product));
				}
			}

			biz_stock_sync($all_skus);
		});
	}

	/**
	 * Add Biz Courier remaining stock synchronization button to the All Products page.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function add_biz_stock_sync_all_button()
	{
		// Ensure active All Products page.
		global $current_screen;
		if ('product' != $current_screen->post_type) {
			return;
		}

		// Enqeue & localize synchronization button script.
		wp_enqueue_script('wc-biz-courier-logistics-stock-sync');
		wp_localize_script('wc-biz-courier-logistics-stock-sync', "StockProperties", array(
			"bizStockSynchronizationNonce" => wp_create_nonce('biz_stock_synchronization_validation'),
		));

		// Insert button HTML.
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
		biz_stock_sync_all_button();
	}


	/**
	 * Add Biz Courier stock synchronisation status column to the All Products page.
	 *
	 * @param 	 array $columns The active list of columns in the All Products page.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function add_biz_stock_sync_indicator_column($columns)
	{
		// Append the Biz synchronization status column.
		$columns['biz_sync'] = __('Biz Warehouse', 'wc-biz-courier-logistics');

		return $columns;
	}

	/**
	 * Add Biz Courier stock synchronisation status indicator column to each product
	 * in the All Products page.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
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
						} else {
							$child_status = get_post_meta($child_id, '_biz_stock_sync_status', true);
							if ($status == 'synced' && $child_status == 'not-synced') {
								$status = 'partial';
								continue;
							}
							if ($status == 'not-synced' && $child_status == 'synced') {
								$status = 'partial';
								continue;
							}
							if ($status == 'disabled') {
								$status = $child_status;
							}
							if ($child_status == 'pending') {
								$status = 'pending';
								continue;
							}
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
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function biz_shipping_method()
	{
		// Import the Biz Shipping Method class.
		if (!class_exists('Biz_Shipping_Method')) require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipping-method.php';
	}

	/**
	 * Declare Biz_Shipping_Method class.
	 * 
	 * @param array $methods The active list of shipping methods.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function add_biz_shipping_method($methods)
	{
		// Append the new shipping method.
		$methods['biz_shipping_method'] = 'Biz_Shipping_Method';

		return $methods;
	}


	/**
	 * Calculate the additional COD fee for Biz shipping on checkout
	 * 
	 * @param object $cart The given checkout cart.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.3
	 * 
	 * @version 1.4.0
	 */
	function add_biz_cod_fee($cart)
	{
		// TODO @alexandrosraikos: Move to public.

		// Ignore AJAX call.
		if (is_admin() && defined('DOING_AJAX')) return;

		// Add the COD fee on COD payment methods.
		if (
			WC()->session->get('chosen_payment_method') == 'cod'
		) {
			/** @var array $biz_shipping_settings The Biz shipping settings. */
			$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

			// Add the registered fee amount.
			if (!empty($biz_shipping_settings['biz_cash_on_delivery_fee'])) {
				$cart->add_fee(__('Cash on Delivery fee', 'wc-biz-courier-logistics'), $biz_shipping_settings['biz_cash_on_delivery_fee']);
			}
		}
	}


	/**
	 * 	Order / Shipment Interactions
	 * 	------------
	 *  This section provides the necessary functionality for mapping, managing and displaying
	 * 	WooCommerce orders and their status to Biz Courier Shipments.
	 * 	
	 */

	/**
	 * Handles shipment creation AJAX requests from authorized users.
	 * NOTE: This is executed through WP's AJAX endpoint.
	 * 
	 * @param string $_POST['order_id'] The related order's ID.
	 *
	 * @since 1.0.0
	 * @version 1.4.0
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 */
	function biz_shipment_send_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function ($data) {
			biz_send_shipment(intval($data['order_id']));
		});
	}

	/**
	 * Handles manual voucher addition AJAX requests from authorized users.
	 * NOTE: This is executed through WP's AJAX endpoint.
	 * 
	 * @param string $_POST['voucher'] The new shipment voucher.
	 * @param string $_POST['order_id'] The related order's ID.
	 *
	 * @since 1.2.0
	 * @version 1.4.0
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 */
	function biz_shipment_add_voucher_handler(): void
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function ($data) {

			// Validate voucher.
			$status_report = biz_shipment_status($_POST['new_voucher']);
			if (!empty($status_report)) {
				if (!update_post_meta($data['order_id'], '_biz_voucher', $data['new_voucher'])) {
					throw new ErrorException("The shipment voucher could not be saved to this order.");
				}
				biz_conclude_order_status($_POST['order_id'], true, $status_report);
			}
		});
	}

	/**
	 * Handles shipment modification AJAX requests from authorized users.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.0.0
	 */
	function biz_shipment_modification_request_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function () {
			biz_modify_shipment(intval($_POST['order_id']), $_POST['shipment_modification_message']);
		});
	}

	/**
	 * Handles shipment cancellation AJAX requests from authorized users.
	 *
	 * @since    1.0.0
	 */
	function biz_shipment_cancellation_request_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function () {
			biz_cancel_shipment(intval($_POST['order_id']));
		});
	}

	/**
	 * Handles manual voucher editing AJAX requests from authorized users.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 */
	function biz_shipment_edit_voucher_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function ($data) {

			// Validate voucher.
			$status_report = biz_shipment_status($_POST['voucher']);
			if (!empty($status_report)) {
				if (!update_post_meta($data['order_id'], '_biz_voucher', $data['new_voucher'])) {
					throw new ErrorException("The shipment voucher could not be saved to this order.");
				}
			}
		});
	}

	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 */
	function biz_shipment_delete_voucher_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function () {
			if (!delete_post_meta($_POST['order_id'], '_biz_voucher')) {
				throw new ErrorException("The shipment voucher could not be deleted from this order.");
			}
		});
	}

	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 */
	function biz_shipment_synchronize_order_handler()
	{
		WC_Biz_Courier_Logistics_Admin::ajax_handler(function () {
			biz_conclude_order_status($_POST['order_id']);
		});
	}

	/**
	 * Handles shipment modification on order change.
	 *
	 * @param int $id The order ID.
	 * @param string $from The previous order status.
	 * @param string $to The next order status.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function biz_order_changed_handler($id, $from, $to)
	{
		/** @var array $biz_settings The integration settings. */
		$biz_settings = get_option('woocommerce_biz_integration_settings');


		// 1. Check existing options for automatic shipment creation.
		if (($biz_settings['automatic_shipment_creation'] ?? 'disabled') != 'disabled') {
			
			// Match preferred sending state on a voucher order.
			if (
				!empty(get_post_meta($id, '_biz_voucher', true)) &&
				substr($biz_settings['automatic_shipment_creation'], 3) == $to
			) {

				// Handle shipment sending.
				WC_Biz_Courier_Logistics_Admin::async_handler(function () use ($id) {
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
					biz_send_shipment($id);
				}, $id);

			}
		}

		// 2. Check existing options for automatic shipment cancellation.
		if (($biz_settings['automatic_shipment_cancellation'] ?? 'disabled') != 'disabled') {
			
			// Match preferred sending state on a voucher order.
			if (
				!empty(get_post_meta($id, '_biz_voucher', true)) &&
				substr($biz_settings['automatic_shipment_cancellation'], 3) == $to
			) {

				// Handle shipment sending.
				WC_Biz_Courier_Logistics_Admin::async_handler(function () use ($id) {
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
					biz_cancel_shipment($id);
				}, $id);

			}
		}
	}

	/**
	 * Register the voucher column.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	function add_biz_order_voucher_column($columns)
	{
		// Append the new key and return.
		$columns['biz-voucher'] = __("Biz shipment voucher", 'wc-biz-courier-logistics');
		return $columns;
	}

	/**
	 * Register the voucher column.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	function biz_order_voucher_column($column, $post_id)
	{
		// Print the voucher in the column's row.
		switch ($column) {
			case 'biz-voucher':
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
				order_column_voucher_html(get_post_meta($post_id, '_biz_voucher', true));
				break;
		}
	}

	/**
	 * Add Biz Courier shipment management in the order editing page.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function add_biz_shipment_meta_box()
	{
		/**
		 * Print the Biz Shipment meta box.
		 * 
		 * @param WP_Post $post The current post.
		 *
		 * @author Alexandros Raikos <alexandros@araikos.gr>
		 * @since 1.0.0
		 */
		function biz_shipment_meta_box($post)
		{
			/**
			 * Prepare scripts for non-submitted orders.
			 */
			function prepare_scripts_new_shipment($order_id)
			{
				// Enqueue and localize send new shipment.
				wp_enqueue_script('wc-biz-courier-logistics-new-shipment');
				wp_localize_script('wc-biz-courier-logistics-new-shipment', "ShipmentProperties", array(
					"bizShipmentSendNonce" => wp_create_nonce('biz_shipment_send'),
					"bizShipmentAddVoucherNonce" => wp_create_nonce('biz_shipment_add_voucher'),
					"orderID" => $order_id,
					"SEND_SHIPMENT_CONFIRMATION" => __("Are you sure you want to send this shipment?", "wc-biz-courier-logistics"),
					"ADD_VOUCHER_MESSAGE" => __("Insert the shipment's voucher number from Biz Courier in the field below.", "wc-biz-courier-logistics")
				));
			}

			/**
			 * Prepare scripts for submitted orders.
			 */
			function prepare_scripts_existing_shipment($order_id)
			{
				// Enqueue and localize button scripts.
				wp_enqueue_script('wc-biz-courier-logistics-existing-shipment');
				wp_localize_script('wc-biz-courier-logistics-existing-shipment', "ShipmentProperties", array(
					"bizShipmentModificationRequestNonce" => wp_create_nonce('biz_shipment_modification_request'),
					"bizShipmentCancellationRequestNonce" => wp_create_nonce('biz_shipment_cancellation_request'),
					"bizShipmentEditVoucherNonce" => wp_create_nonce('biz_shipment_edit_voucher'),
					"bizShipmentDeleteVoucherNonce" => wp_create_nonce('biz_shipment_delete_voucher'),
					"bizShipmentSynchronizeOrderNonce" => wp_create_nonce('biz_shipment_synchronize_order'),
					"orderID" => $order_id,
					"CANCELLATION_REQUEST_CONFIRMATION" => __("Are you sure you want to request the cancellation of this Biz shipment? If you want to send it again, you will receive a new tracking code.", "wc-biz-courier-logistics"),
					"MODIFICATION_REQUEST_PROMPT" => __("Please insert the message you want to send to Biz about the shipment.", "wc-biz-courier-logistics"),
					"EDIT_VOUCHER_PROMPT" => __("Insert the new shipment voucher number from Biz Courier in the field below.", "wc-biz-courier-logistics"),
					"DELETE_VOUCHER_CONFIRMATION" => __("Are you sure you want to delete the shipment voucher from this order?", "wc-biz-courier-logistics")
				));
			}

			/** @var WC_Order $order The order being currently viewed. */
			$order = wc_get_order($post->ID);

			// Print the meta box using Biz API and local data.
			WC_Biz_Courier_Logistics_Admin::async_handler(function () use ($order) {
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';

				/** @var string $voucher The order's voucher. */
				$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);

				// Get Biz API data and prepare scripts appropriately.
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
				if (!empty($voucher)) {
					prepare_scripts_existing_shipment($order->get_id());
					shipment_management_html($voucher, $order->get_status(), biz_shipment_status($voucher));
				} else {
					prepare_scripts_new_shipment($order->get_id());
					shipment_creation_html();
				}
			}, $order->get_id());
		}

		// Ensure the administrator is on the "Edit" screen and not "Add".
		if (get_current_screen()->action != 'add') {

			// Add the meta box.
			add_meta_box(
				'wc-biz-courier-logistics_send_shipment_meta_box',
				__('Biz Courier status', 'wc-biz-courier-logistics'),
				'biz_shipment_meta_box',
				'shop_order',
				'side',
				'high'
			);
		}
	}


	/**
	 * Check for cancelled & completed orders.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	function biz_cron_order_status_checking()
	{
		// Check if the option is enabled.
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		if ($biz_settings['automatic_order_status_updating'] == 'yes') {

			/** @var array $orders All the orders in `processing` state. */
			$orders = wc_get_orders(array(
				'status' => array('wc-processing'),
				'return' => 'ids'
			));

			// Conclude status for each order with an active voucher.
			foreach ($orders as $order_id) {

				/** @var string $voucher The order's voucher. */
				$voucher = get_post_meta($order_id, '_biz_voucher', true);

				// Conclude the status of the order if it has an active voucher.
				if (!empty($voucher)) {
					WC_Biz_Courier_Logistics_Admin::async_handler(function () {
						require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-shipments.php';
						biz_conclude_order_status($order_id, true);
					}, $order_id);
				}
			}
		}
	}
}
