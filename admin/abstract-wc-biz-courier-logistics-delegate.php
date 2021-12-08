<?php

abstract class WCBizCourierLogisticsDelegate
{
    
    protected static function contactBizCourierAPI(
        string $wsdl_url,
        string $method,
        array $data,
        bool $authorized,
        ?callable $completion = null,
        ?callable $rejection = null,
        ?bool $no_crm = false
    ) {
        if ($authorized) {
            $biz_settings = get_option('woocommerce_biz_integration_settings');

            if (empty($biz_settings['account_number']) ||
                empty($biz_settings['warehouse_crm']) ||
                empty($biz_settings['username']) ||
                empty($biz_settings['password'])
            ) {
                throw new WCBizCourierLogisticsRuntimeException(
                    sprintf(
                        __(
                            "Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.",
                            "wc-biz-courier-logistics"
                        ),
                        BIZ_INTEGRATION_SETTINGS_URI
                    )
                );
            }

            $data = array_merge([
                'Code' => $biz_settings['account_number'],
                'CRM' => $biz_settings['warehouse_crm'],
                'User' => $biz_settings['username'],
                'Pass' => $biz_settings['password']
            ], $data);

            if ($no_crm) {
                array_splice($data, 1, 1);
            }
        }
        $client = new SoapClient($wsdl_url, [
            'trace' => 1,
            'exceptions' =>    true,
            'encoding' => 'UTF-8'
        ]);

        $response = $client->__soapCall($method, $data);
        $response = json_decode(json_encode($response), true);

        if (($response['Error'] ?? 0) == 0) {
            if ($completion != null) {
                $completion($response);
            } else {
                return $response;
            }
        } else {
            if ($rejection != null) {
                $rejection($response);
            } else {
                throw new WCBizCourierLogisticsAPIError($response['Error']);
            }
        }
    }

    protected static function ensureUTF8(string $string): string
    {
        return (mb_detect_encoding($string) == 'UTF-8') ? $string : utf8_encode($string);
    }

    protected static function truncateField(string $string, int $length = 40)
    {
        $string = self::ensureUTF8($string);
        return (mb_strlen($string, 'UTF-8') > $length) ? mb_substr($string, 0, $length - 1) . "." : $string;
    }
}
