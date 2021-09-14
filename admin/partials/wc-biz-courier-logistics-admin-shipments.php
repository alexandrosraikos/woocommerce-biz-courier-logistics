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
	 */
	function biz_send_shipment(int $order_id)
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

		// Get order object.
		$order = wc_get_order($order_id);

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

			// Get order items.
			$items = $order->get_items();

			// Check for pre-existing voucher.
			$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
			if (($voucher == false && get_post_meta($order->get_id(), '_biz_status', true) != 'cancelled') || isset($voucher) && get_post_meta($order->get_id(), '_biz_status', true) == 'cancelled') {

				// Check for existing items in order.
				if (empty($items)) {
					throw new Exception('no-products-error');
				}

				// Handle each item included in the order.
				foreach ($items as $item) {
					$product = wc_get_product($item['product_id']);

					if ($product->is_virtual()) {
						continue;
					} elseif ($product->get_stock_quantity() <= 0) {
						throw new Exception('stock-error');
					}

					// Check for active Biz synchronization status.
					if (get_post_meta($product->get_id(), '_biz_stock_sync_status', true) == 'synced') {
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

				$first_name = $order->get_shipping_first_name();
				$last_name = $order->get_shipping_last_name();
				$phone = ($biz_shipping_settings['biz_billing_phone_usage'] ?? 'no') == 'no' ? $order->get_shipping_phone() : (!empty($order->get_shipping_phone()) ? $order->get_shipping_phone() : $order->get_billing_phone());
				$email = $order->get_billing_email();
				$address_one = $order->get_shipping_address_1();
				$address_two = $order->get_shipping_address_2();
				$country = $order->get_shipping_country();
				$city = $order->get_shipping_city();
				$postcode = $order->get_shipping_postcode();

				// Check for complete information.
				if (empty($last_name) || empty($first_name) || empty($address_one) || empty($country) || empty($city) || empty($postcode) || empty($phone)) {
					throw new Exception('recipient-info-error');
				}

				// Create SMS + phone notification setting.
				$sms_notification = ($biz_shipping_settings['biz_sms_notifications'] == "yes") ? "1" : "0";

				// Prepare SOAP query.
				$shipment_data = array(
					'Code' => $biz_settings['account_number'],
					'CRM' => $biz_settings['warehouse_crm'],
					'User' => $biz_settings['username'],
					'Pass' => $biz_settings['password'],
					"R_Name" => truncate_field($last_name . " " . $first_name),
					"R_Address" => truncate_field($address_one . " " . $address_two),
					"R_Area_Code" => $country,
					"R_Area" => truncate_field($city),
					"R_PC" => $postcode,
					"R_Phone1" => $phone,
					"R_Phone2" => "",
					"R_Email" => truncate_field($email, 60),
					"Length" => $total_order_volume['length'], // cm int
					"Width" => $total_order_volume['width'], // cm int
					"Height" => $total_order_volume['height'], // cm int
					"Weight" => $total_order_volume['weight'], // kg int
					"Prod" => explode(":", $shipment_products[0])[0],
					"Pieces" => explode(":", $shipment_products[0])[1],
					"Multi_Prod" => implode("#", $shipment_products),
					"Cash_On_Delivery" => $cash_on_delivery ?? '',
					"Checques_On_Delivery" => "", // Unsupported.
					"Comments" => ((str_contains($order->get_shipping_method(), "Σαββάτου") || str_contains($order->get_shipping_method(), "Saturday")) ? "Saturday delivery" : "") . " Recipient comments: " . ($order->get_customer_note() ?? "none"),
					"Charge" => "3", // Unsupported, always 3.
					"Type" => "2", // Unsupported, always assume parcel.
					"Relative1" => "", // Unsupported.
					"Relative2" => "", // Unsupported.
					"Delivery_Time_To" => "", // Unsupported.
					"SMS" => $sms_notification,
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
							// Set order status.
							update_post_meta($order_id, '_biz_status', 'sent');
							update_post_meta($order->get_id(), '_biz_voucher', $response->Voucher);
							if ($order->get_status() != 'processing') {
								$order->update_status('processing');
							}
							$order->add_order_note(__('The shipment was successfully registered to Biz Courier.', 'wc-biz-courier-logistics'));
						} else {
							throw new Exception('biz-response-data-error');
						}
						break;
					case 1:
						throw new Exception('biz-auth-error');
					case 2:
					case 3:
					case 4:
					case 5:
					case 6:
					case 10:
					case 11:
						throw new Exception('biz-recipient-info-error');
					case 7:
					case 8:
					case 9:
					case 12:
						throw new Exception('biz-package-data-error');
				}
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}
    /**
	 * Modify a shipment with Biz.
	 *
	 * @since    1.0.0
	 */
	function biz_modify_shipment(int $order_id, string $message = "", bool $automated = true)
	{
		try {
			// Initialize client.
			$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl", array(
				'trace' => 1,
				'encoding' => 'UTF-8',
			));

			// Get saved order voucher.
			$order = wc_get_order($order_id);
			$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
			if ($voucher == false) {
				throw new Exception('data-error');
			}

			// Get Biz settings.
			$biz_settings = get_option('woocommerce_biz_integration_settings');

			// Prepare request data.
			$modification_data = array(
				'Code' => $biz_settings['account_number'],
				'CRM' => $biz_settings['warehouse_crm'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password'],
				'voucher' => $voucher,
				'modification' => utf8_encode($message)
			);

			// Make SOAP call.
			$response = $client->__soapCall('modifyShipment', $modification_data);

			// Handle error codes from response.
			if ($response->Error == 0) {
				$order->add_order_note(__("Message sent to Biz: ", "wc-biz-courier-logistics") . $message . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")");
			} else {
				throw new Exception($response->Error);
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}

	/**
	 * Cancel a shipment with Biz.
	 *
	 * @since    1.0.0
	 */
	function biz_cancel_shipment($order_id)
	{
		try {
			// Initialize client.
			$client = new SoapClient("http://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl", array(
				'trace' => 1,
				'encoding' => 'UTF-8',
			));

			// Get saved order voucher.
			$order = wc_get_order($order_id);
			$voucher = get_post_meta($order->get_id(), '_biz_voucher', true);
			if ($voucher == false) {
				throw new Exception('data-error');
			}

			// Get Biz settings.
			$biz_settings = get_option('woocommerce_biz_integration_settings');

			// Prepare request data.
			$modification_data = array(
				'code' => $biz_settings['account_number'],
				'crm' => $biz_settings['warehouse_crm'],
				'user' => $biz_settings['username'],
				'pass' => $biz_settings['password'],
				'voucher' => $voucher,
				'actcode' => 'CANRE',
				'notes' => ''
			);

			// Make SOAP call.
			$response = $client->__soapCall('actionShipment', $modification_data);
			// Handle error codes from response.
			if ($response->Error == 0) {
				update_post_meta($order->get_id(), '_biz_status', 'cancelled');
				$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled. The cancellation code is: %s."), $voucher, $response->ActId), true);
			} elseif ($response->Error == 1) {
				update_post_meta($order->get_id(), '_biz_status', 'cancelled');
				$order->update_status('cancelled', sprintf(__("The Biz shipment with tracking code %s was cancelled."), $voucher, $response->ActId), true);
			} else {
				throw new Exception($response->Error);
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}



	/**
	 * Get the status history of a Biz Courier shipment using the stored voucher number.
	 *
	 * @since    1.0.0
	 * @uses 	 __soapCall()
	 * @param 	 string $voucher The voucher code associated with the Biz shipment.
	 */
	function biz_shipment_status($voucher)
	{
		// Get all available shipment statuses.
		try {
			// Initialize client.
			$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl", [
				'trace' => 1,
				'encoding' => 'UTF-8',
			]);
			$biz_settings = get_option('woocommerce_biz_integration_settings');

			// Make SOAP call.
			$available_statuses = $client->__soapCall('TrackEvntSrv', [
				'Code' => $biz_settings['account_number'],
				'CRM' => $biz_settings['warehouse_crm'],
				'User' => $biz_settings['username'],
				'Pass' => $biz_settings['password'],
			]);

			$available_status_levels = [];
			foreach ($available_statuses as $available_status) {
				$available_status_levels[$available_status->Status_Code] = [
					'level' => $available_status->Level,
					'description' => $available_status->Comments
				];
			}
			$available_status_levels['NONE'] = [
				'level' => 'Pending',
				'description' => __("Delivery status update",'wc-biz-courier-logistics')
			];

			// Get specific order status history from Biz.
			$client = new SoapClient("https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl", array(
				'encoding' => 'UTF-8',
			));
			$response = $client->__soapCall("full_status", array("Voucher" => $voucher));

			// Check for invalid voucher.
			if (empty($response)) throw new Exception("voucher-error");

			$grouped_statuses = array();

			foreach ($response as $status) {
				$i = $status->Status_Date . '-' . $status->Status_Time . '-' . $status->Status_Code;
				$status_code = $status->Status_Code;
				if (empty($status_code)) {
					$status_code = 'NONE';
				}

				$status_outlook = '';
				if($available_status_levels[$status_code]['level'] == 'Final') {
					if($status_code == 'ΠΡΔ' || $status_code == 'COD' || $status_code == 'OK') $status_outlook = 'sent';
					elseif($status_code == 'AKY') $status_outlook = 'cancelled';
					else $status_outlook = 'failed';
				}
				if (!isset($grouped_statuses[$i])) {
					$grouped_statuses[$i] = array(
						'code' => $status_code,
						'level' => $available_status_levels[$status_code]['level'] ?? '',
						'level-description' => $available_status_levels[$status_code]['description'],
						'outlook' => $status_outlook,
						'description' => (get_locale() == 'el') ? $status->Status_Description : $status->Status_Description_En,
						'comments' => $status->Status_Comments,
						'date' => $status->Status_Date,
						'time' => $status->Status_Time,
						'actions' => array(),
						'last_mile_tracking_number' => $status->Part_Tracking_Num ?? ''
					);
				}
				if (isset($status->Action_Description)) {
					array_push($grouped_statuses[$i]['actions'], array(
						'description' => (get_locale() == 'el') ? $status->Action_Description : $status->Action_Description_En,
						'time' => $status->Action_Date,
						'date' => $status->Action_Time,
					));
				}
			}

			return $grouped_statuses;
		} catch (SoapFault $fault) {
			throw new Exception('There was a connection error (' . $fault->getMessage() . ').');
		}
	}
