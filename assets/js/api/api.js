var IMHWPB = IMHWPB || {};

IMHWPB.Api = function( configs ) {
	( function( $ ) {
		var self = this;

		/**
		 * Set key if parameter is set.
		 */
		$( function() {
			var $activateKey = self.GetURLParameter( 'activateKey' ),
			    container = $( 'container_boldgrid_api_key_notice' );
			if ( $activateKey ) {
				document.getElementById( 'boldgrid_api_key' ).value = $activateKey;
			}
		});

		/**
		 * Get parameter from URL
		 *
		 * @link http://www.jquerybyexample.net/2012/06/get-url-parameters-using-jquery.html
		 */
		this.GetURLParameter = function( sParam ) {
			var sPageURL, sURLVariables, sParameterName;

			sPageURL = window.location.search.substring( 1 );
			sURLVariables = sPageURL.split( '&' );

			for ( var i = 0; i < sURLVariables.length; i++ ) {
				sParameterName = sURLVariables[i].split( '=' );

				if ( sParameterName[0] == sParam ) {
					return sParameterName[1];
				}
			}
		};

		this.trackActivation = function () {
			// Create iframe element.
			var iframe = document.createElement( 'iframe' );
			// Assign iframe ID.
			iframe.setAttribute( 'id', 'tracking' );
			// Assign iframe width.
			iframe.setAttribute( 'width', 0 );
			// Assign iframe height.
			iframe.setAttribute( 'height', 0 );
			// Assign iframe tabindex.
			iframe.setAttribute( 'tabindex', -1 );
			// Place iframe before response message.
			var el = document.getElementById( 'boldgrid_api_key_notice_message' );
			el.parentNode.insertBefore( iframe, el );
			// Assign src URL to iframe.
			iframe.setAttribute( 'src', 'https://www.boldgrid.com/activation/' );
			// Set display:none to iframe;
			iframe.style.display = 'none';
		};

		$c_zakn = $( '#container_boldgrid_api_key_notice' );

		/** Toggle the forms around **/
		$( '.boldgridApiKeyLink', $c_zakn ).on( 'click', function() {
			$( '.api-notice', $c_zakn ).hide();
			$( '.new-api-key', $c_zakn ).fadeIn( 'slow' );
		});
		$( '.enterKeyLink', $c_zakn ).on( 'click', function() {
			$( '.new-api-key', $c_zakn ).hide();
			$( '.api-notice', $c_zakn ).fadeIn( 'slow' );
		});

		/** Submit action **/
		$( "#requestKeyForm" ).submit( function( event ) {
			event.preventDefault();

			var posting,
				$form = $( this ),
				$firstName = $form.find( '#firstName' ).val(),
				$lastName = $form.find( '#lastName' ).val(),
				$email = $form.find( '#emailAddr' ).val(),
				$link = $form.find( '#siteUrl' ).val(),
				$alertBox = $( '.error-alerts' ),
				$genericError = 'There was an error communicating with the BoldGrid Connect Key server.  Please try again.';


				$('.error-color').removeClass( 'error-color' );

			// Basic js checks before server-side verification.
			if ( ! $firstName ) {
				$alertBox.text( 'First name is required.' );
				$form.find( '#firstName' ).prev().addClass( 'error-color' );
				return false;
			}
			if ( ! $lastName ) {
				$alertBox.text( 'Last name is required.' );
				$form.find( '#lastName' ).prev().addClass( 'error-color' );
				return false;
			}
			if ( ! ( $email.indexOf( '@' ) > -1 && $email.indexOf( '.' ) > -1 ) ) {
				$alertBox.text( 'Please enter a valid e-mail address.' );
				$form.find( '#emailAddr' ).prev().addClass( 'error-color' );
				return false;
			}

			posting = $.post( $( '#asset-server' ).val() + $( '#generate-api-key' ).val(),
				{
					first: $firstName,
					last: $lastName,
					email: $email,
					link: $link,
				}
			);

			posting.done( function( response ) {
				$alertBox.text( $genericError );
				if ( 400 === response.status ) {
					if ( response.message.indexOf( 'First name' ) >= 0 ) {
						$form.find( '#firstName' ).prev().addClass( 'error-color' );
					}
					if ( response.message.indexOf( 'Last name' ) >= 0 ) {
						$form.find( '#lastName' ).prev().addClass( 'error-color' );
					}
					if ( response.message.indexOf( 'e-mail' ) >= 0 ) {
						$form.find( '#emailAddr' ).prev().addClass( 'error-color' );
					}
					$alertBox.text( response.message );
				}
				if ( 200 === response.status ) {
					$( '.key-request-content' ).text( response.message );
				}
			}).fail( function() {
				$alertBox.text( $genericError );
			});
		});

		/**
		 * Bind events.
		 *
		 * When the submit button is pressed.
		 */
		$( '#boldgrid-api-form' ).submit( function( e ){
			e.preventDefault();
		});

		$( '#boldgrid-api-loading', $c_zakn ).hide();

		$( '#submit_api_key', $c_zakn ).on('click', function() {
			$( '#boldgrid_api_key_notice_message' ).empty();
			if ( ! $( '#tos-box:checked').length  ) {
				$( '#boldgrid_api_key_notice_message', $c_zakn )
					.html( 'You must agree to the Terms of Service before continuing.' )
					.addClass( 'error-color' );
				return false;
			}
			var api_key = $( '#boldgrid_api_key', $c_zakn ).val()
				.replace( /[^a-z0-9]/gi,'' )
				.substr( 0, 32 )
				.replace( /(.{8})/g,"$1\-" )
				.slice( 0, - 1 );
			if ( ! api_key || api_key.length < 32 ) {
				$( '#boldgrid_api_key_notice_message', $c_zakn )
					.html( 'You must enter a valid BoldGrid Connect Key.' )
					.addClass( 'error-color' );
				return false;
			}
			$( '#boldgrid_api_key_notice_message', $c_zakn ).removeClass( 'error-color' );

			self.set( api_key );

			// hide the button
			$( this ).hide();

			// show the loading graphic
			$( '#boldgrid-api-loading', $c_zakn ).show();
		});

		/**
		 * Set the API key.
		 */
		this.set = function( api_key ) {
			var data, nonce, wpHttpReferer;

			// Create a context selector for the BoldGrid API key entry form.
			$apiForm = $('#boldgrid-api-form');

			// Get the wpnonce and referer values.
			nonce = $apiForm.find( '#set_key_auth' ).val();

			wpHttpReferer = $apiForm.find( '[name="_wp_http_referer"]' ).val();

			// Create the data set to post.
			data = {
				'action'  : 'set_api_key',
				'api_key' :  api_key,
				'set_key_auth' : nonce,
				'_wp_http_referer' : wpHttpReferer,
			};

			$.post( ajaxurl, data, function( response ) {
				// Declare variables.
				var responseObj, message;

				// Parse the response.
				responseObj = JSON && JSON.parse( response ) || $.parseJSON( response );

				// If the key was saved successfully.
				if ( responseObj.success ) {
					// Change the notice from red to green.
					$c_zakn.toggleClass( 'error' ).toggleClass( 'updated' );

					// Set message.
					if ( responseObj.message !== undefined ) {
						$message = responseObj.message;
					} else {
						$message = 'Your api key has been saved successfully.';
					}

					// Initiate tracking iframe.
					self.trackActivation();

					$( '#boldgrid_api_key_notice_message', $c_zakn )
						.html( $message + ' <a onClick="window.location.reload(true)" style="cursor:pointer;"> Dismiss Notification</a>' );

					// Remove the loading graphic since success.
					$( '#boldgrid-api-loading', $c_zakn )
						.fadeOut();

					// Finally hide the input elements as we do not need them anymore.
					$( '#boldgrid_api_key', $c_zakn )
						.fadeOut();

					// Reload page after 3 seconds.
					setTimeout( function() {
						window.location.reload();
					}, 3000 );

				} else if ( responseObj.error !== undefined && 'error_saving_key' === responseObj.error ) {
					// Hide loading.
					$( '#boldgrid-api-loading', $c_zakn )
						.hide();

					// Show button.
					$( '#submit_api_key', $c_zakn )
						.show();

					// Set message.
					if ( responseObj.message !== undefined ) {
						$message = responseObj.message;
					} else {
						$message = 'There was an error saving your key.<br />Please try entering your BoldGrid Connect Key again.';
					}

					$( '#boldgrid_api_key_notice_message', $c_zakn )
						.html( $message ).addClass( 'error-color' );
				} else {
					// Hide loading.
					$( '#boldgrid-api-loading', $c_zakn )
						.hide();

					// Show button.
					$( '#submit_api_key', $c_zakn )
						.show();

					// Set message.
					if ( responseObj.message !== undefined ) {
						$message = responseObj.message;
					} else {
						$message = 'Your API key appears to be invalid!<br />Please try to enter your BoldGrid Connect Key again.';
					}

					$( '#boldgrid_api_key_notice_message', $c_zakn )
						.html( $message ).addClass( 'error-color' );
				}
			});
		};
	})( jQuery );
};

new IMHWPB.Api();
