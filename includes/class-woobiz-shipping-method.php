<?php

/**
 * The file that defines the shipping method class.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/alexandrosraikos/woobiz
 * @since      1.0.0
 *
 * @package    WooBiz
 * @subpackage WooBiz/includes
 */

/**
 * The shipping method class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WooBiz
 * @subpackage WooBiz/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */

class Biz_Shipping_Method extends WC_Shipping_Method
{
    public function __construct()
    {
        $this->id = 'biz_shipping_method';
        $this->method_title = __('Biz Courier Shipping', 'woobiz');
        $this->method_description = __('Send with Biz Courier Shipping.', 'woobiz');
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Biz Courier Shipping', 'woobiz');
        $this->init();
    }

    function init()
    {
        $this->init_form_fields();
        $this->init_settings();
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'woobiz'),
                'type' => 'checkbox',
                'description' => __('Allow shipping with Biz Courier.', 'woobiz'),
                'default' => 'yes'
            ),
            'biz_cash_on_delivery_fee'  => array(
                'title' => __('COD fee', 'woobiz'),
                'description' => __('Insert the additional Biz fee for Cash On Delivery payments.','woobiz'),
                'type' => 'price',
            ),
            'biz_warehouses_title' => array(
                'title' => __('Shipping Warehouses', 'woobiz'),
                'type' => 'title',
            ),
            'biz_warehouse_location' => array(
                'title' => __("Warehouse Location", "woobiz"),
                'type' => 'select',
                'description' => __("Select the country of the active Biz warehouse currently connected to this store.", 'woobiz'),
                'options' => array(
                    'GR' => __('Greece', 'woobiz')
                ),
                'biz_default' => 'GR'
            ),
            'biz_destination_pricing_title' => array(
                'title' => __('Destination Pricing', 'woobiz'),
                'type' => 'title',
                'description' => __('Insert the agreed upon prices for Biz Courier Shipping.', 'woobiz'),
            ),
            'biz_same_region_title' => array(
                'title' => __('Same Region', 'woobiz'),
                'type' => 'title',
                'description' => __('Insert the price for destinations within the warehouse region.', 'woobiz'),
            ),
            'biz_same_region_pricing'  => array(
                'title' => __('Delivery pricing', 'woobiz'),
                'type' => 'price',
            ),
            'biz_same_region_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'type' => 'price',
            ),
            'biz_land_destinations_title' => array(
                'title' => __('Land Destinations', 'woobiz'),
                'type' => 'title',
                'description' => __('Insert the price for domestic land destinations.', 'woobiz'),
            ),
            'biz_land_destinations_pricing'  => array(
                'title' => __('Delivery pricing', 'woobiz'),
                'type' => 'price',
            ),
            'biz_land_destinations_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'type' => 'price',
            ),
            'biz_island_destinations_title' => array(
                'title' => __('Island Destinations', 'woobiz'),
                'description' => __('Insert the price for domestic island destinations.', 'woobiz'),
                'type' => 'title',
            ),
            'biz_island_destinations_pricing'  => array(
                'title' => __('Delivery pricing', 'woobiz'),
                'type' => 'price',
            ),
            'biz_island_destinations_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'type' => 'price',
            ),
            'biz_inaccessible_areas_title' => array(
                'title' => __('Inaccessible Areas', 'woobiz'),
                'type' => 'title',
                'description' => __('Insert the price for domestic inaccessible destinations.', 'woobiz'),
            ),
            'biz_inaccessible_areas_pricing'  => array(
                'title' => __('Delivery pricing', 'woobiz'),
                'type' => 'price',
            ),
            'biz_inaccessible_areas_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_title' => array(
                'title' => __('Same Day Delivery', 'woobiz'),
                'type' => 'title',
                'description' => __('Insert the pricing for same day delivery.', 'woobiz'),
            ),
            'biz_same_day_delivery_enabled' => array(
                'title' => __('Enable', 'woobiz'),
                'type' => 'checkbox',
                'description' => __('Allow same region customers to opt for same day delivery.', 'woobiz'),
                'default' => 'yes'
            ),
            'biz_same_day_delivery_pricing'  => array(
                'title' => __('Charge per km', 'woobiz'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_minimum_charge'  => array(
                'title' => __('Minimum charge', 'woobiz'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'type' => 'price',
            ),
            'biz_additional_services_title' => array(
                'title' => __('Additional Services', 'woobiz'),
                'type' => 'title',
                'description' => __('Adjust options for additional services provided by Biz Courier.', 'woobiz'),
            ),
            'biz_morning_delivery_fee'  => array(
                'title' => __('Morning delivery fee', 'woobiz'),
                'description' => __('Insert the additional fee amount for morning deliveries.','woobiz'),
                'type' => 'price',
            ),
            'biz_saturday_delivery_fee'  => array(
                'title' => __('Saturday delivery fee', 'woobiz'),
                'description' => __('Insert the additional fee amount for deliveries on Saturdays.','woobiz'),
                'type' => 'price',
            ),
        );
    }
    
    public function calculate_shipping($package = array())
    {

    }
}
