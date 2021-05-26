<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/woobiz
 * @since      1.0.0
 *
 * @package    WooBiz
 * @subpackage WooBiz/admin/partials
 */

 /**
  *  Biz Courier in WooCommerce Settings views:
  */

 function biz_settings_notice_missing_html() {
    ?>
    <div class="notice notice-warning woobiz-notice is-dismissible"><?php echo sprintf(__("Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.","woobiz"),admin_url('admin.php?page=wc-settings&tab=biz_settings_tab')) ?></div>
    <?php
 }

 function biz_settings_notice_invalid_html() {
    ?>
    <div class="notice notice-error woobiz-notice"><?php echo sprintf(__("Your Biz Courier credentials are invalid. Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.","woobiz"),admin_url('admin.php?page=wc-settings&tab=biz_settings_tab'))  ?></div>
    <?php
 }

 function biz_settings_notice_error_html() {
    ?>
    <div class="notice notice-error woobiz-notice"><?php _e("There was an error contacting Biz Courier.","woobiz")  ?></div>
    <?php
 }


 /**
  *  Biz Courier  stock synchronisation in products' views:
  */

function biz_stock_sync_all_button()
{
?>
    <button class="button button-primary woobiz-sync-stock" style="height:32px;"><?php _e("Get stock", "woobiz") ?></button>
<?php
}

function biz_stock_sync_column_html($status) {
    $label = __("Pending", "woobiz");
    switch ($status):
        case 'synced';
            $label = __("Found","woobiz");
            break;
        case 'not-synced';
            $label = __("Not found", "woobiz");
            break;
    endswitch;
    echo '<div class="biz_sync-indicator ' . $status . '">'.$label.'</div>';
}

/**
 *  Biz Courier shipments in orders' views:
 */

function biz_track_shipment_meta_box_html($voucher)
{
?>
   <p class="woobiz-order-indicator not-synchronized"><?php _e("The order has shipped with Biz.", "woobiz") ?></p>
   <ul>
       <li><?php _e("Voucher number: ").$voucher ?></li>
   </ul>
   <button id="woobiz-track-shipment" class="button save-order button-primary" /><?php _e("Track shipment", "woobiz") ?></button>
<?php
}

function biz_send_shipment_meta_box_html()
{
?>
   <p class="woobiz-order-indicator not-synchronized"><?php _e("This order has not shipped with Biz.", "woobiz") ?></p>
   <button id="woobiz-send-shipment" class="button save-order button-primary" /><?php _e("Send shipment", "woobiz") ?></button>
<?php
}