/**
 * @file Provides functions for existing shipment data.
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
   * Request shipment modification using a message prompt.
   *
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   */
  function requestShipmentModification(e) {
    e.preventDefault();

    const message = prompt(ShipmentProperties.MODIFICATION_REQUEST_PROMPT);
    if (message != null && message != "") {
      makeWPRequest(
        "#biz-modify-shipment",
        "biz_shipment_modification_request",
        ShipmentProperties.bizShipmentModificationRequestNonce,
        {
          order_id: ShipmentProperties.orderID,
          shipment_modification_message: message,
        },
        () => window.location.reload()
      );
    }
  }

  /**
   * Request shipment cancellation using a confirmation prompt.
   *
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   */
  function requestShipmentCancellation(e) {
    e.preventDefault();

    if (confirm(ShipmentProperties.CANCELLATION_REQUEST_CONFIRMATION)) {
      makeWPRequest(
        "#biz-cancel-shipment",
        "biz_shipment_cancellation_request",
        ShipmentProperties.bizShipmentCancellationRequestNonce,
        {
          order_id: ShipmentProperties.orderID,
        },
        () => window.location.reload()
      );
    }
  }

  /**
   * Edit the shipment's voucher using a browser prompt.
   *
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   */
  function editShipmentVoucher(e) {
    e.preventDefault();

    const editedVoucher = prompt(ShipmentProperties.EDIT_VOUCHER_PROMPT);
    if (editedVoucher != null && editedVoucher != "") {
      makeWPRequest(
        "#biz-edit-shipment-voucher",
        "biz_shipment_edit_voucher",
        ShipmentProperties.bizShipmentEditVoucherNonce,
        {
          order_id: ShipmentProperties.orderID,
          voucher: editedVoucher,
        },
        () => window.location.reload()
      );
    }
  }

  /**
   * Delete the shipment's voucher using a confirmation prompt.
   *
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   */
  function deleteShipmentVoucher(e) {
    e.preventDefault();

    if (confirm(ShipmentProperties.DELETE_VOUCHER_CONFIRMATION)) {
      makeWPRequest(
        "#biz-delete-shipment-voucher",
        "biz_shipment_delete_voucher",
        ShipmentProperties.bizShipmentDeleteVoucherNonce,
        {
          order_id: ShipmentProperties.orderID,
        },
        () => window.location.reload()
      );
    }
  }

  /**
   * Manually synchronize order status based on shipment status.
   *
   * @param {Event} e The click event.
   *
   * @since 1.4.0
   */
  function synchronizeOrderStatus(e) {
    e.preventDefault();

    makeWPRequest(
      "#biz-synchronize-order",
      "biz_shipment_synchronize_order",
      ShipmentProperties.bizShipmentSynchronizeOrderNonce,
      {
        order_id: ShipmentProperties.orderID,
      },
      () => window.location.reload()
    );
  }

  // Ensure prepared document.
  $(document).ready(function () {
    /**
     *
     * Generic interface actions & event listeners.
     *
     */

    // Capture click event for modification.
    $("#biz-modify-shipment").click(requestShipmentModification);

    // Capture click event for cancellation.
    $("#biz-cancel-shipment").click(requestShipmentCancellation);

    // Capture click event for voucher editing.
    $("#biz-edit-shipment-voucher").click(editShipmentVoucher);

    // Capture click event for voucher deletion.
    $("#biz-delete-shipment-voucher").click(deleteShipmentVoucher);

    // Capture click event for order synchronization.
    $("#biz-synchronize-order").click(synchronizeOrderStatus);
  });
})(jQuery);
