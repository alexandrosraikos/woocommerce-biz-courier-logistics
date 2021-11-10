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
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */

/**
 * Creates a new shipment with and saves the response voucher in the order's meta
 * as `_biz_voucher`. For more information on this API call visit the official documentation here:
 * https://www.bizcourier.eu/WebServices
 *
 * @param	int $order_id The ID of the WooCommerce order.
 * 
 * @throws	RuntimeException When data are invalid.
 * @throws	ErrorException When there are API errors.
 * @throws  SoapFault When there are connection errors.
 * 
 * @author	Alexandros Raikos <alexandros@araikos.gr>
 * @since	1.0.0
 * @version	1.4.0
 */
function biz_send_shipment(int $order_id): void
{
	// TODO @alexandrosraikos: Handle empty orders or unsupported orders (#36).
	/**
	 * Initialization
	 * 
	 * The following variables and conditions are critical
	 * to the execution of the shipment.
	 */

	/** @var WC_Order $order The order. */
	$order = wc_get_order($order_id);

	// Check for existing voucher.
	if ((!empty(get_post_meta($order->get_id(), '_biz_voucher', true)))) {
		throw new RuntimeException(__("A voucher already exists for this order.", 'wc-biz-courier-logistics'));
	}

	/** @var WC_Order_Item[] $items The order's items. */
	$items = $order->get_items();

	// Check for no items.
	if (empty($items)) throw new RuntimeException(__("There are no items in this order.", 'wc-biz-courier-logistics'));

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

		/** @var WC_Product $product The order item's product data. */
		$product = wc_get_product($item['product_id']);

		// Get exact variation used as the product the referred product is variable.
		if ($product->is_type('variable') && !empty($item['variation_id'])) {
			$product = wc_get_product($item['variation_id']);
		}

		// Check for active Biz synchronization status.
		if (get_post_meta($product->get_id(), '_biz_stock_sync', true) == 'yes') {
			if (get_post_meta($product->get_id(), '_biz_stock_sync_status', true) == 'synced') {

				// Merge order item codes and quantities.
				$shipment_products[] = $product->get_sku() . ":" . $item->get_quantity();

				// Add volume and weight to total dimensions.
				if (
					!empty($product->get_width()) &&
					!empty($product->get_height()) &&
					!empty($product->get_length()) &&
					!empty($product->get_weight())
				) {
					$package_metrics['width'] += $product->get_width() * $item->get_quantity();
					$package_metrics['height'] += $product->get_height() * $item->get_quantity();
					$package_metrics['length'] += $product->get_length() * $item->get_quantity();
					$package_metrics['weight'] += $product->get_weight() * $item->get_quantity();
				} else throw new RuntimeException(__("Please make sure all products in the order have their weight & dimensions registered.", 'wc-biz-courier-logistics'));
			} else throw new RuntimeException(__("Some products were not found in the Biz warehouse. Try going to the All Products page and clicking on \"Get stock levels\" to update their Biz availability.", 'wc-biz-courier-logistics'));
		}
	}

	// Check for supported items.
	if (empty($shipment_products)) throw new RuntimeException(__("There are no Biz items to submit in this order.", 'wc-biz-courier-logistics'));

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
	$phone = $order->get_shipping_phone();

	// Switch to billing phone number if desired or unavailable.
	if (empty($phone) && !empty($biz_shipping_settings['biz_billing_phone_usage'])) {
		$phone = ($biz_shipping_settings['biz_billing_phone_usage'] == 'yes') ? $order->get_billing_phone() : '';
	}

	/** @var string $comments Any order comments. */
	$comments = "";

	// Include comment for Saturday delivery.
	if (
		str_contains($order->get_shipping_method(), "Σαββάτου") ||
		str_contains($order->get_shipping_method(), "Saturday")
	) {
		$comments .= "[SATURDAY DELIVERY] ";
	}

	// Append any recipient comments.
	$comments .= "Recipient comments: " . ($order->get_customer_note() ?? "none");

	/** @var string $morning_delivery Include morrning delivery if present in the shipping method title. @see Biz_Shipping_Method::calculate_shipping */
	$morning_delivery = (str_contains($order->get_shipping_method(), "Πρωινή") || str_contains($order->get_shipping_method(), "Morning")) ? "yes" : "";

	// Check recipient information for completeness.
	if (
		empty($order->get_shipping_first_name()) ||
		empty($order->get_shipping_last_name()) ||
		empty($order->get_shipping_address_1()) ||
		empty($order->get_shipping_country()) ||
		empty($order->get_shipping_city()) ||
		empty($order->get_shipping_postcode()) ||
		empty($phone)
	) throw new RuntimeException(__("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields: First name, last name, phone number, e-mail address, address line #1, city, postal code and country.", 'wc-biz-courier-logistics'));


	/** @var string[] $data The prepared shipment data. */
	$data = [
		"R_Name" => WC_Biz_Courier_Logistics::truncate_field($order->get_shipping_first_name() . " " . $order->get_shipping_last_name()),
		"R_Address" => WC_Biz_Courier_Logistics::truncate_field($order->get_shipping_address_1() . " " . $order->get_shipping_address_2()),
		"R_Area_Code" => $order->get_shipping_country(),
		"R_Area" => WC_Biz_Courier_Logistics::truncate_field($order->get_shipping_city()),
		"R_PC" => $order->get_shipping_postcode(),
		"R_Phone1" => $phone,
		"R_Phone2" => "",
		"R_Email" => WC_Biz_Courier_Logistics::truncate_field($order->get_billing_email(), 60),
		"Length" => $package_metrics['length'], // cm int
		"Width" => $package_metrics['width'], // cm int
		"Height" => $package_metrics['height'], // cm int
		"Weight" => $package_metrics['weight'], // kg int
		"Prod" => explode(":", $first_product)[0],
		"Pieces" => explode(":", $first_product)[1],
		"Multi_Prod" => (count($shipment_products) > 0) ? implode("#", $shipment_products) : '',
		"Cash_On_Delivery" => ($order->get_payment_method() == 'cod') ? number_format($order->get_total(), 2) : '',
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
	];

	// Perform the request.
	WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl",
		"newShipment",
		$data,
		true,
		function ($response) use ($order) {
			// Handle response error codes.
			switch ($response->Error_Code) {
				case 0:
					if (!empty($response->Voucher)) {
		
						// Update order meta.
						delete_post_meta($order->get_id(), '_biz_failure_delivery_note');
						update_post_meta($order->get_id(), '_biz_voucher', $response->Voucher);
		
						// Switch order status to `processing`.
						$order->update_status('processing', __('The shipment was successfully registered to Biz Courier.', 'wc-biz-courier-logistics'));
					} else throw new ErrorException(__("Response from Biz could not be read, please check your warehouse shipments from the official application.", 'wc-biz-courier-logistics'));
				case 1:
					throw new ErrorException(__("There was an error with your Biz credentials.", 'wc-biz-courier-logistics'));
				case 2:
				case 3:
				case 10:
					throw new ErrorException(__("There was a problem registering recipient information with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
				case 4:
					throw new ErrorException(__("There was a problem registering the recipient's area code with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
				case 5:
					throw new ErrorException(__("There was a problem registering the recipient's area with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
				case 6:
					throw new ErrorException(__("There was a problem registering the recipient's phone number with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
				case 7:
					throw new ErrorException(__("The item does not belong to this Biz account. Please check the order.", 'wc-biz-courier-logistics'));
				case 8:
					throw new ErrorException(__("Some products do not belong to this Biz account. Please check the order's items.", 'wc-biz-courier-logistics'));
				case 9:
					throw new ErrorException(__("The shipment's products were incorrectly registered.", 'wc-biz-courier-logistics'));
				case 10:
					throw new ErrorException(__("There was a problem registering the recipient's postal code with Biz. Please fill in all required recipient information entries.", 'wc-biz-courier-logistics'));
				case 11:
					throw new ErrorException(__("There was a problem registering the recipient's postal code with Biz. Please check your recipient information entries.", 'wc-biz-courier-logistics'));
				case 12:
					throw new ErrorException(__("A shipment used for relabeling cannot be used.", 'wc-biz-courier-logistics'));
				default:
					throw new ErrorException(__("An unknown Biz error occured after submitting the shipment information.", 'wc-biz-courier-logistics'));
			}
		}
	);

}

/**
 * Modify a shipment with Biz.
 *
 * @param	 int $order_id The ID of the WooCommerce order.
 * @param string $message? The modification message to include.
 * 
 * @uses 	get_option()
 * @uses	get_post_meta()
 * @uses	update_status()
 * 
 * @throws ErrorException When there is an invalid response from the Biz Courier API.
 * @throws SoapFault When there are connection issues.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since    1.0.0
 */
function biz_modify_shipment(int $order_id, string $message): void
{
	/** @var WC_Order $order The order. */
	$order = wc_get_order($order_id);

	/** @var string $voucher The order's shipment voucher. */
	$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
	if ($voucher == false) throw new RuntimeException(__("The voucher couldn't be retrieved.", 'wc-biz-courier-logistics'));

	// Perform the request.
	WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl",
		"modifyShipment",
		[
			'voucher' => $voucher,
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
 * @since    1.0.0
 * @param	 int $order_id The ID of the WooCommerce order.
 * @uses 	get_option()
 * @uses	get_post_meta()
 * @uses	update_status()
 */
function biz_cancel_shipment($order_id): void
{
	/** @var WC_Order $order The order. */
	$order = wc_get_order($order_id);

	/** @var string $voucher The order's shipment voucher. */
	$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
	if ($voucher == false) throw new RuntimeException(__("The voucher couldn't be retrieved.", 'wc-biz-courier-logistics'));

	// Perform the request.
	WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"http://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl",
		"actionShipment",
		[
			'voucher' => $voucher,
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
 * Get the status history of a Biz Courier shipment using the stored voucher number.
 *
 * @param string $voucher The voucher code associated with the Biz shipment.
 *
 * @uses __soapCall()
 * 
 * @return array An array of the complete status history.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since    1.0.0
 */
function biz_shipment_status($voucher): array
{
	/** @var Object $biz_status_list The official list of status levels. */
	$biz_status_list = WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl",
		"TrackEvntSrv",
		[],
		true
	);

	/** @var array $status_levels All available status levels. */
	$status_levels = [];

	// Populate status levels from the call.
	foreach ($biz_status_list as $biz_status) {
		$status_levels[$biz_status->Status_Code] = [
			'level' => $biz_status->Level,
			'description' => $biz_status->Comments
		];
	}

	// Insert custom `'NONE'` status level.
	$status_levels['NONE'] = [
		'level' => 'Pending',
		'description' => __("Delivery status update", 'wc-biz-courier-logistics')
	];


	/** @var Object $biz_status_list The shipment's status history. */
	$biz_status_history = WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl",
		"full_status",
		[
			'Voucher' => $voucher
		],
		true
	);

	/** @var array $biz_full_status_history The shipment's complete status history. */
	$biz_full_status_history = [];
	foreach ($biz_status_history as $status) {

		/** @var string $i The array key which joins three properties. */
		$i = $status->Status_Date . '-' . $status->Status_Time . '-' . $status->Status_Code;

		/** @var string $status_code The status code of each level. */
		$status_code = $status->Status_Code;
		if (empty($status_code)) $status_code = 'NONE';

		// Reach conclusion on Final levels.
		if ($status_levels[$status_code]['level'] == 'Final') {
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
				'level' => $status_levels[$status_code]['level'] ?? '',
				'level-description' => $status_levels[$status_code]['description'],
				'conclusion' => $conclusion ?? '',
				'description' => (get_locale() == 'el') ? $status->Status_Description : $status->Status_Description_En,
				'comments' => $status->Status_Comments,
				'date' => $status->Status_Date,
				'time' => $status->Status_Time,
				'actions' => array(),
				'last_mile_tracking_number' => $status->Part_Tracking_Num ?? ''
			);
		}

		// Append status actions.
		if (!empty($status->Action_Description)) {
			array_push($biz_full_status_history[$i]['actions'], [
				'description' => (get_locale() == 'el') ? $status->Action_Description : $status->Action_Description_En,
				'time' => $status->Action_Date,
				'date' => $status->Action_Time,
			]);
		}
	}

	// Return full status list.
	return $biz_full_status_history;
}



/**
 * Handles the conclusion of order status based on the shipment status report.
 *
 * @since    1.2.1
 */
function biz_conclude_order_status(string $order_id, bool $note = false, array $report = null): void
{
	// TODO @alexandrosraikos: Clean up and comment.
	try {
		if (!isset($report)) {
			$report = biz_shipment_status(get_post_meta($order_id, '_biz_voucher', true));
		}
		if (end($report)['level'] == 'Final') {
			if (end($report)['conclusion'] == 'completed') {

				// Handle completed shipment status.
				$order = wc_get_order($order_id);
				if ($note) $order->update_status("completed", __("The connected Biz shipment was completed.", 'wc-biz-courier-logistics'));
				else $order->update_status("completed");
			} elseif (end($report)['conclusion'] == 'cancelled') {

				// Handle cancelled shipment status.
				$order = wc_get_order($order_id);
				if ($note) $order->update_status("cancelled", __("The connected Biz shipment was cancelled.", 'wc-biz-courier-logistics'));
				else $order->update_status("cancelled");

				$failure_delivery_note = end($report)['level-description'] . __('Other comments:', 'wc-biz-courier-logistics') . '\n';
				foreach (array_reverse($report) as $status) {
					$failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n' . ($status['comments'] ?? 'none');
				}
				// Add delivery failure note.
				update_post_meta($order_id, '_biz_failure_delivery_note', $failure_delivery_note);
			} elseif (end($report)['conclusion'] == 'failed') {

				// Handle failed shipment status.
				$order = wc_get_order($order_id);
				if ($note) $order->update_status("failed", __("The connected Biz shipment has failed.", 'wc-biz-courier-logistics'));
				else $order->update_status("failed");

				$failure_delivery_note = end($report)['level-description'] . __('Other comments:', 'wc-biz-courier-logistics') . '\n';
				foreach (array_reverse($report) as $status) {
					$failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n' . ($status['comments'] ?? 'none');
				}
				// Add delivery failure note.
				update_post_meta($order_id, '_biz_failure_delivery_note', $failure_delivery_note);
			}
		} else {

			// Handle pending shipment status.
			$order = wc_get_order($order_id);
			$order->update_status("processing", __("The newly connected shipment is pending.", 'wc-biz-courier-logistics'));
		}
	} catch (\Exception $e) {
		throw new $e;
	}
}
