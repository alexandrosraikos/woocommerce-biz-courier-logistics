<?php

/**
 * Provide stock level synchronization functionality.
 *
 * This file is used to enable functionality regarding 
 * the stock level synchronization aspects of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.2.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */



	/**
	 * Gets all SKUs of a product or its variants.
	 *
	 * @since    1.0.0
	 * @uses 	 get_children()
	 * @uses 	 wc_get_product()
	 * @param	 WC_Product $product A WooCommerce product.
	 */
	function get_all_related_skus($product)
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
	function reset_all_sync_status()
	{
		// Get all products.
		$products = wc_get_products(array(
			'posts_per_page' => -1
		));

		// Delete all synchronisation indicators.
		if (!empty($products)) {
			foreach ($products as $product) {
				delete_post_meta($product->get_id(), '_biz_stock_sync_status');
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
				delete_post_meta($variation->get_id(), '_biz_stock_sync_status');
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
	function biz_stock_sync($skus)
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
				reset_all_sync_status();
				throw new Exception('auth-error');
			}

			// Extract SKUs.
			$retrieved_skus = array_map(function ($bp) {
				return $bp->Product_Code;
			}, $response);
			$retrieved_quantities = array_combine($retrieved_skus, array_map(function ($bp) {
				return $bp->Remaining_Quantity;
			}, $response));

			// Compare with each product in the synchronization call.
			foreach ($skus as $sku) {

				// Check for warehouse availability.
				if (in_array($sku, $retrieved_skus)) {

					// Get the product using the SKU / Biz Product Code.
					$product_post_id = wc_get_product_id_by_sku($sku);

					// Check for active stock syncing.
					if (get_post_meta($product_post_id, '_biz_stock_sync', true)) {
						$product_post = get_post($product_post_id);
						$wc_product = wc_get_product($product_post->ID);

						// Update remaining stock quantity.
						if ($retrieved_quantities[$sku] >= 0) {
							wc_update_product_stock($wc_product, $retrieved_quantities[$sku], 'set');
						} else {
							wc_update_product_stock($wc_product, 0, 'set');
						}

						// Update Biz synchronization post metadata.
						update_post_meta($product_post_id, '_biz_stock_sync_status', 'synced');
					}
				} elseif (!in_array($sku, $retrieved_skus)) {

					// Get the product using the SKU.
					$product_post_id = wc_get_product_id_by_sku($sku);

					if (get_post_meta($product_post_id, '_biz_stock_sync', true)) {
						$product_post = get_post($product_post_id);
						$wc_product = wc_get_product($product_post->ID);
						// Update Biz synchronization post metadata.
						update_post_meta($product_post_id, '_biz_stock_sync_status', 'not-synced');
					}
				}
			}
		} catch (SoapFault $fault) {
			throw new Exception('conn-error');
		}
	}