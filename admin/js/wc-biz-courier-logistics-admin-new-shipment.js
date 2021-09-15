(function ($) {
  "use strict";

  function showError(error) {
    $("#wc-biz-courier-logistics-metabox").prepend(
      '<li class="biz-error">' + error.error_description + "</li>"
    );
  }

  $(document).ready(function () {
    // Send shipment button.
    $("#biz-send-shipment").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("#biz-send-shipment").prop("disabled", true);
      $("#biz-send-shipment").addClass("biz-loading");

      // Perform AJAX request.
      $.ajax({
        url: ajax_prop.ajax_url,
        type: "post",
        data: {
          action: "biz_send_shipment",
          nonce: ajax_prop.send_shipment_nonce,
          order_id: ajax_prop.order_id,
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
    });

    // Add shipment voucher button.
    $("#biz-add-shipment-voucher").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("#biz-add-shipment-voucher").prop("disabled", true);
      $("#biz-add-shipment-voucher").addClass("biz-loading");

      var message = prompt(ajax_prop.add_voucher_message);
      if (message != null) {
        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_add_shipment_voucher",
            nonce: ajax_prop.add_shipment_voucher_nonce,
            order_id: ajax_prop.order_id,
            message: message,
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
        $("#biz-add-shipment-voucher").prop("disabled", false);
        $("#biz-add-shipment-voucher").removeClass("biz-loading");
      }
    });
  });
})(jQuery);
