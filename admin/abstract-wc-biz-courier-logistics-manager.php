<?php

abstract class WCBizCourierLogisticsManager
{
    protected function handleSynchronousRequest($completion, $post_id = null): void
    {
        try {
            // Run completion function.
            $completion();
        } catch (SoapFault $f) {
            // Display the error.
            
            notice_display_html(
                __(
                    "There was a connection issue when trying to contact the Biz Courier & Logistics API:",
                    'wc-biz-courier-logistics'
                )
                . " " . $f->getMessage(),
                'failure'
            );
        } catch (\Exception $e) {
            // Display the error.
            
            notice_display_html($e->getMessage(), 'failure');

            if (empty($post_id)) {
                // Register an internal error specific to the post ID.
                update_post_meta($post_id, '_biz_internal_error', $e->getMessage());
            } else {
                // Register a generic internal error.
                update_option('biz_internal_error', $e->getMessage());
            }
        }
    }

    protected function handleAJAXRequest($completion): void
    {
        $action = sanitize_key($_POST['action']);

        // Verify the action related nonce.
        if (!wp_verify_nonce($_POST['nonce'], $action)) {
            http_response_code(403);
            die("Unverified request for action: " . $action);
        }

        // Send shipment using POST data and handle errors.
        try {
            /** @var array $data The filtered $_POST data excluding WP specific keys. */
            $data = $completion(array_filter($_POST, function ($key) {
                return ($key != 'action' && $key != 'nonce');
            }, ARRAY_FILTER_USE_KEY));

            // Prepare the data and send.
            $data = json_encode($data);
            if ($data === false) {
                throw new WCBizCourierLogisticsRuntimeException("There was an error while encoding the data to JSON.");
            } else {
                http_response_code(200);
                die(json_encode($data));
            }
        } catch (WCBizCourierLogisticsProductDelegateNotAllowedException $e) {
            http_response_code(401);
            die($e->getMessage());
        } catch (WCBizCourierLogisticsRuntimeException $e) {
            http_response_code(400);
            die($e->getMessage());
        } catch (WCBizCourierLogisticsAPIError $e) {
            http_response_code(500);
            die($e->getMessage());
        } catch (SoapFault $f) {
            // Log the internal connection error.
            http_response_code(502);
            error_log(
                '[Biz Courier & Logistics for WooCommerce] SOAP client error when contacting Biz: '
                . $f->getMessage() . ' (action: ' . $action . ')'
            );
            die();
        }
    }
}
