<?php

/**
 * The shipment-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.4.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

/**
 * The shipment-specific functionality of the plugin.
 *
 * Defines all the shipment related functions used by hooks
 * to display plugin views and handle jobs.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WCBizCourierLogisticsShipmentManager extends WCBizCourierLogisticsManager
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

        // Require the Shipment Delegate class.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/class-wc-biz-courier-logistics-shipment-delegate.php');

        // Require the shipment display functions.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/partials/wc-biz-courier-logistics-admin-shipment-display.php');

        // Register the shipment creation script.
        wp_register_script(
            'wc-biz-courier-logistics-shipment-creation',
            plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-shipment-creation.js',
            array(
                'jquery',
                'wc-biz-courier-logistics'
            )
        );

        // Register the shipment management script.
        wp_register_script(
            'wc-biz-courier-logistics-shipment-management',
            plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin-shipment-management.js',
            array('jquery')
        );
    }

    /**
     *  Interface Hooks
     *  ------------
     *  All the functionality related to the user interface.
     */

    /**
     * Add the shipment management metabox.
     *
     * This function adds the shipment management view for all orders.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function addShipmentManagementMetabox(): void
    {
        /**
         * The shipment management metabox.
         *
         * @param WP_Post $post The related post (order).
         * @return void
         */
        function shipmentManagementMetabox($post): void
        {
            if (WCBizCourierLogisticsShipmentDelegate::isSubmitted($post->ID)) {
                // Prepare and localize scripts.
                wp_enqueue_script('wc-biz-courier-logistics-shipment-management');
                wp_localize_script('wc-biz-courier-logistics-shipment-management', "ShipmentProperties", [

                    // Value properties.
                    "bizShipmentModificationRequestNonce" => wp_create_nonce('biz_shipment_modification_request'),
                    "bizShipmentCancellationRequestNonce" => wp_create_nonce('biz_shipment_cancellation_request'),
                    "bizShipmentEditVoucherNonce" => wp_create_nonce('biz_shipment_edit_voucher'),
                    "bizShipmentDeleteVoucherNonce" => wp_create_nonce('biz_shipment_delete_voucher'),
                    "bizShipmentSynchronizeOrderNonce" => wp_create_nonce('biz_shipment_synchronize_order'),
                    "orderID" => $post->ID,

                    // Label properties.
                    "CANCELLATION_REQUEST_CONFIRMATION" => __(
                        "Are you sure you want to request the cancellation of this Biz shipment? If you want to send it again, you will receive a new tracking code.",
                        "wc-biz-courier-logistics"
                    ),
                    "MODIFICATION_REQUEST_PROMPT" => __(
                        "Please insert the message you want to send to Biz about the shipment.",
                        "wc-biz-courier-logistics"
                    ),
                    "EDIT_VOUCHER_PROMPT" => __(
                        "Insert the new shipment voucher number from Biz Courier in the field below.",
                        "wc-biz-courier-logistics"
                    ),
                    "DELETE_VOUCHER_CONFIRMATION" => __(
                        "Are you sure you want to delete the shipment voucher from this order?",
                        "wc-biz-courier-logistics"
                    )
                ]);

                // Get shipment details.
                try {

                    /**
                     * The Biz shipment's delegate.
                     * @var WCBizCourierLogisticsShipmentDelegate
                     */
                    $delegate = new WCBizCourierLogisticsShipmentDelegate($post->ID);

                    /**
                     * The shipment's status history.
                     * @var array
                     */
                    $shipment_history = $delegate->getStatus();
                } catch (\Exception $e) {
                    // Display the caught error for user feedback.
                    notice_display_embedded_html($e->getMessage(), 'failure');
                }

                // Show full shipment details.
                shipmentManagementHTML(
                    $delegate->getVoucher(),
                    $delegate->order->get_status(),
                    $shipment_history ?? null
                );
            } else {
                // Prepare and localize scripts.
                wp_enqueue_script('wc-biz-courier-logistics-shipment-creation');
                wp_localize_script('wc-biz-courier-logistics-shipment-creation', "ShipmentProperties", array(

                    // Value properties.
                    "bizShipmentSendNonce" => wp_create_nonce('biz_shipment_send'),
                    "bizShipmentAddVoucherNonce" => wp_create_nonce('biz_shipment_add_voucher'),
                    "orderID" => $post->ID,

                    // Labels properties.
                    "SEND_SHIPMENT_CONFIRMATION" => __(
                        "Are you sure you want to send this shipment?",
                        'wc-biz-courier-logistics'
                    ),
                    "ADD_VOUCHER_MESSAGE" => __(
                        "Insert the shipment's voucher number from Biz Courier in the field below.",
                        "wc-biz-courier-logistics"
                    )
                ));

                /**
                 * The order's items with Biz compatibility indicators,
                 * formatted titles and admin URLs.
                 * @var array
                 */
                $items = array_map(
                    function ($item) {

                        if ($item['product']->is_type('variation')) {
                            /**
                             * The full and properly formatted variation title.
                             * @var string
                             */
                            $title = $item['product']->get_title() . " - " . wc_get_formatted_variation(
                                new WC_Product_Variation($item['product']),
                                true,
                                false
                            );

                            /**
                             * The variation's parent full admin URL.
                             */
                            $url = get_site_url(
                                '',
                                ('/wp-admin/post.php?post='
                                    . (wc_get_product($item['product']->get_parent_id()))->get_id()
                                    . '&action=edit')
                            );
                        } else {
                            /**
                             * The product title.
                             * @var string
                             */
                            $title = $item['product']->get_title();

                            /**
                             * The product's full admin URL.
                             */
                            $url = get_site_url(
                                '',
                                '/wp-admin/post.php?post=' . $item['product']->get_id() . '&action=edit'
                            );
                        }

                        // Return formatted array.
                        return [
                            'url' => $url ?? null,
                            'compatible' => $item['compatible'],
                            'title' => $title
                        ];
                    },
                    WCBizCourierLogisticsShipmentDelegate::getCompatibleOrderItems($post->ID, false)
                );

                // Show complete shipment preparation view.
                shipmentCreationHTML($items);
            }
        }

        // Add the metabox on the 'Edit' screen.
        if (get_current_screen()->action != 'add') {
            add_meta_box(
                'wc-biz-courier-logistics_send_shipment_meta_box',
                __('Biz Courier status', 'wc-biz-courier-logistics'),
                'shipmentManagementMetabox',
                'shop_order',
                'side',
                'high'
            );
        }
    }

    /**
     * Register the shipment voucher column.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $columns
     * @return array
     */
    public function addShipmentVoucherColumn($columns): array
    {
        $columns['biz-voucher'] = __("Biz shipment voucher", 'wc-biz-courier-logistics');
        return $columns;
    }

    /**
     * Show the shipment voucher column row data.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $column The columns list.
     * @param int $post_id The associated post ID.
     * @return void
     */
    public function shipmentVoucherColumn($column, $post_id): void
    {
        if ($column == 'biz-voucher') {
            // Show the shipment's voucher.
            $this->handleSynchronousRequest(function () use ($post_id) {
                $shipment = new WCBizCourierLogisticsShipmentDelegate($post_id);
                shipmentVoucherColumnHTML($shipment->getVoucher());
            });
        }
    }

    /**
     *  Handler Hooks
     *  ------------
     *  All the functionality related to the
     *  Shipment Management handlers.
     */

    /**
     * Handle the change in order status.
     *
     * This function automatically creates and cancels shipments
     * when the order status changes, based on user preference.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param int $id
     * @param string $from
     * @param string $to
     * @return void
     */
    public function handleOrderStatusChange($id, $from, $to): void
    {
        /**
         * The integration preferences.
         * @var array
         */
        $biz_settings = get_option('woocommerce_biz_integration_settings');

        // 1. Check existing options for automatic shipment creation.
        if (substr(($biz_settings['automatic_shipment_creation'] ?? 'disabled'), 3) == $to) {
            // Handle shipment sending.
            $this->handleSynchronousRequest(function () use ($id) {

                /**
                 * The shipment's delegate.
                 * @var WCBizCourierLogisticsShipmentDelegate
                 */
                $delegate = new WCBizCourierLogisticsShipmentDelegate($id);

                // Match preferred sending state on a voucher order.
                if (empty($delegate->getVoucher())) {
                    $delegate->send();
                }
            }, $id);
        }

        // 2. Check existing options for automatic shipment cancellation.
        if (substr(($biz_settings['automatic_shipment_cancellation'] ?? 'disabled'), 3) == $to) {
            // Handle shipment sending.
            $this->handleSynchronousRequest(function () use ($id) {

                /**
                 * The shipment's delegate.
                 * @var WCBizCourierLogisticsShipmentDelegate
                 */
                $delegate = new WCBizCourierLogisticsShipmentDelegate($id);

                // Match preferred sending cancellation on a voucher order.
                if (!empty($delegate->getVoucher())) {
                    $delegate->cancel();
                }
            }, $id);
        }
    }

    /**
     *  AJAX Handler Hooks
     *  ------------
     *  All the functionality related to the
     *  Shipment Management AJAX handlers.
     */

    /**
     * Handle shipment creation AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleCreation(): void
    {
        // Safely send the shipment.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate(intval($data['order_id']));
            $delegate->send();
        });
    }

    /**
     * Handle shipment modification requests via AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleModificationRequest(): void
    {
        // Safely submit the modification request
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->modify($data['shipment_modification_message']);
        });
    }

    /**
     * Handle shipment cancellation requests via AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleCancellationRequest(): void
    {
        // Safely submit a cancellation request.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->cancel();
        });
    }
    /**
     * Handle voucher addition AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleVoucherAddition(): void
    {
        // Safely register the existing voucher to the order.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->setVoucher($data['new_voucher'], true);
        });
    }

    /**
     * Handle voucher editing AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleVoucherEditing(): void
    {
        // Safely replace with the requested voucher.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->setVoucher($data['new_voucher'], true);
        });
    }

    /**
     * Handle voucher editing AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleVoucherDeletion(): void
    {
        // Safely delete the voucher from the associated order.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->deleteVoucher();
        });
    }

    /**
     * Handle order status synchronization AJAX requests.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function handleOrderStatusSynchronization(): void
    {
        // Safely update the order status based on the shipment's status.
        $this->handleAJAXRequest(function ($data) {
            $delegate = new WCBizCourierLogisticsShipmentDelegate($data['order_id']);
            $delegate->concludeOrder();
        });
    }


    /**
     *  Cron Hooks
     *  ------------
     *  All the functionality related to the
     *  Shipment Management cron scheduled jobs.
     */

    /**
     * Periodically checks for shipments reaching final statuses
     * and updates order status based on user preference.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     */
    public function scoutShipmentStatus(): void
    {
        // Check if the option is enabled.
        $biz_settings = get_option('woocommerce_biz_integration_settings');

        if ($biz_settings['automatic_order_status_updating'] == 'yes') {

            /**
             * All the orders in a `processing` state.
             * @var array
             */
            $orders = wc_get_orders(array(
                'status' => array('wc-processing'),
                'return' => 'ids'
            ));

            // Conclude status for each order with an active voucher.
            foreach ($orders as $wc_order_id) {
                if (WCBizCourierLogisticsShipmentDelegate::isSubmitted($wc_order_id)) {
                    $this->handleSynchronousRequest(function () use ($wc_order_id) {
                        $shipment = new WCBizCourierLogisticsShipmentDelegate($wc_order_id);
                        $shipment->concludeOrder();
                    }, $wc_order_id);
                }
            }
        }
    }

    /**
     *
     * Utility Hooks
     *  ------------
     * Additional internal functionality
     * related to shipment management.
     */

    /**
     * Expands order queries to support a voucher parameter.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $query The active query.
     * @param array $query_vars The active query's variables.
     * @return array
     */
    public function extendVoucherCustomQuery($query, $query_vars): array
    {
        // Check for
        if (!empty($query_vars['voucher'])) {
            $query['meta_query'][] = array(
                'key' => '_biz_voucher',
                'value' => esc_attr($query_vars['voucher']),
            );
        }

        return $query;
    }
}
