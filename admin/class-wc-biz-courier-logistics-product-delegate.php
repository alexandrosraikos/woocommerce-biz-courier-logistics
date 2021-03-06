<?php

/**
 * Provide stock level synchronization functionality.
 *
 * This file is used to enable functionality regarding
 * the stock level synchronization aspects of the plugin.
 *
 * @link  https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since 1.2.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */

if (!defined('BIZ_ENABLED_OPTION')) {
    DEFINE("BIZ_ENABLED_OPTION", '_biz_stock_sync');
}
if (!defined('BIZ_SYNC_STATUS_OPTION')) {
    DEFINE("BIZ_SYNC_STATUS_OPTION", '_biz_stock_sync_status');
}


class WCBizCourierLogisticsProductDelegate extends WCBizCourierLogisticsDelegate
{
    /**
     * @var WC_Product $product The connected product.
     */
    public WC_Product $product;

    /**
     * @var bool $permitted An instantiated permission status reference.
     */
    protected bool $permitted;

    /**
     * @var array $status_labels The `code` => `label` valid status labels for synchronization status.
     */
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
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param mixed $wc_product_id_sku A WC_Product, a product ID or an SKU.
     * @throws WCBizCourierLogisticsProductDelegateNotAllowedException
     *  NOTE: Use @see `::isPermitted()` to check before instantiating if unsure.
     * @throws WCBizCourierLogisticsRuntimeException
     */
    public function __construct($wc_product_id_sku)
    {
        // Retrieve the WC_Product.
        if (is_a($wc_product_id_sku, 'WC_Product')) {
            $this->product = $wc_product_id_sku;
        } else {
            // Retrieve the WC_Product by ID.
            $product = wc_get_product($wc_product_id_sku);
            if (empty($product)) {
                // Retrieve the WC_Product by SKU.
                $id = wc_get_product_id_by_sku($wc_product_id_sku);
                $this->product = wc_get_product($id);

                // Check for empty product.
                if (empty($this->product)) {
                    throw new WCBizCourierLogisticsRuntimeException(
                        __(
                            "Unable to retrieve product data.",
                            'wc-biz-courier-logistics'
                        )
                    );
                }
            } else {
                $this->product = $product;
            }
        }

        // Retrieve permission status.
        $this->permitted = self::isPermitted($this->product);
        if (!$this->permitted) {
            throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
                $this->product->get_title()
            );
        }
    }

    /**
     * Prohibit a product from being utilised by the delegate.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsRuntimeException
     * @return void
     */
    public function prohibit(): void
    {
        // Check if permission was removed.
        if (!delete_post_meta($this->product->get_id(), BIZ_ENABLED_OPTION)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "The product couldn't be removed from the Biz Warehouse.",
                    'wc-biz-courier-logistics'
                )
            );
        } else {
            $this->permitted = false;
        }

        // Check if synchronization status were removed.
        if (!delete_post_meta($this->product->get_id(), BIZ_SYNC_STATUS_OPTION)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "The product synchronization status couldn't be deleted.",
                    'wc-biz-courier-logistics'
                )
            );
        }
    }

    /**
     * Get the Biz synchronization status of a product.
     *
     * @since  1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param  bool $composite Whether to create a composite label using children's statuses as well.
     * @return array A tuple-like [`status`, `label`] of the resulting status.
     * @throws WCBizCourierLogisticsUnsupportedValueException If the status value found is unsupported.
     * @throws WCBizCourierLogisticsProductDelegateNotAllowedException
     * @version 1.4.0
     */
    public static function getSynchronizationStatus(WC_Product $product, bool $composite = false): array
    {
        $status = get_post_meta($product->get_id(), BIZ_SYNC_STATUS_OPTION, true);
        $status = !empty($status) ? $status : 'disabled';
        $status = $product->managing_stock() ? $status : 'disabled';

        // Calculate composite label, if preferred.
        if ($composite && $status != 'pending') {
            // Get composite status label from all children.
            self::applyToChildren(
                $product,
                function ($child) use (&$status) {
                    $child_status = self::GetSynchronizationStatus($child->product)[0];
                    if (($status == 'synced' && $child_status == 'not-synced') ||
                        ($status == 'not-synced' && $child_status == 'synced')
                    ) {
                        $status = 'partial';
                    } elseif ($status == 'disabled' || $child_status == 'pending') {
                        $status = $child_status;
                    }
                }
            );
        }

        try {
            return [$status, self::$status_labels[$status]];
        } catch (\Exception $e) {
            throw new WCBizCourierLogisticsUnsupportedValueException($status);
        }
    }

    /**
     * Set the synchronization status given a specific value.
     *
     * @since  1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string $status The status label to be set.
     *                       NOTE: Can be either `synced`, `not-synced`, `pending` or `disabled`.
     * @return void
     * @throws WCBizCourierLogisticsUnsupportedValueException When the status label is invalid.
     * @throws WCBizCourierLogisticsProductDelegateNotAllowedException
     * @version 1.4.0
     */
    public function setSynchronizationStatus(string $status): void
    {
        $this->blockingPermissionsCheck();
        if (array_key_exists($status, self::$status_labels)) {
            update_post_meta($this->product->get_id(), BIZ_SYNC_STATUS_OPTION, $status);
        } else {
            throw new WCBizCourierLogisticsUnsupportedValueException($status);
        }
    }

    /**
     * Reset the synchronization status of the product.
     *
     * @since  1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @throws WCBizCourierLogisticsProductDelegateNotAllowedException
     * @version 1.4.0
     */
    public function resetSynchronizationStatus(): void
    {
        if ($this->permitted) {
            $this->SetSynchronizationStatus("pending");
        } else {
            throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
                $this->product->get_title()
            );
        }
    }

    /**
     * Synchronizes the stock levels of the products.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param int|null $level The desired stock level, or leave null to fetch status from Biz.
     */
    public function synchronizeStockLevels(int $level = null): void
    {
        $this->blockingPermissionsCheck();
        if (isset($level)) {
            // Update remaining stock quantity.
            wc_update_product_stock(
                $this->product,
                abs($level),
                'set'
            );
        } else {
            $stock_levels = self::fetchStockLevels();
            $sku = $this->product->get_sku();

            // Update remaining stock quantity if found in warehouse.
            if (array_key_exists($sku, $stock_levels)) {
                wc_update_product_stock(
                    $this->product,
                    ($stock_levels[$sku] >= 0) ? $stock_levels[$sku] : 0,
                    'set'
                );
                $this->SetSynchronizationStatus('synced');
            } else {
                $this->SetSynchronizationStatus('not-synced');
            }

            // Repeat for all children.
            self::applyToChildren(
                $this->product,
                function ($child) use ($stock_levels) {
                    $sku = $child->product->get_sku();
                    if (array_key_exists($sku, $stock_levels)) {
                        wc_update_product_stock(
                            $child->product,
                            abs($stock_levels[$sku]),
                            'set'
                        );
                        $child->SetSynchronizationStatus('synced');
                    } else {
                        $child->SetSynchronizationStatus('not-synced');
                    }
                }
            );
        }
    }

    /**
     * Get the children delegates of a product.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return array The array of children delegates.
     */
    public static function getChildrenDelegates($product): array
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
                    return self::isPermitted(wc_get_product($child_id));
                }
            )
        );
    }

    /**
     * Applies the selected method recursively to all
     * of the instantiated delegate's product's permitted children.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param callable $method The desired callback.
     * @return ?array
     */
    public static function applyToChildren(WC_Product $product, callable $method): ?array
    {

        $delegates = self::getChildrenDelegates($product);
        $result = [];
        foreach ($delegates as $delegate) {
            $result[] = $method($delegate);
        }
        return $result;
    }

    /**
     * Allow a product to be utilised by the delegate.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param WC_Product $product The product.
     * @return void
     */
    public static function permit(WC_Product $product): void
    {
        // Persist change.
        update_post_meta($product->get_id(), BIZ_ENABLED_OPTION, 'yes');

        // Reset synchronization status.
        $delegate = new self($product);
        $delegate->resetSynchronizationStatus();
    }

    /**
     * Gets all SKUs of a product or its variants.
     *
     * @since  1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param WC_Product $product A WooCommerce product.
     * @version 1.4.0
     */
    public static function getSKUGroup($product)
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
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array|bool $products An array of products, or `true` for all products.
     * @return void
     */
    public static function justSynchronizeAllStockLevels($products = true): void
    {

        $stock_levels = self::fetchStockLevels();

        // Retrieve all products.
        if ($products === true) {
            $products = wc_get_products(
                array(
                    'limit' => -1,
                )
            );
        }

        // Update stock levels for each.
        foreach ($products as $product) {
            if (self::isPermitted($product)) {
                $delegate = new self($product);
                if (array_key_exists($delegate->product->get_sku(), $stock_levels)) {
                    $delegate->synchronizeStockLevels(
                        $stock_levels[$delegate->product->get_sku()]
                    );
                    $delegate->SetSynchronizationStatus('synced');
                } else {
                    $delegate->SetSynchronizationStatus('not-synced');
                }
            }

            // Apply for active children too.
            self::applyToChildren(
                $product,
                function ($child_delegate) use ($stock_levels) {
                    if (array_key_exists($child_delegate->product->get_sku(), $stock_levels)) {
                        $child_delegate->synchronizeStockLevels(
                            $stock_levels[$child_delegate->product->get_sku()]
                        );
                        $child_delegate->SetSynchronizationStatus('synced');
                    } else {
                        $child_delegate->SetSynchronizationStatus('not-synced');
                    }
                }
            );
        }
    }

    /**
     * Resets the synchronization status of all permitted products.
     *
     * @since  1.2.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public static function justResetAllSynchronizationStatus(): void
    {
        // Get all products.
        $products = wc_get_products(
            array(
                'posts_per_page' => -1
            )
        );

        // Delete all synchronisation indicators.
        if (!empty($products)) {
            foreach ($products as $product) {
                delete_post_meta($product->get_id(), BIZ_SYNC_STATUS_OPTION);
            }
        }

        // Get all variations.
        $variations = wc_get_products(
            array(
                'posts_per_page' => -1,
                'type' => 'variation'
            )
        );

        // Delete all synchronisation indicators.
        if (!empty($variations)) {
            foreach ($variations as $variation) {
                delete_post_meta($variation->get_id(), BIZ_SYNC_STATUS_OPTION);
            }
        }
    }

    /**
     * Do a throwing permissions check.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsProductDelegateNotAllowedException
     * @return void
     */
    public function blockingPermissionsCheck(): void
    {
        if (!$this->permitted) {
            throw new WCBizCourierLogisticsProductDelegateNotAllowedException(
                $this->product->get_title()
            );
        }
    }

    /**
     * Check if the product has enabled Biz delegate access.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param  WC_Product $product The connected product.
     * @return bool Whether it is permitted to manipulate warehouse data.
     */
    public static function isPermitted(WC_Product $product): bool
    {
        if ($product->managing_stock()) {
            // Get standalone permission.
            if (!empty(get_post_meta(
                $product->get_id(),
                BIZ_ENABLED_OPTION,
                true
            ))) {
                return get_post_meta(
                    $product->get_id(),
                    BIZ_ENABLED_OPTION,
                    true
                ) == 'yes';
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Fetch the stock levels of all products.
     *
     * @since  1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return array An array with an [`sku` => `level`] schema.
     * @throws WCBizCourierLogisticsAPIError
     */
    protected static function fetchStockLevels(): array
    {
        // Fetch status and update stock.
        $response = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/prod_stock.php?wsdl",
            'prod_stock',
            [],
            true,
            null,
            null,
            true
        );

        if (in_array('Error', $response[0])) {
            throw new WCBizCourierLogisticsAPIError($response[0]['Product_Code']);
        }

        // Return a combined array with SKUs as keys and quantites as values.
        return array_combine(
            array_map(
                function ($product) {
                    return $product['Product_Code'];
                },
                $response
            ),
            array_map(
                function ($bp) {
                    return $bp['Remaining_Quantity'];
                },
                $response
            )
        );
    }
}
