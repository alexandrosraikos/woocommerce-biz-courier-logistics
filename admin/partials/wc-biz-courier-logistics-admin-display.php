<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */

/**
 * 	------------
 * 	Generic
 * 	------------
 *  This section provides generic markup.
 */

/**
 * Display a notice in native WP styling.
 *
 * @param string $message The message to be displayed.
 * @param string $type The type of notice (currently only `warning` and `error` supported).
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function notice_display_html($message, $type = 'error')
{
?>
    <div class="notice notice-<?php echo $type ?>">
        <?php echo $message ?>
    </div>
<?php
}

/**
 * Embed a notice box.
 *
 * @param string $message The message to be displayed.
 * @param string? $type The type of notice (`warning`, `error` & `success` supported).
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function notice_display_embedded_html($message, $type = "")
{
?>
    <div class="wc-biz-courier-logistics-notice <?php echo $type ?>">
        <?php echo $message ?>
    </div>
<?php
}


/**
 * 	------------
 * 	Stock
 * 	------------
 *  This section provides all the markup 
 *  related to stock management.
 * 
 */

/**
 * Print HTML button for stock synchronization.
 *
 * @since    1.0.0
 */
function biz_stock_sync_all_button()
{
?>
    <button class="button button-primary wc-biz-courier-logistics-sync-stock" style="height:32px;">
        <?php _e("Get stock levels", "wc-biz-courier-logistics") ?>
    </button>
<?php
}

/**
 * Print HTML column stock synchronization indicators.
 *
 * @since    1.0.0
 */
function biz_stock_sync_column_html($status)
{
    $label = __("Pending", "wc-biz-courier-logistics");
    switch ($status):
        case 'synced';
            $label = __("Found", "wc-biz-courier-logistics");
            break;
        case 'not-synced';
            $label = __("Not found", "wc-biz-courier-logistics");
            break;
        case 'partial';
            $label = __("Partially found", "wc-biz-courier-logistics");
            break;
        case 'disabled';
            $label = __("Disabled", "wc-biz-courier-logistics");
            break;
    endswitch;
    echo '<div class="biz_sync-indicator ' . $status . '">' . $label . '</div>';
}


/**
 * 	------------
 * 	Shipments
 * 	------------
 *  This section provides the necessary functionality for displaying
 * 	Biz Courier shipment data.
 */


/**
 *  All Orders Page
 * 	------------
 */

/**
 * Print HTML column order voucher.
 *
 * @param string $voucher The order's voucher.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.3.0
 */
function order_column_voucher_html($voucher)
{
    if (empty($voucher)) {
        echo '<span>-</span>';
    } else {
        echo '<a href="https://trackit.bizcourier.eu/app/' . substr(get_locale(), 0, 2) . '/' . $voucher . '" target="blank">' . $voucher . '</a>';
    }
}

/**
 *  Order Page
 * 	------------
 */

/**
 * Print the shipment creation HTML.
 * 
 * @usedby wc-biz-courier-logistics-admin-new-shipment.js
 * @since 1.4.0
 */
function shipment_creation_html()
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
 * @usedby wc-biz-courier-logistics-admin-existing-shipment.js
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function shipment_management_html($voucher, $status, $history)
{
    // TODO @alexandrosraikos: Finish working on styling.
    // TODO @alexandrosraikos: Test all sequences.
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
                            <button class="button" id="biz-synchronize-order">
                                <?php _e("Synchronize order status", "wc-biz-courier-logistics") ?>
                            </button>
            <?php
                        } else {
                            notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You must change the order status to reflect that change.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'warning');
                        }
                    }
                } else {
                    if (end($history)['conclusion'] != "completed") {
                        notice_display_embedded_html(sprintf(__("This shipment has reached a %s state. You cannot perform further actions.", "wc-biz-courier-logistics"), __(end($history)['conclusion'], 'wc-biz-courier-logistics')), 'error');
                    } else {
                        notice_display_embedded_html(__('This shipment was completed. There are no more actions to perform.', 'wc-biz-courier-logistics'), 'success');
                    }
                }
            }
            ?>
        </div>
        <div class="history">
            <h4><?php _e("Status history", 'wc-biz-courier-logistics') ?></h4>
            <?php
            if (!empty($history)) {
            ?>
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
            } else notice_display_embedded_html(__("There is no status history for this shipment.", 'wc-biz-courier-logistics'), 'warning');
    ?>
    </div>
    </div>
<?php
}
