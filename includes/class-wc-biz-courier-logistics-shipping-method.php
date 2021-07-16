<?php

/**
 * The file that defines the shipping method class.
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
 * The shipping method class.
 *
 * This class extends WC_Shipping_Method with necessary functional additions
 * for Biz Courier.
 *
 * @since      1.0.0
 * @package    Biz_Shipping_Method
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class Biz_Shipping_Method extends WC_Shipping_Method
{

	/**
	 * Initialize the class and set its properties
	 *
	 * @since    1.0.0
	 * @param 	 int $instance_id The given instance ID for this shipping method.
	 */
    public function __construct($instance_id = 0)
    {
        $this->id = 'biz_shipping_method';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Biz Courier Shipping', 'wc-biz-courier-logistics');
        $this->method_description = __('Allow your customers to receive their products via Biz Courier Shipping.', 'wc-biz-courier-logistics');
        $this->supports = array('shipping_zones' => true, 'instance-settings' => false);
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Biz Courier Shipping', 'wc-biz-courier-logistics');
        $this->init();
    }


	/**
	 * Initialise the WooCommerce Settings API integration.
	 *
	 * @since    1.0.0
	 */
    function init()
    {
        $this->init_form_fields();
        $this->init_instance_settings();
        $this->init_settings();
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }


	/**
	 * Initialize the WooCommerce Settings API form fields.
	 *
	 * @since    1.0.0
	 */
    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'description' => __('Allow shipping with Biz Courier.', 'wc-biz-courier-logistics'),
                'default' => 'yes'
            ),
            'biz_cash_on_delivery_fee'  => array(
                'title' => __('COD fee', 'wc-biz-courier-logistics'),
                'description' => __('Insert the additional Biz fee for Cash On Delivery payments.', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_title' => array(
                'title' => __('Same Day Delivery', 'wc-biz-courier-logistics'),
                'type' => 'title',
                'description' => __('Insert the pricing for same day delivery.', 'wc-biz-courier-logistics'),
            ),
            'biz_same_day_delivery_enabled' => array(
                'title' => __('Enable', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'description' => __('Allow same region customers to opt for same day delivery.', 'wc-biz-courier-logistics'),
                'default' => 'yes'
            ),
            'biz_same_day_delivery_pricing'  => array(
                'title' => __('Charge per km', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_minimum_charge'  => array(
                'title' => __('Minimum charge', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_same_day_delivery_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_additional_services_title' => array(
                'title' => __('Additional Services', 'wc-biz-courier-logistics'),
                'type' => 'title',
                'description' => __('Adjust options for additional services provided by Biz Courier.', 'wc-biz-courier-logistics'),
            ),
            'biz_sms_notifications' => array(
                'title' => __('SMS notifications', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'description' => __('Update your customers about their Biz shipment status via SMS.', 'wc-biz-courier-logistics'),
                'default' => 'no'
            ),
            'biz_morning_delivery'  => array(
                'title' => __('Morning delivery', 'wc-biz-courier-logistics'),
                'description' => __('Check the box if you would like the morning delivery option enabled in same area deliveries.', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'biz_morning_delivery_fee'  => array(
                'title' => __('Morning delivery fee', 'wc-biz-courier-logistics'),
                'description' => __('Insert the additional fee amount for morning deliveries.', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_saturday_delivery'  => array(
                'title' => __('Saturday delivery', 'wc-biz-courier-logistics'),
                'description' => __('Check the box if you would like the Saturday delivery option enabled in same area deliveries.', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'default' => 'no'
            ),
            'biz_saturday_delivery_fee'  => array(
                'title' => __('Saturday delivery fee', 'wc-biz-courier-logistics'),
                'description' => __('Insert the additional fee amount for deliveries on Saturdays.', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
        );

        $this->instance_form_fields = array(
            'biz_zone_type' => array(
                'title' => __("Shipping zone type", "wc-biz-courier-logistics"),
                'type' => 'select',
                'description' => __("Select the type of Biz area covered by this shipping zone.", 'wc-biz-courier-logistics'),
                'options' => array(
                    'same-area' => __('Same area as warehouse', 'wc-biz-courier-logistics'),
                    'land-destinations' => __('Land destinations', 'wc-biz-courier-logistics'),
                    'island-destinations' => __('Island destinations', 'wc-biz-courier-logistics'),
                    'inaccessible-areas' => __('Inaccessible areas', 'wc-biz-courier-logistics'),
                ),
                'biz_default' => 'same-area'
            ),
            'biz_delivery_pricing'  => array(
                'title' => __('Delivery pricing', 'wc-biz-courier-logistics'),
                'description' => __('Insert the delivery fee amount for up to 2kg.', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
            'biz_delivery_pricing_extra_kg'  => array(
                'title' => __('Charge per extra kg', 'wc-biz-courier-logistics'),
                'description' => __('Insert the delivery fee amount for every extra kg.', 'wc-biz-courier-logistics'),
                'type' => 'price',
            ),
        );
    }



	/**
	 * Calculate shipping and add Biz Courier rates.
	 *
	 * @since    1.0.0
	 * @param 	 array $package The given package to be delivered.
	 */
    public function calculate_shipping($package = array())
    {
        // Calculate weight.
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


        // Calculate simple rate.
        $this->add_rate(array(
            'id' => $this->id,
            'label' => __('Simple delivery', 'wc-biz-courier-logistics'),
            'cost' => $calculated_rate
        ));

        // Calculate rates for extra services.
        if ($this->get_option('biz_zone_type') == 'same-area') {

            if($this->get_option('biz_morning_delivery') == 'yes') {
                // Morning deliveries.
                $this->add_rate(array(
                    'id' => $this->id . '-morning-delivery',
                    'label' => __('Morning delivery (until 10:30 a.m.)', 'wc-biz-courier-logistics'),
                    'cost' => $calculated_rate + $this->get_option('biz_morning_delivery_fee')
                ));
            }

            if($this->get_option('biz_saturday_delivery') == 'yes') {

                // Saturday deliveries.
                $this->add_rate(array(
                    'id' => $this->id . '-saturday-delivery',
                    'label' => __('Saturday delivery', 'wc-biz-courier-logistics'),
                    'cost' => $calculated_rate + $this->get_option('biz_saturday_delivery_fee')
                ));
            }
        }
    }
}
