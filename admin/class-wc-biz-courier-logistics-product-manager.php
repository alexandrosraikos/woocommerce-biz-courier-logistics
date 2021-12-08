<?php

/**
 * The product-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.4.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

/**
 * The product-specific functionality of the plugin.
 *
 * Defines all the shipment related functions used by hooks
 * to display plugin views and handle jobs.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WCBizCourierLogisticsProductManager extends WCBizCourierLogisticsManager
{
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.4.0
     */
    public function __construct()
    {

        // Require the Delegate.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/abstract-wc-biz-courier-logistics-delegate.php'
        );

        // Require the Product Delegate.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/class-wc-biz-courier-logistics-product-delegate.php'
        );

        // Require the display functions.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/partials/wc-biz-courier-logistics-admin-product-display.php'
        );

        // Scripts for product management.
        wp_register_script(
            'wc-biz-courier-logistics-product-management',
            plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-product-management.js',
            array(
                'jquery',
                'wc-biz-courier-logistics'
            )
        );
    }

    /**
     *  Interface Hooks
     *  ------------
     *  All the functionality related to the Product Management WordPress interface.
     */

    /**
     * Add the "Synchronize stock levels" button to the
     * All Products page.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function addSynchronizeAllButton(): void
    {
        // Ensure the current screen is the All Products page.
        global $current_screen;
        if ('product' != $current_screen->post_type) {
            return;
        }

        wp_enqueue_script('wc-biz-courier-logistics-product-management');
        wp_localize_script('wc-biz-courier-logistics-product-management', "StockProperties", array(

            // Value properties.
            "bizStockSynchronizationNonce" => wp_create_nonce('product_stock_synchronization_all'),

            // Label properties.
            "PRODUCT_STOCK_SYNCHRONIZATION_ALL_CONFIRMATION" => __(
                "Are you sure you would like to synchronize the stock levels of all the products in the catalogue with your Biz Warehouse?",
                'wc-biz-courier-logistics'
            )
        ));

        // Print HTML.
        synchronizeAllButtonHTML();
    }

    /**
     * Add the delegate's synchronization status column
     * in the All Products page.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $columns
     * @return array
     * @version 1.4.0
     */
    public function addDelegateStatusColumn($columns): array
    {
        $columns['biz_sync'] = __('Biz Warehouse', 'wc-biz-courier-logistics');
        return $columns;
    }

    /**
     * Print the delegate synchronization status indicator
     * in a product row.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string $column_name
     * @param int $product_post_id
     * @return void
     * @version 1.4.0
     */
    public function delegateStatusColumnIndicator($column_name, $product_post_id): void
    {
        if ($column_name == 'biz_sync') {
            $this->handleSynchronousRequest(function () use ($product_post_id) {
                $product = wc_get_product($product_post_id);
                if (WCBizCourierLogisticsProductDelegate::isPermitted($product)) {
                    $delegate = new WCBizCourierLogisticsProductDelegate($product);
                    $status = $delegate->getSynchronizationStatus(true);
                }
                echo delegateStatusIndicatorHTML($status[0] ?? 'disabled', $status[1] ?? 'Disabled');
            });
        }
    }


    /**
     * Add the product management metabox in the product editing page.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function addProductManagementMetabox(): void
    {

        // Add the metabox.
        if (get_current_screen()->action != 'add') {
            add_meta_box(
                'wc-biz-courier-logistics_product_management_meta_box',
                __("Biz Warehouse", 'wc-biz-courier-logistics'),
                'WCBizCourierLogisticsProductManager::productManagementMetabox',
                'product',
                'side',
                'high'
            );
        }
    }

    /**
     * Print the product management metabox.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param WP_Post $post
     * @return void
     * @version 1.4.0
     */
    public static function productManagementMetabox(): void
    {
        global $post;

        // Prepare scripts
        wp_enqueue_script('wc-biz-courier-logistics-product-management');
        wp_localize_script('wc-biz-courier-logistics-product-management', "ProductProperties", array(
            "bizProductPermitNonce" => wp_create_nonce('product_permit'),
            "bizProductProhibitNonce" => wp_create_nonce('product_prohibit'),
            "bizProductSynchronizeNonce" => wp_create_nonce('product_synchronize'),
        ));

        /**
         * The associated WooCommerce product.
         * @var WC_Product
         */
        $product = wc_get_product($post->ID);

        if (WCBizCourierLogisticsProductDelegate::isPermitted($product) && $product->managing_stock()) {
            /**
             * The product's Biz Courier delegate.
             * @var WCBizCourierLogisticsProductDelegate
             */
            $delegate = new WCBizCourierLogisticsProductDelegate($product);

            // Print HTML.
            productManagementHTML(
                $delegate->getSynchronizationStatus(true),
                $product->get_sku(),
                $product->get_id(),
                $product->is_type('variable') ? array_map(
                    function ($child_id) use ($product) {
                        /**
                         * The child product.
                         * @var WC_Product
                         */
                        $child = wc_get_product($child_id);

                        /**
                         * The formatted product title.
                         * @var string
                         */
                        $product_title = $product->get_title();

                        /**
                         * The formatted variation title.
                         * @var string
                         */
                        $title = wc_get_formatted_variation(
                            new WC_Product_Variation($child),
                            true,
                            false
                        );

                        /**
                         * The child's SKU.
                         * @var string
                         */
                        $sku = $child->get_sku();

                        /**
                         * The child's delegate permission status.
                         * @var bool
                         */
                        $permitted = WCBizCourierLogisticsProductDelegate::isPermitted($child);

                        // Return formatted children array.
                        return [
                            'enabled' => $permitted,
                            'product_title' => $product_title,
                            'title' => $title,
                            'id' => $child_id,
                            'sku' => $sku,
                            'status' => $permitted ?
                                (new WCBizCourierLogisticsProductDelegate($child))->getSynchronizationStatus()
                                : null
                        ];
                    },
                    $product->get_children()
                ) : null
            );
        } else {
            // Print disabled HTML.
            productManagementDisabledHTML(
                $product->get_id(),
                $product->managing_stock() ?
                    __(
                        "You need to enable stock management for this product to activate Biz Courier & Logistics features.",
                        'wc-biz-courier-logistics'
                    )
                    : ''
            );
        }
    }


    /**
     *  Handler Hooks
     *  ------------
     *  All the functionality related to the Product Management handlers.
     */

    /**
     * Handle an SKU change on product save.
     *
     * @since 1.2.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param int $post_id The associated post ID.
     * @return void
     * @version 1.4.0
     */
    public function handleProductSKUChange($post_id)
    {
        /**
         * The associated WooCommerce product.
         * @var WC_Product
         */
        $product = wc_get_product($post_id);

        // Check for SKU change.
        if (
            $_POST['_sku'] != wc_get_product($post_id)->get_sku() &&
            WCBizCourierLogisticsProductDelegate::isPermitted($product)
        ) {
            $delegate = new WCBizCourierLogisticsProductDelegate($product);
            $delegate->setSynchronizationStatus('pending');
        }
    }

    /**
     * Handle an SKU change on product variation save.
     *
     * @since 1.2.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param int $variation_id The associated variation ID.
     * @return void
     * @version 1.4.0
     */
    public function handleVariationSKUChange($variation_id)
    {
        /**
         * The associated WooCommerce product variation.
         */
        $variation = wc_get_product($variation_id);

        // Reset status without checking for SKU change.
        if (WCBizCourierLogisticsProductDelegate::isPermitted($variation)) {
            $delegate = new WCBizCourierLogisticsProductDelegate($variation);
            $delegate->setSynchronizationStatus('pending');
        }
    }

    /**
     *  AJAX Handler Hooks
     *  ------------
     *  All the functionality related to the Product Management AJAX handlers.
     */

    /**
     * Handle product delegate permission (allow) AJAX request.
     *
     * @since 1.3.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function handleProductDelegatePermission(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $product = wc_get_product($data['product_id']);
            WCBizCourierLogisticsProductDelegate::permit($product);
        });
    }

    /**
     * Handle product delegate prohibition (deny) AJAX request.
     *
     * @since 1.3.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function handleProductDelegateProhibition(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsProductDelegate($data['product_id']);
            $delegate->prohibit();
        });
    }

    /**
     * Handle product stock level synchronization AJAX request.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function handleProductSynchronization(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsProductDelegate($data['product_id']);
            $delegate->synchronizeStockLevels();
        });
    }

    /**
     * Handle total stock level synchronization AJAX request.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function handleAllStockSynchronization(): void
    {
        $this->handleAJAXRequest(function () {
            WCBizCourierLogisticsProductDelegate::justSynchronizeAllStockLevels(true);
        });
    }
}
