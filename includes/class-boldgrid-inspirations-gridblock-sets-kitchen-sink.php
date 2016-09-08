<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_GridBlock_Sets_Kitchen_Sink
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations GridBlock Sets Kitchen Sink.
 *
 * @since 1.0.10
 */
class Boldgrid_Inspirations_GridBlock_Sets_Kitchen_Sink {
	/*
	 * Option name for "fetching kitchen sink status".
	 * @since 1.0.10
	 * @access public
	 * @var string $option_name_fetching
	 */
	public $option_name_fetching = 'boldgrid_inspirations_fetching_kitchen_sink_status';

	/*
	 * Option name for saving kitchen sink data.
	 * @since 1.0.10
	 * @access public
	 * @var array $option_name_kitchen_sink
	 */
	public $option_name_kitchen_sink = 'boldgrid_inspirations_kitchen_sink';

	/**
	 * Constructor.
	 *
	 * @since 1.0.10
	 *
	 * @param array $configs
	 */
	public function __construct( $configs ) {
		$this->configs = $configs;

		// For quick debugging. Uncomment to force fresh data.
		// delete_transient( 'boldgrid_inspirations_kitchen_sink' );
		// $this->set_fetching_status('delete');
	}

	/**
	 * Only allow post_type of pages at this time.
	 *
	 * @since 1.0.10
	 */
	public function filter_post_type() {
		foreach ( $this->kitchen_sink['data']['pages'] as $k => $v ) {
			if ( 'page' != $v['preview_data']['post_type'] ) {
				unset( $this->kitchen_sink['data']['pages'][$k] );
			}
		}
	}

	/**
	 * Remove "Contact us" pages.
	 *
	 * @since 1.0.10
	 */
	public function filter_contact_us() {
		foreach ( $this->kitchen_sink['data']['pages'] as $k => $v ) {
			if ( 'contact us' == strtolower( $v['preview_data']['post_title'] ) ) {
				unset( $this->kitchen_sink['data']['pages'][$k] );
			}
		}
	}

	/**
	 * Get our kitchen sink.
	 *
	 * @since 1.0.10
	 *
	 * @return array $this->kitchen_sink.
	 */
	public function get() {
		// If another process is already fetching the kitchen sink, abort.
		if ( true == $this->is_fetching() ) {
			return array (
				'status' => 'fetching',
				'valid' => false,
				'freshness' => 'fresh'
			);
		}

		// Try to get our kitchen sink data from transient.
		$this->kitchen_sink = get_transient( $this->option_name_kitchen_sink );

		// If the transient has expired:
		if ( false === $this->kitchen_sink || empty( $this->kitchen_sink ) ) {
			// Log that we are fetching the kitchen sink.
			$this->set_fetching_status( 'fetching' );

			// Get the kitchen sink.
			$boldgrid_inspirations_gridblock = new Boldgrid_Inspirations_Gridblock( $this->configs );
			$boldgrid_inspirations_gridblock->add_hooks();
			$this->kitchen_sink['data'] = $boldgrid_inspirations_gridblock->fetch_kitchen_sink_pages();

			// Validate the kitchen sink.
			if ( ! $this->is_valid() ) {
				return array (
					'valid' => false,
					'status' => 'fetched_but_invalid'
				);
			}

			$this->filter_post_type();
			$this->filter_contact_us();

			// Reindex ['pages'].
			$this->kitchen_sink['data']['pages'] = array_values(
				$this->kitchen_sink['data']['pages'] );

			// Remove all shortcodes from the kichen sink.
			foreach ( $this->kitchen_sink['data']['pages'] as $page_key => $page_data ) {
				$this->kitchen_sink['data']['pages'][$page_key]['preview_data']['post_content'] = strip_shortcodes(
					$this->kitchen_sink['data']['pages'][$page_key]['preview_data']['post_content'] );
			}

			// Then update the transient.
			set_transient( $this->option_name_kitchen_sink, $this->kitchen_sink, WEEK_IN_SECONDS );

			// Remove our log, we are no longer fetching the kitchen sink data.
			$this->set_fetching_status( 'delete' );
		}

		return $this->kitchen_sink;
	}

	/**
	 * Are we currently fetching the kitchen sink?
	 *
	 * @since 1.0.10
	 *
	 * @return boolean
	 */
	public function is_fetching() {
		// If another process is trying to fetch the kitchen sink, the number of seconds to wait for
		// that process to finish before trying again.
		$fetching_timeout = 120;

		// Get the current status.
		$fetching_status = get_option( $this->option_name_fetching );

		if ( is_array( $fetching_status ) ) {
			$seconds_since_status = time() - $fetching_status['time'];

			if ( 'fetching' == $fetching_status['status'] &&
				 $seconds_since_status < $fetching_timeout ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Set the 'fetching status' of the kitchen sink.
	 *
	 * @since 1.0.10
	 *
	 * @param string $status
	 */
	public function set_fetching_status( $status ) {
		switch ( $status ) {
			case 'fetching' :
				update_option( $this->option_name_fetching,
					array (
						'status' => 'fetching',
						'time' => time()
					) );
				break;
			case 'delete' :
				delete_option( $this->option_name_fetching );
				break;
			default:
				// We are only expecting 'fetching' or 'delete'.
				// If any other $status, do nothing.
				break;
		}
	}

	/**
	 * Is the kitchen sink valid?
	 *
	 * @since 1.0.10
	 *
	 * @return boolean
	 */
	public function is_valid() {
		if ( empty( $this->kitchen_sink['data']['pages'] ) ) {
			return false;
		} else {
			return true;
		}
	}
}