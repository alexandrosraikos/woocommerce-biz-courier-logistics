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

class WC_Biz_Courier_Logistics_Product_Delegate
{
	public WC_Product $product;

	public function __construct(int $wc_product_id)
	{
		$this->product = wc_get_product($wc_product_id);
		if (empty($this->product)) {
			throw new RuntimeException(__("Unable to retrieve product data.", 'wc-biz-courier-logistics'));
		}
	}

	public function enable(): void
	{
		if (!update_post_meta($this->product->get_id(), '_biz_stock_sync', 'yes')) {
			throw new ErrorException(__("The product couldn't be added to the Biz Warehouse.", 'wc-biz-courier-logistics'));
		}
		$this->reset_synchronization_status();
	}

	public function disable(): void
	{
		if (!delete_post_meta($this->product->get_id(), '_biz_stock_sync')) {
			throw new ErrorException(__("The product couldn't be removed from the Biz Warehouse.", 'wc-biz-courier-logistics'));
		}
		if (!delete_post_meta($this->product->get_id(), '_biz_stock_sync_status')) {
			throw new ErrorException(__("The product synchronization status couldn't be deleted.", 'wc-biz-courier-logistics'));
		}
	}

	public function is_enabled(): bool
	{
		if (!empty(get_post_meta($this->product->get_id(), '_biz_stock_sync', true))) {
			return get_post_meta($this->product->get_id(), '_biz_stock_sync', true) == 'yes';
		} else return false;
	}

	// TODO @alexandrosraikos: Getter and setter functions for enabling/disabling Biz Warehouse option on all variations automatically. (#34)

	public function get_synchronization_status(): string
	{
		if ($this->is_enabled()) {
			return get_post_meta($this->product->get_id(), '_biz_stock_sync_status', true);
		} else return '';
	}

	public function set_synchronization_status($value): void
	{
		if ($this->is_enabled()) {
			if (!update_post_meta($this->product->get_id(), '_biz_stock_sync_status', $value)) {
				throw new ErrorException(__("The synchronization status could not be set.", 'wc-biz-courier-logistics'));
			}
		} else throw new RuntimeException(__("The product is not in the Biz Warehouse", 'wc-biz-courier-logistics'));
	}

	public function reset_synchronization_status(): void
	{
		$this->set_synchronization_status("pending");
	}

	public function synchronize(): void
	{
		self::stock_level_synchronization($this->product->get_sku());
	}

	public function get_composite_synchronization_status(): string
	{
		if (!$this->is_enabled()) {
			return 'disabled';
		}

		$status = $this->get_synchronization_status();

		// Get children variations' synchronization status.
		$children_ids = $this->product->get_children();
		if (!empty($children_ids)) {
			foreach ($children_ids as $child_id) {
				$child = new self($child_id);
				if (!$child->is_enabled()) {
					continue;
				} else {
					$child_status = $child->get_synchronization_status();
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

		return $status;
	}

	/**
	 * Gets all SKUs of a product or its variants.
	 *
	 * @since    1.0.0
	 * @uses 	 get_children()
	 * @uses 	 wc_get_product()
	 * @param	 WC_Product $product A WooCommerce product.
	 */
	public static function get_all_related_skus($product)
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

	public static function reset_all_sync_status()
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
	 * @uses WC_Biz_Courier_Logistics_Admin::reset_all_sync_status()
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public static function stock_level_synchronization($skus)
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
					$delegate = new self(wc_get_product_id_by_sku($sku));

					// Check for active stock syncing.
					if ($delegate->is_enabled()) {
						if (in_array($sku, $retrieved_skus)) {

							// Update remaining stock quantity.
							wc_update_product_stock(
								$delegate->product,
								($retrieved_quantities[$sku] >= 0) ? $retrieved_quantities[$sku] : 0,
								'set'
							);

							$delegate->set_synchronization_status('synced');


							// Update same SKU children.
							if ($delegate->product->has_child()) {
								foreach ($delegate->product->get_children() as $child_id) {
									$child_delegate = new self($child_id);

									if (
										$child_delegate->is_enabled() &&
										$child_delegate->product->get_sku() == $delegate->product->get_sku()
									) {

										// Update remaining stock quantity.
										wc_update_product_stock(
											$child_delegate->product,
											($retrieved_quantities[$sku] >= 0) ? $retrieved_quantities[$sku] : 0,
											'set'
										);

										$child_delegate->set_synchronization_status('synced');
									}
								}
							}
						} else {
							$delegate->set_synchronization_status('not-synced');
						}
					}
				}
			}
		);
	}
}
