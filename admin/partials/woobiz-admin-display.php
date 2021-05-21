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
    <p class="woobiz-order-indicator not-synchronized"><?php _e("This order is not synchronized with Biz.", "woobiz") ?></p>
    <input type="submit" value="<?php _e("Synchronize","woobiz")?>" class="button save-order button-primary"/></input>
<?php
}
