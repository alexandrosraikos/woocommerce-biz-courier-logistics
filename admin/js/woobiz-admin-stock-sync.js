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
