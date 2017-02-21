/* global wpStatusesInline */
window.wp = window.wp || {};

( function( $, wp ) {

	// Bail if not set
	if ( typeof wpStatusesInline === 'undefined' ) {
		return;
	}

	wpStatuses = {
		init: function() {
			var isBulk = $('#bulk-edit').length ? true : false;

			console.log( isBulk );
		}
	}

	$( document ).ready( function(){ wpStatuses.init(); } );

} )( jQuery, window.wp );
