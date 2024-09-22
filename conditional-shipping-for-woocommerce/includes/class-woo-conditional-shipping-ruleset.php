<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Woo_Conditional_Shipping_Ruleset {
  private $post_id;
  private $post;
  private $debug;

  /**
   * Constructor
   */
  public function __construct( $post_id = false ) {
    $this->post_id = $post_id;

    $this->debug = Woo_Conditional_Shipping_Debug::instance();
  }

  /**
   * Get ID
   */
  public function get_id() {
    return $this->post_id;
  }

  /**
   * Get title
   */
  public function get_title( $context = 'view' ) {
    $post = $this->get_post();

    if ( $post && $post->post_title ) {
      return $post->post_title;
    }

    if ( $context === 'edit' ) {
      return '';
    }

    return __( 'Ruleset', 'conditional-shipping-for-woocommerce' );
  }

  /**
   * Get row actions
   */
  public function get_row_actions() {
    return [
      'edit' => [
        'title' => __( 'Edit', 'conditional-shipping-for-woocommerce' ),
        'url' => $this->get_admin_edit_url(),
        'class' => 'wcs-ruleset-edit',
      ],
      'delete' => [
        'title' => __( 'Delete', 'conditional-shipping-for-woocommerce' ),
        'url' => $this->get_admin_delete_url(),
        'class' => 'wcs-ruleset-delete',
      ],
      'clone' => [
        'title' => __( 'Duplicate', 'conditional-shipping-for-woocommerce' ),
        'url' => $this->get_admin_duplicate_url(),
        'class' => 'wcs-ruleset-duplicate',
      ],
    ];
  }

  /**
   * Get admin edit URL
   */
  public function get_admin_edit_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
    ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

    return $url;
  }

  /**
   * Get admin delete URL
   */
  public function get_admin_delete_url() {
    $url = add_query_arg( array(
      'ruleset_id' => $this->post_id,
      'action' => 'delete',
    ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

    return $url;
  }

  /**
   * Get admin duplicate URL
   */
  public function get_admin_duplicate_url() {
    $url = add_query_arg( [
      'ruleset_id' => $this->post_id,
      'action' => 'duplicate',
    ], admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

    return $url;
  }

  /**
   * Get post
   */
  public function get_post() {
    if ( ! $this->post ) {
      $this->post = get_post( $this->post_id );
    }

    return $this->post;
  }

  /**
   * Get whether or not ruleset is enabled
   */
  public function get_enabled() {
    $enabled = get_post_meta( $this->post_id, '_wcs_enabled', true );
    $enabled_exists = metadata_exists( 'post', $this->post_id, '_wcs_enabled' );

    // Metadata doesn't exist yet so we assume it's enabled
    if ( ! $enabled_exists ) {
      return true;
    }

    return $enabled === 'yes';
  }

  /**
   * Get products which are selected in conditions
   */
  public function get_products() {
    $product_ids = array();

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['product_ids'] ) && is_array( $condition['product_ids'] ) ) {
        $product_ids = array_merge( $product_ids, $condition['product_ids'] );
      }
    }

    $products = array();
    foreach ( $product_ids as $product_id ) {
      $product = wc_get_product( $product_id );
      if ( $product ) {
        $products[$product_id] = wp_kses_post( $product->get_formatted_name() );
      }
    }

    return $products;
  }

  /**
   * Get coupons which are selected in conditions
   */
  public function get_coupons() {
    $coupon_ids = [];

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['coupon_ids'] ) && is_array( $condition['coupon_ids'] ) ) {
        $coupon_ids = array_merge( $coupon_ids, $condition['coupon_ids'] );
      }
    }

    $general_options = [
      '_all' => __( '- All coupons -', 'conditional-shipping-for-woocommerce' ),
      '_free_shipping' => __( '- Free shipping coupons -', 'conditional-shipping-for-woocommerce' ),
    ];

    $coupons = [];
    foreach ( $coupon_ids as $coupon_id ) {
      if ( isset( $general_options[$coupon_id] ) ) {
        $coupons[$coupon_id] = $general_options[$coupon_id];
      } else {
        $coupon_code = wc_get_coupon_code_by_id( $coupon_id );
        if ( $coupon_code ) {
          $coupons[$coupon_id] = $coupon_code;
        }
      }
    }

    return $coupons;
  }

  /**
   * Get tags which are selected in conditions
   */
  public function get_tags() {
    $tag_ids = [];

    foreach ( $this->get_conditions() as $condition ) {
      if ( isset( $condition['product_tags'] ) && is_array( $condition['product_tags'] ) ) {
        $tag_ids = array_merge( $tag_ids, $condition['product_tags'] );
      }
    }

    $tags = [];
    foreach ( $tag_ids as $tag_id ) {
      $tag = get_term( $tag_id, 'product_tag' );
      if ( $tag ) {
        $tags[$tag->term_id] = wp_kses_post( $tag->name );
      }
    }

    return $tags;
  }
  
  /**
   * Get conditions for the ruleset
   */
  public function get_conditions() {
    $conditions = get_post_meta( $this->post_id, '_wcs_conditions', true );

    if ( ! $conditions ) {
      return array();
    }

    return (array) $conditions;
  }

  /**
   * Get actions for the ruleset
   */
  public function get_actions( $translated = false ) {
    $actions = get_post_meta( $this->post_id, '_wcs_actions', true );

    if ( ! $actions || ! is_array( $actions ) ) {
      return [];
    }

    // Apply translations
    if ( $translated ) {
      foreach ( $actions as $key => $action ) {
        $actions[$key] = wcs_translate_action( $action );
      }
    }

    return $actions;
  }

  /**
   * Get operator for conditions (AND / OR)
   */
  public function get_conditions_operator() {
    $operator = get_post_meta( $this->post_id, '_wcs_operator', true );

    if ( $operator && in_array( $operator, [ 'and', 'or' ], true ) ) {
      return $operator;
    }

    return 'and';
  }

  /**
   * Check if conditions pass for the given package
   */
  public function validate( $package ) {
    // Some 3rd party plugins may deliver empty $package which causes
    // error notices. If so, we fill array manually.
    if ( ! is_array( $package ) ) {
      $package = array();
    }

    if ( ! isset( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
      $package['contents'] = array();
    }

    // Not all shipping plugins provide package contents, in that case we will assume
    // there is only one package and that is equal to cart contents
    if ( empty( $package['contents'] ) && WC()->cart ) {
      $package['contents'] = WC()->cart->get_cart();
    }

    $filters = woo_conditional_shipping_filters();

    $results = [];
    foreach ( $this->get_conditions() as $index => $condition ) {
      if ( isset( $condition['type'] ) && ! empty( $condition['type'] ) ) {
        $type = $condition['type'];

        $function = "filter_{$type}";

        if ( isset( $filters[$type] ) && isset( $filters[$type]['callback'] ) ) {
          $callable = $filters[$type]['callback'];
        } else if ( class_exists( 'Woo_Conditional_Shipping_Filters_Pro' ) && method_exists( 'Woo_Conditional_Shipping_Filters_Pro', $function ) ) {
          $callable = array( 'Woo_Conditional_Shipping_Filters_Pro', $function );
        } else {
          $callable = array( 'Woo_Conditional_Shipping_Filters', $function );
        }

        $results[$index] = (bool) call_user_func( $callable, $condition, $package );

        $this->debug->add_condition( $this->get_id(), $index, $condition, $results[$index] );
      }
    }

    // If operator is OR, it is enough that one condition passed
    if ( $this->get_conditions_operator() === 'or' ) {
      $passed = in_array( false, $results, true ) === true;
    } else {
      $passed = in_array( true, $results, true ) === false;
    }
    
    $this->debug->add_result( $this->get_id(), $passed );

    return $passed;
  }
}
