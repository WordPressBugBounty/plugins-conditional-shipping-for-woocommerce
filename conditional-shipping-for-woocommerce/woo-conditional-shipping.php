<?php

/*
Plugin Name: Conditional Shipping for WooCommerce
Description: Disable shipping methods based on shipping classes, weight, categories and much more.
Version:     3.3.0
Author:      Lauri Karisola / WP Trio
Author URI:  https://wptrio.com
Text Domain: conditional-shipping-for-woocommerce
Domain Path: /languages
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
WC requires at least: 7.0.0
WC tested up to: 9.0.0
*/

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin version
 */
if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_VERSION' ) ) {
	define( 'WOO_CONDITIONAL_SHIPPING_VERSION', '3.3.0' );
}

/**
 * Assets version
 */
if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION' ) ) {
	define( 'WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION', '3.3.0.free' );
}

/**
 * Load plugin textdomain
 *
 * @return void
 */
add_action( 'plugins_loaded', 'woo_conditional_shipping_load_textdomain' );
function woo_conditional_shipping_load_textdomain() {
  load_plugin_textdomain( 'conditional-shipping-for-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * HPOS compatibility
 */
add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

class Woo_Conditional_Shipping {
	/**
	 * Constructor
	 */
	function __construct() {
		// WooCommerce not activated, abort
		if ( ! defined( 'WC_VERSION' ) ) {
			return;
		}

		// Pro version activated, abort
		if ( class_exists( 'Woo_Conditional_Shipping_Pro' ) ) {
			return;
		}

		if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_BASENAME' ) ) {
			define( 'WOO_CONDITIONAL_SHIPPING_BASENAME', plugin_basename( __FILE__ ) );
		}

		if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_URL' ) ) {
			define( 'WOO_CONDITIONAL_SHIPPING_URL', plugin_dir_url( __FILE__ ) );
		}

		if ( ! defined( 'WOO_CONDITIONAL_SHIPPING_FILE' ) ) {
			define( 'WOO_CONDITIONAL_SHIPPING_FILE', __FILE__ );
		}

		$this->includes();

		// Go Pro settings link
		add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Include required files
	 */
	public function includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-conditional-shipping-updater.php' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-woo-conditional-shipping-debug.php' );
		Woo_Conditional_Shipping_Debug::instance();

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-conditional-shipping-filters.php' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-woo-conditional-shipping-post-type.php', 'Woo_Conditional_Shipping_Post_Type' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/class-woo-conditional-shipping-ruleset.php', 'Woo_Conditional_Shipping_Ruleset' );

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/woo-conditional-shipping-utils.php' );

		if ( is_admin() ) {
			$this->admin_includes();
		}

		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/frontend/class-woo-conditional-shipping-frontend.php', 'Woo_Conditional_Shipping_Frontend' );
	}

	/**
	 * Include admin files
	 */
	private function admin_includes() {
		$this->load_class( plugin_dir_path( __FILE__ ) . 'includes/admin/class-woo-conditional-shipping-admin.php', 'Woo_Conditional_Shipping_Admin' );
	}

	/**
	 * Load class
	 */
	private function load_class( $filepath, $class_name = FALSE ) {
		require_once( $filepath );

		if ( $class_name ) {
			return new $class_name;
		}

		return TRUE;
	}

	/**
	 * Add settings link to the plugins page.
	 */
	public function add_settings_link( $links ) {
		$link = '<span style="font-weight:bold;"><a href="https://wptrio.com/products/conditional-shipping/" style="color:#46b450;" target="_blank">' . __( 'Go Pro' ) . '</a></span>';

		return array_merge( array( $link ), $links );
	}
}

function init_woo_conditional_shipping() {
	new Woo_Conditional_Shipping();
}

add_action( 'plugins_loaded', 'init_woo_conditional_shipping', 10 );
