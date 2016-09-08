<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_GridBlock_Sets
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations GridBlock Sets.
 *
 * This class manages GridBlock Sets.
 *
 * @since 1.0.10
 */
class Boldgrid_Inspirations_GridBlock_Sets {
	/**
	 * Construct.
	 *
	 * @since 1.0.10
	 *
	 * @param array $configs
	 */
	public function __construct( $configs ) {
		$this->configs = $configs;

		// Include our Kitchen Sink Class.
		include_once BOLDGRID_BASE_DIR .
			 '/includes/class-boldgrid-inspirations-gridblock-sets-kitchen-sink.php';
		$this->kitchen_sink_helper = new Boldgrid_Inspirations_GridBlock_Sets_Kitchen_Sink(
			$this->configs );
	}

	/**
	 * Get our GridBlock Sets.
	 *
	 * @since 1.0.10
	 *
	 * @return array Our GridBlock Sets.
	 */
	public function get() {
		$this->kitchen_sink = $this->kitchen_sink_helper->get();

		return array (
			'kitchen_sink' => $this->kitchen_sink
		);
	}
}