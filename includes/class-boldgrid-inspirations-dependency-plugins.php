<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Dependency_Plugins
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * The BoldGrid Dependency Plugin class.
 */
class Boldgrid_Inspirations_Dependency_Plugins {
	/**
	 * Class property $dependent_plugins
	 *
	 * @var bool
	 */
	private $dependent_plugins = array ();

	/**
	 * Class property $dependent_plugins_not_installed
	 *
	 * @var array
	 */
	private $dependent_plugins_not_installed = array ();

	/**
	 * Class property $plugin_install_url
	 * Storage array of dependent plugin download URL addresses
	 *
	 * @var array
	 */
	private $plugin_install_url = array ();

	/**
	 * Class property $plugin_install_version
	 * Storage array of dependent plugin version numbers
	 *
	 * @var array
	 */
	private $plugin_install_version = array ();

	/**
	 * Class property $plugin_install_title
	 * Storage array of dependent plugin titles
	 *
	 * @var array
	 */
	private $plugin_install_title = array ();

	/**
	 * Class property $release_channel
	 * The current release channel
	 *
	 * @var string
	 */
	private $release_channel = 'UNKNOWN';

	/**
	 * Class property $user_is_requesting_dependency_plugin_installation
	 *
	 * @var bool
	 */
	private $user_is_requesting_dependency_plugin_installation = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Get dependent plugin URL addresses:
		$plugin_install_url = $this->get_plugin_install_info();

		// Set class properties:
		$this->plugin_install_url = $plugin_install_url;

		$this->dependent_plugins = array (
			'boldgrid-editor' => 'boldgrid-editor/boldgrid-editor.php',
			'boldgrid-staging' => 'boldgrid-staging/boldgrid-staging.php'
		);

		$this->user_is_requesting_dependency_plugin_installation = ( isset(
			$_POST['boldgrid-plugin-install'] ) );
	}

	/**
	 * Activate Staging plugin.
	 *
	 * This is usually called via an ajax call during Inspiriations, before the user deploys. They
	 * may have the Staging plugin, but it's not acivated. The user would make an ajax call to
	 * activate the plugin, and then install as staging.
	 *
	 * @since 1.2.5
	 */
	public function activate_staging() {
		// Is the user able to install plugins?
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_die( '0' );
		}

		if( false === check_ajax_referer( 'nonce-install-staging', 'nonce-install-staging', false ) ) {
			wp_die( '0' );
		}

		$activate = activate_plugin( 'boldgrid-staging/boldgrid-staging.php' );

		if( null === $activate ) {
			wp_die( '1' );
		} else {
			wp_die( '0' );
		}
	}

	/**
	 * Return the dependent plugin information
	 * URL addresses, version numbers, and titles
	 *
	 * @return array
	 */
	private function get_plugin_install_info() {
		// Get boldgrid_api_data transient.
		$boldgrid_api_data = get_site_transient( 'boldgrid_api_data' );

		// Get BoldGrid settings.
		$options = get_option( 'boldgrid_settings' );

		// Set the release channel.
		$release_channel = (
			isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable'
		);

		// Set the class property $release_channel.
		$this->release_channel = $release_channel;

		if ( ! empty( $boldgrid_api_data ) ) {
			// Get BoldGrid Inspirations configs.
			$boldgrid_configs = Boldgrid_Inspirations_Update::get_configs();

			// Create the URL address for asset downloads.
			if ( ! empty( $boldgrid_configs['api_key'] ) ) {
				$get_asset_url = $boldgrid_configs['asset_server'] .
					 $boldgrid_configs['ajax_calls']['get_asset'] . '?key=' .
					 $boldgrid_configs['api_key'] . '&id=';
			}

			// Use asset URL addresses with API key.
			// Set the BoldGrid Editor download URL address:
			if ( isset( $boldgrid_api_data->result->data->editor->asset_id ) &&
			isset( $get_asset_url ) ) {
				$boldgrid_editor_url = $get_asset_url .
				$boldgrid_api_data->result->data->editor->asset_id;

				// Set the BoldGrid Editor version number.
				if ( isset( $boldgrid_api_data->result->data->editor->version ) ) {
					$this->plugin_install_version['editor'] = $boldgrid_api_data->result->data->editor->version;
				}

				// Set the BoldGrid Editor title.
				if ( isset( $boldgrid_api_data->result->data->editor->title ) ) {
					$this->plugin_install_title['editor'] = $boldgrid_api_data->result->data->editor->title;
				}
			}

			// Set the BoldGrid Staging download URL address.
			if ( isset( $boldgrid_api_data->result->data->staging->asset_id ) &&
			isset( $get_asset_url ) ) {
				$boldgrid_staging_url = $get_asset_url .
				$boldgrid_api_data->result->data->staging->asset_id;

				// Set the BoldGrid Staging version number.
				if ( isset( $boldgrid_api_data->result->data->staging->version ) ) {
					$this->plugin_install_version['staging'] = $boldgrid_api_data->result->data->staging->version;
				}

				// Set the BoldGrid Staging title.
				if ( isset( $boldgrid_api_data->result->data->staging->title ) ) {
					$this->plugin_install_title['staging'] = $boldgrid_api_data->result->data->staging->title;
				}
			}
		}

		// If asset links are not available, then use open access links.
		if ( empty( $boldgrid_editor_url ) ) {
			if ( 'stable' !== $release_channel ) {
				// Other channels.
				$boldgrid_editor_url = 'https://repo.boldgrid.com/boldgrid-editor-' . $release_channel .
					 '.zip';
			} else {
				// Stable channel.
				$boldgrid_editor_url = 'https://repo.boldgrid.com/boldgrid-editor.zip';
			}
		}

		if ( empty( $boldgrid_staging_url ) ) {
			if ( 'stable' !== $release_channel ) {
				// Other channels.
				$boldgrid_staging_url = 'https://repo.boldgrid.com/boldgrid-staging-' .
				$release_channel . '.zip';
			} else {
				// Stable channel.
				$boldgrid_staging_url = 'https://repo.boldgrid.com/boldgrid-editor.zip';
			}
		}

		// Create the return array.
		$return_array = array (
			'boldgrid-editor' => $boldgrid_editor_url,
			'boldgrid-staging' => $boldgrid_staging_url
		);

		return $return_array;
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		if ( is_admin() ) {
			add_action( 'admin_init',
				array(
					$this,
					'get_dependent_plugins_not_installed',
				)
			);

			add_action( 'admin_notices',
				array(
					$this,
					'admin_notice',
				)
			);

			add_action( 'admin_notices',
				array(
					$this,
					'install_plugins',
				)
			);

			add_action( 'admin_enqueue_scripts',
				array(
					$this,
					'admin_enqueue_scripts',
				)
			);

			add_action( 'wp_ajax_boldgrid_dismiss_notice',
				array(
					$this,
					'boldgrid_dismiss_notice_callback',
				)
			);

			add_action( 'admin_footer',
				array(
					$this,
					'hide_plugin_list_during_installation',
				)
			);

			add_action( 'wp_ajax_install_staging', array( $this, 'install_staging' ) );

			add_action( 'wp_ajax_activate_staging', array( $this, 'activate_staging' ) );
		}
	}

	/**
	 * Add plugin to active plugins list in wp_options.
	 *
	 * @param string $plugin_name Plugin name/slug.
	 */
	public function add_plugin_to_active_plugins( $plugin_name ) {
		$plugin_name_and_path = $this->dependent_plugins[ $plugin_name ];

		// If we don't have a $plugin_name_and_path, abort.
		if ( empty( $plugin_name_and_path ) ) {
			return;
		}

		$active_plugins = get_option( 'active_plugins' );

		// If the plugin is not currently active.
		if ( ! in_array( $plugin_name_and_path, $active_plugins, true ) ) {
			$active_plugins[] = $plugin_name_and_path;

			update_option( 'active_plugins', $active_plugins );
		}
	}

	/**
	 */
	public function admin_enqueue_scripts( $hook ) {
		if ( $this->show_notice() ) {
			wp_enqueue_script(
				'class-dependency-plugins',
				plugins_url(
					'assets/js/class-dependency-plugins.js',
					BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
				),
				array(),
				BOLDGRID_INSPIRATIONS_VERSION,
				true
			);
		}

		// Include CSS for the dependent plugin admin notice on the plugins.php page:
		if ( 'index.php' === $hook || 'plugins.php' === $hook ) {
			wp_register_style(
				'boldgrid-notice-css',
				plugins_url(
					'assets/css/boldgrid-notice.css', BOLDGRID_BASE_DIR . '/includes'
				),
				array(),
				BOLDGRID_INSPIRATIONS_VERSION
			);

			wp_enqueue_style( 'boldgrid-notice-css' );
		}
	}

	/**
	 * Print admin notice, if needed.
	 *
	 * @see Boldgrid_Inspirations_Dependency_Plugins::show_notice().
	 */
	public function admin_notice() {
		// Get the $post global.
		global $post;

		// Get the $pagenow global.
		global $pagenow;

		$boldgrid_requires = __( 'The BoldGrid Inspirations plugin requires these other plugins for best results. Please click to install and activate', 'boldgrid-inspirations' );

		// Should we print the notice?
		if ( $this->show_notice() &&
			(
				'plugins.php' === $pagenow ||
			 	( 'post.php' === $pagenow && 'attachment' !== $post->post_type ) ||
				'post-new.php' === $pagenow ||
			 	( 'index.php' === $pagenow && ! isset( $_GET['page'] ) )
			)
		) {
		?>
	<div class='notice notice-warning is-dismissible boldgrid-admin-notice' data-admin-notice-id='class-dependency-plugins' >
		<p><?php echo $boldgrid_requires; ?></p>
		<?php $this->print_uninstalled_plugins(); ?>
	</div>
		<?php
		}
	}

	/**
	 * Notice dismiss callback.
	 */
	public function boldgrid_dismiss_notice_callback() {
		global $wpdb;

	// If the user cannot install a plugin, they cannot hide this notice.
	if( ! current_user_can( 'install_plugins' ) ) {
		wp_die();
	}

	// if we have valid data...
	if ( 'class-dependency-plugins' == $_POST['notice'] ) {
		// get the dismissed notices
		$boldgrid_dismissed_admin_notices = get_option( 'boldgrid_dismissed_admin_notices' );

			// Add our new notice to dismiss.
			$boldgrid_dismissed_admin_notices['class-dependency-plugins'] = true;

			// Save the changes.
			update_option( 'boldgrid_dismissed_admin_notices', $boldgrid_dismissed_admin_notices );
		}

		wp_die();
	}

	/**
	 * Set the class property $dependent_plugins_not_installed.
	 *
	 * @see Boldgrid_Inspirations_Dependency_Plugins::plugin_exists().
	 */
	public function get_dependent_plugins_not_installed() {
		foreach ( $this->dependent_plugins as $plugin_name => $plugin_dir_file ) {
			if ( ! $this->plugin_exists( $plugin_dir_file ) ) {
				$this->dependent_plugins_not_installed[ $plugin_name ] = $plugin_dir_file;
			}
		}
	}

	/**
	 * Hide list of plugins on the page.
	 *
	 * When we're installing plugins for the user, we're doing so in at plugins.php.
	 * After we install the plugins, they won't appear in the list until the user refreshes the
	 * page.
	 * This function hides the plugin list to avoid any confusion because of this.
	 *
	 * @see Boldgrid_Inspirations_Utility::inline_js_file().
	 */
	public function hide_plugin_list_during_installation() {
		global $pagenow;

		if ( 'plugins.php' === $pagenow &&
		$this->user_is_requesting_dependency_plugin_installation ) {
			Boldgrid_Inspirations_Utility::inline_js_file( 'hide_plugin_list_during_installation.js' );
		}
	}

	/**
	 * Install plugins.
	 *
	 * @see Boldgrid_Inspirations_Dependency_Plugins::add_plugin_to_active_plugins().
	 *
	 * @return bool
	 */
	public function install_plugins() {
		// Is the user able to install plugins?
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		if ( $this->user_is_requesting_dependency_plugin_installation ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

			foreach ( $_POST['boldgrid-plugin-install'] as $plugin_name => $plugin_dir_file ) {
				// Add the plugin to the 'active_plugins' option.
				$this->add_plugin_to_active_plugins( $plugin_name );

			?>
	<h1>
		Installing <em><?php echo $plugin_name; ?></em>
	</h1>
			<?php
				$plugin_url = $this->plugin_install_url[$plugin_name];

				$upgrader = new Plugin_Upgrader(
					new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

				$upgrader->install( $plugin_url );

				$result = activate_plugin( $plugin_name );
			?>
	<hr />
			<?php
			}

			// Alert success notices.
			?>
	<div class="updated inline">
		<p>
			Installation successful! Please <a href="plugins.php">click here</a>
			to visit your plugins page.
		</p>
	</div>
			<?php
		}

		return true;
	}

	/**
	 * Install the staging plugin via an ajax request.
	 *
	 * This is done during the final stage of Inspirations, when the user decided to download staging
	 * and install the site as a staged site.
	 *
	 * @since 1.2.5
	 */
	public function install_staging() {
		if( false === check_ajax_referer( 'nonce-install-staging', 'nonce-install-staging', false ) ) {
			wp_die( '0' );
		}

		$this->install_plugins();

		// Before wrapping up this ajax call, confirm that staging is installed.
		if( is_plugin_active( 'boldgrid-staging/boldgrid-staging.php' ) ) {
			wp_die( '1' );
		} else {
			wp_die( '0' );
		}
	}

	/**
	 * Check if a plugin exists.
	 *
	 * @param string $plugin_dir_file The plugin slug.
	 *
	 * @return bool
	 */
	public function plugin_exists( $plugin_dir_file ) {
		$current_plugins = get_plugins();

		foreach ( $current_plugins as $current_plugin_dir_file => $plugin_details ) {
			if ( $plugin_dir_file === $current_plugin_dir_file ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Print uninstalled plugins.
	 */
	public function print_uninstalled_plugins() {
		if ( current_user_can( 'install_plugins' ) ) {
			// Print a message if the release channel is not the stable release channel.
			if ( 'stable' !== $this->release_channel ) {
				?>
	<p>
		<font color='#c00'>
		<?php
		printf(
			esc_html__(
				'You are currently on the %s release channel.  You can go to %sBoldGrid Settings%s to change the release channel.',
				'boldgrid-inspirations'
			),
			ucfirst( $this->release_channel ),
			'<a href="' . admin_url( 'options-general.php?page=boldgrid-settings' ) . '">',
			'</a>'
		);
		?>
		</font>
	</p>
	<?php
			}

			?>
	<form method='post' action='plugins.php'>
		<ul>
				<?php

			// Print each plugin, as in.
			// [ ] boldgrid-editor
			// [ ] boldgrid-staging
			foreach ( $this->dependent_plugins_not_installed as $plugin_name => $plugin_dir_file ) {
				?><li><input type='checkbox'
				name='boldgrid-plugin-install[<?php echo $plugin_name?>]'
				id='boldgrid-plugin-install[<?php echo $plugin_name; ?>]'
				value='install' checked /><strong><?php
				// Determine the plugin key from the plugin name.
				$plugin_key = str_replace( 'boldgrid-', '', $plugin_name );

				// If we have a title, then print it, else print the plugin name.
				if ( ! empty( $this->plugin_install_title[ $plugin_key ] ) ) {
					echo $this->plugin_install_title[ $plugin_key ];
				} else {
					echo $plugin_name;
				}

				?></strong>
					<?php

				// If we have a version number, then print it.
				if ( ! empty( $this->plugin_install_version[ $plugin_key ] ) ) {
					echo ' Version ' . $this->plugin_install_version[ $plugin_key ];
				}
				?></li>
					<?php
			}
			?>
		</ul>
		<p>
			<input type='submit' value='Install' class='button button-primary' />
		</p>
	</form>
	<?php
		}
	}

	/**
	 * Show notice.
	 *
	 * @return bool
	 */
	public function show_notice() {
		$admin_notices = new Boldgrid_Inspirations_Admin_Notices();

		// If we've previously dismissed, return false.
		if( $admin_notices->has_been_dismissed( 'class-dependency-plugins' ) ) {
			return false;
		}

		// If the current user cannot install plugins, there's no need to show them this message.
		if( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		if ( ! isset( $_POST['boldgrid-plugin-install'] ) &&
		! empty( $this->dependent_plugins_not_installed ) ) {
			return true;
		}

		return false;
	}
}
