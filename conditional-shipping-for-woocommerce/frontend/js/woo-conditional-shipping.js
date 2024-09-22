jQuery(document).ready(function($) {
	/**
	 * Notices in checkout
	 */
	$( document.body ).on( 'updated_checkout', function() {
		var noticesEl = $( '#wcs-notices-pending' );

		if ( noticesEl.length > 0 ) {
			// Clear existing notices
			$( '#wcs-notices' ).remove();

			var shippingRow = $( 'tr.woocommerce-shipping-totals td:eq(0)' );
			
			if ( shippingRow.length > 0 ) {
				shippingRow.append( noticesEl );
				noticesEl.css( 'display', 'block' ).attr( 'id', 'wcs-notices' );
			}
		}
	} );

	/**
	 * Notices in cart
	 */
	 $( document.body ).on( 'wcs_updated_cart', function() {
		var noticesEl = $( '#wcs-notices-pending' );

		if ( noticesEl.length > 0 ) {
			// Clear existing notices
			$( '#wcs-notices' ).remove();

			var shippingRow = $( 'tr.woocommerce-shipping-totals td:eq(0)' );
			
			if ( shippingRow.length > 0 ) {
				shippingRow.append( noticesEl );
				noticesEl.css( 'display', 'block' ).attr( 'id', 'wcs-notices' );
			}
		}
	} );
	$( document.body ).trigger( 'wcs_updated_cart' );
	$( document.body ).on( 'updated_cart_totals', function() {
		$( document.body ).trigger( 'wcs_updated_cart' );
	} );
});
