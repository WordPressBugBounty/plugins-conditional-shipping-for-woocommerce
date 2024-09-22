jQuery(document).ready(function($) {
	var wcsDebug = {
		init: function() {
			this.toggleDebug();
			this.setInitial();

			var self = this;
			$( document.body ).on( 'updated_checkout wcs_updated_debug', function( data ) {
				self.setInitial();
			} );
		},

		/**
		 * Toggle debug on click
		 */
		toggleDebug: function() {
			var self = this;

			$( document.body ).on( 'click', '#wcs-debug-header', function( e ) {
				if ( $( '#wcs-debug-contents' ).is( ':visible' ) ) {
					$( '#wcs-debug' ).toggleClass( 'closed', true );
				} else {
					$( '#wcs-debug' ).toggleClass( 'closed', false );
				}

				$( '#wcs-debug-contents' ).slideToggle( 200, function() {
					self.saveStatus();
				} );
			} );
		},

		/**
		 * Save debug open / closed status to local storage
		 */
		saveStatus: function() {
			if ( ! this.isLocalStorage() ) {
				return;
			}

			let status = $( '#wcs-debug-contents' ).is( ':visible' ) ? 'true' : 'false';

			localStorage.setItem( 'wcs_debug_status', status );
		},

		/**
		 * Set initial stage for debug
		 */
		setInitial: function() {
			if ( ! this.isLocalStorage() ) {
				return;
			}

			let status = localStorage.getItem( 'wcs_debug_status' );

			$( '#wcs-debug-contents' ).toggle( status === 'true' );
			$( '#wcs-debug' ).toggleClass( 'closed', $( '#wcs-debug-contents' ).is( ':hidden' ) );
		},

		/**
		 * Check if local storage is available
		 */
		isLocalStorage: function() {
			var test = 'test';
			try {
				localStorage.setItem(test, test);
				localStorage.removeItem(test);

				return true;
			} catch(e) {
				return false;
			}
		}
	}

	wcsDebug.init();
});
