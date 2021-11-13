<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/alexandrosraikos/wc-biz-courier-logistics
 * @since      1.0.0
 *
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WC_Biz_Courier_Logistics
 * @subpackage WC_Biz_Courier_Logistics/includes
 * @author     Alexandros Raikos <alexandros@araikos.gr>
 */
class WC_Biz_Courier_Logistics_Deactivator
{

	/**
	 * Delete Biz Courier credentials.
	 *
	 * Upon deactivation, all Biz Courier credentials are deleted from the database.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate()
	{	
		require_once plugin_dir_path(__FILE__) . 'admin/class-wc-biz-courier-logistics-product-delegate.php';
		// delete_option('woocommerce_biz_integration_settings');
		reset_all_sync_status();
	}
}
