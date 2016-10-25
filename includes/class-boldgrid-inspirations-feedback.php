<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Feedback
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Feedback class.
 *
 * This class should only load if the user did not opt-out of feedback.
 *
 * @since 1.0.9
 */
class Boldgrid_Inspirations_Feedback {
	/**
	 * Constructor.
	 *
	 * Add hooks.
	 *
	 * @since 1.0.9
	 *
	 * @global $pagenow The current WordPress page filename.
	 */
	public function __construct() {
		// Get the current WordPress page filename.
		global $pagenow;

		// Is the current page for updates?
		$update_pages = array(
			'update-core.php',
			'plugins.php',
			'upgrade.php',
		);

		$is_update_page = ( in_array( $pagenow, $update_pages, true )
		);

		// Is the current page for settings?
		$is_settings_page = ( 1 === preg_match( '/^options-/', $pagenow ) );

		// Is the current task not to be interrupted with feedback?
		$uninterrupt_tasks = array(
			'deploy'
		);

		$uninterrupt_task = (
			isset( $_POST['task'] ) && in_array( $_POST['task'], $uninterrupt_tasks, true )
		);

		// Add an action to run when using the Customizer.
		add_action( 'customize_register', array(
			$this,
			'customizer_start',
		) );

		// Add an action to run when a theme is activated.
		add_action( 'after_switch_theme', array(
			$this,
			'theme_activation',
		) );

		// Add an action to record when a page is created using a GridBlock.
		add_action( 'boldgrid_inspirations_post_gridblock_set_create_page_callback',
			array(
				$this,
				'gridblock_add_page',
			) );

		// Add an action to check the payload.
		add_action( 'admin_init', array(
			$this,
			'check_payload',
		) );

		// Add an action to display admin notices.
		if ( ! ( is_network_admin() || $is_update_page || $is_settings_page ||
		$uninterrupt_task ) ) {
			add_action( 'admin_notices', array(
				$this,
				'display_feedback_notice'
			) );
		}

		// Add an action to handle diagnostic data requests.
		add_action( 'wp_ajax_boldgrid_feedback_diagnostic_data',
			array(
				$this,
				'feedback_diagnostic_data_callback',
			) );

		// Add an action to handle diagnostic data requests.
		add_action( 'wp_ajax_boldgrid_feedback_submit',
			array(
				$this,
				'feedback_submit_callback',
			) );
	}

	/**
	 * Customizer start.
	 *
	 * Record the first use of the customizer.
	 *
	 * @since 1.0.9
	 */
	public function customizer_start() {
		// Get the current timestamp.
		$timestamp = date( 'Y-m-d H:i:s' );

		// On the very first use.
		if ( false === get_option( 'boldgrid_customizer_first_use' ) ) {
			// Save timestamp of event.
			update_option( 'boldgrid_customizer_first_use', $timestamp );

			// Insert new data.
			self::add_feedback( 'customizer_start' );
		}
	}

	/**
	 * Theme activation.
	 *
	 * Record the theme name on activation.
	 *
	 * @since 1.0.11
	 */
	public function theme_activation() {
		// Get the current active theme.
		$theme_name = wp_get_theme()->get( 'Name' );

		// Insert new data.
		self::add_feedback( 'theme_activation', $theme_name );
	}

	/**
	 * GridBlock page insertion.
	 *
	 * Record when a page is created from a GridBlock.
	 *
	 * @since 1.0.12
	 *
	 * @param array $args {
	 *        An argument array passed from do_action().
	 *
	 *        @type int $page_id The client WordPress page id.
	 *        @type int $boldgrid_page_id BoldGrid page id.
	 *        }
	 * @return null
	 */
	public function gridblock_add_page( $args = null ) {
		// Get the BoldGrid page id.
		$boldgrid_page_id = ( isset( $args['boldgrid_page_id'] ) ? $args['boldgrid_page_id'] : null
		);

		// Insert new data.
		self::add_feedback( 'gridblock_add_page', $boldgrid_page_id );

		return;
	}

	/**
	 * Check payload.
	 *
	 * Check feedback payload to see if data needs to be delivered.
	 *
	 * @since 1.0.9
	 */
	public function check_payload() {
		// Initialize $success.
		$success = false;

		// Get the current feedback data.
		$feedback_data = get_option( 'boldgrid_feedback' );

		// If there is data, then send it.
		if ( ! empty( $feedback_data ) ) {
			// Deliver the data.
			$success = $this->deliver_payload( $feedback_data );
		}

		// If successful, then clear boldgrid_feedback.
		if ( $success ) {
			delete_option( 'boldgrid_feedback' );
		}
	}

	/**
	 * Deliver payload.
	 *
	 * Deliver the feedback payload to the asset server.
	 *
	 * @since 1.0.9
	 * @see Boldgrid_Inspirations_Api::boldgrid_api_call().
	 *
	 * @param array $data Feedback data array (from the WP Option "boldgrid_feedback").
	 * @return bool
	 */
	private function deliver_payload( $data = null ) {
		// Check data.
		if ( empty( $data ) || ! is_array( $data ) ) {
			return false;
		}

		// Use json_encode to the data.
		$feedback_data['boldgrid_feedback'] = wp_json_encode( $data );

		// Send the data.
		$response = Boldgrid_Inspirations_Api::boldgrid_api_call( '/api/feedback/process', false,
			$feedback_data, 'POST'
		);

		// Check response.
		if ( ! empty( $response->message ) && 'Data accepted' === $response->message ) {
			// Success.
			return true;
		} else {
			// Failure.
			return false;
		}
	}

	/**
	 * Add feedback.
	 *
	 * Add feedback data.
	 *
	 * @since 1.0.9
	 *
	 * @param string $metaname A metaname key to identify the type of feedback.
	 * @param mixed $metavalue A metavalue, which can vary in type.
	 * @return bool
	 */
	public static function add_feedback( $metaname, $metavalue = null ) {
		// Validate input.
		if ( empty( $metaname ) ) {
			return false;
		}

		// Get the current timestamp.
		$timestamp = date( 'Y-m-d H:i:s' );

		// Get the current feedback data.
		$feedback_data = get_option( 'boldgrid_feedback' );

		// Insert new data.
		$feedback_data[] = array(
			'type' => $metaname,
			'timestamp' => $timestamp,
			'value' => $metavalue,
		);

		// Save data.
		update_option( 'boldgrid_feedback', $feedback_data, false );

		return true;
	}

	/**
	 * Display feedback notice.
	 *
	 * Feedback notice is triggered 7 and 60 days after a site is installed with Inspirations
	 * version 1.1 or higher. If the notice was dismissed in the last week, or feedback was already
	 * sent within the last week, then do not show the feedback notice.
	 *
	 * @since 1.1
	 *
	 * @return null
	 */
	public function display_feedback_notice() {
		/*
		 * Inspirations deployment creates pages, and so the user must be able to 'edit_pages'. If
		 * the current user cannot, they don't need to see this message.
		 */
		if( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		// Get boldgrid_install_options from WP Options.
		$install_options = get_option( 'boldgrid_install_options' );

		// If the install timestamp is not present, then abort.
		if ( empty( $install_options['install_timestamp'] ) ) {
			return;
		}

		// Create a variable for the unix time (in seconds) 7 days ago.
		$seven_days_ago = strtotime( 'NOW - 7 DAYS' );

		// Create a variable for the unix time (in seconds) 60 days ago.
		$sixty_days_ago = strtotime( 'NOW - 60 DAYS' );

		// Get the install timestamp.
		$install_timestamp = $install_options['install_timestamp'];

		// If is has been less than 7 days since a site was installed, then abort.
		if ( $install_timestamp > $seven_days_ago ) {
			return;
		}

		// Get the current user id.
		$user_id = get_current_user_id();

		// Get boldgrid_feedback_sent (array of timestamps) from user metadata.
		$feedback_sent = get_user_meta( $user_id, 'boldgrid_feedback_sent' );

		// Examine the feedback sent timestamps array, check if the latest is newer than 7 days.
		if ( ! empty( $feedback_sent ) ) {
			// Initialize $latest_feedback_sent.
			$latest_feedback_sent = null;

			foreach ( $feedback_sent as $timestamp ) {
				if ( $timestamp > $latest_feedback_sent ) {
					$latest_feedback_sent = $timestamp;
				}
			}

			// If feedback sent is recent (in the last week), then abort.
			if ( $latest_feedback_sent >= $seven_days_ago ) {
				return;
			}

			// Feedback was sent over a week ago.
			// If the last site install was less than 60 days ago, then abort.
			if ( $install_timestamp < $sixty_days_ago ) {
				return;
			}
		}

		// Instantiate the Boldgrid_Inspirations_Admin_Notices class.
		$admin_notices = new Boldgrid_Inspirations_Admin_Notices();

		$dismissal = $admin_notices->get( 'feedback-notice-1-1' );

		$has_been_dismissed = ( false !== $dismissal );

		if ( $has_been_dismissed ) {
			/*
			 * If the user dismissed this notice within the last week, abort and do not show the
			 * notice.
			 *
			 * Otherwise, the user dismissed the notice more than seven days ago. Delete that
			 * dismissal and give them another reminder for feedback.
			 */
			if ( $dismissal['timestamp'] > $seven_days_ago ) {
				return;
			} else {
				$admin_notices->clear( 'feedback-notice-1-1' );
			}
		}

		// Add an action to ask for feedback.
		add_action( 'all_admin_notices', array(
			$this,
			'ask_feedback',
		) );
	}

	/**
	 * Ask for feedback.
	 *
	 * Displays an admin notice to ask for feedback.
	 *
	 * @since 1.1
	 */
	public function ask_feedback() {
		// Get the admin email address.
		$user_email = '';

		if ( function_exists( 'wp_get_current_user' ) &&
			 false !== ( $current_user = wp_get_current_user() ) ) {
			$user_email = $current_user->user_email;
		}

		// Register and enqueue styles and scripts.
		$this->enqueue_scripts();

		// Get the WP option containing reseller data.
		$reseller_data = get_option( 'boldgrid_reseller' );

		// Get the reseller title.
		$reseller_title = esc_html(
			! empty( $reseller_data['reseller_title'] ) ? $reseller_data['reseller_title'] : null
		);

		// Display the notice.
		include BOLDGRID_BASE_DIR . '/pages/templates/feedback-notice-1-1.php';
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.1
	 *
	 * @return null
	 */
	public function enqueue_scripts() {
		wp_register_style(
			'boldgrid-feedback-css',
			plugins_url(
				'/assets/css/boldgrid-feedback.css',
				BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
			),
			array(),
			BOLDGRID_INSPIRATIONS_VERSION
		);

		wp_enqueue_style( 'boldgrid-feedback-css' );

		wp_register_script(
			'boldgrid-feedback-js',
			plugins_url(
				'assets/js/boldgrid-feedback.js',
				BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
			),
			array(),
			BOLDGRID_INSPIRATIONS_VERSION
		);

		wp_enqueue_script( 'boldgrid-feedback-js' );

		return;
	}

	/**
	 * Callback for diagnostic data requests.
	 *
	 * This callback function prints a text report with diagnostic information.
	 *
	 * @since 1.1
	 *
	 * @see wp_verify_nonce
	 * @see check_ajax_referer
	 * @global string $wp_version The WordPress version.
	 *
	 * @return null
	 */
	public function feedback_diagnostic_data_callback() {
		// If this user is not an Administrator, do not allow this data to be populated and returned.
		if( ! current_user_can( 'manage_options' ) ) {
			echo __( 'To have diagnostic data sent with your feedback, please login as an Administrator', 'boldgrid-inspirations' );
			wp_die( 'Error: WordPress security violation.' );
		}

		// Check the nonce.
		$nonce_check = check_ajax_referer( 'feedback-notice-1-1', 'feedback_auth', false );

		if ( 1 !== $nonce_check ) {
			// Terminate this callback script, with an error message.
			wp_die( 'Error: WordPress security violation.' );
		}

		// Initialize $return.
		$return = 'This diagnostic data is populated to better assist you, and can be modified before submitted.' .
			 PHP_EOL;

		// Import the WordPress global $wp_version.
		global $wp_version;

		// Print WordPress Information.
		$return .= 'WordPress Information:' . PHP_EOL;

		// Print the WordPress version.
		$return .= ' Version: ' . $wp_version . PHP_EOL;

		// Print the WordPress home_url.
		$return .= ' Home URL: ' . home_url() . PHP_EOL;

		// Print the WordPress site_url.
		$return .= ' Site URL: ' . site_url() . PHP_EOL;

		// Print the WordPress Installation Root Directory.
		//$return .= ' Installation Root: ' . ABSPATH . PHP_EOL;

		// Print the WordPress character set.
		//$return .= ' Character Set: ' . get_bloginfo( 'charset' ) . PHP_EOL;

		// Print the WordPress Language.
		//$return .= ' Language: ' . get_bloginfo( 'language' ) . PHP_EOL;

		// Check if is multisite.
		//$is_multisite = is_multisite() ? 'Yes' : 'No';

		//$return .= ' Multisite: ' . $is_multisite . PHP_EOL;

		// Print the WordPress filesystem method.
		//$return .= ' Filesystem Method: ' . get_filesystem_method() . PHP_EOL;

		// Get the site active plugin slugs.
		$site_plugins = get_option( 'active_plugins', array () );

		// Print the network active plugin slugs.
		if ( is_multisite() ) {
			$sitewide_plugins = array_keys( get_site_option( 'active_sitewide_plugins', array() ) );

			$active_plugins = array_unique( array_merge( $site_plugins, $sitewide_plugins ) );
		} else {
			$active_plugins = $site_plugins;
		}

		// Print the installed plugins.
		$return .= ' Installed Plugins:' . PHP_EOL;

		$plugins = get_plugins();

		// Print the installed plugin information.
		foreach ( $plugins as $plugin_path => $plugin_data ) {
			$active = ( false !== array_search( $plugin_path, $active_plugins ) ? ' (Active)' : '' );

			$return .= '  ' . $plugin_data['Name'] . ' (' . $plugin_path . ') [' .
				 $plugin_data['Version'] . ']' . $active . PHP_EOL;

			unset( $is_plugin_active );
			unset( $active );
		}

		unset( $plugins );

		// Get the current stylesheet (active theme).
		$current_active_stylesheet = get_stylesheet();

		// Determine if the boldgrid-staging theme is active.
		$is_staging_active = ( false !== array_search( 'boldgrid-staging/boldgrid-staging.php',
			$active_plugins ) );

		// Get the current staging active theme.
		$current_staging_stylesheet = get_option( 'boldgrid_staging_stylesheet' );

		// Print all WordPress themes.
		$return .= ' Installed Themes:' . PHP_EOL;

		$themes = wp_get_themes( array (
			'errors' => null
		) );

		foreach ( $themes as $key => $object ) {
			$active = ( $current_active_stylesheet === $key ? ' (Active theme)' : '' );

			$staging = ( $current_staging_stylesheet === $key ? ' (Active Staging theme)' : '' );

			$return .= '  ' . $object->get( 'Name' ) . ' (' . $key . ') [' .
				 $object->get( 'Version' ) . ']' . $active . $staging . PHP_EOL;

			$parent_theme = $object->get( 'Template' );

			$return .= '    Parent Theme: ' . ( $parent_theme ? $parent_theme : 'None' ) . PHP_EOL;

			unset( $active );
			unset( $parent_theme );
		}

		unset( $themes );

		// Print BoldGrid Information.
		$return .= 'BoldGrid Information:' . PHP_EOL;

		// Get BoldGrid settings.
		// ( $options = get_site_option( 'boldgrid_settings' ) ) ||
		// ( $options = get_option( 'boldgrid_settings' ) );


		// Print the update release channel.
		//$release_channel = ( isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable' );

		//$return .= ' Release Channel: ' . $release_channel . PHP_EOL;

		// Retrieve all WordPress Options.
		$wp_options = wp_load_alloptions();

		// Print some BoldGrid WordPress Options.
		$return .= ' WordPress Options:' . PHP_EOL;

		$include_options = array(
			//'boldgrid_api_key',
			//'boldgrid_site_hash',
			//'boldgrid_settings',
			'boldgrid_reseller',
			//'boldgrid_has_built_site',
			'boldgrid_install_options'
		);

		foreach ( $wp_options as $key => $value ) {
			if ( false !== array_search( $key, $include_options ) ) {
				$return .= '  ' . $key . ': ' . $value . PHP_EOL;
			}
		}

		// Print system information.
		//$return .= 'OS Information: ' . php_uname() . PHP_EOL;

		// Print PHP Information.
		$return .= 'PHP Information:' . PHP_EOL;

		// Print PHP version.
		$return .= ' Version: ' . phpversion() . PHP_EOL;

		// Print PHP SAPI.
		//$return .= ' SAPI: ' . php_sapi_name() . PHP_EOL;

		// Print the return text.
		echo $return;

		// Terminate this callback script.
		wp_die();

		// Return added for CodeSniffer.
		return;
	}

	/**
	 * Callback for feedback data submission.
	 *
	 * This callback function accepts a JSON array containing submitted form data, if there is data,
	 * it is added to the feedback delivery queue.
	 *
	 * @since 1.1
	 *
	 * @param string $_POST['form_data'] A JSON array containing submitted form data.
	 * @return null
	 */
	public function feedback_submit_callback() {
		// Check the nonce.
		$nonce_check = check_ajax_referer( 'feedback-notice-1-1', 'feedback_auth', false );

		// If you are not at least a Contributor, you may not submit feedback.
		if( ! current_user_can( 'edit_posts' ) ) {
			wp_die( 'Error: WordPress security violation.' );
		}

		if ( 1 !== $nonce_check ) {
			// Terminate this callback script, with an error message.
			wp_die( 'Error: WordPress security violation.' );
		}

		// Validate POST form_data.
		if ( empty( $_POST['form_data'] ) ) {
			// Terminate this callback script, with an error message.
			wp_die( 'Error: Empty form data set.' );
		}

		// Add feedback.
		self::add_feedback( 'feedback_form', $_POST['form_data'] );

		// Get the current user id.
		$user_id = get_current_user_id();

		// Get boldgrid_feedback_sent (array of timestamps) from user metadata.
		$feedback_sent = add_user_meta( $user_id, 'boldgrid_feedback_sent', time(), false );

		// Terminate this callback script, with a success message.
		wp_die( 'Success' );

		// Return added for CodeSniffer.
		return;
	}
}
