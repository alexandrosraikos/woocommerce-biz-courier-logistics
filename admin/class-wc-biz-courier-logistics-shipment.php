<?php

/**
 * Provide shipment management functionality.
 *
 * This file is used to enable functionality regarding 
 * the Biz shipment management aspects of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.2.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

/**
 * The shipments-specific functionality of the plugin.
 *
 * Defines a management interface for shipments
 * with respect to their associated orders.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
class WC_Biz_Courier_Logistics_Shipment
{
	/** @var WC_Order $order The associated WooCommerce order. */
	public WC_Order $order;

	/** @var array $status_definitions The definitions of shipment statuses. */
	protected array $status_definitions;

	/**
	 * Initialize the class and retrieve the associated order data.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function __construct(int $order_id)
	{
		$this->order = wc_get_order($order_id);
		if (empty($this->order)) {
			throw new RuntimeException(__("Unable to retrieve order data.", 'wc-biz-courier-logistics'));
		}
	}

	/**
	 * Get the shipment voucher from the associated
	 * order's metadata.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function get_voucher()
	{
		return get_post_meta($this->order->get_id(), '_biz_voucher', true);
	}

	/**
	 * Update the shipment's voucher in the associated
	 * order's metadata.
	 * 
	 * @param string $value The new voucher value.
	 * @param bool $conclude Whether to conclude the order status based on the status history.
	 * 
	 * @throws RuntimeException When the new shipment voucher cannot be set.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function set_voucher(string $value, bool $conclude = false): void
	{
		$order = wc_get_orders([
			'voucher' => $value
		]);
		if (count($order) == 1) {
			throw new RuntimeException(
				sprintf(
					__(
						"The voucher \"%s\" is already in use by <a href=\"%s\">order #%d</a>.",
						'wc-biz-courier-logistics'
					),
					$value,
					get_edit_post_link($order[0]->id),
					$order[0]->id
				)
			);
		} elseif (count($order) > 1) {
			throw new RuntimeException(
				sprintf(
					__(
						"Using the voucher \"%s\" in multiple orders simultaneously is not supported.",
						'wc-biz-courier-logistics'
					),
					$value
				)
			);
		}

		/** @var array $report The status history report. */
		$report = $this->get_status($value);

		if (empty($report)) {
			throw new RuntimeException(
				sprintf(
					__(
						"The voucher \"%s\" is not registered with Biz. Please provide a valid shipment voucher.",
						'wc-biz-courier-logistics'
					),
					$value
				)
			);
		} else {
			update_post_meta($this->order->get_id(), '_biz_voucher', $value);
			if ($conclude) {
				$this->conclude_order(true, $report);
			}
		}
	}

	/**
	 * Delete the shipment's voucher in the associated
	 * order's metadata.
	 * 
	 * @throws RuntimeException When the shipment voucher cannot be deleted.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function delete_voucher()
	{
		// Check for falsy operation.
		if (!delete_post_meta($this->order->get_id(), '_biz_voucher')) {
			throw new RuntimeException(__("The shipment voucher could not be deleted from this order.", 'wc-biz-courier-logistics'));
		}
	}

	/**
	 * Check if an order has been submitted to Biz.
	 * 
	 * @return bool Whether the order is submitted to Biz Courier.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function is_submitted(int $wc_order_id)
	{
		// Return voucher status.
		return !empty(get_post_meta($wc_order_id, '_biz_voucher', true));
	}

	/**
	 * Get the status history of a Biz Courier shipment using the stored voucher number.
	 *
	 * @param string? $custom_voucher A different voucher code than the one associated.
	 * @return array An array of the complete status history.
	 * 
	 * @uses WC_Biz_Courier_Logistics::contactBizCourierAPI()
	 * @throws RuntimeException When there are no data for the given voucher number.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function get_status(string $custom_voucher = null): array
	{
		/** @var Object $biz_status_list The shipment's status history. */
		$biz_status_history = WC_Biz_Courier_Logistics::contactBizCourierAPI(
			"https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl",
			"full_status",
			[
				'Voucher' => $custom_voucher ?? $this->get_voucher()
			],
			false
		);

		// Check for empty status history.
		if (empty($biz_status_history)) throw new RuntimeException(
			sprintf(
				__(
					"The voucher \"%s\" is not registered with Biz. Please provide a valid shipment voucher.",
					'wc-biz-courier-logistics'
				),
				$custom_voucher ?? $this->get_voucher()
			)
		);

		/** @var array $biz_full_status_history The shipment's complete status history. */
		$biz_full_status_history = [];
		foreach ($biz_status_history as $status) {

			/** @var string $i The array key which joins three properties. */
			$i = $status['Status_Date'] . '-' . $status['Status_Time'] . '-' . $status['Status_Code'];

			/** @var string $status_code The status code of each level. */
			$status_code = $status['Status_Code'];
			if (empty($status_code)) $status_code = 'NONE';

			$status_definition = $this->get_status_definition($status_code);

			// Reach conclusion on Final levels.
			if ($status_definition['level'] == 'Final') {
				if (
					$status_code == 'ΠΡΔ' ||
					$status_code == 'COD' ||
					$status_code == 'OK'
				) $conclusion = 'completed';
				elseif ($status_code == 'AKY') $conclusion = 'cancelled';
				else $conclusion = 'failed';
			}

			// Add basic status information.
			if (!isset($biz_full_status_history[$i])) {
				$biz_full_status_history[$i] = array(
					'code' => $status_code,
					'level' => $status_definition['level'] ?? '',
					'level-description' => $status_definition['description'],
					'conclusion' => $conclusion ?? '',
					'description' => (get_locale() == 'el') ? $status['Status_Description'] : $status['Status_Description_En'],
					'comments' => $status['Status_Comments'],
					'date' => $status['Status_Date'],
					'time' => $status['Status_Time'],
					'actions' => array(),
					'last_mile_tracking_number' => $status['Part_Tracking_Num'] ?? ''
				);
			}

			// Append status actions.
			if (!empty($status['Action_Description'])) {
				array_push($biz_full_status_history[$i]['actions'], [
					'description' => (get_locale() == 'el') ? $status['Action_Description'] : $status['Action_Description_En'],
					'time' => $status['Action_Date'],
					'date' => $status['Action_Time'],
				]);
			}
		}

		// Return full status list.
		return $biz_full_status_history;
	}

	/**
	 * Handles the conclusion of order status based on the status report.
	 * 
	 * @param bool? $add_note A note to add in the order history.
	 * @param array $report A previous report from self::get_status.
	 * 
	 * @uses self::get_status()
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.1
	 * 
	 * @version 1.4.0
	 */
	public function conclude_order(bool $add_note = false, array $report = null): void
	{
		/** @var array $report The full status history report. */
		if (empty($report)) $report = $this->get_status();

		if (end($report)['level'] == 'Final') {
			if (end($report)['conclusion'] == 'completed') {

				if ($add_note) $this->order->update_status("completed", __("The connected Biz shipment was completed.", 'wc-biz-courier-logistics'));
				else $this->order->update_status("completed");
			} elseif (end($report)['conclusion'] == 'cancelled') {

				if ($add_note) $this->order->update_status("cancelled", __("The connected Biz shipment was cancelled.", 'wc-biz-courier-logistics'));
				else $this->order->update_status("cancelled");

				// Add delivery failure add_note.
				$failure_delivery_note = end($report)['level-description'] . __('Other comments:', 'wc-biz-courier-logistics') . '\n';
				foreach (array_reverse($report) as $status) {
					$failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n' . ($status['comments'] ?? 'none');
				}
				update_post_meta($this->order->get_id(), '_biz_failure_delivery_note', $failure_delivery_note);
			} elseif (end($report)['conclusion'] == 'failed') {

				// Handle failed shipment status.
				if ($add_note) $this->order->update_status("failed", __("The connected Biz shipment has failed.", 'wc-biz-courier-logistics'));
				else $this->order->update_status("failed");

				$failure_delivery_note = end($report)['level-description'] . __('Other comments:', 'wc-biz-courier-logistics') . '\n';
				foreach (array_reverse($report) as $status) {
					$failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n' . ($status['comments'] ?? 'none');
				}

				// Add delivery failure add_note.
				update_post_meta($this->order->get_id(), '_biz_failure_delivery_note', $failure_delivery_note);
			}
		} else {

			// Handle pending shipment status.
			$this->order->update_status("processing", __("The newly connected shipment is pending.", 'wc-biz-courier-logistics'));
		}
	}


	/**
	 * Modify a shipment with Biz.
	 *
	 * @param int $order_id The ID of the WooCommerce order.
	 * @param string $message? The modification message to include.
	 * 
	 * @uses self::get_voucher()
	 * @uses WC_Biz_Courier_Logistics::contactBizCourierAPI()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function modify($message)
	{
		/** @var WC_Order $order The extracted order from the shipment object. */
		$order = $this->order;

		// Perform the request.
		WC_Biz_Courier_Logistics::contactBizCourierAPI(
			"https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl",
			"modifyShipment",
			[
				'voucher' => $this->get_voucher(),
				'modification' =>  WC_Biz_Courier_Logistics::ensure_utf8($message)
			],
			true,
			function ($response) use ($order, $message) {
				$order->add_order_note(__("Message sent to Biz: ", "wc-biz-courier-logistics") . $message . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")");
			}
		);
	}

	/**
	 * Cancel a shipment with Biz.
	 *
	 * @param	 int $order_id The ID of the WooCommerce order.
	 * 
	 * @uses self::get_voucher()
	 * @uses WC_Biz_Courier_Logistics::contactBizCourierAPI()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function cancel()
	{
		/** @var string $voucher The extracted voucher from this shipment object. */
		$voucher = $this->get_voucher();

		/** @var WC_Order $order The extracted order from this shipment object. */
		$order = $this->order;

		// Perform the request.
		WC_Biz_Courier_Logistics::contactBizCourierAPI(
			"http://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl",
			"actionShipment",
			[
				'voucher' => $this->get_voucher(),
				'actcode' => 'CANRE',
				'notes' => ''
			],
			true,
			function ($response) use ($order, $voucher) {
				$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled. The cancellation code is: %s."), $voucher, $response->ActId), true);
			},
			function ($error) use ($order, $voucher) {
				$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled."), $voucher, $error->ActId), true);
			}
		);
	}

	/**
	 * Get the status definitions.
	 * 
	 * @param string? $identifier Return data only for a specific identifier.
	 * @param bool? $force_refresh Force refreshes the saved status definitions.
	 * @return array The array of status definitions.
	 * 
	 * @uses WC_Biz_Courier_Logistics::contactBizCourierAPI()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function get_status_definition(string $identifier = null, bool $force_refresh = false): array
	{
		if (!function_exists("retrieve_definitions")) {

			/**
			 * Retrieve the status definitions from the Biz API.
			 * 
			 * @return array The retrieved array of status definitions.
			 */
			function retrieve_definitions(): array
			{
				/** @var Object $biz_status_definitions The official list of status levels. */
				$biz_status_definitions = WC_Biz_Courier_Logistics::contactBizCourierAPI(
					"https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl",
					"TrackEvntSrv",
					[],
					true
				);

				/** @var array $status_levels All available status levels. */
				$status_definitions = [];

				// Populate status levels from the call.
				foreach ($biz_status_definitions as $biz_status_definition) {
					$status_definitions[$biz_status_definition['Status_Code']] = [
						'level' => $biz_status_definition['Level'],
						'description' => $biz_status_definition['Comments']
					];
				}

				// Insert custom `'NONE'` status level.
				$status_definitions['NONE'] = [
					'level' => 'Pending',
					'description' => __("Delivery status update", 'wc-biz-courier-logistics')
				];

				$status_definitions['last_updated'] = time();

				update_option('wc_biz_courier_logistics_status_definitions', $status_definitions);
				return $status_definitions;
			}
		}


		/** @var array $status_definitions The array of status definitions. */
		$status_definitions = get_option('wc_biz_courier_logistics_status_definitions');

		// Fetch definitions the Biz API.
		if ($force_refresh || count($status_definitions) < 3) {
			$status_definitions = retrieve_definitions();
		} else {
			// Refresh definitions older than 7 days.
			if ((time() - $status_definitions['last_updated']) > 7 * 24 * 60 * 60 * 60) {
				$status_definitions = retrieve_definitions();
			}
		}

		// Handle specific identifier.
		if (!empty($identifier)) {
			if (array_key_exists($identifier, $status_definitions)) {
				return $status_definitions[$identifier];
			} else {
				// Fetch once if definition is not found in DB storage.
				$status_definitions = retrieve_definitions();
				if (array_key_exists($identifier, $status_definitions)) {
					return $status_definitions[$identifier];
				} else throw new RuntimeException(
					sprintf(
						__(
							"The definition for the shipment status \"%s\" cannot be found.",
							'wc-biz-courier-logistics'
						),
						$identifier
					)
				);
			}
		} else {
			return $status_definitions;
		}
	}

	/**
	 * Get a sequential list of products and their compatibility status with Biz.
	 * 
	 * @param int $order_id The associated order ID.
	 * @param bool $filter Return only compatible items.
	 * @return array An array using the [`'item' => WC_Order_Item `, `'product' => WC_Product`, `'compatible' => bool`] schema for all items.
	 * 
	 * @author	Alexandros Raikos <alexandros@araikos.gr>
	 * @since	1.4.0
	 */
	public static function get_compatible_order_items(int $order_id, bool $filter = true): array
	{
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

		/** @var WC_Order $order The associated order. */
		$order = wc_get_order($order_id);

		// Make a full item list with compatibility status.
		$items = array_map(
			function ($item) {
				/** @var WC_Product $product The order item's product data. */
				$product = wc_get_product($item['product_id']);

				// Get exact variation used as the product the referred product is variable.
				if ($product->is_type('variable') && !empty($item['variation_id'])) {
					$product = wc_get_product($item['variation_id']);
				}

				if (WC_Biz_Courier_Logistics_Product_Delegate::is_permitted($product)) {
					$delegate = new WC_Biz_Courier_Logistics_Product_Delegate($product);
					$compatible = ($delegate->get_synchronization_status()[0] == 'synced');
				}
				return [
					'self' => $item,
					'product' => $product,
					'compatible' => $compatible ?? false
				];
			},
			$order->get_items()
		);

		// Filter only compatible items.
		if ($filter) {
			$items = array_filter(
				$items,
				function ($item) {
					return $item['compatible'];
				}
			);
		}

		return $items;
	}

	/**
	 * Creates a new shipment with and saves the response voucher in the order's meta
	 * as `_biz_voucher`. For more information on this API call visit the official documentation here:
	 * https://www.bizcourier.eu/WebServices
	 * 
	 * @throws	RuntimeException When data are invalid.
	 * @throws	WCBizCourierLogisticsAPIError When there are API errors.
	 * 
	 * @author	Alexandros Raikos <alexandros@araikos.gr>
	 * @since	1.0.0
	 * 
	 * @version	1.4.0
	 */
	public function send(): void
	{
		/**
		 * Initialization
		 * 
		 * The following variables and conditions are critical
		 * to the execution of the shipment.
		 */

		// Check for existing voucher.
		if ($this->is_submitted($this->order->get_id())) {
			throw new RuntimeException(__("A voucher already exists for this order.", 'wc-biz-courier-logistics'));
		}

		/** @var WC_Order_Item[] $items The order's compatible items. */
		$items = self::get_compatible_order_items($this->order->get_id());

		// Check for no items.
		if (empty($items)) {
			throw new RuntimeException(
				__(
					"There are no items included in this order.",
					'wc-biz-courier-logistics'
				)
			);
		}


		/**
		 * Prepare shipment items.
		 * 
		 * This section checks for product data
		 * and calculates package dimensions.
		 */

		/** @var string[] $shipment_products The order items array in product_code:quantity format. */
		$shipment_products = [];

		/** @var double[] $package_metrics The total calculated dimensions. */
		$package_metrics = [
			'width' => 0,
			'height' => 0,
			'length' => 0,
			'weight' => 0
		];

		// Handle each item included in the order.
		foreach ($items as $item) {

			// Merge order item codes and quantities.
			$shipment_products[] = $item['product']->get_sku() . ":" . $item['self']->get_quantity();

			// Add volume and weight to total dimensions.
			if (
				!empty($item['product']->get_width()) &&
				!empty($item['product']->get_height()) &&
				!empty($item['product']->get_length()) &&
				!empty($item['product']->get_weight())
			) {
				$package_metrics['width'] += $item['product']->get_width() * $item['self']->get_quantity();
				$package_metrics['height'] += $item['product']->get_height() * $item['self']->get_quantity();
				$package_metrics['length'] += $item['product']->get_length() * $item['self']->get_quantity();
				$package_metrics['weight'] += $item['product']->get_weight() * $item['self']->get_quantity();
			} else {
				throw new RuntimeException(
					__(
						"Please make sure all products in the order have their weight & dimensions registered.",
						'wc-biz-courier-logistics'
					)
				);
			}
		}

		/** @var string $first_product The extracted first product from `$shipment_products`. */
		$first_product = array_shift($shipment_products);

		/**
		 * Prepare shipment options.
		 * 
		 * This section checks for enabled options
		 * and prepares fields accordinf to the Biz API.
		 */

		/** @var string[] $biz_shipping_settings Any registered Biz Courier shipping method options. */
		$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

		/** @var string $phone The recipient's phone number. */
		$phone = $this->order->get_shipping_phone();

		// Switch to billing phone number if desired or unavailable.
		if (empty($phone) && !empty($biz_shipping_settings['biz_billing_phone_usage'])) {
			$phone = ($biz_shipping_settings['biz_billing_phone_usage'] == 'yes') ? $this->order->get_billing_phone() : '';
		}

		/** @var string $comments Any order comments. */
		$comments = "";

		// Include comment for Saturday delivery.
		if (
			str_contains($this->order->get_shipping_method(), "Σαββάτου") ||
			str_contains($this->order->get_shipping_method(), "Saturday")
		) {
			$comments .= "[SATURDAY DELIVERY] ";
		}

		// Append any recipient comments.
		$comments .= "Recipient comments: " . ($this->order->get_customer_note() ?? "none");

		/** @var string $morning_delivery Include morrning delivery if present in the shipping method title. @see Biz_Shipping_Method::calculate_shipping */
		$morning_delivery = (str_contains($this->order->get_shipping_method(), "Πρωινή") || str_contains($this->order->get_shipping_method(), "Morning")) ? "yes" : "";

		// Check recipient information for completeness.
		if (
			empty($this->order->get_shipping_first_name()) ||
			empty($this->order->get_shipping_last_name()) ||
			empty($this->order->get_shipping_address_1()) ||
			empty($this->order->get_shipping_country()) ||
			empty($this->order->get_shipping_city()) ||
			empty($this->order->get_shipping_postcode()) ||
			empty($phone)
		) throw new RuntimeException(__("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields: First name, last name, phone number, e-mail address, address line #1, city, postal code and country.", 'wc-biz-courier-logistics'));

		/** @var array $response The API response on shipment creation. */
		$response = WC_Biz_Courier_Logistics::contactBizCourierAPI(
			"https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl",
			"newShipment",
			[
				"R_Name" => WC_Biz_Courier_Logistics::truncate_field($this->order->get_shipping_first_name() . " " . $this->order->get_shipping_last_name()),
				"R_Address" => WC_Biz_Courier_Logistics::truncate_field($this->order->get_shipping_address_1() . " " . $this->order->get_shipping_address_2()),
				"R_Area_Code" => $this->order->get_shipping_country(),
				"R_Area" => WC_Biz_Courier_Logistics::truncate_field($this->order->get_shipping_city()),
				"R_PC" => $this->order->get_shipping_postcode(),
				"R_Phone1" => $phone,
				"R_Phone2" => "",
				"R_Email" => WC_Biz_Courier_Logistics::truncate_field($this->order->get_billing_email(), 60),
				"Length" => $package_metrics['length'], // cm int
				"Width" => $package_metrics['width'], // cm int
				"Height" => $package_metrics['height'], // cm int
				"Weight" => $package_metrics['weight'], // kg int
				"Prod" => explode(":", $first_product)[0],
				"Pieces" => explode(":", $first_product)[1],
				"Multi_Prod" => (count($shipment_products) > 0) ? implode("#", $shipment_products) : '',
				"Cash_On_Delivery" => ($this->order->get_payment_method() == 'cod') ? number_format($this->order->get_total(), 2) : '',
				"Checques_On_Delivery" => "", // Unsupported.
				"Comments" => WC_Biz_Courier_Logistics::truncate_field($comments, 1000),
				"Charge" => "3", // Unsupported, always 3.
				"Type" => "2", // Unsupported, always assume parcel.
				"Relative1" => "", // Unsupported.
				"Relative2" => "", // Unsupported.
				"Delivery_Time_To" => "", // Unsupported.
				"SMS" => (($biz_shipping_settings['biz_sms_notifications'] ?? "no") == "yes") ? "1" : "0",
				"Special_Treatment" => "", // Unsupported.
				"Protocol" => "", // Unsupported.
				"Morning_Delivery" => $morning_delivery,
				"Buy_Amount" => "", // Unsupported.
				"Pick_Up" => "", // Unsupported.
				"Service_Type" => "", // Unsupported.
				"Relabel" => "", // Unsupported.
				"Con_Call" => "0", // Unsupported.
				"Ins_Amount" => "" // Unsupported.
			],
			true
		);

		// Handle response error codes.
		switch ($response['Error_Code']) {
			case 0:
				if (!empty($response['Voucher'])) {
					// Update order meta.
					delete_post_meta($this->order->get_id(), '_biz_failure_delivery_note');

					// Switch order status to `processing`.
					$this->order->update_status('processing', __('The shipment was successfully registered to Biz Courier.', 'wc-biz-courier-logistics'));

					// Set the voucher response.
					$this->set_voucher($response['Voucher']);
				} else throw new WCBizCourierLogisticsAPIError(__("Response from Biz could not be read, please check your warehouse shipments from the official application.", 'wc-biz-courier-logistics'));
			case 1:
				throw new WCBizCourierLogisticsAPIError(__("There was an error with your Biz credentials.", 'wc-biz-courier-logistics'));
			case 2:
			case 3:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering recipient information with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
			case 4:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering the recipient's area code with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
			case 5:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering the recipient's area with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
			case 6:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering the recipient's phone number with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
			case 7:
				throw new WCBizCourierLogisticsAPIError(__("The item does not belong to this Biz account. Please check the order.", 'wc-biz-courier-logistics'));
			case 8:
				throw new WCBizCourierLogisticsAPIError(__("Some products do not belong to this Biz account. Please check the order's items.", 'wc-biz-courier-logistics'));
			case 9:
				throw new WCBizCourierLogisticsAPIError(__("The shipment's products were incorrectly registered.", 'wc-biz-courier-logistics'));
			case 10:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering the recipient's postal code with Biz. Please fill in all required recipient information entries.", 'wc-biz-courier-logistics'));
			case 11:
				throw new WCBizCourierLogisticsAPIError(__("There was a problem registering the recipient's postal code with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
			case 12:
				throw new WCBizCourierLogisticsAPIError(__("A shipment used for relabeling cannot be used.", 'wc-biz-courier-logistics'));
			default:
				throw new WCBizCourierLogisticsAPIError(__("An unknown Biz error occured after submitting the shipment information.", 'wc-biz-courier-logistics'));
		}
	}
}
