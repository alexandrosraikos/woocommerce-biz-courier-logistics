<?php
class BizCourier_Shipping_Method extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->id                 = 'bizcourier';
        $this->method_title       = __('BizCourier Shipping', 'woobiz');
        $this->method_description = __('Enable shipping integration with BizCourier.', 'woobiz');

        $this->init();

        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
        $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('BizCourier Shipping', 'woobiz');
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Define settings field for this shipping
     * @return void 
     */
    function init_form_fields()
    {

        // We will add our settings here

    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    // public function calculate_shipping($package)
    // {

    // 	// We will add the cost, rate and logics in here

    // }
}
