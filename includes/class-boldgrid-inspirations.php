<?php

/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations
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

require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-config.php';

/**
 * BoldGrid Inspirations class
 */
class Boldgrid_Inspirations {

	/**
	 * Array of BoldGrid specific configs
	 *
	 * @var array
	 */
	protected $configs = null;

	/**
	 * Boolean that identifies whether or not the use has passed api key validation.
	 * if this returns null its because you called it on an instance that has not run
	 * "passes_api_check"
	 *
	 * @var bool
	 */
	protected $passed_key_validation = false;

	/**
	 * Static class property $have_enqueued_admin_notice_about_missing_api_key
	 */
	public static $have_enqueued_admin_notice_about_missing_api_key = false;

	/**
	 * Class property for $is_preview_server
	 *
	 * @var bool
	 */
	public $is_preview_server = false;

	/**
	 * Class property for asset server availability
	 *
	 * @var bool
	 */
	private static $is_asset_server_available = false;

	/**
	 * Constructor
	 *
	 * @param unknown $settings
	 */
	public function __construct() {
		// Get configs and set in a class property.
		$this->configs = Boldgrid_Inspirations_Config::get_format_configs();

		add_filter( 'wp_kses_allowed_html', array (
			$this,
			'filter_allowed_html'
		), 10, 2 );

		$this->set_is_preview_server();
		$this->set_asset_user_id();
		$this->set_api_key_hash();

		// Initialize $is_asset_server_available; set class property from transient:
		$is_asset_server_available = ( bool ) get_site_transient( 'boldgrid_available' );

		// If we had communication issues, then check now; it may be better:
		if ( false === $is_asset_server_available ) {
			// Verify API key, which connects to the asset server and sets the status:
			$this->verify_api_key();
		} else {
			self::set_is_asset_server_available( true );

			// Ensure all activation data was sent:
			if ( function_exists( 'wp_get_current_user' ) &&
				 false !== ( $current_user = wp_get_current_user() ) ) {
				$first_login_ts = strtotime(
					get_user_meta( $current_user->ID, 'first_login', true ) );

				// If the first login was made in the last 30 seconds, then verify activation:
				if ( $first_login_ts + 30 > time() ) {
					$_GET['force-check'] = 1;
					$this->verify_api_key();
				}
			}
		}
	}

	/**
	 * Get configuration settings
	 *
	 * @return array
	 */
	public function get_configs() {
		return $this->configs;
	}
	/**
	 * Set configuration settings
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
	 * Get the value of the class property $is_asset_server_available
	 *
	 * @return bool
	 */
	public static function get_is_asset_server_available() {
		return self::$is_asset_server_available;
	}

	/**
	 * Set the value of the class property $is_asset_server_available
	 *
	 * @param bool $is_asset_server_available
	 *
	 * @return bool
	 */
	public static function set_is_asset_server_available( $is_asset_server_available ) {
		// Validate $is_asset_server_available:
		$is_asset_server_available = ( bool ) $is_asset_server_available;
		// Set the property:
		self::$is_asset_server_available = $is_asset_server_available;

		// Save the WP Option:
		set_site_transient( 'boldgrid_available', $is_asset_server_available, HOUR_IN_SECONDS );

		return true;
	}

	/**
	 * Check the connection to the asset server:
	 */
	public function check_asset_server_callback() {
		// Verify API key, which connects to the asset server and sets the status:
		$this->verify_api_key();

		// Return status:
		return self::get_is_asset_server_available();
		wp_die();
	}

	/**
	 * Accessor for $this->passed_key_validation
	 *
	 * @return string
	 */
	public function get_passed_key_validation() {
		return $this->passed_key_validation;
	}

	/**
	 * Setter for $this->passed_key_validation
	 *
	 * @param bool $passed
	 *
	 * @return string
	 */
	private function set_passed_key_validation( $passed ) {
		$this->passed_key_validation = $passed;
		return true;
	}

	/**
	 * Get the asset server grid file styles url
	 *
	 * @return string
	 */
	public function get_grid() {
		$configs = $this->get_configs();
		return $configs['asset_server'] . '/static/grid.css';
	}

	/**
	 * Enqueue grid css style
	 */
	public function enqueue_site_grid() {
		wp_register_style( 'grid-system-imhwpb', $this->get_grid(), array (),
			BOLDGRID_INSPIRATIONS_VERSION );
		wp_enqueue_style( 'grid-system-imhwpb' );
	}

	/**
	 * API key requirement check
	 *
	 * If required, verify the stored API key with the asset server.
	 *
	 * @param bool $api_key_required
	 * @param bool $is_boldgrid_api_data_new
	 *
	 * @return bool
	 */
	public function passes_api_check( $api_key_required = false, $is_boldgrid_api_data_new = false ) {
		// If key is not required, then mark as validated and return true:
		if ( false === $api_key_required ) {
			$this->set_passed_key_validation( true );
			return true;
		}

		// Get the configs:
		$configs = $this->get_configs();

		// Check for api data transient:
		$boldgrid_api_data = get_transient( 'boldgrid_api_data' );

		// If there is no transient data, then retrieve it from the asset server:
		if ( empty( $boldgrid_api_data ) ) {
			$boldgrid_api_data = self::boldgrid_api_call( $configs['ajax_calls']['get_version'] );
			$is_boldgrid_api_data_new = true;
		}

		// Check if we have valid API data:
		if ( isset( $boldgrid_api_data->status ) && 200 == $boldgrid_api_data->status ) {
			// If we did not have a site hash, but got one in the return, then set the WP Option:
			if ( empty( $configs['site_hash'] ) && ! empty(
				$boldgrid_api_data->result->data->site_hash ) ) {
				update_option( 'boldgrid_site_hash', $boldgrid_api_data->result->data->site_hash );
			}

			// If we just retrieved new data, then update reseller option:
			if ( true === $is_boldgrid_api_data_new || isset( $_REQUEST['force-check'] ) ) {
				$boldgrid_reseller_array = array ();

				foreach ( $boldgrid_api_data->result->data as $key => $value ) {
					if ( preg_match( '/^reseller_/', $key ) ) {
						$boldgrid_reseller_array[$key] = $boldgrid_api_data->result->data->$key;
					}
				}

				// Set the reseller option from api data, or delete if no reseller data:
				if ( count( $boldgrid_reseller_array ) ) {
					update_option( 'boldgrid_reseller', $boldgrid_reseller_array );
				} else {
					delete_option( 'boldgrid_reseller' );
				}
			}

			// Mark as validated and return true:
			$this->set_passed_key_validation( true );
			return true;
		} else {
			// API key did not verify or received a bad response, so fail:
			$this->set_passed_key_validation( false );
			return false;
		}
	}

	/**
	 * Connects to the API and returns the response in an array
	 *
	 * @param string $api_path
	 *        	The API path to call.
	 * @param bool $json_array
	 *        	The return format; object (default) or array.
	 * @param string $params_array
	 *        	An optional array of parameters to include.
	 * @param string $method
	 *        	The request method; GET (default) or POST.
	 *
	 * @return object|array|false
	 */
	public static function boldgrid_api_call( $api_path, $json_array = false, $params_array = array(), $method = 'GET' ) {
		// If this is a BoldGrid Inspirations plugin version check, then check if we already have recent data,
		// return it if so, and not a force-check:
		if ( '/api/plugin/check-version' == $api_path ) {
			// Get api data transient:
			if ( is_multisite() ) {
				$boldgrid_api_data = get_site_transient( 'boldgrid_api_data' );
			} else {
				$boldgrid_api_data = get_transient( 'boldgrid_api_data' );
			}

			// If the API data was just retrieved (last 5 seconds) and is ok, then just return it.
			if ( ! empty( $boldgrid_api_data ) && ! ( isset( $_GET['force-check'] ) &&
				 isset( $boldgrid_api_data->updated ) && $boldgrid_api_data->updated < time() - 5 ) ) {

				return $boldgrid_api_data;
			}
		}

		// Get the configs:
		$configs = Boldgrid_Inspirations_Config::get_format_configs();

		// Set the API key:
		if ( isset( $_POST['api_key'] ) ) {
			// On activation
			$api_key_hash = self::hash_api_key( $_POST['api_key'] );
		} elseif ( isset( $_POST['key'] ) ) {
			// POST of the hash
			$api_key_hash = sanitize_text_field( $_POST['key'] );
		} elseif ( isset( $_GET['key'] ) ) {
			// GET of the hash
			$api_key_hash = sanitize_text_field( $_GET['key'] );
		} else {
			// From configs
			$api_key_hash = ( isset( $configs['api_key'] ) ? $configs['api_key'] : null );
		}

		// Build the GET parameters:
		if ( ! empty( $api_key_hash ) ) {
			$params_array['key'] = $api_key_hash;
		}

		if ( ! empty( $configs['site_hash'] ) ) {
			$params_array['site_hash'] = $configs['site_hash'];
		}

		// If getting plugin version information, include other parameters:
		if ( preg_match( '/(check|get-plugin)-version/', $api_path ) ) {
			// Get BoldGrid settings:
			$options = get_option( 'boldgrid_settings' );

			// Include update release channel:
			$params_array['channel'] = isset( $options['release_channel'] ) ? $options['release_channel'] : 'stable';
			$params_array['theme_channel'] = ! empty( $options['theme_release_channel'] ) ? $options['theme_release_channel'] : 'stable';

			// If get_plugin_data does not exist, then load it:
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			// Get the installed plugin data:
			$plugin_data = get_plugin_data( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php',
				false );

			$params_array['installed_core_version'] = $plugin_data['Version'];

			// Get the WordPress version:
			global $wp_version;

			$params_array['installed_wp_version'] = $wp_version;

			// Get PHP version:
			$params_array['installed_php_version'] = phpversion();

			// Include feedback opt-out setting:
			$params_array['feedback_optout'] = isset( $options['boldgrid_feedback_optout'] ) ? $options['boldgrid_feedback_optout'] : '0';

			// If allowed, then include feedback info:
			if ( ! $params_array['feedback_optout'] ) {
				// Include activation/update information:
				if ( function_exists( 'wp_get_current_user' ) &&
					 false !== ( $current_user = wp_get_current_user() ) ) {
					$params_array['first_login'] = get_user_meta( $current_user->ID, 'first_login',
						true );
					$params_array['last_login'] = get_user_meta( $current_user->ID, 'last_login',
						true );
					$params_array['user_login'] = $current_user->user_login;
					$params_array['user_email'] = $current_user->user_email;
				}

				// Mobile ratio.
				if ( is_multisite() ) {
					$mobile_ratio = get_site_option( 'boldgrid_mobile_ratio' );
				} else {
					$mobile_ratio = get_option( 'boldgrid_mobile_ratio' );
				}

				if ( false === empty( $mobile_ratio ) ) {
					$params_array['mobile_ratio'] = $mobile_ratio;
				}
			}
		}

		// Set the complete URL:
		$url = $configs['asset_server'] . $api_path;

		// Make a call to the asset server:
		if ( 'POST' == $method ) {
			$boldgrid_api_data = wp_remote_retrieve_body(
				wp_remote_post( $url, array (
					'body' => $params_array
				) ) );
		} else {
			// Convert the params array into a query string:
			if ( false === empty( $params_array ) ) {
				$params = http_build_query( $params_array );
			}

			// Append the params query string to the URL:
			$url .= '?' . $params;

			// Make the call:
			$boldgrid_api_data = wp_remote_retrieve_body( wp_remote_get( $url ) );
		}

		// Decode the JSON returned into an object:
		$boldgrid_api_data_object = json_decode( $boldgrid_api_data );

		// Check asset server availability:
		if ( isset( $boldgrid_api_data_object->status ) ) {
			Boldgrid_Inspirations::set_is_asset_server_available( true );
		} else {
			Boldgrid_Inspirations::set_is_asset_server_available( false );

			// LOG:
			error_log( __METHOD__ . ': Asset server is unavailable.' );
		}

		// Decode the JSON returned into an object:
		$boldgrid_api_data = json_decode( $boldgrid_api_data, $json_array );

		// If this was a BoldGrid Inpirations plugin version check, then store only valid data:
		if ( '/api/plugin/check-version' == $api_path && isset( $boldgrid_api_data->status ) &&
			 200 == $boldgrid_api_data->status ) {
			// Add the current timestamp (in seconds):
			$boldgrid_api_data->updated = time();

			// Set api data transient, expired in 8 hours:
			if ( is_multisite() ) {
				delete_site_transient( 'boldgrid_api_data' );
				set_site_transient( 'boldgrid_api_data', $boldgrid_api_data, 8 * HOUR_IN_SECONDS );
			} else {
				delete_transient( 'boldgrid_api_data' );
				set_transient( 'boldgrid_api_data', $boldgrid_api_data, 8 * HOUR_IN_SECONDS );
			}
		}

		// Return the object:
		return $boldgrid_api_data;
	}

	/**
	 * Validates the API key and returns details on if it is valid as well as version.
	 *
	 * @return object|string
	 */
	public function verify_api_key() {
		// Include the update class:
		require_once BOLDGRID_BASE_DIR . '/includes/class-boldgrid-inspirations-update.php';

		// Make an API call for API data:
		$boldgrid_api_data = Boldgrid_Inspirations_Update::update_api_data();

		// Handle the response:
		if ( false !== $boldgrid_api_data ) {
			// Check response:
			if ( '200' == $boldgrid_api_data->status && 'OK' == $boldgrid_api_data->message ) {
				$boldgrid_api_data->license_status = true;

				// Process post api key verification checks:
				$this->passes_api_check( true, true );
			} elseif ( $boldgrid_api_data->message == 'Unauthorized' ) {
				$boldgrid_api_data->license_status = false;
			} else {
				// LOG:
				error_log(
					__METHOD__ .
						 ': Error: Error when getting version information.  $boldgrid_api_data: ' . print_r(
							$boldgrid_api_data, true ) );

				return 'Error when getting version info';
			}
		} else {
			return 'api call failed';
		}

		return $boldgrid_api_data;
	}

	/**
	 * Load the necessary resources in the dashboard when prompting the user for an api key
	 *
	 * html
	 * ajax callback
	 * /assets/js/api/api.js
	 */
	public function add_hooks_to_prompt_for_api_key() {
		// At this point, we've decided that we need to ask the user for an api key.
		// Let's only ask them once! IE don't show two admin notices asking for a key.
		if ( false == self::$have_enqueued_admin_notice_about_missing_api_key ) {
			// If the asset server is available and there is no site hash, then ask for the api key, else notify:
			if ( self::get_is_asset_server_available() ) {
				// add a message to the dashboard that asks for the api key
				add_action( 'admin_notices', array (
					$this,
					'prompt_for_api_key'
				) );

				// load javascript that handles ajax submission of api key
				add_action( 'admin_enqueue_scripts',
					array (
						$this,
						'enqueue_js_api_submission'
					) );

				// action to handle submission of key via ajax
				add_action( 'wp_ajax_set_api_key',
					array (
						$this,
						'set_api_key_callback'
					) );

				self::$have_enqueued_admin_notice_about_missing_api_key = true;
			} else {
				// Notify that there is a connection issue:
				add_action( 'admin_notices',
					array (
						$this,
						'notify_connection_issue'
					) );
			}
		}
	}

	/**
	 * Check if string is null or empty
	 *
	 * @return bool Whether or not string is empty.
	 * @since 1.X
	 */
	public function is_null_or_empty( $var ) {
		return ( ! isset( $var ) || trim( $var )==='' );
	}

	/**
	 * Print a notice asking the user to input their api_key
	 */
	public function prompt_for_api_key() {
		// Get current user.
		$current_user = wp_get_current_user();
		// E-mail is always checked and is a required wp field for user.
		$email = $current_user->user_email;
		// First name if exists from user.
		$first_name = $this->is_null_or_empty( $current_user->user_firstname ) ? '' : $current_user->user_firstname;
		// Last name if exists from user.
		$last_name = $this->is_null_or_empty( $current_user->user_lastname ) ? '' : $current_user->user_lastname;
		?>
<div id="container_boldgrid_api_key_notice" class="error">
	<div class="api-notice">
		<h2 class="dashicons-before dashicons-admin-network"><?php _e( 'Enter Your BoldGrid Connect Key' ); ?></h2>
		<p id="boldgrid_api_key_notice_message">
			Please enter your <b>32 digit BoldGrid Connect Key</b> below and click
			submit.
		</p>
		<form id='boldgrid-api-form' autocomplete='off'>
		<?php wp_nonce_field( 'boldgrid_set_key', 'set_key_auth' ); ?>
			<div class="tos-box"><input type="checkbox" id="tos-box" value="0">I agree to the <a href="https://www.boldgrid.com/software-privacy-policy/" target="_blank">Terms of Use and Privacy Policy</a>.</div><br>
			<input type='text' id='boldgrid_api_key' maxlength='37'
				placeholder='XXXXXXXX - XXXXXXXX - XXXXXXXX - XXXXXXXX' autocomplete='off' />
			<button id="submit_api_key" class="button button-primary">Submit</button>
			<span><div id="boldgrid-api-loading" class="boldgrid-wp-spin"></div></span>
		</form>
		<br />
		<a href="#" class="boldgridApiKeyLink"><?php _e( 'Don\'t have a Connect Key yet or lost your Key?' ); ?></a>
	</div>
	<div class="new-api-key hidden">
		<h2 class="dashicons-before dashicons-admin-network"><?php _e( 'Request a BoldGrid Connect Key' ); ?></h2>
		<a href="#" class="enterKeyLink">Have a Connect Key to enter?</a><br /><br />
		<div class="key-request-content">
			<p id="requestKeyMessage">
				There are two types of BoldGrid Connect Keys, a free key or an Official Host Premium Connect Key. <b>A Premium Connect Key is highly recommended and may already come with your hosting account.</b> If you do not have a Premium Connect Key, then you may request a free key below. Please visit <a href="https://www.boldgrid.com/get-it-now/" target="_blank">our site</a> for full details.<br /><br />If you have lost your key, you can have it resent by entering your information below.<br />
			</p>
			<p class="error-alerts"></p>
			<form id="requestKeyForm">
				<label>First Name:</label>
				<input type="text" id="firstName" maxlength="50" placeholder="First Name" value="<?php echo $first_name ?>" />
				<label>Last Name:</label>
				<input type="text" id="lastName" maxlength="50" placeholder="Last Name" value="<?php echo $last_name ?>" />
				<label>E-mail:</label>
				<input type="text" id="emailAddr" maxlength="50" placeholder="your@name.com" value="<?php echo $email ?>" /><br />
				<input type="hidden" id="siteUrl" value='<?php echo get_admin_url(); ?>' /><br />
				<button id="requestKey" class="button button-primary">Submit</button>
			</form>
		</div>
	</div>
</div>
<?php
	}

	/**
	 * Print a notice for connection issues
	 */
	public function notify_connection_issue() {
		require BOLDGRID_BASE_DIR . '/pages/templates/boldgrid_connection_issue.php';
	}

	/**
	 * Load api.js
	 */
	public function enqueue_js_api_submission() {
		wp_enqueue_script( 'api-submission',
			plugins_url( '/assets/js/api/api.js', BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' ),
			array (), BOLDGRID_INSPIRATIONS_VERSION, true );
	}

	/**
	 * Store the user's api_key as wp_option.
	 *
	 * This function is called via ajax.
	 *
	 * @param string $_POST['api_key']
	 */
	public function set_api_key_callback() {
		// Set messages.
		$messages = array(
			'success' => 'Your api key has been saved successfully.',
			'invalid_key' => 'Your API key appears to be invalid!<br />Please try to enter your BoldGrid Connect Key again.',
			'error_saving_key' => 'There was an error saving your key.<br />Please try entering your BoldGrid Connect Key again.',
			'nonce_failed' => 'Security violation (invalid nonce).',
		);

		// Verify nonce.
		if ( false === isset( $_POST['set_key_auth'] ) ||
			1 !== check_ajax_referer( 'boldgrid_set_key', 'set_key_auth', false ) ) {
				echo $messages['nonce_failed'];

				wp_die();
			}

		// Check input API key.
		if ( empty( $_POST['api_key'] ) ) {
			// Failure.
			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'invalid_key',
					'message' => $messages['invalid_key'],
				)
			);

			wp_die();
		}

		$api_key_hash = self::hash_api_key( $_POST['api_key'] );

		if ( null === $api_key_hash ) {
			// Failure.
			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'invalid_key',
					'message' => $messages['invalid_key'],
				)
			);

			wp_die();
		}

		// Delete the boldgrid_api_data transient.
		if ( true === is_multisite() ){
			delete_site_transient( 'boldgrid_api_data' );
		} else {
			delete_transient( 'boldgrid_api_data' );
		}

		// Verify the key.
		$boldgrid_api_data = $this->verify_api_key();

		// Interpret result.
		if ( 'api call failed' === $boldgrid_api_data ) {
			// Failure.
			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'invalid_key',
					'message' => $messages['invalid_key'],
				)
			);
		} elseif ( false === is_object( $boldgrid_api_data ) ) {
			// LOG.
			error_log(
				__METHOD__ . ': Error: $boldgrid_api_data is not an object.  $boldgrid_api_data: ' . print_r(
					$boldgrid_api_data, true ) );

			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'error_saving_key',
					'message' => $messages['error_saving_key'],
				)
			);
		} elseif ( 'OK' === $boldgrid_api_data->message ) {
			// Success.
			echo wp_json_encode(
				array(
					'success' => true,
					'message' => $messages['success'],
				)
			);

			// Update the API key option.
			update_option( 'boldgrid_api_key', $api_key_hash );
		} elseif ( 'Unauthorized' === $boldgrid_api_data->message ) {
			// Failure.
			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'invalid_key',
					'message' => $messages['invalid_key'],
				)
			);
		} else {
			// Failure.
			echo wp_json_encode(
				array(
					'success' => false,
					'error' => 'error_saving_key',
					'message' => $messages['error_saving_key'],
				)
			);
		}

		wp_die();
	}

	/**
	 * Hash API Key
	 *
	 * @param string $api_key
	 *
	 * @return string
	 */
	public static function hash_api_key( $api_key = null ) {
		// Trim the input:
		$api_key = trim( $api_key );

		// Convert to lowercase:
		$api_key = strtolower( $api_key );

		// Remove dashes/hyphens from the input API Key:
		$api_key = preg_replace( '#-#', '', $api_key );

		// Check for the correct number of chars (32):
		if ( 32 != strlen( $api_key ) ) {
			return null;
		}

		// Add dashes to the API Key:
		$api_key = rtrim( chunk_split( $api_key, 8, '-' ), '-' );

		// Hash the API Key:
		$api_key_hash = md5( $api_key );

		return $api_key_hash;
	}

	/**
	 * Set API Key hash
	 */
	public function set_api_key_hash() {
		// REQUIRED - we need authorization
		// Look in the config for the api_key
		$this->api_key_hash = isset( $this->configs['api_key'] ) ? $this->configs['api_key'] : null;
		// If it's not there, check $_REQUEST['key']
		$this->api_key_hash = ( null == $this->api_key_hash && isset( $_REQUEST['key'] ) ) ? sanitize_text_field(
			$_REQUEST['key'] ) : $this->api_key_hash;
	}

	/**
	 * Set asset user id
	 */
	public function set_asset_user_id() {
		$this->asset_user_id = ( isset( $_POST['asset_user_id'] ) ? intval(
			$_POST['asset_user_id'] ) : null );
	}

	/**
	 */
	public function set_is_preview_server() {
		$boldgrid_configs = $this->get_configs();
		$host = ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '';

		$this->is_preview_server = ( $boldgrid_configs['preview_server'] == "https://" . $host ||
			 $boldgrid_configs['author_preview_server'] == "https://" . $host );
	}

	/**
	 * Add to list of allowed attributes
	 *
	 * @param array $allowed
	 * @param array $context
	 * @return array
	 */
	public function filter_allowed_html( $allowed, $context ) {
		if ( is_array( $context ) ) {
			return $allowed;
		}

		if ( 'post' === $context || 'page' === $context ) {
			// Example case
			$allowed['img']['data-imhwpb-asset-id'] = true;
			$allowed['img']['data-imhwpb-built-photo-search'] = true;
			$allowed['img']['data-image-provider-id'] = true;
			$allowed['img']['data-id-from-provider'] = true;
		}

		return $allowed;
	}

	/**
	 * Allow Filter data attibutes
	 *
	 * @param string $context
	 * @return mixed
	 */
	public function wp_kses_allowed_html( $context = '' ) {
		global $allowedposttags, $allowedtags, $allowedentitynames;

		if ( is_array( $context ) )
			return apply_filters( 'wp_kses_allowed_html', $context, 'explicit' );

		switch ( $context ) {
			case 'post' :
				return apply_filters( 'wp_kses_allowed_html', $allowedposttags, $context );
				break;

			case 'user_description' :

			case 'pre_user_description' :
				$tags = $allowedtags;
				$tags['a']['rel'] = true;
				return apply_filters( 'wp_kses_allowed_html', $tags, $context );
				break;

			case 'strip' :
				return apply_filters( 'wp_kses_allowed_html', array (), $context );
				break;

			case 'entities' :
				return apply_filters( 'wp_kses_allowed_html', $allowedentitynames, $context );
				break;

			case 'data' :
			default :

				return apply_filters( 'wp_kses_allowed_html', $allowedtags, $context );
		}
	}

	/**
	 * Check PHP and WordPress versions for compatibility
	 */
	public function check_php_wp_versions() {
		// Check that PHP is installed at our required version or deactivate and die:
		$required_php_version = '5.3';
		if ( version_compare( phpversion(), $required_php_version, '<' ) ) {
			deactivate_plugins( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' );
			wp_die(
				'<p><center><strong>BoldGrid Inspirations</strong> requires PHP ' .
					 $required_php_version . ' or greater.</center></p>', 'Plugin Activation Error',
					array (
						'response' => 200,
						'back_link' => TRUE
					) );
		}

		// Check to see if WordPress version is installed at our required minimum or deactivate and
		// die:
		global $wp_version;
		$required_wp_version = '4.2';
		if ( version_compare( $wp_version, $required_wp_version, '<' ) ) {
			deactivate_plugins( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' );
			wp_die(
				'<p><center><strong>BoldGrid Inspirations</strong> requires WordPress ' .
					 $required_wp_version . ' or higher.</center></p>', 'Plugin Activation Error',
					array (
						'response' => 200,
						'back_link' => TRUE
					) );
		}
	}

	/**
	 * Is feedback opt-out.
	 *
	 * Check the BoldGrid Settings (a WP Option) to see if this site has opted-out for feedback.
	 *
	 * @since 1.0.9
	 *
	 * @return bool
	 */
	public static function is_feedback_optout() {
		// Get BoldGrid settings:
		$options = get_option( 'boldgrid_settings' );

		// Get feedback option:
		$boldgrid_feedback_optout = isset( $options['boldgrid_feedback_optout'] ) ? $options['boldgrid_feedback_optout'] : false;

		// Return the result:
		return ( bool ) $boldgrid_feedback_optout;
	}
}
