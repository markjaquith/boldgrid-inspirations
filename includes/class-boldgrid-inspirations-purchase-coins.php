<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Purchase_Coins
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Inspirations Purchase Coins class.
 */
class Boldgrid_Inspirations_Purchase_Coins extends Boldgrid_Inspirations {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		if ( is_admin() ) {
			$boldgrid_menu_options = get_option( 'boldgrid_settings' );

			( 1 == $boldgrid_menu_options['boldgrid_menu_option'] ? add_action( 'admin_menu',
				array (
					$this,
					'menu_purchase_coins'
				), 1238 ) : add_action( 'admin_menu',
				array (
					$this,
					'menu_purchase_coins'
				), 1456 ) );
		}
	}

	/**
	 * Purchase Coins submenu item.
	 */
	public function menu_purchase_coins() {
		$boldgrid_settings = get_option( 'boldgrid_settings' );

		( 1 == $boldgrid_settings['boldgrid_menu_option'] ? add_submenu_page(
			'boldgrid-transactions', 'Purchase Coins', 'Purchase Coins', 'administrator',
			'boldgrid-purchase-coins', array (
				$this,
				'page_purchase_coins'
			) ) : add_submenu_page( 'boldgrid-inspirations', 'Purchase Coins', 'Purchase Coins',
			'administrator', 'boldgrid-purchase-coins',
			array (
				$this,
				'page_purchase_coins'
			) ) );
	}

	/**
	 * Menu callback.
	 */
	public function page_purchase_coins() {
		include BOLDGRID_BASE_DIR . '/pages/purchase_coins.php';
	}
}
