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
 * @author     Your Name <alexandros@araikos.gr>
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

	// TODO: Add WooCommerce Settings API & Options API pane for BizCourier credentials.
	// - Duplicate placeholder functions above to get started.
	// - Add PHPDoc for clarity.


	/* BIZ CREDENTIALS SETTINGS */

	/**
	 * Add a new settings tab to the WooCommerce settings tabs array.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
	 * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
	 */
	public static function add_biz_settings_tab($settings_tabs)
	{
		$settings_tabs['biz_settings_tab'] = __('BizCourier', 'woocommerce-biz-settings-tab');
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
				'name'     => __('BizCourier Credentials', 'woobiz'),
				'type'     => 'title',
				'desc'     => __('Insert your BizCourier credentials here. If you are still unregistered with BizCourier, please <a href="https://www.bizcourier.eu/ContactUs.htm" target="blank">contact us</a>.', 'woobiz'),
				'id'       => 'wc_biz_settings_tab_section_title'
			),
			'account_number' => array(
				'name' => __('Account Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your account number registered with BizCourier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_account_number'
			),
			'head_crm' => array(
				'name' => __('Head CRM Number', 'woobiz'),
				'type' => 'text',
				'desc' => __('Your head CRM number registered with BizCourier.', 'woobiz'),
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
				'desc' => __('Your username registered with BizCourier.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_username'
			),
			'password' => array(
				'name' => __('Password', 'woobiz'),
				'type' => 'password',
				'desc' => __('Your BizCourier merchant password.', 'woobiz'),
				'id'   => 'wc_biz_settings_tab_password'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_biz_settings_tab_section_end'
			),
		);

		return apply_filters('wc_biz_settings_tab_settings', $settings);
	}
}
