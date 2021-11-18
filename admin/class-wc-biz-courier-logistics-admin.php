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
	 * 	------------
	 * 	General
	 * 	------------
	 *  This section provides all the generic plugin functionality.
	 */

	/**
	 * 	
	 *  Initialization
	 * 	------------
	 *  This section provides all the core initialization functionality.
	 */

	/**
	 * Notify the administrator of minimum hosting requirements.
	 * 
	 * @uses  notice_display_html()
	 * @usedby 'init'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.2
	 * 
	 * @version 1.4.0
	 */
	public function check_minimum_requirements(): void
	{
		// Check for PHP and loaded extensions.
		$minimum_php_version = '7.4.0';
		if (version_compare(phpversion(), $minimum_php_version) < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(sprintf(
				__(
					'This version of %1$s is not supported by Biz Courier & Logistics for WooCommerce. Please update to %1$s %2$s or later.',
					'wc-biz-courier-logistics'
				),
				'PHP',
				$minimum_php_version
			));
		}
		if (!extension_loaded('soap')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("You need to enable the soap extension in your PHP installation in order to use Biz Courier & Logistics features. Please contact your server administrator.", "wc-biz-courier-logistics"));
		}

		// Check for supported WordPress version.
		global $wp_version;
		$minimum_wp_version = '5.7.0';
		if (version_compare($wp_version, $minimum_wp_version) < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(sprintf(
				__(
					'This version of %1$s is not supported by Biz Courier & Logistics for WooCommerce. Please update to %1$s %2$s or later.',
					'wc-biz-courier-logistics'
				),
				'WordPress',
				$minimum_wp_version
			));
		}

		// Check for installed WooCommerce.
		if (!class_exists('WooCommerce')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("Biz Courier & Logistics for WooCommerce requires the WooCommerce plugin to be installed and enabled.", 'wc-biz-courier-logistics'));
		}

		// Check for supported WooCommerce version.
		$minimum_wc_version = '5.6.0';
		// NOTE: The WC_VERSION warning below is completely normal.
		if (version_compare(WC_VERSION, $minimum_wc_version) < 0) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(sprintf(
				__(
					'This version of %1$s is not supported by Biz Courier & Logistics for WooCommerce. Please update to %1$s %2$s or later.',
					'wc-biz-courier-logistics'
				),
				'WooCommerce',
				$minimum_wc_version
			));
		}
	}

	/**
	 * Trigger an asynchronous internal error display on load.
	 * 
	 * @uses `biz_internal_error` A WP option which displays global errors.
	 * @uses `_biz_internal_error` A post meta key which indicates internal post specific errors.
	 * @uses notice_display_html()
	 * 
	 * @usedby 'admin_notices'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function internal_error_notice(): void
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
			delete_post_meta($_GET['post'], '_biz_internal_error');
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @usedby 'admin_enqueue_scripts'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since  1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function enqueue_styles(): void
	{
		wp_register_style($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'css/wc-biz-courier-logistics-admin.css', array(), $this->version, 'all');
		wp_enqueue_style($this->WC_Biz_Courier_Logistics);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @usedby 'admin_enqueue_scripts'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since  1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function enqueue_scripts(): void
	{
		// Global script options.
		wp_enqueue_script($this->WC_Biz_Courier_Logistics, plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin.js', array('jquery'), $this->version, false);
		wp_localize_script($this->WC_Biz_Courier_Logistics, "GlobalProperties", array(
			"ajaxEndpointURL" => admin_url('admin-ajax.php')
		));

		// Scripts for product management.
		wp_register_script('wc-biz-courier-logistics-product-management', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-product-management.js', array('jquery'));

		// Scripts for shipment management.
		wp_register_script('wc-biz-courier-logistics-shipment-creation', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-shipment-creation.js', array('jquery', $this->WC_Biz_Courier_Logistics));
		wp_register_script('wc-biz-courier-logistics-shipment-management', plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-shipment-management.js', array('jquery'));
	}


	/**
	 * Add a plugin action link to the plugin settings.
	 * 
	 * @param  array  $actions List of existing plugin action links.
	 * @return array List of modified plugin action links.
	 * 
	 * @usedby 'plugin_action_links_wc-biz-courier-logistics/wc-biz-courier-logistics.php'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	public function plugin_action_links($actions): array
	{
		return array_merge(array(
			'<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')) . '">' . __('Settings', 'wc-biz-courier-logistics') . '</a>'
		), $actions);
	}

	/**
	 * Add plugin meta links.
	 *
	 * @param  array $links List of existing plugin row meta links.
	 * @return array List of modified plugin row meta links.
	 * 
	 * @usedby 'plugin_row_meta'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	public function plugin_row_meta($links, $file): array
	{
		if (strpos($file, 'wc-biz-courier-logistics.php')) {

			// Add GitHub Sponsors link.
			$links[] = '<a href="https://github.com/sponsors/alexandrosraikos" target="blank">' . __('Donate via GitHub Sponsors', 'wc-biz-courier-logistics') . '</a>';

			// Add documentation link.
			$links[] = '<a href="https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/blob/main/README.md" target="blank">' . __('Documentation', 'wc-biz-courier-logistics') . '</a>';

			// Add support link.
			$links[] = '<a href="https://www.araikos.gr/en/contact/" target="blank">' . __('Support', 'wc-biz-courier-logistics') . '</a>';
		}
		return $links;
	}


	/**
	 * 	
	 *  Internal
	 * 	------------
	 *  This section provides all the internal admin functionality.
	 */

	/**
	 * An error registrar for asynchronous throwing functions.
	 * 
	 * @param callable $completion The action that needs to be done.
	 * @param int? $post_id The ID of the relevant post (optional).
	 * 
	 * @uses notice_display_html()
	 * @usedby All functions triggered by WordPress hooks.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	private function async_handler($completion, $post_id = null): void
	{
		try {
			// Run completion function.
			$completion();
		} catch (SoapFault $f) {
			// Display the error.
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html(__("There was a connection issue when trying to contact the Biz Courier & Logistics API:", 'wc-biz-courier-logistics') . " " . $f->getMessage(), 'failure');
		} catch (\Exception $e) {

			// Display the error.
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			notice_display_html($e->getMessage(), 'failure');

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
	 * @return void The function simply echoes the response to the 
	 * 
	 * @usedby All functions triggered by the WordPress AJAX handler.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	private function ajax_handler($completion): void
	{
		$action = sanitize_key($_POST['action']);

		// Verify the action related nonce.
		if (!wp_verify_nonce($_POST['nonce'], $action)) {
			http_response_code(403);
			die("Unverified request for action: " . $action);
		}

		// Include action relevant definitions.
		if (str_contains($_POST['action'], 'shipment')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';
		} elseif (str_contains($_POST['action'], 'product')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';
		}

		// Send shipment using POST data and handle errors.
		try {
			/** @var array $data The filtered $_POST data excluding WP specific keys. */
			$data = $completion(array_filter($_POST, function ($key) {
				return ($key != 'action' && $key != 'nonce');
			}, ARRAY_FILTER_USE_KEY));

			// Prepare the data and send.
			$data = json_encode($data);
			if ($data == false) {
				throw new ErrorException("There was an error while encoding the data to JSON.");
			} else {
				http_response_code(200);
				die(json_encode($data));
			}
		} catch (RuntimeException $e) {
			http_response_code(400);
			die($e->getMessage());
		} catch (ErrorException $e) {
			http_response_code(500);
			die($e->getMessage());
		} catch (SoapFault $f) {
			// Log the internal connection error.
			http_response_code(502);
			error_log('[Biz Courier & Logistics for WooCommerce] SOAP client error when contacting Biz: ' . $f->getMessage() . ' (action: ' . $action . ')');
			die();
		}
	}


	/**
	 * 	------------
	 * 	Integration
	 * 	------------
	 *  This section provides the necessary functionality for 
	 *  initialising the custom Biz integration.
	 * 
	 */


	/**
	 * Declare the Biz_Integration class.
	 * 
	 * @usedby 'woocommerce_integrations_init'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function biz_integration(): void
	{
		// Include definition if class doesn't exist.
		if (!class_exists('Biz_Integration')) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-integration.php';
		}
	}


	/**
	 * Include Biz_Integration in WooCommerce Integrations.
	 *
	 * @param array $integrations The active list of WooCommerce Integrations.
	 * @return array The expanded list of WooCommerce Integrations.
	 * 
	 * @usedby 'woocommerce_integrations'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function add_biz_integration($integrations): array
	{
		// Add the Biz Integration class identifier.
		$integrations[] = 'Biz_Integration';
		return $integrations;
	}

	/**
	 * Displays a WordPress notice depending on Biz credential and connection status.
	 *
	 * @uses notice_display_html()
	 * @usedby 'admin_notices'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 */
	public function biz_settings_notice()
	{

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

					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
					notice_display_html(sprintf(__("Your Biz Courier credentials are invalid. Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')));
				} elseif ($_GET['biz_error'] == 'conn-error') {

					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
					notice_display_html(__("There was an error contacting Biz Courier, please try again later.", "wc-biz-courier-logistics"));
				}
			} elseif (
				$biz_settings['account_number'] == null ||
				$biz_settings['warehouse_crm'] == null ||
				$biz_settings['username'] == null ||
				$biz_settings['password'] == null
			) {

				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
				notice_display_html(sprintf(__("Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')), 'warning');
			}
		}
	}

	/**
	 * 	------------
	 * 	Product Management
	 * 	------------
	 *  This section provides the necessary functionality for 
	 *  product management.
	 * 
	 */

	/**
	 * 	Interface Hooks
	 * 	------------
	 *  All the functionality related to the Product Management WordPress interface.
	 */

	/**
	 * 
	 * Display Biz Warehouse option to include each product in the stock synchronisation process.
	 *
	 * @uses $post
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @uses product_synchronization_checkbox()
	 * @usedby 'woocommerce_product_options_inventory_product_data'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr> 
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function add_product_biz_warehouse_option(): void
	{
		/** @var WP_Post $post The current post. */
		global $post;


		// TODO @alexandrosraikos: Add 'Include all variations' option. (#34)

		$this->async_handler(function () use ($post) {

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';
			$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($post->ID);

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			product_synchronization_checkbox($delegate->get_synchronization_status());
		}, $post->ID);
	}

	/**
	 * Display Biz Warehouse option to include each product variation in the stock synchronisation process.
	 *
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @uses product_synchronization_status_indicator()
	 * @usedby 'woocommerce_variation_options'
	 * 
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function add_product_variation_biz_warehouse_option($loop, $variation_data, $variation): void
	{
		// TODO @alexandrosraikos: Do not show if variations are all included in parent. (#34)

		$this->async_handler(function () use ($loop, $variation) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';
			$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($variation->ID);

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			product_variation_synchronization_checkbox($loop, $delegate->get_synchronization_status());
		}, $variation->ID);
	}

	/**
	 * Add Biz Courier remaining stock synchronization button to the All Products page.
	 * 
	 * @uses product_stock_synchronize_all_button_html()
	 * @usedby 'manage_posts_extra_tablenav'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function add_product_stock_synchronize_all_button(): void
	{
		// Ensure active All Products page.
		global $current_screen;
		if ('product' != $current_screen->post_type) {
			return;
		}

		// Enqeue & localize synchronization button script.
		wp_enqueue_script('wc-biz-courier-logistics-product-management');
		wp_localize_script('wc-biz-courier-logistics-product-management', "StockProperties", array(
			"bizStockSynchronizationNonce" => wp_create_nonce('product_stock_synchronization_all'),
			"PRODUCT_STOCK_SYNCHRONIZATION_ALL_CONFIRMATION" => __(
				"Are you sure you would like to synchronize the stock levels of all the products in the catalogue with your Biz Warehouse?",
				'wc-biz-courier-logistics'
			)
		));

		// Insert button HTML.
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
		product_stock_synchronize_all_button_html();
	}


	/**
	 * Add Biz Courier stock synchronisation status column to the All Products page.
	 *
	 * @param array $columns The active list of columns in the All Products page.
	 * @return array The expanded list of columns.
	 * 
	 * @usedby 'manage_edit-product_columns'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function add_product_stock_status_indicator_column($columns): array
	{
		// Append the Biz synchronization status column.
		$columns['biz_sync'] = __('Biz Warehouse', 'wc-biz-courier-logistics');

		return $columns;
	}

	/**
	 * Add Biz Courier stock synchronisation status indicator column to each product
	 * in the All Products page.
	 * 
	 * @param string $column_name The name of the column.
	 * @param int $product_post_id The ID of the product post in the row.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @uses product_synchronization_status_indicator()
	 * @usedby 'manage_product_posts_custom_column'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function product_stock_status_indicator_column($column_name, $product_post_id): void
	{
		// Ensure Biz Status column.
		switch ($column_name) {
			case 'biz_sync':
				$this->async_handler(function () use ($product_post_id) {
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';
					$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($product_post_id);

					// Show HTML.
					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
					echo product_synchronization_status_indicator($delegate->get_composite_synchronization_status());
				});
		}
	}

	/**
	 * 	Handler Hooks
	 * 	------------
	 *  All the functionality related to the Product Management handlers.
	 */

	/**
	 * Handle product option persistence for the Biz warehouse stock synchronisation.
	 * 
	 * @param int $post_id The ID of the product post.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @usedby 'woocommerce_process_product_meta'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function save_product_biz_warehouse_option($post_id): void
	{
		// TODO @alexandrosraikos: Save data for all variations too if they are automatically included. (#34)

		$this->async_handler(function () use ($post_id) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

			/** @var WC_Product $product The product. */
			$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($post_id);

			// Reset synchronization status on SKU change.
			if ($_POST['_sku'] != $delegate->product->get_sku()) {
				$delegate->reset_synchronization_status();
			}

			// Reset synchronization status on Biz Warehouse option change.
			if (!empty($_POST['_biz_stock_sync'])) $delegate->enable();
			else $delegate->disable();
		}, $post_id);
	}


	/**
	 * 
	 * Handle product variation option persistence for the Biz warehouse stock synchronisation.
	 * 
	 * @param int $variation_id The ID of the variation product post.
	 * @param int $i The index of the current variation being processed in the array.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @usedby 'woocommerce_save_product_variation'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function save_product_variation_biz_warehouse_option($variation_id, $i): void
	{
		$this->async_handler(function () use ($i) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

			$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($variation_id);

			// Synchronization is set.
			if (!empty($_POST['_biz_stock_sync'][$i])) {
				// Synchronization status.
				if ($_POST['_biz_stock_sync'][$i] == 'yes') {
					$delegate->enable();
				} else {
					$delegate->disable();
				}
			} else {
				$delegate->disable();
			}

			// SKU has changed.
			if ($_POST['variable_sku'][$i] != $delegate->product->get_sku()) {
				$delegate->reset_synchronization_status();
			}
		}, $variation_id);
	}

	/**
	 * 	AJAX Handler Hooks
	 * 	------------
	 *  All the functionality related to the Product Management AJAX handlers.
	 */

	/**
	 * Handles stock sync AJAX requests from authorized users and initiates sync
	 * for all products in the WooCommerce catalogue.
	 *
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Product_Delegate
	 * @usedby 'wp_ajax_biz_stock_synchronization'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function product_stock_synchronization_all(): void
	{
		$this->ajax_handler(function () {
			/** @var WC_Product[] $products An array of all products. */
			$products = wc_get_products(array(
				'limit' => -1,
			));

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

			/** @var string[] $all_skus An array of all SKUs. */
			$all_skus = [];
			if (!empty($products)) {
				foreach ($products as $product) {
					$all_skus = array_merge(
						$all_skus,
						WC_Biz_Courier_Logistics_Product_Delegate::get_all_related_skus($product)
					);
				}
			}

			WC_Biz_Courier_Logistics_Product_Delegate::stock_level_synchronization($all_skus);
		});
	}

	/**
	 * ------------
	 * Shipping Method 
	 * ------------
	 * This section provides the necessary functionality for 
	 * initialising the custom Biz shipping method.
	 * 
	 */

	/**
	 * Declare the Biz_Shipping_Method class.
	 * 
	 * @usedby 'woocommerce_shipping_init'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function biz_shipping_method(): void
	{
		// Import the Biz Shipping Method class.
		if (!class_exists('Biz_Shipping_Method')) require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipping-method.php';
	}

	/**
	 * Declare Biz_Shipping_Method class.
	 * 
	 * @param array $methods The active list of shipping methods.
	 * @return array The expanded list of shipping methods.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	function add_biz_shipping_method($methods): array
	{
		// Append the new shipping method.
		$methods['biz_shipping_method'] = 'Biz_Shipping_Method';

		return $methods;
	}


	/**
	 * ------------
	 * Shipment Management 
	 * ------------
	 * This section provides the necessary functionality for 
	 * managing shipments.
	 * 
	 */

	/**
	 * 	Interface Hooks
	 * 	------------
	 *  All the functionality related to the Shipment Management WordPress interface.
	 */

	/**
	 * Add the shipment management view in the order editing page.
	 * 
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @uses shipment_creation_html()
	 * @uses shipment_management_html()
	 * @uses notice_display_embedded_html()
	 * @usedby 'add_meta_boxes'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function add_shipment_management_meta_box(): void
	{
		/**
		 * Print the Biz Shipment meta box.
		 * 
		 * @param WP_Post $post The current post.
		 * 
		 * @version 1.4.0
		 */
		function shipment_management_meta_box($post): void
		{
			/**
			 * Prepare scripts for non-submitted orders.
			 * 
			 * @param int $order_id The ID of the order post.
			 * 
			 * @version 1.4.0
			 */
			function prepare_scripts_new_shipment($order_id): void
			{
				// Enqueue and localize send new shipment.
				wp_enqueue_script('wc-biz-courier-logistics-shipment-creation');
				wp_localize_script('wc-biz-courier-logistics-shipment-creation', "ShipmentProperties", array(
					"bizShipmentSendNonce" => wp_create_nonce('biz_shipment_send'),
					"bizShipmentAddVoucherNonce" => wp_create_nonce('biz_shipment_add_voucher'),
					"orderID" => $order_id,
					"SEND_SHIPMENT_CONFIRMATION" => __("Are you sure you want to send this shipment?", 'wc-biz-courier-logistics'),
					"ADD_VOUCHER_MESSAGE" => __("Insert the shipment's voucher number from Biz Courier in the field below.", "wc-biz-courier-logistics")
				));
			}

			/**
			 * Prepare scripts for submitted orders.
			 * 
			 * @param int $order_id The ID of the order post.
			 * 
			 * @version 1.4.0
			 */
			function prepare_scripts_existing_shipment($order_id): void
			{
				// Enqueue and localize button scripts.
				wp_enqueue_script('wc-biz-courier-logistics-shipment-management');
				wp_localize_script('wc-biz-courier-logistics-shipment-management', "ShipmentProperties", array(
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

			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';

			$shipment = new WC_Biz_Courier_Logistics_Shipment($post->ID);

			// Get Biz API data and prepare scripts appropriately.
			if (!empty($shipment->get_voucher())) {
				prepare_scripts_existing_shipment($post->ID);
				try {
					$shipment = new WC_Biz_Courier_Logistics_Shipment($post->ID);
					$shipment_history = $shipment->get_status();
				} catch (\Exception $e) {
					notice_display_embedded_html($e->getMessage(), 'failure');
				}
				shipment_management_html($shipment->get_voucher(), $shipment->order->get_status(), $shipment_history ?? null);
			} else {
				prepare_scripts_new_shipment($post->ID);
				shipment_creation_html();
			}
		}

		// Ensure the administrator is on the "Edit" screen and not "Add".
		if (get_current_screen()->action != 'add') {

			// Add the meta box.
			add_meta_box(
				'wc-biz-courier-logistics_send_shipment_meta_box',
				__('Biz Courier status', 'wc-biz-courier-logistics'),
				'shipment_management_meta_box',
				'shop_order',
				'side',
				'high'
			);
		}
	}

	/**
	 * Register the voucher column.
	 * 
	 * @param array $columns The active list of columns.
	 * @return array The extended list of columns.
	 * 
	 * @usedby 'manage_edit-shop_order_columns'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 */
	public function add_shipment_voucher_column($columns): array
	{
		// Append the new key and return.
		$columns['biz-voucher'] = __("Biz shipment voucher", 'wc-biz-courier-logistics');
		return $columns;
	}

	/**
	 * Register the voucher column.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @uses order_column_voucher_html()
	 * @usedby 'manage_shop_order_posts_custom_column
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_voucher_column($column, $post_id): void
	{
		// Print the voucher in the column's row.
		switch ($column) {
			case 'biz-voucher':
				$this->async_handler(function () use ($post_id) {

					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';
					$shipment = new WC_Biz_Courier_Logistics_Shipment($post_id);

					require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/wc-biz-courier-logistics-admin-display.php';
					order_column_voucher_html($shipment->get_voucher());
				});
				break;
		}
	}

	/**
	 * 	Handler Hooks
	 * 	------------
	 *  All the functionality related to the 
	 *  Shipment Management handlers.
	 */

	/**
	 * Handles shipment modification on order change.
	 *
	 * @param int $id The order ID.
	 * @param string $from The previous order status (not used).
	 * @param string $to The next order status.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'woocommerce_order_status_changed'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function order_status_change_handler($id, $from, $to): void
	{
		/** @var array $biz_settings The integration settings. */
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		// 1. Check existing options for automatic shipment creation.
		if (substr(($biz_settings['automatic_shipment_creation'] ?? 'disabled'), 3) == $to) {

			// Handle shipment sending.
			$this->async_handler(function () use ($id) {
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';
				$shipment = new WC_Biz_Courier_Logistics_Shipment($id);

				// Match preferred sending state on a voucher order.
				if (!empty($shipment->get_voucher())) {
					$shipment->send();
				}
			}, $id);
		}

		// 2. Check existing options for automatic shipment cancellation.
		if (substr(($biz_settings['automatic_shipment_cancellation'] ?? 'disabled'), 3) == $to) {

			// Handle shipment sending.
			$this->async_handler(function () use ($id) {
				require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';
				$shipment = new WC_Biz_Courier_Logistics_Shipment($id);

				// Match preferred sending cancellation on a voucher order.
				if (!empty($shipment->get_voucher())) {
					$shipment->cancel();
				}
			}, $id);
		}
	}

	/**
	 * 	AJAX Handler Hooks
	 * 	------------
	 *  All the functionality related to the 
	 *  Shipment Management AJAX handlers.
	 */

	/**
	 * Handles shipment creation AJAX requests from authorized users.
	 * 
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_send'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_creation_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment(intval($data['order_id']));
			$shipment->send();
		});
	}

	/**
	 * Handles shipment modification AJAX requests from authorized users.
	 * 
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_modification_request'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_modification_request_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->modify($data['shipment_modification_message']);
		});
	}

	/**
	 * Handles shipment cancellation AJAX requests from authorized users.
	 * 
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_cancellation_request'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_cancellation_request_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->cancel();
		});
	}

	/**
	 * Handles manual voucher addition AJAX requests from authorized users.
	 * 
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_add_voucher'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_add_voucher_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->set_voucher($data['new_voucher'], true);
		});
	}

	/**
	 * Handles manual voucher editing AJAX requests from authorized users.
	 *
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_edit_voucher'
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_edit_voucher_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->set_voucher($data['new_voucher'], true);
		});
	}

	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_delete_voucher'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_delete_voucher_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->delete_voucher();
		});
	}

	/**
	 * Handles manual voucher deletion AJAX requests from authorized users.
	 *
	 * @uses self::ajax_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'wp_ajax_biz_shipment_synchronize_order'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since    1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_synchronize_order_handler(): void
	{
		$this->ajax_handler(function ($data) {
			$shipment = new WC_Biz_Courier_Logistics_Shipment($data['order_id']);
			$shipment->conclude_order();
		});
	}


	/**
	 * 	Cron Hooks
	 * 	------------
	 *  All the functionality related to the 
	 *  Shipment Management cron scheduled jobs.
	 */

	/**
	 * Check for cancelled & completed orders.
	 * 
	 * @uses self::async_handler()
	 * @uses WC_Biz_Courier_Logistics_Shipment
	 * @usedby 'shipment_status_cron_handler_hook'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public function shipment_status_cron_handler(): void
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
			foreach ($orders as $wc_order_id) {
				if (WC_Biz_Courier_Logistics_Shipment::is_submitted($wc_order_id)) {
					$this->async_handler(function () use ($wc_order_id) {
						require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-shipment.php';
						$shipment = new WC_Biz_Courier_Logistics_Shipment($wc_order_id);
						$shipment->conclude_order();
					}, $wc_order_id);
				}
			}
		}
	}

	/**
	 * 
	 * Utility Hooks
	 * 	------------
	 * Additional internal functionality 
	 * related to shipment management.
	 */


	/**
	 * Filter a query by adding a custom query argument reader for the Biz voucher
	 * key in the meta array.
	 * 
	 * @param WC_Query $query The default WooCommerce query.
	 * @param array $query_vars The passed query arguments.
	 * @usedby 'woocommerce_order_data_store_cpt_get_orders_query'
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	function shipment_voucher_custom_query_var_handler($query, $query_vars)
	{
		// Check for 
		if (!empty($query_vars['voucher'])) {
			$query['meta_query'][] = array(
				'key' => '_biz_voucher',
				'value' => esc_attr($query_vars['voucher']),
			);
		}

		return $query;
	}
}
