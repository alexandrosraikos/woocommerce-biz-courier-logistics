(function ($) {
  "use strict";
  $(document).ready(function () {
    $("#woobiz-sync-stock").click(function (event) {
      event.preventDefault();
      console.log("Sending sync signal...");
      $.ajax({
        type: "post",
        url: ajax_sync.ajax_url,
        data: {
          nonce: ajax_sync.nonce,
          sync_stock: true,
          product_skus: ajax_sync.product_skus,
        },
        dataType: "json",
        success: function (response) {
          if (!response.success) {
            alert(ajax_sync.error_msg);
          }
          location.reload();
        },
      });
    });
  });
})(jQuery);
