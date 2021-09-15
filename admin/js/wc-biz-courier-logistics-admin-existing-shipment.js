(function ($) {
  "use strict";

  function showError(error) {
    $("#wc-biz-courier-logistics-metabox").prepend(
      '<li class="biz-error">' + error.error_description + "</li>"
    );
  }

  // Ensure prepared document.
  $(document).ready(function () {
    // Capture click event for modification.
    $("#biz-modify-shipment").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      var message = prompt(ajax_prop.modification_message);
      if (message != null && message != "") {
        // Disable button.
        $("#biz-modify-shipment").prop("disabled", true);
        $("#biz-modify-shipment").addClass("biz-loading");

        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_modify_shipment",
            modify_shipment_nonce: ajax_prop.nonce,
            order_id: ajax_prop.order_id,
            shipment_modification_message: message,
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText === "OK") {
              window.location.reload();
            } else {
              showError(JSON.parse(response.responseText));
            }
          },
          dataType: "json",
        });
      }
    });

    // Capture click event for cancellation.
    $("#biz-cancel-shipment").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      if (confirm(ajax_prop.cancellation_request_confirmation)) {
        // Disable button.
        $("#biz-cancel-shipment").prop("disabled", true);
        $("#biz-cancel-shipment").addClass("biz-loading");

        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_modify_shipment",
            nonce: ajax_prop.modify_shipment_nonce,
            order_id: ajax_prop.order_id,
            shipment_modification_message: "cancel",
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText === "OK") {
              window.location.reload();
            } else {
              showError(JSON.parse(response.responseText));
            }
          },
          dataType: "json",
        });
      } else {
        // Re-enable button.
        $("#biz-cancel-shipment").prop("disabled", false);
        $("#biz-cancel-shipment").removeClass("biz-loading");
      }
    });

    // Capture click event for voucher editing.
    $("#biz-edit-shipment-voucher").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("#biz-edit-shipment-voucher").prop("disabled", true);
      $("#biz-edit-shipment-voucher").addClass("biz-loading");

      var newVoucher = prompt(ajax_prop.edit_voucher_message);
      if (newVoucher != null) {
        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_edit_shipment_voucher",
            nonce: ajax_prop.edit_shipment_voucher_nonce,
            order_id: ajax_prop.order_id,
            voucher: newVoucher,
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText === "OK") {
              window.location.reload();
            } else {
              showError(JSON.parse(response.responseText));

              // Re-enable button.
              $("#biz-edit-shipment-voucher").prop("disabled", false);
              $("#biz-edit-shipment-voucher").removeClass("biz-loading");
            }
          },
          dataType: "json",
        });
      } else {
        // Re-enable button.
        $("#biz-edit-shipment-voucher").prop("disabled", false);
        $("#biz-edit-shipment-voucher").removeClass("biz-loading");
      }
    });

    // Capture click event for voucher deletion.
    $("#biz-delete-shipment-voucher").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      if (confirm(ajax_prop.delete_confirmation)) {
        // Disable button.
        $("#biz-delete-shipment-voucher").prop("disabled", true);
        $("#biz-delete-shipment-voucher").addClass("biz-loading");

        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_delete_shipment_voucher",
            nonce: ajax_prop.delete_shipment_voucher_nonce,
            order_id: ajax_prop.order_id,
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText === "OK") {
              window.location.reload();
            } else {
              showError(JSON.parse(response.responseText));

              // Re-enable button.
              $("#biz-delete-shipment-voucher").prop("disabled", false);
              $("#biz-delete-shipment-voucher").removeClass("biz-loading");
            }
          },
          dataType: "json",
        });
      }
    });
  });
})(jQuery);
