jQuery(document).ready(function($) {
	/**
	 * Notices in checkout
	 */
	$( document.body ).on( 'updated_checkout', function() {
		let noticesEl = $( '#wcs-notices-pending' );

		if ( noticesEl.length > 0 ) {
			// Clear existing notices
			$( '#wcs-notices' ).remove();

			let shippingMethods = $( '.woocommerce-shipping-totals ul.woocommerce-shipping-methods' );
			
			if ( shippingMethods.length > 0 ) {
				shippingMethods.after( noticesEl );
				noticesEl.css( 'display', 'block' ).attr( 'id', 'wcs-notices' );
			}
		}
	} );

	/**
	 * Notices in cart
	 */
	 $( document.body ).on( 'wcs_updated_cart', function() {
		let noticesEl = $( '#wcs-notices-pending' );

		if ( noticesEl.length > 0 ) {
			// Clear existing notices
			$( '#wcs-notices' ).remove();

			let shippingMethods = $( '.woocommerce-shipping-totals ul.woocommerce-shipping-methods' );
			
			if ( shippingMethods.length > 0 ) {
				shippingMethods.after( noticesEl );
				noticesEl.css( 'display', 'block' ).attr( 'id', 'wcs-notices' );
			}
		}
	} );
	$( document.body ).trigger( 'wcs_updated_cart' );
	$( document.body ).on( 'updated_cart_totals', function() {
		$( document.body ).trigger( 'wcs_updated_cart' );
	} );
});
