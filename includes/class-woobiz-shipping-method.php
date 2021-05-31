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
    public function __construct($instance_id = 0)
    {
        $this->id = 'biz_shipping_method';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Biz Courier Shipping', 'woobiz');
        $this->method_description = __('Allow your customers to receive their products via Biz Courier Shipping.', 'woobiz');
        $this->supports = array('shipping_zones' => true, 'instance-settings' => false);
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Biz Courier Shipping', 'woobiz');
        $this->init();
    }

    function init()
    {
        $this->init_form_fields();
        $this->init_instance_settings();
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

            // 'biz_cash_on_delivery_fee'  => array(
            //     'title' => __('COD fee', 'woobiz'),
            //     'description' => __('Insert the additional Biz fee for Cash On Delivery payments.', 'woobiz'),
            //     'type' => 'price',
            // ),
            // 'biz_same_day_delivery_title' => array(
            //     'title' => __('Same Day Delivery', 'woobiz'),
            //     'type' => 'title',
            //     'description' => __('Insert the pricing for same day delivery.', 'woobiz'),
            // ),
            // 'biz_same_day_delivery_enabled' => array(
            //     'title' => __('Enable', 'woobiz'),
            //     'type' => 'checkbox',
            //     'description' => __('Allow same region customers to opt for same day delivery.', 'woobiz'),
            //     'default' => 'yes'
            // ),
            // 'biz_same_day_delivery_pricing'  => array(
            //     'title' => __('Charge per km', 'woobiz'),
            //     'type' => 'price',
            // ),
            // 'biz_same_day_delivery_minimum_charge'  => array(
            //     'title' => __('Minimum charge', 'woobiz'),
            //     'type' => 'price',
            // ),
            // 'biz_same_day_delivery_pricing_extra_kg'  => array(
            //     'title' => __('Charge per extra kg', 'woobiz'),
            //     'type' => 'price',
            // ),
            'biz_additional_services_title' => array(
                'title' => __('Additional Services', 'woobiz'),
                'type' => 'title',
                'description' => __('Adjust options for additional services provided by Biz Courier.', 'woobiz'),
            ),
            'biz_sms_notifications' => array(
                'title' => __('Enable', 'woobiz'),
                'type' => 'checkbox',
                'description' => __('Update your customers about their Biz shipment status via SMS.', 'woobiz'),
                'default' => 'no'
            ),
            'biz_morning_delivery_fee'  => array(
                'title' => __('Morning delivery fee', 'woobiz'),
                'description' => __('Insert the additional fee amount for morning deliveries.', 'woobiz'),
                'type' => 'price',
            ),
            'biz_saturday_delivery_fee'  => array(
                'title' => __('Saturday delivery fee', 'woobiz'),
                'description' => __('Insert the additional fee amount for deliveries on Saturdays.', 'woobiz'),
                'type' => 'price',
            ),
        );

        $this->instance_form_fields = array(
            'biz_zone_type' => array(
                'title' => __("Shipping zone type", "woobiz"),
                'type' => 'select',
                'description' => __("Select the type of Biz area covered by this shipping zone.", 'woobiz'),
                'options' => array(
                    'same-area' => __('Same area as warehouse', 'woobiz'),
                    'land-destinations' => __('Land destinations', 'woobiz'),
                    'island-destinations' => __('Island destinations', 'woobiz'),
                    'inaccessible-areas' => __('Inaccessible areas', 'woobiz'),
                ),
                'biz_default' => 'same-area'
            ),
            'biz_delivery_pricing'  => array(
                'title' => __('Delivery pricing', 'woobiz'),
                'description' => __('Insert the delivery fee amount for up to 2kg.', 'woobiz'),
                'type' => 'price',
            ),
            'biz_delivery_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'woobiz'),
                'description' => __('Insert the delivery fee amount for every extra kg.', 'woobiz'),
                'type' => 'price',
            ),
        );
    }

    public function calculate_shipping($package = array())
    {
        // Weight calculation
        $calculated_rate = $this->get_option('biz_delivery_pricing');
        $weight = 0;
        foreach ($package['contents'] as $item) {
            $product = $item['data'];
            $weight = $weight + $product->get_weight() * $item['quantity'];
        }
        $weight = wc_get_weight($weight, 'kg');
        if ($weight > 2) {
            $calculated_rate = $calculated_rate + ceil($weight - 2) * $this->get_option('biz_delivery_pricing_extra_kg');
        }


        // Simple rate calculation.
        $this->add_rate(array(
            'id' => $this->id,
            'label' => __('Simple delivery', 'woobiz'),
            'cost' => $calculated_rate
        ));

        // Extra services rates for the same area.
        if ($this->get_option('biz_zone_type') == 'same-area') {
            $this->add_rate(array(
                'id' => $this->id . '-morning-delivery',
                'label' => __('Morning delivery (until 10:30 a.m.)', 'woobiz'),
                'cost' => $calculated_rate + $this->get_option('biz_morning_delivery_fee')
            ));
            $this->add_rate(array(
                'id' => $this->id . '-saturday-delivery',
                'label' => __('Saturday delivery', 'woobiz'),
                'cost' => $calculated_rate + $this->get_option('biz_saturday_delivery_fee')
            ));
        }
    }
}
