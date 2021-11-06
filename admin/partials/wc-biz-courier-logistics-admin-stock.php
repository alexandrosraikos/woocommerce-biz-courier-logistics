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
 * @param array $skus An array of product skus formatted as strings.
 * 
 * @uses get_option()
 * @uses __soapCall()
 * @uses WC_Biz_Courier_Logistics_Admin::reset_all_sync_status()
 * @uses in_array()
 * @uses wc_get_product_id_by_sku()
 * @uses wc_get_product()
 * @uses get_post()
 * @uses update_post_meta()
 * @uses delete_post_meta()
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @version 1.4.0
 * @since 1.0.0
 * 
 */
function biz_stock_sync($skus)
{
	WC_Biz_Courier_Logistics::contactBizCourierAPI(
		"https://www.bizcourier.eu/pegasus_cloud_app/service_01/prod_stock.php?wsdl",
		'prod_stock',
		[],
		true,
		function ($data) use ($skus) {

			/** @var array $retrieved_skus The extracted SKUs from the Biz response. */
			$retrieved_skus = array_map(function ($bp) {
				return $bp->Product_Code;
			}, $data);

			/** @var array $retrieved_quantities The extracted quantities from the Biz response. */
			$retrieved_quantities = array_combine($retrieved_skus, array_map(function ($bp) {
				return $bp->Remaining_Quantity;
			}, $data));

			// Compare with each product in the synchronization call.
			foreach ($skus as $sku) {

				// Check for warehouse availability.
				if (in_array($sku, $retrieved_skus)) {

					/** @var int $product_post_id The ID of the product. */
					$product_post_id = wc_get_product_id_by_sku($sku);

					// Check for active stock syncing.
					if (get_post_meta($product_post_id, '_biz_stock_sync', true) == 'yes') {
						/** @var WP_Post $product_post The post. */
						$product_post = get_post($product_post_id);

						/** @var WC_Product $wc_product The product. */
						$wc_product = wc_get_product($product_post->ID);

						// Update remaining stock quantity.
						wc_update_product_stock($wc_product, ($retrieved_quantities[$sku] >= 0) ? $retrieved_quantities[$sku] : 0, 'set');

						// Update Biz synchronization post metadata.
						update_post_meta($product_post_id, '_biz_stock_sync_status', 'synced');

						// Update same SKU children.
						if ($wc_product->has_child()) {
							foreach ($wc_product->get_children() as $child_id) {

								/** @var WC_Product $child_product The product's child product. */
								$child_product = wc_get_product($child_id);
								if (get_post_meta($child_product->get_id(), '_biz_stock_sync', true) == 'yes' && $child_product->get_sku() == $wc_product->get_sku()) {

									// Update remaining stock quantity.
									wc_update_product_stock($child_product, ($retrieved_quantities[$sku] >= 0) ? $retrieved_quantities[$sku] : 0, 'set');

									// Update Biz synchronization post metadata.
									update_post_meta($child_product->get_id(), '_biz_stock_sync_status', 'synced');
								}
							}
						}
					}
				} else {

					// Get the product using the SKU.
					$product_post_id = wc_get_product_id_by_sku($sku);

					if (get_post_meta($product_post_id, '_biz_stock_sync', true) == 'yes') {

						/** @var WP_Post $product_post The post. */
						$product_post = get_post($product_post_id);

						/** @var WC_Product $wc_product The product. */
						$wc_product = wc_get_product($product_post->ID);

						// Update Biz synchronization post metadata.
						update_post_meta($product_post_id, '_biz_stock_sync_status', 'not-synced');
					}
				}
			}
		}
	);
}
