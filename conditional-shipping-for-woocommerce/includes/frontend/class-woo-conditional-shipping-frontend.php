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

			// Store all post data into the session so data can be used in filters
			add_action( 'woocommerce_checkout_update_order_review', [ $this, 'store_customer_details' ], 10, 1 );

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

			wp_localize_script( 'woo-conditional-shipping-js', 'conditional_shipping_settings', [
				'trigger_fields' => $this->get_trigger_fields(),
			] );
		}

		wp_enqueue_style( 'woo_conditional_shipping_css', plugin_dir_url( __FILE__ ) . '../../frontend/css/woo-conditional-shipping.css', [], WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION );
	}

	/**
	 * Check if blocks based checkout is active
	 */
	public function is_blocks_checkout() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && is_callable( [ 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default'] ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() && ! has_block( 'woocommerce/classic-shortcode' ) ) {
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
	 * Get fields which require manual trigger for checkout update
	 * 
	 * By default changing first name, last name, company and certain other fields
	 * do not trigger checkout update. Thus we need to trigger update manually if we have
	 * conditions for these fields.
	 * 
	 * Triggering will be done in JS. However, we check here if we have conditions for these
	 * fields. If we dont have, we dont want to trigger update as that would be unnecessary.
	 */
	public function get_trigger_fields() {
		$ruleset_fields = get_option( 'wcs_ruleset_fields', [] );

		$trigger_fields = [];
		foreach ( $ruleset_fields as $ruleset_id => $fields ) {
			$trigger_fields = array_merge( $trigger_fields, $fields );
		}

		return array_unique( $trigger_fields );
	}

  	/**
   	 * Store customer details to the session for being used in filters
   	 */
	public function store_customer_details( $post_data ) {
		$data = [];
		parse_str( $post_data, $data );

		$attrs = [
			'billing_first_name', 'billing_last_name', 'billing_company',
			'shipping_first_name', 'shipping_last_name', 'shipping_company',
			'billing_email', 'billing_phone'
		];

		$same_addr = false;
		if ( ! isset( $data['ship_to_different_address'] ) || $data['ship_to_different_address'] != '1' ) {
			$same_addr = true;
			$attrs = [
				'billing_first_name', 'billing_last_name', 'billing_company', 'billing_email', 'billing_phone',
			];
		}

		foreach ( $attrs as $attr ) {
			WC()->customer->set_props( [
				$attr => isset( $data[$attr] ) ? wp_unslash( $data[$attr] ) : null,
			] );

			if ( $same_addr ) {
			$attr2 = str_replace( 'billing', 'shipping', $attr );
				WC()->customer->set_props( [
					$attr2 => isset( $data[$attr] ) ? wp_unslash( $data[$attr] ) : null,
				] );
			}
		}
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
					if ( $passes && $ruleset->notice_applicable( $action ) ) {
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
		WC()->session->set( 'wcs_passed_rule_ids', $this->passed_rule_ids );

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
