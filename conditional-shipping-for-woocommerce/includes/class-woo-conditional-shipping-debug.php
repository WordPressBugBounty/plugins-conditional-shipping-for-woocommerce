<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Debug {
  private static $instance = null;

  private $block_rendered = false;

  private $data = [];
  private $product_attrs = [];
  private $customer_roles = [];
  private $states = [];
  private $countries = [];
  private $weekdays = [];
  private $hours = [];
  private $mins = [];
  private $shipping_methods = [];

  /**
   * Constructor
   */
  public function __construct() {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // Format data
    add_action( 'woocommerce_init', [ $this, 'format' ], 10, 0 );

    // Record shipping zone
    add_action( 'woocommerce_load_shipping_methods', [ $this, 'record_zone' ], 100, 1 );

    // Enqueue scripts
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ], 10, 0 );

    // Render debug information (blocks-based checkout)
    add_filter( 'render_block', [ $this, 'render_block' ], 10, 1 );

    // Render debug information (classic checkout)
    add_action( 'woocommerce_before_checkout_form', [ $this, 'output_debug_checkout' ], 10, 0 );

    // Add debug info to fragments
    add_filter( 'woocommerce_update_order_review_fragments', [ $this, 'debug_fragment' ], 10, 1 );

    // Blow cache at every page load so we can get fresh shipping info
    WC_Cache_Helper::get_transient_version( 'shipping', true );
  }

  /**
   * Render debug information in the checkout (blocks-based checkout) 
   */
  public function render_block( $content ) {
    if ( ! $this->block_rendered && $this->is_blocks_checkout() && function_exists( 'is_checkout' ) && is_checkout() ) {
      $this->output_debug_checkout( true );
    }

    $this->block_rendered = true;

    return $content;
  }

  /**
   * Check if blocks-based checkout is used
   */
  public function is_blocks_checkout() {
    if ( class_exists( 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils' ) && is_callable( [ 'Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils', 'is_checkout_block_default'] ) && \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::is_checkout_block_default() ) {
      return true;
    }

    return false;
  }

  /**
   * Format debug data
   */
  public function format() {
    // Do not run if all rulesets are disabled
    if ( get_option( 'wcs_disable_all', false ) ) {
      return;
    }
    
    $this->data = [];

    $this->data['shipping_methods'] = [
      'before' => [],
      'after' => [],
    ];

    $this->data['shipping_zone'] = false;

    $this->data['rulesets'] = [];
    foreach ( woo_conditional_shipping_get_rulesets( true ) as $ruleset ) {
      $this->data['rulesets'][$ruleset->get_id()] = [
        'conditions' => [],
        'actions' => [],
        'ruleset_id' => $ruleset->get_id(),
        'ruleset_title' => $ruleset->get_title(),
        'result' => false,
      ];

      foreach ( $ruleset->get_conditions() as $index => $condition ) {
        $this->add_condition( $ruleset->get_id(), $index, $condition, false );
      }

      foreach ( $ruleset->get_actions() as $index => $action ) {
        $this->add_action( $ruleset->get_id(), false, $index, $action );
      }
    }
  }

  /**
   * Get instance
   */
  public static function instance() {
    if ( self::$instance == null ) {
      self::$instance = new Woo_Conditional_Shipping_Debug();
    }
 
    return self::$instance;
  }

  /**
   * Get debug mode status
   */
  public function is_enabled() {
    return (bool) get_option( 'wcs_debug_mode', false );
  }

  /**
   * Enqueue scripts and styles
   */
  public function enqueue_scripts() {
    wp_enqueue_script(
      'woo-conditional-shipping-debug-js',
      WOO_CONDITIONAL_SHIPPING_URL . 'frontend/js/woo-conditional-shipping-debug.js',
      [ 'jquery' ],
      WOO_CONDITIONAL_SHIPPING_ASSETS_VERSION
    );
  }

  /**
   * Add debug info to fragments
   */
  public function debug_fragment( $fragments ) {
    $fragments['#wcs-debug'] = $this->output_debug_checkout( false );

    return $fragments;
  }

  /**
   * Output debug information
   */
  public function output_debug_checkout( $echo = true ) {
    // Do not show in order received page
    if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
      return '';
    }

    $debug = $this->data;

    ob_start();

    include 'frontend/views/debug.html.php';

    $contents = ob_get_clean();

    if ( $echo ) {
      echo $contents;
    } else {
      return $contents;
    }
  }

  /**
   * Add condition result
   */
  public function add_condition( $ruleset_id, $condition_index, $condition, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $desc = $this->translate_condition( $condition );

    $this->data['rulesets'][$ruleset_id]['conditions'][$condition_index] = [
      'desc' => $desc,
      'result' => $result,
    ];
  }

  /**
   * Record shipping zone
   */
  public function record_zone( $package ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    // This function will also run in admin and possibly other places so
    // we have to check if there is really a package to be calculated
    if ( ! is_array( $package ) || ! isset( $package['destination'] ) || empty( $package['destination'] ) ) {
      return;
    }

    $zone = WC_Shipping_Zones::get_zone_matching_package( $package );

    if ( $zone ) {
      $zone_url = add_query_arg( [
        'page' => 'wc-settings',
        'tab' => 'shipping',
        'zone_id' => $zone->get_id(),
      ], admin_url( 'admin.php' ) );

      $this->data['shipping_zone'] = [
        'name' => $zone->get_zone_name(),
        'id' => $zone->get_id(),
        'url' => $zone_url,
        'name_with_url' => sprintf( '<a href="%s" target="_blank">%s</a>', $zone_url, $zone->get_zone_name() ),
      ];
    }
  }

  /**
   * Record rates before / after filtering
   */
  public function record_rates( $rates, $mode ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $simplified_rates = [];
    if ( is_array( $rates ) ) {
      foreach ( $rates as $key => $rate ) {
        $simplified_rates[$key] = sprintf( '%s (%s)', $rate->get_label(), $key );
      }
    }

    $this->data['shipping_methods'][$mode] = $simplified_rates;
  }

  /**
   * Add action result
   */
  public function add_action( $ruleset_id, $passes, $action_index, $action ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $this->data['rulesets'][$ruleset_id]['actions'][$action_index] = $this->translate_action( $action, $passes );
  }

  /**
   * Translate action into human-readable format
   */
  public function translate_action( $action, $passes ) {
    $actions = woo_conditional_shipping_actions();
    $price_modes = wcs_get_price_modes();

    $cols = [
      isset( $actions[$action['type']] ) ? $actions[$action['type']]['title'] : __( 'N/A', 'conditional-shipping-for-woocommerce' ),
    ];

    $desc = false;
    $status = $passes ? 'pass' : 'fail';

    switch ( $action['type'] ) {
      case 'disable_shipping_methods':
      case 'enable_shipping_methods':
        $cols['methods'] = implode( ', ', $this->get_shipping_method_titles( $action ) );
        break;
      case 'set_price':
      case 'increase_price':
      case 'decrease_price':
        $cols['methods'] = implode( ', ', $this->get_shipping_method_titles( $action ) );
        $price_mode = isset( $action['price_mode'] ) ? $action['price_mode'] : 'fixed';
        $price_mode_desc = isset( $price_modes[$price_mode] ) ? $price_modes[$price_mode] : '';

        $cols['value'] = sprintf( '%s %s', $action['price'], $price_mode_desc );
        break;
      case 'set_title':
        $cols['methods'] = implode( ', ', $this->get_shipping_method_titles( $action ) );
        $cols['value'] = isset( $action['title'] ) ? $action['title'] : '';
        break;
      case 'custom_error_msg':
        $cols['value'] = $action['error_msg'];
        break;
      case 'shipping_notice':
        $cols['value'] = $action['notice'];
        break;
    }

    if ( ! $passes && $action['type'] === 'enable_shipping_methods' ) {
      $desc = __( 'Shipping methods were disabled by "Enable shipping methods" because conditions did not pass', 'conditional-shipping-for-woocommerce' );
      $status = 'pass';
    }

    return [
      'cols' => $cols,
      'desc' => $desc,
      'status' => $status
    ];
  }

  /**
   * Get shipping method titles
   */
  public function get_shipping_method_titles( $action ) {
    $shipping_method_ids = isset( $action['shipping_method_ids'] ) ? (array) $action['shipping_method_ids'] : [];

    if ( ! $this->shipping_methods ) {
      $options = woo_conditional_shipping_get_shipping_method_options();

      foreach ( $options as $zone_id => $zone ) {
        foreach ( $zone['options'] as $instance_id => $data ) {
          $this->shipping_methods[$instance_id] = $data['title'];
        }
      }
    }

    // Special handling for "Match by name"
    if ( in_array( '_name_match', $shipping_method_ids, true ) ) {
      $name_match = isset( $action['shipping_method_name_match'] ) ? $action['shipping_method_name_match'] : '';
      $this->shipping_methods['_name_match'] = sprintf( __( 'Match by name: %s', 'conditional-shipping-for-woocommerce' ), $name_match );
    }

    return $this->ids_to_list( $shipping_method_ids, $this->shipping_methods );
  }

  /**
   * Add total result for ruleset
   */
  public function add_result( $ruleset_id, $result ) {
    if ( ! $this->is_enabled() ) {
      return;
    }

    $this->data['rulesets'][$ruleset_id]['result'] = $result;
  }

  /**
   * Translate condition to human-readable format
   */
  private function translate_condition( $condition ) {
    $operators = woo_conditional_shipping_operators();
    $filters = woo_conditional_shipping_filters();

    $filter = isset( $filters[$condition['type']] ) ? $filters[$condition['type']]['title'] : __( 'N/A', 'conditional-shipping-for-woocommerce' );
    $operator = isset( $operators[$condition['operator']] ) ? $operators[$condition['operator']] : __( 'N/A', 'conditional-shipping-for-woocommerce' );

    $value = $this->translate_condition_value( $condition );

    $cols = [ $filter ];

    // Subset filter
    if ( in_array( $condition['type'], [ 'subtotal', 'items', 'volume', 'weight' ], true ) && isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
      $cols[] = $this->translate_subset_filter( $condition );
    }

    $cols[] = $operator;

    // Some conditions only has operator and not value (e.g. customer logged in condition)
    if ( $value !== null ) {
      $cols[] = $value;
    }

    return implode( ' - ', $cols );
  }

  /**
   * Translate subset filter
   */
  private function translate_subset_filter( $condition ) {
    if ( strpos( $condition['subset_filter' ], 'shipping_class_' ) !== false ) {
      if ( strpos( $condition['subset_filter'], 'shipping_class_not_' ) !== false ) {
        $prefix = 'shipping_class_not_';
        $title = __( 'of products NOT in a shipping class', 'conditional-shipping-for-woocommerce' );
      } else if ( strpos( $condition['subset_filter'], 'shipping_class_' ) !== false ) {
        $prefix = 'shipping_class_';
        $title = __( 'of products in a shipping class', 'conditional-shipping-for-woocommerce' );
      }

      $shipping_class_id = str_replace( $prefix, '', $condition['subset_filter'] );

      if ( $shipping_class_id && ( $term = get_term_by( 'id', $shipping_class_id, 'product_shipping_class' ) ) ) {
        return sprintf( '%s - %s', $title, $term->name );
      }
    }

    if ( $condition['subset_filter'] === 'non_sale_products' ) {
      return __( 'of non-sale products', 'conditional-shipping-for-woocommerce' );
    }
    
    if ( $condition['subset_filter'] === 'sale_products' ) {
      return __( 'of sale products', 'conditional-shipping-for-woocommerce' );
    }

    return null;
  }

  /**
   * Get condition value depending on the type
   */
  private function translate_condition_value( $condition ) {
    switch( $condition['type'] ) {
      case 'subtotal':
      case 'items':
      case 'weight':
      case 'height_total':
      case 'length_total':
      case 'width_total':
      case 'volume':
      case 'product_weight':
      case 'product_height':
      case 'product_length':
      case 'product_width':
        return $condition['value'];
      case 'products':
        return implode( ', ', array_map( 'get_the_title', (array) $condition['product_ids'] ) );
      case 'shipping_class':
        return implode( ', ', $this->get_term_titles( (array) $condition['shipping_class_ids'], 'product_shipping_class' ) );
      case 'category':
        return implode( ', ', $this->get_term_titles( (array) $condition['category_ids'], 'product_cat' ) );
      case 'product_tags':
        return implode( ', ', $this->get_term_titles( (array) $condition['product_tags'], 'product_tag' ) );
      case 'product_attrs':
        return implode( ', ', $this->get_attr_titles( (array) $condition['product_attrs'] ) );
      case 'coupon':
        $coupon_ids = isset( $condition['coupon_ids'] ) ? (array) $condition['coupon_ids'] : [];
        return implode( ', ', array_map( 'wcs_get_coupon_title', $coupon_ids ) );
      case 'customer_authenticated':
        return null; // This condition doesn't has value, only operator
      case 'customer_role':
        return implode( ', ', $this->get_role_titles( $condition['user_roles'] ) );
      case 'billing_postcode':
      case 'shipping_postcode':
        return $condition['postcodes'];
      case 'billing_city':
      case 'shipping_city':
          return $condition['cities'];
      case 'billing_state':
      case 'shipping_state':
        return implode( ', ', $this->get_state_titles( $condition['states'] ) );
      case 'billing_country':
      case 'shipping_country':
        return implode( ', ', $this->get_country_titles( $condition['countries'] ) );
      case 'currency':
        return implode( ', ', (array) $condition['currencies'] );
      case 'weekdays':
        return implode( ', ', $this->get_weekday_titles( $condition['weekdays'] ) );
      case 'time':
        return $this->get_time_title( $condition );
      case 'date':
        return $condition['date'];
      default:
        return 'N/A';
    }
  }

  /**
   * Get term titles
   */
  private function get_term_titles( $ids, $taxonomy ) {
    $titles = [];
    foreach ( $ids as $id ) {
      $term = get_term_by( 'id', $id, $taxonomy );

      $titles[] = $term ? $term->name : __( 'N/A', 'conditional-shipping-for-woocommerce' );
    }

    return $titles;
  }

  /**
   * Get attribute titles
   */
  private function get_attr_titles( $condition_attrs ) {
    if ( ! $this->product_attrs ) {
      // Flatten attrs
      $this->product_attrs = [];
      foreach ( woo_conditional_product_attr_options() as $taxonomy_id => $attrs ) {
        foreach ( $attrs['attrs'] as $id => $label ) {
          $this->product_attrs[$id] = $label;
        }
      }
    }

    return $this->ids_to_list( $condition_attrs, $this->product_attrs );
  }

  /**
   * Get role titles
   */
  private function get_role_titles( $role_ids ) {
    if ( ! $this->customer_roles ) {
      $this->customer_roles = woo_conditional_shipping_role_options();
    }

    return $this->ids_to_list( $role_ids, $this->customer_roles );
  }

  /**
   * Get state titles
   */
  private function get_state_titles( $state_ids ) {
    if ( ! $this->states ) {
      $options = woo_conditional_shipping_state_options();
      foreach ( $options as $country_id => $states ) {
        foreach ( $states['states'] as $state_id => $state ) {
          $this->states["{$country_id}:{$state_id}"] = $state;
        }
      }
    }

    return $this->ids_to_list( $state_ids, $this->states );
  }

  /**
   * Get country titles
   */
  private function get_country_titles( $country_ids ) {
    if ( ! $this->countries ) {
      $this->countries = woo_conditional_shipping_country_options();
    }

    return $this->ids_to_list( $country_ids, $this->countries );
  }

  /**
   * Get weekday titles
   */
  private function get_weekday_titles( $weekdays ) {
    if ( ! $this->weekdays ) {
      $this->weekdays = woo_conditional_shipping_weekdays_options();
    }

    return $this->ids_to_list( $weekdays, $this->weekdays );
  }

  /**
   * Get time title
   */
  private function get_time_title( $condition ) {
    if ( ! $this->hours ) {
      $this->hours = woo_conditional_shipping_time_hours_options();
    }

    if ( ! $this->mins ) {
      $this->mins = woo_conditional_shipping_time_mins_options();
    }

    $hours = isset( $condition['time_hours'] ) ? $condition['time_hours'] : '0';
    $mins = isset( $condition['time_mins'] ) ? $condition['time_mins'] : '0';


    return sprintf( '%s:%s', $this->hours[$hours], $this->mins[$mins] );
  }

  /**
   * Convert IDs to human-readable list from options
   */
  private function ids_to_list( $values, $options ) {
    $titles = [];

    foreach ( $values as $value ) {
      $titles[] = isset( $options[$value] ) ? $options[$value] : __( 'N/A', 'conditional-shipping-for-woocommerce' );
    }

    return $titles;
  }
}
