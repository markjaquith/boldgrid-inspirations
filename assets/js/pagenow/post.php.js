// JSHint
/* globals jQuery */

var IMHWPB = IMHWPB || {};

/**
 * A class to load when WordPress' pagenow is 'post.php'.
 *
 * @since 1.0.10
 */
IMHWPB.PagenowPost = function( $ ) {
	var self = this;

	self.baseAdmin = new IMHWPB.BaseAdmin();

	$( function() {
		self.init();
	});

	/**
	 * Append "New From GridBlocks" button to the top of the page.
	 *
	 * @since 1.0.10
	 */
	this.gridblock_sets_append_button = function() {
		self.baseAdmin.$wrap.find( 'h1 a' ).after( '<a href="' +
		IMHWPB.gridblock_sets_admin + '" class="page-title-action">' +
		self.baseAdmin.strings.add_gridblock_set + '</a>' );
	};

	/**
	 * Init.
	 *
	 * @since 1.0.10
	 */
	this.init = function() {
		self.init_gridblock_sets();
	};

	/**
	 * Init all actions on 'edit.php' pertaining to GridBlock Sets.
	 *
	 * @since 1.0.10
	 */
	this.init_gridblock_sets = function() {
		// If not &action=edit, abort.
		if ( 'edit' != self.baseAdmin.GetURLParameter( 'action' ) ) {
			return;
		}

		/*
		 * If we're not looking at a 'page', then abort.
		 *
		 * We don't want to offer gridblock options when someone is editing something like a
		 * 'ninja forms' post.
		 */
		if( ! $( 'body' ).hasClass( 'post-type-page' ) ) {
			return;
		}

		// If we're not using the BoldGrid menu:
		if ( 0 === IMHWPB.configs.settings.boldgrid_menu_option ) {
			self.gridblock_sets_append_button();
		} else {
			self.baseAdmin.$wrap_header.find( 'a' )
				.attr( 'href', IMHWPB.gridblock_sets_admin ) ;
		}
	};
};

IMHWPB.PagenowPost( jQuery );
