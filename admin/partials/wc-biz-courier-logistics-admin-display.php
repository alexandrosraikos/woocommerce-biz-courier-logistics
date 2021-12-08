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
 *  ------------
 *  Generic
 *  ------------
 *  This section provides generic markup.
 */

/**
 * Display a notice in native WP styling.
 *
 * @since 1.4.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $message The message to be displayed.
 * @param string $type The type of notice (currently only `warning` and `error` supported).
 * @return void
 */
function notice_display_html($message, $type = 'error'): void
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
 * @since 1.4.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $message The message to be displayed.
 * @param string? $type The type of notice (`warning`, `failure` & `success` supported).
 * @return void
 */
function notice_display_embedded_html($message, $type = ""): void
{
    ?>
    <div class="wc-biz-courier-logistics-notice <?php echo $type ?>">
        <?php echo $message ?>
    </div>
    <?php
}
