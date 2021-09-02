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
 * 	Integration
 * 	------------
 *  This section provides the necessary markdown for initialising the custom Biz integration.
 * 
 */

/**
 * Print HTML notice for missing credentials
 *
 * @since    1.0.0
 * @uses 	 admin_url()
 */
function biz_settings_notice_missing_html()
{
?>
    <div class="notice notice-warning biz-notice is-dismissible">
        <?php echo sprintf(__("Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration')) ?>
    </div>
<?php
}


/**
 * Print HTML notice for invalid credentials
 *
 * @since    1.0.0
 * @uses 	 admin_url()
 */
function biz_settings_notice_invalid_html()
{
?>
    <div class="notice notice-error biz-notice">
        <?php echo sprintf(__("Your Biz Courier credentials are invalid. Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.", "wc-biz-courier-logistics"), admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration'))  ?>
    </div>
<?php
}


/**
 * Print HTML notice for connection errors.
 *
 * @since    1.0.0
 */
function biz_settings_notice_error_html()
{
?>
    <div class="notice notice-error biz-notice">
        <?php _e("There was an error contacting Biz Courier, please try again later.", "wc-biz-courier-logistics")  ?>
    </div>
<?php
}


/**
 * 	Stock Synchronisation
 * 	------------
 *  This section provides all the markdown related to syncing stock 
 * 	between the WooCommerce store and the items in the connected warehouse.
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
 * 	Order Status 
 * 	------------
 *  This section provides the necessary functionality for displaying
 * 	Biz Courier shipment status.
 * 
 */


/**
 * Print HTML for the shipment tracking meta box.
 *
 * @since    1.0.0
 * @param string $voucher The current Biz shipment voucher.
 * @param array $status_history The array containing the status history of the shipment.
 */
function biz_track_shipment_meta_box_html(string $voucher, array $status_history)
{
?>
    <ul>
        <li class="biz-voucher"><?php echo __("Voucher number: ", 'wc-biz-courier-logistics') . '<div>' . $voucher . '</div>' ?></li>
        <li class="biz-shipment-modification">
            <button class="button" id="biz-modify-shipment"><?php _e("Modify shipment", "wc-biz-courier-logistics") ?></button>
            <button class="components-button is-destructive" id="biz-cancel-shipment"><?php _e("Cancel shipment", "wc-biz-courier-logistics") ?></button>
        </li>
        <li>
            <?php
            _e("Status history: ", 'wc-biz-courier-logistics');
            ?>
        </li>
        <li>
            <?php
            foreach (array_reverse($status_history) as $status) {
                echo '<ul class="biz-shipment-status">';
                echo '<li class="status-description">' . $status['description'] . '</li>';
                echo '<li class="status-action">' . $status['action'] . '</li>';
                echo '<li class="status-date">' . $status['date'] . ' ' . __('at', 'wc-biz-courier-logistics') . ' ' . $status['time'] . '</li>';
                echo '</ul>';
            }
            ?>
        </li>
    </ul>
    <!-- <button id="wc-biz-courier-logistics-track-shipment" class="button save-order button-primary" /><?php _e("Track shipment", "wc-biz-courier-logistics") ?></button> -->
<?php
}

/**
 * Print errors in HTML for the shipment tracking meta box.
 *
 * @since    1.0.0
 * @param    string $error The associated error code.
 */
function biz_track_shipment_meta_box_error_html(string $error)
{
    if ($error == 'voucher-error') {
        echo '<p class="biz-shipment-status error">' . __("The Biz Courier voucher is invalid, please contact support.", "wc-biz-courier-logistics") . '</p>';
    }
}

/**
 * Print HTML for the shipment sending meta box.
 *
 * @since    1.0.0
 */
function biz_send_shipment_meta_box_html()
{
?>
    <p class="wc-biz-courier-logistics-order-indicator not-synchronized">
        <?php _e("This order has not shipped with Biz.", "wc-biz-courier-logistics") ?>
    </p>
    <button id="biz-send-shipment" class="button save-order button-primary" />
    <?php _e("Send shipment", "wc-biz-courier-logistics") ?>
    </button>
    <?php
    if (isset($_GET['biz_error'])) {
        if ($_GET['biz_error'] == 'sku-error' || $_GET['biz_error'] == 'biz-package-data-error') {
    ?>
            <p class="biz-send-shipment error"><?php _e('Some products were not found in the Biz warehouse.', 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'metrics-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e('Please make sure all products in the order have their weight & dimensions registered.', 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'biz-auth-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e('There was an error with your Biz credentials.', 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'biz-response-data-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e('There was an unexpected error from Biz.', 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'recipient-info-error' || $_GET['biz_error'] == 'biz-recipient-info-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields.", 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'stock-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e("There isn't enough stock to ship the products.", 'wc-biz-courier-logistics') ?></p>
<?php
        }
    }
}

/**
 * Print errors in HTML for the shipment tracking meta box.
 *
 * @since    1.0.0
 * @param    string $error The associated error code.
 */
function biz_track_shipment_meta_box_cancelled_html()
{
    echo '<p class="biz-shipment-status error">' . __("The Biz Courier shipment was cancelled.", "wc-biz-courier-logistics") . '</p>';
}
