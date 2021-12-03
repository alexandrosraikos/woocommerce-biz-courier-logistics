<?php

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