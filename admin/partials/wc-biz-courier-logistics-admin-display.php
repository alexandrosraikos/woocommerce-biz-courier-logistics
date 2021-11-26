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
 * @param string $message The message to be displayed.
 * @param string? $type The type of notice (`warning`, `failure` & `success` supported).
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function notice_display_embedded_html($message, $type = ""): void
{
?>
    <div class="wc-biz-courier-logistics-notice <?php echo $type ?>">
        <?php echo $message ?>
    </div>
<?php
}

/**
 * ------------
 * Product Management 
 * ------------
 * This section provides the necessary markup for 
 * managing products.
 * 
 */

/**
 * Print HTML button for stock synchronization.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */
function product_stock_synchronize_all_button_html()
{
?>
    <button class="button button-primary wc-biz-courier-logistics" data-action="synchronize-stock" style="height:32px;">
        <?php _e("Get stock levels", "wc-biz-courier-logistics") ?>
    </button>
<?php
}

/**
 * Print HTML column stock synchronization indicators.
 *
 * @param string $status The status code.
 * @param string $label The corresponding status label.
 * 
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 * 
 * @version 1.4.0
 */
function product_synchronization_status_indicator(string $status, string $label): string
{
    /** @var string $label The translated label. */
    $label = __($label, 'wc-biz-courier-logistics');

    return <<<INDICATOR
    <div class="wc-biz-courier-logistics">
        <div class="synchronization-indicator $status"> $label</div>
    </div>
    INDICATOR;
}

function product_management_html(array $status, string $sku, int $id, array $variations = null, bool $aggregated = false): void
{
?>
    <div id="wc-biz-courier-logistics-product-management" class="wc-biz-courier-logistics">
        <div class="status">
            <h4>
                <?php
                _e("Status", 'wc-biz-courier-logistics');
                ?>
            </h4>
            <?php echo product_synchronization_status_indicator($status[0], $status[1]) ?>
            <div class="sku"><?php echo $sku ?></div>
            <button data-action="synchronize" data-product-id="<?php echo $id ?>" class="button button-primary">
                <?php
                _e("Synchronize", 'wc-biz-courier-logistics');
                ?>
            </button>
            <button data-action="prohibit" data-product-id="<?php echo $id ?>">
                <?php
                _e("Disable", 'wc-biz-courier-logistics');
                ?>
            </button>
        </div>
        <?php
        if (!empty($variations)) {
        ?>
            <div class="variations">
                <h4>
                    <?php
                    _e("Variations", 'wc-biz-courier-logistics');
                    ?>
                </h4>
                <ul>
                    <?php
                    foreach ($variations as $variation) {
                        echo "<li class=\"" . ($variation['enabled'] ? 'enabled' : 'disabled') . "\">";
                        echo '<div class="title">' . $variation['product_title'] . " - <span class=\"attribute\">" . $variation['title'] . '</span></div>';
                        echo '<div class="sku">' . $variation['sku'] . '</div>';
                        if ($variation['enabled']) {
                            echo product_synchronization_status_indicator($variation['status'][0], $variation['status'][1]);
                        }
                    ?>
                        <button data-action="<?php echo ($variation['enabled'] ? 'prohibit' : 'permit') ?>" data-product-id="<?php echo $variation['id'] ?>">
                            <?php
                            if ($variation['enabled']) {
                                _e("Disable", "wc-biz-courier-logistics");
                            } else {
                                _e("Enable", "wc-biz-courier-logistics");
                            }
                            ?>
                        </button>
                        </li>
                    <?php
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        <?php
        }
        ?>
    </div>

    <?php
}

function product_management_disabled_html(string $sku, int $id, string $error = null)
{
    if (!empty($error)) {
        notice_display_embedded_html($error);
    } else {
    ?>
        <div id="wc-biz-courier-logistics-product-management" class="wc-biz-courier-logistics">
            <p>
                <?php
                _e(
                    "Start using Biz Courier & Logistics for WooCommerce features with this product.",
                    'wc-biz-courier-logistics'
                );
                ?>
            </p>
            <button data-action="permit" data-product-id="<?php echo $id ?>" class="button button-primary">
                <?php
                _e("Activate", 'wc-biz-courier-logistics');
                ?>
            </button>
        </div>
    <?php
    }
}



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
                        "Any checked items below will be submitted to Biz. Make sure all the items in the order enabled and found in the Biz Warehouse:",
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
                                <?php echo file_get_contents(plugin_dir_path(dirname(__FILE__)) . 'svg/' . (($item['compatible']) ? 'completed.svg' : 'failed.svg')) ?>
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
        } else notice_display_embedded_html(__("Unable to fetch status history for this shipment.", 'wc-biz-courier-logistics'), 'warning');
        ?>
    </div>
<?php
}
