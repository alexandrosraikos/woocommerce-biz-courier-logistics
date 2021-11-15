/**
 * @file Provides functions for stock synchronization.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */

/**
 * Generic
 *
 * This section includes generic functionality
 * related to stock synchronization.
 *
 */

/**
 * Request stock synchronization.
 * @param {Event} e The click event.
 *
 * @since 1.4.0
 * @author Alexandros Raikos <alexandros@araikos.gr>
 */
function synchronizeStock(e) {
  e.preventDefault();
  if (
    window.confirm(
      StockProperties.PRODUCT_STOCK_SYNCHRONIZATION_ALL_CONFIRMATION
    )
  ) {
    makeWPRequest(
      'button.wc-biz-courier-logistics[data-action="synchronize-stock"]',
      "product_stock_synchronization_all",
      StockProperties.bizStockSynchronizationNonce,
      {},
      () => {
        window.location.reload();
      }
    );
  }
}

(function ($) {
  "use strict";

  /**
   *
   * Generic interface actions & event listeners.
   *
   */

  // Ensure prepared document.
  $(document).ready(function () {
    // Capture click event.
    $('button.wc-biz-courier-logistics[data-action="synchronize-stock"]').click(
      synchronizeStock
    );
  });
})(jQuery);
