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
 * Functionality used for BoldGrid Inspiration loaded Gridblocks.
 *
 * Build kitchen sink and return the content used to build those pages. Also ties into a
 * filter in the editor plugin to return the content.
 *
 * @since 1.0.9
 * @link http://www.boldgrid.com.
 * @package Boldgrid_Inspiration.
 * @subpackage Boldgrid_Inspiration/includes.
 * @author BoldGrid <wpb@boldgrid.com>.
 */
class Boldgrid_Inspirations_Gridblock {

	/**
	 * Inspiration PLugin Configurations
	 *
	 * @var array
	 * @since 1.0.9
	 */
	protected $configs;

	/**
	 * Sets the Inspiration plugin configs into this helper class
	 *
	 * @var array configs Set of api configs
	 * @since 1.0.9
	 */
	public function __construct( $configs ) {
		$this->configs = $configs;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'wp_enqueue_scripts', array (
			$this,
			'enqueue_bootstrap_css'
		), 991 );

		// Add any gridblock stored on servers via api calls
		add_filter( 'boldgrid_dynamic_gridblocks', array (
			$this,
			'fetch_gridblocks'
		) );
	}

	/**
	 * Add the bootstrap class with the same handle as the theme
	 * If both the theme and the plugin include the same handle regardless of version,
	 * the themes style will take precedence
	 *
	 * @since 1.0.0
	 */
	public function enqueue_bootstrap_css() {
		// Just the Grid
		wp_enqueue_style( 'bootstrap-styles',
			plugin_dir_url( BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php' ) .
				 'assets/css/bootstrap/bootstrap.min.css', array (), '3.3.1' );
	}

	/**
	 * Get a "sub_cat_id" and a "theme_id" for parent category "Universal".
	 *
	 * If the user has not installed a site using Inspirations, we still want them to get the
	 * kitchen sink. We'll give them the kitchen sink for a subcategory within the "Universal"
	 * category. This method fetches the first sub category within "Universal", and also gets a
	 * theme_id that matches.
	 *
	 * @since 1.0.10
	 */
	public function get_universal_data() {
		// Below, we'll be making some calls to the asset server. If by chance any of the data we
		// get back is invalid, we'll set below "failsafe" data to return.
		$failsafe_return_data = array (
			// Universal > Default
			'sub_cat_id' => 33,
			// boldgrid-gridone
			'theme_id' => 40
		);

		// Set the api url for "get categories".
		$get_categories_url = $this->configs['asset_server'] .
			 $this->configs['ajax_calls']['get_categories'];

		// Reach out to the asset server and get a list of categories.
		$response = wp_remote_post( $get_categories_url,
			array (
				'body' => array (
					'key' => $this->configs['api_key'],
					'site_hash' => $this->configs['site_hash']
				),
				'timeout' => 45
			) );

		// Decode our response.
		$response_body = wp_remote_retrieve_body( $response );
		$response_decoded = json_decode( $response_body, true );

		// If we don't have any categories, return our failsafe data.
		if ( empty( $response_decoded['result']['data']['categories'] ) ) {
			return $failsafe_return_data;
		}

		// Loop through all of the categories. When we get to the 'Universal' category, set
		// $sub_cat_id to the first sub category in the array.
		foreach ( $response_decoded['result']['data']['categories'] as $sub_category_data ) {
			if ( 'Universal' == $sub_category_data['name'] ) {
				$sub_cat_id = $sub_category_data['subcategories'][0]['id'];
			}
		}

		// If we have an invalid "sub_cat_id", return our failsafe data.
		if ( empty( $sub_cat_id ) || ! is_numeric( $sub_cat_id ) ) {
			return $failsafe_return_data;
		}

		// Set the api url for "get themes".
		$get_theme_ids_url = $this->configs['asset_server'] .
			 $this->configs['ajax_calls']['get_theme_ids'];

		// Reach out to the asset server and get a list of themes for our sub category.
		$response = wp_remote_post( $get_theme_ids_url,
			array (
				'body' => array (
					'cat_id' => $sub_cat_id,
					'key' => $this->configs['api_key'],
					'site_hash' => $this->configs['site_hash']
				),
				'timeout' => 45
			) );

		// Decode our response.
		$response_body = wp_remote_retrieve_body( $response );
		$response_decoded = json_decode( $response_body, true );

		// If we don't have a valid theme, return our failsafe data.
		if ( empty( $response_decoded['result']['data']['themes'][0] ) ||
			 ! is_numeric( $response_decoded['result']['data']['themes'][0] ) ) {
			return $failsafe_return_data;
		}

		// Grab the first theme in the list.
		$theme_id = $response_decoded['result']['data']['themes'][0];

		return array (
			'sub_cat_id' => $sub_cat_id,
			'theme_id' => $theme_id
		);
	}

	/**
	 * Gets pages that will serve as Gridblocks in the BoldGrid Editor Plugin.
	 * Attached to main filter.
	 *
	 * @since 1.0.9
	 * @param array $gridblocks
	 * @return array An Array of page that will be parsed for gridblocks
	 */
	public function fetch_gridblocks( $gridblocks ) {
		$kitchen_sink_pages = $this->fetch_kitchen_sink_pages();
		$gridblocks = array_merge( $gridblocks, $kitchen_sink_pages );
		return $gridblocks;
	}

	/**
	 * Gets the html that was created through a building a profile.
	 *
	 * Makes an api call to the preview server and returns post content.
	 *
	 * @since 1.0.9
	 * @param string $site_url
	 *        	Url of the build profile
	 * @return string Result of the API call
	 */
	public function fetch_html( $site_url ) {
		$url = $this->configs['preview_server'] . $this->configs['ajax_calls']['get-site-content'];

		$request_params = array (
			'url' => $site_url
		);

		return wp_remote_retrieve_body(
			wp_remote_post( $url, array (
				'body' => $request_params
			) ) );
	}

	/**
	 * Rebuild the kitchen sink based on the users original selections.
	 * Returns build profile data
	 *
	 * @since 1.0.9
	 * @return array Standard Build Profile data
	 */
	public function build_kitchen_sink() {
		// Set the PHP max_execution_time to 60 seconds (1 minute):
		@ini_set( 'max_execution_time', 60 );

		$build_profile_data = array ();
		$boldgrid_install_options = get_option( 'boldgrid_install_options' );

		$url = $this->configs['asset_server'] . $this->configs['ajax_calls']['get_layouts'];

		// Set our "sub_cat_id" and "theme_id".
		if ( false === $boldgrid_install_options ) {
			$universal_data = $this->get_universal_data();
			$theme_id = $universal_data['theme_id'];
			$sub_cat_id = $universal_data['sub_cat_id'];
		} else {
			$theme_id = $boldgrid_install_options['theme_id'];
			$sub_cat_id = $boldgrid_install_options['subcategory_id'];
		}

		$request_params = array (
			'build_kitchen_sink' => 1, // Hard code to true
			'theme_id' => $theme_id,
			'sub_cat_id' => $sub_cat_id,
			'key' => $this->configs['api_key'],
			'site_hash' => $this->configs['site_hash']
		);

		$response = wp_remote_post( $url,
			array (
				'body' => $request_params,
				'timeout' => 45
			) );

		$response_body = wp_remote_retrieve_body( $response );
		$response_decoded = json_decode( $response_body, true );

		if ( ! empty( $response_decoded['result']['data'] ) ) {
			$build_profile_data = $response_decoded['result']['data'];
		}

		return $build_profile_data;
	}

	/**
	 * Retrieve an array of pages that the user would have received had the built the
	 * kitchen sink for their category.
	 *
	 * @since 1.0.9
	 * @return $page_data_decoded array
	 */
	public function fetch_kitchen_sink_pages() {
		$build_profile_data = $this->build_kitchen_sink();
		$build_profile_id = ! empty( $build_profile_data['theme']['id'] ) ? $build_profile_data['theme']['id'] : '';
		$site_url = ! empty( $build_profile_data['theme']['previewUrl'] ) ? $build_profile_data['theme']['previewUrl'] : '';

		$html_json = $this->fetch_html( $site_url );
		$page_data_decoded['build_profile'] = array (
			'id' => $build_profile_id,
			'url' => $site_url
		);
		$page_data_decoded = array_merge( $page_data_decoded,
			$this->json_decode_response( $html_json ) );

		return $page_data_decoded;
	}

	/**
	 * Json decode the return of an API call.
	 *
	 * @since 1.0.9
	 * @param string $html_json
	 * @return $html array
	 */
	public function json_decode_response( $html_json ) {
		$page_data_decoded = array ();
		if ( $html_json ) {
			$json_array = json_decode( $html_json, true );
			if ( ! empty( $json_array['success'] ) && ! empty( $json_array['data'] ) ) {
				$page_data_decoded = $json_array['data'];
			}
		}

		return $page_data_decoded;
	}
}
