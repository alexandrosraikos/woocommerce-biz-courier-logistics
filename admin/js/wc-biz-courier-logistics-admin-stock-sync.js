(function ($) {
  "use strict";

  // Ensure prepared document.
  $(document).ready(function () {
    // Capture click event.
    $("button.wc-biz-courier-logistics-sync-stock").click(function (event) {
      // Prevent default reload.
      event.preventDefault();

      // Disable button.
      $("button.wc-biz-courier-logistics-sync-stock").prop("disabled", true);
      $("button.wc-biz-courier-logistics-sync-stock").addClass(
        "wc-biz-courier-logistics-loading"
      );

      // Perform AJAX request.
      $.ajax({
        url: ajax_prop.ajax_url,
        type: "post",
        data: {
          action: "biz_stock_sync",
          nonce: ajax_prop.nonce,
        },
        // Handle response.
        complete: function (response) {
          if (
            response.responseText.includes("error") ||
            response.status !== 200
          ) {
            alert(response.responseText);
          } else {
            window.location.reload();
          }
        },
        dataType: "json",
      });
    });
  });
})(jQuery);
