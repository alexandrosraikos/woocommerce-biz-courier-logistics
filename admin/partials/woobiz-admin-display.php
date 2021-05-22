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
    <input type="submit" value="<?php _e("Send shipment","woobiz")?>" class="button save-order button-primary"/></input>
<?php
}
