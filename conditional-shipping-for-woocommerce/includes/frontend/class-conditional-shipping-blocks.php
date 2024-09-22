<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for integrating with WooCommerce Blocks
 */
class Woo_Conditional_Shipping_Integration implements \Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface {
	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'woo_conditional_shipping';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->enqueue_assets();
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_assets() {
		$script_path = '/frontend/js/build/blocks.js';
		$style_path = '/frontend/css/woo-conditional-shipping.css';

		$script_url = plugins_url( $script_path, WOO_CONDITIONAL_SHIPPING_FILE );
		$style_url = plugins_url( $style_path, WOO_CONDITIONAL_SHIPPING_FILE );

		$script_asset_path = dirname( WOO_CONDITIONAL_SHIPPING_FILE ) . '/assets/js/build/blocks.asset.php';

		$script_asset = file_exists( $script_asset_path )
			? require $script_asset_path
			: [
				'dependencies' => [],
				'version' => $this->get_file_version( $script_path ),
			];

		wp_enqueue_style(
			'woo-conditional-shipping-blocks-style',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'woo-conditional-shipping-blocks',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [ 'woo-conditional-shipping-blocks' ];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
	    return [
        ];
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
			return filemtime( $file );
		}

		return WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION;
	}
}
