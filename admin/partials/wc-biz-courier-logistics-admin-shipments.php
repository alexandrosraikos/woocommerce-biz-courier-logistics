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
 * Creates a new shipment with Biz and saves the Biz generated voucher in the order's metadata.
 *
 * @since    1.0.0
 * @param	 int $order_id The ID of the WooCommerce order.
 * @uses	 __soapCall()
 * @uses	 wc_get_order()
 * @uses 	 wc_get_product()
 * @uses 	 get_post_meta()
 * @uses 	 get_option()
 * @uses 	 update_post_meta()
 * @uses 	 delete_post_meta()
 */
function biz_send_shipment(int $order_id): bool
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
		$string = (mb_detect_encoding($string) == 'UTF-8') ? $string : utf8_encode($string);
		return (mb_strlen($string, 'UTF-8') > $length) ? mb_substr($string, 0, $length - 1) . "." : $string;
	}

	// Get Biz credentials and shipping settings.
	$biz_settings = get_option('woocommerce_biz_integration_settings');
	$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

	// Get order object.
	$order = wc_get_order($order_id);

	try {
		// Initialize client.
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl", [
			'trace' => 1,
			'exceptions' =>	true,
			'encoding' => 'UTF-8'
		]);

		// Initialize Biz item format array in product_code:quantity format.
		$shipment_products = [];

		// Initialize total volume array.
		$total_dimensions = [
			'width' => 0,
			'height' => 0,
			'length' => 0,
			'weight' => 0
		];

		// Get order items.
		$items = $order->get_items();
		if (empty($items)) throw new UnexpectedValueException('no-products-error');

		// Check for pre-existing voucher or non-processing state.
		if ((empty(get_post_meta($order->get_id(), '_biz_voucher', true)) || $order->get_status() != 'processing')) {
			// Handle each item included in the order.
			foreach ($items as $item) {

				// Handle product and conditions.
				$product = wc_get_product($item['product_id']);
				if ($product->is_type('variable') && !empty($item['variation_id'])) {
					$product = wc_get_product($item['variation_id']);
				}

				// Check for active Biz synchronization status.
				if (get_post_meta($product->get_id(), '_biz_stock_sync_status', true) == 'synced') {

					// Merge order item codes and quantities.
					$shipment_products[] = $product->get_sku() . ":" . $item->get_quantity();

					// Calculate total dimensions.
					if (
						!empty($product->get_width()) &&
						!empty($product->get_height()) &&
						!empty($product->get_length()) &&
						!empty($product->get_weight())
					) {
						$total_dimensions['width'] += $product->get_width() * $item->get_quantity();
						$total_dimensions['height'] += $product->get_height() * $item->get_quantity();
						$total_dimensions['length'] += $product->get_length() * $item->get_quantity();
						$total_dimensions['weight'] += $product->get_weight() * $item->get_quantity();
					} else throw new UnexpectedValueException('metrics-error');
				} else throw new UnexpectedValueException('sku-error');
			}
			
			// Backwards compatible for older PHP versions.
			if (!function_exists('str_contains')) {
				function str_contains($haystack, $needle) {
					return $needle !== '' && mb_strpos($haystack, $needle) !== false;
				}
			}
			
			// Get phone number, order comments and special delivery methods.
			$phone = $order->get_shipping_phone();
			if (empty($phone) && !empty($biz_shipping_settings['biz_billing_phone_usage'])) {
				$phone = ($biz_shipping_settings['biz_billing_phone_usage'] == 'yes') ? $order->get_billing_phone() : '';
			}	
			$comments = "";
			if (
				str_contains($order->get_shipping_method(), "Σαββάτου") ||
				str_contains($order->get_shipping_method(), "Saturday")
			) {
				$comments .= "[SATURDAY DELIVERY] ";
			}
			$comments .= "Recipient comments:" . ($order->get_customer_note() ?? "none");
			$morning_delivery = (str_contains($order->get_shipping_method(), "Πρωινή") || str_contains($order->get_shipping_method(), "Morning")) ? "yes" : "";

			// Check for completeness.
			if (
				empty($order->get_shipping_first_name()) ||
				empty($order->get_shipping_last_name()) ||
				empty($order->get_shipping_address_1()) ||
				empty($order->get_shipping_country()) ||
				empty($order->get_shipping_city()) ||
				empty($order->get_shipping_postcode()) ||
				empty($phone)
			) throw new UnexpectedValueException('recipient-info-error');

			$data = [
				'Code' => $biz_settings['account_number'],
				'CRM' => $biz_settings['warehouse_crm'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password'],
				"R_Name" => truncate_field($order->get_shipping_first_name() . " " . $order->get_shipping_last_name()),
				"R_Address" => truncate_field($order->get_shipping_address_1() . " " . $order->get_shipping_address_2()),
				"R_Area_Code" => $order->get_shipping_country(),
				"R_Area" => truncate_field($order->get_shipping_city()),
				"R_PC" => $order->get_shipping_postcode(),
				"R_Phone1" => $phone,
				"R_Phone2" => "",
				"R_Email" => truncate_field($order->get_billing_email(), 60),
				"Length" => $total_dimensions['length'], // cm int
				"Width" => $total_dimensions['width'], // cm int
				"Height" => $total_dimensions['height'], // cm int
				"Weight" => $total_dimensions['weight'], // kg int
				"Prod" => explode(":", $shipment_products[0])[0],
				"Pieces" => explode(":", $shipment_products[0])[1],
				"Multi_Prod" => (count($shipment_products) > 1) ? implode("#", array_shift($shipment_products)) : '',
				"Cash_On_Delivery" => ($order->get_payment_method() == 'cod') ? number_format($order->get_total(), 2) : '',
				"Checques_On_Delivery" => "", // Unsupported.
				"Comments" => truncate_field($comments, 1000),
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

			// Make SOAP call as per shipment creation API v2.2.
			// $response = $client->__soapCall('newShipment', $data);
			
			switch ($response->Error_Code) {
				case 0:
					if (!empty($response->Voucher)) {
						// Update order information.
						delete_post_meta($order->get_id(), '_biz_failure_delivery_note');
						update_post_meta($order->get_id(), '_biz_voucher', $response->Voucher);
						$order->update_status('processing', __('The shipment was successfully registered to Biz Courier.', 'wc-biz-courier-logistics'));
						return true;
					} else throw new ErrorException('biz-response-data-error');
				case 1:
					throw new ErrorException('biz-auth-error');
				case 2:
				case 3:
				case 10:
					throw new ErrorException('biz-recipient-info-error');
				case 4:
					throw new ErrorException('biz-area-code-error');
				case 5:
					throw new ErrorException('biz-area-error');
				case 6:
					throw new ErrorException('biz-recipient-phone-error');
				case 7:
					throw new ErrorException('biz-product-ownership-error');
				case 8:
					throw new ErrorException('biz-multiple-product-ownership-error');
				case 9:
					throw new ErrorException('biz-product-field-error');
				case 11:
					throw new ErrorException('biz-postal-code-error');
				case 12:
					throw new ErrorException('biz-package-data-error');
				default:
					throw new ErrorException('biz-unknown-error-code-'.$response->Error_Code);
			}
		} else throw new LogicException('voucher-exists-error');
	} catch (SoapFault $fault) {
		throw $fault;
	}
}

/**
 * Modify a shipment with Biz.
 *
 * @since    1.0.0
 * @param	 int $order_id The ID of the WooCommerce order.
 * @uses 	get_option()
 * @uses	get_post_meta()
 * @uses	update_status()
 */
function biz_modify_shipment(int $order_id, string $message = ""): bool
{
	// Get saved order voucher.
	$order = wc_get_order($order_id);
	$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
	if ($voucher == false) throw new UnexpectedValueException('data-error');

	try {
		// Initialize client.
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl", [
			'trace' => 1,
			'exceptions' =>	true,
			'encoding' => 'UTF-8',
		]);

		// Get Biz settings.
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		// Make SOAP call.
		$response = $client->__soapCall('modifyShipment', [
			'Code' => $biz_settings['account_number'],
			'CRM' => $biz_settings['warehouse_crm'],
			'User' => $biz_settings['username'],
			'Pass' => $biz_settings['password'],
			'voucher' => $voucher,
			'modification' => utf8_encode($message)
		]);

		// Handle response.
		if ($response->Error == 0) {
			$order->add_order_note(__("Message sent to Biz: ", "wc-biz-courier-logistics") . $message . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")");
			return true;
		} else throw new ErrorException($response->Error);
	} catch (SoapFault $fault) {
		throw $fault;
	}
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
function biz_cancel_shipment($order_id): bool
{
	// Get saved order voucher.
	$order = wc_get_order($order_id);
	$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
	if ($voucher == false) throw new UnexpectedValueException('data-error');

	try {
		// Initialize client.
		$client = new SoapClient("http://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl", [
			'trace' => 1,
			'exceptions' =>	true,
			'encoding' => 'UTF-8',
		]);

		// Get Biz settings.
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		// Prepare request data.
		$modification_data = [
			'code' => $biz_settings['account_number'],
			'crm' => $biz_settings['warehouse_crm'],
			'user' => $biz_settings['username'],
			'pass' => $biz_settings['password'],
			'voucher' => $voucher,
			'actcode' => 'CANRE',
			'notes' => ''
		];

		// Make SOAP call.
		$response = $client->__soapCall('actionShipment', $modification_data);
		// Handle error codes from response.
		if ($response->Error == 0) {
			$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled. The cancellation code is: %s."), $voucher, $response->ActId), true);
		} elseif ($response->Error == 1) {
			$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled."), $voucher, $response->ActId), true);
		} else throw new ErrorException($response->Error);
		return true;
	} catch (SoapFault $fault) {
		throw $fault;
	}
}



/**
 * Get the status history of a Biz Courier shipment using the stored voucher number.
 *
 * @since    1.0.0
 * @uses 	 __soapCall()
 * @param 	 string $voucher The voucher code associated with the Biz shipment.
 */
function biz_shipment_status($voucher): array
{
	// Get all available shipment statuses.
	try {
		// Initialize client.
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl", [
			'trace' => 1,
			'exceptions' =>	true,
			'encoding' => 'UTF-8',
		]);
		$biz_settings = get_option('woocommerce_biz_integration_settings');

		// Make SOAP call.
		$biz_status_list = $client->__soapCall('TrackEvntSrv', [
			'Code' => $biz_settings['account_number'],
			'CRM' => $biz_settings['warehouse_crm'],
			'User' => $biz_settings['username'],
			'Pass' => $biz_settings['password'],
		]);

		$status_levels = [];
		foreach ($biz_status_list as $biz_status) {
			$status_levels[$biz_status->Status_Code] = [
				'level' => $biz_status->Level,
				'description' => $biz_status->Comments
			];
		}
		$status_levels['NONE'] = [
			'level' => 'Pending',
			'description' => __("Delivery status update", 'wc-biz-courier-logistics')
		];

		// Get specific order status history from Biz.
		$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl", [
			'encoding' => 'UTF-8',
		]);
		$response = $client->__soapCall("full_status", ["Voucher" => $voucher]);

		// Check for invalid voucher.
		if (empty($response)) throw new ErrorException("voucher-error");

		$complete_statuses = [];
		foreach ($response as $status) {

			// Create grouped index.
			$i = $status->Status_Date . '-' . $status->Status_Time . '-' . $status->Status_Code;
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
			if (!isset($complete_statuses[$i])) {
				$complete_statuses[$i] = array(
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
				array_push($complete_statuses[$i]['actions'], [
					'description' => (get_locale() == 'el') ? $status->Action_Description : $status->Action_Description_En,
					'time' => $status->Action_Date,
					'date' => $status->Action_Time,
				]);
			}
		}

		// Return full status list.
		return $complete_statuses;
	} catch (SoapFault $fault) {
		throw $fault;
	}
}



/**
 * Handles the conclusion of order status based on the shipment status report.
 *
 * @since    1.2.1
 */
function biz_conclude_order_status(string $order_id, bool $note = false, array $report = null): bool
{
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

		return true;
	} catch (\Exception $e) {
		throw new $e;
	}
}
