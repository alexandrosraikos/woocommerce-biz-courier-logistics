<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/public
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WC_Biz_Courier_Logistics_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $WC_Biz_Courier_Logistics    The ID of this plugin.
	 */
	private $WC_Biz_Courier_Logistics;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $WC_Biz_Courier_Logistics       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $WC_Biz_Courier_Logistics, $version ) {

		$this->WC_Biz_Courier_Logistics = $WC_Biz_Courier_Logistics;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WC_Biz_Courier_Logistics_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WC_Biz_Courier_Logistics_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->WC_Biz_Courier_Logistics, plugin_dir_url( __FILE__ ) . 'css/wc-biz-courier-logistics-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WC_Biz_Courier_Logistics_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WC_Biz_Courier_Logistics_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->WC_Biz_Courier_Logistics, plugin_dir_url( __FILE__ ) . 'js/wc-biz-courier-logistics-public.js', array( 'jquery' ), $this->version, false );

	}

	/**
	 * Calculate the additional COD fee for Biz shipping on checkout
	 * 
	 * @param object $cart The given checkout cart.
	 *
	 * @author Alexandros Raikos <alexandros@araikos.gr>
	 * @since 1.3.3
	 * 
	 * @version 1.4.0
	 */
	function add_biz_cod_fee($cart)
	{

		// Ignore AJAX call.
		if (is_admin() && defined('DOING_AJAX')) return;

		// Add the COD fee on COD payment methods.
		if (
			WC()->session->get('chosen_payment_method') == 'cod'
		) {
			/** @var array $biz_shipping_settings The Biz shipping settings. */
			$biz_shipping_settings = get_option('woocommerce_biz_shipping_method_settings');

			// Add the registered fee amount.
			if (!empty($biz_shipping_settings['biz_cash_on_delivery_fee'])) {
				$cart->add_fee(__('Cash on Delivery fee', 'wc-biz-courier-logistics'), $biz_shipping_settings['biz_cash_on_delivery_fee']);
			}
		}
	}


	/**
	 * Inject a script to refresh checkout fees on payment method change.
	 *
	 * @since    1.4.0
	 */
	function biz_checkout_refresh (){
		if(is_checkout() && ! is_wc_endpoint_url()):
		?>
		<script type="text/javascript">
		jQuery( function($){
			$('form.checkout').on('change', 'input[name="payment_method"]', function(){
				$(document.body).trigger('update_checkout');
			});
		});
		</script>
		<?php
		endif;
	}

}
