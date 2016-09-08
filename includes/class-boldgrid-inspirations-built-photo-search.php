<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Built_Photo_Search
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * The BoldGrid Built Photo Search class.
 */
class Boldgrid_Inspirations_Built_Photo_Search extends Boldgrid_Inspirations {
	/**
	 * Reach out to the asset server and get photo data.
	 *
	 * The data should come back in one of two sets:
	 * 1: An $image_provider_id and an $id_from_provider
	 * 2. An $imgr_image_id
	 *
	 * @param array $params Parameters.
	 *
	 * @return array|bool
	 */
	public function get_photo_data( $params ) {
		// Get configs.
		$boldgrid_configs = $this->get_configs();

		// Set the URL address.
		$url = $boldgrid_configs['asset_server'] . $boldgrid_configs['ajax_calls']['bps-get-photo'];

		// Make a call to the asset server.
		$response = wp_remote_post( $url, array (
			'body' => $params
		) );

		// FAIL if our $response is an error.
		if ( is_wp_error( $response ) ) {
			// Log.
			error_log(
				__METHOD__ . ': Error: Received WP Error response.  ' .
				print_r(
					array(
						'Server' => 'WordPress',
						'$url' => $url,
						'$params' => $params,
						'method' => 'POST',
						'$response' => $response,
					), true
				)
			);

			return false;
		}

		// FAIL if $response is empty.
		if ( empty( $response ) ) {
			// Log.
			error_log(
				__METHOD__ . ': Error: Received empty response.  ' .
				print_r(
					array(
						'Server' => 'WordPress',
						'$url' => $url,
						'$params' => $params,
						'method' => 'POST',
						'$response' => $response,
					), true
				)
			);

			return false;
		}

		/**
		 * Process the response we received from the ASSET server.
		 */
		$body = json_decode( $response['body'], true );

		$return = array(
			'image_provider_id' => isset( $body['result']['data']['image_provider_id'] ) ? $body['result']['data']['image_provider_id'] : null,
			'id_from_provider' => isset( $body['result']['data']['id_from_provider'] ) ? $body['result']['data']['id_from_provider'] : null,
			'imgr_image_id' => isset( $body['result']['data']['imgr_image_id'] ) ? $body['result']['data']['imgr_image_id'] : null,
		);

		/**
		 * Ensure we have valid data.
		 *
		 * One of the following must be true:
		 *
		 * 1. We have both an image_provider_id and an id_from_provider.
		 * 2. We have an imgr_image_id.
		 */
		if ( ! empty( $return['imgr_image_id'] ) ||
		! ( empty( $return['image_provider_id'] ) && empty( $return['id_from_provider'] ) )
		) {
			// Return the data.
			return $return;
		} else {
			// Log.
			error_log(
				__METHOD__ . ': Error: Invalid data in response.  ' .
				print_r(
					array(
						'$url' => $url,
						'$params' => $params,
						'$body' => $body,
						'$return' => $return,
					), true
				)
			);

			return false;
		}
	}
}
