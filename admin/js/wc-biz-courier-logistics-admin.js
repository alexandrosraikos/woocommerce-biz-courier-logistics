/**
 * @file Provides global functions for later enqueued scripts.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.0.0
 */

/** @var $ The jQuery entrypoint. */
var $ = jQuery;

/**
 * Global
 *
 * This section includes global functionality.
 */

/**
 *
 * Display an alert container relative to the referenced element.
 *
 * @param {string} selector The DOM selector of the element that will be alerted about.
 * @param {string} message The message that will be displayed in the alert.
 * @param {string?} type The type of alert (either `'failure'`, `'warning'` or left `null`).
 * @param {Boolean} placeBefore Whether the alert is placed before the selected element.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function showAlert(selector, message, type = null, placeBefore = false) {
  console.log($(selector));
  if (placeBefore) {
    $(selector).before(
      '<div class="wc-biz-courier-logistics-notice ' +
        (type ?? "") +
        '">' +
        message +
        "</div>"
    );
  } else {
    $(selector).after(
      '<div class="wc-biz-courier-logistics-notice ' +
        (type ?? "") +
        '">' +
        message +
        "</div>"
    );
  }
}

/**
 * Make a WP request.
 *
 * This function handles success data using the `completion` and appends errors automatically.
 *
 * @param {string} actionDOMSelector The selector of the DOM element triggering the action.
 * @param {string} action The action as registered in {@link ../../class-wc-biz-courier-logistics.php}
 * @param {string} nonce The single nonce appointed to the action.
 * @param {Object} data The array of data to be included in the request.
 * @param {Function} completion The actions to perform when the response was successful.
 *
 * @author Alexandros Raikos <alexandros@araikos.gr>
 * @since 1.4.0
 */
function makeWPRequest(actionDOMSelector, action, nonce, data, completion) {
  // Add the loading class.
  $(actionDOMSelector).addClass("loading");
  if (actionDOMSelector.includes("button")) {
    $(actionDOMSelector).prop("disabled", true);
  }

  // Prepare data fields for WordPress.
  data.action = action;
  data.nonce = nonce;

  // Perform AJAX request.
  $.ajax({
    url: GlobalProperties.ajaxEndpointURL,
    type: "post",
    data: data,
    dataType: "json",
    complete: (response) => {
      if (response.status === 200) {
        try {
          // Parse the data.
          var object = JSON.parse(
            response.responseText == ""
              ? '{"message":"completed"}'
              : response.responseText
          );

          // Execution completion callback.
          if (object.message === "completed") completion(object);
          else completion();

          // Remove the loading class.
          $(actionDOMSelector).removeClass("loading");
          if (actionDOMSelector.includes("button")) {
            $(actionDOMSelector).prop("disabled", false);
          }
        } catch (objError) {
          console.error("Invalid JSON response: " + objError);
        }
      } else if (response.status === 400 || response.status === 500) {
        showAlert(actionDOMSelector, response.responseText, "failure");

        // Remove the loading class.
        $(actionDOMSelector).removeClass("loading");
        if (actionDOMSelector.includes("button")) {
          $(actionDOMSelector).prop("disabled", false);
        }
      } else {
        showAlert(
          actionDOMSelector,
          "There was an unknown connection error to the Biz API. Please try again later.",
          "failure"
        );

        // Remove the loading class.
        $(actionDOMSelector).removeClass("loading");
        if (actionDOMSelector.includes("button")) {
          $(actionDOMSelector).prop("disabled", false);
        }
      }
    },
    dataType: "json",
  });
}
