<?php

/**
 * The shipment-specific HTML templates of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */

/**
 * Print HTML column order voucher.
 *
 * @since 1.3.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $voucher The order's voucher.
 * @return void
 * @version 1.4.0
 */
function shipmentVoucherColumnHTML(string $voucher): void
{
    if (empty($voucher)) {
        // Print a dash.
        echo "<span>-</span>";
    } else {
        // Print a hyperlink.
        echo "
        <a href=\"https://trackit.bizcourier.eu/app/"
            . substr(get_locale(), 0, 2) . '/' . $voucher . "\" target=\"blank\">"
            . $voucher .
            "</a>";
    }
}


/**
 * Print the shipment creation HTML.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param ?array $items The order's items formatted by @see WCBizCourierShipmentManager::shipmentManagementMetabox.
 * @return void
 * @version 1.4.0
 */
function shipmentCreationHTML(?array $items): void
{
?>
    <div id="wc-biz-courier-logistics-shipment-management" class="wc-biz-courier-logistics">
        <p><?= __("This order has not shipped with Biz.", "wc-biz-courier-logistics") ?></p>
        <div class="actions">
            <button data-action="send" class="button button-primary" />
            <?= __("Send shipment", "wc-biz-courier-logistics") ?>
            </button>
            <button data-action="add-voucher" class="button">
                <?= __("Add existing voucher number", "wc-biz-courier-logistics") ?>
            </button>
        </div>
        <?php if (!empty($items)) { ?>
            <div class="item-list">
                <h4>
                    <?= __("Warehouse items", 'wc-biz-courier-logistics') ?>
                </h4>
                <p>
                    <?=
                    __(
                        "Any checked items below will be submitted to Biz. 
                        Make sure all the items in the order are enabled 
                        and found in the Biz Warehouse:",
                        "wc-biz-courier-logistics"
                    );
                    ?>
                </p>
                <ul>
                    <?php
                    foreach ($items as $item) {
                    ?>
                        <li>
                            <a href="<?= $item['url'] ?>" class="<?= (($item['compatible']) ? 'compatible' : 'incompatible') ?>">
                                <?=
                                file_get_contents(
                                    plugin_dir_path(dirname(__FILE__))
                                        . 'svg/'
                                        . (($item['compatible']) ? 'completed.svg' : 'cancelled.svg')
                                ) ?>
                                <?= $item['title'] ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php } ?>
    </div>
<?php
}

/**
 * Print the shipment management HTML.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $voucher The connected shipment voucher.
 * @param string $status The order's current status.
 * @param array $history The shipment's complete history.
 * @return void
 * @version 1.4.0
 */
function shipmentManagementHTML(string $voucher, string $status, array $history): void
{
?>
    <div id="wc-biz-courier-logistics-shipment-management" class="wc-biz-courier-logistics">
        <div class="voucher">
            <h4><?= __("Voucher number", 'wc-biz-courier-logistics') ?></h4>
            <div class="number">
                <?= $voucher ?>
            </div>
            <?php
            // Show last mile tracking number, if available.
            if (!empty(end($history)['last_mile_tracking_number'])) {
            ?>
                <h5>
                    <?= __('Partner tracking number', 'wc-biz-courier-logistics') ?>
                </h5>
                <div class="partner-number">
                    <?= end($history)['last_mile_tracking_number'] ?>
                </div>
            <?php
            }
            ?>
            <button data-action="edit">
                <?= __("Edit voucher", "wc-biz-courier-logistics") ?>
            </button>
            <button data-action="delete">
                <?= __("Delete voucher", "wc-biz-courier-logistics") ?>
            </button>
        </div>
        <?php
        if (!empty($history)) {
        ?>
            <div class="actions">
                <h4>
                    <?= __("Shipment actions", 'wc-biz-courier-logistics') ?>
                </h4>
                <?php
                // Shipment modification actions.
                if (end($history)['level'] != 'Final') {
                    if ($status == "processing") {
                ?>
                        <button class="button" data-action="modify">
                            <?= __("Modify shipment", "wc-biz-courier-logistics") ?>
                        </button>
                        <button class="button" data-action="cancel">
                            <?= __("Request shipment cancellation", "wc-biz-courier-logistics") ?>
                        </button>
                    <?php
                    } else {
                        notice_display_embedded_html(
                            __(
                                'You must change the order status to "Processing"
                                in order to perform more actions on this shipment.',
                                'wc-biz-courier-logistics'
                            ),
                            'warning'
                        );
                    ?>
                        <button class="button button-primary" data-action="sync">
                            <?= __("Synchronize order status", "wc-biz-courier-logistics") ?>
                        </button>
                        <?php
                    }
                } else {
                    if ($status != end($history)['conclusion']) {
                        $biz_settings = get_option('woocommerce_biz_integration_settings');
                        if ($biz_settings['automatic_order_status_updating'] == 'yes') {
                            if ($status == 'processing') {
                                notice_display_embedded_html(
                                    sprintf(
                                        __(
                                            "This shipment has reached a %s state. The order status will be updated automatically in a few minutes. You can disable automatic order status updates in <em>WooCoomerce Settings > Integrations > Biz Courier & Logistics</em>.",
                                            "wc-biz-courier-logistics"
                                        ),
                                        __(end($history)['conclusion'], 'wc-biz-courier-logistics')
                                    )
                                );
                            } else {
                                notice_display_embedded_html(
                                    sprintf(
                                        __(
                                            "This shipment has reached a %s state. You must change the order status to reflect that change.",
                                            "wc-biz-courier-logistics"
                                        ),
                                        __(end($history)['conclusion'], 'wc-biz-courier-logistics')
                                    ),
                                    'warning'
                                );
                            }
                        } else {
                            if ($status == 'processing') {
                                notice_display_embedded_html(
                                    sprintf(
                                        __(
                                            "This shipment has reached a %s state. You must change the order status to reflect that change. You can also enable automatic order status updates in <em>WooCoomerce Settings > Integrations > Biz Courier & Logistics</em>.",
                                            "wc-biz-courier-logistics"
                                        ),
                                        __(end($history)['conclusion'], 'wc-biz-courier-logistics')
                                    ),
                                    'warning'
                                );
                        ?>
                                <button class="button button-primary" data-action="sync">
                                    <?= __("Synchronize order status", "wc-biz-courier-logistics") ?>
                                </button>
                <?php
                            } else {
                                notice_display_embedded_html(
                                    sprintf(
                                        __(
                                            "This shipment has reached a %s state. You must change the order status to reflect that change.",
                                            "wc-biz-courier-logistics"
                                        ),
                                        __(end($history)['conclusion'], 'wc-biz-courier-logistics')
                                    ),
                                    'warning'
                                );
                            }
                        }
                    } else {
                        if (end($history)['conclusion'] != "completed") {
                            notice_display_embedded_html(
                                sprintf(
                                    __(
                                        "This shipment has reached a %s state. You cannot perform further actions.",
                                        "wc-biz-courier-logistics"
                                    ),
                                    __(end($history)['conclusion'], 'wc-biz-courier-logistics')
                                ),
                                'failure'
                            );
                        } else {
                            notice_display_embedded_html(
                                __(
                                    'This shipment was completed. There are no more actions to perform.',
                                    'wc-biz-courier-logistics'
                                ),
                                'success'
                            );
                        }
                    }
                }
                ?>
            </div>
            <div class="history">
                <h4>
                    <?= __("Status history", 'wc-biz-courier-logistics') ?>
                </h4>
                <ul class="status-list">
                    <?php
                    foreach (array_reverse($history) as $status) {
                    ?>
                        <li class="status <?= $status['conclusion'] ?>">
                            <span class="level">
                                <?= __($status['level'], 'wc-biz-courier-logistics') ?>
                            </span>
                            <h5 class="description">
                                <?= $status['description'] ?>
                                <?=
                                // Status final level icon.
                                (
                                    ($status['level'] == "Final")
                                    ? file_get_contents(
                                        plugin_dir_path(dirname(__FILE__)) . 'svg/' . $status['conclusion'] . '.svg'
                                    )
                                    : ""
                                )
                                ?>
                            </h5>
                            <p class="comments">
                                <?=
                                (
                                    (!empty($status['comments']))
                                    ? $status['comments']
                                    : __('No other comments.', 'wc-biz-courier-logistics')
                                )
                                ?>
                            </p>
                            <?php
                            // Additional status actions.
                            if (!empty($status['actions'])) {
                            ?>
                                <ul class="actions">
                                    <div class="title">
                                        <?= __('Actions:', 'wc-biz-courier-logistics') ?>
                                    </div>
                                    <?php
                                    foreach (array_reverse($status['actions']) as $action) {
                                    ?>
                                        <hr />
                                        <li class="description">
                                            <?= $action['description'] ?>
                                        </li>
                                        <li class="action-date">
                                            <?=
                                            $action['date']
                                                . ' ' . __('at', 'wc-biz-courier-logistics') . ' '
                                                . $action['time']
                                            ?>
                                        </li>
                                    <?php
                                    }
                                    ?>
                                </ul>
                            <?php
                            }
                            ?>
                            <span class="date">
                                <?=
                                $status['date']
                                    . ' ' . __('at', 'wc-biz-courier-logistics') . ' '
                                    . $status['time']
                                ?>
                            </span>
                        </li>
                    <?php
                    }
                    ?>
            </div>
        <?php
        } else {
            notice_display_embedded_html(
                __("Unable to fetch status history for this shipment.", 'wc-biz-courier-logistics'),
                'warning'
            );
        }
        ?>
    </div>
<?php
}
