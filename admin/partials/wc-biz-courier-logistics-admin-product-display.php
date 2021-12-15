<?php

/**
 * The product-specific HTML templates of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin/partials
 */

/**
 * Print HTML button for stock synchronization.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @return void
 */
function synchronizeAllButtonHTML(): void
{
    ?>
    <button class="button button-primary wc-biz-courier-logistics" data-action="synchronize-stock" style="height:32px;">
        <?= __("Get stock levels", "wc-biz-courier-logistics") ?>
    </button>
    <?php
}

/**
 * Print HTML column stock synchronization indicators.
 *
 * @since 1.0.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param string $status The status code.
 * @param string $label The corresponding status label.
 * @version 1.4.0
 * @return string The HTML template for the status indicator.
 */
function delegateStatusIndicatorHTML(string $status, string $label): string
{
    /** @var string $label The translated label. */
    $label = __($label, 'wc-biz-courier-logistics');

    return <<<INDICATOR
    <div class="wc-biz-courier-logistics">
        <div class="synchronization-indicator $status"> $label</div>
    </div>
    INDICATOR;
}

function productVariationManagementHTML(array $variations = null)
{
    ?>

    <?php
    if (!empty($variations)) {
        ?>
        <div class="variations">
            <h4>
                <?= __("Variations", 'wc-biz-courier-logistics') ?>
            </h4>
            <ul>
                <?php
                foreach ($variations as $variation) {
                    ?>
                    <li class="<?= ($variation['enabled'] ? 'enabled' : 'disabled') ?>">
                        <div class="title">
                            <?= $variation['product_title'] ?> -
                            <span class="attribute">
                                <?= $variation['title'] ?>
                            </span>
                        </div>
                        <div class="sku">
                            <?= $variation['sku'] ?>
                        </div>
                        <?=
                        ($variation['enabled']) ?
                            delegateStatusIndicatorHTML(
                                $variation['status'][0],
                                $variation['status'][1]
                            )
                            : ''
                        ?>
                        <?php
                        if (!empty($variation['error'])) {
                            notice_display_embedded_html($variation['error']);
                        }
                        ?>
                        <button data-action="<?= ($variation['enabled'] ? 'prohibit' : 'permit') ?>" data-product-id="<?= $variation['id'] ?>">
                            <?=
                            ($variation['enabled']) ?
                                __("Disable", "wc-biz-courier-logistics") :
                                __("Enable", "wc-biz-courier-logistics")
                            ?>
                        </button>
                    </li>
                    <?php
                }
                ?>
            </ul>
        </div>
    <?php } ?>
    <?php
}

/**
 * Print the product management HTML.
 *
 * @since 1.4.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param array $status The formatted product status.
 * @param string $sku The product's SKU.
 * @param integer $id The product's ID.
 * @param array|null $variations The formatted variation information.
 * @return void
 */
function productManagementHTML(
    array $status,
    string $sku,
    int $id,
    array $variations = null
): void {
    ?>
    <div id="wc-biz-courier-logistics-product-management" class="wc-biz-courier-logistics">
        <div class="status">
            <h4>
                <?= __("Status", 'wc-biz-courier-logistics') ?>
            </h4>
            <?= delegateStatusIndicatorHTML($status[0], $status[1]) ?>
            <div class="sku"><?= $sku ?></div>
            <button data-action="synchronize" data-product-id="<?= $id ?>" class="button button-primary">
                <?= __("Synchronize", 'wc-biz-courier-logistics') ?>
            </button>
            <button data-action="prohibit" data-product-id="<?= $id ?>">
                <?= __("Disable", 'wc-biz-courier-logistics') ?>
            </button>
        </div>
        <?php productVariationManagementHTML($variations) ?>
    </div>
    <?php
}

/**
 * Print the disabled product management HTML.
 *
 * @since 1.4.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @param int $id The product's ID.
 * @param string $error Any errors to display.
 * @return void
 */
function productManagementDisabledHTML(int $id, array $variations = null, string $error = null)
{
    ?>
    <div id="wc-biz-courier-logistics-product-management" class="wc-biz-courier-logistics">
        <?php
        if (!empty($error)) {
            notice_display_embedded_html($error);
        } else {
            ?>
            <p>
                <?php
                _e(
                    "Start using Biz Courier & Logistics for WooCommerce features with this product.",
                    'wc-biz-courier-logistics'
                );
                ?>
            </p>
            <button data-action="permit" data-product-id="<?= $id ?>" class="button button-primary">
                <?php
                _e("Activate", 'wc-biz-courier-logistics');
                ?>
            </button>
            <?php
        }
        if (!empty($variations)) {
            ?>
            <button data-action="synchronize" data-product-id="<?= $id ?>" class="button button-primary">
                <?= __("Synchronize", 'wc-biz-courier-logistics') ?>
            </button>
            <?php
        }
        ?>
        <?php productVariationManagementHTML($variations) ?>
    </div>
    <?php
}
