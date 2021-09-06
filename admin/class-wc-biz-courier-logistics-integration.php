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
 * The integration method class.
 *
 * This class extends WC_Integration with necessary functional additions
 * for Biz Courier.
 *
 * @since      1.0.0
 * @package    WC_Integration
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */

class Biz_Integration extends WC_Integration
{

	/**
	 * Initialize the class and set its properties
	 *
	 * @since    1.0.0
	 * @param 	 int $instance_id The given instance ID for this shipping method.
	 */
    public function __construct()
    {
        global $woocommerce;
        $this->id = 'biz_integration';
        $this->method_title = __('Biz Courier & Logistics', 'wc-biz-courier-logistics');
        $this->method_description = __('An integration for synchronising stock, orders and payment fees with the Biz Courier & Logistics platform.', 'wc-biz-courier-logistics');

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
        $this->init_settings();
        add_action('woocommerce_update_options_integration_' . $this->id, array($this, 'process_admin_options'));
    }


	/**
	 * Initialize the WooCommerce Settings API form fields.
	 *
	 * @since    1.0.0
	 */
    public function init_form_fields()
    {
        $this->form_fields = array(
			'credentials_section_title' => array(
				'title'     => __('Biz Courier Credentials', 'wc-biz-courier-logistics'),
				'type'     => 'title',
				'description'     => __("Insert your Biz Courier credentials here. If you are still unregistered with Biz Courier, please <a href=\"https://www.bizcourier.eu/ContactUs.htm\" target=\"blank\">contact Biz</a>.", 'wc-biz-courier-logistics'),
				'id'       => 'section_title'
			),
			'account_number' => array(
				'title' => __('Account Number', 'wc-biz-courier-logistics'),
				'type' => 'decimal',
				'description' => __('Your account number registered with Biz Courier.', 'wc-biz-courier-logistics'),
				'id'   => 'account_number'
			),
			'warehouse_crm' => array(
				'title' => __('Warehouse CRM Number', 'wc-biz-courier-logistics'),
				'type' => 'decimal',
				'description' => __('Your CRM number for the warehouse location assigned to this store.', 'wc-biz-courier-logistics'),
				'id'   => 'warehouse_crm'
			),
			'username' => array(
				'title' => __('Username', 'wc-biz-courier-logistics'),
				'type' => 'text',
				'description' => __('Your username registered with Biz Courier.', 'wc-biz-courier-logistics'),
				'id'   => 'username'
			),
			'password' => array(
				'title' => __('Password', 'wc-biz-courier-logistics'),
				'type' => 'password',
				'description' => __('Your Biz Courier merchant password.', 'wc-biz-courier-logistics'),
				'id'   => 'password'
			),
			'general_section_title' => array(
				'title'     => __('General', 'wc-biz-courier-logistics'),
				'type'     => 'title',
				'description'     => __("Choose your optimal working settings for integrating Biz Courier & Logistics with your WooCommerce shop.", 'wc-biz-courier-logistics'),
				'id'       => 'section_title'
			),
            'automatic_order_status_updating' => array(
                'title' => __('Automatic order status updates', 'wc-biz-courier-logistics'),
                'type' => 'checkbox',
                'description' => __("Update Processing shipment status automatically to Completed or Cancelled, based on Biz Courier data.", 'wc-biz-courier-logistics'),
                'default' => 'no'
            ),
        );
    }
}
