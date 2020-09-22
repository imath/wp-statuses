/* global wpStatuses, postL10n */

// Make sure the wp object exists.
window.wp = window.wp || {};

( function( $ ) {

	// Bail if not set
	if ( typeof wpStatuses === 'undefined' ) {
		return;
	}

	if ( typeof postL10n !== 'undefined' && typeof wp.deprecateL10nObject === 'undefined' ) {
		wpStatuses.L10n = postL10n;
	} else if ( typeof wp.i18n.__ !== 'undefined' ) {
		wpStatuses.L10n = {
			publishOnFuture:  wp.i18n.__( 'Schedule for:' ),
			schedule:  wp.i18n._x( 'Schedule', 'post action/button label' ),
			publishOn:  wp.i18n.__( 'Publish on:' ),
			publish:  wp.i18n.__( 'Publish' ),
			publishOnPast: wp.i18n.__( 'Published on:' ),
			update: wp.i18n.__( 'Update' ),
			dateFormat: wp.i18n.__( '%1$s %2$s, %3$s at %4$s:%5$s' ),
			savePending: wp.i18n.__( 'Save as Pending' ),
			saveDraft: wp.i18n.__( 'Save Draft' )
		};
	} else {
		return;
	}

	var wpStatusesBox = {

		init: function() {
			this.publishingBox   = $( '#wp-statuses-publish-box' );
			this.timeStampDiv    = $( '#timestampdiv' );
			this.majorSubmitName = $( '#publish' ).prop( 'name' );

			this.setListeners();
		},

		setListeners: function() {
			// Status changed
			this.publishingBox.on( 'change', '#wp-statuses-dropdown', this.updateStatus.bind( this ) );

			// Edit publish time click
			this.publishingBox.on( 'click', '.edit-timestamp, .cancel-timestamp, .save-timestamp', this.editTimestamp.bind( this ) );

			// Submit form
			$( '#post' ).on( 'submit', this.submitForm.bind( this ) );
		},

		updateStatus: function( e ) {
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
			this.setStatusAttributes( newStatus );

			if ( 'password' === newStatus ) {
				newStatus = 'publish';
				$( '#wp-statuses-dropdown :selected' ).prop( 'value', newStatus );
			}

			$( '#wp-statuses-status' ).val( newStatus );

			// Make sure UI texts are updated.
			this.updateText();
		},

		setStatusAttributes: function( status ) {
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
			} else if ( -1 !== $.inArray( status, wpStatuses.public_statuses ) ) {
				$( '#sticky-span' ).removeClass( 'hide-if-js' );

				if ( wpStatuses.attributes.sticky ) {
					$( '#sticky' ).prop( 'checked', wpStatuses.attributes.sticky );
				}
			}
		},

		updateText: function() {
			var currentDate, formDate, originalDate, dateObject = {
					aa: parseInt( $('#aa').val(), 10 ),
					mm: parseInt( $('#mm').val(), 10 ) - 1,
					jj: parseInt( $('#jj').val(), 10  ),
					hh: parseInt( $('#hh').val(), 10  ),
					mn: parseInt( $('#mn').val(), 10 )
				}, publishOn, dateDiff, status = $( '#wp-statuses-dropdown' ).val(), month, day,
				rStatus = $( '#wp-statuses-dropdown :selected' ).data( 'status' ),
				originalStatus = $( '#original_post_status' ).val();

			if ( ! this.timeStampDiv.length ) {
				return true;
			}

			// Set dates
			formDate     = new Date( dateObject.aa, dateObject.mm, dateObject.jj, dateObject.hh, dateObject.mn );
			currentDate  = new Date( $('#cur_aa').val(), $('#cur_mm').val() - 1, $('#cur_jj').val(), $('#cur_hh').val(),$('#cur_mn').val() );
			originalDate = new Date( $('#hidden_aa').val(), $('#hidden_mm').val() - 1, $('#hidden_jj').val(), $('#hidden_hh').val(),$('#hidden_mn').val() );

			// Catch unexpected date problems.
			if ( formDate.getFullYear() !== dateObject.aa || ( formDate.getMonth() ) !== dateObject.mm || formDate.getDate() !== dateObject.jj || formDate.getMinutes() !== dateObject.mn ) {
				this.timeStampDiv.find( '.timestamp-wrap' ).addClass( 'form-invalid' );
				return false;
			} else {
				this.timeStampDiv.find( '.timestamp-wrap' ).removeClass( 'form-invalid' );
			}

			// Reset the Major Publish name
			$('#publish').prop( 'name', this.majorSubmitName );

			// Future, past or now ?
			dateDiff = formDate - currentDate;

			// Schedule action
			if ( dateDiff > 0 && -1 !== $.inArray( status, ['draft', 'pending', 'publish'] ) ) {
				publishOn = wpStatuses.L10n.publishOnFuture;
				$( '#publish' ).val( wpStatuses.L10n.schedule );
				$( '#publish' ).prop( 'name', 'publish' );

				if ( 'password' === rStatus ) {
					$( '#wp-statuses-dropdown :selected' ).prop( 'value', 'future' );
				}

			// Publish action
			} else if ( dateDiff <= 0 && -1 !== $.inArray( status, ['draft', 'pending', 'publish', 'future'] ) && ( 'publish' !== originalStatus || ( 'publish' === originalStatus && -1 !== $.inArray( status, ['draft', 'pending'] ) ) ) ) {
				publishOn = wpStatuses.L10n.publishOn;
				$( '#publish' ).val( wpStatuses.L10n.publish );
				$( '#publish' ).prop( 'name', 'publish' );

				if ( 'password' === rStatus ) {
					$( '#wp-statuses-dropdown :selected' ).prop( 'value', 'publish' );
				}

			// Update action
			} else {
				publishOn = wpStatuses.L10n.publishOnPast;
				$( '#publish' ).val( wpStatuses.L10n.update );

				// Make sure the name property is
				if ( -1 === $.inArray( status, ['draft', 'pending', 'publish', 'future'] ) ) {
					$('#publish').prop( 'name', 'save' );

					// Use customized labels.
					publishOn = wpStatuses.strings.labels[ rStatus ].metabox_save_on;
					$( '#publish' ).val( wpStatuses.strings.labels[ rStatus ].metabox_submit );
				}
			}

			// If the date is the same, set a different label.
			if ( originalDate.toUTCString() === formDate.toUTCString() ) {
				publishOn = wpStatuses.strings.labels[ rStatus ].metabox_save_on;

				if ( dateDiff <= 0 || originalStatus === status ) {
					publishOn = wpStatuses.strings.labels[ rStatus ].metabox_saved_on;
				}

				if ( -1 !== $.inArray( originalStatus, ['draft', 'auto-draft'] ) ) {
					$( '#timestamp' ).html( '\n' + wpStatuses.strings.labels[ rStatus ].metabox_save_now );
					publishOn = false;
				}
			}

			// Update the timestamp
			if ( publishOn ) {
				month = dateObject.mm + 1;
				day   = dateObject.jj;

				if ( 1 === month.toString().length ) {
					month = '0' + month;
				}

				if ( 1 === day.toString().length ) {
					day = '0' + day;
				}

				$( '#timestamp' ).html(
					'\n' + publishOn + ' <b>' +
					wpStatuses.L10n.dateFormat
						.replace( '%1$s', $( 'option[value="' + month + '"]', '#mm' ).data( 'text' ) )
						.replace( '%2$s', day )
						.replace( '%3$s', dateObject.aa )
						.replace( '%4$s', ( '00' + dateObject.hh.toString() ).slice( -2 ) )
						.replace( '%5$s', ( '00' + dateObject.mn.toString() ).slice( -2 ) ) +
						'</b> '
				);
			}

			// Handle The minor publishing action button
			if ( 'pending' === status || 'draft' === status ) {
				var text = 'pending' === status ? wpStatuses.L10n.savePending : wpStatuses.L10n.saveDraft;

				if ( $( '#save-post' ).length ) {
					$( '#save-post' ).show().val( text );
				} else {
					$( '#save-action' ).prepend(
						$( '<input></input>' ).val( text ).prop( {
							type : 'submit',
							name : 'save',
							id   : 'save-post'
						} ).addClass( 'button' )
					);
				}

				$( '#post-preview' ).html( wpStatuses.strings.preview );

			} else {
				$( '#save-post' ).hide();

				if ( 'publish' === originalStatus ) {
					$( '#post-preview' ).html( wpStatuses.strings.previewChanges );
				}
			}

			return true;
		},

		editTimestamp: function( e ) {
			var link = $( e.currentTarget ), timeStampDiv = this.timeStampDiv;

			e.preventDefault();

			if ( timeStampDiv.hasClass( 'hide-if-js' ) ) {
				timeStampDiv.slideDown( 'fast', function() {
					$( 'input, select', timeStampDiv.find( '.timestamp-wrap' ) ).first().focus();
				} ).removeClass( 'hide-if-js' );

				link.hide();

			// Handle Time Stamp Div links.
			} else {
				// Cancelling the Time Stamp edit.
				if ( link.hasClass( 'cancel-timestamp' ) ) {
					$.each( timeStampDiv.find( '[type="hidden"]' ), function( i, element ) {
						var id = $( element ).prop( 'id' ).replace( 'hidden_', '#' );

						if ( 0 === id.indexOf( '#' ) ) {
							$( id ).val( $( element ).val() );
						}
					} );
				}

				// Validate texts
				var textUpdated = this.updateText();

				/**
				 * Do not restore the display if Validating the Time stamp
				 * failed and the Save action was clicked
				 */
				if ( true !== textUpdated && link.hasClass( 'save-timestamp' ) ) {
					return;
				}

				// Restore display.
				timeStampDiv.slideUp( 'fast' ).siblings( 'a.edit-timestamp' ).show().focus();
				timeStampDiv.addClass( 'hide-if-js' );
			}
		},

		submitForm: function( e ) {
			if ( true === this.updateText() ) {
				return e;
			}

			e.preventDefault();

			this.timeStampDiv.show();

			if ( wp.autosave ) {
				wp.autosave.enableButtons();
			}

			$( '#publishing-action .spinner' ).removeClass( 'is-active' );
		}
	};

	$( document ).ready( function() {
		wpStatusesBox.init();
	} );

} )( jQuery );
