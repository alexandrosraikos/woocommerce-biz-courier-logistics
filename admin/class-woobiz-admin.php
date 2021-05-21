<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/alexandrosraikos/woobiz
 * @since      1.0.0
 *
 * @package    WooBiz
 * @subpackage WooBiz/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WooBiz
 * @subpackage WooBiz/admin
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WooBiz_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $WooBiz    The ID of this plugin.
	 */
	private $WooBiz;

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
	 * @param      string    $WooBiz       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($WooBiz, $version)
	{

		$this->WooBiz = $WooBiz;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WooBiz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WooBiz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->WooBiz, plugin_dir_url(__FILE__) . 'css/woobiz-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in WooBiz_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The WooBiz_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->WooBiz, plugin_dir_url(__FILE__) . 'js/woobiz-admin.js', array('jquery'), $this->version, false);
	}

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_biz_settings_tab($settings_tabs)
	{
		$settings_tabs['biz_settings_tab'] = __('Biz Courier', 'woocommerce-biz-settings-tab');
		return $settings_tabs;
	}


	/**
	 * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
	 *
	 * @uses woocommerce_admin_fields()
	 * @uses self::get_settings()
	 */
	public static function biz_settings_tab()
	{
		woocommerce_admin_fields(self::get_settings());
	}


	/**
	 * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
	 *
	 * @uses woocommerce_update_options()
	 * @uses self::get_settings()
	 */
	public static function update_biz_settings()
	{
		woocommerce_update_options(self::get_settings());
	}

	/**
	 * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
	 *
	 * @return array Array of settings for @see woocommerce_admin_fields() function.
	 */
	public static function get_settings()
	{

		$settings = array(
			'section_title' => array(
				'name'     => __('Biz Courier Credentials', 'woobiz'),
				'type'     => 'title',
				'desc'     => __('Insert your Biz Courier credentials here. If you are still unregistered with Biz Courier, please <a href="https://www.bizcourier.eu/ContactUs.htm" target="blank">contact us</a>.', 'woobiz'),
				'id'       => 'wc_biz_settings_tab_section_title'
			),
			'account_number' => array(
				'name' => __('Account Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your account number registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_account_number'
			),
			'head_crm' => array(
				'name' => __('Head CRM Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your head CRM number registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_head_crm'
			),
			'warehouse_crm' => array(
				'name' => __('Warehouse CRM Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your CRM number for the warehouse location assigned to this store.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_warehouse_crm'
			),
			'username' => array(
				'name' => __('Username', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your username registered with Biz Courier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_username'
			),
			'password' => array(
				'name' => __('Password', 'woobiz'),
				'type' => 'password',
				'desc' => __('Your Biz Courier merchant password.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_password'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_biz_settings_tab_section_end'
			),
		);

		return apply_filters('wc_biz_settings_tab_settings', $settings);
	}

	function biz_order_status($order) {
		// $client = new SoapClient();

	}



	/**
	 * Add Biz Courier connection indicator metabox to a single order.
	 *
	 * @since    1.0.0
	 */
	function add_biz_order_meta_box()
	{
		function biz_order_meta_box($post)
		{

			// Get markup.
			require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/woobiz-admin-display.php';

			$order = wc_get_order($post->ID);

			/*	1. Check order meta (if sync on/off).
					1.1. If not synced, prompt admin to sync:
			*/
			biz_order_meta_box_not_synchronized_html();
			/*
					1.2. If synced,
						1.2.1. Get order status and show to admin: 
			*/
			// TODO: Implement biz_order_status($order->get_ID()) for front and back & handle connection errors.
			/*
				2. Check if POST['woobiz_sync']=true (& nonce) and do initial sync.
			*/
			// TODO: Implement biz_order_sync($order), raise sync meta flag & handle connection errors.


		}
		add_meta_box('woobiz_order_meta_box', __('Biz Courier status', 'woobiz'), 'biz_order_meta_box', 'shop_order', 'side', 'high');
	}
}
