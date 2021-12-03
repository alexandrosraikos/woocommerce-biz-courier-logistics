<?php

/**
 * ------------
 * Shipment Management
 * ------------
 * This section provides the necessary markup for
 * managing shipments.
 *
 */

/**
 * Print HTML column order voucher.
 *
 * @param string $voucher The order's voucher.
 *
 * @usedby WC_Biz_Courier_Logistics_Admin::shipment_voucher_column()
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.3.0
 */
function order_column_voucher_html($voucher): void
{
    if (empty($voucher)) {
        echo '<span>-</span>';
    } else {
        echo '<a href="https://trackit.bizcourier.eu/app/' . substr(get_locale(), 0, 2) . '/' . $voucher . '" target="blank">' . $voucher . '</a>';
    }
}


/**
 * Print the shipment creation HTML.
 *
 * @usedby WC_Biz_Courier_Logistics_Admin::add_shipment_management_meta_box()
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 *
 * @version 1.4.0
 */
function shipment_creation_html(?array $items): void
{
    ?>
    <div id="wc-biz-courier-logistics-shipment-management" class="wc-biz-courier-logistics">
        <p><?php _e("This order has not shipped with Biz.", "wc-biz-courier-logistics") ?></p>
        <div class="actions">
            <button data-action="send" class="button button-primary" />
            <?php _e("Send shipment", "wc-biz-courier-logistics") ?>
            </button>
            <button data-action="add-voucher" class="button">
                <?php _e("Add existing voucher number", "wc-biz-courier-logistics") ?>
            </button>
        </div>
        <?php if (!empty($items)) { ?>
            <div class="item-list">
                <h4><?php _e("Warehouse items", 'wc-biz-courier-logistics') ?></h4>
                <p>
                    <?php
                    _e(
                        "Any checked items below will be submitted to Biz. Make sure all the items in the order are enabled and found in the Biz Warehouse:",
                        "wc-biz-courier-logistics"
                    );
                    ?>
                </p>
                <ul>
                    <?php
                    foreach ($items as $item) {
                        ?>
                        <li>
                            <a href="<?php echo $item['url'] ?>" class="<?php echo (($item['compatible']) ? 'compatible' : 'incompatible') ?>">
                                <?php echo file_get_contents(plugin_dir_path(dirname(__FILE__)) . 'svg/' . (($item['compatible']) ? 'completed.svg' : 'cancelled.svg')) ?>
                                <?php echo $item['title'] ?>
                            </a>
                        </li>
                        <?php
                    }
                    ?>
                </ul>
            </div>
        <?php } ?>
    </div>
    <?php
}

/**
 * Print the shipment management HTML.
 *
 * @param string $voucher The connected shipment voucher.
 * @param string $status The order's status.
 * @param array $history The shipment's complete history.
 *
 * @usedby WC_Biz_Courier_Logistics_Admin::add_shipment_management_meta_box()
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 *
 * @version 1.4.0
 */
function shipment_management_html($voucher, $status, $history): void
{
    ?>
    <div id="wc-biz-courier-logistics-shipment-management" class="wc-biz-courier-logistics">
        <div class="voucher">
            <h4><?php _e("Voucher number", 'wc-biz-courier-logistics') ?></h4>
            <div class="number"><?php echo $voucher ?></div>

        <?php
        // Show last mile tracking number, if available.
        if (!empty($history)) {
            if (!empty(end($history)['last_mile_tracking_number'])) {
                ?>
                    <h5><?php _e('Partner tracking number', 'wc-biz-courier-logistics') ?></h5>
                    <div class="partner-number"> <?php echo end($history)['last_mile_tracking_number'] ?></div>
                    <?php
            }
        }
        ?>

            <button data-action="edit">
            <?php _e("Edit voucher", "wc-biz-courier-logistics") ?>
            </button>
            <button data-action="delete">
            <?php _e("Delete voucher", "wc-biz-courier-logistics") ?>
            </button>
        </div>
            <?php
            if (!empty($history)) {
                ?>
            <div class="actions">
                <h4><?php _e("Shipment actions", 'wc-biz-courier-logistics') ?></h4>
                <?php

                // Shipment modification actions.
                if (end($history)['level'] != 'Final') {
                    if ($status == "processing") {
                        ?>
                        <button class="button" data-action="modify">
                            <?php _e("Modify shipment", "wc-biz-courier-logistics") ?>
                        </button>
                        <button class="button" data-action="cancel">
                            <?php _e("Request shipment cancellation", "wc-biz-courier-logistics") ?>
                        </button>
                        <?php
                    } else {
                        notice_display_embedded_html(__('You must change the order status to "Processing" in order to perform more actions on this shipment.', 'wc-biz-courier-logistics'), 'warning');
                        ?>
                        <button class="button button-primary" data-action="sync">
                            <?php _e("Synchronize order status", "wc-biz-courier-logistics") ?>
                        </button>
                        <?php
                    }
                } else {
                    if ($status != end($history)['conclusion']) {
                        $biz_settings = get_option('woocommerce_biz_integration_settings');
                        if ($biz_settings['automatic_order_status_updating'] == 'yes') {
                            if ($status == 'processing') {
                                notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. The order status will be updated automatically in a few minutes. You can disable automatic order status updates in <em>WooCoomerce Settings > Integrations > Biz Courier & Logistics</em>.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')));
                            } else {
                                notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You must change the order status to reflect that change.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'warning');
                            }
                        } else {
                            if ($status == 'processing') {
                                notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You must change the order status to reflect that change. You can also enable automatic order status updates in <em>WooCoomerce Settings > Integrations > Biz Courier & Logistics</em>.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'warning');
                                ?>
                                <button class="button button-primary" data-action="sync">
                                    <?php _e("Synchronize order status", "wc-biz-courier-logistics") ?>
                                </button>
                                <?php
                            } else {
                                notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You must change the order status to reflect that change.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'warning');
                            }
                        }
                    } else {
                        if (end($history)['conclusion'] != "completed") {
                            notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You cannot perform further actions.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'failure');
                        } else {
                            notice_display_embedded_html(__('This shipment was completed. There are no more actions to perform.', 'wc-biz-courier-logistics'), 'success');
                        }
                    }
                }
                ?>
            </div>
            <div class="history">
                <h4><?php _e("Status history", 'wc-biz-courier-logistics') ?></h4>
                <ul class="status-list">
                    <?php
                    foreach (array_reverse($history) as $status) {
                        // Basic information.
                        echo '<li class="status ' . $status['conclusion'] . '">';
                        echo '<span class="level">' . __($status['level'], 'wc-biz-courier-logistics') . '</span>';
                        echo '<h5 class="description">' . $status['description'] . (($status['level'] == "Final") ? file_get_contents(plugin_dir_path(dirname(__FILE__)) . 'svg/' . $status['conclusion'] . '.svg') : "") . '</h5>';
                        echo '<p class="comments">' . ((!empty($status['comments'])) ? $status['comments'] : __('No other comments.', 'wc-biz-courier-logistics')) . '</p>';

                        // Additional status actions.
                        if (!empty($status['actions'])) {
                            echo '<ul class="actions">';
                            echo '<div class="title">' . __('Actions:', 'wc-biz-courier-logistics') . '</div>';
                            foreach (array_reverse($status['actions']) as $action) {
                                echo '<hr/><li class="description">' . $action['description'] . '</li>';
                                echo '<li class="action-date">' . $action['date'] . ' ' . __('at', 'wc-biz-courier-logistics') . ' ' . $action['time'] . '</li>';
                            }
                            echo '</ul>';
                        }

                        // Additional metadata.
                        echo '<span class="date">' . $status['date'] . ' ' . __('at', 'wc-biz-courier-logistics') . ' ' . $status['time'] . '</span>';
                        echo '</li>';
                    }
                    ?>
            </div>
                <?php
            } else {
                notice_display_embedded_html(__("Unable to fetch status history for this shipment.", 'wc-biz-courier-logistics'), 'warning');
            }
            ?>
    </div>
        <?php
}
