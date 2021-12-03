<?php

class WCBizCourierLogisticsShipmentDelegate extends WCBizCourierLogisticsDelegate
{
    public WC_Order $order;
    protected array $status_definitions;
    protected ?array $status_report;

    public function __construct(int $order_id)
    {
        DEFINE('BIZ_VOUCHER_KEY', '_biz_voucher');
        DEFINE('BIZ_DELIVERY_FAILURE_KEY', '_biz_failure_delivery_note');

        $this->order = wc_get_order($order_id);
        if (empty($this->order)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __("Unable to retrieve order data.", 'wc-biz-courier-logistics')
            );
        }
    }

    public function getVoucher()
    {
        return get_post_meta($this->order->get_id(), BIZ_VOUCHER_KEY, true);
    }

    public function setVoucher(string $value, bool $conclude = false): void
    {
        $order = wc_get_orders([
            'voucher' => $value
        ]);
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

    public function deleteVoucher()
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

    public static function isSubmitted(int $wc_order_id)
    {
        return !empty(get_post_meta($wc_order_id, BIZ_VOUCHER_KEY, true));
    }

    protected function fetchStatusHistory(string $custom_voucher = null): array
    {
        $biz_status_history = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/full_history.php?wsdl",
            "full_status",
            [
                'Voucher' => $custom_voucher ?? $this->getVoucher()
            ],
            false
        );

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

    public function getStatus(string $custom_voucher = null): array
    {
        $biz_full_status_history = [];

        foreach ($this->fetchStatusHistory($custom_voucher ?? null) as $status) {
            $i = $status['Status_Date'] . '-' . $status['Status_Time'] . '-' . $status['Status_Code'];
            $status_code = !empty($status['Status_Code']) ? $status['Status_Code'] : 'NONE';
            $status_definition = $this->getStatusDefinitions($status_code);

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

            if (!isset($biz_full_status_history[$i])) {
                $biz_full_status_history[$i] = array(
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

            if (!empty($status['Action_Description'])) {
                array_push($biz_full_status_history[$i]['actions'], [
                    'description' => $status[(get_locale() == 'el') ? 'Action_Description' : 'Action_Description_En'],
                    'time' => $status['Action_Date'],
                    'date' => $status['Action_Time'],
                ]);
            }
        }

        return $biz_full_status_history;
    }

    protected function appendDeliveryFailureNote(array $report)
    {
        $failure_delivery_note = (end($report)['level-description']
            . __('Other comments:', 'wc-biz-courier-logistics')
            . '\n');
        foreach (array_reverse($report) as $status) {
            $failure_delivery_note .= ($status['date'] . '-' . $status['time']) . ':\n'
                . ($status['comments'] ?? 'none');
        }
        update_post_meta($this->order->get_id(), BIZ_DELIVERY_FAILURE_KEY, $failure_delivery_note);
    }

    public function concludeOrder(bool $add_note = false, array $report = null): void
    {
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
                __("The newly connected shipment is pending.", 'wc-biz-courier-logistics')
            );
        }
    }

    public function modify($message)
    {
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
                $current_order->add_order_note(
                    __("Message sent to Biz: ", "wc-biz-courier-logistics")
                        . $message
                        . __(" (modification id: ", "wc-biz-courier-logistics") . $response->ModCode . ")"
                );
            }
        );
    }

    public function cancel()
    {
        $voucher = $this->getVoucher();
        $current_order = $this->order;

        self::contactBizCourierAPI(
            "http://www.bizcourier.eu/pegasus_cloud_app/service_01/loc_app/biz_add_act.php?wsdl",
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

    protected static function fetchStatusDefinitions(): array
    {
        $biz_status_definitions = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/TrackEvntSrv.php?wsdl",
            "TrackEvntSrv",
            [],
            true
        );

        $status_definitions = [];

        foreach ($biz_status_definitions as $biz_status_definition) {
            $status_definitions[$biz_status_definition['Status_Code']] = [
                'level' => $biz_status_definition['Level'],
                'description' => $biz_status_definition['Comments']
            ];
        }

        $status_definitions['NONE'] = [
            'level' => 'Pending',
            'description' => __("Delivery status update", 'wc-biz-courier-logistics')
        ];

        $status_definitions['last_updated'] = time();

        update_option('wc_biz_courier_logistics_status_definitions', $status_definitions);
        return $status_definitions;
    }

    public static function getStatusDefinitions(string $identifier = null, bool $force_refresh = false): array
    {
        $status_definitions = get_option('wc_biz_courier_logistics_status_definitions');

        if ($force_refresh || count($status_definitions) < 3) {
            $status_definitions = self::fetchStatusDefinitions();
        } else {
            if ((time() - $status_definitions['last_updated']) > 7 * 24 * 60 * 60 * 60) {
                $status_definitions = self::fetchStatusDefinitions();
            }
        }

        if (!empty($identifier)) {
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

    public static function getCompatibleOrderItems(int $order_id, bool $filter = true): array
    {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';

        $order = wc_get_order($order_id);

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
            $items = array_filter(
                $items,
                function ($item) {
                    return $item['compatible'];
                }
            );
        }

        return $items;
    }

    protected static function getPackageMetrics(array $items): array
    {
        $package_metrics = [
            'width' => 0,
            'height' => 0,
            'length' => 0,
            'weight' => 0
        ];

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

    protected function prepareShipmentData(): array
    {
        $biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');
        $items = self::getCompatibleOrderItems($this->order->get_id());
        if (empty($items)) {
            throw new WCBizCourierLogisticsRuntimeException(
                __(
                    "There are no items included in this order.",
                    'wc-biz-courier-logistics'
                )
            );
        }

        $shipment_products = [];
        foreach ($items as $item) {
            $shipment_products[] = $item['product']->get_sku() . ":" . $item['self']->get_quantity();
        }
        $first_product = array_shift($shipment_products);

        $package_metrics = self::getPackageMetrics($items);

        $phone = $this->order->get_shipping_phone();
        if (empty($phone) && !empty($biz_shipping_settings['biz_billing_phone_usage'])) {
            $phone = (
                ($biz_shipping_settings['biz_billing_phone_usage'] == 'yes')
                ? $this->order->get_billing_phone()
                : '');
        }

        $comments = "";
        if (str_contains($this->order->get_shipping_method(), "Σαββάτου") ||
            str_contains($this->order->get_shipping_method(), "Saturday")
        ) {
            $comments .= "[SATURDAY DELIVERY] ";
        }
        $comments .= "Recipient comments: " . ($this->order->get_customer_note() ?? "none");
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

    public function send(): void
    {
        if ($this->isSubmitted($this->order->get_id())) {
            throw new WCBizCourierLogisticsRuntimeException(
                __("A voucher already exists for this order.", 'wc-biz-courier-logistics')
            );
        }

        $data = $this->prepareShipmentData();
        $response = self::contactBizCourierAPI(
            "https://www.bizcourier.eu/pegasus_cloud_app/service_01/shipmentCreation_v2.2.php?wsdl",
            "newShipment",
            $data,
            true
        );
        switch ($response['Error_Code']) {
            case 0:
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
}
