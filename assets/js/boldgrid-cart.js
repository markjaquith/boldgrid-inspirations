var IMHWPB = IMHWPB || {};

IMHWPB.BoldGrid_Cart = function( configs ) {
	( function( $ ) {
		var self = this;

		this.configs = configs;
		this.api_url = this.configs.asset_server;
		this.api_key = this.configs.api_key;

		this.api_param = 'key';
		this.api_key_query_str = this.api_param + "=" + this.api_key;

		self.ajax = new IMHWPB.Ajax( configs );
		self.baseAdmin = new IMHWPB.BaseAdmin();

		$c_wpbody = $( '#wpbody' );

		$( function() {
			/**
			 * ********************************************************************
			 * Var definitions:
			 * ********************************************************************
			 */

			// The element on the page that holds the total cost, data-total-cost.
			total_cost_element = $( '.total_cost' );

			// The element on the page that holds the "insufficient funds" message.
			insufficient_funds_element = $( '.insufficient-funds' );

			// The element on the page that holds the user's coin balance.
			coin_balance_element = $( '[data-coin-balance]' );

			// The element on the page that contains any purchase errors.
			purchase_error_element = $( '#purchase_error' );

			// The element on the page that is the Purchase for Publish button
			self.purchase_all_for_publishing = $( '#purchase_all_for_publishing' );

			/**
			 * Error Messages
			 */

			// TOS - is it checked?
			error_tos_not_agreed_to = 'Please review the terms of service and<br />check the box above if you agree.';

			// Valid BoldGrid Connect Key.
			error_no_key_entered = 'Please enter your BoldGrid Connect Key!';
			error_key_too_short = 'Your BoldGrid Connect Key appears to be invalid, it is too short.';
			error_invalid_key = 'Invalid BoldGrid Connect Key!';

			/**
			 * ********************************************************************
			 * Actions
			 * ********************************************************************
			 */

			/*
			 * Determine whether or not to show/hide the insufficient funds message.
			 */
			self.toggle_insufficient_funds();

			/*
			 * Action to take when a checkbox is selected for an image.
			 */
			$( '.image-select' ).change( function() {
				// The price of this image.
				var image_price = $( this ).attr( 'data-coin-cost' );

				// The column containing this checkbox.
				var image_container = $( this ).closest( '.col-md-3' );

				// Is the checkbox checked?
				var checked = $( this ).attr( 'checked' ) ? true : false;

				// If applicable, add opacity to the image to show it's unselectd.
				if ( checked ) {
					image_container.removeClass( 'unselected-image' );
					self.baseAdmin.update_header_cart( image_price );
				} else {
					image_container.addClass( 'unselected-image' );
					self.baseAdmin.update_header_cart( -1 * image_price );
				}

				self.update_all_price_totals();

				// Update the 'checked_in_cart' attribute of the asset.
				var asset_id = $( this ).data( 'asset-id' );
				self.image_in_shopping_cart_checked( asset_id, checked );
			});

			// Validate connect_key and submit form to "Purchase all for publishing"
			$( 'button#purchase_all_for_publishing' ).on( 'click', function() {
				self.validate_form();
				return false;
			});

			// Purchase more coins button... do nothing for now.
			$( 'button.purchase-more-coins' ).on( 'click', function() {
				window.location.href = 'admin.php?page=boldgrid-purchase-coins';
				return false;
			});

			// Update the cart total after purchase.
			if ( typeof boldgrid_cart_total_coins_spent != 'undefined' ) {
				self.baseAdmin.update_header_cart( -1 * boldgrid_cart_total_coins_spent );
			}
		});

		/**
		 * Return the user's coin balance.
		 */
		this.get_coin_balance = function() {
			return Number( coin_balance_element.attr( 'data-coin-balance' ) );
		};

		/**
		 * Return the total cost.
		 */
		this.get_total_cost = function() {
			return Number( total_cost_element.attr( 'data-total-cost' ) );
		};

		/**
		 * Update the 'checked_in_cart' attribute of the asset.
		 */
		this.image_in_shopping_cart_checked = function( asset_id, checked ) {
			var data = {
				'action' : 'image_in_shopping_cart_checked',
				'asset_id' : asset_id,
				'checked' : checked,
			};

			$.post(ajaxurl, data, function( response ) {
				if ( 'success' != response ) {
					alert( 'Error, image selection has not been updated.' );
				}
			});
		};

		/**
		 * Display a message if there are any errors when trying to submit request
		 * for purchase.
		 */
		this.set_purchase_error = function( msg ) {
			purchase_error_element.html( msg );
		};

		/**
		 * Determine whether or not to show/hide the insufficient funds message and
		 * purchase button
		 */
		this.toggle_insufficient_funds = function() {
			// If we purchased all of the images on the page, how many coins would
			// we have left?
			var balance_if_purchased = ( self.get_coin_balance() - self.get_total_cost() );

			// If our balance is not high enough
			if (balance_if_purchased < 0) {
				insufficient_funds_element.removeClass( 'hidden' );

				// Also disable the Purchase for Publish button:
				self.purchase_all_for_publishing.attr( 'disabled', 'disabled' );
			} else {
				insufficient_funds_element.addClass( 'hidden' );

				// Also toggle the Purchase for Publish button, if needed:
				if ( self.get_coin_balance() <= 0 || self.get_total_cost() <= 0 ) {
					self.purchase_all_for_publishing.attr( 'disabled', 'disabled' );
				} else {
					self.purchase_all_for_publishing.removeAttr( 'disabled' );
				}
			}
		};

		/**
		 * Validate the form.
		 *
		 * If valid, submit the form to "purchase all for publishing".
		 */
		this.validate_form = function() {
			// clear any existing messages
			$( 'span#purchase_error' ).empty();

			/**
			 * ********************************************************************
			 * Validate Connect Key length
			 * ********************************************************************
			 */

			var boldgrid_connect_key = $( 'form#purchase_for_publish input[id=boldgrid_connect_key]' )
				.val().trim();

			// Abort if the user did not enter their BoldGrid Connect Key.
			if ( '' === boldgrid_connect_key ) {
				self.set_purchase_error( error_no_key_entered );
				return false;
			}

			// Abort if the user entered an invalid BoldGrid Connect Key (either too
			// long or short)
			if ( boldgrid_connect_key.length < 32 ) {
				self.set_purchase_error( error_key_too_short );
				return false;
			}

			/**
			 * ********************************************************************
			 * Ensure TOS has been agreed to.
			 * ********************************************************************
			 */

			// Abort if the user did not click the checkbox.
			if ( ! $( '#agree_to_tos' ).is( ':checked' ) ) {
				self.set_purchase_error( error_tos_not_agreed_to );
				return false;
			}

			/**
			 * ********************************************************************
			 * Validate the Connect Key with BoldGrid.
			 *
			 * If valid, submit the form.
			 * ********************************************************************
			 */
			var success_action = function( response ) {
				if ( response.result.data ) {
					$( 'form#purchase_for_publish' ).submit();
				} else {
					$( 'span#purchase_error' ).html( 'Invalid BoldGrid Connect Key!' );
				}
			};

			data = {
				'api_key' : boldgrid_connect_key
			};

			self.ajax.ajaxCall( data, 'validate_connect_key', success_action );
		};

		/**
		 *
		 */
		this.update_all_price_totals = function() {
			// The total cost of all images on all pages.
			var total_cost = 0;

			// The total cost of all images on one page.
			var total_page_cost = 0;

			/*
			 * Loop through every page. Based upon images that are selected,
			 * calculate the total coin cost of the page. Update the 'page coin
			 * cost'.
			 */

			/**
			 * ********************************************************************
			 * Loop through every page.
			 * ********************************************************************
			 */
			$( '[data-page-id]' ).each( function() {
				// The container holding all of the images for this page.
				var page_container = $( this );

				// The page id of this page.
				var page_id = page_container.data( 'page-id' );

				// Reset total_page_cost.
				var total_page_cost = 0;

				/**
				 * ********************************************************
				 * Loop through every image in this page container.
				 * ********************************************************
				 */
				page_container.find( '.image-select' ).each( function() {
					// The checkbox that represents this image.
					var image = $( this );

					// The cost of this image.
					var image_cost = image.data( 'coin-cost' );

					// Is this image selected?
					if ( image.attr( 'checked' ) ) {
						total_cost += image_cost;
						total_page_cost += image_cost;
					}
				});

				// We are done looping through every image in this page.
				// Now, update the cost of this page.
				var page_cost_element = page_container
					.find( '.total-page-cost-' + page_id );
				page_cost_element.html( total_page_cost )
					.attr( 'data-total-page-cost', total_page_cost );
			});

			/*
			 * We are now done looping through all of the pages. We now need to
			 * update the summary items at the bottom of the page, such as total
			 * cost, toggle 'insufficent funds', etc.
			 */
			// Update the total coin cost
			total_cost_element.html( total_cost ).attr( 'data-total-cost', total_cost );
			// Toggle the "Insufficient funds" notice.
			self.toggle_insufficient_funds();
		};
	})( jQuery );
};

new IMHWPB.BoldGrid_Cart( IMHWPB.configs );
