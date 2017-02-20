( function( $ ) {

	// Bail if not set
	if ( typeof wpStatuses === 'undefined' ) {
		return;
	}

	// Set selected Status attributes
	setStatusAttributes = function( status ) {
		// First reset attributes
		$.each( $( '#wp-statuses-attibutes input' ), function( i, element ) {
			if ( 'checkbox' === element.type || 'radio' === element.type ) {
				$( element ).prop( 'checked', false );

			} else if ( 'text' === element.type ) {
				$( element ).val( '' );
			}

			$( element ).parent( '.wp-statuses-attribute-container' ).addClass( 'hide-if-js' );
		} );

		if ( 'password' === status || ( status === wpStatuses.status && wpStatuses.attributes.password ) ) {
			$( '#password-span' ).removeClass( 'hide-if-js' );

			if ( wpStatuses.attributes.password ) {
				$( '#post_password' ).val( wpStatuses.attributes.password );
			}
		} else if ( 'private' !== status ) {
			$( '#sticky-span' ).removeClass( 'hide-if-js' );

			if ( wpStatuses.attributes.sticky ) {
				$( '#sticky' ).prop( 'checked', wpStatuses.attributes.sticky );
			}
		}
	}

	$( '#wp-statuses-publish-box' ).on( 'change', '#wp-statuses-dropdown', function( e ) {
		var newDashicon = $( e.currentTarget ).find( ':selected').data( 'dashicon' ),
			oldDashicon = $( e.currentTarget ).parent().find( '.dashicons' ),
			newStatus   = $( e.currentTarget ).find( ':selected').data( 'status' );

		if ( ! newDashicon ) {
			newDashicon = 'dashicons-post-status';
		}

		// Reset Class
		oldDashicon.prop( 'class', '' ).addClass( 'dashicons' );
		oldDashicon.addClass( newDashicon );

		// Handle Status attributes
		setStatusAttributes( newStatus );
	} );

	$( '#wp-statuses-publish-box' ).on( 'click', '.edit-timestamp', function( e ) {
		console.log( $( '#wp-statuses-dropdown' ).val() );
	} );

	$( '#wp-statuses-publish-box' ).on( 'click', '.save-timestamp', function( e ) {
		// if now is < date then make sure Publish is future
		console.log( wpStatuses.attributes.modified );
	} );

	$( '#wp-statuses-publish-box' ).on( 'click', '.cancel-timestamp', function( e ) {
		// if now is >= date then make sure Future is publish
		console.log( wpStatuses.attributes.modified );
	} );

} )( jQuery );
