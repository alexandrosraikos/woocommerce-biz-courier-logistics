(function ($) {
  "use strict";

  // Ensure prepared document.
  $(document).ready(function () {
    $("#biz-edit-shipment-voucher").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("#biz-edit-shipment-voucher").prop("disabled", true);
      $("#biz-edit-shipment-voucher").addClass("biz-loading");

      var message = prompt(ajax_prop_two.edit_voucher_message);
      if (message != null) {
        // Perform AJAX request.
        $.ajax({
          url: ajax_prop_two.ajax_url,
          type: "post",
          data: {
            action: "biz_edit_shipment_voucher",
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
        $("#biz-edit-shipment-voucher").prop("disabled", false);
        $("#biz-edit-shipment-voucher").removeClass("biz-loading");
      }
    });
  });
})(jQuery);
