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

      // TODO @alexandrosraikos: Show shipment sending confirmation (#33 - https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/issues/33)

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
            try {
              showError(JSON.parse(response.responseText));
            } catch {
              showError(response.responseText);
            }
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

      var voucher = prompt(ajax_prop.add_voucher_message);
      if (voucher != null) {
        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_add_shipment_voucher",
            nonce: ajax_prop.add_shipment_voucher_nonce,
            order_id: ajax_prop.order_id,
            voucher: voucher,
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText === "OK") {
              window.location.reload();
            } else {
              showError(JSON.parse(response.responseText));
              // Re-enable button.
              $("#biz-add-shipment-voucher").prop("disabled", false);
              $("#biz-add-shipment-voucher").removeClass("biz-loading");
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
