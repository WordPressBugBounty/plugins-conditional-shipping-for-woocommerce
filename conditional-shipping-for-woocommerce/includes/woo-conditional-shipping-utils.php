<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get rulesets
 * 
 * @return Woo_Conditional_Shipping_Ruleset[]
 */
function woo_conditional_shipping_get_rulesets( $only_enabled = false ) {
	$args = array(
		'post_status' => array( 'publish' ),
		'post_type' => 'wcs_ruleset',
		'posts_per_page' => -1,
		'orderby' => 'title',
		'order' => 'ASC',
	);

  $posts = get_posts( $args );
  
  $rulesets = array();
  foreach ( $posts as $post ) {
    $ruleset = new Woo_Conditional_Shipping_Ruleset( $post->ID );

    if ( ! $only_enabled || $ruleset->get_enabled() ) {
      $rulesets[] = $ruleset;
    }
  }

  $ordering = get_option( 'wcs_ruleset_order', false );
  if ( $ordering && is_array( $ordering ) ) {
    $order_end = 999;
    $ordered_rulesets = [];

    foreach ( $rulesets as $ruleset ) {
      $ruleset_id = $ruleset->get_id();

      if ( isset( $ordering[ $ruleset_id ] ) && is_numeric( $ordering[ $ruleset_id ] ) ) {
        $ordered_rulesets[ $ordering[ $ruleset_id ] ] = $ruleset;
      } else {
        $ordered_rulesets[ $order_end ] = $ruleset;
        $order_end++;
      }
    }

    ksort( $ordered_rulesets );

    $rulesets = $ordered_rulesets;
  }

  return $rulesets;
}

/**
 * Get a list of operators
 */
function woo_conditional_shipping_operators() {
  return array(
    'gt' => __( 'greater than', 'conditional-shipping-for-woocommerce' ),
    'gte' => __( 'greater than or equal', 'conditional-shipping-for-woocommerce' ),
    'lt' => __( 'less than', 'conditional-shipping-for-woocommerce' ),
    'lte' => __( 'less than or equal', 'conditional-shipping-for-woocommerce' ),
    'e' => __( 'equals', 'conditional-shipping-for-woocommerce' ),
    'in' => __( 'include', 'conditional-shipping-for-woocommerce' ),
    'exclusive' => __( 'include only', 'conditional-shipping-for-woocommerce' ),
    'notin' => __( 'exclude', 'conditional-shipping-for-woocommerce' ),
    'allin' => __( 'include all', 'conditional-shipping-for-woocommerce' ),
    'is' => __( 'is', 'conditional-shipping-for-woocommerce' ),
    'isnot' => __( 'is not', 'conditional-shipping-for-woocommerce' ),
    'exists' => __( 'is not empty', 'conditional-shipping-for-woocommerce' ),
    'notexists' => __( 'is empty', 'conditional-shipping-for-woocommerce' ),
    'contains' => __( 'contains', 'conditional-shipping-for-woocommerce' ),
    'loggedin' => __( 'logged in', 'conditional-shipping-for-woocommerce' ),
    'loggedout' => __( 'logged out', 'conditional-shipping-for-woocommerce' ),
  );
}

/**
 * Get list of price modes
 */
function wcs_get_price_modes() {
  $currency_symbol = get_woocommerce_currency_symbol();

  return [
    'fixed' => $currency_symbol,
    'per_weight_unit' => sprintf( __( '%s per %s', 'conditional-shipping-for-woocommerce' ), $currency_symbol, get_option( 'woocommerce_weight_unit' ) ),
    'per_piece' => sprintf( __( '%s per piece', 'conditional-shipping-for-woocommerce' ), $currency_symbol ),
    'pct' => __( '% of subtotal', 'conditional-shipping-for-woocommerce' ),
    'pct_shipping' => __( '% of shipping', 'conditional-shipping-for-woocommerce' ),
  ];
}

/**
 * Get a list of subset filters
 */
function woo_conditional_shipping_subset_filters() {
  return apply_filters( 'woo_conditional_shipping_subset_filters', [] );
}

/**
 * Get a list of filter groups
 */
function woo_conditional_shipping_filter_groups() {
  return apply_filters( 'woo_conditional_shipping_filters', array(
    'cart' => array(
      'title' => __( 'Cart', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'subtotal' => array(
          'title' => __( 'Subtotal', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
        'products' => array(
          'title' => __( 'Products', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'in', 'notin', 'exclusive', 'allin' ),
        ),
        'items' => [
          'title' => __( 'Number of Items', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ],
        'shipping_class' => [
          'title' => __( 'Shipping Classes', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'in', 'notin', 'exclusive', 'allin' ],
          'pro' => true,
        ],
        'category' => [
          'title' => __( 'Categories', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'in', 'notin', 'exclusive', 'allin' ],
          'pro' => true,
        ],
        'product_tags' => [
          'title' => __( 'Product Tags', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'in', 'exclusive', 'notin' ],
          'pro' => true,
        ],
        'product_attrs' => [
          'title' => __( 'Product Attributes', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'in', 'notin', 'exclusive' ],
          'pro' => true,
        ],
        'coupon' => [
          'title' => __( 'Coupons', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'in', 'notin' ],
          'pro' => true,
        ],
        'currency' => [
          'title' => __( 'Currency', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'is', 'isnot' ],
          'pro' => true,
        ],
      )
    ),
    'package_measurements' => array(
      'title' => __( 'Package Measurements', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'weight' => array(
          'title' => sprintf( __( 'Total Weight (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
        'height_total' => array(
          'title' => sprintf( __( 'Total Height (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
        'length_total' => array(
          'title' => sprintf( __( 'Total Length (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
        'width_total' => array(
          'title' => sprintf( __( 'Total Width (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
        'volume' => array(
          'title' => sprintf( __( 'Total Volume (%s&sup3;)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
        ),
      )
    ),
    'product_measurements' => [
      'title' => __( 'Product Measurements', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'product_weight' => array(
          'title' => sprintf( __( 'Weight (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_weight_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ),
        'product_height' => array(
          'title' => sprintf( __( 'Height (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ),
        'product_length' => array(
          'title' => sprintf( __( 'Length (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ),
        'product_width' => array(
          'title' => sprintf( __( 'Width (%s)', 'conditional-shipping-for-woocommerce' ), get_option( 'woocommerce_dimension_unit' ) ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ),
      )
    ],
    'customer' => [
      'title' => __( 'Customer', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'customer_authenticated' => array(
          'title' => __( 'Logged in / out', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'loggedin', 'loggedout' ),
          'pro' => true,
        ),
        'customer_role' => array(
          'title' => __( 'Role', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
      ),
    ],
    'billing_address' => [
      'title' => __( 'Billing Address', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'billing_city' => array(
          'title' => __( 'City (billing)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'billing_postcode' => array(
          'title' => __( 'Postcode (billing)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'billing_state' => array(
          'title' => __( 'State (billing)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'billing_country' => array(
          'title' => __( 'Country (billing)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
      ),
    ],
    'shipping_address' => [
      'title' => __( 'Shipping Address', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'shipping_city' => array(
          'title' => __( 'City (shipping)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'shipping_postcode' => array(
          'title' => __( 'Postcode (shipping)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'shipping_state' => array(
          'title' => __( 'State (shipping)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'shipping_country' => array(
          'title' => __( 'Country (shipping)', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
      ),
    ],
    'misc' => [
      'title' => __( 'Misc', 'conditional-shipping-for-woocommerce' ),
      'filters' => array(
        'date' => array(
          'title' => __( 'Date', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte', 'e' ],
          'pro' => true,
        ),
        'weekdays' => array(
          'title' => __( 'Weekday', 'conditional-shipping-for-woocommerce' ),
          'operators' => array( 'is', 'isnot' ),
          'pro' => true,
        ),
        'time' => array(
          'title' => __( 'Time', 'conditional-shipping-for-woocommerce' ),
          'operators' => [ 'gt', 'gte', 'lt', 'lte' ],
          'pro' => true,
        ),
      )
    ]
  ) );
}

/**
 * Get a list of filters
 */
function woo_conditional_shipping_filters() {
  $groups = woo_conditional_shipping_filter_groups();

  $filters = array();
  foreach ( $groups as $group ) {
    foreach ( $group['filters'] as $key => $filter ) {
      $filters[$key] = $filter;
    }
  }

  return $filters;
}

/**
 * Get a list of actions
 */
function woo_conditional_shipping_actions() {
  return apply_filters( 'woo_conditional_shipping_actions', [
    'disable_shipping_methods' => [
      'title' => __( 'Disable shipping methods', 'conditional-shipping-for-woocommerce' ),
    ],
    'enable_shipping_methods' => [
      'title' => __( 'Enable shipping methods', 'conditional-shipping-for-woocommerce' ),
    ],
    'set_price' => [
      'title' => __( 'Set shipping method price', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
    'increase_price' => [
      'title' => __( 'Increase shipping method price', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
    'decrease_price' => [
      'title' => __( 'Decrease shipping method price', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
    'set_title' => [
      'title' => __( 'Set shipping method title', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
    'custom_error_msg' => [
      'title' => __( 'Set custom no shipping message', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
    'shipping_notice' => [
      'title' => __( 'Set shipping notice', 'conditional-shipping-for-woocommerce' ),
      'pro' => true,
    ],
  ] );
}

/**
 * Currency options
 */
function wcs_currency_options() {
  return get_woocommerce_currencies();
}

/**
 * Country options
 */
function woo_conditional_shipping_country_options() {
  $countries_obj = new WC_Countries();

  return $countries_obj->get_countries();
}

/**
 * Get coupon title
 */
function wcs_get_coupon_title( $coupon_id ) {
  $general_options = [
    '_all' => __( '- All coupons -', 'conditional-shipping-for-woocommerce' ),
    '_free_shipping' => __( '- Free shipping coupons -', 'conditional-shipping-for-woocommerce' ),
  ];

  if ( isset( $general_options[$coupon_id] ) ) {
    return $general_options[$coupon_id];
  }

  return get_the_title( $coupon_id );
}

/**
 * State options
 */
function woo_conditional_shipping_state_options() {
  $countries_obj = new WC_Countries();
  $countries = $countries_obj->get_countries();
  $states = array_filter( $countries_obj->get_states() );

  $options = [];

  foreach ( $states as $country_id => $state_list ) {
    $options[$country_id] = [
      'states' => $state_list,
      'country' => $countries[$country_id],
    ];
  }

  // Move US as first as it is the most commonly used
  $us = $options['US'];
  unset( $options['US'] );
  $options = ['US' => $us] + $options;

  return $options;
}

/**
 * Get shipping method options
 */
function woo_conditional_shipping_get_shipping_method_options() {
  $shipping_zones = WC_Shipping_Zones::get_zones();
  $shipping_zones[] = new WC_Shipping_Zone( 0 );

  $zones_count = count( $shipping_zones );

  $options = [];

  foreach ( $shipping_zones as $shipping_zone ) {
    if ( is_array( $shipping_zone ) && isset( $shipping_zone['zone_id'] ) ) {
      $shipping_zone = WC_Shipping_Zones::get_zone( $shipping_zone['zone_id'] );
    } else if ( ! is_object( $shipping_zone ) ) {
      // Skip
      continue;
    }

    $zone_id = $shipping_zone->get_id();

    $options['_all'] = [
      'title' => __( 'General', 'conditional-shipping-for-woocommerce' ),
      'options' => [
        '_all' => [
          'title' => __( 'All shipping methods', 'conditional-shipping-for-woocommerce' )
        ],
        '_name_match' => [
          'title' => __( 'Match by name', 'conditional-shipping-for-woocommerce' )
        ],
      ],
    ];

    $options[$zone_id] = array(
      'title' => $shipping_zone->get_zone_name(),
      'options' => array(),
    );

    foreach ( $shipping_zone->get_shipping_methods() as $instance_id => $shipping_method ) {
      if ( $zones_count > 1 ) {
        $title = sprintf( '%s (%s)', $shipping_method->title, $shipping_zone->get_zone_name() );
      } else {
        $title = $shipping_method->title;
      }

      $options[$zone_id]['options'][$instance_id] = array(
        'title' => $title,
      );
    }
  }

  // Remove zones with no shipping methods
  $options = array_filter( $options, function( $option ) {
    return ! empty( $option['options'] );
  } );

  $options = apply_filters( 'woo_conditional_shipping_method_options', $options );

  return $options;
}

/**
 * Check if shipping method is selected
 */
function wcs_method_selected( $method_title, $instance_id, $action ) {
  $shipping_method_ids = isset( $action['shipping_method_ids'] ) ? (array) $action['shipping_method_ids'] : [];
  $names = isset( $action['shipping_method_name_match'] ) ? $action['shipping_method_name_match'] : false;

  $shipping_method_ids = array_map( 'strval', $shipping_method_ids );

  $passes = [
    'all' => in_array( '_all', $shipping_method_ids, true ),
    'name_match' => in_array( '_name_match', $shipping_method_ids, true ) && wcs_method_name_match( $method_title, $names ),
    'instance' => ( $instance_id !== false && in_array( strval( $instance_id ), $shipping_method_ids, true ) ),
  ];

  return in_array( true, $passes, true );
}

/**
 * Check if shipping method title matches the names
 */
function wcs_method_name_match( $title, $names ) {
  $title = strtolower( trim( strval( $title ) ) );

  // Split names by newline as they are entered one per line
  $names = array_filter( array_map( 'strtolower', array_map( 'wc_clean', explode( "\n", $names ) ) ) );

  // Check if some name matches
  foreach ( $names as $name ) {
    if ( strpos( $name, '*' ) !== false ) {
      if ( fnmatch( $name, $title ) ) {
        return true;
      }
    } else {
      if ( $name === $title ) {
        return true;
      }
    }
  }

  return false;
}

/**
 * Get product attribute options
 */
function woo_conditional_product_attr_options() {
  $options = array();

  $taxonomies = wc_get_attribute_taxonomies();

  foreach ( $taxonomies as $key => $taxonomy ) {
    $options[$taxonomy->attribute_id] = array(
      'label' => $taxonomy->attribute_label,
      'attrs' => array(),
    );

    $taxonomy_id = wc_attribute_taxonomy_name( $taxonomy->attribute_name );
    if ( taxonomy_exists( $taxonomy_id ) ) {
      $terms = get_terms( $taxonomy_id, 'hide_empty=0' );

      foreach ( $terms as $term ) {
        $attribute_id = sprintf( 'pa_%s:%s', $taxonomy->attribute_name, $term->slug );
        $options[$taxonomy->attribute_id]['attrs'][$attribute_id] = $term->name;
      }
    }
  }

  return $options;
}

/**
 * Get shipping class options
 */
function woo_conditional_shipping_get_shipping_class_options() {
  $shipping_classes = WC()->shipping->get_shipping_classes();
  $shipping_class_options = array();
  foreach ( $shipping_classes as $shipping_class ) {
    $shipping_class_options[$shipping_class->term_id] = $shipping_class->name;
  }

  return $shipping_class_options;
}

/**
 * Get category options
 */
function woo_conditional_shipping_get_category_options() {
  $categories = get_terms( 'product_cat', array(
    'hide_empty' => false,
    'suppress_filter' => true,
  ) );

  $sorted = array();
  woo_conditional_shipping_sort_terms_hierarchicaly( $categories, $sorted );

  // Flatten hierarchy
  $options = array();
  woo_conditional_shipping_flatten_terms( $options, $sorted );

  return $options;
}

/**
 * Output term tree into a select field options
 */
function woo_conditional_shipping_flatten_terms( &$options, $cats, $depth = 0 ) {
  foreach ( $cats as $cat ) {
    if ( $depth > 0 ) {
      $prefix = str_repeat( ' - ', $depth );
      $options[$cat->term_id] = "{$prefix} {$cat->name}";
    } else {
      $options[$cat->term_id] = "{$cat->name}";
    }

    if ( isset( $cat->children ) && ! empty( $cat->children ) ) {
      woo_conditional_shipping_flatten_terms( $options, $cat->children, $depth + 1 );
    }
  }
}

/**
 * Sort categories hierarchically
 */
function woo_conditional_shipping_sort_terms_hierarchicaly( Array &$cats, Array &$into, $parentId = 0 ) {
  foreach ( $cats as $i => $cat ) {
    if ( $cat->parent == $parentId ) {
      $into[$cat->term_id] = $cat;
      unset( $cats[$i] );
    }
  }

  foreach ( $into as $topCat ) {
    $topCat->children = array();
    woo_conditional_shipping_sort_terms_hierarchicaly( $cats, $topCat->children, $topCat->term_id );
  }
}

/**
 * Load all roles to be used in a select field
 */
function woo_conditional_shipping_role_options() {
  global $wp_roles;
  
  $options = array();

  if ( is_a( $wp_roles, 'WP_Roles' ) && isset( $wp_roles->roles ) ) {
    $roles = $wp_roles->roles;

    foreach ( $roles as $role => $details ) {
      $name = translate_user_role( $details['name'] );
      $options[$role] = $name;
    }
  }

  return $options;
}

/**
 * Options for weekday filter
 */
function woo_conditional_shipping_weekdays_options() {
  $options = array();

  for ( $i = 0; $i < 7; $i++ ) {
    $timestamp = strtotime( 'monday' ) + $i * 86400;

    $options[$i + 1] = date_i18n( 'l', $timestamp );
  }

  return $options;
}

/**
 * Options for time hours filter
 */
function woo_conditional_shipping_time_hours_options() {
  $options = array();

  for ( $i = 0; $i < 24; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 3600;

    $options[$i] = date_i18n( 'H', $timestamp );
  }

  return $options;
}

/**
 * Options for time minutes filter
 */
function woo_conditional_shipping_time_mins_options() {
  $options = array();

  for ( $i = 0; $i < 60; $i++ ) {
    $timestamp = strtotime( 'monday midnight' ) + $i * 60;

    $options[$i] = date_i18n( 'i', $timestamp );
  }

  return $options;
}

/**
 * Get shipping method title by instance ID
 */
function woo_conditional_shipping_get_method_title( $instance_id ) {
  // Simple caching mechanism as this can take quite a while
  static $options = [];
  if ( empty( $options ) ) {
    $options = woo_conditional_shipping_get_shipping_method_options();
  }

  foreach ( $options as $zone_id => $data ) {
    foreach ( $data['options'] as $id => $option ) {
      if ( $instance_id == $id ) {
        return $option['title'];
      }
    }
  }

  return $instance_id;
}

/**
 * Get label for ruleset operator
 */
function wcs_get_ruleset_operator_label( $ruleset_id ) {
  $operator = get_post_meta( $ruleset_id, '_wcs_operator', true );

  switch ( $operator ) {
    case 'or':
      return __( 'One condition has to pass (OR)', 'conditional-shipping-for-woocommerce' );
    default:
      return __( 'All conditions have to pass (AND)', 'conditional-shipping-for-woocommerce' );
  }
}

/**
 * Get shipping zone ULR
 */
function woo_conditional_shipping_get_zone_url( $zone_id ) {
  return add_query_arg( array(
    'page' => 'wc-settings',
    'tab' => 'shipping',
    'zone_id' => $zone_id,
  ), admin_url( 'admin.php' ) );
}

/**
 * Get shipping method instance
 */
function woo_conditional_shipping_method_get_instance( $instance_id ) {
  if ( ! ctype_digit( strval( $instance_id ) ) ) {
    return null;
  }

  global $wpdb;
  $results = $wpdb->get_results( $wpdb->prepare( "SELECT zone_id, method_id, instance_id, method_order, is_enabled FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE instance_id = %d LIMIT 1;", $instance_id ) );

  if ( count( $results ) <> 1 ) {
    return null;
  }

  return reset( $results );
}

/**
 * Get action title by action ID
 */
function woo_conditional_shipping_action_title( $action_id ) {
  $actions = woo_conditional_shipping_actions();

  if ( isset( $actions[$action_id] ) ) {
    return $actions[$action_id]['title'];
  }

  return __( 'N/A', 'conditional-shipping-for-woocommerce' );
}

/**
 * Format ruleset IDs into a list of links
 */
function woo_conditional_shipping_format_ruleset_ids( $ids ) {
  $items = array();

  foreach ( $ids as $id ) {
    $ruleset = new Woo_Conditional_Shipping_Ruleset( $id );

    if ( $ruleset->get_post() ) {
      $items[] = sprintf( '<a href="%s" target="_blank">%s</a>', $ruleset->get_admin_edit_url(), $ruleset->get_title() );
    }
  }

  return implode( ', ', $items );
}

/**
 * Get ruleset admin edit URL
 */
function wcs_get_ruleset_admin_url( $ruleset_id ) {
  $url = add_query_arg( array(
    'ruleset_id' => $ruleset_id,
  ), admin_url( 'admin.php?page=wc-settings&tab=shipping&section=woo_conditional_shipping' ) );

  return $url;
}

/**
 * Get product categories
 */
function woo_conditional_shipping_get_product_cats( $product_id ) {
  $cat_ids = array();

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_cat' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $cat_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product categories
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_cat' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $cat_ids[$term->term_id] = true;
        }
      }
    }

    // Finally add all parent terms
    if ( apply_filters( 'woo_conditional_shipping_incl_parent_cats', true ) ) {
      foreach ( array_keys( $cat_ids ) as $term_id ) {
        $ancestors = (array) get_ancestors( $term_id, 'product_cat', 'taxonomy' );

        foreach ( $ancestors as $ancestor_id ) {
          $cat_ids[$ancestor_id] = true;
        }
      }
    }
  }

  $cat_ids = array_keys( $cat_ids );

  // Special handling for WPML
  if ( function_exists( 'icl_object_id' ) ) {
    $default_lang = apply_filters( 'wpml_default_language', NULL );

    foreach ( $cat_ids as $key => $cat_id ) {
      $orig_cat_id = apply_filters( 'wpml_object_id', $cat_id, 'product_cat', true, $default_lang );

      $cat_ids[$key] = $orig_cat_id;
    }
  }

  return $cat_ids;
}

/**
 * Get product tags
 */
function wcs_get_product_tags( $product_id ) {
  $tag_ids = [];

  if ( $product = wc_get_product( $product_id ) ) {
    $terms = get_the_terms( $product->get_id(), 'product_tag' );
    if ( $terms ) {
      foreach ( $terms as $term ) {
        $tag_ids[$term->term_id] = true;
      }
    }

    // If this is variable product, append parent product categories
    if ( $product->get_parent_id() ) {
      $terms = get_the_terms( $product->get_parent_id(), 'product_tag' );
      if ( $terms ) {
        foreach ( $terms as $term ) {
          $tag_ids[$term->term_id] = true;
        }
      }
    }
  }

  $tag_ids = array_keys( $tag_ids );

  // Special handling for WPML
  if ( function_exists( 'icl_object_id' ) ) {
    $default_lang = apply_filters( 'wpml_default_language', NULL );

    foreach ( $tag_ids as $key => $tag_id ) {
      $orig_tag_id = apply_filters( 'wpml_object_id', $tag_id, 'product_tag', true, $default_lang );

      $tag_ids[$key] = $orig_tag_id;
    }
  }

  return $tag_ids;
}

/**
 * Get cart function
 * 
 * In some cases cart is not always available so we cannot trust WC()->cart
 * to exist. If cart doesn't exist, return sensible default value
 */
function wcs_get_cart_func( $func = 'get_cart' ) {
  $cart = WC()->cart;
  $default = false;

  switch ( $func ) {
    case 'get_cart':
    case 'get_applied_coupons':
      $default = array();
      break;
    case 'display_prices_including_tax':
      $default = false;
      break;
    case 'get_displayed_subtotal':
    case 'get_discount_total':
    case 'get_discount_tax':
    case 'get_cart_contents_count':
      $default = 0;
      break;
  }

  return $cart ? call_user_func( [$cart, $func] ) : $default;
}

/**
 * Escape text to be used in JS template
 */
function wcs_esc_html( $text ) {
  // Escape curly braces because they will be intepreted as JS variables
  $text = str_replace( '{', '&#123;', $text );
  $text = str_replace( '}', '&#125;', $text );

  // Normal HTML escape
  return esc_html( $text );
}

/**
 * Get condition or action title
 */
function wcs_get_control_title( $control ) {
  if ( isset( $control['pro'] ) && $control['pro'] ) {
    return sprintf( __( '%s (Pro)', 'conditional-shipping-for-woocommerce' ), $control['title'] );
  }

  return $control['title'];
}

/**
 * Get cart weight
 */
function wcs_get_cart_weight() {
  if ( function_exists( 'WC' ) ) {
    $cart = WC()->cart;
    
    if ( is_callable( [ $cart, 'get_cart' ] ) ) {
      $weight = 0;
      foreach ( $cart->get_cart() as $item ) {
        $product = $item['data'];

        if ( ! $product->needs_shipping() ) {
          continue;
        }
  
        $weight += floatval( $product->get_weight() ) * floatval( $item['quantity'] );
      }

      return $weight;
    }
  }

  return 0;
}

/**
 * Check if WPML has translatable strings for this plugin
 */
function wcs_wpml_has_strings() {
  if ( function_exists( 'icl_st_get_contexts' ) ) {
    $contexts = icl_st_get_contexts( false );

    if ( is_array( $contexts ) ) {
      foreach ( $contexts as $context ) {
        if ( is_object( $context ) && isset( $context->context ) && $context->context === 'WooCommerce Conditional Shipping Pro' ) {
          return true;
        }
      }
    }
  }

  return false;
}

/**
 * Translate action with WPML
 */
function wcs_translate_action( $action ) {
  if ( function_exists( 'icl_object_id' ) && isset( $action['guid'] ) ) {
    if ( $action['type'] === 'set_title' ) {
      $action['title'] = apply_filters( 'wpml_translate_single_string', $action['title'], 'WooCommerce Conditional Shipping Pro', sprintf( 'Shipping method title (GUID: %s)', $action['guid'] ) );
    }

    if ( $action['type'] === 'shipping_notice' ) {
      $action['notice'] = apply_filters( 'wpml_translate_single_string', $action['notice'], 'WooCommerce Conditional Shipping Pro', sprintf( 'Shipping notice (GUID: %s)', $action['guid'] ) );
    }

    if ( $action['type'] === 'custom_error_msg' ) {
      $action['error_msg'] = apply_filters( 'wpml_translate_single_string', $action['error_msg'], 'WooCommerce Conditional Shipping Pro', sprintf( 'No shipping message (GUID: %s)', $action['guid'] ) );
    }
  }

  return $action;
}
