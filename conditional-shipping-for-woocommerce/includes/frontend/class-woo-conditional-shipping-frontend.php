<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Frontend {
	private $passed_rule_ids = [];
	private $notices = [];

	/**
	 * @var Woo_Conditional_Shipping_Debug
	 */
	private $debug;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->debug = Woo_Conditional_Shipping_Debug::instance();

		// Load frontend styles and scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10, 0 );

		if ( ! get_option( 'wcs_disable_all', false ) ) {
			add_filter( 'woocommerce_package_rates', array( $this, 'filter_shipping_methods' ), 100, 2 );

			// Multicurrency support
			add_filter( 'wcs_convert_price', [ $this, 'convert_price' ], 10, 1 );
			add_filter( 'wcs_convert_price_reverse', [ $this, 'convert_price_reverse' ], 10, 1 );

			// Blow shipping method cache after WPML has changed currency
			// Otherwise subtotals might be in wrong currency
			add_action( 'wcml_switch_currency', function() {
				if ( class_exists( 'WC_Cache_Helper' ) ) {
					WC_Cache_Helper::get_transient_version( 'shipping', true );
				}
			}, 10, 0 );
		}

		add_action( 'woocommerce_blocks_loaded', [ $this, 'register_blocks_support' ], 10, 0 );
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_blocks_checkout() ) {
			wp_enqueue_script(
				'woo-conditional-shipping-js',
				plugin_dir_url( __FILE__ ) . '../../frontend/js/woo-conditional-shipping.js',
				[ 'jquery' ],
				WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION
			);
		}

		wp_enqueue_style( 'woo_conditional_shipping_css', plugin_dir_url( __FILE__ ) . '../../frontend/css/woo-conditional-shipping.css', [], WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION );
	}

	/**
	 * Check if blocks based checkout is active
	 */
	public function is_blocks_checkout() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && is_callable( [ 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default'] ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
			return true;
		}

		return false;
	}

	/**
	 * Register blocks support
	 */
	public function register_blocks_support() {
		if ( interface_exists( '\Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			require_once 'class-conditional-shipping-blocks.php';

			add_action(
				'woocommerce_blocks_checkout_block_registration',
				function( $integration_registry ) {
					$integration_registry->register( new Woo_Conditional_Shipping_Integration() );
				}
			);

			woocommerce_store_api_register_endpoint_data(
				[
					'endpoint' => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
					'namespace' => 'woo-conditional-shipping',
					'data_callback' => [ $this, 'store_api_data' ],
					'schema_callback' => [ $this, 'store_api_schema' ],
					'schema_type' => ARRAY_A,
				]
			);
		}
	}

	/**
	 * Store API data (Blocks checkout)
	 */
	public function store_api_data() {
		$debug = false;
		if ( $this->debug->is_enabled() ) {
			$debug = $this->debug->output_debug_checkout( false );
		}

		return [
			'notices' => array_values( apply_filters( 'wcs_notices', array_values( array_unique( $this->notices ) ) ) ),
			'debug' => $debug,
		];
	}

	/**
	 * Store API schema (Blocks checkout)
	 */
	public function store_api_schema() {
		return [
			'notices' => [
				'description' => __( 'Shipping notices', 'conditional-shipping-for-woocommerce' ),
				'type' => [ 'array', 'null' ],
				'readonly' => true,
			],
			'debug' => [
				'description' => __( 'Debug information', 'conditional-shipping-for-woocommerce' ),
				'type' => [ 'string', 'null' ],
				'readonly' => true,
			],
		];
	}
 
	/**
	 * Filter shipping methods
	 */
	public function filter_shipping_methods( $rates, $package ) {
		$this->debug->record_rates( $rates, 'before' );

		$rulesets = woo_conditional_shipping_get_rulesets( true );
		$this->passed_rule_ids = [];
		$this->notices = [];

		$disable_keys = [];
		$enable_keys = [];

		foreach ( $rulesets as $ruleset ) {
			$passes = $ruleset->validate( $package );

			if ( $passes ) {
				$this->passed_rule_ids[] = $ruleset->get_id();
			}

			foreach ( $ruleset->get_actions( true ) as $action_index => $action ) {
				if ( $action['type'] === 'disable_shipping_methods' ) {
					if ( $passes ) {
						foreach ( $rates as $key => $rate ) {
							$instance_id = $this->get_rate_instance_id( $rate );
							$method_title = is_callable( [ $rate, 'get_label' ] ) ? $rate->get_label() : false;

							if ( wcs_method_selected( $method_title, $instance_id, $action ) ) {
								$disable_keys[$key] = true;
								unset( $enable_keys[$key] );
							}
						}
					}
				}

				if ( $action['type'] === 'enable_shipping_methods' ) {
					foreach ( $rates as $key => $rate ) {
						$instance_id = $this->get_rate_instance_id( $rate );
						$method_title = is_callable( [ $rate, 'get_label' ] ) ? $rate->get_label() : false;

						if ( wcs_method_selected( $method_title, $instance_id, $action ) ) {
							if ( $passes ) {
								$enable_keys[$key] = true;
								unset( $disable_keys[$key] );
							} else {
								$disable_keys[$key] = true;
								unset( $enable_keys[$key] );
							}
						}
					}
				}

				if ( $action['type'] === 'set_title' ) {
					if ( $passes ) {
						foreach ( $rates as $key => $rate ) {
							$instance_id = $this->get_rate_instance_id( $rate );
							$method_title = is_callable( [ $rate, 'get_label' ] ) ? $rate->get_label() : false;

							if ( wcs_method_selected( $method_title, $instance_id, $action ) ) {
								$rate->set_label( $action['title'] );
							}
						}
					}
				}

				if ( $action['type'] === 'shipping_notice' ) {
					if ( $passes ) {
						$notice = do_shortcode( strval( $action['notice'] ) );

						$this->notices[] = sprintf( '<div class="conditional-shipping-notice">%s</div>', $notice );
					}
				}

				$this->debug->add_action( $ruleset->get_id(), $passes, $action_index, $action );
			}
		}

		foreach ( $rates as $key => $rate ) {
			if ( isset( $disable_keys[$key] ) && ! isset( $enable_keys[$key] ) ) {
				unset( $rates[$key] );
			}
		}

		// Store passed rule IDs into the session for later use
		// We cannot use $this->passed_rule_ids directly since this function is not evaluated
		// if rates are fetched from WC cache. Thus we use session which will always contain
		// passed_rule_ids
		WC()->session->set( 'wcp_passed_rule_ids', $this->passed_rule_ids );

		$this->debug->record_rates( $rates, 'after' );

		return $rates;
	}

	/**
	 * Helper function for getting rate instance ID
	 */
	public function get_rate_instance_id( $rate ) {
		$instance_id = false;

		if ( method_exists( $rate, 'get_instance_id' ) && strlen( strval( $rate->get_instance_id() ) ) > 0 ) {
			$instance_id = $rate->get_instance_id();
		} else {
			if ( $rate->method_id == 'oik_weight_zone_shipping' ) {
				$ids = explode( '_', $rate->id );
				$instance_id = end( $ids );
			} else {
				$ids = explode( ':', $rate->id );
				if ( count($ids) >= 2 ) {
					$instance_id = $ids[1];
				}
			}
		}

		$instance_id = apply_filters( 'woo_conditional_shipping_get_instance_id', $instance_id, $rate );

		return $instance_id;
	}

	/**
	 * Convert price to the active currency from the default currency
	 */
	public function convert_price( $value ) {
		// WooCommerce Currency Switcher by realmag777
		if ( isset( $GLOBALS['WOOCS'] ) && is_callable( [ $GLOBALS['WOOCS'], 'woocs_exchange_value' ] ) ) {
			return floatval( $GLOBALS['WOOCS']->woocs_exchange_value( $value ) );
		}

		// WPML
		if ( isset( $GLOBALS['woocommerce_wpml'] ) && isset( $GLOBALS['woocommerce_wpml']->multi_currency->prices ) && is_callable( [ $GLOBALS['woocommerce_wpml']->multi_currency->prices, 'convert_price_amount' ] ) ) {
			return floatval( $GLOBALS['woocommerce_wpml']->multi_currency->prices->convert_price_amount( $value ) );
		}

		// Currency Switcher by Aelia
		if ( isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) && $GLOBALS['woocommerce-aelia-currencyswitcher'] ) {
			$base_currency = apply_filters( 'wc_aelia_cs_base_currency', false );

			return floatval( apply_filters( 'wc_aelia_cs_convert', $value, $base_currency, get_woocommerce_currency() ) );
		}

		// Price Based on Country for WooCommerce
		if ( class_exists( 'WCPBC_Pricing_Zones' ) ) {
			$zone = WCPBC_Pricing_Zones::get_zone( false );

			if ( ! empty( $zone ) && method_exists( $zone, 'get_exchange_rate_price' ) ) {
				return floatval( $zone->get_exchange_rate_price( $value ) );
			}
		}

		return $value;
	}

	/**
	 * Convert price to the default currency from the active currency
	 */
	public function convert_price_reverse( $value ) {
		// WooCommerce Currency Switcher by realmag777
		if ( isset( $GLOBALS['WOOCS'] ) && is_callable( [ $GLOBALS['WOOCS'], 'convert_from_to_currency' ] ) ) {
			return floatval( $GLOBALS['WOOCS']->convert_from_to_currency( $value, $GLOBALS['WOOCS']->current_currency, $GLOBALS['WOOCS']->default_currency ) );
		}

		// WPML
		if ( isset( $GLOBALS['woocommerce_wpml'] ) && isset( $GLOBALS['woocommerce_wpml']->multi_currency->prices ) && is_callable( [ $GLOBALS['woocommerce_wpml']->multi_currency->prices, 'unconvert_price_amount' ] ) ) {
			return floatval( $GLOBALS['woocommerce_wpml']->multi_currency->prices->unconvert_price_amount( $value ) );
		}

		// Currency Switcher by Aelia
		if ( isset( $GLOBALS['woocommerce-aelia-currencyswitcher'] ) && $GLOBALS['woocommerce-aelia-currencyswitcher'] ) {
			$base_currency = apply_filters( 'wc_aelia_cs_base_currency', false );

			return floatval( apply_filters( 'wc_aelia_cs_convert', $value, get_woocommerce_currency(), $base_currency ) );
		}

		// Price Based on Country for WooCommerce
		if ( class_exists( 'WCPBC_Pricing_Zones' ) ) {
			$zone = WCPBC_Pricing_Zones::get_zone( false );

			if ( ! empty( $zone ) && method_exists( $zone, 'get_base_currency_amount' ) ) {
				return floatval( $zone->get_base_currency_amount( $value ) );
			}
		}

		return $value;
	}
}
