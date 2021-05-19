<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/alexandrosraikos/woobiz
 * @since      1.0.0
 *
 * @package    WooBiz
 * @subpackage WooBiz/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WooBiz
 * @subpackage WooBiz/includes
 * @author     Your Name <alexandros@araikos.gr>
 */
class WooBiz_Deactivator {

	/**
	 * Delete BizCourier credentials.
	 *
	 * Upon deactivation, all BizCourier credentials are deleted from the database.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		delete_option( 'wc_biz_settings_tab_account_number' );
		delete_option( 'wc_biz_settings_tab_head_crm' );
		delete_option( 'wc_biz_settings_tab_warehouse_crm' );
		delete_option( 'wc_biz_settings_tab_username' );
		delete_option( 'wc_biz_settings_tab_password' );
	}

}
