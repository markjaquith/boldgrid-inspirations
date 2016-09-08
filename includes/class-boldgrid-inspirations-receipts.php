<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Receipts
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * BoldGrid Receipts class.
 */
class Boldgrid_Inspirations_Receipts extends Boldgrid_Inspirations {
	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		if ( is_admin() ) {
				// Load Javascript and CSS.
				add_action( 'admin_menu',
					array(
						$this,
						'menu_transactions',
					)
					, 1001
				);

				add_action( 'admin_enqueue_scripts',
					array(
						$this,
						'admin_enqueue_transaction_menus',
					)
				);
		}
	}

	/**
	 * Add transaction history script for toplevel page.
	 *
	 * @see Boldgrid_Inspirations_Api::get_is_asset_server_available()
	 * @see Boldgrid_Inspirations_Utility::file_to_var()
	 *
	 * @param string $hook The hook.
	 * @return null
	 */
	public function admin_enqueue_transaction_menus( $hook ) {
		// Define an array of allowed hooks.
		$allowed_hooks = array(
			'toplevel_page_boldgrid-transactions',
			'boldgrid_page_boldgrid-transactions',
		);

		// If the hook is not for transactions, then abort.
		if ( ! in_array( $hook, $allowed_hooks, true ) ) {
			return;
		}

		wp_register_script( 'transaction-history',
			plugins_url(
				'/assets/js/transaction_history.js',
				BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
			),
			array(
				'jquery'
			),
			BOLDGRID_INSPIRATIONS_VERSION,
			true
		);

		// Check if the asset server is marked as available.
		$asset_server_available = Boldgrid_Inspirations_Api::get_is_asset_server_available();

		// Get the error message markup from the template file.
		$connection_error_message = Boldgrid_Inspirations_Utility::file_to_var(
			BOLDGRID_BASE_DIR . '/pages/templates/boldgrid-connection-issue.php'
		);

		// Prepare the data array for transaction history script localization.
		$connection_info = array(
			'assetServerAvailable' => $asset_server_available,
			'connectionErrorMessage' => $connection_error_message,
		);

		// Add the connection info to the transaction history script.
		wp_localize_script(
			'transaction-history',
			'connectionInfo',
			$connection_info
		);

		// Enqueue the transaction history script.
		wp_enqueue_script( 'transaction-history' );

		return;
	}

	/**
	 * Add transactions menu item or submenu item based on user's preference in settings.
	 *
	 * @see Boldgrid_Inspirations_Api::get_is_asset_server_available()
	 * @see Boldgrid_Inspirations_Admin_Notices::display_connection_notice()
	 *
	 * @return null
	 */
	public function menu_transactions() {
		// Check asset server availability.
		if ( ! Boldgrid_Inspirations_Api::get_is_asset_server_available() ) {
			// Notify that there is a connection issue.
			add_action(
				'admin_notices',
				Boldgrid_Inspirations_Admin_Notices::display_connection_notice()
			);

			return;
		}

		// Add menu page.
		add_menu_page(
			'Transactions',
			'Transactions',
			'manage_options',
			'boldgrid-transactions',
			array(
				$this,
				'page_receipts',
			),
			'none'
		);

		// Add Submenu item receipts.
		add_submenu_page(
			'boldgrid-transactions',
			'Receipts',
			'Receipts',
			'administrator',
			'boldgrid-transactions'
		);

		return;
	}

	/**
	 * Add submenu page for receipts.
	 */
	public function submenu_receipts() {
		// Add submenu receipts.
		add_submenu_page(
			'boldgrid-inspirations',
			'Receipts',
			'Receipts',
			'administrator',
			'boldgrid-transactions',
			array(
				$this,
				'page_receipts',
			)
		);
	}

	/**
	 * Menu callback for submenu page for receipts.
	 */
	public function page_receipts() {
		include BOLDGRID_BASE_DIR . '/pages/transaction_history.php';
	}
}
