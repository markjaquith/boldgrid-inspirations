<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Update
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations (core) update class.
 */
class Boldgrid_Inspirations_Update {

	/**
	 * BoldGrid Inspirations Configuration.
	 *
	 * @since 1.1.7
	 * @access private
	 * @static
	 *
	 * @var array
	 */
	private static $configs = array();

	/**
	 * Setter for the BoldGrid Inspirations class object.
	 *
	 * @since 1.1.7
	 * @access private
	 * @static
	 *
	 * @param array $configs The BoldGrid configuration array.
	 * @return bool
	 */
	private static function set_configs( $configs = array() ) {
		// If configs is empty, then get and set the array.
		if ( empty( $configs ) ) {
			// Load the Boldgrid_Inspirations_Config class if needed.
			if ( ! class_exists( 'Boldgrid_Inspirations_Config' ) ) {
				require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-config.php';
			}

			// Get the configs.
			$configs = Boldgrid_Inspirations_Config::get_format_configs();
		}

		self::$configs = $configs;

		return true;
	}

	/**
	 * Getter for the BoldGrid Inspirations class object.
	 *
	 * @since 1.1.7
	 * @static
	 *
	 * @return array
	 */
	public static function get_configs() {
		// Set the configs, if not set.
		if ( empty( self::$configs ) ) {
			self::set_configs();
		}

		return self::$configs;
	}

	/**
	 * Constructor.
	 *
	 * @global $pagenow The current WordPress page filename.
	 *
	 * @param object $boldgrid_inspirations The BoldGrid Inspirations object (optional).
	 */
	public function __construct( $boldgrid_inspirations ) {
		// Set the BoldGrid configuration array.
		if ( is_a( $boldgrid_inspirations, 'BoldGrid_Inspirations' ) ) {
			// Object.
			self::set_configs( $boldgrid_inspirations->get_configs() );
		} else {
			// Static.
			self::set_configs();
		}

		// Only for wp-admin.
		if ( is_admin() ) {
			// Get the current WordPress page filename.
			global $pagenow;

			// Make an array of plugin update pages.
			$plugin_update_pages = array (
				'plugins.php',
				'update-core.php',
			);

			// Is page for plugin information?
			$is_plugin_information = ( 'plugin-install.php' === $pagenow && isset( $_GET['plugin'] ) &&
				 'boldgrid-inspirations' === $_GET['plugin'] && isset( $_GET['tab'] ) &&
				 'plugin-information' === $_GET['tab'] );

			// Is this a plugin update action?
			$is_plugin_update = ( isset( $_REQUEST['action'] ) &&
				 'update-plugin' === $_REQUEST['action'] && 'admin-ajax.php' === $pagenow );

			// Add filters to modify plugin update transient information.
			if ( in_array( $pagenow, $plugin_update_pages, true ) || $is_plugin_information ||
				 $is_plugin_update ) {
				// Add filters.
				add_filter( 'pre_set_site_transient_update_plugins',
					array (
						$this,
						'custom_plugins_transient_update'
					)
				);

				add_filter( 'plugins_api',
					array (
						$this,
						'custom_plugins_transient_update'
					)
				);

				// Force WP to check for updates, don't rely on cache / transients.
				add_filter( 'site_transient_update_plugins',
					array (
						$this,
						'site_transient_update_plugins'
					)
				);
			}

			// Make an array of theme update pages.
			$theme_update_pages = array (
				'themes.php',
				'update-core.php',
				'update.php',
			);

			// Is this a theme upgrade action?
			$is_theme_upgrade = ( isset( $_REQUEST['action'] ) &&
				 'upgrade-theme' === $_REQUEST['action'] && 'update.php' === $pagenow );

			// Add filters to modify theme update transient information.
			if ( in_array( $pagenow, $theme_update_pages, true ) || $is_theme_upgrade ) {
				add_filter( 'pre_set_site_transient_update_themes',
					array (
						$this,
						'custom_themes_transient_update'
					)
				);

				add_filter( 'site_transient_update_themes',
					array (
						$this,
						'custom_themes_transient_update'
					)
				);
			}
		}

		// If on the dashboard, then check if there is an admin notice to display.
		add_action( 'admin_head-index.php', array (
			$this,
			'display_notices'
		) );

		// Check and update the current and previous version options.
		add_action( 'admin_init', array (
			$this,
			'update_version_options'
		) );
	}

	/**
	 * Update api data transient from data on our asset server.
	 *
	 * @see Boldgrid_Inspirations_Update::get_configs().
	 * @see Boldgrid_Inspirations_Api::boldgrid_api_call().
	 * @see Boldgrid_Inspirations_Api::set_is_asset_server_available().
	 *
	 * @return object|false A standard data object or FALSE on error.
	 */
	public static function update_api_data() {
		// Get api data transient.
		$boldgrid_api_data = get_site_transient( 'boldgrid_api_data' );

		// If the API data was just retrieved (last 5 seconds) and is ok, then just return it.
		if ( ! empty( $boldgrid_api_data ) &&
		isset( $boldgrid_api_data->updated ) &&
		$boldgrid_api_data->updated >= time() - 5 ) {
			return $boldgrid_api_data;
		}

		// Initialize $boldgrid_api_data.
		$boldgrid_api_data = null;

		// Get BoldGrid configs, or just set the required info.
		$boldgrid_configs = self::get_configs();

		// If the ajax call path is not available.
		if ( ! isset( $boldgrid_configs['ajax_calls']['get_version'] ) ) {
			$boldgrid_configs['ajax_calls']['get_version'] = '/api/plugin/check-version';
		}

		// If we have no transient but do have configs, then get data and set transient.
		// Load the Boldgrid_Inspirations class if needed.
		if ( class_exists( 'Boldgrid_Inspirations' ) ) {
			require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations.php';
		}

		// Get the latest version information (API call).
		$boldgrid_api_data = Boldgrid_Inspirations_Api::boldgrid_api_call(
			$boldgrid_configs['ajax_calls']['get_version']
		);

		// Check asset server availability.
		if ( isset( $boldgrid_api_data->status ) ) {
			Boldgrid_Inspirations_Api::set_is_asset_server_available( true );
		} else {
			Boldgrid_Inspirations_Api::set_is_asset_server_available( false );

			return false;
		}

		// Fail if we do not have success.
		if ( 200 !== $boldgrid_api_data->status || 'OK' !== $boldgrid_api_data->message ) {
			error_log(
				__METHOD__ . ': Failed to get valid updated boldgrid_api_data.  ' .
				print_r(
					array(
						'uri' => $boldgrid_configs['ajax_calls']['get_version'],
						'$boldgrid_api_data' => $boldgrid_api_data,
					),
					true
				)
			);

			return false;
		}

		// Add the current timestamp (in seconds).
		$boldgrid_api_data->updated = time();

		// Set api data transient, expired in 8 hours.
		delete_site_transient( 'boldgrid_api_data' );
		set_site_transient( 'boldgrid_api_data', $boldgrid_api_data, 8 * HOUR_IN_SECONDS );

		// Update boldgrid_reseller option.
		$boldgrid_reseller_array = array();

		foreach ( $boldgrid_api_data->result->data as $key => $value ) {
			if ( preg_match( '/^reseller_/', $key ) ) {
				$boldgrid_reseller_array[ $key ] = $boldgrid_api_data->result->data->$key;
			}
		}

		// Set the reseller option from api data, or mark as no brand if no reseller data.
		if ( count( $boldgrid_reseller_array ) ) {
			update_option( 'boldgrid_reseller', $boldgrid_reseller_array );
		} else {
			update_option( 'boldgrid_reseller',
				array(
					'reseller_nobrand' => true,
				)
			);
		}

		return $boldgrid_api_data;
	}

	/**
	 * Update the plugin update transient.
	 *
	 * @global $pagenow The current WordPress page filename.
	 *
	 * @param object $transient WordPress plugin update transient object.
	 * @return object $transient
	 */
	public function custom_plugins_transient_update( $transient ) {
		// Get api data transient.
		$boldgrid_api_data = get_site_transient( 'boldgrid_api_data' );

		// If the api data transient does not exist or is a force check, then get the data and set
		// it.
		if ( empty( $boldgrid_api_data ) || isset( $_GET['force-check'] ) ) {
			$boldgrid_api_data = self::update_api_data();
		}

		// If we have no data, then return unchanged plugin update transient.
		if ( empty( $boldgrid_api_data ) ) {
			return $transient;
		}

		// Get configs.
		$boldgrid_configs = self::get_configs();

		// If the API key is not set, then abort; return unchanged plugin update transient.
		if( empty( $boldgrid_configs['api_key'] ) ){
			return $transient;
		}

		// Get the current WordPress page filename.
		global $pagenow;

		// Create a new object to be injected into transient.
		if ( 'plugin-install.php' === $pagenow && isset( $_GET['plugin'] ) &&
			 'boldgrid-inspirations' === $_GET['plugin'] ) {
			// For version information iframe (/plugin-install.php).
			$transient = new stdClass();

			// If we have section data, then prepare it for use.
			if ( ! empty( $boldgrid_api_data->result->data->sections ) ) {
				// Remove new lines and double-spaces, to help prevent a broken JSON set.
				$boldgrid_api_data->result->data->sections = preg_replace( '/\s+/', ' ',
					trim( $boldgrid_api_data->result->data->sections ) );

				// Convert the JSON set into an array.
				$transient->sections = json_decode( $boldgrid_api_data->result->data->sections,
					true );

				// If we have data, format it for use, else set a default message.
				if ( ! empty( $transient->sections ) && count( $transient->sections ) ) {
					foreach ( $transient->sections as $section => $section_data ) {
						$transient->sections[$section] = html_entity_decode( $section_data,
							ENT_QUOTES );
					}
				} else {
					$transient->sections['description'] = 'Data not available';
				}
			} else {
				$transient->sections['description'] = 'Data not available';
			}

			// Set the other elements.
			$transient->name = $boldgrid_api_data->result->data->title;
			$transient->requires = $boldgrid_api_data->result->data->requires_wp_version;
			$transient->tested = $boldgrid_api_data->result->data->tested_wp_version;
			// $transient->downloaded = $boldgrid_api_data->result->data->downloads;
			$transient->last_updated = $boldgrid_api_data->result->data->release_date;
			$transient->download_link = $boldgrid_configs['asset_server'] .
				 $boldgrid_configs['ajax_calls']['get_asset'] . '?key=' .
				 $boldgrid_configs['api_key'] . '&id=' . $boldgrid_api_data->result->data->asset_id;

			if ( ! empty( $boldgrid_api_data->result->data->compatibility ) && null !== ( $compatibility = json_decode(
				$boldgrid_api_data->result->data->compatibility, true ) ) ) {
				$transient->compatibility = $boldgrid_api_data->result->data->compatibility;
			}

			/*
			 * Not currently showing ratings.
			 * if ( ! ( empty( $boldgrid_api_data->result->data->rating ) ||
			 * empty( $boldgrid_api_data->result->data->num_ratings ) ) ) {
			 * $transient->rating = ( float ) $boldgrid_api_data->result->data->rating;
			 * $transient->num_ratings = ( int ) $boldgrid_api_data->result->data->num_ratings;
			 * }
			 */

			$transient->added = '2015-03-19';

			if ( ! empty( $boldgrid_api_data->result->data->siteurl ) ) {
				$transient->homepage = $boldgrid_api_data->result->data->siteurl;
			}

			if ( ! empty( $boldgrid_api_data->result->data->tags ) &&
				 null !== ( $tags = json_decode( $boldgrid_api_data->result->data->tags, true ) ) ) {
				$transient->tags = $boldgrid_api_data->result->data->tags;
			}

			if ( ! empty( $boldgrid_api_data->result->data->banners ) && null !== ( $banners = json_decode(
				$boldgrid_api_data->result->data->banners, true ) ) ) {
				$transient->banners = $banners;
			}

			$transient->plugin_name = 'boldgrid-inspirations.php';
			$transient->slug = 'boldgrid-inspirations';
			$transient->version = $boldgrid_api_data->result->data->version;
			$transient->new_version = $boldgrid_api_data->result->data->version;
			// $transient->active_installs = false;
		} else {
			// For plugins.php and update-core.php pages, and DOING_CRON.
			$obj = new stdClass();
			$obj->slug = 'boldgrid-inspirations';
			$obj->plugin = 'boldgrid-inspirations/boldgrid-inspirations.php';
			$obj->new_version = $boldgrid_api_data->result->data->version;

			if ( ! empty( $boldgrid_api_data->result->data->siteurl ) ) {
				$obj->url = $boldgrid_api_data->result->data->siteurl;
			}

			$obj->package = $boldgrid_configs['asset_server'] .
				 $boldgrid_configs['ajax_calls']['get_asset'] . '?key=' .
				 $boldgrid_configs['api_key'] . '&id=' . $boldgrid_api_data->result->data->asset_id;

			$plugin_data = get_plugin_data( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php',
				false );

			if ( $plugin_data['Version'] !== $boldgrid_api_data->result->data->version ) {
				$transient->response[$obj->plugin] = $obj;
				$transient->tested = $boldgrid_api_data->result->data->tested_wp_version;
			} else {
				$transient->no_update[$obj->plugin] = $obj;
			}
		}

		return $transient;
	}

	/**
	 * Update the theme update transient.
	 *
	 * @param object $transient WordPress plugin update transient object.
	 * @return object $transient
	 */
	public function custom_themes_transient_update( $transient ) {
		// If we do not need to check for an update, then just return unchanged transient.
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get api data transient.
		$boldgrid_api_data = get_site_transient( 'boldgrid_api_data' );

		// If the api data transient does not exist or is a force check, then get the data and set
		// it.
		if ( empty( $boldgrid_api_data ) || isset( $_GET['force-check'] ) ) {
			$boldgrid_api_data = self::update_api_data();
		}

		// If we have no data, then return.
		if ( empty( $boldgrid_api_data ) ) {
			return $transient;
		}

		// Get theme versions from $boldgrid_api_data, as an array.
		// Using json_encode rather than wp_json_encode, due to an empty array in WP 4.6.
		$theme_versions = json_decode(
			json_encode( $boldgrid_api_data->result->data->theme_versions ), true
		);

		// Get installed themes (array of WP_Theme objects).
		$installed_themes = wp_get_themes();

		// If themes are found, then iterate through them, adding update info for our themes.
		if ( count( $installed_themes ) ) {
			foreach ( $installed_themes as $installed_theme ) {
				// If the current theme is a BoldGrid theme, then check for an upgrade.
				if ( false !== strpos( $installed_theme->get( 'TextDomain' ), 'boldgrid' ) ) {
					// Get the boldgrid-theme-id from the Tags line in the stylesheet.
					$tags = $installed_theme->get( 'Tags' );

					// Iterate through the tags to find theme id (boldgrid-theme-id-##).
					$theme_id = null;
					foreach ( $tags as $tag ) {
						if ( preg_match( '/^boldgrid-theme-([0-9]+)$/', $tag, $matches ) ) {
							$boldgrid_tag = $matches[0];
							$theme_id = $matches[1];
							unset( $matches );

							break;
						}
					}

					// Check if update available for a theme by comparing versions.
					$current_version = $installed_theme->Version;
					$incoming_version = ! empty( $theme_versions[ $theme_id ]['version'] ) ?
						$theme_versions[ $theme_id ]['version'] : null;
					$update_available = $incoming_version && $current_version != $incoming_version;

					// Update is available set transient.
					if ( $update_available ) {

						// Get the theme slug, name, and theme URI.
						$slug = $installed_theme->get_template();
						$theme_name = $installed_theme->get( 'Name' );
						$theme_uri = $installed_theme->get( 'ThemeURI' );

						// Add array elements to the transient.
						$transient->response[$slug]['theme'] = $slug;
						$transient->response[$slug]['new_version'] = $theme_versions[$theme_id]['version'];

						// URL for the new theme version information iframe.
						$transient->response[$slug]['url'] = empty( $theme_uri ) ? '//www.boldgrid.com/themes/' .
							 strtolower( $theme_name ) : $theme_uri;

						// Theme package download link.
						$transient->response[$slug]['package'] = isset(
							$theme_versions[$theme_id]['package'] ) ? $theme_versions[$theme_id]['package'] : null;

						// $transient->response[$slug]['browse'] = 'updated';
						$transient->response[$slug]['author'] = $installed_theme->Author;
						$transient->response[$slug]['Tag'] = $installed_theme->Tags;
						$transient->response[$slug]['search'] = $boldgrid_tag;
						$transient->response[$slug]['fields'] = array (
							'version' => $theme_versions[$theme_id]['version'],
							'author' => $installed_theme->Author,
							// 'preview_url' => '',
							// 'screenshot_url' = '',
							// 'screenshot_count' => 0,
							// 'screenshots' => array (),
							// 'sections' => array (),
							'description' => $installed_theme->Description,
							'download_link' => $transient->response[$slug]['package'],
							'name' => $installed_theme->Name,
							'slug' => $slug,
							'tags' => $installed_theme->Tags,
							// 'contributors' => '',
							'last_updated' => $theme_versions[$theme_id]['updated'],
							'homepage' => ( isset( $boldgrid_api_data->result->data->siteurl ) ? $boldgrid_api_data->result->data->siteurl : 'http://www.boldgrid.com/' )
						);
						unset( $theme_id );
					}
				}
			}
		}

		// Return the transient.
		return $transient;
	}

	/**
	 * Force WP to check for updates, don't rely on cache / transients.
	 *
	 * @param object $value WordPress plugin update transient object.
	 * @return object
	 */
	public function site_transient_update_plugins( $value ) {
		global $pagenow;

		// Only require fresh data IF user is clicking "Check Again".
		if ( 'update-core.php' !== $pagenow || ! isset( $_GET['force-check'] ) ) {
			return $value;
		}

		// Set the last_checked to 1, so it will trigger the timeout and check again.
		if ( isset( $value->last_checked ) ) {
			$value->last_checked = 1;
		}

		return $value;
	}

	/**
	 * Action to add a filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.1.7
	 */
	public function wp_update_this_plugin () {
		// Add filters to modify plugin update transient information.
		add_filter( 'pre_set_site_transient_update_plugins',
			array (
				$this,
				'custom_plugins_transient_update'
			)
		);

		add_filter( 'plugins_api',
			array (
				$this,
				'custom_plugins_transient_update'
			)
		);

		add_filter( 'site_transient_update_plugins',
			array (
				$this,
				'site_transient_update_plugins'
			)
		);

		add_filter( 'auto_update_plugin',
			array (
				$this,
				'auto_update_this_plugin'
			), 10, 2
		);

		add_filter( 'auto_update_plugins',
			array (
				$this,
				'auto_update_this_plugin'
			), 10, 2
		);

		// Have WordPress check for plugin updates.
		wp_maybe_auto_update();
	}

	/**
	 * Filter to check if this plugin should be auto-updated.
	 *
	 * @since 1.1.7
	 *
	 * @param bool $update Whether or not this plugin is set to update.
	 * @param object $item The plugin transient object.
	 * @return bool Whether or not to update this plugin.
	 */
	public function auto_update_this_plugin ( $update, $item ) {
		if ( isset( $item->slug['boldgrid-inspirations'] ) && isset( $item->autoupdate ) ) {
			return true;
		} else {
			return $update;
		}
	}

	/**
	 * Update version options.
	 *
	 * Checks and updates the versions stored in WP options.
	 *
	 * @since 1.0.12
	 *
	 * @return null
	 */
	public function update_version_options() {
		// Get the current plugin version.
		$plugin_data = get_plugin_data( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php', false );

		// Get the live plugin version.
		$live_version = $plugin_data['Version'];

		// Get the current plugin version from WP options.
		$current_version = get_site_option( 'boldgrid_inspirations_current_version' );

		// If the current version matches the live version, then abort.
		if ( $current_version === $live_version ) {
			return;
		}

		// Update the recorded previous and current versions in WP options.
		update_site_option( 'boldgrid_inspirations_previous_version', $current_version );
		update_site_option( 'boldgrid_inspirations_current_version', $live_version );
	}

	/**
	 * Display admin notices.
	 *
	 * @since 1.0.12
	 */
	public function display_notices() {
		// Show any pending notices.
		add_action( 'admin_notices', array (
			$this,
			'show_notices'
		) );
	}

	/**
	 * Show any pending notices.
	 *
	 * @since 1.0.12
	 */
	public function show_notices() {
		$admin_notices = new Boldgrid_Inspirations_Admin_Notices();

		// Get boldgrid settings.
		$boldgrid_settings = get_option( 'boldgrid_settings' );

		// Get the boldgrid menu option from settings.
		$boldgrid_menu_option = $boldgrid_settings['boldgrid_menu_option'];

		// Get the current plugin version.
		$plugin_data = get_plugin_data( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php', false );

		// Get the boldgrid_inspirations_activated option.
		$activated_version = get_site_option( 'boldgrid_inspirations_activated_version' );

		/*
		 * If current version is 1.0.12 or higher, the version originally activated was earlier than
		 * 1.0.12, and the update notice was not previously dismissed, then show it.
		 */

		// Is the live version greater than or equal to 1.0.12?
		$is_live_ge_1012 = version_compare( $plugin_data['Version'], '1.0.12', '>=' );

		// Is the original activated version less than 1.0.12.
		$is_activated_lt_1012 = ( empty( $activated_version ) ||
			 version_compare( $activated_version, '1.0.12', '<' ) );

		// Is the notice already marked as dismissed.
		$has_been_dismissed = $admin_notices->has_been_dismissed( 'update-notice-1-0-12' );

		/*
		 * Check if the notice should be displayed.
		 *
		 * As of 1.2.5, the current_user_can check was added. If the user cannot edit pages, there's
		 * no need to tell them "Inspirations - Pages" has moved.
		 */
		if ( $is_live_ge_1012 && $is_activated_lt_1012 && ! $has_been_dismissed & current_user_can( 'edit_pages' ) ) {
			// Display the notice.
			?>
<div id='update-notice-1-0-12'
	class='updated notice is-dismissible fade boldgrid-admin-notice'
	data-admin-notice-id='update-notice-1-0-12'>
	<h2><?php echo __( 'Update notice' ); ?></h2>
	<p>BoldGrid Inspirations <?php echo __( 'has been updated to version' ) . ' ' . $plugin_data['Version']; ?>.</p>
	<p>
		<?php echo __( 'Please note that as of version 1.0.12, the' ); ?> <strong><i><?php echo $boldgrid_menu_option?'Inspirations':'BoldGrid'; ?>
		- Add Pages</i></strong> <?php echo __( 'feature has been removed and replaced with' ); ?> <strong><i>Pages
				- <a href='edit.php?post_type=page&page=boldgrid-add-gridblock-sets'><?php echo $boldgrid_menu_option?'Add New':'New from GridBlocks'?></a>
		</i></strong>.
	</p>
</div>
<?php
		}
	}
}
