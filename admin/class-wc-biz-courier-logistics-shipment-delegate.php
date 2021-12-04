<?php
/**
 * The shipment-specific interoperability interface of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.4.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

if (!defined('BIZ_VOUCHER_KEY')) {
    /**
     * The associated query key for vouchers
     * stored in orders.
     * @var string
     */
    DEFINE('BIZ_VOUCHER_KEY', '_biz_voucher');
}

if (!defined('BIZ_DELIVERY_FAILURE_KEY')) {
    /**
     * The associated query key for delivery
     * failure notes stored in orders.
     * @var string
     */
    DEFINE('BIZ_DELIVERY_FAILURE_KEY', '_biz_failure_delivery_note');
}
/**
 * The shipment-specific interoperability interface of the plugin.
 *
 * Defines all the shipment related functions used by hooks
 * to display plugin views and handle jobs.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WCBizCourierLogisticsShipmentDelegate extends WCBizCourierLogisticsDelegate
{
    /**
     * The associated WooCommerce order.
     * @var WC_Order
     */
    public WC_Order $order;

    /**
     * The status history definitions of shipments,
     * as defined by Biz Courier & Logistics.
     * @var array
     * @access protected
     */
    protected array $status_definitions;

    /**
     * The current order's status history report.
     * @var array|null
     * @access protected
     */
    protected ?array $status_report;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.4.0
     * @param int $order_id The ID of the WooCommerce order.
     */
    public function __construct(int $order_id)
    {
        // Get the current order.
        $this->order = wc_get_order($order_id);

        // Throw if order is unretrieavable.
        if (empty($this->order)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __("Unable to retrieve order data.", 'wc-biz-courier-logistics')
            );
        }
    }

    /**
     * Send a shipment to Biz Courier.
     *
     * This function checks and submits compatible order items to
     * Biz Courier & Logistics.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function send(): void
    {
        // Check for existing shipment.
        if ($this->isSubmitted($this->order->get_id())) {
            throw new WCBizCourierLogisticsRuntimeException(
                __("A voucher already exists for this order.", 'wc-biz-courier-logistics')
            );
        }

        /**
         * The shipment's data.
         * @var array
         */
        $data = $this->prepareShipmentData();

        /**
         * The API response.
         * @var array
         */
        $response = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl",
            "newShipment",
            $data,
            true
        );

        // Handle response types.
        switch ($response['Error_Code']) {
            case 0:
                // Clear failure notes and set the new voucher.
                if (!empty($response['Voucher'])) {
                    delete_post_meta($this->order->get_id(), BIZ_DELIVERY_FAILURE_KEY);
                    $this->order->update_status(
                        'processing',
                        __(
                            'The shipment was successfully registered to Biz Courier.',
                            'wc-biz-courier-logistics'
                        )
                    );
                    $this->setVoucher($response['Voucher']);
                    break;
                } else {
                    throw new WCBizCourierLogisticsAPIError(
                        __(
                            "Response from Biz could not be read, please check your 
							warehouse shipments from the official application.",
                            'wc-biz-courier-logistics'
                        )
                    );
                }
            case 1:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was an error with your Biz credentials.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 2:
            case 3:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering recipient information with Biz.
						Please check your recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 4:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering the recipient's area code with Biz.
						Please check your recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 5:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering the recipient's area with Biz.
						Please check your recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 6:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering the recipient's phone number with Biz.
						Please check your recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 7:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "The item does not belong to this Biz account. Please check the order.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 8:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "Some products do not belong to this Biz account. Please check the order's items.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 9:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "The shipment's products were incorrectly registered.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 10:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering the recipient's postal code with Biz.
						Please fill in all required recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 11:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "There was a problem registering the recipient's postal code with Biz.
						Please check your recipient information entries.",
                        'wc-biz-courier-logistics'
                    )
                );
            case 12:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "A shipment used for relabeling cannot be used.",
                        'wc-biz-courier-logistics'
                    )
                );
            default:
                throw new WCBizCourierLogisticsAPIError(
                    __(
                        "An unknown Biz error occured after submitting the shipment information.",
                        'wc-biz-courier-logistics'
                    )
                );
        }
    }

    /**
     * Get the order's voucher number.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsRuntimeException When there is no voucher found.
     * @return string The order's voucher number.
     */
    public function getVoucher(): string
    {
        // Get the voucher.
        $voucher = get_post_meta($this->order->get_id(), BIZ_VOUCHER_KEY, true);

        // Run a validity check.
        if ($voucher === false) {
            throw new WCBizCourierLogisticsRuntimeException(
                "This order does not have a voucher number."
            );
        }

        // Return the voucher.
        return $voucher;
    }

    /**
     * Set the order's voucher number.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsRuntimeException When the voucher is already in use.
     * @throws WCBizCourierLogisticsRuntimeException When the voucher is invalid.
     * @param string $value The new voucher number value.
     * @param bool $conclude Whether to change the order's status after setting.
     * @return string The order's voucher number.
     */
    public function setVoucher(string $value, bool $conclude = false): void
    {
        /**
         * Any orders that may have the voucher value.
         * @var array
         */
        $order = wc_get_orders([
            'voucher' => $value
        ]);

        // Throw accordingly if the voucher exists somewhere else.
        if (count($order) == 1) {
            throw new WCBizCourierLogisticsRuntimeException(
                sprintf(
                    __(
                        "The voucher \"%s\" is already in use by <a href=\"%s\">order #%d</a>.",
                        'wc-biz-courier-logistics'
                    ),
                    $value,
                    get_edit_post_link($order[0]->id),
                    $order[0]->id
                )
            );
        } elseif (count($order) > 1) {
            throw new WCBizCourierLogisticsRuntimeException(
                sprintf(
                    __(
                        "Using the voucher \"%s\" in multiple orders simultaneously is not supported.",
                        'wc-biz-courier-logistics'
                    ),
                    $value
                )
            );
        }

        /**
         * The status history report of this voucher number.
         * @var array
         */
        $report = $this->getStatus($value);

        if (empty($report)) {
            throw new WCBizCourierLogisticsRuntimeException(
                sprintf(
                    __(
                        "The voucher \"%s\" is not registered with Biz. Please provide a valid shipment voucher.",
                        'wc-biz-courier-logistics'
                    ),
                    $value
                )
            );
        } else {
            update_post_meta($this->order->get_id(), BIZ_VOUCHER_KEY, $value);
            if ($conclude) {
                $this->concludeOrder(true, $report);
            }
        }
    }

    /**
     * Delete the order's associated voucher number.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsRuntimeException When the voucher cannot be deleted.
     * @return void
     */
    public function deleteVoucher(): void
    {
        if (!delete_post_meta($this->order->get_id(), BIZ_VOUCHER_KEY)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "The shipment voucher could not be deleted from this order.",
                    'wc-biz-courier-logistics'
                )
            );
        }
    }

    /**
     * Get the fully formatted status of a shipment.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string|null $custom_voucher A custom voucher to use instead.
     * @return array
     */
    public function getStatus(string $custom_voucher = null): array
    {
        /**
         * The fully formatted history of the shipment.
         */
        $formatted_history = [];

        // Process each raw status.
        foreach ($this->fetchStatusHistory($custom_voucher ?? null) as $status) {

            /**
             * The unique associative array index,
             * formatted as date-time-code.
             * @var string
             */
            $i = $status['Status_Date'] . '-' . $status['Status_Time'] . '-' . $status['Status_Code'];

            /**
             * The looped status code, defaults
             * to custom NONE.
             * @var string
             */
            $status_code = !empty($status['Status_Code']) ? $status['Status_Code'] : 'NONE';

            /**
             * The complete definition of the
             * status code.
             * @var array
             */
            $status_definition = $this->getStatusDefinitions($status_code);

            // Assign conclusion label on final status.
            if ($status_definition['level'] == 'Final') {
                if ($status_code == 'ΠΡΔ' ||
                    $status_code == 'COD' ||
                    $status_code == 'OK'
                ) {
                    $conclusion = 'completed';
                } elseif ($status_code == 'AKY') {
                    $conclusion = 'cancelled';
                } else {
                    $conclusion = 'failed';
                }
            }

            // Write without overwriting possibly existing index (for nested actions).
            if (!isset($formatted_history[$i])) {
                $formatted_history[$i] = array(
                    'code' => $status_code,
                    'level' => $status_definition['level'] ?? '',
                    'level-description' => $status_definition['description'],
                    'conclusion' => $conclusion ?? '',
                    'description' => $status[(get_locale() == 'el') ? 'Status_Description' : 'Status_Description_En'],
                    'comments' => $status['Status_Comments'],
                    'date' => $status['Status_Date'],
                    'time' => $status['Status_Time'],
                    'actions' => array(),
                    'last_mile_tracking_number' => $status['Part_Tracking_Num'] ?? ''
                );
            }

            // Append to nested actions array.
            if (!empty($status['Action_Description'])) {
                array_push($formatted_history[$i]['actions'], [
                    'description' => $status[(get_locale() == 'el') ? 'Action_Description' : 'Action_Description_En'],
                    'time' => $status['Action_Date'],
                    'date' => $status['Action_Time'],
                ]);
            }
        }

        return $formatted_history;
    }

    /**
     * Automatically change the order's status using the shipment's status.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param boolean $add_note Append a note to justify the order status change.
     * @param array|null $report Use a pre-existing status report.
     * @return void
     */
    public function concludeOrder(bool $add_note = false, array $report = null): void
    {
        // Get the shipment's status history report.
        if (empty($report)) {
            $report = $this->getStatus();
        }

        if (end($report)['level'] == 'Final') {
            switch (end($report)['conclusion']) {
                case 'completed':
                    $this->order->update_status(
                        "completed",
                        $add_note ? __("The connected Biz shipment was completed.", 'wc-biz-courier-logistics') : ''
                    );
                    break;

                case 'cancelled':
                    $this->order->update_status(
                        "cancelled",
                        $add_note ? __("The connected Biz shipment was cancelled.", 'wc-biz-courier-logistics') : ''
                    );
                    $this->appendDeliveryFailureNote($report);
                    break;

                case 'failed':
                    $this->order->update_status(
                        "failed",
                        $add_note ? __("The connected Biz shipment has failed.", 'wc-biz-courier-logistics') : ''
                    );
                    $this->appendDeliveryFailureNote($report);
                    break;

                default:
                    throw new WCBizCourierLogisticsUnsupportedValueException(
                        end($report)['conclusion']
                    );
            }
        } else {
            $this->order->update_status(
                "processing",
                $add_note ? __("The newly connected shipment is pending.", 'wc-biz-courier-logistics') : ''
            );
        }
    }

    /**
     * Sends a shipment modification request to Biz Courier
     * and adds an order note with the modification ID.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string $message The modification message.
     * @return void
     */
    public function modify(string $message): void
    {
        /**
         * Capture the current order to pass
         * in the completion callback.
         * @var WC_Order
         */
        $current_order = $this->order;

        self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/bizmod.php?wsdl",
            "modifyShipment",
            [
                'voucher' => $this->getVoucher(),
                'modification' =>  self::ensureUTF8($message)
            ],
            true,
            function ($response) use ($current_order, $message) {
                // Add the order note.
                $current_order->add_order_note(
                    __("Message sent to Biz: ", "wc-biz-courier-logistics")
                        . $message
                        . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")"
                );
            }
        );
    }

    /**
     * Sends a shipment cancellation request to Biz Courier
     * and adds an order note with the cancellation ID.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string $message The modification message.
     * @return void
     */
    public function cancel(): void
    {
        /**
         * Capture the current voucher to pass
         * into the completion callback.
         * @var string
         */
        $voucher = $this->getVoucher();

        /**
         * Capture the current order to pass
         * in the completion callback.
         * @var WC_Order
         */
        $current_order = $this->order;

        self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl",
            "actionShipment",
            [
                'voucher' => $this->getVoucher(),
                'actcode' => 'CANRE',
                'notes' => ''
            ],
            true,
            function ($response) use ($current_order, $voucher) {
                $current_order->update_status(
                    'cancelled',
                    sprintf(
                        __(
                            "The Biz shipment with tracking code %s was cancelled. The cancellation code is: %s.",
                            'wc-biz-courier-logistics'
                        ),
                        $voucher,
                        $response->ActId
                    ),
                    true
                );
            },
            function ($error) use ($current_order, $voucher) {

                // Update status and add note.
                $current_order->update_status(
                    'cancelled',
                    sprintf(
                        __(
                            "The Biz shipment with tracking code %s was cancelled.",
                            'wc-biz-courier-logistics'
                        ),
                        $voucher,
                        $error->ActId
                    ),
                    true
                );
            }
        );
    }

    /**
     * Get the shipment status definitions.
     *
     * The retrieval is either local or remote depending on
     * availability and data age.
     *
     * @since 1.3.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string|null $identifier
     * @param bool $force_refresh
     * @return array
     * @version 1.4.0
     */
    public static function getStatusDefinitions(string $identifier = null, bool $force_refresh = false): array
    {
        /**
         * The status definitions,
         * retrieved from storage.
         * @var array
         */
        $status_definitions = get_option('wc_biz_courier_logistics_status_definitions');

        // Examine criteria and refresh definitions from the API.
        if ($force_refresh || count($status_definitions) < 3) {
            $status_definitions = self::fetchStatusDefinitions();
        } else {
            if ((time() - $status_definitions['last_updated']) > 7 * 24 * 60 * 60 * 60) {
                $status_definitions = self::fetchStatusDefinitions();
            }
        }

        if (!empty($identifier)) {
            // Get a specific definition, with refresh if needed.
            if (array_key_exists($identifier, $status_definitions)) {
                return $status_definitions[$identifier];
            } else {
                $status_definitions = self::fetchStatusDefinitions();
                if (array_key_exists($identifier, $status_definitions)) {
                    return $status_definitions[$identifier];
                } else {
                    throw new WCBizCourierLogisticsRuntimeException(
                        sprintf(
                            __(
                                "The definition for the shipment status \"%s\" cannot be found.",
                                'wc-biz-courier-logistics'
                            ),
                            $identifier
                        )
                    );
                }
            }
        } else {
            return $status_definitions;
        }
    }

    /**
     * Get the compatible order items, formatted.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param int $order_id The associated order's ID.
     * @param bool $filter  Whether to exclude non-compatible items on the output.
     * @return array
     */
    public static function getCompatibleOrderItems(int $order_id, bool $filter = true): array
    {
        // Require the Product Delegate.
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

        /**
         * The associated order.
         * @var WC_Order
         */
        $order = wc_get_order($order_id);

        /**
         * Get all items from order, formatted.
         * @var array
         */
        $items = array_map(
            function ($item) {
                $product = wc_get_product($item['product_id']);
                if ($product->is_type('variable') && !empty($item['variation_id'])) {
                    $product = wc_get_product($item['variation_id']);
                }

                if (WCBizCourierLogisticsProductDelegate::isPermitted($product)) {
                    $delegate = new WCBizCourierLogisticsProductDelegate($product);
                    $compatible = ($delegate->getSynchronizationStatus()[0] == 'synced');
                }
                return [
                    'self' => $item,
                    'product' => $product,
                    'compatible' => $compatible ?? false
                ];
            },
            $order->get_items()
        );

        if ($filter) {
            // Remove all non-compatible.
            $items = array_filter(
                $items,
                function ($item) {
                    return $item['compatible'];
                }
            );
        }

        return $items;
    }

    /**
     * Check whether the order has been submitted.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param integer $wc_order_id
     * @return boolean
     */
    public static function isSubmitted(int $wc_order_id): bool
    {
        return !empty(get_post_meta($wc_order_id, BIZ_VOUCHER_KEY, true));
    }

    /**
     * Prepare all shipment data for submission.
     *
     * This function checks order recipient fields, user preferences
     * and shipping options and returns an API compatible array.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @throws WCBizCourierLogisticsRuntimeException When there are no items.
     * @throws WCBizCourierLogisticsRuntimeException When recipient fields are incomplete.
     * @access protected
     * @return array
     */
    protected function prepareShipmentData(): array
    {
        /**
         * The plugin's shipping settings.
         * @var string[]
         */
        $biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

        /**
         * The compatible order items.
         * @var array
         */
        $items = self::getCompatibleOrderItems($this->order->get_id());
        if (empty($items)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "There are no items included in this order.",
                    'wc-biz-courier-logistics'
                )
            );
        }

        /**
         * The shipment's products.
         * @var string[]
         */
        $shipment_products = [];
        
        // Assign items to shipment products with "sku:quantity" formatting.
        foreach ($items as $item) {
            $shipment_products[] = $item['product']->get_sku() . ":" . $item['self']->get_quantity();
        }

        /**
         * The first shipment product.
         */
        $first_product = array_shift($shipment_products);

        /**
         * The total package metrics.
         * @var int[]
         */
        $package_metrics = self::calculatePackageMetrics($items);

        /**
         * The recipient's phone number.
         * @var string
         */
        $phone = $this->order->get_shipping_phone();

        // Try billing phone number.
        if (empty($phone) && !empty($biz_shipping_settings['biz_billing_phone_usage'])) {
            $phone = (
                ($biz_shipping_settings['biz_billing_phone_usage'] == 'yes')
                ? $this->order->get_billing_phone()
                : '');
        }

        /**
         * The recipient's comments.
         * @var string
         */
        $comments = "";

        // Include Saturday delivery in comment.
        if (str_contains($this->order->get_shipping_method(), "Σαββάτου") ||
            str_contains($this->order->get_shipping_method(), "Saturday")
        ) {
            $comments .= "[SATURDAY DELIVERY] ";
        }
        $comments .= "Recipient comments: " . ($this->order->get_customer_note() ?? "none");

        /**
         * The morning delivery indicator.
         * @var string
         */
        $morning_delivery = (str_contains(
            $this->order->get_shipping_method(),
            "Πρωινή"
        ) ||
            str_contains(
                $this->order->get_shipping_method(),
                "Morning"
            ))
            ? "yes"
            : "";

        // Check for required recipient information.
        if (empty($this->order->get_shipping_first_name()) ||
            empty($this->order->get_shipping_last_name()) ||
            empty($this->order->get_shipping_address_1()) ||
            empty($this->order->get_shipping_country()) ||
            empty($this->order->get_shipping_city()) ||
            empty($this->order->get_shipping_postcode()) ||
            empty($phone)
        ) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "There was a problem with the recipient's information.
					Make sure you have filled in all the necessary fields:
					First name, last name, phone number, e-mail address, 
					address line #1, city, postal code and country.",
                    'wc-biz-courier-logistics'
                )
            );
        }

        return [
            "R_Name" => self::truncateField(
                $this->order->get_shipping_first_name() . " " . $this->order->get_shipping_last_name()
            ),
            "R_Address" => self::truncateField(
                $this->order->get_shipping_address_1() . " " . $this->order->get_shipping_address_2()
            ),
            "R_Area_Code" => $this->order->get_shipping_country(),
            "R_Area" => self::truncateField($this->order->get_shipping_city()),
            "R_PC" => $this->order->get_shipping_postcode(),
            "R_Phone1" => $phone,
            "R_Phone2" => "",
            "R_Email" => self::truncateField($this->order->get_billing_email(), 60),
            "Length" => $package_metrics['length'], // cm int
            "Width" => $package_metrics['width'], // cm int
            "Height" => $package_metrics['height'], // cm int
            "Weight" => $package_metrics['weight'], // kg int
            "Prod" => explode(":", $first_product)[0],
            "Pieces" => explode(":", $first_product)[1],
            "Multi_Prod" => !empty($shipment_products) ? implode("#", $shipment_products) : '',
            "Cash_On_Delivery" => (
                ($this->order->get_payment_method() == 'cod')
                ? number_format($this->order->get_total(), 2)
                : ''),
            "Checques_On_Delivery" => "", // Unsupported.
            "Comments" => self::truncateField($comments, 1000),
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
    }

    /**
     * Fetch the raw status history of a shipment from Biz Courier.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param string|null $custom_voucher A custom voucher to use instead.
     * @access protected
     * @return array
     */
    protected function fetchStatusHistory(string $custom_voucher = null): array
    {
        /**
         * The raw status history response from Biz Courier.
         */
        $biz_status_history = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl",
            "full_status",
            [
                'Voucher' => $custom_voucher ?? $this->getVoucher()
            ],
            false
        );

        // Blame the voucher number.
        if (empty($biz_status_history)) {
            throw new WCBizCourierLogisticsRuntimeException(
                sprintf(
                    __(
                        "The voucher \"%s\" is not registered with Biz. Please provide a valid shipment voucher.",
                        'wc-biz-courier-logistics'
                    ),
                    $custom_voucher ?? $this->getVoucher()
                )
            );
        }

        return $biz_status_history;
    }

    /**
     * Append a delivery failure note.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $report The status report.
     * @access protected
     * @return void
     */
    protected function appendDeliveryFailureNote(array $report): void
    {
        /**
         * The delivery failure note.
         */
        $failure_delivery_note = (
            end($report)['level-description']
            . __('Other comments:', 'wc-biz-courier-logistics')
            . '\n'
        );
        
        // Add statuses.
        foreach (array_reverse($report) as $status) {
            $failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n'
                . ($status['comments'] ?? 'none');
        }

        // Persist on database.
        update_post_meta($this->order->get_id(), BIZ_DELIVERY_FAILURE_KEY, $failure_delivery_note);
    }

    /**
     * Fetch the status definitions from the Biz Courier API.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @access protected
     * @return array
     */
    protected static function fetchStatusDefinitions(): array
    {
        /**
         * The raw status definitions from Biz Courier.
         * @var array
         */
        $biz_status_definitions = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl",
            "TrackEvntSrv",
            [],
            true
        );

        /**
         * The formatted status definitions.
         * @var array
         */
        $status_definitions = [];

        // Parse the status definitions.
        foreach ($biz_status_definitions as $biz_status_definition) {
            $status_definitions[$biz_status_definition['Status_Code']] = [
                'level' => $biz_status_definition['Level'],
                'description' => $biz_status_definition['Comments']
            ];
        }

        // Insert custom NONE status definition.
        $status_definitions['NONE'] = [
            'level' => 'Pending',
            'description' => __("Delivery status update", 'wc-biz-courier-logistics')
        ];

        // Insert timestamp for expiry checks.
        $status_definitions['last_updated'] = time();

        // Persist in database.
        update_option('wc_biz_courier_logistics_status_definitions', $status_definitions);

        return $status_definitions;
    }

    /**
     * Calculate the metrics of an order's items.
     *
     * @since 1.4.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @access protected
     * @param array $items The order's items.
     * @return array An array containing `width`, `height`, `length` and `weight`.
     * @throws WCBizCourierLogisticsRuntimeException When there are no product metrics registered.
     */
    protected static function calculatePackageMetrics(array $items): array
    {
        /**
         * The formatted package metrics array.
         * @var array
         */
        $package_metrics = [
            'width' => 0,
            'height' => 0,
            'length' => 0,
            'weight' => 0
        ];

        // Sum all packages' dimensions.
        foreach ($items as $item) {
            if (!empty($item['product']->get_width()) &&
                !empty($item['product']->get_height()) &&
                !empty($item['product']->get_length()) &&
                !empty($item['product']->get_weight())
            ) {
                $package_metrics['width'] += $item['product']->get_width() * $item['self']->get_quantity();
                $package_metrics['height'] += $item['product']->get_height() * $item['self']->get_quantity();
                $package_metrics['length'] += $item['product']->get_length() * $item['self']->get_quantity();
                $package_metrics['weight'] += $item['product']->get_weight() * $item['self']->get_quantity();
            } else {
                throw new WCBizCourierLogisticsRuntimeException(
                    __(
                        "Please make sure all products in the order have their weight & dimensions registered.",
                        'wc-biz-courier-logistics'
                    )
                );
            }
        }

        return $package_metrics;
    }
}
