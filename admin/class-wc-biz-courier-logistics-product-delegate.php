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
	/** @var WC_Product $product The connected product. */
	public WC_Product $product;

	/** @var bool $aggregated Whether the delegate concerns the product's children as well. */
	public bool $aggregated;

	/** @var bool $permitted An instantiated permission status reference. */
	protected bool $permitted;

	/** @var bool $status_labels The `code` => `label` valid status labels for synchronization status. */
	protected static array $status_labels = [
		'synced' => "Found",
		'not-synced' => "Not found",
		'partial' => "Partially found",
		'pending' => "Pending",
		'disabled' => "Disabled"
	];

	/**
	 * The core constructor method.
	 * 
	 * @param mixed $wc_product_id_sku A WC_Product, a product ID or an SKU.
	 * 
	 * @throws WCBizCourierLogisticsProductDelegateNotAllowedException When the delegate isn't permitted to use the product.
	 * 	NOTE: Use @see `::is_permitted()` to check before instantiating if unsure.
	 * @throws RuntimeException
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function __construct(mixed $wc_product_id_sku)
	{
		// Retrieve the WC_Product.
		if (is_a($this->product, 'WC_Product')) {
			$this->product = $wc_product_id_sku;
		} else {
			// Retrieve the WC_Product by ID.
			$this->product = wc_get_product($wc_product_id_sku);
			if (empty($this->product)) {
				// Retrieve the WC_Product by SKU.
				$id = wc_get_product_id_by_sku($wc_product_id_sku);
				$this->product = wc_get_product($id);
				if (empty($this->product)) {
					throw new RuntimeException(
						__(
							"Unable to retrieve product data.",
							'wc-biz-courier-logistics'
						)
					);
				}
			}
		}

		// Retrieve permission status.
		$this->permitted = self::is_permitted($this->product);
		if (!$this->permitted) {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}

		// Retrieve aggregate status if there are children.
		$this->aggregated = ($this->product->has_child() &&
			!empty(get_post_meta(
				$this->product->get_id(),
				'_biz_stock_sync_aggregate',
				true
			)));
	}

	// TODO @alexandrosraikos: Extend functions that can be considered `aggregate` with apply_to_children(). (#34)

	/**
	 * Prohibit a product from being utilised by the delegate.
	 * 
	 * @throws RuntimeException
	 * 	 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function prohibit(): void
	{
		// Persist prohibition on the instance and the database.
		$this->permitted = !delete_post_meta($this->product->get_id(), '_biz_stock_sync');

		// Check if permission was removed.
		if ($this->permitted) {
			throw new RuntimeException(
				__(
					"The product couldn't be removed from the Biz Warehouse.",
					'wc-biz-courier-logistics'
				)
			);
		}

		// Check if synchronization status were removed.
		if (!delete_post_meta($this->product->get_id(), '_biz_stock_sync_status')) {
			throw new RuntimeException(
				__(
					"The product synchronization status couldn't be deleted.",
					'wc-biz-courier-logistics'
				)
			);
		}
	}

	/**
	 * Enable product children aggregate methods.
	 * 	 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function aggregate(): void
	{
		if ($this->permitted) {
			// Persist change.
			update_post_meta(
				$this->product->get_id(),
				'_biz_stock_sync_aggregate',
				'yes'
			);

			// 
			$this->aggregated = true;
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}
	}

	/**
	 * Disable product children aggregate methods.
	 * 
	 * @throws WCBizCourierLogisticsProductDelegateNotAllowedException If delegate permission has been revoked during the same instance.
	 * @throws RuntimeException
	 * 	 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function separate(): void
	{
		if ($this->permitted) {
			// Persist separation on the instance and the database.
			$this->aggregated = !delete_post_meta($this->product->get_id(), '_biz_stock_sync_aggregate');

			// Check if separation was complete.
			if ($this->aggregated) {
				throw new RuntimeException(
					__(
						"The product synchronization status couldn't be deleted.",
						'wc-biz-courier-logistics'
					)
				);
			}
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}
	}

	/**
	 * Get the Biz synchronization status of a product.
	 * 
	 * @param bool $composite Whether to create a composite label using children's statuses as well.
	 * @return array A tuple-like [`label`, `status`] of the resulting status.
	 * 
	 * @uses self::apply_to_children
	 * @uses self::get_synchronization_status
	 * @throws WCBizCourierLogisticsUnsupportedValueException If the status value found is unsupported.
	 * @throws WCBizCourierLogisticsProductDelegateNotAllowedException If delegate permission has been revoked during the same instance.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function get_synchronization_status(bool $composite = false): array
	{
		if ($this->permitted) {

			/** @var string $status The synchronization status. */
			$status = get_post_meta($this->product->get_id(), '_biz_stock_sync_status', true);

			// Calculate composite label, if preferred.
			if ($composite && $status != 'pending') {
				// Get status labels for all children.
				$children_statuses = $this->apply_to_children(
					function ($child) {
						return $child->get_synchronization_status();
					}
				);

				foreach ($children_statuses as $child_status) {
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

			try {
				return [$status, self::$status_labels[$status]];
			} catch (OutOfBoundsException $e) {
				throw new WCBizCourierLogisticsUnsupportedValueException($status);
			}
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		};
	}

	/**
	 * Set the synchronization status given a specific value.
	 * 
	 * @param string $status The status label to be set.
	 * 	NOTE: Can be either `synced`, `not-synced`, `pending` or `disabled`.
	 * 
	 * @throws WCBizCourierLogisticsUnsupportedValueException When the status label is invalid.
	 * @throws WCBizCourierLogisticsProductDelegateNotAllowedException If delegate permission has been revoked during the same instance.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function set_synchronization_status(string $status): void
	{
		if ($this->permitted) {
			if (array_key_exists($status, self::$status_labels)) {
				update_post_meta($this->product->get_id(), '_biz_stock_sync_status', $status);
			} else {
				throw new WCBizCourierLogisticsUnsupportedValueException($status);
			}
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}
	}

	/**
	 * Reset the synchronization status of the product.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public function reset_synchronization_status(): void
	{
		if ($this->permitted) {
			$this->set_synchronization_status("pending");
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}
	}

	/**
	 * Synchronizes the stock levels of the products.
	 * 
	 * @param int|null $level The desired stock level, or leave null to fetch status from Biz.
	 * 
	 * @uses self::fetch_stock_levels
	 * @uses self::apply_to_children
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public function synchronize_stock_levels(int|null $level): void
	{
		if ($this->permitted) {
			if (isset($level)) {
				// Update remaining stock quantity.
				wc_update_product_stock(
					$this->product,
					($level >= 0) ? $level : 0,
					'set'
				);
			} else {
				/** @var array $stock_levels The retrieved stock levels. */
				$stock_levels = self::fetch_stock_levels();

				/** @var string $sku The delegate instance's SKU. */
				$sku = $this->product->get_sku();

				// Update remaining stock quantity if found in warehouse.
				if (array_key_exists($sku, $stock_levels)) {
					wc_update_product_stock(
						$this->product,
						($stock_levels[$sku] >= 0) ? $stock_levels[$sku] : 0,
						'set'
					);
				}

				// Repeat for all children.
				if ($this->aggregated) {
					$this->apply_to_children(
						function ($child) use ($stock_levels) {
							$sku = $child->product->get_sku();
							if (array_key_exists($sku, $stock_levels)) {
								wc_update_product_stock(
									$child->product,
									($stock_levels[$sku] >= 0) ? $stock_levels[$sku] : 0,
									'set'
								);
							}
						}
					);
				}
			}
		} else {
			throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
				$this->product->get_title()
			);
		}
	}

	/**
	 * Applies the selected method recursively to all
	 * of the instantiated delegate's product's permitted children.
	 * 
	 * @param callable $method The desired callback.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	protected function apply_to_children(callable $method): ?array
	{
		function get_children_delegates(WC_Product $product): array
		{
			return array_map(
				function ($child_id) {
					// Instantiate delegate.
					return new self($child_id);
				},
				// Only permitted children.
				array_filter(
					$product->get_children(),
					function ($child_id) {
						return self::is_permitted(wc_get_product($child_id));
					}
				)
			);
		}

		$delegates = get_children_delegates($this->product);
		$result = [];
		foreach ($delegates as $delegate) {
			$result[] = $method($delegate);
		}
		return $result;
	}

	/**
	 * Allow a product to be utilised by the delegate.
	 * 
	 * @param WC_Product $product The product.
	 * 
	 * @uses self::reset_synchronization_status
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function permit(WC_Product $product): void
	{
		// Persist change.
		update_post_meta($product->get_id(), '_biz_stock_sync', 'yes');

		// Reset synchronization status.
		$delegate = new self($product);
		$delegate->reset_synchronization_status();
	}

	/**
	 * Gets all SKUs of a product or its variants.
	 *
	 * @param	 WC_Product $product A WooCommerce product.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.0.0
	 * 
	 * @version 1.4.0
	 */
	public static function get_sku_group($product)
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
	 * Synchronizes the stock levels of permitted products.
	 * 
	 * @param array|bool $products An array of products, or `true` for all products.
	 * 
	 * @uses self::fetch_stock_levels
	 * @uses self::synchronize_stock_levels
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function just_synchronize_all_stock_levels(array|bool $products = true): void
	{
		/** @var array $stock_levels The retrieved stock levels. */
		$stock_levels = self::fetch_stock_levels();

		// Retrieve all products.
		if (is_bool($products) && !empty($products)) {
			$products = wc_get_products(array(
				'limit' => -1,
			));
		}

		// Update stock levels for each.
		foreach ($products as $product) {
			if (self::is_permitted($product)) {
				$delegate = new self($product);
				if (
					$delegate->enabled &&
					array_key_exists($delegate->product->get_sku(), $stock_levels)
				) {
					$delegate->synchronize_stock_levels(
						$stock_levels[$delegate->product->get_sku()]
					);
				}
			}
		}
	}

	/**
	 * Resets the synchronization status of all permitted products.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.2.0
	 * 
	 * @version 1.4.0
	 */
	public static function just_reset_all_synchronization_status(): void
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
	 * Check if the product has enabled Biz delegate access.
	 * 
	 * @param WC_Product $product The connected product.
	 * @return bool Whether it is permitted to manipulate warehouse data.
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	public static function is_permitted(WC_Product $product): bool
	{
		// Get standalone permission.
		if (!empty(get_post_meta(
			$product->get_id(),
			'_biz_stock_sync',
			true
		))) {
			return true;
		} else {
			// Check for transitive permission by parent.
			$parent_id = $product->get_parent_id();
			if ($parent_id == 0) {
				return false;
			} else {
				return WC_Biz_Courier_Logistics_Product_Delegate::is_permitted(wc_get_product($parent_id));
			}
		}
	}

	/**
	 * Fetch the stock levels of all products.
	 * 
	 * @return array An array with an [`sku` => `level`] schema.
	 * 
	 * @uses WC_Biz_Courier_Logistics::contactBizCourierAPI
	 * 
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.4.0
	 */
	protected static function fetch_stock_levels(): array
	{
		// Fetch status and update stock.
		$response = WC_Biz_Courier_Logistics::contactBizCourierAPI(
			"https://www.bizcourier.eu/pegasus_cloud_app/service_01/prod_stock.php?wsdl",
			'prod_stock',
			[],
			true
		);

		// Return a combined array with SKUs as keys and quantites as values.
		return array_combine(
			array_map(function ($product) {
				return $product['Product_Code'];
			}, $response),
			array_map(function ($bp) {
				return $bp['Remaining_Quantity'];
			}, $response)
		);
	}
}
