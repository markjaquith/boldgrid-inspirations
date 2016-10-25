/**
 * ****************************************************************************
 * BoldGrid Dashboard Widget
 * ****************************************************************************
 */
var BoldGridDashboard = BoldGridDashboard || {};

( function( $, _, Backbone ) {
	"use strict";
	// This is the primary view for the BoldGrid Dashboard.
	var BoldGridDashView = Backbone.View.extend({
		/**
		 * Main view for BoldGrid Dashboard Widget This is the set of
		 * tabs and actual content displayed in the dashboard widget.
		 */
		// Edit Screen Options text in WordPress Dashboard.
		boldgridScreenOptions : function() {
			/**
			 * Change the text of the "Welcome Panel" in WordPress and put the option
			 * for BoldGrid, "Welcome to BoldGrid!"
			 *
			 * @since 1.13
			 *
			 * Updated variable names to be more logical in reading.
			 * @since 1.14
			 */
			var wpScreenOptions = jQuery( 'label[for=wp_welcome_panel-hide] input' );

			if ( 'undefined' != typeof wpScreenOptions[0] ) {
				var welcome = wpScreenOptions[0].nextSibling.nodeValue,
				boldgridScreenOptions = welcome.replace( /Welcome/i, ' Welcome to BoldGrid!' );
				wpScreenOptions[0].nextSibling.nodeValue = boldgridScreenOptions;
			}
		},

		// Custom YouTube Player.
		boldgridYouTubePlayer : function() {
			/**
			 * Prevent YouTube embeds rendered in view from draining resources. Also
			 * adds overlay image in orange for player button, and sets up the video
			 * embed to be done on a click event opposed to straight embedding it by
			 * grabbing thumbnails from youtube until video is selected by user.
			 *
			 * @since 1.13
			 */

			jQuery( document ).ready( function() {
				( function() {
					var v = document.getElementsByClassName( 'youtube-player' );
					for ( var n = 0; n < v.length; n++ ) {
						var p = document.createElement( 'div' );
						p.innerHTML = boldgridThumb( v[n].dataset.id );
						p.onclick = boldgridIframe;
						v[n].appendChild( p );
					}
				})();

				function boldgridThumb( id ) {
					return '<img class="youtube-thumb" src="//i.ytimg.com/vi/' + id +
						'/hqdefault.jpg"><div class="play-button"></div>';
				}

				function boldgridIframe() {
					var iframe = document.createElement( 'iframe' );

					iframe.setAttribute( 'src', '//www.youtube.com/embed/' +
						this.parentNode.dataset.id +
						'?autoplay=1&autohide=2&border=0&wmode=opaque&enablejsapi=1&theme=light&controls=1&rel=0&showinfo=0'
					);

					iframe.setAttribute( 'frameborder', '0' );
					iframe.setAttribute( 'allowfullscreen', '' );
					iframe.setAttribute( 'id', 'youtube-iframe' );
					this.parentNode.replaceChild( iframe, this );
				}
			} );
		},
		// Initialize.
		initialize : function() {
			this.render();
		},
		// Render.
		render : function() {
			this.boldgridScreenOptions();
			this.boldgridYouTubePlayer();
		}
	});

	// Router for BoldGrid Dashboard Widget.
	var BoldgridRouter = Backbone.Router.extend({
		routes : {
			'' : 'boldgriddashRoute',
		},
		// Main route for BoldGridDashView.
		boldgriddashRoute : function() {
			// Create new BoldGridDashView to display widget on WP Dashboard.
			var boldgriddashView = new BoldGridDashView();
		}
	});
	// Setup.
	var boldgridRouter = new BoldgridRouter();
	// Start.
	Backbone.history.start();
}( jQuery, _, Backbone ) );
