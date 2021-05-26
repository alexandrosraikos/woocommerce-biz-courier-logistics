(function ($) {
  "use strict";
  $(document).ready(function () {
    $("button.woobiz-sync-stock").click(function (event) {
      event.preventDefault();
      $("button.woobiz-sync-stock").prop("disabled", true);
      $("button.woobiz-sync-stock").addClass("woobiz-loading");
      $.ajax({
        url: ajax_prop.ajax_url,
        type: "post",
        data: {
          action: "biz_stock_sync",
          nonce: ajax_prop.nonce,
          product_skus: ajax_prop.product_skus,
        },
        success: function (response) {
          window.location.reload();
        },
        dataType: "json",
      });
    });
  });
})(jQuery);
