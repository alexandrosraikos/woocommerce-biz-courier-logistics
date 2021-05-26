(function ($) {
  "use strict";
  $(document).ready(function () {
    $("#woobiz-send-shipment").click(function (event) {
      event.preventDefault();
      $.ajax({
        url: ajax_prop.ajax_url,
        type: "post",
        data: {
          action: "biz_send_shipment",
          nonce: ajax_prop.nonce,
          order_id: ajax_prop.order_id,
        },
        success: function (response) {
          // window.location.reload();
          alert(response);
        },
        dataType: "json",
      });
    });
  });
})(jQuery);
