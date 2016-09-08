<?php
/**
 * BoldGrid Source Code
 *
 * @package Boldgrid_Inspirations_Tutorials
 * @copyright BoldGrid.com
 * @version $Id$
 * @author BoldGrid.com <wpb@boldgrid.com>
 */

/**
 * The Boldgrid Inspirations Tutorials class.
 */
 class Boldgrid_Inspirations_Tutorials {
	/**
	 * Add hooks.
	 */
	public function add_hooks() {
		add_action( 'admin_menu',
			array(
				$this,
				'boldgrid_tutorials_menu',
			)
		);

		// if in admin add CSS and JS to dashboard for widget and styling
		add_action( 'admin_enqueue_scripts',
			array(
				$this,
				'enqueue_script_tutorials',
			)
		);
	}

	/**
	 * Add the menu item labeled 'Tutorials.'.
	 *
	 * @since 0.21
	 */
	public function boldgrid_tutorials_menu() {
		/**
		 * Add the menu page for BoldGrid Tutorials.
		 *
		 * This function will create a new top level menu item with the title 'Tutorials'.
		 *
		 * @see add_menu_page().
		 * @link https://developer.wordpress.org/reference/functions/add_menu_page/
		 *
		 * @var $page_title The text to be displayed in the title tag of the page.
		 * @var $menu_title This is text that will displayed on the menu.
		 * @var $capability The capability required for this menu to be displayed to the user.
		 * @var $menu_slug The slug name to refer to this menu by. It should be a unique name.
		 * @var $function The callback.
		 *
		 * @since 1.0.0
		 */
		/* --- --- --- --- --- --- --- */
		$page_title = 'Tutorials';
		/* This will add the top level */
		$menu_title = 'Tutorials';
		/* menu item to the WordPress */
		$capability = 'manage_options';
		/* admin menu. */
		$menu_slug = 'boldgrid-tutorials';
		/* --- --- --- --- --- --- --- */

		$function = array(
			$this,
			'boldgrid_tutorials_page',
		);

		$icon_url = 'dashicons-welcome-learn-more';

		$position = '63.32';

		add_menu_page(
			$page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position
		);
	}

	/**
	 * BoldGrid tutorials page.
	 */
	public function boldgrid_tutorials_page() {
		include BOLDGRID_BASE_DIR . '/pages/boldgrid-tutorials.php';
	}

	/**
	 * Add CSS and JS to admin dashboard.
	 */
	public function enqueue_script_tutorials( $hook ) {
		if ( 'toplevel_page_boldgrid-tutorials' === $hook ) {
			wp_register_style(
				'boldgrid-tutorials-css',
				plugins_url(
					'/assets/css/boldgrid-tutorials.css',
					BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
				),
				array(),
				BOLDGRID_INSPIRATIONS_VERSION
			);

			wp_enqueue_style( 'boldgrid-tutorials-css' );

			wp_enqueue_script(
				'boldgrid-tutorials-js',
				plugins_url(
					'/assets/js/boldgrid-tutorials.js',
					BOLDGRID_BASE_DIR . '/boldgrid-inspirations.php'
				),
				array(
					'handlebars',
				),
				BOLDGRID_INSPIRATIONS_VERSION,
				true
			);
		}
	}
}
