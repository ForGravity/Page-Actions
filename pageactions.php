<?php
/**
Plugin Name: Page Actions for Gravity Forms
Plugin URI: http://forgravity.com/plugins/page-actions/
Description:
Version: 1.0-dev-1
Author: ForGravity
Author URI: https://forgravity.com
Text Domain: forgravity_pageactions
Domain Path: /languages
 **/

if ( ! defined( 'FG_EDD_STORE_URL' ) ) {
	define( 'FG_EDD_STORE_URL', 'https://forgravity.com' );
}

define( 'FG_PAGEACTIONS_VERSION', '1.0-dev-1' );
define( 'FG_PAGEACTIONS_EDD_ITEM_NAME', 'Page Actions' );

// Initialize plugin updater.
add_action( 'init', array( 'PageActions_Bootstrap', 'updater' ), 0 );

// If Gravity Forms is loaded, bootstrap the Page Actions Add-On.
add_action( 'gform_loaded', array( 'PageActions_Bootstrap', 'load' ), 5 );

/**
 * Class Bootstrap
 *
 * Handles the loading of the Page Actions Add-On and registers with the Add-On framework.
 */
class PageActions_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Page Actions Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		if ( ! class_exists( '\ForGravity\PageActions\EDD_SL_Plugin_Updater' ) ) {
			require_once( 'includes/EDD_SL_Plugin_Updater.php' );
		}

		require_once( 'class-pageactions.php' );

		GFAddOn::register( '\ForGravity\PageActions\Page_Actions' );

	}

	/**
	 * Initialize plugin updater.
	 *
	 * @access public
	 * @static
	 */
	public static function updater() {

		// Get Page Actions instance.
		$page_actions = fg_pageactions();

		// If Page Actions could not be retrieved, exit.
		if ( ! $page_actions ) {
			return;
		}

		// Get plugin settings.
		$settings = $page_actions->get_plugin_settings();

		// Get license key.
		$license_key = trim( rgar( $settings, 'license_key' ) );

		new ForGravity\PageActions\EDD_SL_Plugin_Updater(
			FG_EDD_STORE_URL,
			__FILE__,
			array(
				'version'   => FG_PROGRESSIVEPROFILING_VERSION,
				'license'   => $license_key,
				'item_name' => FG_PROGRESSIVEPROFILING_EDD_ITEM_NAME,
				'author'    => 'ForGravity',
			)
		);

	}

}

/**
 * Returns an instance of the Page_Actions class
 *
 * @see    Page_Actions::get_instance()
 *
 * @return object Page_Actions
 */
function fg_pageactions() {
	if ( class_exists( '\ForGravity\PageActions\Page_Actions' )  ) {
		return ForGravity\PageActions\Page_Actions::get_instance();
	}
}
