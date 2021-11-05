/**
 * @file Provides functions for new shipment data.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  /**
   * Generic
   *
   * This section includes generic functionality
   * related to assigning new shipments.
   *
   */

  /**
   * Request a shipment submission based on the order.
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   * @author Alexandros Raikos <alexandros@araikos.gr>
   */
  function requestShipment(e) {
    e.preventDefault();
    if (window.confirm(ShipmentProperties.SEND_SHIPMENT_CONFIRMATION)) {
      makeWPRequest(
        "#biz-send-shipment",
        "biz_shipment_send",
        ShipmentProperties.bizShipmentSendNonce,
        {
          order_id: ShipmentProperties.orderID,
        },
        () => {
          window.location.reload();
        }
      );
    }
  }

  /**
   * Request the addition of a shipment voucher to the order's metadata.
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   * @author Alexandros Raikos <alexandros@araikos.gr>
   */
  function addVoucher(e) {
    e.preventDefault();
    const newVoucher = prompt(ShipmentProperties.ADD_VOUCHER_MESSAGE);
    if (voucher != null) {
      makeWPRequest(
        "#biz-add-shipment-voucher",
        "biz_shipment_add_voucher",
        ShipmentProperties.bizShipmentAddVoucherNonce,
        {
          order_id: ShipmentProperties.orderID,
          new_voucher: newVoucher,
        },
        () => {
          window.location.reload();
        }
      );
    }
  }

  $(document).ready(function () {
    /**
     *
     * Generic interface actions & event listeners.
     *
     */

    // Send shipment button.
    $("#biz-send-shipment").click(requestShipment);

    // Add shipment voucher button.
    $("#biz-add-shipment-voucher").click(addVoucher);
  });
})(jQuery);
