<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines all the generic functionality related to plugin setup,
 * loading styles and scripts and setting up WooCommerce extensions.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WCBizCourierLogisticsAdmin
{
    /**
     * The ID of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $WC_Biz_Courier_Logistics The ID of this plugin.
     */
    private $WC_Biz_Courier_Logistics;

    /**
     * The version of this plugin.
     *
     * @since 1.0.0
     * @access private
     * @var string $version The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param string $WC_Biz_Courier_Logistics The column_name of this plugin.
     * @param string $version The version of this plugin.
     */
    public function __construct($WC_Biz_Courier_Logistics, $version)
    {
        $this->WC_Biz_Courier_Logistics = $WC_Biz_Courier_Logistics;
        $this->version = $version;

        // Require the main display module.
        require_once(plugin_dir_path(
            dirname(__FILE__)
        )
            . 'admin/partials/wc-biz-courier-logistics-admin-display.php'
        );
    }


    /**
     * Check the minimum technical requirements for the plugin's installation environment.
     *
     * This function checks for PHP version, required modules, and the WordPress &
     * WooCommerce installations for compatibility.
     *
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function checkMinimumRequirements(): void
    {
        // Define the default version requirement notice message.
        define(
            'BIZ_COURIER_MINIMUM_REQUIREMENT_NOTICE',
            __(
                'This version of %1$s is not supported by Biz Courier & Logistics for WooCommerce. Please update to %1$s %2$s or later.',
                'wc-biz-courier-logistics'
            )
        );

        /**
         * The minimum PHP version required.
         * @var string
         */
        $minimum_php_version = '7.4.0';

        if (version_compare(phpversion(), $minimum_php_version) < 0) {
            notice_display_html(
                sprintf(BIZ_COURIER_MINIMUM_REQUIREMENT_NOTICE, 'PHP', $minimum_php_version)
            );
        }

        if (!extension_loaded('soap')) {
            notice_display_html(
                __(
                    "You need to enable the soap extension in your PHP installation in order to use Biz Courier & Logistics features. Please contact your server administrator.",
                    "wc-biz-courier-logistics"
                )
            );
        }

        /**
         * The minimum WordPress version required.
         * @var string
         */
        $minimum_wp_version = '5.7.0';

        if (version_compare($GLOBALS['wp_version'], $minimum_wp_version) < 0) {
            notice_display_html(
                sprintf(BIZ_COURIER_MINIMUM_REQUIREMENT_NOTICE, 'WordPress', $minimum_wp_version)
            );
        }

        // Check for existing WooCommerce installation.
        if (!class_exists('WooCommerce')) {
            notice_display_html(
                __(
                    "Biz Courier & Logistics for WooCommerce requires the WooCommerce plugin to be installed and enabled.",
                    'wc-biz-courier-logistics'
                )
            );
        }

        // Check for WooCommerce version definition.
        if (defined('WC_VERSION')) {

            /**
             * The minimum WooCommerce version required.
             * @var string
             */
            $minimum_wc_version = '5.6.0';

            if (version_compare(constant('WC_VERSION'), $minimum_wc_version) < 0) {
                notice_display_html(
                    sprintf(BIZ_COURIER_MINIMUM_REQUIREMENT_NOTICE, 'WooCommerce', $minimum_wc_version)
                );
            }
        }
    }

    /**
     * Display a notice if there are any internal errors.
     *
     * This function searches for errors that have persisted in the database
     * either globally, via the `BIZ_INTERNAL_ERROR` key, or on a per-post
     * basis, via the `BIZ_INTERNAL_POST_ERROR` key.
     *
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function internalErrorNotice(): void
    {
        /**
         * The retrieved global internal error.
         * @var ?string
         */
        $internal_error = get_option(BIZ_INTERNAL_ERROR_KEY);

        // Display any global internal error and clear.
        if (!empty($internal_error)) {
            notice_display_html($internal_error);
            delete_option(BIZ_INTERNAL_ERROR_KEY);
        }

        if (!empty($_GET['post'])) {

            /**
             * The retrieved post-specific internal error.
             * @var ?string
             */
            $internal_error = get_post_meta($_GET['post'], BIZ_INTERNAL_POST_ERROR_KEY, true);

            // Display any post-specific internal error.
            if (!empty($internal_error)) {
                notice_display_html($internal_error, 'error');
                delete_post_meta($_GET['post'], BIZ_INTERNAL_POST_ERROR_KEY);
            }
        }
    }

    /**
     * Enqueue the plugin's main styles.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function enqueueStyles(): void
    {
        // Enqueue the main stylesheet.
        wp_enqueue_style(
            $this->WC_Biz_Courier_Logistics,
            plugin_dir_url(__FILE__) . 'css/wc-biz-courier-logistics-admin.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Enqueue the plugin's main scripts.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @return void
     * @version 1.4.0
     */
    public function enqueueScripts(): void
    {
        // Enqueue the main script.
        wp_enqueue_script(
            $this->WC_Biz_Courier_Logistics,
            plugin_dir_url(__FILE__) . 'js/wc-biz-courier-logistics-admin.js',
            array('jquery'),
            $this->version,
            false
        );

        // Localize the main script.
        wp_localize_script($this->WC_Biz_Courier_Logistics, "GlobalProperties", array(
            "ajaxEndpointURL" => admin_url('admin-ajax.php')
        ));
    }


    /**
     * Add a plugin action link to the plugin settings.
     *
     * @since 1.3.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param  array  $actions List of existing plugin action links.
     * @return array List of modified plugin action links.
     * @version 1.4.0
     */
    public function pluginActionLinks($actions): array
    {
        // Return an array expanded with the settings hyperlink.
        return array_merge(array(
            '<a href="' . esc_url(BIZ_INTEGRATION_SETTINGS_URI) . '">'
                . __('Settings', 'wc-biz-courier-logistics')
                . '</a>'
        ), $actions);
    }

    /**
     * Add plugin meta links.
     *
     * @since 1.3.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param  array $links List of existing plugin row meta links.
     * @return array List of modified plugin row meta links.
     * @version 1.4.0
     */
    public function pluginRowMeta($links, $file): array
    {
        if (strpos($file, 'wc-biz-courier-logistics.php')) {
            // Add GitHub Sponsors link.
            $links[] = ('<a href="https://github.com/sponsors/alexandrosraikos" target="blank">'
                . __('Donate via GitHub Sponsors', 'wc-biz-courier-logistics')
                . '</a>'
            );

            // Add documentation link.
            $links[] = ("<a href=\"https://github.com/alexandrosraikos/woocommerce-biz-courier-logistics/blob/main/README.md\" 
                target=\"blank\">"
                . __('Documentation', 'wc-biz-courier-logistics') . '</a>'
            );

            // Add support link.
            $links[] = ('<a href="https://www.araikos.gr/en/contact/" target="blank">'
                . __('Support', 'wc-biz-courier-logistics')
                . '</a>'
            );
        }
        return $links;
    }

    /**
     *  Integration
     *  ------------
     *  This section provides the necessary functionality for
     *  initialising the custom Biz integration.
     */

    /**
     * Declare the Biz_Integration class.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @version 1.4.0
     */
    public function bizIntegration(): void
    {
        // Include definition if class doesn't exist.
        if (!class_exists('Biz_Integration')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-integration.php';
        }
    }

    /**
     * Include Biz_Integration in WooCommerce Integrations.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $integrations The active list of WooCommerce Integrations.
     * @return array The expanded list of WooCommerce Integrations.
     * @version 1.4.0
     */
    public function addBizIntegration($integrations): array
    {
        // Add the Biz Integration class identifier.
        $integrations[] = 'Biz_Integration';
        return $integrations;
    }

    /**
     * Displays a WordPress notice depending on Biz credential and connection status.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @uses notice_display_html()
     */
    public function bizSettingsNotice()
    {

        /**
         * Whether the Biz Integration tab is active.
         * @var bool
         */
        $in_biz_tab = false;

        // Check if in the Biz Integration tab.
        if (isset($_GET['tab']) && isset($_GET['section'])) {
            $in_biz_tab = $_GET['section'] == 'biz_integration';
        }

        // Handle undesirable credentials state outside the Biz Integration tab.
        if (is_admin() && !$in_biz_tab) {

            /**
             * The saved Biz Integration settings.
             * @var array
             */
            $biz_settings = get_option('woocommerce_biz_integration_settings');

            if (
                $biz_settings['account_number'] == null ||
                $biz_settings['warehouse_crm'] == null ||
                $biz_settings['username'] == null ||
                $biz_settings['password'] == null
            ) {
                // Show setup completion notice.
                notice_display_html(
                    sprintf(
                        __(
                            "Please setup your Biz Courier credentials in <a href='%s'>WooCommerce Settings</a>.",
                            "wc-biz-courier-logistics"
                        ),
                        BIZ_INTEGRATION_SETTINGS_URI
                    ),
                    'warning'
                );
            }
        }
    }

    /**
     * Shipping Method
     * ------------
     * This section provides the necessary functionality for
     * initialising the custom Biz shipping method.
     *
     */

    /**
     * Declare the Biz_Shipping_Method class.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @version 1.4.0
     */
    public function bizShippingMethod(): void
    {
        // Import the Biz Shipping Method class.
        if (!class_exists('Biz_Shipping_Method')) {
            require_once(plugin_dir_path(dirname(__FILE__))
                . 'admin/class-wc-biz-courier-logistics-shipping-method.php'
            );
        }
    }

    /**
     * Declare Biz_Shipping_Method class.
     *
     * @since 1.0.0
     * @author Alexandros Raikos <alexandros@araikos.gr>
     * @param array $methods The active list of shipping methods.
     * @return array The expanded list of shipping methods.
     * @version 1.4.0
     */
    public function addBizShippingMethod($methods): array
    {
        // Append the new shipping method.
        $methods['biz_shipping_method'] = 'Biz_Shipping_Method';
        return $methods;
    }
}
