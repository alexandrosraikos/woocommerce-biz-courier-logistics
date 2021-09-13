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
function biz_track_shipment_meta_box_html(string $voucher, array $status_history, bool $completed = false)
{
?>
    <ul>
        <li class="biz-voucher"><?php echo __("Voucher number: ", 'wc-biz-courier-logistics') . '<div>' . $voucher . '</div>' ?></li>
        <li class="biz-shipment-modification">
            <?php if (!$completed) { ?>
                <button class="button" id="biz-modify-shipment"><?php _e("Modify shipment", "wc-biz-courier-logistics") ?></button>
            <?php } ?>

            <button class="button" id="biz-edit-shipment-voucher"><?php _e("Edit voucher", "wc-biz-courier-logistics") ?></button>
            <button class="components-button is-destructive" id="biz-delete-shipment-voucher"><?php _e("Delete voucher", "wc-biz-courier-logistics") ?></button>
            <?php if (!$completed) { ?>
                <button class="components-button is-destructive" id="biz-cancel-shipment"><?php _e("Request shipment cancellation", "wc-biz-courier-logistics") ?></button>
            <?php } ?>

        </li>
        <?php
        if (isset($_GET['biz_error'])) {
            if ($_GET['biz_error'] == 'voucher-error') {
        ?>
                <p class="biz-send-shipment error"><?php _e("This voucher number doesn't exist.", 'wc-biz-courier-logistics') ?></p>
            <?php
            }
        }

        if (!empty(end($status_history)['last_mile_tracking_number'])) {
            ?>
            <li class="biz-shipment-partner">
                <?php
                echo '<div class="title">' . __('Partner tracking number:', 'wc-biz-courier-logistics') . '</div>';
                echo '<div class="tracking-number">' . end($status_history)['last_mile_tracking_number'] . '</div>';
                ?>
            </li>
        <?php } ?>
        <li>
            <?php

            _e("Status history: ", 'wc-biz-courier-logistics');
            ?>
        </li>
        <li>
            <?php
            foreach (array_reverse($status_history) as $status) {
                echo '<ul class="biz-shipment-status ' . $status['outlook'] . '">';
                echo '<li class="status-level">' . __($status['level'], 'wc-biz-courier-logistics') . '</li>';
                echo '<li class="status-description">' . $status['description'] . '</li>';
                echo '<li class="status-comments">' . ((!empty($status['comments'])) ? $status['comments'] : __('No other comments.', 'wc-biz-courier-logistics')) . '</li>';
                if (!empty($status['actions'])) {
                    echo '<ul class="actions">';
                    echo '<div class="title">' . __('Actions:', 'wc-biz-courier-logistics') . '</div>';
                    foreach ($status['actions'] as $action) {
                        echo '<hr/><li class="action-description">' . $action['description'] . '</li>';
                        echo '<li class="action-date">' . $action['date'] . ' ' . __('at', 'wc-biz-courier-logistics') . ' ' . $action['time'] . '</li>';
                    }
                    echo '</ul>';
                }
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
    ?>
        <p class="biz-shipment-status error"> <?php _e("The Biz Courier voucher is invalid, please contact support.", "wc-biz-courier-logistics") ?></p>
        <button class="button" id="biz-edit-shipment-voucher"><?php _e("Edit voucher", "wc-biz-courier-logistics") ?></button>
        <?php
    }
}

function biz_send_shipment_errors_html()
{
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
        if ($_GET['biz_error'] == 'voucher-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e("This voucher number doesn't exist.", 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'no-products-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e("There are no products in this order.", 'wc-biz-courier-logistics') ?></p>
        <?php
        }
        if ($_GET['biz_error'] == 'recipient-info-error' || $_GET['biz_error'] == 'biz-recipient-info-error') {
        ?>
            <p class="biz-send-shipment error"><?php _e("There was a problem with the recipient's information. Make sure you have filled in all the necessary fields:", 'wc-biz-courier-logistics') ?>
            <ul class="biz-send-shipment error">
                <li><?php _e("First name", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("Last name", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("Phone number", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("E-mail address", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("Address line #1", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("City", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("Postal code", 'wc-biz-courier-logistics') ?></li>
                <li><?php _e("Country", 'wc-biz-courier-logistics') ?></li>
            </ul>
            </p>

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
    <button id="biz-add-shipment-voucher" class="button">
        <?php _e("Add existing voucher number", "wc-biz-courier-logistics") ?>
    </button>
<?php
    biz_send_shipment_errors_html();
}

/**
 * Print errors in HTML for the shipment tracking meta box.
 *
 * @since    1.0.0
 * @param    string $error The associated error code.
 */
function biz_track_shipment_meta_box_cancelled_html($voucher = "")
{
    echo '<p class="biz-shipment-status">' . __("The Biz Courier shipment was cancelled.", "wc-biz-courier-logistics") . '</p>';
?>
    <ul>
        <li class="biz-voucher"><?php echo __("Voucher number: ", 'wc-biz-courier-logistics') . '<div>' . $voucher . '</div>' ?></li>
        <li class="biz-shipment-modification">
    </ul>

    <button id="biz-send-shipment" class="button save-order button-primary" />
    <?php _e("Resend shipment", "wc-biz-courier-logistics") ?>
    </button>
    <button id="biz-add-shipment-voucher" class="button">
        <?php _e("Add existing voucher number", "wc-biz-courier-logistics") ?>
    </button>
<?php
    biz_send_shipment_errors_html();
}
