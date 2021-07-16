(function ($) {
  "use strict";

  // Ensure prepared document.
  $(document).ready(function () {
    // Capture click event for cancellation.
    $("#biz-cancel-shipment").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      if (confirm(ajax_prop.delete_confirmation)) {
        // Disable button.
        $("#biz-cancel-shipment").prop("disabled", true);
        $("#biz-cancel-shipment").addClass("biz-loading");

        // Perform AJAX request.
        $.ajax({
          url: ajax_prop.ajax_url,
          type: "post",
          data: {
            action: "biz_modify_shipment",
            nonce: ajax_prop.nonce,
            order_id: ajax_prop.order_id,
            shipment_modification_message: "cancel",
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText.includes("error")) {
              var url = new URL(window.location.href);
              var params = url.searchParams;
              params.set("biz_error", response.responseText);
              url.search = params.toString();
              window.location.href = url.toString();
            } else {
              window.location.reload();
            }
          },
          dataType: "json",
        });
      }
    });

    // Capture click event for cancellation.
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
            nonce: ajax_prop.nonce,
            order_id: ajax_prop.order_id,
            shipment_modification_message: message,
          },
          // Handle response.
          complete: function (response) {
            if (response.responseText.includes("error")) {
              var url = new URL(window.location.href);
              var params = url.searchParams;
              params.set("biz_error", response.responseText);
              url.search = params.toString();
              window.location.href = url.toString();
            } else {
              window.location.reload();
            }
          },
          dataType: "json",
        });
      }
    });
  });
})(jQuery);
