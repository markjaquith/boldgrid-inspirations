var IMHWPB = IMHWPB || {};

IMHWPB.Ajax = function( configs ) {
	var self = this;

	this.configs = configs;
	this.api_url = this.configs.asset_server;
	this.api_key = this.configs.api_key;
	this.site_hash = this.configs.site_hash;

	// Execute an ajax call.
	this.ajaxCall = function( data, requestUrlKey, successAction, errorAction, completeAction ) {

		if ( 'function' !== typeof errorAction ) {
			errorAction = self.errorAction;
		}
		if ( 'function' !== typeof completeAction ) {
			completeAction = function() {
				/** No Default * */
			};
		}

		data['key'] = self.api_key;
		data['site_hash'] = self.site_hash;

		jQuery.ajax( {
			type : 'POST',
			url : self.api_url + self.configs.ajax_calls[requestUrlKey],
			data : data,
			timeout : 120000,
			requestHeaders : {
				Accept : 'application/json'
			},
			dataType : 'json',
			success : successAction,
			error : errorAction,
			complete : completeAction
		} );
	};

	/**
	 * Error handling.
	 */
	this.errorAction = function( jqXHR, textStatus, errorThrown ) {
		switch ( textStatus ) {
		case 'timeout':
			alert( 'Ajax error: timeout. Please try your request again.' );
			break;

		case 'parsererror':
			alert( 'Ajax error: Unexpected return. In some cases, trying your request again may help.' );
			break;

		case 'error':
			// Provide a friendly error for comm failure.
			jQuery( '#wpbody-content' )
				.html(
					"<div id='container_boldgrid_connection_notice' class='error'><h2 class='dashicons-before dashicons-admin-network'>BoldGrid Connection Issue</h2><p>There was an issue reaching the BoldGrid Connect server. Some BoldGrid features may be temporarily unavailable. Please try again in a moment.</p><p>If the issue persists, then please feel free to check our <a	target='_blank' href='https://www.boldgrid.com/'>BoldGrid Status</a> page.</p></div>"
				);
			// Make WordPress check the asset server connection.
			var data = {
					'action': 'check_asset_server'
				};
			jQuery.post( ajaxurl, data );

			break;

		default:
		}

		console.log( 'jqXHR:', jqXHR, '\n\njqXHR.responseText: ' + jqXHR.responseText +
		'\n\nTextStatus:' + textStatus + '\n\nerrorThrown: ' + errorThrown
		);
	};
};