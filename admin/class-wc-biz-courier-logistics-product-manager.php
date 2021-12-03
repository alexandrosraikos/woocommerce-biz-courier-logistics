<?php

class WCBizCourierLogisticsProductManager extends WCBizCourierLogisticsManager
{
    public function __construct()
    {
        require_once(
            plugin_dir_path(
                dirname(__FILE__)
            )
            . 'admin/class-wc-biz-courier-logistics-product-delegate.php'
        );

        require_once(
            plugin_dir_path(
                dirname(__FILE__)
            )
            . 'admin/partials/wc-biz-courier-logistics-admin-product-display.php'
        );

        // Scripts for product management.
        wp_register_script(
            'wc-biz-courier-logistics-product-management',
            plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-product-management.js',
            array('jquery')
        );
    }

    /**
     *  Interface Hooks
     *  ------------
     *  All the functionality related to the Product Management WordPress interface.
     */

    public function addSynchronizeAllButton(): void
    {
        global $current_screen;
        if ('product' != $current_screen->post_type) {
            return;
        }

        wp_enqueue_script('wc-biz-courier-logistics-product-management');
        wp_localize_script('wc-biz-courier-logistics-product-management', "StockProperties", array(
            "bizStockSynchronizationNonce" => wp_create_nonce('product_stock_synchronization_all'),
            "PRODUCT_STOCK_SYNCHRONIZATION_ALL_CONFIRMATION" => __(
                "Are you sure you would like to synchronize the stock levels of all the products in the catalogue with your Biz Warehouse?",
                'wc-biz-courier-logistics'
            )
        ));

        product_stock_synchronize_all_button_html();
    }

    public function addDelegateStatusColumn($columns): array
    {
        $columns['biz_sync'] = __('Biz Warehouse', 'wc-biz-courier-logistics');
        return $columns;
    }

    public function delegateStatusColumnIndicator($column_name, $product_post_id): void
    {
        switch ($column_name) {
            case 'biz_sync':
                $this->handleSynchronousRequest(function () use ($product_post_id) {
                    $product = wc_get_product($product_post_id);
                    if (WCBizCourierLogisticsProductDelegate::isPermitted($product)) {
                        $delegate = new WCBizCourierLogisticsProductDelegate($product);
                        $status = $delegate->getSynchronizationStatus(true);
                    }
                    echo product_synchronization_status_indicator($status[0] ?? 'disabled', $status[1] ?? 'Disabled');
                });
        }
    }

    public function addProductManagementMetabox(): void
    {
        function productManagementMetabox($post): void
        {
            wp_enqueue_script('wc-biz-courier-logistics-product-management');
            wp_localize_script('wc-biz-courier-logistics-product-management', "ProductProperties", array(
                "bizProductPermitNonce" => wp_create_nonce('product_permit'),
                "bizProductProhibitNonce" => wp_create_nonce('product_prohibit'),
                "bizProductSynchronizeNonce" => wp_create_nonce('product_synchronize'),
            ));

            $product = wc_get_product($post->ID);

            if (WCBizCourierLogisticsProductDelegate::isPermitted($product) && $product->managing_stock()) {
                $delegate = new WCBizCourierLogisticsProductDelegate($product);
                    product_management_html(
                        $delegate->getSynchronizationStatus(true),
                        $product->get_sku(),
                        $product->get_id(),
                        $product->is_type('variable') ? array_map(
                            function ($child_id) use ($product) {
                                $child = wc_get_product($child_id);
                                $product_title = $product->get_title();
                                $title = wc_get_formatted_variation(
                                    new WC_Product_Variation($child),
                                    true,
                                    false
                                );
                                $sku = $child->get_sku();
                                $permitted = WCBizCourierLogisticsProductDelegate::isPermitted($child);
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
                product_management_disabled_html(
                    $product->get_sku(),
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

        if (get_current_screen()->action != 'add') {
            add_meta_box(
                'wc-biz-courier-logistics_product_management_meta_box',
                __("Biz Warehouse", 'wc-biz-courier-logistics'),
                'productManagementMetabox',
                'product',
                'side',
                'high'
            );
        }
    }


    /**
     *  Handler Hooks
     *  ------------
     *  All the functionality related to the Product Management handlers.
     */

    public function handleProductSKUChange($post_id)
    {
        $product = wc_get_product($post_id);
        if ($_POST['_sku'] != wc_get_product($post_id)->get_sku() &&
            WCBizCourierLogisticsProductDelegate::isPermitted($product)
            ) {
            $delegate = new WCBizCourierLogisticsProductDelegate($product);
            $delegate->setSynchronizationStatus('pending');
        }
    }

    public function handleVariationSKUChange($variation_id)
    {
        
        $variation = wc_get_product($variation_id);

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

    public function handleAllStockSynchronization(): void
    {
        $this->handleAJAXRequest(function () {
            WCBizCourierLogisticsProductDelegate::justSynchronizeAllStockLevels(true);
        });
    }

    public function handleProductDelegatePermission(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $product = wc_get_product($data['product_id']);
            WCBizCourierLogisticsProductDelegate::permit($product);
        });
    }

    public function handleProductDelegateProhibition(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsProductDelegate($data['product_id']);
            $delegate->prohibit();
        });
    }

    public function handleProductSynchronization(): void
    {
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsProductDelegate($data['product_id']);
            $delegate->synchronizeStockLevels();
        });
    }
}
