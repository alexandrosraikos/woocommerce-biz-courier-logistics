(function ($) {
  "use strict";

  $("#biz-delete-shipment-voucher").click(function (event) {
    // Prevent default reload.
    event.preventDefault();

    if (confirm(ajax_prop_three.delete_confirmation)) {
      // Disable button.
      $("#biz-delete-shipment-voucher").prop("disabled", true);
      $("#biz-delete-shipment-voucher").addClass("biz-loading");

      // Perform AJAX request.
      $.ajax({
        url: ajax_prop_three.ajax_url,
        type: "post",
        data: {
          action: "biz_delete_shipment_voucher",
          nonce: ajax_prop_three.nonce,
          order_id: ajax_prop_three.order_id,
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
})(jQuery);
