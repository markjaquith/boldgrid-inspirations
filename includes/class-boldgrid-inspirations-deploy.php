<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Deploy
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations Deploy class.
 */
class Boldgrid_Inspirations_Deploy {
	/**
	 * BoldGrid configs array.
	 *
	 * @access protected
	 *
	 * @var array $configs
	 */
	protected $configs;

	/**
	 * Default post status.
	 *
	 * @access protected
	 *
	 * @var string
	 */
	protected $post_status = 'publish';

	/**
	 * Active theme.
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $activate_theme = true;

	/**
	 * The homepage_only option.
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $homepage_only = false;

	/**
	 * Whether or not to add pages to menu.
	 *
	 * @access protected
	 *
	 * @var boolean
	 */
	protected $add_pages_to_menu = true;

	/**
	 * Whether to set the homepgae if we have it.
	 *
	 * @access protected
	 *
	 * @var boolean
	 */
	protected $set_homepage = true;

	/**
	 * A list of all the installed pages.
	 *
	 * @access protected
	 *
	 * @var array
	 */
	protected $full_page_list;

	/**
	 * Is this a generic build?
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $is_generic = false;

	/**
	 * Is this a preview server?
	 *
	 * @access protected
	 *
	 * @var bool
	 */
	protected $is_preview_server = false;

	/**
	 * Subcategory ID.
	 *
	 * @access protected
	 *
	 * @var int
	 */
	protected $subcategory_id = null;

	/**
	 * A variable to store the menu_id that we create using:
	 * $menu_id = wp_create_nav_menu( 'primary' );
	 *
	 * @access protected
	 *
	 * @var int
	 */
	public $primary_menu_id;

	/**
	 * As the installation process runs,
	 * we will record data about the plugins that are installed.
	 *
	 * @access protected
	 *
	 * @var array
	 */
	public $plugin_installation_data = array();

	/**
	 * Class property for the asset cache object (only for preview servers).
	 *
	 * @since 1.1.2
	 * @access private
	 *
	 * @var object|null
	 */
	private $asset_cache = null;

	/**
	 * Does the user want to start over before deployment?
	 *
	 * @since 1.2.3
	 * @access public
	 */
	public $start_over = false;

	/**
	 * The Boldgrid Inspirations Asset Manager class object.
	 *
	 * @var Boldgrid_Inspirations_Asset_Manager
	 */
	private $asset_manager;

	/**
	 * The Boldgrid Inspirations Built Photo Search class object.
	 *
	 * @var Boldgrid_Inspirations_Built_Photo_Search
	 */
	private $built_photo_search;

	/**
	 * Constructor.
	 *
	 * @param array $configs BoldGrid configuration array.
	 */
	public function __construct( $configs ) {
		// Set $this->configs class property.
		$this->configs = $configs;

		// Include the deploy pages class.
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-deploy-pages.php';

		// Instantiate the asset manager class.
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-asset-manager.php';
		$this->asset_manager = new Boldgrid_Inspirations_Asset_Manager();

		// Get the asset cache object from the asset manager.
		$this->asset_cache = $this->asset_manager->get_asset_cache();

		// Instantiate the built photo search class.
		require_once BOLDGRID_BASE_DIR .
			 '/includes/class-boldgrid-inspirations-built-photo-search.php';
		$this->built_photo_search = new Boldgrid_Inspirations_Built_Photo_Search();

		// Variables used for debug purposes.
		$this->start_time = time();
		$this->timer_start = microtime( true );
		$this->show_full_log = false;

		$this->full_deploy_log = null;
		$this->full_deploy_log['procedural'][] = '\t#########################################';
		$this->full_deploy_log['procedural'][] = '\tFULL DEPLOY LOG';
		$this->full_deploy_log['procedural'][] = '\t#########################################';

		$this->built_photo_search_log = array ();
		$this->built_photo_search_log['count'] = 0;

		// Includes Checking to see if external plugins are active
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-external-plugin.php';
		$this->external_plugin = new Boldgrid_Inspirations_External_Plugin();

		// Allow downloads over the backlan.
		add_filter( 'http_request_host_is_external',
			array (
				$this,
				'allow_downloads_over_the_backlan'
			), 10, 3 );
	}

	/**
	 * Getter for configs array.
	 *
	 * @return array $configs
	 */
	public function get_configs() {
		return $this->configs;
	}

	/**
	 * Setter for configs array.
	 *
	 * @param array $configs
	 *
	 * @return bool
	 */
	public function set_configs( $configs ) {
		$this->configs = $configs;
		return true;
	}

	/**
	 * Get deploy details.
	 *
	 * Get all of the details needed so we can deploy a new website.
	 * For example, we need to know which theme to install, which category, etc.
	 *
	 * @todo We are hard coding the details below. In the future, the values
	 *       will be grabbed from the options table.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 */
	public function get_deploy_details() {
		// Get configs.
		$boldgrid_configs = $this->get_configs();

		/*
		 * REQUIRED VARIABLES TO BE PASSED IN
		 */

		// REQUIRED - $this->page_set_id tells us which individual pages to download
		$this->page_set_id = intval( $_POST['boldgrid_page_set_id'] );

		// REQUIRED - we need authorization.
		// Look in the config for the api_key.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		// If the hash is missing, then try getting it from the configs.
		if ( empty( $api_key_hash ) ) {
			$api_key_hash = (
				isset( $this->configs['api_key'] ) ?
				sanitize_text_field( $this->configs['api_key'] ) : null
			);
		}

		// If the hash is still not found, then check $_REQUEST['key'].
		if ( empty( $api_key_hash ) && ! empty( $_REQUEST['key'] ) ) {
				$api_key_hash = sanitize_text_field( $_REQUEST['key'] );
		}

		// REQUIRED
		$this->site_hash = (
			isset( $_REQUEST['site_hash'] ) ?
			sanitize_title_with_dashes( trim( $_REQUEST['site_hash'] ) ) : null
		);

		$this->site_hash = (
			( null == $this->site_hash && isset( $boldgrid_configs['site_hash'] ) ) ?
			sanitize_title_with_dashes( $boldgrid_configs['site_hash'] ) : $this->site_hash
		);

		// REQUIRED
		$this->theme_id = intval( $_POST['boldgrid_theme_id'] );

		// REQUIRED - used to get primary display elements
		if ( isset( $_POST['boldgrid_pde'] ) ) {
			if ( is_array( $_POST['boldgrid_pde'] ) ) {
				$this->pde = is_array( $_POST['boldgrid_pde'] ) ? $_POST['boldgrid_pde'] : null;
			} else {
				$this->pde = json_decode( stripslashes( $_POST['boldgrid_pde'] ), true );
			}
		} else {
			$this->pde = null;
		}

		// Get and set the subcategory:
		// REQUIRED todo: subcategory_id is used in deploy_page_sets to get homepage data... Should
		// this actually be category_id ?
		$this->subcategory_id = null;
		if ( ! empty( $_POST['boldgrid_sub_cat_id'] ) ) {
			// For most requests:
			$this->subcategory_id = intval( $_POST['boldgrid_sub_cat_id'] );
		} elseif ( ! empty( $_POST['subcategory_id'] ) ) {
			// For direct call to deploy_page_sets:
			$this->subcategory_id = intval( $_POST['subcategory_id'] );
		} else {
			// If subcategory is not available in POST, then try to get it from the install options:
			if ( $this->is_staging_install() ) {
				$install_options = get_option( 'boldgrid_staging_boldgrid_install_options' );
			} else {
				$install_options = get_option( 'boldgrid_install_options' );
			}

			if ( ! empty ( $install_options['subcategory_id'] ) ) {
				$this->subcategory_id = $install_options['subcategory_id'];
			}
		}

		// KIND OF REQURED... Used when getting built_photos_search photos
		$this->language_id = ( isset( $_POST['boldgrid_language_id'] ) ? intval(
			$_POST['boldgrid_language_id'] ) : null );

		// REQUIRED
		$this->asset_user_id = ( isset( $_POST['asset_user_id'] ) ? intval(
			$_POST['asset_user_id'] ) : null );

		/*
		 * REQUIRED VARIABLES ON SELECT SERVERS
		 */

		// REQUIRED ONLY on preview server
		$this->new_path = ( isset( $_POST['boldgrid_new_path'] ) ? trim(
			$_POST['boldgrid_new_path'] ) : '' );

		// REQUIRED ONLY on preview server
		$this->create_preview_site = ( isset( $_POST['create_preview_site'] ) &&
			 $_POST['create_preview_site'] ? true : false );

		// REQUIRED ONLY on author server
		$this->ticket_number = ( isset( $_POST['boldgrid_ticket_number'] ) ? sanitize_text_field(
			$_POST['boldgrid_ticket_number'] ) : false );

		// OPTIONAL: Passing this array of page id's will install only these pages.
		$this->custom_pages = $this->get_pages_param();

		// OPTIONAL: Passing this array of page id's will install only these pages.
		$this->deploy_type = ( isset( $_POST['deploy-type'] ) ? sanitize_text_field(
			$_POST['deploy-type'] ) : null );

		/*
		 * REQUIRED VARIABLES to be calculated at runtime
		 */

		// REQUIRED
		$this->is_preview_server = ( $boldgrid_configs['preview_server'] ==
			 'https://' . $_SERVER['SERVER_NAME'] ||
			 $boldgrid_configs['author_preview_server'] == 'https://' . $_SERVER['SERVER_NAME'] ? true : false );

		// REQUIRED to allow for budget and cost tracking
		$this->current_build_cost = 0;
		$this->coin_budget = isset( $_POST['coin_budget'] ) ? intval( $_POST['coin_budget'] ) : 20;

		// Default these to the active version if not explicitly passed.
		$this->theme_version_type = isset( $_POST['boldgrid_theme_version_type'] ) ? sanitize_text_field(
			$_POST['boldgrid_theme_version_type'] ) : 'active';
		$this->page_set_version_type = isset( $_POST['boldgrid_page_set_version_type'] ) ? sanitize_text_field(
			$_POST['boldgrid_page_set_version_type'] ) : 'active';

		$this->is_author = isset( $_POST['author_type'] ) ? true : false;

		$this->boldgrid_build_profile_id = isset( $_POST['boldgrid_build_profile_id'] ) ? intval(
			$_POST['boldgrid_build_profile_id'] ) : null;

		// Is this a generic build?
		if( $this->is_preview_server && isset( $_POST['is_generic'] ) && '1' === $_POST['is_generic'] ) {
			$this->is_generic = true;
		}

		// Does the user want to start over?
		if( isset( $_POST['start_over'] ) && 'true' === $_POST['start_over'] ) {
			$this->start_over = true;
		}
	}

	/**
	 * Sets Deploy Options
	 */
	public function update_install_options() {
		// store these install options for later use
		$boldgrid_install_options = array (
			'author_type' => isset( $_POST['author_type'] ) ? sanitize_text_field(
				$_POST['author_type'] ) : null,
			'language_id' => isset( $_POST['language_id'] ) ? intval( $_POST['language_id'] ) : null,
			'theme_group_id' => isset( $_POST['theme_group'] ) ? sanitize_text_field(
				$_POST['theme_group'] ) : null,
			'theme_id' => intval( $this->theme_id ),
			'theme_version_type' => sanitize_text_field( $this->theme_version_type ),
			'category_id' => isset( $_POST['boldgrid_cat_id'] ) ? intval(
				$_POST['boldgrid_cat_id'] ) : null,
			'subcategory_id' => intval( $this->subcategory_id ),
			'page_set_id' => intval( $this->page_set_id ),
			'page_set_version_type' => sanitize_text_field( $this->page_set_version_type ),
			'pde' => $this->pde,
			'new_path' => trim( $this->new_path ),
			'ticket_number' => sanitize_text_field( $this->ticket_number ),
			'build_profile_id' => intval( $this->boldgrid_build_profile_id ),
			'custom_pages' => $this->custom_pages,
			'install_timestamp' => time()
		);

		update_option( 'boldgrid_install_options', $boldgrid_install_options );
	}

	/**
	 * Grab installation details from the asset server.
	 *
	 * This method is intended to retrieve options in bulk instead of retrieving install data
	 * 1 call at a time.
	 *
	 * @since 1.1.2
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 *
	 * @return array Array of pages.
	 */
	public function remote_install_options() {
		$this->change_deploy_status( 'Updating Remote Install Options...' );

		// Get configs.
		$boldgrid_install_options = get_option( 'boldgrid_install_options' );
		$boldgrid_configs = $this->get_configs();

		// Reach out to the asset server to get a collection of install options.
		$get_install_details = $boldgrid_configs['asset_server'] .
			 $boldgrid_configs['ajax_calls']['get_install_details'];

		// Get the API key hash.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		$arguments = array (
			'method' => 'POST',
			'body' => array (
				'subcategory_id' => $boldgrid_install_options['subcategory_id'],
				'page_set_id' => $boldgrid_install_options['page_set_id'],
				'key' => ! empty( $api_key_hash ) ? $api_key_hash : null
			),
			'timeout' => 20
		);

		$response = wp_remote_retrieve_body( wp_remote_post( $get_install_details, $arguments ) );
		$response = json_decode( $response ?  : '', true );
		$remote_options = (
			! empty( $response['result']['data'] ) ? $response['result']['data'] : array()
		);

		// Update the boldgird_ install options array.
		$boldgrid_install_options = array_merge( $boldgrid_install_options, $remote_options );
		update_option( 'boldgrid_install_options', $boldgrid_install_options );

		$this->add_to_deploy_log( 'Finished Updating Remote Install Options.' );
	}

	/**
	 * Get pages from POST request and return them in an array.
	 *
	 * @return array Array of pages.
	 */
	public function get_pages_param() {
		$pages = array ();
		if ( isset( $_POST['pages'] ) ) {
			if ( is_array( $_POST['pages'] ) ) {
				$pages = $_POST['pages'];
			} else {
				$pages = json_decode( stripslashes( trim( $_POST['pages'] ) ), true );
			}
		}

		return $pages;
	}

	/**
	 * Get remote page id from local page id.
	 *
	 * @param integer $page_id
	 */
	public function get_remote_page_id_from_local_page_id( $page_id ) {
		foreach ( $this->installed_page_ids as $remote_page_id => $local_page_id ) {
			if ( $local_page_id == $page_id ) {
				return $remote_page_id;
			}
		}
	}

	/**
	 * If we're on the preview server, create a new site
	 */
	public function create_new_install() {
		if ( $this->is_preview_server && $this->create_preview_site ) {
			// Set the blog title:
			$blog_title = 'Company Name';

			// create the new blog
			$this->add_to_deploy_log( 'Creating new blog...' );
			$new_blog_id = wpmu_create_blog( $_SERVER['SERVER_NAME'], '/' . $this->new_path,
				$blog_title, get_current_user_id() );
			$this->add_to_deploy_log( 'Finished, new blog created!', false );

			if ( is_object( $new_blog_id ) ) {
				?>
<pre>
				<?php print_r( $new_blog_id ); ?>
</pre>
<?php
			}

			// Switch to the new blog.
			$this->add_to_deploy_log( 'Switching to new blog...', false );
			switch_to_blog( $new_blog_id );

			// Set the blog's admin email address using the network admin email address.
			$email_address = get_site_option( 'admin_email' );

			update_option( 'admin_email' , $email_address );

			// If this is a generic build, then set an option to identify it later (purges, etc.).
			if ( $this->is_generic ) {
				update_option( 'is_generic_build', true );
			}

			// Ensure that we have the current boldgrid_asset information (should be empty).
			$this->asset_manager->get_wp_options_asset();

			// JoeC says site needs to be https, so let's get er done
			$path_to_new_blog = esc_url(
				'https://' . $_SERVER['SERVER_NAME'] . '/' . $this->new_path );
			update_option( 'siteurl', $path_to_new_blog );
			update_option( 'home', $path_to_new_blog );
			update_option( 'upload_url_path', $path_to_new_blog . '/wp-content/uploads' );

			// Disable comments:
			update_option( 'default_comment_status', 'closed' );

			$this->add_to_deploy_log( 'New blog has been created and switched to.' );
		}
	}

	/**
	 * http://stackoverflow.com/questions/10589889/returning-header-as-array-using-curl
	 *
	 * @param string $response
	 *
	 * @return array
	 */
	public function curl_response_arrayify( $response ) {
		$headers = array ();

		$strpos_rnrn = strpos( $response, "\r\n\r\n" );

		$header_text = substr( $response, 0, $strpos_rnrn );
		$body = substr( $response, $strpos_rnrn );

		foreach ( explode( "\r\n", $header_text ) as $i => $line ) {
			if ( $i === 0 )
				$headers['http_code'] = $line;
			else {
				list ( $key, $value ) = explode( ': ', $line );

				$headers[strtolower( $key )] = $value;
			}
		}

		$file['headers'] = $headers;
		$file['body'] = trim( $body );

		return $file;
	}

	/**
	 * Update a site (network) option until successful or timeout.
	 * Same as update_site_option, except with a retry feature with a timeout.
	 * Also returns true if the old value matches the new value, instead of false.
	 *
	 * @param string $option Option name.
	 * @param mixed $value Option value.
	 * @param int $timeout A timeout in seconds. Default is 5 seconds.
	 *
	 * @return bool
	 */
	public function update_site_option_retry( $option = null, $value = null, $timeout = 5 ) {
		// Validate input:
		if ( empty( $option ) || empty( $value ) || ! is_numeric( $timeout ) || $timeout < 0 ) {
			return false;
		}

		// If the current value matches the new value, then return true:
		if ( get_site_option( $option, false, false ) == $value ) {
			return true;
		}

		// Initialize $start_time:
		$start_time = time();

		// Initialize $success:
		$success = false;

		// Determine $deadline:
		$deadline = $start_time + $timeout;

		while ( ! $success && time() < $deadline ) {
			if ( update_site_option( $option, $value ) ) {
				// Success: Return true:
				return true;
			}

			// Sleep for a moment:
			usleep( rand( 150000, 250000 ) );
		}

		// Failure: Return false:
		return false;
	}

	/**
	 * Download the theme chosen by the user and set it as the active theme
	 *
	 * @todo Refactor/rework this method. It should be moved to Boldgrid_Inspirations_Theme_Install.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 *
	 * @return string or false
	 */
	public function deploy_theme() {
		$this->change_deploy_status( 'Downloading theme...' );
		$this->add_to_deploy_log( 'Beginning theme deployment.' );

		// Get configs:
		$boldgrid_configs = $this->get_configs();

		// Connect to the asset server and get all of the details for our theme.
		$url_to_get_theme_details = $boldgrid_configs['asset_server'] .
			 $boldgrid_configs['ajax_calls']['get_theme_details'];

		// Get the API key hash.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		$arguments = array (
			'method' => 'POST',
			'body' => array (
				'theme_id' => $this->theme_id,
				'page_set_id' => $this->page_set_id,
				'theme_version_type' => $this->theme_version_type,
				'is_preview_server' => $this->is_preview_server,
				'build_profile_id' => $this->boldgrid_build_profile_id,
				'is_staged' => ! empty( $_POST['staging'] ) ? trim( $_POST['staging'] ) : null,
				'key' => ! empty( $api_key_hash ) ? $api_key_hash : null,
				'site_hash' => ! empty( $boldgrid_configs['site_hash'] ) ? $boldgrid_configs['site_hash'] : null
			),
			'timeout' => 20
		);

		$response = wp_remote_post( $url_to_get_theme_details, $arguments );

		if ( is_wp_error( $response ) ) {
			// LOG:
			error_log(
				__METHOD__ .
					 ': Error: Received WP_Error in wp_remote_post to the asset server. Response: ' .
					 print_r( $response, true ) );

			// Unrecoverable error:
			$this->add_to_deploy_log( "Error: Failed to retrieve theme!" );

			// $this->add_to_deploy_log( "Error: Exiting theme deployment." );
			// Failing deployment should be avoided at this time.
			// $this->fail_deployment( $response->get_error_message() );

			return false;
		}

		$this->theme_details = json_decode( $response['body'] );

		if ( ! isset( $this->theme_details->status ) || 200 != $this->theme_details->status ) {
			$this->add_to_deploy_log(
				'Error: Received an unsuccessful return code when retrieving theme information!' );
			// LOG:
			error_log(
				__METHOD__ . ': Error: ' . ( isset( $this->theme_details->status ) ? 'Received status code "' .
					 $this->theme_details->status .
					 '" when retrieving theme details from the asset server' : 'Failed theme details: ' .
					 print_r( $this->theme_details, true ) ) . print_r(
						array (
							'$arguments' => $arguments,
							'$response' => print_r( $response, true )
						), true ) );

			// Failing deployment should be avoided at this time.
			// $this->fail_deployment( "Non 200 status when getting theme details." );

			return;
		}

		$this->theme_details = $this->theme_details->result->data;

		/**
		 * At this point, we have the theme details from the asset server.
		 */

		/**
		 * When the deployment script was initially written, $this->theme_details was always used
		 * under the assumption
		 * that there was no parent / child themes being used.
		 *
		 * $this->theme_details is used in several places within the deployment script. Other than
		 * this deploy_theme
		 * method, $this->theme_details is always referring to a single theme (ie not a parent and a
		 * child theme)
		 *
		 * Whew... So basically, if this is the child theme, store the theme_details temporarily.
		 * After the deploy_theme
		 * method is complete, set $this->theme_details = $this->child_theme_details
		 */
		// Temporarily save the theme details:
		$this->theme_details_original = $this->theme_details;

		foreach ( array (
			'child',
			//'parent'
		) as $entity ) {
			if ( 'parent' == $entity ) {
				$this->theme_details = $this->theme_details->parent;
				// If parent doesnt exists, continue (skip this iteration)
				if ( empty( $this->theme_details ) ) {
					continue;
				}
			}

			// Theme Folder name is the same as theme name.
			$theme_folder_name = $this->theme_details->theme->Name;

			if ( $this->is_preview_server ) {
				// Use the random filename instead.
				$theme_folder_name = wp_basename( $this->theme_details->themeAssetFilename, '.zip' );
			}

			$theme = wp_get_theme( $theme_folder_name );

			// Get the installed theme version timestamp from wp options:
			$theme_version_option_name = 'boldgrid_theme_revision_' .
				 $this->theme_details->themeRevision->Title;

			$theme_dir = ABSPATH . 'wp-content/themes/' . $theme_folder_name;
			$theme_dir_exists = is_dir( $theme_dir );

			if ( is_multisite() ) {
				$installed_theme_version = get_site_option( $theme_version_option_name, null,
					false );

				if ( $installed_theme_version && ! $theme_dir_exists ) {
					delete_site_option( $theme_version_option_name );

					$installed_theme_version = null;
				}
			} else {
				$installed_theme_version = get_option( $theme_version_option_name );

				if ( $installed_theme_version && ! $theme_dir_exists ) {
					delete_option( $theme_version_option_name );

					$installed_theme_version = null;
				}
			}

			// If attempting to install over a .git directory, don't install theme.
			// Only do this if is author because if a git is accidently commited,
			// Theme will not install for anyone.
			$is_git_theme = false;
			if ( $theme_dir_exists && $this->is_author && ! $this->is_preview_server ) {
				$is_git_theme = in_array( '.git', scandir( $theme_dir ) );
			}

			$incoming_theme_version = $this->theme_details->themeRevision->RevisionNumber;
			$incoming_version_number = ! empty( $this->theme_details->themeRevision->VersionNumber ) ?
				$this->theme_details->themeRevision->VersionNumber : null;
			$installed_version_number = is_object( $theme ) ? $theme->get('Version') : null;

			$is_version_change = $incoming_version_number && ( $incoming_version_number != $installed_version_number );
			$install_this_theme = ( $is_version_change || ! $theme_dir_exists ) && ! $is_git_theme;

			/**
			 * About to attempt to install this theme.
			 */

			// Check if theme is already installed and the latest version:
			if ( $install_this_theme ) {
				$theme_url = $boldgrid_configs['asset_server'] .
					 $boldgrid_configs['ajax_calls']['get_asset'] . '?id=' .
					 $this->theme_details->themeRevision->AssetId;

				if ( ! empty( $api_key_hash ) ) {
					$theme_url .= '&key=' . $api_key_hash;
				}

				// If this is a user environment, install for repo.boldgrid.com.
				if ( ! $this->is_preview_server ) {
					$theme_url = $this->theme_details->repo_download_link;
				}

				$theme_installation_done = false;
				$theme_installation_failed_attemps = 0;

				while ( false == $theme_installation_done ) {
					if ( is_multisite() && $this->is_preview_server ) {
						// Get the WordPress version:
						global $wp_version;

						// If WordPress >=4.4.0, flush the WordPress object cache,
						// or rely on the 3rd parameter of get_site_option as false to disable cache:
						if ( version_compare( $wp_version, '4.4.0', '>=' ) ) {
							wp_cache_flush();
						}

						// Get the WP Option boldgrid_we_are_currently_installing_a_theme:
						$we_are_currently_installing_a_theme = get_site_option(
							'boldgrid_we_are_currently_installing_a_theme', false, false
						);

						if ( $theme_installation_failed_attemps >
							 $boldgrid_configs['installation']['max_num_install_attempts'] ) {

							// LOG:
							error_log(
								__METHOD__ .
								 ': Error: Failed to install theme; Exceeded max theme install attempts. ' . print_r(
									array (
										'$this->theme_details' => $this->theme_details
									), true ) );

							$this->add_to_deploy_log(
								'Error: Exceeded max theme install attempts!' );

							$this->add_to_deploy_log( 'Error: Exiting theme deployment.' );

							return false;
						}

						/**
						 * Should we install this theme?
						 * For example, if we're already installing a different
						 * theme, then we'll want to wait before
						 * that completes before we install this theme.
						 */
						$theme_install_wait_time = 20;

						if ( false == $we_are_currently_installing_a_theme ) {
							// Get the installed theme version:
							$installed_theme_version = get_site_option( $theme_version_option_name,
								null, false
							);

							// Check the current theme version against the incoming version:
							$install_this_theme = ( $installed_theme_version !=
								 $incoming_theme_version );

							if ( ! $install_this_theme ) {
								// Latest theme already installed, so break out of the while loop:
								break;
							}
						} elseif ( time() - $we_are_currently_installing_a_theme <
							 $theme_install_wait_time ) {
							// The last install was initiated within the last 20 seconds,
							// so wait a little longer.
							$install_this_theme = false;
						} else {
							// The last install theme install fatally failed and/or
							// was more than 20 seconds ago.
							// either way, life must move on. let's try to install
							// this theme.
							$install_this_theme = true;
						}

						/**
						 * If we do have the 'go ahead' to install a this theme
						 * right now, let's try to 'lock it'
						 * so that other themes aren't installed this very moment
						 */
						if ( true == $install_this_theme ) {
							if ( ! $this->update_site_option_retry(
								'boldgrid_we_are_currently_installing_a_theme', time() ) ) {
								$install_this_theme = false;
							} else {
								$this->update_site_option_retry(
									'boldgrid_we_are_currently_installing_this_theme',
									$this->theme_details->theme->Name );
							}
						}

						/**
						 * Multiple themes could be locking at the same time.
						 * Let's make sure the current theme is the
						 * the theme that set the lock.
						 */
						if ( true == $install_this_theme ) {
							$we_are_currently_installing_this_theme = get_site_option(
								'boldgrid_we_are_currently_installing_this_theme', false, false );
							if ( $this->theme_details->theme->Name !=
								 $we_are_currently_installing_this_theme ) {
								$install_this_theme = false;
							}
						}
						// else, we are not on a multisite, so go ahead and try to
						// install the theme
					} else {
						$install_this_theme = true;
					}

					/**
					 * If we ultimately decided not to attempt theme installation at
					 * this second, sleep for a bit
					 * and try again.
					 */
					if ( false == $install_this_theme ) {
						sleep( 1 );
						// Increment the failed attempts counter:
						$theme_installation_failed_attemps += 0.5;
					} else {

						// Delete the old theme, if exists:
						if ( $theme->exists() ) {
							delete_theme( $theme_folder_name );
							$this->add_to_deploy_log(
								'Theme already installed, updating to the latest copy.' );
						}

						// Install the theme:
						include_once ABSPATH . 'wp-admin/includes/file.php';
						include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

						$upgrader = new Theme_Upgrader(
							new Theme_Installer_Skin(
								compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

						// Download and install the theme:
						$wp_theme_install_success = $upgrader->install( $theme_url,
							array (
								'clear_destination' => true,
								'abort_if_destination_exists' => false
							) );

						$this->theme_name = $upgrader->result['destination_name'];

						// If Theme_Upgrader::install reports failure or we have no theme name, then
						// something went wrong.
						if ( ( ! $wp_theme_install_success || empty( $this->theme_name ) ) ) {
							// Delete the theme:
							delete_theme( $this->theme_details->theme->Name );

							// Increment the failed attempts counter:
							$theme_installation_failed_attemps ++;

							// LOG:
							error_log(
								__METHOD__ . 'Error: Failed to install theme. ' . print_r(
									array (
										'is_wp_error' => is_wp_error( $wp_theme_install_success ) ? 'true' : 'false',
										'WP_Error' => is_wp_error( $wp_theme_install_success ) ? get_error_messages(
											$wp_theme_install_success ) : $wp_theme_install_success,
										'$theme_url' => $theme_url,
										'$this->theme_details' => $this->theme_details,
										'theme_name' => $this->theme_name
									), true ) );

							$this->add_to_deploy_log( 'Error: Exiting theme deployment.' );

							// On multisite, remove locks.
							if ( is_multisite() ) {
								$we_are_currently_installing_this_theme = get_site_option(
									'boldgrid_we_are_currently_installing_this_theme', false, false
								);

								if ( $this->theme_details->theme->Name ===
								$we_are_currently_installing_this_theme ) {
									delete_site_option(
										'boldgrid_we_are_currently_installing_this_theme' );
									delete_site_option(
										'boldgrid_we_are_currently_installing_a_theme' );
								}
							}

							return false;
						} else {
							// Looks like the theme was installed successfully.
							$theme_installation_done = true;

							// Set wp options to mark the newly-installed them version.
							if ( is_multisite() ) {
								$this->update_site_option_retry( $theme_version_option_name,
									$incoming_theme_version );
							} else {
								update_option( $theme_version_option_name, $incoming_theme_version );
							}
						}

						// Regardless of whether we failed or succeeded, we're not longer
						// installing.
						if ( is_multisite() ) {
							delete_site_option( 'boldgrid_we_are_currently_installing_a_theme' );
							delete_site_option( 'boldgrid_we_are_currently_installing_this_theme' );
						}
					}
				} // End of while.
			}

			// Enable Theme Sitewide.
			$allowed_themes = get_site_option( 'allowedthemes' );
			$allowed_themes[$theme_folder_name] = true;
			$this->update_site_option_retry( 'allowedthemes', $allowed_themes );

			if ( 'child' == $entity ) {
				// Save the theme id as a theme mod.
				$this->set_theme_mod_id( $theme_folder_name, $this->theme_details->theme->Id );

				if ( $this->activate_theme ) {
					$activation_theme = $theme_folder_name;

					// For authors, activate the git repo instead of the theme.
					if ( $this->is_author && ! empty( $this->theme_details->theme->GitRepoUrl ) ) {
						$repo_name = basename( $this->theme_details->theme->GitRepoUrl );
						if ( file_exists( get_theme_root() . '/' . $repo_name ) ) {
							$activation_theme = $repo_name;
						}
					}

					// Activate the theme.
					switch_theme( $activation_theme );
				}
			}

			// Enable theme options:
			if ( isset( $this->theme_details->options ) ) {
				foreach ( $this->theme_details->options as $option_k => $option_obj ) {
					update_option( $option_obj->name, $option_obj->value, '',
						$option_obj->autoload );
				}
			}
		} // foreach( array ( 'child', 'parent' ) as $entity )

		// Reset the $this->theme_details variable. Refer to loooon comment above as to why.
		$this->theme_details = $this->theme_details_original;

		$this->add_to_deploy_log( 'Finished theme deployment.' );

		do_action( 'boldgrid_deployment_deploy_theme_pre_return', $theme_folder_name );

		return $theme_folder_name;
	} // public function deploy_theme()

	/**
	 * Set the theme id of the given theme, as a theme mod
	 *
	 * @param srting $theme_name
	 * @param integer $theme_id
	 */
	public function set_theme_mod_id( $theme_name, $theme_id ) {
		$theme_mods = get_option( 'theme_mods_' . $theme_name );
		if ( ! $theme_mods ) {
			$theme_mods = array ();
		}

		$theme_mods['_boldgrid_theme_id'] = $theme_id;
		update_option( 'theme_mods_' . $theme_name, $theme_mods );
	}

	/**
	 * Start over before deployment.
	 *
	 * @since 1.2.3
	 */
	public function start_over() {
		// If the user does not want to start over, abort.
		if( ! $this->start_over ) {
			return;
		}

		// Check our nonce.
		check_admin_referer( 'deploy', 'deploy' );

		$start_over = new BoldGrid_Inspirations_Start_over();

		// Are we starting over with our active site?
		$start_over->start_over_active = ( false === $this->is_staging_install() );

		// Are we starting over with our staging site?
		$start_over->start_over_staging = (true === $this->is_staging_install() );

		// Are we deleting forms?
		$start_over->delete_forms = false;

		// Are we deleting pages?
		$start_over->delete_pages = false;

		// Are we deleting themes?
		$start_over->delete_themes = false;

		$start_over->start_over();
	}

	/**
	 * When we install a page we attach post meta data to indicate that it is a boldgrid page
	 * This function returns all pages that are still installed on the users wordpress that were
	 * created by boldgrid
	 *
	 * @return array
	 */
	public function get_existing_pages() {
		$previous_install_options = Boldgrid_Inspirations_Built::find_all_install_options();

		if ( true == $this->is_staging_install() ) {
			$installed_pages = $previous_install_options['boldgrid_staging_options']['installed_pages'];
		} else {
			$installed_pages = $previous_install_options['active_options']['installed_pages'];
		}

		return is_array( $installed_pages ) ? $installed_pages : array ();
	}

	/**
	 * Download and import the page set the user selected
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 * @link http://codex.wordpress.org/Function_Reference/wp_insert_post
	 */
	public function deploy_page_sets() {
		$this->change_deploy_status( 'Creating pages...' );
		$this->add_to_deploy_log( 'Beginning page set deployment.' );

		$pages_created = 0;

		/**
		 * ********************************************************************
		 * Create a menu
		 * ********************************************************************
		 */
		if ( $this->add_pages_to_menu ) {
			// Set the menu name
			$menu_name = 'primary';

			// Allow plugins, like BoldGrid Staging, to create 'primary-staging' instead of
			// 'primary'.
			$menu_name = apply_filters( 'boldgrid_deployment_primary_menu_name', $menu_name );

			// We want to start fresh, so if the menu exists, delete it.
			$menu_exists = wp_get_nav_menu_object( $menu_name );
			if ( true == $menu_exists ) {
				wp_delete_nav_menu( $menu_name );
			}

			// Create the menu
			$menu_id = wp_create_nav_menu( $menu_name );
			$this->primary_menu_id = $menu_id;

			$this->assign_menu_id_to_all_locations( $menu_id );
		}
		/**
		 * ********************************************************************
		 * Begin downloading all of the pages in the pageset.
		 * ********************************************************************
		 */

		// Download all of the pages in our pageset:
		// Get configs:
		$boldgrid_configs = $this->get_configs();

		// Set the pageset url:
		$page_set_url = $boldgrid_configs['asset_server'] .
			 $boldgrid_configs['ajax_calls']['get_page_set'];

		// Determine the release channel:
		$options = get_option( 'boldgrid_settings' );

		$release_channel = isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable';

		// Get the theme id, category id, etc.
		$this->get_deploy_details();

		// Build API call arguments:
		$arguments = array (
			'method' => 'POST',
			'body' => array (
				'page_set_id' => $this->page_set_id,
				'theme_id' => $this->theme_id,
				'subcategory_id' => $this->subcategory_id,
				'page_set_version_type' => $this->page_set_version_type,
				'custom_pages' => $this->custom_pages,
				'homepage_only' => $this->homepage_only,
				'channel' => $release_channel
			)
		);
		// 'include_additional_pages' => 1,

		// Get the API key hash.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		// Add the API key to the arguments:
		if ( ! empty( $api_key_hash ) ) {
			$arguments['body']['key'] = $api_key_hash;
		}

		// Make a call to the asset server:
		$response = wp_remote_post( $page_set_url, $arguments );

		// Check response:
		if ( is_wp_error( $response ) ) {
			$error_message = $response->get_error_message();
			$this->add_to_deploy_log( 'WP ERROR: ' . $error_message );
		}

		// JSON decode the response into an object:
		$json_response = json_decode( $response['body'] );

		// Check response code:
		if ( 200 != $json_response->status ) {
			$this->add_to_deploy_log( 'Error: Asset server did not return HTTP 200 OK!' );

			// LOG:
			error_log(
				__METHOD__ . ': Error: Asset server did not return HTTP 200 OK.  ' . print_r(
					array (
						'$page_set_url' => $page_set_url,
						'$arguments' => print_r( $arguments, true ),
						'$response' => print_r( $response, true )
					), true ) );
		}

		// Check the response data:
		if ( empty( $json_response->result->data ) ) {
			$this->add_to_deploy_log( 'Error: Asset server returned an empty data set!' );

			// LOG:
			error_log(
				__METHOD__ . ': Error: Asset server returned an empty data set.  ' . print_r(
					array (
						'$page_set_url' => $page_set_url,
						'$arguments' => print_r( $arguments, true ),
						'$response' => print_r( $response, true )
					), true ) );
		}

		// Save the parent category name, if available:
		if ( ! empty( $json_response->result->data->parent_category_name ) ) {
			$this->update_existing_install_options(
				array (
					'parent_category_name' => $json_response->result->data->parent_category_name
				) );
		}

		$pages_in_pageset = isset( $json_response->result->data->pages ) ? $json_response->result->data->pages : array ();
		$additional_pages = ! empty( $json_response->result->data->additional_pages ) ? $json_response->result->data->additional_pages : array ();

		/*
		 * This is a list of the pages that the user requested as well additional
		 * pages included in their category that will later be used to create grid blocks
		 * and pages.
		 */
		$this->full_page_list = array (
			'pages' => array (
				'pages_in_pageset' => $pages_in_pageset,
				'additional' => $additional_pages
			)
		);
		// 'plugins' => '...' potentially a list of plugins related to the complete list of pages

		$this->installed_page_ids = array ();

		$boldgrid_installed_pages_metadata = array ();

		$existing_pages_from_meta_data = $this->get_existing_pages();

		foreach ( $pages_in_pageset as $page_k => $page_v ) {
			if ( ! is_object( $page_v ) ) {
				continue;
			}

			/**
			 * *Prevent the user from installing the same page twice**
			 */
			// This was put in place in order to prevent homepages that were installed automatically
			// from being installed multiple times
			if ( in_array( $page_v->id, $existing_pages_from_meta_data ) ) {
				continue;
			}

			// is this a page or a post?
			/*
			 * $page_type = "post"; if($page_v->is_post == 0) $page_type = "page";
			 */

			$page_type = $page_v->post_type;

			// insert the page
			$post['post_content'] = $page_v->code;
			$post['post_name'] = $page_v->page_slug;
			$post['post_title'] = $page_v->page_title;
			$post['post_status'] = $this->post_status;
			$post['post_type'] = $page_type;
			$post['comment_status'] = 'closed';

			// Allow other plugins to modify the post.
			$post = apply_filters( 'boldgrid_deployment_pre_insert_post', $post );

			$post_id = wp_insert_post( $post );

			// store the pages we created for later use
			$this->installed_page_ids[$page_v->id] = $post_id;

			// Store additional info about the pages.
			$boldgrid_installed_pages_metadata[$post_id] = array (
				'is_readonly' => $page_v->is_readonly,
				'post_type' => $post['post_type'],
				'post_status' => $post['post_status']
			);

			// add page to menu
			if ( '1' == $page_v->in_menu && $this->add_pages_to_menu ) {
				// configure the url to this page
				$menu_item_url = home_url( '/' );

				if ( ! $page_v->homepage_theme_id ) {
					$menu_item_url = home_url( '/' ) . "?p=$post_id";
				}

				$this->add_to_deploy_log(
					'Adding page: <em>' . $page_v->page_title . '</em> to primary menu.', false );

				$menu_item_db_id = wp_update_nav_menu_item( $menu_id, 0,
					array (
						'menu-item-object-id' => $post_id,
						'menu-item-parent-id' => 0,
						'menu-item-object' => 'page',
						'menu-item-type' => 'post_type',
						'menu-item-status' => 'publish'
					) );
			}

			if ( $page_v->checklist_html ) {
				$to_do_list[] = $page_v->checklist_html . ' <a href="post.php?post=' . $post_id .
					 '&action=edit">Click here.</a>';
			}

			// set homepage
			if ( $page_v->homepage_theme_id && $this->set_homepage ) {
				update_option( 'show_on_front', 'page' );
				update_option( 'page_on_front', $post_id );
			}

			// Is there post meta to be set?
			//
			// As of 2015.01.15, this code block below only assigns page templates to pages.
			// ... page templates meaning page-full.php, page-inside.php, etc. // BradM //
			if ( isset( $this->theme_details->postmeta ) ) {
				foreach ( $this->theme_details->postmeta as $post_meta ) {
					// As of 2015.01.15, $column_name will always = "layout"
					// this is because 'layout' is the only distinct value for columnName currently
					// in the table
					$column_name = $post_meta->ColumnName;

					if ( $page_v->$column_name == $post_meta->ColumnValue ) {
						add_post_meta( $post_id, $post_meta->KeyName, $post_meta->KeyValue );
					}
				}
			}

			// do we have any featured images?
			if ( $page_v->featured_image_asset_id ) {
				$this->asset_manager->download_and_attach_asset( $post_id, true,
					$page_v->featured_image_asset_id );
			}

			$pages_created ++;

			$page_id_to_post[$page_v->id] = $post_id;

			// Add the page id so that we can recognize it
			add_post_meta( $post_id, 'boldgrid_page_id', $page_v->id );
		}

		// Download Plugins needed for pages:
		if ( isset( $json_response->result->data->plugins ) ) {
			foreach ( $json_response->result->data->plugins as $plugin ) {
				$this->download_and_install_plugin( $plugin->plugin_zip_url,
					$plugin->plugin_activate_path, $plugin->version, $plugin );

				// If the we have defined configurations for this plugin, configure it
				if ( ! empty( $plugin->config_script ) ) {
					// Passing page_id to config script
					$plugin_install_details = $this->plugin_installation_data[$plugin->plugin_activate_path];

					$post_id = ! empty( $page_id_to_post[$plugin->page_id] ) ? $page_id_to_post[$plugin->page_id] : null;

					// Configure Plugin
					if ( file_exists(
						BOLDGRID_BASE_DIR . '/includes/configure_plugin/' . $plugin->config_script ) ) {
						require_once BOLDGRID_BASE_DIR . '/includes/configure_plugin/' .
							 $plugin->config_script;
					}
				}
			}
		}

		// Do we have a blogdescription?
		/**
		 * 9-13-15 - Temp Setting this field blank.
		 */
		$updated = update_option( 'blogdescription', '' );
		/*
		 * if ( isset( $json_response->result->data->blog_description ) ) {
		 * //$updated = update_option( 'blogdescription',
		 * $json_response->result->data->blog_description );
		 * }
		 */

		// Store the pages we created:
		update_option( 'boldgrid_installed_page_ids', $this->installed_page_ids );
		update_option( 'boldgrid_installed_pages_metadata', $boldgrid_installed_pages_metadata );

		if ( isset( $to_do_list ) ) {
			update_option( 'boldgrid_todo', json_encode( $to_do_list ) );
		}
		// delete the "Sample Page"
		// @thanks
		// https://wordpress.org/support/topic/remove-default-pages-created-on-all-multisites
		$defaultPage = get_page_by_title( 'Sample Page' );
		if ( $defaultPage ) {
			wp_delete_post( $defaultPage->ID );
		}

		$defaultPage = get_page_by_title( 'Hello world!', OBJECT, 'post' );
		if ( $defaultPage ) {
			wp_delete_post( $defaultPage->ID );
		}

		// setup our menus
		//
		// This is no longer being done.
		// It is instead handled above with a call to
		// $this->assign_menu_id_to_all_locations();

		// setup our custom homepage (per theme_id and homepage)
		if ( isset( $this->theme_details->homepage ) ) {
			$this->set_custom_homepage();
		}

		$this->add_to_deploy_log( 'Finished page set deployment.' );
	}

	/**
	 * Strip uneeded markup
	 *
	 * @param DOMDOcument $dom
	 * @return string
	 */
	public function format_html_fragment( $dom ) {
		$html = preg_replace( '/^<!DOCTYPE.+?>/', '',
			str_replace( array (
				'<html>',
				'</html>',
				'<body>',
				'</body>'
			), array (
				'',
				'',
				'',
				''
			), $dom->saveHTML() ) );

		return $html;
	}

	/**
	 * Each page/post includes media.
	 * Loop through each post/page and download / setup the required media (typically imgs).
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 */
	public function deploy_page_sets_media() {
		$this->change_deploy_status( 'Downloading page/post media...' );
		$this->add_to_deploy_log( 'Checking for required media...' );

		$images_downloaded = 0;

		$allowable_atts = array (
			'alt',
			'class',
			'width',
			'height',
			'style',
			'title'
		);

		$pattern = get_shortcode_regex();

		/**
		 * // 2015.05.11 BradM // We no longer need to get ALL pages and posts.
		 * Instead, grab only the pages this deployment script as created.
		 */
		// configure posts args
		// http://codex.wordpress.org/Template_Tags/get_posts
		// http://stackoverflow.com/questions/12010497/get-posts-not-returning-all-posts

		$post_params = array (
			'posts_per_page' => - 1,
			'post__in' => $this->installed_page_ids,
			'post_type' => array (
				'page',
				'post'
			)
		);

		if ( 'publish' != $this->post_status ) {
			$post_params['post_status'] = $this->post_status;
		}

		$pages_and_posts = get_posts( $post_params );

		foreach ( $pages_and_posts as $k => $page ) {
			$dom = new DOMDocument();

			@$dom->loadHTML( Boldgrid_Inspirations_Utility::utf8_to_html( $page->post_content ) );

			$images = $dom->getElementsByTagName( 'img' );

			$changes_made = false;

			// Keep track of the order in which built_photo_search images appear on the page.
			// For further info, see docBlock for set_built_photo_search_placement()
			$remote_page_id = $this->get_remote_page_id_from_local_page_id( $page->ID );

			$built_photo_search_counter = 0;

			foreach ( $images as $image ) {
				$asset_id = $image->getAttribute( 'data-imhwpb-asset-id' );

				$built_photo_search = $image->getAttribute( 'data-imhwpb-built-photo-search' );

				$source = $image->getAttribute( 'src' );

				// If we're downloading an asset_id...
				if ( $asset_id && ! $built_photo_search ) {
					$this->add_to_deploy_log( 'Beginning to download asset' );

					$this->add_to_deploy_log( 'Beginning download_and_attach call.' );

					$url_to_downloaded_image = $this->asset_manager->download_and_attach_asset(
						$page->ID, false, $asset_id );

					// Validate return:
					if ( empty( $url_to_downloaded_image ) ) {
						// Bad return, so notify and skip this image:
						$this->add_to_deploy_log(
							'Error: Image download failed; skipping this image.' );

						continue;
					}

					$this->add_to_deploy_log( 'Finished download_and_attach call.' );

					$image->setAttribute( 'src', $url_to_downloaded_image );

					$dom->saveHTML();

					$changes_made = true;
				}

				/**
				 * If we're downloading an image from "built_photo_search"...
				 */
				if ( $built_photo_search && false == $this->is_author ) {
					// keep track of the number of bps we've requested
					$this->built_photo_search_log['count'] ++;

					// keep track of the src for this bps
					$this->built_photo_search_log['sources'][] = $built_photo_search;

					// get built_photo_search details (query_id | orientation)
					$exploded_built_photo_search = explode( '|', $built_photo_search );

					$bps_query_id = $exploded_built_photo_search[0];

					$bps_orientation = $exploded_built_photo_search[1];

					// get width details
					$exploded_source = explode( '/', $source );

					$exploded_results = explode( 'x', $exploded_source[3] );

					$width = $exploded_results[0];

					// Get the API key hash.
					$api_key_hash = $this->asset_manager->api->get_api_key_hash();

					/*
					 * Download and attach the image
					 */
					$post_params_for_get_photo_api_call = array (
						'query_id' => $bps_query_id,
						'orientation' => $bps_orientation,
						'subcategory_id' => $this->subcategory_id,
						'page_set_id' => $this->page_set_id,
						'theme_id' => $this->theme_id,
						'language_id' => $this->language_id,
						'page_id' => $remote_page_id,
						'position' => $built_photo_search_counter,
						'asset_user_id' => $this->asset_user_id,
						'key' => $api_key_hash,
						'current_build_cost' => $this->current_build_cost,
						'coin_budget' => $this->coin_budget,
						'site_hash' => $this->site_hash
					);

					$this->add_to_deploy_log( 'Beginning get_photo_data.' );

					$built_photo_search_get_photo_data = $this->built_photo_search->get_photo_data(
						$post_params_for_get_photo_api_call
					);

					$this->add_to_deploy_log( 'Finished get_photo_data.' );

					if ( false == $built_photo_search_get_photo_data ) {
						error_log(
							__METHOD__ . ': Error: $built_photo_search_get_photo_data is FALSE.  ' . print_r(
								array (
									'$built_photo_search_get_photo_data' => $built_photo_search_get_photo_data,
									'$post_params_for_get_photo_api_call' => $post_params_for_get_photo_api_call
								), true ) );

						$this->add_to_deploy_log( 'Error downloading dynamic image...' );
					} else {
						$item = array (
							'type' => 'built_photo_search',
							'params' => array (
								'image_provider_id' => $built_photo_search_get_photo_data['image_provider_id'],
								'id_from_provider' => $built_photo_search_get_photo_data['id_from_provider'],
								'imgr_image_id' => $built_photo_search_get_photo_data['imgr_image_id'],
								'width' => $width,
								'orientation' => $bps_orientation
							)
						);

						$this->add_to_deploy_log( 'Beginning download_and_attach call.' );

						$new_image_data = $this->asset_manager->download_and_attach_asset(
							$page->ID, false, $item, 'all' );

						$this->add_to_deploy_log( 'Finished download_and_attach call.' );

						if ( false == $new_image_data ) {
							error_log( __METHOD__ . ': $new_image_data is FALSE' );

							$this->add_to_deploy_log( 'Error downloading dynamic image...' );
						} else {
							// update the current price of this build thus far
							$this->current_build_cost += $new_image_data['coin_cost'];

							// Update and save the <img> tag
							$image->setAttribute( 'src', $new_image_data['uploaded_url'] );

							$image->setAttribute( 'width', $width );

							$dom->saveHTML();

							$changes_made = true;

							/*
							 * Update $this->built_photo_search_placement
							 */
							// save our asset id for this built_photo_search image
							$this->set_built_photo_search_placement( $remote_page_id,
								$built_photo_search_counter, $new_image_data['asset_id'] );

							// increment our counter
							$built_photo_search_counter ++;
						}
					}
				}
			}

			if ( $changes_made ) {
				$page->post_content = $this->format_html_fragment( $dom );
			}

			if ( preg_match_all( '/\[gallery .+?\]/i', $page->post_content, $matches ) ) {
				foreach ( $matches[0] as $index => $match ) {
					preg_match( '/data-imhwpb-assets=\'.*\'/', $match, $data_assets );

					$images = array ();
					if ( preg_match( '/data-imhwpb-assets=\'(.+)\'/i', $data_assets[0],
						$asset_images_ids ) ) {
						$images = ( explode( ',', $asset_images_ids[1] ) );
					}

					$attachment_ids = array ();

					foreach ( $images as $image_asset_id ) {
						$attachment_id = $this->asset_manager->download_and_attach_asset( $page->ID,
							false, $image_asset_id, 'attachment_id', true );

						$attachment_ids[] = $attachment_id;
					}

					$attribute_value = ' ids="' . implode( ',', $attachment_ids ) . '" ';

					$updated_match = str_ireplace( 'ids=""', $attribute_value, $match );

					$page->post_content = str_ireplace( $match, $updated_match,
						$page->post_content );

					$changes_made = true;
				}
			}

			if ( $changes_made ) {
				$this->add_to_deploy_log( 'Beginning to update post in db with new html code.' );

				wp_update_post( $page );

				$this->add_to_deploy_log( 'Finished updating post in db with new html code.' );
			}
		}
	}

	/**
	 * Deploy page sets: Media: Find placeholders.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 */
	public function deploy_page_sets_media_find_placeholders() {
		// Update deploy status and log:
		$this->change_deploy_status( 'Gathering media information...' );
		$this->add_to_deploy_log( 'Gathering media information for pages...' );

		// Get configs:
		$boldgrid_configs = $this->get_configs();

		/**
		 * ********************************************************************
		 * Get all pages that we've installed.
		 * ********************************************************************
		 */
		$post_params = array (
			'posts_per_page' => - 1,
			'post__in' => $this->installed_page_ids,
			'post_type' => array (
				'page',
				'post'
			)
		);

		if ( 'publish' != $this->post_status ) {
			$post_params['post_status'] = $this->post_status;
		}

		$pages_and_posts = get_posts( $post_params );

		// Get the API key hash.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		$this->image_placeholders_needing_images['bps_build_info'] = array (
			'subcategory_id' => $this->subcategory_id,
			'page_set_id' => $this->page_set_id,
			'theme_id' => $this->theme_id,
			'language_id' => $this->language_id,
			'asset_user_id' => $this->asset_user_id,
			'key' => $api_key_hash,
			'current_build_cost' => $this->current_build_cost,
			'coin_budget' => $this->coin_budget,
			'site_hash' => $this->site_hash
		);

		/**
		 * ********************************************************************
		 * Loop through every page.
		 * ********************************************************************
		 */
		foreach ( $pages_and_posts as $k => $page ) {
			// Get all of the images.
			$dom = new DOMDocument();
			@$dom->loadHTML( Boldgrid_Inspirations_Utility::utf8_to_html( $page->post_content ) );
			$images = $dom->getElementsByTagName( 'img' );

			// Keep track of the order in which built_photo_search images appear on the page.
			// For further info, see docBlock for set_built_photo_search_placement()
			$remote_page_id = $this->get_remote_page_id_from_local_page_id( $page->ID );

			$bps_image_position = 0;
			$asset_image_position = 0;
			$gallery_image_position = 0;

			/**
			 * ****************************************************************
			 * Loop through every image in this page.
			 * ****************************************************************
			 */
			foreach ( $images as $image ) {
				// Reset the placeholder.
				$image_placeholder = array ();

				$asset_id = $image->getAttribute( 'data-imhwpb-asset-id' );

				$built_photo_search = $image->getAttribute( 'data-imhwpb-built-photo-search' );

				$source = $image->getAttribute( 'src' );

				/**
				 * ************************************************************
				 * If we're downloading an asset_id...
				 * ************************************************************
				 */
				if ( $asset_id && ! $built_photo_search ) {
					$image_placeholder = array (
						'page_id' => $page->ID,
						'asset_id' => $asset_id,
						'asset_image_position' => $asset_image_position
					);

					$asset_image_position ++;
				}

				/**
				 * ************************************************************
				 * If we're downloading an image from "built_photo_search"...
				 * ************************************************************
				 */
				if ( $built_photo_search && false == $this->is_author ) {
					// keep track of the number of bps we've requested
					$this->built_photo_search_log['count'] ++;

					// keep track of the src for this bps
					$this->built_photo_search_log['sources'][] = $built_photo_search;

					// get built_photo_search details (query_id | orientation)
					$exploded_built_photo_search = explode( '|', $built_photo_search );

					$bps_query_id = $exploded_built_photo_search[0];

					$bps_orientation = $exploded_built_photo_search[1];

					// get width details
					$exploded_source = explode( '/', $source );

					$exploded_results = explode( 'x', $exploded_source[3] );

					$width = $exploded_results[0];

					$image_placeholder = array (
						'page_id' => $page->ID,
						'asset_id' => null,
						'bps_image_position' => $bps_image_position,
						'bps_query_id' => $bps_query_id,
						'bps_orienation' => $bps_orientation,
						'bps_width' => $width,
						'remote_page_id' => $remote_page_id
					);

					$bps_image_position ++;
				}

				if ( ! empty( $image_placeholder ) ) {
					$this->image_placeholders_needing_images['by_page_id'][$page->ID][] = $image_placeholder;
				}
			}

			/**
			 * ************************************************************
			 * Do we have a [gallery] on the page?
			 * ************************************************************
			 */
			if ( preg_match_all( '/\[gallery .+?\]/i', $page->post_content, $matches ) ) {
				foreach ( $matches[0] as $index => $match ) {
					preg_match( '/data-imhwpb-assets=\'.*\'/', $match, $data_assets );

					$images = array ();

					if ( preg_match( '/data-imhwpb-assets=\'(.+)\'/', $data_assets[0],
						$asset_images_ids ) ) {
						$images = ( explode( ',', $asset_images_ids[1] ) );
					}

					foreach ( $images as $image_asset_id ) {
						$image_placeholder = array (
							'page_id' => $page->ID,
							'asset_id' => $image_asset_id,
							'gallery_image_position' => $gallery_image_position
						);

						$gallery_image_position ++;

						$this->image_placeholders_needing_images['by_page_id'][$page->ID][] = $image_placeholder;
					}
				}
			}
		}

		/**
		 * ********************************************************************
		 * tmp testing, get all the bps data from the image server.
		 * ********************************************************************
		 */
		$params = array (
			'key' => $api_key_hash,
			'image_placeholders_needing_images' => json_encode(
				$this->image_placeholders_needing_images ),
			'coin_budget' => $this->coin_budget,
			'is_generic' => $this->is_generic,
		);

		// Get configs:
		$boldgrid_configs = $this->get_configs();

		// Set the URL address:
		$url = $boldgrid_configs['asset_server'] . $boldgrid_configs['ajax_calls']['bps-get-photos'];

		// Make a call to the asset server:
		$response = wp_remote_post( $url, array (
			'body' => $params,
			'timeout' => 60
		) );

		// Decode response into an array:
		$response_body = json_decode( $response['body'], true );

		// Validate response:
		if ( empty( $response_body['result']['data'] ) || ( isset(
			$response_body['result']['status'] ) && 200 != $response_body['result']['status'] ) ) {
			return;
		}

		// Set $response_data from the response data:
		$response_data = $response_body['result']['data'];

		// Update our ['by_page_id'] value with that of the API call return.
		$this->image_placeholders_needing_images['by_page_id'] = $response_data;

		/**
		 * ********************************************************************
		 * Do one final loop, and create the download urls.
		 * ********************************************************************
		 */
		foreach ( $this->image_placeholders_needing_images['by_page_id'] as $page_id => $images_array ) {
			// If $images_array is empty or is not an array, then skip this iteration:
			if ( empty( $images_array ) || ! is_array( $images_array ) ) {
				continue;
			}

			// Iterate through $images_array:
			foreach ( $images_array as $images_array_key => $image_data ) {
				// Are we downloading an asset?
				if ( isset( $image_data['asset_id'] ) && is_numeric( $image_data['asset_id'] ) ) {
					$download_url = $boldgrid_configs['asset_server'] .
						 $boldgrid_configs['ajax_calls']['get_asset'] . '?id=' .
						 $image_data['asset_id'] . '&key=' . $api_key_hash;

					$this->image_placeholders_needing_images['by_page_id'][$page_id][$images_array_key]['download_type'] = 'get';
					$this->image_placeholders_needing_images['by_page_id'][$page_id][$images_array_key]['download_url'] = $download_url;
				}

				// Are we downloading a bps?
				if ( isset( $image_data['bps_query_id'] ) ) {
					$download_url = $boldgrid_configs['asset_server'] .
						 $boldgrid_configs['ajax_calls']['image_download'];

					/* @formatter:off */
					$download_params = array(
						'key' => $api_key_hash,
						'id_from_provider' =>			isset( $image_data['getPhotoAction']['id_from_provider'] ) 			? $image_data['getPhotoAction']['id_from_provider']  : null,
						'image_provider_id' =>			isset( $image_data['getPhotoAction']['image_provider_id'] ) 		? $image_data['getPhotoAction']['image_provider_id'] : null,
						'imgr_image_id' =>				isset( $image_data['getPhotoAction']['imgr_image_id'] ) 			? $image_data['getPhotoAction']['imgr_image_id']	 : null,
						'width' =>						isset( $image_data['bps_width'] ) 									? $image_data['bps_width']							 : null,
						'orientation' =>				isset( $image_data['bps_orientation'] ) 							? $image_data['bps_orientation']					 : null,
						'image_size' =>					isset( $item['params']['image_size'] ) 								? $image_data['params']['image_size']				 : null,
						'is_redownload' =>				isset( $item['params']['is_redownload'] ) 							? $image_data['params']['is_redownload']			 : false,
						'user_transaction_item_id' =>	isset( $item['params']['user_transaction_item_id'] ) 				? $image_data['params']['user_transaction_item_id']	 : null,
						'boldgrid_connect_key' =>		isset( $item['params']['boldgrid_connect_key'] ) 					? $image_data['params']['boldgrid_connect_key']		 : null,
					);
					/* @formatter:on */

					$this->image_placeholders_needing_images['by_page_id'][$page_id][$images_array_key]['download_type'] = 'post';
					$this->image_placeholders_needing_images['by_page_id'][$page_id][$images_array_key]['download_url'] = $download_url;
					$this->image_placeholders_needing_images['by_page_id'][$page_id][$images_array_key]['download_params'] = $download_params;
				}
			}
		}

		// Update the deploy log:
		$this->add_to_deploy_log( 'Finished gathering media information for pages.' );
	}

	/**
	 * Deploy page sets: Media: Process image queue
	 */
	public function deploy_page_sets_media_process_image_queue() {
		// Update deploy status and log:
		$this->change_deploy_status( 'Downloading media...' );
		$this->add_to_deploy_log( 'Downloading media for pages...' );

		// Validate $this->image_placeholders_needing_images['by_page_id']:
		if ( empty( $this->image_placeholders_needing_images['by_page_id'] ) ) {
			// Update the deploy log:
			$this->add_to_deploy_log( 'No media to download for pages.' );

			return;
		}

		// Create our image queue.
		foreach ( $this->image_placeholders_needing_images['by_page_id'] as $page_id => $images_array ) {
			// If $images_array is empty or is not an array, then skip this iteration:
			if ( empty( $images_array ) || ! is_array( $images_array ) ) {
				continue;
			}

			foreach ( $images_array as $images_array_key => $image_data ) {
				$image_queue[] = array (
					'download_type' => isset( $image_data['download_type'] ) ? $image_data['download_type'] : null,
					'download_url' => isset( $image_data['download_url'] ) ? $image_data['download_url'] : null,
					'download_params' => isset( $image_data['download_params'] ) ? $image_data['download_params'] : null,
					'post_id' => isset( $image_data['page_id'] ) ? $image_data['page_id'] : null,
					'images_array_key' => $images_array_key
				);
			}
		}

		// Using curl_multi_:
		$mh = curl_multi_init();

		global $wp_version;

		$user_agent = 'WordPress/' . $wp_version . '; ' . get_site_url();

		// If $image_queue is empty, then return:
		if ( empty( $image_queue ) ) {
			return;
		}

		foreach ( $image_queue as $image_key => $image_data ) {
			// If image caching is enabled, then check cache.
			if ( null !== $this->asset_cache ) {
				// Create an array to be used to set a cache id.
				if ( isset( $image_data['bps_query_id'] ) ) {
					$cache_array = array (
						'id_from_provider' => $image_data['download_params']['id_from_provider'],
						'image_provider_id' => $image_data['download_params']['image_provider_id'],
						'imgr_image_id' => $image_data['download_params']['imgr_image_id'],
						'width' => $image_data['download_params']['width'],
						'orientation' => $image_data['download_params']['orientation'],
						'image_size' => $image_data['download_params']['image_size']
					);
				} else {
					$cache_array = $image_data;
				}

				// Set the cache id.
				$image_queue[$image_key]['cache_id'] = $this->asset_cache->set_cache_id(
					$cache_array );

				// Try to get the $response from cache.
				if ( ! empty( $image_queue[$image_key]['cache_id'] ) ) {
					$response[$image_key] = $this->asset_cache->get_cache_files(
						$image_queue[$image_key]['cache_id'] );
				}
			}

			// If there was no cached response, then queue the download.
			if ( empty( $response[$image_key] ) ) {
				// Using curl_multi_:
				${'ch' . $image_key} = curl_init();

				curl_setopt( ${'ch' . $image_key}, CURLOPT_URL, $image_data['download_url'] );
				curl_setopt( ${'ch' . $image_key}, CURLOPT_HEADER, true );
				curl_setopt( ${'ch' . $image_key}, CURLOPT_RETURNTRANSFER, true );
				curl_setopt( ${'ch' . $image_key}, CURLOPT_USERAGENT, $user_agent );

				if ( 'post' == $image_data['download_type'] &&
					 ! empty( $image_data['download_params'] ) ) {
					curl_setopt( ${'ch' . $image_key}, CURLOPT_POST, true );
					curl_setopt( ${'ch' . $image_key}, CURLOPT_POSTFIELDS,
						http_build_query( $image_data['download_params'] ) );
				}

				curl_multi_add_handle( $mh, ${'ch' . $image_key} );

				$image_queue[$image_key]['cached'] = false;
			} else {
				$image_queue[$image_key]['cached'] = true;
			}
		}

		// If any curl handle was added to the $mh handle, then get data from curl_multi_.
		if ( isset( $mh ) ) {
			// Using curl_multi_.
			$still_running = null;

			do {
				$mrc = curl_multi_exec( $mh, $still_running );
			} while ( $still_running > 0 );

			while ( $still_running && CURLM_OK == $mrc ) {
				if ( curl_multi_select( $mh ) != - 1 ) {
					do {
						$mrc = curl_multi_exec( $mh, $still_running );
					} while ( $still_running > 0 );
				}
			}

			foreach ( $image_queue as $image_key => $image_data ) {
				if ( empty( $response[$image_key] ) ) {
					$response[$image_key] = curl_multi_getcontent( ${'ch' . $image_key} );

					curl_multi_remove_handle( $mh, ${'ch' . $image_key} );
				}
			}
		}

		// Check responses.
		foreach ( $image_queue as $image_key => $image_data ) {
			if ( isset( $response[$image_key]['headers']['z-filename'] ) ) {
				$arrayify = $response[$image_key];
			} else {
				$arrayify = $this->curl_response_arrayify( $response[$image_key] );
			}

			// If we did not receive a filename in the headers, then log and skip.
			if ( empty( $arrayify['headers']['z-filename'] ) ) {
				// If body is binary, then remove it.
				if ( ! empty( $arrayify['body'] ) &&
					 Boldgrid_Inspirations_Utility::is_binary( $arrayify['body'] ) ) {
					$arrayify['body'] = '(removed)';
				}

				// Log:
				error_log(
					__METHOD__ . ': Image download response header is missing z-filename. ' . print_r(
						array (
							'$image_key' => $image_key,
							'$image_data' => $image_data,
							'$arrayify' => $arrayify
						), true ) );

				// Skip this iteration in the loop:
				continue;
			} else {
				// If image caching is enabled, a cache id exists, and was not retrieved from cache,
				// then save to cache.
				if ( null !== $this->asset_cache && ! empty( $image_data['cache_id'] ) &&
					 ! $image_data['cached'] ) {
					$this->asset_cache->save_cache_files( $image_data['cache_id'], $arrayify );
				}
			}

			$attachment_data = $this->asset_manager->attach_asset(
				array (
					'headers' => $arrayify['headers'],
					'body' => $arrayify['body'],
					'post_id' => $image_data['post_id'],
					'featured_image' => false,
					'return' => 'all',
					'add_meta_data' => ( isset(
						$this->image_placeholders_needing_images['by_page_id'][$image_data['post_id']][$image_data['images_array_key']]['gallery_image_position'] ) )
				) );

			// Update our data...
			$this->image_placeholders_needing_images['by_page_id'][$image_data['post_id']][$image_data['images_array_key']]['attachment_url'] = $attachment_data['uploaded_url'];
			$this->image_placeholders_needing_images['by_page_id'][$image_data['post_id']][$image_data['images_array_key']]['attachment_id'] = $attachment_data['attachment_id'];
			$this->image_placeholders_needing_images['by_page_id'][$image_data['post_id']][$image_data['images_array_key']]['asset_id'] = $attachment_data['asset_id'];

			// Update the cost of this build.
			if ( isset( $attachment_data['coin_cost'] ) ) {
				$this->current_build_cost += $attachment_data['coin_cost'];
			}
		}

		// Update the deploy log:
		$this->add_to_deploy_log( 'Finished downloading media for pages.' );
	}

	/**
	 * Deploy page sets: Media: Replace placeholders
	 */
	public function deploy_page_sets_media_replace_placeholders() {
		// Update deploy status and log:
		$this->change_deploy_status( 'Replacing media in pages...' );
		$this->add_to_deploy_log( 'Replacing media in pages...' );

		$images_downloaded = 0;

		$post_params = array (
			'posts_per_page' => - 1,
			'post__in' => $this->installed_page_ids,
			'post_type' => array (
				'page',
				'post'
			)
		);

		if ( 'publish' != $this->post_status ) {
			$post_params['post_status'] = $this->post_status;
		}

		$pages_and_posts = get_posts( $post_params );

		foreach ( $pages_and_posts as $k => $page ) {
			$dom = new DOMDocument();

			@$dom->loadHTML( Boldgrid_Inspirations_Utility::utf8_to_html( $page->post_content ) );

			$images = $dom->getElementsByTagName( 'img' );

			$changes_made = false;

			// Keep track of the order in which built_photo_search images appear on the page.
			// For further info, see docBlock for set_built_photo_search_placement()
			$remote_page_id = $this->get_remote_page_id_from_local_page_id( $page->ID );

			$built_photo_search_counter = 0;

			$bps_image_position = 0;
			$asset_image_position = 0;

			foreach ( $images as $image ) {
				// Check if we have the information we need, or skip this iteration:
				if ( empty(
					$this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['attachment_url'] ) ||
					 empty(
						$this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['asset_id'] ) ) {
					continue;
				}

				$asset_id = $image->getAttribute( 'data-imhwpb-asset-id' );

				$built_photo_search = $image->getAttribute( 'data-imhwpb-built-photo-search' );

				$source = $image->getAttribute( 'src' );

				// If we're downloading an asset_id...
				if ( $asset_id && ! $built_photo_search ) {
					$attachment_url = $this->image_placeholders_needing_images['by_page_id'][$page->ID][$asset_image_position]['attachment_url'];

					$attachment_id = ( isset(
						$this->image_placeholders_needing_images['by_page_id'][$page->ID][$asset_image_position]['attachment_id'] ) ? ( int ) $this->image_placeholders_needing_images['by_page_id'][$page->ID][$asset_image_position]['attachment_id'] : null );

					$image->setAttribute( 'src', $attachment_url );

					// Add wp-image-## to the image's class.
					// This class is required if WordPress is to later add the srcset attribute.
					if ( ! empty( $attachment_id ) ) {
						$new_image_class = $this->dom_element_append_attribute( $image, 'class',
							'wp-image-' . $attachment_id );
						$image->setAttribute( 'class', $new_image_class );
					}

					$dom->saveHTML();

					$changes_made = true;

					$asset_image_position ++;
				}

				// If we're downloading an image from "built_photo_search"...
				if ( $built_photo_search && false == $this->is_author ) {
					// keep track of the number of bps we've requested
					$this->built_photo_search_log['count'] ++;

					// keep track of the src for this bps
					$this->built_photo_search_log['sources'][] = $built_photo_search;

					$attachment_url = $this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['attachment_url'];
					$attachment_width = $this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['bps_width'];
					$asset_id = $this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['asset_id'];

					$attachment_id = ( isset(
						$this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['attachment_id'] ) ? ( int ) $this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['attachment_id'] : null );

					// Update and save the <img> tag
					$image->setAttribute( 'src', $attachment_url );
					$image->setAttribute( 'width', $attachment_width );

					if ( $this->is_preview_server ) {
						$image->setAttribute( 'data-id-from-provider',
							$this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['download_params']['id_from_provider'] );
						$image->setAttribute( 'data-image-provider-id',
							$this->image_placeholders_needing_images['by_page_id'][$page->ID][$bps_image_position]['download_params']['image_provider_id'] );
					}

					// Add wp-image-## to the image's class.
					// This class is required if WordPress is to later add the srcset attribute.
					if ( ! empty( $attachment_id ) ) {
						$new_image_class = $this->dom_element_append_attribute( $image, 'class',
							'wp-image-' . $attachment_id );
						$image->setAttribute( 'class', $new_image_class );
					}

					$dom->saveHTML();

					$changes_made = true;

					$bps_image_position ++;

					/*
					 * Update $this->built_photo_search_placement
					 */
					// save our asset id for this built_photo_search image
					$this->set_built_photo_search_placement( $remote_page_id,
						$built_photo_search_counter, $asset_id );

					// increment our counter
					$built_photo_search_counter ++;
				}

				if ( $changes_made ) {
					$page->post_content = $this->format_html_fragment( $dom );
				}
			} // End of foreach images

			// Get asset ids for gallery images and swap data with the attachment ids in the
			  // shortcode:
			if ( preg_match_all( '/\[gallery .+?\]/i', $page->post_content, $matches ) ) {
				foreach ( $matches[0] as $index => $match ) {
					preg_match( '/data-imhwpb-assets=\'.*\'/', $match, $data_assets );

					$images = array ();

					if ( preg_match( '/data-imhwpb-assets=\'(.+)\'/i', $data_assets[0],
						$asset_images_ids ) ) {
						$images = ( explode( ',', $asset_images_ids[1] ) );
					}

					$attachment_ids = array ();

					foreach ( $images as $image_asset_id ) {
						foreach ( $this->image_placeholders_needing_images['by_page_id'][$page->ID] as $asset_index => $asset_info ) {
							if ( $asset_info['asset_id'] == $image_asset_id ) {
								$attachment_id = $asset_info['attachment_id'];
								break;
							}
						}

						$attachment_ids[$image_asset_id] = $attachment_id;
					}

					$attribute_value = ' ids="' . implode( ',', $attachment_ids ) . '" ';

					$updated_match = str_ireplace( 'ids=""', $attribute_value, $match );

					$page->post_content = str_ireplace( $match, $updated_match,
						$page->post_content );

					$changes_made = true;
				}
			}

			if ( $changes_made ) {
				$this->add_to_deploy_log( 'Beginning to update post in db with new html code.' );

				wp_update_post( $page );

				$this->add_to_deploy_log( 'Finished updating post in db with new html code.' );
			}
		} // End of foreach pages_and_posts

		// Update the deploy log:
		$this->add_to_deploy_log( 'Finished replacing media in pages.' );
	}

	/**
	 * Primary Design Elements (pde) vary based upon theme group.
	 * This function will download and setup the appropriate pde's.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 */
	public function deploy_pde( $params = array() ) {
		// Update deploy status and log:
		$this->change_deploy_status( 'Setting up primary design elements...' );
		$this->add_to_deploy_log( 'Checking <em>Primary Design Elements</em>...' );

		$defaults = array (
			'update_current_themes_mods' => true
		);

		$params = wp_parse_args( $params, $defaults );

		if ( is_array( $this->pde ) ) {
			$this->add_to_deploy_log( 'Yes, we have a pde value.' );

			foreach ( $this->pde as $pde ) {

				if ( 'header_image' == $pde['pde_type_name'] ||
					 'background_image' == $pde['pde_type_name'] ) {
					/**
					 * ********************************************************
					 * Step 1: Get the asset id of the pde
					 * ********************************************************
					 */
					// get curated item object
					$boldgrid_configs = $this->get_configs();

					$get_curated_url = $boldgrid_configs['asset_server'] .
						 $boldgrid_configs['ajax_calls']['get_curated'];

					$arguments = array (
						'method' => 'POST',
						'body' => array (
							'curated_id' => $pde['pde_curated_id']
						)
					);

					// Get the API key hash.
					$api_key_hash = $this->asset_manager->api->get_api_key_hash();

					if ( ! empty( $api_key_hash ) ) {
						$arguments['body']['key'] = $api_key_hash;
					}

					$response = wp_remote_post( $get_curated_url, $arguments );

					if ( $response instanceof WP_Error ) {
						throw new Exception( 'Error downloading asset.' );
					}

					$data = json_decode( $response['body'] );

					$asset_id = $data->result->data->asset_id;

					/**
					 * ********************************************************
					 * Step 2: Download and attach this asset_id
					 * ********************************************************
					 */
					// Set the last argument to true in order to 'add_meta_data'.
					// This is because the attribution class looks for thumbnails.
					$pde_url = $this->asset_manager->download_and_attach_asset( false, false,
						$asset_id, 'url', true );

					/**
					 * ********************************************************
					 * Step 3: Set the theme mod
					 * ********************************************************
					 */
					if ( $params['update_current_themes_mods'] ) {
						set_theme_mod( 'default_' . $pde['pde_type_name'], $pde_url );
					}

					/*
					 * There may be times we don't want to update the theme mods for the current
					 * theme. If we're using Inspiration's "install new themes", let's save this
					 * theme mod to the new theme's theme_mods, which will become activated once
					 * the user enables the new theme. If we actually DID set the theme mod (done
					 * above), it would affect the user's current live site (IE change their
					 * background / header image).
					 */
					if ( ! $params['update_current_themes_mods'] &&
						 isset( $params['stylesheet'] ) ) {
						$staging_prefix = $this->is_staging_install() ? 'boldgrid_staging_' : '';

						// Create the name of the option we'll be working with.
						$option_name = $staging_prefix . 'theme_mods_' . $params['stylesheet'];

						// If this theme already has theme_mods, get them.
						$theme_mods_for_new_theme = get_option( $option_name );

						// Set the theme mod.
						$theme_mods_for_new_theme[$pde['pde_type_name']] = $pde_url;

						// Save the theme mod. It will take effect if/when the user enables this
						// theme.
						update_option( $option_name, $theme_mods_for_new_theme );
					}
				}
			}
		}

		// Update the deploy log:
		$this->add_to_deploy_log( 'Primary Design Elements configuration complete.' );
	}

	/**
	 * This function exists and does nothing.
	 *
	 * This is because we're using get_shortcode_regex and we need "imhwpb"
	 * added to the regex list.
	 * In order to do this, we need to use add_shortcode, which requires a
	 * function.
	 *
	 * http://codex.wordpress.org/Function_Reference/add_shortcode
	 * http://codex.wordpress.org/Function_Reference/get_shortcode_regex
	 */
	public function dummy_shortcode_imhwpb() {
	}

	/**
	 * These two functions (change_deploy_status / add_to_deploy_log)
	 * are used to print / update logs on the user's screen of the installation process.
	 */
	public function change_deploy_status( $status ) {
		// The preview server does not need to print this information.
		if ( ! $this->is_preview_server ) {
			ob_start();

			$oneliner = 'jQuery("#deploy_text").html("' . htmlentities( $status, ENT_QUOTES ) . '");';
			Boldgrid_Inspirations_Utility::inline_js_oneliner( $oneliner );

			if ( false != ob_get_length() ) {
				ob_flush();
				flush();
			}

			ob_end_clean();
		}
	}

	/**
	 * See description for change_deploy_status()
	 *
	 * @param unknown $status
	 * @param bool $log_the_time
	 */
	public function add_to_deploy_log( $status, $log_the_time = true ) {
		$status_no_tags = strip_tags( $status );

		if ( true == $log_the_time ) {
			// calculate the process time
			$process_time = round( microtime( true ) - $this->timer_start, 2 );
			if ( 0 == $process_time ) {
				$process_time = '0.00';
			}

			// reset the counter
			$this->timer_start = microtime( true );

			// append to our log
			$this->full_deploy_log['procedural'][] = '\t' . $process_time . '\t' . $status_no_tags;

			// add to total time
			if ( ! isset( $this->full_deploy_log['per task'][$status_no_tags]['total'] ) ) {
				$this->full_deploy_log['per task'][$status_no_tags]['total'] = 0;
			}

			if ( ! isset( $this->full_deploy_log['per task'][$status_no_tags]['count'] ) ) {
				$this->full_deploy_log['per task'][$status_no_tags]['count'] = 0;
			}

			$this->full_deploy_log['per task'][$status_no_tags]['total'] += $process_time;

			$this->full_deploy_log['per task'][$status_no_tags]['count'] ++;
		} else {
			$this->full_deploy_log['procedural'][] = '\t    \t\t' . strip_tags( $status );
		}

		/**
		 * Print the javascript that adds lines to the deployment log.
		 */
		if ( ! $this->is_preview_server ) {
			ob_start();

			$oneliner = 'jQuery("#deploy_log").append("<li>' . $status . '</li>");';
			Boldgrid_Inspirations_Utility::inline_js_oneliner( $oneliner );
			Boldgrid_Inspirations_Utility::inline_js_oneliner( 'update_deploy_log_line_count();' );

			if ( false != ob_get_length() ) {
				ob_flush();
				flush();
			}

			ob_end_clean();
		}
	}

	/**
	 * Allow downloads over the backlan.
	 *
	 * WordPress blocks 10.x.x.x connections.
	 *
	 * @thanks http://www.emanueletessore.com/wordpress-download-failed-valid-url-provided/
	 */
	public function allow_downloads_over_the_backlan( $allow, $host, $url ) {
		$boldgrid_configs = $this->get_configs();

		if ( $host == str_replace( 'https://', '', $boldgrid_configs['asset_server'] ) ) {
			$allow = true;
		}

		return $allow;
	}

	/**
	 * Fail deployment
	 *
	 * @param string $message
	 */
	public function fail_deployment( $message ) {
		?>
<hr />
<h1>Deployment failed.</h1>
<p>We're sorry but unfortunately the site deployment failed with the
	following message:</p>
<p>
	<em><?php echo $message; ?></em>
</p>
<?php

		// LOG:
		error_log(
			__METHOD__ . ': Error: ' . print_r( array (
				'$message' => $message
			), true ) );
	}

	/**
	 * Send to home
	 */
	public function redirect_to_admin( $action = '' ) {
		$url = get_admin_url() . $action;

		$oneliner = 'window.location.href = "' . $url . '";';
		Boldgrid_Inspirations_Utility::inline_js_oneliner( $oneliner );
	}

	/**
	 * This function handles things to do after the deployment is done.
	 */
	public function finish_deployment() {
		// This may not be our first deployment. If we have prior kitchen sink data, remove it.
		delete_transient( 'boldgrid_inspirations_kitchen_sink' );

		$install_time = time() - $this->start_time;

		$this->change_deploy_status( 'Installation complete!' );

		$this->add_to_deploy_log( 'Installed in ' . $install_time . ' seconds.' );

		/**
		 * Configure $this->deploy_results, the data to be returned to the asset server.
		 */
		// installation time
		$this->deploy_results['install_time_in_seconds'] = $install_time;

		// built photo search usage
		$this->deploy_results['built_photo_search_placement'] = isset(
			$this->built_photo_search_placement ) ? $this->built_photo_search_placement : null;

		// built photo search log
		$this->deploy_results['built_photo_search_log'] = $this->built_photo_search_log;

		$path = $this->new_path;

		update_option( 'boldgrid_has_built_site', 'yes' );
		update_option( 'boldgrid_show_tip_start_editing', 'yes' );

		if ( $this->create_preview_site ) {
			update_option( 'boldgrid_built_as_preview_site', 'yes' );
		}

		$this->add_to_deploy_log( get_site_url() );

		/**
		 * ********************************************************************
		 * Grab the total coin cost to purchase for publish
		 * ********************************************************************
		 */
		require_once BOLDGRID_BASE_DIR .
			 '/includes/class-boldgrid-inspirations-purchase-for-publish.php';
		$purchase_for_publish = new Boldgrid_Inspirations_Purchase_For_Publish(
			array (
				'configDir' => BOLDGRID_BASE_DIR . '/includes/config'
			) );

		$this->deploy_results['total_cost_to_purchase_for_publish'] = $purchase_for_publish->get_total_cost_to_purchase_for_publishing();

		/**
		 * ********************************************************************
		 * If we're showing the full log...
		 * ********************************************************************
		 */
		if ( true == $this->show_full_log ) {
			// create % of time data
			foreach ( $this->full_deploy_log['per task'] as $task => $task_data ) {
				$percentage_of_deployment = round( $task_data['total'] / $install_time * 100, 2 );

				$this->full_deploy_log['per task'][$task]['percentage_of_deployment'] = $percentage_of_deployment;

				$this->full_deploy_log['percentage of deployment (' . $install_time . ' seconds)'][$task] = $percentage_of_deployment;

				$this->full_deploy_log['seconds of deployment (' . $install_time . ' seconds)'][$task] = $task_data['total'];
			}
			arsort(
				$this->full_deploy_log['percentage of deployment (' . $install_time . ' seconds)'] );

			arsort(
				$this->full_deploy_log['seconds of deployment (' . $install_time . ' seconds)'] );
		}

		/**
		 * ********************************************************************
		 * We inteded for the preview server to return a json string.
		 * This was not possible however because WordPress has data echoing that cannot be canceled.
		 *
		 * For example, WordPress prints a "status log" as it's installing a theme / plugin.
		 * This printing cannot be disabled.
		 *
		 * To work around this, We are surrounding our json data with "[RETURN_ARRAY]"
		 * We can use explode to then get the data we need.
		 * ********************************************************************
		 */
		if ( $this->is_preview_server ) {
			echo '[RETURN_ARRAY]' . json_encode( $this->deploy_results ) . '[RETURN_ARRAY]';
		}

		/**
		 * ********************************************************************
		 * Display the "stop and explain page
		 * ********************************************************************
		 */
		include BOLDGRID_BASE_DIR . '/pages/deploy_stop_and_explain.php';

		// After deployment, we'll want to update the coin cost in the top right of the page.
		Boldgrid_Inspirations_Utility::inline_js_oneliner(
			'boldgrid_deploy_cost = ' . $this->current_build_cost . ';' );
	}

	/**
	 * This plugin requests a list of sitewide plugins to be installed, and then installs them.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 */
	public function install_sitewide_plugins() {
		$this->change_deploy_status( 'Installation sitewide plugins...' );

		$this->add_to_deploy_log( 'Requesting list of any sitewide plugins...' );

		$boldgrid_configs = $this->get_configs();

		$get_plugins_url = $boldgrid_configs['asset_server'] .
			 $boldgrid_configs['ajax_calls']['get_plugins'];

		// Determine the release channel:
		$options = get_option( 'boldgrid_settings' );

		$release_channel = isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable';

		// Build API call arguments:
		$arguments = array (
			'method' => 'POST',
			'body' => array(
				'channel' => $release_channel
			)
		);

		// Get the API key hash.
		$api_key_hash = $this->asset_manager->api->get_api_key_hash();

		if ( ! empty( $api_key_hash ) ) {
			$arguments['body']['key'] = $api_key_hash;
		}

		$response = wp_remote_post( $get_plugins_url, $arguments );

		if ( $response instanceof WP_Error ) {
			throw new Exception( 'Error downloading plugin list.' );
		}

		$plugin_list = json_decode( $response['body'] );

		$plugin_list = isset( $plugin_list->result->data ) ? $plugin_list->result->data : array ();

		if ( count( $plugin_list ) ) {
			foreach ( $plugin_list as $plugin_list_k => $plugin_list_v ) {
				$this->download_and_install_plugin( $plugin_list_v->plugin_zip_url,
					$plugin_list_v->plugin_activate_path, $plugin_list_v->version, $plugin_list_v );
			}
		} else {
			$this->add_to_deploy_log( 'No plugins found to install.' );
		}
	}

	/**
	 * Determine if this install is for a staging site
	 *
	 * @return bool
	 */
	public function is_staging_install() {
		return ( isset( $_POST['staging'] ) && 1 == $_POST['staging'] );
	}

	/**
	 * If we activated any existing plugins on behalf of the user, print this notices
	 */
	public function get_plugin_activation_notices() {
		$plugin_titles = array ();

		$notices = '';

		foreach ( $this->plugin_installation_data as $plugin ) {
			if ( ! empty( $plugin['forked_plugin_activated'] ) &&
				 ! empty( $plugin['full_data']->plugin_title ) ) {
				$plugin_titles[] = $plugin['full_data']->plugin_title;
			}
		}

		if ( count( $plugin_titles ) ) {
			$notices = '<div class="updated auto-updated-plugins"><p>The following existing' .
				 ' plugins where activated for use on your new BoldGrid site:</p><ul>';

			foreach ( $plugin_titles as $plugin_title ) {
				$notices .= "<li>{$plugin_title}</li>";
			}

			$notices .= '</ul></div>';
		}

		return $notices;
	}

	/**
	 * Download and activate a plugin.
	 *
	 * @see Boldgrid_Inspirations_Api::get_api_key_hash().
	 *
	 * @param string $url A URL such as "https://downloads.wordpress.org/plugin/quick-cache.140829.zip".
	 * @param string $activate_path A plugin path such as "quick-cache/quick-cache.php".
	 * @param string $version Version number.
	 * @param object $full_plugin_data Plugin details.
	 */
	public function download_and_install_plugin( $url, $activate_path, $version, $full_plugin_data ) {
		$boldgrid_configs = $this->get_configs();

		// If ASSET_SERVER in plugin url name, then replace it from configs.
		if ( false !== strpos( $url, 'ASSET_SERVER' ) ) {
			// Replace ASSET_SERVER with the asset server name
			$url = str_replace( 'ASSET_SERVER', $boldgrid_configs['asset_server'], $url );

			// Get the API key hash.
			$api_key_hash = $this->asset_manager->api->get_api_key_hash();

			// Attach the api key:
			if ( ! empty( $api_key_hash ) ) {
				$url .= '&key=' . $api_key_hash;
			}
		}

		$this->add_to_deploy_log(
			'Installing plugin: ' . $activate_path . ' version ' . $version . '.' );

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		/**
		 * Check if the version we are trying to install.
		 */
		$plugin_version_already_exists = false;

		$plugin_path = ABSPATH . 'wp-content/plugins/';

		$absolute_activation_path = $plugin_path . $activate_path;

		if ( file_exists( $absolute_activation_path ) ) {
			// Get the installed plugin data:
			$plugin_data = get_plugin_data( $absolute_activation_path );

			// Compare versions:
			$comparison = version_compare( $plugin_data['Version'], $version );

			// Set flag and add to deploy log:
			switch ( $comparison ) {
				case - 1 :
					// Older version installed.
					$this->add_to_deploy_log(
						'An older version (' . $plugin_data['Version'] .
							 ') of the plugin is installed.  Update to version ' . $version .
							 ' using WordPress Updates.' );

					break;

				case 0 :
					// Current version installed.
					$plugin_version_already_exists = true;

					$this->add_to_deploy_log(
						'Plugin version ' . $plugin_data['Version'] . ' is already installed.' );

					break;

				case 1 :
					// Newer version installed.
					$plugin_version_already_exists = true;

					$this->add_to_deploy_log(
						'A newer version (' . $plugin_data['Version'] .
							 ') of the plugin is already installed.' );

					break;
			}
		}

		// If the user already has the parent plugin skip this installation, init settings:
		$this->plugin_installation_data[$activate_path] = array (
			'forked_plugin_exists' => false,
			'forked_plugin_active' => false,
			'forked_plugin_activated' => false,
			'full_data' => $full_plugin_data
		);

		// Do not install plugins if the forked plugin exists:
		$original_active_path = $activate_path; // <-- overwriting if activating forked plugin

		// If the plugin needs to be installed, first check for a forked version:
		if ( ! $plugin_version_already_exists &&
			 ! empty( $full_plugin_data->forked_plugin_path ) ) {
			if ( file_exists( $plugin_path . $full_plugin_data->forked_plugin_path ) ) {
				// Check if forked plugin active:
				$forked_plugin_active = $this->external_plugin->is_active(
					$full_plugin_data->forked_plugin_path );

				// Update settings:
				$this->plugin_installation_data[$activate_path] = array (
					'forked_plugin_active' => $forked_plugin_active,
					'forked_plugin_exists' => true
				);

				$activate_path = $full_plugin_data->forked_plugin_path;
				$plugin_version_already_exists = true;

				// Add to deploy log:
				$this->add_to_deploy_log(
					'A fork of this plugin was found; skipping installation.' );
			}
		}

		// If the plugin still needs to be installed, then do it:
		if ( ! $plugin_version_already_exists ) {
			// Install the plugin:
			$upgrader = new Plugin_Upgrader(
				new Plugin_Installer_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

			$upgrader->install( $url );

			// Check result:
			if ( is_object( $upgrader->skin->result ) &&
				 ( is_wp_error( $upgrader->skin->result ) || false == $upgrader->skin->result ) ) {
				$error_message = $upgrader->skin->result->get_error_message();

				if ( 'Destination folder already exists.' == $error_message ) {
					$this->add_to_deploy_log( 'Plugin files already exist.' );
				}
			} else {
				$this->add_to_deploy_log( 'Plugin installation complete.' );
			}
		}

		// Activate the plugin:
		$this->add_to_deploy_log( 'Activating plugin...' );

		$result = activate_plugin( $activate_path );

		// Check for activation error:
		if ( is_wp_error( $result ) ) {
			// LOG:
			error_log(
				__METHOD__ . ': Error: Plugin activation failed! ' . print_r(
					array (
						'$activate_path' => $activate_path,
						'$result' => $result
					), true ) );
		} elseif ( $this->plugin_installation_data[$original_active_path]['forked_plugin_exists'] &&
			 false == $forked_plugin_active ) {
			/*
			 * In the case that the activation of this plugin was a success and the plugin was a
			 * fork, set "forked_plugin_activated" so that we can display a message to the user
			 */
			$this->plugin_installation_data[$original_active_path]['forked_plugin_activated'] = true;
		} else {
			$this->add_to_deploy_log( 'Plugin activation complete.' );
		}
	}

	/**
	 * Include grid system css.
	 *
	 * @return null
	 */
	public function add_grid_system() {
		// Before we can include the grid system, we need to allow css files to be uploaded.
		// @thanks http://www.paulund.co.uk/change-wordpress-upload-mime-types
		add_filter( 'upload_mimes', 'boldgrid_add_custom_mime_types' );

		/**
		 *
		 * @param array $mimes An associative array of mime types
		 *
		 * @return array Merged associative array of mime types.
		 */
		function boldgrid_add_custom_mime_types( $mimes ) {
			return array_merge( $mimes, array (
				'css' => 'text/css'
			) );
		}
	}

	/**
	 * Keep track of the order in which built_photo_search images appear on the page.
	 *
	 * Ultimately, we want to know for this images we've dynamically loaded:
	 *
	 * [built_photo_search_placement] => stdClass Object (
	 * [45] => Array
	 * . (
	 * . . [0] => 9558
	 * . . [1] => 10658
	 * . )
	 *
	 * This equates to:
	 *
	 * [built_photo_search_placement] => stdClass Object (
	 * [remote_page_id] => Array
	 * . (
	 * . . [counter] => asset_id
	 * . . [counter] => asset_id
	 * . )
	 */
	public function set_built_photo_search_placement( $remote_page_id, $counter, $asset_id ) {
		$this->built_photo_search_placement[$remote_page_id][$counter] = $asset_id;
	}

	/**
	 * Set custom homepage
	 */
	public function set_custom_homepage() {
		$homepage_var = '';

		foreach ( $this->theme_details->homepage as $homepage_step_key => $homepage_step_obj ) {
			switch ( $homepage_step_obj->action ) {
				case 'page' :

					$page_type = $homepage_step_obj->page->post_type;

					// only create the page if it doesn't already exist
					$existing_page = get_page_by_title( $homepage_step_obj->page->page_title,
						OBJECT, $page_type );

					if ( null === $existing_page ) {
						// insert the page
						$post['post_content'] = $homepage_step_obj->page->code;
						$post['post_name'] = $homepage_step_obj->page->page_slug;
						$post['post_title'] = $homepage_step_obj->page->page_title;
						$post['post_status'] = 'publish';
						$post['post_type'] = $page_type;
						$this->add_to_deploy_log( '' );
						$post_id = wp_insert_post( $post );
					} else
						$post_id = $existing_page->ID;

						// do we have any featured images?
					if ( 0 != $homepage_step_obj->page->featured_image_asset_id ) {
						$this->asset_manager->download_and_attach_asset( $post_id, true,
							$homepage_step_obj->page->featured_image_asset_id );
					}

					if ( ! empty( $homepage_step_obj->return_value_save_as ) ) {
						switch ( $homepage_step_obj->return_value_save_as ) {
							case 'array_item' :
								$homepage_var[$homepage_step_obj->return_value_save_to][] = $post_id;

								break;

							case 'option' :
								update_option( $homepage_step_obj->return_value_save_to, $post_id );

								break;

							default :
								// if there is a ':' after 'option'
								if ( substr( $homepage_step_obj->return_value_save_as, 0, 7 ) ==
									 'option:' ) {
									$exploded_return_value_save_as = explode( ':',
										$homepage_step_obj->return_value_save_as );

									$option_value_key = $exploded_return_value_save_as[1];

									// if the key ends in "[]"
									if ( substr( $option_value_key, - 2 ) == '[]' ) {
										$option_value_key = str_replace( '[]', '',
											$option_value_key );

										$current_option = get_option(
											$homepage_step_obj->return_value_save_to );

										$current_option[$option_value_key][] = $post_id;

										update_option( $homepage_step_obj->return_value_save_to,
											$current_option );
									}
								}

								break;
						}
					}

					break;

				case 'option' :

					// update sting option
					if ( empty( $homepage_step_obj->option->value_key ) ) {
						update_option( $homepage_step_obj->option->name,
							$homepage_step_obj->option->value );
					} else {
						// update array option
						$current_option = get_option( $homepage_step_obj->option->name );

						$current_option[$homepage_step_obj->option->value_key] = $homepage_step_obj->option->value_value;

						update_option( $homepage_step_obj->option->name, $current_option );
					}

					break;

				case 'process_return_value' :
					update_option( $homepage_step_obj->return_value_save_to,
						$homepage_var[$homepage_step_obj->return_value_save_to] );

					break;

				case 'download_asset' :
					$url_to_uploaded_asset = $this->asset_manager->download_and_attach_asset( false,
						false, $homepage_step_obj->action_id );

					if ( substr( $homepage_step_obj->return_value_save_as, 0, 7 ) == 'option:' ) {
						$exploded_return_value_save_as = explode( ':',
							$homepage_step_obj->return_value_save_as );

						$option_value_key = $exploded_return_value_save_as[1];

						$current_option = get_option( $homepage_step_obj->return_value_save_to );

						$current_option[$option_value_key] = $url_to_uploaded_asset;

						update_option( $homepage_step_obj->return_value_save_to, $current_option );
					}

					break;

				case 'get_permalink' :
					$existing_page = get_page_by_title( $homepage_step_obj->page->page_title,
						OBJECT, $homepage_step_obj->page->post_type );

					$post_id = $existing_page->ID;

					$permalink = get_permalink( $post_id );

					update_option( $homepage_step_obj->return_value_save_to, $permalink );

					break;

				case 'add_widget_text' :

					/**
					 * First we need to add the new text widget to the database
					 */

					// create the widget array
					$widget['title'] = $homepage_step_obj->widget_text->title;
					$widget['text'] = $homepage_step_obj->widget_text->text;
					$widget['filter'] = $homepage_step_obj->widget_text->filter;
					$widget['_multiwidget'] = $homepage_step_obj->widget_text->_multiwidget;

					// get current widgets
					$current_widgets = get_option( 'widget_text' );

					// add our new text widget
					$current_widgets[] = $widget;

					end( $current_widgets );
					$widget_key = key( $current_widgets );

					// update widgets
					update_option( 'widget_text', $current_widgets );

					/**
					 * Then we need to update the sidebar widget
					 */
					$current_sidebar_widgets = get_option( 'sidebars_widgets' );
					$current_sidebar_widgets[$homepage_step_obj->return_value_save_to][] = "text-" .
						 $widget_key;
					update_option( 'sidebars_widgets', $current_sidebar_widgets );

					break;
			}
		}
	}

	/**
	 * Assign a menu_id to all locations
	 */
	public function assign_menu_id_to_all_locations( $menu_id ) {
		/**
		 * Get the current assignment of menu_id's to theme menu locations.
		 * Generally will be blank at this point.
		 *
		 * $locations =
		 */
		$locations = get_theme_mod( 'nav_menu_locations' );

		/**
		 * Get the nav menus registered by current theme.
		 *
		 * $registered_nav_menus = Array
		 * (
		 * * [primary] => Primary Menu
		 * )
		 *
		 * The keys are the locations, while the values are the descriptions.
		 */
		$registered_nav_menus = get_registered_nav_menus();

		/**
		 * There may be a timing issue related to this that we'll need to resolve in the future.
		 * We're trying to get registered_nav_menus before the theme has been able to register them.
		 * If this is the case, the theme data from the asset server may need to contain the
		 * registered nav menus.
		 */
		$additional_menus_to_register = array (
			'primary' => 'Primary menu'
		);
		// 'secondary' => 'Secondary menu'

		foreach ( $additional_menus_to_register as $name => $description ) {
			if ( ! isset( $registered_nav_menus[$name] ) ) {
				$registered_nav_menus[$name] = $description;
			}
		}

		/**
		 * Assign this new menu_id to those locations if they're not already set.
		 */
		// foreach ( $registered_nav_menus as $menu_key => $menu_description ) {
		$locations['primary'] = $menu_id;
		// }

		/**
		 * We've finished updating $locations, it now looks like this:
		 *
		 * $locations = Array
		 * (
		 * * [primary] => 2
		 * )
		 */
		set_theme_mod( 'nav_menu_locations', $locations );
	}

	/**
	 */
	public function build_attribution_page() {
		// create the attribution page
		$settings = array (
			'configDir' => BOLDGRID_BASE_DIR . '/includes/config',
			'menu_id' => $this->primary_menu_id
		);

		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-attribution.php';

		$attribution = new Boldgrid_Inspirations_Attribution( $settings );

		$attribution->build_attribution_page();
	}

	/**
	 * Full deployment.
	 *
	 * @return boolean
	 */
	public function full_deploy() {
		// if we need to, fire up a new site.
		$this->create_new_install();

		// Updates the install Options.
		$this->update_install_options();

		/*
		 * Pass the requested install options to the asset server and return install
		 * options that will be stored in the users WP.
		 */
		$this->remote_install_options();

		// Install the selected theme.
		$deploy_theme_success = $this->deploy_theme();

		// If theme deployemnt fails, then show a message to choose a different theme.
		if ( ! $deploy_theme_success ) {
			// Add info to the deployment log.
			$this->add_to_deploy_log( 'Theme deployment failed.  Please choose another theme.' );

			// LOG:
			error_log( __METHOD__ . ': Error: Theme deployment failed.' );

			// Change the deployment status.
			$this->change_deploy_status( 'Installation failed!  Please choose another theme.' );

			// Remove the loading graphic.
			$js = "	jQuery( '#deploy_status .boldgrid-loading' ).slideUp();
					jQuery( '#deploy_status .spinner' ).hide();
			";
			Boldgrid_Inspirations_Utility::inline_js_oneliner( $js );

			return false;
		}

		// import the selected page set.
		$this->deploy_page_sets();

		$boldgrid_inspiration_deploy_pages = new Boldgrid_Inspirations_Deploy_Pages(
			array (
				'post_status' => $this->post_status
			) );

		// Create temp pages in order to force image creation.
		$this->installed_page_ids = $boldgrid_inspiration_deploy_pages->deploy_temp_pages(
			$this->full_page_list, $this->installed_page_ids );

		// Download / setup the images required for each page/post.
		//
		// Option 1: Serial.
		// $this->deploy_page_sets_media();
		//
		// Option 2: Parallel.
		$this->deploy_page_sets_media_find_placeholders();

		$this->deploy_page_sets_media_process_image_queue();

		$this->deploy_page_sets_media_replace_placeholders();

		// Remove Temp pages that were created in order to force image creation.
		$boldgrid_inspiration_deploy_pages->cleanup_temp_pages( $this->full_page_list,
			$this->installed_page_ids );
		$this->add_to_deploy_log( 'Created static page backups.' );

		// download / setup the primary design elements.
		$this->deploy_pde();

		// create the attribution page.
		$this->build_attribution_page();

		if ( false == $this->is_preview_server ) {
			// Install Site Wide Plugins.
			$this->install_sitewide_plugins();
		}

		// do all final steps to finish deployment.
		$this->finish_deployment();
	}

	/**
	 * Update existing install options
	 *
	 * @param array $options
	 */
	public function update_existing_install_options( $options = array() ) {
		( $existing_options = get_option( 'boldgrid_install_options' ) ) ||
			 ( $existing_options = get_option( 'imhwpb_install_options' ) ) ||
			 ( $existing_options = array () );

		$options_merged = array_merge( $existing_options, $options );

		update_option( 'boldgrid_install_options', $options_merged );
	}

	/**
	 * Deploy only pages
	 */
	public function deploy_pages_only() {
		$this->post_status = 'draft';

		$this->add_pages_to_menu = false;

		$this->set_homepage = false;

		$this->deploy_page_sets();

		// download / setup the images required for each page/post
		$this->deploy_page_sets_media();

		// create the attribution page
		$this->build_attribution_page();

		// Send to admin screen
		$this->redirect_to_admin( 'edit.php?post_type=page' );
	}

	/**
	 * Copy theme_mods from old theme to new theme.
	 *
	 * If you install a new theme, you lose all of your theme_mods. This method will copy your
	 * nav_menu_location settings from your old theme to your new theme. This is the only theme_mod
	 * that is copied at this time.
	 *
	 * @param $new_stylesheet folder name of theme just installed.
	 * @param $initial_theme_mods theme_mods of theme prior to deployment.
	 */
	public function deploy_theme_copy_theme_mods( $new_stylesheet, $initial_theme_mods ) {
		$staging_prefix = $this->is_staging_install() ? 'boldgrid_staging_' : '';

		$new_theme_mod_option_name = $staging_prefix . 'theme_mods_' . $new_stylesheet;

		// Get theme_mods, if any, for the new theme.
		$new_theme_mods = get_option( $new_theme_mod_option_name );

		// Copy the nav_menu_locations
		if ( isset( $initial_theme_mods['nav_menu_locations'] ) ) {
			$new_theme_mods['nav_menu_locations'] = $initial_theme_mods['nav_menu_locations'];

			update_option( $new_theme_mod_option_name, $new_theme_mods );
		}
	}

	/**
	 * Only install a BG theme
	 */
	public function deploy_theme_only() {
		$this->activate_theme = false;

		$this->homepage_only = true;

		$this->add_pages_to_menu = false;

		$this->set_homepage = false;

		$this->post_status = 'draft';

		// Get the current theme's theme_mods. This will be used just a few lines below, in
		// $this->deploy_theme_copy_theme_mod().
		$initial_theme_mods = get_theme_mods();

		// install the selected theme
		$stylesheet = $this->deploy_theme();

		// Check if theme deployment failed:
		if ( empty( $stylesheet ) ) {
			// Add info to the deployment log:
			$this->add_to_deploy_log( 'Theme deployment failed.  Please choose another theme.' );

			// Change the deployment status:
			$this->change_deploy_status(
				'Theme installation failed!  Please choose another theme.' );

			// Remove the loading graphic:
			?>
<script type="text/javascript">
	jQuery( '#deploy_status .boldgrid-loading' ).slideUp();
	jQuery( '#deploy_status .spinner' ).hide();
</script>
<?php
			return false;
		}

		// Apply the old theme's theme_mods to the new theme's theme_mods. We do this to copy over
		// the nav_menu_locations settings, so we see menus instead of "Add a menu".
		$this->deploy_theme_copy_theme_mods( $stylesheet, $initial_theme_mods );

		// download / setup the primary design elements
		$deploy_pde_params = array (
			'stylesheet' => $stylesheet,
			'update_current_themes_mods' => false
		);
		$this->deploy_pde( $deploy_pde_params );

		// Update Existing theme in install options
		$this->update_existing_install_options( array (
			'theme_id' => $this->theme_id
		) );

		$this->deploy_page_sets();

		// download / setup the images required for each page/post
		$this->deploy_page_sets_media();

		// create the attribution page
		$this->build_attribution_page();

		// Send to admin screen
		$this->redirect_to_admin(
			sprintf(
				'themes.php?page=boldgrid-inspirations&staging=%s&inspiration-install=1&task=theme-install-success&stylesheet=' .
					 $stylesheet, true == $this->is_staging_install() ? '1' : '0' ) );
	}

	/**
	 * Check the permalink structure, if no active site, set to "/%postname%/" if needed
	 */
	private function check_permalink_structure() {
		$permalink_structure = get_option( 'permalink_structure' );

		if ( '/%postname%/' != $permalink_structure &&
			 ! Boldgrid_Inspirations_Built::has_active_site() ) {
			global $wp_rewrite;

			$wp_rewrite->set_permalink_structure( '/%postname%/' );

			// We need to make sure that a .htaccess file is being created.
			// If it is not, 404 pages may be handled by .htaccess rules set in higher directories.
			//
			// The parameter we're passing, $hard, is defined by WordPress as so:
			//
			// Whether to update .htaccess (hard flush) or just update rewrite_rules transient (soft
			// flush).
			flush_rewrite_rules( true );
		}
	}

	/**
	 * Deployment
	 */
	public function do_deploy() {
		// Get configs.
		$boldgrid_configs = $this->get_configs();

		// Set the PHP max_execution_time to 120 seconds (2 minutes):
		@ini_set( 'max_execution_time', 120 );

		// Start XHProf.
		if ( ! empty( $boldgrid_configs['xhprof'] ) && extension_loaded( 'xhprof' ) ) {
			xhprof_enable( XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY );
		}

		// Get the theme id, category id, etc.
		$this->get_deploy_details();

		// Check permalink structure:
		$this->check_permalink_structure();

		// Start over.
		$this->start_over();

		// Run the specified deployment:
		switch ( $this->deploy_type ) {
			case 'pages' :
				$this->deploy_pages_only();
				break;

			case 'theme' :
				$this->deploy_theme_only();
				break;

			default :
				$this->full_deploy();
				break;
		}

		// Save report to the log.
		if ( ! empty( $boldgrid_configs['xhprof'] ) && extension_loaded( 'xhprof' ) ) {
			$xhprof_data = xhprof_disable();

			$xhprof_utils_path = '/usr/share/pear/xhprof_lib/utils';

			if ( file_exists( $xhprof_utils_path . '/xhprof_lib.php' ) &&
				 file_exists( $xhprof_utils_path . '/xhprof_runs.php' ) ) {
				require_once $xhprof_utils_path . '/xhprof_lib.php';
				require_once $xhprof_utils_path . '/xhprof_runs.php';

				$xhprof_runs = new XHProfRuns_Default();
				$run_id = $xhprof_runs->save_run( $xhprof_data, 'xhprof_testing' );

				error_log(
					__METHOD__ . ': https://' . $_SERVER['HTTP_HOST'] . '/xhprof/index.php?run=' .
						 $run_id . '&source=xhprof_testing' );
			}
		}
	}

	/**
	 * Modify a dom element's attribute.
	 *
	 * For example, let's say we want to add a new class to an image, 'my-class'. If we simply set
	 * the image's class to 'my-class', then any other classes previously set will be lost. This
	 * method instead takes into consideration any values currently existing for a given attribute.
	 *
	 * @since 1.0.8
	 *
	 * @param object $dom_item A dom element, gathered elsewhere via a DOMDocument object.
	 * @param string $attribute An attribute of dom element, such as 'class' or 'src'.
	 * @param string $value A value to set for the above attribute.
	 * @return string An updated $value.
	 */
	public function dom_element_append_attribute( $dom_item, $attribute, $value ) {
		$current_value = $dom_item->getAttribute( $attribute );

		// If a value already exists, append our new value.
		// Else, our new value becomes the value.
		if ( ! empty( $current_value ) ) {
			$new_value = $current_value . ' ' . $value;
		} else {
			$new_value = $value;
		}

		return $new_value;
	}
}
