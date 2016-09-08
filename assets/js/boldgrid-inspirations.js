var IMHWPB = IMHWPB || {};

/**
 * Inspirstions, design first.
 *
 * @since 1.2.3
 */
IMHWPB.InspirationsDesignFirst = function( $, configs ) {
	var self = this;

	this.configs = configs;
//	this.api_url = this.configs.asset_server;
//	this.api_key = this.configs.api_key;
//	this.api_param = 'key';
//	this.api_key_query_str = this.api_param + "=" + this.api_key;

	self.ajax = new IMHWPB.Ajax( configs );

	self.$categories = $( '#categories' );

	self.categories = '';

	self.$themes = $( '.theme-browser .themes');
	self.themes = '';

	self.$theme = '';
	self.$pageset = '';
	self.$budget = '';

	/**
	 * The selected sub category id in step 1.
	 *
	 * @since 1.2.5
	 */
	self.subCategoryId = '0';

	// scroll position.
	self.scrollPosition = '';

	/**
	 * Enable or disable all actions on the page.
	 *
	 * @since 1.2.3
	 */
	this.allActions = function( effect ) {
		if( 'disable' === effect ) {
			$( 'body' ).addClass( 'waiting' );
			$( '.top-menu a' ).addClass( 'disabled' );
			$( '#build-summary button' ).attr( 'disabled', true );
		} else {
			$( 'body' ).removeClass( 'waiting' );
			$( '.top-menu a' ).removeClass( 'disabled' );
			$( '#build-summary button' ).attr( 'disabled', false );
		}
	};

	/**
	 * User chooses a theme
	 *
	 * @since 1.2.3
	 */
	this.chooseTheme = function( ) {
		// Immediately hide the iframe to give a better transition effect.
		$( '#screen-content iframe#theme-preview' )
			.addClass( 'hidden' )
			.css( 'display', '' );

		// Load the theme title and sub category title.
		$( '#sub-category-title' ).html( '- ' + self.$theme.closest( '.theme' ).attr( 'data-sub-category-title' ) );
		$( '#theme-title' ).html( self.$theme.closest( '.theme' ).attr( 'data-theme-title' ) );

		self.toggleStep( 'content' );

		$( '[data-step="content"]' ).removeClass( 'disabled' );

		// Reset the coin budget to 20.
		$( 'input[data-coin="20"]' ).prop( 'checked', true );

		self.initPagesets();
	};

	/**
	 *
	 */
	this.toggleCheckbox = function () {
		var $subCategory = $( 'input[name="sub-category"]:checked' );
		$subCategory.parent().css( 'background', 'blue' );
	};

	/**
	 * @summary Toggle the notes listed in the last step, the confirmation page.
	 *
	 * For example, if you click the radio button to overwrite your existing site, there is a note
	 * that your pages will be trashed. This method should show that note.
	 *
	 * @since 1.2.5
	 */
	this.toggleConfirmationNotes = function() {
		var installDecision = $( 'input[name="install-decision"]:checked' ).val(),
			showStagingDecisions = [ 'download-staging', 'install-as-staging', 'activate-staging' ];

		// Begin by hiding all of the notes, which is any paragraph with a class startign with note-.
		$( '.wrap.confirmation .top p[class^="note-"]' ).hide();

		/*
		 * If there is no install decision, there are no notes that need to be toggled. For example,
		 * on a fresh install there will be no site and no Staging pluin installed, so the user
		 * does not need to make a decision, just install. At this point, we can abort.
		 */
		if( installDecision === undefined ) {
			return;
		}

		// Toggle the approprate note based upon the install decision.
		if( -1 !== showStagingDecisions.indexOf( installDecision ) ) {
			$( '.note-download-staging' ).show();
		} else if( 'overwrite-active' === installDecision ) {
			$( '.note-overwrite' ).show();
		} else if( 'overwrite-staging' === installDecision ) {
			$( '.note-overwrite-staging' ).show();
		}
	};

	/**
	 * @summary Actions to take when a device preview button is clicked.
	 *
	 * @since 1.2.3
	 */
	this.devicePreviews = function () {
		var $previewContainer = $( '#preview-container' );

		$( '.wrap' ).on( 'click', '.devices button', function() {
			var $button = $( this ), iframeClass;

			/*
			 * If we're waiting on a preview to load, don't allow the user to click different device
			 * previews.
			 */
			if( $( 'body' ).hasClass( 'waiting' ) ) {

				// When you click automatically, focus will be added. Remove it.
				if( $button.is( ':focus' ) ) {
					$button.blur();
				}

				return;
			}

			/*
			 * If we're clicking on a device preview button, we're forcing dimensions. We can remove
			 * the default highlight (which is based upon the preview's dimensions.
			 */
			$( '.devices button' ).removeClass( 'highlight' );

			// Determine which preview button we've clicked on.
			if( $button.hasClass( 'preview-desktop' ) ) {
				iframeClass = 'preview-desktop';
			} else if( $button.hasClass( 'preview-tablet' ) ) {
				iframeClass = 'preview-tablet';
			} else {
				iframeClass = 'preview-mobile';
			}

			/*
			 * If the button is already active and we're clicking on it again, disable a device
			 * preview and just show the preview full width (the default behavior / view when going
			 * to step 2 for the first time.
			 */
			if( $button.hasClass( 'active' ) ) {
				$button
					.removeClass( 'active' )
					.blur();

				$previewContainer.removeClass();
				self.highlightDeviceButton();

				return;
			}

			// Remove the active class from the previously active button.
			$( '.devices .active' )
				.attr( 'aria-pressed', 'false' )
				.removeClass( 'active' );

			// Mark the current device preview button as active.
			$( this )
				.attr( 'aria-pressed', 'true' )
				.addClass( 'active' );

			$previewContainer
				.removeClass()
				.addClass( iframeClass );
		});
	};

	/**
	 * Event handler for the back button on step 2.
	 */
	this.backButton = function() {
		$( '.inspirations.button-secondary' ).on( 'click', function() {
			self.toggleStep( 'design' );
		});
	};

	/**
	 * @summary Bind the click of various elements.
	 *
	 * @since 1.2.5
	 */
	this.bindClicks = function() {

		/*
		 * During step 1, if there is an error fetching categories, we'll give the user a button to
		 * try again. Handle the click of that try again button.
		 */
		$( '.wrap' ).on( 'click', '#try-categories-again', self.initCategories );

		/*
		 * During step 1, if there is an error fetching themes, we'll give the user a button to try
		 * again. Handle the click of that try again button.
		 */
		$( '.wrap' ).on( 'click', '#try-themes-again', self.initThemes );

		/*
		 * During step 2, if there is an error fetching pagesets, we'll give the user a button to
		 * try again. Handle the click of that try again button.
		 */
		$( '.wrap' ).on( 'click', '#try-pagesets-again', self.initPagesets );
	};

	/**
	 *
	 */
	this.bindInstallModal = function() {
		$( 'button.install' ).click( function() {
			$('.wrap.main').addClass('hidden');
			$('.wrap.confirmation').removeClass('hidden');
			self.toggleConfirmationNotes();
		});

		$( 'button.go-back' ).on( 'click', function() {
			$('.wrap.main').removeClass('hidden');
			$('.wrap.confirmation').addClass('hidden');
		});

		// Take action when someone clicks on a install-decision radio button.
		$( '.wrap' ).on( 'click', 'input[type="radio"]', function() {
			self.toggleConfirmationNotes();
		});

		/*
		 * Bind click of "Install this website!".
		 *
		 * This is the button that submits the #post_deploy form and actually installs a website.
		 */
		$( 'button.install-this-website' ).on( 'click', function() {
			// Get our install decision.
			var installDecision = $( 'input[name="install-decision"]:checked' ).val(), data;

			// Disable the "Go back" and "Install this website" buttons.
			$( '#install-buttons button' ).prop( 'disabled', true );

			// Show a spinner
			$( '#install-buttons' ).append( '<span class="spinner inline"></span>' );


			switch( installDecision ) {

				/*
				 * Install as Active site.
				 *
				 * If installDecision is undefined, it means there is no install decision, install
				 * to active site.
				 */
				case 'install-as-active':
				case undefined:
					$( '#post_deploy' ).submit();
					break;

				// Install as Staging site.
				case 'install-as-staging':
					$( 'input[name="staging"]' ).val( 1 );
					$( '#post_deploy' ).submit();
					break;

				// Install as Active site, overwriting existing active site.
				case 'overwrite-active':
					$( '#start_over' ).val( 'true' );
					$( '#post_deploy' ).submit();
					break;

				// Install as Staging site, overwriting existing staging site.
				case 'overwrite-staging':
					$( 'input[name="staging"]' ).val( 1 );
					$( '#start_over' ).val( 'true' );
					$( '#post_deploy' ).submit();
					break;

				case 'download-staging':
					data = {
						'action': 'install_staging',
						'boldgrid-plugin-install[boldgrid-staging]': 'install',
						'nonce-install-staging': $( '#nonce-install-staging' ).val(),
					};

					$.post(ajaxurl, data, function( response ) {
						/*
						 * Validate success of installing staging.
						 *
						 * Installing staging via ajax produces a bit of output. If the last character
						 * of the output is a 1, success, otherwise failure.
						 */
						if( '1' === response.substr( response.length - 1)) {
							$( 'input[name="staging"]' ).val( 1 );
							$( '#start_over' ).val( 'true' );
							$( '#post_deploy' ).submit();
						} else {
							alert ('failed setting up staging plugin');
						}
					});
					break;

				case 'activate-staging':
					data = {
						'action': 'activate_staging',
						'nonce-install-staging': $( '#nonce-install-staging' ).val(),
					};

					$.post(ajaxurl, data, function( response ) {
						if( '1' === response ) {
							$( 'input[name="staging"]' ).val( 1 );
							$( '#start_over' ).val( 'true' );
							$( '#post_deploy' ).submit();
						} else {
							alert ('failed activating up staging plugin');
						}
					});
					break;
			}
		});
	};

	/**
	 * Checks to see if the mobile menu is actually displayed.
	 *
	 * @return boolean
	 */
	this.isMobile = function() {
		return ( $( '.wp-filter:visible').length === 0 ? false : true );
	};

	/**
	 * Toggle the mobile menu open and closed.
	 */
	this.mobileToggle = function() {
		$( '.left' ).toggle( 'slow' );
		$( '.drawer-toggle' ).toggleClass( 'open' );
	};

	/**
	 * Force the mobile menu to close.
	 */
	this.mobileCollapse = function() {
		var $mobileMenu = $( '.left' );
		if ( $mobileMenu.is( ':visible' ) ) {
			self.mobileToggle();
		}
	};

	this.mobileMenuToggle = function() {
		$( '.drawer-toggle' ).on( 'click', function() {
			self.mobileToggle();
		});
	};

	/**
	 * @summary Actions to take when the window is resized.
	 *
	 * This method is triggered from init().
	 *
	 * @since 1.2.5
	 */
	this.onResize = function() {

		/*
		 * When the window is resized, wait 0.4 seconds and readjust the highlighted device preview
		 * button.
		 */
		$( window ).resize( function() {
		    clearTimeout( $.data( this, 'resizeTimer' ) );

		    $.data( this, 'resizeTimer', setTimeout( function() {
		    	self.highlightDeviceButton();
		    }, 400 ) );
		});
	};

	/**
	 * @summary Handles the Show All filter.
	 *
	 * @since 1.2.3
	 */
	 this.showAll = function() {
		$( '.wrap' ).on( 'click', '[data-sort="show-all"]', function() {
			var $all = $( '[data-sub-category-id="0"]' ),
			    ref = $all.parent( '.sub-category' );

			// Remove all active classes from sub categories.
			$( '.sub-category.active' ).removeClass( 'active' );
			// Check radio.
			$all.prop( 'checked', true );
			// Check radio check.
			if ( $all.is( ':checked' ) ) {
				ref.addClass( 'active' );
			}
			// collapse mobile.
			self.mobileCollapse();
			// Update filter text.
			self.updateFilterText( 'All' );
			// Display all themes.
			self.toggleSubCategory( 0 );
			// toggle the current class for show all.
			self.toggleShowAll( ref );
		});
	};

	/**
	 * @summary Shuffle an array.
	 *
	 * Used to shuffle our generic builds. If we didn't shuffle those, you'd see them grouped by
	 * theme.
	 *
	 * @since 1.2.3
	 * @link http://stackoverflow.com/questions/3718282/javascript-shuffling-objects-inside-an-object-randomize
	 */
	this.shuffle = function( myArray ) {
		var i = myArray.length, j, tempi, tempj;

		if ( i === 0 ) {
			return false;
		}

		while ( --i ) {
			j = Math.floor( Math.random() * ( i + 1 ) );

		    tempi = myArray[i];
		    tempj = myArray[j];

		    myArray[i] = tempj;
		    myArray[j] = tempi;
		}

		return myArray;
	};

	/**
	 * @summary Sort all builds based upon "All Order".
	 *
	 * @since 1.2.3
	 */
	this.sortAll = function( genericBuilds ) {
		return $( genericBuilds ).sort( function( a, b ) {
			if( ! a.AllOrder ) {
				return 1;
			}

			return ( parseInt( a.AllOrder ) > parseInt( b.AllOrder ) ? 1 : -1 );
		});
	};

	/**
	 * @summary Sort themes.
	 *
	 * @since 1.2.3
	 */
	this.sortThemes = function( sortBy ) {
		$( '.themes .theme:visible' ).sort( function( a, b ) {
			var aSort = parseInt( $( a ).attr( sortBy ) ),
				bSort = parseInt( $( b ).attr( sortBy ) );

			if( ! aSort ) {
				return 1;
			}

			return ( aSort > bSort ? 1 : -1 );
		}).prependTo( '.themes' );
	};

	/**
	 * Toggle the show all current class.
	 */
	this.toggleShowAll = function( o ) {
		var $showAll = $( '[data-sort="show-all"]' ),
		    $subcatId = o.find( '[data-sub-category-id]' ).data( 'sub-category-id');

		// Add current class to show all filter if previewing all themes.
		$showAll.addClass( 'current' );
		// If we aren't clicking on All remove that class.
		if ( 0 !== $subcatId ) {
			$showAll.removeClass( 'current' );
		}
	};

	/**
	 * Update the filter text on the mobile view.
	 */
	this.updateFilterText = function( text ) {
		$( '.theme-count' ).text( text );
	};

	/**
	 * Subcategories event handler.
	 */
	this.subcategories = function() {
		// Subcategories.
		$( '.wrap' ).on( 'click', '.sub-category', function() {
			var $subCategory = $( this ).find( 'input[name="sub-category"]' ),
			    $subcategoryName = $( this ).find( '.sub-category-name' ).text(),
			    subCategoryId = $subCategory.attr( 'data-sub-category-id' ),
			    ref = $( this );

			/*
			 * Keep track of the sub category id the user clicked.
			 *
			 * If the user is clicking a sub category that's already showing (IE they click Fashion and
			 * then click Fashion immediately again), abort. We don't want the builds to be continually
			 * shuffled.
			 */
			if( subCategoryId === self.subCategoryId ) {
				return;
			} else {
				self.subCategoryId = subCategoryId;
			}

			// Reset scroll position.
			window.scrollTo( 0, 0 );
			// Remove any active classes.
			$( '.sub-category.active' ).removeClass( 'active' );
			// Mark subcategory as active.
			$subCategory.prop( 'checked', true );
			// Add active class.
			if ( $subCategory.is( ':checked' ) ) {
				ref.addClass( 'active' );
			}
			self.updateFilterText( $subcategoryName );
			// Toggle the show all filter.
			self.toggleShowAll( ref );
			// Mobile actions.
			if ( self.isMobile() ) {
				// Collapse the menu when selection is made.
				self.mobileToggle();
			}
			// Always toggle subcategory.
			self.toggleSubCategory( subCategoryId );
		});
	};

	/**
	 * Selects theme to load to continue on to step 2 of inspirations.
	 */
	this.selectTheme = function() {
		$( '.wrap' ).on( 'click', '.theme', function() {
			self.$theme = $( this );
			self.chooseTheme();
		});
	};

	/**
	 * Sets the hover colors class.
	 */
	this.hoverColors = function() {
		// Hovers.
		$( '.wrap' ).on( 'mouseenter mouseleave', '.sub-category, .pageset-option, .coin-option', function() {
			$( this ).toggleClass( 'blue' );
		});
	};

	/**
	 * @summary Based upon the preview size, highlight which device preview is closest.
	 *
	 * For example, if your preview is only 300px wide, highlight the phone preview button.
	 *
	 * @since 1.2.5
	 */
	this.highlightDeviceButton = function() {
		// Get the active button.
		var $activeButton = $( '.devices button.active' ), previewWidth;

		// If we have an active button, there's no need to highlight at this point, abort.
		if( $activeButton.length > 0 ) {
			return;
		}

		// Remove our highlight class from all buttons, we'll add it back in a moment.
		$( '.devices button' ).removeClass( 'highlight' );

		// Determine width of our preview.
		previewWidth = $( '#preview-container' ).outerWidth();

		// Highlight the appropriate device button.
		if( previewWidth <= 320 ) {
			$( '.devices .preview-mobile' ).addClass( 'highlight' );
		} else if( previewWidth < 768 ) {
			$( '.devices .preview-tablet' ).addClass( 'highlight' );
		} else {
			$( '.devices .preview-desktop' ).addClass( 'highlight' );
		}
	};

	/**
	 * Click event handler for pageset options section.
	 */
	this.pagesetOptions = function() {
		// Pageset Options.
		$( '.wrap' ).on( 'click', '.pageset-option', function() {

			// If we're waiting on something, don't allow the user to select a different pageset.
			if( $( 'body' ).hasClass( 'waiting' ) ) {
				return;
			}

			var $pagesetInput = $( this ).find( 'input[name="pageset"]' );

			$( '.pageset-option.active' ).removeClass( 'active' );

			$pagesetInput.prop( 'checked', true );

			if ( $pagesetInput.is( ':checked' ) ) {
				$( this ).addClass( 'active' );
			}

			self.$pageset = $( 'input[name="pageset"]:checked' );

			self.loadBuild();
		});
	};

	/**
	 * Click event handler for coin budget options section.
	 */
	this.coinOptions = function() {
		// Coin Budgets.
		$( '.wrap' ).on( 'click', '.coin-option', function() {

			// If we're waiting on something, don't allow the user to select a different budget.
			if( $( 'body' ).hasClass( 'waiting' ) ) {
				return;
			}

			var $coinInput = $( this ).find( 'input[name="coin-budget"]' );

			$( '.coin-option.active' ).removeClass( 'active' );

			$coinInput.prop( 'checked', true );

			if ( $coinInput.is( ':checked' ) ) {
				$( this ).addClass( 'active' );
			}

			self.$budget = $( 'input[name="coin-budget"]:checked' );

			self.loadBuild();
		});
	};

	/**
	 * Loads the iframe for the theme preview.
	 */
	this.iframeLoad = function() {
		$( '#screen-content iframe#theme-preview' ).on( 'load', function() {
			var $iframe = $( this );
			$( '#screen-content .boldgrid-loading' ).fadeOut( function() {
				self.allActions( 'enable' );
				$( '#build-cost' )
					.html( $iframe.attr( 'data-build-cost' ) + ' Coins' )
					.animate( { opacity: 1 }, 400 );
				$( '#screen-content iframe#theme-preview' ).fadeIn();
			} );
		});
	};

	/**
	 * Manages the steps (tabs) of inspirations.
	 */
	this.steps = function() {
		$( '.wrap' ).on( 'click', '.top-menu [data-step]', function() {
			var $link = $( this ),
				step = $link.attr( 'data-step' );

			if( $link.hasClass( 'disabled' ) ) {
				return;
			} else {
				self.toggleStep( step );
			}
		});
	};

	/**
	 * Init.
	 *
	 * @since 1.2.3
	 */
	this.init = function() {
		self.bindClicks();
		self.initCategories();
		self.toggleCheckbox();
		self.devicePreviews();
		self.backButton();
		self.mobileMenuToggle();
		self.subcategories();
		self.selectTheme();
		self.showAll();
		self.hoverColors();
		self.coinOptions();
		self.pagesetOptions();
		self.iframeLoad();
		self.steps();
		self.bindInstallModal();
		self.onResize();
	};

	/**
	 * Init the list of categories.
	 *
	 * @since 1.2.3
	 */
	this.initCategories = function( ) {
		var failureMessage, failAction, success_action;

		// Show a loading message to the user that we're fetching categories.
		self.$categories.html( Inspiration.fetchingCategories + ' <span class="spinner inline"></span>' );

		// Define a message for users when fetching themes has failed.
		failureMessage = Inspiration.errorFetchingCategories + ' ' + Inspiration.tryFewMinutes + '<br />' +
		'<button class="button" id="try-categories-again">' + Inspiration.tryAgain + '</button>';

		// Display a 'Try again' message to the user if our call to get active categories fails.
		failAction = function() {
			self.$categories.html( failureMessage );
		};

		success_action = function( msg ) {
			var template = wp.template('init-categories');

			self.categories = msg.result.data.categories;

			/*
			 * If our categories are not valid or we have 0 categories, show a 'Try again' message
			 * and abort.
			 */
			if( self.categories === undefined || $.isEmptyObject( self.categories ) ) {
				self.$categories.html( failureMessage );

				return;
			}

			self.$categories.html( ( template( self.categories ) ) );
			self.initThemes();
		};

		self.ajax.ajaxCall( {'inspirations_mode' : 'standard'}, 'get_categories', success_action, failAction );
	};

	/**
	 * @summary Init pagesets.
	 *
	 * After the ajax request comes back with pagesets, choose the base pageset and continue to load
	 * that site into the iframe.
	 *
	 * @since 1.2.5
	 */
	this.initPagesets = function() {
		// Define a message for users when fetching pagesets has failed.
		var failureMessage = Inspiration.errorFetchingPagesets + ' ' + Inspiration.tryFewMinutes + '<br />' +
		'<button class="button" id="try-pagesets-again">' + Inspiration.tryAgain + '</button>',
			// If there are any issues with fetching pagesets, notices will be placed in $notices.
			$notices = $( '#step-content-notices p' ),
			categoryId = self.$theme.closest( '.theme' ).attr( 'data-category-id' ),
			pagesetFail, pagesetSuccess;

		// Reset any previous error messages.
		$notices.html( '' );

		// Error function: If we failed to retrieve pagesets, show a 'Try again' message to the user.
		pagesetFail = function() {
			$( '#step-content-notices p' ).html( failureMessage );
		};

		// Success function: We successfully fetched pagesets.
		pagesetSuccess = function( msg ) {
			var template = wp.template( 'pagesets' );

			// If we have 0 pagesets, show a try again notice and abort.
			if( 0 === $( msg.result.data.pageSets ).length ) {
				$notices.html( failureMessage );
				return;
			}

			$( '#pageset-options' ).html( ( template( msg.result.data.pageSets ) ) );

			self.$pageset = $( 'input[name="pageset"]:checked' );
			self.$budget = $( 'input[name="coin-budget"]:checked' );

			self.loadBuild();
		};

		self.ajax.ajaxCall( { 'category_id' : categoryId }, 'get_category_page_sets', pagesetSuccess, pagesetFail );
	};

	/**
	 * @summary Init Themes.
	 *
	 * @since 1.2.3
	 */
	this.initThemes = function() {
		var template = wp.template( 'theme' ),
			data = { 'site_hash' : self.configs.site_hash },
			genericBuilds, getGenericSuccess, getGenericFail, failureMessage;

		// Define a message for users when fetching themes has failed.
		failureMessage = Inspiration.errorFetchingThemes + ' ' + Inspiration.tryFewMinutes + '<br />' +
		'<button class="button" id="try-themes-again">' + Inspiration.tryAgain + '</button>';

		// Show a loading message to the user that we're fetching themes.
		self.$themes.html( Inspiration.fetchingThemes + ' <span class="spinner inline"></span>' );

		/*
		 * This is the error function passed to our api call to get generic themes. If there is a
		 * failure, we'll display a 'Try again' notice to the user.
		 */
		getGenericFail = function() {
			self.$themes.html( failureMessage );
		};

		getGenericSuccess = function( msg ) {

			// If there were 0 themes returned, show a 'Try again' message and abort.
			if( 0 === msg.result.data.length ) {
				self.$themes.html( failureMessage );
				return;
			}

			// Empty the themes container. We'll fill it with themes below.
			self.$themes.empty();

			genericBuilds = self.shuffle( msg.result.data );

			genericBuilds = self.sortAll( genericBuilds );

			_.each( genericBuilds, function( build ){
				self.$themes.append( template( { configs: IMHWPB.configs, build: build } ) );
			});

			$( "img.lazy" ).lazyload({threshold : 400});
		};

		self.ajax.ajaxCall( data, 'get_generic', getGenericSuccess, getGenericFail );
	};

	/**
	 * Load a new build on the Content tab.
	 *
	 * @since 1.2.3
	 */
	this.loadBuild = function() {
		// Disable all actions.
		self.allActions( 'disable' );

		// Load our loading graphic.
		$( '#build-cost' ).animate( { opacity: 0 }, 400 );
		$( '#screen-content iframe#theme-preview' ).fadeOut( function() {
			$( '#screen-content .boldgrid-loading' ).fadeIn();
		} );


		var success_action = function( msg ) {
			var $screenContent = $( '#screen-content' ),
				$iframe = $screenContent.find( 'iframe#theme-preview' ),
				url = msg.result.data.profile.preview_url;

			$iframe
				.attr( 'src', url )
				.attr( 'data-build-cost', msg.result.data.profile.coins );

			self.highlightDeviceButton();
		};

		data = {
			'theme_id' :			self.$theme.closest( '.theme' ).attr( 'data-theme-id' ),
			'cat_id' :				self.$theme.closest( '.theme' ).attr( 'data-category-id' ),
			'sub_cat_id' :			self.$theme.closest( '.theme' ).attr( 'data-sub-category-id' ),
			'page_set_id' :			self.$pageset.attr( 'data-page-set-id' ),
			'pde' :					self.$theme.closest( '.theme' ).attr( 'data-pde' ),
			'wp_language' :			'en-US',
			'coin_budget' :			self.$budget.attr( 'data-coin' ),
			'theme_version_type' :	null,
			'page_version_type' :	null,
			'site_hash' :			self.configs.site_hash,
			'inspirations_mode' :	'standard',
			'is_generic' :			( '1' === self.$pageset.attr( 'data-is-default' ) ? 'true' : 'false' ),
		};

		// Set form.
		$( '[name=boldgrid_cat_id]' ).val( data.cat_id );
		$( '[name=boldgrid_sub_cat_id]' ).val( data.sub_cat_id );
		$( '[name=boldgrid_theme_id]' ).val( data.theme_id );
		$( '[name=boldgrid_page_set_id]' ).val( data.page_set_id );
		$( '[name=boldgrid_api_key_hash]' ).val( data.site_hash );
		$( '[name=boldgrid_pde]' ).val( data.pde );
		$( '[name=coin_budget]' ).val( data.coin_budget );

		self.ajax.ajaxCall( data, 'get_build_profile', success_action );
	};

	/**
	 *
	 */
	this.toggleStep = function( step ) {
		var $content = $( '#screen-content' ),
			$design = $( '#screen-design' ),
			$contentLink = $( '[data-step="content"]' ),
			$designLink = $( '[data-step="design"]' );

		if( 'design' === step ) {
			$contentLink.removeClass( 'active' );
			$designLink.addClass( 'active' );
			$designLink.parent( '.top-menu' ).removeClass( 'content' );
			$designLink.parent( '.top-menu' ).addClass( 'design' );

			$content.addClass( 'hidden' );
			$design.removeClass( 'hidden' );
			// Restore scroll position when coming back to design page.
			$( document ).scrollTop( self.scrollPosition );
		} else {
			// Store the scroll position of the design page.
			self.scrollPosition = $( document ).scrollTop();
			$contentLink.addClass( 'active' );
			$designLink.removeClass( 'active' );
			$contentLink.parent( '.top-menu' ).removeClass( 'design' );
			$contentLink.parent( '.top-menu' ).addClass( 'content' );

			$content.removeClass( 'hidden' );
			$design.addClass( 'hidden' );
		}
	};

	/**
	 * @summary Toggle a sub category.
	 *
	 * Show only themes belonging to a sub category.
	 *
	 * @since 1.2.3
	 */
	this.toggleSubCategory = function( subCategoryId ) {
		if( '0' === subCategoryId ) {
			$( '.theme[data-sub-category-id]').removeClass( 'hidden' );
			// Show subcategory name if browsing all subcategories.
			$( '.theme-name .sub-category-name' ).show();

			self.sortThemes( 'data-all-order' );
		} else {
			// Hide subcategory name if browsing singular subcategory.
			$( '.theme-name .sub-category-name' ).hide();

			$( '.theme[data-sub-category-id!="' + subCategoryId + '"]')
				.addClass( 'hidden' )
				.appendTo( '.themes' );

			$( '.theme[data-sub-category-id="' + subCategoryId + '"]').removeClass( 'hidden' );

			self.sortThemes( 'data-category-order' );
		}

		$( 'img.lazy' ).lazyload({threshold : 400});
	};

	$( function() {
		self.init();
	});
};

IMHWPB.InspirationsDesignFirst( jQuery, IMHWPB.configs );
