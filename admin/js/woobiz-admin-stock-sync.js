(function ($) {
  "use strict";
  $(document).ready(function () {
    $("#woobiz-sync-stock").click(function (event) {
      event.preventDefault();
      console.log("Sending sync signal...");
      $.ajax({
        type: "post",
        url: ajax_prop.ajax_url,
        data: {
          action: "biz_stock_sync",
          nonce: ajax_prop.nonce,
          product_skus: ajax_prop.product_skus,
        },
        dataType: "json",
        success: function (response) {
          if (!response.success) {
            alert("API Failure!");
          } else {
            location.reload();
          }
        },
      });
    });
  });
})(jQuery);
