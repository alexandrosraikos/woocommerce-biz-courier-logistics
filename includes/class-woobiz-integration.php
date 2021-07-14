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
        $this->method_title = __('Biz Courier & Logistics', 'woobiz');
        $this->method_description = __('An integration for synchronising stock, orders and payment fees with the Biz Courier & Logistics platform.', 'woobiz');

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
			'section_title' => array(
				'title'     => __('Biz Courier Credentials', 'woobiz'),
				'type'     => 'title',
				'description'     => __("Insert your Biz Courier credentials here. If you are still unregistered with Biz Courier, please <a href=\"https://www.bizcourier.eu/ContactUs.htm\" target=\"blank\">contact us</a>.", 'woobiz'),
				'id'       => 'section_title'
			),
			'account_number' => array(
				'title' => __('Account Number', 'woobiz'),
				'type' => 'decimal',
				'description' => __('Your account number registered with Biz Courier.', 'woobiz'),
				'id'   => 'account_number'
			),
			'warehouse_crm' => array(
				'title' => __('Warehouse CRM Number', 'woobiz'),
				'type' => 'decimal',
				'description' => __('Your CRM number for the warehouse location assigned to this store.', 'woobiz'),
				'id'   => 'warehouse_crm'
			),
			'username' => array(
				'title' => __('Username', 'woobiz'),
				'type' => 'text',
				'description' => __('Your username registered with Biz Courier.', 'woobiz'),
				'id'   => 'username'
			),
			'password' => array(
				'title' => __('Password', 'woobiz'),
				'type' => 'password',
				'description' => __('Your Biz Courier merchant password.', 'woobiz'),
				'id'   => 'password'
			),
        );
    }
}
