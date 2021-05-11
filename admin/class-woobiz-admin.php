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

	/**
	 * Register BizCourier credentials.
	 * 
	 * @since 1.0.0
	 */
		/**
		 * Initialise Gateway Settings Form Fields
		 */
		function bizcourier_shipping_method()
		{
			if (!class_exists('BizCourier_Shipping_Method')) {
				require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/shipping-method.php';
			}
		}

		function add_bizcourier_shipping_method($methods)
		{
			$methods[] = 'BizCourier_Shipping_Method';
			return $methods;
		}

	}
