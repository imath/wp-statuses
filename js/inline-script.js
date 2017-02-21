/* global wpStatusesInline */
( function( $ ) {

	// Bail if not set
	if ( typeof wpStatusesInline === 'undefined' ) {
		return;
	}

	wpStatuses = {
		init: function() {
			var that = this;

			// Reset the status for the bulk-edit action.
			that.resetStatuses( $( '#bulk-edit' ).find( '[name="_status"]' ), '', 'bulk' );

			$( '#the-list' ).on( 'click', '.editinline', function( event ) {
				var Id       = window.inlineEditPost.getId( this ),
					select   = $( '#edit-' + Id ).find( '[name="_status"]' ) || null,
					password = $( '#edit-' + Id ).find( '[name="post_password"]' ) || null,
					private  = $( '#edit-' + Id ).find( '[name="keep_private"]' ) || null,
					sticky   = $( '#edit-' + Id ).find( '[name="sticky"]' ) || null;

				if ( select && password && private ) {
					var selectedStatus = $( select ).val();
					that.Id = Id;

					// Save the date for a later use.
					that.Date = new Date(
						$( '[name="aa"]' ).val(),
						$( '[name="mm"]' ).val() - 1,
						$( '[name="jj"]' ).val(),
						$( '[name="hh"]' ).val(),
						$( '[name="mn"]' ).val()
					);

					if ( $( password ).val() ) {
						selectedStatus = 'password';
					} else {
						$( password ).prop( 'disabled', true );
					}

					if ( -1 === $.inArray( selectedStatus, [ 'draft', 'pending', 'publish', 'future'] ) ) {
						$( sticky ).prop( 'disabled', true );
					}

					$( select ).parent().after( $( password ).parents( 'label' ).first() );

					// Remove Private checkbox
					$( private ).parents( '.inline-edit-group' ).first().find( '.inline-edit-or' ).remove();
					$( private ).parent().remove();

					// Reset the statuses select tag.
					that.resetStatuses( select, selectedStatus );
				}
			} );

			$( '#the-list' ).on( 'change', '[name="_status"]', function( event ) {
				var newStatus = $( event.currentTarget ).find( ':selected' ).data( 'status' );

				if ( 'password' === newStatus ) {
					$( '#edit-' + that.Id ).find( '[name="sticky"]' ).prop( 'disabled', true );
					$( '#edit-' + that.Id ).find( '[name="post_password"]' ).prop( 'disabled', false ).focus();
				} else {
					$( '#edit-' + that.Id ).find( '[name="post_password"]' ).prop( 'disabled', true );
				}

				if ( -1 !== $.inArray( newStatus, [ 'draft', 'pending', 'publish', 'future'] ) ) {
					$( '#edit-' + that.Id ).find( '[name="sticky"]' ).prop( 'disabled', false );
					$( '#bulk-edit' ).find( '[name="sticky"]' ).prop( 'disabled', false );
				} else {
					$( '#edit-' + that.Id ).find( '[name="sticky"]' ).prop( 'disabled', true );
					$( '#bulk-edit' ).find( '[name="sticky"]' ).prop( 'disabled', true );
				}
			} );
		},

		resetStatuses: function( tag, current, action ) {
			var type = action || 'inline', selected = current || '';

			// Reset options.
			tag.empty();

			if ( 'bulk' === type ) {
				$( tag ).append(
					$( '<option></option>' ).prop( 'value', '-1' ).html( wpStatusesInline.bulk_default )
				);
			}

			// Repopulate with WP Statuses statuses.
			$.each( wpStatusesInline[type], function( v, t ) {
				var s = v;
				if ( 'password' === v ) {
					s = 'publish';
				}

				$( tag ).append(
					$( '<option></option>' ).prop( 'value', s ).prop( 'selected', v === selected ).data( 'status', v ).html( t )
				);
			} );
		}
	}

	$( document ).ready( function(){ wpStatuses.init(); } );

} )( jQuery );
