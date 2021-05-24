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

function biz_order_meta_box_not_synchronized_html()
{
?>
    <p class="woobiz-order-indicator not-synchronized"><?php _e("This order has not shipped with Biz.", "woobiz") ?></p>
    <input type="submit" value="<?php _e("Send shipment", "woobiz") ?>" class="button save-order button-primary" /></input>
<?php
}

function biz_stock_sync_meta_box_html()
{
?>
    <p class="woobiz-order-indicator not-synchronized"><?php _e("This product's remaining stock is not yet synchronised with your Biz Courier warehouse.", "woobiz") ?></p>
    <button id="woobiz-sync-stock" class="button save-order button-primary" /><?php _e("Synchronize", "woobiz") ?></button>
<?php
}

function biz_stock_sync_meta_box_error_html()
{
?>
    <p class="woobiz-stock-sync-meta-box error"><?php _e("There was an error synchronizing warehouse data. Please make sure the product code(s) registered in this product and its variations match the ones in Biz. If the problem persists, please contact Biz Courier for further support.", "woobiz") ?></p>
    <button id="woobiz-sync-stock" class="button save-order button-primary" /><?php _e("Retry", "woobiz") ?></button>
<?php
}

function biz_stock_sync_meta_box_success_html()
{
?>
    <p class="woobiz-stock-sync-meta-box"><?php _e("The product is successfully connected to the warehouse.", "woobiz") ?> &#10003;</p>
    <button id="woobiz-sync-stock" class="button save-order button-primary"/><?php _e("Get remaining stock", "woobiz") ?></button>
<?php
}

function biz_stock_sync_all_button()
{
?>
    <button id="woobiz-sync-stock" class="button button-primary" style="height:32px;"><?php _e("Synchronize", "woobiz") ?></button>
<?php
}
