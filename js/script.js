/* global wpStatuses, postL10n */
( function( $ ) {

	// Bail if not set
	if ( typeof wpStatuses === 'undefined' || typeof postL10n === 'undefined' ) {
		return;
	}

	// Set selected Status attributes
	window.setStatusAttributes = function( status ) {
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
	};

	$( '#submitdiv' ).on( 'change', '#wp-statuses-dropdown', function( e ) {
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
		window.setStatusAttributes( newStatus );

		// Handle The minor publishing action button
		if ( 'pending' === newStatus || 'draft' === newStatus ) {
			var text = 'pending' === newStatus ? postL10n.savePending : postL10n.saveDraft;

			$( '#save-post' ).show().val( text );
		} else {
			$( '#save-post' ).hide();
		}

		/**
		 * As WordPress is overriding the $_POST global inside _wp_translate_postdata()
		 * We'll use this input to remember what was the real posted status.
		 */
		$( '#wp-statuses-status' ).val( newStatus );
	} );

	$( '#submitdiv' ).on( 'click', '.save-timestamp', function() {
		var formDate = new Date( $('#aa').val(), $('#mm').val() - 1, $('#jj').val(), $('#hh').val(), $('#mn').val() ),
			now      = new Date(), diff = formDate - now, status = $( '#wp-statuses-dropdown' ).val(),
			oStatus  = $( '#wp-statuses-dropdown :selected' ).data( 'status' );

		// In case someone is moving the date backward, reset the Status to its origine.
		if ( diff < 0 && 'future' === status ) {
			var resetStatus = oStatus;
			if ( 'password' === resetStatus ) {
				resetStatus = 'publish';
			}

			$( '#wp-statuses-dropdown :selected' ).prop( 'value', resetStatus );

		// Set the status to be future for scheduled public posts.
		} else if ( diff > 0 && 'publish' === status ) {
			$( '#wp-statuses-dropdown :selected' ).prop( 'value', 'future' );
		}

		// Handle The minor publishing action button
		if ( 'draft' !== oStatus ) {
			$( '#save-post' ).hide();
		}

		if ( 'pending' === oStatus ) {
			$( '#save-post' ).show().val( postL10n.savePending );
		}
	} );

	$( '#submitdiv' ).on( 'click', '.cancel-timestamp', function() {
		var oStatus = $( '#wp-statuses-dropdown :selected' ).data( 'status' ) || wpStatuses.status;

		if ( 'password' === oStatus ) {
			oStatus = 'publish';
		}

		// Handle The minor publishing action button
		if ( 'draft' !== oStatus ) {
			$( '#save-post' ).hide();
		}

		if ( 'pending' === oStatus ) {
			$( '#save-post' ).show().val( postL10n.savePending );
		}

		// Reset the original status.
		$( '#wp-statuses-dropdown :selected' ).prop( 'value', oStatus );
	} );

} )( jQuery );
