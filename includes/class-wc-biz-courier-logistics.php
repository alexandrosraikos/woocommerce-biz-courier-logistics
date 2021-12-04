<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WC_Biz_Courier_Logistics
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WC_Biz_Courier_Logistics_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $WC_Biz_Courier_Logistics    The string used to uniquely identify this plugin.
     */
    protected $WC_Biz_Courier_Logistics;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        $this->version = '1.4.0';
        $this->WC_Biz_Courier_Logistics = 'wc-biz-courier-logistics';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WC_Biz_Courier_Logistics_Loader. Orchestrates the hooks of the plugin.
     * - WC_Biz_Courier_Logistics_i18n. Defines internationalization functionality.
     * - WC_Biz_Courier_Logistics_Admin. Defines all hooks for the admin area.
     * - WC_Biz_Courier_Logistics_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        define('BIZ_INTEGRATION_SETTINGS_URI', admin_url('admin.php?page=wc-settings&tab=integration&section=biz_integration'));
        define('BIZ_INTERNAL_ERROR_KEY', 'biz_internal_error');
        define('BIZ_INTERNAL_POST_ERROR_KEY', '_biz_internal_error');


        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wc-biz-courier-logistics-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wc-biz-courier-logistics-public.php';

        /**
         * The class responsible for custom exceptions.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wc-biz-courier-logistics-exceptions.php';

        $this->loader = new WC_Biz_Courier_Logistics_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the WC_Biz_Courier_Logistics_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {
        $plugin_i18n = new WC_Biz_Courier_Logistics_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {
        // TODO @alexandrosraikos: Test all use cases (#37).
        // TODO @alexandrosraikos: Finalize code docs (#38) - [Product Manager & Delegate, Displays and Public remaining].

        /**
         * ----------------
         * Plugin administration
         * ----------------
         */

        $plugin_admin = new WCBizCourierLogisticsAdmin($this->get_WC_Biz_Courier_Logistics(), $this->get_version());

        /** Interface hooks */
        $this->loader->add_action('init', $plugin_admin, 'checkMinimumRequirements');
        $this->loader->add_filter('admin_notices', $plugin_admin, 'internalErrorNotice');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueueStyles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueueScripts');
        $this->loader->add_filter(
            'plugin_action_links_wc-biz-courier-logistics/wc-biz-courier-logistics.php',
            $plugin_admin,
            'pluginActionLinks'
        );
        $this->loader->add_filter('plugin_row_meta', $plugin_admin, 'pluginRowMeta', 10, 2);

        /**
         * Integration
         * ----------------
         */

        /** Extensions */
        $this->loader->add_action('woocommerce_integrations_init', $plugin_admin, 'bizIntegration');
        $this->loader->add_filter('woocommerce_integrations', $plugin_admin, 'addBizIntegration');

        /** Interface hooks */
        $this->loader->add_filter('admin_notices', $plugin_admin, 'bizSettingsNotice');


        /**
         * Shipping method
         * ----------------
         */

        /** Extensions */
        $this->loader->add_action(
            'woocommerce_shipping_init',
            $plugin_admin,
            'bizShippingMethod'
        );
        $this->loader->add_filter(
            'woocommerce_shipping_methods',
            $plugin_admin,
            'addBizShippingMethod'
        );

        $this->defineAdminProductHooks();
        $this->defineAdminShipmentHooks();
    }

    private function defineAdminProductHooks()
    {
        
        /**
         * ----------------
         * Product Management
         * ----------------
         */

        $product_manager = new WCBizCourierLogisticsProductManager();

        /** Interface hooks */

        $this->loader->add_action(
            'manage_posts_extra_tablenav',
            $product_manager,
            'addSynchronizeAllButton',
            20,
            1
        );

        $this->loader->add_filter(
            'manage_edit-product_columns',
            $product_manager,
            'addDelegateStatusColumn'
        );

        $this->loader->add_action(
            'manage_product_posts_custom_column',
            $product_manager,
            'delegateStatusColumnIndicator',
            10,
            2
        );

        $this->loader->add_action(
            'add_meta_boxes',
            $product_manager,
            'addProductManagementMetabox'
        );

        /** Handler hooks */

        $this->loader->add_action(
            'woocommerce_process_product_meta',
            $product_manager,
            'handleProductSKUChange',
            10,
            1
        );

        $this->loader->add_action(
            'woocommerce_save_product_variation',
            $product_manager,
            'handleVariationSKUChange',
            10,
            1
        );

        /** AJAX handler hooks */

        $this->loader->add_action(
            'wp_ajax_product_stock_synchronization_all',
            $product_manager,
            'handleAllStockSynchronization'
        );

        $this->loader->add_action(
            'wp_ajax_product_permit',
            $product_manager,
            'handleProductDelegatePermission'
        );

        $this->loader->add_action(
            'wp_ajax_product_prohibit',
            $product_manager,
            'handleProductDelegateProhibition'
        );

        $this->loader->add_action(
            'wp_ajax_product_synchronize',
            $product_manager,
            'handleProductSynchronization'
        );
    }

    private function defineAdminShipmentHooks()
    {

        /**
         * ----------------
         * Shipment Management
         * ----------------
         */

        $shipment_manager = new WCBizCourierLogisticsShipmentManager();

        /** Interface hooks */

        $this->loader->add_action(
            'add_meta_boxes',
            $shipment_manager,
            'addShipmentManagementMetabox'
        );

        $this->loader->add_filter(
            'manage_edit-shop_order_columns',
            $shipment_manager,
            'addShipmentVoucherColumn'
        );

        $this->loader->add_action(
            'manage_shop_order_posts_custom_column',
            $shipment_manager,
            'shipmentVoucherColumn',
            10,
            2
        );

        /** Handler hooks */

        $this->loader->add_action(
            'woocommerce_order_status_changed',
            $shipment_manager,
            'handleOrderStatusChange',
            10,
            3
        );

        /** AJAX handler hooks */

        $this->loader->add_action(
            'wp_ajax_biz_shipment_send',
            $shipment_manager,
            'handleCreation'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_modification_request',
            $shipment_manager,
            'handleModificationRequest'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_cancellation_request',
            $shipment_manager,
            'handleCancellationRequest'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_add_voucher',
            $shipment_manager,
            'handleVoucherAddition'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_edit_voucher',
            $shipment_manager,
            'handleVoucherEditing'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_delete_voucher',
            $shipment_manager,
            'handleVoucherDeletion'
        );

        $this->loader->add_action(
            'wp_ajax_biz_shipment_synchronize_order',
            $shipment_manager,
            'handleOrderStatusSynchronization'
        );

        /** Cron hooks */

        add_filter(
            'cron_schedules',
            function ($schedules) {
                $schedules['ten_minutes'] = array(
                    'interval' => 300,
                    'display' => 'Every 5 minutes.'
                );
                return $schedules;
            }
        );
        $this->loader->add_action('shipment_status_cron_handler_hook', $shipment_manager, 'scoutShipmentStatus');
        if (!wp_next_scheduled('shipment_status_cron_handler_hook')) {
            wp_schedule_event(time(), 'ten_minutes', 'shipment_status_cron_handler_hook');
        }

        /** Utility hooks */

        $this->loader->add_filter(
            'woocommerce_order_data_store_cpt_get_orders_query',
            $shipment_manager,
            'extendVoucherCustomQuery',
            10,
            2
        );
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new WC_Biz_Courier_Logistics_Public(
            $this->get_WC_Biz_Courier_Logistics(),
            $this->get_version()
        );

        /**
         * General
         * ----------------
         */

        /** Interface hooks */
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        /**
         * Shipping Method
         * ----------------
         */

        /** Interface hooks */
        $this->loader->add_action('woocommerce_cart_calculate_fees', $plugin_public, 'add_biz_cod_fee', 10, 1);
        $this->loader->add_action('wp_footer', $plugin_public, 'biz_checkout_refresh');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_WC_Biz_Courier_Logistics()
    {
        return $this->WC_Biz_Courier_Logistics;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    WC_Biz_Courier_Logistics_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
