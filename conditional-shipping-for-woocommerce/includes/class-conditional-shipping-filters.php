<?php

/**
 * Prevent direct access to the script.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Woo_Conditional_Shipping_Filters' ) ) {
class Woo_Conditional_Shipping_Filters {
  public static function filter_weight( $condition, $package ) {
		$package_weight = self::calculate_package_weight( $package, $condition );

		if ( isset( $condition['value'] ) ) {
			$weight = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $package_weight, $weight, $condition['operator'] );
		}

		return FALSE;
  }

  public static function filter_height_total( $condition, $package ) {
		$package_height = self::calculate_package_height( $package );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$height = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $package_height, $height, $condition['operator'] );
		}

		return FALSE;
  }

  public static function filter_length_total( $condition, $package ) {
		$package_length = self::calculate_package_length( $package );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$length = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $package_length, $length, $condition['operator'] );
		}

		return FALSE;
  }

  public static function filter_width_total( $condition, $package ) {
		$package_width = self::calculate_package_width( $package );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$width = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $package_width, $width, $condition['operator'] );
		}

		return FALSE;
	}
	
  public static function filter_volume( $condition, $package ) {
		$package_volume = self::calculate_package_volume( $package, $condition );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$volume = self::parse_number( $condition['value'] );

			return ! self::compare_numeric_value( $package_volume, $volume, $condition['operator'] );
		}

		return FALSE;
	}

  public static function filter_subtotal( $condition, $package ) {
		$cart_subtotal = self::get_cart_subtotal( $condition );

		if ( isset( $condition['value'] ) && ! empty( $condition['value'] ) ) {
			$subtotal = self::parse_number( $condition['value'] );
			$subtotal = apply_filters( 'wcs_convert_price', $subtotal );

			return ! self::compare_numeric_value( $cart_subtotal, $subtotal, $condition['operator'] );
		}

		return FALSE;
  }

  public static function filter_products( $condition, $package ) {
		if ( isset( $condition['product_ids'] ) && ! empty( $condition['product_ids'] ) ) {
			$condition_product_ids = self::merge_product_children_ids( $condition['product_ids'] );

			$products = self::get_cart_products( $package );

			// Special handling for WPML
			if ( function_exists( 'icl_object_id' ) ) {
				$default_lang = apply_filters( 'wpml_default_language', NULL );
				foreach ( $products as $product_id => $product ) {
					$orig_id = apply_filters( 'wpml_object_id', $product_id, $product->post_type, true, $default_lang );

					$products[$orig_id] = $product;
				}
			}

			if ( ! empty( $products ) ) {
				$product_ids = array_keys( $products );

				return ! self::group_comparison( $product_ids, $condition_product_ids, $condition['operator'] );
			}
		}

		return FALSE;
	}
	
	/**
	 * Get subtotal of the cart with possible subset filters
	 */
	public static function get_cart_subtotal( $condition = false ) {
		if ( $condition && isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
			return self::get_subset_subtotal( $condition );
		}

		$total = wcs_get_cart_func( 'get_displayed_subtotal' );

		if ( $condition && isset( $condition['subtotal_includes_coupons'] ) && $condition['subtotal_includes_coupons'] && method_exists( WC()->cart, 'get_discount_total' ) ) {
			$total -= floatval( wcs_get_cart_func( 'get_discount_total' ) );

			if ( wcs_get_cart_func( 'display_prices_including_tax' ) ) {
				$total -= floatval( wcs_get_cart_func( 'get_discount_tax' ) );
			}
		}

		$total = round( $total, wc_get_price_decimals() );

		return $total;
	}

	/**
	 * Get subtotal for a subset of cart items
	 */
	private static function get_subset_subtotal( $condition ) {
		$subtotal = 0;

		$subtotal_includes_coupons = isset( $condition['subtotal_includes_coupons'] ) && $condition['subtotal_includes_coupons'];

		$items = self::get_subset_items( $condition );

		$incl_tax = wcs_get_cart_func( 'display_prices_including_tax' );

		foreach ( $items as $key => $item ) {
			if ( $subtotal_includes_coupons ) {
				$subtotal += $item['line_total'];

				if ( $incl_tax ) {
					$subtotal += $item['line_tax'];
				}
			} else {
				$subtotal += $item['line_subtotal'];

				if ( $incl_tax ) {
					$subtotal += $item['line_subtotal_tax'];
				}
			}
		}

		return $subtotal;
	}

	/**
	 * Get subset of items
	 */
	public static function get_subset_items( $condition ) {
		$subset = [];
		$items = wcs_get_cart_func( 'get_cart' );

		if ( $condition && isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
			foreach ( $items as $key => $item ) {
				if ( self::item_in_subset( $item, $condition['subset_filter'] ) ) {
					$subset[$key] = $item;
				}
			}
		}

		return $subset;
	}

	/**
	 * Check if item belongs to subset
	 */
	public static function item_in_subset( $item, $subset_filter ) {
		// Get shipping class ID
		$product_shipping_class_id = null;
		if ( isset( $item['data'] ) && is_callable( [ $item['data'], 'get_shipping_class_id' ] ) ) {
			$product_shipping_class_id = self::get_product_shipping_class_id( $item['data'] );
		}

		// Get sale status
		$is_on_sale = null;
		if ( isset( $item['data'] ) && is_callable( [ $item['data'], 'is_on_sale' ] ) ) {
			$is_on_sale = $item['data']->is_on_sale();
		}

		// Not in a shipping class
		if ( strpos( $subset_filter, 'shipping_class_not_' ) !== false ) {
			$shipping_class_id = str_replace( 'shipping_class_not_', '', $subset_filter );

			if ( $product_shipping_class_id !== null && $shipping_class_id != $product_shipping_class_id ) {
				return true;
			}
		}
		// In a shipping class
		else if ( strpos( $subset_filter, 'shipping_class_' ) !== false ) {
			$shipping_class_id = str_replace( 'shipping_class_', '', $subset_filter );

			if ( $product_shipping_class_id !== null && $shipping_class_id == $product_shipping_class_id ) {
				return true;
			}
		}
		// Non-sale product
		else if ( $subset_filter === 'non_sale_products' ) {
			return $is_on_sale !== null && ! $is_on_sale;
		}
		// Sale product
		else if ( $subset_filter === 'sale_products' ) {
			return $is_on_sale !== null && $is_on_sale;
		}

		return false;
	}

	/**
	 * Get product shipping class
	 */
	public static function get_product_shipping_class_id( $product ) {
		// Special handling for WPML
		if ( function_exists( 'icl_object_id' ) ) {
			return apply_filters( 'wpml_object_id', $product->get_shipping_class_id(), 'product_shipping_class', true, apply_filters( 'wpml_default_language', NULL ) );
		}

		return $product->get_shipping_class_id();
	}

	/**
	 * Get order attribute
	 */
	public static function get_order_attr( $attr ) {
		if ( WC()->cart ) {
			$value = call_user_func( array( WC()->customer, "get_{$attr}" ) );
		} else {
			$value = NULL;
		}

		return apply_filters( 'woo_conditional_shipping_get_order_attr', $value, $attr );
	}

	/**
	 * Get product IDs in the cart
	 */
	public static function get_cart_products( $package = false ) {
		$products = [];

		$items = wcs_get_cart_func( 'get_cart' );

		if ( $package !== false && apply_filters( 'wcs_multi_package_mode', false ) ) {
			$items = isset( $package['contents'] ) ? $package['contents'] : [];
		}

		foreach ( $items as $key => $item ) {
			if ( isset( $item['data'] ) ) {
				if ( isset( $item['variation_id'] ) && ! empty( $item['variation_id'] ) ) {
					$products[$item['variation_id']] = $item['data'];
				} else if ( isset( $item['product_id'] ) && ! empty( $item['product_id'] ) ) {
					$products[$item['product_id']] = $item['data'];
				}
			}
		}

		return $products;
	}

	/**
	 * Merge children IDs for parent product IDs
	 */
	private static function merge_product_children_ids( $product_ids ) {
		$args = array(
			'post_type' => array( 'product_variation' ),
			'post_parent__in' => $product_ids,
			'fields' => 'ids',
			'posts_per_page' => -1
		);
		$children_ids = get_posts( $args );

		return array_merge( $children_ids, $product_ids );
	}

	/**
	 * Calculate cart weight
	 */
	private static function calculate_package_weight( $package, $condition ) {
		$items = $package['contents'];

		if ( is_array( $condition ) && isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
			$items = self::get_subset_items( $condition );
		}

		$total_weight = 0;

		foreach ( $items as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() ) {
				continue;
			}

			$item_weight = floatval( $product->get_weight() );

			if ( $item_weight ) {
				$total_weight += $item_weight * $data['quantity'];
			}
		}

		return apply_filters( 'woo_conditional_shipping_package_weight', $total_weight, $package, $condition );
	}

	/**
	 * Calculate cart volume
	 */
	private static function calculate_package_volume( $package, $condition ) {
		$items = $package['contents'];

		if ( is_array( $condition ) && isset( $condition['subset_filter'] ) && ! empty( $condition['subset_filter'] ) ) {
			$items = self::get_subset_items( $condition );
		}

		$total_volume = 0;

		foreach ( $items as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() ) {
				continue;
			}

			$length = $product->get_length();
			$width = $product->get_width();
			$height = $product->get_height();

			if ( is_numeric ( $length ) && is_numeric( $width ) && is_numeric( $height ) ) {
				$volume = $length * $width * $height;
				$total_volume += $volume * $data['quantity'];
			}
		}

		return $total_volume;
	}

	/**
	 * Calculate cart height
	 */
	private static function calculate_package_height($package) {
		$total = 0;

		foreach ( $package['contents'] as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() || ! $product->has_dimensions() ) {
				continue;
			}

			$item_height = $product->get_height();

			if ( $item_height ) {
				$total += floatval( $item_height ) * $data['quantity'];
			}
		}

		return $total;
	}

	/**
	 * Calculate cart length
	 */
	private static function calculate_package_length($package) {
		$total = 0;

		foreach ( $package['contents'] as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() || ! $product->has_dimensions() ) {
				continue;
			}

			$length = $product->get_length();

			if ( $length ) {
				$total += floatval( $length ) * $data['quantity'];
			}
		}

		return $total;
	}

	/**
	 * Calculate cart width
	 */
	private static function calculate_package_width($package) {
		$total = 0;

		foreach ( $package['contents'] as $key => $data ) {
			$product = $data['data'];

			if ( ! $product->needs_shipping() || ! $product->has_dimensions() ) {
				continue;
			}

			$width = $product->get_width();

			if ( $width ) {
				$total += floatval( $width ) * $data['quantity'];
			}
		}

		return $total;
	}

	/**
	 * Parse string number into float
	 */
	private static function parse_number($number) {
		$number = str_replace( ',', '.', $number );

		if ( is_numeric( $number ) ) {
			return floatval( $number );
		}

		return FALSE;
	}

	/**
	 * Compare value with given operator
	 */
	public static function compare_numeric_value( $a, $b, $operator ) {
		switch ( $operator ) {
			case 'e':
				return $a == $b;
			case 'gt':
				return $a > $b;
			case 'gte':
				return $a >= $b;
			case 'lt':
				return $a < $b;
			case 'lte':
				return $a <= $b;
		}

		error_log( "Invalid operator given" );

		return NULL;
	}

	/**
	 * Check inclusiveness or exclusiveness in an array
	 */
	private static function group_comparison( $a, $b, $operator ) {
		$a = array_unique( $a );
		$b = array_unique( $b );

		switch ( $operator ) {
			case 'in':
				return count( array_intersect( $a, $b ) ) > 0;
			case 'notin':
				return count( array_intersect( $a, $b ) ) == 0;
			case 'exclusive':
				return count( array_diff( $a, $b ) ) == 0;
			case 'allin':
				return count( array_diff( $b, $a ) ) == 0;
		}

		error_log( "Invalid operator given in group comparison" );

		return NULL;
	}

	/**
	 * Check is / is not in an array
	 */
	public static function is_array_comparison( $needle, $haystack, $operator ) {
		if ( $operator == 'is' ) {
			return in_array( $needle, $haystack );
		} else if ( $operator == 'isnot' ) {
			return ! in_array( $needle, $haystack );
		}

		return NULL;
	}
}
}
