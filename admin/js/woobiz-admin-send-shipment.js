(function ($) {
  "use strict";
  $(document).ready(function () {
    $("#woobiz-send-shipment").click(function (event) {
      event.preventDefault();
      $("#woobiz-send-shipment").prop("disabled", true);
      $("#woobiz-send-shipment").addClass("woobiz-loading");
      $.ajax({
        url: ajax_prop.ajax_url,
        type: "post",
        data: {
          action: "biz_send_shipment",
          nonce: ajax_prop.nonce,
          order_id: ajax_prop.order_id,
        },
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
