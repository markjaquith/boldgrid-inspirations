var IMHWPB = IMHWPB || {};

/**
 * Add GridBlock Set / New From GridBlocks.
 *
 * A class used on the "New From GridBlocks" page.
 *
 * @since 1.0.10
 */
IMHWPB.AddGridBlockSet = function($) {
	var self = this;

	self.baseAdmin = new IMHWPB.BaseAdmin();

	// Create selectors.
	self.$main_container = $('#gridblock_sets', self.baseAdmin.$wrap);
	self.$loading_message = $('#loading_message', self.baseAdmin.$wrap);

	// A collection of GridBlock Sets.
	self.gridblock_sets;

	// An object of strings used within this class.
	self.strings = {
		homepage_iframe : '<iframe id="homepage" src="' + IMHWPB.homepage_url + '"></iframe>',
		homepage_iframe_loading : '<div style="position:fixed; top:45%; left:45%;">Loading preview...</div>',
		select_message : '<p>Select a GridBlock Set below to use with your new page.</p>'
	};

	jQuery(function() {
		self.init();
	});

	/**
	 * Update ajaxurl for staging.
	 *
	 * If we're staging, append "?staging=1" to ajaxurl.
	 *
	 * @since 1.0.10
	 */
	this.ajaxurl_get = function() {
		if (1 == self.baseAdmin.GetURLParameter('staging')) {
			return ajaxurl + '?staging=1';
		} else {
			return ajaxurl;
		}
	};

	/**
	 * Load an iframe with a src to the front end of the site.
	 *
	 * This iframe will be used to display previews of each selection when
	 * they're clicked. This iframe is also scanned for stylesheets, each of
	 * which are applied to the smaller, preview iframes.
	 *
	 * @since 1.0.9
	 */
	this.create_homepage_iframe = function() {
		// Add the <iframe /> markup to our page_previwer.
		self.$page_previewer_content.append(self.strings.homepage_iframe);

		// Create a reference to this iframe.
		self.$homepage_iframe = self.$page_previewer_content
				.find('iframe#homepage');

		// When the iframe loads:
		self.$homepage_iframe.load(function() {
			var head = self.$homepage_iframe.contents().find( 'head' ),
				isBoldGridTheme = ( head.find( 'link[href*="/themes/boldgrid-"]' ).length > 0 );

			/*
			 * Get all of the stylesheets.
			 * If this is not a BoldGrid theme, only get bootstrap for the grid.
			 */
			if( isBoldGridTheme ) {
				self.$homepage_iframe_stylesheets = head.find( 'link[rel="stylesheet"] ');
			} else {
				self.$homepage_iframe_stylesheets = head.find(
					'link[rel="stylesheet"][href*="/boldgrid-inspirations/assets/css/bootstrap/bootstrap."]' );
			}

			self.gridblock_set_preview_cleanup();

			// Once we have all of the stylesheets for our homepage, fill our
			// main container with content.
			self.main_container_fill();

			// remove this.
			self.$homepage_iframe.unbind();

			// Remove the iframe's src so that on first preview, we can properly
			// show our loading message.
			self.$homepage_iframe.attr('src', '');
		});
	};

	/**
	 * Preview a GridBlock Set.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_set_preview = function(category, key) {
		self.page_previewer.open();

		self.gridblock_set_preview_show_loading();

		// Add click event to 'Select'.
		$('#page_previewer .media-toolbar-primary button.button-primary')
				// Remove previous click events, otherwise we would be installing every page previewed.
				.unbind( 'click' )
				.on(
						'click',
						function() {
							$(this)
									.prop("disabled", true)
									.html("Installing")
									.css('margin-left', '0px')
									.after(
											"<span class='spinner inline left-of-button'></span>");
							self.gridblock_set_install(category, key);
						});

		// Create the preview.
		var data = {
			'action' : 'gridblock_set_create_preview',
			'key' : key,
			'category' : category
		};

		jQuery.post(self.ajaxurl_get(), data, function(response) {
			self.$homepage_iframe.attr('src', response);

			self.$homepage_iframe.load(function() {
				self.gridblock_set_preview_cleanup();
			});
		});
	};

	/**
	 * Clean up a preview.
	 *
	 * For example, remove the admin bar, the 'edit' link, etc. You don't need
	 * to see these in a preview.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_set_preview_cleanup = function() {

		// Remove the admin bar, not really needed during a preview.
		self.$homepage_iframe.contents().find( 'body #wpadminbar, #boldgrid-reset-adminbar' ).remove();

		// Remove the empty spacing from missing wpadminbar.
		self.$homepage_iframe.contents().find( 'head' ).append(
			$( '<style id="boldgrid-reset-adminbar" type="text/css"></style>' ).html( 'html{margin-top: 0px !important;}' )
		);

		// Remove the "Edit" link, not really needed during a preview.
		self.$homepage_iframe.contents().find( 'body .post-edit-link' ).remove();

		// Prevent any link clicks within the iframe.
		// This iframe is meant to preview a single page, and not for browsing
		// the site.
		self.$homepage_iframe.contents().find( 'body a' ).on( 'click', function( e ) {
			e.preventDefault();
		});
	};

	/**
	 *
	 */
	this.gridblock_set_preview_fill = function( $iframe, item ) {
		$iframe.contents().find( 'head' )
			.append( self.$homepage_iframe_stylesheets.clone() );

		// prevents scrollbars on the iframe.
		$iframe.contents().find( 'body' ).css({
			'overflow': 'hidden',
			'padding-top': '30px'
		});

		// Add our content to the preview.
		$iframe.contents().find( 'body' )
			.html( '<div class="container">' + item.preview_data.post_content + '</div>' );

		$iframe.contents().find( 'body' )
			.addClass( 'palette-primary mce-content-body' );
	};

	/**
	 * Show a "preview is loading" message.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_set_preview_show_loading = function() {
		self.$homepage_iframe.contents().find('body').css('background-image',
				'none').css('background', '#fff');

		self.$homepage_iframe.contents().find('body').html(
				self.strings.homepage_iframe_loading);
	};

	/**
	 * Create a 'lightbox' for our selection previews.
	 *
	 * The media modal makes a nice 'lightbox'. We'll create a media modal, wipe
	 * it clean, and use it as we wish.
	 *
	 * @since 1.0.9
	 */
	this.create_media_modal_previewer = function() {
		// Create the media modal.
		self.page_previewer = wp.media({
			title : 'Preview',
			id : 'page_previewer',
			frame : 'select'
		});

		// Imediately open / close it so that #page_previewer exists in the dom
		// for immediate manipulation.
		self.page_previewer.open().close();

		// Create a reference to our media modal.
		var div_page_previewer = $('#page_previewer', 'body');

		// Create global references to the title, router, and content areas of
		// our router.
		self.$page_previewer_title = div_page_previewer
				.find('.media-frame-title');
		self.$page_previewer_router = div_page_previewer
				.find('.media-frame-router');
		self.$page_previewer_content = div_page_previewer
				.find('.media-frame-content');

		// The router is not needed, remove it.
		self.$page_previewer_router.remove();

		// Empty the contents.
		self.$page_previewer_content.empty();

		// Get the "Select" button.
		self.$page_previewer_select_button = div_page_previewer
				.find('.media-frame-toolbar .media-toolbar .media-button-select');

		// The 'Select' button is disabled by default. Enable it.
		self.$page_previewer_select_button.prop('disabled', false);

		// Add a "Go Back" button.
		self.$page_previewer_select_button
				.after("<a class='button button-secondary media-button button-large'>Go Back</a>");

		// Create a reference to our new "Go Back" button.
		self.$page_previewer_go_back_button = self.$page_previewer_select_button
				.siblings('.button-secondary');

		// Bind the click of 'Go Back'.
		// Essentailly, clicking 'Go Back' closes the media modal.
		self.$page_previewer_go_back_button.on('click', function() {
			self.page_previewer.close();
			self.$homepage_iframe.attr('src', '');
		});
	};

	/**
	 * Force fresh data on page load.
	 *
	 * When this page starts to render, #new_from_gridblocks_loaded == false.
	 *
	 * When this page has completely finished rendering,
	 * #new_from_gridblocks_loaded == true.
	 *
	 * Because we're in init(), #new_from_gridblocks_loaded SHOULD == false.
	 *
	 * If it doesn't, the user probably clicked their back button, and
	 * #new_from_gridblocks_loaded == true because of cache.
	 *
	 * In the event we're dealing with true, refresh the page to ensure fresh
	 * content.
	 *
	 * @since 1.0.10
	 */
	this.force_fresh_data = function() {
		if ('true' == $('#new_from_gridblocks_loaded', self.baseAdmin.$wrap)
				.val()) {
			location.reload(true);
		}
	};

	// We want to refresh the page as soon as we know we should. Therefore, run
	// this.force_fresh_data() immediately after it is declared.
	self.force_fresh_data();

	/**
	 * Install a GridBlock set.
	 *
	 * Make an ajax call to WP and request that it install a gridblock set into
	 * a new page via the GridBlock's category and key.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_set_install = function(category, key) {
		var data = {
			'action' : 'gridblock_set_create_page',
			'key' : key,
			'category' : category
		};

		jQuery.post(self.ajaxurl_get(), data, function(response) {
			window.location = 'post.php?post=' + response + '&action=edit';
		});
	};

	/**
	 * Get our GridBlock Sets.
	 *
	 * We get our GridBlock Sets from the DOM (if cached) or via ajax (if
	 * getting fresh data).
	 *
	 * @since 1.0.10
	 */
	this.gridblock_sets_get = function() {
		// If we don't have any cached data:
		if ( 'undefined' == typeof IMHWPB.gridblock_sets ||
			! IMHWPB.gridblock_sets.kitchen_sink ||
			0 === IMHWPB.gridblock_sets.kitchen_sink.length ) {
				self.$loading_message.removeClass( 'hidden' )
					.html(
						'<span class="spinner inline"></span> ' +
						'Downloading the newest GridBlock Sets. ' +
						'This may take up to one minute this first time.' );

			var data = {
				'action' : 'get_gridblock_sets'
			};

			jQuery.post(self.ajaxurl_get(), data, function( response ) {
				if ( 0 == response ) {
					self.gridblock_sets_invalid();
				} else {
					self.gridblock_sets = JSON.parse( response );
					self.gridblock_sets_validate();
				}
			});
		} else {
			self.$loading_message
					.removeClass('hidden')
					.html("<p><span class='spinner inline'></span>Loading GridBlock Sets.</p>");

			self.gridblock_sets = IMHWPB.gridblock_sets;
			self.gridblock_sets_validate();
		}
	};

	/**
	 * Actions to take when our GridBlock Sets is invalid.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_sets_invalid = function() {
		// Hide the loading message.
		self.$loading_message.addClass( 'hidden' );

		var template = wp.template( 'gridblock_set_error_fetching' );
		self.$main_container.before( template() );

		// Bind the "Try again" button.
		$( '#try_again' ).on( 'click', function() {
			// Remove the error message.
			$( this ).closest( 'div' ).remove();

			// Try to get the GridBlock Sets again.
			self.gridblock_sets_get();
		});
	};

	/**
	 * Actions to take when our GridBlock Sets are valid.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_sets_valid = function() {
		self.create_media_modal_previewer();
		self.create_homepage_iframe();
	};

	/**
	 * Validate our GridBlock Sets.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_sets_validate = function() {
		var valid = false;

		if ( 'undefined' != typeof self.gridblock_sets.kitchen_sink &&
			'undefined' != typeof self.gridblock_sets.kitchen_sink.data &&
			'undefined' != typeof self.gridblock_sets.kitchen_sink.data.pages &&
			self.gridblock_sets.kitchen_sink.data.pages.length > 0 ) {
				valid = true;
		}

		if (valid) {
			self.gridblock_sets_valid();
		} else {
			self.gridblock_sets_invalid();
		}
	};

	/**
	 * Init the page.
	 *
	 * @since 1.0.10
	 */
	this.init = function() {
		self.gridblock_sets_get();
	};

	/**
	 * Fill our main container with GridBlock Set previews.
	 *
	 * @since 1.0.10
	 */
	this.main_container_fill = function() {
		// Start off by emptying the main container.
		self.$main_container.empty();

		// Add our blank container.
		var template = wp.template( 'gridblock_set_blank_container' );
		self.$main_container.append( template );
		self.$main_container.find( '.gridblock-set.blank' )
			.on( 'click', function() {
				$(this)
					.find( 'a.button-primary' )
					.attr( 'disabled', 'disabled' )
					.css( 'opacity', 1 )
					.html( "Loading" )
					.after( "<span class='spinner inline left-of-anchor'></span>" );
				window.location.href = 'post-new.php?post_type=page&';
			});

		template = wp.template( 'gridblock_set_container' );

		var category = 'kitchen_sink';

		$.each( self.gridblock_sets[category].data.pages, function( key, item ) {
			var data = {
				title : item.preview_data.post_title,
				post_type : item.preview_data.post_type,
				wp_page_layout : item.preview_data.wp_page_layout,
				key : key,
				category : category
			};
			self.$main_container.append(template(data));

			var $iframe = $( '[data-gridblock-set-key=' + key +
				'][data-gridblock-set-category="' + category + '"]' ).find( 'iframe' );

			/*
			 * Below are references to NON FIREFOX and FIREFOX.
			 *
			 * @see http://stackoverflow.com/questions/24686443/setting-content-of-iframe-using-javascript-fails-in-firefox
			 */

			// NON FIREFOX
			self.gridblock_set_preview_fill( $iframe, item );
			// FIREFOX
			$iframe.load( function() {
				self.gridblock_set_preview_fill( $iframe, item );
			});

			// Add click event to 'preview'.
			var $preview_button = $iframe.siblings( '.preview-fader' );
			$preview_button.on( 'click', function() {
				self.gridblock_set_preview( category, key );
			});

			// Add click event to 'Select'.
			var $select_button = $iframe.closest( '.gridblock-set' )
				.find( '.controls a' ).on( 'click', function() {
					// 1: Disable the button.
					// 2: Show the button (even if
					// the user mouses out).
					// 3: Set the button text to
					// "Installing..."
					$( this ).attr( 'disabled', 'disabled' )
						.css( 'opacity', 1 )
						.html( 'Installing' )
						.after( "<span class='spinner inline left-of-anchor'></span>" );

					self.gridblock_set_install( category, key );
				});
		});

		// After we have filled our main container, hide the loading message.
		self.$loading_message.addClass( 'hidden' );

		self.$main_container.before( self.strings.select_message );

		// When all is said and done, label it so.
		// Please see comments within this.force_fresh_data() for more details.
		$( '#new_from_gridblocks_loaded', self.baseAdmin.$wrap ).val( 'true' );
	};
};

new IMHWPB.AddGridBlockSet(jQuery);
