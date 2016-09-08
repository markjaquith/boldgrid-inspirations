<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Options
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

// Prevent direct calls
if ( ! defined( 'WPINC' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

/**
 * BoldGrid Inspirations Start Over class
 */
class Boldgrid_Inspirations_Start_Over {

	/**
	 * Does the user want to start over with their active site?
	 *
	 * @var bool
	 */
	public $start_over_active = false;

	/**
	 * Does the user want to start over with their staging site?
	 *
	 * @var bool
	 */
	public $start_over_staging = false;

	/**
	 * Does the user want to delete all forms?
	 */
	public $delete_forms = false;

	/**
	 * Does the user want to delete pages, or ust trash them?
	 */
	public $delete_pages = false;

	/**
	 * Does the user want to delete themes?
	 */
	public $delete_themes = false;

	/**
	 * Search option table for option names begining with $search.
	 *
	 * @param string $search
	 * @return array|boolean
	 */
	public function get_option_names_starting_with( $search ) {
		global $wpdb;

		$search = $wpdb->esc_like( $search );

		$options = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT      option_name
				FROM        {$wpdb->prefix}options
				WHERE       (`option_name` LIKE '%s')
				", $search . '%' ) );

		if ( is_array( $options ) and ! empty( $options ) ) {
			return $options;
		} else {
			return false;
		}
	}

	/**
	 * Delete all pages and menus
	 */
	public function cleanup_pages_and_menus() {
		$post_types = array (
			'page',
			'post',
			'revision',
			'attachment'
		);

		$post_statuses = array (
			'publish',
			'staging',
			'draft'
		);

		$active_post_statuses = array (
			'publish',
			'draft'
		);

		/**
		 * ************************************************************
		 * Step 1: Get all page IDs that we'll need to delete.
		 * ************************************************************
		 */
		$page_ids = null;

		foreach ( $post_types as $post_type ) {
			foreach ( $post_statuses as $post_status ) {
				// If we're trying to get staging pages but the user does not want to start over
				// with staging, continue / abort.
				if ( 'staging' == $post_status && false == $this->start_over_staging ) {
					continue;
				}

				// If we're trying to get active pages (public / draft) but the user does not want
				// to start over with active, continue / abort.
				if ( in_array( $post_status, $active_post_statuses ) &&
					 false == $this->start_over_active ) {
					continue;
				}

				$pages = get_posts(
					array (
						'post_type' => $post_type,
						'post_status' => $post_status,
						'numberposts' => - 1
					) );

				if ( count( $pages ) && isset( $pages[0]->ID ) ) {
					foreach ( $pages as $page ) {
						$page_ids[] = $page->ID;
					}
				}
			}
		}

		// Add our attribution page to the pages list as well:

		$attribution = get_option( 'boldgrid_attribution' );

		if ( is_array( $attribution ) && ! empty( $attribution['page']['id'] ) ) {
			$page_ids[] = $attribution['page']['id'];
		}

		// Allow other plugins to modify the page id's that are deleted.
		$page_ids = apply_filters( 'boldgrid_inspirations_cleanup_page_ids', $page_ids );

		/**
		 * ************************************************************
		 * Step 2: Delete those pages.
		 * ************************************************************
		 */
		if ( null != $page_ids ) {
			foreach ( $page_ids as $page_id ) {
				wp_delete_post( $page_id, $this->delete_pages ); // 2nd param: false = trash, true = delete
			}
		}

		/*
		 * If the user is starting over with their staging site, set the BoldGrid Staging's session
		 * setting to point the user to their active site.
		 *
		 * If the user's last setting before starting over pointed them to staging, and they delete
		 * their staging site, then let's not send them to their staging site when they don't
		 * have one.
		 */
		if( session_id() && $this->start_over_staging ) {
			$_SESSION['wp_staging_view_version'] = 'production';
		}
	}

	/**
	 * Remove menus.
	 *
	 * Based on the user's request, delete either / both the active / staging menus.
	 */
	public function cleanup_nav_menus() {
		/**
		 * Active site.
		 *
		 * BoldGrid uses one menu, 'primary'. Let's delete that menu
		 */
		if ( true == $this->start_over_active ) {
			wp_delete_nav_menu( 'primary' );
		}

		/**
		 * Staging site.
		 */
		if ( true == $this->start_over_staging ) {
			do_action( 'boldgrid_options_cleanup_nav_menus' );
		}
	}

	/**
	 * Update / delete various wp_options
	 */
	public function cleanup_wp_options() {
		/**
		 * ********************************************************************
		 * DELETE options (both active / staging)
		 * ********************************************************************
		 */
		// Define a set of options to DELETE, and then delete them.
		$options_to_delete = array (
			'boldgrid_asset',
			'boldgrid_install_options',
			'boldgrid_attribution',
			'boldgrid_installed_page_ids',
			'boldgrid_installed_pages_metadata',
			'boldgrid_show_tip_start_editing',
			// Class: Boldgrid_Inspirations_GridBlock_Sets_Kitchen_Sink.
			'boldgrid_inspirations_fetching_kitchen_sink_status',
			'_transient_boldgrid_inspirations_kitchen_sink',
			'_transient_timeout_boldgrid_inspirations_kitchen_sink'
		);

		// Delete those options.
		foreach ( $options_to_delete as $option ) {
			// Active site
			if ( true == $this->start_over_active ) {
				delete_option( $option );
			}

			// Staging site.
			// Try to delete the staging version of the option as well.
			if ( true == $this->start_over_staging ) {
				delete_option( 'boldgrid_staging_' . $option );
			}
		}

		/**
		 * ********************************************************************
		 * UPDATE options (both active / staging)
		 * ********************************************************************
		 */
		// Update options:
		if ( true == $this->start_over_active ) {
			update_option( 'boldgrid_has_built_site', 'no' );
		}
		if ( true == $this->start_over_staging ) {
			update_option( 'boldgrid_staging_boldgrid_has_built_site', 'no' );
		}

		// Delete ALL "boldgrid_staging_%" options.
		if ( true == $this->start_over_staging ) {
			$staging_options = $this->get_option_names_starting_with( 'boldgrid_staging_' );

			if ( $staging_options ) {
				foreach ( $staging_options as $option_to_delete ) {
					delete_option( $option_to_delete );
				}
			}
		}
	}

	/**
	 * Removed boldgrid_ admin pointers from dismissed_wp_pointers
	 */
	public function reset_pointers() {
		if ( ! isset( $_POST['_wpnonce'] ) ||
			 ! wp_verify_nonce( $_POST['_wpnonce'], 'reset_pointers' ) ) {
			// nonce not verified; print an error message and return false:
			?>
<div class="error">
	<p>Error processing request to reset pointers (help messages);
		WordPress security violation! Please try again.</p>
</div>
<?php
		} else {
			// clear all the pointers
			update_user_meta( get_current_user_id(), 'dismissed_wp_pointers', '' );

			// clear all admin notices
			delete_option( 'boldgrid_dismissed_admin_notices' );
		}
	}

	/**
	 * Execute the cleanup scripts needed to 'start over'
	 */
	public function start_over() {
		// Delete any BoldGrid Forms and Entries Installed
		$this->cleanup_boldgrid_forms();

		// Delete pages
		$this->cleanup_pages_and_menus();

		// Delete nav menus
		$this->cleanup_nav_menus();

		// Reset boldgrid framework
		$this->reset_framework();

		// Update / delete several boldgrid_ options
		$this->cleanup_wp_options();

		// Delete theme_mods_{$theme_name} options:
		$this->cleanup_theme_mods();

		// Delete all BoldGrid themes
		$this->cleanup_boldgrid_themes();
	}

	/**
	 * Reset Framework
	 */
	public function reset_framework() {
		// Reset Boldgrid Theme Framework
		if( $this->start_over_active ) {
			do_action( 'boldgrid_framework_reset', true );
		}
		if( $this->start_over_staging ) {
			do_action( 'boldgrid_framework_reset', false );
		}

		// Make sure option is reset if theme not active
		delete_option( 'boldgrid_framework_init' );
	}

	/**
	 * Cleanup BoldGrid forms and entries that might have been generated from the install.
	 * If BoldGrid Forms is an installed and active plugin, we will find all of the forms
	 * by ID, then for each ID found, it will be deleted. After dropping all the tables
	 * a lot of errors occur, so then we remove most all of the options, minus some of the
	 * unique keys that would be needed for it to run or have the same config for the user
	 * that they had before. Once activated, the three default forms we include are there.
	 *
	 * NO FILTER AVAILABLE FOR ACTIVE / STAGING SITE.
	 *
	 * @since .21
	 */
	protected function cleanup_boldgrid_forms() {
		// If user has selected the box to delete BoldGrid Forms, then delete them.
		if ( $this->delete_forms ) {
			global $boldgrid_forms;
			$boldgrid_forms['force_uninstall'] = true;

			$plugin = 'boldgrid-ninja-forms/ninja-forms.php';
			deactivate_plugins( $plugin );
			uninstall_plugin( $plugin );
			update_option( 'recently_activated',
				array (
					$plugin => time()
				) + ( array ) get_option( 'recently_activated' ) );
		}
	}

	/**
	 * Cleanup BoldGrid themes that might have been generated from the install.
	 *
	 * NO FILTER FOR ACTIVE / STAGING.
	 *
	 * @since .21
	 *
	 * @param int $_POST['boldgrid_delete_themes']
	 */
	protected function cleanup_boldgrid_themes() {
		// This will provide an array with all of the themes installed
		$themes = wp_get_themes( array (
			'errors' => null
		) );

		if ( $this->delete_themes ) {
			// If the user's current theme is a BoldGrid theme, let's switch the user to
			// twentyfifteen.
			if ( Boldgrid_Inspirations_Utility::startsWith( get_stylesheet(), 'boldgrid' ) ) {
				switch_theme( 'twentyfifteen' );
			}

			// Grab each theme, and see if it has $stylesheet (folder name theme is contained in)
			// with "boldgrid" in the name.
			if ( count( $themes ) ) {
				foreach ( $themes as $theme_key => $theme ) {
					if ( $theme->exists() && false !== stristr( $theme_key, 'boldgrid' ) ) {
						// If it does, then delete the theme.
						delete_theme( $theme_key );
					}
				}
			}
		}
	}

	/**
	 * Cleanup theme_mods_boldgrid in WP Options.
	 *
	 * Originally, we got a list of all the themes using wp_get_themes(). If the theme name began
	 * with boldgrid, then we would delete the theme mods. One problem here is that when you delete
	 * a theme, WordPress does not delete the theme mods. So, our call to wp_get_themes() would not
	 * get all of the theme_mods we actually want to delete.
	 *
	 * Instead, we'll use SQL to query for all theme_mods_boldgrid% options, and then delete those.
	 */
	protected function cleanup_theme_mods() {
		// Active site:
		if ( true == $this->start_over_active ) {
			$boldgrid_theme_mods = $this->get_option_names_starting_with( 'theme_mods_boldgrid' );

			if ( $boldgrid_theme_mods ) {
				foreach ( $boldgrid_theme_mods as $option_to_delete ) {
					// Delete all options and set an option which will reset the themes color
					// palette
					update_option( $option_to_delete,
						array (
							'force_scss_recompile' => array (
								'active' => true,
								'staging' => true
							)
						) );
				}
			}
		}

		// Staging site:
		if ( true == $this->start_over_staging ) {
			$boldgrid_theme_mods = $this->get_option_names_starting_with(
				'boldgrid_staging_theme_mods_boldgrid' );

			if ( $boldgrid_theme_mods ) {
				foreach ( $boldgrid_theme_mods as $option_to_delete ) {
					delete_option( $option_to_delete );
				}
			}
		}
	}
}
