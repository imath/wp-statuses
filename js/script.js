( function( $ ) {

	$( '#wp-statuses-publish-box' ).on( 'change', '#wp-statuses-dropdown', function( e ) {
		var newDashicon = $( e.currentTarget ).find( ':selected').data( 'dashicon' ),
			oldDashicon = $( e.currentTarget ).parent().find( '.dashicons' );
		if ( ! newDashicon ) {
			newDashicon = 'dashicons-post-status';
		}

		// Reset Class
		oldDashicon.prop( 'class', '' ).addClass( 'dashicons' );
		oldDashicon.addClass( newDashicon );
	} );

} )( jQuery );
