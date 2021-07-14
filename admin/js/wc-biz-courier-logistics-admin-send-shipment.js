(function ($) {
  "use strict";

  // Ensure prepared document.
  $(document).ready(function () {
    // Capture click event.
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
          nonce: ajax_prop.nonce,
          order_id: ajax_prop.order_id,
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
    });
  });
})(jQuery);
