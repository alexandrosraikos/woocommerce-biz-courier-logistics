(function ($) {
  "use strict";

  // Ensure prepared document.
  $(document).ready(function () {
    $("#biz-add-shipment-voucher").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("#biz-add-shipment-voucher").prop("disabled", true);
      $("#biz-add-shipment-voucher").addClass("biz-loading");

      var message = prompt(ajax_prop_two.add_voucher_message);
      if (message != null) {
        // Perform AJAX request.
        $.ajax({
          url: ajax_prop_two.ajax_url,
          type: "post",
          data: {
            action: "biz_add_shipment_voucher",
            nonce: ajax_prop_two.nonce,
            order_id: ajax_prop_two.order_id,
            voucher: message,
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
      } else {
        // Re-enable button.
        $("#biz-add-shipment-voucher").prop("disabled", false);
        $("#biz-add-shipment-voucher").removeClass("biz-loading");
      }
    });
  });
})(jQuery);
