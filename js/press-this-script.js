/* global wpStatusesPressThis */
( function( $ ) {

	// Bail if not set
	if ( typeof wpStatusesPressThis === 'undefined' ) {
		return;
	}

	window.wpStatuses = {
		init: function() {
			if ( 'object' !== typeof wpStatusesPressThis.statuses ) {
				return;
			}

			$.each( wpStatusesPressThis.statuses, function( name, label ) {
				$( '.post-actions .split-button-body' ).prepend(
					$( '<li></li>' ).html(
						$( '<button></button>' ).prop( 'type', 'button' ).addClass(
							'button-link publish-button split-button-option wp-statuses-button'
						).data( 'status', name ).html( label )
					)
				);
			} );

			/**
			 * window.wp.pressThis has no public method for submitPost()
			 */
			$( '.wp-statuses-button' ).on( 'click', function( event ) {
				var status = $( event.currentTarget ).data( 'status');

				if ( 'undefined' !== typeof wpStatusesPressThis.statuses[status] ) {
					if ( $( '#pressthis-form [name="_wp_statuses_status"]' ).length ) {
						$( '#pressthis-form [name="_wp_statuses_status"]' ).val( status );
					} else {
						$( '#pressthis-form' ).prepend(
							$( '<input></input>' ).prop( 'name', '_wp_statuses_status' ).prop( 'type', 'hidden' ).val( status )
						);
					}

					$( '#pt-force-redirect' ).val( 'true' );
				}
			} );
		}
	};

	$( document ).ready( function(){ window.wpStatuses.init(); } );

} )( jQuery );
