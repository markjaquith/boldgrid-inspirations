<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Config
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Config class.
 */
class Boldgrid_Inspirations_Config {

	/**
	 * Class property for asset server availability
	 *
	 * @since 1.2.2
	 * @access private
	 * @var array
	 * @static
	 */
	private static $configs = array();


	/**
	 * Check if a feature has been enabled for a users release channel.
	 *
	 * @since 1.10
	 *
	 * @return boolean Whether or not the feature is enabled on given branch.
	 */
	public static function has_feature( $feature, $configs ) {
		$has_feature = false;
		$channels = ! empty( $configs['features'][ $feature ] ) ? $configs['features'][ $feature ] : false;

		if ( $channels ) {
			$boldgrid_settings = get_option( 'boldgrid_settings', array () );
			$release_channel = ! empty( $boldgrid_settings['release_channel'] ) ?
				$boldgrid_settings['release_channel'] : 'stable';

			if ( in_array( $release_channel, $channels ) ) {
				$has_feature = true;
			}

		}

		return $has_feature;
	}

	/**
	 * Get formated configurations.
	 *
	 * This static function is used by boldgrid-inspirations and other BoldGrid plugins.
	 *
	 * @return array
	 */
	public static function get_format_configs() {
		// If configs were already read, then just return the array.
		if ( ! empty( self::$configs)) {
			return self::$configs;
		}

		// Set the configuration directory.
		$config_dir = BOLDGRID_BASE_DIR . '/includes/config';

		// Set the path to the global configuration file.
		$global_configs = require $config_dir . '/config.plugin.php';

		// Initialize $local_configs.
		$local_configs = array();

		// If local file exists, then read it.
		if ( file_exists( $local_config_filename = $config_dir . '/config.local.php' ) ) {
			$local_configs = require $local_config_filename;
		}

		// If the user has an api key stored in their database, then set it as the global api_key.
		$api_key_from_database = get_option( 'boldgrid_api_key' );

		if ( ! empty( $api_key_from_database ) ) {
			$global_configs['api_key'] = $api_key_from_database;
		}

		// Check for site hash in WP Options, if present, add to config array.
		$site_hash = get_option( 'boldgrid_site_hash' );

		if ( ! empty( $site_hash ) ) {
			$global_configs['site_hash'] = $site_hash;
		}

		// Add the siteurl to the array.
		$global_configs['site_url'] = get_site_url();

		// Merge global and local configs.
		if ( ! empty( $local_configs ) ) {
			$formated_configs = array_merge( $global_configs, $local_configs );
		} else {
			$formated_configs = $global_configs;
		}

		// Add boldgrid_settings to our configs.
		// @since 1.0.10
		if ( ! isset( $formated_configs['settings'] ) ) {
			$formated_configs['settings'] = get_option( 'boldgrid_settings' );
		}

		// Save the config array.
		self::$configs = $formated_configs;

		// Return the configuration array.
		return $formated_configs;
	}
}
